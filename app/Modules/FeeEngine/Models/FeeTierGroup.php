<?php

namespace App\Modules\FeeEngine\Models;

use App\Models\Client;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FeeTierGroup extends Model
{
    use SoftDeletes;

    protected $table = 'fee_tier_groups';

    protected $fillable = [
        'name',
        'code',
        'description',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active'  => 'boolean',
    ];

    public function currencyFeeTiers(): HasMany
    {
        return $this->hasMany(CurrencyFeeTier::class, 'fee_tier_group_id');
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class, 'fee_tier_group_id');
    }

    /**
     * The group that acts as fallback for clients without an explicit assignment.
     */
    public static function default(): ?self
    {
        return static::where('is_default', true)->where('is_active', true)->first();
    }
}
