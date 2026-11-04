<?php

namespace App\Modules\Fireblocks\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\Account;
use App\Models\Client;
use App\Modules\Fireblocks\Models\CryptoBeneficiary;
use App\Modules\Fireblocks\Models\CryptoExternalWallet;
use App\Modules\Fireblocks\Models\Network;
use App\Modules\Fireblocks\Models\Asset;
use App\Modules\Fireblocks\Services\BeneficiaryService;
use App\Modules\Chainalysis\Services\ChainalysisService;
use App\Modules\Chainalysis\Services\ChainAnalysisConfigService;
use App\Helpers\IDORHelper;
use Illuminate\Support\Facades\Validator;

class BeneficiaryController extends Controller
{
    protected BeneficiaryService $beneficiaryService;

    public function __construct(BeneficiaryService $beneficiaryService)
    {
        $this->beneficiaryService = $beneficiaryService;
    }

    /**
     * GET /api/crypto-management/wallets?client_id=X
     * List all external wallets for the authenticated user's client.
     */
    public function wallets($client_id)
    {
        

        if (empty($client_id)) {
            return response()->json(['success' => false, 'message' => 'Validation error.', 'errors' => 'No External wallets found.'], 422);
        }

        if (!IDORHelper::isClientBelongToUser($client_id, Auth::id())) {
            return response()->json(['success' => false, 'message' => 'Client not found.'], 403);
        }

        $wallets = CryptoExternalWallet::where('client_id', $client_id)->get();

        return response()->json([
            'success' => true,
            'message' => 'Wallets retrieved successfully.',
            'data'    => $wallets,
        ], 200);
    }

    /**
     * GET /api/crypto-management/wallets/{id}
     * Show a single external wallet with its beneficiaries.
     */
    public function showWallet(Request $request, int $id)
    {
        $wallet = CryptoExternalWallet::find($id);

        if (!$wallet) {
            return response()->json(['success' => false, 'message' => 'Wallet not found.'], 404);
        }

        if (!IDORHelper::isClientBelongToUser($wallet->client_id, Auth::id())) {
            return response()->json(['success' => false, 'message' => 'Wallet not found.'], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Wallet retrieved successfully.',
            'data'    => $wallet,
        ], 200);
    }

    /**
     * GET /api/crypto-management/beneficiaries/{account_id}
     */
    public function index($account_id)
    {
        if (empty($account_id)) {
            return response()->json(['success' => false, 'message' => 'Account ID must be provided.'], 422);
        }

        if (!IDORHelper::isAccountBelongToUser($account_id, Auth::id())) {
            return response()->json(['success' => false, 'message' => 'Account not found.'], 403);
        }

        $result = $this->beneficiaryService->listForAccount((int) $account_id);


        return response()->json([
            'success' => true,
            'message' => 'Beneficiaries retrieved successfully.',
            'data'    => $result['data'],
        ], 200);
    }

    /**
     * GET /api/crypto-management/beneficiaries/detail/{id}
     */
    public function show(Request $request, int $id)
    {
        
        $beneficiary = CryptoBeneficiary::with('compliance')->findOrFail($id);
        if (!$beneficiary) {
            return response()->json(['success' => false, 'message' => 'Beneficiary not found.'], 404);
        }
        
        if (ChainAnalysisConfigService::isSanctionsScreeningEnabled()) {
            app(ChainalysisService::class)->screenBeneficiary($beneficiary);
        }

        if (!IDORHelper::isAccountBelongToUser($beneficiary->account_id, $request->user()->id)) {
            return response()->json(['success' => false, 'message' => 'The account does not belong to you.'], 403);
        }

        $result = $this->beneficiaryService->fetchAndSync($beneficiary);

        return response()->json([
            'success' => true,
            'message' => 'Beneficiary retrieved successfully.',
            'data'    => $result['success'] ? $result['data'] : $beneficiary,
        ], 200);
    }

