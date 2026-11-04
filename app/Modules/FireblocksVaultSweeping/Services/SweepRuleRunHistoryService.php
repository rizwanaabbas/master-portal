<?php

namespace App\Modules\FireblocksVaultSweeping\Services;

use App\Modules\FireblocksVaultSweeping\Models\FireblocksSweepJob;
use App\Modules\FireblocksVaultSweeping\Models\FireblocksSweepRule;
use App\Modules\FireblocksVaultSweeping\Support\SweepDestination;
use Illuminate\Support\Collection;

class SweepRuleRunHistoryService
{
    public function __construct(
        protected SweepBalanceService $balances,
    ) {
    }

    /**
     * Aggregate summary for a rule (full job table, not capped).
     *
     * @return array{
     *     total: int,
     *     submitted: int,
     *     skipped: int,
     *     failed: int,
     *     completed: int,
     *     last_run_at: ?string,
     *     by_trigger: array{deposit: int, scheduled: int, manual: int}
     * }
     */
    public function summaryForRule(FireblocksSweepRule $rule): array
    {
        $base = FireblocksSweepJob::query()->where('rule_id', $rule->id);

        $lastJob = (clone $base)->orderByDesc('id')->first(['created_at']);

        return [
            'total' => (clone $base)->count(),
            'submitted' => (clone $base)->whereIn('status', [
                FireblocksSweepJob::STATUS_SUBMITTED,
                FireblocksSweepJob::STATUS_WAITING_FOR_GAS,
                FireblocksSweepJob::STATUS_COMPLETED,
            ])->count(),
            'skipped' => (clone $base)->where('status', FireblocksSweepJob::STATUS_SKIPPED)->count(),
            'failed' => (clone $base)->where('status', FireblocksSweepJob::STATUS_FAILED)->count(),
            'completed' => (clone $base)->where('status', FireblocksSweepJob::STATUS_COMPLETED)->count(),
            'last_run_at' => $lastJob?->created_at?->toDateTimeString(),
            'by_trigger' => [
                'deposit' => (clone $base)->where('trigger_source', 'deposit')->count(),
                'scheduled' => (clone $base)->where('trigger_source', 'scheduled')->count(),
                'manual' => (clone $base)->where('trigger_source', 'manual')->count(),
            ],
        ];
    }

    /**
     * @return array{
     *     jobs: Collection<int, FireblocksSweepJob>,
     *     summary: array{total: int, submitted: int, skipped: int, failed: int, completed: int, last_run_at: ?string, by_trigger: array{deposit: int, scheduled: int, manual: int}},
     *     last_job: ?FireblocksSweepJob,
     *     limit: int
     * }
     */
    public function forRule(FireblocksSweepRule $rule, int $limit = 100): array
    {
        $jobs = FireblocksSweepJob::query()
            ->where('rule_id', $rule->id)
            ->with(['logs' => fn ($q) => $q->orderByDesc('id')->limit(8)])
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        $summary = $this->summaryForRule($rule);

        return [
            'jobs' => $jobs,
            'summary' => $summary,
            'last_job' => $jobs->first(),
            'limit' => $limit,
        ];
    }

    public function vaultLabel(string $vaultId): string
    {
        return $this->balances->vaultDisplayLabel($vaultId);
    }

    public function humanizeSkipReason(?string $reason): string
    {
        $reason = trim((string) $reason);

        if (str_starts_with($reason, 'superseded_by_job_')) {
            return 'Superseded by a newer sweep for the same vault/asset';
        }

        return match ($reason) {
            'dry_run' => 'Dry run — no Fireblocks transaction sent',
            'duplicate_external_tx_id' => 'Blocked by idempotency (deposit replay or in-flight job with same key)',
            'in_flight' => 'Another sweep in progress for this vault/asset (see job logs for blocking job id)',
            'stale_in_flight' => 'Previous sweep job was stuck in pending/queued and was released',
            'stale_submitted' => 'Previous submitted sweep timed out and was released',
            'stale_waiting_for_gas' => 'Gas feed took longer than the configured wait window',
            'missing_fireblocks_tx_id' => 'Reconciliation cleared job before gas feed completed (retry via gas webhook if gas still pending)',
            'missing_fireblocks_tx_id' => 'Submitted sweep job had no Fireblocks transaction id',
            'orphan_deposit_no_ledger' => 'Orphan deposit — no ledger match',
            'orphan_sweep_disabled' => 'Orphan deposit — matching rule has Sweep orphan deposits disabled',
            'below_threshold' => 'Balance below threshold',
            'insufficient_after_reserve' => 'Sweep amount computed as zero',
            'invalid_destination' => 'Destination omnibus not configured',
            'rule_disabled' => 'Rule is disabled or archived',
            'insufficient_native_gas' => 'Source vault needs native gas (e.g. ETH_TEST5) — fund source vault manually',
            'zero_native_gas_balance' => 'Native gas wallet exists but balance is zero',
            'native_gas_wallet_missing' => 'Native gas asset not enabled on source vault',
            'gas_estimate_failed' => 'Fireblocks fee estimate failed',
            'gas_funding_vault_missing_asset' => 'Gas funding vault missing native gas asset wallet',
            'gas_funding_vault_insufficient' => 'Gas funding vault has insufficient native gas',
            'insufficient_after_gas_feed' => 'No balance after gas feed',
            '' => '—',
            default => $reason,
        };
    }

