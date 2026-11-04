<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\Currency;
use App\Modules\Fireblocks\Models\Network;
use App\Modules\Fireblocks\Models\Asset;
use App\Helpers\FileFirewall;
use App\Helpers\S3Helper;
use App\Rules\SupportingDocumentsRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\DataTables;

class CryptoCurrencyController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:currency list', ['only' => [
            'index',
            'addCurrency',
            'storeCurrency',
            'editCurrency',
            'updateCurrency',
            'deleteCurrency',
        ]]);

        $this->middleware('role_or_permission:currency list|crypto management', ['only' => [
            'cryptoNetworks',
            'getNetworksData',
            'getAssetsData',
            'storeNetwork',
            'updateNetwork',
            'deleteNetwork',
            'storeAsset',
            'updateAsset',
            'deleteAsset',
            'getAsset',
        ]]);
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Currency::query()->where('is_crypto', true);

            $active = trim((string) $request->input('filter_active', '1'));
            if ($active === '1' || $active === '0') {
                $query->where('is_active', (int) $active);
            }

            $canEdit = auth()->user()->can('edit bank');
            $canDelete = auth()->user()->can('delete records');

            $dataTable = DataTables::of($query)
                ->filter(function ($q) use ($request) {
                    $search = trim((string) $request->input('search.value'));
                    if ($search === '') {
                        return;
                    }

                    $q->where(function ($inner) use ($search) {
                        $inner->where('id', 'like', "%{$search}%")
                            ->orWhere('short', 'like', "%{$search}%")
                            ->orWhere('name', 'like', "%{$search}%");
                    });
                }, true)
                ->addColumn('edit_action', function ($currency) use ($canEdit) {
                    if (!$canEdit) {
                        return '';
                    }

                    $url = url('/crypto-management/crypto-currencies/edit/' . $currency->id);

                    return '<a href="' . e($url) . '"><i class="fal fa-edit"></i></a>';
                })
                ->addColumn('usd_rate_label', function ($currency) {
                    if (!is_numeric($currency->usd_rate ?? null) || (float) $currency->usd_rate <= 0) {
                        return '—';
                    }

                    return number_format((float) $currency->usd_rate, 4, '.', ',');
                })
                ->addColumn('logo_html', function ($currency) {
                    if (empty($currency->logo)) {
                        return '';
                    }

                    return '<img src="' . e($currency->logo) . '" alt="' . e($currency->name) . '" height="18px" style="vertical-align: middle;" />';
                })
                ->addColumn('active_badge', function ($currency) {
                    $class = $currency->is_active ? 'badge-success' : 'badge-primary';
                    $label = $currency->is_active ? 'Yes' : 'No';

                    return '<span class="badge ' . $class . '">' . $label . '</span>';
                })
                ->addColumn('delete_action', function ($currency) use ($canDelete) {
                    if (!$canDelete) {
                        return '';
                    }

                    return '<a href="javascript:deleteCurrency(' . (int) $currency->id . ')"><i class="alert-danger fal fa-times-circle rounded-circle"></i></a>';
                })
                ->rawColumns(['edit_action', 'logo_html', 'active_badge', 'delete_action']);

            $dataTable->orderColumn('id', function ($query, $order) {
                $query->reorder()->orderBy('id', $order);
            });
            $dataTable->orderColumn('short', function ($query, $order) {
                $query->reorder()->orderBy('short', $order);
            });
            $dataTable->orderColumn('name', function ($query, $order) {
                $query->reorder()->orderBy('name', $order);
            });
            $dataTable->orderColumn('usd_rate', function ($query, $order) {
                $query->reorder()->orderBy('usd_rate', $order);
            });
            $dataTable->orderColumn('is_active', function ($query, $order) {
                $query->reorder()->orderBy('is_active', $order);
            });

            return $dataTable->make(true);
        }

        return view('crypto-management.crypto-currency.list');
    }

    public function cryptoNetworks(Request $request)
    {
        $currencies = Currency::orderBy('name')->get();
        $allNetworks = Network::orderBy('name')->get();
        $networkOptions = $allNetworks->map(function ($network) {
            return [
                'id' => $network->id,
                'currency_id' => $network->currency_id,
                'label' => $network->name . ' (' . $network->code . ')',
            ];
        })->values();

        return view('crypto-management.crypto-networks.index', compact('currencies', 'allNetworks', 'networkOptions'));
    }

    public function getNetworksData(Request $request)
    {
        if ($request->ajax()) {
            $query = Network::query()->with('currency');

            return DataTables::of($query)
                ->addColumn('edit_action', function ($network) {
                    return '<span role="button" class="js-edit-network" data-toggle="modal" data-target="#networkModal"'
                        . ' data-id="' . (int) $network->id . '"'
                        . ' data-currency-id="' . (int) $network->currency_id . '"'
                        . ' data-code="' . e($network->code) . '"'
                        . ' data-name="' . e($network->name) . '"'
                        . ' data-is-testnet="' . (int) $network->is_testnet . '"'
                        . ' data-is-enabled="' . (int) ($network->is_enabled ?? 1) . '"'
                        . ' data-logo-url="' . e($network->logo_url ?? '') . '">'
                        . '<i class="fal fa-edit"></i></span>';
                })
                ->addColumn('currency_short', function ($network) {
                    return $network->currency->short ?? '-';
                })
                ->filterColumn('currency_short', function ($query, $keyword) {
                    $query->whereHas('currency', function ($q) use ($keyword) {
                        $q->where('name', 'like', "%{$keyword}%")
                            ->orWhere('short', 'like', "%{$keyword}%");
                    });
                })
                ->orderColumn('currency_short', function ($query, $order) {
                    $query->orderBy(
                        Currency::select('short')
                            ->whereColumn('currencies.id', 'networks.currency_id')
                            ->limit(1),
                        $order
                    );
                })
                ->addColumn('testnet_badge', function ($network) {
                    return $network->is_testnet
                        ? '<span class="badge badge-warning">Yes</span>'
                        : '<span class="badge badge-success">No</span>';
                })
                ->filterColumn('testnet_badge', function ($query, $keyword) {
                    $lower = strtolower($keyword);
                    if (in_array($lower, ['yes', 'true', '1'], true)) {
                        $query->where('is_testnet', 1);
                    } elseif (in_array($lower, ['no', 'false', '0'], true)) {
                        $query->where('is_testnet', 0);
                    }
                })
                ->orderColumn('testnet_badge', 'networks.is_testnet $1')
                ->addColumn('enabled_badge', fn ($network) => $this->formatEnabledBadge($network->is_enabled ?? true))
                ->filterColumn('enabled_badge', function ($query, $keyword) {
                    $lower = strtolower($keyword);
                    if (in_array($lower, ['enabled', 'active', 'yes', 'true', '1'], true)) {
                        $query->where('is_enabled', 1);
                    } elseif (in_array($lower, ['disabled', 'inactive', 'no', 'false', '0'], true)) {
                        $query->where('is_enabled', 0);
                    }
                })
                ->orderColumn('enabled_badge', 'networks.is_enabled $1')
                ->rawColumns(['edit_action', 'testnet_badge', 'enabled_badge'])
                ->make(true);
        }

        abort(404);
    }

    public function getAssetsData(Request $request)
    {
        if ($request->ajax()) {
            $query = Asset::query()->with(['currency', 'network']);

            return DataTables::of($query)
                ->addColumn('edit_action', function ($asset) {
                    return '<span role="button" data-toggle="modal" data-target="#assetModal" onclick="loadAssetForEdit('
                        . (int) $asset->id
                        . ')"><i class="fal fa-edit"></i></span>';
                })
                ->editColumn('type', function ($asset) {
                    return ucfirst((string) $asset->type);
                })
                ->addColumn('currency_short', function ($asset) {
                    return $asset->currency->short ?? '-';
                })
                ->filterColumn('currency_short', function ($query, $keyword) {
                    $query->whereHas('currency', function ($q) use ($keyword) {
                        $q->where('name', 'like', "%{$keyword}%")
                            ->orWhere('short', 'like', "%{$keyword}%");
                    });
                })
                ->orderColumn('currency_short', function ($query, $order) {
                    $query->orderBy(
                        Currency::select('short')
                            ->whereColumn('currencies.id', 'assets.currency_id')
                            ->limit(1),
                        $order
                    );
                })
                ->addColumn('network_name', function ($asset) {
                    return $asset->network->name ?? $asset->network->code ?? '-';
                })
                ->filterColumn('network_name', function ($query, $keyword) {
                    $query->whereHas('network', function ($q) use ($keyword) {
                        $q->where('name', 'like', "%{$keyword}%")
                            ->orWhere('code', 'like', "%{$keyword}%");
                    });
                })
                ->orderColumn('network_name', function ($query, $order) {
                    $query->orderBy(
                        Network::select('name')
                            ->whereColumn('networks.id', 'assets.network_id')
                            ->limit(1),
                        $order
                    );
                })
                ->addColumn('contract_address_label', function ($asset) {
                    return $asset->contract_address ?: '-';
                })
                ->filterColumn('contract_address_label', function ($query, $keyword) {
                    $query->where('contract_address', 'like', "%{$keyword}%");
                })
                ->addColumn('vault_account_id_label', function ($asset) {
                    $vaultId = $asset->vault_account_id;
                    if ($vaultId !== null && $vaultId !== '') {
                        return e((string) $vaultId);
                    }

                    return '<span class="text-muted">—</span>';
                })
                ->filterColumn('vault_account_id_label', function ($query, $keyword) {
                    $query->where('vault_account_id', 'like', "%{$keyword}%");
                })
                ->addColumn('testnet_badge', function ($asset) {
                    return $asset->is_testnet
                        ? '<span class="badge badge-warning">Yes</span>'
                        : '<span class="badge badge-success">No</span>';
                })
                ->filterColumn('testnet_badge', function ($query, $keyword) {
                    $lower = strtolower($keyword);
                    if (in_array($lower, ['yes', 'true', '1'], true)) {
                        $query->where('is_testnet', 1);
                    } elseif (in_array($lower, ['no', 'false', '0'], true)) {
                        $query->where('is_testnet', 0);
                    }
                })
                ->orderColumn('testnet_badge', 'assets.is_testnet $1')
                ->addColumn('enabled_badge', fn ($asset) => $this->formatEnabledBadge($asset->is_enabled ?? true))
                ->filterColumn('enabled_badge', function ($query, $keyword) {
                    $lower = strtolower($keyword);
                    if (in_array($lower, ['enabled', 'active', 'yes', 'true', '1'], true)) {
                        $query->where('is_enabled', 1);
                    } elseif (in_array($lower, ['disabled', 'inactive', 'no', 'false', '0'], true)) {
                        $query->where('is_enabled', 0);
                    }
                })
                ->orderColumn('enabled_badge', 'assets.is_enabled $1')
                ->rawColumns(['edit_action', 'vault_account_id_label', 'testnet_badge', 'enabled_badge'])
                ->make(true);
        }

        abort(404);
    }

    public function addCurrency()
    {
        return view("crypto-management.crypto-currency.new");
    }

    public function storeCurrency(Request $request)
    {
        $this->validate($request, [
            'short' => 'required|unique:currencies,short',
            'name' => 'required',
            'min-amount' => 'required',
            'max-amount' => 'required',
            'logo' => ['required', new SupportingDocumentsRule, 'mimes:jpeg,jpg,png,webp,svg']
        ]);

        $currency = new Currency();

        $currency->short = $request->get("short");
        $currency->name = $request->get("name");
        $currency->request_auto_approval = ($request->get("request_auto_approval")) ? 1 : 0;
        $currency->is_active = ($request->get("is_active")) ? 1 : 0;
        $currency->min_amount = $request->get("min-amount");
        $currency->decimals = $request->get("decimals");
        $currency->is_crypto = 1;

        if ($request->hasFile('logo')) {

            // validate file
            if (!FileFirewall::isAllowedType($request->file('logo'))) {
                return FileFirewall::logBadFileException($request->file('logo'));
            }

            if ($currency->logo) {
                S3Helper::fileDelete($currency->logo);
            }

            $currency->logo = S3Helper::fileUpload($request->file('logo'), "crypto-logos/" . $request->get("short"));
        }

        $currency->save();

        return redirect("/crypto-management/crypto-currencies")->with("success", "Currency added successfully");
    }

    public function editCurrency($id)
    {
        $currency = Currency::find($id);

        $currencyNetworks = Network::where('currency_id', $id)->get();

        //$allNetworks = Network::orderBy('name')->get();

        $assets = Asset::with('network')
            ->where('currency_id', $id)
            ->whereHas('network', function ($q) use ($id) {
                $q->where('currency_id', $id);
            })
            ->get();

        return view("crypto-management.crypto-currency.edit", compact('currency', 'currencyNetworks', 'assets'));
    }

    public function updateCurrency(Request $request)
    {
        $this->validate($request, [
            'short' => 'required',
            'name' => 'required',
            'min-amount' => 'required',
            'max-amount' => 'required',
            'logo' => [new SupportingDocumentsRule, 'mimes:jpeg,jpg,png,webp,svg']
        ]);

        $currency = Currency::find($request->get("id"));

        if ($currency) {
            if (!$currency->logo) {
                $this->validate($request, [
                    'logo' => ['required', new SupportingDocumentsRule, 'mimes:jpeg,jpg,png,webp,svg']
                ]);
            }

            $currency->short = $request->get("short");
            $currency->name = $request->get("name");
            $currency->request_auto_approval = ($request->get("request_auto_approval")) ? 1 : 0;
            $currency->is_active = ($request->get("is_active")) ? 1 : 0;
            $currency->min_amount = $request->get("min-amount");
            $currency->max_amount = $request->get("max-amount");
            $currency->decimals = $request->get("decimals");

            if ($request->hasFile('logo')) {

                // validate file
                if (!FileFirewall::isAllowedType($request->file('logo'))) {
                    return FileFirewall::logBadFileException($request->file('logo'));
                }

                if ($currency->logo) {
                    S3Helper::fileDelete($currency->logo);
                }

                $currency->logo = S3Helper::fileUpload($request->file('logo'), "crypto-logos/" . $request->get("short"));
            }

            $currency->save();

            return redirect("/crypto-management/crypto-currencies")->with("success", "Currency updated successfully");
        } else {
            return redirect("/crypto-management/crypto-currencies")->with("error", "Currency doesn't exists");
        }
    }

    public function deleteCurrency(Request $request)
    {
        $currency = Currency::find($request->get("row_id"));

        // check if currency is not attached to any transaction
        $isTransactionExists = AccountTransaction::where("currency_id", $currency->id)->first();

        // check if currency is not attached to any account
        $isAccountExists = Account::where("account_currency", $currency->id)->first();

        if ($isTransactionExists || $isAccountExists) {
            return redirect("/finance-management/currencies")->with("error", "Currency can't be deleted, its attached to some transactions and or accounts");
        } else {
            if ($currency->logo) {
                S3Helper::fileDelete($currency->logo);
            }

            $currency->delete();
            return redirect("/crypto-management/crypto-currencies")->with("success", "Currency deleted successfully");
        }
    }

    /* --------------------------------------------------------------
     *  Network CRUD
     * -------------------------------------------------------------- */

    public function storeNetwork(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'currency_id' => 'required|integer',
            'code' => 'required|string|max:100',
            'name' => 'required|string|max:255',
            'is_testnet' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        Network::create([
            'currency_id' => $request->get('currency_id'),
            'code' => $request->get('code'),
            'name' => $request->get('name'),
            'is_testnet' => $request->boolean('is_testnet') ? 1 : 0,
            'is_enabled' => $request->boolean('is_enabled', true) ? 1 : 0,
        ]);

        return redirect()->back()->with('success', 'Network added successfully.');
    }

    public function updateNetwork(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer',
            'currency_id' => 'required|integer',
            'code' => 'required|string|max:100',
            'name' => 'required|string|max:255',
            'is_testnet' => 'nullable|boolean',
            'logo' => ['nullable', new SupportingDocumentsRule, 'mimes:jpeg,jpg,png,webp,svg'],
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $network = Network::find($request->get('id'));
        if (!$network) {
            return redirect()->back()->with('error', 'Network not found.');
        }

        $payload = [
            'currency_id' => $request->get('currency_id'),
            'code' => $request->get('code'),
            'name' => $request->get('name'),
            'is_testnet' => $request->boolean('is_testnet') ? 1 : 0,
            'is_enabled' => $request->boolean('is_enabled') ? 1 : 0,
        ];

        if ($request->hasFile('logo')) {
            $logoResponse = $this->uploadCryptoLogo(
                $request,
                'crypto-network-logos/' . preg_replace('/[^A-Za-z0-9._-]/', '_', (string) $request->get('code')),
                $network->logo_url
            );
            if ($logoResponse instanceof \Illuminate\Http\RedirectResponse) {
                return $logoResponse;
            }
            $payload['logo_url'] = $logoResponse;
        }

        $network->update($payload);

        return redirect()->back()->with('success', 'Network updated successfully.');
    }

    /* --------------------------------------------------------------
     *  Asset CRUD
     * -------------------------------------------------------------- */

    public function storeAsset(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'currency_id' => 'required|integer',
            'network_id' => 'required|integer',
            'asset_id' => 'required|string|max:255',
            'asset_name' => 'required|string|max:255',
            'type' => 'required|in:utxo,account,token,fiat',
            'contract_address' => 'nullable|string|max:255',
            'vault_account_id' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        Asset::create([
            'currency_id' => $request->get('currency_id'),
            'network_id' => $request->get('network_id'),
            'asset_id' => $request->get('asset_id'),
            'name' => $request->get('asset_name'),
            'type' => $request->get('type'),
            'is_testnet' => $request->boolean('is_testnet') ? 1 : 0,
            'is_enabled' => $request->boolean('is_enabled', true) ? 1 : 0,
            'contract_address' => $request->get('contract_address'),
            'vault_account_id' => $request->get('vault_account_id'),
        ]);

        return redirect()->back()->with('success', 'Asset added successfully.');
    }

    public function updateAsset(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer',
            'currency_id' => 'required|integer',
            'network_id' => 'required|integer',
            'asset_id' => 'required|string|max:255',
            'asset_name' => 'required|string|max:255',
            'type' => 'required|in:utxo,account,token,fiat',
            'contract_address' => 'nullable|string|max:255',
            'vault_account_id' => 'nullable|string|max:255',
            'logo' => ['nullable', new SupportingDocumentsRule, 'mimes:jpeg,jpg,png,webp,svg'],
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $asset = Asset::find($request->get('id'));
        if (!$asset) {
            return redirect()->back()->with('error', 'Asset not found.');
        }

        $data = [
            'currency_id' => $request->get('currency_id'),
            'network_id' => $request->get('network_id'),
            'asset_id' => $request->get('asset_id'),
            'name' => $request->get('asset_name'),
            'type' => $request->get('type'),
            'is_testnet' => $request->boolean('is_testnet') ? 1 : 0,
            'is_enabled' => $request->boolean('is_enabled') ? 1 : 0,
            'contract_address' => $request->get('contract_address'),
            'vault_account_id' => $request->get('vault_account_id'),
        ];

        if ($request->hasFile('logo')) {
            $logoResponse = $this->uploadCryptoLogo(
                $request,
                'crypto-asset-logos/' . preg_replace('/[^A-Za-z0-9._-]/', '_', (string) $request->get('asset_id')),
                $asset->logo_url
            );
            if ($logoResponse instanceof \Illuminate\Http\RedirectResponse) {
                return $logoResponse;
            }
            $data['logo_url'] = $logoResponse;
        }

        $asset->update($data);

        return redirect()->back()->with('success', 'Asset updated successfully.');
    }

    public function deleteNetwork(Request $request)
    {
        $network = Network::find($request->get('id'));
        if (!$network) {
            return redirect()->back()->with('error', 'Network not found.');
        }
        $network->delete();
        return redirect()->back()->with('success', 'Network deleted successfully.');
    }

    public function deleteAsset(Request $request)
    {
        $asset = Asset::find($request->get('id'));
        if (!$asset) {
            return redirect()->back()->with('error', 'Asset not found.');
        }
        $asset->delete();
        return redirect()->back()->with('success', 'Asset deleted successfully.');
    }

    public function getAsset($id)
    {
        $asset = Asset::with('network')->find($id);
        if (!$asset) {
            return response()->json(['success' => false, 'message' => 'Asset not found.'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $asset->id,
                'currency_id' => $asset->currency_id,
                'network_id' => $asset->network_id,
                'asset_id' => $asset->asset_id,
                'asset_name' => $asset->name,
                'type' => $asset->type,
                'contract_address' => $asset->contract_address,
                'vault_account_id' => $asset->vault_account_id,
                'is_testnet' => $asset->is_testnet,
                'is_enabled' => $asset->is_enabled,
                'logo_url' => $asset->logo_url,
            ]
        ]);
    }

    private function formatEnabledBadge(bool $enabled): string
    {
        return $enabled
            ? '<span class="badge badge-success">Enabled</span>'
            : '<span class="badge badge-secondary">Disabled</span>';
    }

    /**
     * @return string|\Illuminate\Http\RedirectResponse
     */
    private function uploadCryptoLogo(Request $request, string $storagePath, ?string $existingLogoUrl)
    {
        if (!FileFirewall::isAllowedType($request->file('logo'))) {
            return FileFirewall::logBadFileException($request->file('logo'));
        }

        if ($existingLogoUrl) {
            S3Helper::fileDelete($existingLogoUrl);
        }

        return S3Helper::fileUpload($request->file('logo'), $storagePath);
    }
}
