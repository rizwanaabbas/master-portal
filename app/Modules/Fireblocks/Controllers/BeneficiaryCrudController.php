<?php

namespace App\Modules\Fireblocks\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\Account;
use App\Models\Currency;
use App\Models\BitpaceVasp;
use App\Helpers\ClientHelper;
use App\Modules\Fireblocks\Models\CryptoBeneficiary;
use App\Modules\Fireblocks\Models\Network;
use App\Modules\Fireblocks\Models\Asset;
use App\Modules\Fireblocks\Helpers\FireblocksStatusHelper;
use App\Modules\Fireblocks\Services\BeneficiaryGridService;
use App\Modules\Fireblocks\Services\BeneficiaryService;
use App\Modules\Fireblocks\Services\FireblocksConfigService;
use Illuminate\Database\Eloquent\Builder;
use Yajra\DataTables\Facades\DataTables;
use App\Helpers\CryptoLogHelper;
use Illuminate\Support\Facades\DB;
use App\Modules\Chainalysis\Services\ChainAnalysisConfigService;
use App\Modules\Chainalysis\Services\ChainalysisService;

class BeneficiaryCrudController extends Controller
{
    protected BeneficiaryService $beneficiaryService;

    const ASSET_TYPES = ['crypto', 'iban', 'aba', 'sepa', 'ach', 'us_wire', 'spei', 'pix', 'momo', 'lbt', 'interac', 'payid', 'chaps'];

    /**
     * Fireblocks NETWORK codes whose chains use a destination tag / memo to route
     * funds. Tag support is a property of the network, not the currency — e.g. ATOM
     * on Cosmos (ATOM_COS) needs a memo, but ATOM on BNB Smart Chain does not.
     * Codes are matched after stripping any trailing "_TEST" suffix.
     * Derived from the configured `networks` table (native memo/tag chains).
     */
    const TAG_NETWORKS = [
        'XRP',           // XRP Ledger — destination tag
        'XLM',           // Stellar — memo
        'XDB',           // DigitalBits — memo
        'EOS',           // EOS — memo
        'XEM',           // NEM — message
        'HBAR',          // Hedera — memo
        'TON',           // TON — comment/memo
        // Cosmos-SDK family — memo
        'ATOM_COS', 'ATOM_PROVIDER', 'OSMO', 'KAVA_KAVA', 'AKASH', 'AXL',
        'BABYLON', 'CELESTIA', 'DYDX_DYDX', 'INITIA', 'INJ', 'INJ_INJ',
        'MANTRA', 'NTRN', 'RUNE_THOR', 'SAGA', 'SEI', 'TX_COS', 'XION', 'ZIGCHAIN',
    ];

    /**
     * Whether the given Fireblocks network code requires a destination tag / memo.
     */
    public static function requiresDestinationTag(?string $networkCode): bool
    {
        $code = strtoupper((string) $networkCode);
        $code = preg_replace('/_TEST$/', '', $code); // normalise testnet codes

        return in_array($code, self::TAG_NETWORKS, true);
    }

    public function __construct(
        BeneficiaryService $beneficiaryService,
        protected BeneficiaryGridService $beneficiaryGrid,
    ) {
        $this->middleware(['auth', '2fa', 'permission:fireblocks custody']);
        $this->beneficiaryService = $beneficiaryService;
    }

    /**
     * List all Fireblocks beneficiaries.
     */
    public function index()
    {
        $activeCurrencies = Currency::query()
            ->where('is_active', true)
            ->orderBy('short')
            ->get(['id', 'short', 'name']);

        $fbAssetStatuses = CryptoBeneficiary::query()
            ->whereNotNull('fireblocks_asset_status')
            ->where('fireblocks_asset_status', '!=', '')
            ->distinct()
            ->orderBy('fireblocks_asset_status')
            ->pluck('fireblocks_asset_status');

        return view('fireblocks::beneficiaries.index', compact('activeCurrencies', 'fbAssetStatuses'));
    }

