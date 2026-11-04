<?php

namespace App\Modules\FeeEngine\Providers;

use App\Modules\FeeEngine\Services\FeeCalculationService;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;

class ServiceProvider extends LaravelServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(FeeCalculationService::class, function () {
            return new FeeCalculationService();
        });

        $this->app->singleton('fee-engine', function ($app) {
            return $app->make(FeeCalculationService::class);
        });
    }

    public function boot(): void
    {
        // Views live inside the module (referenced as `feeengine::...`).
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'feeengine');
    }
}
