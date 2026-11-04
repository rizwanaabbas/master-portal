<?php

namespace App\Modules\Fireblocks\Console\Commands;

use App\Helpers\Common;
use App\Modules\Fireblocks\Services\FireblocksUserSyncService;
use Illuminate\Console\Command;

class SyncFireblocksUsers extends Command
{
    protected $signature = 'fireblocks:sync-users';

    protected $description = 'Sync Fireblocks workspace users (id, name, role) into fireblocks_users';

    public function handle(FireblocksUserSyncService $syncService): int
    {
        if (!Common::isModuleEnabled('Fireblocks')) {
            $this->warn('Fireblocks module is not enabled; skipping user sync.');

            return self::SUCCESS;
        }

        set_time_limit(120);

        try {
            $result = $syncService->sync();
        } catch (\Throwable $e) {
            report($e);
            $detail = trim((string) $e->getMessage());
            if ($detail === '' || strtolower($detail) === 'ok') {
                $detail = 'Fireblocks users API rejected the request (check Admin API key permissions).';
            }
            $this->error('Fireblocks user sync failed: ' . $detail);

            return self::FAILURE;
        }

        if (empty($result['success'])) {
            $this->error($result['message'] ?? 'No Fireblocks users were synced.');

            return self::FAILURE;
        }

        $this->info($result['message'] ?? 'Fireblocks users synced.');

        return self::SUCCESS;
    }
}
