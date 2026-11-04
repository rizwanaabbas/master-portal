<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AccountTransaction;
use App\Models\TRRequest;

class StoreTrnRequestForAdminTrn extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'store:trn-request';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command will look for transactions, created by Admin, in account_transactions table, which do not have any transaction request associated with and will create a transaction request for it.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $allTargetTransactions = AccountTransaction::where('tr_request_id', null)->get();

        $true_count = 0;
        $false_count = 0;
        $trn_ids = [];
        foreach ($allTargetTransactions as $trn) {

            switch ($trn->transaction_nature) {
                case "Cheque":
                case "TT":

                    // create transaction request
                    $trn_request = new TRRequest();

                    $trn_request->account_id = $trn->account_id;
                    $trn_request->link_tr_id = $trn->id;
                    $trn_request->currency_id = $trn->currency_id;
                    $trn_request->transaction_type = $trn->transaction_type;
                    $trn_request->transaction_nature = $trn->transaction_nature;
                    $trn_request->transaction_date = $trn->transaction_date;
                    $trn_request->amount = $trn->amount;
                    $trn_request->remarks = $trn->remarks;
                    $trn_request->status = $trn->status;
                    $trn_request->created_by = $trn->created_by;

                    $trn_request->save();

                    $trn->tr_request_id = $trn_request->id;
                    $trn->save();

                    $true_count++;
                    $trn_ids[] = $trn->id;
                    break;

                case "Internal":

                    if ($trn->transaction_type == "Debit") {
                        // create client transaction
                        $trn_request = new TRRequest();

                        $trn_request->account_id = $trn->account_id;
                        $trn_request->link_tr_id = $trn->id;
                        $trn_request->currency_id = $trn->currency_id;
                        $trn_request->transaction_type = $trn->transaction_type;
                        $trn_request->transaction_nature = $trn->transaction_nature;
                        $trn_request->transaction_date = $trn->transaction_date;
                        $trn_request->amount = $trn->amount;
                        $trn_request->ex_rate = $trn->ex_rate;
                        $trn_request->receiver_account_number = $trn->linkedTrn ? $trn->linkedTrn->account->account_number : null;
                        $trn_request->remarks = $trn->remarks;
                        $trn_request->status = $trn->status;
                        $trn_request->created_by = $trn->created_by;

                        $trn_request->save();

                        $trn->tr_request_id = $trn_request->id;
                        $trn->save();


                        $true_count++;
                        $trn_ids[] = $trn->id;
                    } else {
                        if ($trn->linkedTrn) {
                            $linked_trn = $trn->linkedTrn;

                            if ($linked_trn->tr_request_id) {
                                $trn->tr_request_id = $linked_trn->tr_request_id;
                                $trn->save();
                            }

                            $linked_trn->link_tr_id = $trn->id;

                            $linked_trn->save();

                            $true_count++;
                            $trn_ids[] = $trn->id;
                        }
                    }


                    break;

                default:

                    $false_count++;
                    echo "\nFalse Count: " . $false_count . " - None of the Above. Nature: " . $trn->transaction_nature . "\n";
                    break;
            }
        }

        echo "\n" . $true_count . " transactions request(s) has been associated.\n";
        dd($trn_ids);
    }
}
