<?php

namespace App\Helpers;

class SignatureHelper
{
    public static function createSignature(string $method, string $url, int $timestamp, array $data = []): string
    {
        $dataToSign = "{$method}:{$url}:{$timestamp}";

        if (strtoupper($method) === 'POST' && !empty($data)) {
            $jsonData = json_encode($data, JSON_UNESCAPED_SLASHES);
            $dataToSign .= ":{$jsonData}";
        }
        
        $privateKeyPath = storage_path('app/keys/private_key.pem');
        $privateKey = file_get_contents($privateKeyPath);

        if (!$privateKey) {
            throw new \Exception('Private key not found.');
        }

        $pkey = openssl_pkey_get_private($privateKey);

        
        if (!$pkey) {
            throw new \Exception('Invalid private key.');
        }

        $signature = '';

        openssl_sign($dataToSign, $signature, $pkey, OPENSSL_ALGO_SHA256);

        return base64_encode($signature);
    }
}
