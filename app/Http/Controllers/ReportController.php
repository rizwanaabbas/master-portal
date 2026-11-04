<?php

namespace App\Http\Controllers;

use App\Exports\AccountTransactionsReportExport;
use App\Exports\AccountTransactionsReportExport6Months;
use App\Exports\AccountWiseExport;
use App\Exports\AllBankWiseReportExport;
use App\Exports\AllClientsReportExport;
use App\Exports\CurrencyWiseV2Export;
use App\Exports\DailyAllBanksExport;
use App\Exports\DailyAllClientsWiseExport;
use App\Exports\DailyCurrencyWiseExport;
use App\Exports\UserClientWiseExport;
use App\Helpers\Common;
use App\Helpers\EmailNotification;
use App\Helpers\FeeHelper;
use App\Helpers\FileFirewall;
use App\Helpers\OnboardingFeeHelper;
use App\Helpers\S3Helper;
use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\Bank;
use App\Models\Client;
use App\Models\ClientUser;
use App\Models\MaintenanceFeeReport;
use App\Models\VostroBanksReport;
use App\Models\Currency;
use App\Models\OnboardingFeeRecords;
use App\Models\User;
use App\Models\OwnCompany;
use Carbon\Carbon;
use Exception;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Maatwebsite\Excel\Facades\Excel;
use Mews\Purifier\Facades\Purifier;
use Illuminate\Support\Facades\Log;
use stdClass;
use App\Rules\SupportingDocumentsRule;
use Illuminate\Pagination\LengthAwarePaginator;

class ReportController extends Controller
{
    protected $currencyIds = null;
    protected $companyName = null;

    protected $transactionNatures = [];

    public function __construct()
    {
        //account transaction wise
        $this->middleware('permission:account transaction wise', ['only' => ['accountTransactions', 'accountTransactionsSearch']]);
        // $this->middleware('permission:client account statement report', ['only' => 'clientAccountStatement']);

        if (in_array("Crypto", \App\Helpers\Common::getEnabledModules())) {
            $this->currencyIds = Currency::select("id")->pluck("id")->toArray();
            $this->transactionNatures = ["Cheque", "TT", "Internal", "Buy", "Sell", "Withdraw", "Deposit", "Deposit Fee", "Maintenance Fee"];
        } else {
            $this->currencyIds = Currency::where("is_crypto", false)->select("id")->pluck("id")->toArray();
            $this->transactionNatures = ["Cheque", "TT", "Internal", "Maintenance Fee"];
        }
        $this->companyName = OwnCompany::first();

        if ($this->companyName && empty($this->companyName->short_name)) {
            $this->companyName = "";
        }
        $enabledModules = \App\Helpers\Common::getEnabledModules();
        if (array_intersect(['VistaMoney', 'MatchMove', 'E-Money'], $enabledModules)) {
            $this->transactionNatures[] = 'Top-up';
        }
    }

    public function accountTransactions6Months()
    {
        $transactions = $this->get6MonthsTransactions('Credit')->paginate(10);

        $request = [
            'transaction_type' => '',
            'per_page' => 10,
            'search_string' => ''
        ];

        Session::forget('transaction_type');

        return view("reports.account-transactions-6-months", compact('transactions', 'request'));
    }

    public function accountTransactionsReport6Months($filterApplied = null)
    {
        if ($filterApplied == "true") {
            $transactionType = Session::get('transaction_type');
        } else {
            $transactionType = "Credit";
        }

        $transactions = $this->get6MonthsTransactions($transactionType)->get();

        return Excel::download(new AccountTransactionsReportExport6Months($transactions, $transactionType), ($transactionType == "Credit" ? "Inward" : "Owtward") . " Transactions Report - 1st June 2024 to 31st Dec 2024.xlsx");
    }

    protected function get6MonthsTransactions($transactionType)
    {
        $transactions = AccountTransaction::orderBy("transaction_date", "desc")
            ->where('status', 'Approved')
            ->whereIn("currency_id", $this->currencyIds)
            ->whereIn("transaction_nature", ["Cheque", "TT", "Withdraw", "Deposit"])
            ->where("transaction_type", $transactionType)
            ->where(function ($query) {
                $query->where('is_fee_transaction', 0)
                    ->orwhereNull('is_fee_transaction');
            });

        $from = Carbon::createFromFormat("Y-m-d", "2024-06-01")->format("Y-m-d 00:00:00");
        $transactions->where("transaction_date", ">=", $from);

        $to = Carbon::createFromFormat("Y-m-d", "2024-12-31")->format("Y-m-d 23:59:59");
        $transactions->where("transaction_date", "<=", $to);

        return $transactions;
    }

    public function accountTransactionsSearch6Months(Request $request)
    {
        $transaction_type = $request->get("transaction_type");

        Session::put('transaction_type', $transaction_type);

        $transactions = $this->get6MonthsTransactions($transaction_type);
        $perPage = $request->get("per_page");

        if ($request->get('search_string')) {
            $transactions->whereHas('account', function ($query) use ($request) {
                $query->where('account_number', 'LIKE', '%' . $request->get('search_string') . '%');
            })->orwhere('remarks', 'LIKE', '%' . $request->get('search_string') . '%')
                ->orwhere('amount', 'LIKE', '%' . $request->get('search_string') . '%');
        }

        if ($perPage == -1 || ($request->get('search_string') && $transactions->count() < 10)) {
            $perPage = $transactions->count();
        } elseif (!$request->get("per_page")) {
            $perPage = 10;
        }

        $transactions = $transactions->paginate($perPage);
        $request = $request->all();

        return view("reports.account-transactions-6-months", compact('transactions', 'request'));
    }

    public function currencyWise()
    {
        $currencies = Currency::where('is_active', true)->whereIn('id', $this->currencyIds)->get();

        $types = ["individual", "company"];
        $accountIds = Account::select("id")->where("is_active", true)->whereIn('type', $types)->whereIn('account_currency', $this->currencyIds)->get();




        $data = [];
        foreach ($currencies as $currency) {
            $credit = AccountTransaction::where("currency_id", $currency->id)
                ->where("transaction_type", "Credit")
                ->whereIn('account_id', $accountIds)
                ->whereIn("transaction_nature", $this->transactionNatures)
                ->where("status", "Approved")
                ->sum('amount');

            $debit = AccountTransaction::where("currency_id", $currency->id)
                ->where("transaction_type", "Debit")
                ->whereIn('account_id', $accountIds)
                ->whereIn("transaction_nature", $this->transactionNatures)
                ->where("status", "Approved")
                ->sum('amount');

            $data[] = [
                'currency' => Common::getCurrencyNameWithShort($currency->id),
                'accounts' => Common::getAccountsCountByCurrencyId($currency->id),
                'balance' => $credit - $debit,
                'is_crypto' => $currency->is_crypto
            ];
        }

        return view("reports.currency-wise-search", compact('data'));
    }

    public function currencyWiseV2()
    {
        $account_ids = Common::getAccountIdsWithoutBanks();
        $account_ids = count($account_ids) > 0 ? $account_ids : [0];
        $currencyIds = !empty($this->currencyIds) ? $this->currencyIds : [0];

        $results = DB::table('account_transactions')
            ->select('currency_id', 'transaction_type', DB::raw('SUM(amount) as amount'))
            ->where('status', 'Approved')
            ->whereIn('currency_id', $currencyIds)
            ->whereIn('account_id', $account_ids)
            ->groupBy('currency_id', 'transaction_type')
            ->orderBy('currency_id')
            ->get();

        $currencies = Currency::where('is_active', true)->whereIn('id', $this->currencyIds)->get();

        $data = [];
        if ($results) {
            $credits = [];
            $debits = [];
            foreach ($results as $record) {

                if ($record->transaction_type == "Credit") {
                    $credits[$record->currency_id] = $record->amount;
                }

                if ($record->transaction_type == "Debit") {
                    $debits[$record->currency_id] = $record->amount;
                }
            }

            // create response
            foreach ($currencies as $currency) {

                $credit = (isset($credits[$currency->id])) ? $credits[$currency->id] : 0;
                $debit = (isset($debits[$currency->id])) ? $debits[$currency->id] : 0;

                $data[] = [
                    'currency' => Common::getCurrencyNameWithShort($currency->id),
                    'accounts' => Common::getAccountsCountByCurrencyId($currency->id, true),
                    'balance' => $credit - $debit,
                    'is_crypto' => $currency->is_crypto
                ];
            }
        }

        Session::forget('from_date');
        Session::forget('to_date');

        return view("reports.currency-wise-search", compact('data'));
    }

