<?php

namespace App\Modules\FireblocksVaultSweeping\Providers;

use App\Modules\FireblocksVaultSweeping\Services;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;

class ServiceProvider extends LaravelServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Services\SweepRuntimeLogger::class);

        $this->commands([
            \App\Modules\FireblocksVaultSweeping\Console\Commands\RunFireblocksSweeps::class,
            \App\Modules\FireblocksVaultSweeping\Console\Commands\ReconcileSweepJobs::class,
        ]);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'fireblocks-sweeping');

        $assetSource = __DIR__ . '/../Resources/assets/css';
        $assetTarget = public_path('assets/css/fireblocks-vault-sweeping');

        $this->publishes([
            $assetSource => $assetTarget,
        ], 'fireblocks-vault-sweeping-assets');

        if (is_dir($assetSource) && (!is_dir($assetTarget) || $this->app->environment('local'))) {
            if (!is_dir($assetTarget)) {
                mkdir($assetTarget, 0755, true);
            }

            foreach (glob($assetSource . '/*.css') ?: [] as $cssFile) {
                $destination = $assetTarget . '/' . basename($cssFile);
                if (!file_exists($destination) || filemtime($cssFile) > filemtime($destination)) {
                    copy($cssFile, $destination);
                }
            }
        }
    }
}
