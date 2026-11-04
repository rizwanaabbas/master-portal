<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\Common;
use App\Helpers\EmailNotification;
use App\Models\Account;
use App\Helpers\ClientHelper;
use App\Helpers\IDORHelper;
use App\Helpers\FileFirewall;
use App\Helpers\S3Helper;
use App\Models\Client;
use App\Models\TRRequest;
use App\Models\TRRequestDoc;
use App\Models\BitpaceVasp;
use App\Models\BitpaceBeneficiary;
use App\Models\Currency;
use App\Rules\SupportingDocumentsRule;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Helpers\BitpaceMerchantApis;
use App\Models\AccountTransaction;
use Session;


class CryptoTRRequestController extends Controller
{
    private $enabledModules;

    public function __construct()
    {
        $this->middleware('permission:add crypto transaction', ['only' => ['newAdminBuy', 'newAdminSell', 'newAdminWithdraw', 'adminBuy', 'adminSell', 'adminWithdraw']]);


        $this->enabledModules = Common::getEnabledModules();
    }

    public function index($nid = null)
    {
        if ($nid) {
            Common::readNotification($nid);
        }

        $client_ids = Common::getClientIdsByUserId(Auth::user()->id);
        $accounts = Account::whereIn("client_id", $client_ids)->pluck("id")->toArray();

        $ged_accounts = $this->getAccountsByClientCurrency($client_ids, 'GED');
        $module_enabled = Common::isModuleEnabled('GED');
        $have_ged_account = 'no';
        if ($ged_accounts && $module_enabled) {
            $have_ged_account = 'yes';
        }
        $transactions = TRRequest::whereIn("account_id", $accounts)
            //->where("otp_verified", true)
            ->where("crypto_amount", ">", 0)
            ->where("status", "<>", 'Failed')
            ->orderBy("created_at", "DESC")
            ->get();


        return view("client-portal.crypto-transaction-request.list", compact('transactions', 'have_ged_account'));
    }

    public function getAccountsByClientCurrency($client_ids, $currency_code)
    {

        $accounts = Account::join('currencies', 'accounts.account_currency', '=', 'currencies.id')
            ->where('currencies.short', $currency_code)
            ->whereIn('accounts.client_id', $client_ids)
            ->where('accounts.is_active', true)
            ->select('accounts.id', 'accounts.title', 'accounts.account_number')
            ->get();
        return $accounts;
    }

    public function newBuy()
    {

        $client_ids = Common::getClientIdsByUserId(Auth::user()->id);
        $cryptoCurrencies = Currency::where("is_active", true)->where("is_crypto", true)->select("id")->get();
        $cryptoAccounts = Account::whereIn("client_id", $client_ids)
            ->whereIn("account_currency", $cryptoCurrencies)
            ->where("is_active", true)
            ->select("id", "title", "account_number", "account_currency")
            ->get();

        $currencies = Currency::where("is_active", true)
            ->where("is_crypto", false);
        if (in_array('Bitpace', $this->enabledModules)) {
            //$currencies->where("short", 'EUR');
            $currencies->whereIn("short", array("EUR", "USD"));
        }
        $currencies->select("id")
            ->get();
        $accounts = Account::whereIn("client_id", $client_ids)
            ->whereIn("account_currency", $currencies)
            ->where("is_active", true)
            ->select("id", "title", "account_number")
            ->get();
        if (in_array('Bitpace', $this->enabledModules)) {
            return view("client-portal.crypto-transaction-request.new.bitpace.buy", compact('accounts', 'cryptoAccounts'));
        }
        if (!\App\Services\CryptoConfigService::isManualCryptoEnabled()) {
            return redirect('/client/crypto-transaction-requests')->with('error', 'Manual crypto is disabled in module configuration.');
        }

        return view("client-portal.crypto-transaction-request.new.buy", compact('accounts', 'cryptoAccounts'));
    }

    public function newSell()
    {
        $client_ids = Common::getClientIdsByUserId(Auth::user()->id);

        $fiatCurrencies = Currency::where("is_active", true)
            ->where("is_crypto", false);
        if (in_array('Bitpace', $this->enabledModules)) {
            //$fiatCurrencies->where("short", 'EUR');
            $fiatCurrencies->whereIn("short", array("EUR", "USD"));
        }
        $fiatCurrencies->select("id")
            ->get();
        $fiatAccounts = Account::whereIn("client_id", $client_ids)
            ->whereIn("account_currency", $fiatCurrencies)
            ->where("is_active", true)
            ->select("id", "title", "account_number", "account_currency")
            ->get();
        $currencies = Currency::where("is_active", true)->where("is_crypto", true)->select("id")->get();
        $accounts = Account::whereIn("client_id", $client_ids)
            ->where("is_active", true)
            ->whereIn("account_currency", $currencies)
            ->select("id", "title", "account_number")
            ->get();

        if (in_array('Bitpace', $this->enabledModules)) {
            return view("client-portal.crypto-transaction-request.new.bitpace.sell", compact('accounts', 'fiatAccounts'));
        }
        if (!\App\Services\CryptoConfigService::isManualCryptoEnabled()) {
            return redirect('/client/crypto-transaction-requests')->with('error', 'Manual crypto is disabled in module configuration.');
        }

        return view("client-portal.crypto-transaction-request.new.sell", compact('accounts', 'fiatAccounts'));
    }

    public function newWithdraw()
    {
        $client_ids = Common::getClientIdsByUserId(Auth::user()->id);
        $cryptoCurrencies = Currency::where("is_active", true)->where("is_crypto", true)->select("id")->get();
        $cryptoAccounts = Account::whereIn("client_id", $client_ids)
            ->whereIn("account_currency", $cryptoCurrencies)
            ->where("is_active", true)
            ->select("id", "title", "account_number", "account_currency")
            ->get();

        return view("client-portal.crypto-transaction-request.new.withdraw", compact('cryptoAccounts'));
    }

    public function newDeposit(Request $request)
    {
        $client_ids = Common::getClientIdsByUserId(Auth::user()->id);
        $currencies = Currency::where("is_active", true)->where("is_crypto", true)->select("id")->get();
        $accounts = Account::whereIn("client_id", $client_ids)
            ->whereIn("account_currency", $currencies)
            ->where("is_active", true)
            ->select("id", "title", "account_number")
            ->get();
        $account_id = '';
        $network = $request->get("network");
        if ($request->get("ac") != '')
            $account_id =  $request->get("ac");
        if (in_array('Bitpace', $this->enabledModules)) {
            return view("client-portal.crypto-transaction-request.new.bitpace.deposit", compact('accounts', 'account_id', 'network'));
        }
        if (!\App\Services\CryptoConfigService::isManualCryptoEnabled()) {
            return redirect('/client/crypto-transaction-requests')->with('error', 'Manual crypto is disabled in module configuration.');
        }

        return view("client-portal.crypto-transaction-request.new.deposit", compact('accounts', 'account_id', 'network'));
    }


    public function newAdminBuy(Request $request)
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
            ->where("is_crypto", false);
        //if($request->get("method") == '')
        //$currencies ->where("short", 'EUR');

        $currencies->select("id")
            ->get();

