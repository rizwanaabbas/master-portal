<?php

namespace App\Http;

use App\Http\Middleware\APIKeyMiddleware;
use App\Http\Middleware\Check2FA;
use App\Http\Middleware\DraftClient;
use App\Http\Middleware\LockUser;
use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array
     */
    protected $middleware = [
        // \App\Http\Middleware\TrustHosts::class,
        \App\Http\Middleware\TrustProxies::class,
        \Fruitcake\Cors\HandleCors::class,
        \App\Http\Middleware\PreventRequestsDuringMaintenance::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \App\Http\Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array
     */
    protected $middlewareGroups = [
        'web' => [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \App\Http\Middleware\EnforceSessionIdleTimeout::class,
            // \Illuminate\Session\Middleware\AuthenticateSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            LockUser::class,
            Check2FA::class,
            \App\Http\Middleware\AccessLogsMiddleware::class,
            \App\Http\Middleware\RecordUserJourney::class,
            \App\Http\Middleware\SanitizeClientErrorResponses::class,
        ],

        'api' => [
            'throttle:api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Http\Middleware\SanitizeClientErrorResponses::class,
        ],
    ];

    /**
     * The application's route middleware.
     *
     * These middleware may be assigned to groups or used individually.
     *
     * @var array
     */
    protected $routeMiddleware = [
        'auth' => \App\Http\Middleware\Authenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
        'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
        'signed' => \Illuminate\Routing\Middleware\ValidateSignature::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,

        'role' => \Spatie\Permission\Middlewares\RoleMiddleware::class,
        'permission' => \Spatie\Permission\Middlewares\PermissionMiddleware::class,
        'role_or_permission' => \Spatie\Permission\Middlewares\RoleOrPermissionMiddleware::class,
        'draft_client' => DraftClient::class,
        'api_key' => APIKeyMiddleware::class,
        'prevent.2fa.access' => \App\Http\Middleware\PreventAccessAfter2FA::class,
        '2fa' => \App\Http\Middleware\TwoFactorMiddleware::class,
        'prevent.duplicate' => \App\Http\Middleware\PreventDuplicateSubmission::class,
        'module.enabled'    => \App\Http\Middleware\CheckModuleEnabled::class,
        'fireblocks.active' => \App\Modules\Fireblocks\Middleware\FireblocksModuleActive::class,
        'fireblocks.monitoring.active' => \App\Modules\FireblocksTransactionsMonitoring\Middleware\MonitoringModuleActive::class,
        'fireblocks.sweeping.active' => \App\Modules\FireblocksVaultSweeping\Middleware\SweepingModuleActive::class,
        'b2c2.active' => \App\Modules\B2c2\Middleware\ModuleActive::class,
        'aquanow.active' => \App\Modules\Aquanow\Middleware\ModuleActive::class,
        'liquidity.active' => \App\Modules\Liquidity\Middleware\ModuleActive::class,
        'whitelist.webhook.ip' => \App\Http\Middleware\WhitelistWebhookIp::class,
        'musd.signature' => \App\Http\Middleware\VerifyMusdWebhookSignature::class,
        "idempotency" => \App\Http\Middleware\CheckIdempotency::class,
    ];
}