    public function assetsSuggest(Request $request)
    {
        $term = trim((string) $request->input('q', ''));

        $assets = Asset::query()
            ->enabled()
            ->with(['network:id,code,name'])
            ->when($term !== '', function ($query) use ($term) {
                $like = '%' . $term . '%';
                $query->where(function ($inner) use ($like) {
                    $inner->where('asset_id', 'like', $like)
                        ->orWhereHas('network', function ($networkQuery) use ($like) {
                            $networkQuery->where('code', 'like', $like)
                                ->orWhere('name', 'like', $like);
                        });
                });
            })
            ->orderBy('asset_id')
            ->limit(40)
            ->get(['id', 'asset_id', 'network_id']);

        return response()->json([
            'results' => $assets->map(function (Asset $asset) {
                $network = $asset->network;
                $suffix = $network
                    ? ' — ' . $network->code . ' (' . $network->name . ')'
                    : '';

                return [
                    'id' => $asset->id,
                    'text' => $asset->asset_id . $suffix,
                ];
            })->values(),
        ]);
    }

    public function data(Request $request)
    {
        $query = $this->beneficiaryGrid->baseQuery();
        $this->beneficiaryGrid->applyEnabledCurrencyScope($query);
        $this->beneficiaryGrid->applyFilters($query, $request);

        $caStatusMap = [
            'low' => 'success',
            'medium' => 'warning',
            'high' => 'danger',
            'severe' => 'dark',
        ];

        return DataTables::of($query)
            // false = only custom applySearch; true also runs Yajra default search on
            // column aliases (beneficiary_name, account_title, …) and breaks Ajax.
            ->filter(function (Builder $q) use ($request) {
                $this->beneficiaryGrid->applySearch($q, $request->input('search.value'));
            }, false)
            ->orderColumn('id', 'crypto_beneficiaries.id $1')
            ->orderColumn('fireblocks_wallet_id', 'crypto_beneficiaries.fireblocks_wallet_id $1')
            ->orderColumn('beneficiary_name', 'crypto_beneficiaries.beneficiary_legal_name $1')
            ->orderColumn('account_title', 'accounts.title $1')
            ->orderColumn('currency_short', 'currencies.short $1')
            ->orderColumn('network_code', 'networks.code $1')
            ->orderColumn('wallet_address', 'crypto_beneficiaries.wallet_address $1')
            ->orderColumn('ca_status', 'crypto_address_compliance.status $1')
            ->orderColumn('fb_asset_status', 'crypto_beneficiaries.fireblocks_asset_status $1')
            ->orderColumn('portal_status', 'crypto_beneficiaries.is_active $1')
            ->addColumn('view_action', function (CryptoBeneficiary $row) {
                $url = url('/fireblocks/beneficiaries/' . $row->id . '/edit');

                return '<a href="' . e($url) . '" title="View"><i class="alert-success fal fa-eye"></i></a>';
            })
            ->addColumn('beneficiary_name', function (CryptoBeneficiary $row) {
                $name = $row->beneficiary_legal_name ?: trim($row->beneficiary_first_name . ' ' . $row->beneficiary_last_name);
                $type = e($row->beneficiary_type ?? '');

                return e($name) . ($type !== '' ? '<small class="d-block text-muted">' . $type . '</small>' : '');
            })
            ->addColumn('account_label', function (CryptoBeneficiary $row) {
                $account = $row->account;
                if (!$account) {
                    return '—';
                }

                return e($account->title) . '<small class="d-block text-muted">' . e($account->account_number) . '</small>';
            })
            ->addColumn('currency_short', fn (CryptoBeneficiary $row) => e(optional($row->currency)->short ?? '—'))
            ->addColumn('asset_label', function (CryptoBeneficiary $row) {
                $network = $row->network;
                if (!$network) {
                    return '—';
                }

                return e($network->name) . ' <small>(' . e($network->code) . ')</small>';
            })
            ->addColumn('wallet_cell', function (CryptoBeneficiary $row) {
                $html = '<span style="word-break: break-all;">' . e($row->wallet_address) . '</span>';
                if ($row->destination_tag) {
                    $html .= '<small class="d-block text-muted">Tag: ' . e($row->destination_tag) . '</small>';
                }

                return $html;
            })
            ->addColumn('ca_status_cell', function (CryptoBeneficiary $row) use ($caStatusMap) {
                $compliance = $row->compliance;
                $status = e($compliance->status ?? '--');
                $html = $status;
                if ($compliance && $compliance->risk) {
                    $riskKey = strtolower((string) $compliance->risk);
                    $badge = $caStatusMap[$riskKey] ?? 'secondary';
                    $html .= ' <span class="badge badge-' . e($badge) . '">' . e($compliance->risk) . '</span>';
                }

                return $html;
            })
            ->addColumn('fb_asset_status', fn (CryptoBeneficiary $row) => e($row->fireblocks_asset_status ?? '--'))
            ->addColumn('portal_status_badge', function (CryptoBeneficiary $row) {
                $badge = FireblocksStatusHelper::beneficiaryStatusBadge($row->fireblocks_asset_status, $row->is_active);

                return '<span class="badge ' . e($badge['class']) . '">' . e($badge['label']) . '</span>';
            })
            ->rawColumns([
                'view_action',
                'beneficiary_name',
                'account_label',
                'asset_label',
                'wallet_cell',
                'ca_status_cell',
                'portal_status_badge',
            ])
            ->make(true);
    }

