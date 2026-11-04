<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use App\Mail\EmailVerification;
use Illuminate\Auth\Notifications\VerifyEmail;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        try {
            \App\Modules\Security\Services\SecurityConfigService::applyRuntimeConfig();
        } catch (\Throwable $e) {
            // Database may not be available during migrate/package discovery.
        }

        if ($this->shouldForceHttps()) {
            URL::forceScheme('https');
        }

        // Override the email notification for verifying email
        VerifyEmail::toMailUsing(function ($notifiable) {
            $verifyUrl = URL::temporarySignedRoute(
                'verification.verify',
                \Illuminate\Support\Carbon::now()->addMinutes(\Illuminate\Support\Facades\Config::get('auth.verification.expire', 60)),
                [
                    'id' => $notifiable->getKey(),
                    'hash' => sha1($notifiable->getEmailForVerification()),
                ]
            );
            return new EmailVerification($verifyUrl, $notifiable);
        });
        // "A" => "Active",
        //         "T" => "Temporary Closed",
        //         "L" => "Lost",
        //         "S" => "Stolen",
        //         "D" => "Damaged",
        //         "F" => "Fraud",
        //         "TC" => "Terminated Card",
        //         "W" => "Waiting For Activation"

        view()->share('statusBadge', [
            'Created' => 'badge-primary',
            'Pending' => 'badge-primary',
            'waiting' => 'badge-primary',
            'INITIATED' => 'badge-info',
            'Initiated' => 'badge-info',
            'FAILED' => 'badge-danger',
            'Failed' => 'badge-danger',
            'CANCELLED' => 'badge-danger',
            'Initial Review' => 'badge-info',
            'Pending OTP' => 'badge-secondary',
            'Approved' => 'badge-success',
            'active' => 'badge-success',
            'Rejected' => 'badge-danger',
            'Closed' => 'badge-danger',
            'Draft' => 'badge-secondary',
            'In Progress' => 'badge-primary',
            'Liveness Done' => 'badge-dark',
            'created' => 'badge-primary',
            'updated' => 'badge-secondary',
            'deleted' => 'badge-danger',
            'Verified' => 'badge-success',
            'Unverified' => 'badge-danger',
            'Alive' => 'badge-success',
            '0' => 'badge-primary',
            '1' => 'badge-success',
            '0_suspicious' => 'badge-success',
            '1_suspicious' => 'badge-danger',
            '' => 'badge-secondary',
            null => 'badge-secondary',
            'Active' => 'badge-success',
            'Inactive' => 'badge-secondary',
            'Blocked' => 'badge-danger',
            'Locked' => 'badge-danger',
            'Cancelled' => 'badge-danger',
            'Liveness Pending' => 'badge-primary',
            'Waiting For Activation' => 'badge-secondary',
            'Temporary Closed' => 'badge-warning',
            'Lost' => 'badge-danger',
            'Stolen' => 'badge-danger',
            'Damaged' => 'badge-danger',
            'Fraud' => 'badge-danger',
            'Terminated Card' => 'badge-secondary',
            'Completed' => 'badge-success',
            'GREEN' => 'badge-success',
            'RED' => 'badge-danger',
        ]);

        view()->share('complianceStatus', ['CANCELLED', 'MODERATION', 'ASSIGNED']);

        if (
            !app()->runningInConsole()
            || (
                app()->runningInConsole()
                && (
                    isset($_SERVER['argv'][1])
                    && (
                        $_SERVER['argv'][1] == 'notify:docs-expiry'
                        || $_SERVER['argv'][1] == 'deduct:maintenance-fee'
                    )
                )
            )
        ) {
            $ownCompany = \App\Helpers\Common::getOwnCompanyData();
            view()->share('ownCompany', $ownCompany);

            $enabledModules = \App\Helpers\Common::getEnabledModules();
            view()->share('enabledModules', $enabledModules);
        }
    }

    /**
     * Production/staging servers use HTTPS. Local dev (e.g. php artisan serve :8000) stays on HTTP
     * unless FORCE_HTTPS=true. Avoid forcing HTTPS when APP_ENV=staging on a laptop.
     */
    protected function shouldForceHttps(): bool
    {
        if ($this->app->environment('local')) {
            return filter_var(env('FORCE_HTTPS', false), FILTER_VALIDATE_BOOLEAN);
        }

        if (!$this->app->environment('production', 'staging')) {
            return false;
        }

        $appUrl = strtolower(trim((string) config('app.url', '')));

        // Staging/production .env copied locally often keeps https APP_URL; explicit http:// means plain HTTP dev.
        if ($appUrl !== '' && str_starts_with($appUrl, 'http://')) {
            return filter_var(env('FORCE_HTTPS', false), FILTER_VALIDATE_BOOLEAN);
        }

        return true;
    }
}
