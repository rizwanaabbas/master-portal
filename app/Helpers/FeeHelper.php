<?php

/**
 * Created by Jehangir Wahid
 * Date: 16/10/2023
 */

namespace App\Helpers;

use App\Helpers\Common;
use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\Bank;
use App\Models\Currency;
use App\Models\MaintenanceFeeReport;
use App\Models\TRRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class FeeHelper
{

    protected static $shortName;

    public static function prepareMaintenanceFeeDeduction($clients, $previous_fee_report)
    {
        // Get the user ID with maintenance role.
        $maintenancePersonnelId = User::whereHas('roles', function ($query) {
            $query->where('name', 'maintenance');
        })->get()[0]->id;

        // Get currencies in order
        // $desiredOrder = ['EUR', 'AED', 'USD'];
        // $additionalCurrencies = ['RUB'];

        // $currencies = Currency::where("is_crypto", false)
        //     ->orderBy(function ($query) use ($desiredOrder, $additionalCurrencies) {
        //         $caseStatement = collect($desiredOrder)->map(function ($currency, $index) {
        //             return "WHEN short = '{$currency}' THEN {$index}";
        //         })->implode(' ');

        //         $additionalOrder = collect($additionalCurrencies)->map(function ($currency, $index) {
        //             return "WHEN short = '{$currency}' THEN {$index}";
        //         })->implode(' ');

        //         $query->selectRaw("CASE {$caseStatement} {$additionalOrder} ELSE " . count($desiredOrder) . " END");
        //     })->get();

        $currencyIds = Currency::select('id')
            ->where("is_crypto", false)
            ->orderByRaw(
                "CASE
                WHEN short = 'EUR' THEN 1
                WHEN short = 'AED' THEN 2
                WHEN short = 'USD' THEN 3
                WHEN short = 'RUB' THEN 8
                ELSE 4
            END, name ASC"
            )->pluck('id')
            ->toArray();
        if (empty($currencyIds)) {
            $currencyIds = [0];
        }
        $fieldPlaceholders = implode(',', array_fill(0, count($currencyIds), '?'));

        $now = Carbon::now();
        $processedClients = [];
        $credit_transaction_date = $now->format("Y-m-d H:i:s");
        $debit_transaction_date = $now->addSeconds(10)->format("Y-m-d H:i:s");
        $unique_id_date = $now->format('YmdHis');
        $default_client_account = null;
        $amount = 0;

        foreach ($clients as $client) {
            $default_client_account = null;
            // if ($client->client_id != 467) {
            //     continue;
            // }

            $accounts = Account::where("client_id", $client->client_id)
                ->where("is_active", true)
                ->where("is_crypto", false)
                ->orderByRaw("FIELD(account_currency, $fieldPlaceholders)", $currencyIds)
                ->get();

            $counter = $accounts->count();

            // Log::info("--------------------- Client -------------------------");
            // Log::info('Client ID: ' . $client->client_id . ' | Type: ' . $client->type);
            // Log::info("----------------------------------------------\n");

            foreach ($accounts as $account) {
                $counter--;
                // dd($counter);

                // Log::info("--------------------- Currency -------------------------");
                // Log::info('Currency ID: ' . $account->currency->id . ' | Short: ' . $account->currency->short . ' | Client ID: ' . $client->client_id . ' | Type: ' . $client->type  . ' | Counter: ' . $counter);
                // Log::info("----------------------------------------------\n");

                if (!$account->currency->maintenance) {
                    if ($default_client_account != null && $counter == 0) {
                        // $processedClients = self::addPendingRecord($counter, $default_client_account, $default_client_account, $client, $amount, 0, $unique_id_date, $processedClients);
                        if (array_key_exists($client->client_id, $previous_fee_report)) {
                            $previous_account_id = $previous_fee_report[$client->client_id]['account_id'];
                            $default_client_account = $previous_account_id ? Account::find($previous_account_id) : $default_client_account;
                            $previous_pending_months = $previous_fee_report[$client->client_id]['previous_pending_months'];
                        } else {
                            $previous_pending_months = 0;
                        }

                        $balance = Common::getAccountBalanceById($default_client_account->id);

                        $amount = self::getMaintenanceFeeAmount($default_client_account, $client->type);

                        if ($balance > 0) {
                            $amount = $amount - $balance;
                        }

                        // Get the Maintenance Bank account for the current currency.
                        $creditAccount = Bank::where('is_maintenance_fee_bank', true)
                            ->first()
                            ->accounts
                            ->where('account_currency', $default_client_account->account_currency)
                            ->first();

                        $unique_id = $client->unique_id ?? $client->client_id . $unique_id_date . $default_client_account->id;

                        self::deductPendingMaintenanceFee(
                            $default_client_account,
                            $creditAccount,
                            $amount,
                            $maintenancePersonnelId,
                            $credit_transaction_date,
                            $debit_transaction_date,
                            $unique_id,
                            $balance
                        );

                        if (($previous_pending_months + 1) >= 3) {
                            $processedClients['pending_for_3_months'] = true;
                            $processedClients[$client->client_id]['pending_for_3_months'] = true;
                        }

                        $processedClients[$client->client_id]['have_paid'] = false;
                        $processedClients[$client->client_id]['account_id'] = $default_client_account->id;
                        $processedClients[$client->client_id]['amount'] = $amount;
                        $processedClients[$client->client_id]['previous_pending_months'] = $previous_pending_months + 1;
                        $processedClients[$client->client_id]['unique_id'] = $unique_id;

                        break;
                        // if (!$havePaidPreviously) {
                        //     break;
                        // }
                    }

                    // Log::error('The Currency ' . $currency->name . ' doesn\'t have any defined Maintenance-Fee.');
                    continue;
                }

                $amount = self::getMaintenanceFeeAmount($account, $client->type);

                if ($amount > 0) {
                    // Get the Client account for the current currency
                    // $debitAccount = Account::where('client_id', $client->client_id)->where('account_currency', $currency->id)->first();

                    // if ($debitAccount) {
                    if ($default_client_account == null) {
                        $default_client_account = $account;

                        // Log::info("--------------------- Default Client Account -------------------------");
                        // Log::info('Default Client Account ID: ' . $default_client_account->id . ' | Default Client Account Title: ' . $default_client_account->title);
                        // Log::info("----------------------------------------------\n");
                    }

                    $debitAccount = $account;

                    // Log::info("--------------------- Debit Account -------------------------");
                    // Log::info('Account ID: ' . $debitAccount->id . ' | Title: ' . $debitAccount->title);
                    // Log::info("----------------------------------------------\n");

                    // if ($previous_fee_report)


                    // Check if the client has a balance in the current currency
                    $balance = Common::getAccountBalanceById($debitAccount->id);

                    // $havePaidPreviously = true;
                    $previous_amount = 0;
                    $previous_account_id = null;
                    $previous_pending_months = 0;

                    // if (isset($previous_fee_report[$client->client_id])) {
                    if (array_key_exists($client->client_id, $previous_fee_report)) {
                        $default_client_account = $previous_fee_report[$client->client_id]['account_id'] ? Account::find($previous_fee_report[$client->client_id]['account_id']) : $default_client_account;
                        // $havePaidPreviously = (bool)$previous_fee_report[$client->client_id]['have_paid'];
                        $havePaidPreviously = $previous_fee_report[$client->client_id]['have_paid'];
                        // dd(gettype($havePaidPreviously));
                        $previous_amount = $previous_fee_report[$client->client_id]['amount'];
                        $previous_account_id = $previous_fee_report[$client->client_id]['account_id'];
                        $previous_pending_months = $previous_fee_report[$client->client_id]['previous_pending_months'];
                    } else {
                        Log::info('CLIENT IS NOT THERE IN THE PREVIOUS REPORT. Client ID: ' . $client->client_id);
                    }

                    // Log::info("--------------------- Balance -------------------------");
                    // Log::info('Balance: ' . $balance . ' | Amount: ' . $amount);
                    // Log::info("----------------------------------------------\n");

                    if ($balance >= $amount) {


                        // Get the Maintenance Bank account for the current currency.
                        $creditAccount = Bank::where('is_maintenance_fee_bank', true)
                            ->first()
                            ->accounts
                            ->where('account_currency', $account->account_currency)
                            ->first();

                        // Log::info("--------------------- Credit Account -------------------------");
                        // Log::info('Account ID: ' . $creditAccount->id . ' | Title: ' . $creditAccount->title);
                        // Log::info("----------------------------------------------\n");

                        if (array_key_exists($client->client_id, $processedClients)) {
                            unset($processedClients[$client->client_id]);
                        }

                        $processedClients[$client->client_id]['have_paid'] = true;
                        $processedClients[$client->client_id]['account_id'] = $debitAccount->id;
                        $processedClients[$client->client_id]['amount'] = $amount;
                        $processedClients[$client->client_id]['previous_pending_months'] = $previous_pending_months;
                        $processedClients[$client->client_id]['unique_id'] = $client->unique_id ?? $client->client_id . $unique_id_date . $debitAccount->id;

                        // Charge the fee from the account
                        self::deductMaintenanceFee(
                            $debitAccount,
                            $creditAccount,
                            $amount,
                            $maintenancePersonnelId,
                            $credit_transaction_date,
                            $debit_transaction_date,
                            $processedClients[$client->client_id]['unique_id']
                        );

                        // Log::info("--------------------- Processed Clients Array - New Client ID: " . $client->client_id . " -------------------------");
                        // Log::info('Count: ' . count($processedClients));
                        // Log::info("----------------------------------------------");

                        break; // Stop when a payment is successful
                    } else {
                        // $processedClients = self::addPendingRecord($counter, $default_client_account, $debitAccount, $client, $amount, $previous_pending_months, $unique_id_date, $processedClients);

                        $recordAmount = $amount;

                        if ($counter == 0) {
                            $debitAccount = $default_client_account;

                            $balance = Common::getAccountBalanceById($debitAccount->id);

                            $amount = self::getMaintenanceFeeAmount($default_client_account, $client->type);
                            $recordAmount = $amount;

                            if ($balance > 0) {
                                $recordAmount = $recordAmount - $balance;
                            }
                        }

                        if (($previous_pending_months + 1) >= 3) {
                            $processedClients['pending_for_3_months'] = true;
                            $processedClients[$client->client_id]['pending_for_3_months'] = true;
                        }

                        $processedClients[$client->client_id]['have_paid'] = false;
                        $processedClients[$client->client_id]['account_id'] = $debitAccount->id;
                        $processedClients[$client->client_id]['amount'] = $recordAmount;
                        $processedClients[$client->client_id]['previous_pending_months'] = $previous_pending_months + 1;
                        $processedClients[$client->client_id]['unique_id'] = $client->unique_id ?? $client->client_id . $unique_id_date . $debitAccount->id;

                        if ($counter == 0) {

                            // Log::info("--------------------- Default Client Account -------------------------");
                            // Log::info('Default Client Account ID: ' . $default_client_account->id . ' | Default Client Account Title: ' . $default_client_account->title);
                            // Log::info("----------------------------------------------\n");

                            // Log::info("--------------------- Debit Account -------------------------");
                            // Log::info('Account ID: ' . $debitAccount->id . ' | Title: ' . $debitAccount->title);
                            // Log::info("----------------------------------------------\n");

                            // Get the Maintenance Bank account for the current currency.
                            $creditAccount = Bank::where('is_maintenance_fee_bank', true)
                                ->first()
                                ->accounts
                                ->where('account_currency', $debitAccount->account_currency)
                                ->first();

                            self::deductPendingMaintenanceFee(
                                $debitAccount,
                                $creditAccount,
                                $amount,
                                $maintenancePersonnelId,
                                $credit_transaction_date,
                                $debit_transaction_date,
                                $processedClients[$client->client_id]['unique_id'],
                                $balance
                            );

                            break;
                        }

                        // if (!$havePaidPreviously) {
                        //     break;
                        // }
                        // Log::critical('The client Account ' . $debitAccount->title . ' don\'t have sufficient balance to pay ' . $amount . ' ' . $currency->short . ' as the Monthly Maintenance-Fee.');
                    }
                    // }
                }
                // else {
                //     // TODO: if $default_client_account != null && $counter == 0 then deduct the fee from default account
                // }

                // Log::info("----------------------------------------------\n\n");
                // Log::info("^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^\n\n");
            }

            // Log::info("----------------------------------------------\n\n\n\n");
            Log::info("#################################################################################################################\n\n\n\n");
        }

        Log::info("--------------------- Processed Clients Array -------------------------");
        Log::info('Count: ' . count($processedClients));
        Log::info("----------------------------------------------");

        $data['clients'] = $processedClients;
        $data['date_charged'] = $credit_transaction_date;

        return $data;
    }

    private static function getMaintenanceFeeAmount($account, $client_type)
    {
        $fee_amount = 0;

        if ($account->maintenance_fee && $account->maintenance_fee > 0) {
            $fee_amount = $account->maintenance_fee;
        } else {
            $feeMappingByType = [
                'individual' => $account->currency->maintenance->individual_fee,
                'company' => $account->currency->maintenance->company_fee
            ];

            $fee_amount = $feeMappingByType[$client_type];
        }

        return $fee_amount;
    }

    private static function addPendingRecord($counter, $default_client_account, $debitAccount, $client, $amount, $previous_pending_months, $unique_id_date, $processedClients)
    {
        if ($counter == 0) {
            $debitAccount = $default_client_account;

            $balance = Common::getAccountBalanceById($debitAccount->id);

            // Log::info("000000000000000000000 COUNTER IS 0 0000000000000000000000000");
            // Log::info('Default Client Account ID: ' . $default_client_account->id . ' | Default Client Account Title: ' . $default_client_account->title);
            // Log::info("0000000000000000000000000000000000000000000000\n");

            $feeMappingForDefaultAccount = ['individual' => $default_client_account->currency->maintenance->individual_fee, 'company' => $default_client_account->currency->maintenance->company_fee];
            $amount = $feeMappingForDefaultAccount[$client->type];

            if ($balance > 0) {
                $amount = $amount - $balance;
            }
        }

        if (($previous_pending_months + 1) >= 3) {
            // TODO: Notify the Finance Managers about the Client, who is not paying the maintenance fee for 3 months.
        }

        $processedClients[$client->client_id]['have_paid'] = false;
        $processedClients[$client->client_id]['account_id'] = $debitAccount->id;
        $processedClients[$client->client_id]['amount'] = $amount;
        $processedClients[$client->client_id]['previous_pending_months'] = $previous_pending_months + 1;
        $processedClients[$client->client_id]['unique_id'] = $client->unique_id ?? $client->client_id . $unique_id_date . $debitAccount->id;

        return $processedClients;

        // if (!$havePaidPreviously) {
        //     break;
        // }
    }

    private static function deductPendingMaintenanceFee(
        $debit_account,
        $credit_account,
        $amount,
        $maintenancePersonnelId,
        $credit_transaction_date,
        $debit_transaction_date,
        $unique_id,
        $balance
    ) {
        self::setOwnCompanyData();

        $trRequest = new TRRequest();

        // create debit transaction
        $trRequest->maintenance_fee_unique_id = $unique_id;
        $trRequest->account_id = $debit_account->id;
        $trRequest->currency_id = $debit_account->account_currency;
        $trRequest->transaction_type = 'Debit';
        $trRequest->transaction_nature = 'Maintenance Fee';
        $trRequest->transaction_date = $debit_transaction_date;
        $trRequest->amount = $amount;
        $trRequest->otp_verified = true;
        $trRequest->remarks = self::$shortName . ' Maintenance Fee.';
        $trRequest->status = 'Approved';
        $trRequest->created_by = $maintenancePersonnelId;
        $trRequest->approved_by = $maintenancePersonnelId;
        $trRequest->approved_at = Carbon::now();

        $trRequest->save();

        // Log::info("--------------------- Transaction Request - " . $unique_id . " -------------------------");
        // Log::info('Transaction ID: ' . $trRequest->id);
        // Log::info("----------------------------------------------\n\n\n");

        $fee_debit_transaction = new AccountTransaction();

        $fee_debit_transaction->maintenance_fee_unique_id = $unique_id;
        $fee_debit_transaction->tr_request_id = $trRequest->id;
        $fee_debit_transaction->account_id = $debit_account->id;
        $fee_debit_transaction->currency_id = $debit_account->account_currency;
        $fee_debit_transaction->transaction_type = 'Debit';
        $fee_debit_transaction->transaction_nature = 'Maintenance Fee';
        $fee_debit_transaction->transaction_date = $debit_transaction_date;
        $fee_debit_transaction->amount = $amount;
        // $fee_debit_transaction->ex_rate = $exchangeRate;
        $fee_debit_transaction->is_fee_transaction = true;
        $fee_debit_transaction->remarks = self::$shortName . ' Maintenance Fee.';
        $fee_debit_transaction->status = 'Approved';
        $fee_debit_transaction->created_by = $maintenancePersonnelId;
        $fee_debit_transaction->approved_by = $maintenancePersonnelId;
        $fee_debit_transaction->approved_at = Carbon::now();

        $fee_debit_transaction->save();

        Log::info("--------------------- Debit Transaction - " . $unique_id . " -------------------------");
        Log::info('Transaction ID: ' . $fee_debit_transaction->id);
        Log::info("----------------------------------------------\n\n\n");

        $trRequest->link_tr_id = $fee_debit_transaction->id;
        $trRequest->save();

        if ($balance > 0) {
            $fee_credit_transaction = new AccountTransaction();

            $fee_credit_transaction->maintenance_fee_unique_id = $unique_id;
            $fee_credit_transaction->account_id = $credit_account->id;
            $fee_credit_transaction->tr_request_id = $trRequest->id;
            $fee_credit_transaction->link_tr_id = $fee_debit_transaction->id;
            $fee_credit_transaction->currency_id = $credit_account->account_currency;
            $fee_credit_transaction->transaction_type = 'Credit';
            $fee_credit_transaction->transaction_nature = 'Maintenance Fee';
            $fee_credit_transaction->transaction_date = $credit_transaction_date;
            $fee_credit_transaction->amount = $balance; // $amount * $exchangeRate;
            // $fee_credit_transaction->ex_rate = $exchangeRate;
            $fee_credit_transaction->is_fee_transaction = true;
            $fee_credit_transaction->remarks = self::$shortName . ' Maintenance Fee deduction from ' . $debit_account->title . ' - ' . $debit_account->account_number . '.';
            $fee_credit_transaction->status = 'Approved';
            $fee_credit_transaction->created_by = $maintenancePersonnelId;
            $fee_credit_transaction->approved_by = $maintenancePersonnelId;
            $fee_credit_transaction->approved_at = Carbon::now();

            $fee_credit_transaction->save();

            // Log::info("--------------------- Credit Transaction - " . $unique_id . " -------------------------");
            // Log::info('Transaction ID: ' . $fee_credit_transaction->id);
            // Log::info("----------------------------------------------\n\n\n");

            $fee_debit_transaction->link_tr_id = $fee_credit_transaction->id;
            $fee_debit_transaction->save();
        }
    }

    private static function deductMaintenanceFee(
        $debit_account,
        $credit_account,
        $amount,
        $maintenancePersonnelId,
        $credit_transaction_date,
        $debit_transaction_date,
        $unique_id
    ) {
        self::setOwnCompanyData();

        $trRequest = new TRRequest();

        // create debit transaction
        $trRequest->maintenance_fee_unique_id = $unique_id;
        $trRequest->account_id = $debit_account->id;
        $trRequest->currency_id = $debit_account->account_currency;
        $trRequest->transaction_type = 'Debit';
        $trRequest->transaction_nature = 'Maintenance Fee';
        $trRequest->transaction_date = $debit_transaction_date;
        $trRequest->amount = $amount;
        $trRequest->otp_verified = true;
        $trRequest->remarks = self::$shortName . ' Maintenance Fee.';
        $trRequest->status = 'Approved';
        $trRequest->created_by = $maintenancePersonnelId;
        $trRequest->approved_by = $maintenancePersonnelId;
        $trRequest->approved_at = Carbon::now();

        $trRequest->save();

        // Log::info("--------------------- Transaction Request - " . $unique_id . " -------------------------");
        // Log::info('Transaction ID: ' . $trRequest->id);
        // Log::info("----------------------------------------------\n\n\n");

        $fee_debit_transaction = new AccountTransaction();

        $fee_debit_transaction->maintenance_fee_unique_id = $unique_id;
        $fee_debit_transaction->account_id = $debit_account->id;
        $fee_debit_transaction->tr_request_id = $trRequest->id;
        $fee_debit_transaction->currency_id = $debit_account->account_currency;
        $fee_debit_transaction->transaction_type = 'Debit';
        $fee_debit_transaction->transaction_nature = 'Maintenance Fee';
        $fee_debit_transaction->transaction_date = $debit_transaction_date;
        $fee_debit_transaction->amount = $amount;
        // $fee_debit_transaction->ex_rate = $exchangeRate;
        $fee_debit_transaction->is_fee_transaction = true;
        $fee_debit_transaction->remarks = self::$shortName . ' Maintenance Fee.';
        $fee_debit_transaction->status = 'Approved';
        $fee_debit_transaction->created_by = $maintenancePersonnelId;
        $fee_debit_transaction->approved_by = $maintenancePersonnelId;
        $fee_debit_transaction->approved_at = Carbon::now();

        $fee_debit_transaction->save();

        Log::info("--------------------- Debit Transaction - " . $unique_id . " -------------------------");
        Log::info('Transaction ID: ' . $fee_debit_transaction->id);
        Log::info("----------------------------------------------\n\n\n");

        $trRequest->link_tr_id = $fee_debit_transaction->id;
        $trRequest->save();

        $fee_credit_transaction = new AccountTransaction();

        $fee_credit_transaction->maintenance_fee_unique_id = $unique_id;
        $fee_credit_transaction->account_id = $credit_account->id;
        $fee_credit_transaction->tr_request_id = $trRequest->id;
        $fee_credit_transaction->link_tr_id = $fee_debit_transaction->id;
        $fee_credit_transaction->currency_id = $credit_account->account_currency;
        $fee_credit_transaction->transaction_type = 'Credit';
        $fee_credit_transaction->transaction_nature = 'Maintenance Fee';
        $fee_credit_transaction->transaction_date = $credit_transaction_date;
        $fee_credit_transaction->amount = $amount; // $amount * $exchangeRate;
        // $fee_credit_transaction->ex_rate = $exchangeRate;
        $fee_credit_transaction->is_fee_transaction = true;
        $fee_credit_transaction->remarks = self::$shortName . ' Maintenance Fee deduction from ' . $debit_account->title . ' - ' . $debit_account->account_number . '.';
        $fee_credit_transaction->status = 'Approved';
        $fee_credit_transaction->created_by = $maintenancePersonnelId;
        $fee_credit_transaction->approved_by = $maintenancePersonnelId;
        $fee_credit_transaction->approved_at = Carbon::now();

        $fee_credit_transaction->save();

        // Log::info("--------------------- Credit Transaction - " . $unique_id . " -------------------------");
        // Log::info('Transaction ID: ' . $fee_credit_transaction->id);
        // Log::info("----------------------------------------------\n\n\n");

        $fee_debit_transaction->link_tr_id = $fee_credit_transaction->id;
        $fee_debit_transaction->save();
    }

    private static function setOwnCompanyData()
    {
        $ownCompany = Common::getOwnCompanyData() ? Common::getOwnCompanyData() : null;
        self::$shortName = $ownCompany ? $ownCompany->short_name : '';
    }

    public static function storeMaintenanceFeeReport($clients, $dateCharged)
    {
        foreach ($clients as $key => $client) {
            if ($key == 'pending_for_3_months') {
                continue;
            }

            // dd($key, $client);
            $report = new MaintenanceFeeReport();

            $report->unique_id = $client['unique_id'];
            $report->client_id = $key;
            $report->have_paid = $client['have_paid'];
            $report->account_id = $client['account_id'];
            $report->amount = $client['amount'];
            $report->previous_pending_months = $client['previous_pending_months'];
            $report->date_charged = $dateCharged;

            $report->save();
        }
    }

    public static function updateMaintenanceFeeReport($clients, $dateCharged)
    {
        foreach ($clients as $key => $client) {
            if ($key == 'pending_for_3_months') {
                continue;
            }

            $report = MaintenanceFeeReport::where('unique_id', $client['unique_id'])->first();

            $report->unique_id = $client['unique_id'];
            $report->client_id = $key;
            $report->have_paid = $client['have_paid'];
            $report->account_id = $client['account_id'];
            $report->amount = $client['amount'];
            $report->previous_pending_months =  $client['previous_pending_months'];
            $report->date_charged = $dateCharged;

            $report->save();
        }
    }

    public static function reconcileMaintenanceFee($client_account_id, $credit_amount, $request)
    {
        // try {
        $pendingFees = MaintenanceFeeReport::where('account_id', $client_account_id)->where('have_paid', 0)->where('previous_pending_months', '>', 0)->get();

        if ($pendingFees->count() > 0) {

            if (!$request->get("ex_rate") || $request->get("ex_rate") == 1) {
                if ($request->get("service_fee_client_account") == $client_account_id && $request->get("service_fee") > 0 &&  !$request->has('is_service_fee_trn_hidden_for_client')) {
                    $credit_amount = $credit_amount - $request->get("service_fee");
                }

                if ($request->get("trn_fee_client_account") == $client_account_id && $request->get("trn_fee") > 0 && !$request->has('is_trn_fee_trn_hidden_for_client')) {
                    $credit_amount = $credit_amount - $request->get("trn_fee");
                }
            }

            $maintenancePersonnelId = User::whereHas('roles', function ($query) {
                $query->where('name', 'maintenance');
            })->get()[0]->id;

            $debit_account = Account::find($client_account_id);
            $deducted_amount = 0;
            $now = Carbon::now();
            $credit_transaction_date = $now->format("Y-m-d H:i:s");
            // $debit_transaction_date = $now->addSeconds(10)->format("Y-m-d H:i:s");
            $credit_account = Bank::where('is_maintenance_fee_bank', true)
                ->first()
                ->accounts
                ->where('account_currency', $debit_account->account_currency)
                ->first();

            foreach ($pendingFees as $pendingFee) {

                Log::info("----------------------------------------------");
                Log::info('Client Account ID: ' . $pendingFee->account_id . ' | Title: ' . $debit_account->title . ' | Amount: ' . $pendingFee->amount . ' | Deducted Amount: ' . $deducted_amount . ' | Credit Amount: ' . $credit_amount);
                Log::info("----------------------------------------------\n");

                if (($pendingFee->amount + $deducted_amount) <= $credit_amount) {

                    $deducted_amount = self::reconcileFeeDeduction(
                        $pendingFee,
                        $deducted_amount,
                        $debit_account,
                        // $debit_transaction_date,
                        $pendingFee->amount,
                        $maintenancePersonnelId,
                        $credit_account,
                        $credit_transaction_date
                    );

                    $pendingFee->previous_pending_months = 0;
                    $pendingFee->have_paid = 1;

                    $pendingFee->save();
                } elseif ((($pendingFee->amount + $deducted_amount) - $credit_amount) > 0 && $credit_amount - $deducted_amount > 0) {
                    $remaining_amount = $credit_amount - $deducted_amount;

                    $deducted_amount = self::reconcileFeeDeduction(
                        $pendingFee,
                        $deducted_amount,
                        $debit_account,
                        // $debit_transaction_date,
                        $remaining_amount,
                        $maintenancePersonnelId,
                        $credit_account,
                        $credit_transaction_date
                    );

                    $pendingFee->previous_pending_months = 1;
                    $pendingFee->amount = $pendingFee->amount - $remaining_amount;

                    $pendingFee->save();
                }

                $credit_amount = $credit_amount - $deducted_amount;
            }
        }
        // } catch (\Exception $e) {
        //     throw new \Exception($e->getMessage());
        // }
    }

    private static function reconcileFeeDeduction(
        $pendingFee,
        $deducted_amount,
        $debit_account,
        // $debit_transaction_date,
        $amount,
        $maintenancePersonnelId,
        $credit_account,
        $credit_transaction_date
    ) {
        self::setOwnCompanyData();

        $fee_debit_transaction = AccountTransaction::select('id', 'link_tr_id')->where('is_fee_transaction', true)
            ->where('maintenance_fee_unique_id', $pendingFee->unique_id)
            ->where('transaction_type', 'Debit')
            ->first();

        $tr_request_id = TRRequest::select('id', 'maintenance_fee_unique_id')->where('maintenance_fee_unique_id', $pendingFee->unique_id)->first()->id;

        // // create debit transaction
        // $trRequest->maintenance_fee_unique_id = $pendingFee->unique_id;
        // $trRequest->account_id = $debit_account->id;
        // $trRequest->currency_id = $debit_account->account_currency;
        // $trRequest->transaction_type = 'Debit';
        // $trRequest->transaction_nature = 'Maintenance Fee';
        // $trRequest->transaction_date = $debit_transaction_date;
        // $trRequest->amount = $amount;
        // $trRequest->otp_verified = true;
        // $trRequest->remarks = self::$shortName . ' Maintenance Fee reconciliation for ' . $debit_account->title . ' - ' . $debit_account->account_number . '.';
        // $trRequest->status = 'Approved';
        // $trRequest->created_by = $maintenancePersonnelId;
        // $trRequest->approved_by = $maintenancePersonnelId;
        // $trRequest->approved_at = Carbon::now();

        // $trRequest->save();

        $fee_credit_transaction = new AccountTransaction();

        $fee_credit_transaction->maintenance_fee_unique_id = $pendingFee->unique_id;
        $fee_credit_transaction->account_id = $credit_account->id;
        $fee_credit_transaction->tr_request_id = $tr_request_id;
        $fee_credit_transaction->link_tr_id = $fee_debit_transaction->id;
        $fee_credit_transaction->currency_id = $credit_account->account_currency;
        $fee_credit_transaction->transaction_type = 'Credit';
        $fee_credit_transaction->transaction_nature = 'Maintenance Fee';
        $fee_credit_transaction->transaction_date = $credit_transaction_date;
        $fee_credit_transaction->amount = $amount; // $amount * $exchangeRate;
        // $fee_credit_transaction->ex_rate = $exchangeRate;
        $fee_credit_transaction->is_fee_transaction = true;
        $fee_credit_transaction->remarks = self::$shortName . ' Maintenance Fee reconciliation for ' . $debit_account->title . ' - ' . $debit_account->account_number . '.';
        $fee_credit_transaction->status = 'Approved';
        $fee_credit_transaction->created_by = $maintenancePersonnelId;
        $fee_credit_transaction->approved_by = $maintenancePersonnelId;
        $fee_credit_transaction->approved_at = Carbon::now();

        $fee_credit_transaction->save();

        $fee_debit_transaction->link_tr_id = $fee_debit_transaction->link_tr_id ?? $fee_credit_transaction->id;
        $fee_debit_transaction->save();


        return $deducted_amount + $pendingFee->amount;
    }

    /**
     * ADDITIVE integration hook for the Fee Engine module.
     *
     * Exposes the existing per-account fee amount (as used by the maintenance
     * fee flow) so the Fee Engine can incorporate it without duplicating or
     * modifying any existing logic. Returns a flat amount in the account's
     * currency, or 0.0 if it cannot be resolved.
     */
    public static function resolveAccountFeeAmount($account): float
    {
        try {
            $client = $account->client;
            $clientType = $client && !empty($client->type) ? strtolower($client->type) : 'individual';
            if (!in_array($clientType, ['individual', 'company'], true)) {
                $clientType = 'individual';
            }

            return (float) self::getMaintenanceFeeAmount($account, $clientType);
        } catch (\Throwable $e) {
            Log::warning('[FeeHelper] resolveAccountFeeAmount failed', [
                'account_id' => $account->id ?? null,
                'error'      => $e->getMessage(),
            ]);

            return 0.0;
        }
    }
}
