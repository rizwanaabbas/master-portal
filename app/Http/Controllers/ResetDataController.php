<?php

namespace App\Http\Controllers;

use App\Models\AccountTransaction;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ResetDataController extends Controller
{
    public function reset()
    {
        return view("data-management.reset");
    }

    public function deleteTransaction()
    {
        if (!Auth::user()->hasRole("super admin")){
            abort(404);
        }

        $trns = AccountTransaction::all();

        foreach ($trns as $trn){

            if ($trn){

                switch ($trn->transaction_nature){

                    case 'Cheque':
                        $trn->cheque->delete();
                        break;
                }

                $trn->delete();
            }
        }

        return redirect("/data-management/reset")->with("success", "All transactions were deleted successfully.");

    }

    public function deleteClients()
    {
        if (!Auth::user()->hasRole("super admin")){
            abort(404);
        }

        $clients = Client::all();
        $cc = new ClientController();

        foreach ($clients as $client){

            if ($client->type == "individual"){
                $cc->deleteIndividualClient($client);
            }else{
                $cc->deleteCompanyClient($client);
            }
        }

        return redirect("/data-management/reset")->with("success", "All clients were deleted successfully.");

    }
}
