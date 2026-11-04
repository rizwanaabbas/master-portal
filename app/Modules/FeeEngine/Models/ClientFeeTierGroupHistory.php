<?php

namespace App\Modules\FeeEngine\Models;

use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientFeeTierGroupHistory extends Model
{
    protected $table = 'client_fee_tier_group_history';

    protected $fillable = [
        'client_id',
        'fee_tier_group_id',
        'changed_by',
        'effective_from',
        'effective_to',
        'reason',
    ];

    protected $casts = [
        'effective_from' => 'datetime',
        'effective_to'   => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(FeeTierGroup::class, 'fee_tier_group_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
