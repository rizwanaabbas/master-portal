<?php

namespace App\Modules\FireblocksVaultSweeping\Services;

use App\Modules\FireblocksVaultSweeping\Models\FireblocksSweepJob;
use Fireblocks\Sdk\Facades\Fireblocks;
use Illuminate\Support\Facades\Log;

class SweepJobReconciliationService
{
    public function __construct(
        protected SweepTransactionWebhookService $webhookHandler,
    ) {
    }

    /**
     * Reconcile in-flight sweep jobs for a vault/asset before evaluating new sweeps.
     */
    /**
     * @return array{changed: bool, status: string}
     */
    public function reconcileJob(FireblocksSweepJob $job): array
    {
        $before = $job->status;
        if ($this->isTerminal($job)) {
            if ($job->status === FireblocksSweepJob::STATUS_FAILED && $this->hasOpenTokenLeg($job)) {
                $resumeStatus = trim((string) ($job->gas_feed_fireblocks_tx_id ?? '')) !== ''
                    ? FireblocksSweepJob::STATUS_WAITING_FOR_GAS
                    : FireblocksSweepJob::STATUS_PENDING;

                $job->update([
                    'status' => $resumeStatus,
                    'skip_reason' => null,
                    'failed_at' => null,
                ]);
                $job->refresh();
                $before = $job->status;
            } else {
                return ['changed' => false, 'status' => $before];
            }
        }

        $this->pollFireblocksStatus($job);
        $job->refresh();

        return [
            'changed' => $job->status !== $before,
            'status' => $job->status,
        ];
    }

    protected function hasOpenTokenLeg(FireblocksSweepJob $job): bool
    {
        return trim((string) ($job->fireblocks_tx_id ?? '')) === '';
    }

    public function reconcileVaultAsset(string $sourceVaultId, string $assetId): int
    {
        $reconciled = 0;

        $activeJobs = FireblocksSweepJob::query()
            ->where('source_vault_id', $sourceVaultId)
            ->where('asset_id', $assetId)
            ->whereIn('status', FireblocksSweepJob::ACTIVE_FIREBLOCKS_STATUSES)
            ->orderByDesc('id')
            ->get();

        if ($activeJobs->isNotEmpty()) {
            $newest = $activeJobs->first();

            foreach ($activeJobs->skip(1) as $older) {
                if ($this->supersedeJob($older, $newest)) {
                    $reconciled++;
                }
            }

            if ($newest !== null && $this->pollFireblocksStatus($newest)) {
                $reconciled++;
            }
        }

        $failedOpenLeg = FireblocksSweepJob::query()
            ->where('source_vault_id', $sourceVaultId)
            ->where('asset_id', $assetId)
            ->where('status', FireblocksSweepJob::STATUS_FAILED)
            ->where(function ($q) {
                $q->whereNull('fireblocks_tx_id')
                    ->orWhere('fireblocks_tx_id', '');
            })
            ->orderByDesc('id')
            ->get();

        foreach ($failedOpenLeg as $failedJob) {
            $before = $failedJob->status;
            $this->reconcileJob($failedJob);
            $failedJob->refresh();
            if ($failedJob->status !== $before) {
                $reconciled++;
            }
        }

        return $reconciled;
    }

    public function pollFireblocksStatus(FireblocksSweepJob $job): bool
    {
        if ($this->isTerminal($job)) {
            return false;
        }

        $fireblocksTxId = trim((string) ($job->fireblocks_tx_id ?? ''));
        if ($fireblocksTxId === '') {
            return $this->pollGasFeedLeg($job);
        }

        try {
            $apiTx = Fireblocks::transactions()->get($fireblocksTxId);
            $status = trim((string) ($apiTx->status ?? ''));
            $subStatus = isset($apiTx->subStatus) ? trim((string) $apiTx->subStatus) : null;

            if ($status === '') {
                return false;
            }

            $before = $job->status;
            $this->webhookHandler->handleTransactionWebhook(
                $fireblocksTxId,
                $status,
                $subStatus !== '' ? $subStatus : null,
                is_array($apiTx) ? $apiTx : (array) json_decode(json_encode($apiTx), true)
            );

            $job->refresh();

            if ($job->status !== $before) {
                Log::info('[Sweep] Reconciled job from Fireblocks API', [
                    'job_id' => $job->id,
                    'fireblocks_tx_id' => $fireblocksTxId,
                    'from_status' => $before,
                    'to_status' => $job->status,
                    'fireblocks_status' => $status,
                ]);

                return true;
            }

            return $this->failIfStaleActiveJob($job);
        } catch (\Throwable $e) {
            Log::warning('[Sweep] Fireblocks poll failed during reconciliation', [
                'job_id' => $job->id,
                'fireblocks_tx_id' => $fireblocksTxId,
                'error' => $e->getMessage(),
            ]);

            return $this->failIfStaleActiveJob($job);
        }
    }

