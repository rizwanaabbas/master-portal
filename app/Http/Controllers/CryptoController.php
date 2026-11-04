<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Redirector;
use App\Helpers\Common;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\DB;
use App\Helpers\IDORHelper;
use App\Helpers\ClientHelper;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CryptoController extends Controller
{
    public function __construct(Redirector $redirect)
    {
      
        if (!in_array('Crypto', Common::getEnabledModules())) {
            //$redirect->to('/')->send();
            abort('403');
        }
        
        $this->middleware('permission:crypto management', ['only' => [ 'getDepositAddresses','getAddressData','getAddressDataQRCode']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request) {}

    /**
     * Display the Adresses grid.
     *
     * @param  \App\Models\Bitpace  $bitpace
     * @return \Illuminate\Http\Response
     */

    public function getDepositAddresses(Request $request)
    {
        if ($request->ajax()) {
            $query = DB::table('crypto_addresses')
                ->select(
                    'crypto_addresses.id',
                    \DB::raw("
                        CASE 
                            WHEN crypto_addresses.address_alias IS NOT NULL 
                            THEN CONCAT(accounts.title, '  \n', crypto_addresses.address_alias)
                            ELSE accounts.title 
                        END as title
                    "),
                    'accounts.client_id',
                    'accounts.id as account_id',
                    'accounts.type',
                    'crypto_addresses.crypto_address',
                    'crypto_addresses.destination_tag',
                    'crypto_addresses.network',
                    'crypto_addresses.provider',
                    'currencies.name',
                    'currencies.logo',
                    'currencies.short',
                )
                ->leftJoin('accounts', 'crypto_addresses.account_id', '=', 'accounts.id')
                ->leftJoin('currencies', 'currencies.id', '=', 'accounts.account_currency');

            $provider = trim((string) $request->input('filter_provider', ''));
            if ($provider !== '' && strtolower($provider) !== 'all') {
                $query->where('crypto_addresses.provider', $provider);
            }

            $network = trim((string) $request->input('filter_network', ''));
            if ($network !== '' && strtolower($network) !== 'all') {
                $query->where('crypto_addresses.network', $network);
            }

            $currency = trim((string) $request->input('filter_currency', ''));
            if ($currency !== '' && strtolower($currency) !== 'all') {
                $query->where('currencies.short', $currency);
            }

            $type = trim((string) $request->input('filter_type', ''));
            if ($type !== '' && strtolower($type) !== 'all') {
                $query->where('accounts.type', $type);
            }

            $dataTable = Datatables::of($query)
                ->filter(function ($q) use ($request) {
                    $search = trim((string) $request->input('search.value'));
                    if ($search === '') {
                        return;
                    }

                    $q->where(function ($inner) use ($search) {
                        $inner->where('crypto_addresses.id', 'like', "%{$search}%")
                            ->orWhere('crypto_addresses.crypto_address', 'like', "%{$search}%")
                            ->orWhere('crypto_addresses.destination_tag', 'like', "%{$search}%")
                            ->orWhere('crypto_addresses.network', 'like', "%{$search}%")
                            ->orWhere('crypto_addresses.provider', 'like', "%{$search}%")
                            ->orWhere('crypto_addresses.address_alias', 'like', "%{$search}%")
                            ->orWhere('accounts.title', 'like', "%{$search}%")
                            ->orWhere('accounts.id', 'like', "%{$search}%")
                            ->orWhere('accounts.client_id', 'like', "%{$search}%")
                            ->orWhere('accounts.type', 'like', "%{$search}%")
                            ->orWhere('currencies.name', 'like', "%{$search}%")
                            ->orWhere('currencies.short', 'like', "%{$search}%");
                    });
                });

            $dataTable->orderColumn('crypto_addresses.id', function ($query, $order) {
                $query->reorder()->orderBy('crypto_addresses.id', $order);
            });
            $dataTable->orderColumn('crypto_addresses.network', function ($query, $order) {
                $query->reorder()->orderBy('crypto_addresses.network', $order);
            });
            $dataTable->orderColumn('crypto_addresses.provider', function ($query, $order) {
                $query->reorder()->orderBy('crypto_addresses.provider', $order);
            });
            $dataTable->orderColumn('accounts.client_id', function ($query, $order) {
                $query->reorder()->orderBy('accounts.client_id', $order);
            });
            $dataTable->orderColumn('accounts.id', function ($query, $order) {
                $query->reorder()->orderBy('accounts.id', $order);
            });
            $dataTable->orderColumn('currencies.name', function ($query, $order) {
                $query->reorder()->orderBy('currencies.name', $order);
            });
            $dataTable->orderColumn('accounts.type', function ($query, $order) {
                $query->reorder()->orderBy('accounts.type', $order);
            });

            return $dataTable->make(true);
        }

        $providerOptions = DB::table('crypto_addresses')
            ->whereNotNull('provider')
            ->where('provider', '!=', '')
            ->distinct()
            ->orderBy('provider')
            ->pluck('provider');

        $networkOptions = DB::table('crypto_addresses')
            ->whereNotNull('network')
            ->where('network', '!=', '')
            ->distinct()
            ->orderBy('network')
            ->pluck('network');

        $currencyOptions = DB::table('crypto_addresses')
            ->leftJoin('accounts', 'crypto_addresses.account_id', '=', 'accounts.id')
            ->leftJoin('currencies', 'currencies.id', '=', 'accounts.account_currency')
            ->whereNotNull('currencies.short')
            ->where('currencies.short', '!=', '')
            ->distinct()
            ->orderBy('currencies.short')
            ->pluck('currencies.short');

        $typeOptions = DB::table('crypto_addresses')
            ->leftJoin('accounts', 'crypto_addresses.account_id', '=', 'accounts.id')
            ->whereNotNull('accounts.type')
            ->where('accounts.type', '!=', '')
            ->distinct()
            ->orderBy('accounts.type')
            ->pluck('accounts.type');

        return view('bitpace.deposit-addresses', compact(
            'providerOptions',
            'networkOptions',
            'currencyOptions',
            'typeOptions'
        ));
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
                //->whereNotNull('users.client_id') // <-- Added condition here
                  ->whereNotNull('crypto_addresses.crypto_address') // <-- Added condition here
                ->where('accounts.id', $account_id)
                ->where('crypto_addresses.network', $network)
                ->first();
            if (!empty($address)) {
                echo json_encode($address);
            }
        }
    }

    public function generateAddressQrCode(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'wallet_address'  => 'required|string',
            'destination_tag' => 'nullable|string',
            'network'         => 'nullable|string', // e.g. XRP, TON, ATOM, XLM
            'logo'            => 'nullable|string',
            'size'            => 'nullable|numeric'
        ]);

        if ($validation->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors'  => $validation->errors()
            ], 422);
        }

        try {
            $size           = (int) $request->get('size', 250);
            $walletAddress  = trim($request->get('wallet_address'));
            $destinationTag = trim((string) $request->get('destination_tag'));
            $network        = strtoupper((string) $request->get('network', ''));

            // 1. Format the payload to include the destination tag / memo
            $qrPayload = $walletAddress;

            if ($destinationTag !== '') {
                // Standard URI format for XRP / Ripple wallets
                if ($network === 'XRP' || str_starts_with($walletAddress, 'r')) {
                    $qrPayload = 'ripple:' . $walletAddress . '?dt=' . urlencode($destinationTag);
                } 
                // Standard URI format for Stellar / XLM memo
                elseif ($network === 'XLM' || str_starts_with($walletAddress, 'G')) {
                    $qrPayload = 'stellar:' . $walletAddress . '?memo=' . urlencode($destinationTag);
                } 
                // Fallback general query param representation
                else {
                    $qrPayload = $walletAddress . '?dt=' . urlencode($destinationTag);
                }
            }

            // 2. Build BaconQrCode with native SvgImageBackEnd
            $renderer = new ImageRenderer(new RendererStyle($size), new SvgImageBackEnd());
            $writer   = new Writer($renderer);

            // 3. Generate clean SVG markup using the formatted payload
            $svgString = $writer->writeString($qrPayload);

            // 4. Return standard data URI
            $base64Svg = 'data:image/svg+xml;base64,' . base64_encode($svgString);

            return response()->json([
                'success'  => true,
                'message'  => 'QR Code generated successfully',
                'qr_code'  => $base64Svg,
                'payload'  => $qrPayload
            ], 200);

        } catch (\Throwable $th) {
            Log::error('BaconQRCode Generation Error: ' . $th->getMessage() . ' | Line: ' . $th->getLine());
            Log::error($th->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate QR code',
                'error'   => $th->getMessage()
            ], 500);
        }
    }

}
