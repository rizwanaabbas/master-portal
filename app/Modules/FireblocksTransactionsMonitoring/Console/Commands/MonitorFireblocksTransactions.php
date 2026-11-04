<?php

namespace App\Modules\FireblocksTransactionsMonitoring\Console\Commands;

use App\Modules\FireblocksTransactionsMonitoring\Jobs\PollStuckTransactionsJob;
use Illuminate\Console\Command;

class MonitorFireblocksTransactions extends Command
{
    protected $signature = 'fireblocks:monitor-transactions';

    protected $description = 'Poll and evaluate stuck Fireblocks transactions (fallback to webhooks)';

    public function handle(): int
    {
        PollStuckTransactionsJob::dispatchSync();
        $this->info('Fireblocks transaction monitoring poll completed.');

        return self::SUCCESS;
    }
}
