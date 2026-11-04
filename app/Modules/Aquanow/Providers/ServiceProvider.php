<?php

namespace App\Modules\Aquanow\Providers;

use App\Modules\Aquanow\Services\ClientService;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;

class ServiceProvider extends LaravelServiceProvider
{
    public function register(): void
    {
        $this->commands([
            \App\Modules\Aquanow\Console\Commands\SyncBalances::class,
        ]);

        // ClientService resolves its config lazily on first API call, not here —
        // constructing it must not fail just because the module is disabled
        // (e.g. Laravel resolving controller dependencies for `route:list`).
        $this->app->singleton(ClientService::class, fn () => new ClientService());

        $this->app->singleton('aquanow', function ($app) {
            return $app->make(ClientService::class);
        });
    }

    public function boot(): void
    {
        // Blade views live inside the module so its boundary stays self-contained.
        // Referenced as `aquanow::buy`, `aquanow::sell`, etc.
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'aquanow');
    }
}
