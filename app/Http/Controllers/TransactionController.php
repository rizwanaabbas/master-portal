<?php

namespace App\Http\Controllers;

use App\Helpers\ChangeLogger;
use App\Helpers\ClientHelper;
use App\Helpers\Common;
use App\Helpers\EmailNotification;
use App\Helpers\FeeHelper;
use App\Helpers\IDORHelper;
use App\Helpers\LorumApis;
use App\Helpers\OpenpaydApis;
use App\Helpers\NeroDataHelper;
use App\Helpers\S3Helper;
use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\AccountTransactionCheque;
use App\Models\AccountTransactionNote;
use App\Models\Bank;
use App\Models\BankAccount;
use App\Models\Client;
use App\Models\ClientAccount;
use App\Models\ClientAccountCheque;
use App\Models\ClientAccountTransaction;
use App\Models\ClientBeneficiary;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Module;
use App\Models\MaintenanceFeeReport;
//use App\Models\OpenpaydBankBeneficiary;
//use App\Models\OpenpaydTransaction;
use App\Models\VirtualBankBeneficiary;
use App\Models\VirtualBankTransaction;
use App\Models\Sanctions;
use App\Models\SenderDetail;
use App\Models\TRRequest;
use App\Models\TRRequestDoc;
use App\Models\User;
use App\Models\TransactionMethod;
use App\Rules\SupportingDocumentsRule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Exception;