    public function currencyWiseV2Excel($filterApplied = null)
    {
        if ($filterApplied == "true") {
            $fromDate = Session::get('from_date');
            $toDate = Session::get('to_date');
            $data = $this->getCurrencyWiseData($fromDate, $toDate);
        } else {
            $account_ids = Common::getAccountIdsWithoutBanks();
            $account_ids = count($account_ids) > 0 ? $account_ids : [0];
            $currencyIds = !empty($this->currencyIds) ? $this->currencyIds : [0];
            $currencies = Currency::where('is_active', true)->whereIn('id', $this->currencyIds)->get();

            $results = DB::table('account_transactions')
                ->select('currency_id', 'transaction_type', DB::raw('SUM(amount) as amount'))
                ->where('status', 'Approved')
                ->whereIn('currency_id', $currencyIds)
                ->whereIn('account_id', $account_ids)
                ->groupBy('currency_id', 'transaction_type')
                ->orderBy('currency_id')
                ->get();

            $data = [];
            if ($results) {
                $credits = [];
                $debits = [];
                foreach ($results as $record) {

                    if ($record->transaction_type == "Credit") {
                        $credits[$record->currency_id] = $record->amount;
                    }

                    if ($record->transaction_type == "Debit") {
                        $debits[$record->currency_id] = $record->amount;
                    }
                }

                foreach ($currencies as $currency) {

                    $credit = (isset($credits[$currency->id])) ? $credits[$currency->id] : 0;
                    $debit = (isset($debits[$currency->id])) ? $debits[$currency->id] : 0;

                    $data[] = [
                        'currency' => Common::getCurrencyNameWithShort($currency->id),
                        'accounts' => Common::getAccountsCountByCurrencyId($currency->id),
                        'balance' => $credit - $debit,
                        'is_crypto' => $currency->is_crypto
                    ];
                }
            }
        }
        return Excel::download(new CurrencyWiseV2Export($data), "Currency Deposit Report - Reports Management -  " . $this->companyName->short_name . ".xlsx");
    }

    public function currencyWiseSearchV2(Request $request)
    {
        $fromDate = $request->get("from_date");
        $toDate = $request->get("to_date");

        if (!Common::validateDate($fromDate, $toDate)) {
            return redirect('/reports-management/client/currency-wise')->with('error', 'Invalid date format');
        }

        Session::put('from_date', $fromDate);
        Session::put('to_date', $toDate);

        $data = $this->getCurrencyWiseData($fromDate, $toDate);

        $request = $request->all();

        return view("reports.currency-wise-search", compact('data', 'request'));
    }

    private function getCurrencyWiseData($fromDate, $toDate)
    {
        $account_ids = Common::getAccountIdsWithoutBanks();
        $currencies = Currency::where('is_active', true)->whereIn('id', $this->currencyIds)->get();

        if ($fromDate == "" && $toDate == "") {
            $from_date = Carbon::now()->subDays(30)->format("Y-m-d 00:00:00");
            $to_date = Carbon::now()->format("Y-m-d 23:59:59");
        } else {
            $fd = Purifier::clean($fromDate);
            $td = Purifier::clean($toDate);
            $from_date = Carbon::createFromFormat("d/m/Y", $fd)->format("Y-m-d 00:00:00");
            $to_date = Carbon::createFromFormat("d/m/Y", $td)->format("Y-m-d 23:59:59");
        }

        $account_ids = count($account_ids) > 0 ? $account_ids : [0];
        $currencyIds = !empty($this->currencyIds) ? $this->currencyIds : [0];

        $results = DB::table('account_transactions')
            ->select('currency_id', 'transaction_type', DB::raw('SUM(amount) as amount'))
            ->where('status', 'Approved')
            ->whereIn('currency_id', $currencyIds)
            ->whereIn('account_id', $account_ids)
            ->whereBetween('transaction_date', [$from_date, $to_date])
            ->groupBy('currency_id', 'transaction_type')
            ->orderBy('currency_id')
            ->get();

        $data = [];
        if ($results) {
            $credits = [];
            $debits = [];
            foreach ($results as $record) {

                if ($record->transaction_type == "Credit") {
                    $credits[$record->currency_id] = $record->amount;
                }

                if ($record->transaction_type == "Debit") {
                    $debits[$record->currency_id] = $record->amount;
                }
            }

            // create response
            foreach ($currencies as $currency) {

                $credit = (isset($credits[$currency->id])) ? $credits[$currency->id] : 0;
                $debit = (isset($debits[$currency->id])) ? $debits[$currency->id] : 0;

                $data[] = [
                    'currency' => Common::getCurrencyNameWithShort($currency->id),
                    'accounts' => Common::getAccountsCountByCurrencyId($currency->id, true),
                    'balance' =>  Common::getCurrencyBalanceTillDate($currency->id, $from_date, $account_ids) +  $credit - $debit,
                    'is_crypto' => $currency->is_crypto
                ];
            }
        }

        return $data;
    }

    public function dailyCurrencyWise(Request $request)
    {
        if ($request->has('from_date') && $request->has('to_date')) {
            if (!Common::validateDate($request->get('from_date'), $request->get('to_date'))) {
                return redirect('/reports-management/client/daily-currency-wise')->with('error', 'Invalid date format');
            }

            $from_date = Carbon::createFromFormat("d/m/Y", $request->get("from_date"))->format("Y-m-d 00:00:00");
            $to_date = Carbon::createFromFormat("d/m/Y", $request->get("to_date"))->format("Y-m-d 23:59:59");
        } else {
            $from_date = Carbon::now()->subDays(30)->format("Y-m-d 00:00:00");
            $to_date = Carbon::now()->format("Y-m-d 23:59:59");
        }

        $account_ids = Common::getAccountIdsWithoutBanks();

        $currencies = Currency::where('is_active', true)->whereIn('id', $this->currencyIds)->get();

        // Fetch the list of active currencies
        $currencies = Currency::where('is_active', true)
            ->whereIn('id', $this->currencyIds)
            ->get();

        // Create dates between two dates
        $period = CarbonPeriod::create($from_date, $to_date);

        $data = [];

        foreach ($period as $date) {
            $db_date = $date->format('Y-m-d');
            $balances = [];

            // Group transactions by currency ID and transaction type
            $transactions = AccountTransaction::whereIn("transaction_nature", $this->transactionNatures)
                ->where("transaction_date", $db_date)
                ->where("status", "Approved")
                ->whereIn('account_id', $account_ids)
                ->selectRaw('SUM(CASE WHEN transaction_type = "Credit" THEN amount ELSE 0 END) as credit')
                ->selectRaw('SUM(CASE WHEN transaction_type = "Debit" THEN amount ELSE 0 END) as debit')
                ->groupBy('currency_id')
                ->get();

            foreach ($currencies as $currency) {
                // Find the corresponding transaction for the currency
                $currencyTransaction = $transactions->where('currency_id', $currency->id)->first();

                $credit = $currencyTransaction ? $currencyTransaction->credit : 0;
                $debit = $currencyTransaction ? $currencyTransaction->debit : 0;

                // Get currency balance till date
                $prev_balance = Common::getCurrencyBalanceTillDate($currency->id, $db_date, $account_ids);

                $balances[] = [
                    'balance' => ($credit - $debit) + $prev_balance,
                    'currency' => $currency->name . " (" . $currency->short . ")",
                    'is_crypto' => $currency->is_crypto
                ];
            }

            $data[$db_date] = $balances;
        }

        $request['from_date'] = Carbon::createFromFormat("Y-m-d H:i:s", Carbon::parse($from_date))->format("d/m/Y");
        $request['to_date'] = Carbon::createFromFormat("Y-m-d H:i:s", Carbon::parse($to_date))->format("d/m/Y");


        if ($request->has('export_excel') && $request->input('export_excel') === 'excel') {
            return Excel::download(new DailyCurrencyWiseExport($data), "Daily Currency Deposit Report - Reports Management - " . $this->companyName->short_name . ".xlsx");
        }

        if ($request->has('export_pdf') && $request->input('export_pdf') === 'pdf') {
            $pdf = Pdf::loadView('reports.partials.pdf.daily-currency-wise-pdf', compact('data'))
                ->setPaper('A4', 'portrait');
            return $pdf->download("Daily Currency Deposit Report - Reports Management - " . $this->companyName->short_name . ".pdf");
        }

        return view("reports.daily-currency-wise", compact('data', 'request', 'currencies'));
    }

    public function currencyWiseGenerate(Request $request)
    {
        $currencies = Currency::where('is_active', true)->whereIn('id', $this->currencyIds)->get();
        $types = ["individual", "company"];
        $accountIds = Account::select("id")->where("is_active", true)->whereIn('type', $types)->whereIn('account_currency', $this->currencyIds)->get();

        $data = [];
        foreach ($currencies as $currency) {
            $credit = AccountTransaction::where("currency_id", $currency->id);
            $debit = AccountTransaction::where("currency_id", $currency->id);

            if ($request->get("from_date") != "") {
                $from = Carbon::createFromFormat("d/m/Y", $request->get("from_date"))->format("Y-m-d 00:00:00");
                $credit->where("transaction_date", ">=", $from);
                $debit->where("transaction_date", ">=", $from);
            }

            if ($request->get("to_date") != "") {
                $to = Carbon::createFromFormat("d/m/Y", $request->get("to_date"))->format("Y-m-d 23:59:59");
                $credit->where("transaction_date", "<=", $to);
                $debit->where("transaction_date", "<=", $to);
            }

            $credit = $credit->where("transaction_type", "Credit")
                ->whereIn('account_id', $accountIds)
                ->whereIn("transaction_nature", $this->transactionNatures)
                ->where("status", "Approved")
                ->sum('amount');

            $debit = $debit->where("transaction_type", "Debit")
                ->whereIn('account_id', $accountIds)
                ->whereIn("transaction_nature", $this->transactionNatures)
                ->where("status", "Approved")
                ->sum('amount');

            $data[] = [
                'currency' => Common::getCurrencyNameWithShort($currency->id),
                'accounts' => Common::getAccountsCountByCurrencyId($currency->id),
                'balance' => $credit - $debit,
                'is_crypto' => $currency->is_crypto
            ];
        }

        return view("reports.currency-wise", compact('data', 'request'));
    }

