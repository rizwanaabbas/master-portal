<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Hash;
use App\Models\ClientDirector;
use App\Models\Client;
use App\Models\KycRequest;
use Illuminate\Http\Request;
use App\Helpers\Common;
use App\Helpers\EmailNotification;
use App\Models\User;

class ClientDirectorController extends Controller
{

    public function __construct(Redirector $redirect)
    {
        // if (!in_array('ClientDirector', Common::getEnabledModules())) {
        //     abort('403');
        // }
    }
    public function requestKYC(Request $request)
    {
        // $request->validate([
        //     'request_passport' => [
        //         function ($attribute, $value, $fail) use ($request) {
        //             if (empty($value) && empty($request->get('request_selfie'))) {
        //                 $fail('At least one of the fields request_passport or request_selfie is required.');
        //             }
        //         },
        //     ],
        //     'request_selfie' => [
        //         function ($attribute, $value, $fail) use ($request) {
        //             if (empty($value) && empty($request->get('request_passport'))) {
        //                 $fail('At least one of the fields request_passport or request_selfie is required.');
        //             }
        //         },
        //     ],
        // ]);

        try {

            $client_id = $request->get("client_id");
            $client = Client::find($request->get("client_id"));
            $director_id = $request->get("director_id");
            /*  $client->status = "Liveness Pending"; */
            // $user = ClientHelper::getUserByClientId($client->id);
            $user = User::where("email", $client->company->contact->email_address)->first();
            $request_passport = $request->get("request_passport");
            $request_selfie = $request->get("request_selfie");
            $request_signature = $request->get("request_signature") ?? 0;

            if ($request_passport == 0 && $request_selfie == 0) {
                echo json_encode(array('status' => 'error', 'type' => 'internal', 'message' => 'At least one of the fields Request passport or Request selfie is required.'));
                die();
            };
            if (empty($client_id) || empty($director_id) && $director_id != 0 || empty($request)) {
                echo json_encode(array('status' => 'error', 'type' => 'internal', 'message' => 'Invalid request.'));
                die();
            };

            $director = ClientDirector::find($director_id);
            /*  $client->status = "Liveness Pending"; */
            if ($client) {
                $director_id = '';
                if (!empty($director)) {
                    $director->kyc_status = 1;
                    $director->save();
                    $director_id =  $director->id;
                } else {
                    $client->is_kyc_requested = 1;
                    $client->save();
                }

                $hash = str_replace('/', '', Hash::make($client->id . '-' . $director_id . '-' . time()));
                $kyc_req  =  new KycRequest();
                $kyc_req->created_by = Auth::user()->id;
                $kyc_req->expiry = date("Y-m-d H:i:s", strtotime("+1 week"));
                $kyc_req->hash = $hash;
                $kyc_req->client_id =  $client->id;
                if (!empty($director_id))
                    $kyc_req->director_id =  $director_id;

                $kyc_req->request_passport =  $request_passport;
                $kyc_req->request_selfie =  $request_selfie;

                $kyc_req->request_signature =  $request_signature;

                $kyc_req->save();
                $request = KycRequest::where("hash", $hash)->first();
                EmailNotification::notifyClientKYCRequest($client, $hash);

                if ($user && !$user->hasRole("API") && empty($director_id)) {
                    $user->removeRole("client");
                    $user->assignRole("draft client");
                }

                //return redirect(url()->previous())->with('success', 'Client KYC has been requested successfully!')->with('formnumber', 3);
                echo json_encode(array('status' => 'success', 'type' => 'internal', 'message' => 'Kyc request sent successfully.'));
                die();
            }
        } catch (\Exception $e) {
            Common::logError($e, __METHOD__, __LINE__, $request);

            //return redirect(url()->previous())->with('error', $e);
            echo json_encode(array('status' => 'error', 'type' => 'internal', 'message' => $e));
            die();
        }
    }
}
