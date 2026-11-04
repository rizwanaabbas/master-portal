<?php

namespace App\Modules\Aquanow\Console\Commands;

use App\Helpers\Common;
use App\Modules\Aquanow\Services\BalanceService;
use App\Modules\Liquidity\Services\ProviderResolver;
use Illuminate\Console\Command;

class SyncBalances extends Command
{
    protected $signature = 'aquanow:sync-balances';

    protected $description = 'Fetch Aquanow account balances and persist them to liquidity_balances';

    public function handle(BalanceService $balanceService): int
    {
        if (!Common::isModuleEnabled('Liquidity') || !Common::isModuleEnabled('Aquanow')) {
            $this->warn('Liquidity / Aquanow is not enabled; skipping balance sync.');

            return self::SUCCESS;
        }

        if (ProviderResolver::active() !== ProviderResolver::AQUANOW) {
            $this->warn('Aquanow is not the active liquidity provider; skipping balance sync.');

            return self::SUCCESS;
        }

        try {
            $result = $balanceService->getBalances();
        } catch (\Throwable $e) {
            report($e);
            $this->error('Aquanow balance sync failed: ' . $e->getMessage());

            return self::FAILURE;
        }

        if (empty($result['success'])) {
            $this->error($result['message'] ?? 'Failed to fetch Aquanow balances.');

            return self::FAILURE;
        }

        $this->info('Aquanow liquidity balances synced from API.');

        return self::SUCCESS;
    }
}
