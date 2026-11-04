<?php

namespace App\Modules\Fireblocks\Providers;

use App\Modules\Fireblocks\Services\FireblocksConfigService;
use Fireblocks\Sdk\Api\FireblocksClient;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class FireblocksServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->commands([
            \App\Modules\Fireblocks\Console\Commands\SyncWithdrawalPoolBalances::class,
            \App\Modules\Fireblocks\Console\Commands\SyncFireblocksUsers::class,
            \App\Modules\Fireblocks\Console\Commands\BackfillFireblocksTransactionActors::class,
            \App\Modules\Fireblocks\Console\Commands\BackfillFireblocksTransactionAmounts::class,
            \App\Modules\Fireblocks\Console\Commands\RefreshCoinGeckoUsdPrices::class,
        ]);

        // Re-bind the singleton registered by the vendor package, using DB config.
        $this->app->singleton(FireblocksClient::class, function () {
            try {
                $config = FireblocksConfigService::get();
            } catch (\RuntimeException $e) {
                Log::warning('Fireblocks SDK not initialised: ' . $e->getMessage());
                throw $e;
            }

            // Merge DB config on top of the published config file so static keys
            // (webhook_public_keys PEM block, etc.) are still available.
            $baseConfig = config('fireblocks', []);

            return new FireblocksClient(array_merge($baseConfig, $config));
        });

        $this->app->singleton('fireblocks', function ($app) {
            return $app->make(FireblocksClient::class);
        });
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'fireblocks');
    }
}
