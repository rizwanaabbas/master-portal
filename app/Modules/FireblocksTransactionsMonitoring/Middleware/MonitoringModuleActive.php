<?php

namespace App\Modules\FireblocksTransactionsMonitoring\Middleware;

use App\Helpers\Common;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MonitoringModuleActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Common::isModuleEnabled('Fireblocks') || !Common::isModuleEnabled('FireblocksTransactionsMonitoring')) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Fireblocks Transaction Monitoring is not available.',
                ], Response::HTTP_SERVICE_UNAVAILABLE);
            }

            abort(Response::HTTP_SERVICE_UNAVAILABLE, 'Fireblocks Transaction Monitoring is not available.');
        }

        return $next($request);
    }
}
