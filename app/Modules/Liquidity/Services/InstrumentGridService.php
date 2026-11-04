<?php

namespace App\Modules\Liquidity\Services;

use App\Modules\Liquidity\Models\Instrument;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class InstrumentGridService
{
    public function baseQuery(string $provider): Builder
    {
        return Instrument::query()
            ->provider($provider)
            ->select('liquidity_instruments.*')
            ->join('currencies as from_cur', 'from_cur.id', '=', 'liquidity_instruments.from_currency_id')
            ->join('currencies as to_cur', 'to_cur.id', '=', 'liquidity_instruments.to_currency_id')
            ->where('from_cur.is_active', true)
            ->where('to_cur.is_active', true)
            ->with(['fromCurrency', 'toCurrency']);
    }

    public function applyFilters(Builder $query, Request $request): void
    {
        if ($code = trim((string) $request->input('currency_code'))) {
            $query->where(function (Builder $q) use ($code) {
                $q->where('from_cur.short', $code)
                    ->orWhere('to_cur.short', $code);
            });
        }

        if ($type = trim((string) $request->input('instrument_type'))) {
            $query->where('liquidity_instruments.instrument_type', $type);
        }

        $portalStatus = $request->input('portal_status');
        if ($portalStatus === 'active') {
            $query->where('liquidity_instruments.is_active', true);
        } elseif ($portalStatus === 'inactive') {
            $query->where('liquidity_instruments.is_active', false);
        }

        $assetType = $request->input('asset_type');
        if ($assetType === 'crypto') {
            $query->where(function (Builder $q) {
                $q->where('from_cur.is_crypto', true)
                    ->orWhere('to_cur.is_crypto', true);
            });
        } elseif ($assetType === 'fiat') {
            $query->where('from_cur.is_crypto', false)
                ->where('to_cur.is_crypto', false);
        }
    }

    public function applySearch(Builder $query, ?string $search): void
    {
        if ($search === null || $search === '') {
            return;
        }

        $like = '%' . $search . '%';
        $query->where(function (Builder $q) use ($like) {
            $q->where('liquidity_instruments.instrument', 'like', $like)
                ->orWhere('liquidity_instruments.name', 'like', $like)
                ->orWhere('liquidity_instruments.instrument_type', 'like', $like)
                ->orWhere('from_cur.short', 'like', $like)
                ->orWhere('from_cur.name', 'like', $like)
                ->orWhere('to_cur.short', 'like', $like)
                ->orWhere('to_cur.name', 'like', $like);
        });
    }

    public static function formatDecimal(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        return rtrim(rtrim(number_format((float) $value, 8, '.', ''), '0'), '.') ?: '0';
    }
}
