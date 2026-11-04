<?php

namespace App\Modules\FireblocksTransactionsMonitoring\Services;

use App\Models\AccountTransaction;
use App\Models\FireblocksTransaction;
use App\Modules\Fireblocks\Helpers\FireblocksStatusHelper;

class TransactionRetryChainService
{
    public function getChain(FireblocksTransaction $record): array
    {
        $members = $this->loadChainMembers($record);

        if ($members->isEmpty()) {
            return [$this->formatChainEntry($record, true)];
        }

        return $members->map(fn ($tx) => $this->formatChainEntry(
            $tx,
            $tx->fireblocks_tx_id === $record->fireblocks_tx_id
        ))->values()->all();
    }

    /**
     * Hierarchical retry / resubmit chain for the detail modal.
     *
     * @return array{root_id: string, total: int, tree: array<string, mixed>}|null
     */
    public function getChainTree(FireblocksTransaction $record): ?array
    {
        $flat = $this->getChain($record);
        if (empty($flat)) {
            return null;
        }

        $rootId = $record->root_fireblocks_tx_id ?: $record->fireblocks_tx_id;
        if (!collect($flat)->contains(fn ($e) => $e['fireblocks_tx_id'] === $rootId)) {
            $rootId = $flat[0]['fireblocks_tx_id'];
        }

        $tree = $this->buildTree($flat, $rootId);
        if ($tree === null) {
            return null;
        }

        return [
            'root_id' => $rootId,
            'total' => count($flat),
            'tree' => $tree,
        ];
    }

    public function getChainLength(FireblocksTransaction $record): int
    {
        return $this->loadChainMembers($record)->count();
    }

    public function getChainPosition(FireblocksTransaction $record): int
    {
        $members = $this->loadChainMembers($record);
        foreach ($members->values() as $index => $tx) {
            if ($tx->fireblocks_tx_id === $record->fireblocks_tx_id) {
                return $index + 1;
            }
        }

        return 1;
    }

    public function gridBadgeHtml(FireblocksTransaction $record): string
    {
        $len = $this->getChainLength($record);
        $txId = e($record->fireblocks_tx_id);

        if ($len > 1) {
            $pos = $this->getChainPosition($record);

            return '<button type="button" class="btn btn-xs btn-outline-info btn-view-tx btn-chain-view" data-tx-id="'
                . $txId . '" title="View resubmit chain">Retry ' . $pos . '/' . $len . '</button>';
        }

        $parent = trim((string) ($record->parent_fireblocks_tx_id ?? ''));
        $replacedBy = trim((string) ($record->replaced_by_fireblocks_tx_id ?? ''));

        if ($parent !== '' || $replacedBy !== '') {
            return '<button type="button" class="btn btn-xs btn-outline-secondary btn-view-tx btn-chain-view" data-tx-id="'
                . $txId . '" title="View resubmit chain">Chain</button>';
        }

        return '<span class="text-muted">—</span>';
    }

    protected function loadChainMembers(FireblocksTransaction $record)
    {
        $rootId = $record->root_fireblocks_tx_id ?: $record->fireblocks_tx_id;

        return FireblocksTransaction::query()
            ->where(function ($q) use ($rootId) {
                $q->where('root_fireblocks_tx_id', $rootId)
                    ->orWhere('fireblocks_tx_id', $rootId);
            })
            ->orderBy('created_at')
            ->get();
    }

    /**
     * @param  array<int, array<string, mixed>>  $entries
     */
    protected function buildTree(array $entries, string $rootId): ?array
    {
        if (count($entries) === 0) {
            return null;
        }

        if (count($entries) === 1) {
            return $this->wrapTreeNode($entries[0], [], 0, true);
        }

        $byId = [];
        foreach ($entries as $entry) {
            $byId[$entry['fireblocks_tx_id']] = $entry;
        }

        $childrenMap = [];
        foreach ($entries as $entry) {
            $id = $entry['fireblocks_tx_id'];
            if ($id === $rootId) {
                continue;
            }

            $parentId = $this->resolveParentId($entry, $byId, $rootId);
            $childrenMap[$parentId][] = $entry;
        }

        foreach ($childrenMap as &$children) {
            usort($children, fn ($a, $b) => strcmp($a['created_at'] ?? '', $b['created_at'] ?? ''));
        }
        unset($children);

        $rootEntry = $byId[$rootId] ?? $entries[0];

        return $this->wrapTreeNode(
            $rootEntry,
            $this->buildChildNodes($rootEntry['fireblocks_tx_id'], $childrenMap, 1),
            0,
            true
        );
    }

