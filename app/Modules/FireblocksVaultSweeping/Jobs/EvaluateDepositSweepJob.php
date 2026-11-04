<?php

namespace App\Modules\FireblocksVaultSweeping\Jobs;

use App\Modules\FireblocksVaultSweeping\Jobs\Concerns\UsesApplicationQueue;
use App\Modules\FireblocksVaultSweeping\Services\SweepEvaluationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class EvaluateDepositSweepJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, UsesApplicationQueue;

    public function __construct(
        public string $fireblocksTxId,
        public array $webhookData,
    ) {
        $this->assignApplicationQueue();
    }

    public function handle(SweepEvaluationService $evaluation): void
    {
        Log::info('[Sweep deposit webhook] deposit evaluation started', [
            'fireblocks_tx_id' => $this->fireblocksTxId,
            'vault_id' => $this->webhookData['destination']['id'] ?? null,
            'asset_id' => $this->webhookData['assetId'] ?? null,
            'orphan_deposit' => $this->webhookData['_sweep_context']['orphan_deposit'] ?? false,
        ]);

        try {
            $result = $evaluation->evaluateDeposit($this->fireblocksTxId, $this->webhookData);

            Log::info('[Sweep deposit webhook] deposit evaluation finished', [
                'fireblocks_tx_id' => $this->fireblocksTxId,
                'status' => $result['status'] ?? ($result['evaluated'] > 0 ? 'evaluated' : 'skipped'),
                'evaluated' => $result['evaluated'] ?? 0,
                'message' => $result['message'] ?? null,
            ]);
        } catch (\Throwable $e) {
            Log::error('[Sweep deposit webhook] deposit evaluation failed', [
                'fireblocks_tx_id' => $this->fireblocksTxId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
