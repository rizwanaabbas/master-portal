<?php

namespace App\Http\Controllers;

use App\Helpers\ClientHelper;
use App\Models\ClientFileConfig;
use App\Models\ClientKYC;
use App\Models\CompanyType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DraftClientDashboardController extends Controller
{
    public function index()
    {
        $client = ClientHelper::getClientByUserId(Auth::user()->id);
       
        if ($client->type == "individual") {
            $configFiles = ClientFileConfig::Individual()->where("signed_pdf", 1)->get();
        } elseif ($client->type == "company") {
            $configFiles = ClientFileConfig::Company()->where("signed_pdf", 1)->get();
        }

        $clientKYC = $client->is_kyc_requested;

        return view("draft-clients.dashboard.dashboard", compact('client', 'configFiles', 'clientKYC'));
    }
}
