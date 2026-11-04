<?php

use App\Http\Controllers\FireblocksController;
use App\Modules\Fireblocks\Controllers\BeneficiaryController;
use App\Modules\Fireblocks\Controllers\BeneficiaryCrudController;
use App\Modules\Fireblocks\Controllers\FireblocksWithdrawController;
use App\Modules\Fireblocks\Controllers\DepositAddressController;
use App\Modules\Fireblocks\Controllers\FireblocksUserController;
use App\Modules\Fireblocks\Controllers\WithdrawalPoolBalanceController;
use App\Modules\Fireblocks\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

// Webhook endpoint — public, no auth or module check
Route::post('/fireblocks/webhook', [WebhookController::class, 'handle']);

Route::group(['prefix' => 'fireblocks', 'middleware' => ['auth', '2fa']], function () {

    // ------------------------------------------------------------------
    // DB-only routes — always accessible regardless of module state
    // ------------------------------------------------------------------
    Route::get('/deposit-addresses',            [DepositAddressController::class, 'index']);
    Route::get('/deposit-addresses/{id}',       [DepositAddressController::class, 'show']);
    Route::get('/new-deposit-address',          [DepositAddressController::class, 'newDepositAddress']);
    Route::get('/vaults/{currency}/deposit-addresses', [DepositAddressController::class, 'depositAddresses']);
    Route::post('/get-address-data',            [DepositAddressController::class, 'getAddressData']);
    Route::post('/currency-networks',           [DepositAddressController::class, 'getCurrencyNetworks']);
    Route::middleware('permission:view fireblocks crypto networks')->group(function () {
        Route::get('/crypto-networks', [App\Http\Controllers\CryptoCurrencyController::class, 'cryptoNetworks']);
        Route::get('/crypto-networks/networks-data', [App\Http\Controllers\CryptoCurrencyController::class, 'getNetworksData']);
        Route::get('/crypto-networks/assets-data', [App\Http\Controllers\CryptoCurrencyController::class, 'getAssetsData']);
    });
        
    Route::get('/withdrawals',                  [DepositAddressController::class, 'listWithdrawals']);

    // Beneficiary routes — reads always accessible
    Route::get('/crypto-management/wallets/{client_id}',      [BeneficiaryController::class, 'wallets']);
    Route::get('/crypto-management/wallets/detail/{id}',      [BeneficiaryController::class, 'showWallet']);
    Route::get('/crypto-management/beneficiaries/{account_id}', [BeneficiaryController::class, 'index']);
    Route::get('/crypto-management/beneficiaries/detail/{id}', [BeneficiaryController::class, 'show']);

    // ------------------------------------------------------------------
    // Fireblocks Beneficiary CRUD (web) — reads always accessible
    // ------------------------------------------------------------------
    Route::middleware('permission:view fireblocks beneficiaries')->group(function () {
        Route::get('/beneficiaries',                 [BeneficiaryCrudController::class, 'index']);
        Route::get('/beneficiaries/data',            [BeneficiaryCrudController::class, 'data']);
        Route::get('/beneficiaries/assets-suggest',  [BeneficiaryCrudController::class, 'assetsSuggest']);
        Route::get('/beneficiaries/create',          [BeneficiaryCrudController::class, 'create']);
        Route::get('/beneficiaries/{id}/edit',       [BeneficiaryCrudController::class, 'edit']);
    });

    // ------------------------------------------------------------------
    // Fireblocks direct withdraw (web) — form + beneficiary lookup
    // ------------------------------------------------------------------
    // Route::get('/withdraw',                      [FireblocksWithdrawController::class, 'create']);
    // Route::get('/withdraw/beneficiaries/{account_id}', [FireblocksWithdrawController::class, 'beneficiaries']);

    // ------------------------------------------------------------------
    // Fireblocks SDK routes — blocked when module is disabled
    // ------------------------------------------------------------------
    Route::middleware('fireblocks.active')->group(function () {

        // Fireblocks beneficiary writes
        Route::middleware('permission:view fireblocks beneficiaries')->group(function () {
            Route::post('/beneficiaries',            [BeneficiaryCrudController::class, 'store']);
            Route::put('/beneficiaries/{id}',        [BeneficiaryCrudController::class, 'update']);
            Route::delete('/beneficiaries/{id}',     [BeneficiaryCrudController::class, 'destroy']);
        });

        // Fireblocks direct withdrawal
        Route::post('/withdraw',                 [FireblocksWithdrawController::class, 'store']);


        // Beneficiary write routes
        Route::post('/crypto-management/beneficiaries/wallet', [BeneficiaryController::class, 'storeWallet']); // Step 1: find-or-create wallet
        Route::post('/crypto-management/beneficiaries/store',  [BeneficiaryController::class, 'store']);        // Step 2: add address

        // Legacy FireblocksController routes
        Route::get('/vaults',                   [FireblocksController::class, 'listVaults']);
        Route::get('/vaults/data',              [FireblocksController::class, 'vaultsData']);
        Route::get('/vaults/clients-suggest',   [FireblocksController::class, 'vaultClientsSuggest']);
        Route::get('/vaults-live',               [FireblocksController::class, 'listVaultsFromSdk']);
        Route::post('/transactions',            [FireblocksController::class, 'sendTransaction']);
        Route::get('/transactions/{txId}',      [FireblocksController::class, 'getTransaction']);
        Route::get('/wallets/internal',         [FireblocksController::class, 'listInternalWallets']);

        // Deposit address SDK routes
        Route::get('/supported-assets',         [DepositAddressController::class, 'getSupportedAssets']);
        Route::post('/store-deposit-address',   [DepositAddressController::class, 'storeNewVaultAsset']);
        Route::get('/vault-asset/{vaultAccountId}/{assetId}', [DepositAddressController::class, 'getVaultAsset']);
        Route::get('/vaults/{vaultAccountId}/assets/{assetId}/deposit-addresses', [DepositAddressController::class, 'listFromSdk']);
        Route::post('/vaults/{vaultAccountId}/assets/{assetId}/deposit-addresses', [DepositAddressController::class, 'storeForVaultAsset']);

        // Withdrawal SDK routes
        Route::post('/withdrawals/validate-address', [DepositAddressController::class, 'validateWithdrawalAddress']);
        Route::post('/withdrawals',             [DepositAddressController::class, 'withdraw']);
        Route::get('/withdrawals/{txId}/sync', [DepositAddressController::class, 'syncWithdrawalStatus']);

        Route::get('/withdrawal-pool-balances', [WithdrawalPoolBalanceController::class, 'page']);
        Route::get('/omnibus-balances', [WithdrawalPoolBalanceController::class, 'omnibus']);
        Route::get('/withdrawal-pool-balances/data', [WithdrawalPoolBalanceController::class, 'data']);
        Route::get('/omnibus-balances/data', [WithdrawalPoolBalanceController::class, 'data']);
        Route::post('/withdrawal-pool-balances/sync', [WithdrawalPoolBalanceController::class, 'sync']);
        Route::post('/omnibus-balances/sync', [WithdrawalPoolBalanceController::class, 'sync']);

        Route::get('/users', [FireblocksUserController::class, 'page']);
        Route::get('/users/data', [FireblocksUserController::class, 'data']);
        Route::post('/users/sync', [FireblocksUserController::class, 'sync']);
        Route::post('/users', [FireblocksUserController::class, 'store']);
        Route::get('/users/{id}', [FireblocksUserController::class, 'show']);
        Route::put('/users/{id}', [FireblocksUserController::class, 'update']);
    });
});
