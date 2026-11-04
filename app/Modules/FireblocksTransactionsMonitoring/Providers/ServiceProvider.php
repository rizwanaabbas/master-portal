<?php

namespace App\Modules\FireblocksTransactionsMonitoring\Providers;

use Illuminate\Support\ServiceProvider as LaravelServiceProvider;

class ServiceProvider extends LaravelServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'fireblocks-monitoring');

        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Modules\FireblocksTransactionsMonitoring\Console\Commands\MonitorFireblocksTransactions::class,
            ]);
        }
    }
}