        $accounts = Account::where("is_active", true)
            ->whereIn("account_currency", $currencies)
            ->select("id", "title", "account_number")
            ->get();
        if ($request->get("method") != '')
            $method =  $request->get("method");
        return view("crypto-management.crypto-transactions.new.buy", compact('accounts', 'method'));
    }

    public function newAdminDeposit(Request $request)
    {
        $currencies = Currency::where("is_active", true)
            ->where("is_crypto", true)
            ->select("id")
            ->get();

        $accounts = Account::where("is_active", true)
            ->whereIn("account_currency", $currencies)
            ->select("id", "title", "account_number")
            ->get();

        if ($request->get("method") != '')
            $method =  $request->get("method");
        return view("crypto-management.crypto-transactions.new.deposit", compact('accounts', 'method'));
    }

    public function confirmMethod(Request $request)
    {
        $type =  $request->get("m");
        return view("crypto-management.crypto-transactions.new.confirm-method", compact('type'));
    }
    public function newAdminSell(Request $request)
    {
        $currencies = Currency::where("is_active", true)
            ->where("is_crypto", true)
            ->select("id")
            ->get();

        $accounts = Account::where("is_active", true)
            ->whereIn("account_currency", $currencies)
            ->select("id", "title", "account_number")
            ->get();
        if ($request->get("method") != '')
            $method =  $request->get("method");
        return view("crypto-management.crypto-transactions.new.sell", compact('accounts', 'method'));
    }

    public function newAdminWithdraw(Request $request)
    {
        $currencies = Currency::where("is_active", true)
            ->where("is_crypto", true)
            ->select("id")
            ->get();

        $cryptoAccounts = Account::where("is_active", true)
            ->whereIn("account_currency", $currencies)
            ->select("id", "title", "account_number")
            ->get();
        if ($request->get("method") != '')
            $method =  $request->get("method");
        $vasp_list = BitpaceVasp::get();
        return view("crypto-management.crypto-transactions.new.withdraw", compact('cryptoAccounts', 'method', 'vasp_list'));
    }

    public function adminBuy(Request $request)
    {
        // validate input data
        $this->validate($request, [
            'debit_account_id' => 'required',
            'receiver_account_id' => 'required',
            'transaction_date' => 'required',
            'remarks' => 'required',
            'crypto_system' => 'required',
            'supporting_documents' => [new SupportingDocumentsRule]
        ], [
            'crypto_system.required' => 'Please select method for this transaction'

        ]);

        $debit_account = Account::find($request->get("debit_account_id"));
        $credit_account = Account::find($request->get("receiver_account_id"));
        $client = ClientHelper::getClientByAccountId($request->get("debit_account_id"));
        $currency = Currency::where('id', $credit_account->account_currency)->first();

        // if account is not available alert it
        if (!$credit_account || !$debit_account) {
            return redirect(url()->previous())->with('error', "Account details not found.");
        }

        // check if same account is selected for transaction
        if ($credit_account->id == $debit_account->id) {
            return redirect(url()->previous())->with('error', "Please choose different accounts");
        }


        if (!Common::validTime($request->get('transaction_time'))) {
            return redirect(url()->previous())->with('error', "Please enter valid time.");
        }
        if ($currency) {
            $this->validate($request, [
                'amount' => 'required|numeric|min:' . $currency->min_amount . '|max:' . $currency->max_amount,
            ]);
        } else {
            return redirect(url()->previous())->with('error', "Currency not found");
        }

        $balance = Common::getAccountBalanceById($debit_account->id);
        if ($balance <= 0) {
            return redirect(url()->previous())->with('error', "The account " . $debit_account->title . " have zero balance, please deposit balance in this account first.");
        }
        // create client transaction
        $dt_trn = new TRRequest();

        // create debit transaction
        $dt_trn->account_id = $debit_account->id;
        $dt_trn->currency_id = $debit_account->account_currency;
        $dt_trn->transaction_type = "Debit";
        $dt_trn->transaction_nature = "Buy";
        $dt_trn->transaction_date = Carbon::createFromFormat('d/m/Y H:i', $request->get('transaction_date') . ' ' . $request->get('transaction_time'))->format('Y-m-d H:i:s');
        $dt_trn->crypto_amount = $request->get("amount");
        $dt_trn->amount = 0;
        $dt_trn->receiver_account_number = $credit_account->account_number;
        $dt_trn->remarks = $request->get("remarks");
        $dt_trn->status = "Pending";
        $dt_trn->crypto_system = $request->get("crypto_system");
        $dt_trn->otp_verified = true;
        $dt_trn->created_by = Auth::user()->id;
        $dt_trn->save();

        $data = [
            'trn_id' => $dt_trn->id,
            "amount" => 0,
            "crypto_amount" => isset($request['amount']) ? $request['amount'] : null,
            "transaction_date" => $dt_trn->transaction_date,
            "remarks" => isset($request['remarks']) ? $request['remarks'] : null
        ];

        Common::saveTransactionDetails($data, $dt_trn);
        Common::uploadSupportingDocuments($request, $dt_trn, $client);

        return redirect("/crypto-management/crypto-pending-transactions")->with('success', "Crypto Buy transaction request created successfully.");
    }

    public function adminDeposit(Request $request)
    {
        // validate input data
        $this->validate($request, [
            'credit_account_id' => 'required',
            'transaction_date' => 'required',
            'transaction_time' => 'required',
            'remarks' => 'required',
            'crypto_system' => 'required',
            'supporting_documents' => [new SupportingDocumentsRule],
        ], [
            'crypto_system.required' => 'Please select method for this transaction'
        ]);

        $credit_account = Account::find($request->get("credit_account_id"));
        $client = ClientHelper::getClientByAccountId($request->get("credit_account_id"));
        $currency = Currency::where('id', $credit_account->account_currency)->first();

        // if account is not available alert it
        if (!$credit_account) {
            return redirect(url()->previous())->with('error', "Account details not found.");
        }

        if ($currency) {
            $this->validate($request, [
                'amount' => 'required|numeric|min:' . $currency->min_amount . '|max:' . $currency->max_amount,
            ]);
        } else {
            return redirect(url()->previous())->with('error', "Currency not found");
        }

        // validate time
        $time = Common::validateTime($request->get("transaction_time"));
        if (!$time) {
            return redirect("/finance-management/transactions/new/tt")->with('error', "Please provide a valid time in 24 hours format.");
        }

        // create client transaction
        $cr_trn = new TRRequest();

        // create credit transaction
        $cr_trn->account_id = $credit_account->id;
        $cr_trn->currency_id = $credit_account->account_currency;
        $cr_trn->transaction_type = "Credit";
        $cr_trn->transaction_nature = "Deposit";
        $cr_trn->transaction_date = Carbon::createFromFormat('d/m/Y', $request->get('transaction_date'))->format('Y-m-d') . ' ' . $time;
        $cr_trn->crypto_amount = $request->get("amount");
        $cr_trn->remarks = $request->get("remarks");
        $cr_trn->status = "Pending";
        $cr_trn->crypto_system = $request->get("crypto_system");
        $cr_trn->otp_verified = true;
        $cr_trn->created_by = Auth::user()->id;
        $cr_trn->save();

        $data = [
            'trn_id' => $cr_trn->id,
            "amount" => 0,
            "crypto_amount" => isset($request['amount']) ? $request['amount'] : null,
            "transaction_date" => $cr_trn->transaction_date,
            "remarks" => isset($request['remarks']) ? $request['remarks'] : null
        ];

        Common::saveTransactionDetails($data, $cr_trn);
        Common::uploadSupportingDocuments($request, $cr_trn, $client);

        // Common::uploadSupportingDocuments($request, $cr_trn, $client);

        return redirect("/crypto-management/crypto-pending-transactions")->with('success', "Crypto Deposit transaction request created successfully.");
    }

    public function adminSell(Request $request)
    {
        // validate input data
        $this->validate($request, [
            'debit_account_id' => 'required',
            'receiver_account_id' => 'required',
            'transaction_date' => 'required',
            'transaction_time' => 'required',
            'remarks' => 'required',
            'crypto_system' => 'required',
            'supporting_documents' => [new SupportingDocumentsRule]
        ], [
            'crypto_system.required' => 'Please select method for this transaction'

        ]);

        $debit_account = Account::find($request->get("debit_account_id"));
        $credit_account = Account::find($request->get("receiver_account_id"));
        $client = ClientHelper::getClientByAccountId($request->get("debit_account_id"));
        $currency = Currency::where('id', $debit_account->account_currency)->first();

        // if account is not available alert it
        if (!$credit_account || !$debit_account) {
            return redirect(url()->previous())->with('error', "Account details not found.");
        }

        // check if same account is selected for transaction
        if ($credit_account->id == $debit_account->id) {
            return redirect(url()->previous())->with('error', "Please choose different accounts");
        }

        // validate time
        $time = Common::validateTime($request->get("transaction_time"));
        if (!$time) {
            return redirect("/finance-management/transactions/new/tt")->with('error', "Please provide a valid time in 24 hours format.");
        }

        // // check if debit account is having enough balance
        $balance = Common::getAccountBalanceById($debit_account->id);

        if ($request->get("amount") > $balance) {
            return redirect(url()->previous())->with('error', "You don't have enough money in your account");
        }

        if ($currency) {
            $this->validate($request, [
                'amount' => 'required|numeric|min:' . $currency->min_amount . '|max:' . $currency->max_amount,
            ]);
        } else {
            return redirect(url()->previous())->with('error', "Currency not found");
        }

        // create client transaction
        $dt_trn = new TRRequest();

        // create debit transaction
        $dt_trn->account_id = $debit_account->id;
        $dt_trn->currency_id = $debit_account->account_currency;
        $dt_trn->transaction_type = "Debit";
        $dt_trn->transaction_nature = "Sell";
        $dt_trn->transaction_date = Carbon::createFromFormat('d/m/Y', $request->get('transaction_date'))->format('Y-m-d') . ' ' . $time;
        $dt_trn->crypto_amount = $request->get("amount");
        $dt_trn->amount = 0;
        $dt_trn->receiver_account_number = $credit_account->account_number;
        $dt_trn->remarks = $request->get("remarks");
        $dt_trn->status = "Pending";
        $dt_trn->crypto_system = $request->get("crypto_system");
        $dt_trn->otp_verified = true;
        $dt_trn->created_by = Auth::user()->id;

        $dt_trn->save();

        $data = [
            'trn_id' => $dt_trn->id,
            "amount" => 0,
            "crypto_amount" => isset($request['amount']) ? $request['amount'] : null,
            "transaction_date" => $dt_trn->transaction_date,
            "remarks" => isset($request['remarks']) ? $request['remarks'] : null
        ];

        Common::saveTransactionDetails($data, $dt_trn);
        Common::uploadSupportingDocuments($request, $dt_trn, $client);

        return redirect("/crypto-management/crypto-pending-transactions")->with('success', "Crypto Sell transaction request created successfully.");
    }

    public function adminWithdraw(Request $request)
    {
        $this->validate($request, [
            'account_id' => 'required',
            'to_wallet_address' => 'required',
            'network' => 'required',
            'transaction_date' => 'required',
            'transaction_time' => 'required',
            'remarks' => 'required',
            'crypto_system' => 'required',
            'supporting_documents' => [new SupportingDocumentsRule]
        ], [
            'crypto_system.required' => 'Please select method for this transaction',
            'network.required' => 'Please select a network for this withdrawal'
        ]);
        //dd($request->all());
        $debit_account = Account::find($request->get("account_id"));
        $client = ClientHelper::getClientByAccountId($request->get("account_id"));
        $currency = Currency::where('id', $debit_account->account_currency)->first();


        // if account is not available alert it
        if (!$debit_account) {
            return redirect(url()->previous())->with('error', "Account details not found.");
        }

        // validate time
        $time = Common::validateTime($request->get("transaction_time"));
        if (!$time) {
            return redirect("/finance-management/transactions/new/tt")->with('error', "Please provide a valid time in 24 hours format.");
        }

        // // check if debit account is having enough balance
        $balance = Common::getAccountBalanceById($debit_account->id);

        if ($request->get("amount") > $balance) {
            return redirect(url()->previous())->with('error', "You don't have enough money in your account");
        }

        if ($currency) {
            $this->validate($request, [
                'amount' => 'required|numeric|min:' . $currency->min_amount . '|max:' . $currency->max_amount,
            ]);

            // Validate destination_tag for currencies that require it
            $tagRequiredCurrencies = ['XRP', 'XLM', 'EOS', 'XEM', 'ATOM', 'BNB'];
            if (in_array($currency->short, $tagRequiredCurrencies) && empty($request->get('destination_tag'))) {
                return redirect(url()->previous())->with('error', "Destination tag / memo is required for {$currency->short} withdrawals.")->withInput();
            }
        } else {
            return redirect(url()->previous())->with('error', "Currency not found");
        }
        $bitpaceHelper = new BitpaceMerchantApis();
        $travel_rule_enabled = $bitpaceHelper->isTravelRuleEnabled();
        if ($travel_rule_enabled) {



            if (empty($request->get('beneficiary_type')) && $request->get("is_sending_to_myself") == false) {
                return redirect(url()->previous())->with('error', "Please select beneficiary type.")->withInput();
            }

            if (empty($request->get('wallet_type'))) {
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
                    if (empty($request->get("beneficiary_legal_name"))) {
                        return redirect(url()->previous())->with('error', "Please provide beneficiary legal name for business beneficiary type.")->withInput();
                    }
                }
            }


            if ($request->get('wallet_type') == 'CUSTODIAL') {
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
                if (empty($request->get('beneficiary_vasp_did')) && empty($request->get('beneficiary_vasp_name'))) {
                    return redirect(url()->previous())->with('error', "Please provide beneficiary VASP DID or name.")->withInput();
                }
                if (empty($beneficiary_vasp_did)) {
                    return redirect(url()->previous())->with('error', "Please provide beneficiary VASP DID for VASP beneficiary.")->withInput();
                }
            }
        }

        // create client transaction
        $dt_trn = new TRRequest();

        // create debit transaction
        $dt_trn->account_id = $debit_account->id;
        $dt_trn->currency_id = $debit_account->account_currency;
        $dt_trn->transaction_type = "Debit";
        $dt_trn->transaction_nature = "Withdraw";
        $dt_trn->transaction_date = Carbon::createFromFormat('d/m/Y', $request->get('transaction_date'))->format('Y-m-d') . ' ' . $time;
        $dt_trn->crypto_amount = $request->get("amount");
        $dt_trn->to_wallet_address = $request->get("to_wallet_address");
        $dt_trn->crypto_network = $request->get("network");
        $dt_trn->destination_tag = $request->get("destination_tag");
        $dt_trn->remarks = $request->get("remarks");
        $dt_trn->crypto_system = $request->get("crypto_system");
        $dt_trn->status = "Pending";
        $dt_trn->otp_verified = true;
        $dt_trn->created_by = Auth::user()->id;

        $dt_trn->save();
        if ($travel_rule_enabled && $request->get("is_sending_to_myself") == false) {
            $client = ClientHelper::getClientByAccountId($request->get("account_id"));
            $this->saveBeneficiary($request, $dt_trn, $client);
        }
        Common::uploadSupportingDocuments($request, $dt_trn, $client);

        return redirect("/crypto-management/crypto-pending-transactions")->with('success', "Crypto Withdraw transaction request created successfully.");
    }

    public function saveBeneficiary($request, $dt_trn, $client)
    {
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
    public function viewCryptoTransaction($id)
    {
        $transaction = TRRequest::find($id);
        //dd($transaction);

        try {
            $belongsToClient = IDORHelper::isAccountBelongToUser(Auth::user(), $transaction->account_id);
            //get blockcain id
            $transactions = $transaction->transactions;
            $blockchain_tx_id = '';
            if (!empty($transactions[0]->crypto_transaction_id)) {
                $crypto_transaction_id = $transactions[0]->crypto_transaction_id;
                $transaction_log = [];

                $transaction_log = DB::table('crypto_transactions_log')->where('transaction_id', $crypto_transaction_id)->orderBy('id', 'DESC')->first();
                if (!empty($transaction_log->details))
                    $trx_details = json_decode($transaction_log->details);
                if (!empty($trx_details->blockchain_tx_id))
                    $blockchain_tx_id = $trx_details->blockchain_tx_id;
            }
            if ($belongsToClient) {
                return view("client-portal.crypto-transaction-request.view." . strtolower($transaction->transaction_nature), compact('transaction', 'blockchain_tx_id'));
            }
        } catch (Exception $e) {
            Common::logError($e, __METHOD__, __LINE__);

            if (401 === $e->getCode()) {
                return back()->with('error', 'You are not authorized to access this page.');
            } else {
                return back()->with('error', 'Something went wrong, please try again later.');
            }
        }
    }

    public function editCryptoTransaction($id)
    {
        $transaction = TRRequest::find($id);

        try {
            $belongsToClient = IDORHelper::isAccountBelongToUser(Auth::user(), $transaction->account_id);

            if ($belongsToClient) {

                if ('Approved' === $transaction->status) {
                    return back()->with('error', 'The transaction has already been approved!');
                }

                $client_ids = Common::getClientIdsByUserId(Auth::user()->id);

                $cryptoCurrencies = Currency::where("is_active", true)->where("is_crypto", true)->select("id")->get();
                $cryptoAccounts = Account::whereIn("client_id", $client_ids)
                    ->whereIn("account_currency", $cryptoCurrencies)
                    ->where("is_active", true)
                    ->select("id", "title", "account_number", "account_currency")
                    ->get();

                $currencies = Currency::where("is_active", true)
                    ->where("is_crypto", false)
                    ->select("id")
                    ->get();

                $accounts = Account::whereIn("client_id", $client_ids)
                    ->whereIn("account_currency", $currencies)
                    ->where("is_active", true)
                    ->select("id", "title", "account_number")
                    ->get();

                $transactionNature = $transaction->transaction_nature;

                return view("client-portal.crypto-transaction-request.edit." . strtolower($transactionNature), compact('transaction', 'accounts', 'cryptoAccounts'));
            }
        } catch (Exception $e) {
            Common::logError($e, __METHOD__, __LINE__);

            if (401 === $e->getCode()) {
                return back()->with('error', 'You are not authorized to access this page.');
            } else {
                return back()->with('error', 'Something went wrong, please try again later.');
            }
        }
    }

    public function buy(Request $request)
    {
        $this->validate($request, [
            'debit_account_id' => 'required',
            'receiver_account_id' => 'required',
            //'transaction_date' => 'required',
            //'remarks' => 'required',
            'order_type' => 'required',
            'amount' => 'required'
            //'supporting_documents' => [new SupportingDocumentsRule],
        ]);
        $is_batpace = false;
        $source = 'Manual';
        if (in_array('Bitpace', $this->enabledModules)) {
            $source = 'Bitpace';
        } else {
            session()->put('cryptio_price_quote', '');
        }
        $transaction_date = Carbon::createFromFormat('d/m/Y', date('d/m/Y', time()))->format('Y-m-d H:i:s');
        if ($source  != 'Bitpace') {

            if ($request->get("transaction_date") == '') {
                echo json_encode(array('status' => 'DECLINED', 'type' => 'internal', 'message' => 'Please enter date.'));
                die();
            }
            if ($request->get("remarks") == '') {
                echo json_encode(array('status' => 'DECLINED', 'type' => 'internal', 'message' => 'Please enter remarks.'));
                die();
            }
            $valid =  Common::validTime($request->get('transaction_time'));
            if (!$valid) {
                echo json_encode(array('status' => 'DECLINED', 'type' => 'internal', 'message' => 'Please enter valid time.'));
                die();
            }
            $transaction_date = Carbon::createFromFormat('d/m/Y H:i', $request->get('transaction_date') . ' ' . $request->get('transaction_time'))->format('Y-m-d H:i:s');
        }


        try {
            // Check the Debit account_id
            $belongsToClient = IDORHelper::isAccountBelongToUser(Auth::user(), $request->get("debit_account_id"));

            if ($belongsToClient) {

                // Check the Credit account_id
                $belongsToClient = IDORHelper::isAccountBelongToUser(Auth::user(), $request->get("receiver_account_id"));

                if ($belongsToClient) {

                    $debit_account = Account::find($request->get("debit_account_id"));
                    $client = ClientHelper::getClientByAccountId($request->get("debit_account_id"));
                    $credit_account = Account::find($request->get("receiver_account_id"));
                    $currency = Currency::where('id', $credit_account->account_currency)->first();

                    // if account is not available alert it
                    if (!$credit_account || !$debit_account) {
                        echo json_encode(array('status' => 'DECLINED', 'type' => 'internal', 'message' => 'Account details not found.'));
                        die();
                    }

                    // check if same account is selected for transaction
                    if ($credit_account->id == $debit_account->id) {
                        echo json_encode(array('status' => 'DECLINED', 'type' => 'internal', 'message' => 'Please choose different accounts.'));
                        die();
                    }

                    // check if debit account is having enough balance
                    $balance = Common::getAccountBalanceById($debit_account->id);
                    if ($source  == 'Bitpace') {
                        $cryptio_price_quote = Session::get('cryptio_price_quote');
                        $cryptio_price_quote = json_decode($cryptio_price_quote);

                        if ($cryptio_price_quote->total_amount > $balance) {
                            $message =  "The account " . $debit_account->title . "  don't have enough money., please deposite balance in your account first.";
                            echo json_encode(array('status' => 'DECLINED', 'type' => 'internal', 'message' => $message));
                            die();
                        }
                    } elseif ($request->get("amount") <= 0) {

                        $message =  "The account " . $debit_account->title . "  don't have enough money., please deposite balance in your account first.";
                        echo json_encode(array('status' => 'DECLINED', 'type' => 'internal', 'message' => $message));
                        die();
                    }


                    if ($currency) {
                        $this->validate($request, [
                            'amount' => 'required|numeric|min:' . $currency->min_amount . '|max:' . $currency->max_amount,
                        ]);
                    } else {
                        echo json_encode(array('status' => 'DECLINED', 'type' => 'internal', 'message' => 'Currency not found'));
                        die();
                    }

                    // create client transaction

                    if (!empty($debit_account->id)) {

                        $total_amount = 0;
                        $cryptio_price_quote = Session::get('cryptio_price_quote');
                        if (!empty($cryptio_price_quote)) {
                            $cryptio_price_quote = json_decode($cryptio_price_quote);
                            $total_amount = $cryptio_price_quote->total_amount;
                        }


                        $dt_trn = new TRRequest();

                        // create debit transaction
                        $dt_trn->account_id = $debit_account->id;
                        $dt_trn->currency_id = $debit_account->account_currency;
                        $dt_trn->transaction_type = "Debit";
                        $dt_trn->transaction_nature = "Buy";
                        $dt_trn->transaction_date = $transaction_date;
                        $dt_trn->crypto_amount = $request->get("amount");
                        $dt_trn->amount = $total_amount;
                        $dt_trn->receiver_account_number = $credit_account->account_number;
                        $dt_trn->remarks = $request->get("remarks");
                        $dt_trn->status = "Pending";
                        $dt_trn->crypto_system = $source;
                        $dt_trn->created_by = Auth::user()->id;

                        if ($dt_trn->save()) {
                            //do api call and post transaction
                            if ($source  == 'Bitpace') {
                                $token = $request->get("token");
                                $this->postBuySellTransaction($dt_trn, $credit_account, $debit_account, $request->get("order_type"), $token, $cryptio_price_quote);
                            } else {
                                echo json_encode(array('status' => 'SUCCESS'));
                                die();
                            }
                        }

                        //Common::uploadSupportingDocuments($request, $dt_trn, $client);

                        // generate OTP code and send it to user
                        //EmailNotification::RequestOTP($dt_trn);

                        //return redirect("client/crypto-transaction-requests/otp/" . $dt_trn->id);
                    } else {
                        echo json_encode(array('status' => 'DECLINED', 'type' => 'internal', 'message' => 'Price not available for selected currency.'));
                        die();
                    }
                }
            }
        } catch (Exception $e) {
            Common::logError($e, __METHOD__, __LINE__);

            if (401 === $e->getCode()) {
                // return back()->with('error', 'You are not authorized to make this transaction.');
                echo json_encode(array('status' => 'DECLINED', 'type' => 'internal', 'message' => 'You are not authorized to make this transaction.'));
                die();
            } else {
                //return back()->with('error', 'Something went wrong, please try again later.');
                echo json_encode(array('status' => 'DECLINED', 'type' => 'internal', 'message' => 'Something went wrong, please try again later.'));
                die();
            }
        }
    }

    public function sell(Request $request)
    {
        // validate input data
        // $this->validate($request, [
        //     'debit_account_id' => 'required',
        //     'receiver_account_id' => 'required',
        //     'transaction_date' => 'required',
        //     'remarks' => 'required',
        //     'supporting_documents' => [new SupportingDocumentsRule],
        // ]);
        $this->validate($request, [
            'debit_account_id' => 'required',
            'receiver_account_id' => 'required',
            //'transaction_date' => 'required',
            //'remarks' => 'required',
            'order_type' => 'required',
            'amount' => 'required'
            //'supporting_documents' => [new SupportingDocumentsRule],
        ]);


        $source = 'Manual';
        if (in_array('Bitpace', $this->enabledModules)) {
            $source = 'Bitpace';
        } else {
            session()->put('cryptio_price_quote', '');
        }
        $transaction_date = Carbon::createFromFormat('d/m/Y', date('d/m/Y', time()))->format('Y-m-d H:i:s');
        if ($source  != 'Bitpace') {

            if ($request->get("transaction_date") == '') {
                echo json_encode(array('status' => 'DECLINED', 'type' => 'internal', 'message' => 'Please enter date.'));
                die();
            }
            if ($request->get("remarks") == '') {
                echo json_encode(array('status' => 'DECLINED', 'type' => 'internal', 'message' => 'Please enter remarks.'));
                die();
            }
            $valid =  Common::validTime($request->get('transaction_time'));
            if (!$valid) {
                echo json_encode(array('status' => 'DECLINED', 'type' => 'internal', 'message' => 'Please enter valid time.'));
                die();
            }
            $transaction_date = Carbon::createFromFormat('d/m/Y H:i', $request->get('transaction_date') . ' ' . $request->get('transaction_time'))->format('Y-m-d H:i:s');
        }

        try {
            // Check the Debit account_id
            $belongsToClient = IDORHelper::isAccountBelongToUser(Auth::user(), $request->get("debit_account_id"));

            if ($belongsToClient) {

                // Check the Credit account_id
                $belongsToClient = IDORHelper::isAccountBelongToUser(Auth::user(), $request->get("receiver_account_id"));

                if ($belongsToClient) {

                    $debit_account = Account::find($request->get("debit_account_id"));
                    $client = ClientHelper::getClientByAccountId($request->get("debit_account_id"));
                    $credit_account = Account::find($request->get("receiver_account_id"));
                    $currency = Currency::where('id', $debit_account->account_currency)->first();

                    // if account is not available alert it
                    if (!$credit_account || !$debit_account) {
                        echo json_encode(array('status' => 'DECLINED', 'type' => 'internal', 'message' => 'Account details not found.'));
                        die();
                    }

                    // check if same account is selected for transaction
                    if ($credit_account->id == $debit_account->id) {
                        echo json_encode(array('status' => 'DECLINED', 'type' => 'internal', 'message' => 'Please choose different accounts.'));
                        die();
                    }

                    // check if debit account is having enough balance
                    $balance = Common::getAccountBalanceById($debit_account->id);
                    if ($source  == 'Bitpace') {
                        $cryptio_price_quote = Session::get('cryptio_price_quote');
                        $cryptio_price_quote = json_decode($cryptio_price_quote);
                        if ($cryptio_price_quote->cryptocurrency_amount > $balance) {
                            $message =  "The account " . $debit_account->title . "  don't have enough money., please deposite balance in your account first.";
                            echo json_encode(array('status' => 'DECLINED', 'type' => 'internal', 'message' => $message));
                            die();
                        }
                    } elseif ($request->get("amount") <= 0) {
                        $message =  "The account " . $debit_account->title . "  don't have enough money., please deposite balance in your account first.";
                        echo json_encode(array('status' => 'DECLINED', 'type' => 'internal', 'message' => $message));
                        die();
                    }

                    if ($currency) {
                        $this->validate($request, [
                            'amount' => 'required|numeric|min:' . $currency->min_amount . '|max:' . $currency->max_amount,
                        ]);
                    } else {
                        echo json_encode(array('status' => 'DECLINED', 'type' => 'internal', 'message' => 'Currency not found".'));
                        die();
                    }

                    // create client transaction
                    $cryptio_price_quote = Session::get('cryptio_price_quote');
                    if (!empty($debit_account->id)) {

                        $total_amount = 0;
                        $cryptio_price_quote = Session::get('cryptio_price_quote');
                        if (!empty($cryptio_price_quote)) {
                            $cryptio_price_quote = json_decode($cryptio_price_quote);
                            $total_amount = $cryptio_price_quote->total_amount;
                        }


                        $dt_trn = new TRRequest();

                        // create debit transaction
                        $dt_trn->account_id = $debit_account->id;
                        $dt_trn->currency_id = $debit_account->account_currency;
                        $dt_trn->transaction_type = "Debit";
                        $dt_trn->transaction_nature = "Sell";
                        // $dt_trn->transaction_date = Carbon::createFromFormat('d/m/Y', $request->get('transaction_date'))->format('Y-m-d H:i:s');
                        $dt_trn->transaction_date = $transaction_date;
                        $dt_trn->crypto_amount = $request->get("amount");
                        $dt_trn->amount = $total_amount;
                        $dt_trn->receiver_account_number = $credit_account->account_number;

                        $dt_trn->remarks = $request->get("remarks");
                        $dt_trn->status = "Pending";
                        $dt_trn->crypto_system = $source;
                        $dt_trn->created_by = Auth::user()->id;

                        if ($dt_trn->save()) {
                            if ($source  == 'Bitpace') {
                                $token = $request->get("token");
                                $this->postBuySellTransaction($dt_trn, $credit_account, $debit_account, $request->get("order_type"), $token, $cryptio_price_quote);
                            } else {
                                echo json_encode(array('status' => 'SUCCESS'));
                                die();
                            }
                        }

                        Common::uploadSupportingDocuments($request, $dt_trn, $client);


                        // generate OTP code and send it to user
                        //EmailNotification::RequestOTP($dt_trn);


                        //return redirect("client/crypto-transaction-requests/otp/" . $dt_trn->id);
                    } else {
                        echo json_encode(array('status' => 'DECLINED', 'type' => 'internal', 'message' => 'Price not available for selected currency.'));
                        die();
                    }
                }
            }
        } catch (Exception $e) {
            Common::logError($e, __METHOD__, __LINE__);

            if (401 === $e->getCode()) {
                // return back()->with('error', 'You are not authorized to make this transaction.');
                echo json_encode(array('status' => 'DECLINED', 'type' => 'internal', 'message' => 'You are not authorized to make this transaction.'));
                die();
            } else {
                //return back()->with('error', 'Something went wrong, please try again later.');
                echo json_encode(array('status' => 'DECLINED', 'type' => 'internal', 'message' => 'Something went wrong, please try again later.'));
                die();
            }
        }
    }

    function postBuySellTransaction($transaction_request, $credit_account, $debit_account, $order_type, $token, $cryptio_price_quote)
    {

        // echo "transaction saved"; die();
        $account = $this->getAccountInfoById($credit_account->id);
        $name = $account->name;

        $crypto_account_id = $account->id;

        $nameArr =  explode(' ', $name, 2);
        $firstname = $name;
        $lastname = '';
        if (count($nameArr) > 0) {
            $firstname = $nameArr[0];
            if (isset($nameArr[1]))
                $lastname = $nameArr[1];
        }


        $currency_id = $account->currency_id;

        //prepare payload for API call
        $data = '{
                "customer": {
                "reference_id": "' . $account->id . '",
                "first_name": "' . $firstname . '",
                "last_name": "' . $lastname . '",
                "email": "' . $account->email . '"
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
                        $crypto_account_id = $credit_account->id;
                        $currency_id = $credit_account->account_currency;
                        $to_account_id = $debit_account->id;
                        $tr_time = date("Y-m-d H:i:s", time());
                    }
                    if ($order_type == 'SELL') {
                        $type = 'Debit';
                        $nature = 'Sell';
                        $crypto_account_id = $debit_account->id;
                        $currency_id = $debit_account->account_currency;
                        $to_account_id = $credit_account->id;
                        // plus 10 seconds
                        $tr_time = date("Y-m-d H:i:s", time() + 10);
                    }
                    $order = $bitpaceHelper->getOrderV2($order_id);
                    $status = $order->status;
                    $order_data =  json_encode($order);
                    $this->storeApiResponseData($order_id, $order_data, 'Bitpace');
                    $status = $order->status;
                    //$status  = 'FAILED';
                    //if status FAILED from API

                    if ($order->status  == 'FAILED' || $order->status  == 'DECLINED') {
                        //$tr_status = 'Failed';
                        $transaction_request->status = 'Failed';
                        $transaction_request->save();
                        echo   $order_data;
                        die();
                        //echo json_encode(array('status'=>'DECLINED','type'=>'internal','message'=>'Transaction request has been failed. Please try again later.')); die();
                    }

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
                    $cr_trn->remarks = $transaction_request->remarks;
                    $cr_trn->tr_request_id = $transaction_request->id;
                    $cr_trn->status = $status;
                    $cr_trn->crypto_system = "Bitpace";
                    $cr_trn->created_by = Auth::user()->id;
                    $cr_trn->approved_by = Auth::user()->id;
                    if ($cr_trn->save()) {
                        $response = true;
                        // DEBIT the Fiat Account
                        if ($order_type == 'BUY') {
                            $type = 'Debit';
                            $nature = 'Buy';
                            // plus 10 sendons
                            $tr_time = date("Y-m-d H:i:s", time() + 10);
                            $currency_id = $debit_account->account_currency;
                        }
                        if ($order_type == 'SELL') {
                            $type = 'Credit';
                            $nature = 'Sell';
                            $tr_time = date("Y-m-d H:i:s", time());
                            $currency_id = $debit_account->account_currency;
                        }

                        $account = $this->getAccountInfoById($to_account_id);

                        $currency_id = $account->currency_id;
                        $dt_trn = new AccountTransaction();
                        $dt_trn->account_id = $account->id;
                        $dt_trn->link_tr_id = $cr_trn->id;
                        $dt_trn->crypto_transaction_id = $order_id;
                        //$dt_trn->beneficiary_id = $request->get("beneficiary_id");
                        $dt_trn->currency_id = $currency_id;
                        $dt_trn->transaction_type = $type;
                        $dt_trn->transaction_nature = $nature;
                        $dt_trn->transaction_date = $tr_time;
                        $dt_trn->amount = $cryptio_price_quote->total_amount;
                        $dt_trn->is_crypto = 1;
                        $dt_trn->remarks = $transaction_request->remarks;
                        $dt_trn->tr_request_id = $transaction_request->id;
                        $dt_trn->status = $status;
                        $dt_trn->crypto_system = "Bitpace";
                        $dt_trn->created_by = Auth::user()->id;
                        $dt_trn->approved_by = Auth::user()->id;
                        if ($dt_trn->save()) {
                            $cr_trn->link_tr_id = $dt_trn->id;
                            $cr_trn->save();
                            if ($data->order_id != '') {
                                $transaction_request->status = $status;
                                $transaction_request->save();
                                if ($status == 'Approved') {
                                    EmailNotification::notifyClientForTransactionApproval($transaction_request);
                                    // Log::info("\n\n  Transaction saved with Status Approved: " . json_encode($transaction_request));
                                }
                            }
                        }
                    } else {
                        $response = false;
                    }

                    echo  $order_data;
                    session()->put('cryptio_price_quote', '');
                    die();
                }
                // }
            } else {
                //{"code":"170","message":"Merchant balance not enough for this transaction","status":"DECLINED","traceId":"1cf8dc30d8fbdfee"}
                //send email for low balance

                $transaction_request->status = 'Failed';
                $transaction_request->save();
                session()->put('cryptio_price_quote', '');
                if (!empty($data->code)) {
                    if ($data->code == '170') {
                        //EmailNotification::notifyClientForTransactionApproval($transaction_request);
                        EmailNotification::notifyFinanceManagersForBitpaceLowBalance($transaction_request);
                        echo json_encode(array('status' => 'DECLINED', 'type' => 'internal', 'message' => 'Crypto transaction request could not be completed at this time. Please try again later.'));
                        die();
                    }
                }
                echo json_encode($data);
            }
        }
    }
    public function storeApiResponseData($transaction_id, $data, $crypto_system)
    {

        DB::table('crypto_transactions_log')->insert([
            'transaction_id' => $transaction_id,
            'details' => $data,
            'crypto_system' => $crypto_system,
            'source' => 'client',
            'date_created' => date('Y-m-d H:i:s', time())
        ]);
    }
    public function getAccountInfoById($account_id)
    {
        return DB::table('accounts')
            ->select(
                'accounts.id',
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
            ->where('accounts.id', '=', $account_id)
            ->first();
    }

    public function updateBuy(Request $request)
    {
        $this->validate($request, [
            'debit_account_id' => 'required',
            'receiver_account_id' => 'required',
            'transaction_date' => 'required',
            'remarks' => 'required',
            'supporting_documents' => [new SupportingDocumentsRule],
        ]);

        try {
            // Check the Debit account_id
            $belongsToClient = IDORHelper::isAccountBelongToUser(Auth::user(), $request->get("debit_account_id"));

            if ($belongsToClient) {

                // Check the Credit account_id
                $belongsToClient = IDORHelper::isAccountBelongToUser(Auth::user(), $request->get("receiver_account_id"));

                if ($belongsToClient) {

                    $debit_account = Account::find($request->get("debit_account_id"));
                    $client = ClientHelper::getClientByAccountId($request->get("debit_account_id"));
                    $credit_account = Account::find($request->get("receiver_account_id"));
                    $currency = Currency::where('id', $credit_account->account_currency)->first();

                    // if account is not available alert it
                    if (!$credit_account || !$debit_account) {
                        return redirect(url()->previous())->with('error', "Account details not found.");
                    }

                    // check if same account is selected for transaction
                    if ($credit_account->id == $debit_account->id) {
                        return redirect(url()->previous())->with('error', "Please choose different accounts");
                    }

                    // check if debit account is having enough balance
                    // $balance = Common::getAccountBalanceById($debit_account->id);

                    // if ($request->get("amount") > $balance){
                    //     return redirect(url()->previous())->with('error', "You don't have enough money in your account");
                    // }

                    if ($currency) {
                        $this->validate($request, [
                            'amount' => 'required|numeric|min:' . $currency->min_amount . '|max:' . $currency->max_amount,
                        ]);
                    } else {
                        return redirect(url()->previous())->with('error', "Currency not found");
                    }

                    // Get client transaction
                    $dt_trn = TRRequest::find($request->get("id"));

                    // update debit transaction
                    $dt_trn->account_id = $debit_account->id;
                    $dt_trn->currency_id = $debit_account->account_currency;
                    $dt_trn->transaction_type = "Debit";
                    $dt_trn->transaction_nature = "Buy";
                    $dt_trn->crypto_amount = $request->get("amount");
                    $dt_trn->amount = 0;
                    $dt_trn->receiver_account_number = $credit_account->account_number;
                    $dt_trn->remarks = $request->get("remarks");
                    $dt_trn->status = "Pending";
                    $dt_trn->created_by = Auth::user()->id;

                    $dt_trn->save();

                    Common::uploadSupportingDocuments($request, $dt_trn, $client);


                    // generate OTP code and send it to user
                    EmailNotification::RequestOTP($dt_trn);

                    return redirect("client/crypto-transaction-requests/otp/" . $dt_trn->id);
                }
            }
        } catch (Exception $e) {
            Common::logError($e, __METHOD__, __LINE__);

            if (401 === $e->getCode()) {
                return back()->with('error', 'You are not authorized to update this transaction.');
            } else {
                return back()->with('error', 'Something went wrong, please try again later.');
            }
        }
    }

    public function deposit(Request $request)
    {
        // validate input data
        $this->validate($request, [
            'credit_account_id' => 'required',
            'transaction_date' => 'required',
            'remarks' => 'required',
            'supporting_documents' => [new SupportingDocumentsRule],
        ]);

        try {
            $belongsToClient = IDORHelper::isAccountBelongToUser(Auth::user(), $request->get("credit_account_id"));

            if ($belongsToClient) {
                $credit_account = Account::find($request->get("credit_account_id"));
                $client = ClientHelper::getClientByAccountId($request->get("credit_account_id"));
                $currency = Currency::where('id', $credit_account->account_currency)->first();

                // if account is not available alert it
                if (!$credit_account) {
                    return redirect(url()->previous())->with('error', "Account details not found.");
                }

                if ($currency) {
                    $this->validate($request, [
                        'amount' => 'required|numeric|min:' . $currency->min_amount . '|max:' . $currency->max_amount,
                    ]);
                } else {
                    return redirect(url()->previous())->with('error', "Currency not found");
                }

                // create client transaction
                $cr_trn = new TRRequest();

                // create credit transaction
                $cr_trn->account_id = $credit_account->id;
                $cr_trn->currency_id = $credit_account->account_currency;
                $cr_trn->transaction_type = "Credit";
                $cr_trn->transaction_nature = "Deposit";
                $cr_trn->transaction_date = Carbon::createFromFormat('d/m/Y', $request->get('transaction_date'))->format('Y-m-d H:i:s');
                $cr_trn->crypto_amount = $request->get("amount");
                $cr_trn->remarks = $request->get("remarks");
                $cr_trn->status = "Pending";
                $cr_trn->created_by = Auth::user()->id;

                $cr_trn->save();

                Common::uploadSupportingDocuments($request, $cr_trn, $client);


                // generate OTP code and send it to user
                EmailNotification::RequestOTP($cr_trn);

                return redirect("client/crypto-transaction-requests/otp/" . $cr_trn->id);
            }
        } catch (Exception $e) {
            Common::logError($e, __METHOD__, __LINE__);

            if (401 === $e->getCode()) {
                return back()->with('error', 'You are not authorized to make this transaction.');
            } else {
                return back()->with('error', 'Something went wrong, please try again later.');
            }
        }
    }

    public function updateDeposit(Request $request)
    {
        // validate input data
        $this->validate($request, [
            'credit_account_id' => 'required',
            'transaction_date' => 'required',
            'remarks' => 'required',
            'supporting_documents' => [new SupportingDocumentsRule],
        ]);

        try {
            $belongsToClient = IDORHelper::isAccountBelongToUser(Auth::user(), $request->get("credit_account_id"));

            if ($belongsToClient) {
                $credit_account = Account::find($request->get("credit_account_id"));
                $client = ClientHelper::getClientByAccountId($request->get("credit_account_id"));
                $currency = Currency::where('id', $credit_account->account_currency)->first();

                // if account is not available alert it
                if (!$credit_account) {
                    return redirect(url()->previous())->with('error', "Account details not found.");
                }

                if ($currency) {
                    $this->validate($request, [
                        'amount' => 'required|numeric|min:' . $currency->min_amount . '|max:' . $currency->max_amount,
                    ]);
                } else {
                    return redirect(url()->previous())->with('error', "Currency not found");
                }

                // Get client transaction
                $cr_trn = TRRequest::find($request->get("id"));

                // create credit transaction
                $cr_trn->account_id = $credit_account->id;
                $cr_trn->currency_id = $credit_account->account_currency;
                $cr_trn->transaction_type = "Credit";
                $cr_trn->transaction_nature = "Deposit";
                $cr_trn->crypto_amount = $request->get("amount");
                $cr_trn->remarks = $request->get("remarks");
                $cr_trn->status = "Pending";
                $cr_trn->created_by = Auth::user()->id;

                $cr_trn->save();

                Common::uploadSupportingDocuments($request, $cr_trn, $client);


                // generate OTP code and send it to user
                EmailNotification::RequestOTP($cr_trn);

                return redirect("client/crypto-transaction-requests/otp/" . $cr_trn->id);
            }
        } catch (Exception $e) {
            Common::logError($e, __METHOD__, __LINE__);

            if (401 === $e->getCode()) {
                return back()->with('error', 'You are not authorized to update this transaction.');
            } else {
                return back()->with('error', 'Something went wrong, please try again later.');
            }
        }
    }

    public function updateSell(Request $request)
    {
        // validate input data
        $this->validate($request, [
            'debit_account_id' => 'required',
            'receiver_account_id' => 'required',
            'transaction_date' => 'required',
            'remarks' => 'required',
            'supporting_documents' => [new SupportingDocumentsRule],
        ]);

        try {
            // Check the Debit account_id
            $belongsToClient = IDORHelper::isAccountBelongToUser(Auth::user(), $request->get("debit_account_id"));

            if ($belongsToClient) {

                // Check the Credit account_id
                $belongsToClient = IDORHelper::isAccountBelongToUser(Auth::user(), $request->get("receiver_account_id"));

                if ($belongsToClient) {

                    $debit_account = Account::find($request->get("debit_account_id"));
                    $client = ClientHelper::getClientByAccountId($request->get("debit_account_id"));
                    $credit_account = Account::find($request->get("receiver_account_id"));
                    $currency = Currency::where('id', $debit_account->account_currency)->first();

                    // if account is not available alert it
                    if (!$credit_account || !$debit_account) {
                        return redirect(url()->previous())->with('error', "Account details not found.");
                    }

                    // check if same account is selected for transaction
                    if ($credit_account->id == $debit_account->id) {
                        return redirect(url()->previous())->with('error', "Please choose different accounts");
                    }

                    // check if debit account is having enough balance
                    $balance = Common::getAccountBalanceById($debit_account->id);

                    if ($request->get("amount") > $balance) {
                        return redirect(url()->previous())->with('error', "You don't have enough money in your account");
                    }

                    if ($currency) {
                        $this->validate($request, [
                            'amount' => 'required|numeric|min:' . $currency->min_amount . '|max:' . $currency->max_amount,
                        ]);
                    } else {
                        return redirect(url()->previous())->with('error', "Currency not found");
                    }

                    // Get client transaction
                    $dt_trn = TRRequest::find($request->get("id"));

                    // update debit transaction
                    $dt_trn->account_id = $debit_account->id;
                    $dt_trn->currency_id = $debit_account->account_currency;
                    $dt_trn->transaction_type = "Debit";
                    $dt_trn->transaction_nature = "Sell";
                    $dt_trn->crypto_amount = $request->get("amount");
                    $dt_trn->amount = 0;
                    $dt_trn->receiver_account_number = $credit_account->account_number;
                    $dt_trn->remarks = $request->get("remarks");
                    $dt_trn->status = "Pending";
                    $dt_trn->created_by = Auth::user()->id;

                    $dt_trn->save();

                    Common::uploadSupportingDocuments($request, $dt_trn, $client);


                    // generate OTP code and send it to user
                    EmailNotification::RequestOTP($dt_trn);

                    return redirect("client/crypto-transaction-requests/otp/" . $dt_trn->id);
                }
            }
        } catch (Exception $e) {
            Common::logError($e, __METHOD__, __LINE__);

            if (401 === $e->getCode()) {
                return back()->with('error', 'You are not authorized to update this transaction.');
            } else {
                return back()->with('error', 'Something went wrong, please try again later.');
            }
        }
    }

    public function withdraw(Request $request)
    {
        $this->validate($request, [
            'account_id' => 'required',
            'to_wallet_address' => 'required',
            'transaction_date' => 'required',
            'remarks' => 'required',
            'network' => 'required',
            'supporting_documents' => [new SupportingDocumentsRule],
        ]);

        try {
            // Check the Debit account_id
            $belongsToClient = IDORHelper::isAccountBelongToUser(Auth::user(), $request->get("account_id"));

            if ($belongsToClient) {

                $debit_account = Account::find($request->get("account_id"));
                $client = ClientHelper::getClientByAccountId($request->get("account_id"));
                $currency = Currency::where('id', $debit_account->account_currency)->first();

                // if account is not available alert it
                if (!$debit_account) {
                    return redirect(url()->previous())->with('error', "Account details not found.")->withInput();
                }

                // // check if debit account is having enough balance
                $balance = Common::getAccountBalanceById($debit_account->id);

                if ($request->get("amount") > $balance) {
                    return redirect(url()->previous())->with('error', "You don't have enough money in your account")->withInput();
                }

                $bitpaceHelper = new BitpaceMerchantApis();
                $bitpace_config =  $bitpaceHelper->getConfigs();
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

                if ($currency) {
                    $this->validate($request, [
                        'amount' => 'required|numeric|min:' . $currency->min_amount . '|max:' . $currency->max_amount,
                    ]);
                } else {
                    return redirect(url()->previous())->with('error', "Currency not found");
                }
                $existing_request = TRRequest::where('status', 'pending')->where('account_id', $debit_account->id)->where('transaction_nature', 'Withdraw')->first();

                if (!empty($existing_request)) {
                    return redirect(url()->previous())->with('error', "There is already withdraw request pending, please wait for approval and make new request again.")->withInput();
                }
                // create client transaction
                $dt_trn = new TRRequest();

                // create debit transaction
                $dt_trn->account_id = $debit_account->id;
                $dt_trn->currency_id = $debit_account->account_currency;
                $dt_trn->transaction_type = "Debit";
                $dt_trn->transaction_nature = "Withdraw";
                $dt_trn->transaction_date = Carbon::createFromFormat('d/m/Y', $request->get('transaction_date'))->format('Y-m-d H:i:s');
                $dt_trn->crypto_amount = $request->get("amount");
                $dt_trn->to_wallet_address = $request->get("to_wallet_address");
                $dt_trn->destination_tag = $request->get("destination_tag");
                $dt_trn->crypto_network = $request->get("network");
                $dt_trn->remarks = $request->get("remarks");
                $dt_trn->status = "Pending";
                $dt_trn->crypto_system = "Bitpace";
                $dt_trn->created_by = Auth::user()->id;

                $dt_trn->save();

                Common::uploadSupportingDocuments($request, $dt_trn, $client);
                // generate OTP code and send it to user
                EmailNotification::RequestOTP($dt_trn);

                return redirect("client/crypto-transaction-requests/otp/" . $dt_trn->id);
            }
        } catch (Exception $e) {
            Common::logError($e, __METHOD__, __LINE__);

            if (401 === $e->getCode()) {
                return back()->with('error', 'You are not authorized to make this transaction.');
            } else {
                return back()->with('error', 'Something went wrong, please try again later.');
            }
        }
    }

    public function updateWithdraw(Request $request)
    {
        $this->validate($request, [
            'account_id' => 'required',
            'to_wallet_address' => 'required',
            'transaction_date' => 'required',
            'remarks' => 'required',
            'supporting_documents' => [new SupportingDocumentsRule],
        ]);

        try {
            // Check the Debit account_id
            $belongsToClient = IDORHelper::isAccountBelongToUser(Auth::user(), $request->get("account_id"));

            if ($belongsToClient) {

                $debit_account = Account::find($request->get("account_id"));
                $client = ClientHelper::getClientByAccountId($request->get("account_id"));
                $currency = Currency::where('id', $debit_account->account_currency)->first();

                // if account is not available alert it
                if (!$debit_account) {
                    return redirect(url()->previous())->with('error', "Account details not found.");
                }

                // check if debit account is having enough balance
                $balance = Common::getAccountBalanceById($debit_account->id);

                if ($request->get("amount") > $balance) {
                    return redirect(url()->previous())->with('error', "You don't have enough money in your account");
                }

                if ($currency) {
                    $this->validate($request, [
                        'amount' => 'required|numeric|min:' . $currency->min_amount . '|max:' . $currency->max_amount,
                    ]);
                } else {
                    return redirect(url()->previous())->with('error', "Currency not found");
                }

                // Get client transaction
                $dt_trn = TRRequest::find($request->get("id"));

                // update debit transaction
                $dt_trn->account_id = $debit_account->id;
                $dt_trn->currency_id = $debit_account->account_currency;
                $dt_trn->transaction_type = "Debit";
                $dt_trn->transaction_nature = "Withdraw";
                $dt_trn->crypto_amount = $request->get("amount");
                $dt_trn->to_wallet_address = $request->get("to_wallet_address");
                $dt_trn->destination_tag = $request->get("destination_tag");
                $dt_trn->remarks = $request->get("remarks");

                $dt_trn->status = "Pending";
                $dt_trn->created_by = Auth::user()->id;

                $dt_trn->save();

                Common::uploadSupportingDocuments($request, $dt_trn, $client);


                // generate OTP code and send it to user
                EmailNotification::RequestOTP($dt_trn);

                return redirect("client/crypto-transaction-requests/otp/" . $dt_trn->id);
            }
        } catch (Exception $e) {
            Common::logError($e, __METHOD__, __LINE__);

            if (401 === $e->getCode()) {
                return back()->with('error', 'You are not authorized to make this transaction.');
            } else {
                return back()->with('error', 'Something went wrong, please try again later.');
            }
        }
    }
}
