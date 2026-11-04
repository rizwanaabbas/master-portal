<?php

namespace App\Http\Controllers;

use App\Imports\ImportClient;
use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\Client;
use App\Models\Currency;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ImportTransactionController extends Controller
{
    public function add()
    {
        return view("data-management.transaction-import");
    }

    public function importTransactions(Request $request)
    {
        $file = $request->file('file');
        $excelData = (new ImportClient)->toArray($file);

        $clients = $excelData[0];
        $admin = User::role("super admin")->first();

        // remove the header
        unset($clients[0]);

        foreach ($clients as $row) {
            try {
                if ($row[0] != ""){

                    // create tt transaction
                    $client = Client::where("name", $row[1])->first();

                    if ($client){
                        $currency = Currency::where("short", $row[2])->first();
                        $account = Account::where("client_id", $client->id)->where("account_currency", $currency->id)->first();
                        $createdby = User::where("code", $row[6])->first();
                        $updatedby = User::where("code", $row[8])->first();

                        // special formating for date fields
                        $tr_date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row[3])->format("Y-m-d");
                        $created_at = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row[7])->format("Y-m-d H:i:s");
                        $updated_at = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row[9])->format("Y-m-d H:i:s");

                        // create client transaction
                        if ($client && $currency && $account && $createdby && $updatedby){
                            $trn = new AccountTransaction();

                            $trn->account_id = $account->id;
                            $trn->currency_id = $currency->id;
                            $trn->transaction_type = ($row[4] > 0) ? "Credit" : "Debit";
                            $trn->transaction_nature = "TT";
                            $trn->transaction_date = $tr_date;
                            $trn->amount = ($row[4] > 0) ? $row[4] : $row[5];
                            $trn->remarks = trim($row[10]);
                            $trn->status = "Approved";
                            $trn->created_by = $createdby->id;
                            $trn->created_at = $created_at;
                            $trn->updated_by = $updatedby->id;
                            $trn->updated_at = $updated_at;

                            $trn->save();
                        }
                    }

                }
            }catch(Exception $e){
                dd ($e, $row);
            }

        }

        return redirect("/data-management/import/transactions/new")->with("success", "Transactions Data Imported Successfully.");


    }
}
