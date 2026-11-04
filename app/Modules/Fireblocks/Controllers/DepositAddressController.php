<?php

namespace App\Modules\Fireblocks\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Account;
use App\Models\CryptoAddress;
use App\Modules\Fireblocks\Services\DepositAddressService;
use App\Modules\Fireblocks\Services\FireblocksConfigService;
use App\Modules\Fireblocks\Models\FireblocksVaultAccount;
use App\Modules\Fireblocks\Models\Network;
use App\Modules\Fireblocks\Models\Asset;
use App\Helpers\IDORHelper;
use App\Models\Currency;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DepositAddressController extends Controller
{
    /**
     * @var DepositAddressService
     */
    protected $depositAddressService;

    /**
     * @var \App\Modules\Fireblocks\Services\WithdrawalService
     */
    protected $withdrawalService;

    public function __construct(DepositAddressService $depositAddressService, \App\Modules\Fireblocks\Services\WithdrawalService $withdrawalService)
    {
        $this->middleware(['auth', '2fa', 'permission:fireblocks custody']);
        $this->depositAddressService = $depositAddressService;
        $this->withdrawalService = $withdrawalService;
    }

    /**
     * List locally persisted deposit addresses from crypto_addresses table.
     */
    public function index(Request $request)
    {
        try {
            $filters = $request->only(['vault_account_id', 'asset_id', 'client_id', 'address']);
            $result = $this->depositAddressService->listLocalDepositAddresses($filters);

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show a locally persisted deposit address.
     */
    public function show($id)
    {
        try {
            $result = $this->depositAddressService->getLocalDepositAddress((int) $id);

            return response()->json($result);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Deposit address not found.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * List supported assets / currency networks from Fireblocks SDK.
     */
    public function getSupportedAssets(Request $request)
    {
        try {
            $result = $this->depositAddressService->listSupportedAssets();

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * List deposit addresses directly from Fireblocks SDK for a vault account asset.
     */
    // public function listFromSdk(Request $request, string $vaultAccountId, string $assetId)
    // {
    //     try {
    //         $result = $this->depositAddressService->listDepositAddressesFromSdk($vaultAccountId, $assetId);

    //         return response()->json($result);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    /**
     * Create a new deposit address via Fireblocks SDK.
     */
    // public function store(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'vault_account_id' => 'required|string',
    //         'asset_id' => 'required|string',
    //         'description' => 'nullable|string|max:255',
    //         'customer_ref_id' => 'nullable|string|max:255',
    //         'account_id' => 'nullable|integer',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json([
    //             'success' => false,
    //             'errors' => $validator->errors()
    //         ], 422);
    //     }

    //     try {
    //         $result = $this->depositAddressService->createDepositAddressViaSdk(
    //             $request->input('vault_account_id'),
    //             $request->input('asset_id'),
    //             $request->input('description'),
    //             $request->input('customer_ref_id'),
    //             $request->input('account_id') ? (int) $request->input('account_id') : null
    //         );

    //         return response()->json($result, 201);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    /**
     * Create a new deposit address via Fireblocks SDK for a specific vault and asset.
     */
    // public function storeForVaultAsset(Request $request, string $vaultAccountId, string $assetId)
    // {
        
    //     $validator = Validator::make($request->all(), [
    //         'description' => 'nullable|string|max:255',
    //         'customer_ref_id' => 'nullable|string|max:255',
    //         'account_id' => 'nullable|integer',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json([
    //             'success' => false,
    //             'errors' => $validator->errors()
    //         ], 422);
    //     }

    //     try {
    //         $result = $this->depositAddressService->createDepositAddressViaSdk(
    //             $vaultAccountId,
    //             $assetId,
    //             $request->input('description'),
    //             $request->input('customer_ref_id'),
    //             $request->input('account_id') ? (int) $request->input('account_id') : null
    //         );

    //         return response()->json($result, 201);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    /**
     *  Display the Fireblocks deposit addresses page for a specific currency.
     */
    public function newDepositAddress(Request $request)
    {
       // $accounts = Account::where('is_active', true)->where('is_crypto', 1)->get();
        $account_id =  $request->get("ac");
        $network =  $request->get("network");
        $accounts = Account::where("is_active", true)->where('is_crypto', 1)->get();

        return view("fireblocks::new-deposit-address", compact('accounts', 'account_id', 'network'));
        //return view('fireblocks::deposit-addresses', compact('currency', 'vaultAccountId', 'assetId', 'accounts'));
    }

    /**
     * Display the Fireblocks deposit addresses page for a specific currency.
     */
    public function depositAddresses(Request $request, string $currency)
    {
        $currencyModel = \App\Models\Currency::where('short', strtoupper($currency))->where('is_crypto', true)->first();
        if (!$currencyModel) {
            abort(404, 'Currency not found.');
        }

        $network = Network::where('currency_id', $currencyModel->id)
            ->when(env('APP_ENV') === 'production', fn ($q) => $q->where('is_testnet', false), fn ($q) => $q->where('is_testnet', true))
            ->first();

        if (!$network) {
            abort(404, 'Network not configured for this currency.');
        }

        $asset = $this->getAssetConfig($currencyModel->id, $network->id);
        if (!$asset) {
            abort(404, 'Fireblocks asset not configured for this currency and network.');
        }

        if ($asset->type === 'utxo') {
            $vaultAccountId = $asset->vault_account_id;
        } else {
            $vaultAccountId = 'account_based_vault';
        }

        $assetId = $asset->asset_id;
        $accounts = Account::where('is_active', true)->where('is_crypto', 1)->get();

        return view('fireblocks::deposit-addresses', compact('currency', 'vaultAccountId', 'assetId', 'accounts'));
    }

    
   

    
    /**
     * Create a new deposit address via Fireblocks SDK for a specific vault and asset.
     */
    public function storeNewVaultAsset(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'account_id' => 'required|integer',
            'network' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $account_id = (int) $request->get('account_id');
        $network_id = (int) $request->get('network');

        $IDOR = new IDORHelper();
        $is_account_owner = $IDOR->isAccountBelongToUser(Auth::user(), $account_id);
        if (!$is_account_owner) {
            return redirect()->back()->with('error', 'You are not authorized to use this account.');
        }

        $account = Account::where('id', $account_id)->where('is_active', true)->where('is_crypto', true)->first();

        if (empty($account)) {
            return redirect()->back()->with('error', 'Account is required.');
        }

        $client_id = $account->client_id;
        if (!$client_id) {
            return redirect()->back()->with('error', 'Not a valid client account as Client ID is Missing.');
        }

        $currency = $account->currency;
        if (!$currency) {
            return redirect()->back()->with('error', 'Account currency could not be resolved.');
        }

        $title = $currency->short . '-' . $account->client_id;
        $currency_id = $currency->id;
        $asset = $this->getAssetConfig($currency_id, $network_id);

        if (!$asset) {
            return redirect('/fireblocks/new-deposit-address?ac=' . $account_id . '&network=' . $network_id)
                ->with('error', 'No Fireblocks asset is configured for this currency and network. Please contact support.');
        }

        if ($asset->type === 'utxo') {
            if (empty($asset->vault_account_id) || empty($asset->asset_id)) {
                return redirect('/fireblocks/new-deposit-address?ac=' . $account_id . '&network=' . $network_id)
                    ->with('error', 'UTXO asset configuration is incomplete (vault account or asset ID missing).');
            }

            $vaultAccountId = $asset->vault_account_id;
            $assetId = $asset->asset_id;

            $result = $this->depositAddressService->createDepositAddressViaSdk(
                $vaultAccountId,
                $assetId,
                $title . '-' . $account->account_number,
                $account_id,
                $account_id,
                $network_id
            );

            if (!empty($result) && !empty($result['success'])) {
                $address = $result['data']['address'] ?? 'created';
                $message = 'Deposit address ' . $address . ' has been added.';

                return redirect('/fireblocks/new-deposit-address?ac=' . $account_id . '&network=' . $network_id)
                    ->with('success', $message);
            }

            $errorMessage = $result['error'] ?? $result['message'] ?? 'Deposit address was not created, please try again.';

            return redirect('/fireblocks/new-deposit-address?ac=' . $account_id . '&network=' . $network_id)
                ->with('error', $errorMessage);
        }

        // Non-UTXO assets handling
        $vault_name = 'client-vault-' . $account->client_id;

        $addressExists = CryptoAddress::where('account_id', $account_id)->where('network_id', $network_id)->first();

        if ($addressExists) {
            return redirect('/fireblocks/new-deposit-address?ac=' . $account_id)
                ->with('error', 'A deposit address already exists for this account.');
        }

        $fireblocksVault = FireblocksVaultAccount::where('client_id', $client_id)->first();

        if ($fireblocksVault) {
            $vaultAccountId = $fireblocksVault->vault_id;
        } else {
            $vault = $this->createVaultAccount($vault_name, $client_id);

            if (!is_array($vault) || empty($vault['success']) || empty($vault['data']['id'])) {
                return redirect('/fireblocks/new-deposit-address?ac=' . $account_id)
                    ->with('error', 'Vault account could not be created for the asset. Please contact support.');
            }

            $vaultAccountId = $vault['data']['id'];

            FireblocksVaultAccount::create([
                'client_id' => $client_id,
                'vault_id' => $vaultAccountId,
                'vault_title' => $vault_name,
            ]);
        }

        $this->depositAddressService->attachClientIntermediaryTag((string) $vaultAccountId);

        if (empty($asset->asset_id)) {
            return redirect('/fireblocks/new-deposit-address?ac=' . $account_id . '&network=' . $network_id)
                ->with('error', 'Asset configuration is missing the Fireblocks asset ID.');
        }

        $assetId = $asset->asset_id;

        $assetResult = $this->depositAddressService->createVaultAsset(
            $vaultAccountId,
            $assetId,
            $title . '-' . $account->account_number,
            $account_id,
            $network_id
        );

        if (empty($assetResult) || empty($assetResult['success'])) {
            $errorMessage = $assetResult['error'] ?? $assetResult['message'] ?? 'Vault asset was not created, please try again.';

            return redirect('/fireblocks/new-deposit-address?ac=' . $account_id)
                ->with('error', $errorMessage);
        }

        $depositAddresses = $assetResult['data']['address'] ?? null;
        if (empty($depositAddresses)) {
            return redirect('/fireblocks/new-deposit-address?ac=' . $account_id)
                ->with('error', 'No deposit addresses were returned from Fireblocks.');
        }

        $message = 'Deposit address ' . $depositAddresses . ' has been added.';

        return redirect('/fireblocks/new-deposit-address?ac=' . $account_id . '&network=' . $network_id)
            ->with('success', $message);
    }

    public function getVaultAsset(Request $request, string $vaultAccountId, string $assetId) {
        try {
            $result = $this->depositAddressService->getVaultAsset($vaultAccountId, $assetId);

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function createVaultAccount($title,  $customerRefId ) {

        $result = $this->depositAddressService->createVaultAccount(
                $title,
                FireblocksConfigService::vaultHiddenOnUi(),
                $customerRefId,
                FireblocksConfigService::vaultAutoFuel()
            );

        return $result;
    }


    public function getAddressData(Request $request)
    {
        $this->validate($request, [
            'account_id' => 'required',
            'network' => 'required'
        ]);

        $account_id = $request->get("account_id");
        $network = $request->get("network");
        $IDOR = new IDORHelper();
        $is_account_owner = $IDOR->isAccountBelongToUser(Auth::user(), $account_id);
        if (!$is_account_owner) {
            echo json_encode(array());
        }

        if ($account_id != '') {
            $address =   DB::table('accounts')
                ->select(

                    'accounts.title',
                    //'crypto_currency_network_address.crypto_address as bitpace_address',
                    'currencies.name',
                    'currencies.short',
                    'currencies.logo',

                    'users.email',
                    'crypto_addresses.crypto_address',
                    'crypto_addresses.destination_tag',
                    'crypto_addresses.provider',
                     'crypto_addresses.address_alias',
                    'crypto_addresses.network',
                    'crypto_addresses.id as crypto_address_id',
                    'crypto_addresses.created_at',

                )
                ->leftJoin('crypto_addresses', 'crypto_addresses.account_id', '=', 'accounts.id')
                ->leftJoin('currencies', 'currencies.id', '=', 'accounts.account_currency')
                //->leftJoin('crypto_currency_network_address', 'currencies.id', '=', 'crypto_currency_network_address.currency_id')
                ->leftJoin('clients', 'accounts.client_id', '=', 'clients.id')
                ->leftJoin('client_users', 'client_users.client_id', '=', 'clients.id')
                ->leftJoin('users', 'client_users.user_id', '=', 'users.id')
                ->whereNotNull('crypto_addresses.crypto_address') // <-- Added condition here

                ->where('accounts.id', $account_id)
                ->where('crypto_addresses.network_id', $network)
                ->first();
                
            if (!empty($address)) {
                echo json_encode($address);
            }
        }
    }

    public function getAddressDataQRCode(Request $request)
    {
        $this->validate($request, [
            'wallet_address' => 'required'
        ]);
        $wallet_address = $request->get("wallet_address");
        $logo = $request->get('logo');

        if ($wallet_address != '') {
            $clientHttp = new GuzzleHttpClient();

            $token = Session::get('loginToken');
            $onboardingHelper = new OnboardingHelper();
            $baseUrl = $onboardingHelper->getBaseUrlOnboarding();
            $apiKey = $onboardingHelper->getApiKey();

            try {

                $response = $clientHttp->post($baseUrl . 'client/crypto/create-qr-code', [
                    'headers'   => [
                        'api-key'        => $apiKey,
                        'accept'         => 'application/json',
                        'Authorization' => 'Bearer ' . $token,
                    ],
                    'multipart' => [
                        [
                            'name'     => 'wallet_address',
                            'contents' => $wallet_address
                        ],
                        [
                            'name'     => 'logo',
                            'contents' => $logo
                        ]
                    ],
                ]);
            } catch (\Exception $e) {
                Log::info('error from create qr code', ['url' => $e->getMessage(),]);
            }

            echo $response->getBody();
        }
    }

     // Asset to vault account and asset ID mapping - in real implementation, this should come from a config or database
    public function assetMapping()
    {
        
        if (env('APP_ENV') == 'production' ) {
            return  [
                // UTXO
                'btc' => ['type' => 'utxo', 'accountId' => '0', 'assetId' => 'BTC'],
                'bch' => ['type' => 'utxo', 'accountId' => '1', 'assetId' => 'BCH'],
                'ltc' => ['type' => 'utxo', 'accountId' => '2', 'assetId' => 'LTC'],

                // Account-based
                'eth' => ['type' => 'account', 'assetId' => 'ETH'],
                'bnb' => ['type' => 'account', 'assetId' => 'BNB'],

                // (Add more as supported by your provider)
            ];
        } else {
            return  [

                    // UTXO
                    'btc' => ['type' => 'utxo', 'accountId' => '0', 'assetId' => 'BTC_TEST'],
                    'bch' => ['type' => 'utxo', 'accountId' => '1', 'assetId' => 'BCH_TEST'],
                    'ltc' => ['type' => 'utxo', 'accountId' => '2', 'assetId' => 'LTC_TEST'],
                    'xrp' => ['type' => 'utxo', 'accountId' => '0', 'assetId' => 'XRP_TEST'],

                    // Account-based
                    'eth' => ['type' => 'account', 'assetId' => 'ETH_TEST5'],
                    'sol' => ['type' => 'account', 'assetId' => 'SOL_TEST'],
                    'ftsep' => ['type' => 'account', 'assetId' => 'ETH_TEST5'],
                    'trx' => ['type' => 'account', 'assetId' => 'TRX_TEST'],
                    'usdt' => ['type' => 'account', 'assetId' => 'GUSDT_TEST'],
                    'usdc' => ['type' => 'account', 'assetId' => 'USDC_ETH_TEST5_0GER'],
                    //'xrp' => ['type' => 'account', 'assetId' => 'USDC_ETH_TEST5_0GER'],
                    //'sol' => ['type' => 'account', 'assetId' => 'SOL_TEST'],

                    // (Add more testnets as supported by your provider)
                ];
        }
       
    }
    public function getAssetConfig($currency_id, $network_id)
    {
        if (empty($currency_id) || empty($network_id)) {
            return null;
        }

        $query = Asset::where('currency_id', $currency_id)
            ->where('network_id', $network_id)
            ->enabled();

        if (env('APP_ENV') === 'production') {
            $query->where('is_testnet', false);
        } else {
            $query->where('is_testnet', true);
        }

        return $query->first();
    }
    // function getAssetConfig($symbol) {
    //     $symbol = strtolower($symbol);
    //     $map = $this->assetMapping();
    //     if (!isset($map[$symbol])) {
    //         throw new Exception("Unsupported asset: $symbol");
    //     }

    //     return $map[$symbol];
    // }

    public function getCurrencyNetworks(Request $request)
    {
        $currencyCode = $request->get('currency_code');
        $accountId    = $request->get('account_id');
        $currencyId   = null;

        if ($accountId) {
            $currency = $this->getAccountCurrency($accountId);
            if ($currency && $currency->is_crypto) {
                $currencyId = $currency->currency_id;
            }
        } else {
            $currency = Currency::where('short', $currencyCode)
                ->where('is_crypto', true)
                ->first();
            $currencyId = $currency ? $currency->id : null;
        }

        if (!$currencyId) {
            return response()->json([
                'success' => true,
                'message' => 'Success',
                'data'    => [],
            ], 200);
        }

        $isProduction = app()->environment('production');

        $assets = Asset::query()
        ->AssetsEnabled()
        ->join('networks', 'assets.network_id', '=', 'networks.id')
        ->leftJoin('currencies', 'assets.currency_id', '=', 'currencies.id')
        ->where('assets.currency_id', $currencyId)
        ->where('assets.is_testnet', !$isProduction)
        ->where('networks.is_enabled', true) // or use network scope/condition directly
        ->select([
            'assets.id',
            'assets.network_id',
            'assets.currency_id',
            'assets.asset_id as code',
            'assets.type',
            'networks.name as name',
        ])
        ->get();

        return response()->json($assets);
    }


    protected function getAccountCurrency(int $accountId): ?object
    {
        return DB::table('accounts')
            ->select('accounts.account_number', 'currencies.id as currency_id', 'currencies.short as currency_code', 'currencies.name as currency_name', 'currencies.is_crypto')
            ->leftJoin('currencies', 'currencies.id', '=', 'accounts.account_currency')
            ->where('accounts.id', $accountId)
            ->first();
    }

    /**
     * Validate a destination address for a specific asset.
     */
    public function validateWithdrawalAddress(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'asset_id' => 'required|string',
            'address' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $result = $this->withdrawalService->validateAddress(
            $request->input('asset_id'),
            $request->input('address')
        );

        return response()->json($result);
    }

    /**
     * Process a withdrawal transaction via Fireblocks.
     */
    public function withdraw(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'asset_id' => 'required|string',
            'source_vault_account_id' => 'required|string',
            'destination_address' => 'required|string',
            'amount' => 'required|numeric|gt:0',
            'destination_tag' => 'nullable|string',
            'fee_level' => 'nullable|string|in:LOW,MEDIUM,HIGH',
            'note' => 'nullable|string',
            'external_tx_id' => 'nullable|string',
            'customer_ref_id' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $result = $this->withdrawalService->withdraw($request->all());

        if (!$result['success']) {
            return response()->json($result, 500);
        }

        return response()->json($result, 201);
    }

    /**
     * Sync a Fireblocks transaction status locally.
     */
    public function syncWithdrawalStatus(Request $request, string $txId)
    {
        $result = $this->withdrawalService->syncTransactionStatus($txId);

        if (!$result['success']) {
            return response()->json($result, 500);
        }

        return response()->json($result);
    }

    /**
     * List local withdrawal transactions.
     */
    public function listWithdrawals(Request $request)
    {
        try {
            $filters = $request->only(['asset_id', 'status', 'destination_address', 'source_vault_account_id']);
            $result = $this->withdrawalService->listTransactions($filters);

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