class TransactionController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:transaction list', ['only' => 'index']);
        $this->middleware('permission:add transaction', ['only' => ['addCheque', 'addTT', 'addInternal', 'storeCheque', 'storeTT', 'storeInternal']]);
        $this->middleware('permission:edit transaction', ['only' => 'editCheque', 'editTT', 'editInternal', 'updateCheque', 'updateTT', 'updateInternal']);
        $this->middleware('permission:delete transaction', ['only' => 'deleteTransaction']);
        $this->middleware('permission:maintenance fees list', ['only' => 'maintenanceFeesList']);
    }

    public function index(Request $request)
    {
        // if ($request->ajax()) {
        $transactions = AccountTransaction::select(
            "account_transactions.id",
            // "account_transactions.tr_request_id",
            "account_transactions.transaction_type",
            "account_transactions.transaction_nature",
            "account_transactions.amount",
            DB::raw("CONCAT_WS(' - ', transaction_methods.name, modules.name) as method"),
            "account.title as account_title",
            // "account_transactions.is_service_fee",
            // "account_transactions.is_trn_fee",
            "account_transactions.status",
            "account_transactions.is_fee_transaction",
            "account_transactions.tr_request_id",
            "account_transactions.parent_trn_id",
            "account.account_number",
            "trn_request.is_exchange",
            "account_transactions.transaction_date",
            "user.name as creator",
            "approver_user.name as approver",
            "reviewer_user.name as reviewer"
        )
            ->where("account_transactions.is_crypto", false)
            ->leftJoin("accounts as account", "account.id", "=", "account_transactions.account_id")
            ->leftJoin("users as user", "account_transactions.created_by", "=", "user.id")
            ->leftJoin("users as approver_user", "account_transactions.approved_by", "=", "approver_user.id")
            ->leftJoin("transaction_details as trn_details", "account_transactions.tr_request_id", "=", "trn_details.trn_id")
            ->leftJoin("account_transaction_requests as trn_request", "account_transactions.tr_request_id", "=", "trn_request.id")
            ->leftJoin("users as reviewer_user", "trn_details.user_id", "=", "reviewer_user.id")
            ->leftJoin("transaction_methods", "transaction_methods.id", "=", "account_transactions.transaction_method_id")
            ->leftJoin("modules", "modules.id", "=", "transaction_methods.module_id")
            ->whereIn("account_transactions.transaction_nature", ["Cheque", "TT", "Internal"])
            ->where("account_transactions.status", "Approved")
            ->orderBy('account_transactions.updated_at', 'desc');

        $perPage = $request->get("per_page");

        if ($request->get('search_string')) {
            $transactions->where(function ($query) use ($request) {
                $query->where('account.account_number', 'LIKE', '%' . $request->get('search_string') . '%')
                    ->orwhere('account.title', 'LIKE', '%' . $request->get('search_string') . '%')
                    ->orwhere('account_transactions.tr_request_id', 'LIKE', '%' . $request->get('search_string') . '%')
                    ->orwhere('account_transactions.transaction_type', 'LIKE', '%' . $request->get('search_string') . '%')
                    ->orwhere('account_transactions.transaction_nature', 'LIKE', '%' . $request->get('search_string') . '%')
                    ->orwhere('account_transactions.amount', 'LIKE', '%' . $request->get('search_string') . '%')
                    ->orwhere('account_transactions.transaction_date', 'LIKE', '%' . $request->get('search_string') . '%')
                    ->orwhere('user.name', 'LIKE', '%' . $request->get('search_string') . '%')
                    ->orwhere('approver_user.name', 'LIKE', '%' . $request->get('search_string') . '%');
            })
                ->whereIn("account_transactions.transaction_nature", ["Cheque", "TT", "Internal"]);
        }

        if ($perPage == -1 || ($request->get('search_string') && $transactions->count() < 10)) {
            $perPage = $transactions->count();
        } elseif (!$request->get("per_page")) {
            $perPage = 10;
        }

        $transactions = $transactions->paginate($perPage);

        return view("finance-management.transactions.list", compact('transactions', 'request'));
    }

    public function musdTransactionsList(Request $request)
    {
        // if ($request->ajax()) {
        $transactions = AccountTransaction::select(
            "account_transactions.id",
            "account_transactions.transaction_type",
            "account_transactions.transaction_nature",
            "account_transactions.tr_request_id",
            "account_transactions.amount",
            "account.title as account_title",
            "account_transactions.status",
            "account.account_number",
            "account_transactions.transaction_date",
            "user.name as creator",
            "approver_user.name as approver"
        )
            ->where("account_transactions.is_crypto", false)
            ->leftJoin("accounts as account", "account.id", "=", "account_transactions.account_id")
            ->leftJoin("users as user", "account_transactions.created_by", "=", "user.id")
            ->leftJoin("users as approver_user", "account_transactions.approved_by", "=", "approver_user.id")
            ->where("account_transactions.transaction_nature", "MUSD Reserve")
            ->where("account_transactions.status", "Approved")
            ->orderBy('account_transactions.id', 'desc');

        $perPage = $request->get("per_page");

        if ($request->get('search_string')) {
            $transactions->where(function ($query) use ($request) {
                $query->where('account_number', 'LIKE', '%' . $request->get('search_string') . '%')
                    ->orwhere('title', 'LIKE', '%' . $request->get('search_string') . '%')
                    ->orwhere('tr_request_id', 'LIKE', '%' . $request->get('search_string') . '%')
                    ->orwhere('transaction_type', 'LIKE', '%' . $request->get('search_string') . '%')
                    ->orwhere('amount', 'LIKE', '%' . $request->get('search_string') . '%')
                    ->orwhere('transaction_date', 'LIKE', '%' . $request->get('search_string') . '%')
                    ->orwhere('user.name', 'LIKE', '%' . $request->get('search_string') . '%')
                    ->orwhere('approver_user.name', 'LIKE', '%' . $request->get('search_string') . '%');
            })
                ->where("transaction_nature", "MUSD Reserve");
        }

        if ($perPage == -1 || ($request->get('search_string') && $transactions->count() < 10)) {
            $perPage = $transactions->count();
        } elseif (!$request->get("per_page")) {
            $perPage = 10;
        }

        $transactions = $transactions->paginate($perPage);

        // Calculate the balance for the bank for which the is_reserve_bank flag is turned on.
        $reservesBankAccount = Account::whereHas('bank', function ($query) {
                $query->where('is_reserve_bank', true);
            })->first();
        
        $reservesCredit = AccountTransaction::where("status", "Approved")
            ->where("account_id", $reservesBankAccount->id)
            ->where("transaction_type", "Credit")
            ->sum("amount");

        $reservesDebit = AccountTransaction::where("status", "Approved")
            ->where("account_id", $reservesBankAccount->id)
            ->where("transaction_type", "Debit")
            ->sum("amount");

        $reservesBalance = $reservesCredit - $reservesDebit;

        return view("finance-management.transactions.musd-transactions-list", compact('transactions', 'request', 'reservesBalance'));
    }

    public function maintenanceFeesList(Request $request)
    {
        // if ($request->ajax()) {
        $transactions = AccountTransaction::select(
            "account_transactions.id",
            "account_transactions.transaction_type",
            "account_transactions.transaction_nature",
            "account_transactions.tr_request_id",
            "account_transactions.amount",
            "account.title as account_title",
            "account_transactions.status",
            "account.account_number",
            "account_transactions.transaction_date",
            "user.name as creator",
            "approver_user.name as approver"
        )
            ->where("account_transactions.is_crypto", false)
            ->leftJoin("accounts as account", "account.id", "=", "account_transactions.account_id")
            ->leftJoin("users as user", "account_transactions.created_by", "=", "user.id")
            ->leftJoin("users as approver_user", "account_transactions.approved_by", "=", "approver_user.id")
            ->where("account_transactions.transaction_nature", "Maintenance Fee")
            ->where("account_transactions.status", "Approved")
            ->orderBy('account_transactions.id', 'desc');

        $perPage = $request->get("per_page");

        if ($request->get('search_string')) {
            $transactions->where(function ($query) use ($request) {
                $query->where('account_number', 'LIKE', '%' . $request->get('search_string') . '%')
                    ->orwhere('title', 'LIKE', '%' . $request->get('search_string') . '%')
                    ->orwhere('tr_request_id', 'LIKE', '%' . $request->get('search_string') . '%')
                    ->orwhere('transaction_type', 'LIKE', '%' . $request->get('search_string') . '%')
                    ->orwhere('amount', 'LIKE', '%' . $request->get('search_string') . '%')
                    ->orwhere('transaction_date', 'LIKE', '%' . $request->get('search_string') . '%')
                    ->orwhere('user.name', 'LIKE', '%' . $request->get('search_string') . '%')
                    ->orwhere('approver_user.name', 'LIKE', '%' . $request->get('search_string') . '%');
            })
                ->where("transaction_nature", "Maintenance Fee");
        }

        if ($perPage == -1 || ($request->get('search_string') && $transactions->count() < 10)) {
            $perPage = $transactions->count();
        } elseif (!$request->get("per_page")) {
            $perPage = 10;
        }

        $transactions = $transactions->paginate($perPage);

        return view("finance-management.transactions.maintenance-fees-list", compact('transactions', 'request'));
    }

    public function backOfficeReviewedTransactions(Request $request)
    {
        if ($request->ajax()) {
            $transactions = AccountTransaction::select(
                "account_transactions.id",
                "account_transactions.tr_request_id",
                "account_transactions.transaction_type",
                "account_transactions.transaction_nature",
                "account_transactions.amount",
                "account.title",
                "account_transactions.status",
                "account.account_number",
                "account_transactions.transaction_date",
                "trn_request.is_exchange",
                "user.name",
                "reviewer_user.name as reviewer"
            )
                ->where("account_transactions.is_crypto", false)
                ->leftJoin("accounts as account", "account.id", "=", "account_transactions.account_id")
                ->leftJoin("users as user", "account_transactions.created_by", "=", "user.id")
                ->whereHas('requestedTrn', function ($requestedTrn) {
                    $requestedTrn->whereHas('transactionDetails', function ($transactionDetails) {
                        $transactionDetails->whereHas('reviewer', function ($reviewer) {
                            $reviewer->hasRole('finance');
                        });
                    });
                })
                ->leftJoin("transaction_details as trn_details", "account_transactions.tr_request_id", "=", "trn_details.trn_id")
                ->leftJoin("account_transaction_requests as trn_request", "account_transactions.tr_request_id", "=", "trn_request.id")
                ->leftJoin("users as reviewer_user", "trn_details.user_id", "=", "reviewer_user.id")
                // ->rightJoin("transaction_details as trn_details", "account_transactions.tr_request_id", "=", "trn_details.trn_id")
                // ->leftJoin("model_has_roles as model_role", "trn_details.user_id", "=", "model_role.model_id")
                // ->leftJoin("roles as role", "model_role.role_id", "=", "role.id")
                // ->where("role.name", "finance")
                ->whereIn("account_transactions.transaction_nature", ["Cheque", "TT", "Internal", "Maintenance Fee"])
                ->where("account_transactions.status", "Pending");

            $table = "account_transactions";
            $column = "is_for_client";

            if (Schema::hasColumn($table, $column)) {
                $transactions = $transactions->where("{$table}.{$column}", false);
            }

            $transactions = $transactions->orderBy('account_transactions.created_at', 'desc')->get();

            return Datatables::of($transactions)->make(true);
        }

        $transactions = [];
        return view("finance-management.transactions.list-reviewed-only-transactions", compact('transactions'));
    }

    public function backOfficePendingTransactions(Request $request)
    {
        $needle = "pending";

        if ($request->ajax()) {
            $transactions = AccountTransaction::select(
                "account_transactions.id",
                "account_transactions.tr_request_id",
                "account_transactions.transaction_type",
                "account_transactions.transaction_nature",
                "account_transactions.amount",
                DB::raw("CONCAT_WS(' - ', transaction_methods.name, modules.name) as method"),
                "account.title",
                "account_transactions.status",
                "account.account_number",
                "account_transactions.transaction_date",
                "trn_request.is_exchange",
                "user.name as creator",
                "reviewer_user.name as reviewer",

            )
                ->where("account_transactions.is_crypto", false)
                ->leftJoin("accounts as account", "account.id", "=", "account_transactions.account_id")
                ->leftJoin("users as user", "account_transactions.created_by", "=", "user.id")
                ->leftJoin("transaction_details as trn_details", "account_transactions.tr_request_id", "=", "trn_details.trn_id")
                ->leftJoin("account_transaction_requests as trn_request", "account_transactions.tr_request_id", "=", "trn_request.id")
                ->leftJoin("users as reviewer_user", "trn_details.user_id", "=", "reviewer_user.id")
                ->leftJoin("transaction_methods", "transaction_methods.id", "=", "account_transactions.transaction_method_id")
                ->leftJoin("modules", "modules.id", "=", "transaction_methods.module_id");
            //dd($transactions->toSql());

            if ($request->get('reviewed_only')) {
                $needle = "reviewed-only";
                // $transactions = $transactions->whereHas('requestedTrn', function ($requestedTrn) {
                //     $requestedTrn->transactionDetails->reviewer->hasAnyRole('finance', 'finance manager', 'super admin');
                // });

                $transactions = $transactions->whereHas('requestedTrn', function ($requestedTrn) {
                    $requestedTrn->whereHas('transactionDetails', function ($transactionDetails) {
                        $transactionDetails->whereHas('reviewer.roles', function ($query) {
                            $query->where('name', 'finance');
                        });
                    });
                });

                // $transactions = $transactions->rightJoin("transaction_details as trn_details", "account_transactions.tr_request_id", "=", "trn_details.trn_id")
                //     ->leftJoin("model_has_roles as model_role", "trn_details.user_id", "=", "model_role.model_id")
                //     ->leftJoin("roles as role", "model_role.role_id", "=", "role.id")
                //     ->where("role.name", "finance");
            }

            $table = "account_transactions";
            $column = "is_for_client";

            if (Schema::hasColumn($table, $column)) {
                $transactions = $transactions->where("{$table}.{$column}", false);
            }

            $transactions = $transactions->whereIn("account_transactions.transaction_nature", ["Cheque", "TT", "Internal", "Maintenance Fee"])
                ->where("account_transactions.status", "Pending")
                ->orderBy('account_transactions.created_at', 'desc')
                ->get();

            return Datatables::of($transactions)->make(true);
        }

        $transactions = [];
        return view("finance-management.transactions.list-" . $needle . "-transactions", compact('transactions'));
    }

    public function backOfficeRejectedTransactions(Request $request)
    {
        if ($request->ajax()) {
            $transactions = AccountTransaction::select(
                "account_transactions.id",
                "account_transactions.tr_request_id",
                "account_transactions.transaction_type",
                "account_transactions.transaction_nature",
                "account_transactions.amount",
                "account.title",
                "account_transactions.status",
                "account.account_number",
                "account_transactions.transaction_date",
                'account_transactions.reject_reason',
                "trn_request.is_exchange",
                "user.name",
                "approver_user.name as approver",
                "rejector_user.name as rejector"
            )
                ->where("account_transactions.is_crypto", false)
                ->leftJoin("accounts as account", "account.id", "=", "account_transactions.account_id")
                ->leftJoin("users as user", "account_transactions.created_by", "=", "user.id")
                ->leftJoin("account_transaction_requests as trn_request", "account_transactions.tr_request_id", "=", "trn_request.id")
                ->leftJoin("users as approver_user", "account_transactions.approved_by", "=", "approver_user.id")
                ->leftJoin("users as rejector_user", "account_transactions.rejected_by", "=", "rejector_user.id")
                ->whereIn("account_transactions.transaction_nature", ["Cheque", "TT", "Internal", "Maintenance Fee"])
                ->where("account_transactions.status", "Rejected");

            $table = "account_transactions";
            $column = "is_for_client";

            if (Schema::hasColumn($table, $column)) {
                $transactions = $transactions->where("{$table}.{$column}", false);
            }

            $transactions = $transactions->orderBy('account_transactions.created_at', 'desc')->get();

            return Datatables::of($transactions)->make(true);
        }

        $transactions = [];
        return view("finance-management.transactions.list-pending-transactions", compact('transactions'));
    }

    public function indexbk()
    {
        $transactions = AccountTransaction::orderBy('created_at', 'desc')->get();
        return view("finance-management.transactions.list", compact('transactions'));
    }

    public function getAccountBalance(Request $request)
    {
        try {

            # Security check
            # If the client is calling account balance, we need to check if the account belongs to the logged in client
            $belongsToClient = IDORHelper::isAccountBelongToUser(Auth::user(), $request->get("account_id"));

            if ($belongsToClient) {
                if ($request->get("account_id") > 0) {
                    $source = '';
                    if (!empty($request->get("source"))) {
                        $source = $request->get("source");
                    }
                    $account = Account::find($request->get("account_id"));
                    $balance = Common::getAccountBalanceById($request->get("account_id"));
                    $cryptoAccounts = null;
                    $fiatAccounts = null;

                    if ($request->get("needCryptoAccounts")) {

                        $cryptoCurrencies = Currency::where("is_active", true)->where("is_crypto", true)->select("id")->get();

                        if ($account->type == "bank") {
                            $cryptoAccounts = Account::where("type", "bank")->where("bank_id", $account->bank_id)
                                ->whereIn("account_currency", $cryptoCurrencies)
                                ->where("is_active", true)
                                ->select("id", "title", "account_number", "account_currency")
                                ->get();
                        } else {
                            $cryptoAccounts = Account::where("client_id", $account->client->id)
                                ->whereIn("account_currency", $cryptoCurrencies)
                                ->where("is_active", true)
                                ->select("id", "title", "account_number", "account_currency")
                                ->get();
                        }
                    }
                    //echo $request->get("needFiatAccounts");
                    if ($request->get("needFiatAccounts")) {
                        $fiatCurrencies = Currency::where("is_active", true)
                            ->where("is_crypto", false);

                        if ($source == 'crypto') {
                            //$fiatCurrencies->where('short','EUR');
                            $fiatCurrencies->whereIn("short", array("EUR", "USD"));
                        }
                        $fiatCurrencies->select("id")->get();
                        if ($account->type == "bank") {
                            $fiatAccounts = Account::where("type", "bank")->where("bank_id", $account->bank_id)
                                ->whereIn("account_currency", $fiatCurrencies)
                                ->where("is_active", true)
                                ->select("id", "title", "account_number", "account_currency")
                                ->get();
                        } else {

                            $fiatAccounts = Account::where("client_id", $account->client->id)
                                ->whereIn("account_currency", $fiatCurrencies)
                                ->where("is_active", true)
                                ->select("id", "title", "account_number", "account_currency")
                                ->get();
                        }
                    }

                    return [
                        'formatted_balance' => number_format($balance, strlen(substr(strrchr($balance, "."), 1))),
                        'balance' => round($balance, 2),
                        'raw_balance' => $balance,
                        'currency_id' => $account->account_currency,
                        'currency_short' => $account->currency->short,
                        'min_currency_limit' => $account->currency->min_amount ? $account->currency->min_amount : 1,
                        'max_currency_limit' => $account->currency->max_amount ? $account->currency->max_amount : -1,
                        'crypto_accounts' => $cryptoAccounts,
                        'fiat_accounts' => $fiatAccounts
                    ];
                } else {
                    return [
                        'formatted_balance' => 0,
                        'balance' => 0,
                        'raw_balance' => 0,
                    ];
                }
            }
        } catch (Exception $e) {
            Common::logError($e, __METHOD__, __LINE__);

            return [
                'formatted_balance' => 0,
                'balance' => 0,
            ];
        }
    }

    public function addCheque()
    {
        $currencies = Currency::where("is_active", true)->where("is_crypto", false)->select("id")->get();
        $accounts = Account::where("is_active", true)->whereIn("account_currency", $currencies)
            ->where(function ($query) {
                $query->whereHas("bank", function ($query) {
                    $query->where("is_reserve_bank", false);
                })
                    ->orWhereHas("client", function ($query) {
                        $query->where("status", "Approved");
                    });
            })
            ->get();

        return view("finance-management.transactions.new-cheque", compact('accounts'));
    }

    private function isBelowToday($date)
    {
        $today = Carbon::today();
        $ndate = Carbon::createFromFormat('d/m/Y h:m:s', $date . " 00:00:00");

        return $today->gte($ndate);
    }

    public function storeCheque(Request $request)
    {
        $this->validate($request, [
            'account_id' => 'required|integer',
            'amount' => 'required|numeric|min:0',
            'transaction_date' => 'required',
            'transaction_type' => 'required',
            'remarks' => 'required',
            'cheque_bank' => 'required',
            'in_fav_of' => 'required',
            'cheque_number' => 'required',
            'cheque_date' => 'required',
            'supporting_documents' => [new SupportingDocumentsRule]
        ]);

        $account = Account::find($request->get("account_id"));
        $client = ClientHelper::getClientByAccountId($request->get('account_id'));

        // if account is not available alert it
        if (!$account) {
            return redirect("/finance-management/transactions/new/cheque")->with('error', "Account details not found.");
        }

        if ($account->is_active == 0) {
            return redirect("/finance-management/transactions/new/cheque")->with('error', "Something went wrong. Please try again later.");
        }

        // restrict future date
        if (!self::isBelowToday($request->get("transaction_date"))) {
            return redirect("/finance-management/transactions/new/cheque")->with('error', "Please provide past or current date.");
        }

        $balance = Common::getAccountBalanceById($request->get("account_id"));

        if ($request->get("transaction_type") == "Debit") {
            if ($balance < $request->get("amount")) {
                return redirect("/finance-management/transactions/new/cheque")->with('error', "Insufficient balance.");
            }
        }

        $transaction_date = Carbon::createFromFormat("d/m/Y", $request->get("transaction_date"));

        $transaction_date->format("Y-m-d H:i:s");

        // create transaction request
        $trn_request = new TRRequest();

        $trn_request->account_id = $request->get("account_id");
        $trn_request->currency_id = $account->account_currency;
        $trn_request->transaction_type = $request->get("transaction_type");
        $trn_request->transaction_nature = "Cheque";
        $trn_request->transaction_date = $transaction_date;
        $trn_request->amount = $request->get("amount");
        $trn_request->remarks = $request->get("remarks");
        $trn_request->status = "Pending";
        $trn_request->created_by = Auth::user()->id;

        $trn_request->save();

        Common::uploadSupportingDocuments($request, $trn_request, $client);

        // create client transaction
        $trn = new AccountTransaction();

        $trn->account_id = $request->get("account_id");
        $trn->tr_request_id = $trn_request->id;
        $trn->currency_id = $account->account_currency;
        $trn->transaction_type = $request->get("transaction_type");
        $trn->transaction_nature = "Cheque";
        $trn->transaction_date = $transaction_date;
        $trn->amount = $request->get("amount");
        $trn->remarks = $request->get("remarks");
        $trn->status = "Pending";
        $trn->created_by = Auth::user()->id;

        $trn->save();

        // store cheque
        $cheque = new AccountTransactionCheque();

        $cheque->transaction_id = $trn->id; // link client transaction with cheque
        $cheque->currency_id = $account->account_currency;
        $cheque->transaction_id = $trn->id;
        $cheque->in_fav_of = $request->get('in_fav_of');
        $cheque->cheque_number = $request->get('cheque_number');
        $cheque->cheque_date = Carbon::createFromFormat('d/m/Y', $request->get('cheque_date'))->format('Y-m-d');
        $cheque->cheque_bank = $request->get('cheque_bank');

        $cheque->save();

        $trn_request->link_tr_id = $trn->id;
        $trn_request->save();

        Common::notifyComplianceTeamNeroData($trn_request);

        return redirect("/finance-management/pending-transactions")->with('success', "Cheque details added successfully.");


        // generate email notification to line manager

    }

    public function editCheque($id)
    {
        $trn = AccountTransaction::find($id);
        $currencies = Currency::where("is_active", true)->where("is_crypto", false)->select("id")->get();
        $accounts = Account::whereIn("account_currency", $currencies)->where("is_active", true);
        $transactionRequest = TRRequest::find($trn->tr_request_id);
        if ($trn->status != "Approved") {
            $accounts->where("is_active", true);
        }

        $accounts = $accounts->get();

        $changes = self::getChanges($trn);

        $trnRequest = TRRequest::find($trn->tr_request_id);

        if ($trnRequest) {
            $docs = $trnRequest->docs;
        } else {
            $docs = [];
        }
        $rulesText = '';
        $portalTransactionsUrl = '';
        $neroDataTransactionId = 0;
        $amlNoteTexts = null;

        if (Common::isModuleEnabled('NeroData')) {
            $neroDataHelper = new NeroDataHelper();
            $rulesText = $neroDataHelper->getNeroDataLogText($transactionRequest->id);

            $notesJson = DB::table('crypto_transactions_log')
                ->where('transaction_id', $transactionRequest->id)
                ->orderByDesc('id')
                ->limit(1)
                ->value(DB::raw(
                    "COALESCE(JSON_EXTRACT(details, '$.payload.notes'), JSON_EXTRACT(details, '$.response.notes'))"
                ));
            $notesArray = json_decode($notesJson, true);
            if (is_string($notesArray)) {
                // handles cases where DB returns JSON as a quoted string
                $notesArray = json_decode($notesArray, true);
            }

            $amlNoteTexts = collect($notesArray ?: [])
                ->map(fn($n) => is_array($n) ? ($n['text'] ?? null) : $n)
                ->filter()
                ->values();
            $neroDataTransactionId = $neroDataHelper->getNeroDataTransactionIdByTrId($transactionRequest->id);
            $portalTransactionsUrl = $neroDataHelper->getPortalTransactionsUrl();
        }

        return view("finance-management.transactions.edit-cheque", compact(
            'trn',
            'changes',
            'accounts',
            'docs',
            'transactionRequest',
            'rulesText',
            'portalTransactionsUrl',
            'neroDataTransactionId',
            'amlNoteTexts'
        ));
    }

    public function editChequeWithFees($id)
    {
        $transaction = AccountTransaction::find($id);
        $currencies = Currency::where("is_active", true)->where("is_crypto", false)->select("id")->get();
        $accounts = Account::whereIn("account_currency", $currencies)->where("is_active", true);
        $transactionRequest = TRRequest::find($transaction->tr_request_id);
        $serviceFeeBankAccount = Bank::where('is_service_fee_bank', true)->first()->accounts->where('account_currency', $transactionRequest->currency_id)->first()->id;
        $trnFeeBankAccount = Bank::where('is_trn_fee_bank', true)->first()->accounts->where('account_currency', $transactionRequest->currency_id)->first()->id;

        if ($transaction->status != "Approved") {
            $accounts->where("is_active", true);
        }

        $accounts = $accounts->get();

        $changes = self::getChanges($transaction);

        if ($transactionRequest) {
            $docs = $transactionRequest->docs;
        } else {
            $docs = [];
        }

        $client = ClientHelper::getClientByAccountId($transactionRequest->account_id);
        $clientAccounts = false;

        return view("finance-management.transactions.edit-cheque", compact('transaction', 'changes', 'accounts', 'docs', 'transactionRequest', 'clientAccounts', 'serviceFeeBankAccount', 'trnFeeBankAccount'));
    }

    public function updateCheque(Request $request)
    {
        $this->validate($request, [
            'account_id' => 'required',
            'amount' => 'required|numeric|min:0',
            'transaction_date' => 'required',
            'transaction_type' => 'required',
            'remarks' => 'required',
            'cheque_bank' => 'required',
            'in_fav_of' => 'required',
            'cheque_number' => 'required',
            'cheque_date' => 'required',
            'supporting_documents' => [new SupportingDocumentsRule]
        ]);

        $account = Account::find($request->get("account_id"));
        $client = ClientHelper::getClientByAccountId($request->get("account_id"));

        // if account is not available alert it
        if (!$account) {
            return redirect("/finance-management/transactions/edit/cheque/" . $request->get("id"))->with('error', "Account details not found.");
        }

        if ($account->is_active == 0) {
            return redirect("/finance-management/transactions/edit/cheque/" . $request->get("id"))->with('error', "Something went wrong. Please try again later.");
        }

        // restrict future date
        if (!self::isBelowToday($request->get("transaction_date"))) {
            return redirect("/finance-management/transactions/edit/cheque/" . $request->get("id"))->with('error', "Please provide past or current date.");
        }

        $trn = AccountTransaction::find($request->get("id"));

        if ($trn->status != 'Approved') {
            $balance = Common::getAccountBalanceById($request->get("account_id"));

            if ($request->get("transaction_type") == "Debit") {
                if ($balance < $request->get("amount")) {
                    return redirect("/finance-management/transactions/edit/cheque/" . $request->get("id"))->with('error', "Insufficient balance.");
                }
            }
        }

        // update client transaction
        $transaction_date = Carbon::createFromFormat("d/m/Y", $request->get("transaction_date"));

        if ($request->get("transaction_type") == "Debit") {
            $transaction_date->addSeconds(10);
        }

        $transaction_date->format("Y-m-d H:i:s");

        $trn->account_id = $request->get("account_id");
        $trn->currency_id = $account->account_currency;
        $trn->transaction_type = $request->get("transaction_type");
        $trn->transaction_nature = "Cheque";
        $trn->transaction_date = $transaction_date;
        $trn->amount = $request->get("amount");
        $trn->remarks = $request->get("remarks");
        $trn->updated_by = Auth::user()->id;

        $trn->save();

        Common::uploadSupportingDocuments($request, $trn->requestedTrn, $client);

        // store cheque
        $cheque = $trn->cheque;

        $cheque->transaction_id = $trn->id; // link client transaction with cheque
        $cheque->currency_id = $account->account_currency;
        $cheque->transaction_id = $trn->id;
        $cheque->in_fav_of = $request->get('in_fav_of');
        $cheque->cheque_number = $request->get('cheque_number');
        $cheque->cheque_date = Carbon::createFromFormat('d/m/Y', $request->get('cheque_date'))->format('Y-m-d');
        $cheque->cheque_bank = $request->get('cheque_bank');

        $cheque->save();

        if (!Common::canUserMakeChange()) {
            return redirect("finance-management/transactions/edit/cheque/" . $trn->id)->with('success', "Changes has been submitted to your line manager for approval.");
        } else {
            return redirect("/finance-management/transactions")->with('success', "Cheque details updated successfully.");
        }
    }

    public function deleteTransaction(Request $request)
    {
        $trn = AccountTransaction::find($request->get("trn_id"));

        $statusMapping = [
            'Approved' => '/finance-management/transactions',
            'Pending' => '/finance-management/pending-transactions',
            'Initial Review' => '/finance-management/initial-review-transactions',
            'Rejected' => '/finance-management/rejected-transactions'
        ];

        // dd($trn->childTransactions($trn->id));

        DB::beginTransaction();
        try {
            if ($trn) {
                // restrict deletion if not manager and transaction status is approved
                if ($trn->status == "Approved") {
                    if (!Auth::user()->hasRole(["finance manager", "operations manager", "super admin"])) {
                        return redirect($statusMapping[$trn->status])->with('error', "You don't have permission to delete the transaction.");
                    }

                    if ($trn->childTransactions($trn->id)->count() > 0) {
                        foreach ($trn->childTransactions($trn->id) as $child) {
                            $child->is_deleted = true;
                            $child->save();
                        }
                    }
                }

                switch ($trn->transaction_nature) {
                    case 'Cheque':
                        $trn->cheque->is_deleted = true;
                        $trn->cheque->save();
                        break;
                }

                if ($trn->requestedTrn) {
                    $trn->requestedTrn->is_deleted = true;
                    $trn->requestedTrn->save();
                }

                if ($trn->linkedTrn) {
                    $trn->linkedTrn->is_deleted = true;
                    $trn->linkedTrn->save();
                }

                // delete documents if any
                if ($trn->docs && $trn->docs->count() > 0) {
                    foreach ($trn->docs as $doc) {
                        // S3Helper::fileDelete($doc->url);
                        $doc->is_deleted = true;
                        $doc->save();
                    }
                }

                $trn->is_deleted = true;
                $trn->save();

                DB::commit();

                return redirect($statusMapping[$trn->status])->with('success', "Transaction has been deleted successfully.");
            } else {
                DB::rollBack();

                Log::error('The transaction, with Transaction ID: ' . $request->get("trn_id") . ', has not been found. | Method: ' . __METHOD__ . ' | Line#: ' . __LINE__);

                return redirect($statusMapping[$trn->status])->with('error', "Internal server error, please try again later.");
            }
        } catch (Exception $e) {
            Log::error('------------------------------------------------');
            Log::error($e->getMessage() . ' | Line: ' . $e->getLine());
            Log::error($e->getTraceAsString());
            Log::error('------------------------------------------------');

            DB::rollback();

            return redirect($statusMapping[$trn->status])->with('error', "Internal server error, please try again later.");
        }
    }

    public function deleteMaintenanceFeeTransactions(Request $request)
    {
        $trn = AccountTransaction::find($request->get("trn_id"));
        $statusMapping = [
            'Approved' => '/finance-management/transactions',
            'Pending' => '/finance-management/pending-transactions',
            'Initial Review' => '/finance-management/initial-review-transactions',
            'Rejected' => '/finance-management/rejected-transactions'
        ];

        DB::beginTransaction();
        try {
            if ($trn) {
                // restrict deletion if not manager and transaction status is approved
                if (!Auth::user()->hasRole(["finance manager", "operations manager", "super admin"])) {
                    if ($trn->status == "Approved") {
                        DB::rollBack();

                        return redirect($statusMapping[$trn->status])->with('error', "You don't have permission to delete the transaction.");
                    }
                }

                AccountTransaction::where('maintenance_fee_unique_id', $trn->maintenance_fee_unique_id)->update(['is_deleted' => true]);
                $trReq = TRRequest::where('maintenance_fee_unique_id', $trn->maintenance_fee_unique_id)->first();
                if ($trReq) {
                    $trReq->is_deleted = true;
                    $trReq->save();
                }

                $report = MaintenanceFeeReport::where('unique_id', $trn->maintenance_fee_unique_id)->first();

                // delete documents if any
                if ($trn->docs && $trn->docs->count() > 0) {
                    foreach ($trn->docs as $doc) {
                        // S3Helper::fileDelete($doc->url);
                        $doc->is_deleted = true;
                        $doc->save();
                    }
                }

                if ($report) {
                    $report->account_id = null;
                    $report->amount = null;
                    $report->is_deleted = true;
                    $report->have_paid = 0;

                    $report->save();
                }
                DB::commit();

                return redirect(url("finance-management/transaction/maintenance-fees"))->with('success', "Transaction has been deleted successfully.");
            } else {
                DB::rollBack();

                Log::error('The transaction, with Transaction ID: ' . $request->get("trn_id") . ', has not been found. | Method: ' . __METHOD__ . ' | Line#: ' . __LINE__);

                return redirect($statusMapping[$trn->status])->with('error', "Internal server error, please try again later.");
            }
        } catch (Exception $e) {
            Log::error('------------------------------------------------');
            Log::error($e->getMessage() . ' | Line: ' . $e->getLine());
            Log::error($e->getTraceAsString());
            Log::error('------------------------------------------------');

            DB::rollback();

            return redirect($statusMapping[$trn->status])->with('error', "Internal server error, please try again later.");
        }
    }

    public function addTT()
    {
        $countries = Country::get();
        $currencies = Currency::where("is_crypto", false)->select("id", "name", "short", 'virtual_iban_provider')->get();

        $currencies_ids = Currency::where("is_crypto", false)->select("id")->get();
        $accounts = Account::where("is_active", true)->whereIn("account_currency", $currencies_ids)
            ->where(function ($query) {
                $query->whereHas("bank", function ($query) {
                    $query->where("is_reserve_bank", false);
                })
                    ->orWhereHas("client", function ($query) {
                        $query->where("status", "Approved");
                    });
            })
            ->get();

        $transaction_methods = Common::getTransactionMethods();

        return view("finance-management.transactions.new-tt", compact('accounts', 'currencies', 'countries', 'transaction_methods'));
    }

    public function storeTT(Request $request)
    {
        $account = Account::find($request->get("account_id"));

        if (!$account) {
            return redirect("/finance-management/transactions/new/tt")->with('error', "Account details not found.");
        }

        $isVirtual = $account->virtual_bank_account_id;

        $rules = [
            'account_id' => 'required',
            'amount' => 'numeric|gt:0',
            'transaction_date' => 'required',
            'transaction_time' => 'required',
            'transaction_type' => 'required',
            'remarks' => 'required',
            'supporting_documents' => [new SupportingDocumentsRule]
        ];

        if ($request->get('transaction_type') == 'Credit' && !$isVirtual) {
            $rules = array_merge($rules, [
                'sender_name' => 'required|string|max:255',
                'sender_address' => 'required|string|max:255',
                'sender_city' => 'required|string|max:255',
                'sender_country' => 'required|integer',
                'sending_bank_name' => 'required|string|max:255',
                'sending_bank_address' => 'required|string|max:255',
                'sending_bank_city' => 'required|string|max:255',
                'sending_bank_country' => 'required|integer',
                'sending_bank_swift' => 'required|string|max:255',
                'intermediary_bank_name' => 'nullable|string|max:255',
                'intermediary_bank_swift' => 'nullable|string|max:255',
            ]);
        }

        $this->validate($request, $rules);

        $client = ClientHelper::getClientByAccountId($request->get('account_id'));
        $amount = $request->get("amount");

        if ($account->is_active == 0) {
            return redirect("/finance-management/transactions/new/tt")->with('error', "Something went wrong. Please try again later.");
        }

        // restrict future date
        if (!self::isBelowToday($request->get("transaction_date"))) {
            return redirect("/finance-management/transactions/new/tt")->with('error', "Please provide past or current date.");
        }

        $balance = Common::getAccountBalanceById($request->get("account_id"));

        if ($request->get("transaction_type") == "Debit") {
            if ($request->get('is_zeroize')) {
                $amount = $balance;
            } elseif ($balance < $amount) {
                return redirect("/finance-management/transactions/new/internal")->with('error', "Insufficient balance.");
            }
        }

        // validate time
        $time = Common::validateTime($request->get("transaction_time"));
        if (!$time) {
            return redirect("/finance-management/transactions/new/tt")->with('error', "Please provide a valid time in 24 hours format.");
        }

        $date = Carbon::createFromFormat('d/m/Y', $request->get('transaction_date'))->format('Y-m-d');

        DB::beginTransaction();
        try {
            // create transaction request
            $trn_request = new TRRequest();

            $trn_request->account_id = $request->get("account_id");
            $trn_request->currency_id = $account->account_currency;
            $trn_request->transaction_type = $request->get("transaction_type");
            $trn_request->transaction_nature = "TT";
            $trn_request->transaction_date = $date . " " . $time;
            $trn_request->amount = $amount;
            $trn_request->remarks = $request->get("remarks");
            $trn_request->status = "Pending";
            $trn_request->created_by = Auth::user()->id;

            if ($request->get('op_beneficiary_id') && $request->get('transaction_type') == 'Debit') {
                $trn_request->beneficiary_id = $request->get('op_beneficiary_id');
            }

            $trn_request->save();

            if ($request->get("transaction_type") == "Credit" && !$isVirtual) {
                $sender_details = new SenderDetail();
                $sender_details->tr_request_id = $trn_request->id;
                $sender_details->sender_name = $request->get("sender_name");
                $sender_details->sender_address = $request->get("sender_address");
                $sender_details->sender_city = $request->get("sender_city");
                $sender_details->sender_country = $request->get("sender_country");
                $sender_details->sending_bank_name = $request->get("sending_bank_name");
                $sender_details->sending_bank_address = $request->get("sending_bank_address");
                $sender_details->sending_bank_city = $request->get("sending_bank_city");
                $sender_details->sending_bank_country = $request->get("sending_bank_country");
                $sender_details->sending_bank_swift = $request->get("sending_bank_swift");
                $sender_details->intermediary_bank_name = $request->get("intermediary_bank_name");
                $sender_details->intermediary_bank_swift = $request->get("intermediary_bank_swift");

                $sender_details->save();
            }

            $fees = self::calculateTransactionFees($account->currency, $request->get("amount"));

            $data = [
                'trn_id' => $trn_request->id,
                "amount" => isset($request['amount']) ? $request['amount'] : null,
                "transaction_date" => $trn_request->transaction_date,
                "remarks" => isset($request['remarks']) ? $request['remarks'] : null,
                'zeroize' => null,
                'service_fee_client_account' => $request->get("account_id"),
                'service_fee_bank_account_id' => Common::getBankAccountIdByCurrencyId($account->account_currency, 'is_service_fee_bank'),
                'service_fee_currency_id' => $account->account_currency,
                'service_fee' => $fees['bank_fee_amount'],
                'service_fee_remarks' => $request->get('remarks'),
                'is_service_fee_trn_hidden_for_client' => null,
                'trn_fee_client_account' => $request->get("account_id"),
                'trn_fee_bank_account_id' => Common::getBankAccountIdByCurrencyId($account->account_currency, 'is_trn_fee_bank'),
                'trn_fee_currency_id' => $account->account_currency,
                'trn_fee' => $fees['transaction_fee_amount'],
                'trn_fee_remarks' => $request->get('remarks'),
                'is_trn_fee_trn_hidden_for_client' => null
            ];

            Common::saveTransactionDetails($data, $trn_request);
            Common::uploadSupportingDocuments($request, $trn_request, $client);

            // create client transaction
            $trn = new AccountTransaction();

            $transaction_date = Carbon::createFromFormat("d/m/Y", $request->get("transaction_date"));

            $transaction_date->format("Y-m-d H:i:s");

            $trn->account_id = $request->get("account_id");
            $trn->tr_request_id = $trn_request->id;
            $trn->currency_id = $account->account_currency;
            $trn->transaction_type = $request->get("transaction_type");
            $trn->transaction_nature = "TT";
            $trn->transaction_date = $date . " " . $time;
            $trn->amount = $amount;
            $trn->remarks = $request->get("remarks");
            $trn->status = "Pending";
            $trn->created_by = Auth::user()->id;

            if ($request->get('op_beneficiary_id') && $request->get('transaction_type') == 'Debit') {
                $trn->beneficiary_id = $request->get('op_beneficiary_id');
            }

            $trn->save();

            $trn_request->link_tr_id = $trn->id;
            $trn_request->save();

            // notify finance manager for the transaction
            if (Auth::user()->hasRole('finance')) {
                EmailNotification::notifyTransactionCreation($trn);
            }

            Common::notifyComplianceTeamNeroData($trn_request);

            DB::commit();
            //dd($trnTransaction);
            return redirect("/finance-management/pending-transactions")->with('success', "TT details added successfully.");
        } catch (Exception $e) {
            Log::error('------------------------------------------------');
            Log::error($e->getMessage() . ' | Line: ' . $e->getLine());
            Log::error($e->getTraceAsString());
            Log::error('------------------------------------------------');

            DB::rollBack();

            return redirect("/finance-management/pending-transactions")->with('error', $e->getMessage());
        }
    }

    public function editTT($id)
    {
        $transaction_methods = Common::getTransactionMethods();
        $transaction = AccountTransaction::find($id);
        $transactionRequest = TRRequest::with('senderDetails')->find($transaction->tr_request_id);

        // $serviceFeeBankAccount = Bank::where('is_service_fee_bank', true)->first()->accounts->where('account_currency', $transactionRequest->currency_id)->first()->id;
        // $trnFeeBankAccount = Bank::where('is_trn_fee_bank', true)->first()->accounts->where('account_currency', $transactionRequest->currency_id)->first()->id;
        $serviceFeeBankAccounts = Bank::where('is_service_fee_bank', true)->first()->accounts;
        $trnFeeBankAccounts = Bank::where('is_trn_fee_bank', true)->first()->accounts;
        $serviceFeeAccounts = [];
        $trnFeeAccounts = [];
        $virtualTransaction = VirtualBankTransaction::where('parent_transaction_id', $transaction->id)->first();

        foreach ($serviceFeeBankAccounts as $account) {
            $serviceFeeAccounts[$account->account_currency] = $account->id;
        }

        foreach ($trnFeeBankAccounts as $account) {
            $trnFeeAccounts[$account->account_currency] = $account->id;
        }

        $service_fee_trn = AccountTransaction::select('id', 'link_tr_id', 'account_id', 'fee_debit_account_id', 'amount', 'is_hidden_for_clients', 'remarks')
            ->where('tr_request_id', $transactionRequest->id)
            ->where('is_service_fee', true)
            ->where('transaction_type', 'Credit')
            ->first();

        $trn_fee_trn = AccountTransaction::select('id', 'link_tr_id', 'account_id', 'fee_debit_account_id', 'amount', 'is_hidden_for_clients', 'remarks')
            ->where('tr_request_id', $transactionRequest->id)
            ->where('is_trn_fee', true)
            ->where('transaction_type', 'Credit')
            ->first();

        // dd('is_service_fee_hidden_for_clients', $is_service_fee_hidden_for_clients, 'is_trn_fee_hidden_for_clients', $is_trn_fee_hidden_for_clients);
        // dd('Service-Fee Transaction', $service_fee_trn, 'Transaction-Fee Transaction', $trn_fee_trn);

        $currencies = Currency::where("is_crypto", false)->select("id")->get();

        // $client_ids = Common::getClientIdsByClientId($transactionRequest->account->client->id);
        if (!empty($transactionRequest->account->client)) {
            $idColumn = 'client_id';
            $foreignId = $transactionRequest->account->client->id;
        } elseif (!empty($transactionRequest->account->bank)) {
            $idColumn = 'bank_id';
            $foreignId = $transactionRequest->account->bank->id;
        }

        $accounts = Account::where('is_active', true)->where($idColumn, $foreignId)->whereIn("account_currency", $currencies)->select("id", "title", "account_number", "account_currency")->get();
        $changes = self::getChanges($transaction);
        $detailsChanges = self::getTransactionChangesDetails($transactionRequest);
        // dd($detailsChanges);
        // $transactionChanges = self::getChanges($transaction);
        $beneficiary = null;

        if ($transactionRequest) {
            if ($transactionRequest->beneficiary_id > 0) {
                $beneficiary = $transactionRequest->beneficiary;
                $virtual_beneficaiary = $beneficiary->virtualBankBeneficiary;

                if ($virtual_beneficaiary && $virtual_beneficaiary->payment_types) {
                    $payment_type = $virtual_beneficaiary->payment_types;
                    $normalizedTypes = array_map(function ($type) {
                        return strtoupper($type);
                    }, $payment_type);

                    $transaction_methods = $transaction_methods->filter(function ($method) use ($normalizedTypes) {
                        $methodName = strtoupper($method->name);

                        // Always include SWIFT
                        if ($methodName === 'SWIFT') {
                            return true;
                        }

                        // Otherwise include only if beneficiary allows it
                        return in_array($methodName, $normalizedTypes);
                    });
                } else if ($transactionRequest->currency->short == "USD") {
                    // if ($transactionRequest->currency->virtual_iban_provider == "zenus"){
                    //     $transaction_methods = ZenusController::getBeneficiaryPaymentMethods($virtual_beneficaiary);
                    // } else {
                    $transaction_methods = TransactionMethod::where('name', 'WIRE')->orWhere('is_default', true)->get();
                    // }
                } else if ($transactionRequest->currency->short == "AED") {
                    $transaction_methods = TransactionMethod::where('name', 'IBAN')->orWhere('is_default', true)->get();
                }
            }

            $docs = $transactionRequest->docs;
        } else {
            $docs = [];
        }

        $virtual_bank_transaction_ids = VirtualBankTransaction::where('parent_transaction_id', $id)->pluck('virtual_bank_transaction_id')->toArray();
        $transaction_log = [];
        $isSweep = null;

        if (!empty($virtual_bank_transaction_ids)) {
            $transaction_log = DB::table('crypto_transactions_log')
                ->whereIn('transaction_id', $virtual_bank_transaction_ids)
                ->orderBy('id', 'DESC')->get();
            $isSweep = $this->isSweepTrnExists($transaction_log);
        }

        $rulesText = '';
        $amlNoteTexts = null;
        $portalTransactionsUrl = '';
        $neroDataTransactionId = 0;

        if (Common::isModuleEnabled('NeroData')) {
            $neroDataHelper = new NeroDataHelper();
            $rulesText = $neroDataHelper->getNeroDataLogText($transactionRequest->id);

            $notesJson = DB::table('crypto_transactions_log')
                ->where('transaction_id', $transactionRequest->id)
                ->orderByDesc('id')
                ->limit(1)
                ->value(DB::raw(
                    "COALESCE(JSON_EXTRACT(details, '$.payload.notes'), JSON_EXTRACT(details, '$.response.notes'))"
                ));
            $notesArray = json_decode($notesJson, true);
            if (is_string($notesArray)) {
                // handles cases where DB returns JSON as a quoted string
                $notesArray = json_decode($notesArray, true);
            }

            $amlNoteTexts = collect($notesArray ?: [])
                ->map(fn($n) => is_array($n) ? ($n['text'] ?? null) : $n)
                ->filter()
                ->values();
            $neroDataTransactionId = $neroDataHelper->getNeroDataTransactionIdByTrId($transactionRequest->id);
            $portalTransactionsUrl = $neroDataHelper->getPortalTransactionsUrl();
        }
        // $client = ClientHelper::getClientByAccountId($transactionRequest->account_id);
        // $clientAccounts = false;
        // dd($transaction_methods);
        $payment_type = Common::getPaymentTypeById($transactionRequest->payment_type_id);
        return view("finance-management.transactions.edit-tt", compact(
            'accounts',
            'transaction',
            'changes',
            'detailsChanges',
            'docs',
            'beneficiary',
            'transactionRequest',
            'serviceFeeAccounts',
            'trnFeeAccounts',
            'service_fee_trn',
            'trn_fee_trn',
            'transaction_log',
            'rulesText',
            'transaction_methods',
            'isSweep',
            'amlNoteTexts',
            'portalTransactionsUrl',
            'neroDataTransactionId',
            'payment_type',
            'virtualTransaction'
        ));
    }

    public function isSweepTrnExists($transaction_log)
    {
        foreach ($transaction_log as $log) {
            $log_details = json_decode($log->details);
            if ($log->crypto_system === 'Lorum') {
                if ($log_details->type === 'SWEEP_TRANSFER') {
                    return $log_details;
                }
            }

            if (!empty($log_details->type) && $log_details->type === 'SWEEP_TRANSFER') {
                return $log_details;
            }
        }
    }

    public function updateTT(Request $request)
    {
        // Log::info('--------------------------------------------------------');
        // Log::info('Update TT | User Name: ' . Auth::user()->name . ' | User ID: ' . Auth::user()->id);
        // Log::info(print_r($request->all(), true));
        // Log::info('--------------------------------------------------------');

        $this->validate($request, [
            'account_id' => 'required',
            'amount' => 'required|numeric|min:0',
            'transaction_date' => 'required|date_format:d/m/Y',
            'transaction_time' => 'required|date_format:H:i',
            'transaction_type' => 'required',
            'remarks' => 'required',
            'supporting_documents' => [new SupportingDocumentsRule],
            'payment_advice' => [new SupportingDocumentsRule]
        ]);

        $account = Account::find($request->get("account_id"));
        $client = ClientHelper::getClientByAccountId($request->get("account_id"));

        // if account is not available alert it
        if (!$account) {
            return redirect("finance-management/transactions/edit/tt/" . $request->get("id"))->with('error', "Account details not found.");
        }

        if ($account->is_active == 0) {
            return redirect("finance-management/transactions/edit/tt/" . $request->get("id"))->with('error', "Something went wrong. Please try again later.");
        }

        // restrict future date
        if (!self::isBelowToday($request->get("transaction_date"))) {
            return redirect("finance-management/transactions/edit/tt/" . $request->get("id"))->with('error', "Please provide past or current date.");
        }

        $trn = AccountTransaction::find($request->get("id"));

        if ($trn->status != 'Approved') {
            $balance = Common::getAccountBalanceById($request->get("account_id"));

            if ($request->get("transaction_type") == "Debit") {
                if ($balance < $request->get("amount")) {
                    return redirect("finance-management/transactions/edit/tt/" . $request->get("id"))->with('error', "Insufficient balance.");
                }
            }
        }

        // validate time
        $time = Common::validateTime($request->get("transaction_time"));
        if (!$time) {
            return redirect("finance-management/transactions/edit/tt/" . $request->get("id"))->with('error', "Please provide a valid time in 24 hours format.");
        }

        $date = Carbon::createFromFormat('d/m/Y', $request->get('transaction_date'))->format('Y-m-d');
        // update client transaction
        if ($trn->requestedTrn && $trn->remarks != $request->get("remarks")) {
            $trn->requestedTrn->remarks = $request->get("remarks");

            $trn->requestedTrn->save();
        }

        $transaction_date = Carbon::createFromFormat("d/m/Y", $request->get("transaction_date"));

        if ($request->get("transaction_type") == "Debit") {
            $transaction_date->addSeconds(10);
        }

        $transaction_date->format("Y-m-d H:i:s");

        $trn->account_id = $request->get("account_id");
        $trn->currency_id = $account->account_currency;
        $trn->transaction_type = $request->get("transaction_type");
        $trn->transaction_nature = "TT";
        $trn->transaction_date = $date . " " . $time;
        $trn->amount = $request->get("amount");
        $trn->remarks = $request->get("remarks");
        $trn->updated_by = Auth::user()->id;

        $trn->save();

        Common::uploadSupportingDocuments($request, $trn->requestedTrn, $client, 'payment_advice');
        Common::uploadSupportingDocuments($request, $trn->requestedTrn, $client);

        if (!Common::canUserMakeChange()) {
            return redirect("finance-management/transactions/edit/tt/" . $trn->id)->with('success', "Changes has been submitted to your line manager for approval.");
        } else {
            return redirect("/finance-management/transactions")->with('success', "TT details updated successfully.");
        }
    }

    public function updateTTWithFees(Request $request)
    {
        // dd($request->all());

        $this->validate($request, [
            // 'account_id' => 'required',
            'amount' => 'required',
            'transaction_date' => 'required',
            // 'transaction_time' => 'required',
            // 'transaction_type' => 'required',
            'remarks' => 'required'
        ]);

        // $account = Account::find($request->get("account_id"));

        // if account is not available alert it
        // if (!$account) {
        //     return redirect(url()->previous())->with('error', 'Account details not found.');
        //     // return redirect("finance-management/transactions/edit/tt/" . $request->get("id"))->with('error', "Account details not found.");
        // }

        // restrict future date
        // if (!self::isBelowToday($request->get("transaction_date"))) {
        //     return redirect("finance-management/transactions/edit/tt/" . $request->get("id"))->with('error', "Please provide past or current date.");
        // }

        // validate time
        // $time = Common::validateTime($request->get("transaction_time"));
        // if (!$time) {
        //     return redirect("finance-management/transactions/edit/tt/" . $request->get("id"))->with('error', "Please provide a valid time in 24 hours format.");
        // }

        try {
            $transactionDate = Carbon::createFromFormat('d/m/Y', $request->get('transaction_date'))->format('Y-m-d H:i:s');

            DB::beginTransaction();

            // create client transaction
            $trn = AccountTransaction::find($request->get("id"));
            $client = ClientHelper::getClientByAccountId($trn->account_id);

            if ($trn->requestedTrn && $trn->remarks != $request->get("remarks")) {
                $trn->requestedTrn->amount = $request->get("amount");
                $trn->requestedTrn->remarks = $request->get("remarks");
                $trn->requestedTrn->updated_by = Auth::user()->id;

                $trn->requestedTrn->save();
            }

            // $transaction_date = Carbon::createFromFormat("d/m/Y", $request->get("transaction_date"));

            // if ($request->get("transaction_type") == "Debit") {
            //     $transaction_date->addSeconds(10);
            // }

            // $transaction_date->format("Y-m-d H:i:s");

            // $trn->account_id = $request->get("account_id");
            // $trn->currency_id = $account->account_currency;
            // $trn->transaction_type = $request->get("transaction_type");
            // $trn->transaction_nature = "TT";
            $trn->transaction_date = $transactionDate;
            $trn->amount = $request->get("amount");
            $trn->remarks = $request->get("remarks");
            $trn->updated_by = Auth::user()->id;

            $trn->save();

            Common::handleFeesOnTransactionUpdate($request, $trn);
            // dd($request->all());

            Common::uploadSupportingDocuments($request, $trn->requestedTrn, $client, 'payment_advice');
            Common::uploadSupportingDocuments($request, $trn->requestedTrn, $client);
            DB::commit();

            if (!Common::canUserMakeChange()) {
                return redirect("finance-management/transactions/edit/tt/" . $trn->id)->with('success', "Changes has been submitted to your line manager for approval.");
            } else {
                return redirect("/finance-management/transactions")->with('success', "TT details updated successfully.");
            }
        } catch (Exception $e) {
            DB::rollback();

            Log::error($e->getMessage());
            Log::error($e->getLine());
            return redirect(url()->previous())->with('error', $e->getMessage());
        }
    }

    public function saveTransactionChanges(Request $request)
    {
        // $reques = $request->all();
        // $reques['is_trn_fee_trn_hidden_for_client'] = 0;
        // dd(isset($reques['is_service_fee_trn_hidden_for_client']));
        // dd('Request', $request->all(), 'Auth User ID', Auth::user()->id);

        // validate input data
        $this->validate($request, [
            // 'account_id' => 'required',
            'amount' => 'numeric|min:0',
            'transaction_date' => 'required',
            'supporting_documents' => [new SupportingDocumentsRule],
            // 'payment_advice' => [new SupportingDocumentsRule],
            // 'remarks' => 'required'
        ]);

        if ($request->has('platform') && $request->get('platform') == 'client_portal') {
            $trn_request = TRRequest::find($request->get('id'));
            $accTrn = $trn_request;
        } else {
            $accTrn = AccountTransaction::find($request->get('id'));
            $trn_request = TRRequest::find($accTrn->tr_request_id);
        }

        $account = $accTrn->account;
        $amount = $request->get("amount");

        if ($accTrn->transaction_type == 'Debit' && $trn_request) {
            if ($request->get('is_zeroize')) {
                $request->merge(['amount' => Common::getAvailableBalanceForTrApproval($trn_request)]);
            } elseif (! Common::trApprovalHasSufficientBalance(
                $trn_request,
                (float) $amount,
                $request->filled('service_fee') ? (float) $request->get('service_fee') : null,
                $request->filled('trn_fee') ? (float) $request->get('trn_fee') : null,
                $request->filled('service_fee_client_account') ? (int) $request->get('service_fee_client_account') : null,
                $request->filled('trn_fee_client_account') ? (int) $request->get('trn_fee_client_account') : null,
            )) {
                return redirect(url()->previous())->with('error', Common::trApprovalInsufficientBalanceMessage(
                    $trn_request,
                    (float) $amount,
                    $request->filled('service_fee') ? (float) $request->get('service_fee') : null,
                    $request->filled('trn_fee') ? (float) $request->get('trn_fee') : null,
                    $request->filled('service_fee_client_account') ? (int) $request->get('service_fee_client_account') : null,
                    $request->filled('trn_fee_client_account') ? (int) $request->get('trn_fee_client_account') : null,
                ));
            }
        }

        if ($account->is_active == 0) {
            return redirect(url()->previous())->with('error', "Something went wrong, please try again later.");
        }

        // restrict future date
        if (!self::isBelowToday($request->get("transaction_date"))) {
            return redirect(url()->previous())->with('error', "Please provide past or current date.");
        }

        DB::beginTransaction();
        try {
            // create transaction
            // $trn_request->updated_by = Auth::user()->id;

            // $trn_request->save();

            if (!$request->has('remarks')) {
                $request->merge(['remarks' => $request->get('debit_remarks')]);
            }

            // if ($trn_request->transaction_nature == 'Internal') {
            //     $trn_request->ex_rate = $request->get("ex_rate");
            //     $trn_request->save();
            // }

            if (Carbon::parse($trn_request->transaction_date)->format('Y-m-d') != Carbon::createFromFormat('d/m/Y', $request->get('transaction_date'))->format('Y-m-d')) {
                $trn_request->transaction_date = Carbon::createFromFormat('d/m/Y', $request->get('transaction_date'))->format('Y-m-d H:i:s');
            }

            $trn_request->amount = $request->get("amount");
            $trn_request->remarks = $request->get("remarks");

            $reviewer = Auth::user()->id;
            Common::saveTransactionDetails($request->all(), $trn_request, $reviewer);

            DB::commit();

            return redirect("/finance-management/pending-transactions")->with('success', "TT details updated successfully.");
        } catch (Exception $e) {
            Log::error('------------------------------------------------');
            Log::error($e->getMessage() . ' | Line: ' . $e->getLine());
            Log::error($e->getTraceAsString());
            Log::error('------------------------------------------------');

            DB::rollback();

            return redirect(url()->previous())->with('error', $e->getMessage());
        }
    }

    public function deletePaymentFile(Request $request)
    {
        $file = TRRequestDoc::find($request->get('file_id'));

        // S3Helper::fileDelete($file->url);
        // $file->delete();
        $file->is_deleted = true;
        $file->save();

        return redirect(url()->previous())->with('success', "File deleted successfully.");
    }

    public function addInternal()
    {

        $currencies = Currency::where("is_crypto", false)->select("id")->get();
        //dd($currencies);
        $accounts = Account::where("is_active", true);

        $accounts = $accounts->whereIn("account_currency", $currencies)
            ->where(function ($query) {
                $query->whereHas("bank", function ($query) {
                    $query->where("is_reserve_bank", false);
                })
                    ->orWhereHas("client", function ($query) {
                        $query->where("status", "Approved");
                    });
            })
            // ->whereIn("type", ['company', 'individual'])
            ->get();
        //dd($accounts);

        return view("finance-management.transactions.new-internal", compact('accounts'));
    }

    public function uploadDocuments(Request $request)
    {
        // validate input data
        $this->validate($request, [
            'id' => 'required',
            'supporting_documents' => [new SupportingDocumentsRule],
            'payment_advice' => [new SupportingDocumentsRule]
        ]);

        try {
            $trn = AccountTransaction::find($request->get("id"));
            $client = ClientHelper::getClientByAccountId($trn->account_id);

            Common::uploadSupportingDocuments($request, $trn->requestedTrn, $client, 'payment_advice');
            Common::uploadSupportingDocuments($request, $trn->requestedTrn, $client);

            return redirect("/finance-management/pending-transactions")->with('success', "Transaction details updated successfully.");
        } catch (Exception $e) {
            Log::error('------------------------------------------------');
            Log::error($e->getMessage() . ' | Line: ' . $e->getLine());
            Log::error($e->getTraceAsString());
            Log::error('------------------------------------------------');

            return redirect(url()->previous())->with('error', $e->getMessage());
        }
    }

    public function storeInternal(Request $request)
    {
        // dd($request->all());
        $this->validate($request, [
            'credit_account_id' => 'required',
            'debit_account_id' => 'required',
            'amount' => 'numeric|gt:0',
            'transaction_date' => 'required',
            'transaction_time' => 'required',
            'credit_remarks' => 'required',
            'debit_remarks' => 'required',
            'supporting_documents' => [new SupportingDocumentsRule]
        ]);

        $credit_account = Account::find($request->get("credit_account_id"));
        $debit_account = Account::find($request->get("debit_account_id"));
        $client = ClientHelper::getClientByAccountId($request->get("debit_account_id"));
        $amount = $request->get("amount");

        // if account is not available alert it
        if (!$credit_account || !$debit_account) {
            return redirect("/finance-management/transactions/new/internal")->with('error', "Account details not found.");
        }

        // check if same account is selected for transaction
        if ($credit_account->id == $debit_account->id) {
            return redirect("/finance-management/transactions/new/internal")->with('error', "Please choose different accounts in the same currency");
        }

        if ($credit_account->is_active == 0) {
            return redirect("/finance-management/transactions/new/internal")->with('error', "Something went wrong. Please try again later.");
        }

        if ($debit_account->is_active == 0) {
            return redirect("/finance-management/transactions/new/internal")->with('error', "Something went wrong. Please try again later.");
        }

        // restrict future date
        if (!self::isBelowToday($request->get("transaction_date"))) {
            return redirect("/finance-management/transactions/new/internal")->with('error', "Please provide past or current date.");
        }

        $balance = Common::getAccountBalanceById($request->get("debit_account_id"));

        if ($request->get('is_zeroize')) {
            // dd($amount, $balance);
            $amount = $balance;
        } elseif ($balance < $amount) {
            return redirect("/finance-management/transactions/new/internal")->with('error', "Insufficient balance.");
        }

        // validate time
        $time = Common::validateTime($request->get("transaction_time"));
        if (!$time) {
            return redirect("/finance-management/transactions/new/internal")->with('error', "Please provide a valid time in 24 hours format.");
        }

        // get db date
        $date = Carbon::createFromFormat('d/m/Y', $request->get('transaction_date'))->format('Y-m-d');

        DB::beginTransaction();
        try {
            // create client transaction
            $dt_trn_request = new TRRequest();

            if ($request->get('credit_currency_id') != $request->get('debit_currency_id')) {
                $is_exchange = 1;
            } else {
                $is_exchange = 0;
            }

            // create debit transaction
            $dt_trn_request->account_id = $debit_account->id;
            $dt_trn_request->currency_id = $debit_account->account_currency;
            $dt_trn_request->transaction_type = "Debit";
            $dt_trn_request->transaction_nature = "Internal";
            $dt_trn_request->transaction_date = $date . " " . $time;
            $dt_trn_request->amount = $amount;
            $dt_trn_request->ex_rate = $request->get("ex_rate");
            $dt_trn_request->receiver_account_number = $credit_account->account_number;
            $dt_trn_request->remarks = $request->get("debit_remarks");
            $dt_trn_request->status = "Pending";
            $dt_trn_request->is_exchange = $is_exchange;
            $dt_trn_request->created_by = Auth::user()->id;

            $dt_trn_request->save();

            $data = [
                'trn_id' => $dt_trn_request->id,
                'zeroize' => null,
                'amount' => $amount,
                'ex_rate' => $request->get("ex_rate"),
                'transaction_date' => $date . " " . $time,
                'remarks' => $request->get("debit_remarks"),
                'service_fee_client_account' => $credit_account->id,
                'service_fee_bank_account_id' => Common::getBankAccountIdByCurrencyId($credit_account->account_currency, 'is_service_fee_bank'),
                'service_fee_currency_id' => $credit_account->account_currency,
                'service_fee' => 0,
                'service_fee_remarks' => $request->get("debit_remarks"),
                'is_service_fee_trn_hidden_for_client' => ($credit_account->account_currency != $debit_account->account_currency ? true : false),
                'trn_fee_client_account' => $credit_account->id,
                'trn_fee_bank_account_id' => Common::getBankAccountIdByCurrencyId($credit_account->account_currency, 'is_trn_fee_bank'),
                'trn_fee_currency_id' => $credit_account->account_currency,
                'trn_fee' => 0,
                'trn_fee_remarks' => $request->get("debit_remarks"),
                'is_trn_fee_trn_hidden_for_client' => null
            ];

            Common::saveTransactionDetails($data, $dt_trn_request);
            Common::uploadSupportingDocuments($request, $dt_trn_request, $client);

            // create client transaction
            $cr_trn = new AccountTransaction();

            // create credit transaction
            $cr_trn->account_id = $credit_account->id;
            $cr_trn->tr_request_id = $dt_trn_request->id;
            $cr_trn->currency_id = $credit_account->account_currency;
            $cr_trn->transaction_type = "Credit";
            $cr_trn->transaction_nature = "Internal";
            $cr_trn->transaction_date = $date . " " . $time;
            $cr_trn->amount = $amount * ($request->get("ex_rate") ?? 1);
            $cr_trn->ex_rate = $request->get("ex_rate");
            $cr_trn->remarks = $request->get("credit_remarks");
            $cr_trn->status = "Pending";
            $cr_trn->created_by = Auth::user()->id;

            $cr_trn->save();

            // add 1 minute to debit transaction to avoid same time entry
            $datetime = Carbon::parse($cr_trn->transaction_date)->addMinutes(1)->format("Y-m-d H:i:s");

            // create client transaction
            $client_trn = new AccountTransaction();

            // create debit transaction
            $client_trn->account_id = $debit_account->id;
            $client_trn->link_tr_id = $cr_trn->id;
            $client_trn->tr_request_id = $dt_trn_request->id;
            $client_trn->currency_id = $debit_account->account_currency;
            $client_trn->transaction_type = "Debit";
            $client_trn->transaction_nature = "Internal";
            $client_trn->transaction_date = $datetime;
            $client_trn->amount = $amount;
            $client_trn->ex_rate = $request->get("ex_rate") ?? 1;
            $client_trn->remarks = $request->get("debit_remarks");
            $client_trn->status = "Pending";
            $client_trn->created_by = Auth::user()->id;

            $client_trn->save();

            $cr_trn->link_tr_id = $client_trn->id;
            $cr_trn->save();

            $dt_trn_request->link_tr_id = $client_trn->id;

            $dt_trn_request->save();

            Common::notifyComplianceTeamNeroData($dt_trn_request);

            // Log::info("\n --------------------------------------TRRequest----------------------------------------- \n");
            // Log::info(print_r($dt_trn_request, true));
            // Log::info("\n\n\n -------------------------------------Credit Transaction------------------------------------------ \n");
            // Log::info(print_r($cr_trn, true));
            // Log::info("\n\n\n -------------------------------------Debit Transaction------------------------------------------ \n");
            // Log::info(print_r($client_trn, true));

            // generate email notification to line manager
            if (Auth::user()->hasRole('finance')) {
                EmailNotification::notifyTransactionCreation($client_trn);
            }

            DB::commit();

            return redirect("/finance-management/pending-transactions")->with('success', "Internal Transfer details updated successfully.");
        } catch (Exception $e) {
            Log::error('------------------------------------------------');
            Log::error($e->getMessage() . ' | Line: ' . $e->getLine());
            Log::error($e->getTraceAsString());
            Log::error('------------------------------------------------');

            DB::rollBack();

            return redirect("/finance-management/pending-transactions")->with('error', $e->getMessage());
        }
    }

    public function editInternal($id, $nid = "")
    {
        $transaction_methods = Common::getTransactionMethods();
        $trn = AccountTransaction::find($id);
        if ($trn->transaction_type == 'Debit') {
            $transaction = $trn;
            $linkedTransaction = $trn->linkedTrn;
        } else {
            $linkedTransaction = $trn;
            $transaction = $trn->linkedTrn;
        }
        // $linkedTransaction = $transaction->linkedTrn;
        $transactionRequest = TRRequest::find($transaction->tr_request_id);

        $debitCurrencyId = $transactionRequest->currency_id;

        if ($transactionRequest->account->account_currency !== $transactionRequest->receiver->account_currency) {
            $debitCurrencyId = $transactionRequest->receiver->currency->id;
        }

        // $serviceFeeBankAccount = Bank::where('is_service_fee_bank', true)->first()->accounts->where('account_currency', $debitCurrencyId)->first()->id;
        // $trnFeeBankAccount = Bank::where('is_trn_fee_bank', true)->first()->accounts->where('account_currency', $debitCurrencyId)->first()->id;
        $serviceFeeBankAccounts = Bank::where('is_service_fee_bank', true)->first()->accounts;
        $trnFeeBankAccounts = Bank::where('is_trn_fee_bank', true)->first()->accounts;
        $serviceFeeAccounts = [];
        $trnFeeAccounts = [];

        foreach ($serviceFeeBankAccounts as $account) {
            $serviceFeeAccounts[$account->account_currency] = $account->id;
        }

        foreach ($trnFeeBankAccounts as $account) {
            $trnFeeAccounts[$account->account_currency] = $account->id;
        }

        $service_fee_trn = AccountTransaction::select('id', 'link_tr_id', 'account_id', 'fee_debit_account_id', 'amount', 'is_hidden_for_clients', 'remarks')
            ->where('tr_request_id', $transactionRequest->id)
            ->where('is_service_fee', true)
            ->where('transaction_type', 'Credit')
            ->first();

        $trn_fee_trn = AccountTransaction::select('id', 'link_tr_id', 'account_id', 'fee_debit_account_id', 'amount', 'is_hidden_for_clients', 'remarks')
            ->where('tr_request_id', $transactionRequest->id)
            ->where('is_trn_fee', true)
            ->where('transaction_type', 'Credit')
            ->first();

        $currencies = Currency::where("is_crypto", false)->select("id")->get();
        $client_id = $transactionRequest->account->client ? $transactionRequest->account->client->id : $transactionRequest->receiver->client->id;
        $receiver_id = $transactionRequest->receiver->client ? $transactionRequest->receiver->client->id : $client_id;

        $accounts = Account::where('is_active', true)
            ->where(function ($query) use ($client_id, $receiver_id) {
                $query->where('client_id', $client_id)
                    ->orWhere("client_id", $receiver_id);
            })
            ->whereIn("account_currency", $currencies)
            ->select("id", "title", "account_number", "account_currency")
            ->get();

        $changes = self::getChanges($transaction);
        $detailsChanges = self::getTransactionChangesDetails($transactionRequest);

        if ($nid > 0) {
            Common::readNotification($nid);
        }

        if ($transactionRequest) {
            $docs = $transactionRequest->docs;
        } else {
            $docs = [];
        }

        // $client = ClientHelper::getClientByAccountId($transactionRequest->account_id);
        // $clientAccounts = false;

        // $openpayd_transaction = OpenpaydTransaction::where('parent_transaction_id', $id)->first();
        $openpayd_transaction = VirtualBankTransaction::where('parent_transaction_id', $id)->first();
        if (empty($openpayd_transaction)) {
            $openpayd_transaction = VirtualBankTransaction::where('parent_transaction_id', $trn->link_tr_id)->first();
        }

        $transaction_log = [];
        if (!empty($openpayd_transaction)) {
            $transaction_log = DB::table('crypto_transactions_log')->where('transaction_id', $openpayd_transaction->virtual_bank_transaction_id)->orderBy('id', 'DESC')->get();
        }

        $rulesText = "";
        $amlNoteTexts = collect();
        $neroDataTransactionId = 0;
        $portalTransactionsUrl = '';
        if (Common::isModuleEnabled('NeroData')) {
            $neroDataHelper = new NeroDataHelper();
            $rulesText = $neroDataHelper->getNeroDataLogText($transactionRequest->id);


            $notesJson = DB::table('crypto_transactions_log')
                ->where('transaction_id', $transactionRequest->id)
                ->orderByDesc('id')
                ->limit(1)
                ->value(DB::raw(
                    "COALESCE(JSON_EXTRACT(details, '$.payload.notes'), JSON_EXTRACT(details, '$.response.notes'))"
                ));
            $notesArray = json_decode($notesJson, true);
            if (is_string($notesArray)) {
                // handles cases where DB returns JSON as a quoted string
                $notesArray = json_decode($notesArray, true);
            }

            $amlNoteTexts = collect($notesArray ?: [])
                ->map(fn($n) => is_array($n) ? ($n['text'] ?? null) : $n)
                ->filter()
                ->values();
            $neroDataTransactionId = $neroDataHelper->getNeroDataTransactionIdByTrId($transactionRequest->id);
            $portalTransactionsUrl = $neroDataHelper->getPortalTransactionsUrl();
        }

        $payment_type = Common::getPaymentTypeById($transactionRequest->payment_type_id);
        return view("finance-management.transactions.edit-internal", compact(
            'accounts',
            'transaction',
            'linkedTransaction',
            'changes',
            'detailsChanges',
            'docs',
            'transactionRequest',
            'serviceFeeAccounts',
            'trnFeeAccounts',
            'service_fee_trn',
            'trn_fee_trn',
            'transaction_log',
            'rulesText',
            'transaction_methods',
            'amlNoteTexts',
            'neroDataTransactionId',
            'portalTransactionsUrl',
            'payment_type'
        ));
    }

    public function updateInternal(Request $request)
    {
        $this->validate($request, [
            'account_id' => 'required',
            'amount' => 'required|numeric|min:0',
            'transaction_date' => 'required',
            'transaction_time' => 'required',
            'transaction_type' => 'required',
            'remarks' => 'required',
            'supporting_documents' => [new SupportingDocumentsRule]
        ]);

        $account = Account::find($request->get("account_id"));
        $client = ClientHelper::getClientByAccountId($request->get("account_id"));

        // if account is not available alert it
        if (!$account) {
            return redirect("/finance-management/transactions/edit/internal/" . $request->get("id"))->with('error', "Account details not found.");
        }

        if ($account->is_active == 0) {
            return redirect("/finance-management/transactions/edit/internal/" . $request->get("id"))->with('error', "Something went wrong. Please try again later.");
        }

        // restrict future date
        if (!self::isBelowToday($request->get("transaction_date"))) {
            return redirect("/finance-management/transactions/edit/internal/" . $request->get("id"))->with('error', "Please provide past or current date.");
        }

        $trn = AccountTransaction::find($request->get("id"));

        if ($trn->status != 'Approved' && $request->get("transaction_type") == "Debit") {
            $balance = Common::getAccountBalanceById($request->get("account_id"));

            if ($balance < $request->get("amount")) {
                return redirect("/finance-management/transactions/new/internal")->with('error', "Insufficient balance.");
            }
        }

        // validate time
        $time = Common::validateTime($request->get("transaction_time"));
        if (!$time) {
            return redirect("/finance-management/transactions/new/internal")->with('error', "Please provide a valid time in 24 hours format.");
        }

        // get db date
        $date = Carbon::createFromFormat('d/m/Y', $request->get('transaction_date'))->format('Y-m-d');

        // update client transaction
        $transaction_date = Carbon::createFromFormat("d/m/Y", $request->get("transaction_date"));

        if ($request->get("transaction_type") == "Debit") {
            $transaction_date->addSeconds(10);
            $linked_trn = AccountTransaction::find($trn->link_tr_id);

            $linked_trn->link_tr_id = $trn->id;
            $linked_trn->save();
        }

        $transaction_date->format("Y-m-d H:i:s");

        $trn->account_id = $request->get("account_id");
        $trn->currency_id = $account->account_currency;
        $trn->transaction_type = $request->get("transaction_type");
        $trn->transaction_nature = "Internal";
        $trn->transaction_date = $date . " " . $time;
        $trn->amount = $request->get("amount");
        $trn->remarks = $request->get("remarks");
        $trn->updated_by = Auth::user()->id;

        $trn->save();

        Common::uploadSupportingDocuments($request, $trn->requestedTrn, $client);

        if (!Common::canUserMakeChange()) {
            return redirect("finance-management/transactions/edit/internal/" . $trn->id)->with('success', "Changes has been submitted to your line manager for approval.");
        } else {
            return redirect("/finance-management/transactions")->with('success', "Internal Transfer details updated successfully.");
        }
    }

    public function updateInternalWithFees(Request $request)
    {
        $this->validate($request, [
            // 'account_id' => 'required',
            'amount' => 'required',
            // 'transaction_type' => 'required',
            // 'remarks' => 'required',
            'debit_remarks' => 'required',
            // 'linked_transaction_account_id' => 'required',
            'transaction_date' => 'required',
            // 'linked_transaction_date' => 'required',
            // 'linked_transaction_time' => 'required',
            // 'linked_transaction_remarks' => 'required'
            'credit_remarks' => 'required'
        ]);

        // $account = Account::find($request->get("account_id"));

        // // if account is not available alert it
        // if (!$account) {
        //     return redirect("/finance-management/transactions/new/internal")->with('error', "Account details not found.");
        // }

        // restrict future date
        if (!self::isBelowToday($request->get("transaction_date"))) {
            return redirect("/finance-management/transactions/new/internal")->with('error', "Please provide past or current date.");
        }

        // // validate time
        // $time = Common::validateTime($request->get("linked_transaction_time"));
        // if (!$time) {
        //     return redirect("/finance-management/transactions/new/internal")->with('error', "Please provide a valid time in 24 hours format.");
        // }

        // get db date
        // $date = Carbon::createFromFormat('d/m/Y', $request->get('linked_transaction_date'))->format('Y-m-d');

        DB::beginTransaction();
        try {
            // update client transaction
            $trn = AccountTransaction::find($request->get("id"));
            $client = ClientHelper::getClientByAccountId($trn->account_id);

            Common::handleFeesOnTransactionUpdate($request, $trn);

            $credit_trn = AccountTransaction::find($trn->link_tr_id);

            $trn_date = Carbon::createFromFormat("d/m/Y", $request->get("transaction_date"));
            $credit_transaction_date = $trn_date->format("Y-m-d H:i:s");
            $debit_transaction_date = $trn_date->addSeconds(10)->format("Y-m-d H:i:s");

            // $credit_trn->account_id = $request->get('linked_transaction_account_id');
            $credit_trn->transaction_date = $credit_transaction_date;
            $credit_trn->amount = $request->get("amount") * ($request->get("ex_rate") ?? 1);
            $credit_trn->ex_rate = $request->get("ex_rate");
            $credit_trn->remarks = $request->get('credit_remarks');
            $credit_trn->updated_by = Auth::user()->id;

            $credit_trn->save();

            // $trn->account_id = $request->get("account_id");
            // $trn->currency_id = $account->account_currency;
            // $trn->transaction_type = $request->get("transaction_type");
            $trn->transaction_nature = "Internal";
            $trn->transaction_date = $debit_transaction_date;
            $trn->amount = $request->get("amount");
            $trn->ex_rate = $request->get("ex_rate") ?? 1;
            $trn->remarks = $request->get("debit_remarks");
            $trn->updated_by = Auth::user()->id;

            $trn->save();

            if ($trn->requestedTrn) {
                $trn->requestedTrn->amount = $request->get("amount");
                $trn->requestedTrn->ex_rate = $request->get("ex_rate") ?? 1;
                $trn->requestedTrn->remarks = $request->get("debit_remarks");
                $trn->requestedTrn->updated_by = Auth::user()->id;

                $trn->requestedTrn->save();
            }

            Common::uploadSupportingDocuments($request, $trn->requestedTrn, $client);

            DB::commit();

            if (!Common::canUserMakeChange()) {
                return redirect("finance-management/transactions/edit/internal/" . $trn->id)->with('success', "Changes has been submitted to your line manager for approval.");
            } else {
                return redirect("/finance-management/transactions")->with('success', "Internal Transfer details updated successfully.");
            }
        } catch (Exception $e) {
            DB::rollback();

            return redirect(url()->previous())->with('error', $e->getMessage());
        }
    }

    public function approveChequeTransaction(Request $request)
    {
        $this->validate($request, [
            'transaction_id' => 'required'
        ]);

        $isAllowedApprover = Common::isAllowedApprover($request->get('transaction_id'), 'AccountTransaction');

        if (!$isAllowedApprover) {
            return redirect(url()->previous())->with('error', "You are not authorized to approve this transaction.");
        }

        $transaction = AccountTransaction::find($request->get('transaction_id'));
        // $client = ClientHelper::getClientByAccountId($transaction->account_id);
        // $clientAccount = Account::where('account_currency', $transaction->currency_id)->where('client_id', $client->parent_id)->first();
        // dd("Client", $client, "ClientAccount", $clientAccount);

        if ($transaction->status == "Rejected") {
            return redirect(url()->previous())->with('error', "Something went wrong. Please try again later.");
        }

        if ($transaction) {

            $transaction->status = "Approved";
            $transaction->approved_at = Carbon::now();
            $transaction->approved_by = Auth::user()->id;

            $transaction->save();

            if ($transaction->tr_request_id) {
                $linked_tr_req = TRRequest::find($transaction->tr_request_id);

                $linked_tr_req->status = "Approved";
                $linked_tr_req->approved_at = Carbon::now();
                $linked_tr_req->approved_by = Auth::user()->id;

                $linked_tr_req->save();
            }

            if ($transaction->link_tr_id) {
                $linked_tr = AccountTransaction::find($transaction->link_tr_id);

                $linked_tr->status = "Approved";
                $linked_tr->approved_at = Carbon::now();
                $linked_tr->approved_by = Auth::user()->id;

                $linked_tr->save();
            }

            $client = ClientHelper::getClientByAccountId($transaction->account_id);

            if ($transaction->transaction_type == 'Credit') {
                if ($client->parent_id) {
                    Common::transferMoneyToParentAccount($transaction, $client->parent_id, $request);
                } else {
                    FeeHelper::reconcileMaintenanceFee($transaction->account_id, $transaction->amount, $request);
                }
            }

            if (!$client->users[0]->user->hasRole('API')) {
                // notify creator
                EmailNotification::notifyTransactionApproval($transaction);
            }

            return redirect("/finance-management/transactions")->with('success', "Transaction approved successfully.");
        }
    }

    public function approveTransaction(Request $request)
    {
        $this->validate($request, [
            'id' => 'required'
        ]);

        $isAllowedApprover = Common::isAllowedApprover($request->get('id'), 'AccountTransaction');
        $isApproverReviewer = Common::isApproverReviewer($request->get('id'), 'AccountTransaction');

        if (!$isAllowedApprover) {
            return redirect(url()->previous())->with('error', "You are not authorized to approve this transaction.");
        }

        if (!$isApproverReviewer) {
            return redirect(url()->previous())->with('error', "You cannot approve a transaction you have reviewed.");
        }

        // dd($request->all());
        $this->validate($request, [
            'id' => 'required',
            'service_fee_bank_account_id' => 'required',
            'service_fee_remarks' => 'required',
            'service_fee_client_account' => 'required',
            'service_fee_currency_id' => 'required',
            'service_fee' => 'required',
            // 'trn_fee_bank_account_id' => 'required',
            // 'trn_fee_remarks' => 'required',
            // 'trn_fee_client_account' => 'required',
            // 'trn_fee_currency_id' => 'required',
            // 'trn_fee' => 'required',
            'transaction_date' => 'required',
            'transaction_method_id' => 'required'
            // 'remarks' => 'required'
        ]);

        // Log::info('Method: ' . __METHOD__ . ' | Line#: ' . __LINE__ . ' | Request is validated.');
        $transaction = AccountTransaction::find($request->get('id'));

        DB::beginTransaction();
        try {
            if ($transaction) {
                if ($transaction->status == "Rejected") {
                    DB::rollBack();

                    return redirect(url()->previous())->with('error', "Something went wrong. Please try again later.");
                }

                // Log::info('Method: ' . __METHOD__ . ' | Line#: ' . __LINE__ . ' | Request has transaction.');
                $req = TRRequest::find($transaction->tr_request_id);
                $amount = $request->get('amount');
                $credit_account_id = null;
                $credit_amount = null;

                Common::neroDataComplianceStatus($req->compliance_status);

                // check if there is enough balance in the client account
                if ($req->transaction_type == 'Debit') {
                    if ($request->get('is_zeroize')) {
                        $amount = Common::getAvailableBalanceForTrApproval($req);
                    } elseif (! Common::trApprovalHasSufficientBalance(
                        $req,
                        (float) $amount,
                        (float) $request->get('service_fee'),
                        $request->filled('trn_fee') ? (float) $request->get('trn_fee') : null,
                        (int) $request->get('service_fee_client_account'),
                        $request->filled('trn_fee') ? (int) $request->get('trn_fee_client_account') : null,
                    )) {
                        DB::rollBack();

                        return redirect(url()->previous())->with('error', Common::trApprovalInsufficientBalanceMessage(
                            $req,
                            (float) $amount,
                            (float) $request->get('service_fee'),
                            $request->filled('trn_fee') ? (float) $request->get('trn_fee') : null,
                            (int) $request->get('service_fee_client_account'),
                            $request->filled('trn_fee') ? (int) $request->get('trn_fee_client_account') : null,
                        ));
                    }
                }

                $is_openpayd_enabled = Common::isModuleEnabled('OpenPayd');
                $is_lorum_enabled    = Common::isModuleEnabled('Lorum');
                $is_zenus_enabled    = Common::isModuleEnabled('Zenus');

                $enabled_provider_map = [
                    'openpayd' => $is_openpayd_enabled,
                    'lorum'    => $is_lorum_enabled,
                    'zenus'    => $is_zenus_enabled,
                ];

                $module_name = '';

                $transaction_method = $request->filled('transaction_method_id')
                    ? TransactionMethod::with('module')->find($request->transaction_method_id)
                    : TransactionMethod::where('is_default', true)->with('module')->first();

                if (!$transaction_method) {
                    DB::rollBack();
                    return back()->with('error', 'Transaction method not found.');
                }

                $transaction_method_id = $transaction_method->id;

                // Skip if default OR no beneficiary
                if (!$transaction_method->is_default || $request->filled('beneficiary_id')) {
                    $module = $transaction_method->module;

                    if (!$module?->is_enabled) {
                        DB::rollBack();
                        return back()->with('error', "{$module->name} module is disabled.");
                    }

                    $module_name = strtolower($module->name ?? '');
                    $currency = $req->currency;
                    $currency_virtual_provider = $currency->virtual_iban_provider;

                    // optional provider override (if allowed)
                    if (!empty($req->account->virtual_bank_provider ?? null)) {
                        $provider = $req->account->virtual_bank_provider;

                        if (!empty($enabled_provider_map[$provider])) {
                            $currency_virtual_provider = $provider;
                        }
                    }

                    $is_openpayd_flow = $module_name === 'openpayd' && $currency_virtual_provider === 'openpayd';
                    $is_lorum_flow = $currency_virtual_provider === 'lorum';

                    // ================= OPENPAYD FLOW =================
                    if ($is_openpayd_flow) {
                        $beneficiary = $req->beneficiary;
                        $is_beneficiary_valid = VirtualBankBeneficiary::where('parent_beneficiary_id', $beneficiary->id)->exists();

                        if (!$is_beneficiary_valid) {
                            return back()->with('error', 'Invalid request, Beneficiary is not added to Openpayd.');
                        }

                        if (!$request->filled('funding_bank_account_id')) {
                            return back()->with('error', 'Funding bank account is required for OpenPayd payout transaction.');
                        }
                    }

                    // ================= LORUM FLOW =================
                    if ($is_lorum_flow) {
                        $lorumApis = new LorumApis();
                        $lorum_account_id = $lorumApis->getPrimaryAccountByCurrency($currency->short);

                        if (!$lorum_account_id) {
                            return back()->with('error', "Invalid request, Lorum {$currency->short} Primary Account not Found!");
                        }
                    }
                }

                $transaction->amount = $amount;
                $transaction_status = "Approved";

                $transaction->status = $transaction_status;
                $transaction->transaction_method_id = $transaction_method_id;
                $transaction->ex_rate = $request->get("ex_rate") ?? 1;
                $transaction->approved_at = Carbon::now();
                $transaction->approved_by = Auth::user()->id;

                $transaction->save();

                if ($transaction->transaction_type == 'Credit') {
                    $credit_account_id = $transaction->account_id;
                    $credit_amount = $amount;
                }

                if ($transaction->tr_request_id) {
                    $req->amount = $amount;
                    $req->status = $transaction_status;
                    $req->ex_rate = $request->get("ex_rate") ?? 1;
                    $req->otp_verified = true;
                    $req->approved_at = Carbon::now();
                    $req->approved_by = Auth::user()->id;
                    $req->transaction_method_id = $transaction_method_id;

                    $req->save();

                    if (!empty($req->transactionDetails)) {
                        ChangeLogger::deleteChanges($req->transactionDetails);
                    }
                }

                // Log::info("\n --------------------------------------TRRequest----------------------------------------- \n");
                // Log::info(print_r($req, true));
                // Log::info("\n\n\n -------------------------------------Credit Transaction------------------------------------------ \n");
                // Log::info(print_r($transaction, true));

                $transactionForWebhook = $transaction;

                if ($transaction->link_tr_id) {
                    $linked_tr = AccountTransaction::find($transaction->link_tr_id);

                    if ($linked_tr->transaction_type == 'Credit') {
                        $credit_account_id = $linked_tr->account_id;
                        $credit_amount = $amount * ($request->get("ex_rate") ?? 1);
                    } else {
                        $transactionForWebhook = $linked_tr;
                    }

                    // $linked_tr->status = "Approved";
                    $linked_tr->status = $transaction_status;

                    $linked_tr->amount = $amount * ($request->get("ex_rate") ?? 1);
                    $linked_tr->ex_rate = $request->get("ex_rate") ?? 1;
                    $linked_tr->approved_at = Carbon::now();
                    $linked_tr->approved_by = Auth::user()->id;
                    $linked_tr->transaction_method_id = $transaction_method_id;

                    $linked_tr->save();

                    // Log::info("\n\n\n -------------------------------------Debit Transaction------------------------------------------ \n");
                    // Log::info(print_r($linked_tr, true));
                }

                // DB::commit();


                Common::handleFeesOnTransactionApproval($request, $req, $transaction);

                $client = ClientHelper::getClientByAccountId($req->account_id);

                if ($client->parent_id && $transaction->transaction_type == "Credit") {
                    Common::transferMoneyToParentAccount($transaction, $client->parent_id, $request);
                } else {
                    if ($credit_account_id) {
                        FeeHelper::reconcileMaintenanceFee($credit_account_id, $credit_amount, $request);
                    }
                }

                Common::uploadSupportingDocuments($request, $req, $client, 'payment_advice');
                Common::uploadSupportingDocuments($request, $req, $client);

                if ($client instanceof \App\Models\Client && isset($client->users[0])) {
                    if ($client->users[0]->user) {
                        if (!$client->users[0]->user->hasRole('API')) {
                            EmailNotification::notifyTransactionApproval($transaction);
                        } else {
                            Common::sendWebhookNotification($transactionForWebhook, $client->users[0]->user, 'transaction', 'Your transaction request has been Approved');
                        }
                    }
                }

                $statusMap = [
                    "IN_PROGRESS" => 'Initiated',
                    "INITIATED" => 'Initiated',
                    'CANCELLED' => 'Failed',
                    "COMPLETED" => 'Approved',

                    'Created'   => 'Initiated',
                    'Submitted' => 'Pending',
                    'Executed'  => 'Approved',
                    'Settled'   => 'Approved',
                    'Failed'    => 'Failed',

                    'INSERTED'        => 'Initiated',
                    'AML_REVIEW'      => 'Pending',
                    'WAITING_FOR_AML' => 'Pending',
                    'PROCESSED'       => 'Approved',
                    'Submitted'       => 'Pending',

                ];

                // Create Virtual Transactions
                if ($is_openpayd_enabled && $module_name == 'openpayd') {
                    $openPaydHelper = new OpenpaydApis();
                    $funding_bank_account_id =  $request->get('funding_bank_account_id');

                    if ($transaction->transaction_nature == "TT") {

                        if ($req->beneficiary_id && $funding_bank_account_id) {
                            if ($this->isVirtualBankTransactionExists($transaction->id)) {
                                DB::rollBack();
                                return redirect()->back()->with('error', 'This transaction already has an associated virtual bank transaction.');
                            }

                            // if the Trafer to Primary account is enabled. Then create a Sweep Payout.
                            if ($openPaydHelper->process_payout_from_child) {
                                $openPayd_transaction = $openPaydHelper->createSweepPayout(
                                    $transaction,
                                    $req,
                                    $funding_bank_account_id,
                                    $transaction_method->name
                                );
                            } else {
                                $openPayd_transaction = $openPaydHelper->newPayoutTransaction(
                                    $transaction,
                                    $req->id,
                                    $transaction_method->name,
                                    $funding_bank_account_id
                                );
                            }

                            // if (!empty($openPayd_transaction->errorCode)) {
                            if (!empty($openPayd_transaction) && empty($openPayd_transaction->id)) {
                                DB::rollBack();
                                return redirect(url()->previous())->with('error', $openPayd_transaction->message);
                            }
                        } else if ($transaction->transaction_type == "Credit") {
                            $openPayd_transaction_details = $openPaydHelper->getTransactionLogByTrnId($transaction->id);

                            if (empty($openPayd_transaction_details) && $request->get("isSweepVerified") === "false") {
                                DB::rollBack();
                                return redirect(url()->previous())->with('error', 'Sweep transaction not found.');
                            }
                        }

                        $transaction_status = $statusMap[$openPayd_transaction->status];

                        // update account transaction status.
                        $transaction->status = $transaction_status;
                        $transaction->save();

                        if ($transaction->tr_request_id) {
                            // update account transaction request status.
                            $req->status = $transaction_status;
                            $req->save();
                        }

                        if ($transaction->link_tr_id) {
                            // update linked tr request status.
                            $linked_tr->status = $transaction_status;
                            $linked_tr->save();
                        }
                    }
                } else if ($is_lorum_enabled && $module_name == 'lorum') {

                    $lorum = new LorumController();
                    $transaction_state = 'Approved'; // default

                    if ($transaction->transaction_nature === 'TT') {
                        if ($req->beneficiary_id) {
                            $lorum_transaction = $lorum->createOutboundPayment($transaction);

                            if (empty($lorum_transaction?->id)) {
                                DB::rollBack();
                                return redirect()->back()->with('error', $lorum_transaction->message ?? 'Lorum payment failed');
                            }

                            $lorum->storeLorumTransaction($lorum_transaction, $req->id, $transaction->id);

                            $transaction_state = $statusMap[$lorum_transaction->state] ?? 'Initiated';
                        } else if ($transaction->transaction_type == 'Credit') {
                            // Case 2: Credit Sweep TT
                            $lorum_transaction_details = $lorum->getSweepTransactionByTrnId($transaction->id);

                            if (empty($lorum_transaction_details) && $request->get('isSweepVerified') === 'false') {
                                DB::rollBack();
                                return redirect()->back()->with('error', 'Sweep transaction not found.');
                            }
                        }
                    }

                    // -------------------- Persist statuses --------------------

                    // update account transaction status.
                    $transaction->status = $transaction_state;
                    $transaction->save();

                    // update account transaction request status.
                    if ($transaction->tr_request_id) {
                        $req->status = $transaction_state;
                        $req->save();
                    }

                    // update linked tr request status.
                    if ($transaction->link_tr_id) {
                        $linked_tr->status = $transaction_state;
                        $linked_tr->save();
                    }
                } else if ($is_zenus_enabled && $module_name == 'zenus') {

                    if ($transaction->transaction_nature === "TT" && $req->beneficiary_id) {

                        // If virtual bank transaction already exists then don`t create another.
                        $virtual_transaction = VirtualBankTransaction::where('parent_transaction_id', $transaction->id)->first();

                        if (!empty($virtual_transaction) && in_array($virtual_transaction->status, ['Initiated', 'Pending'])) {
                            DB::rollBack();
                            return redirect('/finance-management/transactions/edit/tt/' . $transaction->id)->with('error', 'Virtual bank transaction already exists, waiting for confirmation of Zenus Bank.');
                        }

                        $virtual_bank_transaction = new ZenusController();
                        $zenus_transaction = $virtual_bank_transaction->createZenusPayoutPayment($transaction);

                        if (!empty($zenus_transaction) && empty($zenus_transaction->paymentId)) {
                            DB::rollBack();
                            return redirect('/finance-management/transactions/edit/tt/' . $transaction->id)->with('error', $zenus_transaction->message);
                        } else {
                            $virtual_bank_transaction->storeZenusTransaction($zenus_transaction, $req->id, $transaction->id);
                            if (!empty($zenus_transaction->statusCode)) {
                                $transaction_state = $statusMap[$zenus_transaction->statusCode] ?? 'Initiated';
                            }
                        }
                    } else {
                        $transaction_state = 'Approved';
                    }

                    // -------------------- Persist statuses --------------------

                    // update account transaction status.
                    $transaction->status = $transaction_state;
                    $transaction->save();

                    // update account transaction request status.
                    if ($transaction->tr_request_id) {
                        $req->status = $transaction_state;
                        $req->save();
                    }

                    // update linked tr request status.
                    if ($transaction->link_tr_id) {
                        $linked_tr->status = $transaction_state;
                        $linked_tr->save();
                    }
                }

                DB::commit();

                Log::info("\n\n ------------------------------------- Transaction ------------------------------------------ \n");
                Log::info('Transaction ID: ' . $transaction->id . ' | Status: ' . $transaction->status . ' | Amount: ' . $transaction->amount  . "\n");
                Log::info("\n\n\n ------------------------------------- Transaction Saved Completed ------------------------------------------ \n");

                if ($transaction->status === 'Approved') {
                    // notify creator
                    EmailNotification::notifyTransactionApproval($transaction);
                    return redirect("/finance-management/transactions")->with('success', "Transaction approved successfully.");
                } else if ($request->get('is_virtual_bank_trn') || strtolower($transaction->status) == "initiated") {
                    return redirect('/finance-management/transactions')->with('success', 'Transaction initiated successfully. Please refresh the page after a while.');
                }

                return redirect("/finance-management/transactions")->with('success', "Transaction approved successfully.");
            }
        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Exception on line: ' . $e->getLine() . ' | Message: ' . $e->getMessage());
            Log::error('Stack Trace: ' . $e->getTraceAsString());

            return redirect(url()->previous())->with('error', $e->getMessage());
        }
    }

    public function rejectTransaction(Request $request)
    {
        $transaction = AccountTransaction::find($request->get('transaction_id'));

        if ($transaction->status == "Approved") {
            return redirect(url()->previous())->with('error', "Something went wrong. Please try again later.");
        }

        if ($transaction) {

            $transaction->status = "Rejected";
            $transaction->rejected_at = Carbon::now();
            $transaction->rejected_by = Auth::user()->id;
            $transaction->reject_reason = $request->get("reject_reason");

            $transaction->save();

            $req = TRRequest::find($transaction->tr_request_id);
            if ($req && $req->status !== 'Approved') {
                $req->status = 'Rejected';
                $req->rejected_at = Carbon::now();
                $req->rejected_by = Auth::user()->id;
                $req->reject_reason = $request->get('reject_reason');
                $req->save();

                if ($req->transactions->count() > 0) {
                    Common::rejectLinkedTransactions($req->transactions, $request->get('reject_reason'));
                }
            }

            if (!$req) {
                return redirect("/finance-management/transactions")->with('success', "Transaction rejected successfully.");
            }

            $client = ClientHelper::getClientByAccountId($req->account_id);
            if (!empty($client->users) && isset($client->users[0])) {
                if (!$client->users[0]->user->hasRole('API')) {
                    // notify creator
                    EmailNotification::notifyTransactionRejection($transaction);
                }

                if ($client instanceof \App\Models\Client) {
                    if ($client->users[0]->user->hasRole('API')) {
                        Common::sendWebhookNotification($transaction, $client->users[0]->user, 'transaction', 'Your transaction request has been Rejected');
                    }
                }
            }

            return redirect("/finance-management/transactions")->with('success', "Transaction rejected successfully.");
        }
    }

    public function getReviewedTransactionRequests(Request $request)
    {
        $transactions = TRRequest::with(['transactions', 'sanctions'])
            ->select(
                "account_transaction_requests.id",
                "account_transaction_requests.account_id",
                "account_transaction_requests.transaction_nature",
                "account_transaction_requests.transaction_type",
                "account_transaction_requests.amount",
                "account_transaction_requests.created_at",
                "account_transaction_requests.created_by",
                "account_transaction_requests.updated_at",
                "account_transaction_requests.status",
                "account_transaction_requests.reject_reason",
                "reviewer_user.name as reviewer"
            )
            ->where("account_transaction_requests.otp_verified", true)
            ->where("account_transaction_requests.crypto_amount", null)
            ->whereIn("account_transaction_requests.transaction_nature", ["Cheque", "TT", "Internal", "Maintenance Fee"])
            ->whereHas('transactionDetails', function ($transactionDetails) {
                $transactionDetails->whereHas('reviewer.roles', function ($query) {
                    $query->where('name', 'finance');
                });
            })
            ->leftJoin("transaction_details as trn_details", "account_transaction_requests.id", "=", "trn_details.trn_id")
            ->leftJoin("users as reviewer_user", "trn_details.user_id", "=", "reviewer_user.id")
            // ->rightJoin("transaction_details as trn_details", "account_transaction_requests.id", "=", "trn_details.trn_id")
            // ->leftJoin("model_has_roles as model_role", "trn_details.user_id", "=", "model_role.model_id")
            // ->leftJoin("roles as role", "model_role.role_id", "=", "role.id")
            // ->where("role.name", "finance")
            ->where("account_transaction_requests.status", "Pending")
            ->orderBy("account_transaction_requests.updated_at", "desc")
            ->get();


        // Check if any sanctions are suspicious for this transaction
        /*         foreach ($transactions as $transaction) {

            $isSuspicious = false;

            if ($transaction->case_id) {
                $isSuspicious = Sanctions::where('case_id', $transaction->case_id)
                    ->where('is_suspicious', true)
                    ->exists();
                $transaction["is_suspicious"] = $isSuspicious ? 1 : 0;
            }
        } */

        return view("finance-management.transactions.list-reviewed-only-transactions", compact('transactions'));
    }

    public function getTransactionRequests(Request $request)
    {
        // dd($request->route());
        $needle = "pending";
        $transactions = TRRequest::with(['transactions', 'sanctions'])
            ->where("account_transaction_requests.otp_verified", true)
            ->where("account_transaction_requests.crypto_amount", null)
            ->whereIn("account_transaction_requests.transaction_nature", ["Cheque", "TT", "Internal", "Maintenance Fee"]);

        if ($request->get('reviewed_only')) {
            $needle = "reviewed-only";
            $transactions = $transactions->rightJoin("transaction_details as trn_details", "account_transaction_requests.id", "=", "trn_details.trn_id")
                ->leftJoin("model_has_roles as model_role", "trn_details.user_id", "=", "model_role.model_id")
                ->leftJoin("roles as role", "model_role.role_id", "=", "role.id")
                ->where("role.name", "finance");
        }

        $transactions = $transactions->where("account_transaction_requests.status", "Pending")
            ->orderBy("account_transaction_requests.updated_at", "desc")
            ->get();

        // Check if any sanctions are suspicious for this transaction
        /*         foreach ($transactions as $transaction) {

            $isSuspicious = false;

            if ($transaction->case_id) {
                $isSuspicious = Sanctions::where('case_id', $transaction->case_id)
                    ->where('is_suspicious', true)
                    ->exists();
                $transaction["is_suspicious"] = $isSuspicious ? 1 : 0;
            }
        } */

        return view("finance-management.transactions.list-" . $needle . "-transactions", compact('transactions'));
    }

    public function getInitialReviewRequests(Request $request)
    {
        $transactions = TRRequest::with(['transactions', 'sanctions'])
            ->where("otp_verified", true)
            ->where("crypto_amount", null)
            ->whereIn("transaction_nature", ["Cheque", "TT", "Internal", "Maintenance Fee"])
            ->where("status", "Initial Review")
            ->orderBy("updated_at", "desc")
            ->get();

        return view("finance-management.transactions.list-initial-review-transactions", compact('transactions'));
    }

    public function getRejectedTransaction(Request $request)
    {
        $transactions = TRRequest::with('transactions')
            ->where("otp_verified", true)
            ->where("crypto_amount", null)
            ->whereIn("transaction_nature", ["Cheque", "TT", "Internal"])
            ->whereIn("status", ["Rejected", "CANCELLED", "FAILED"])
            ->orderBy("created_at", "desc")
            ->get();

        return view("finance-management.transactions.list-rejected-transactions", compact('transactions'));
    }

    public function viewInternalPendingTransaction($id)
    {
        $transactionRequest = TRRequest::find($id);
        $payment_type = Common::getPaymentTypeById($transactionRequest->payment_type_id);

        if ($transactionRequest->status == "Pending") {
            $debitCurrencyId = $transactionRequest->currency_id;

            if ($transactionRequest->account->account_currency !== $transactionRequest->receiver->account_currency) {
                $debitCurrencyId = $transactionRequest->receiver->currency->id;
            }

            // $serviceFeeBankAccount = Bank::where('is_service_fee_bank', true)->first()->accounts->where('account_currency', $debitCurrencyId)->first()->id;
            // $trnFeeBankAccount = Bank::where('is_trn_fee_bank', true)->first()->accounts->where('account_currency', $debitCurrencyId)->first()->id;
            // $client = ClientHelper::getClientByAccountId($transactionRequest->account_id);
            $serviceFeeBankAccounts = Bank::where('is_service_fee_bank', true)->first()->accounts;
            $trnFeeBankAccounts = Bank::where('is_trn_fee_bank', true)->first()->accounts;
            $serviceFeeAccounts = [];
            $trnFeeAccounts = [];

            foreach ($serviceFeeBankAccounts as $account) {
                $serviceFeeAccounts[$account->account_currency] = $account->id;
            }

            foreach ($trnFeeBankAccounts as $account) {
                $trnFeeAccounts[$account->account_currency] = $account->id;
            }

            $currencies = Currency::where("is_active", true)->where("is_crypto", false)->select("id")->get();
            $client_ids = Common::getClientIdsByClientId($transactionRequest->account->client->id);
            $accounts = Account::where('is_active', true)
                ->where(function ($query) use ($client_ids, $transactionRequest) {
                    $query->whereIn('client_id', $client_ids)
                        ->orWhere("client_id", $transactionRequest->receiver->client->id);
                })
                ->whereIn("account_currency", $currencies)
                ->select("id", "title", "account_number", "account_currency")
                ->get();

            $detailsChanges = self::getTransactionChangesDetails($transactionRequest);
            $rulesText = "";
            $amlNoteTexts = collect();
            $neroDataTransactionId = 0;
            $portalTransactionsUrl = '';
            if (Common::isModuleEnabled('NeroData')) {
                $neroDataHelper = new NeroDataHelper();
                $rulesText = $neroDataHelper->getNeroDataLogText($transactionRequest->id);


                $notesJson = DB::table('crypto_transactions_log')
                    ->where('transaction_id', $transactionRequest->id)
                    ->orderByDesc('id')
                    ->limit(1)
                    ->value(DB::raw(
                        "COALESCE(JSON_EXTRACT(details, '$.payload.notes'), JSON_EXTRACT(details, '$.response.notes'))"
                    ));
                $notesArray = json_decode($notesJson, true);
                if (is_string($notesArray)) {
                    // handles cases where DB returns JSON as a quoted string
                    $notesArray = json_decode($notesArray, true);
                }

                $amlNoteTexts = collect($notesArray ?: [])
                    ->map(fn($n) => is_array($n) ? ($n['text'] ?? null) : $n)
                    ->filter()
                    ->values();
                $neroDataTransactionId = $neroDataHelper->getNeroDataTransactionIdByTrId($transactionRequest->id);
                $portalTransactionsUrl = $neroDataHelper->getPortalTransactionsUrl();
            }

            return view("finance-management.transactions.view-internal-pending-transaction", compact('transactionRequest', 'serviceFeeAccounts', 'trnFeeAccounts', 'accounts', 'detailsChanges', 'rulesText', 'amlNoteTexts', 'neroDataTransactionId', 'portalTransactionsUrl', 'payment_type'));
        } else {
            return view("finance-management.transactions.view-internal-approved-transaction", compact('transactionRequest', 'payment_type'));
        }
    }

    public function viewTTPendingTransaction($id)
    {
        $transactionRequest = TRRequest::with('senderDetails')->find($id);
        $transaction_methods = Common::getTransactionMethods();
        // $serviceFeeBankAccount = Bank::where('is_service_fee_bank', true)->first()->accounts->where('account_currency', $transactionRequest->currency_id)->first()->id;
        // $trnFeeBankAccount = Bank::where('is_trn_fee_bank', true)->first()->accounts->where('account_currency', $transactionRequest->currency_id)->first()->id;
        // $client = ClientHelper::getClientByAccountId($transactionRequest->account_id);
        $serviceFeeBankAccounts = Bank::where('is_service_fee_bank', true)->first()->accounts;
        $trnFeeBankAccounts = Bank::where('is_trn_fee_bank', true)->first()->accounts;
        $serviceFeeAccounts = [];
        $trnFeeAccounts = [];
        $virtualTransaction = VirtualBankTransaction::where('trn_id', $transactionRequest->id)->first();

        if (!empty($virtualTransaction)) {
            $virtual_bank_transaction_ids = VirtualBankTransaction::where('parent_transaction_id', $id)->pluck('virtual_bank_transaction_id')->toArray();
            $transaction_log = [];

            if (!empty($virtual_bank_transaction_ids)) {
                $transaction_log = DB::table('crypto_transactions_log')
                    ->whereIn('transaction_id', $virtual_bank_transaction_ids)
                    ->orderBy('id', 'DESC')->get();
            }
        }

        /*  $isSuspicious = false; */

        /*   if ($transaction->case_id) {
            $isSuspicious = Sanctions::where('case_id', $transaction->case_id)
                ->where('is_suspicious', true)
                ->exists();
            $isResolved = Sanctions::where('case_id', $transaction->case_id)
                ->where('is_suspicious', true)
                ->where('resolved', false)
                ->exists();
            $transaction["is_suspicious"] = $isSuspicious ? 1 : 0;
            $transaction["is_resolved"] = $isResolved ? 0 : 1;
        } */

        foreach ($serviceFeeBankAccounts as $account) {
            $serviceFeeAccounts[$account->account_currency] = $account->id;
        }

        foreach ($trnFeeBankAccounts as $account) {
            $trnFeeAccounts[$account->account_currency] = $account->id;
        }

        $currencies = Currency::where("is_active", true)->where("is_crypto", false)->select("id")->get();

        $client_ids = Common::getClientIdsByClientId($transactionRequest->account->client->id);
        $accounts = Account::where('is_active', true)->whereIn("client_id", $client_ids)->whereIn("account_currency", $currencies)->select("id", "title", "account_number", "account_currency")->get();
        $detailsChanges = self::getTransactionChangesDetails($transactionRequest);
        $virtual_beneficaiary = null;

        // dd($transactionRequest->beneficiary_id);
        $transaction_log = [];
        $virtual_transaction = VirtualBankTransaction::where('trn_id', $id)->first();
        if (!empty($virtual_transaction)) {
            $transaction_log = DB::table('crypto_transactions_log')->where('transaction_id', $virtual_transaction->virtual_bank_transaction_id)->orderBy('id', 'DESC')->get();
        }
        if ($transactionRequest->beneficiary_id) {
            $beneficiary = $transactionRequest->beneficiary;
            $virtual_beneficaiary = $beneficiary->virtualBankBeneficiary;
            if ($virtual_beneficaiary && $virtual_beneficaiary->payment_types) {
                $payment_type = $virtual_beneficaiary->payment_types;
                $normalizedTypes = array_map(function ($type) {
                    return strtoupper($type);
                }, $payment_type);

                $transaction_methods = $transaction_methods->filter(function ($method) use ($normalizedTypes) {
                    $methodName = strtoupper($method->name);

                    // Always include SWIFT
                    if ($methodName === 'SWIFT') {
                        return true;
                    }

                    // Otherwise include only if beneficiary allows it
                    return in_array($methodName, $normalizedTypes);
                });
            } else {
                $currency_short = $transactionRequest->currency->short;
                $currency_provider = $transactionRequest->currency->virtual_iban_provider;
                $transaction_methods = Common::getPaymentMethodByCurrency($currency_short, $currency_provider);
            }

            // else if ($transactionRequest->currency->short == "USD") {
            //     $transaction_methods = TransactionMethod::where('name', 'WIRE')->orWhere('is_default', true)->get();
            // } else if ($transactionRequest->currency->short == "AED") {
            //     $transaction_methods = TransactionMethod::where('name', 'IBAN')->orWhere('is_default', true)->get();
            // }
        }

        $rulesText = '';
        $amlNoteTexts = collect();
        $neroDataTransactionId = 0;
        $portalTransactionsUrl = "";
        if (Common::isModuleEnabled('NeroData')) {
            $neroDataHelper = new NeroDataHelper();
            $rulesText = $neroDataHelper->getNeroDataLogText($transactionRequest->id);

            $notesJson = DB::table('crypto_transactions_log')
                ->where('transaction_id', $transactionRequest->id)
                ->orderByDesc('id')
                ->limit(1)
                ->value(DB::raw(
                    "COALESCE(JSON_EXTRACT(details, '$.payload.notes'), JSON_EXTRACT(details, '$.response.notes'))"
                ));
            $notesArray = json_decode($notesJson, true);
            if (is_string($notesArray)) {
                // handles cases where DB returns JSON as a quoted string
                $notesArray = json_decode($notesArray, true);
            }

            $amlNoteTexts = collect($notesArray ?: [])
                ->map(fn($n) => is_array($n) ? ($n['text'] ?? null) : $n)
                ->filter()
                ->values();
            $neroDataTransactionId = $neroDataHelper->getNeroDataTransactionIdByTrId($transactionRequest->id);
            $portalTransactionsUrl = $neroDataHelper->getPortalTransactionsUrl();
        }
        $payment_type = Common::getPaymentTypeById($transactionRequest->payment_type_id);
        //dd(compact('transactionRequest', 'serviceFeeAccounts', 'trnFeeAccounts', 'accounts', 'detailsChanges', 'virtual_beneficaiary', 'transaction_log', 'rulesText', 'transaction_methods'));
        return view("finance-management.transactions.view-tt-pending-transaction", compact(
            'transactionRequest',
            'serviceFeeAccounts',
            'trnFeeAccounts',
            'accounts',
            'detailsChanges',
            'virtual_beneficaiary',
            'transaction_log',
            'rulesText',
            'transaction_methods',
            'amlNoteTexts',
            'neroDataTransactionId',
            'portalTransactionsUrl',
            'payment_type',
            'virtualTransaction',
        ));
    }

    public function viewInitialReviewTransaction($id)
    {
        $transaction = TRRequest::with('senderDetails')->find($id);
        $docs = $transaction->docs;

        $rulesText = '';
        $neroDataTransactionId = 0;
        $portalTransactionsUrl = '';
        if (Common::isModuleEnabled('NeroData')) {

            $neroDataHelper = new NeroDataHelper();

            $rulesText = $neroDataHelper->getNeroDataLogText($transaction->id);

            $notesJson = DB::table('crypto_transactions_log')
                ->where('transaction_id', $transaction->id)
                ->orderByDesc('id')
                ->limit(1)
                ->value(DB::raw(
                    "COALESCE(JSON_EXTRACT(details, '$.payload.notes'), JSON_EXTRACT(details, '$.response.notes'))"
                ));
            $notesArray = json_decode($notesJson, true);
            if (is_string($notesArray)) {
                // handles cases where DB returns JSON as a quoted string
                $notesArray = json_decode($notesArray, true);
            }

            $amlNoteTexts = collect($notesArray ?: [])
                ->map(fn($n) => is_array($n) ? ($n['text'] ?? null) : $n)
                ->filter()
                ->values();
            $neroDataTransactionId = $neroDataHelper->getNeroDataTransactionIdByTrId($transaction->id);
            $portalTransactionsUrl = $neroDataHelper->getPortalTransactionsUrl();
        }
        $payment_type = Common::getPaymentTypeById($transaction->payment_type_id);
        return view("finance-management.transactions.view-tt-initial-review", compact('transaction', 'docs', 'rulesText', 'neroDataTransactionId', 'portalTransactionsUrl', 'payment_type'));
    }

    public function approveReviewForTransaction(Request $request)
    {
        $this->validate($request, [
            'id' => 'required'
        ]);

        try {

            $req = TRRequest::find($request->id);

            Common::neroDataComplianceStatus($req->compliance_status);

            if ($req->status == 'Initial Review') {

                $req->status = "Pending";
                $req->save();

                $client = ClientHelper::getClientByAccountId($req->account_id);

                if (!$client->users[0]->user->hasRole('API')) {
                    // notify client
                    EmailNotification::notifyClientForTransactionInitialReviewApproval($req);
                }

                if ($req->link_tr_id) {
                    $trn = AccountTransaction::find($req->link_tr_id);

                    $trn->status = "Pending";
                    $trn->save();

                    $req->id = $trn->id;
                } else {
                    $trn = $req;
                }

                if ($client instanceof \App\Models\Client) {
                    if ($client->users[0]->user->hasRole('API')) {
                        Common::sendWebhookNotification($trn, $client->users[0]->user, 'transaction', 'The Initial Review of your transaction request has been Approved');
                    }
                }

                return redirect("/finance-management/initial-review-transactions")->with('success', "The Initial Review of the Transaction has been approved successfully.");
            } else {
                return redirect("/finance-management/initial-review-transactions")->with('error', "Transaction not found.");
            }
        } catch (Exception $e) {
            return redirect(url()->previous())->with('error', $e->getMessage());
        }
    }

    public function approveTTPendingTransaction(Request $request)
    {
        $this->validate($request, [
            'id' => 'required'
        ]);

        $isAllowedApprover = Common::isAllowedApprover($request->get('id'), 'TRRequest');
        $isApproverReviewer = Common::isApproverReviewer($request->get('id'), 'TRRequest');

        if (!$isAllowedApprover) {
            return redirect(url()->previous())->with('error', "You are not authorized to approve this transaction.");
        }

        if (!$isApproverReviewer) {
            return redirect(url()->previous())->with('error', "You cannot approve a transaction you have reviewed.");
        }

        // dd($request->all());
        $this->validate($request, [
            'amount' => 'required',
            // 'service_fee_bank_account_id' => 'required',
            // 'service_fee_remarks' => 'required',
            // 'service_fee_client_account' => 'required',
            // 'service_fee_currency_id' => 'required',
            // 'service_fee' => 'required',
            'transaction_date' => 'required',
            'remarks' => 'required',
            'supporting_documents' => [new SupportingDocumentsRule]
        ]);

        // if (array_key_exists('service_fee_exchange_rate', $request->all())) {
        //     $this->validate($request, [
        //         'service_fee_exchange_rate' => 'required'
        //     ]);
        // }

        //todo: this code need to be refactored

        if ($request->get('is_virtual_bank_trn')) {
            $this->validate($request, [
                'transaction_method_id' => 'required',
            ]);
        }

        try {
            $req = TRRequest::find($request->id);

            if ($req) {


                Common::neroDataComplianceStatus($req->compliance_status);

                // if ($req->sanctions) {
                //     $isResolved = Sanctions::where('case_id', $req->case_id)
                //         ->where('is_suspicious', true)
                //         ->where('resolved', false)
                //         ->exists();

                //     if ($isResolved) {

                //         return redirect()->back()->with('error', "This case needs to be resolved to do this operation.");
                //     }
                // }

                $is_openpayd_enabled = Common::isModuleEnabled('OpenPayd');
                $is_lorum_enabled    = Common::isModuleEnabled('Lorum');
                $is_zenus_enabled    = Common::isModuleEnabled('Zenus');

                $enabled_provider_map = [
                    'openpayd' => $is_openpayd_enabled,
                    'lorum'    => $is_lorum_enabled,
                    'zenus'    => $is_zenus_enabled,
                ];

                $account = $req->account;
                $transaction_method = $request->filled('transaction_method_id')
                    ? TransactionMethod::with('module')->find($request->transaction_method_id)
                    : TransactionMethod::where('is_default', true)->with('module')->first();

                if (!$transaction_method) {
                    return back()->with('error', 'Transaction method not found.');
                }

                $module = $transaction_method->module;
                $module_name = strtolower($module?->name ?? '');

                $currency = $req->currency;
                $currency_virtual_provider = $currency->virtual_iban_provider;

                // Override provider if account allows it
                if (!empty($account->virtual_bank_provider) && !empty($enabled_provider_map[$account->virtual_bank_provider])) {
                    $currency_virtual_provider = $account->virtual_bank_provider;
                }

                $is_default_method = $transaction_method->is_default;

                // ================= DEFAULT METHOD SKIP =================
                if (!$is_default_method) {

                    // 1. Module must be enabled
                    if (!$module?->is_enabled) {
                        return back()->with('error', "{$module->name} module is disabled.");
                    }

                    // 2. Currency must support method
                    if ($currency_virtual_provider !== $module_name) {
                        // It should allow, Zenus and Lorum Beneficiaries for USD transactions.
                        if ($currency->short !== 'USD' && !in_array($module_name, ['zenus', 'lorum'])) {
                            return back()->with('error', "{$transaction_method->name} is not supported by {$currency->short}.");
                        }
                    }

                    // 3. Beneficiary validation
                    if ($request->filled('beneficiary_id')) {
                        $beneficiary = $req->beneficiary;
                        $is_supported = VirtualBankBeneficiary::where('parent_beneficiary_id', $beneficiary->id)->exists();

                        if (!$is_supported) {
                            return back()->with('error', "Invalid request, Beneficiary is not supported by {$module_name}.");
                        }
                    }

                    // 4. OpenPayd specific validation
                    if ($module_name === 'openpayd' && $currency_virtual_provider === 'openpayd') {
                        if (!$request->filled('funding_bank_account_id')) {
                            return back()->with('error', 'Funding bank account is required for OpenPayd payout transaction.');
                        }
                    }

                    // 5. Lorum specific validation
                    if ($currency_virtual_provider === 'lorum') {
                        $lorumApis = new LorumApis();
                        $lorum_account_id = $lorumApis->getPrimaryAccountByCurrency($currency->short);

                        if (!$lorum_account_id) {
                            return back()->with('error', "Invalid request, Lorum {$currency->short} Primary Account not Found!");
                        }
                    }
                }

                $transaction_method_id = $transaction_method->id;

                if ($req->status == "Rejected") {
                    return redirect(url()->previous())->with("error", "Something went wrong. Please try again later.");
                }

                $client = ClientHelper::getClientByAccountId($req->account_id);
                $req->transaction_date = Carbon::createFromFormat('d/m/Y', $request->get('transaction_date'));

                $service_fee = $request->get('service_fee');
                $trn_fee = 0;

                if ($request->has('trn_fee')) {
                    // $this->validate($request, [
                    //     'trn_fee_bank_account_id' => 'required',
                    //     'trn_fee_remarks' => 'required',
                    //     'trn_fee_client_account' => 'required',
                    //     'trn_fee_currency_id' => 'required',
                    //     'trn_fee' => 'required'
                    // ]);

                    $trn_fee = $request->get('trn_fee');
                }

                $amount = $request->get('amount');

                // check if there is enough balance in the client account
                if ($req->transaction_type == 'Debit') {
                    if ($request->get('is_zeroize')) {
                        $amount = Common::getAvailableBalanceForTrApproval($req);
                    } elseif (! Common::trApprovalHasSufficientBalance(
                        $req,
                        (float) $amount,
                        (float) $service_fee,
                        (float) $trn_fee,
                        (int) $request->get('service_fee_client_account'),
                        $request->has('trn_fee') ? (int) $request->get('trn_fee_client_account') : null,
                    )) {
                        return redirect(url()->previous())->with('error', Common::trApprovalInsufficientBalanceMessage(
                            $req,
                            (float) $amount,
                            (float) $service_fee,
                            (float) $trn_fee,
                            (int) $request->get('service_fee_client_account'),
                            $request->has('trn_fee') ? (int) $request->get('trn_fee_client_account') : null,
                        ));
                    }
                }

                DB::beginTransaction();

                $transactionDate = Carbon::createFromFormat("d/m/Y", $request->get("transaction_date"))->format('Y-m-d H:i:s');

                if ($req->link_tr_id) {
                    $trn = AccountTransaction::find($req->link_tr_id);

                    $trn->transaction_date = $transactionDate;
                    $trn->amount = $amount;
                    $trn->remarks = $request->get('remarks');
                    $trn->status = 'Approved';
                    $trn->transaction_method_id = $transaction_method_id;
                    $trn->approved_by = Auth::user()->id;
                    $trn->approved_at = Carbon::now();

                    $trn->save();
                } else {
                    $trn = new AccountTransaction();

                    $trn->account_id = $req->account_id;
                    $trn->tr_request_id = $req->id;
                    $trn->currency_id = $req->currency_id;
                    $trn->transaction_type = $req->transaction_type;
                    $trn->transaction_nature = $req->transaction_nature;
                    $trn->transaction_date = $transactionDate;
                    $trn->amount = $amount;
                    $trn->remarks = $request->get('remarks');
                    $trn->status = 'Approved';
                    $trn->transaction_method_id = $transaction_method_id ?? null;
                    $trn->created_by = $req->created_by;
                    $trn->approved_by = Auth::user()->id;
                    $trn->approved_at = Carbon::now();

                    // if the tt is for beneficiary
                    if ($req->beneficiary_id > 0) {
                        $trn->beneficiary_id = $req->beneficiary_id;
                        $trn->corresponding_bank_name = $req->corresponding_bank_name ?? null;
                        $trn->corresponding_bank_swift_code = $req->corresponding_bank_swift_code ?? null;
                        $trn->corresponding_bank_address = $req->corresponding_bank_address ?? null;
                    }

                    $trn->save();
                }

                // if (!empty($openpayd_tr)) {
                //     $openpayd_tr->parent_transaction_id = $trn['id'];
                //     $openpayd_tr->save();
                // }

                $req->transaction_date->format('Y-m-d H:i:s');
                $req->remarks = $request->remarks;
                $req->amount = $amount;
                $req->status = 'Approved';
                $req->link_tr_id = $trn->id;
                $req->approved_at = Carbon::now();
                $req->approved_by = Auth::user()->id;
                $req->transaction_method_id = $transaction_method_id;
                $req->save();

                Common::handleFeesOnTransactionApproval($request, $req, $trn);

                if ($req->transaction_type == 'Credit') {
                    if ($client->parent_id) {
                        Common::transferMoneyToParentAccount($trn, $client->parent_id, $request);
                    } else {
                        FeeHelper::reconcileMaintenanceFee($req->account_id, $amount, $request);
                    }
                }

                Common::uploadSupportingDocuments($request, $req, $client);

                if ($client instanceof \App\Models\Client) {
                    if ($client->users[0]->user->hasRole('API')) {
                        Common::sendWebhookNotification($trn, $client->users[0]->user, 'transaction', 'Your transaction request has been Approved');
                    }
                }

                $statusMap = [
                    "IN_PROGRESS" => 'Initiated',
                    "INITIATED" => 'Initiated',
                    "COMPLETED" => 'Approved',

                    'Created'   => 'Initiated',
                    'Submitted' => 'Pending',
                    'Executed'  => 'Approved',
                    'Settled'   => 'Approved',
                    'Failed'    => 'Failed',
                    'Draft'     => 'Pending',

                    'INSERTED'        => 'Initiated',
                    'AML_REVIEW'      => 'Pending',
                    'WAITING_FOR_AML' => 'Pending',
                    'PROCESSED'       => 'Approved',
                ];

                // creating virtual transaction.
                if ($is_openpayd_enabled && $module_name == 'openpayd') {


                    $openPaydHelper = new OpenpaydApis();
                    $funding_bank_account_id =  $request->get('funding_bank_account_id');

                    if ($trn->transaction_nature === "TT" && $req->beneficiary_id && $funding_bank_account_id) {

                        if ($this->isVirtualBankTransactionExists($trn->id)) {
                            DB::rollBack();
                            return redirect()->back()->with('error', 'This transaction already has an associated virtual bank transaction.');
                        }

                        // if the Trafer to Primary account is enabled. Then create a Sweep Payout.
                        if ($openPaydHelper->process_payout_from_child) {
                            $openpayd_payout = $openPaydHelper->createSweepPayout(
                                $trn,
                                $req,
                                $funding_bank_account_id,
                                $transaction_method->name
                            );
                        } else {
                            $openpayd_payout = $openPaydHelper->newPayoutTransaction(
                                $trn,
                                $req->id,
                                $transaction_method->name,
                                $funding_bank_account_id
                            );
                        }
                    }

                    if (!empty($openpayd_payout) && empty($openpayd_payout->id)) {
                        DB::rollBack();
                        return redirect()->back()->with('error', $openpayd_payout->message);
                    }

                    if (!empty($openpayd_payout->id)) {
                        // $trn_status = $openpayd_payout->status === 'COMPLETED' ? 'Approved' : $openpayd_payout->status;
                        $trn_status = $statusMap[$openpayd_payout->status] ?? "Initiated";
                        $trn->status = $trn_status;
                        $trn->save();

                        $req->status  = $trn_status;
                        $req->save();
                    }
                } else if ($is_lorum_enabled && $module_name == 'lorum') {

                    if ($trn->transaction_nature === "TT" && $req->beneficiary_id) {

                        // If virtual bank transaction already exists then don`t create another.
                        $virtual_transaction = VirtualBankTransaction::where('parent_transaction_id', $trn->id)->first();

                        if (!empty($virtual_transaction) && in_array($virtual_transaction->status, ['Initiated', 'Pending'])) {
                            DB::rollBack();
                            return redirect('/finance-management/transactions/edit/tt/' . $trn->id)->with('error', 'Virtual bank transaction already exists, waiting for confirmation of Lorum Bank.');
                        }

                        $virtual_bank_transaction = new LorumController();
                        $lorum_transaction = $virtual_bank_transaction->createOutboundPayment($trn);

                        if (!empty($lorum_transaction) && empty($lorum_transaction->id)) {
                            DB::rollBack();
                            return redirect('/finance-management/pending-transactions/view/tt/' . $req->id)->with('error', $lorum_transaction->message);
                        } else {
                            $virtual_bank_transaction->storeLorumTransaction($lorum_transaction, $req->id, $trn->id);
                            if (!empty($lorum_transaction->state)) {
                                $transaction_state = $statusMap[$lorum_transaction->state];
                            }
                        }
                    } else {
                        $transaction_state = 'Approved';
                    }

                    // update account transaction status.
                    $trn->status = $transaction_state;
                    $trn->save();

                    // update linked tr request status.
                    $req->status  = $transaction_state;
                    $req->save();
                } else if ($is_zenus_enabled && $module_name == 'zenus') {
                    // dd("zenus bank");
                    if ($trn->transaction_nature === "TT" && $req->beneficiary_id) {

                        // If virtual bank transaction already exists then don`t create another.
                        $virtual_transaction = VirtualBankTransaction::where('parent_transaction_id', $trn->id)->first();

                        if (!empty($virtual_transaction) && in_array($virtual_transaction->status, ['Initiated', 'Pending'])) {
                            DB::rollBack();
                            return redirect('/finance-management/transactions/edit/tt/' . $trn->id)->with('error', 'Virtual bank transaction already exists, waiting for confirmation of Zenus Bank.');
                        }

                        $virtual_bank_transaction = new ZenusController();
                        $zenus_transaction = $virtual_bank_transaction->createZenusPayoutPayment($trn);

                        if (!empty($zenus_transaction) && empty($zenus_transaction->paymentId)) {
                            DB::rollBack();
                            return redirect('/finance-management/pending-transactions/view/tt/' . $req->id)->with('error', $zenus_transaction->message);
                        } else {
                            $virtual_bank_transaction->storeZenusTransaction($zenus_transaction, $req->id, $trn->id);
                            if (!empty($zenus_transaction->statusCode)) {
                                $transaction_state = $statusMap[$zenus_transaction->statusCode] ?? 'Initiated';
                            }
                        }
                    } else {
                        $transaction_state = 'Approved';
                    }

                    // update account transaction status.
                    $trn->status = $transaction_state;
                    $trn->save();

                    // update linked tr request status.
                    $req->status  = $transaction_state;
                    $req->save();
                }

                // dd("here");

                DB::commit();

                Log::info("\n\n\n ------------------------------------- Transaction ------------------------------------------ \n");
                Log::info('Transaction ID: ' . $trn->id . ' | Status: ' . $trn->status . ' | Amount: ' . $trn->amount  . "\n");
                Log::info("\n\n\n ------------------------------------- Transaction Saved Completed ------------------------------------------ \n");

                if ($trn->status === 'Approved') {
                    // notify client
                    EmailNotification::notifyClientForTransactionApproval($trn);
                    return redirect('/finance-management/pending-transactions')->with('success', 'Transaction approved successfully.');
                } else if ($request->get('is_virtual_bank_trn') || strtolower($trn->status) == "initiated") {
                    return redirect('/finance-management/pending-transactions')->with('success', 'Transaction initiated successfully. Please refresh the page after a while.');
                }

                return redirect('/finance-management/pending-transactions')->with('success', 'Transaction approved successfully.');
            } else {
                return redirect('/finance-management/pending-transactions')->with('error', 'Transaction not found.');
            }
        } catch (Exception $e) {
            DB::rollBack();

            Log::error($e->getMessage() . "  " . $e->getFile() . "  " . $e->getLine());
            return redirect(url()->previous())->with('error', $e->getMessage());
        }
    }

    public function approveInternalPendingTransaction(Request $request)
    {
        $this->validate($request, [
            'id' => 'required'
        ]);

        $isAllowedApprover = Common::isAllowedApprover($request->get('id'), 'TRRequest');

        if (!$isAllowedApprover) {
            return redirect(url()->previous())->with('error', "You are not authorized to approve this transaction.");
        }

        if (array_key_exists('ex_rate', $request->all())) {
            $this->validate($request, [
                'ex_rate' => 'required'
            ]);
        }

        $this->validate($request, [
            'transaction_date' => 'required',
            'debit_remarks' => 'required',
            'credit_remarks' => 'required',
            'service_fee_bank_account_id' => 'required',
            'service_fee_remarks' => 'required',
            'service_fee_currency_id' => 'required',
            'service_fee_client_account' => 'required',
            'service_fee' => 'required',
            'supporting_documents' => [new SupportingDocumentsRule]
        ]);


        // if (array_key_exists('service_fee_exchange_rate', $request->all())) {
        //     $this->validate($request, [
        //         'service_fee_exchange_rate' => 'required'
        //     ]);
        // }

        // if (array_key_exists('trn_fee_exchange_rate', $request->all())) {
        //     $this->validate($request, [
        //         'trn_fee_exchange_rate' => 'required'
        //     ]);
        // }

        try {
            $req = TRRequest::find($request->id);

            if ($req) {

                Common::neroDataComplianceStatus($req->compliance_status);

                $client = ClientHelper::getClientByAccountId($req->account_id);
                $trn_date = Carbon::createFromFormat("d/m/Y", $request->get("transaction_date"));
                $credit_transaction_date = $trn_date->format("Y-m-d H:i:s");
                $debit_transaction_date = $trn_date->addSeconds(10)->format("Y-m-d H:i:s");
                $receiver_account = $req->receiver;
                $transactionAmount = $req->amount;

                // check if the receiver account is active
                if (!$receiver_account->is_active || !$req->account->is_active) {
                    return redirect(url()->previous())->with("error", "Debit and Credit account needs to be active");
                }

                // if ($req->currency->is_openpayd ^ $receiver_account->currency->is_openpayd) {
                //     return redirect()->back()->with("error", "Invalid request, both accounts should be SEPA accounts.");
                // }

                // if ($req->currency->virtual_iban_provider == 'openpayd' && $receiver_account->currency->virtual_iban_provider == 'openpayd') {
                //     $is_openpayd_enabled = Common::isModuleEnabled('OpenPayd');
                //     if (!$is_openpayd_enabled) {
                //         return redirect()->back()->with("error", "Openpayd module is disabled.");
                //     }
                // }

                $trn_fee = 0;

                if ($request->has('trn_fee_bank_account_id')) {
                    $this->validate($request, [
                        'trn_fee_bank_account_id' => 'required',
                        'trn_fee_remarks' => 'required',
                        'trn_fee_currency_id' => 'required',
                        'trn_fee_client_account' => 'required',
                        'trn_fee' => 'required'
                    ]);

                    $trn_fee = $request->get('trn_fee');
                }

                $service_fee = $request->get('service_fee');

                // check if there is enough balance in the client account
                $amount = $request->get('amount');

                if ($request->get('is_zeroize')) {
                    $amount = Common::getAvailableBalanceForTrApproval($req);
                } elseif (! Common::trApprovalHasSufficientBalance(
                    $req,
                    (float) $amount,
                    (float) $service_fee,
                    (float) $trn_fee,
                    (int) $request->get('service_fee_client_account'),
                    $request->has('trn_fee') ? (int) $request->get('trn_fee_client_account') : null,
                )) {
                    return redirect(url()->previous())->with('error', Common::trApprovalInsufficientBalanceMessage(
                        $req,
                        (float) $amount,
                        (float) $service_fee,
                        (float) $trn_fee,
                        (int) $request->get('service_fee_client_account'),
                        $request->has('trn_fee') ? (int) $request->get('trn_fee_client_account') : null,
                    ));
                }

                DB::beginTransaction();

                if ($req->link_tr_id) {
                    $transaction = AccountTransaction::find($req->link_tr_id);

                    $transaction->transaction_date = $debit_transaction_date;
                    $transaction->amount = $amount;
                    $transaction->ex_rate = $request->get("ex_rate") ?? 1;
                    $transaction->remarks = $request->get("debit_remarks");
                    $transaction->status = 'Approved';
                    $transaction->approved_by = Auth::user()->id;
                    $transaction->approved_at = Carbon::now();

                    if ($request->get('transaction_method_id')) {
                        $transaction->transaction_method_id = $request->get('transaction_method_id');
                    }

                    $transaction->save();

                    if ($transaction->link_tr_id) {
                        $cr_trn = AccountTransaction::find($transaction->link_tr_id);

                        $cr_trn->transaction_date = $debit_transaction_date;
                        $cr_trn->amount = $amount;
                        $cr_trn->ex_rate = $request->get("ex_rate") ?? 1;
                        $cr_trn->remarks = $request->get("debit_remarks");
                        $cr_trn->status = 'Approved';
                        $cr_trn->approved_by = Auth::user()->id;
                        $cr_trn->approved_at = Carbon::now();

                        if ($request->get('transaction_method_id')) {
                            $cr_trn->transaction_method_id = $request->get('transaction_method_id');
                        }

                        $cr_trn->save();
                    }
                } else {
                    // create debit transaction
                    $transaction = new AccountTransaction();

                    $transaction->account_id = $req->account_id;
                    $transaction->tr_request_id = $req->id;
                    $transaction->currency_id = $req->currency_id;
                    $transaction->transaction_type = "Debit";
                    $transaction->transaction_nature = $req->transaction_nature;
                    $transaction->transaction_date = $debit_transaction_date;
                    $transaction->amount = $amount;
                    $transaction->ex_rate = $request->get("ex_rate") ?? 1;
                    $transaction->remarks = $request->get("debit_remarks");
                    $transaction->status = "Approved";
                    $transaction->created_by = $req->created_by;
                    $transaction->approved_by = Auth::user()->id;
                    $transaction->approved_at = Carbon::now();

                    if ($request->get('transaction_method_id')) {
                        $transaction->transaction_method_id = $request->get('transaction_method_id');
                    }

                    $transaction->save();

                    // create credit transaction
                    $cr_trn = new AccountTransaction();

                    $cr_trn->account_id = $receiver_account->id;
                    $cr_trn->parent_trn_id = $transaction->id;
                    $cr_trn->tr_request_id = $req->id;
                    $cr_trn->link_tr_id = $transaction->id;
                    $cr_trn->currency_id = $receiver_account->account_currency;
                    $cr_trn->transaction_type = "Credit";
                    $cr_trn->transaction_nature = $req->transaction_nature;
                    $cr_trn->transaction_date = $credit_transaction_date;
                    $cr_trn->amount = $amount * ($request->get("ex_rate") ?? 1);
                    $cr_trn->ex_rate = $request->get("ex_rate") ?? 1;
                    $cr_trn->remarks = $request->get("credit_remarks");
                    $cr_trn->status = "Approved";
                    $cr_trn->created_by = $req->created_by;
                    $cr_trn->approved_by = Auth::user()->id;
                    $cr_trn->approved_at = Carbon::now();

                    if ($request->get('transaction_method_id')) {
                        $cr_trn->transaction_method_id = $request->get('transaction_method_id');
                    }

                    $cr_trn->save();

                    // update link transaction id in the debit transaction
                    $transaction->link_tr_id = $cr_trn->id;
                    $transaction->save();

                    // get the client id for the credit transaction
                    $client = ClientHelper::getClientByAccountId($cr_trn->account_id);
                }

                // approve transaction request
                $req->amount = $amount;
                $req->link_tr_id = $transaction->id;
                $req->ex_rate = $request->get("ex_rate") ?? 1;
                $req->remarks = $request->get("debit_remarks");
                $req->status = "Approved";
                $req->approved_at = Carbon::now();
                $req->approved_by = Auth::user()->id;
                $req->transaction_method_id = $request->get('transaction_method_id');

                if ($request->get('transaction_method_id')) {
                    $req->transaction_method_id = $request->get('transaction_method_id');
                }

                $req->save();

                Common::handleFeesOnTransactionApproval($request, $req, $transaction);

                if ($client->parent_id) {
                    Common::transferMoneyToParentAccount($cr_trn, $client->parent_id, $request);
                } else {
                    FeeHelper::reconcileMaintenanceFee($receiver_account->id, $amount * ($request->get("ex_rate") ?? 1), $request);
                }

                Common::uploadSupportingDocuments($request, $req, $client);

                if ($client instanceof \App\Models\Client && count($client->users) > 0) {
                    if ($client->users[0]->user->hasRole('API')) {
                        Common::sendWebhookNotification($transaction, $client->users[0]->user, 'transaction', 'Your transaction request has been Approved');
                    }
                }

                // if ($request->get('is_open_payd') == 1 && $is_openpayd_enabled) {
                //     $openPaydHelper = new OpenpaydApis();
                //     $openPaydPayout = $openPaydHelper->createInternalTransfer($req, $transaction->id);

                //     if (!empty($openPaydPayout->errorCode)) {
                //         DB::rollBack();
                //         return redirect()->back()->with('error', $openPaydPayout->message);
                //     }

                //     $status = $openPaydPayout->status === 'COMPLETED' ? 'Approved' : $openPaydPayout->status;

                //     $transaction->status = $status;
                //     $transaction->save();

                //     $cr_trn->status = $status;
                //     $cr_trn->save();
                // }

                DB::commit();

                Log::info("\n\n\n ------------------------------------- Transaction ------------------------------------------ \n");
                Log::info('Transaction ID: ' . $transaction->id . ' | Status: ' . $transaction->status . ' | Amount: ' . $transaction->amount . "\n");
                Log::info("\n\n\n ------------------------------------- Transaction Saved Completed ------------------------------------------ \n");

                if ($transaction->status === 'Approved') {
                    // notify client
                    EmailNotification::notifyClientForTransactionApproval($transaction);
                    return redirect('/finance-management/transactions')->with("success", "Internal transaction request approved successfully");
                }
                // else if ($request->get('is_open_payd')) {
                //     return redirect('/finance-management/transactions')->with('success', 'Internal transaction request initiated successfully. Please refresh the page after a while.');
                // }

                return redirect('/finance-management/pending-transactions')->with("success", "Internal transaction request approved successfully");
            } else {
                return redirect(url()->previous())->with("error", "Invalid internal transaction request id");
            }
        } catch (Exception $e) {
            DB::rollBack();

            return redirect(url()->previous())->with('error', $e->getMessage());
        }
    }

    public function printTransaction($id)
    {
        $transaction = AccountTransaction::find($id);

        $trnRequest = TRRequest::find($transaction->tr_request_id);

        if ($trnRequest) {
            $docs = $trnRequest->docs;
        } else {
            $docs = [];
        }

        if ($transaction->transaction_nature == 'Internal' && $transaction->link_tr_id) {
            $needle = 'internal-';
        } else {
            $needle = '';
        }

        $view = view("pdfs.finance." . $needle . "transaction-request", compact('transaction', 'docs'));
        $pdf = PDF::loadHTML($view)->setPaper('a4');
        $file_name = $transaction->transaction_nature . "-transaction-request.pdf";
        return $pdf->stream($file_name);
    }

    public function printTransactionRequest($id)
    {
        $transaction = TRRequest::find($id);

        // if ($transaction->transaction_nature == 'internal' && $transaction->link_tr_id) {
        //     $needle = 'internal-';
        // } else {
        //     $needle = '';
        // }

        $view = view("pdfs.finance.transaction-request", compact('transaction'));
        $pdf = PDF::loadHTML($view)->setPaper('a4');
        $file_name = $transaction->transaction_nature . "-transaction-request.pdf";
        return $pdf->stream($file_name);
    }

    public function rejectTTPendingTransaction(Request $request)
    {
        $req = TRRequest::find($request->id);

        if ($req->status == "Approved") {
            return redirect(url()->previous())->with("error", "Something went wrong. Please try again later.");
        }

        if ($req) {
            $req->status = "Rejected";
            $req->rejected_by = Auth::user()->id;
            $req->rejected_at = Carbon::now();
            $req->reject_reason = $request->get("reject_reason");

            $req->save();

            if ($req->transactions->count() > 0) {
                Common::rejectLinkedTransactions($req->transactions, $request->get("reject_reason"));
            }

            $client = ClientHelper::getClientByAccountId($req->account_id);

            if (!$client->users[0]->user->hasRole('API')) {
                // notify client
                EmailNotification::notifyClientForTransactionRejection($req);
            }

            if ($client instanceof \App\Models\Client) {
                $trn = $req->linkedTrn;

                if ($client->users[0]->user->hasRole('API') && $trn) {
                    Common::sendWebhookNotification($trn, $client->users[0]->user, 'transaction', 'Your transaction request has been Rejected');
                }
            }

            return redirect("/finance-management/pending-transactions")->with('success', "Transaction Rejected successfully.");
        } else {
            return redirect("/finance-management/pending-transactions")->with('error', "Transaction not found.");
        }
    }

    public function rejectInternalPendingTransaction(Request $request)
    {
        $req = TRRequest::find($request->id);

        if ($req->status == "Approved") {
            return redirect(url()->previous())->with("error", "Something went wrong. Please try again later.");
        }

        if ($req) {
            $req->status = "Rejected";
            $req->rejected_by = Auth::user()->id;
            $req->rejected_at = Carbon::now();
            $req->reject_reason = $request->get("reject_reason");

            $req->save();

            if ($req->transactions->count() > 0) {
                Common::rejectLinkedTransactions($req->transactions, $request->get("reject_reason"));
            }

            $client = ClientHelper::getClientByAccountId($req->account_id);

            if (!$client->users[0]->user->hasRole('API')) {
                // notify client
                EmailNotification::notifyClientForTransactionRejection($req);
            }

            if ($client instanceof \App\Models\Client) {
                $trn = $req->linkedTrn;

                if ($client->users[0]->user->hasRole('API') && $trn) {
                    Common::sendWebhookNotification($trn, $client->users[0]->user, 'transaction', 'Your transaction request has been Rejected | Reason: ' . $request->get("reject_reason"));
                }
            }

            return redirect("/finance-management/pending-transactions")->with('success', "Transaction Rejected successfully.");
        } else {
            return redirect("/finance-management/pending-transactions")->with('error', "Transaction not found.");
        }
    }

    private static function getTransactionChangesDetails($transactionRequest)
    {
        $detailsChanges = null;
        if (
            !empty($transactionRequest->transactionDetails)
            // && !Auth::user()->hasRole('finance')
        ) {
            $transactionDetailsChanges = self::getChanges($transactionRequest->transactionDetails);

            if (count($transactionDetailsChanges) > 0) {
                $detailsChanges['maker'] = $transactionDetailsChanges[0][0]->maker->name;

                foreach ($transactionDetailsChanges[0] as $change) {
                    // dd($change);
                    $detailsChanges[$change->details[0]->key]['old_value'][] = $change->details[0]->old_value;
                    $detailsChanges[$change->details[0]->key]['new_value'][] = $change->details[0]->new_value;
                    $oldValue = $detailsChanges[$change->details[0]->key]['old_value'][count($detailsChanges[$change->details[0]->key]['old_value']) - 1];

                    if (strpos($change->details[0]->key, 'fee_client_account_id')) {
                        $oldValue = Account::find($detailsChanges[$change->details[0]->key]['old_value'][count($detailsChanges[$change->details[0]->key]['old_value']) - 1])->title;
                    }

                    $detailsChanges[$change->details[0]->key]['tooltip'] = '
                    <div class="tooltip-container">
                        <div class="tooltip-circle"
                            data-tooltip="' . e('Old Value: ' . $oldValue) . '">
                        </div>
                    </div>
                ';
                }

                // dd('Details', $detailsChanges);
            }
        }

        return $detailsChanges;
    }

    private static function getChanges($model)
    {
        $transaction = ChangeLogger::get($model);
        $cheque = ($model->cheque) ? ChangeLogger::get($model->cheque) : null;

        $changes = [];
        if ($transaction) {
            $changes[] = $transaction;
        }

        if ($cheque) {
            $changes[] = $cheque;
        }

        return $changes;
    }

    private static function getChangesUsingCreatedAt($model)
    {
        $transaction = ChangeLogger::getUsingCreatedAt($model);
        $cheque = ($model->cheque) ? ChangeLogger::get($model->cheque) : null;

        $changes = [];
        if ($transaction) {
            $changes[] = $transaction;
        }

        if ($cheque) {
            $changes[] = $cheque;
        }

        return $changes;
    }

    public function deletePendingTransaction(Request $request)
    {
        $trn = TRRequest::find($request->get("id"));

        $statusMapping = [
            'Approved' => '/finance-management/transactions',
            'Pending' => '/finance-management/pending-transactions',
            'Initial Review' => '/finance-management/initial-review-transactions',
            'Rejected' => '/finance-management/rejected-transactions'
        ];

        // dd($trn->childTransactions($trn->id));

        DB::beginTransaction();
        try {
            if ($trn) {
                // delete documents if any
                if ($trn->docs && $trn->docs->count() > 0) {
                    foreach ($trn->docs as $doc) {
                        // S3Helper::fileDelete($doc->url);
                        $doc->is_deleted = true;
                        $doc->save();
                    }
                }

                // delete transaction
                $trn->is_deleted = true;
                $trn->save();

                DB::commit();

                return back()->with("success", "Transaction Request deleted successfully");
            } else {
                DB::rollBack();

                Log::error('The transaction, with Transaction ID: ' . $request->get("trn_id") . ', has not been found. | Method: ' . __METHOD__ . ' | Line#: ' . __LINE__);

                return redirect($statusMapping[$trn->status])->with('error', "Internal server error, please try again later.");
            }
        } catch (Exception $e) {
            Log::error('------------------------------------------------');
            Log::error($e->getMessage() . ' | Line: ' . $e->getLine());
            Log::error($e->getTraceAsString());
            Log::error('------------------------------------------------');

            DB::rollback();

            return redirect($statusMapping[$trn->status])->with('error', "Internal server error, please try again later.");
        }
    }

    public function createNewBeneficiary(Request $request)
    {
        $this->validate($request, [
            'client_id' => 'required|exists:clients,id',
            'currency_short_code' => 'required|exists:currencies,id',
            'beneficiary_country' => 'required|exists:countries,id',
            'bank_account_country' => 'required|exists:countries,id',
            'name' => 'required|string|max:255',
            // 'account_number' => 'required|string',
            // 'iban' => 'required|string|max:34',
            'beneficiary_address' => 'nullable|string|max:255',
            'bank_name' => 'required|string|max:255',
            'swift_code' => 'required|string',
            'bank_address' => 'nullable|string|max:255',
            'beneficiary_type' => 'required',
        ]);

        $client = Client::find($request->get("client_id"));

        DB::beginTransaction();

        try {

            $openpaydHelper = new OpenpaydApis();

            // add new beneficiary
            $ben = new ClientBeneficiary();
            $ben->client_id = $client->id;
            $ben->user_id = $client->users[0]->user->id;
            $ben->name = $request->get("name");
            $ben->account_number = $request->get("account_number");
            $ben->iban = $request->get("iban");
            $ben->address = $request->get("address");
            $ben->bank_name = $request->get("bank_name");
            $ben->swift_code = $request->get("swift_code");
            $ben->account_currency = $request->get("currency_short_code");
            $ben->beneficiary_country = $request->get("beneficiary_country");
            $ben->bank_account_country = $request->get("bank_account_country");
            $ben->bank_address = $request->get("bank_address");

            $ben->payment_type_id = $request->get('payment_type_id');
            $ben->beneficiary_type = $request->get("beneficiary_type");
            $ben->bank_city = $request->get('bank_city');
            $ben->postal_code = $request->get('postal_code');
            $ben->sort_code = $request->get('sort_code');

            $ben->status = "Approved";
            $ben->created_by = Auth::user()->id;
            $ben->is_sepa = true; // Assuming this is a SEPA beneficiary
            $ben->save();

            $beneficiary_type = [
                "individual" => "RETAIL",
                "company" => "CORPORATE"
            ];

            $bank_beneficiary_data = [
                "sort_code" => $request->get('sort_code'),
                "beneficiary_type" => $beneficiary_type[$request->get("beneficiary_type")]
            ];

            $enabledModules = Common::getEnabledModules();
            $enabled_provider_map = [
                'openpayd' => in_array('OpenPayd', $enabledModules),
                'lorum' => in_array('Lorum', $enabledModules),
                'zenus' => in_array('Zenus', $enabledModules),
            ];

            // Override virtual bank for beneficiary
            $beneficiary_currency = Currency::find($request->get('currency_short_code'))->first();
            $currency = $beneficiary_currency->short;
            $virtual_bank_provider = $ben->currency->virtual_iban_provider;
            $override_provider = $client->clientAccounts()->where('account_currency', $ben->account_currency)->first()->virtual_bank_provider ?? null;

            if ($override_provider) {
                $is_override_enabled = $enabled_provider_map[$override_provider] ?? false;

                if ($override_provider && $is_override_enabled) {
                    $virtual_bank_provider = $override_provider;
                }
            }

            if ($virtual_bank_provider == 'openpayd') {
                $linkedben = $openpaydHelper->createBeneficiary($ben, $bank_beneficiary_data);

                if (!empty($linkedben->errorCode)) {
                    DB::rollBack();
                    $errorMessage = isset($linkedben->errorCode) ? $linkedben->errorCode : 'Error occurred while creating beneficiary.';
                    return response()->json([
                        'status' => false,
                        'message' => $errorMessage,
                    ], 500);
                }

                // DB::commit();

                if (!empty($linkedben)) {
                    $openpayd_beneficiary =  VirtualBankBeneficiary::where('bank_beneficiary_id', $linkedben->id)->first();
                    $openpayd_beneficiary->parent_beneficiary_id = $ben->id;
                    $openpayd_beneficiary->save();
                }
            } else if ($virtual_bank_provider == 'lorum') {
                $lorumController = new LorumController();
                $linkedben = $lorumController->createLorumBeneficiary($request, $ben);

                if (!empty($linkedben->errorCode)) {
                    DB::rollBack();
                    $errorMessage = isset($linkedben->errorCode) ? $linkedben->errorCode : 'Error occurred while creating beneficiary.';
                    return response()->json([
                        'status' => false,
                        'message' => $errorMessage,
                    ], 500);
                }

                // DB::commit();
            } else if ($virtual_bank_provider == 'zenus') {
                $ibanController = new IBANController();
                $linkedben = $ibanController->createVirtualBeneficiary($request, $ben, $virtual_bank_provider);

                if (!empty($linkedben->errorCode)) {
                    DB::rollBack();
                    $errorMessage = isset($linkedben->errorCode) ? $linkedben->errorCode : 'Error occurred while creating beneficiary.';
                    return response()->json([
                        'status' => false,
                        'message' => $errorMessage,
                    ], 500);
                }
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Beneficiary created successfully.',
            ], 200);
        } catch (Exception $e) {
            Log::error('------------------------------------------------');
            Log::error($e->getMessage() . ' | Line: ' . $e->getLine());
            Log::error($e->getTraceAsString());
            return redirect(url()->previous())->with('error', 'Error creating New Beneficiary: ' . $e->getMessage());
        }
    }

    public function getClientBeneficiaries($client_id, $currency_id)
    {
        $client = Client::find($client_id);
        if (!$client) {
            return response()->json([
                'status' => 'success',
                'error' => 'Client not found.'
            ], 404);
        }

        $beneficiaries = ClientBeneficiary::select('id', 'iban', 'swift_code', 'account_currency', 'account_number', 'name', 'bank_name', 'bank_address')
            ->where('client_id', $client_id)
            ->where('account_currency', $currency_id)
            ->where('is_sepa', true)
            ->where('status', 'Approved')
            ->orderBy('name', 'asc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $beneficiaries,
        ], 200);
    }

    public function saveTransactionNote(Request $request)
    {
        $this->validate($request, [
            'transaction_notes' => 'required|string',
        ]);

        try {
            $transaction_id = TRRequest::find($request->get('transaction_request_id'))->id;
            $transaction_note = new AccountTransactionNote();
            $transaction_note->transaction_request_id = $transaction_id;
            $transaction_note->note = $request->get('transaction_notes');
            $transaction_note->created_by = Auth::user()->id;
            $transaction_note->save();

            return redirect()->back()->with('success', 'Transaction note saved successfully.');
        } catch (Exception $e) {
            Log::error('------------------------------------------------');
            Log::error($e->getMessage() . ' | Line: ' . $e->getLine());
            Log::error($e->getTraceAsString());
            return redirect()->back()->with('error', 'Failed to save transaction note. Please try again later.');
        }
    }

    public function getTransactionNotes($id)
    {
        $notes = AccountTransactionNote::where('transaction_request_id', $id)
            ->where('is_deleted', false)
            ->with('creator:id,name')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($note) {
                return [
                    'note' => $note->note,
                    'created_at' => Carbon::parse($note->created_at)->format('d M, Y H:i:s'),
                    'creator' => $note->creator ? ['name' => $note->creator->name] : null,
                ];
            });

        return response()->json($notes);
    }

    public function isVirtualBankTransactionExists($account_transaction_id)
    {
        return VirtualBankTransaction::where('parent_transaction_id', $account_transaction_id)->exists();
    }

    public static function calculateTransactionFees($currency, $amount)
    {
        $transaction_fee_rate = $currency->transaction_fee_rate ?? 0;
        $bank_fee_rate = $currency->bank_fee_rate ?? 0;

        // calculate the fee
        $transaction_amount = $amount;
        $transaction_fee_amount = ($transaction_amount * $transaction_fee_rate) / 100;
        $bank_fee_amount = ($transaction_amount * $bank_fee_rate) / 100;

        // Currency Treshold fees
        $transaction_fee_threshold = $currency->transaction_fee_threshold ?? 0;
        $bank_fee_threshold = $currency->bank_fee_threshold ?? 0;

        // Charge calculated fee if it exceeds currency_fee_threshold; otherwise, charge the threshold.
        if ($transaction_fee_amount < $transaction_fee_threshold) {
            $transaction_fee_amount = $transaction_fee_threshold;
        }

        // Charge calculated fee if it exceeds currency_fee_threshold; otherwise, charge the threshold.
        if ($transaction_fee_amount < $transaction_fee_threshold) {
            $transaction_fee_amount = $transaction_fee_threshold;
        }

        if ($bank_fee_amount < $bank_fee_threshold) {
            $bank_fee_amount = $bank_fee_threshold;
        }

        return [
            'transaction_fee_amount' => round($transaction_fee_amount, 2),
            'bank_fee_amount' => round($bank_fee_amount, 2),
            'total_fee_amount' => round($transaction_fee_amount + $bank_fee_amount, 2)
        ];
    }
}