    public function accountWise(Request $request)
    {
        $tr_currencies = Account::select("account_currency")
            ->whereIn('account_currency', $this->currencyIds)
            ->where('is_active', true)
            ->distinct()
            ->pluck('account_currency')
            ->toArray();

        $currencies = Currency::whereIn('id', $tr_currencies)->get();
        $clients = Client::where('is_bank', false);
        $clients = $clients->where("status", "Approved")->get();

        return view("reports.client-account-wise", compact('currencies', 'clients', 'request'));
    }

    public function accountWiseSearch(Request $request)
    {
        $fromDate = $request->get("from_date");
        $toDate = $request->get("to_date");

        if (!Common::validateDate($fromDate, $toDate)) {
            return redirect('/reports-management/client/account-wise')->with('error', 'Invalid date format');
        }

        // Parse the original date string and set default time to midnight
        $formattedFormDate = Carbon::createFromFormat('d/m/Y', $fromDate)->startOfDay();
        $formattedToDate = Carbon::createFromFormat('d/m/Y', $toDate)->endOfDay(); // Updated to end of the day

        // Format the date to the desired format
        $formattedFormDate = $formattedFormDate->format('Y-m-d H:i:s');
        $formattedToDate = $formattedToDate->format('Y-m-d H:i:s');

        Session::put('from_date', $fromDate);
        Session::put('to_date', $toDate);

        $tr_currencies = Account::select("account_currency")
            ->whereIn('account_currency', $this->currencyIds)
            ->where('is_active', true)
            ->distinct()
            ->pluck('account_currency')
            ->toArray();

        $currencies = Currency::whereIn('id', $tr_currencies)->get();

        $clients = Client::where('is_bank', false)
            ->whereBetween('created_at', [$formattedFormDate, $formattedToDate]) // Filtering based on the date range
            ->where("status", "Approved")
            ->get();
        // dd($clients);
        // dd([$formattedFormDate, $formattedToDate]);
        return view("reports.client-account-wise", compact('currencies', 'clients', 'request'));
    }

    public function accountWiseReport()
    {
        $tr_currencies = Account::select("account_currency")
            ->whereIn('account_currency', $this->currencyIds)
            ->where('is_active', true)
            ->distinct()
            ->pluck('account_currency')
            ->toArray();
        $currencies = Currency::whereIn('id', $tr_currencies)->get();

        if (request()->query("from_date") && request()->query("to_date")) {
            $fromDate = request()->query("from_date");
            $toDate = request()->query("to_date");
            // Parse the original date string and set default time to midnight
            $formattedFormDate = Carbon::createFromFormat('d/m/Y', $fromDate)->startOfDay();
            $formattedToDate = Carbon::createFromFormat('d/m/Y', $toDate)->endOfDay(); // Updated to end of the day

            // Format the date to the desired format
            $formattedFormDate = $formattedFormDate->format('Y-m-d H:i:s');
            $formattedToDate = $formattedToDate->format('Y-m-d H:i:s');

            Session::put('from_date', $fromDate);
            Session::put('to_date', $toDate);

            $clients = Client::where('is_bank', false)
                ->whereBetween('created_at', [$formattedFormDate, $formattedToDate])
                ->where("status", "Approved")
                ->get();
        } else {

            $clients = Client::where('is_bank', false)->where("status", "Approved")->get();
        }

        if (request()->has('format') && request()->query('format') === 'pdf') {
            $pdf = Pdf::loadView('reports.partials.pdf.account-wise-pdf', compact('currencies', 'clients'))
                ->setPaper('A4', 'portrait');
            return $pdf->download("Client Deposit List Report - Reports Management - " . $this->companyName->short_name . ".pdf");
        } else if (request()->has('format') && request()->query('format') === 'excel') {
            return Excel::download(new AccountWiseExport($currencies, $clients), "Client Deposit List Report - Reports Management - " . $this->companyName->short_name . ".xlsx");
        }
    }

    public function allClientsWise(Request $request)
    {
        $tr_currencies = AccountTransaction::whereIn('currency_id', $this->currencyIds)
            ->select("currency_id")
            ->distinct()
            ->pluck('currency_id')
            ->toArray();

        $currencies = Currency::whereIn('id', $tr_currencies)->get();
        Session::forget('from_date');
        Session::forget('to_date');
        $clients = Client::where('is_bank', false)->where("status", "Approved")->paginate(10);

        return view("reports.all-clients-wise-search", compact('currencies', 'clients', 'request'));
    }

    public function getClientsReport($filterApplied = null)
    {
        if ($filterApplied == "true") {
            $fromDate = Session::get('from_date');
            $toDate = Session::get('to_date');
            $currencies = $this->getFilteredCurrencies($fromDate, $toDate)->get();
        } else {
            $tr_currencies = AccountTransaction::whereIn('currency_id', $this->currencyIds)
                ->select("currency_id")
                ->distinct()
                ->pluck('currency_id')
                ->toArray();

            $currencies = Currency::whereIn('id', $tr_currencies)->get();
        }

        $clients = Client::where('is_bank', false)->where("status", "Approved")->get();

        if (request()->has('format') && request()->query('format') === 'pdf') {
            $pdf = Pdf::loadView('reports.partials.pdf.all-clients-wise-pdf', compact('currencies', 'clients'))
                ->setPaper('A4', 'landscape');
            return $pdf->download("Client Wise Report - Reports Management - " . $this->companyName->short_name . ".pdf");
        } else {

            return Excel::download(new AllClientsReportExport($currencies, $clients), "Client Wise Report - Reports Management - " . $this->companyName->short_name . ".xlsx");
        }
    }

    public function allClientsWiseGenerate(Request $request)
    {
        if ($request->has('from_date') && $request->has('to_date') && $request->filled('from_date') && $request->filled('to_date')) {
            if (!Common::validateDate($request->get('from_date'), $request->get('to_date'))) {
                return redirect('/reports-management/client/all-clients')->with('error', 'Invalid date format');
            }
        }

        $fromDate = $request->get("from_date");
        $toDate = $request->get("to_date");

        Session::put('from_date', $fromDate);
        Session::put('to_date', $toDate);

        $currencies = $this->getFilteredCurrencies($fromDate, $toDate);
        $currencies_array = $currencies->pluck('id')->toArray();
        $currencies = $currencies->get();

        $clients = new LengthAwarePaginator([], 0, 10);

        if ($currencies->count() > 0) {
            $perPage = $request->get("per_page");
            $clients = Client::whereHas('clientAccounts.currency', function ($query) use ($currencies_array) {
                $query->whereIn('id', $currencies_array);
            });

            if ($request->get('search_string')) {
                $clients->where('name', 'LIKE', '%' . $request->get('search_string') . '%');
            }

            $clients = Client::where('is_bank', false)->where("status", "Approved");

            if ($perPage == -1 || ($request->get('search_string') && $clients->count() < 10)) {
                $perPage = $clients->count();
            } elseif (!$request->get("per_page")) {
                $perPage = 10;
            }

            $clients = $clients->paginate($perPage);
        }

        return view("reports.all-clients-wise", compact('currencies', 'clients', 'request'));
    }

    private function getFilteredCurrencies($fromDate, $toDate)
    {
        $tr_currencies = AccountTransaction::whereIn('currency_id', $this->currencyIds);

        if ($fromDate) {
            $from = Carbon::createFromFormat("d/m/Y", $fromDate)->format("Y-m-d 00:00:00");
            $tr_currencies->where("transaction_date", ">=", $from);
        }

        if ($toDate) {
            $to = Carbon::createFromFormat("d/m/Y", $toDate)->format("Y-m-d 23:59:59");
            $tr_currencies->where("transaction_date", "<=", $to);
        }

        $tr_currencies = $tr_currencies->select("currency_id")
            ->distinct()
            ->pluck('currency_id')
            ->toArray();

        $currencies = Currency::whereIn('id', $tr_currencies);

        return $currencies;
    }

    public function userClientWise()
    {
        $user_ids = ClientUser::join('clients', 'client_users.client_id', '=', 'clients.id')
            ->where('clients.status', 'Approved')
            ->select('client_users.user_id')
            ->distinct()
            ->pluck('user_id')
            ->toArray();
        $users = User::whereIn("id", $user_ids)->get();
        $tr_currencies = AccountTransaction::whereIn('currency_id', $this->currencyIds)
            ->select("currency_id")
            ->distinct()
            ->pluck('currency_id')
            ->toArray();

        $currencies = Currency::whereIn('id', $tr_currencies)->get();

        return view("reports.user-clients-search", compact('currencies', 'users'));
    }

