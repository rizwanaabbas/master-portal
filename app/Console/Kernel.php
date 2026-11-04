<?php

namespace App\Console;

use App\Helpers\Common;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        if (env('APP_ENV') == 'production' || env('APP_ENV') == 'staging') {
            // create hourly backups
            $schedule->command('db:backup')->hourly();

            // Notify the Operations about Client's document expiry
            $schedule->command('notify:docs-expiry')->daily();

            // Delete Access Logs daily
            $schedule->command('access-logs:clean')->daily();

            // Delete Expired Idempotency Keys daily
            $schedule->command('delete:idempotency-keys')->daily();

            // Delete Audits daily
            // $schedule->command('audits:clean')->daily();

            // Fetch all of the Cards Statements, using the Vista Money API, and store it in the Database.
            $schedule->command('get:card-statements')->dailyAt('00:05');

            // Deduct maintenance fee, been defined against each currency, from all clients.
            if (Common::getOwnCompanyData()->maintenance_fee_job_enabled) {
                $schedule->command('deduct:maintenance-fee')->monthlyOn(1, '23:30');
            }

            if (in_array('MatchMove', Common::getEnabledModules()) || in_array('E-Money', Common::getEnabledModules())) {
                $schedule->command('transactions:fetch-fingate')->everyThirtyMinutes();
            }

            if (in_array('FireblocksTransactionsMonitoring', Common::getEnabledModules())) {
                $schedule->command('fireblocks:monitor-transactions')->everyFiveMinutes();
            }

            if (in_array('Fireblocks', Common::getEnabledModules())) {
                $schedule->command('fireblocks:sync-withdrawal-pool-balances')->hourly();
                $schedule->command('fireblocks:sync-users')->daily();
                $schedule->command('fireblocks:refresh-coingecko-prices')->everyFiveMinutes();
            }

            if (
                in_array('Liquidity', Common::getEnabledModules(), true)
                && in_array('Aquanow', Common::getEnabledModules(), true)
            ) {
                $schedule->command('aquanow:sync-balances')->hourly();
            }
        } else {
            // clear hourly Debugbar files
            $schedule->command('debugbar:clear')->hourly();
        }

        $this->scheduleFireblocksVaultSweeps($schedule);
    }

    protected function scheduleFireblocksVaultSweeps(Schedule $schedule): void
    {
        if (!in_array('FireblocksVaultSweeping', Common::getEnabledModules())) {
            return;
        }

        $sweepInterval = 5;
        $sweepConfig = \App\Modules\FireblocksVaultSweeping\Services\SweepConfigService::tryGet();
        if ($sweepConfig !== null) {
            $sweepInterval = max(1, (int) ($sweepConfig['scheduled_interval_minutes'] ?? 5));
        }

        $sweepCommand = $schedule->command('fireblocks:run-sweeps');
        if ($sweepInterval === 1) {
            $sweepCommand->everyMinute();
        } elseif ($sweepInterval < 60) {
            $sweepCommand->cron(sprintf('*/%d * * * *', $sweepInterval));
        } elseif ($sweepInterval % 60 === 0) {
            $hours = max(1, (int) ($sweepInterval / 60));
            if ($hours === 1) {
                $sweepCommand->hourly();
            } else {
                $sweepCommand->cron(sprintf('0 */%d * * *', $hours));
            }
        } else {
            $sweepCommand->everyThirtyMinutes();
        }
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
