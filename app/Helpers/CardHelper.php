<?php

namespace App\Helpers;

use App\Models\User;
use App\Models\CardDetail;
use App\Models\UserCardLink;
use App\Models\CardStatement;
use Illuminate\Support\Facades\Auth;
use App\Helpers\VistaMoneyMerchantApis;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;

class CardHelper
{
    public static function getCardsByUser(User $user, $username = null)
    {
        //todo: need to refactor for multiple cards later
        if ($user) {
            $clients = $user->linkClients;
            $linkedCards = null;
            foreach ($clients as $cl) {
                if ($cl->client->cardLink) {
                    if ($username !== null && $cl->client->cardLink->username == $username) {
                        return $cl->client->cardLink;
                    }
                    $linkedCards[] = $cl->client->cardLink;
                }
            }
            return $linkedCards;
        }

        return false;
    }

    public static function getAllClientsCardsByUser(User $user)
    {
        if ($user) {
            $clientLinks = $user->linkClients;
            $linkedCards = null;

            foreach ($clientLinks as $cl) {
                foreach ($cl->client->cardLinks as $cardLink) {
                    $linkedCards[] = $cardLink;
                }
            }
            return $linkedCards;
        }
        return [];
    }

    public static function getLinkedClientsByUser(User $user, $username = null)
    {
        //todo: need to refactor for multiple cards later
        if ($user) {
            $linkedClients = $user->linkClients;
            $clients = null;
            foreach ($linkedClients as $cl) {
                if ($cl->client->cardLink) {
                    if ($username !== null && $cl->client->cardLink->username == $username) {
                        return $cl->client;
                    }
                    $clients[] = $cl->client;
                }
            }
            return $clients;
        }

        return false;
    }

    public static function isBalanceHidden($mpin)
    {
        return UserCardLink::where('mpin', $mpin)->first()->hide_balance;

        // if ($user) {
        //     $linkedClients = $user->LinkClients;
        //     foreach ($linkedClients as $cl) {
        //         if ($cl->client->cardLink->cardDetails->id == $id) {
        //             return $cl->client->cardLink->hide_balance;
        //         }
        //     }
        //     return true;
        // }
    }

    public static function checkAndDownloadStatements($card, $isAssignedCard)
    {
        try {
            // Log::info("Checking statements started.");
            $cardStatements = CardStatement::where('mpin', $card->mpin);
            // $startDate = Carbon::parse("2022-05-01 00:00:00");
            // $endDate = Carbon::parse("2022-06-01 23:59:59");

            $startDate = \Carbon\Carbon::parse(\Carbon\Carbon::parse($card->linkedCard->card_created_at)->format('Y-m-d 00:00:00'));
            $endDate = \Carbon\Carbon::parse($startDate->addMonth(1)->format('Y-m-d 23:59:59'));

            $startDate->subMonth(1);

            if ($cardStatements->count() > 0) {

                $monthsDifference = Carbon::now()->ceilMonth()->diffInMonths($startDate);

                for ($i = 1; $i <= $monthsDifference; $i++) {

                    $startDate->addMonth(1);
                    $endDate->addMonth(1);

                    //echo "Search Statements - Assigned: " . $isAssignedCard ." MPIN:". $card->mpin . " | date: " . $startDate ."\n";
                    //Log::info("Search Statements - MPIN: " . $card->mpin . " | date: " . $startDate);

                    $cardStatements = CardStatement::where('mpin', $card->mpin)
                        ->where('time', '>=', $startDate)
                        ->where('time', '<=', $endDate)
                        ->get();

                    if ($cardStatements->count() > 0) {

                        $cardStatements = CardStatement::where('mpin', $card->mpin)
                            ->where('time', '>=', $startDate->addMonth(1))
                            ->where('time', '<=', $endDate->addMonth(1))
                            ->get();

                        $startDate->subMonth(1);
                        $endDate->subMonth(1);

                        if ($cardStatements->count() > 0) {
                            if ($startDate->format("Y-m-d") != Carbon::now()->format("Y-m-d")) {
                                continue;
                            }
                        }
                    }

                    self::insertNewStatements($startDate, $endDate, $card, $isAssignedCard);
                }
            } else {
                self::iterateOverTheMonths($startDate, $endDate, $card, $isAssignedCard);
            }

            return true;
        } catch (Exception $e) {
            Log::error("Exception thrown checking the statements. Exception Message: " . $e->getMessage() . " | Line: " . $e->getLine());
        }
    }

    protected static function insertNewStatements($startDate, $endDate, $card, $isAssingedCard)
    {
        throw new Exception("Vista Money API is down, cannot fetch the statements.");

        if ($isAssingedCard) {
            // echo "\n ========= Assigned Card =============";
            $vistaMoneyApis = new VistaMoneyMerchantApis(true, null, null, $card);
        } else {
            //echo "\n ========= *** Not Assigned Card =============";
            //dd($card);
            $vistaMoneyApis = new VistaMoneyMerchantApis(true, $card);
        }

        $statement = $vistaMoneyApis->getSetteledCardStatementMonthWise($startDate->format('m'), $startDate->format('Y'));

        //dd($statement);
        if (count($statement) > 0) {
            // Log::info("Storing the Statements.");

            self::saveCardStatement($statement, $card->mpin, $startDate, $endDate);
        }
    }

