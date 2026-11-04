<?php

use App\Modules\Liquidity\Controllers\BalanceController;
use App\Modules\Liquidity\Controllers\InstrumentController;
use App\Modules\Liquidity\Controllers\TradeController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'liquidity', 'middleware' => ['auth', '2fa', 'liquidity.active']], function () {
    // Buy / Sell pages, and the Instruments/Balances management pages —
    // Crypto Management delegates here; the gateway decides whether B2c2 or
    // Aquanow serves the request based on which is enabled.
    // Gated by the same permission as the Crypto Transactions page.
    Route::middleware('can:crypto management')->group(function () {
        Route::get('/trade/buy',  [TradeController::class, 'buy']);
        Route::get('/trade/sell', [TradeController::class, 'sell']);

        Route::get('/crypto-management/instruments/manage', [InstrumentController::class, 'page']);
        Route::get('/instruments/data', [InstrumentController::class, 'data']);
        Route::get('/instruments/live', [InstrumentController::class, 'sync']);
        Route::post('/instruments/{id}/toggle-status', [InstrumentController::class, 'toggleStatus']);

        Route::get('/crypto-management/balances/manage', [BalanceController::class, 'page']);
        Route::get('/balances/data', [BalanceController::class, 'data']);
        Route::get('/balances/live', [BalanceController::class, 'sync']);
    });
});
