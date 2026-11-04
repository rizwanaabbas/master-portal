<?php

namespace App\Modules\Fireblocks\Console\Commands;

use App\Helpers\Common;
use App\Modules\Fireblocks\Services\WithdrawalPoolBalanceSyncService;
use Illuminate\Console\Command;

class SyncWithdrawalPoolBalances extends Command
{
    protected $signature = 'fireblocks:sync-withdrawal-pool-balances';

    protected $description = 'Sync Fireblocks UTXO source vault and wallet pool withdrawal balances from the API';

    public function handle(WithdrawalPoolBalanceSyncService $syncService): int
    {
        if (!Common::isModuleEnabled('Fireblocks')) {
            $this->warn('Fireblocks module is not enabled; skipping withdrawal pool balance sync.');

            return self::SUCCESS;
        }

        set_time_limit(300);

        try {
            $result = $syncService->sync();
        } catch (\Throwable $e) {
            report($e);
            $this->error('Withdrawal pool balance sync failed: ' . $e->getMessage());

            return self::FAILURE;
        }

        foreach ($result['errors'] ?? [] as $error) {
            $this->warn((string) $error);
        }

        if (empty($result['success'])) {
            $this->error($result['message'] ?? 'No withdrawal pool balances were synced.');

            return self::FAILURE;
        }

        $this->info($result['message'] ?? 'Withdrawal pool balances synced.');

        return self::SUCCESS;
    }
}