    public function userClientWiseSearch(Request $request)
    {
        $user_id = $request->get("user_id");
        $user_ids = ClientUser::join('clients', 'client_users.client_id', '=', 'clients.id')
            ->where('clients.status', '!=', 'Draft')
            ->select('client_users.user_id')
            ->distinct()
            ->pluck('user_id')
            ->toArray();
        $users = User::whereIn("id", $user_ids)->get();

        $client_ids = ClientUser::where("user_id", $user_id)->pluck("client_id")->toArray();
        $account_currencies = Account::whereIn("client_id", $client_ids)
            ->whereIn('account_currency', $this->currencyIds)
            ->select("account_currency")
            ->distinct()
            ->pluck("account_currency")
            ->toArray();

        $currencies = Currency::whereIn('id', $account_currencies)->get();

        $clients = Client::whereIn("id", $client_ids)->where("status", "Approved")->get();


        if ($request->has('export_excel') && $request->input('export_excel') === 'excel') {
            return Excel::download(new UserClientWiseExport($currencies, $users, $clients), "Client Wise Report - Reports Management - " . $this->companyName->short_name . ".xlsx");
        }

        if ($request->has('export_pdf') && $request->input('export_pdf') === 'pdf') {
            $pdf = Pdf::loadView('reports.partials.pdf.user-client-wise-pdf', compact('currencies', 'users', 'clients'))
                ->setPaper('A4', 'portrait');
            return $pdf->download("Client Wise Report - Reports Management - " . $this->companyName->short_name . ".pdf");
        }

        return view("reports.user-clients-wise", compact('currencies', 'users', 'clients', 'user_id'));
    }

    public function accountTransactions($account_id = null)
    {
        $accounts = Account::where("is_active", true)->whereIn('account_currency', $this->currencyIds)->get();
        $this->transactionNatures[] = "Maintenance Fee";

        $transactions = AccountTransaction::orderBy("transaction_date", "desc")
            ->where('status', 'Approved')
            ->whereIn("currency_id", $this->currencyIds)
            ->whereIn("transaction_nature", $this->transactionNatures);

        if ($account_id > 0) {
            $transactions->where("account_id", $account_id);
        }

        $transactions = $transactions->paginate(10);

        $request = [
            'account_id' => '',
            'from_date' => '',
            'to_date' => '',
            'per_page' => 10,
            'search_string' => ''
        ];

        Session::forget('account_id');
        Session::forget('from_date');
        Session::forget('to_date');

        return view("reports.account-transactions", compact('accounts', 'transactions', 'request'));
    }

    public function accountTransactionsReport($filterApplied = null)
    {
        $accounts = Account::where("is_active", true)->whereIn('account_currency', $this->currencyIds)->get();

        if ($filterApplied == "true") {
            $accountID = Session::get('account_id');
            $fromDate = Session::get('from_date');
            $toDate = Session::get('to_date');
            $transactions = $this->accountTransactionsData($accountID, $fromDate, $toDate)->get();
        } else {
            $this->transactionNatures[] = "Maintenance Fee";

            $transactions = AccountTransaction::orderBy("transaction_date", "desc")
                ->where('status', 'Approved')
                ->whereIn("currency_id", $this->currencyIds)
                ->whereIn("transaction_nature", $this->transactionNatures)
                ->get();
        }

        if (request()->has('format') && request()->query('format') === 'pdf') {
            $groupedTransactions = $transactions->groupBy('transaction_nature');
            $pdf = Pdf::loadView('reports.partials.pdf.account-transactions-pdf', compact('accounts', 'groupedTransactions'))
                ->setPaper('A4', 'portrait');
            return $pdf->download("Account Transactions Report - Reports Management - " . $this->companyName->short_name . ".pdf");
        } else {
            return Excel::download(new AccountTransactionsReportExport($accounts, $transactions), "Account Transactions Report - Reports Management - " . $this->companyName->short_name . ".xlsx");
        }
    }

    public function accountTransactionsSearch(Request $request)
    {
        if ($request->has('from_date') && $request->has('to_date') && $request->filled('from_date') && $request->filled('to_date')) {
            if (!Common::validateDate($request->get('from_date'), $request->get('to_date'))) {
                return redirect('/reports-management/client/account-transactions')->with('error', 'Invalid date format');
            }
        }
        $accounts = Account::where("is_active", true)->whereIn('account_currency', $this->currencyIds)->get();

        $accountID = $request->get("account_id");
        $fromDate = $request->get("from_date");
        $toDate = $request->get("to_date");

        Session::put('account_id', $accountID);
        Session::put('from_date', $fromDate);
        Session::put('to_date', $toDate);

        $transactions = $this->accountTransactionsData($accountID, $fromDate, $toDate);
        $perPage = $request->get("per_page");

        if ($request->get('search_string')) {
            $transactions->whereHas('account', function ($query) use ($request) {
                $query->where('account_number', 'LIKE', '%' . $request->get('search_string') . '%');
            })->orwhere('remarks', 'LIKE', '%' . $request->get('search_string') . '%')
                ->orwhere('amount', 'LIKE', '%' . $request->get('search_string') . '%');
        }

        if ($perPage == -1 || ($request->get('search_string') && $transactions->count() < 10)) {
            $perPage = $transactions->count();
        } elseif (!$request->get("per_page")) {
            $perPage = 10;
        }

        $transactions = $transactions->paginate($perPage);
        $request = $request->all();

        return view("reports.account-transactions", compact('accounts', 'transactions', 'request'));
    }

    private function accountTransactionsData($accountID, $fromDate, $toDate)
    {
        $this->transactionNatures[] = "Maintenance Fee";

        $transactions = AccountTransaction::orderBy("transaction_date", "desc")
            ->where('status', 'Approved')
            ->whereIn("currency_id", $this->currencyIds)
            ->whereIn("transaction_nature", $this->transactionNatures);

        if ($accountID > 0) {
            $transactions->where("account_id", $accountID);
        }

        if ($fromDate != "") {
            $from = Carbon::createFromFormat("d/m/Y", $fromDate)->format("Y-m-d 00:00:00");
            $transactions->where("transaction_date", ">=", $from);
        }

        if ($toDate != "") {
            $to = Carbon::createFromFormat("d/m/Y", $toDate)->format("Y-m-d 23:59:59");
            $transactions->where("transaction_date", "<=", $to);
        }

        return $transactions;
    }

    public function clientAccountStatement()
    {
        $types = ["individual", "company"];
        $accounts = Account::where("is_active", true)->whereIn('type', $types)->whereIn('account_currency', $this->currencyIds)->get();
        //dd($this->currencyIds);
        $periods = Common::getAccountStatementPeriods();
        $start_date = Carbon::parse(Auth::user()->created_at)->format("d/m/Y");

        if ($accounts->count() > 0) {
            $this->transactionNatures[] = "Maintenance Fee";

            $transactions = AccountTransaction::where("account_id", $accounts->first()->id)
                ->whereIn("transaction_nature", $this->transactionNatures)
                ->orderBy("transaction_date", "asc")
                ->where("status", "Approved")
                ->take(-10)
                ->get();

            if ($transactions->first()) {
                $start_date = Carbon::parse($transactions->first()->transaction_date)->format("d/m/Y");
                $balance = Common::getAccountBalanceTillDate($accounts->first()->id, $transactions->first()->transaction_date);
            } else {
                $balance = 0;
            }
        } else {
            $balance = 0;
            $transactions = [];
        }

        return view("reports.statement-search", compact('balance', 'transactions', 'periods', 'accounts', 'start_date'));
    }

    public function closedClientsAccountStatement()
    {
        $types = ["individual", "company"];
        $clientIds = Client::where("status", "Closed")->select('id')->get();
        $accounts = Account::whereIn("client_id", $clientIds)->whereIn('type', $types)->whereIn('account_currency', $this->currencyIds)->get();
        $periods = Common::getAccountStatementPeriods();
        $start_date = Carbon::parse(Auth::user()->created_at)->format("d/m/Y");


        if ($accounts->count() > 0) {
            $this->transactionNatures[] = "Maintenance Fee";

            $transactions = AccountTransaction::where("account_id", $accounts->first()->id)
                ->whereIn("transaction_nature", $this->transactionNatures)
                ->orderBy("transaction_date", "asc")
                ->where("status", "Approved")
                ->take(-10)
                ->get();

            if ($transactions->first()) {
                $start_date = Carbon::parse($transactions->first()->transaction_date)->format("d/m/Y");
                $balance = Common::getAccountBalanceTillDate($accounts->first()->id, $transactions->first()->transaction_date);
            } else {
                $balance = 0;
            }
        } else {
            $balance = 0;
            $transactions = [];
        }

        return view("reports.closed-client-statement-search", compact('balance', 'transactions', 'periods', 'accounts', 'start_date'));
    }

    public function accountStatementGenerate(Request $request)
    {
        if ($request->has('from_date') && $request->has('to_date') && $request->filled('from_date') && $request->filled('to_date')) {
            if (!Common::validateDate($request->get('from_date'), $request->get('to_date'))) {
                return redirect('/reports-management/client/client-account-statement')->with('error', 'Invalid date format');
            }
        }
        // dd(Carbon::now()->format("Y-m-d 23:59:59"));
        $types = ["individual", "company"];
        $accounts = Account::where("is_active", true)->whereIn('type', $types)->whereIn("account_currency", $this->currencyIds)->get();
        $start_date = Carbon::parse(Auth::user()->created_at)->format("d/m/Y");
        $periods = Common::getAccountStatementPeriods();

        $this->transactionNatures[] = "Maintenance Fee";

        $transactions = AccountTransaction::whereIn("currency_id", $this->currencyIds)
            ->whereIn("transaction_nature", $this->transactionNatures)
            ->orderBy("transaction_date", "asc")
            ->where("status", "Approved");

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

        $transactions = $transactions->get();

        if ($transactions->count() > 0) {
            $first_trn = $transactions->first();
            $balance = Common::getAccountBalanceTillDate($request->get("account_id"), $first_trn->transaction_date);
        } else {
            $balance["amount"] = Common::getAccountBalanceTillDate($request->get("account_id"), $to);
            $balance["is_crypto"] = Account::find($request->get("account_id"))->currency->is_crypto;
        }

        $request = $request->all();

        return view("reports.account-statement", compact('balance', 'transactions', 'accounts', 'request', 'periods', 'start_date', 'to'));
    }

