<?php

namespace App\Http\Controllers;

use App\Helpers\CardHelper;
use App\Helpers\ClientHelper;
use App\Helpers\Common;
use App\Helpers\VistaMoneyMerchantApis;
use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\Client;
use App\Models\ClientUser;
use App\Models\Currency;
use App\Models\UserCardLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use Exception;

class ClientChartController extends Controller
{
    protected $currencyIds = null;

    protected $transactionNatures = [];

    public function __construct()
    {
        if (in_array("Crypto", Common::getEnabledModules())) {
            $this->currencyIds = Currency::select("id")->get();
            $this->transactionNatures = ["Cheque", "TT", "Internal", "Buy", "Sell", "Withdraw", "Deposit", "Deposit Fee"];
        } else {
            $this->currencyIds = Currency::where("is_crypto", false)->select("id")->get();
            $this->transactionNatures = ["Cheque", "TT", "Internal"];
        }

        if (in_array("VistaMoney", Common::getEnabledModules())) {
            array_push($this->transactionNatures, 'Top-up');
        }
    }

    public function index()
    {

        try {
            $client_ids = ClientUser::where("user_id", Auth::user()->id)->pluck("client_id")->toArray();

            /**
             * This code is duplicated here. Because, the function in the first condition -- Common::showClientCrypto() -- requires Auth::user,
             * which is null if we call that helper function from the constructor.
             */
            if (Common::showClientCrypto() && in_array("Crypto", Common::getEnabledModules())) {
                $this->currencyIds = Currency::select("id")->get();
                $this->transactionNatures = ["Cheque", "TT", "Internal", "Buy", "Sell", "Withdraw", "Deposit", "Deposit Fee"];
            } else {
                $this->currencyIds = Currency::where("is_crypto", false)->select("id")->get();
                $this->transactionNatures = ["Cheque", "TT", "Internal"];
            }

            $account_ids = Account::whereIn("client_id", $client_ids)
                ->whereIn("account_currency", $this->currencyIds)
                ->where("is_active", true)
                ->pluck("id")
                ->toArray();

            $account_currencies = Account::whereIn("client_id", $client_ids)->where("is_active", true)
                ->select("account_currency")->distinct()->pluck("account_currency")->toArray();

            $currencies = Currency::whereIn("id", $this->currencyIds)->whereIn("id", $account_currencies)->get();
            $clients = Client::whereIn("id", $client_ids)->where("status", "Approved")->get();

            $accounts = [];
            if ($currencies) {
                foreach ($currencies as $key => $value) {

                    $credit = AccountTransaction::whereIn("account_id", $account_ids)
                        ->where("currency_id", $value->id)
                        // ->whereIn("transaction_nature", $this->transactionNatures)
                        ->where("transaction_type", "Credit")
                        ->where("status", "Approved")
                        ->sum("amount");

                    $debit = AccountTransaction::whereIn("account_id", $account_ids)
                        ->where("currency_id", $value->id)
                        // ->whereIn("transaction_nature", $this->transactionNatures)
                        ->where("transaction_type", "Debit")
                        ->where("status", "Approved")
                        ->sum("amount");

                    $accounts[$key]['balance'] = $credit - $debit;
                    $accounts[$key]['logo'] = $value->logo;
                    $accounts[$key]['short'] = $value->short;
                    $accounts[$key]['is_crypto'] = $value->is_crypto;
                }
            }

            // last 5 transactions
            $transactions = AccountTransaction::whereIn("account_id", $account_ids)
                ->where("status", "Approved")
                ->where("is_hidden_for_clients", false)
                ->orderBy("created_at", "desc")
                ->limit(3)
                ->get();

            // get multiple cards for the logged in user
            $clientCards = CardHelper::getAllClientsCardsByUser(Auth::user());

            if (!$clientCards) {
                $clientCards = [];
            }

            $balance = 'Loading...';
            // foreach ($clients as $client) {
            //     if ($client->cardLinks) {
            //         $clientCards[] = $client->cardLinks;
            //         // break; // we need to remove this in phase 2, the logged-in user should see all cards related to the assigned clients
            //     }
            // }


            // client id -> card details


            //            $client = ClientHelper::getClientIdByUserIdAndName(Auth::user()->id, Auth::user()->name);
            //            $linkedUser = UserCardLink::where('client_id', $client->id)->first();
            //            $cardDetails = null;
            //
            //            if ($linkedUser) {
            //                $cardDetails = $linkedUser->cardDetails;
            //                $balance = 'Loading...';
            //            }

            return view("client-portal.dashboard.home-charts", compact('accounts', 'transactions', 'currencies', 'clients', 'clientCards', 'balance'));
        } catch (Exception $e) {
            $clientCards = null;
            return view("client-portal.dashboard.home-charts", compact('accounts', 'transactions', 'currencies', 'clients', 'clientCards'))->with('error', $e->getMessage());
        }
    }

