<?php

namespace App\Modules\FireblocksVaultSweeping\Middleware;

use App\Helpers\Common;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SweepingModuleActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Common::isModuleEnabled('Fireblocks') || !Common::isModuleEnabled('FireblocksVaultSweeping')) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Fireblocks Vault Sweeping is not available.',
                ], Response::HTTP_SERVICE_UNAVAILABLE);
            }

            abort(Response::HTTP_SERVICE_UNAVAILABLE, 'Fireblocks Vault Sweeping is not available.');
        }

        return $next($request);
    }
}
