<?php

namespace App\Http\Controllers;

use App\Helpers\Common;
use App\Helpers\EmailNotification;
use App\Helpers\IDORHelper;
use App\Models\Currency;
use App\Models\Country;
use App\Models\ClientBeneficiary;
use App\Models\OpenPaydLinkedClients;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Helpers\OpenpaydApis;
use App\Models\virtualBankBeneficiary;

class ClientBeneficiaryController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:super admin|finance manager', ['only' => 'deleteBeneficiary']);
    }

    public function myBeneficiaries($nid = "")
    {
        $beneficiaries = ClientBeneficiary::where("user_id", Auth::user()->id)->get();

        if ($nid) {
            Common::readNotification($nid);
        }

        return view("client-portal.transaction-request.beneficiary-list", compact("beneficiaries"));
    }

    public function viewMyBeneficiary($id)
    {
        $ben = ClientBeneficiary::find($id);

        try {
            $belongsToClient = IDORHelper::isEntityBelongsToUser(Auth::user(), $ben->user_id);

            if ($belongsToClient) {

                return view("client-portal.transaction-request.beneficiary-view", compact('ben'));
            }
        } catch (Exception $e) {
            Common::logError($e, __METHOD__, __LINE__);

            if (401 === $e->getCode()) {
                return back()->with('error', 'You are not authorized to access this page.');
            } else {
                return back()->with('error', 'Something went wrong, please try again later.');
            }
        }
    }

    public function add()
    {
        $currencies = Currency::where('is_active', 1)->where('is_crypto', 0)->get();
        $countries = Country::get();
        return view("client-portal.transaction-request.new-beneficiary", compact('currencies', 'countries'));
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
            'account_number' => 'required',
            // 'iban' => 'required', // |unique:client_beneficiaries,iban',
            'address' => 'required',
            'bank_name' => 'required',
            'bank_address' => 'required'
        ]);

        $client = Auth::user()->linkClients[0]->user_id;
        if ($client == null) {
            return redirect(url()->previous())->with("error", "You can't add beneficiary because you are not a client");
        }

        // add new beneficiary
        $ben = new ClientBeneficiary();
        $openpaydHelper = new OpenpaydApis();
        $ben->user_id = Auth::user()->id;
        $ben->name = $request->get("name");
        $ben->account_number = $request->get("account_number");
        $ben->iban = $request->get("iban");
        $ben->address = $request->get("address");
        $ben->bank_name = $request->get("bank_name");
        $ben->swift_code = $request->get("swift_code");
        $ben->account_currency = $request->get("currency_short_code");
        $ben->beneficiary_country = $request->get("beneficiary_country");
        $ben->bank_account_country = $request->get("bank_account_country");
        $ben->bank_address = $request->get("bank_address");
        //$ben->status = "Pending"; Changed this to Approved on FAIB request.
        $ben->status = "Approved";

        //die();
        $ben->created_by = Auth::user()->id;

        $ben->save();
        // $linkedben = $openpaydHelper->createBeneficiary(Auth::user()->linkClients[0], $ben);
        // if (!empty($linkedben->errorCode)) {
        //     $ben->delete();
        //     $errorMessage = isset($linkedben->errorCode) ? $linkedben->errorCode : 'Error occurred while creating beneficiary.';
        //     return redirect()->back()->with("error", $errorMessage);
        //     // return redirect("client/transaction-requests/beneficiaries/view/".$ben['id'])->with("error", "OpenPayd" . $linkedben->errorCode);
        // }

        // generate email notification to finance manager
        // EmailNotification::notifyOperationManagersForBeneficiary($ben); Removed the notification feature on FAIB request.

        return redirect("/client/transaction-requests/beneficiaries")->with("success", "Beneficiary details created successfully");
    }

    public function linkToVirtualBank($ben_id)
    {
        $openpaydHelper = new OpenpaydApis();
        $ben = ClientBeneficiary::where('id', $ben_id)->first();

        $bank_beneficiary_data = [
            "sort_code" => $ben->sort_code,
            // "beneficiary_type" => $beneficiary_type[$ben->beneficiary_type]
            "beneficiary_type" => $ben->beneficiary_type,
        ];

        if (empty($ben->beneficiary_country)) {
            return redirect()->back()->with("error", "Beneficiary details is invalid!");
        }

        // dd($ben);

        try {
            $linkedben = $openpaydHelper->createBeneficiary($ben, $bank_beneficiary_data);

            if (!empty($linkedben->errorCode)) {
                $message = isset($linkedben->message) ? $linkedben->message : $linkedben->errorCode;
                return redirect()->back()->with("error", "OpenPayd: " . $message);
            }

            if (!empty($linkedben)) {
                $virtual_bank_beneficiary =  virtualBankBeneficiary::where('bank_beneficiary_id', $linkedben->id)->first();
                $virtual_bank_beneficiary->parent_beneficiary_id = $ben->id;
                $virtual_bank_beneficiary->save();
            }

            return redirect()->back()->with("success", "Beneficiary linked to OpenPayd successfully");
        } catch (Exception $e) {
            Log::error('------------------------------------------------');
            Log::error($e->getMessage() . ' | Line: ' . $e->getLine());
            Log::error($e->getTraceAsString());
            return redirect(url()->previous())->with('error', 'Error creating New Beneficiary: ' . $e->getMessage());
        }
    }

    public function getAllBeneficiaries()
    {
        $beneficiaries = ClientBeneficiary::orderBy("created_at", "DESC")->get();

        return view("finance-management.beneficiary-list", compact("beneficiaries"));
    }

    public function viewBeneficiary($id, $nid = "")
    {
        $ben = ClientBeneficiary::find($id);
        $user =  $ben->user;

        $open_payd_client = null;
        if (!empty($user->client_id)) {
            $client = isset($user->linkClients[0]) ? $user->linkClients[0] : null;
            $open_payd_client =  OpenPaydLinkedClients::where('client_id', $client->client_id)->first();
        }
        if ($nid) {
            Common::readNotification($nid);
        }
        
        $countries = Country::get();
        $currencies = Currency::where("is_crypto", false)->select("id", "name", "short", 'virtual_iban_provider')->get();

        return view("finance-management.beneficiary-view", compact("ben", 'open_payd_client', 'countries', 'currencies'));
    }
    
    public function editBeneficiary($id, $nid = "")
    {
        $ben = ClientBeneficiary::find($id);
        $user =  $ben->user;

        $open_payd_client = null;
        if (!empty($user->client_id)) {
            $client = isset($user->linkClients[0]) ? $user->linkClients[0] : null;
            $open_payd_client =  OpenPaydLinkedClients::where('client_id', $client->client_id)->first();
        }
        if ($nid) {
            Common::readNotification($nid);
        }

        $countries = Country::get();
        $currencies = Currency::where("is_crypto", false)->select("id", "name", "short", 'virtual_iban_provider')->get();
        // dd($ben);
        return view("finance-management.beneficiary-edit", compact("ben", 'open_payd_client', 'countries', 'currencies'));
    }
    
    public function updateBeneficiary($id, Request $request)
    {
        // VALIDATION
        $request->validate([
            'name' => 'required|string|max:255',
            'account_number' => 'required',
            'account_currency' => 'required',
            'beneficiary_country' => 'required',
            'bank_account_country' => 'required',
        ]);
        
        try {

            $ben = ClientBeneficiary::find($id);

            if (!$ben) {
                abort(404, 'Beneficiary not found');
            }



            // =========================
            // UPDATE BENEFICIARY
            // =========================
            $ben->name = $request->get("name");
            $ben->account_number = $request->get("account_number");
            $ben->iban = $request->get("iban");
            $ben->address = $request->get("address");
            $ben->bank_name = $request->get("bank_name");
            $ben->swift_code = $request->get("swift_code");
            $ben->account_currency = $request->get("account_currency");
            $ben->beneficiary_country = $request->get("beneficiary_country");
            $ben->bank_account_country = $request->get("bank_account_country");
            $ben->bank_address = $request->get("bank_address");
            $ben->beneficiary_type = $request->get("beneficiary_type");
            $ben->bank_city = $request->get('bank_city');
            $ben->postal_code = $request->get('postal_code');
            
            $ben->beneficiary_state = $request->get('beneficiary_state');
            $ben->beneficiary_city = $request->get('beneficiary_city');

            $ben->status = "Approved";
            $ben->updated_by = Auth::id();
            $ben->is_sepa = true;

            // =========================
            // ROUTING / SORT CODE UPDATE
            // =========================
            $bankCodes = [];

            if ($request->filled('sort_code') && $ben->currency->short === "GBP") {
                $bankCodes[] = [
                    "routingCodeKey" => "SORT_CODE",
                    "routingCodeValue" => $request->sort_code
                ];
            }

            if ($request->filled('routing_number')) {
                $bankCodes = [
                    "routingCodeKey" => "ROUTING_NUMBER",
                    "routingCodeValue" => $request->routing_number
                ];
            }

            if ($ben->virtualBankBeneficiary) {
                $ben->virtualBankBeneficiary->bank_routing_codes = json_encode($bankCodes);
                $ben->virtualBankBeneficiary->save();
            }

            $ben->save();

            return redirect('finance-management/beneficiaries')->with('success', 'Beneficiary updated successfully');

        } catch (\Exception $e) {

            Log::error('Beneficiary update failed', [
                'beneficiary_id' => $id,
                'error_all' => $e,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'Something went wrong while updating beneficiary.');
        }
    }

    public function approveBeneficiary(Request $request)
    {
        $ben = ClientBeneficiary::find($request->get("id"));

        $ben->status = "Approved";
        $ben->approved_at = Carbon::now();
        $ben->approved_by = Auth::user()->id;
        $ben->save();

        // notify client for beneficiary approval
        EmailNotification::notifyBeneficiaryApproval($ben);

        return redirect(url()->previous())->with("success", "Beneficiary approved successfully");
    }

    public function rejectBeneficiary(Request $request)
    {
        $ben = ClientBeneficiary::find($request->get("id"));

        $ben->status = "Rejected";
        $ben->reject_reason = $request->get("reject_reason");
        $ben->rejected_at = Carbon::now();
        $ben->rejected_by = Auth::user()->id;

        $ben->save();

        // notify client for beneficiary Rejection
        EmailNotification::notifyBeneficiaryRejection($ben);

        return redirect(url()->previous())->with("success", "Beneficiary Rejected successfully");
    }

    public function deleteBeneficiary(Request $request)
    {
        $ben = ClientBeneficiary::find($request->get("id"));

        if ($ben) {

            // check if there are transactions requests against the beneficiary
            if ($ben->transactions_requests || $ben->transactions_requests->count() > 0) {
                // return redirect(url()->previous())->with("error", "Can't delete beneficiary, there are transactions against the beneficiary");
                $ben->is_deleted = 1;
                $ben->deleted_by = Auth::user()->id;
                $ben->deleted_at = Carbon::now();

                $ben->save();
            } else {
                $ben->delete();
            }

            return redirect(url()->previous())->with("success", "Beneficiary Deleted successfully");
        } else {
            return redirect(url()->previous())->with("error", "Beneficiary not found");
        }
    }
}