    public function transactionByNature()
    {
        $clients = ClientUser::where("user_id", Auth::user()->id)->pluck("client_id")->toArray();
        $accounts = Account::whereIn("client_id", $clients)
            ->whereIn("account_currency", $this->currencyIds)
            ->pluck("id")
            ->toArray();

        $modes = $this->transactionNatures;

        foreach ($modes as $mode) {

            $credit = AccountTransaction::whereIn("account_id", $accounts)
                ->where("transaction_type", "Credit")
                ->where("transaction_nature", $mode)
                ->where("status", "Approved")
                ->sum("amount");

            $debit = AccountTransaction::whereIn("account_id", $accounts)
                ->where("transaction_type", "Debit")
                ->where("transaction_nature", $mode)
                ->where("status", "Approved")
                ->sum("amount");

            $chart[] = [$mode, $credit, $debit];
        }

        return $chart;
    }

    public function creditVsDebit()
    {
        $clients = ClientUser::where("user_id", Auth::user()->id)->pluck("client_id")->toArray();
        $accounts = Account::whereIn("client_id", $clients)
            ->whereIn("account_currency", $this->currencyIds)
            ->pluck("id")
            ->toArray();

        $chart[] = ["Credit", AccountTransaction::whereIn("account_id", $accounts)->whereIn("transaction_nature", $this->transactionNatures)->where("transaction_type", "Credit")->where("status", "Approved")->sum("amount")];
        $chart[] = ["Debit", AccountTransaction::whereIn("account_id", $accounts)->whereIn("transaction_nature", $this->transactionNatures)->where("transaction_type", "Debit")->where("status", "Approved")->sum("amount")];

        return $chart;
    }

    public function amountByCurrency()
    {
        $clients = ClientUser::where("user_id", Auth::user()->id)->pluck("client_id")->toArray();
        $accounts = Account::whereIn("client_id", $clients)->pluck("id")->toArray();

        // get account currencies
        $currencies = Account::where("id", $accounts)
            ->whereIn("account_currency", $this->currencyIds)
            ->select("account_currency")
            ->distinct("account_currency")
            ->get();

        if ($currencies) {

            foreach ($currencies as $currency) {
                $credit = AccountTransaction::whereIn("account_id", $accounts)
                    ->where("transaction_type", "Credit")
                    ->whereIn("transaction_nature", $this->transactionNatures)
                    ->where("currency_id", $currency->account_currency)
                    ->where("status", "Approved")
                    ->sum("amount");

                $debit = AccountTransaction::whereIn("account_id", $accounts)
                    ->where("transaction_type", "Debit")
                    ->whereIn("transaction_nature", $this->transactionNatures)
                    ->where("currency_id", $currency->account_currency)
                    ->where("status", "Approved")
                    ->sum("amount");

                $chart[] = [Common::getCurrencyShort($currency->account_currency), ($credit - $debit)];
            }
        } else {
            $chart = [];
        }


        //        if ($accounts){
        //            $credit = Account::whereIn("id", $accounts)->where("transaction_type", "Credit")->where("status", "Approved")->sum("amount");
        //            $debit = Account::whereIn("id", $accounts)->where("transaction_type", "Debit")->where("status", "Approved")->sum("amount");
        //
        //            $chart[] = [Common::getCurrencyShort($account->account_currency), ($credit - $debit)];
        //        }else{
        //            $chart = [];
        //        }

        return $chart;
    }
}
