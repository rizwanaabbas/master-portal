<?php

namespace App\Modules\Fireblocks\Services;

use App\Models\FireblocksTransaction;
use App\Modules\Fireblocks\Models\FireblocksUser;
use Fireblocks\Sdk\Facades\Fireblocks;
use Illuminate\Support\Facades\Log;

class FireblocksTransactionActorService
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function apply(FireblocksTransaction $record, array $payload = []): void
    {
        $extracted = $this->extract($this->flattenPayload($record, $payload));

        if ($extracted['initiator'] !== null && $extracted['initiator'] !== '') {
            $record->initiator_user_id = $extracted['initiator'];
        }

        if ($extracted['signers'] !== []) {
            $record->signed_by_user_ids = $extracted['signers'];
        }

        if ($extracted['approvers'] !== []) {
            $record->tap_approver_user_ids = $extracted['approvers'];
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{initiator: ?string, signers: array<int, string>, approvers: array<int, string>}
     */
    public function extract(array $payload): array
    {
        $initiator = $this->normalizeUserId($payload['createdBy'] ?? $payload['created_by'] ?? null);

        $signers = [];
        foreach ($this->asList($payload['signedBy'] ?? $payload['signed_by'] ?? null) as $item) {
            $id = $this->normalizeUserId($item);
            if ($id !== null) {
                $signers[] = $id;
            }
        }

        $approvers = [];
        $auth = $payload['authorizationInfo'] ?? $payload['authorization_info'] ?? null;
        if (is_array($auth)) {
            foreach ($this->asList($auth['groups'] ?? null) as $group) {
                foreach ($this->groupUserEntries($group) as $userId => $statusRaw) {
                    if (!$this->isTapApproverStatus($statusRaw)) {
                        continue;
                    }
                    $id = $this->normalizeUserId($userId);
                    if ($id !== null) {
                        $approvers[] = $id;
                    }
                }
            }
        }

        $signers = array_values(array_unique($signers));
        $approvers = array_values(array_unique($approvers));
        if ($approvers === [] && $signers !== []) {
            $approvers = $signers;
        }

        return [
            'initiator' => $initiator,
            'signers' => $signers,
            'approvers' => $approvers,
        ];
    }

    public function enrichFromApi(FireblocksTransaction $record): bool
    {
        $txId = trim((string) $record->fireblocks_tx_id);
        if ($txId === '' || strpos($txId, 'local-') === 0) {
            return false;
        }

        try {
            $api = Fireblocks::get('/v1/transactions/' . $txId);
        } catch (\Throwable $e) {
            Log::warning('Fireblocks actor enrich failed', [
                'fireblocks_tx_id' => $txId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }

        if (!is_array($api) || $api === []) {
            return false;
        }

        $existing = $this->asArray($record->response_data);
        foreach (['signedBy', 'createdBy', 'authorizationInfo', 'rejectedBy'] as $key) {
            if (array_key_exists($key, $api)) {
                $existing[$key] = $api[$key];
            }
        }
        $record->response_data = $existing;
        $this->apply($record, $api);

        return true;
    }

    /**
     * @return array{initiator: string, signers: string, approvers: string}
     */
    public function labelsFor(FireblocksTransaction $record): array
    {
        return [
            'initiator' => $this->formatIds($this->idsFor($record, 'initiator')),
            'signers' => $this->formatIds($this->idsFor($record, 'signers')),
            'approvers' => $this->formatIds($this->idsFor($record, 'approvers')),
        ];
    }

    public function labelsHtml(FireblocksTransaction $record, string $field): string
    {
        $ids = $this->idsFor($record, $field);
        if ($ids === []) {
            return '<span class="text-muted">—</span>';
        }

        $users = $this->userMap($ids);
        $parts = [];
        foreach ($ids as $id) {
            if (isset($users[$id])) {
                $parts[] = $users[$id]->badgeHtml();
            } else {
                $parts[] = FireblocksUser::unmappedBadgeHtml($id);
            }
        }

        return implode(' ', $parts);
    }

    /**
     * @return array<int, string>
     */
    public function idsFor(FireblocksTransaction $record, string $field): array
    {
        if ($field === 'initiator') {
            $ids = $this->idList($record->initiator_user_id);
        } elseif ($field === 'signers') {
            $ids = $this->idList($record->signed_by_user_ids);
        } else {
            $ids = $this->idList($record->tap_approver_user_ids);
        }

        if ($ids !== []) {
            return $ids;
        }

        $extracted = $this->extract($this->flattenPayload($record));
        if ($field === 'initiator') {
            return $this->idList($extracted['initiator']);
        }
        if ($field === 'signers') {
            return $extracted['signers'];
        }

        return $extracted['approvers'];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function flattenPayload(FireblocksTransaction $record, array $payload = []): array
    {
        $stored = $this->asArray($record->response_data ?? null);
        $layers = [];

        foreach (['webhook_bootstrap', 'data', 'transaction'] as $nestedKey) {
            if (!isset($stored[$nestedKey]) || !is_array($stored[$nestedKey])) {
                continue;
            }
            $layers[] = $stored[$nestedKey];
            if (isset($stored[$nestedKey]['data']) && is_array($stored[$nestedKey]['data'])) {
                $layers[] = $stored[$nestedKey]['data'];
            }
        }

        $layers[] = $stored;
        $layers[] = $payload;

        $merged = [];
        foreach ($layers as $layer) {
            if (!is_array($layer)) {
                continue;
            }
            $merged = $this->mergePreferFilled($merged, $layer);
        }

        return $merged;
    }

    /**
     * @param  array<string, mixed>  $base
     * @param  array<string, mixed>  $overlay
     * @return array<string, mixed>
     */
    protected function mergePreferFilled(array $base, array $overlay): array
    {
        foreach ($overlay as $key => $value) {
            if ($this->isEmptyActorValue($value) && !$this->isEmptyActorValue($base[$key] ?? null)) {
                continue;
            }
            $base[$key] = $value;
        }

        return $base;
    }

    /**
     * @param  mixed  $value
     */
    protected function isEmptyActorValue($value): bool
    {
        return $value === null || $value === '' || $value === [];
    }

    /**
     * @return array<int, string>
     */
    public function idList($value): array
    {
        if (is_string($value) && $value !== '') {
            $trimmed = trim($value);
            if ($trimmed !== '' && ($trimmed[0] === '[' || $trimmed[0] === '{')) {
                $decoded = json_decode($trimmed, true);
                $value = is_array($decoded) ? $decoded : [$trimmed];
            } else {
                return [$trimmed];
            }
        }
        if (!is_array($value) || $value === []) {
            return [];
        }

        $ids = [];
        $isList = array_keys($value) === range(0, count($value) - 1);
        if ($isList) {
            foreach ($value as $item) {
                $id = $this->normalizeUserId($item);
                if ($id !== null) {
                    $ids[] = $id;
                }
            }
        } else {
            foreach ($value as $key => $item) {
                $id = $this->normalizeUserId($key);
                if ($id === null) {
                    $id = $this->normalizeUserId($item);
                }
                if ($id !== null) {
                    $ids[] = $id;
                }
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * @param  array<int, string>  $ids
     */
    public function formatIds(array $ids): string
    {
        if ($ids === []) {
            return '—';
        }

        $users = $this->userMap($ids);
        $parts = [];
        foreach ($ids as $id) {
            $parts[] = isset($users[$id]) ? $users[$id]->displayName() : $id;
        }

        return implode(', ', $parts);
    }

    /**
     * @param  array<int, string>  $ids
     * @return array<string, FireblocksUser>
     */
    protected function userMap(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return FireblocksUser::query()
            ->whereIn('fireblocks_user_id', $ids)
            ->get()
            ->keyBy('fireblocks_user_id')
            ->all();
    }

    /**
     * @return array<string|int, mixed>
     */
    protected function groupUserEntries($group): array
    {
        if (!is_array($group)) {
            return [];
        }

        $users = $group['users'] ?? [];
        if (!is_array($users) || $users === []) {
            return [];
        }

        if (array_keys($users) !== range(0, count($users) - 1)) {
            return $users;
        }

        $entries = [];
        foreach ($users as $index => $user) {
            if (is_array($user)) {
                $id = $user['id'] ?? $user['userId'] ?? $index;
                $entries[$id] = $user['approval'] ?? $user['status'] ?? $user;
            } else {
                $entries[(string) $user] = 'APPROVED';
            }
        }

        return $entries;
    }

    /**
     * @param  mixed  $statusRaw
     */
    protected function isTapApproverStatus($statusRaw): bool
    {
        $status = $this->approvalStatus($statusRaw);
        if ($status === '') {
            return true;
        }

        return !in_array($status, ['REJECTED', 'NA', 'N/A', 'DECLINED'], true);
    }

    /**
     * @param  mixed  $value
     */
    protected function approvalStatus($value): string
    {
        if (is_array($value)) {
            $value = $value['approval'] ?? $value['status'] ?? $value['value'] ?? '';
        }
        if (!is_scalar($value)) {
            return '';
        }

        return strtoupper(trim((string) $value));
    }

    /**
     * @return array<string, mixed>
     */
    protected function asArray($value): array
    {
        if (is_array($value)) {
            return $value;
        }
        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);

            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    /**
     * @return array<int, mixed>
     */
    protected function asList($value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        return is_array($value) ? array_values($value) : [$value];
    }

    protected function normalizeUserId($value): ?string
    {
        if (is_array($value)) {
            $value = $value['id'] ?? $value['userId'] ?? $value['user_id'] ?? null;
        }
        if (!is_scalar($value)) {
            return null;
        }
        $id = trim((string) $value);

        return $id !== '' ? $id : null;
    }
}
