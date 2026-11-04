<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyMusdWebhookSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $timestamp = $request->header('Tonce');
        $signature = $request->header('Signature');

        if (!$timestamp || !$signature) {
            return response()->json([
                'message' => 'Invalid webhook request.',
            ], 401);
        }

        /*
         * Reject requests with an invalid timestamp.
         *
         * 5 minutes is a reasonable starting point.
         */
        $tolerance = 300;

        if (abs(time() - (int) $timestamp) > $tolerance) {
            return response()->json([
                'message' => 'Webhook request expired.',
            ], 401);
        }

        /*
         * Get the exact raw request body.
         */
        $payload = $request->getContent();

        /*
         * Construct the exact string that the sender signed.
         */
        $signedPayload = $timestamp . '.' . $payload;

        /*
         * Calculate the expected signature.
         */
        $expectedSignature = hash_hmac(
            'sha256',
            $signedPayload,
            config('services.musd.webhook_secret')
        );

        /*
         * Timing-safe comparison.
         */
        if (!hash_equals($expectedSignature, $signature)) {
            return response()->json([
                'message' => 'Invalid webhook signature.',
            ], 401);
        }

        return $next($request);
    }
}