    public function statusLabel(string $status): string
    {
        return match ($status) {
            FireblocksSweepJob::STATUS_SUBMITTED => 'Submitted',
            FireblocksSweepJob::STATUS_WAITING_FOR_GAS => 'Waiting for gas',
            FireblocksSweepJob::STATUS_COMPLETED => 'Completed',
            FireblocksSweepJob::STATUS_FAILED => 'Failed',
            FireblocksSweepJob::STATUS_SKIPPED => 'Skipped',
            FireblocksSweepJob::STATUS_PENDING => 'Pending',
            FireblocksSweepJob::STATUS_QUEUED => 'Queued',
            default => ucfirst($status),
        };
    }

    public function statusBadgeClass(string $status): string
    {
        return match ($status) {
            FireblocksSweepJob::STATUS_SUBMITTED, FireblocksSweepJob::STATUS_COMPLETED => 'badge-success',
            FireblocksSweepJob::STATUS_WAITING_FOR_GAS, FireblocksSweepJob::STATUS_QUEUED, FireblocksSweepJob::STATUS_PENDING => 'badge-info',
            FireblocksSweepJob::STATUS_FAILED => 'badge-danger',
            FireblocksSweepJob::STATUS_SKIPPED => 'badge-secondary',
            default => 'badge-light',
        };
    }

    public function formatFireblocksStatus(?FireblocksSweepJob $job): string
    {
        if ($job === null || empty($job->fireblocks_status)) {
            return '—';
        }

        $label = $job->fireblocks_status;
        if (!empty($job->fireblocks_sub_status)) {
            $label .= ' / ' . $job->fireblocks_sub_status;
        }

        return $label;
    }

    public function formatStatusUpdatedAt(?FireblocksSweepJob $job): ?string
    {
        return $job?->fireblocks_status_updated_at?->format('Y-m-d H:i:s');
    }

    public function triggerLabel(?string $triggerSource): string
    {
        return match (strtolower(trim((string) $triggerSource))) {
            'deposit' => 'Deposit webhook',
            'scheduled' => 'Scheduled',
            'manual' => 'Manual',
            default => $triggerSource !== '' && $triggerSource !== null ? ucfirst((string) $triggerSource) : '—',
        };
    }

    public function triggerBadgeClass(?string $triggerSource): string
    {
        return match (strtolower(trim((string) $triggerSource))) {
            'deposit' => 'badge-primary',
            'scheduled' => 'badge-info',
            'manual' => 'badge-dark',
            default => 'badge-light',
        };
    }

    public function formatDepositTxId(?FireblocksSweepJob $job): ?string
    {
        $txId = trim((string) ($job?->deposit_fireblocks_tx_id ?? ''));

        return $txId !== '' ? $txId : null;
    }

    public function formatJobDetails(FireblocksSweepJob $job): string
    {
        if ($job->status === FireblocksSweepJob::STATUS_FAILED || $job->status === FireblocksSweepJob::STATUS_SKIPPED) {
            return $this->humanizeSkipReason($job->skip_reason);
        }

        if ($job->fireblocks_tx_id) {
            return 'Sweep tx: ' . $job->fireblocks_tx_id;
        }

        if ($depositTx = $this->formatDepositTxId($job)) {
            return 'Deposit tx: ' . $depositTx;
        }

        return '—';
    }

    /**
     * Preflight data for manual run confirmation modal.
     *
     * @return array{
     *     has_prior_run: bool,
     *     in_flight: bool,
     *     message: string,
     *     last_status: ?string,
     *     last_at: ?string,
     *     last_trigger: ?string,
     *     jobs_today: int
     * }
     */
    public function manualRunPreflight(FireblocksSweepRule $rule): array
    {
        $lastJob = FireblocksSweepJob::query()
            ->where('rule_id', $rule->id)
            ->orderByDesc('id')
            ->first();

        $inFlight = FireblocksSweepJob::query()
            ->where('rule_id', $rule->id)
            ->whereIn('status', FireblocksSweepJob::IN_FLIGHT_STATUSES)
            ->exists();

        $jobsToday = (int) FireblocksSweepJob::query()
            ->where('rule_id', $rule->id)
            ->whereDate('created_at', today())
            ->count();

        if ($lastJob === null) {
            return [
                'has_prior_run' => false,
                'in_flight' => $inFlight,
                'message' => '',
                'last_status' => null,
                'last_at' => null,
                'last_trigger' => null,
                'jobs_today' => 0,
            ];
        }

        $statusLabel = $this->statusLabel((string) $lastJob->status);
        $detail = $this->humanizeSkipReason($lastJob->skip_reason);
        if ($lastJob->fireblocks_tx_id) {
            $detail = 'Fireblocks tx ' . $lastJob->fireblocks_tx_id;
        } elseif ($detail === '—') {
            $detail = 'no additional details';
        }

        $message = sprintf(
            "Last run: %s · %s (%s) · trigger: %s.%s",
            $lastJob->created_at?->format('Y-m-d H:i:s') ?? 'unknown',
            $statusLabel,
            $detail,
            $this->triggerLabel($lastJob->trigger_source),
            $jobsToday > 1 ? sprintf(' %d job(s) logged today.', $jobsToday) : ''
        );

        if ($inFlight) {
            $message .= ' A sweep is currently in flight.';
        }

        return [
            'has_prior_run' => true,
            'in_flight' => $inFlight,
            'message' => $message,
            'last_status' => $lastJob->status,
            'last_at' => $lastJob->created_at?->toDateTimeString(),
            'last_trigger' => $lastJob->trigger_source,
            'jobs_today' => $jobsToday,
        ];
    }
}
