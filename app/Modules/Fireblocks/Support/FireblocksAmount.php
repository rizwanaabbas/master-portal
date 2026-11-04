<?php

namespace App\Modules\Fireblocks\Support;

use App\Models\FireblocksTransaction;

/**
 * Fireblocks often omits top-level `amount` on outgoing transfers (or sends 0)
 * until confirmation. Incoming deposits usually have `amount` / `amountInfo.amount`.
 * Resolve a positive quantity from the row plus stored/API payload.
 */
class FireblocksAmount
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromPayload(array $payload): ?string
    {
        $info = is_array($payload['amountInfo'] ?? null) ? $payload['amountInfo'] : [];

        $candidates = [
            $payload['amount'] ?? null,
            $info['amount'] ?? null,
            $info['requestedAmount'] ?? null,
            $info['netAmount'] ?? null,
            $payload['requestedAmount'] ?? null,
            $payload['netAmount'] ?? null,
        ];

        foreach ($candidates as $value) {
            if (!self::isPositiveNumeric($value)) {
                continue;
            }

            return self::normalizeNumericString($value);
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromRecord(FireblocksTransaction $record, array $payload = []): ?string
    {
        if (self::isPositiveNumeric($record->amount)) {
            return self::trimDecimals(self::normalizeNumericString($record->amount));
        }

        $fromPayload = self::fromPayload(self::flattenRecordPayload($record, $payload));
        if ($fromPayload !== null) {
            return self::trimDecimals($fromPayload);
        }

        return self::isNumericString($record->amount) ? self::normalizeNumericString($record->amount) : null;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function assetFromRecord(FireblocksTransaction $record, array $payload = []): string
    {
        $asset = trim((string) ($record->asset_id ?? ''));
        if ($asset !== '' && strtoupper($asset) !== 'UNKNOWN') {
            return $asset;
        }

        $flat = self::flattenRecordPayload($record, $payload);
        foreach (['assetId', 'asset_id', 'asset'] as $key) {
            $candidate = trim((string) ($flat[$key] ?? ''));
            if ($candidate !== '' && strtoupper($candidate) !== 'UNKNOWN') {
                return $candidate;
            }
        }

        $fromLedger = self::assetFromLedger($record);
        if ($fromLedger !== '') {
            return $fromLedger;
        }

        return $asset;
    }

    /**
     * Fill amount / asset_id on the model when the row is empty or zero
     * but the payload has a real quantity. Never overwrite a positive amount with 0.
     *
     * @param  array<string, mixed>  $payload
     */
    public static function applyToRecord(FireblocksTransaction $record, array $payload = []): void
    {
        $resolved = self::fromPayload(self::flattenRecordPayload($record, $payload));
        if ($resolved !== null && !self::isPositiveNumeric($record->amount)) {
            $record->amount = $resolved;
        }

        $asset = self::assetFromRecord($record, $payload);
        if ($asset !== '' && (trim((string) $record->asset_id) === '' || strtoupper((string) $record->asset_id) === 'UNKNOWN')) {
            $record->asset_id = $asset;
        }

        $usd = self::usdAtTxFromRecord($record, $payload);
        if ($usd !== null && self::canStoreAmountUsd($record) && !self::isPositiveNumeric($record->amount_usd ?? null)) {
            $record->amount_usd = $usd;
        }
        if (
            self::canStoreAmountUsd($record)
            && !self::isPositiveNumeric($record->amount_usd ?? null)
        ) {
            $snap = self::snapshotUsd(self::fromRecord($record, $payload), self::assetFromRecord($record, $payload));
            if ($snap !== null) {
                $record->amount_usd = $snap;
            }
        }
    }

    /**
     * USD at submit/display time: CoinGecko cached spot × amount, or $1 for USDT/USDC.
     */
    public static function snapshotUsd($amount, string $assetId): ?float
    {
        if ($amount === null || $amount === '' || !is_numeric($amount) || (float) $amount <= 0 || trim($assetId) === '') {
            return null;
        }
        $spot = null;
        try {
            $spot = CoinGeckoUsdPriceService::usdForAsset($assetId);
        } catch (\Throwable $e) {
            $spot = null;
        }
        if (($spot === null || $spot <= 0) && CoinGeckoUsdPriceService::isUsdStableAsset($assetId)) {
            $spot = 1.0;
        }
        if ($spot === null || $spot <= 0) {
            return null;
        }

        return round((float) $amount * (float) $spot, 8);
    }

    /**
     * Fireblocks amountInfo.amountUSD (static). Prefer persisted amount_usd.
     *
     * @param  array<string, mixed>  $payload
     */
    public static function usdAtTxFromRecord(FireblocksTransaction $record, array $payload = []): ?float
    {
        if (self::isPositiveNumeric($record->amount_usd ?? null)) {
            return (float) $record->amount_usd;
        }

        $flat = self::flattenRecordPayload($record, $payload);
        $info = is_array($flat['amountInfo'] ?? null) ? $flat['amountInfo'] : [];
        foreach ([$info['amountUSD'] ?? null, $flat['amountUSD'] ?? null] as $value) {
            if (self::isPositiveNumeric($value)) {
                return (float) $value;
            }
        }

        return null;
    }

    /**
     * Live USD = amount × CoinGecko spot. Null if unmapped or API miss.
     */
    public static function usdNowFromRecord(FireblocksTransaction $record, array $payload = []): ?float
    {
        $amount = self::fromRecord($record, $payload);
        $asset = self::assetFromRecord($record, $payload);
        if ($amount === null || !is_numeric($amount) || $asset === '') {
            return null;
        }
        try {
            $spot = CoinGeckoUsdPriceService::usdForAsset($asset);
        } catch (\Throwable $e) {
            $spot = null;
        }
        if (($spot === null || $spot <= 0) && CoinGeckoUsdPriceService::isUsdStableAsset($asset)) {
            $spot = 1.0;
        }
        if ($spot === null || $spot <= 0) {
            return null;
        }

        return (float) $amount * $spot;
    }

    public static function formatPlain(FireblocksTransaction $record, array $payload = []): string
    {
        $amount = self::fromRecord($record, $payload);
        $asset = self::assetFromRecord($record, $payload);
        $amountFmt = $amount !== null ? self::trimDecimals($amount) : '—';
        $line = trim($amountFmt . ' ' . $asset);
        $txUsd = self::usdAtTxFromRecord($record, $payload);
        $nowUsd = self::usdNowFromRecord($record, $payload);
        if ($txUsd !== null) {
            $line .= ' | Tx $' . self::formatUsd($txUsd);
        }
        if ($nowUsd !== null) {
            $line .= ' | Now $' . self::formatUsd($nowUsd);
        }

        return $line;
    }

    public static function formatHtml(FireblocksTransaction $record, array $payload = []): string
    {
        $amount = self::fromRecord($record, $payload);
        $asset = self::assetFromRecord($record, $payload);
        $amountFmt = $amount !== null ? e(self::trimDecimals($amount)) : '<span class="text-muted">—</span>';
        $assetHtml = $asset !== '' ? ' <small>' . e($asset) . '</small>' : '';
        $txUsd = self::usdAtTxFromRecord($record, $payload);
        $nowUsd = self::usdNowFromRecord($record, $payload);

        $html = '<div>' . $amountFmt . $assetHtml;
        $html .= '<div class="small text-muted" title="USD value stored from Fireblocks amountInfo.amountUSD at transaction time">Tx: '
            . ($txUsd !== null ? '$' . e(self::formatUsd($txUsd)) . ' USD' : '—') . '</div>';
        $html .= '<div class="small text-muted" title="Current USD from CoinGecko spot × amount">Now: '
            . ($nowUsd !== null ? '$' . e(self::formatUsd($nowUsd)) . ' USD' : '—') . '</div>';
        $html .= '</div>';

        return $html;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public static function flattenRecordPayload(FireblocksTransaction $record, array $payload = []): array
    {
        $stored = is_array($record->response_data) ? $record->response_data : [];
        $layers = [];

        foreach (['webhook_bootstrap', 'data', 'transaction'] as $nestedKey) {
            if (!isset($stored[$nestedKey]) || !is_array($stored[$nestedKey])) {
                continue;
            }
            $layers[] = $stored[$nestedKey];
        }

        $layers[] = $stored;
        $layers[] = $payload;

        $merged = [];
        foreach ($layers as $layer) {
            $merged = array_merge($merged, $layer);
        }

        return $merged;
    }

    /**
     * Outgoing withdrawals often persist amount from the portal request but leave
     * Fireblocks asset_id empty (SDK create response omits assetId). Recover it
     * from the linked TR + Fireblocks asset map, else the ledger currency short code.
     */
    protected static function assetFromLedger(FireblocksTransaction $record): string
    {
        try {
            $tr = null;
            $externalId = trim((string) ($record->external_tx_id ?? ''));
            if ($externalId !== '' && ctype_digit($externalId)) {
                $tr = \App\Models\TRRequest::query()->find((int) $externalId);
            }
            if (!$tr && !empty($record->fireblocks_tx_id)) {
                $tr = \App\Models\TRRequest::query()
                    ->where('crypto_transaction_id', $record->fireblocks_tx_id)
                    ->orderByDesc('id')
                    ->first();
            }
            if (!$tr || empty($tr->currency_id)) {
                return '';
            }

            $assetQuery = \App\Modules\Fireblocks\Models\Asset::query()
                ->where('currency_id', $tr->currency_id);
            $networkCode = trim((string) ($tr->crypto_network ?? ''));
            if ($networkCode !== '') {
                $assetQuery->whereHas('network', function ($q) use ($networkCode) {
                    $q->where('code', $networkCode);
                });
            }
            $mapped = $assetQuery->orderByDesc('is_enabled')->first();
            if ($mapped && trim((string) $mapped->asset_id) !== '') {
                return trim((string) $mapped->asset_id);
            }

            $currency = \App\Models\Currency::query()->find($tr->currency_id);

            return trim((string) ($currency->short ?? ''));
        } catch (\Throwable $e) {
            return '';
        }
    }

    protected static function isPositiveNumeric($value): bool
    {
        return self::isNumericString($value) && (float) $value > 0;
    }

    protected static function isNumericString($value): bool
    {
        if ($value === null || $value === '' || is_bool($value) || is_array($value)) {
            return false;
        }

        return is_numeric($value);
    }

    protected static function normalizeNumericString($value): string
    {
        if (is_int($value) || is_float($value)) {
            return rtrim(rtrim(sprintf('%.18F', $value), '0'), '.') ?: '0';
        }

        return trim((string) $value);
    }

    protected static function trimDecimals(string $amount): string
    {
        if (!is_numeric($amount)) {
            return $amount;
        }

        return rtrim(rtrim(number_format((float) $amount, 8, '.', ''), '0'), '.') ?: '0';
    }

    protected static function formatUsd(float $usd): string
    {
        if ($usd == 0.0) {
            return '0.00';
        }

        if (abs($usd) >= 0.01) {
            return number_format($usd, 2, '.', ',');
        }

        return rtrim(rtrim(number_format($usd, 8, '.', ''), '0'), '.') ?: '0.00';
    }

    protected static function canStoreAmountUsd(FireblocksTransaction $record): bool
    {
        try {
            return \Illuminate\Support\Facades\Schema::hasColumn($record->getTable(), 'amount_usd');
        } catch (\Throwable $e) {
            return false;
        }
    }
}
