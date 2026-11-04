<?php

namespace App\Modules\FireblocksVaultSweeping\Jobs\Concerns;

trait UsesApplicationQueue
{
    protected function assignApplicationQueue(): void
    {
        $queue = env('QUEUE_PREFIX');
        if (is_string($queue) && $queue !== '') {
            $this->onQueue($queue);
        }
    }
}
