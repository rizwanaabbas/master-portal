<?php

namespace App\Modules\FeeEngine\Models;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CurrencyFeeTier extends Model
{
    use SoftDeletes;

    protected $table = 'currency_fee_tiers';

    protected $fillable = [
        'fee_tier_group_id',
        'currency_id',
        'currency_scope',
        'label',
        'fee_type',
        'fee_value',
        'min_fee',
        'max_fee',
        'priority',
        'is_active',
    ];

    protected $casts = [
        'fee_value' => 'decimal:8',
        'min_fee'   => 'decimal:8',
        'max_fee'   => 'decimal:8',
        'priority'  => 'integer',
        'is_active' => 'boolean',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(FeeTierGroup::class, 'fee_tier_group_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public function isWildcard(): bool
    {
        return $this->currency_scope !== 'currency';
    }

    public function scopeLabel(): string
    {
        return match ($this->currency_scope) {
            'all_crypto' => 'All crypto',
            'all_fiat'   => 'All fiat',
            default      => optional($this->currency)->short ?? '—',
        };
    }
}
