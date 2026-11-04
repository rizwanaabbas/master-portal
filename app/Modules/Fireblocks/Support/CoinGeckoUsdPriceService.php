<?php

namespace App\Modules\Fireblocks\Support;

use App\Helpers\Common;
use App\Models\Currency;
use App\Models\Module;
use App\Models\ModuleConfiguration;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Spot USD prices for monitoring (CoinGecko simple/price). Cached per process.
 * Aquanow RFQ is not used — that creates tradable quotes.
 */
class CoinGeckoUsdPriceService
{
    public const CACHE_KEY_PREFIX = 'fb_cg_usd_coin:';

    /**
     * @param  array<int, string>  $fireblocksAssetIds
     * @return array<string, float> geckoId => usd
     */
    public static function pricesUsdByGeckoId(array $fireblocksAssetIds): array
    {
        $ids = [];
        foreach ($fireblocksAssetIds as $asset) {
            $id = self::geckoIdForAsset((string) $asset);
            if ($id !== null) {
                $ids[$id] = true;
            }
        }
        $ids = array_keys($ids);
        if ($ids === []) {
            return [];
        }

        $out = [];
        $missing = [];
        foreach ($ids as $id) {
            $cached = Cache::get(self::CACHE_KEY_PREFIX . $id);
            if (is_numeric($cached) && (float) $cached > 0) {
                $out[$id] = (float) $cached;
            } else {
                $missing[] = $id;
            }
        }

        if ($missing !== []) {
            $fromTable = self::usdRatesFromCurrencyTable($missing);
            $stillMissing = [];
            foreach ($missing as $id) {
                if (isset($fromTable[$id])) {
                    $out[$id] = $fromTable[$id];
                } else {
                    $stillMissing[] = $id;
                }
            }
            if ($stillMissing !== []) {
                $fetched = self::fetchSimplePrice($stillMissing);
                $ttl = self::cacheTtl();
                foreach ($fetched as $id => $usd) {
                    if (!is_numeric($usd) || (float) $usd <= 0) {
                        continue;
                    }
                    $out[$id] = (float) $usd;
                    Cache::put(self::CACHE_KEY_PREFIX . $id, (float) $usd, $ttl);
                }
                self::persistRatesOnEnabledCurrencies($fetched);
            }
        }

        return $out;
    }

    public static function usdForAsset(string $fireblocksAssetId): ?float
    {
        $id = self::geckoIdForAsset($fireblocksAssetId);
        if ($id === null) {
            return null;
        }
        $prices = self::pricesUsdByGeckoId([$fireblocksAssetId]);

        return isset($prices[$id]) ? (float) $prices[$id] : null;
    }

    public static function geckoIdForAsset(string $assetId): ?string
    {
        $a = strtoupper(trim($assetId));
        if ($a === '' || $a === 'UNKNOWN') {
            return null;
        }

        $map = [
            'BTC' => 'bitcoin',
            'BTC_TEST' => 'bitcoin',
            'ETH' => 'ethereum',
            'ETH_TEST3' => 'ethereum',
            'ETH_TEST5' => 'ethereum',
            'ETH_TEST6' => 'ethereum',
            'SOL' => 'solana',
            'SOL_TEST' => 'solana',
            'XRP' => 'ripple',
            'TRX' => 'tron',
            'TRX_TEST' => 'tron',
            'BNB' => 'binancecoin',
            'BNB_BSC' => 'binancecoin',
            'AVAX' => 'avalanche-2',
            'MATIC' => 'matic-network',
            'MATIC_POLYGON' => 'matic-network',
            'POL_POLYGON_POS' => 'matic-network',
        ];
        if (isset($map[$a])) {
            return $map[$a];
        }
        // Fireblocks token ids are often NETWORK_TOKEN (TRX_USDT_S2UZ, USDT_ERC20).
        if (str_contains($a, 'USDT')) {
            return 'tether';
        }
        if (str_contains($a, 'USDC')) {
            return 'usd-coin';
        }
        if (str_starts_with($a, 'ETH')) {
            return 'ethereum';
        }
        if (str_starts_with($a, 'BTC')) {
            return 'bitcoin';
        }
        if (str_starts_with($a, 'SOL')) {
            return 'solana';
        }

        return null;
    }

    public static function isUsdStableAsset(string $fireblocksAssetId): bool
    {
        $a = strtoupper(trim($fireblocksAssetId));

        return str_contains($a, 'USDT') || str_contains($a, 'USDC');
    }

