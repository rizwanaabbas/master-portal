<?php

namespace App\Modules\FireblocksVaultSweeping\Services;

use App\Modules\FireblocksVaultSweeping\Jobs\ExecuteSweepJob;
use App\Modules\FireblocksVaultSweeping\Models\FireblocksSweepJob;
use App\Modules\FireblocksVaultSweeping\Models\FireblocksSweepRule;
use Illuminate\Support\Facades\Log;

class SweepJobRecoveryService
{
    public function __construct(
        protected SweepJobReconciliationService $reconciliation,
        protected SweepExecutionService $execution,
        protected TronGasFundingService $tronGas,
        protected SweepLogService $logger,
    ) {
    }

    /**
     * When gas feed webhook tx id does not match a row, bind the newest open job on the destination vault.
     */
    public function findLatestOpenJobForVault(string $sourceVaultId, string $gasFeedFireblocksTxId): ?FireblocksSweepJob
    {
        $sourceVaultId = trim($sourceVaultId);
        if ($sourceVaultId === '') {
            return null;
        }

        $job = FireblocksSweepJob::query()
            ->where('source_vault_id', $sourceVaultId)
            ->where(function ($q) {
                $q->whereNull('fireblocks_tx_id')
                    ->orWhere('fireblocks_tx_id', '');
            })
            ->whereIn('status', [
                FireblocksSweepJob::STATUS_WAITING_FOR_GAS,
                FireblocksSweepJob::STATUS_GAS_FUNDED,
                FireblocksSweepJob::STATUS_PENDING,
                FireblocksSweepJob::STATUS_QUEUED,
                FireblocksSweepJob::STATUS_FAILED,
            ])
            ->orderByDesc('id')
            ->first();

        if ($job === null) {
            return null;
        }

        if ($job->status === FireblocksSweepJob::STATUS_FAILED
            && !in_array((string) $job->skip_reason, TronGasFundingService::recoverableGasWaitSkipReasons(), true)) {
            return null;
        }

        if (trim((string) ($job->gas_feed_fireblocks_tx_id ?? '')) === '') {
            $job->update(['gas_feed_fireblocks_tx_id' => $gasFeedFireblocksTxId]);
            $job->refresh();
            Log::info('[Sweep recovery] Linked gas feed tx to vault job', [
                'sweep_job_id' => $job->id,
                'source_vault_id' => $sourceVaultId,
                'gas_feed_fireblocks_tx_id' => $gasFeedFireblocksTxId,
                'deposit_fireblocks_tx_id' => $job->deposit_fireblocks_tx_id,
            ]);
        }

        return $job;
    }

    /**
     * @return list<string>
     */
    public function availableActions(FireblocksSweepJob $job): array
    {
        $actions = [];

        if ($this->canReconcile($job)) {
            $actions[] = 'reconcile';
        }
        if ($this->canRequeue($job)) {
            $actions[] = 'requeue';
        }
        if ($this->canExecute($job)) {
            $actions[] = 'execute';
        }

        return $actions;
    }

    public function canReconcile(FireblocksSweepJob $job): bool
    {
        return in_array($job->status, array_merge(
            FireblocksSweepJob::ACTIVE_FIREBLOCKS_STATUSES,
            [FireblocksSweepJob::STATUS_FAILED]
        ), true);
    }

    public function canRequeue(FireblocksSweepJob $job): bool
    {
        return in_array($job->status, [
            FireblocksSweepJob::STATUS_QUEUED,
            FireblocksSweepJob::STATUS_PENDING,
            FireblocksSweepJob::STATUS_FAILED,
            FireblocksSweepJob::STATUS_SKIPPED,
        ], true);
    }

    public function canExecute(FireblocksSweepJob $job): bool
    {
        if (!$job->rule_id) {
            return false;
        }

        return in_array($job->status, [
            FireblocksSweepJob::STATUS_PENDING,
            FireblocksSweepJob::STATUS_QUEUED,
            FireblocksSweepJob::STATUS_WAITING_FOR_GAS,
            FireblocksSweepJob::STATUS_GAS_FUNDED,
            FireblocksSweepJob::STATUS_FAILED,
            FireblocksSweepJob::STATUS_SKIPPED,
        ], true);
    }

    /**
     * @return array{success: bool, message: string, status?: string}
     */
    public function reconcile(FireblocksSweepJob $job): array
    {
        if (!$this->canReconcile($job)) {
            return ['success' => false, 'message' => 'Job is not eligible for reconcile.'];
        }

        $before = $job->status;
        $this->reconciliation->reconcileJob($job);
        $job->refresh();

        $this->logger->log('recovery_reconcile', 'Manual reconcile from admin UI', $job, context: [
            'status_before' => $before,
            'status_after' => $job->status,
        ]);

        return [
            'success' => true,
            'message' => $before !== $job->status
                ? 'Reconciled — status is now ' . $job->status . '.'
                : 'Reconcile finished (no status change). Check logs for Fireblocks poll results.',
            'status' => $job->status,
        ];
    }

