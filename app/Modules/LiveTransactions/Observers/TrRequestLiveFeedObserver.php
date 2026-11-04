<?php

namespace App\Modules\LiveTransactions\Observers;

use App\Models\TRRequest;
use App\Modules\LiveTransactions\Services\LiveTransactionFeedService;

class TrRequestLiveFeedObserver
{
    public function __construct(
        protected LiveTransactionFeedService $feedService
    ) {
    }

    public function created(TRRequest $trRequest): void
    {
        $this->feedService->syncFromTrRequest($trRequest);
    }

    public function updated(TRRequest $trRequest): void
    {
        $this->feedService->syncFromTrRequest($trRequest);
    }
}