    public function closedClientsAccountStatementGenerate(Request $request)
    {
        if ($request->has('from_date') && $request->has('to_date') && $request->filled('from_date') && $request->filled('to_date')) {
            if (!Common::validateDate($request->get('from_date'), $request->get('to_date'))) {
                return redirect('/reports-management/client/closed-client-account-statement')->with('error', 'Invalid date format');
            }
        }

        $types = ["individual", "company"];
        $clientIds = Client::where("status", "Closed")->select('id')->get();
        $accounts = Account::whereIn("client_id", $clientIds)->whereIn('type', $types)->whereIn('account_currency', $this->currencyIds)->get();
        $start_date = Carbon::parse(Auth::user()->created_at)->format("d/m/Y");
        $periods = Common::getAccountStatementPeriods();

        $this->transactionNatures[] = "Maintenance Fee";

        $transactions = AccountTransaction::whereIn("currency_id", $this->currencyIds)
            ->whereIn("transaction_nature", $this->transactionNatures)
            ->orderBy("transaction_date", "asc")
            ->where("status", "Approved");

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

        $transactions = $transactions->get();

        if ($transactions->count() > 0) {
            $first_trn = $transactions->first();
            $balance = Common::getAccountBalanceTillDate($request->get("account_id"), $first_trn->transaction_date);
        } else {
            $balance["amount"] = Common::getAccountBalanceTillDate($request->get("account_id"), $to);
            $balance["is_crypto"] = Account::find($request->get("account_id"))->currency->is_crypto;
        }

        $request = $request->all();

        return view("reports.closed-client-account-statement", compact('balance', 'transactions', 'accounts', 'request', 'periods', 'start_date', 'to'));
    }

    public function accountStatementPDF(Request $request)
    {
        $transactions = AccountTransaction::orderBy("transaction_date", "asc")->where("status", "Approved");
        $start_date = Carbon::now()->subDays(30)->format("Y-m-d 00:00:00");

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

        return $pdf->stream($file_name)->header('Content-Type', 'application/pdf');
    }

    public function bankAccountStatementPDF(Request $request)
    {
        $transactions = AccountTransaction::orderBy("transaction_date", "asc")->where("status", "Approved");

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

        return $pdf->stream($file_name)->header('Content-Type', 'application/pdf');
    }

    public function bankAccountStatement()
    {
        // $accounts = Account::where("is_active", true)->where("type", "bank")->whereIn('account_currency', $this->currencyIds)->get();
        $isMaintenanceFeeReport = false;
        $banks = Bank::where('status', 'Approved');

        if (strpos(\Illuminate\Support\Facades\Request::path(), 'reports-management/maintenance-fee/revenue') !== false) {
            $isMaintenanceFeeReport = true;
            $banks->where('name', 'Maintenance Fee');
        }
        // dd(\Illuminate\Support\Facades\Request::path(), $isMaintenanceFeeReport);

        $banks = $banks->get();
        $periods = Common::getAccountStatementPeriods();
        $balance = 0;
        $transactions = [];
        $start_date = Carbon::parse(Auth::user()->created_at)->format("d/m/Y");

        // if ($banks->count() > 0) {
        //     $transactions = AccountTransaction::where("account_id", $banks->first()->accounts->first()->id)
        //         ->whereIn("transaction_nature", $this->transactionNatures)
        //         ->orderBy("transaction_date", "asc")
        //         ->where("status", "Approved")
        //         ->take(-10)
        //         ->get();

        //     if ($transactions->first()) {
        //         $start_date = Carbon::parse($transactions->first()->transaction_date)->format("d/m/Y");
        //         $balance = Common::getAccountBalanceTillDate($banks->first()->accounts->first()->id, $transactions->first()->transaction_date);
        //     } else {
        //         $balance = 0;
        //     }
        // }

        return view("reports.bank.statement-search", compact(
            'balance',
            'transactions',
            'periods',
            'banks',
            'start_date',
            'isMaintenanceFeeReport'
        ));
    }

    public function bankAccountStatementGenerate(Request $request)
    {
        // $accounts = Account::where("is_active", true)->where("type", "bank")->whereIn("account_currency", $this->currencyIds)->get();
        $isMaintenanceFeeReport = false;
        $banks = Bank::where('status', 'Approved');

        if (strpos(\Illuminate\Support\Facades\Request::path(), 'reports-management/maintenance-fee/revenue') !== false) {
            $isMaintenanceFeeReport = true;
            $banks->where('name', 'Maintenance Fee');
        }

        $banks = $banks->get();
        $start_date = Carbon::parse(Auth::user()->created_at)->format("d/m/Y");
        $periods = Common::getAccountStatementPeriods();

        $transactions = AccountTransaction::whereIn("currency_id", $this->currencyIds)
            ->whereIn("transaction_nature", $this->transactionNatures)
            ->orderBy("transaction_date", "asc")
            ->where("status", "Approved");

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

        if ($request->get("bank_id") > 0) {
            $bank = Bank::find($request->get("bank_id"));
            $transactions->whereIn("account_id", $bank->accounts->pluck('id'));
        }

        $transactions = $transactions->get();

        $requestUri = $request->getRequestUri();
        $request = $request->all();

        return view("reports.bank.account-statement", compact('transactions', 'banks', 'request', 'periods', 'start_date', 'to', 'isMaintenanceFeeReport'));
    }

    public function clientBankAccountStatement()
    {
        $clientIds = Client::where('is_bank', 1)->where('status', 'Approved')->select('id')->get();
        $banks = Client::where('is_bank', 1)->where('status', 'Approved')->get();

        $accounts = Account::where("is_active", true)
            ->whereIn('client_id', $clientIds)
            // ->where("type", "bank")
            ->whereIn('account_currency', $this->currencyIds)
            ->get();

        $periods = Common::getAccountStatementPeriods();
        $start_date = Carbon::parse(Auth::user()->created_at)->format("d/m/Y");

        if ($accounts->count() > 0) {
            $transactions = AccountTransaction::where("account_id", $accounts->first()->id)
                ->whereIn("transaction_nature", $this->transactionNatures)
                ->orderBy("transaction_date", "asc")
                ->where("status", "Approved")
                ->take(-10)
                ->get();

            if ($transactions->first()) {
                $start_date = Carbon::parse($transactions->first()->transaction_date)->format("d/m/Y");
                $balance = Common::getAccountBalanceTillDate($accounts->first()->id, $transactions->first()->transaction_date);
            } else {
                $balance = 0;
            }
        } else {
            $balance = 0;
            $transactions = [];
        }

        return view("reports.bank.client-statement-search", compact('balance', 'transactions', 'periods', 'accounts', 'start_date'));
    }

    public function clientBankAccountStatementGenerate(Request $request)
    {
        if ($request->has('from_date') && $request->has('to_date') && $request->filled('from_date') && $request->filled('to_date')) {
            if (!Common::validateDate($request->get('from_date'), $request->get('to_date'))) {
                return redirect('/reports-management/client-bank-reports/bank-account-statement')->with('error', 'Invalid date format');
            }
        }

        $clientIds = Client::where('is_bank', 1)->where('status', 'Approved')->select('id')->get();
        $banks = Client::where('is_bank', 1)->where('status', 'Approved')->get();

        $accounts = Account::where("is_active", true)
            ->whereIn('client_id', $clientIds)
            // ->where("type", "bank")
            ->whereIn('account_currency', $this->currencyIds)
            ->get();

        $start_date = Carbon::parse(Auth::user()->created_at)->format("d/m/Y");
        $periods = Common::getAccountStatementPeriods();

        $transactions = AccountTransaction::whereIn("currency_id", $this->currencyIds)
            ->whereIn("transaction_nature", $this->transactionNatures)
            ->orderBy("transaction_date", "asc")
            ->where("status", "Approved");

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

        $transactions = $transactions->get();

        if ($transactions->count() > 0) {
            $first_trn = $transactions->first();
            $balance = Common::getAccountBalanceTillDate($request->get("account_id"), $first_trn->transaction_date);
        } else {
            $balance["amount"] = Common::getAccountBalanceTillDate($request->get("account_id"), $to);
            $balance["is_crypto"] = Account::find($request->get("account_id"))->currency->is_crypto;
        }

        $requestUri = $request->getRequestUri();
        $request = $request->all();

        return view("reports.bank.client-account-statement", compact('balance', 'transactions', 'accounts', 'request', 'periods', 'start_date', 'to'));
    }