    /**
     * Show the create beneficiary form (find-or-create wallet + add address).
     */
    public function create()
    {
        $cryptoAccounts = $this->cryptoAccounts();
        $vasp_list = BitpaceVasp::get();
        $asset_types = self::ASSET_TYPES;
        $travelRulesEnabled = FireblocksConfigService::travelRulesEnabled();

        return view('fireblocks::beneficiaries.create', compact('cryptoAccounts', 'vasp_list', 'asset_types', 'travelRulesEnabled'));
    }

    /**
     * Store a new beneficiary — find-or-creates the client's external wallet then whitelists the address.
     */
    public function store(Request $request)
    {
        $travelRulesEnabled = FireblocksConfigService::travelRulesEnabled();

        $rules = [
            'account_id'     => 'required|numeric',
            'network_id'     => 'required|numeric',
            'label'          => 'required|string|max:100',
            'wallet_address' => 'required|string|max:255',
            'asset_type'     => 'required|in:' . implode(',', self::ASSET_TYPES),
            'destination_tag' => 'nullable|string|max:100',
        ];

        if ($travelRulesEnabled) {
            // Travel Rule PII
            $rules['beneficiary_type'] = 'required|in:INDIVIDUAL,BUSINESS';
            $rules['wallet_type']      = 'required|in:CUSTODIAL,SELF_HOSTED';
        }

        $this->validate($request, $rules);

        $account = Account::where('id', $request->account_id)
            ->where('is_active', true)
            ->where('is_crypto', true)
            ->first();

        if (!$account) {
            return redirect()->back()->withInput()->with('error', 'Account not found or is not an active crypto account.');
        }

        $client = ClientHelper::getClientByAccountId($account->id);
        if (!$client) {
            return redirect()->back()->withInput()->with('error', 'Client not found for this account.');
        }

        $network = Network::where('id', $request->network_id)
            ->where('currency_id', $account->account_currency)
            ->enabled()
            ->first();

        if (!$network) {
            return redirect()->back()->withInput()->with('error', 'Network not found for the account currency.');
        }

        // Destination tag is mandatory only for networks whose chain uses one.
        if (self::requiresDestinationTag($network->code) && trim((string) $request->destination_tag) === '') {
            return redirect()->back()->withInput()
                ->with('error', "A destination tag / memo is required for the {$network->name} network.");
        }

        $asset = Asset::where('network_id', $network->id)
            ->where('currency_id', $account->account_currency)
            ->enabled()
            ->first();

        if (!$asset) {
            return redirect()->back()->withInput()->with('error', 'Asset not configured for the selected network.');
        }

        // Prevent duplicate address on the same network for this account
        $exists = CryptoBeneficiary::where('account_id', $account->id)
            ->where('wallet_address', $request->wallet_address)
            ->where('network_id', $network->id)
            ->exists();

        if ($exists) {
            return redirect()->back()->withInput()->with('error', 'A beneficiary with this wallet address and network already exists for this account.');
        }

        // Chainalysis compliance screen (only when the module is enabled)
        if (ChainAnalysisConfigService::isSanctionsScreeningEnabled()) {
            try {
                $compliance = app(ChainalysisService::class)->screenAddress($request->wallet_address, 'withdraw_address', null);
                if (ChainAnalysisConfigService::isRisky($compliance->risk)) {
                    return redirect()->back()->withInput()->with('error', 'The wallet address did not pass compliance checks and cannot be added.');
                }
            } catch (\Throwable $e) {
                Log::error('BeneficiaryCrudController@store: Chainalysis scan failed. ' . $e->getMessage());
                return redirect()->back()->withInput()->with('error', 'The wallet address could not be verified. Please try again later.');
            }
        }

        // Step 1 — find-or-create the client's external wallet on Fireblocks
        $walletResult = $this->beneficiaryService->findOrCreateExternalWallet([
            'client_id'       => $client->id,
            'name'            => $client->name . ' - ' . $client->id,
            'customer_ref_id' => (string) $client->id,
            'created_by'      => Auth::id(),
        ]);

        if (!$walletResult['success']) {
            if (($walletResult['error'] ?? '') === 'operation_unavailable') {
                return redirect()->back()->withInput()->with(
                    'error',
                    $walletResult['message'] ?? 'This operation is not available at the moment. Please contact support.'
                );
            }

            Log::error('BeneficiaryCrudController@store: wallet - ' . ($walletResult['error'] ?? 'Unknown'));
            return redirect()->back()->withInput()->with('error', 'Failed to prepare external wallet: ' . ($walletResult['error'] ?? 'Unknown error'));
        }

        $externalWallet = $walletResult['data'];

        // Step 2 — whitelist the address/asset under the wallet and create the beneficiary
        $assetResult = $this->beneficiaryService->addWalletAsset($externalWallet, [
            'account_id'           => $account->id,
            'label'                => $request->label,
            'wallet_address'       => $request->wallet_address,
            'fireblocks_wallet_id' => $externalWallet->fireblocks_wallet_id,
            'destination_tag'      => $request->destination_tag,
            'currency_id'          => $account->account_currency,
            'network_id'           => $network->id,
            'asset_id'             => $asset->asset_id,
            'asset_type'           => $request->asset_type,
            'additional_info'      => $request->additional_info,
            'created_by'           => Auth::id(),
        ]);

        if (!$assetResult['success']) {
            if (($assetResult['error'] ?? '') === 'operation_unavailable') {
                return redirect()->back()->withInput()->with(
                    'error',
                    $assetResult['message'] ?? 'This operation is not available at the moment. Please contact support.'
                );
            }

            Log::error('BeneficiaryCrudController@store: asset - ' . ($assetResult['error'] ?? 'Unknown'));
            return redirect()->back()->withInput()->with('error', 'Failed to add address to wallet: ' . ($assetResult['error'] ?? 'Unknown error'));
        }

        $beneficiary = $assetResult['data'];

        ChainalysisService::attachComplianceToBeneficiary($request->wallet_address, (int) $beneficiary->id);

        // Persist the Travel Rule PII on the beneficiary (only when enabled)
        if ($travelRulesEnabled) {
            $beneficiary->update($this->travelRuleFields($request, $client));
        }

        // Record the creation on the crypto transaction log trail.
        CryptoLogHelper::logCryptoTransaction(
            $beneficiary->logKey(),
            [
                'action'          => 'beneficiary_created',
                'beneficiary_id'  => $beneficiary->id,
                'label'           => $beneficiary->label,
                'wallet_address'  => $beneficiary->wallet_address,
                'destination_tag' => $beneficiary->destination_tag,
                'currency'        => optional($beneficiary->currency)->short,
                'network'         => optional($beneficiary->network)->name,
                'asset_id'        => $beneficiary->fireblocks_asset_id,
                'status'          => $beneficiary->fireblocks_asset_status,
                'created_by'      => optional(Auth::user())->name,
            ],
            'Fireblocks',
            'client'
        );

        return redirect('/fireblocks/beneficiaries')->with('success', 'Fireblocks beneficiary created successfully.');
    }

