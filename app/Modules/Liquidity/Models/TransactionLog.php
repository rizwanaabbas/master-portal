<?php

namespace App\Modules\Liquidity\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class TransactionLog extends Model
{
    protected $table = 'liquidity_transaction_logs';

    protected $fillable = [
        'provider',
        'endpoint',
        'method',
        'request_payload',
        'response_payload',
        'response_status',
        'status',
        'error_message',
    ];

    protected $casts = [
        'request_payload' => 'array',
        'response_payload' => 'array',
    ];

    public function scopeProvider(Builder $query, string $provider): Builder
    {
        return $query->where('provider', $provider);
    }
}
