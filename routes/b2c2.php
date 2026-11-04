<?php

use App\Modules\B2c2\Controllers\BalanceController;
use App\Modules\B2c2\Controllers\InstrumentController;
use App\Modules\B2c2\Controllers\RFQController;
use App\Modules\B2c2\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'b2c2', 'middleware' => ['auth', '2fa']], function () {

    // DB-only routes — always accessible regardless of module state
    Route::get('/balances',            [BalanceController::class, 'local']);
    Route::get('/instruments',         [InstrumentController::class, 'local']);
    Route::get('/rfqs',                [RFQController::class, 'index']);
    Route::get('/orders',              [OrderController::class, 'index']);

    // B2c2 API routes — blocked when module is disabled
    Route::middleware('b2c2.active')->group(function () {

        // Buy / Sell pages now live under /liquidity/trade/* (App\Modules\Liquidity),
        // which delegates back into B2c2\Controllers\TradeController when B2c2 is
        // the active provider. Gated by the same permission as the Crypto
        // Transactions page.
        Route::middleware('can:crypto management')->group(function () {
            // B2c2 management pages under Crypto Management
            Route::get('/crypto-management/instruments/manage', [InstrumentController::class, 'page']);
            Route::get('/crypto-management/balances/manage',    [BalanceController::class, 'page']);
            Route::post('/instruments/{id}/toggle-status', [InstrumentController::class, 'toggleStatus']);
        });

        Route::get('/balances/live',        [BalanceController::class, 'index']);
        Route::get('/balances/breakdown',   [BalanceController::class, 'breakdown']);
        Route::get('/instruments/live',     [InstrumentController::class, 'index']);
        Route::post('/rfq',                 [RFQController::class, 'requestQuote']);
        Route::post('/orders',              [OrderController::class, 'create']);
        Route::get('/orders/{orderId}',     [OrderController::class, 'show']);
    });
});
