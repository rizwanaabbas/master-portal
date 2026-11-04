<?php

namespace App\Modules\Liquidity\Models;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Balance extends Model
{
    protected $table = 'liquidity_balances';

    protected $fillable = [
        'provider',
        'currency',
        'currency_id',
        'amount',
        'hold',
        'available',
        'raw_response',
    ];

    protected $casts = [
        'amount' => 'decimal:8',
        'hold' => 'decimal:8',
        'available' => 'decimal:8',
        'raw_response' => 'array',
    ];

    public function scopeProvider(Builder $query, string $provider): Builder
    {
        return $query->where('provider', $provider);
    }

    /** Balances whose provider currency code matches an active portal currency. */
    public function scopeEnabledCurrenciesOnly(Builder $query): Builder
    {
        return $query->whereIn('liquidity_balances.currency', function ($sub) {
            $sub->select('short')
                ->from('currencies')
                ->where('is_active', true);
        });
    }

    public function scopeJoinActiveCurrency(Builder $query): Builder
    {
        return $query
            ->join('currencies', 'currencies.short', '=', 'liquidity_balances.currency')
            ->where('currencies.is_active', true)
            ->select('liquidity_balances.*');
    }

    // Named `currencyInfo` to avoid clashing with the `currency` string column.
    public function currencyInfo(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }
}
