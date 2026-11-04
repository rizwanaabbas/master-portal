<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class TwoFactorMiddleware
{
    public function handle($request, Closure $next)
    {
        $user = Auth::user();

        if ($user->roles->count() == 1 && ($user->hasRole('client') || $user->hasRole('draft client') || $user->hasRole('basic client') || !env('GOOGLE_AUTHENTICATOR'))) {
            return $next($request);
        }

        if (Auth::check() && !session('2fa') && env('GOOGLE_AUTHENTICATOR')) {
            return redirect()->route('2fa.form');
        }

        return $next($request);
    }
}