    /**
     * Step 1 — Find-or-create external wallet on Fireblocks for this client.
     *
     * POST /api/crypto-management/beneficiaries/wallet
     * Body: account_id
     *
     * Returns the CryptoExternalWallet record with fireblocks_wallet_id.
     * The client then calls Step 2 to add an address under this wallet.
     */
    public function storeWallet(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_id' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation error.', 'errors' => $validator->errors()], 422);
        }

        if (!IDORHelper::isClientBelongToUser($request->client_id, $request->user()->id)) {
            return response()->json(['success' => false, 'message' => 'Client not found.'], 403);
        }

        $client = Client::where('id', $request->client_id)
            ->where('status', 'Approved')
            ->first();

        if (!$client) {
            return response()->json(['success' => false, 'message' => 'Client not found or not approved yet.'], 404);
        }

       

        $result = $this->beneficiaryService->findOrCreateExternalWallet([
            'client_id'        => $client->id,
            'name'             => $client->name . ' - ' . $client->id,
            'customer_ref_id'  => (string) $client->id,
            'created_by'       => $request->user()->id,
        ]);

        if (!$result['success']) {
            if (($result['error'] ?? '') === 'operation_unavailable') {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'This operation is not available at the moment. Please contact support.',
                ], 503);
            }

            Log::error('BeneficiaryController@storeWallet: ' . ($result['error'] ?? 'Unknown error'));
            return response()->json(['success' => false, 'message' => 'Failed to create external wallet.', 'error' => $result['error'] ?? null], 500);
        }

        return response()->json([
            'success' => true,
            'message' => $result['created'] ? 'External wallet created on Fireblocks.' : 'Existing external wallet retrieved.',
            'data'    => $result['data'],
        ], $result['created'] ? 201 : 200);
    }

    /**
     * Step 2 — Add a whitelisted address/asset to the external wallet.
     *
     * POST /api/crypto-management/beneficiaries/store
     * Body:
     *   account_id, network_id, asset_id (local DB id),
     *   label, wallet_address, asset_type,
     *   destination_tag (optional — crypto only),
     *   additional_info (optional object — fiat rails only)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'wallet_id'      => 'required|numeric',//externalWallet table pk
            'account_id'      => 'required|numeric',
            'network_id'      => 'required|numeric',
            'asset_id'        => 'required|numeric',  
            'wallet_address'  => 'required|string|max:255',
            'asset_type'      => 'required|in:crypto,iban,aba,sepa,ach,us_wire,spei,pix,momo,lbt,interac,payid,chaps',
            'destination_tag' => 'nullable|string|max:100',
            'additional_info' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation error.', 'errors' => $validator->errors()], 422);
        }
        // Chainalysis compliance check — block risky addresses before creating the beneficiary
        if (ChainAnalysisConfigService::isSanctionsScreeningEnabled()) {
            try {
                $compliance = app(ChainalysisService::class)->screenAddress(
                    $request->wallet_address,
                    'withdraw_address',
                    null  // no beneficiary id yet
                );

                if (ChainAnalysisConfigService::isRisky($compliance->risk)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'The wallet address could not be added. It did not pass our compliance checks.',
                    ], 422);
                }
            } catch (\Throwable $e) {
                Log::error('BeneficiaryController@store: Chainalysis scan failed. ' . $e->getMessage());

                return response()->json([
                    'success' => false,
                    'message' => 'The wallet address could not be verified. Please try again later.',
                ], 422);
            }
        }

        if (!IDORHelper::isAccountBelongToUser($request->account_id, $request->user()->id)) {
            return response()->json(['success' => false, 'message' => 'Account not found.'], 403);
        }

        $account = Account::where('id', $request->account_id)
            ->where('is_active', true)
            ->where('is_crypto', true)
            ->first();

        if (!$account) {
            return response()->json(['success' => false, 'message' => 'Account not found or is not an active crypto account.'], 404);
        }

        $network = Network::where('id', $request->network_id)
            ->where('currency_id', $account->account_currency)
            ->enabled()
            ->first();

        if (!$network) {
            return response()->json(['success' => false, 'message' => 'Network not found for the given currency.'], 404);
        }

        $asset = Asset::where('id', $request->asset_id)
            ->where('network_id', $request->network_id)
            ->enabled()
            ->first();

        if (!$asset) {
            return response()->json(['success' => false, 'message' => 'Asset not found for the given network.'], 404);
        }
        $client_id = $account->client->id;
        $wallet_id = $request->wallet_id;
        // Ensure the external wallet exists for this client
        $externalWallet = CryptoExternalWallet::where('id', $wallet_id)->where('client_id',$client_id)->first();

        if (!$externalWallet) {
            return response()->json([
                'success' => false,
                'message' => 'External wallet not found. Please call POST /beneficiaries/wallet first.',
            ], 422);
        }

       

        // Prevent duplicate address on the same network for this account
        // $exists = CryptoBeneficiary::where('account_id', $request->account_id)
        //     ->where('wallet_address', $request->wallet_address)
        //     ->where('network_id', $request->network_id)
        //     ->exists();

        // if ($exists) {
        //     return response()->json(['success' => false, 'message' => 'A beneficiary with this wallet address and network already exists for this account.'], 409);
        // }

        $result = $this->beneficiaryService->addWalletAsset($externalWallet, [
            'account_id'      => $request->account_id,
            'label'           => $account->title,
            'wallet_address'  => $request->wallet_address,
            'fireblocks_wallet_id'  => $externalWallet->fireblocks_wallet_id,
            'destination_tag' => $request->destination_tag,
            'currency_id'     => $account->account_currency,
            'network_id'      => $network->id,
            'asset_id'        => $asset->asset_id,
            'asset_type'      => $request->asset_type,
            'additional_info' => $request->additional_info,
            'created_by'      => $request->user()->id,
        ]);

        if (!$result['success']) {
            if (($result['error'] ?? '') === 'operation_unavailable') {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'This operation is not available at the moment. Please contact support.',
                ], 503);
            }

            Log::error('BeneficiaryController@store: ' . ($result['error'] ?? 'Unknown error'));
            return response()->json(['success' => false, 'message' => 'Failed to add asset to wallet.', 'error' => $result['error'] ?? null], 500);
        }

        $beneficiary = $result['data'];
        ChainalysisService::attachComplianceToBeneficiary($request->wallet_address, (int) $beneficiary->id);

        return response()->json([
            'success' => true,
            'message' => 'Beneficiary whitelisted successfully.',
            'data'    => $beneficiary,
        ], 201);
    }

    /**
     * PUT /api/crypto-management/beneficiaries/{id}
     * Only label and destination_tag are mutable after creation.
     */
    // public function update(Request $request, int $id)
    // {
    //     $validator = \Validator::make($request->all(), [
    //         'label'           => 'required|string|max:100',
    //         'destination_tag' => 'nullable|string|max:100',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json(['success' => false, 'message' => 'Validation error.', 'errors' => $validator->errors()], 422);
    //     }

    //     $beneficiary = CryptoBeneficiary::find($id);

    //     if (!$beneficiary) {
    //         return response()->json(['success' => false, 'message' => 'Beneficiary not found.'], 404);
    //     }

    //     if (!IDORHelper::isAccountBelongsToUser($beneficiary->account_id, $request->user()->id)) {
    //         return response()->json(['success' => false, 'message' => 'The account does not belong to you.'], 403);
    //     }

    //     try {
    //         $beneficiary->update([
    //             'label'           => $request->label,
    //             'destination_tag' => $request->destination_tag,
    //         ]);

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Beneficiary updated successfully.',
    //             'data'    => $beneficiary->load(['currency:id,short,name', 'network:id,code,name']),
    //         ], 200);

    //     } catch (\Throwable $th) {
    //         Log::error('BeneficiaryController@update: ' . $th->getMessage());
    //         return response()->json(['success' => false, 'message' => 'An unexpected error occurred.'], 500);
    //     }
    // }

    // /**
    //  * DELETE /api/crypto-management/beneficiaries/{id}
    //  */
    // public function destroy(Request $request, int $id)
    // {
    //     $beneficiary = CryptoBeneficiary::find($id);

    //     if (!$beneficiary) {
    //         return response()->json(['success' => false, 'message' => 'Beneficiary not found.'], 404);
    //     }

    //     if (!IDORHelper::isAccountBelongsToUser($beneficiary->account_id, $request->user()->id)) {
    //         return response()->json(['success' => false, 'message' => 'The account does not belong to you.'], 403);
    //     }

    //     $result = $this->beneficiaryService->delete($beneficiary);

    //     if (!$result['success']) {
    //         return response()->json(['success' => false, 'message' => 'Failed to delete beneficiary.', 'error' => $result['error'] ?? null], 500);
    //     }

    //     return response()->json(['success' => true, 'message' => 'Beneficiary deleted successfully.'], 200);
    // }
}
