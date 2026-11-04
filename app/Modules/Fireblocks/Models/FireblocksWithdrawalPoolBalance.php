<?php

namespace App\Modules\Fireblocks\Models;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FireblocksWithdrawalPoolBalance extends Model
{
    protected $table = 'fireblocks_withdrawal_pool_balances';

    protected $fillable = [
        'vault_account_id',
        'wallet_pool_id',
        'balance_source',
        'asset_id',
        'portal_asset_id',
        'currency_id',
        'total',
        'available',
        'pending',
        'raw_response',
    ];

    protected $casts = [
        'total' => 'decimal:18',
        'available' => 'decimal:18',
        'pending' => 'decimal:18',
        'raw_response' => 'array',
    ];

    public function portalAsset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'portal_asset_id');
    }

    /**
     * Portal catalog row matched by Fireblocks asset id (assets.asset_id).
     */
    public function catalogAsset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'asset_id', 'asset_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }
}
