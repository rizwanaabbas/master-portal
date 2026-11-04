<?php

namespace App\Http\Controllers;

use Fireblocks\Sdk\Facades\Fireblocks;
use Fireblocks\Sdk\Models\TransactionRequest;
use App\Models\Client;
use App\Modules\Fireblocks\Models\FireblocksVaultAccount;
use App\Modules\Fireblocks\Services\VaultGridService;
use App\Modules\Fireblocks\Services\WithdrawalService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class FireblocksController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', '2fa', 'permission:fireblocks custody']);
    }

    /**
     * List locally persisted vault accounts (fireblocks_vault_accounts table)
     * with the owning client and deposit-address count, rendered as an HTML page.
     */
    public function listVaults()
    {
        return view('fireblocks::vaults');
    }

    public function vaultClientsSuggest(Request $request)
    {
        $term = trim((string) $request->input('q', ''));

        $clients = Client::query()
            ->when($term !== '', function ($query) use ($term) {
                $query->where('name', 'like', '%' . $term . '%');
            })
            ->orderBy('name')
            ->limit(40)
            ->get(['id', 'name']);

        return response()->json([
            'results' => $clients->map(fn (Client $client) => [
                'id' => $client->id,
                'text' => $client->name,
            ])->values(),
        ]);
    }

    public function vaultsData(Request $request, VaultGridService $vaultGrid)
    {
        $query = $vaultGrid->baseQuery();
        $vaultGrid->applyFilters($query, $request);

        return DataTables::of($query)
            ->addIndexColumn()
            ->filter(function (Builder $q) use ($request, $vaultGrid) {
                $vaultGrid->applySearch($q, $request->input('search.value'));
            }, true)
            ->orderColumn('vault_id', 'fireblocks_vault_accounts.vault_id $1')
            ->orderColumn('vault_title', 'fireblocks_vault_accounts.vault_title $1')
            ->orderColumn('client_name', 'clients.name $1')
            ->orderColumn('address_count', 'address_count $1')
            ->orderColumn('created_at', 'fireblocks_vault_accounts.created_at $1')
            ->addColumn('client_name', fn ($row) => e($row->client_name ?? '—'))
            ->addColumn('address_count', fn ($row) => (int) ($row->address_count ?? 0))
            ->addColumn('created_fmt', function ($row) {
                return $row->created_at ? e($row->created_at->format('d/m/Y H:i')) : '—';
            })
            ->make(true);
    }

    /**
     * JSON — live vault accounts direct from the Fireblocks API.
     */
    public function listVaultsFromSdk()
    {
        try {
            $accounts = Fireblocks::vaults()->listAccounts();
            return response()->json([
                'success' => true,
                'data' => $accounts
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new transaction
     */
    public function sendTransaction(Request $request)
    {
        try {
            $assetId = $request->input('asset', 'BTC');
            $address = $request->input('address');
            $walletId = $request->input('fireblocks_wallet_id')
                ?: app(WithdrawalService::class)->resolveExternalWalletId($address, $assetId);

            if (empty($walletId)) {
                return response()->json([
                    'success' => false,
                    'error' => 'No Fireblocks EXTERNAL_WALLET found for destination. Address must be whitelisted before withdrawing.',
                ], 422);
            }

            $txRequest = new TransactionRequest();
            $txRequest
                ->withAsset($assetId)
                ->withAmount($request->input('amount', '0.1'))
                ->fromVaultAccount($request->input('source_vault', '0'))
                ->toExternalWallet($walletId)
                ->withFeeLevel($request->input('fee_level', 'MEDIUM'))
                ->withNote($request->input('note', ''));

            $transaction = Fireblocks::transactions()->create($txRequest);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $transaction->id,
                    'status' => $transaction->status,
                    'txHash' => $transaction->txHash,
                    'destination_type' => 'EXTERNAL_WALLET',
                    'fireblocks_wallet_id' => $walletId,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get transaction details
     */
    public function getTransaction($txId)
    {
        try {
            $transaction = Fireblocks::transactions()->get($txId);
            return response()->json([
                'success' => true,
                'data' => $transaction
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * List internal wallets
     */
    public function listInternalWallets()
    {
        try {
            $wallets = Fireblocks::wallets()->listInternalWallets();
            return response()->json([
                'success' => true,
                'data' => $wallets
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
