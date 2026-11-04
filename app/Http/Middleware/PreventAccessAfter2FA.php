<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Session;

class PreventAccessAfter2FA
{
    public function handle($request, Closure $next)
    {
        if (Session::get('2fa') === 'true') {
            return redirect()->route('home');
        }

        return $next($request);
    }
}
