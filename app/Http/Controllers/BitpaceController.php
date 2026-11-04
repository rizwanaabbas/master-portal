<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Redirector;
use App\Http\Requests\StoreBitpaceRequest;
use App\Http\Requests\UpdateBitpaceRequest;
use App\Helpers\Common;
use App\Models\Bitpace;
use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\Currency;
use App\Models\TRRequest;
use App\Models\BitpaceVasp;
use App\Models\BitpaceBeneficiary;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use App\Helpers\BitpaceMerchantApis;
use App\Helpers\EmailNotification;
use App\Helpers\IDORHelper;
use App\Helpers\ClientHelper;
use App\Helpers\OnboardingHelper;
use Illuminate\Support\Facades\Log;
use Session;
use GuzzleHttp\Client as GuzzleHttpClient;

class BitpaceController extends Controller
{
    public function __construct(Redirector $redirect)
    {
        if (!in_array('Bitpace', Common::getEnabledModules())) {
            //$redirect->to('/')->send();
            abort('403');
        }
        $this->middleware('permission:bitpace management', ['only' => ['sell', 'getDepositAddresses', 'newAddress', 'storeAddress', 'getAddressData', 'buy', 'withdrawSave', 'getPrice', 'balance', 'getCurrencyNetworks', 'getAccountCurrency', 'getCryptoCurrencies', 'storeApiResponseData']]);
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

    // public function getDepositAddresses(Request $request)
    // {
    //     if ($request->ajax()) {
    //         $addresses =   DB::table('crypto_addresses')
    //             ->select(
    //                 'crypto_addresses.id',
    //                 \DB::raw("
    //                     CASE 
    //                         WHEN crypto_addresses.address_alias IS NOT NULL 
    //                         THEN CONCAT(accounts.title, '  \n', crypto_addresses.address_alias)
    //                         ELSE accounts.title 
    //                     END as title
    //                 "),
    //                 'accounts.client_id',
    //                 'accounts.id as account_id',
    //                 'accounts.type',
    //                 'crypto_addresses.crypto_address',
    //                 'crypto_addresses.destination_tag',
    //                 'crypto_addresses.network',
    //                 'crypto_addresses.provider',
    //                 //'crypto_currency_network_address.address',
    //                 'currencies.name',
    //                 'currencies.logo',
    //                 'currencies.short',
    //                 //'currencies.network'
    //             )
    //             ->leftJoin('accounts', 'crypto_addresses.account_id', '=', 'accounts.id')
    //             ->leftJoin('currencies', 'currencies.id', '=', 'accounts.account_currency')
    //             //->leftJoin('crypto_currency_network_address', 'currencies.id', '=', 'crypto_currency_network_address.currency_id')
    //             ->orderBy('crypto_addresses.created_at', 'desc')
    //             ->get();

    //         return Datatables::of($addresses)->make(true);
    //     }

    //     $addresses = [];
    //     return view("bitpace.deposit-addresses", compact('addresses'));
    // }

    public function newAddress(Request $request)
    {
        $account_id =  $request->get("ac");
        $network =  $request->get("network");
        $accounts = Account::where("is_active", true)->where('is_crypto', 1)->get();
        return view("bitpace.new-address", compact('accounts', 'account_id', 'network'));
    }

    /**
     * Store the new Bitpace address.
     *
     * @return \Illuminate\Http\Response
     */

    public function storeAddress(Request $request)
    {
        $this->validate($request, [
            'account_id' => 'required',
            'network' => 'required'
        ]);

        $account_id =  $request->get("account_id");
        $crypto_network =  $request->get("network");
        $user_type =  $request->get("type");
        //$network =  $request->get("nework");
        $IDOR = new IDORHelper();
        $is_account_owner = $IDOR->isAccountBelongToUser(Auth::user(), $account_id);
        if (!$is_account_owner) {
            echo json_encode(array());
        }
        if (!empty($account_id) && $crypto_network != '') {

            //get client account data
            $client = DB::table('accounts')
                ->select(
                    'clients.id as client_id',
                    'users.id as user_id',
                    'clients.name',
                    'accounts.account_number',
                    'users.email',
                    'currencies.id as currency_id',
                    'currencies.short as currency',
                    'crypto_addresses.crypto_address',
                    'crypto_addresses.destination_tag',
                    'crypto_addresses.network'
                )
                ->leftJoin('clients', 'accounts.client_id', '=', 'clients.id')
                ->leftJoin('client_users', 'client_users.client_id', '=', 'clients.id')
                ->leftJoin('users', 'client_users.user_id', '=', 'users.id')
                ->leftJoin('currencies', 'currencies.id', '=', 'accounts.account_currency')
                ->leftJoin('crypto_addresses', 'accounts.id', '=', 'crypto_addresses.account_id')
                ->where('accounts.id', '=', $account_id)
                ->first();
            //print_r($client);
            $name = $client->name;
            $account_number = $client->account_number;
            $email = $client->email;
            $currency = $client->currency;
            $currency_id = $client->currency_id;
            //$network = $network;
            $nameArr =  explode(' ', $name, 2);
            $firstname = $name;
            $lastname = '';
            if (count($nameArr) > 0) {
                $firstname = $nameArr[0];
                if (!empty($nameArr[1]))
                    $lastname = $nameArr[1];
                else
                    $lastname = '';
            }

            //prepare payload for API call
            // $data = '{
            //     "customer": {
            //      "reference_id": "' . $account_id . '",
            //       "first_name": "' . $firstname . '",
            //       "last_name": "' . $lastname . '",
            //       "email": "' . $email . '"
            //     },
            //     "deposit_type": "COIN_STORE",
            //     "cryptocurrency": "' . $currency . '",
            //     "network": "' . $crypto_network . '"
            //   }';

            // $payload = json_decode($data, true);
            $payload =  [
                "customer" => [
                    "reference_id" => $account_id,
                    "first_name" => $firstname,
                    "last_name" => $lastname,
                    "email" => $email,
                ],
                "deposit_type" => "COIN_STORE",
                "cryptocurrency" => $currency,
                "network" => $crypto_network,
            ];
            $bitpaceHelper = new BitpaceMerchantApis();
            //dd($payload);
            $data = $bitpaceHelper->createBitpaceAddress($payload);
            //dd($data);

            if (!empty($data->code) && !empty($data->address)) {

                $addressArr = explode('?dt=', $data->address);

                if (isset($addressArr[1])) {
                    $address = $addressArr[0];
                    $destination_tag = $addressArr[1];
                } else {
                    $address = $data->address;
                    $destination_tag = '';
                }
                $currency_code = $data->code;
                //after creating address save in the database
                DB::table('crypto_addresses')
                    ->updateOrInsert(
                        ['account_id' => $account_id, 'destination_tag' => $destination_tag, 'crypto_address' => $address, 'network' => $crypto_network],
                        [
                            'account_id' => $account_id,
                            'crypto_address' => $address,
                            'destination_tag' => $destination_tag,
                            "network" => $crypto_network,
                            'created_at' => DB::raw('CURRENT_TIMESTAMP')
                        ]
                    );

                // DB::table('crypto_currency_network_address')
                // ->updateOrInsert(
                //     ['currency_id' => $currency_id, 'crypto_address' => $address, "network"=> $crypto_network],
                //     ['currency_id' => $currency_id, 'crypto_address' => $address,
                //     "network"=> $crypto_network ,'created_at'=>DB::raw('CURRENT_TIMESTAMP')]
                // );

                //update the currency table and add crypto bitpace address
                //if(!empty( $currency_id ))
                //    DB::table('currencies')->where('id', $currency_id)->update(array('bitpace_address'=>$address));

                $message =  "Deposit addess " . $data->address . " has been added.";
                $messgae_type = "success";
            } else {
                $message = "Deposit address was not created, please try again. " . $data->message;
                $messgae_type = "error";
            }
        }
        if ($user_type == 'client') {

            return redirect("/client/crypto-transaction-requests/deposit/new?ac=" . $account_id . "&network=" . $crypto_network)->with($messgae_type, $message);
        } else {
            return redirect("/crypto-management/crypto-transactions/bitpace/new-address?ac=" . $account_id . "&network=" . $crypto_network)->with($messgae_type, $message);
        }
    }

    // public function getAddressData(Request $request)
    // {
    //     $this->validate($request, [
    //         'account_id' => 'required',
    //         'network' => 'required'
    //     ]);
    //     $account_id = $request->get("account_id");
    //     $network = $request->get("network");
    //     $IDOR = new IDORHelper();
    //     $is_account_owner = $IDOR->isAccountBelongToUser(Auth::user(), $account_id);
    //     if (!$is_account_owner) {
    //         echo json_encode(array());
    //     }

    //     if ($account_id != '') {
    //         $address =   DB::table('accounts')
    //             ->select(

    //                 'accounts.title',
    //                 //'crypto_currency_network_address.crypto_address as bitpace_address',
    //                 'currencies.name',
    //                 'currencies.short',
    //                 'currencies.logo',

    //                 'users.email',
    //                 'crypto_addresses.crypto_address',
    //                 'crypto_addresses.destination_tag',
    //                 'crypto_addresses.network',
    //                 'crypto_addresses.id as crypto_address_id',
    //                 'crypto_addresses.created_at',

    //             )
    //             ->leftJoin('crypto_addresses', 'crypto_addresses.account_id', '=', 'accounts.id')
    //             ->leftJoin('currencies', 'currencies.id', '=', 'accounts.account_currency')
    //             //->leftJoin('crypto_currency_network_address', 'currencies.id', '=', 'crypto_currency_network_address.currency_id')
    //             ->leftJoin('clients', 'accounts.client_id', '=', 'clients.id')
    //             ->leftJoin('client_users', 'client_users.client_id', '=', 'clients.id')
    //             ->leftJoin('users', 'client_users.user_id', '=', 'users.id')
    //             ->whereNotNull('users.client_id') // <-- Added condition here

    //             ->where('accounts.id', $account_id)
    //             ->where('crypto_addresses.network', $network)
    //             ->first();
    //         if (!empty($address)) {
    //             echo json_encode($address);
    //         }
    //     }
    // }

    // public function getAddressDataQRCode(Request $request)
    // {
    //     $this->validate($request, [
    //         'wallet_address' => 'required'
    //     ]);
    //     $wallet_address = $request->get("wallet_address");
    //     $logo = $request->get('logo');

    //     if ($wallet_address != '') {
    //         $clientHttp = new GuzzleHttpClient();

    //         $token = Session::get('loginToken');
    //         $onboardingHelper = new OnboardingHelper();
    //         $baseUrl = $onboardingHelper->getBaseUrlOnboarding();
    //         $apiKey = $onboardingHelper->getApiKey();

    //         try {

    //             $response = $clientHttp->post($baseUrl . 'client/crypto/create-qr-code', [
    //                 'headers'   => [
    //                     'api-key'        => $apiKey,
    //                     'accept'         => 'application/json',
    //                     'Authorization' => 'Bearer ' . $token,
    //                 ],
    //                 'multipart' => [
    //                     [
    //                         'name'     => 'wallet_address',
    //                         'contents' => $wallet_address
    //                     ],
    //                     [
    //                         'name'     => 'logo',
    //                         'contents' => $logo
    //                     ]
    //                 ],
    //             ]);
    //         } catch (\Exception $e) {
    //             Log::info('error from create qr code', ['url' => $e->getMessage(),]);
    //         }

    //         echo $response->getBody();
    //     }
    // }

    public function buy()
    {

        $cryptoCurrencies = Currency::where("is_active", true)
            ->where("is_crypto", true)
            ->select("id")
            ->get();

        $clientIds = Account::where("is_active", true)
            ->whereIn("account_currency", $cryptoCurrencies)
            ->select("client_id")
            ->get();

        $currencies = Currency::where("is_active", true)
            ->whereIn("short", array("EUR", "USD"))
            ->select("id")
            ->get();

        $accounts = Account::where("is_active", true)
            ->whereIn("account_currency", $currencies)
            ->select("id", "title", "account_number")
            ->get();

        $currencies = $this->getCryptoCurrencies();
        //$accounts = Account::where("is_active", true)->where('is_crypto',1)->get();
        return view("bitpace.buy", compact('currencies', 'accounts'));
    }
    public function buySave(Request $request)
    {
        $this->validate($request, [
            'crypto_account_id' => 'required',
            'fiat_account_id' => 'required',
            //'fiat_amount' => 'required',
            //'coin' => 'required',
            //'coin_amount' => 'required',
            'token' => 'required',
        ]);

        $crypto_account_id =  $request->get("crypto_account_id");
        $fiat_account_id =  $request->get("fiat_account_id");
        $token =  $request->get("token");
        $order_type =  $request->get("order_type");
        $remarks =  $request->get("remarks");
        $cryptio_price_quote = Session::get('cryptio_price_quote');

        if (empty($cryptio_price_quote)) {
            echo json_encode(array('status' => 'DECLINED', 'type' => 'internal', 'message' => 'No price quote available.'));
            die();
        }

        $cryptio_price_quote = json_decode($cryptio_price_quote);
        if ($order_type == 'BUY') {
            $debit_account = Account::find($fiat_account_id);
            $client = ClientHelper::getClientByAccountId($fiat_account_id);
            $credit_account = Account::find($crypto_account_id);
            $currency = Currency::where('id', $debit_account->account_currency)->first();
            // check if debit account is having enough balance
            $balance = Common::getAccountBalanceById($debit_account->id);
            $buy_sell_amount = $cryptio_price_quote->total_amount;
        }
        if ($order_type == 'SELL') {
            $debit_account = Account::find($crypto_account_id);
            $client = ClientHelper::getClientByAccountId($crypto_account_id);
            $credit_account = Account::find($fiat_account_id);
            $currency = Currency::where('id', $debit_account->account_currency)->first();
            $balance = Common::getAccountBalanceById($debit_account->id);
            $buy_sell_amount = $cryptio_price_quote->cryptocurrency_amount;
        }

        // if account is not available alert it
        if (!$credit_account || !$debit_account) {
            // return redirect(url()->previous())->with('error', "Account details not found.");
            echo json_encode(array('status' => 'DECLINED', 'type' => 'internal', 'message' => 'Account details not found.'));
            die();
        }

        // check if same account is selected for transaction
        if ($credit_account->id == $debit_account->id) {
            // return redirect(url()->previous())->with('error', "Please choose different accounts");
            echo json_encode(array('status' => 'DECLINED', 'type' => 'internal', 'message' => 'Please choose different accounts.'));
            die();
        }
        if (!$currency) {
            // return redirect(url()->previous())->with('error', "Currency not found");
            echo json_encode(array('status' => 'DECLINED', 'type' => 'internal', 'message' => 'Currency not found".'));
            die();
        }

        if (!empty($debit_account->id) && !empty($cryptio_price_quote)) {
            // check if debit account is having enough balance

            //Log::info("\n\n  Check Balance: Amount " .$buy_sell_amount . ' => Balance ' .$balance );

            if ($buy_sell_amount > $balance) {
                //return redirect(url()->previous())->with('error', "You don't have enough money in your account.");
                echo json_encode(array('status' => 'DECLINED', 'type' => 'internal', 'message' => "You don't have enough money in your account."));
                die();
            }
            //get client account data
            $client = DB::table('accounts')
                ->select(
                    'clients.id as client_id',
                    'users.id as user_id',
                    'clients.name',
                    'accounts.account_number',
                    'users.email',
                    'currencies.id as currency_id',
                    'currencies.short as currency',
                    'currencies.network'
                )
                ->leftJoin('clients', 'accounts.client_id', '=', 'clients.id')
                ->leftJoin('client_users', 'client_users.client_id', '=', 'clients.id')
                ->leftJoin('users', 'client_users.user_id', '=', 'users.id')
                ->leftJoin('currencies', 'currencies.id', '=', 'accounts.account_currency')
                ->where('accounts.id', '=', $crypto_account_id)
                ->first();
            $name = $client->name;
            $account_number = $client->account_number;

            $email = $client->email;
            $currency_id = $client->currency_id;
            $nameArr =  explode(' ', $name, 2);
            $firstname = $name;
            $lastname = '';
            if (count($nameArr) > 0) {
                $firstname = $nameArr[0];
                if (isset($nameArr[1]))
                    $lastname = $nameArr[1];
            }

            //prepare payload for API call
            $data = '{
                "customer": {
                "reference_id": "' . $crypto_account_id . '",
                "first_name": "' . $firstname . '",
                "last_name": "' . $lastname . '",
                "email": "' . $email . '"
                },
                "token": "' . $token . '",
                "description": "' . $order_type . ' Order created from FAIB Portal"
            }';

            $payload = json_decode($data, true);
            $bitpaceHelper = new BitpaceMerchantApis();
            $data = $bitpaceHelper->createBitpaceOrder($payload);


            if (!empty($data->order_id) || !empty($data->status)) {

                if (!empty($data->order_id)) {
                    //$status = 'Approved';
                    $order_id = $data->order_id;
                    // if(!$this->orderExists($order_id)) {
                    //CREDIT Crypto Account
                    if ($crypto_account_id && $currency_id) {
                        if ($order_type == 'BUY') {
                            $type = 'Credit';
                            $nature = 'Buy';
                            $tr_time = date("Y-m-d H:i:s", time());
                        }
                        if ($order_type == 'SELL') {
                            $type = 'Debit';
                            $nature = 'Sell';
                            $tr_time = date("Y-m-d H:i:s", time() + 10);
                        }
                        $order = $bitpaceHelper->getOrderV2($order_id);
                        $order_data =  json_encode($order);
                        $this->storeApiResponseData($data->order_id, $order_data, 'Bitpace');
                        $status = $order->status;

                        if ($status == 'COMPLETED')
                            $status = 'Approved';
                        else
                            $status = 'Pending';

                        $cr_trn = new AccountTransaction();
                        $cr_trn->account_id = $crypto_account_id;
                        $cr_trn->crypto_transaction_id = $order_id;
                        //$dt_trn->beneficiary_id = $request->get("beneficiary_id");
                        $cr_trn->currency_id = $currency_id;
                        $cr_trn->transaction_type = $type;
                        $cr_trn->transaction_nature = $nature;
                        $cr_trn->transaction_date = $tr_time;
                        $cr_trn->amount = $cryptio_price_quote->cryptocurrency_amount;
                        $cr_trn->is_crypto = 1;
                        $cr_trn->remarks = $remarks;
                        $cr_trn->status = $status;
                        $cr_trn->crypto_system = 'Bitpace';
                        $cr_trn->created_by = Auth::user()->id;
                        $cr_trn->approved_by = Auth::user()->id;
                        if ($cr_trn->save()) {
                            $response = true;
                            // DEBIT the Fiat Account
                            $total_amount = $cryptio_price_quote->total_amount;
                            if ($order_type == 'BUY') {
                                $type = 'Debit';
                                $nature = 'Buy';
                                if (!empty($cryptio_price_quote->merchant_commission_fee))
                                    $total_amount = $cryptio_price_quote->total_amount + $cryptio_price_quote->merchant_commission_fee;
                            }
                            if ($order_type == 'SELL') {
                                $type = 'Credit';
                                $nature = 'Sell';
                            }
                            $account = DB::table('accounts')
                                ->select(
                                    'clients.id as client_id',
                                    'users.id as user_id',
                                    'clients.name',
                                    'accounts.account_number',
                                    'users.email',
                                    'currencies.id as currency_id',
                                    'currencies.short as currency',
                                    'currencies.network'
                                )
                                ->leftJoin('clients', 'accounts.client_id', '=', 'clients.id')
                                ->leftJoin('client_users', 'client_users.client_id', '=', 'clients.id')
                                ->leftJoin('users', 'client_users.user_id', '=', 'users.id')
                                ->leftJoin('currencies', 'currencies.id', '=', 'accounts.account_currency')
                                ->where('accounts.id', '=', $fiat_account_id)
                                ->first();
                            // "total_amount": 13.00,
                            // "currency": "EUR",
                            // "cryptocurrency_amount": 26.360000,
                            // "cryptocurrency": "XRP",
                            // "merchant_commission_fee": 0.13,
                            $currency_id = $account->currency_id;
                            $dt_trn = new AccountTransaction();
                            $dt_trn->account_id = $fiat_account_id;
                            $dt_trn->link_tr_id = $cr_trn->id;

                            $dt_trn->crypto_transaction_id = $order_id;
                            //$dt_trn->beneficiary_id = $request->get("beneficiary_id");
                            $dt_trn->currency_id = $currency_id;
                            $dt_trn->transaction_type = $type;
                            $dt_trn->transaction_nature = $nature;
                            $dt_trn->transaction_date = $tr_time;
                            $dt_trn->amount = $total_amount;
                            $dt_trn->is_crypto = 1;
                            $dt_trn->remarks = $remarks;
                            $dt_trn->status = $status;
                            $dt_trn->crypto_system = 'Bitpace';
                            $dt_trn->created_by = Auth::user()->id;
                            $dt_trn->approved_by = Auth::user()->id;
                            $dt_trn->save();
                            $cr_trn->link_tr_id = $dt_trn->id;
                            if ($order_type == 'SELL') {
                                $account_id =  $crypto_account_id;
                                $account_number =  $account->account_number;
                            } else {
                                $account_id =  $fiat_account_id;
                            }
                            //create a request for client view
                            $dt_trn_req = new TRRequest();
                            $dt_trn_req->account_id = $account_id;
                            $dt_trn_req->receiver_account_number = $account_number;
                            //$dt_trn_req->crypto_transaction_id = $order_id;
                            $dt_trn_req->currency_id = $currency_id;
                            $dt_trn_req->transaction_type = $type;
                            $dt_trn_req->transaction_nature = $nature;
                            $dt_trn_req->transaction_date = date("Y-m-d H:i:s", time());
                            $dt_trn_req->amount = $cryptio_price_quote->total_amount;
                            $dt_trn_req->crypto_amount = $cryptio_price_quote->cryptocurrency_amount;
                            //$dt_trn_req->is_crypto = 1;
                            $dt_trn_req->remarks = $remarks;
                            $dt_trn_req->status = $status;
                            $dt_trn_req->crypto_system = 'Bitpace';
                            $dt_trn_req->created_by = Auth::user()->id;
                            $dt_trn_req->approved_by = Auth::user()->id;
                            $dt_trn_req->save();
                            $tr_id = $dt_trn_req->id;
                            if (!empty($tr_id)) {
                                $dt_trn->tr_request_id = $tr_id;
                                $dt_trn->save();
                                $cr_trn->tr_request_id = $tr_id;
                                $cr_trn->save();
                                if ($status != 'Pending')
                                    EmailNotification::notifyClientForTransactionApproval($dt_trn_req);
                            }
                            // print_r($dt_trn_req);
                        } else {
                            $response = false;
                        }
                        echo  $order_data;
                    }
                    // }
                } else {
                    if (!empty($data->code)) {
                        if ($data->code == '170') {
                            if ($order_type == 'BUY') {
                                $type = 'Debit';
                                $nature = 'Buy';
                            }
                            if ($order_type == 'SELL') {
                                $type = 'Credit';
                                $nature = 'Sell';
                            }
                            //create a request for client view
                            $dt_trn_req = new TRRequest();
                            $dt_trn_req->account_id = $crypto_account_id;
                            $dt_trn_req->receiver_account_number = $account_number;
                            //$dt_trn_req->crypto_transaction_id = $order_id;
                            $dt_trn_req->currency_id = $currency_id;
                            $dt_trn_req->transaction_type = $type;
                            $dt_trn_req->transaction_nature = $nature;
                            $dt_trn_req->transaction_date = date("Y-m-d H:i:s", time());
                            $dt_trn_req->amount = $cryptio_price_quote->total_amount;
                            $dt_trn_req->crypto_amount = $cryptio_price_quote->cryptocurrency_amount;
                            //$dt_trn_req->is_crypto = 1;
                            $dt_trn_req->remarks = $remarks;
                            $dt_trn_req->status = 'Failed';
                            $dt_trn_req->crypto_system = 'Bitpace';
                            $dt_trn_req->created_by = Auth::user()->id;
                            $dt_trn_req->approved_by = Auth::user()->id;
                            $dt_trn_req->save();
                            EmailNotification::notifyFinanceManagersForBitpaceLowBalance($dt_trn_req);
                            //echo json_encode(array('status'=>'DECLINED','type'=>'internal','message'=>'Crypto transaction request could not be completed at this time. Please try again later.')); die();
                        }
                    }
                    echo json_encode($data);
                    session()->put('cryptio_price_quote', '');
                }
            }
        }

        // return redirect("/crypto-management/crypto-transactions/bitpace/new-address?ac=" . $account_id)->with($messgae_type,$message);
    }
    public function storeApiResponseData($transaction_id, $data, $crypto_system)
    {

        DB::table('crypto_transactions_log')->insert([
            'transaction_id' => $transaction_id,
            'details' => $data,
            'crypto_system' => $crypto_system,
            'source' => 'admin',
            'date_created' => date('Y-m-d H:i:s', time())
        ]);
    }