    /**
     * @return array{success: bool, message: string, status?: string}
     */
    public function requeue(FireblocksSweepJob $job): array
    {
        if (!$this->canRequeue($job)) {
            return ['success' => false, 'message' => 'Job is not eligible for requeue.'];
        }

        $rule = $this->resolveRule($job);
        if (!$rule) {
            return ['success' => false, 'message' => 'Rule not found or disabled.'];
        }

        $job = $this->resetForRetry($job);

        ExecuteSweepJob::dispatch(
            (int) $rule->id,
            $job->source_vault_id,
            $job->asset_id,
            $job->trigger_source,
            $job->deposit_fireblocks_tx_id,
            $job->id
        );

        $this->logger->log('recovery_requeue', 'Queued ExecuteSweepJob from admin UI', $job);

        return [
            'success' => true,
            'message' => 'Sweep requeued — worker will run ExecuteSweepJob shortly.',
            'status' => $job->status,
        ];
    }

    /**
     * @return array{success: bool, message: string, status?: string}
     */
    public function execute(FireblocksSweepJob $job): array
    {
        if (!$this->canExecute($job)) {
            return ['success' => false, 'message' => 'Job is not eligible for execute.'];
        }

        $rule = $this->resolveRule($job);
        if (!$rule) {
            return ['success' => false, 'message' => 'Rule not found or disabled.'];
        }

        if (in_array($job->status, [
            FireblocksSweepJob::STATUS_WAITING_FOR_GAS,
            FireblocksSweepJob::STATUS_GAS_FUNDED,
        ], true)) {
            $result = $this->tronGas->recoverGasLegForJob($job);
            $job->refresh();

            if (!in_array($job->status, [
                FireblocksSweepJob::STATUS_WAITING_FOR_GAS,
                FireblocksSweepJob::STATUS_GAS_FUNDED,
            ], true) && empty($result['recovered'])) {
                $result = array_merge(['status' => $job->status, 'message' => 'Gas leg reconciled'], $result);
            }
        } else {
            $job = $this->resetForRetry($job);
            $result = $this->execution->execute(
                $rule,
                $job->source_vault_id,
                $job->asset_id,
                $job->trigger_source,
                $job->deposit_fireblocks_tx_id,
                null,
                $job
            );
        }

        $this->logger->log('recovery_execute', 'Manual execute from admin UI', $job->fresh(), context: $result);

        $status = (string) ($result['status'] ?? $job->status);
        $message = (string) ($result['message'] ?? $result['reason'] ?? 'Execute finished with status ' . $status);

        return [
            'success' => !in_array($status, [FireblocksSweepJob::STATUS_FAILED, FireblocksSweepJob::STATUS_SKIPPED], true),
            'message' => $message,
            'status' => $status,
        ];
    }

    public function renderActionButtons(FireblocksSweepJob $job, string $sizeClass = 'btn-xs'): string
    {
        $html = '<span class="sweep-job-recovery-actions">';

        foreach ($this->availableActions($job) as $action) {
            $route = route('fireblocks.vault-sweeping.jobs.recover.' . $action, $job);
            if ($action === 'reconcile') {
                $label = 'Reconcile';
                $class = 'btn-outline-info';
            } elseif ($action === 'requeue') {
                $label = 'Requeue';
                $class = 'btn-outline-warning';
            } elseif ($action === 'execute') {
                $label = 'Execute';
                $class = 'btn-outline-success';
            } else {
                $label = ucfirst($action);
                $class = 'btn-outline-secondary';
            }
            $html .= '<form method="POST" action="' . e($route) . '" class="d-inline-block mr-1 mb-1" '
                . 'onsubmit="return confirm(\'Run ' . e($label) . ' on job #' . (int) $job->id . '?\');">'
                . csrf_field()
                . '<button type="submit" class="btn ' . e($sizeClass) . ' ' . e($class) . '">' . e($label) . '</button>'
                . '</form>';
        }

        $html .= '</span>';

        return $html !== '<span class="sweep-job-recovery-actions d-inline-flex flex-wrap gap-1"></span>'
            ? $html
            : '';
    }

    protected function resolveRule(FireblocksSweepJob $job): ?FireblocksSweepRule
    {
        if (!$job->rule_id) {
            return null;
        }

        return FireblocksSweepRule::query()->active()->where('id', $job->rule_id)->first();
    }

    protected function resetForRetry(FireblocksSweepJob $job): FireblocksSweepJob
    {
        $updates = [
            'skip_reason' => null,
            'failed_at' => null,
        ];

        if (in_array($job->status, [
            FireblocksSweepJob::STATUS_FAILED,
            FireblocksSweepJob::STATUS_SKIPPED,
        ], true)) {
            $updates['status'] = FireblocksSweepJob::STATUS_PENDING;
        }

        if ($job->status === FireblocksSweepJob::STATUS_QUEUED) {
            $updates['status'] = FireblocksSweepJob::STATUS_QUEUED;
        }

        $job->update($updates);

        return $job->fresh();
    }
}
