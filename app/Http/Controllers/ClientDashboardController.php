<?php

namespace App\Http\Controllers;

use App\Helpers\Common;
use App\Helpers\IDORHelper;
use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\Client;
use App\Models\Currency;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ClientDashboardController extends Controller
{
    protected $currencyIds = null;

    protected $transactionNatures = [];

    public function __construct()
    {
        if (in_array("Crypto", \App\Helpers\Common::getEnabledModules())) {
            $this->currencyIds = Currency::select("id")->get();
            $this->transactionNatures = ["Cheque", "TT", "Internal", "Maintenance Fee", "Buy", "Sell", "Withdraw", "Deposit", "Deposit Fee"];
        } else {
            $this->currencyIds = Currency::where("is_crypto", false)->select("id")->get();
            $this->transactionNatures = ["Cheque", "TT", "Internal"];
        }

        if (in_array("VistaMoney", \App\Helpers\Common::getEnabledModules())) {
            array_push($this->transactionNatures, 'Top-up');
        }
    }

    public function transactionsList()
    {
        $accounts = Account::where("client_id", Auth::user()->client_id)
            ->whereIn('account_currency', $this->currencyIds)
            ->pluck("id")
            ->toArray();

        $transactions = AccountTransaction::whereIn("account_id", $accounts)
            ->whereIn("transaction_nature", $this->transactionNatures)
            ->orderBy("created_at", "desc")
            ->get();

        return view("client-portal.finance-management.transaction-list", compact('transactions'));
    }

    public function transactionsView($id)
    {
        $trn = AccountTransaction::find($id);

        return view("client-portal.finance-management.transaction-view", compact('trn'));
    }

    public function accountStatement()
    {
        $ids = Common::getClientIdsByUserId(Auth::user()->id, true);
        $client_ids = Client::select("id")->where('status', 'Approved')->whereIn("id", $ids)->pluck("id")->toArray();

        $accounts = Account::whereIn("client_id", $client_ids)
            ->whereIn('account_currency', $this->currencyIds)
            ->where('is_active', true)
            ->select("id", "title", "account_number", "account_currency")
            ->get();

        $periods = Common::getAccountStatementPeriods();

        if ($accounts->count() > 0) {
            $transactions = AccountTransaction::where("account_id", $accounts->first()->id)
                ->whereIn("transaction_nature", $this->transactionNatures)
                ->where("status", "Approved")
                ->where("is_hidden_for_clients", false)
                ->take(-10)
                ->orderBy("transaction_date", "asc")
                ->get();

            if ($transactions->first()) {
                $balance = Common::getAccountBalanceTillDate($accounts->first()->id, $transactions->first()->transaction_date);
            } else {
                $balance = 0;
            }
        } else {
            $balance = 0;
            $transactions = [];
        }

        $registered_date = Carbon::parse(Auth::user()->created_at)->format("d/m/Y");

        $from_date = '';
        $to_date = '';

        if ($transactions) {
            $first = $transactions->first();
            $last = $transactions->last();

            $from_date =  ($first) ? Carbon::parse($first->transaction_date)->format("d/m/Y") : '';
            $to_date = ($last) ? Carbon::parse($last->transaction_date)->format("d/m/Y") : '';
        }

        return view("client-portal.finance-management.statement-search", compact('balance', 'transactions', 'periods', 'accounts', 'registered_date', 'from_date', 'to_date'));
    }

    public function accountStatementGenerate(Request $request)
    {
        try {
            if ($request->has('from_date') && $request->has('to_date') && $request->filled('from_date') && $request->filled('to_date')) {
                if (!Common::validateDate($request->get('from_date'), $request->get('to_date'))) {
                    return redirect('/client/dashboard/account-statement')->with('error', 'Invalid date format');
                }
            }
            $belongsToClient = IDORHelper::isAccountBelongToUser(Auth::user(), $request->get("account_id"));

            if ($belongsToClient) {

                $client_ids = Common::getClientIdsByUserId(Auth::user()->id, true);
                $accounts = Account::whereIn("client_id", $client_ids)
                    ->whereIn('account_currency', $this->currencyIds)
                    ->where('is_active', true)
                    ->select("id", "title", "account_number", "account_currency")
                    ->get();

                if ($request->get("account_id") > 0) {
                    $transactions = AccountTransaction::whereIn("transaction_nature", $this->transactionNatures)
                        ->where("account_id", $request->get("account_id"))
                        ->where("status", "Approved")
                        ->where("is_hidden_for_clients", false);
                } else {
                    $transactions = AccountTransaction::whereIn("transaction_nature", $this->transactionNatures)
                        ->where("status", "Approved")
                        ->where("is_hidden_for_clients", false);
                }

                $start_date = Carbon::parse(Auth::user()->created_at)->format("d/m/Y");
                $periods = Common::getAccountStatementPeriods();

                if ($request->get("period") > 0) {

                    $from = Carbon::now()->subDays($request->get("period") * 30)->format("Y-m-d 00:00:00");
                    $to = Carbon::now()->format("Y-m-d 23:59:59");

                    $transactions->where("transaction_date", ">=", $from);
                    $transactions->where("transaction_date", "<=", $to);
                }

                if ($request->get("from_date") != "") {
                    $from = Carbon::createFromFormat("d/m/Y", $request->get("from_date"))->format("Y-m-d 00:00:00");
                    $transactions->where("transaction_date", ">=", $from);
                }

                if ($request->get("to_date") != "") {
                    $to = Carbon::createFromFormat("d/m/Y", $request->get("to_date"))->format("Y-m-d 23:59:59");
                    $transactions->where("transaction_date", "<=", $to);
                } else {
                    $to = Carbon::now()->format("Y-m-d 23:59:59");
                }

                if ($request->get("account_id") > 0) {
                    $transactions->where("account_id", $request->get("account_id"));
                }

                $transactions = $transactions->orderBy("transaction_date", "asc")->get();

                if ($transactions->count() > 0) {
                    $first_trn = $transactions->first();
                    $balance = Common::getAccountBalanceTillDate($request->get("account_id"), $first_trn->transaction_date);
                } else {
                    $balance = Common::getAccountBalanceTillDate($request->get("account_id"), $to);
                }

                $request = $request->all();

                return view("client-portal.finance-management.account-statement", compact('balance', 'transactions', 'accounts', 'request', 'periods', 'start_date', 'to'));
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

    public function accountStatementPDF(Request $request)
    {
        try {
            $belongsToClient = IDORHelper::isAccountBelongToUser(Auth::user(), $request->get("account_id"));

            if ($belongsToClient) {
                $transactions = AccountTransaction::orderBy("transaction_date", "asc")
                    ->where("status", "Approved")
                    ->where("is_hidden_for_clients", false);

                if ($request->get("period") > 0) {
                    $start_date = Carbon::now()->subDays($request->get("period") * 30)->format("Y-m-d 00:00:00");
                    $to_date = Carbon::now()->format("Y-m-d 23:59:59");
                }

                if ($request->get("from_date") != "") {
                    $start_date = Carbon::createFromFormat("d/m/Y", $request->get("from_date"))->format("Y-m-d 00:00:00");
                }

                if ($request->get("to_date") != "") {
                    $to_date = Carbon::createFromFormat("d/m/Y", $request->get("to_date"))->format("Y-m-d 23:59:59");
                } else {
                    $to_date = Carbon::now()->format("Y-m-d 23:59:59");
                }

                if ($request->get("account_id") > 0) {
                    $transactions->where("account_id", $request->get("account_id"));
                }

                $transactions->where("transaction_date", ">=", $start_date);
                $transactions->where("transaction_date", "<=", $to_date);

                $transactions = $transactions->get();

                if ($transactions->count() > 0) {
                    $first_trn = $transactions->first();
                    $last_trn = $transactions->last();

                    $starting_balance = Common::getAccountBalanceTillDate($request->get("account_id"), $first_trn->transaction_date);
                    $ending_balance = Common::getAccountEndingBalanceTillDate($request->get("account_id"), $last_trn->transaction_date);
                    $balance = $starting_balance;
                } else {
                    $starting_balance = Common::getAccountBalanceTillDate($request->get("account_id"), $start_date);
                    $ending_balance = Common::getAccountBalanceTillDate($request->get("account_id"), $to_date);
                    $balance = $ending_balance;
                }

                if ($request->get("account_id")) {
                    $account = Account::find($request->get("account_id"));
                    $credit = Common::getTotalCredit($request->get("account_id"), $start_date, $to_date);
                    $debit = Common::getTotalDebit($request->get("account_id"), $start_date, $to_date);
                }

                $view = view("pdfs.account-statement", compact('balance', 'transactions', 'account', 'starting_balance', 'ending_balance', 'start_date', 'to_date', 'credit', 'debit'));
                $pdf = PDF::loadHTML($view);
                $file_name = $account->account_number . "_" . date("dmY") . ".pdf";
                return $pdf->download($file_name);
            }
        } catch (Exception $e) {
            Common::logError($e, __METHOD__, __LINE__);

            if (401 === $e->getCode()) {
                return redirect('/client/dashboard/account-statement')->with('error', 'You are not authorized to access this page.');
            } else {
                return back()->with('error', 'Something went wrong, please try again later.');
            }
        }
    }
}
