<?php

namespace App\Modules\Liquidity\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Rfq extends Model
{
    protected $table = 'liquidity_rfqs';

    protected $fillable = [
        'provider',
        'rfq_id',
        'client_rfq_id',
        'instrument',
        'side',
        'quantity',
        'price',
        'status',
        'created_by',
        'valid_until',
        'created',
        'raw_request',
        'raw_response',
    ];

    protected $casts = [
        'quantity' => 'decimal:8',
        'price' => 'decimal:8',
        'valid_until' => 'datetime',
        'created' => 'datetime',
        'raw_request' => 'array',
        'raw_response' => 'array',
    ];

    public function scopeProvider(Builder $query, string $provider): Builder
    {
        return $query->where('provider', $provider);
    }
}