    public function sell()
    {
        $currencies = Currency::where("is_active", true)
            ->where("is_crypto", true)
            ->select("id")
            ->get();

        $accounts = Account::where("is_active", true)
            ->whereIn("account_currency", $currencies)
            ->select("id", "title", "account_number")
            ->get();

        //return view("crypto-management.crypto-transactions.new.sell", compact('accounts'));
        return view("bitpace.sell", compact('accounts'));
    }
    public function withdraw()
    {
        $currencies = Currency::where("is_active", true)
            ->where("is_crypto", true)
            ->where("short", 'USDT')
            ->select("id")
            ->get();
        $vasp_list = BitpaceVasp::get();
        $cryptoAccounts = Account::where("is_active", true)
            ->whereIn("account_currency", $currencies)
            ->select("id", "title", "account_number")
            ->get();

        return view("bitpace.withdraw", compact('cryptoAccounts', 'vasp_list'));
        //return view("bitpace.withdraw",compact('currencies','accounts'));
        // return view("bitpace.withdraw");

    }
    public function withdrawSave(Request $request)
    {
        /*
            https://api-sandbox.bitpace.com/api/v1/orders/coin-withdraw
            {"customer":{"reference_id":"11111","first_name":"Rizwan","last_name":"Abbas","email":"rizwanaabbas@gmail.com"},"withdraw":{"address":"xxx33333122xx"},"cryptocurrency":"XRP","cryptocurrency_amount":20,"description":"Withdraw amount"}
        */
        $this->validate($request, [
            'account_id' => 'required',
            'to_wallet_address' => 'required',
            'amount' => 'required',
            'network' => 'required',
            // 'beneficiary_type' => 'required',
            // 'wallet_type' => 'required',
            //'beneficiary_vasp_did' => 'required',
            'remarks' => 'required'
        ]);
        
        $bitpaceHelper = new BitpaceMerchantApis();

       
        

        $debit_account = Account::find($request->get("account_id"));

        // if account is not available alert it
        if (!$debit_account) {
            return redirect(url()->previous())->with('error', "Account details not found.")->withInput();
        }

        // validate time
        $time = Common::validateTime(date('H:i:s', time()));
        if (!$time) {
            return redirect("/finance-management/transactions/new/tt")->with('error', "Please provide a valid time in 24 hours format.")->withInput();
        }

        // // check if debit account is having enough balance
        $balance = Common::getAccountBalanceById($debit_account->id);

        if ($request->get("amount") > $balance) {
            return redirect(url()->previous())->with('error', "You don't have enough money in your account")->withInput();
        }
        
        $bitpace_config =  $bitpaceHelper->getConfigs();
        //dd($bitpace_config);
        if (isset($bitpace_config['comission_percentage_to_hold']['value'])) {
            $percentage = $bitpace_config['comission_percentage_to_hold']['value'];
            if ($percentage > 0) {
                //$percentage = 5;
                $withdraw_amount = ($balance * $percentage) / 100;
                $withdraw_amount = $balance - $withdraw_amount;
                if ($request->get("amount") > $withdraw_amount) {
                    return redirect(url()->previous())->with('error', "Insufficient funds to cover for fee and charges, Maximum withdrawl amount available is $withdraw_amount")->withInput();
                }
            }
        }
        $existing_request = TRRequest::where('status', 'pending')->where('account_id', $debit_account->id)->where('transaction_nature', 'Withdraw')->first();

        if (!empty($existing_request)) {
            return redirect(url()->previous())->with('error', "There is already withdraw request pending, please wait for approval and make new request again.")->withInput();
        }
         $travel_rule_enabled = $bitpaceHelper->isTravelRuleEnabled();
        if($travel_rule_enabled) {
           
            if(empty($request->get('beneficiary_type') )) {
                return redirect(url()->previous())->with('error', "Please select beneficiary type.")->withInput();
            }

            if(empty($request->get('wallet_type') )) {
                return redirect(url()->previous())->with('error', "Please select Wallet type.")->withInput();
            }
           
            //IF beneficiary_type = INDIVIDUAL then Beneficiary First Name  and Beneficiary Last Name  are required
            if ($request->get("beneficiary_type") == 'INDIVIDUAL') {
                if (empty($request->get("beneficiary_first_name")) || empty($request->get("beneficiary_last_name"))) {
                    return redirect(url()->previous())->with('error', "Please provide beneficiary first name and last name for individual beneficiary type.")->withInput();
                }
            }

            if ($request->get("beneficiary_type") == 'BUSINESS') {
            //if is_sending_to_myself is true then beneficiary legal name is not required
                if ($request->get("is_sending_to_myself") == false) {    
                    if (empty($request->get("beneficiary_legal_name")) ) {
                        return redirect(url()->previous())->with('error', "Please provide beneficiary legal name for business beneficiary type.")->withInput();
                    }
                }
            }
            
           
            if($request->get('wallet_type') == 'CUSTODIAL') {
                 $beneficiary_vasp_did = $request->get("beneficiary_vasp_did");
            //if is_not_on_vasp_list is true then beneficiary_vasp_name is required 
                if ($request->get("is_not_on_vasp_list") == true) {
                // echo "I am here";
                    if (empty($request->get("beneficiary_vasp_name"))) {
                        return redirect(url()->previous())->with('error', "Please provide beneficiary VASP name for non VASP beneficiary.")->withInput();
                    } else {
                    $beneficiary_vasp_did = $request->get("beneficiary_vasp_name");
                    }
                } 
                if(empty($request->get('beneficiary_vasp_did')) && empty($request->get('beneficiary_vasp_name') )) {
                    return redirect(url()->previous())->with('error', "Please provide beneficiary VASP DID or name.")->withInput();
                }
                if (empty($beneficiary_vasp_did) ) {
                    return redirect(url()->previous())->with('error', "Please provide beneficiary VASP DID for VASP beneficiary.")->withInput();
                }
            }
            
        }
        //dd($existing_request);

        // create client transaction
        $dt_trn = new TRRequest();

        // create debit transaction
        $dt_trn->account_id = $debit_account->id;
        $dt_trn->currency_id = $debit_account->account_currency;
        $dt_trn->transaction_type = "Debit";
        $dt_trn->transaction_nature = "Withdraw";
        $dt_trn->crypto_network =  $request->get("network");
        //$dt_trn->transaction_date = Carbon::createFromFormat('d/m/Y', date('Y-m-d',time()))->format('Y-m-d') . ' ' . $time;
        $dt_trn->transaction_date = date("Y-m-d H:i:s", time());
        $dt_trn->crypto_amount = $request->get("amount");
        $dt_trn->to_wallet_address = $request->get("to_wallet_address");
        $dt_trn->destination_tag = $request->get("destination_tag");
        $dt_trn->remarks = $request->get("remarks");
        $dt_trn->status = "Pending";
        $dt_trn->crypto_system = "Bitpace";
        $dt_trn->otp_verified = true;
        $dt_trn->created_by = Auth::user()->id;
        $dt_trn->save();
        $client = ClientHelper::getClientByAccountId($request->get("account_id"));
        //save benficiary data
        if($travel_rule_enabled) {
            $this->saveBeneficiary($request, $dt_trn, $client);
        }
        Common::uploadSupportingDocuments($request, $dt_trn, $client);
        EmailNotification::notifyFinanceManagersForTRRequest($dt_trn, 'Withdraw');
        return redirect("/crypto-management/crypto-pending-transactions")->with('success', "Crypto Withdraw transaction request created successfully.");
    }

