<?php

use App\Modules\Aquanow\Controllers\BalanceController;
use App\Modules\Aquanow\Controllers\InstrumentController;
use App\Modules\Aquanow\Controllers\RFQController;
use App\Modules\Aquanow\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'aquanow', 'middleware' => ['auth', '2fa']], function () {

    // DB-only routes — always accessible regardless of module state
    Route::get('/balances',            [BalanceController::class, 'local']);
    Route::get('/instruments',         [InstrumentController::class, 'local']);
    Route::get('/rfqs',                [RFQController::class, 'index']);
    Route::get('/orders',              [OrderController::class, 'index']);

    // Aquanow API routes — blocked when module is disabled
    Route::middleware('aquanow.active')->group(function () {

        // Buy / Sell pages are rendered via /liquidity/trade/*, which delegates
        // back into Aquanow\Controllers\TradeController when Aquanow is active.
        Route::middleware('permission:view aquanow instruments')->group(function () {
            Route::get('/crypto-management/instruments/manage', [InstrumentController::class, 'page']);
            Route::get('/instruments/data', [InstrumentController::class, 'gridData']);
            Route::post('/instruments/{id}/toggle-status', [InstrumentController::class, 'toggleStatus']);
        });
        Route::middleware('permission:view aquanow balances')->group(function () {
            Route::get('/crypto-management/balances/manage', [BalanceController::class, 'page']);
            Route::get('/balances/data', [BalanceController::class, 'gridData']);
        });

        Route::get('/balances/live',        [BalanceController::class, 'index']);
        Route::get('/balances/breakdown',   [BalanceController::class, 'breakdown']);
        Route::get('/instruments/live',     [InstrumentController::class, 'index']);
        Route::post('/rfq',                 [RFQController::class, 'requestQuote']);
        Route::post('/orders',              [OrderController::class, 'create']);
        Route::get('/orders/{orderId}',     [OrderController::class, 'show']);
    });
});