    /**
     * Show the beneficiary record (view only).
     */
    public function edit($id)
    {
        $beneficiary = CryptoBeneficiary::with(['account:id,title,account_number', 'currency:id,short,name', 'network:id,code,name'])->findOrFail($id);
        $vasp_list = BitpaceVasp::get();
        $travelRulesEnabled = FireblocksConfigService::travelRulesEnabled();

        // Full trail (creation + webhook status changes) for this beneficiary.
        $transaction_log = DB::table('crypto_transactions_log')
            ->where('transaction_id', $beneficiary->logKey())
            ->orderBy('id', 'DESC')
            ->get();

        return view('fireblocks::beneficiaries.edit', compact('beneficiary', 'vasp_list', 'travelRulesEnabled', 'transaction_log'));
    }

    /**
     * Update mutable fields + Travel Rule PII.
     */
    public function update(Request $request, $id)
    {
        $travelRulesEnabled = FireblocksConfigService::travelRulesEnabled();

        $rules = [
            'label'            => 'required|string|max:100',
            'destination_tag'  => 'nullable|string|max:100',
        ];

        if ($travelRulesEnabled) {
            $rules['beneficiary_type'] = 'required|in:INDIVIDUAL,BUSINESS';
            $rules['wallet_type']      = 'required|in:CUSTODIAL,SELF_HOSTED';
        }

        $this->validate($request, $rules);

        $beneficiary = CryptoBeneficiary::with('network:id,code,name')->findOrFail($id);

        // Destination tag is mandatory only for networks whose chain uses one.
        if (self::requiresDestinationTag(optional($beneficiary->network)->code) && trim((string) $request->destination_tag) === '') {
            return redirect()->back()->withInput()
                ->with('error', 'A destination tag / memo is required for the ' . optional($beneficiary->network)->name . ' network.');
        }

        $client = ClientHelper::getClientByAccountId($beneficiary->account_id);

        $data = [
            'label'           => $request->label,
            'destination_tag' => $request->destination_tag,
            'is_active'       => $request->boolean('is_active'),
        ];

        if ($travelRulesEnabled) {
            $data = array_merge($data, $this->travelRuleFields($request, $client));
        }

        $beneficiary->update($data);

        return redirect('/fireblocks/beneficiaries')->with('success', 'Beneficiary updated successfully.');
    }

