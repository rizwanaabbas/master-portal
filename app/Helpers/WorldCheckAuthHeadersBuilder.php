<?php

namespace App\Helpers;

class WorldCheckAuthHeadersBuilder
{
    const SIGNATURE_HEADERS = "(request-target) host date";
    const SIGNATURE_HEADERS_WITH_CONTENT = "(request-target) host date content-type content-length";

    public  function generateDateHeader()
    {
        return gmdate('D, d M Y H:i:s \G\M\T', time());
    }

    public  function generateAuthHeader($apiKey, $secret, $hostHeader, $dateHeader, $method, $path, $contentType, $content)
    {
        $signature = $this->generateSignature($secret, $hostHeader, $dateHeader, $method, $path, $contentType, $content);
        $headers = $content != null ? self::SIGNATURE_HEADERS_WITH_CONTENT : self::SIGNATURE_HEADERS;

        return "Signature keyId=\"$apiKey\",algorithm=\"hmac-sha256\",headers=\"$headers\",signature=\"$signature\"";
    }

    public  function generateSignature($secret, $hostHeader, $dateHeader, $method, $path, $contentType, $content)
    {
        $method = strtolower($method);
        $dataToSign = "(request-target): $method $path\nhost: $hostHeader\ndate: $dateHeader";

        if ($content != "" && $contentType != "") {
            $contentLength = strval(strlen($content));
            $dataToSign .= "\ncontent-type: $contentType\ncontent-length: $contentLength\n";
        }

        return $this->signContentWithHmacAlgorithm($secret, $dataToSign, $content);
    }

    private function signContentWithHmacAlgorithm($secret, $dataToSign, $content)
    {
        $hashDigest = hash_init("sha256", HASH_HMAC, $secret);
        hash_update($hashDigest, $dataToSign);
        if ($content != "") {
            hash_update($hashDigest, $content);
        }

        return base64_encode(hash_final($hashDigest, true));
    }
}
