<?php

namespace App\Modules\FireblocksVaultSweeping\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FireblocksSweepLog extends Model
{
    protected $table = 'fireblocks_sweep_logs';

    protected $fillable = [
        'sweep_job_id',
        'source_vault_id',
        'asset_id',
        'level',
        'event',
        'message',
        'context',
    ];

    protected $casts = [
        'context' => 'array',
    ];

    public function job(): BelongsTo
    {
        return $this->belongsTo(FireblocksSweepJob::class, 'sweep_job_id');
    }
}
