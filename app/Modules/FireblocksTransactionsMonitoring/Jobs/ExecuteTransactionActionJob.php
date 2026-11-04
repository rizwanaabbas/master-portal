<?php

namespace App\Modules\FireblocksTransactionsMonitoring\Jobs;

use App\Models\FireblocksTransaction;
use App\Modules\FireblocksTransactionsMonitoring\Services\MonitoringConfigService;
use App\Modules\FireblocksTransactionsMonitoring\Services\TransactionActionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExecuteTransactionActionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $action,
        public int $fireblocksTransactionId,
        public string $triggerSource = 'manual',
    ) {}

    public function handle(TransactionActionService $actions): void
    {
        $record = FireblocksTransaction::find($this->fireblocksTransactionId);
        if (!$record) {
            return;
        }

        $config = MonitoringConfigService::tryGet();
        if (!$config) {
            $this->clearPending($record);

            return;
        }

        try {
            match ($this->action) {
                'boost' => $actions->boost($record, $this->triggerSource, $config),
                'drop' => $actions->drop($record, $this->triggerSource, $config),
                'resubmit' => $actions->resubmit($record, $this->triggerSource, $config),
                'sync' => $actions->syncStatus($record, $this->triggerSource),
                default => null,
            };
        } finally {
            $record->refresh();
            if ($record->pending_action === $this->action) {
                $this->clearPending($record);
            }
        }
    }

    protected function clearPending(FireblocksTransaction $record): void
    {
        $record->pending_action = null;
        $record->save();
    }
}
