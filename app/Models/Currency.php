<?php

namespace App\Models;

use App\Modules\Fireblocks\Models\Asset;
use App\Modules\Fireblocks\Models\Network;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    use HasFactory;
    protected $table = "currencies";
    protected $fillable = [
        'short',
        'name',
        'is_crypto',
        'is_active',
        'usd_rate',
        'usd_rate_at',
    ];

    protected $casts = [
        'usd_rate' => 'float',
        'usd_rate_at' => 'datetime',
    ];
    public function maintenance()
    {
        return $this->hasOne(PerCurrencyMaintenanceFee::class, 'currency_id', 'id');
    }

    public function bankSSI()
    {
        return $this->hasMany(BanksSSI::class, 'currency_id', 'id');
    }
    public function assets()
    {
        return $this->hasMany(Asset::class);
    }
    
    public function networks()
    {
        return $this->hasMany(Network::class);
    }
}