    /**
     * @param  array<string, array<string, mixed>>  $byId
     */
    protected function resolveParentId(array $entry, array $byId, string $rootId): string
    {
        $parent = $entry['parent_fireblocks_tx_id'] ?? null;
        if ($parent && isset($byId[$parent])) {
            return $parent;
        }

        $id = $entry['fireblocks_tx_id'];
        foreach ($byId as $candidate) {
            if (($candidate['replaced_by_fireblocks_tx_id'] ?? null) === $id) {
                return $candidate['fireblocks_tx_id'];
            }
        }

        return $rootId;
    }

    /**
     * @param  array<string, array<int, array<string, mixed>>>  $childrenMap
     * @return array<int, array<string, mixed>>
     */
    protected function buildChildNodes(string $parentId, array $childrenMap, int $depth): array
    {
        $nodes = [];
        foreach ($childrenMap[$parentId] ?? [] as $child) {
            $childId = $child['fireblocks_tx_id'];
            $nodes[] = $this->wrapTreeNode(
                $child,
                $this->buildChildNodes($childId, $childrenMap, $depth + 1),
                $depth,
                false
            );
        }

        return $nodes;
    }

    /**
     * @param  array<int, array<string, mixed>>  $children
     * @return array<string, mixed>
     */
    protected function wrapTreeNode(array $entry, array $children, int $depth, bool $isRoot): array
    {
        $childCount = count($children);

        return [
            'entry' => $entry,
            'depth' => $depth,
            'is_root' => $isRoot,
            'branch_type' => $childCount > 1 ? 'parallel' : ($childCount === 1 ? 'linear' : 'leaf'),
            'children' => $children,
        ];
    }

    protected function formatChainEntry(FireblocksTransaction $tx, bool $isCurrent): array
    {
        $resolved = FireblocksStatusHelper::resolveForMonitoring($tx);
        $internalBadge = FireblocksStatusHelper::internalStatusBadge($resolved['internal_status']);
        $monitoringBadge = FireblocksStatusHelper::monitoringStateBadge($tx->monitoring_state ?: 'normal');
        $isFailed = in_array(strtoupper((string) $tx->status), FireblocksStatusHelper::REJECTED_STATUSES, true);
        $accountTransaction = AccountTransaction::where('crypto_transaction_id', $tx->fireblocks_tx_id)
            ->where('crypto_system', 'fireblocks')
            ->first();

        return [
            'fireblocks_tx_id' => $tx->fireblocks_tx_id,
            'status' => $tx->status,
            'status_label' => FireblocksStatusHelper::primaryStatusLabel($tx->status ?? ''),
            'sub_status' => $tx->sub_status,
            'substatus_message' => FireblocksStatusHelper::getSubstatusMessage($tx->sub_status),
            'substatus_category' => FireblocksStatusHelper::substatusCategory($tx->sub_status),
            'internal_status' => $resolved['internal_status'],
            'internal_status_badge' => $internalBadge,
            'monitoring_state' => $tx->monitoring_state ?: 'normal',
            'monitoring_badge' => $monitoringBadge,
            'parent_fireblocks_tx_id' => $tx->parent_fireblocks_tx_id,
            'replaced_by_fireblocks_tx_id' => $tx->replaced_by_fireblocks_tx_id,
            'created_at' => $tx->created_at?->format('Y-m-d H:i:s'),
            'is_current' => $isCurrent,
            'is_failed' => $isFailed,
            'ledger_url' => $accountTransaction
                ? url('/crypto-management/crypto-transactions/edit/crypto-transaction/' . $accountTransaction->id)
                : null,
            'dashboard_url' => url('/fireblocks/transaction-monitoring') . '?search=' . urlencode($tx->fireblocks_tx_id),
        ];
    }
}
