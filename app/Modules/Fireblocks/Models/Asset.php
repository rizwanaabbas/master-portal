<?php

namespace App\Modules\Fireblocks\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Currency; // 👈 IMPORTANT
class Asset extends Model
{
    protected $fillable = [
        'currency_id',
        'network_id',
        'asset_id',
        'name',
        'logo_url',
        'is_testnet',
        'is_enabled',
        'type',
        'vault_account_id',
        'contract_address',
    ];

    protected $casts = [
        'is_testnet' => 'boolean',
        'is_enabled' => 'boolean',
    ];

    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('is_enabled', true);
    }

    public function scopeAssetsEnabled($query)
    {
        return $query->where($this->qualifyColumn('is_enabled'), true);
        // or: return $query->where('assets.is_enabled', true);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function network()
    {
        return $this->belongsTo(Network::class);
    }
}
