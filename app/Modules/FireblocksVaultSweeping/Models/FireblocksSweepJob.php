<?php

namespace App\Modules\FireblocksVaultSweeping\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FireblocksSweepJob extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_QUEUED = 'queued';
    public const STATUS_WAITING_FOR_GAS = 'waiting_for_gas';
    public const STATUS_GAS_FUNDED = 'gas_funded';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_SKIPPED = 'skipped';

    public const IN_FLIGHT_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_QUEUED,
        self::STATUS_WAITING_FOR_GAS,
        self::STATUS_GAS_FUNDED,
        self::STATUS_SUBMITTED,
    ];

    /** Fireblocks submission actively in progress — always blocks same vault/asset. */
    public const ACTIVE_FIREBLOCKS_STATUSES = [
        self::STATUS_WAITING_FOR_GAS,
        self::STATUS_GAS_FUNDED,
        self::STATUS_SUBMITTED,
    ];

    /** Local evaluation / delayed queue — may block only same deposit or stale rows. */
    public const LOCAL_IN_FLIGHT_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_QUEUED,
    ];

    /** Jobs the reconcile CLI / recovery may poll (includes failed without token tx). */
    public static function statusesEligibleForReconcile(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_QUEUED,
            self::STATUS_WAITING_FOR_GAS,
            self::STATUS_GAS_FUNDED,
            self::STATUS_SUBMITTED,
            self::STATUS_FAILED,
        ];
    }

    protected $table = 'fireblocks_sweep_jobs';

    protected $fillable = [
        'rule_id',
        'source_vault_id',
        'destination_vault_id',
        'asset_id',
        'gas_asset_id',
        'trigger_source',
        'deposit_fireblocks_tx_id',
        'status',
        'skip_reason',
        'fireblocks_tx_id',
        'fireblocks_status',
        'fireblocks_sub_status',
        'fireblocks_status_updated_at',
        'completed_at',
        'failed_at',
        'amount',
        'planned_sweep_amount',
        'gas_required_amount',
        'gas_feed_amount',
        'gas_funding_vault_id',
        'gas_feed_fireblocks_tx_id',
        'gas_feed_external_tx_id',
        'fee_estimate',
        'external_tx_id',
    ];

    protected $casts = [
        'amount' => 'decimal:18',
        'planned_sweep_amount' => 'decimal:18',
        'gas_required_amount' => 'decimal:18',
        'gas_feed_amount' => 'decimal:18',
        'fee_estimate' => 'decimal:18',
        'fireblocks_status_updated_at' => 'datetime',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    public function rule(): BelongsTo
    {
        return $this->belongsTo(FireblocksSweepRule::class, 'rule_id')->withTrashed();
    }

    public function logs(): HasMany
    {
        return $this->hasMany(FireblocksSweepLog::class, 'sweep_job_id')->orderByDesc('created_at');
    }
}
