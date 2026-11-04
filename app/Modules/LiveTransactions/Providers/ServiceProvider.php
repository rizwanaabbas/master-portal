<?php

namespace App\Modules\LiveTransactions\Providers;

use App\Models\AccountTransaction;
use App\Models\FireblocksTransaction;
use App\Models\TRRequest;
use App\Helpers\Common;
use App\Modules\Liquidity\Models\Order as LiquidityOrder;
use App\Modules\LiveTransactions\Observers\AccountTransactionLiveFeedObserver;
use App\Modules\LiveTransactions\Observers\FireblocksTransactionLiveFeedObserver;
use App\Modules\LiveTransactions\Observers\LiquidityOrderLiveFeedObserver;
use App\Modules\LiveTransactions\Observers\TrRequestLiveFeedObserver;
use App\Modules\LiveTransactions\Services\LiveTransactionsConfigService;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;

class ServiceProvider extends LaravelServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'live-transactions');

        $this->app->booted(function () {
            if (!Common::isDatabaseAvailable() || !Common::isModuleEnabled('LiveTransactions')) {
                return;
            }

            TRRequest::observe(TrRequestLiveFeedObserver::class);
            AccountTransaction::observe(AccountTransactionLiveFeedObserver::class);
            FireblocksTransaction::observe(FireblocksTransactionLiveFeedObserver::class);
            LiquidityOrder::observe(LiquidityOrderLiveFeedObserver::class);

            view()->composer('live-transactions::partials.tray', function ($view) {
                $view->with('liveTransactionsConfig', LiveTransactionsConfigService::tryGet() ?? LiveTransactionsConfigService::defaults());
            });
        });
    }
}