    public function closedClientBanksAccountStatement()
    {
        $clientIds = Client::where("status", "Closed")->where('is_bank', true)->select('id')->get();
        $accounts = Account::whereIn("client_id", $clientIds)->where('type', 'bank')->whereIn('account_currency', $this->currencyIds)->get();
        $periods = Common::getAccountStatementPeriods();
        $start_date = Carbon::parse(Auth::user()->created_at)->format("d/m/Y");


        if ($accounts->count() > 0) {
            $this->transactionNatures[] = "Maintenance Fee";

            $transactions = AccountTransaction::where("account_id", $accounts->first()->id)
                ->whereIn("transaction_nature", $this->transactionNatures)
                ->orderBy("transaction_date", "asc")
                ->where("status", "Approved")
                ->take(-10)
                ->get();

            if ($transactions->first()) {
                $start_date = Carbon::parse($transactions->first()->transaction_date)->format("d/m/Y");
                $balance = Common::getAccountBalanceTillDate($accounts->first()->id, $transactions->first()->transaction_date);
            } else {
                $balance = 0;
            }
        } else {
            $balance = 0;
            $transactions = [];
        }

        return view("reports.bank.closed-client-bank-statement-search", compact('balance', 'transactions', 'periods', 'accounts', 'start_date'));
    }

    public function closedClientBanksAccountStatementGenerate(Request $request)
    {
        if ($request->has('from_date') && $request->has('to_date') && $request->filled('from_date') && $request->filled('to_date')) {
            if (!Common::validateDate($request->get('from_date'), $request->get('to_date'))) {
                return redirect('/reports-management/client-bank-reports/closed-bank-account-statement')->with('error', 'Invalid date format');
            }
        }

        $clientIds = Client::where("status", "Closed")->where('is_bank', true)->select('id')->get();
        $accounts = Account::whereIn("client_id", $clientIds)->where('type', 'bank')->whereIn('account_currency', $this->currencyIds)->get();
        $start_date = Carbon::parse(Auth::user()->created_at)->format("d/m/Y");
        $periods = Common::getAccountStatementPeriods();

        $this->transactionNatures[] = "Maintenance Fee";

        $transactions = AccountTransaction::whereIn("currency_id", $this->currencyIds)
            ->whereIn("transaction_nature", $this->transactionNatures)
            ->orderBy("transaction_date", "asc")
            ->where("status", "Approved");

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

        $transactions = $transactions->get();

        if ($transactions->count() > 0) {
            $first_trn = $transactions->first();
            $balance = Common::getAccountBalanceTillDate($request->get("account_id"), $first_trn->transaction_date);
        } else {
            $balance["amount"] = Common::getAccountBalanceTillDate($request->get("account_id"), $to);
            $balance["is_crypto"] = Account::find($request->get("account_id"))->currency->is_crypto;
        }

        $request = $request->all();

        return view("reports.bank.closed-client-bank-account-statement", compact('balance', 'transactions', 'accounts', 'request', 'periods', 'start_date', 'to'));
    }

    public function allBanksWise()
    {
        $clientIds = Client::where('is_bank', 1)->where('status', 'Approved')->select('id')->get();
        $banks = Client::where('is_bank', 1)->where('status', 'Approved')->get();

        $acc_currencies = Account::whereIn('client_id', $clientIds)
            // ->where('type', 'bank')
            ->whereIn('account_currency', $this->currencyIds)
            ->select('account_currency')
            ->distinct()
            ->get();

        $tr_currencies = AccountTransaction::whereIn('currency_id', $acc_currencies)
            ->select("currency_id")
            ->distinct()
            ->pluck('currency_id')
            ->toArray();

        $currencies = Currency::whereIn('id', $tr_currencies)->get();
        // $banks = Account::where("is_active", true)->where('type', 'bank')->select('id', 'title')->get();

        Session::forget('from_date');
        Session::forget('to_date');

        return view("reports.bank.all-banks-wise-search", compact('currencies', 'banks'));
    }

    public function dailyAllClientsWise(Request $request)
    {
        $startTime = Carbon::now();
        $clients = Client::where("status", "Approved")->select("id", "name")->get();

        if ($request->get("from_date") == "" && $request->get("to_date") == "") {
            $from_date = Carbon::now()->subDays(30)->format("Y-m-d 00:00:00");
            $to_date = Carbon::now()->format("Y-m-d 23:59:59");
        } else {
            $from_date = Carbon::createFromFormat("d/m/Y", $request->get("from_date"))->format("Y-m-d 00:00:00");
            $to_date = Carbon::createFromFormat("d/m/Y", $request->get("to_date"))->format("Y-m-d 23:59:59");
        }

        // create dates between two dates
        $period = CarbonPeriod::create($from_date, $to_date);

        // get all active clients accounts
        if ($request->get("client_id") > 0) {
            $allClients = Client::whereIn("id", $request->get("client_id"))->get();
        } else {
            $allClients = Client::where("status", "Approved")->select("id", "name")->get();
        }

        // get all currencies for client accounts
        $currencies = Account::whereIn('type', ['company', 'individual'])
            ->select('account_currency')
            ->distinct()
            ->get();

        $data = [];
        foreach ($period as $date) {

            $db_date = $date->format('Y-m-d');

            foreach ($allClients as $client) {
                $data[$db_date][$client->name] = self::getClientBalaceByDateAndCurrency($client->id, $currencies, $date);
            }
        } // end of period loop

        $request['from_date'] = Carbon::createFromFormat("Y-m-d", $from_date)->format("d/m/Y");
        $request['to_date'] = Carbon::createFromFormat("Y-m-d", $to_date)->format("d/m/Y");
        $endTime = Carbon::now();

        $totalDuration = $endTime->diffInSeconds($startTime);
        $totalDuration = gmdate('H:i:s', $totalDuration);

        return view("reports.daily-all-clients-wise-search", compact('currencies', 'data', 'request', 'totalDuration', 'clients'));
    }

    public function dailyAllClientsWiseV2(Request $request)
    {
        if ($request->has('from_date') && $request->has('to_date')) {
            if (!Common::validateDate($request->get('from_date'), $request->get('to_date'))) {
                return redirect('/reports-management/client/daily-all-clients')->with('error', 'Invalid date format');
            }

            $from_date = Carbon::createFromFormat("d/m/Y", $request->get("from_date"))->format("Y-m-d 00:00:00");
            $to_date = Carbon::createFromFormat("d/m/Y", $request->get("to_date"))->format("Y-m-d 23:59:59");
        } else {
            $from_date = Carbon::now()->subDays(30)->format("Y-m-d 00:00:00");
            $to_date = Carbon::now()->format("Y-m-d 23:59:59");
        }

        $startTime = Carbon::now();
        $clients = Client::where("status", "Approved")->select("id", "name")->get();

        // get all active clients accounts
        if ($request->get("client_id") > 0) {
            $allClients = Client::whereIn("id", $request->get("client_id"))
                ->select("id")
                ->pluck("id")
                ->toArray();
        } else {
            $allClients = Client::where("status", "Approved")
                ->select("id")
                ->pluck("id")
                ->toArray();
        }

        // get all currencies for client accounts
        $currencies = Account::whereIn('type', ['company', 'individual'])
            ->select('account_currency')
            ->distinct()
            ->get();

        // get currencies with codes
        $curs = [];
        foreach ($currencies as $cr) {
            $curs[$cr->account_currency] = Common::getCurrencyShort($cr->account_currency);
        }

        $data = [];

        $data[] = self::getClientBalaceByDateAndCurrencyV2($allClients, $curs, $from_date, $to_date);

        $request['from_date'] = Carbon::createFromFormat("Y-m-d H:i:s", Carbon::parse($from_date))->format("d/m/Y");
        $request['to_date'] = Carbon::createFromFormat("Y-m-d H:i:s", Carbon::parse($to_date))->format("d/m/Y");
        $endTime = Carbon::now();

        $totalDuration = $endTime->diffInSeconds($startTime);
        $totalDuration = gmdate('H:i:s', $totalDuration);


        if ($request->has('export_excel') && $request->input('export_excel') === 'excel') {
            return Excel::download(new DailyAllClientsWiseExport($currencies, $data), "Daily Client Wise Report - Reports Management - " . $this->companyName->short_name . ".xlsx");
        } else if ($request->has('export_pdf') && $request->input('export_pdf') === 'pdf') {
            $pdf = Pdf::loadView('reports.partials.pdf.daily-all-clients-wise-pdf', compact('currencies', 'data'))
                ->setPaper('A4', 'portrait');
            return $pdf->download("Daily Client Wise Report - Reports Management - " . $this->companyName->short_name . ".pdf");
        }

        return view("reports.daily-all-clients-wise-search-v2", compact('currencies', 'data', 'request', 'totalDuration', 'clients'));
    }

    private function getClientBalaceByDateAndCurrency($client_id, $currencies, $date)
    {
        $balances = [];
        foreach ($currencies as $currency) {

            // check account for currency
            $account = Account::where("client_id", $client_id)
                ->where("account_currency", $currency->account_currency)
                ->select("id")
                ->first();

            if ($account) {

                $results = DB::table('account_transactions')
                    ->select('transaction_type', DB::raw('sum(amount) as amount'))
                    ->where('account_id', $account->id)
                    ->where('transaction_date', $date)
                    ->where('status', 'Approved')
                    ->groupBy('transaction_type')
                    ->get();

                $credit = 0;
                $debit = 0;
                if ($results) {
                    foreach ($results as $result) {
                        $credit = ($result->transaction_type == "Credit") ? $result->amount : 0;
                        $debit = ($result->transaction_type == "Debit") ? $result->amount : 0;
                    }
                }

                // get account balance till date
                $prev_balance = Common::getAccountBalanceTillDate($account->id, $date);

                $balances[] = [
                    'balance' => ($credit - $debit) + $prev_balance,
                    'account' => $account->title,
                ];
            } else {
                $balances[] = [
                    'balance' => 0
                ];
            }
        } // end of currency loop

        return $balances;
    }

