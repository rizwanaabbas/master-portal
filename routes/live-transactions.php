<?php

use App\Modules\LiveTransactions\Controllers\LiveTransactionFeedController;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => ['auth', '2fa']], function () {
    Route::get('/live-transactions/feed', [LiveTransactionFeedController::class, 'feed']);
});