    protected function supersedeJob(FireblocksSweepJob $older, ?FireblocksSweepJob $newest): bool
    {
        if ($this->isTerminal($older)) {
            return false;
        }

        $previousStatus = $older->status;

        $older->update([
            'status' => FireblocksSweepJob::STATUS_FAILED,
            'skip_reason' => 'superseded_by_job_' . ($newest?->id ?? 'newer'),
            'failed_at' => now(),
        ]);

        Log::info('[Sweep] Superseded duplicate in-flight sweep job', [
            'job_id' => $older->id,
            'superseded_by_job_id' => $newest?->id,
            'previous_status' => $previousStatus,
        ]);

        return true;
    }

    protected function pollGasFeedLeg(FireblocksSweepJob $job): bool
    {
        if (!in_array($job->status, [
            FireblocksSweepJob::STATUS_WAITING_FOR_GAS,
            FireblocksSweepJob::STATUS_GAS_FUNDED,
        ], true)) {
            return false;
        }

        $gasTxId = trim((string) ($job->gas_feed_fireblocks_tx_id ?? ''));
        if ($gasTxId === '') {
            try {
                app(TronGasFundingService::class)->recoverGasLegForJob($job);
                $job->refresh();
                $gasTxId = trim((string) ($job->gas_feed_fireblocks_tx_id ?? ''));
                if ($gasTxId !== '') {
                    return true;
                }
            } catch (\Throwable $e) {
                Log::warning('[Sweep] Gas resubmit during reconcile failed', [
                    'job_id' => $job->id,
                    'error' => $e->getMessage(),
                ]);
            }

            return $this->failIfStaleGasWait($job);
        }

        try {
            $apiTx = Fireblocks::transactions()->get($gasTxId);
            $status = trim((string) ($apiTx->status ?? ''));
            if ($status === '') {
                return $this->failIfStaleGasWait($job);
            }

            $subStatus = isset($apiTx->subStatus) ? trim((string) $apiTx->subStatus) : null;
            $payload = is_array($apiTx) ? $apiTx : (array) json_decode(json_encode($apiTx), true);
            $before = $job->status;

            app(TronGasFundingService::class)->handleFeedWebhook($gasTxId, $status, $payload);
            $job->refresh();

            if ($job->status !== $before) {
                Log::info('[Sweep] Reconciled gas feed from Fireblocks API', [
                    'job_id' => $job->id,
                    'gas_feed_fireblocks_tx_id' => $gasTxId,
                    'from_status' => $before,
                    'to_status' => $job->status,
                    'fireblocks_status' => $status,
                ]);

                return true;
            }

            return $this->failIfStaleGasWait($job);
        } catch (\Throwable $e) {
            Log::warning('[Sweep] Gas feed poll failed during reconciliation', [
                'job_id' => $job->id,
                'gas_feed_fireblocks_tx_id' => $gasTxId,
                'error' => $e->getMessage(),
            ]);

            return $this->failIfStaleGasWait($job);
        }
    }

    protected function failIfStaleGasWait(FireblocksSweepJob $job): bool
    {
        $config = SweepConfigService::tryGet() ?? [];
        $staleMinutes = max(60, (int) ($config['gas_wait_stale_minutes'] ?? 480));
        $lastTouch = $job->updated_at;
        if ($lastTouch !== null && $lastTouch->gt(now()->subMinutes($staleMinutes))) {
            return false;
        }

        return $this->failJob($job, 'stale_waiting_for_gas');
    }

    protected function failIfStaleActiveJob(FireblocksSweepJob $job): bool
    {
        if (in_array($job->status, [
            FireblocksSweepJob::STATUS_WAITING_FOR_GAS,
            FireblocksSweepJob::STATUS_GAS_FUNDED,
        ], true)) {
            return $this->failIfStaleGasWait($job);
        }

        $staleMinutes = 120;
        $lastTouch = $job->fireblocks_status_updated_at ?? $job->updated_at;
        if ($lastTouch !== null && $lastTouch->gt(now()->subMinutes($staleMinutes))) {
            return false;
        }

        return $this->failJob($job, 'stale_submitted');
    }

    protected function failJob(FireblocksSweepJob $job, string $reason): bool
    {
        if ($this->isTerminal($job)) {
            return false;
        }

        $job->update([
            'status' => FireblocksSweepJob::STATUS_FAILED,
            'skip_reason' => $reason,
            'failed_at' => now(),
        ]);

        Log::warning('[Sweep] Released stale in-flight sweep job', [
            'job_id' => $job->id,
            'reason' => $reason,
        ]);

        return true;
    }

    protected function isTerminal(FireblocksSweepJob $job): bool
    {
        return in_array($job->status, [
            FireblocksSweepJob::STATUS_COMPLETED,
            FireblocksSweepJob::STATUS_FAILED,
            FireblocksSweepJob::STATUS_SKIPPED,
        ], true);
    }
}
