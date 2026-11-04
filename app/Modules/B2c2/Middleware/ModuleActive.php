<?php

namespace App\Modules\B2c2\Middleware;

use App\Helpers\Common;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ModuleActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Common::isModuleEnabled('B2c2')) {
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
