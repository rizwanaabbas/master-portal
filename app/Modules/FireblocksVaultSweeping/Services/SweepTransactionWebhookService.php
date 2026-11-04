<?php

namespace App\Modules\FireblocksVaultSweeping\Services;

use App\Modules\Fireblocks\Helpers\FireblocksStatusHelper;
use App\Modules\FireblocksVaultSweeping\Models\FireblocksSweepJob;
use App\Modules\FireblocksVaultSweeping\Support\SweepJobReason;
use Carbon\CarbonInterface;

class SweepTransactionWebhookService
{
    public function __construct(
        protected SweepLogService $logger,
    ) {
    }

    public function handleTransactionWebhook(
        string $fireblocksTxId,
        string $status,
        ?string $subStatus = null,
        array $webhookData = []
    ): bool {
        $fireblocksTxId = trim($fireblocksTxId);
        if ($fireblocksTxId === '') {
            return false;
        }

        $job = $this->findSweepJob($fireblocksTxId, $webhookData);
        if ($job === null) {
            return false;
        }

        if ($job->gas_feed_fireblocks_tx_id === $fireblocksTxId) {
            return false;
        }

        $normalizedStatus = strtoupper(trim($status));
        $normalizedSubStatus = $subStatus !== null && $subStatus !== ''
            ? strtoupper(trim($subStatus))
            : null;
        $now = now();

        $this->logWebhookStatusChange($job, $normalizedStatus, $normalizedSubStatus, $fireblocksTxId, $webhookData, $now);

        if ($this->isTerminalJobStatus($job->status)) {
            $job->update([
                'fireblocks_status' => $normalizedStatus,
                'fireblocks_sub_status' => $normalizedSubStatus,
                'fireblocks_status_updated_at' => $now,
            ]);

            return true;
        }

        $updates = [
            'fireblocks_status' => $normalizedStatus,
            'fireblocks_sub_status' => $normalizedSubStatus,
            'fireblocks_status_updated_at' => $now,
        ];

        if ($this->isCompletedStatus($normalizedStatus, $normalizedSubStatus)) {
            $updates['status'] = FireblocksSweepJob::STATUS_COMPLETED;
            $updates['completed_at'] = $now;
        } elseif ($this->isFailedStatus($normalizedStatus)) {
            $updates['status'] = FireblocksSweepJob::STATUS_FAILED;
            $updates['failed_at'] = $now;
            $updates['skip_reason'] = SweepJobReason::normalize(
                $this->buildFailureReason($normalizedStatus, $normalizedSubStatus)
            );
        } elseif ($job->status === FireblocksSweepJob::STATUS_PENDING) {
            $updates['status'] = FireblocksSweepJob::STATUS_SUBMITTED;
        }

        $job->update($updates);

        if (($updates['status'] ?? null) === FireblocksSweepJob::STATUS_COMPLETED) {
            $this->logger->log(
                'sweep_completed',
                'Sweep transaction completed via Fireblocks webhook',
                $job,
                level: 'info',
                context: [
                    'fireblocks_tx_id' => $fireblocksTxId,
                    'fireblocks_status' => $normalizedStatus,
                    'fireblocks_sub_status' => $normalizedSubStatus,
                    'completed_at' => $now->toDateTimeString(),
                ]
            );
        } elseif (($updates['status'] ?? null) === FireblocksSweepJob::STATUS_FAILED) {
            $failureDetail = $normalizedSubStatus
                ? FireblocksStatusHelper::getSubstatusMessage($normalizedSubStatus)
                : '';
            $skipReason = $updates['skip_reason'] ?? null;

            $this->logger->log(
                'sweep_job_error',
                'Sweep job failed via Fireblocks webhook: '
                    . $normalizedStatus
                    . ($normalizedSubStatus ? ' (' . $normalizedSubStatus . ')' : '')
                    . ($failureDetail !== '' ? ' — ' . $failureDetail : ''),
                $job,
                level: 'error',
                context: [
                    'fireblocks_tx_id' => $fireblocksTxId,
                    'fireblocks_status' => $normalizedStatus,
                    'fireblocks_sub_status' => $normalizedSubStatus,
                    'fireblocks_failure_detail' => $failureDetail !== '' ? $failureDetail : null,
                    'skip_reason' => $skipReason,
                    'job_status' => FireblocksSweepJob::STATUS_FAILED,
                    'job_amount' => $job->amount,
                    'planned_sweep_amount' => $job->planned_sweep_amount,
                    'source_vault_id' => $job->source_vault_id,
                    'asset_id' => $job->asset_id,
                    'rule_id' => $job->rule_id,
                    'deposit_fireblocks_tx_id' => $job->deposit_fireblocks_tx_id,
                    'failed_at' => $now->toDateTimeString(),
                    'webhook_amount' => $webhookData['amount'] ?? $webhookData['amountInfo']['amount'] ?? null,
                ]
            );

            $this->logger->log(
                'sweep_failed',
                'Sweep transaction failed via Fireblocks webhook: ' . $normalizedStatus,
                $job,
                level: 'error',
                context: [
                    'fireblocks_tx_id' => $fireblocksTxId,
                    'fireblocks_status' => $normalizedStatus,
                    'fireblocks_sub_status' => $normalizedSubStatus,
                    'skip_reason' => $skipReason,
                    'failed_at' => $now->toDateTimeString(),
                ]
            );
        }

        return true;
    }

