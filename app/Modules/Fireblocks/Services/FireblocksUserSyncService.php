<?php

namespace App\Modules\Fireblocks\Services;

use App\Modules\Fireblocks\Models\FireblocksUser;
use Fireblocks\Sdk\Facades\Fireblocks;
use Illuminate\Support\Facades\Log;

class FireblocksUserSyncService
{
    /**
     * Console users (Admin / Non-Signing Admin) plus legacy GET /v1/users.
     *
     * @return array{success: bool, synced: int, message?: string}
     */
    public function sync(): array
    {
        $errors = [];
        $collected = [];

        foreach (['/v1/management/users', '/v1/users'] as $path) {
            try {
                $response = Fireblocks::get($path);
                foreach ($this->unwrapUsers($response) as $row) {
                    if (!is_array($row)) {
                        continue;
                    }
                    $id = trim((string) ($row['id'] ?? ''));
                    if ($id === '') {
                        continue;
                    }
                    $collected[$id] = $row;
                }
            } catch (\Throwable $e) {
                $errors[] = $path . ': ' . $this->exceptionMessage($e);
                Log::warning('Fireblocks user list failed', [
                    'path' => $path,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($collected === []) {
            return [
                'success' => false,
                'synced' => 0,
                'message' => 'Fireblocks returned no users. '
                    . ($errors !== [] ? implode(' ', $errors) : 'Use an Admin or Non-Signing Admin API key.'),
            ];
        }

        $now = now();
        $synced = 0;

        foreach ($collected as $id => $row) {
            FireblocksUser::updateOrCreate(
                ['fireblocks_user_id' => $id],
                [
                    'first_name' => $row['firstName'] ?? null,
                    'last_name' => $row['lastName'] ?? null,
                    'email' => $row['email'] ?? null,
                    'role' => $row['role'] ?? null,
                    'enabled' => array_key_exists('enabled', $row) ? (bool) $row['enabled'] : null,
                    'raw_response' => $row,
                    'last_synced_at' => $now,
                ]
            );
            $synced++;
        }

        Log::info('Fireblocks users synced', ['count' => $synced]);

        return [
            'success' => true,
            'synced' => $synced,
            'message' => $synced . ' Fireblocks user' . ($synced === 1 ? '' : 's') . ' synced.',
        ];
    }

    protected function exceptionMessage(\Throwable $e): string
    {
        $message = trim((string) $e->getMessage());
        if ($message === '' || strtolower($message) === 'ok') {
            $code = $e->getCode();
            $message = 'HTTP ' . ($code ?: 'error') . ' from Fireblocks users API';
        }

        return $message;
    }

    /**
     * @param  mixed  $response
     * @return array<int, mixed>
     */
    protected function unwrapUsers($response): array
    {
        if (!is_array($response)) {
            return [];
        }

        if ($this->isSequentialUserList($response)) {
            return array_values($response);
        }

        foreach (['users', 'data', 'items'] as $key) {
            if (isset($response[$key]) && is_array($response[$key]) && $this->isSequentialUserList($response[$key])) {
                return array_values($response[$key]);
            }
        }

        return [];
    }

    /**
     * @param  array<mixed>  $rows
     */
    protected function isSequentialUserList(array $rows): bool
    {
        if ($rows === [] || array_keys($rows) !== range(0, count($rows) - 1)) {
            return false;
        }

        $first = $rows[0] ?? null;

        return is_array($first) && (isset($first['id']) || isset($first['role']) || isset($first['email']));
    }
}
