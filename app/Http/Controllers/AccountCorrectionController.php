<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AccountCorrection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccountCorrectionController extends Controller
{
    public function index()
    {
        $duplicates = DB::table('accounts')
            ->select('id', 'title', 'account_number')
            ->whereIn('account_number', function ($q){
                $q->select('account_number')
                    ->from('accounts')
                    ->groupBy('account_number')
                    ->havingRaw('COUNT(*) > 1');
            })->orderBy("account_number")->get();

        $changes = AccountCorrection::all();

        return view("account-correction.new", compact('duplicates', 'changes'));
    }

    public function store(Request $request)
    {
        return redirect()->back()->with("error", "Operation Disabled.");

        $updated = [];
        foreach ($request->get("ids") as $id){
            $account = Account::find ($id);
            $new_number = $request->get("number_".$id);
            // check existence
            $isExists = Account::where("account_number", $new_number)->first();

            if (!$isExists){

                // log to database
                $log = new AccountCorrection();

                $log->account_id = $account->id;
                $log->old_number = $account->account_number;
                $log->new_number = $new_number;
                $log->save();

                $account->account_number = $request->get("number_".$id);
                $account->save();
                $updated[] = $account->account_number;
            }else{
                return redirect()->back()->with("error", "Already exists");
            }
        }

        return redirect()->back()->with("success", "Updated ".implode (", ", $updated));
    }
}
