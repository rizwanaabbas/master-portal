<?php

namespace App\Http\Controllers;

use App\Helpers\Common;
use App\Models\Account;
use App\Helpers\VistaMoneyMerchantApis;
use App\Models\AccountTransaction;
use App\Models\AssignedCard;
use App\Models\Client;
use App\Models\ClientUser;
use App\Models\Currency;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class OperationsDashboardController extends Controller
{
    protected $currencyIds = null;

    protected $transactionNatures = [];

    public function __construct()
    {
        if (in_array("Crypto", \App\Helpers\Common::getEnabledModules())) {
            $this->currencyIds = Currency::select("id")->get();
            $this->transactionNatures = ["Cheque", "TT", "Internal", "Buy", "Sell", "Withdraw", "Deposit", "Deposit Fee"];
        } else {
            $this->currencyIds = Currency::where("is_crypto", false)->select("id")->get();
            $this->transactionNatures = ["Cheque", "TT", "Internal"];
        }

        if (in_array("Card", \App\Helpers\Common::getEnabledModules())) {
            array_push($this->transactionNatures, 'Top-up');
        }
    }

    public function index()
    {
        $clientsCount = Client::where("status", "Approved")->count();

        $accountsCount = Account::where("is_active", true)->whereIn("account_currency", $this->currencyIds)->count();

        $transactionsCount = AccountTransaction::where('status', 'Approved')
            ->whereIn("transaction_nature", $this->transactionNatures)
            ->whereIn('currency_id', $this->currencyIds)
            ->count();

        $currenciesCount = Currency::select(DB::raw('count(distinct name) as count'))
            ->whereIn('id', $this->currencyIds)
            ->where('is_active', true)
            ->first();

        $clientCards = [];
        if (in_array('Card', Common::getEnabledModules())) {

            $client_ids = ClientUser::where("user_id", Auth::user()->id)->pluck("client_id")->toArray();
            $linkedClients = Client::whereIn("id", $client_ids)->where("status", "Approved")->get();

            foreach ($linkedClients as $client) {
                if ($client->cardLink) {
                    $clientCards[] = $client->cardLink->cardDetails;
                    // break; // we need to remove this in phase 2, the logged-in user should see all cards related to the assigned clients
                }
            }
        }

        return view("dashboards.operation", compact('clientsCount', 'accountsCount', 'transactionsCount', 'currenciesCount', 'clientCards'));
    }

    public function clientsByStatus()
    {
        $clients = Client::select("status", DB::raw("count(*) as total"))
            ->groupBy("status")
            ->pluck("total", "status")
            ->toArray();

        $chart = [];
        foreach ($clients as $key => $value) {
            $chart[] = [$key, $value];
        }

        return $chart;
    }

    public function clientsByType()
    {
        $clients = Client::select("type", DB::raw("count(*) as total"))
            ->groupBy("type")
            ->get();

        $data = [];
        foreach ($clients as $client) {
            $data[] = [$client->type, $client->total];
        }

        return $data;
    }

    public function accountsByCurrency()
    {
        $clients = Client::select("status", DB::raw("count(*) as total"))
            ->groupBy("status")
            ->get();

        $data = [];
        foreach ($clients as $client) {
            $data[] = [$client->status, $client->total];
        }

        return $data;
    }

    public function transactionsByNature()
    {
        $transactions = AccountTransaction::select("transaction_nature", DB::raw("count(*) as total"))
            ->whereIn("transaction_nature", $this->transactionNatures)
            ->whereIn('currency_id', $this->currencyIds)
            ->groupBy("transaction_nature")
            ->pluck("total", "transaction_nature")
            ->toArray();

        $chart = [];
        foreach ($transactions as $key => $value) {
            $chart[] = [$key, $value];
        }

        return $chart;
    }

    public function transactionsByStatus()
    {
        $transactions = AccountTransaction::select("status", DB::raw("count(*) as total"))
            ->whereIn("transaction_nature", $this->transactionNatures)
            ->whereIn('currency_id', $this->currencyIds)
            ->groupBy("status")
            ->pluck("total", "status")
            ->toArray();

        $chart = [];
        foreach ($transactions as $key => $value) {
            $chart[] = [$key, $value];
        }

        return $chart;
    }

    public function transactionsbyDate()
    {
        $transactions = AccountTransaction::select(DB::raw("Date(`created_at`) as tr_date"), DB::raw("count(*) as total"))
            ->whereIn("transaction_nature", $this->transactionNatures)
            ->whereIn('currency_id', $this->currencyIds)
            ->groupBy("tr_date")
            ->pluck("total", "tr_date")
            ->toArray();

        $chart = [];
        foreach ($transactions as $key => $value) {
            $chart[] = [Carbon::parse($key)->format("d/M"), $value];
        }

        return $chart;
    }
}
