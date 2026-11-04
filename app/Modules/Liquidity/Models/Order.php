<?php

namespace App\Modules\Liquidity\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Order extends Model
{
    protected $table = 'liquidity_orders';

    protected $fillable = [
        'provider',
        'order_id',
        'client_order_id',
        'rfq_id',
        'created_by',
        'instrument',
        'side',
        'quantity',
        'price',
        'sub_total',
        'fee',
        'fee_type',
        'fee_rate',
        'grand_total',
        'fiat_currency_id',
        'crypto_currency_id',
        'tr_request_id',
        'dr_tr_id',
        'cr_tr_id',
        'filled_amount',
        'remaining_amount',
        'order_type',
        'status',
        'error_message',
        'raw_request',
        'raw_response',
    ];

    protected $casts = [
        'quantity' => 'decimal:8',
        'price' => 'decimal:8',
        'sub_total' => 'decimal:8',
        'fee' => 'decimal:8',
        'fee_rate' => 'decimal:8',
        'grand_total' => 'decimal:8',
        'filled_amount' => 'decimal:8',
        'remaining_amount' => 'decimal:8',
        'raw_request' => 'array',
        'raw_response' => 'array',
    ];

    public function scopeProvider(Builder $query, string $provider): Builder
    {
        return $query->where('provider', $provider);
    }
}
