<?php

namespace App\Modules\FireblocksVaultSweeping\Models;

use App\Modules\FireblocksVaultSweeping\Support\SweepAssetMetadata;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FireblocksSweepRule extends Model
{
    use SoftDeletes;

    public const SWEEP_AMOUNT_SOURCE_MAX_SPENDABLE = 'max_spendable';

    public const SWEEP_AMOUNT_SOURCE_AVAILABLE = 'available';

    public const FEE_LEVELS = ['LOW', 'MEDIUM', 'HIGH'];

    protected $table = 'fireblocks_sweep_rules';

    protected $fillable = [
        'name',
        'priority',
        'enabled',
        'source_type',
        'source_vault_ids',
        'destination_vault_id',
        'asset_ids',
        'target_asset_id',
        'gas_funding_vault_id',
        'feed_trx_amount',
        'use_gasless',
        'treat_as_gross_amount',
        'min_balance',
        'scheduled_min_balance',
        'schedule_interval_minutes',
        'schedule_run_at',
        'schedule_last_run_at',
        'min_balance_usd',
        'reserve_amount',
        'sweep_amount_source',
        'trigger_modes',
        'deposit_delay_minutes',
        'sweep_orphan_deposits',
        'fee_level',
        'max_fee_usd',
        'skip_if_fee_high',
        'sweep_tokens_before_base',
        'dry_run',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'min_balance' => 'decimal:18',
        'scheduled_min_balance' => 'decimal:18',
        'schedule_interval_minutes' => 'integer',
        'schedule_last_run_at' => 'datetime',
        'feed_trx_amount' => 'decimal:18',
        'use_gasless' => 'boolean',
        'treat_as_gross_amount' => 'boolean',
        'min_balance_usd' => 'decimal:8',
        'reserve_amount' => 'decimal:18',
        'max_fee_usd' => 'decimal:8',
        'skip_if_fee_high' => 'boolean',
        'sweep_tokens_before_base' => 'boolean',
        'dry_run' => 'boolean',
        'sweep_orphan_deposits' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    public function jobs(): HasMany
    {
        return $this->hasMany(FireblocksSweepJob::class, 'rule_id');
    }

    public function scopeActive($query)
    {
        return $query->where('enabled', true);
    }

    public function isRunnable(): bool
    {
        return (bool) $this->enabled && !$this->trashed();
    }

    public function triggerModeList(): array
    {
        $raw = strtolower(str_replace(' ', '', (string) $this->trigger_modes));

        return array_values(array_filter(explode(',', $raw)));
    }

    public function supportsTrigger(string $mode): bool
    {
        return in_array(strtolower($mode), $this->triggerModeList(), true);
    }

    public function assetIdList(): array
    {
        $raw = trim((string) $this->asset_ids);
        if ($raw === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', preg_split('/[\s,]+/', $raw) ?: [])));
    }

    public function sourceVaultIdList(): array
    {
        $raw = trim((string) $this->source_vault_ids);
        if ($raw === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', preg_split('/[\s,]+/', $raw) ?: [])));
    }

    public function sourceAssetId(): ?string
    {
        $list = $this->assetIdList();

        return $list[0] ?? null;
    }

    public function targetAssetId(): ?string
    {
        $target = trim((string) $this->target_asset_id);

        return $target !== '' ? $target : $this->sourceAssetId();
    }

    public function sweepAmountSource(): string
    {
        $source = strtolower(trim((string) ($this->sweep_amount_source ?? '')));

        return $source === self::SWEEP_AMOUNT_SOURCE_MAX_SPENDABLE
            ? self::SWEEP_AMOUNT_SOURCE_MAX_SPENDABLE
            : self::SWEEP_AMOUNT_SOURCE_AVAILABLE;
    }

    public function usesAvailableBalanceForSweep(): bool
    {
        return $this->sweepAmountSource() === self::SWEEP_AMOUNT_SOURCE_AVAILABLE;
    }

    public function sweepAmountSourceLabel(): string
    {
        return match ($this->sweepAmountSource()) {
            self::SWEEP_AMOUNT_SOURCE_AVAILABLE => 'Available balance',
            default => 'Max spendable (Fireblocks API)',
        };
    }

    /**
     * Whether this rule submits ERC-20 sweeps with Fireblocks useGasless (Universal Gasless relay).
     */
    public function shouldUseGasless(?string $sourceAssetId = null): bool
    {
        if (!(bool) $this->use_gasless) {
            return false;
        }

        $assetId = trim((string) ($sourceAssetId ?? $this->sourceAssetId() ?? ''));

        return $assetId !== '' && SweepAssetMetadata::usesEvmGasRelay($assetId);
    }

    /**
     * Native/base asset sweeps: Fireblocks deducts network fee from the requested amount (treatAsGrossAmount).
     */
    public function shouldTreatAsGrossAmount(?string $sourceAssetId = null): bool
    {
        if (!(bool) $this->treat_as_gross_amount) {
            return false;
        }

        $assetId = trim((string) ($sourceAssetId ?? $this->sourceAssetId() ?? ''));

        return $assetId !== '' && SweepAssetMetadata::isNativeAccountAsset($assetId);
    }

    /**
     * Fireblocks feeLevel for estimate_fee and create transaction (LOW, MEDIUM, HIGH).
     */
    public function feeLevel(): string
    {
        $level = strtoupper(trim((string) ($this->fee_level ?? 'LOW')));

        return in_array($level, self::FEE_LEVELS, true) ? $level : 'LOW';
    }

    /**
     * When enabled, deposit-triggered sweeps may run even if the inbound address
     * could not be matched to a client ledger account (orphan custody deposit).
     */
    public function sweepsOrphanDeposits(): bool
    {
        return (bool) $this->sweep_orphan_deposits;
    }
}
