<?php

use App\Modules\FireblocksTransactionsMonitoring\Controllers\TransactionMonitoringController;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'fireblocks/transaction-monitoring',
    'middleware' => ['auth', '2fa', 'fireblocks.active', 'fireblocks.monitoring.active'],
], function () {
    Route::get('/', [TransactionMonitoringController::class, 'index'])->name('fireblocks.transaction-monitoring.index');
    Route::get('/manual', [TransactionMonitoringController::class, 'manual'])->name('fireblocks.transaction-monitoring.manual');
    Route::get('/data', [TransactionMonitoringController::class, 'data'])->name('fireblocks.transaction-monitoring.data');
    Route::get('/charts', [TransactionMonitoringController::class, 'charts'])->name('fireblocks.transaction-monitoring.charts');
    Route::get('/summary', [TransactionMonitoringController::class, 'summary'])->name('fireblocks.transaction-monitoring.summary');
    Route::get('/status-reference', [TransactionMonitoringController::class, 'statusReference'])->name('fireblocks.transaction-monitoring.status-reference');
    Route::get('/currency-rates', [TransactionMonitoringController::class, 'currencyRates'])->name('fireblocks.transaction-monitoring.currency-rates');
    Route::post('/currency-rates/sync', [TransactionMonitoringController::class, 'syncCurrencyRates'])->name('fireblocks.transaction-monitoring.currency-rates.sync');
    Route::get('/fireblocks-balances', [TransactionMonitoringController::class, 'fireblocksBalances'])->name('fireblocks.transaction-monitoring.fireblocks-balances');
    Route::post('/fireblocks-balances/sync', [TransactionMonitoringController::class, 'syncFireblocksBalances'])->name('fireblocks.transaction-monitoring.fireblocks-balances.sync');
    Route::get('/aquanow-balances', [TransactionMonitoringController::class, 'aquanowBalances'])->name('fireblocks.transaction-monitoring.aquanow-balances');
    Route::post('/aquanow-balances/sync', [TransactionMonitoringController::class, 'syncAquanowBalances'])->name('fireblocks.transaction-monitoring.aquanow-balances.sync');
    Route::get('/{txId}', [TransactionMonitoringController::class, 'show'])->name('fireblocks.transaction-monitoring.show');
    Route::post('/{txId}/sync', [TransactionMonitoringController::class, 'sync'])->name('fireblocks.transaction-monitoring.sync');
    Route::post('/{txId}/boost', [TransactionMonitoringController::class, 'boost'])->name('fireblocks.transaction-monitoring.boost');
    Route::post('/{txId}/drop', [TransactionMonitoringController::class, 'drop'])->name('fireblocks.transaction-monitoring.drop');
    Route::post('/{txId}/resubmit', [TransactionMonitoringController::class, 'resubmit'])->name('fireblocks.transaction-monitoring.resubmit');
    Route::post('/alerts/{id}/approve', [TransactionMonitoringController::class, 'approveAlert'])->name('fireblocks.transaction-monitoring.alerts.approve');
    Route::post('/alerts/{id}/dismiss', [TransactionMonitoringController::class, 'dismissAlert'])->name('fireblocks.transaction-monitoring.alerts.dismiss');
});
