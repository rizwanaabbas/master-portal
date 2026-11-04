<?php

namespace App\Modules\Liquidity\Providers;

use Illuminate\Support\ServiceProvider as LaravelServiceProvider;

class ServiceProvider extends LaravelServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Referenced as `liquidity::instruments`, `liquidity::balances`, etc.
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'liquidity');
    }
}
