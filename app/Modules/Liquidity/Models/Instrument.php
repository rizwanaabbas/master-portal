<?php

namespace App\Modules\Liquidity\Models;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Instrument extends Model
{
    protected $table = 'liquidity_instruments';

    protected $fillable = [
        'provider',
        'instrument',
        'name',
        'instrument_type',
        'from_currency_id',
        'to_currency_id',
        'is_active',
        'raw_response',
    ];

    protected $casts = [
        'is_active'    => 'boolean',
        'raw_response' => 'array',
    ];

    public function scopeProvider(Builder $query, string $provider): Builder
    {
        return $query->where('provider', $provider);
    }

    /**
     * Match a currency pair regardless of which side is "from" and which is
     * "to". An instrument like XRP-USD is stored once (e.g. from=XRP,
     * to=USD) but is the same tradeable pair for both buy (USD -> XRP) and
     * sell (XRP -> USD) — the ticker never flips, so lookups by the two
     * accounts' currencies must check both orderings.
     */
    public function scopeForCurrencyPair(Builder $query, ?int $currencyIdA, ?int $currencyIdB): Builder
    {
        return $query->where(function (Builder $q) use ($currencyIdA, $currencyIdB) {
            $q->where(function (Builder $q2) use ($currencyIdA, $currencyIdB) {
                $q2->where('from_currency_id', $currencyIdA)->where('to_currency_id', $currencyIdB);
            })->orWhere(function (Builder $q2) use ($currencyIdA, $currencyIdB) {
                $q2->where('from_currency_id', $currencyIdB)->where('to_currency_id', $currencyIdA);
            });
        });
    }

    // Trade constraints exposed by the provider's API, stored inside raw_response.
    public function getMinQuantityAttribute()
    {
        return $this->raw_response['min_quantity_per_trade'] ?? null;
    }

    public function getMaxQuantityAttribute()
    {
        return $this->raw_response['max_quantity_per_trade'] ?? null;
    }

    public function getPriceSignificantDigitsAttribute()
    {
        return $this->raw_response['price_significant_digits'] ?? null;
    }

    public function fromCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'from_currency_id');
    }

    public function toCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'to_currency_id');
    }
}
