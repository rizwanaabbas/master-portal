<?php

namespace App\Modules\Liquidity\Middleware;

use App\Modules\Liquidity\Services\ProviderResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ModuleActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!ProviderResolver::active()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This feature is currently unavailable.',
                ], Response::HTTP_SERVICE_UNAVAILABLE);
            }

            abort(Response::HTTP_SERVICE_UNAVAILABLE, 'This feature is currently unavailable.');
        }

        return $next($request);
    }
}
