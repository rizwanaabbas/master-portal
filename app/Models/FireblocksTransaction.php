<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FireblocksTransaction extends Model
{
    use HasFactory;

    /** Custody audit note — no client AccountTransaction / TRRequest is created. */
    public const CUSTODY_ONLY_NO_LEDGER_NOTE = 'Custody only — deposit address not in ledger (sweep eligible)';

    /** TRON Station gas feed into a client vault — operational custody only, never a client deposit. */
    public const CUSTODY_ONLY_SWEEP_GAS_FEED_NOTE = 'Custody only — TRON Station gas feed (no ledger credit)';

    public static function isCustodyOnlyWithoutLedger(?self $transaction): bool
    {
        if ($transaction === null) {
            return false;
        }

        $note = trim((string) ($transaction->note ?? ''));

        return $note === self::CUSTODY_ONLY_NO_LEDGER_NOTE
            || $note === self::CUSTODY_ONLY_SWEEP_GAS_FEED_NOTE
            || str_contains($note, 'Custody only — deposit address not in ledger')
            || str_contains($note, 'Custody only — TRON Station gas feed');
    }

    protected $table = 'fireblocks_transactions';

    protected $fillable = [
        'fireblocks_tx_id',
        'asset_id',
        'source_vault_account_id',
        'destination_address',
        'destination_tag',
        'amount',
        'amount_usd',
        'status',
        'sub_status',
        'chainalysis_status',
        'chainalysis_scan_url',
        'internal_status',
        'monitoring_state',
        'direction',
        'operation',
        'is_sweep',
        'sweep_job_id',
        'stuck_detected_at',
        'last_polled_at',
        'last_webhook_at',
        'auto_action_count',
        'pending_action',
        'last_reattempt_at',
        'root_fireblocks_tx_id',
        'parent_fireblocks_tx_id',
        'replaced_by_fireblocks_tx_id',
        'tx_hash',
        'fee',
        'note',
        'external_tx_id',
        'customer_ref_id',
        'error_message',
        'pending_reason',
        'response_data',
        'wallet_pool_id',
        'initiator_user_id',
        'signed_by_user_ids',
        'tap_approver_user_ids',
    ];

    protected $casts = [
        'amount' => 'decimal:18',
        'amount_usd' => 'decimal:8',
        'response_data' => 'array',
        'signed_by_user_ids' => 'array',
        'tap_approver_user_ids' => 'array',
        'is_sweep' => 'boolean',
        'stuck_detected_at' => 'datetime',
        'last_polled_at' => 'datetime',
        'last_webhook_at' => 'datetime',
        'last_reattempt_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function accountTransaction()
    {
        return $this->hasOne(AccountTransaction::class, 'crypto_transaction_id', 'fireblocks_tx_id')
            ->where('crypto_system', 'fireblocks');
    }

    public function monitoringLogs()
    {
        return $this->hasMany(
            \App\Modules\FireblocksTransactionsMonitoring\Models\FireblocksTransactionMonitoringLog::class,
            'fireblocks_tx_id',
            'fireblocks_tx_id'
        )->orderByDesc('created_at');
    }

    public function alerts()
    {
        return $this->hasMany(
            \App\Modules\FireblocksTransactionsMonitoring\Models\FireblocksTransactionAlert::class,
            'fireblocks_tx_id',
            'fireblocks_tx_id'
        )->orderByDesc('created_at');
    }
}
