<?php

namespace App\Modules\FeeEngine\Models;

use App\Models\Account;
use App\Models\Client;
use App\Models\Currency;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeeTransactionCharge extends Model
{
    protected $table = 'fee_transaction_charges';

    protected $fillable = [
        'transaction_reference',
        'transaction_type',
        'b2c2_order_id',
        'tr_request_id',
        'client_id',
        'account_id',
        'currency_id',
        'direction',
        'fee_tier_group_id',
        'currency_fee_tier_id',
        'fee_label',
        'fee_type',
        'fee_rate',
        'transaction_amount',
        'fee_amount',
        'crypto_fee_display',
        'status',
        'meta',
    ];

    protected $casts = [
        'fee_rate'           => 'decimal:8',
        'transaction_amount' => 'decimal:8',
        'fee_amount'         => 'decimal:8',
        'meta'               => 'array',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(FeeTierGroup::class, 'fee_tier_group_id');
    }

    public function currencyFeeTier(): BelongsTo
    {
        return $this->belongsTo(CurrencyFeeTier::class, 'currency_fee_tier_id');
    }
}
