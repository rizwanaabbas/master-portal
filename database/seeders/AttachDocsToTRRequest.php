<?php

namespace Database\Seeders;

use App\Models\AccountTransaction;
use App\Models\TRRequest;
use App\Models\TRRequestDoc;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class AttachDocsToTRRequest extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $accTrnsCounter = 0;
        $docsCounter = 0;
        $cryptoTrns = AccountTransaction::select('id', 'tr_request_id')
            ->whereNotNull('tr_request_id')
            ->where('is_crypto', true)
            ->where('status', 'Approved')
            ->pluck('tr_request_id', 'id')
            ->toArray();
        $cryptoTrRequests = TRRequest::select('id')
            ->whereNotNull('crypto_amount')
            ->where('status', 'Approved')
            ->pluck('id')->toArray();

        // dd(count($cryptoTrns));

        foreach ($cryptoTrns as $id => $trRequestId) {
            $cryptoTrnsDocs = TRRequestDoc::where('transaction_id', $id)->get();

            if ($cryptoTrnsDocs->count() > 0 && !in_array($id, $cryptoTrRequests)) {
                $accTrnsCounter++;

                foreach ($cryptoTrnsDocs as $doc) {
                    $docsCounter++;
                    Log::info("Acc Trn ID: " . $id . " | Request ID: " . $trRequestId . " | Doc ID: " . $doc->id . "Count: " . $cryptoTrnsDocs->count());

                    $doc->transaction_id = $trRequestId;
                    $doc->save();
                }
            }
        }

        Log::info('Total Account Transactions: ' . $accTrnsCounter . ' | Total Docs Processed: ' . $docsCounter);
        dd('Total Account Transactions: ' . $accTrnsCounter, 'Total Docs Processed: ' . $docsCounter);
    }
}
