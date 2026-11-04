<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IpGeo
{
    public static function lookup(?string $ip): ?array
    {
        $ip = trim((string) $ip);
        if ($ip === '' || !filter_var($ip, FILTER_VALIDATE_IP) || !self::isPublicIp($ip)) {
            return null;
        }

        $key = 'uj_ipgeo:' . $ip;
        $cached = Cache::get($key);
        if (is_array($cached)) {
            return $cached;
        }
        if ($cached === 'miss') {
            return null;
        }

        $geo = self::fetch($ip);
        Cache::put($key, $geo ?: 'miss', now()->addDays(7));

        return $geo ?: null;
    }

    public static function isPublicIp(string $ip): bool
    {
        return (bool) filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }

    private static function fetch(string $ip): ?array
    {
        $key = UserJourneyRecorder::ip2locationKey() ?: env('IP2LOCATION_KEY', env('IP2LOCATION_API_KEY'));
        if ($key) {
            $geo = self::fromIp2Location($ip, $key);
            if ($geo) {
                return $geo;
            }
        }

        return self::fromIpApi($ip);
    }

    private static function fromIp2Location(string $ip, string $key): ?array
    {
        try {
            $res = Http::timeout(3)->get('https://api.ip2location.io/', [
                'ip' => $ip,
                'key' => $key,
            ]);
            if (!$res->ok()) {
                return null;
            }
            $data = $res->json();
            $country = strtoupper((string) ($data['country_code'] ?? ''));
            if (strlen($country) !== 2) {
                return null;
            }

            return [
                'country' => $country,
                'region' => $data['region_name'] ?? null,
                'city' => $data['city_name'] ?? null,
                'latitude' => isset($data['latitude']) && is_numeric($data['latitude']) ? (float) $data['latitude'] : null,
                'longitude' => isset($data['longitude']) && is_numeric($data['longitude']) ? (float) $data['longitude'] : null,
            ];
        } catch (\Throwable $e) {
            Log::debug('IP2Location lookup failed: ' . $e->getMessage());

            return null;
        }
    }

    private static function fromIpApi(string $ip): ?array
    {
        try {
            $res = Http::timeout(3)->get('http://ip-api.com/json/' . urlencode($ip), [
                'fields' => 'status,countryCode,regionName,city,lat,lon',
            ]);
            if (!$res->ok()) {
                return null;
            }
            $data = $res->json();
            if (($data['status'] ?? '') !== 'success') {
                return null;
            }
            $country = strtoupper((string) ($data['countryCode'] ?? ''));
            if (strlen($country) !== 2) {
                return null;
            }

            return [
                'country' => $country,
                'region' => $data['regionName'] ?? null,
                'city' => $data['city'] ?? null,
                'latitude' => isset($data['lat']) && is_numeric($data['lat']) ? (float) $data['lat'] : null,
                'longitude' => isset($data['lon']) && is_numeric($data['lon']) ? (float) $data['lon'] : null,
            ];
        } catch (\Throwable $e) {
            Log::debug('ip-api lookup failed: ' . $e->getMessage());

            return null;
        }
    }
}
