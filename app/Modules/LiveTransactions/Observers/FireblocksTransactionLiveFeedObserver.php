<?php

namespace App\Modules\LiveTransactions\Observers;

use App\Models\FireblocksTransaction;
use App\Modules\LiveTransactions\Services\LiveTransactionFeedService;

class FireblocksTransactionLiveFeedObserver
{
    public function __construct(
        protected LiveTransactionFeedService $feedService
    ) {
    }

    public function created(FireblocksTransaction $transaction): void
    {
        if (FireblocksTransaction::isCustodyOnlyWithoutLedger($transaction)) {
            return;
        }

        $this->feedService->syncFromFireblocks($transaction);
    }

    public function updated(FireblocksTransaction $transaction): void
    {
        if (FireblocksTransaction::isCustodyOnlyWithoutLedger($transaction)) {
            return;
        }

        $this->feedService->syncFromFireblocks($transaction);
    }
}
