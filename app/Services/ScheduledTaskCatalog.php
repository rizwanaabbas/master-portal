<?php

namespace App\Services;

use App\Helpers\Common;

class ScheduledTaskCatalog
{
    public static function tasks(): array
    {
        $tasks = [
            [
                'command' => 'db:backup',
                'description' => 'Database backup',
                'schedule' => 'Hourly',
                'environments' => ['production', 'staging'],
                'module' => null,
            ],
            [
                'command' => 'notify:docs-expiry',
                'description' => 'Notify operations about client document expiry',
                'schedule' => 'Daily',
                'environments' => ['production', 'staging'],
                'module' => null,
            ],
            [
                'command' => 'access-logs:clean',
                'description' => 'Purge old access logs',
                'schedule' => 'Daily',
                'environments' => ['production', 'staging'],
                'module' => null,
            ],
            [
                'command' => 'get:card-statements',
                'description' => 'Fetch card statements from Vista Money API',
                'schedule' => 'Daily at 00:05',
                'environments' => ['production', 'staging'],
                'module' => null,
            ],
            [
                'command' => 'deduct:maintenance-fee',
                'description' => 'Deduct currency maintenance fees from clients',
                'schedule' => 'Monthly on 1st at 23:30',
                'environments' => ['production', 'staging'],
                'module' => null,
            ],
        ];

        if (in_array('MatchMove', Common::getEnabledModules(), true) || in_array('E-Money', Common::getEnabledModules(), true)) {
            $tasks[] = [
                'command' => 'transactions:fetch-fingate',
                'description' => 'Fetch Fingate card transactions',
                'schedule' => 'Every 30 minutes',
                'environments' => ['production', 'staging'],
                'module' => 'MatchMove / E-Money',
            ];
        }

        if (in_array('FireblocksTransactionsMonitoring', Common::getEnabledModules(), true)) {
            $tasks[] = [
                'command' => 'fireblocks:monitor-transactions',
                'description' => 'Poll Fireblocks transactions for stuck detection and auto-actions',
                'schedule' => 'Every 5 minutes',
                'environments' => ['production', 'staging'],
                'module' => 'FireblocksTransactionsMonitoring',
            ];
        }

        if (in_array('Fireblocks', Common::getEnabledModules(), true)) {
            $tasks[] = [
                'command' => 'fireblocks:sync-withdrawal-pool-balances',
                'description' => 'Sync Fireblocks withdrawal pool (UTXO vault + wallet pool) balances',
                'schedule' => 'Hourly',
                'environments' => ['production', 'staging'],
                'module' => 'Fireblocks',
            ];
            $tasks[] = [
                'command' => 'fireblocks:refresh-coingecko-prices',
                'description' => 'Cache CoinGecko USD spots for Transaction Monitoring Now values',
                'schedule' => 'Every 5 minutes',
                'environments' => ['production', 'staging'],
                'module' => 'Fireblocks',
            ];
        }

        if (
            in_array('Liquidity', Common::getEnabledModules(), true)
            && in_array('Aquanow', Common::getEnabledModules(), true)
        ) {
            $tasks[] = [
                'command' => 'aquanow:sync-balances',
                'description' => 'Sync Aquanow liquidity balances from API',
                'schedule' => 'Hourly',
                'environments' => ['production', 'staging'],
                'module' => 'Aquanow',
            ];
        }

        if (in_array('FireblocksVaultSweeping', Common::getEnabledModules(), true)) {
            $tasks[] = [
                'command' => 'fireblocks:run-sweeps',
                'description' => 'Scan client deposit vaults and submit scheduled vault sweeps',
                'schedule' => 'Every 5 minutes',
                'environments' => ['production', 'staging'],
                'module' => 'FireblocksVaultSweeping',
            ];
        }

        if (!in_array(env('APP_ENV'), ['production', 'staging'], true)) {
            $tasks[] = [
                'command' => 'debugbar:clear',
                'description' => 'Clear Debugbar cache files',
                'schedule' => 'Hourly',
                'environments' => [env('APP_ENV')],
                'module' => null,
            ];
        }

        return $tasks;
    }
}
