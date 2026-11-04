<?php

namespace App\Modules\FireblocksVaultSweeping\Jobs;

use App\Modules\FireblocksVaultSweeping\Jobs\Concerns\UsesApplicationQueue;
use App\Modules\FireblocksVaultSweeping\Models\FireblocksSweepJob;
use App\Modules\FireblocksVaultSweeping\Models\FireblocksSweepRule;
use App\Modules\FireblocksVaultSweeping\Services\SweepExecutionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExecuteSweepJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, UsesApplicationQueue;

    public function __construct(
        public int $ruleId,
        public string $sourceVaultId,
        public string $assetId,
        public string $triggerSource,
        public ?string $depositFireblocksTxId = null,
        public ?int $sweepJobId = null,
    ) {
        $this->assignApplicationQueue();
    }

    public function handle(SweepExecutionService $execution): void
    {
        $rule = FireblocksSweepRule::query()->active()->where('id', $this->ruleId)->first();
        if (!$rule) {
            return;
        }

        $existingJob = null;
        if ($this->sweepJobId !== null) {
            $existingJob = FireblocksSweepJob::query()->where('id', $this->sweepJobId)->first();
            if ($existingJob === null) {
                return;
            }
        }

        $execution->execute(
            $rule,
            $this->sourceVaultId,
            $this->assetId,
            $this->triggerSource,
            $this->depositFireblocksTxId,
            null,
            $existingJob
        );
    }
}
