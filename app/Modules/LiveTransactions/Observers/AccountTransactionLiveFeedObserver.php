<?php

namespace App\Modules\LiveTransactions\Observers;

use App\Models\AccountTransaction;
use App\Modules\LiveTransactions\Services\LiveTransactionFeedService;

class AccountTransactionLiveFeedObserver
{
    public function __construct(
        protected LiveTransactionFeedService $feedService
    ) {
    }

    public function created(AccountTransaction $transaction): void
    {
        $this->feedService->syncFromAccountTransaction($transaction);
    }

    public function updated(AccountTransaction $transaction): void
    {
        $this->feedService->syncFromAccountTransaction($transaction);
    }
}
