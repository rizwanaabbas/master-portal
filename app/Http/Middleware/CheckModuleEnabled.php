<?php

namespace App\Http\Middleware;

use App\Helpers\Common;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckModuleEnabled
{
    public function handle(Request $request, Closure $next, string $module): Response
    {
        if (!Common::isModuleEnabled($module)) {
            abort(Response::HTTP_NOT_FOUND, 'The requested resource is not found.');
        }

        return $next($request);
    }
}