    /**
     * Soft-delete the beneficiary.
     */
    public function destroy($id)
    {
        $beneficiary = CryptoBeneficiary::findOrFail($id);
        $result = $this->beneficiaryService->delete($beneficiary);

        if (!$result['success']) {
            return redirect()->back()->with('error', 'Failed to delete beneficiary: ' . ($result['error'] ?? 'Unknown error'));
        }

        return redirect('/fireblocks/beneficiaries')->with('success', 'Beneficiary deleted successfully.');
    }

    /**
     * Map request input to the Travel Rule PII columns.
     */
    private function travelRuleFields(Request $request, $client = null): array
    {
        $isSendingToMyself = $request->boolean('is_sending_to_myself');
        $legalName = $request->beneficiary_legal_name;

        if ($isSendingToMyself && $client) {
            $legalName = $client->name;
        }

        // Resolve VASP identifier (custom name when the beneficiary VASP is not on the list)
        $vaspDid = $request->beneficiary_vasp_did;
        if ($request->boolean('is_not_on_vasp_list') && !empty($request->beneficiary_vasp_name)) {
            $vaspDid = $request->beneficiary_vasp_name;
        }

        return [
            'beneficiary_type'         => $request->beneficiary_type,
            'beneficiary_first_name'   => $request->beneficiary_first_name,
            'beneficiary_last_name'    => $request->beneficiary_last_name,
            'beneficiary_legal_name'   => $legalName,
            'dob'                      => $request->dob ?: null,
            'country_code'             => $request->country_code,
            'wallet_type'              => $request->wallet_type,
            'beneficiary_vasp_did'     => $vaspDid,
            'beneficiary_vasp_name'    => $request->beneficiary_vasp_name,
            'is_not_on_vasp_list'      => $request->boolean('is_not_on_vasp_list'),
            'is_ownership_declaration' => $request->boolean('is_ownership_declaration'),
            'is_sending_to_myself'     => $isSendingToMyself,
        ];
    }

    /**
     * Active crypto accounts for the account dropdown.
     */
    private function cryptoAccounts()
    {
        $currencies = Currency::where('is_active', true)->where('is_crypto', true)->select('id')->get();

        return Account::where('is_active', true)
            ->whereIn('account_currency', $currencies)
            ->select('id', 'title', 'account_number')
            ->get();
    }
}
