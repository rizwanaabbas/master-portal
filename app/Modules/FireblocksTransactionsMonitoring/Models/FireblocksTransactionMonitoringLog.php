<?php

namespace App\Modules\FireblocksTransactionsMonitoring\Models;

use Illuminate\Database\Eloquent\Model;

class FireblocksTransactionMonitoringLog extends Model
{
    protected $table = 'fireblocks_transaction_monitoring_logs';

    protected $fillable = [
        'fireblocks_tx_id',
        'action',
        'trigger_source',
        'previous_status',
        'new_status',
        'previous_sub_status',
        'new_sub_status',
        'request_payload',
        'response_payload',
        'success',
        'error_message',
        'performed_by',
        'idempotency_key',
    ];

    protected $casts = [
        'request_payload' => 'array',
        'response_payload' => 'array',
        'success' => 'boolean',
    ];
}