    protected function findSweepJob(string $fireblocksTxId, array $webhookData): ?FireblocksSweepJob
    {
        $job = FireblocksSweepJob::query()
            ->where('fireblocks_tx_id', $fireblocksTxId)
            ->first();

        if ($job !== null) {
            return $job;
        }

        $externalTxId = trim((string) ($webhookData['externalTxId'] ?? ''));
        if ($externalTxId !== ''
            && \App\Modules\Fireblocks\Support\FireblocksTransactionReferences::isVaultSweepExternalTxId($externalTxId)) {
            return FireblocksSweepJob::query()
                ->where('external_tx_id', $externalTxId)
                ->first();
        }

        return null;
    }

    protected function isTerminalJobStatus(string $status): bool
    {
        return in_array($status, [
            FireblocksSweepJob::STATUS_COMPLETED,
            FireblocksSweepJob::STATUS_FAILED,
            FireblocksSweepJob::STATUS_SKIPPED,
        ], true);
    }

    protected function isCompletedStatus(string $status, ?string $subStatus): bool
    {
        if ($status !== 'COMPLETED') {
            return false;
        }

        if ($subStatus !== null
            && in_array($subStatus, FireblocksStatusHelper::NEEDS_REVIEW_SUBSTATUSES, true)) {
            return false;
        }

        return true;
    }

    protected function isFailedStatus(string $status): bool
    {
        return in_array($status, array_merge(
            FireblocksStatusHelper::REJECTED_STATUSES,
            FireblocksStatusHelper::CANCELLED_STATUSES
        ), true);
    }

    protected function buildFailureReason(string $status, ?string $subStatus): string
    {
        $message = 'Fireblocks status: ' . $status;
        if ($subStatus) {
            $detail = FireblocksStatusHelper::getSubstatusMessage($subStatus);
            $message .= ' (' . $subStatus . ($detail ? ': ' . $detail : '') . ')';
        }

        return $message;
    }

    protected function logWebhookStatusChange(
        FireblocksSweepJob $job,
        string $status,
        ?string $subStatus,
        string $fireblocksTxId,
        array $webhookData,
        CarbonInterface $at
    ): void {
        $subMessage = $subStatus ? FireblocksStatusHelper::getSubstatusMessage($subStatus) : '';
        $message = sprintf(
            'Fireblocks webhook — status %s%s at %s',
            $status,
            $subStatus ? ' / ' . $subStatus : '',
            $at->format('Y-m-d H:i:s')
        );
        if ($subMessage !== '') {
            $message .= ' — ' . $subMessage;
        }

        $this->logger->log(
            'webhook_status_update',
            $message,
            $job,
            level: $this->isFailedStatus($status) ? 'error' : 'info',
            context: [
                'fireblocks_tx_id' => $fireblocksTxId,
                'fireblocks_status' => $status,
                'fireblocks_sub_status' => $subStatus,
                'event_type' => $webhookData['type'] ?? $webhookData['eventType'] ?? null,
                'tx_hash' => $webhookData['txHash'] ?? null,
                'webhook_at' => $at->toDateTimeString(),
            ]
        );
    }
}
