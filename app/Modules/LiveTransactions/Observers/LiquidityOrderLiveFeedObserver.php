<?php

namespace App\Modules\LiveTransactions\Observers;

use App\Modules\Liquidity\Models\Order as LiquidityOrder;
use App\Modules\LiveTransactions\Services\LiveTransactionFeedService;

class LiquidityOrderLiveFeedObserver
{
    public function __construct(
        protected LiveTransactionFeedService $feedService
    ) {
    }

    public function created(LiquidityOrder $order): void
    {
        $this->feedService->syncFromLiquidityOrder($order);
    }

    public function updated(LiquidityOrder $order): void
    {
        $this->feedService->syncFromLiquidityOrder($order);
    }
}
