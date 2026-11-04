<?php

use App\Modules\FireblocksVaultSweeping\Controllers\SweepDashboardController;
use App\Modules\FireblocksVaultSweeping\Controllers\SweepRuleController;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'fireblocks/vault-sweeping',
    'middleware' => ['auth', '2fa', 'fireblocks.active', 'fireblocks.sweeping.active'],
], function () {
    Route::get('/', [SweepDashboardController::class, 'index'])->name('fireblocks.vault-sweeping.index');
    Route::get('/data', [SweepDashboardController::class, 'data'])->name('fireblocks.vault-sweeping.data');
    Route::get('/jobs/{job}/logs/data', [SweepDashboardController::class, 'logsData'])->name('fireblocks.vault-sweeping.jobs.logs.data');
    Route::post('/jobs/{job}/recover/reconcile', [SweepDashboardController::class, 'recoverReconcile'])->name('fireblocks.vault-sweeping.jobs.recover.reconcile');
    Route::post('/jobs/{job}/recover/requeue', [SweepDashboardController::class, 'recoverRequeue'])->name('fireblocks.vault-sweeping.jobs.recover.requeue');
    Route::post('/jobs/{job}/recover/execute', [SweepDashboardController::class, 'recoverExecute'])->name('fireblocks.vault-sweeping.jobs.recover.execute');
    Route::get('/jobs/{job}', [SweepDashboardController::class, 'show'])->name('fireblocks.vault-sweeping.jobs.show');

    Route::get('/rules/gas-relay-status', [SweepRuleController::class, 'gasRelayStatus'])->name('fireblocks.vault-sweeping.rules.gas-relay-status');
    Route::get('/rules/data', [SweepRuleController::class, 'data'])->name('fireblocks.vault-sweeping.rules.data');
    Route::post('/rules/reorder-priorities', [SweepRuleController::class, 'reorderPriorities'])->name('fireblocks.vault-sweeping.rules.reorder-priorities');
    Route::get('/rules/create', [SweepRuleController::class, 'create'])->name('fireblocks.vault-sweeping.rules.create');
    Route::get('/rules', [SweepRuleController::class, 'index'])->name('fireblocks.vault-sweeping.rules.index');
    Route::post('/rules', [SweepRuleController::class, 'store'])->name('fireblocks.vault-sweeping.rules.store');
    Route::post('/rules/import', [SweepRuleController::class, 'import'])->name('fireblocks.vault-sweeping.rules.import');
    Route::get('/rules/{rule}/jobs/data', [SweepRuleController::class, 'jobsData'])->name('fireblocks.vault-sweeping.rules.jobs.data');
    Route::get('/rules/{rule}/dry-run', [SweepRuleController::class, 'dryRun'])->name('fireblocks.vault-sweeping.rules.dry-run');
    Route::post('/rules/{rule}/dry-run', [SweepRuleController::class, 'dryRun'])->name('fireblocks.vault-sweeping.rules.dry-run.execute');
    Route::get('/rules/{rule}/edit', [SweepRuleController::class, 'edit'])->name('fireblocks.vault-sweeping.rules.edit');
    Route::post('/rules/{rule}/run', [SweepRuleController::class, 'run'])->name('fireblocks.vault-sweeping.rules.run');
    Route::get('/rules/{rule}', [SweepRuleController::class, 'show'])->name('fireblocks.vault-sweeping.rules.show');
    Route::get('/rules/{rule}/export', [SweepRuleController::class, 'export'])->name('fireblocks.vault-sweeping.rules.export');
    Route::put('/rules/{rule}', [SweepRuleController::class, 'update'])->name('fireblocks.vault-sweeping.rules.update');
    Route::delete('/rules/{rule}', [SweepRuleController::class, 'destroy'])->name('fireblocks.vault-sweeping.rules.destroy');
});
