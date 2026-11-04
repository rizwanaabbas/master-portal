<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\WebhookIpWhitelist;
use Symfony\Component\HttpFoundation\Response;

class WhitelistWebhookIp
{
    public function handle(Request $request, Closure $next): Response
    {
        $allowedIps = WebhookIpWhitelist::where('is_active', true)->pluck('ip_address')->toArray();

        if (! in_array($request->ip(), $allowedIps, true)) {
            abort(403, 'Forbidden');
        }

        return $next($request);
    }
}