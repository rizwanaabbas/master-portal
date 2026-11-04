<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AccountTransaction;

class StoreLinkTrIdWithCreditTrn extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'store:link-trn-id';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        $allTargetDebitTransactions = AccountTransaction::whereIn('transaction_nature', ['Internal', 'Buy', 'Sell'])
            ->where('transaction_type', 'Debit')
            ->get();

        foreach ($allTargetDebitTransactions as $trn) {
            if ($trn->link_tr_id) {
                // dd($trn);
                echo "\n" . $trn->linkedTrn->id . "\n";
                $linkedTrn = $trn->linkedTrn;

                $linkedTrn->link_tr_id = $trn->id;
                $linkedTrn->save();
            }
        }
    }
}
