<?php

namespace App\Helpers;

use App\Models\UserJourneyHeatPoint;
use App\Models\UserJourneyLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class UserJourneyRecorder
{
    public static function isEnabled(): bool
    {
        return Common::isModuleEnabled('UserJourneyAnalytics');
    }

    public static function ipLookupOnEvent(): bool
    {
        return (bool) (self::moduleConfig()['ip2location_on_event'] ?? false);
    }

    public static function ip2locationKey(): string
    {
        return trim((string) (self::moduleConfig()['ip2location_api_key'] ?? ''));
    }

    private static function moduleConfig(): array
    {
        static $resolved = null;
        if ($resolved !== null) {
            return $resolved;
        }
        $resolved = ['ip2location_on_event' => false, 'ip2location_api_key' => ''];
        try {
            $module = \App\Models\Module::where('name', 'UserJourneyAnalytics')->first();
            if (!$module) {
                return $resolved;
            }
            $record = \App\Models\ModuleConfiguration::where('module_id', $module->id)->first();
            if (!$record) {
                return $resolved;
            }
            $config = unserialize($record->configurations);
            if (!is_array($config)) {
                return $resolved;
            }
            $on = (string) ($config['ip2location_on_event']['value'] ?? '0');
            $resolved['ip2location_on_event'] = in_array($on, ['1', 'true', 'yes', 'on'], true);
            $resolved['ip2location_api_key'] = (string) ($config['ip2location_api_key']['value'] ?? '');
        } catch (\Throwable $e) {
        }

        return $resolved;
    }

    public static function parseUserAgent(?string $agent): array
    {
        $agent = $agent ?: '';
        $device = 'Desktop';
        if (preg_match('/iPad|tablet|sm-t/i', $agent)) {
            $device = 'Tablet';
        } elseif (preg_match('/mobile|iphone|android|okhttp|cfnetwork|pixel/i', $agent)) {
            $device = 'Mobile';
        }

        $browser = 'Other';
        if (stripos($agent, 'Edg/') !== false || stripos($agent, 'Edge') !== false) {
            $browser = 'Edge';
        } elseif (stripos($agent, 'OPR/') !== false || stripos($agent, 'Opera') !== false) {
            $browser = 'Opera';
        } elseif (stripos($agent, 'Firefox') !== false) {
            $browser = 'Firefox';
        } elseif (stripos($agent, 'Chrome') !== false || stripos($agent, 'CriOS') !== false) {
            $browser = 'Chrome';
        } elseif (stripos($agent, 'Safari') !== false) {
            $browser = 'Safari';
        } elseif (stripos($agent, 'okhttp') !== false) {
            $browser = 'Android App';
        } elseif (stripos($agent, 'CFNetwork') !== false || stripos($agent, 'Darwin') !== false) {
            $browser = 'iOS App';
        }

        $os = 'Other';
        if (preg_match('/Windows NT|Windows/i', $agent)) {
            $os = 'Windows';
        } elseif (preg_match('/iPhone|iPad|iOS|CPU OS /i', $agent)) {
            $os = 'iOS';
        } elseif (preg_match('/Mac OS X|Macintosh/i', $agent)) {
            $os = 'macOS';
        } elseif (stripos($agent, 'Android') !== false) {
            $os = 'Android';
        } elseif (stripos($agent, 'Linux') !== false) {
            $os = 'Linux';
        }

        $deviceName = self::deviceNameFromAgent($agent, $os, $device);

        return ['device' => $device, 'browser' => $browser, 'os' => $os, 'device_name' => $deviceName];
    }

    public static function deviceNameFromAgent(string $agent, string $os, string $device): string
    {
        if (preg_match('/iPhone\s?(\d{1,2}(?:\s?(?:Pro|Plus|Max|mini))?)/i', $agent, $m)) {
            return 'iPhone ' . trim($m[1]);
        }
        if (stripos($agent, 'iPhone') !== false) {
            return 'iPhone';
        }
        if (stripos($agent, 'iPad') !== false) {
            return 'iPad';
        }
        if (preg_match('/Pixel\s?(\w+)/i', $agent, $m)) {
            return 'Google Pixel ' . $m[1];
        }
        if (stripos($agent, 'Pixel') !== false) {
            return 'Google Pixel';
        }
        if (preg_match('/SM-([A-Z0-9]+)/i', $agent, $m)) {
            return 'Samsung ' . strtoupper($m[1]);
        }
        if (preg_match('/(OnePlus|Xiaomi|Huawei|OPPO|vivo|Redmi)[\/\s-]?([A-Za-z0-9]+)?/i', $agent, $m)) {
            return trim($m[1] . ' ' . ($m[2] ?? ''));
        }
        if ($os === 'Windows') {
            return 'Windows PC';
        }
        if ($os === 'macOS') {
            return 'Mac';
        }
        if ($os === 'Linux') {
            return 'Linux';
        }
        if ($os === 'Android') {
            return 'Android';
        }
        if ($os === 'iOS') {
            return 'iPhone';
        }

        return $device ?: 'Unknown';
    }

    public static function channelMeta(?string $channel): array
    {
        return match ($channel) {
            'portal_admin' => ['label' => 'Admin portal', 'icon' => 'fal fa-user-shield', 'tone' => 'admin'],
            'portal_client' => ['label' => 'Client portal', 'icon' => 'fal fa-user', 'tone' => 'client'],
            'mobile' => ['label' => 'Mobile app', 'icon' => 'fal fa-mobile-android', 'tone' => 'mobile'],
            default => ['label' => $channel ?: 'Portal', 'icon' => 'fal fa-globe', 'tone' => 'other'],
        };
    }

    public static function osIcon(?string $os, ?string $deviceName = null): string
    {
        $hay = strtolower(trim(($os ?: '') . ' ' . ($deviceName ?: '')));
        if (stripos($hay, 'windows') !== false) {
            return 'fab fa-windows';
        }
        if (stripos($hay, 'android') !== false || stripos($hay, 'pixel') !== false || stripos($hay, 'samsung') !== false) {
            return 'fab fa-android';
        }
        if (stripos($hay, 'linux') !== false) {
            return 'fab fa-linux';
        }
        if (stripos($hay, 'mac') !== false || stripos($hay, 'ios') !== false || stripos($hay, 'iphone') !== false || stripos($hay, 'ipad') !== false) {
            return 'fab fa-apple';
        }

        return 'fal fa-desktop';
    }

    public static function locationLabel(?string $city, ?string $country, ?string $region = null): string
    {
        $names = [
            'AE' => 'United Arab Emirates', 'GB' => 'United Kingdom', 'US' => 'United States',
            'SG' => 'Singapore', 'DE' => 'Germany', 'IN' => 'India', 'PK' => 'Pakistan',
            'FR' => 'France', 'CH' => 'Switzerland', 'SA' => 'Saudi Arabia',
            'CA' => 'Canada', 'AU' => 'Australia', 'NL' => 'Netherlands', 'IE' => 'Ireland',
            'IT' => 'Italy', 'ES' => 'Spain', 'BE' => 'Belgium', 'AT' => 'Austria',
            'SE' => 'Sweden', 'NO' => 'Norway', 'DK' => 'Denmark', 'FI' => 'Finland',
            'PL' => 'Poland', 'PT' => 'Portugal', 'QA' => 'Qatar', 'KW' => 'Kuwait',
            'BH' => 'Bahrain', 'OM' => 'Oman', 'EG' => 'Egypt', 'ZA' => 'South Africa',
            'NG' => 'Nigeria', 'KE' => 'Kenya', 'JP' => 'Japan', 'KR' => 'South Korea',
            'CN' => 'China', 'HK' => 'Hong Kong', 'MY' => 'Malaysia', 'TH' => 'Thailand',
            'PH' => 'Philippines', 'ID' => 'Indonesia', 'NZ' => 'New Zealand', 'BR' => 'Brazil',
            'MX' => 'Mexico', 'AR' => 'Argentina', 'TR' => 'Turkey', 'RU' => 'Russia',
        ];
        $countryName = $country ? ($names[strtoupper($country)] ?? strtoupper($country)) : null;
        $parts = array_values(array_filter([$city, $region && $region !== $city ? $region : null, $countryName]));

        return $parts ? implode(', ', $parts) : 'Unknown location';
    }

    public static function hydrateLogLocation($row): void
    {
        if (!$row || (!empty($row->country) && !empty($row->city))) {
            return;
        }
        $ip = $row->ip_v4 ?? $row->ip_address ?? null;
        $geo = IpGeo::lookup($ip);
        if (!$geo) {
            return;
        }
        $row->country = $row->country ?: $geo['country'];
        $row->region = $row->region ?: ($geo['region'] ?? null);
        $row->city = $row->city ?: ($geo['city'] ?? null);
        if (empty($row->latitude) && !empty($geo['latitude'])) {
            $row->latitude = $geo['latitude'];
            $row->longitude = $geo['longitude'] ?? null;
        }
        if (!empty($row->id)) {
            try {
                UserJourneyLog::query()->where('id', $row->id)->update([
                    'country' => $row->country,
                    'region' => $row->region,
                    'city' => $row->city,
                    'latitude' => $row->latitude ?? null,
                    'longitude' => $row->longitude ?? null,
                ]);
            } catch (\Throwable $e) {
            }
        }
    }

    public static function displayLocation($row): string
    {
        self::hydrateLogLocation($row);

        return self::locationLabel($row->city ?? null, $row->country ?? null, $row->region ?? null);
    }

    public static function ipsFromRequest(Request $request): array
    {
        $found = [];
        $add = function ($value) use (&$found) {
            if ($value === null || $value === '') {
                return;
            }
            if (stripos((string) $value, 'for=') !== false) {
                preg_match_all('/for=("?\[?)([^";\s\]]+)/i', (string) $value, $matches);
                foreach ($matches[2] ?? [] as $part) {
                    $add($part);
                }
                return;
            }
            foreach (preg_split('/[,\s]+/', (string) $value) as $raw) {
                $ip = trim($raw, "[] \t\"'");
                if ($ip === '' || strcasecmp($ip, 'unknown') === 0) {
                    continue;
                }
                if (preg_match('/^(\d{1,3}(?:\.\d{1,3}){3}):\d+$/', $ip, $m)) {
                    $ip = $m[1];
                }
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    $found[$ip] = true;
                }
            }
        };

        $add($request->ip());
        $add($request->server('REMOTE_ADDR'));
        foreach ([
            'CF-Connecting-IP',
            'True-Client-IP',
            'X-Real-IP',
            'X-Client-IP',
            'X-Forwarded-For',
            'Forwarded',
            'X-Forwarded',
        ] as $header) {
            $add($request->header($header));
        }

        $all = array_keys($found);
        $v4 = [];
        $v6 = [];
        $public = [];
        foreach ($all as $ip) {
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                $v4[] = $ip;
            } elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                $v6[] = $ip;
            }
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                $public[] = $ip;
            }
        }

        $primary = $request->ip();
        if (!filter_var((string) $primary, FILTER_VALIDATE_IP)) {
            $primary = $public[0] ?? ($v4[0] ?? ($v6[0] ?? ($all[0] ?? null)));
        }

        $others = array_values(array_filter($public, function ($ip) use ($primary) {
            return $ip !== $primary;
        }));
        if (!$others) {
            $others = array_values(array_filter($all, function ($ip) use ($primary) {
                return $ip !== $primary;
            }));
        }

        return [
            'primary' => $primary,
            'v4' => $v4[0] ?? (filter_var((string) $primary, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) ? $primary : null),
            'v6' => $v6[0] ?? (filter_var((string) $primary, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ? $primary : null),
            'vpn' => $others ? implode(', ', array_slice($others, 0, 4)) : null,
            'forwarded' => $all ? implode(', ', array_slice($all, 0, 8)) : null,
        ];
    }

    public static function formatIps($row): string
    {
        $lines = [];
        $v4 = $row->ip_v4 ?? null;
        $v6 = $row->ip_v6 ?? null;
        $vpn = $row->ip_vpn ?? null;
        $primary = $row->ip_address ?? null;
        if ($v4) {
            $lines[] = 'IPv4 ' . $v4;
        }
        if ($v6) {
            $lines[] = 'IPv6 ' . $v6;
        }
        if (!$v4 && !$v6 && $primary) {
            $lines[] = $primary;
        } elseif ($primary && $primary !== $v4 && $primary !== $v6) {
            $lines[] = $primary;
        }
        if ($vpn) {
            $lines[] = 'VPN / other ' . $vpn;
        }

        return $lines ? implode("\n", $lines) : '—';
    }

    public static function locationFromRequest(Request $request): array
    {
        $country = strtoupper((string) (
            $request->header('CF-IPCountry')
            ?: $request->header('X-AppEngine-Country')
            ?: $request->header('CloudFront-Viewer-Country')
            ?: ''
        ));
        if ($country === '' || $country === 'XX' || $country === 'T1') {
            $country = null;
        }

        return [
            'country' => $country ? substr($country, 0, 2) : null,
            'region' => $request->header('CF-Region') ?: null,
            'city' => $request->header('CF-IPCity') ?: null,
            'latitude' => self::decimalOrNull($request->header('CF-IPlatitude')),
            'longitude' => self::decimalOrNull($request->header('CF-IPlongitude')),
        ];
    }

    public static function record(Request $request, array $extra = []): void
    {
        if (!self::isEnabled()) {
            return;
        }

        try {
            $ua = self::parseUserAgent($request->userAgent());
            $headerName = trim((string) ($request->header('X-Device-Name') ?: $request->input('device_name') ?: ''));
            $headerModel = trim((string) ($request->header('X-Device-Model') ?: $request->input('device_model') ?: ''));
            if ($headerName !== '') {
                $ua['device_name'] = $headerModel && $headerModel !== $headerName
                    ? $headerName . ' · ' . $headerModel
                    : $headerName;
            } elseif ($headerModel !== '') {
                $ua['device_name'] = $headerModel;
            }
            $platform = strtolower((string) ($request->header('X-Device-Platform') ?: $request->input('device_platform') ?: ''));
            if ($platform === 'ios' || $platform === 'iphone') {
                $ua['os'] = 'iOS';
                $ua['device'] = 'Mobile';
                $ua['browser'] = 'iOS App';
            } elseif ($platform === 'android') {
                $ua['os'] = 'Android';
                $ua['device'] = 'Mobile';
                $ua['browser'] = 'Android App';
            }
            $geo = self::locationFromRequest($request);
            try {
                if ($request->hasSession()) {
                    if (empty($geo['latitude']) && $request->session()->has('uj_latitude')) {
                        $geo['latitude'] = $request->session()->get('uj_latitude');
                        $geo['longitude'] = $request->session()->get('uj_longitude');
                    }
                    if (empty($geo['city']) && $request->session()->has('uj_city')) {
                        $geo['city'] = $request->session()->get('uj_city');
                    }
                    if (empty($geo['country']) && $request->session()->has('uj_country')) {
                        $geo['country'] = $request->session()->get('uj_country');
                    }
                }
            } catch (\Throwable $e) {
            }
            if (isset($extra['latitude']) && is_numeric($extra['latitude'])) {
                $geo['latitude'] = (float) $extra['latitude'];
            }
            if (isset($extra['longitude']) && is_numeric($extra['longitude'])) {
                $geo['longitude'] = (float) $extra['longitude'];
            }
            $path = $extra['route_path'] ?? ('/' . ltrim($request->path(), '/'));
            if (strlen($path) > 255) {
                $path = substr($path, 0, 255);
            }
            $eventName = $extra['event_name'] ?? 'page_view';
            $sessionId = substr((string) self::sessionId($request, $extra), 0, 191);

            if (in_array($eventName, ['page_view', 'api_view'], true)
                && self::isRepeatPageView($request, $sessionId, $path)) {
                return;
            }

            if (empty($geo['country']) && $sessionId !== '') {
                $prior = UserJourneyLog::query()
                    ->where('session_id', $sessionId)
                    ->whereNotNull('country')
                    ->orderByDesc('id')
                    ->first(['country', 'region', 'city', 'latitude', 'longitude']);
                if ($prior) {
                    $geo['country'] = $geo['country'] ?: $prior->country;
                    $geo['region'] = $geo['region'] ?: $prior->region;
                    $geo['city'] = $geo['city'] ?: $prior->city;
                    $geo['latitude'] = $geo['latitude'] ?: $prior->latitude;
                    $geo['longitude'] = $geo['longitude'] ?: $prior->longitude;
                }
            }

            $ips = self::ipsFromRequest($request);
            if (empty($geo['country']) && self::ipLookupOnEvent()) {
                $fromIp = IpGeo::lookup($ips['primary'] ?: $request->ip());
                if ($fromIp) {
                    $geo['country'] = $geo['country'] ?: $fromIp['country'];
                    $geo['region'] = $geo['region'] ?: $fromIp['region'];
                    $geo['city'] = $geo['city'] ?: $fromIp['city'];
                    $geo['latitude'] = $geo['latitude'] ?: $fromIp['latitude'];
                    $geo['longitude'] = $geo['longitude'] ?: $fromIp['longitude'];
                }
            }
            $payload = [
                'user_id' => Auth::id() ?? ($extra['user_id'] ?? null),
                'session_id' => $sessionId,
                'route_path' => $path,
                'method' => strtoupper($extra['method'] ?? $request->method()),
                'user_agent' => substr((string) $request->userAgent(), 0, 255),
                'ip_address' => $ips['primary'] ?: ($request->ip() ?: '0.0.0.0'),
                'country' => $extra['country'] ?? $geo['country'],
                'region' => $extra['region'] ?? $geo['region'],
                'city' => $extra['city'] ?? $geo['city'],
                'latitude' => $extra['latitude'] ?? $geo['latitude'],
                'longitude' => $extra['longitude'] ?? $geo['longitude'],
                'device_type' => $ua['device'],
                'device_name' => $ua['device_name'] ?? null,
                'browser' => $ua['browser'],
                'os' => $ua['os'],
                'referrer' => substr((string) ($extra['referrer'] ?? $request->headers->get('referer')), 0, 255) ?: null,
                'page_title' => isset($extra['page_title']) ? substr((string) $extra['page_title'], 0, 160) : null,
                'channel' => $extra['channel'] ?? 'portal',
                'event_name' => $eventName,
                'screen_name' => isset($extra['screen_name']) ? substr((string) $extra['screen_name'], 0, 80) : null,
                'created_at' => now(),
            ];
            if (\Schema::hasColumn('user_journey_logs', 'ip_v4')) {
                $payload['ip_v4'] = $ips['v4'];
                $payload['ip_v6'] = $ips['v6'];
                $payload['ip_vpn'] = $ips['vpn'];
                $payload['ip_forwarded'] = $ips['forwarded'];
            }
            UserJourneyLog::create($payload);
        } catch (\Throwable $e) {
            Log::debug('User journey log skipped: ' . $e->getMessage());
        }
    }

    public static function recordHeat(Request $request, string $path, int $x, int $y, string $channel = 'portal'): void
    {
        if (!self::isEnabled()) {
            return;
        }

        try {
            UserJourneyHeatPoint::create([
                'user_id' => Auth::id(),
                'session_id' => substr((string) self::sessionId($request), 0, 191),
                'route_path' => substr($path, 0, 255),
                'x' => max(0, min(100, $x)),
                'y' => max(0, min(100, $y)),
                'channel' => $channel,
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::debug('User journey heat skipped: ' . $e->getMessage());
        }
    }

    public static function shouldSkip(Request $request): bool
    {
        if ($request->ajax() && !$request->is('user-trace/journey-beacon')) {
            return true;
        }

        $path = $request->path();
        $skip = [
            'user-trace/journey-beacon',
            'user-trace/journey-tab',
            'user-trace/journey-visit',
            'live-transactions',
            '_debugbar',
            'broadcasting/auth',
        ];
        foreach ($skip as $needle) {
            if (stripos($path, $needle) !== false) {
                return true;
            }
        }

        return (bool) preg_match('/\.(js|css|png|jpg|jpeg|gif|svg|woff2?|map)$/i', $path);
    }

    private static function normalizePath(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            return '/';
        }
        if (strpos($path, '?') !== false) {
            $path = substr($path, 0, strpos($path, '?'));
        }

        return '/' . trim($path, '/');
    }

    private static function isRepeatPageView(Request $request, string $sessionId, string $path): bool
    {
        $path = self::normalizePath($path);
        $cacheKey = 'uj_last_path:' . (Auth::id() ?: $sessionId ?: $request->ip());

        if ((string) Cache::get($cacheKey) === $path) {
            return true;
        }

        $referer = (string) $request->headers->get('referer');
        if ($referer !== '') {
            $refPath = parse_url($referer, PHP_URL_PATH) ?: '';
            if (self::normalizePath($refPath) === $path) {
                Cache::put($cacheKey, $path, 86400);

                return true;
            }
        }

        $last = UserJourneyLog::query()
            ->when(Auth::id(), function ($q) {
                $q->where('user_id', Auth::id());
            }, function ($q) use ($sessionId) {
                if ($sessionId !== '') {
                    $q->where('session_id', $sessionId);
                }
            })
            ->where(function ($q) {
                $q->whereNull('event_name')->orWhereIn('event_name', ['page_view', 'api_view']);
            })
            ->orderByDesc('id')
            ->value('route_path');

        if ($last && self::normalizePath((string) $last) === $path) {
            Cache::put($cacheKey, $path, 86400);

            return true;
        }

        Cache::put($cacheKey, $path, 86400);

        return false;
    }

    private static function sessionId(Request $request, array $extra = []): string
    {
        if (!empty($extra['session_id'])) {
            return (string) $extra['session_id'];
        }
        try {
            if ($request->hasSession()) {
                return (string) $request->session()->getId();
            }
        } catch (\Throwable $e) {
        }

        return (string) ($request->header('X-Session-Id') ?: $request->ip() ?: 'none');
    }

    private static function decimalOrNull($value): ?float
    {
        if ($value === null || $value === '' || !is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }
}