    public function saveBeneficiary($request, $dt_trn, $client) {
        $beneficiary = new BitpaceBeneficiary();

        $beneficiary->tr_request_id = $dt_trn->id;
        $beneficiary->client_id = $client->id ?? null;

        $beneficiary->beneficiary_type = $request->get("beneficiary_type");

        // INDIVIDUAL
        $beneficiary->beneficiary_name = $request->get("beneficiary_first_name");
        $beneficiary->beneficiary_last_name = $request->get("beneficiary_last_name");
        $beneficiary->dob = $request->get("dob");

        // BUSINESS
        $beneficiary->beneficiary_legal_name = $request->get("beneficiary_legal_name");
        $beneficiary->registration_number = $request->get("registration_number");
        $beneficiary->lei_code = $request->get("lei_code");

        // COMMON
        $beneficiary->country_code = $request->get("country_code");

        // WALLET TYPE
        $beneficiary->wallet_type = $request->get("wallet_type");
        
        $beneficiary_vasp_did = $request->get("beneficiary_vasp_did");
        //if is_not_on_vasp_list is true then beneficiary_vasp_name is required 
        if ($request->get("is_not_on_vasp_list") == 'true') {
            if (!empty($request->get("beneficiary_vasp_name"))) {
               $beneficiary_vasp_did = $request->get("beneficiary_vasp_name");
            } 
        }
        // CUSTODIAL
        $beneficiary->beneficiary_vasp_did = $beneficiary_vasp_did;
        $beneficiary->beneficiary_vasp_name = $request->get("beneficiary_vasp_name");
        $beneficiary->is_not_on_vasp_list = $request->get("is_not_on_vasp_list") == true;

        // SELF HOSTED
        $beneficiary->is_ownership_declaration = $request->get("is_ownership_declaration") == true;

        // SENDING TO MYSELF
        $beneficiary->is_sending_to_myself = $request->get("is_sending_to_myself") == true;

        $beneficiary->save();
    }
    public function getCryptoCurrencies()
    {
        return Currency::where("is_active", true)->where('is_crypto', 1)->get();
    }
    public function getAccountCurrency($account_id)
    {
        $account = DB::table('accounts')
            ->select('accounts.account_number', 'currencies.id as currency_id', 'currencies.short as currency_code', 'currencies.name as currency_name', 'currencies.is_crypto')
            ->leftJoin('currencies', 'currencies.id', '=', 'accounts.account_currency')
            ->where('accounts.id', '=', $account_id)
            ->first();
        return $account;
    }

