<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;

class EnforceSessionIdleTimeout
{
    /**
     * Log out authenticated users after SESSION_LIFETIME minutes without activity.
     * Background polling (dashboard charts, etc.) does not count as activity.
     */
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            return $next($request);
        }

        $lifetimeMinutes = (int) Config::get('session.lifetime', 120);
        if ($lifetimeMinutes <= 0) {
            return $next($request);
        }

        $sessionKey = 'portal_last_activity';
        $now = time();
        $lastActivity = $request->session()->get($sessionKey);

        if ($lastActivity === null) {
            $request->session()->put($sessionKey, $now);

            return $next($request);
        }

        if (($now - (int) $lastActivity) > ($lifetimeMinutes * 60)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/login')->with('error', 'Your session has expired due to inactivity.');
        }

        if (!$this->isBackgroundRequest($request)) {
            $request->session()->put($sessionKey, $now);
        }

        return $next($request);
    }

    private function isBackgroundRequest(Request $request): bool
    {
        if (!$request->isMethod('GET')) {
            return false;
        }

        $path = $request->path();

        $backgroundPathPatterns = [
            'home',
            'dashboard/admin/charts/*',
            'dashboards/finance/charts/*',
            'fireblocks/crypto-networks/networks-data',
            'fireblocks/crypto-networks/assets-data',
            'crypto-management/crypto-networks/networks-data',
            'crypto-management/crypto-networks/assets-data',
        ];

        foreach ($backgroundPathPatterns as $pattern) {
            if ($request->is($pattern)) {
                return true;
            }
        }

        return str_contains($path, '/charts/') && str_ends_with($path, '-data');
    }
}
