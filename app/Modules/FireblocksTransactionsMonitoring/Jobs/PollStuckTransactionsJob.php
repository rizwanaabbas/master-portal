<?php

namespace App\Modules\FireblocksTransactionsMonitoring\Jobs;

use App\Models\FireblocksTransaction;
use App\Modules\FireblocksTransactionsMonitoring\Services\MonitoringConfigService;
use App\Modules\FireblocksTransactionsMonitoring\Services\StuckTransactionDetector;
use App\Modules\FireblocksTransactionsMonitoring\Services\TransactionActionService;
use App\Modules\FireblocksTransactionsMonitoring\Services\TransactionMonitoringSync;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PollStuckTransactionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(
        TransactionMonitoringSync $sync,
        StuckTransactionDetector $detector,
        TransactionActionService $actions,
    ): void {
        $config = MonitoringConfigService::tryGet();
        if (!$config || empty($config['monitoring_enabled']) || empty($config['polling_fallback_enabled'])) {
            return;
        }

        $interval = max(3, (int) $config['polling_interval_minutes']);
        $candidates = FireblocksTransaction::query()
            ->whereNotNull('fireblocks_tx_id')
            ->where(function ($q) use ($config) {
                $q->whereIn('status', $config['monitored_fireblocks_statuses'])
                    ->orWhere('monitoring_state', 'stuck')
                    ->orWhere('monitoring_state', 'action_pending');
            })
            ->where(function ($q) use ($interval) {
                $q->whereNull('last_polled_at')
                    ->orWhere('last_polled_at', '<=', now()->subMinutes($interval));
            })
            ->orderBy('stuck_detected_at')
            ->limit(50)
            ->get();

        foreach ($candidates as $record) {
            try {
                if (!empty($config['resend_webhook_on_stale'])
                    && $record->last_webhook_at
                    && $record->last_webhook_at->lt(now()->subMinutes($config['stale_webhook_threshold_minutes']))) {
                    $actions->resendWebhooks($record);
                }

                $actions->syncStatus($record, 'scheduler');

                $record->refresh();
                if ($detector->evaluate($record, 'scheduler')) {
                    if (!empty($config['auto_boost_enabled']) && empty($config['auto_boost_requires_atc_webhook'])) {
                        $actions->queueOrExecute('boost', $record, 'scheduler', $config);
                    }
                }

                if ($config['auto_resubmit_mode'] === 'auto' && $detector->isRetriableFailure($record->fresh(), $config)) {
                    $actions->queueOrExecute('resubmit', $record->fresh(), 'scheduler', $config);
                }
            } catch (\Throwable $e) {
                Log::error('Fireblocks poll job failed for tx', [
                    'fireblocks_tx_id' => $record->fireblocks_tx_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
