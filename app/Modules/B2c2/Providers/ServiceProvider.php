<?php

namespace App\Modules\B2c2\Providers;

use App\Modules\B2c2\Services\ClientService;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;

class ServiceProvider extends LaravelServiceProvider
{
    public function register(): void
    {
        // ClientService resolves its config lazily on first API call, not here —
        // constructing it must not fail just because the module is disabled
        // (e.g. Laravel resolving controller dependencies for `route:list`).
        $this->app->singleton(ClientService::class, fn () => new ClientService());

        $this->app->singleton('b2c2', function ($app) {
            return $app->make(ClientService::class);
        });
    }

    public function boot(): void
    {
        // Blade views live inside the module so its boundary stays self-contained.
        // Referenced as `b2c2::buy`, `b2c2::sell`, etc.
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'b2c2');
    }
}