    /**
     * Pull CoinGecko simple/price for the catalog and write per-coin cache keys
     * used by Transaction Monitoring "Now" USD.
     *
     * @return array{count: int, ids: array<int, string>}
     */
    public static function refreshCachedPrices(): array
    {
        $currencies = self::enabledCryptoCurrencies();
        $ids = [];
        foreach ($currencies as $currency) {
            $gid = self::geckoIdForCurrency($currency);
            if ($gid !== null) {
                $ids[$gid] = true;
            }
        }
        $ids = array_keys($ids);

        $fetched = self::fetchSimplePrice($ids);
        if ($fetched === [] && $ids !== []) {
            $fetched = self::fetchSimplePrice($ids, true);
        }
        $ttl = self::cacheTtl();
        $stored = [];
        foreach ($fetched as $id => $usd) {
            if (!is_numeric($usd) || (float) $usd <= 0) {
                continue;
            }
            Cache::put(self::CACHE_KEY_PREFIX . $id, (float) $usd, $ttl);
            $stored[] = $id;
        }
        self::persistRatesOnEnabledCurrencies($fetched, $currencies);
        Cache::put(self::CACHE_KEY_PREFIX . 'refreshed_at', now()->toIso8601String(), $ttl);

        return ['count' => count($stored), 'ids' => $stored];
    }

    /**
     * @return array<int, string>
     */
    public static function catalogGeckoIds(): array
    {
        $ids = [];
        foreach (self::enabledCryptoCurrencies() as $currency) {
            $gid = self::geckoIdForCurrency($currency);
            if ($gid !== null) {
                $ids[] = $gid;
            }
        }
        $ids = array_values(array_unique(array_filter($ids, function ($id) {
            return is_string($id) && preg_match('/^[a-z0-9-]+$/', $id);
        })));
        sort($ids);

        return $ids;
    }

    /**
     * Enabled crypto rows from `currencies` (the From-currency set).
     *
     * @return Collection<int, Currency>
     */
    public static function enabledCryptoCurrencies(): Collection
    {
        try {
            $query = Currency::query()
                ->where('is_crypto', true)
                ->where('is_active', true);
            if (Schema::hasTable('assets')) {
                $query->with('assets');
            }

            return $query->get();
        } catch (\Throwable $e) {
            return collect();
        }
    }

    public static function geckoIdForCurrency(Currency $currency): ?string
    {
        $fromShort = self::geckoIdForAsset((string) $currency->short);
        if ($fromShort !== null) {
            return $fromShort;
        }
        $assets = $currency->relationLoaded('assets') ? $currency->assets : collect();
        foreach ($assets as $asset) {
            $id = self::geckoIdForAsset((string) ($asset->asset_id ?? ''));
            if ($id !== null) {
                return $id;
            }
        }

        return null;
    }

    /**
     * Write CoinGecko USD spots onto enabled crypto currency rows.
     *
     * @param  array<string, float>  $geckoToUsd
     * @param  Collection<int, Currency>|null  $currencies
     */
    protected static function persistRatesOnEnabledCurrencies(array $geckoToUsd, $currencies = null): void
    {
        if (!Schema::hasColumn('currencies', 'usd_rate')) {
            return;
        }
        $currencies = $currencies ?? self::enabledCryptoCurrencies();
        $now = now();
        foreach ($currencies as $currency) {
            $gid = self::geckoIdForCurrency($currency);
            $usd = $gid !== null ? ($geckoToUsd[$gid] ?? null) : null;
            if ((!is_numeric($usd) || (float) $usd <= 0) && self::isUsdStableAsset((string) $currency->short)) {
                $usd = 1.0;
            }
            if (!is_numeric($usd) || (float) $usd <= 0) {
                continue;
            }
            Currency::query()->where('id', $currency->id)->update([
                'usd_rate' => (float) $usd,
                'usd_rate_at' => $now,
            ]);
        }
    }

    /**
     * @param  array<int, string>  $geckoIds
     * @return array<string, float>
     */
    protected static function usdRatesFromCurrencyTable(array $geckoIds): array
    {
        if ($geckoIds === [] || !Schema::hasColumn('currencies', 'usd_rate')) {
            return [];
        }
        $wanted = array_flip($geckoIds);
        $out = [];
        foreach (self::enabledCryptoCurrencies() as $currency) {
            $gid = self::geckoIdForCurrency($currency);
            if ($gid === null || !isset($wanted[$gid])) {
                continue;
            }
            if (is_numeric($currency->usd_rate) && (float) $currency->usd_rate > 0) {
                $out[$gid] = (float) $currency->usd_rate;
            }
        }

        return $out;
    }

    /**
     * @return array<int, string>
     */
    protected static function configSupportedAssets(): array
    {
        if (!Common::isModuleEnabled('CoinGecko')) {
            return [];
        }
        $module = Module::where('name', 'CoinGecko')->first();
        $record = $module
            ? ModuleConfiguration::where('module_id', $module->id)->first()
            : null;
        $cfg = $record ? @unserialize($record->configurations) : [];
        if (!is_array($cfg)) {
            return [];
        }
        $raw = self::cfgVal($cfg, 'supported_assets', '');
        if (is_array($raw)) {
            $raw = implode(',', $raw);
        }
        $parts = preg_split('/[\s,]+/', strtolower(trim((string) $raw)), -1, PREG_SPLIT_NO_EMPTY);

        return is_array($parts) ? $parts : [];
    }