    private function getClientBalaceByDateAndCurrencyV2($clients, $currencies, $from_date, $to_date)
    {
        $balances = [];
        $accounts = Account::whereIn("client_id", $clients)
            // ->where("is_crypto", 0)
            ->where("is_crypto", false)
            ->where("is_active", true)
            ->pluck('id')
            ->toArray();

        $accountIds = count($accounts) > 0 ? $accounts : [0];

        $results = DB::table('account_transactions')
            ->select(
                'account_id',
                DB::raw('date(transaction_date) as transaction_date'),
                'currency_id',
                'transaction_type',
                DB::raw('SUM(amount) as amount')
            )
            ->whereIn('account_id', $accountIds)
            ->whereRaw('DATE(transaction_date) BETWEEN ? AND ?', [$from_date, $to_date])
            ->groupBy('account_id', DB::raw('date(transaction_date)'), 'currency_id', 'transaction_type')
            ->orderBy('transaction_date', 'desc')
            ->get();

        $prev_balance = [];
        $response = [];
        $titles = [];
        foreach ($results as $account) {

            // get balance for the first date of the currency
            $prev_balance[$account->account_id] = Common::getAccountBalanceTillDate($account->account_id, Carbon::createFromFormat("Y-m-d", $account->transaction_date)->format("Y-m-d 23:59:59"));

            if (!isset($title[$account->account_id])) {
                $tmp = Account::where("id", $account->account_id)->select("title")->first();
                $title[$account->account_id] = $tmp->title;
            }

            $response[$account->transaction_date . $account->account_id] = [
                'title' => $title[$account->account_id],
                'currency' => $currencies[$account->currency_id],
                'date' => $account->transaction_date,
                $account->currency_id => isset($response[$account->transaction_date . $account->account_id][$account->currency_id]) ? $response[$account->transaction_date . $account->account_id][$account->currency_id] + $prev_balance[$account->account_id] : $prev_balance[$account->account_id]
            ];
        }
        return $response;
    }

    public function dailyAllBanksWise(Request $request)
    {
        if ($request->has('from_date') && $request->has('to_date')) {
            if (!Common::validateDate($request->get('from_date'), $request->get('to_date'))) {
                return redirect('/reports-management/client-bank-reports/daily-all-banks-report')->with('error', 'Invalid date format');
            }

            $from_date = Carbon::createFromFormat("d/m/Y", $request->get("from_date"))->format("Y-m-d 00:00:00");
            $to_date = Carbon::createFromFormat("d/m/Y", $request->get("to_date"))->format("Y-m-d 23:59:59");
        } else {
            $from_date = Carbon::now()->subDays(30)->format("Y-m-d 00:00:00");
            $to_date = Carbon::now()->format("Y-m-d 23:59:59");
        }

        try {
            // get all active banks accounts
            $banks = Client::where('is_bank', 1)
                ->where('status', 'Approved')
                ->select("id")
                ->pluck("id")
                ->toArray();

            // get all currencies for client accounts
            $currencies = Account::whereIn('type', ['company', 'individual'])
                ->where("is_crypto", 0)
                ->select('account_currency')
                ->distinct()
                ->get();

            if (0 === count($banks)) {
                throw new Exception('No records found.');
            }

            // get currencies with codes
            $curs = [];
            foreach ($currencies as $cr) {
                $curs[$cr->account_currency] = Common::getCurrencyShort($cr->account_currency);
            }

            $data = [];
            $data[] = self::getClientBalaceByDateAndCurrencyV2($banks, $curs, $from_date, $to_date);

            $request['from_date'] = Carbon::createFromFormat("Y-m-d H:i:s", Carbon::parse($from_date))->format("d/m/Y");
            $request['to_date'] = Carbon::createFromFormat("Y-m-d H:i:s", Carbon::parse($to_date))->format("d/m/Y");

            if (request()->has('format')) {

                if (request()->query('format') === 'pdf') {
                    $pdf = PDF::loadView('reports.partials.pdf.daily-all-banks-pdf', compact('currencies', 'data'))
                        ->setPaper('A4', 'landscape');
                    return $pdf->download("All Banks Report - Reports Management - " . $this->companyName->short_name . ".pdf");
                } else {
                    return Excel::download(new DailyAllBanksExport($currencies, $data), "Daily All Banks Report - Reports Management - " . $this->companyName->short_name . ".xlsx");
                }
            }

            return view("reports.bank.daily-all-banks-wise-search", compact('currencies', 'data', 'request'));
        } catch (Exception $e) {
            $data = [];
            return view("reports.bank.daily-all-banks-wise-search", compact('currencies', 'data', 'request'));
        }
    }

    public function allBankWiseReport($filterApplied = null)
    {
        if ($filterApplied == "true") {
            $fromDate = Session::get('from_date');
            $toDate = Session::get('to_date');
            $currencies = $this->getFilteredAllBanks($fromDate, $toDate);
        } else {

            $clientIds = Client::where('is_bank', 1)->where('status', 'Approved')->select('id')->get();

            $acc_currencies = Account::whereIn('client_id', $clientIds)
                // ->where('type', 'bank')
                ->whereIn('account_currency', $this->currencyIds)
                ->select('account_currency')
                ->distinct()
                ->get();

            $tr_currencies = AccountTransaction::whereIn('currency_id', $acc_currencies)
                ->select("currency_id")
                ->distinct()
                ->pluck('currency_id')
                ->toArray();

            $currencies = Currency::whereIn('id', $tr_currencies)->get();
        }
        // $banks = Account::where("is_active", true)->where('type', 'bank')->select('id', 'title')->get();
        $banks = Client::where('is_bank', 1)->where('status', 'Approved')->get();


        if (request()->has('format') && request()->query('format') === 'pdf') {
            $pdf = PDF::loadView('reports.partials.pdf.all-banks-wise-pdf', compact('currencies', 'banks'))
                ->setPaper('A4', 'landscape');
            return $pdf->download("All Banks Report - Reports Management - " . $this->companyName->short_name . ".pdf");
        } else {
            return Excel::download(new AllBankWiseReportExport($currencies, $banks), "All Banks Report - Reports Management - " . $this->companyName->short_name . ".xlsx");
        }
    }

    public function allBanksWiseGenerate(Request $request)
    {
        $fromDate = $request->get("from_date");
        $toDate = $request->get("to_date");

        if (!Common::validateDate($request->get('from_date'), $request->get('to_date'))) {
            return redirect('/reports-management/client-bank-reports/all-banks-report')->with('error', 'Invalid date format');
        }

        Session::put('from_date', $fromDate);
        Session::put('to_date', $toDate);

        $currencies = $this->getFilteredAllBanks($fromDate, $toDate);
        // $banks = Account::where("is_active", true)->where('type', 'bank')->select('id', 'title')->get();
        $banks = Client::where('is_bank', 1)->where('status', 'Approved')->get();

        return view("reports.bank.all-banks-wise", compact('currencies', 'banks', 'request'));
    }

    private function getFilteredAllBanks($fromDate, $toDate)
    {
        $clientIds = Client::where('is_bank', 1)->where('status', 'Approved')->select('id')->get();

        $acc_currencies = Account::whereIn('client_id', $clientIds)
            // ->where('type', 'bank')
            ->whereIn('account_currency', $this->currencyIds)
            ->select('account_currency')
            ->distinct()
            ->get();

        $tr_currencies = AccountTransaction::whereIn('currency_id', $acc_currencies);

        if ($fromDate != "") {
            $from = Carbon::createFromFormat("d/m/Y", $fromDate)->format("Y-m-d 00:00:00");
            $tr_currencies->where("transaction_date", ">=", $from);
        }

        if ($toDate != "") {
            $to = Carbon::createFromFormat("d/m/Y", $toDate)->format("Y-m-d 23:59:59");
            $tr_currencies->where("transaction_date", "<=", $to);
        }

        $tr_currencies = $tr_currencies->select("currency_id")
            ->distinct()
            ->pluck('currency_id')
            ->toArray();

        $currencies = Currency::whereIn('id', $tr_currencies)->get();

        return $currencies;
    }

    public function listVostroBanksReports(Request $request)
    {
        $reports = VostroBanksReport::select(
            'vostro_banks_reports.id',
            'vostro_banks_reports.file_url',
            'vostro_banks_reports.report_date',
            'vostro_banks_reports.should_send_email',
            'vostro_banks_reports.created_at',
            'creator.name as creator'
        )
            ->leftJoin("users as creator", "vostro_banks_reports.created_by", "=", "creator.id")
            ->orderBy('vostro_banks_reports.report_date', 'desc');

        $perPage = $request->get("per_page");
        $searchString = $request->get('search_string');

        if ($searchString) {
            $reports->where('file_url', 'LIKE', '%' . $searchString . '%')
                ->orwhere('creator.name', 'LIKE', '%' . $searchString . '%')
                ->orwhere('report_date', 'LIKE', '%' . $searchString . '%');
        }

        if ($perPage == -1 || ($searchString && $reports->count() < 10)) {
            $perPage = $reports->count();
        } elseif (!$request->get("per_page")) {
            $perPage = 10;
        }

        $reports = $reports->paginate($perPage);

        return view("reports.vostro-banks-reports", compact("reports", "request"));
    }

