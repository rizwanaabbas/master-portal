<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class APIKeyMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (!$request->header("api-key")){
            return response()->json([
                "success" => false,
                "message" => "no api key"
            ]);
        }

        if ($request->header("api-key") != env("API_KEY")){
            return response()->json([
                "success" => false,
                "message" => "invalid api key"
            ]);
        }

        return $next($request);
    }
}
