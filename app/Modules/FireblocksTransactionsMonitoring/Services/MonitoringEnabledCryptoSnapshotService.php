<?php

namespace App\Modules\FireblocksTransactionsMonitoring\Services;

use App\Models\Currency;
use App\Modules\Fireblocks\Models\FireblocksWithdrawalPoolBalance;
use App\Modules\Fireblocks\Support\CoinGeckoUsdPriceService;
use App\Modules\Liquidity\Models\Balance as LiquidityBalance;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class MonitoringEnabledCryptoSnapshotService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function currencyRates(): array
    {
        $rows = [];
        foreach (CoinGeckoUsdPriceService::enabledCryptoCurrencies() as $currency) {
            $geckoId = CoinGeckoUsdPriceService::geckoIdForCurrency($currency);
            $usd = null;
            if ($geckoId) {
                $cached = Cache::get(CoinGeckoUsdPriceService::CACHE_KEY_PREFIX . $geckoId);
                if (is_numeric($cached) && (float) $cached > 0) {
                    $usd = (float) $cached;
                }
            }
            if ($usd === null && is_numeric($currency->usd_rate ?? null) && (float) $currency->usd_rate > 0) {
                $usd = (float) $currency->usd_rate;
            }

            $rows[] = [
                'logo' => (string) ($currency->logo ?? ''),
                'short' => (string) $currency->short,
                'name' => (string) $currency->name,
                'usd_rate' => $usd,
                'source' => $usd !== null ? 'CoinGecko' : null,
                'updated_at' => optional($currency->usd_rate_at)->toDateTimeString(),
            ];
        }

        usort($rows, fn ($a, $b) => strcasecmp($a['short'], $b['short']));

        return $rows;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function fireblocksBalances(string $source = 'wallet_pool'): array
    {
        $currencies = CoinGeckoUsdPriceService::enabledCryptoCurrencies();
        $ids = $currencies->pluck('id')->filter()->values()->all();
        $shorts = $currencies->pluck('short')->map(fn ($s) => strtoupper((string) $s))->all();
        $assetIds = [];
        foreach ($currencies as $currency) {
            foreach ($currency->assets ?? [] as $asset) {
                $aid = trim((string) ($asset->asset_id ?? ''));
                if ($aid !== '') {
                    $assetIds[] = $aid;
                }
            }
        }

        if (!Schema::hasTable('fireblocks_withdrawal_pool_balances') || ($ids === [] && $assetIds === [])) {
            return [];
        }

        $query = FireblocksWithdrawalPoolBalance::query()->with(['currency', 'portalAsset']);
        $this->applyFireblocksBalanceSource($query, $source);
        $query->where(function ($q) use ($ids, $assetIds) {
            if ($ids !== []) {
                $q->whereIn('currency_id', $ids);
            }
            if ($assetIds !== []) {
                $q->orWhereIn('asset_id', $assetIds);
            }
        });

        $byId = $currencies->keyBy('id');
        $byShort = $currencies->keyBy(fn ($c) => strtoupper((string) $c->short));

        $rows = [];
        foreach ($query->orderBy('asset_id')->get() as $row) {
            $currency = $row->currency ?: ($byId[$row->currency_id] ?? null);
            if (!$currency) {
                $shortGuess = strtoupper((string) preg_replace('/_.*$/', '', (string) $row->asset_id));
                $currency = $byShort[$shortGuess] ?? null;
            }
            if (!$currency && $shorts !== []) {
                continue;
            }

            $rate = $this->usdRateForCurrency($currency);
            $availableQty = is_numeric($row->available) ? (float) $row->available : null;

            $rows[] = [
                'logo' => (string) ($currency->logo ?? optional($row->portalAsset)->logo_url ?? ''),
                'short' => (string) ($currency->short ?? $row->asset_id),
                'name' => (string) ($currency->name ?? $row->asset_id),
                'asset_id' => (string) $row->asset_id,
                'available' => $this->fmtQty($row->available),
                'available_usd' => $this->usdValue($availableQty, $rate),
                'total' => $this->fmtQty($row->total),
                'pending' => $this->fmtQty($row->pending),
                'updated_at' => optional($row->updated_at)->toDateTimeString(),
            ];
        }

        return $rows;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function aquanowBalances(): array
    {
        if (!Schema::hasTable('liquidity_balances')) {
            return [];
        }

        $crypto = CoinGeckoUsdPriceService::enabledCryptoCurrencies();
        $fiat = Currency::query()->where('is_crypto', false)->where('is_active', true)->get();
        $cryptoById = $crypto->keyBy('id');
        $cryptoByShort = $crypto->keyBy(fn ($c) => strtoupper((string) $c->short));
        $fiatById = $fiat->keyBy('id');
        $fiatByShort = $fiat->keyBy(fn ($c) => strtoupper((string) $c->short));

        $rows = [];
        foreach (LiquidityBalance::query()->provider('aquanow')->orderBy('currency')->get() as $row) {
            $short = strtoupper((string) $row->currency);
            $currency = ($cryptoById[$row->currency_id] ?? null)
                ?: ($fiatById[$row->currency_id] ?? null)
                ?: ($cryptoByShort[$short] ?? null)
                ?: ($fiatByShort[$short] ?? null);
            if (!$currency) {
                continue;
            }

            $rate = $this->usdRateForCurrency($currency);
            $totalQty = is_numeric($row->amount) ? (float) $row->amount : null;

            $rows[] = [
                'logo' => (string) ($currency->logo ?? ''),
                'short' => (string) $currency->short,
                'name' => (string) $currency->name,
                'total' => $this->fmtQty($row->amount),
                'total_amount' => $totalQty,
                'total_usd' => $this->usdValue($totalQty, $rate),
                'updated_at' => optional($row->updated_at)->toDateTimeString(),
            ];
        }

        usort($rows, function (array $a, array $b) {
            $left = $a['total_amount'];
            $right = $b['total_amount'];
            if ($left === null && $right === null) {
                return 0;
            }
            if ($left === null) {
                return 1;
            }
            if ($right === null) {
                return -1;
            }

            return $right <=> $left;
        });

        return $rows;
    }

    /**
     * @return array{last_refreshed_at: ?string, refresh_interval: string}
     */
    public function currencyRatesMeta(): array
    {
        $cached = Cache::get(CoinGeckoUsdPriceService::CACHE_KEY_PREFIX . 'refreshed_at');
        $at = is_string($cached) && $cached !== '' ? $cached : null;
        if ($at === null && Schema::hasColumn('currencies', 'usd_rate_at')) {
            $latest = CoinGeckoUsdPriceService::enabledCryptoCurrencies()
                ->pluck('usd_rate_at')
                ->filter()
                ->sortDesc()
                ->first();
            $at = $latest ? (is_string($latest) ? $latest : $latest->toDateTimeString()) : null;
        }

        return [
            'last_refreshed_at' => $this->fmtWhen($at),
            'refresh_interval' => 'Scheduled every 5 minutes',
        ];
    }

    /**
     * @return array{last_refreshed_at: ?string, refresh_interval: string}
     */
    public function fireblocksBalancesMeta(string $source = 'wallet_pool'): array
    {
        $at = null;
        if (Schema::hasTable('fireblocks_withdrawal_pool_balances')) {
            $query = FireblocksWithdrawalPoolBalance::query();
            $this->applyFireblocksBalanceSource($query, $source);
            $at = $query->max('updated_at');
        }

        return [
            'last_refreshed_at' => $this->fmtWhen($at),
            'refresh_interval' => 'Scheduled hourly',
        ];
    }

    protected function applyFireblocksBalanceSource($query, string $source): void
    {
        if ($source === 'omnibus') {
            $query->whereIn('balance_source', ['source_vault', 'utxo_vault']);

            return;
        }

        $query->where('balance_source', 'wallet_pool_member');
    }

    /**
     * @return array{last_refreshed_at: ?string, refresh_interval: string}
     */
    public function aquanowBalancesMeta(): array
    {
        $at = null;
        if (Schema::hasTable('liquidity_balances')) {
            $at = LiquidityBalance::query()->provider('aquanow')->max('updated_at');
        }

        return [
            'last_refreshed_at' => $this->fmtWhen($at),
            'refresh_interval' => 'Scheduled hourly',
        ];
    }

    protected function fmtWhen($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        try {
            return \Carbon\Carbon::parse($value)->format('d M, Y H:i');
        } catch (\Throwable $e) {
            return (string) $value;
        }
    }

    protected function usdRateForCurrency($currency): ?float
    {
        if (!$currency) {
            return null;
        }
        if (!$currency->is_crypto && strtoupper((string) $currency->short) === 'USD') {
            return 1.0;
        }
        $geckoId = CoinGeckoUsdPriceService::geckoIdForCurrency($currency);
        if ($geckoId) {
            $cached = Cache::get(CoinGeckoUsdPriceService::CACHE_KEY_PREFIX . $geckoId);
            if (is_numeric($cached) && (float) $cached > 0) {
                return (float) $cached;
            }
        }
        if (is_numeric($currency->usd_rate ?? null) && (float) $currency->usd_rate > 0) {
            return (float) $currency->usd_rate;
        }

        return CoinGeckoUsdPriceService::isUsdStableAsset((string) $currency->short) ? 1.0 : null;
    }

    protected function usdValue(?float $qty, ?float $rate): ?float
    {
        if ($qty === null || $rate === null || $qty < 0 || $rate <= 0) {
            return null;
        }

        return round($qty * $rate, 2);
    }

    protected function fmtQty($value): string
    {
        if (!is_numeric($value)) {
            return '—';
        }

        return rtrim(rtrim(number_format((float) $value, 8, '.', ''), '0'), '.') ?: '0';
    }
}
