<?php

namespace App\Http\Middleware;

use App\Providers\RouteServiceProvider;
use Closure;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Models\AccessLogs;

class AccessLogsMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $accessLog = new AccessLogs();
        
        $excludedUrls = [
            $request->getSchemeAndHttpHost() . "/home",
            $request->getSchemeAndHttpHost() . "/dashboard/admin/charts/clients-by-status",
            $request->getSchemeAndHttpHost() . "/dashboard/admin/charts/accounts-by-currency",
            $request->getSchemeAndHttpHost() . "/dashboard/admin/charts/transactions-by-status",
            $request->getSchemeAndHttpHost() . "/dashboard/admin/charts/transactions-by-nature",
            $request->getSchemeAndHttpHost() . "/dashboard/admin/charts/transactions-by-date",
        ];

        if (
            Auth::user() !== null
            && (!$request->session()->get('current-url')
            || $request->session()->get('current-url') != $request->fullUrl())
            && !in_array($request->fullUrl(), $excludedUrls)
        ) {
            $accessLog->user_id = Auth::user()->id;
            $accessLog->request_url = $request->fullUrl();
            $accessLog->request_datetime = Carbon::now()->timestamp;
    
            $accessLog->save();
            
            $request->session()->put('current-url', $request->fullUrl());
        }

        return $next($request);
    }
}
