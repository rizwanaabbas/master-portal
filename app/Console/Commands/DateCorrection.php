<?php

namespace App\Console\Commands;

use App\Models\AccountTransaction;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;

class DateCorrection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'date:correct';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command will correct all the debit transaction date time to be ahead of credit transactions, for now both dates are falling on the same date and time (credit & debit)';

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
        Auth::loginUsingId(14, true);
        $transactions = AccountTransaction::select("account_id", "transaction_date")->groupBy("account_id", "transaction_date")->get();

        foreach ($transactions as $tr){

            // select the debit transaction on the date
            $trs = AccountTransaction::where("account_id", $tr->account_id)
                ->where("transaction_date", $tr->transaction_date)
                ->select("id", "account_id", "transaction_date", "transaction_type","amount")
                ->orderBy("transaction_type")
                ->get();

            if ($trs->count() > 0){
                $prev_dt = '';
                foreach ($trs as $trn){
                    if ($prev_dt != ""){
                        $trn->transaction_date = Carbon::parse($prev_dt)->addSeconds(5);
                        $prev_dt = $trn->transaction_date;
                    }else{
                        $trn->transaction_date = Carbon::parse($trn->transaction_date)->addSeconds(5);
                        $prev_dt = $trn->transaction_date;
                    }

                    $trn->save();
                    echo "\n\r updated account_id: ".$trn->account_id." date: ".$trn->transaction_date." type: ".$trn->transaction_type;
                }
            }else{
                echo "\n\r we didn't found any transactions with the same date and time with the same account id.";
            }
        }
        return 0;
    }
}