    public function getPrice(Request $request)
    {

        $order_type = $request->get("order_type");
        $fiat_account_id = $request->get("fiat_account_id");
        $crypto_account_id = $request->get("crypto_account_id");
        $coin_amount = floatval($request->get("coin_amount"));
        $fiat_amount = floatval($request->get("fiat_amount"));
        $currency_amount_field = $request->get("currency_amount_field");
        $coin = $request->get("coin");
        $fiat_currency = $this->getAccountCurrency($fiat_account_id);
        $crypto_currency = $this->getAccountCurrency($crypto_account_id);

        if ($coin_amount <= 0 && $fiat_amount <= 0) {
            echo json_encode(array('status' => "DECLINED", "message" => "Please fill Either crypto of Fiat amount to trade."));
            die();
        }
        if ($coin_amount <= 0 && $currency_amount_field == 'amount') {
            $currency_amount_field = 'fiat_amount';
        }

        // $network = 'XRP';//$request->get("network");
        // //"network": "'.$network.'",
        //"currency_amount": '.$fiat_amount.',
        $payload = '';

        if ($order_type == 'BUY') {
            $payload = ' {
                "order_type": "' . $order_type . '",
                "cryptocurrency": "' . $crypto_currency->currency_code . '",';

            if ($currency_amount_field == 'fiat_amount'  && $fiat_amount > 0) {
                $payload .=    ' "currency_amount": ' . $fiat_amount . ',';
            } else {
                $payload .=    ' "cryptocurrency_amount": ' . $coin_amount . ',';
            }

            $payload .=  ' "currency": "' . $fiat_currency->currency_code . '",
                "show_fees": true,
                "exclude_withdraw_fee": false
            }';
        }