    protected static function iterateOverTheMonths($startDate, $endDate, $card, $isAssignedCard)
    {
        $monthsDifference = Carbon::now()->ceilMonth()->diffInMonths($startDate);

        for ($i = 1; $i <= $monthsDifference; $i++) {

            $startDate->addMonth(1);
            $endDate->addMonth(1);

            self::insertNewStatements($startDate, $endDate, $card, $isAssignedCard);
        }
    }

    public static function saveCardStatement($response, $mpin, $startDate, $endDate)
    {
        $card = CardDetail::where("mpin", $mpin)->first();

        $existingStatement = CardStatement::where('mpin', $mpin)
            ->where('time', '>=', $startDate)
            ->where('time', '<=', $endDate)
            ->get();

        $nature = ['+' => 'Credit', '-' => 'Debit'];

        try {
            if ($existingStatement) {
                foreach ($existingStatement as $statement) {
                    // Log::info("Deleting Existing statements. Start Date: " . $startDate . " | End Date: " . $endDate);
                    $statement->delete();
                }
            }
            $str_json  = array();
            echo "\n Total Records: " . count($response) . "\n";
            echo "\n ====================================== \n";
            foreach ($response as $statement) {
                $md5Str = md5(json_encode($statement));
                $str_json[] = $md5Str;
                $count = CardStatement::where('trx_hash', $md5Str)->count();
                if ($count > 0)
                    continue;

                echo ".";
                // $existing = CardStatement::where('txn_id', $statement->txn_id)->first();

                // if (!$existing) {
                $cardStatement = new CardStatement();

                $cardStatement->local_card_id = $card ? $card->id : 0;
                $cardStatement->currency_code = $card ? $card->currency : 'USD';
                $cardStatement->mpin = $mpin;
                $cardStatement->terminal_owner_country = $statement->terminal_owner_country;
                $cardStatement->terminal_type = $statement->terminal_type;
                $cardStatement->processing_currency = $statement->processing_currency;
                $cardStatement->debit_credit = trim(preg_replace('/\s+/', ' ', $statement->debit_credit));
                $cardStatement->type = trim(preg_replace('/\s+/', ' ', $statement->type));
                $cardStatement->result = $statement->result;
                $cardStatement->txn_id = $statement->txn_id;
                $cardStatement->card_holder = $statement->card_holder;
                $cardStatement->visa_reference_no = isset($statement->visa_reference_no) ? $statement->visa_reference_no : "";
                $cardStatement->base_amount = $statement->base_amount;
                $cardStatement->cardholder_fee = $statement->cardholder_fee;
                $cardStatement->terminal_id = $statement->terminal_id;
                $cardStatement->base_currency = $statement->base_currency;
                $cardStatement->approval_code = $statement->approval_code;
                $cardStatement->card_number = $statement->card_number;
                $cardStatement->terminal_currency = $statement->terminal_currency;
                $cardStatement->terminal_amount = $statement->terminal_amount;
                $cardStatement->MCC = isset($statement->MCC) ? $statement->MCC : "0000";
                $cardStatement->type_of_operation = isset($statement->type_of_operation) ? $statement->type_of_operation : "";
                $cardStatement->card_id = isset($statement->card_id) ? $statement->card_id : $statement->card_token;
                $cardStatement->is_reversal = $statement->is_reversal;
                $cardStatement->processing_amount = $statement->processing_amount;
                $cardStatement->card_token = $statement->card_token;
                $cardStatement->iso_fee = $statement->iso_fee;
                $cardStatement->transaction_state = $statement->transaction_state;
                $cardStatement->time = Carbon::createFromFormat('d/m/Y', $statement->time)->format('Y-m-d H:i:s');
                $cardStatement->terminal_owner_title = $statement->terminal_owner_title;
                $cardStatement->transaction_nature = $nature[substr($statement->processing_amount, 0, 1)];
                $cardStatement->trx_hash = $md5Str;

                $cardStatement->save();
                // }
            }

            // $csvPath = storage_path('logs/statement_hashes.csv');
            // $file = fopen($csvPath, 'a'); // 'a' = append mode
            // $i = 1;
            // foreach ($str_json as $hash) {
            //     echo "\n$i: ******** Hash $hash *********\n";
            //     fputcsv($file, [$mpin, now()->toDateTimeString(),$statement->base_amount, $hash]);
            //     $i++;
            // }

            // fclose($file);
            //return $str_json;
        } catch (Exception $e) {
            Log::error("Exception thrown during saving a statement. Exception Message: " . $e->getMessage() . " | Line: " . $e->getLine());
            echo $e->getMessage();
        }
    }

    public static function getCardBalance($mpin)
    {
        $visaMoney = new VistaMoneyMerchantApis($mpin);
        $balance = $visaMoney->getCardBalance();

        return ($balance !== null) ? $balance : "N/A";
    }
}
