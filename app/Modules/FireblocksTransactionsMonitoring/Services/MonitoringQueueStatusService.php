<?php

namespace App\Modules\FireblocksTransactionsMonitoring\Services;

use App\Models\FireblocksTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MonitoringQueueStatusService
{
    public function resolve(FireblocksTransaction $record): array
    {
        if ($record->monitoring_state === 'action_pending') {
            $action = $record->pending_action ?: 'action';

            return [
                'label' => 'Awaiting approval',
                'detail' => ucfirst($action),
                'class' => 'warning',
            ];
        }

        if (!empty($record->pending_action)) {
            $jobStatus = $this->findQueuedJobStatus((int) $record->id);

            if ($jobStatus) {
                return $jobStatus;
            }

            return [
                'label' => 'Queued',
                'detail' => ucfirst($record->pending_action),
                'class' => 'info',
            ];
        }

        $failed = $this->findFailedJobStatus((int) $record->id);
        if ($failed) {
            return $failed;
        }

        return [
            'label' => '—',
            'detail' => '',
            'class' => 'secondary',
        ];
    }

    public function badgeHtml(FireblocksTransaction $record): string
    {
        $status = $this->resolve($record);

        if ($status['label'] === '—') {
            return '<span class="text-muted">—</span>';
        }

        $detail = !empty($status['detail'])
            ? '<br><small>' . e($status['detail']) . '</small>'
            : '';

        return '<span class="badge badge-' . e($status['class']) . '">' . e($status['label']) . '</span>' . $detail;
    }

    protected function findQueuedJobStatus(int $fireblocksTransactionId): ?array
    {
        if (!Schema::hasTable('jobs')) {
            return null;
        }

        $jobs = DB::table('jobs')->orderByDesc('id')->limit(200)->get(['id', 'payload', 'reserved_at', 'attempts']);

        foreach ($jobs as $job) {
            if (!$this->payloadMatchesTransaction($job->payload, $fireblocksTransactionId)) {
                continue;
            }

            if (!empty($job->reserved_at)) {
                return [
                    'label' => 'Processing',
                    'detail' => 'Attempt ' . ((int) $job->attempts + 1),
                    'class' => 'primary',
                    'job_id' => $job->id,
                ];
            }

            return [
                'label' => 'Queued',
                'detail' => 'Waiting in queue',
                'class' => 'info',
                'job_id' => $job->id,
            ];
        }

        return null;
    }

    protected function findFailedJobStatus(int $fireblocksTransactionId): ?array
    {
        if (!Schema::hasTable('failed_jobs')) {
            return null;
        }

        $failed = DB::table('failed_jobs')->orderByDesc('id')->limit(200)->get(['id', 'payload', 'failed_at']);

        foreach ($failed as $job) {
            if ($this->payloadMatchesTransaction($job->payload, $fireblocksTransactionId)) {
                return [
                    'label' => 'Queue failed',
                    'detail' => 'See failed jobs',
                    'class' => 'danger',
                    'failed_job_id' => $job->id,
                ];
            }
        }

        return null;
    }

    protected function payloadMatchesTransaction(string $payload, int $fireblocksTransactionId): bool
    {
        return str_contains($payload, '"fireblocksTransactionId":' . $fireblocksTransactionId)
            || str_contains($payload, 's:25:\"fireblocksTransactionId\";i:' . $fireblocksTransactionId);
    }
}
