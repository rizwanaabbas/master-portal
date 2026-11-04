<?php

namespace App\Modules\Aquanow\Helpers;

/**
 * Builds the x-api-key / x-nonce / x-signature headers Aquanow's REST API
 * requires on every authenticated request.
 *
 * Matches Aquanow's own Postman pre-request script exactly:
 *   nonce = Date.now().toString()
 *   signatureContent = JSON.stringify({ httpMethod, path, nonce })   // note: no body, no query string
 *   signature = HMAC-SHA384(signatureContent, secret) — both UTF-8, hex output
 *
 * `path` is the request path only (segments joined with "/", leading "/"),
 * no host/query string — e.g. "/trades/v1/order".
 */
class SignatureHelper
{
    public static function nonce(): string
    {
        return (string) round(microtime(true) * 1000);
    }

    public static function sign(string $secret, string $nonce, string $method, string $path): string
    {
        // Key order matters: PHP associative arrays preserve insertion order,
        // matching JS's JSON.stringify({ httpMethod, path, nonce }).
        // JSON_UNESCAPED_SLASHES: JS's JSON.stringify does not escape "/",
        // PHP's json_encode does by default — must match byte-for-byte with
        // Aquanow's own signing script or the HMAC won't verify.
        $signatureContent = json_encode([
            'httpMethod' => strtoupper($method),
            'path'       => $path,
            'nonce'      => $nonce,
        ], JSON_UNESCAPED_SLASHES);

        return hash_hmac('sha384', $signatureContent, $secret);
    }

    /**
     * Build the full auth header set for a request. `$path` must be the bare
     * request path (no query string), e.g. "/trades/v1/order".
     */
    public static function headers(string $apiKey, string $apiSecret, string $method, string $path): array
    {
        $nonce = self::nonce();

        return [
            'x-api-key'   => $apiKey,
            'x-nonce'     => $nonce,
            'x-signature' => self::sign($apiSecret, $nonce, $method, $path),
        ];
    }
}