        if ($order_type == 'SELL') {
            //  $payload = ' {
            //     "order_type": "'.$order_type.'",
            //     "cryptocurrency": "'.$coin.'",
            //     "cryptocurrency_amount": '.$coin_amount.',
            //     "currency": "'.$fiat_currency->currency_code.'",
            //     "show_fees": true,
            //     "exclude_withdraw_fee": false
            // }';
            $payload = ' {
                "order_type": "' . $order_type . '",
                "cryptocurrency": "' . $crypto_currency->currency_code . '",';
            if ($currency_amount_field == 'fiat_amount' && $fiat_amount > 0) {
                $payload .=    ' "currency_amount": ' . $fiat_amount . ',';
            } else {
                $payload .=    ' "cryptocurrency_amount": ' . $coin_amount . ',';
            }

            $payload .=  ' "currency": "' . $fiat_currency->currency_code . '",
                "show_fees": true,
                "exclude_withdraw_fee": false
            }';
        }
        //echo $payload; die();
        $payload = json_decode($payload, true);
        $bitpaceHelper = new BitpaceMerchantApis();
        $data =  json_encode($bitpaceHelper->getOrderPrice($payload));
        session()->put('cryptio_price_quote', $data);
        echo $data;
    }

    public function getCurrencyNetworks(Request $request)
    {
        $currency_code = $request->get("currency_code");
        $account_id = $request->get("account_id");
        if ($account_id != '') {
            $currency = $this->getAccountCurrency($account_id);
            if ($currency->is_crypto)
                $currency_code = $currency->currency_code;
        }
        if ($currency_code != '') {
            $bitpaceHelper = new BitpaceMerchantApis();
            echo json_encode($bitpaceHelper->getCurrencyNetworks($currency_code));
            die();
        }
        echo json_encode(array());
    }

    public function balance(Request $request)
    {
        $bitpaceHelper = new BitpaceMerchantApis();
        $balances = $bitpaceHelper->getAccountBalance();
        $currency_balances = array();
        if (!empty($balances)) {

            $i = 0;
            foreach ($balances as $balance) {
                //print_r($balance); die();
                if (!is_object($balance)) {
                    if ($balance == 00) {
                        echo json_encode(array('status' => "DECLINED", "message" => "Could not find any available balances for merchant account."));
                        die();
                    }
                }
                $currency =  DB::table('currencies')->where('short', $balance->currency)->first();
                if (!empty($currency)) {
                    $currency_balances[$i]['logo'] = $currency->logo;
                    $currency_balances[$i]['name'] =  $currency->name;
                    $currency_balances[$i]['balance'] =  $balance->balance;
                    $currency_balances[$i]['short'] =  $currency->short;
                    $i++;
                }
            }
        }
        echo json_encode($currency_balances);
    }
}
