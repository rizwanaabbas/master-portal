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

class ResumeSweepAfterGasFeedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, UsesApplicationQueue;

    public function __construct(
        public int $sweepJobId,
    ) {
        $this->assignApplicationQueue();
    }

    public function handle(SweepExecutionService $execution): void
    {
        $job = FireblocksSweepJob::query()->find($this->sweepJobId);
        if (!$job || !in_array($job->status, [FireblocksSweepJob::STATUS_GAS_FUNDED, FireblocksSweepJob::STATUS_WAITING_FOR_GAS], true)) {
            return;
        }

        $rule = FireblocksSweepRule::query()->active()->where('id', $job->rule_id)->first();
        if (!$rule) {
            $job->update([
                'status' => FireblocksSweepJob::STATUS_SKIPPED,
                'skip_reason' => 'rule_disabled',
            ]);

            return;
        }

        $execution->resumeAfterGasFeed($job, $rule);
    }
}
