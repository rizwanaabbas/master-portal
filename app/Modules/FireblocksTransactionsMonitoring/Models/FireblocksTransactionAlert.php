<?php

namespace App\Modules\FireblocksTransactionsMonitoring\Models;

use Illuminate\Database\Eloquent\Model;

class FireblocksTransactionAlert extends Model
{
    protected $table = 'fireblocks_transaction_alerts';

    protected $fillable = [
        'fireblocks_tx_id',
        'event_type',
        'issue_type',
        'severity',
        'vault_account_id',
        'base_asset',
        'blocked_transaction_count',
        'stuck_duration_seconds',
        'recommended_action',
        'payload',
        'status',
        'action_taken',
        'action_taken_at',
        'dismissed_by',
        'dismissed_at',
    ];

    protected $casts = [
        'recommended_action' => 'array',
        'payload' => 'array',
        'action_taken_at' => 'datetime',
        'dismissed_at' => 'datetime',
    ];
}
