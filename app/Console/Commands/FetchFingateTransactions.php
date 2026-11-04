<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CardStatement;
use App\Models\CardDetail;
use App\Models\CardStatementsDetail;
use App\Models\Currency;
use App\Helpers\FingateHelper;
use Carbon\Carbon;
use Log;

class FetchFingateTransactions extends Command
{
    protected $signature = 'transactions:fetch-fingate';
    protected $description = 'Fetch transactions from Fingate for the last 10 days and store them in card_statements';
    //php artisan transactions:fetch-fingate

    public function handle()
    {
        $this->info("Fetching Fingate transactions...");
        
        $startDate = Carbon::now()->subDays(10)->startOfDay()->setTimezone('UTC')->format('Y-m-d\T00:00:00.000\Z');
        $endDate = Carbon::now()->startOfDay()->setTimezone('UTC')->format('Y-m-d\T00:00:00.000\Z');

        try {
            
            $fingateHelper = new FinGateHelper();
            
            $filters = [
               // "SearchStartDate"=>$startDate,
                //"SearchEndDate"=>$endDate
            ];
            $filters = '';
            $response = $fingateHelper->getAllTransactions($filters);
            //print_r(json_encode($response)); die();
            //dd($response);

            if (empty($response) || empty($response->TransactionList)) {
                $this->warn("No transactions found.");
                return;
            }
          // dd($response->TransactionList);
            $count = 0;
            foreach ($response->TransactionList as $txn) {
               //$txn =  json_decode($txn);
               // dd($txn->CardId);
                $card_detail = CardDetail::where('card_id',$txn->CardId)->first();
                if(empty($card_detail) ) {
                    $this->error("Card Detail not found in DB for CardId ".$txn->CardId);
                    continue;
                } 
                //else if(!empty($card_detail->id)) {
                //     $this->info("Card Detail ID =>".$card_detail->id." found for CardId ".$txn->CardId);
                // }

                $transaction_nature = $txn->TransactionEffect === 'D' ? 'Debit' : 'Credit';
                $base_currency = Currency::where('short', $txn->OriginalTransactionCurrencyCode)->first();
                $terminal_currency = Currency::where('short', $txn->SettlementTransactionCurrencyCode)->first();
                //dd($base_currency);
                if ( $txn->FeeAmount == $txn->BillingAmount &&
                    strpos(strtolower($txn->Description), 'transaction fee') !== false ) {
                    $txn->TransactionId = $txn->TransactionId . '-fee';
                    $txn->SettlementTransactionAmount = $txn->BillingAmount;
                }
                $tr_description = 'NA';
                $is_reversal = 0;
                if(!empty($txn->Description)) {
                    $tr_description = $txn->Description;
                    if(!empty($txn->TransactionCodeName) && strtolower($txn->TransactionCodeName) == 'Refund') {
                        $tr_description .= ' - ' . ucfirst($txn->TransactionCodeName);
                        $is_reversal  = strtolower($txn->TransactionCodeName);
                    }
                }

                $tr = [
                    'local_card_id'         => $card_detail->id ?? 0,
                    'currency_code'         => $txn->BillingCurrencyCode ?? 'USD',
                    'mpin'                  => $card_detail->mpin ?? 0,//$txn->ReferenceNumber ?? null,
                    'terminal_owner_country'    => 'NA',
                    'terminal_type' => 'NA',
                    'processing_currency'   => $base_currency->id ?? '',
                    'debit_credit'          => $tr_description ?? null,
                    'type'                  => $txn->Description ?? null,
                    'txn_id'                => $txn->TransactionId ?? 0,
                    //'card_holder'           => $txn->CustomerId ?? null,
                    'card_holder'         => $card_detail->cardHoldername ?? null,
                    //'visa_reference_no'     => $txn->Rrn ?? null,
                    'visa_reference_no'         => $txn->ReferenceNumber ?? 0,
                    'base_amount'           => $txn->OriginalTransactionAmount ?? 0,
                    'cardholder_fee'           => $txn->FeeAmount ?? 0,
                    'terminal_id'           =>  $txn->Rrn ?? null,
                    'base_currency'         => $base_currency->id ?? 0,
                    'approval_code'         => $txn->AuthorizationCode ?? null,
                    'card_number'           =>  $txn->CardTokenNumber ?? null,
                    'terminal_currency'     => $terminal_currency->id ?? 0,
                    'terminal_amount'     => $txn->SettlementTransactionAmount ?? 0,
                    'MCC'                   => $txn->Mcc ?? null,
                    'type_of_operation'     => $transaction_nature,
                    'card_id'               => $txn->CardId  ?? null,
                    'is_reversal'           => $is_reversal ,
                    'processing_amount'     => $txn->BillingAmount ?? 0,
                   
                    'card_token'            => $txn->CardTokenNumber ?? null,
                    'transaction_state'     => FingateHelper::mapSettlementStatus($txn->SettlementStatus ?? null),
                    'time'                  => Carbon::parse($txn->LocalTransactionDateTime)->toDateTimeString(),
                    'terminal_owner_title'           => 'NA',
                    'transaction_nature'    => $transaction_nature,
                    'result'                => FingateHelper::mapTransactionStatus($txn->TransactionStatus ?? null),
                    'cardholder_fee'        => $txn->FeeAmount ?? 0,
                    'terminal_id'           => $txn->Rrn ?? null,
                    'created_at'            => now(),
                    'updated_at'            => now(),
                ];
                $trx_hash = md5(implode('-',$tr));
                $tr['trx_hash'] = $trx_hash;
                $statement = CardStatement::updateOrCreate(
                    ['txn_id' => $txn->TransactionId],
                    $tr
                );
                //dd($statement);
                // if(!empty($statement))
                //     continue;
                //$this->info("Stetement ID: ". $statement->id . " => Card Detail ID: ". $card_detail->id );
                //dd($statement);
                CardStatementsDetail::updateOrCreate(
                    [
                        // Criteria to find the record — adjust as needed
                        'transaction_id' => $txn->TransactionId,
                    ],
                    [
                        'statement_id'   => $statement->id ?? null,
                        'card_id'        => $txn->CardId ?? null,
                        'json_data'      => json_encode($txn),
                    ]
                );
                $count++;
                // $this->info($txn->TransactionId . " Transactions imported successfully.");
                 $this->info("Transaction imported successfully. => ".$txn->Description." => Txn_id = ".$txn->TransactionId);
            }
             $this->info("Total ( " . $count . " ) Transactions imported successfully. ");
           
        } catch (\Throwable $th) {
            $this->info("Some Error Occured please check the logs.");
            $this->error("Error: " . $th->getMessage());
        }
    }
}
