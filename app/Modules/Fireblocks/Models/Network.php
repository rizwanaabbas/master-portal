<?php

namespace App\Modules\Fireblocks\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Currency; // 👈 IMPORTANT
class Network extends Model
{
    protected $fillable = [
        'code',
        'name',
        'logo_url',
        'is_testnet',
        'is_enabled',
        'currency_id',
    ];

    protected $casts = [
        'is_testnet' => 'boolean',
        'is_enabled' => 'boolean',
    ];

    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('is_enabled', true);
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function assets()
    {
        return $this->hasMany(Asset::class);
    }
    
    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }
    
}
