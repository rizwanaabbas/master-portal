<?php

namespace App\Http\Middleware;

use Closure;
use Fideloper\Proxy\TrustProxies as Middleware;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    /**
     * Trust all proxies in deployed environments (ELB/nginx). On local, trust none
     * (empty list) so X-Forwarded-* from the client is ignored on :8000.
     *
     * @var array|string|null
     */
    protected $proxies = '*';

    public function __construct(Repository $config)
    {
        parent::__construct($config);
    }

    public function handle(Request $request, Closure $next)
    {
        if (app()->environment('local')) {
            return $next($request);
        }

        return parent::handle($request, $next);
    }

    /**
     * The headers that should be used to detect proxies.
     *
     * @var int
     */
    protected $headers = Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_HOST | Request::HEADER_X_FORWARDED_PORT | Request::HEADER_X_FORWARDED_PROTO | Request::HEADER_X_FORWARDED_AWS_ELB;
}
