<?php

namespace App\Modules\Fireblocks\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Fireblocks\Models\FireblocksWithdrawalPoolBalance;
use App\Modules\Fireblocks\Services\WithdrawalPoolBalanceGridService;
use App\Modules\Fireblocks\Services\WithdrawalPoolBalanceSyncService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class WithdrawalPoolBalanceController extends Controller
{
    public function __construct(
        protected WithdrawalPoolBalanceGridService $grid,
        protected WithdrawalPoolBalanceSyncService $syncService
    ) {
        $this->middleware(['auth', '2fa']);
        $this->middleware('permission:view fireblocks withdrawal pool balances');
        $this->middleware('permission:manage fireblocks withdrawal pool balances', [
            'only' => ['sync'],
        ]);
    }

    public function page(Request $request)
    {
        return $this->renderPage($request, 'wallet_pool');
    }

    public function omnibus(Request $request)
    {
        return $this->renderPage($request, 'omnibus');
    }

    protected function renderPage(Request $request, string $balanceSource)
    {
        $configuredOmnibusVaultId = \App\Modules\Fireblocks\Services\FireblocksConfigService::omnibusVaultId();
        $configuredWalletPoolId = \App\Modules\Fireblocks\Services\FireblocksConfigService::walletPoolId();
        $isOmnibus = $balanceSource === 'omnibus';

        $vaultQuery = FireblocksWithdrawalPoolBalance::query();
        if ($isOmnibus) {
            $vaultQuery->whereIn('balance_source', ['source_vault', 'utxo_vault']);
        } else {
            $vaultQuery->where('balance_source', 'wallet_pool_member');
        }

        $vaultIds = collect([$isOmnibus ? $configuredOmnibusVaultId : ''])
            ->merge($vaultQuery->distinct()->orderBy('vault_account_id')->pluck('vault_account_id'))
            ->map(fn ($id) => trim((string) $id))
            ->filter(fn ($id) => $id !== '')
            ->unique()
            ->values();

        return view('fireblocks::withdrawal-pool-balances', compact(
            'vaultIds',
            'configuredOmnibusVaultId',
            'configuredWalletPoolId',
            'balanceSource',
            'isOmnibus'
        ));
    }

    public function data(Request $request)
    {
        $query = $this->grid->baseQuery();
        $this->grid->applyFilters($query, $request);

        return DataTables::of($query)
            ->addIndexColumn()
            // false = only custom applySearch (asset_id / vault / catalog). true also runs
            // Yajra default search on grid columns (total, vault id, …) and ANDs them,
            // so asset/network text matches become recordsFiltered: 0.
            ->filter(function (Builder $q) use ($request) {
                $this->grid->applySearch($q, $request->input('search.value'));
            }, false)
            ->orderColumn('vault_account_id', 'fireblocks_withdrawal_pool_balances.vault_account_id $1')
            ->orderColumn('asset_id', 'fireblocks_withdrawal_pool_balances.asset_id $1')
            ->orderColumn('total_fmt', 'fireblocks_withdrawal_pool_balances.total $1')
            ->orderColumn('available_fmt', 'fireblocks_withdrawal_pool_balances.available $1')
            ->orderColumn('pending_fmt', 'fireblocks_withdrawal_pool_balances.pending $1')
            ->orderColumn('last_synced', 'fireblocks_withdrawal_pool_balances.updated_at $1')
            ->addColumn('network_asset', fn ($row) => e($this->grid->networkAssetLabel($row)))
            ->addColumn('total_fmt', fn ($row) => WithdrawalPoolBalanceGridService::formatDecimal($row->total))
            ->addColumn('available_fmt', fn ($row) => WithdrawalPoolBalanceGridService::formatDecimal($row->available))
            ->addColumn('pending_fmt', fn ($row) => WithdrawalPoolBalanceGridService::formatDecimal($row->pending))
            ->addColumn('last_synced', fn ($row) => $row->updated_at ? e($row->updated_at->format('Y-m-d H:i')) : '—')
            ->make(true);
    }

    public function sync(Request $request)
    {
        set_time_limit(300);

        try {
            $result = $this->syncService->sync();
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'synced' => 0,
                'message' => 'Withdrawal pool balance sync failed. Check application logs for details.',
            ], 500);
        }

        $status = !empty($result['success']) ? 200 : 422;

        return response()->json($result, $status);
    }
}