    public function storeVostroBanksReport(Request $request)
    {
        $this->validate($request, [
            'report_date' => 'required',
            'file' => ['required', new SupportingDocumentsRule, 'mimes:xlsx,xls'],
            'should_send_email' => 'required'
        ]);

        try {
            $report = new VostroBanksReport();
            $url = "";

            $report->report_date = Carbon::createFromFormat('d/m/Y', $request->get('report_date'))->format('Y-m-d H:i:s');
            $report->should_send_email = ($request->get("should_send_email")) ? 1 : 0;
            $report->created_by = Auth::user()->id;

            if ($request->hasFile('file')) {

                // validate file
                if (!FileFirewall::isAllowedType($request->file('file'))) {
                    return FileFirewall::logBadFileException($request->file('file'));
                }

                $url = S3Helper::fileUpload($request->file('file'), "vostro-banks-reports");
            }

            $report->file_url = $url;

            $report->save();

            if ($report->should_send_email) {
                EmailNotification::sendVostroBanksReportToChairPerson($report);
            }

            return redirect("/reports-management/vostro-banks-reports/daily-banks-balances")->with("success", "Report uploaded successfully!");
        } catch (Exception $e) {
            Log::error("Exception thrown during storing Vostro Banks Report. Exception Message: " . $e->getMessage() . " | Line: " . $e->getLine());

            return redirect("/reports-management/vostro-banks-reports/daily-banks-balances")->with("error", "Internal Server Error, please try again later.");
        }
    }

    public function deleteVostroBanksReport(Request $request)
    {
        try {
            $report = VostroBanksReport::find($request->get("report_id"));

            if ($report->file_url) {
                S3Helper::fileDelete($report->file_url);
            }

            $report->delete();
            return redirect("/reports-management/vostro-banks-reports/daily-banks-balances")->with("success", "Report deleted successfully!");
        } catch (Exception $e) {
            Log::error("Exception thrown during deleting Vostro Banks Report. Exception Message: " . $e->getMessage() . " | Line: " . $e->getLine());

            return redirect("/reports-management/vostro-banks-reports/daily-banks-balances")->with("error", "Internal Server Error, please try again later.");
        }
    }

    public function getMaintenanceFeeReport(Request $request)
    {
        if ($request->get('report_date')) {
            $date = Carbon::createFromFormat("d/m/Y", $request->get('report_date'))->format("Y-m");
        } else {
            $date = Carbon::now()->format('Y-m');
        }

        $reports = MaintenanceFeeReport::whereRaw("DATE_FORMAT(maintenance_fee_reports.created_at, '%Y-%m') = ?", [$date])->get();

        // dd($reports[6]);

        return view('reports.maintenance-fee-report', compact('reports', 'request'));
    }

    public function getPendingMaintenanceFeeReport(Request $request)
    {
        // if ($request->get('report_date')) {
        //     $date = Carbon::createFromFormat("d/m/Y", $request->get('report_date'))->format("Y-m");
        // } else {
        $date = Carbon::now()->format('Y-m');
        // }

        // $reports = MaintenanceFeeReport::whereRaw("DATE_FORMAT(maintenance_fee_reports.created_at, '%Y-%m') = ?", [$date])
        $unpaidReports = MaintenanceFeeReport::whereRaw("DATE_FORMAT(maintenance_fee_reports.created_at, '%Y-%m') = ?", [Carbon::parse($date)->format('Y-m')])
            ->where('have_paid', 0)
            ->orderBy('previous_pending_months', 'asc')
            ->get();

        $paidReports = MaintenanceFeeReport::whereRaw("DATE_FORMAT(maintenance_fee_reports.created_at, '%Y-%m') = ?", [Carbon::parse($date)->format('Y-m')])
            ->where('have_paid', 1)
            ->orderBy('previous_pending_months', 'asc')
            ->get();

        $paidCount = $paidReports->count();
        $unpaidCount = $unpaidReports->count();

        foreach ($unpaidReports as $report) {
            $report->amount = Common::getAccountBalanceById($report->account_id);
        }

        // dd($reports[6]);

        return view('reports.pending-maintenance-fee-report', compact('unpaidReports', 'paidCount', 'unpaidCount', 'request'));
    }

    public function chargePendingAccountsForMaintenanceFee(Request $request)
    {
        if ($request->get('report_date')) {
            $date = Carbon::createFromFormat("d/m/Y", $request->get('report_date'))->format("Y-m");
        } else {
            $date = Carbon::now()->format('Y-m');
        }

        $clients = MaintenanceFeeReport::select(
            'maintenance_fee_reports.unique_id',
            'maintenance_fee_reports.client_id',
            'maintenance_fee_reports.have_paid',
            'maintenance_fee_reports.created_at',
            'client.type',
        )
            ->whereRaw("DATE_FORMAT(maintenance_fee_reports.created_at, '%Y-%m') = ?", [$date])->where('maintenance_fee_reports.have_paid', false)
            ->leftJoin('clients as client', 'client.id', '=', 'maintenance_fee_reports.client_id')
            ->get();

        // dd($clients[0]);

        $data = FeeHelper::prepareMaintenanceFeeDeduction($clients);

        $filteredClients = array_filter($data['clients'], function ($client) {
            return $client['have_paid'] == true;
        });

        $credit_transaction_date = Carbon::now()->format("Y-m-d H:i:s");

        FeeHelper::updateMaintenanceFeeReport($filteredClients, $credit_transaction_date);

        $clientsCount = count($filteredClients);

        return redirect()->back()->with($clientsCount > 0 ? 'success' : 'error', $clientsCount . ' Client(s) have been charged for the Maintenance fee.');
    }

    public function getOnboardingFeeReport(Request $request)
    {
        if ($request->get('report_date')) {
            $date = Carbon::createFromFormat("d/m/Y", $request->get('report_date'))->format("Y-m");
        } else {
            $date = Carbon::now()->format('Y-m');
        }

        $reports = OnboardingFeeRecords::whereRaw("DATE_FORMAT(onboarding_fee_records.created_at, '%Y-%m') = ?", [$date])->get();

        return view('reports.onboarding-fee-report', compact('reports', 'request'));
    }

    public function showOnboardingFeeReport(Request $request)
    {
        try {
            $onboardingFee = new OnboardingFeeHelper();

            $report = $onboardingFee->searchTransactionByClientID($request["id"]);

            return view('reports.onboarding-fee-report-detailed', compact('report'));
        } catch (Exception $e) {
            return redirect('/reports.onboarding-fee-report')->with('error', 'Report Not Found');
        }
    }
    public function clientRisk(Request $request)
    {
        // if ($request->ajax()) {
        $clients = Client::select(
            "clients.id",
            "clients.name",
            "clients.type",
            "clients.created_at",
            "clients.approved_at",
            "clients.status",
            "clients.parent_id",
            "clients.is_bank",
            "creator.name as creator",
            "approver.name as approver"
        )
            ->where("clients.status", "Approved")
            ->leftJoin("users as creator", "clients.created_by", "=", "creator.id")
            ->leftJoin("users as approver", "clients.approved_by", "=", "approver.id")
            ->orderBy('clients.approved_at', 'desc')
            //->with('parent');
            ->with('parent', 'clientriskrating');

        // if ($request->has('risk_level') && $request->risk_level !== '') {
        //     // Get all clients first
        //     $clients = $clients->get()->filter(function ($client) use ($request) {
        //         return $client->risk_level === $request->risk_level;
        // });


        $perPage = $request->get("per_page");

        if ($request->get('search_string')) {
            $clients->where(function ($query) use ($request) {
                $query->where('clients.name', 'like', '%' . $request->get('search_string') . '%')
                    ->orWhere('clients.type', 'like', '%' . $request->get('search_string') . '%')
                    ->orWhere('creator.name', 'like', '%' . $request->get('search_string') . '%')
                    ->orWhere('approver.name', 'like', '%' . $request->get('search_string') . '%');
            });
        };

        if ($perPage == -1 || ($request->get('search_string') && $clients->count() < 10)) {
            $perPage = $clients->count();
        } elseif (!$request->get("per_page")) {
            $perPage = 10;
        }
        // return Datatables::of($clients)->make(true);
        // }
        if ($request->has('risk_level') && $request->risk_level !== '') {
            // Get all clients first
            $clients = $clients->get()->filter(function ($client) use ($request) {
                if ($request->risk_level == 'all')
                    return $client;
                // else if($request->risk_level == 'none')
                //     return empty($client->risk_level);
                else
                    return $client->risk_level === $request->risk_level;
            });

            // Manually paginate the collection
            $perPage = 10;
            $page = request()->get('page', 1);
            $clients = new \Illuminate\Pagination\LengthAwarePaginator(
                $clients->forPage($page, $perPage), // Slice the collection
                $clients->count(), // Total items
                $perPage,
                $page,
                ['path' => request()->url(), 'query' => request()->query()] // Preserve query params
            );
        } else {
            $clients = $clients->paginate($perPage);
        }

        //dd($clients);
        // $clients = [];
        return view('reports.risk', compact('clients', 'request'));
    }
}