    /**
     * @param  array<int, string>  $geckoIds
     * @return array<string, float>
     */
    protected static function fetchSimplePrice(array $geckoIds, bool $publicOnly = false): array
    {
        $geckoIds = array_values(array_unique(array_filter($geckoIds, function ($id) {
            return is_string($id) && preg_match('/^[a-z0-9-]+$/', $id);
        })));
        if ($geckoIds === []) {
            return [];
        }

        $config = self::config();
        if ($publicOnly) {
            $config['base'] = 'https://api.coingecko.com/api/v3';
            $config['api_key'] = '';
            $config['use_pro'] = false;
        }
        $base = rtrim((string) $config['base'], '/');
        $url = $base . '/simple/price';
        $query = [
            'ids' => implode(',', $geckoIds),
            'vs_currencies' => 'usd',
        ];
        $headers = ['Accept' => 'application/json'];
        $apiKey = trim((string) $config['api_key']);
        if ($apiKey !== '') {
            if (!empty($config['use_pro'])) {
                $headers['x-cg-pro-api-key'] = $apiKey;
            } else {
                $headers['x-cg-demo-api-key'] = $apiKey;
            }
        }

        try {
            $response = Http::timeout($config['timeout'])
                ->withHeaders($headers)
                ->get($url, $query);
            if (!$response->successful()) {
                $body = substr((string) $response->body(), 0, 400);
                Log::warning('CoinGecko simple/price HTTP ' . $response->status(), [
                    'url' => $url,
                    'ids' => $query['ids'],
                    'use_pro' => !empty($config['use_pro']),
                    'has_key' => $apiKey !== '',
                    'body' => $body,
                ]);

                return [];
            }
            $json = $response->json();
            if (!is_array($json)) {
                return [];
            }
            $out = [];
            foreach ($geckoIds as $id) {
                $usd = $json[$id]['usd'] ?? null;
                if (is_numeric($usd)) {
                    $out[$id] = (float) $usd;
                }
            }

            return $out;
        } catch (\Throwable $e) {
            Log::warning('CoinGecko simple/price error', ['error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * @return array{base: string, api_key: string, timeout: int, use_pro: bool}
     */
    protected static function config(): array
    {
        $base = 'https://api.coingecko.com/api/v3';
        $apiKey = '';
        $timeout = 8;
        $usePro = false;
        try {
            if (Common::isModuleEnabled('CoinGecko')) {
                $module = Module::where('name', 'CoinGecko')->first();
                $record = $module
                    ? ModuleConfiguration::where('module_id', $module->id)->first()
                    : null;
                $cfg = $record ? @unserialize($record->configurations) : [];
                if (is_array($cfg)) {
                    $useProVal = self::cfgVal($cfg, 'use_pro_api', '0');
                    $usePro = $useProVal === '1' || $useProVal === 1 || $useProVal === true || $useProVal === 'true';
                    $apiKey = trim((string) self::cfgVal($cfg, 'api_key', ''));
                    $configuredBase = trim((string) (self::cfgVal($cfg, 'api_base_url', '') ?: ''));
                    if ($usePro) {
                        $base = 'https://pro-api.coingecko.com/api/v3';
                    } elseif ($configuredBase !== '') {
                        $base = rtrim($configuredBase, '/');
                    }
                    $timeout = (int) self::cfgVal($cfg, 'timeout', $timeout);
                    if ($usePro && $apiKey === '') {
                        $usePro = false;
                        $base = 'https://api.coingecko.com/api/v3';
                    }
                }
            }
        } catch (\Throwable $e) {
            // public API fallback
        }

        return [
            'base' => $base,
            'api_key' => $apiKey,
            'timeout' => max(3, $timeout),
            'use_pro' => $usePro,
        ];
    }

    protected static function cacheTtl(): int
    {
        try {
            if (!Common::isModuleEnabled('CoinGecko')) {
                return 300;
            }
            $module = Module::where('name', 'CoinGecko')->first();
            $record = $module
                ? ModuleConfiguration::where('module_id', $module->id)->first()
                : null;
            $cfg = $record ? @unserialize($record->configurations) : [];
            if (is_array($cfg)) {
                $n = (int) self::cfgVal($cfg, 'cache_duration', 300);

                return max(300, $n);
            }
        } catch (\Throwable $e) {
        }

        return 300;
    }

    protected static function cfgVal(array $cfg, string $key, $default = null)
    {
        if (!array_key_exists($key, $cfg)) {
            return $default;
        }
        $v = $cfg[$key];
        if (is_array($v) && array_key_exists('value', $v)) {
            return $v['value'];
        }

        return $v;
    }
}
