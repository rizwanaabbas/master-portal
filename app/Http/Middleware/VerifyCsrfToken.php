<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [
        'bitpace/callback',
        'openpayd/callback',
        'lorum/callback',
        'nerodata/callback',
        'fingate/callback',
        'fireblocks/webhook', // Exclude Fireblocks webhook from CSRF protection
        '/zenus/callback',
        'fingate/documents/status/callback',
        'sumsub/callback',
        'musd/buy-completed/callback',
        'musd/sell-completed/callback'
    ];
}
