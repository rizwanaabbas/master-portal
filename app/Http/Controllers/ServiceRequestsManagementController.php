<?php

namespace App\Http\Controllers;

use App\Helpers\Common;
use App\Helpers\EmailNotification;
use App\Helpers\IDORHelper;
use App\Helpers\OpenpaydApis;
use App\Helpers\FingateHelper;
use App\Models\Account;
use App\Models\Client;
use App\Models\Currency;
use App\Models\CardProvider;
use App\Models\MobileRequest;
use App\Models\Module;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ServiceRequestsManagementController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:mobile requests list', ['only' => [
            'index',
            'listPendingRequests',
            'listRejectedRequests',
            'viewRequest',
            'approveRequest',
            'rejectRequest'
        ]]);
    }

    public function index($nid = null)
    {
        if (!in_array('MobileRequests', Common::getEnabledModules())) {
            return redirect('/')->with('error', 'Operation not allowed.');
        }

        if ($nid) {
            Common::readNotification($nid);
        }

        $requests = MobileRequest::where('status', 'Approved')->orderBy('approved_at', 'DESC')->get();

        return view('mobile-requests.list', compact('requests'));
    }

    public function listPendingRequests($nid = null)
    {
        if (!in_array('MobileRequests', Common::getEnabledModules())) {
            return redirect('/')->with('error', 'Operation not allowed.');
        }

        if ($nid) {
            Common::readNotification($nid);
        }

        $requests = MobileRequest::where('status', 'Pending')->where('is_otp_verified', true)->orderBy('created_at', 'DESC')->get();

        return view('mobile-requests.list-pending', compact('requests'));
    }

    public function listOtpPendingRequests($nid = null)
    {
        if (!in_array('MobileRequests', Common::getEnabledModules())) {
            return redirect('/')->with('error', 'Operation not allowed.');
        }

        if ($nid) {
            Common::readNotification($nid);
        }

        $requests = MobileRequest::where('status', 'Pending')->where('is_otp_verified', false)->orderBy('created_at', 'DESC')->get();

        return view('mobile-requests.list-pending', compact('requests'));
    }

    public function listRejectedRequests($nid = null)
    {
        if (!in_array('MobileRequests', Common::getEnabledModules())) {
            return redirect('/')->with('error', 'Operation not allowed.');
        }

        if ($nid) {
            Common::readNotification($nid);
        }

        $requests = MobileRequest::where('status', 'Rejected')->orderBy('updated_at', 'DESC')->get();

        return view('mobile-requests.list-rejected', compact('requests'));
    }

    public function viewRequest($id, $nid = null)
    {
        if (!in_array('MobileRequests', Common::getEnabledModules())) {
            return redirect('/')->with('error', 'Operation not allowed.');
        }

        if ($nid) {
            Common::readNotification($nid);
        }

        $request = MobileRequest::find($id);
        $providers = CardProvider::all()->pluck('name', 'handle')->toArray();
        $openPaydHelper = new OpenpaydApis();
        $openpayd_allowed_currencies = $openPaydHelper->getAllowedCurrencies(true);

        $showModuleProviders = [];

        // if ($request->type == 'APPLY_CARD' && $request->sub_type == 'virtual') {

        //     $showModuleProviders = [
        //         'match_move' => true,
        //         'e_money'   => true,
        //     ];
        // }

        if ($request->type == 'APPLY_CARD') {
            foreach ($providers as $handle => $name) {
                $module = Module::with('configuration')
                    ->where('name', $name)
                    ->where('is_enabled', 1)
                    ->first();

                if ($module) {
                    $showProvider = true;

                    if ($module->configuration && $request->sub_type == 'physical') {
                        $configs = @unserialize($module->configuration->configurations) ?: [];
                        $hasPhysical = $configs['has_physical_card']['value'] ?? "0";

                        if ($hasPhysical != "1") {
                            $showProvider = false;
                        }
                    }

                    $showModuleProviders[$handle] = $showProvider;
                }
            }
        }


        // $match_move_enabled = 1;
        // if (!in_array('MatchMove', Common::getEnabledModules())) {
        //     $match_move_enabled = 0;
        // }

        return view('mobile-requests.view', compact('request', 'openpayd_allowed_currencies', 'providers', 'showModuleProviders'));
        // return view('mobile-requests.view', compact('request','match_move_enabled'));
    }

    public function approveRequest(Request $request)
    {
        if (!in_array('MobileRequests', Common::getEnabledModules())) {
            return redirect('/')->with('error', 'Operation not allowed.');
        }

        try {
            $req = MobileRequest::find($request->get('id'));

            if ($req->type == 'APPLY_CARD' && $req->sub_type == 'physical') {
                session([
                    'service_request_id' => $req->id,
                    'client_id'          => $req->client_id,
                    'is_physical_card'   => true
                ]);
                return redirect('/card-management/cards/unassigned/list')
                    ->with('success', 'Select a physical card to assign.');
            }

            $account = null;

            if ($req->status == "Rejected" || !$req->is_otp_verified) {
                return redirect(url()->previous())->with("error", "Something went wrong. Please try again later.");
            }

            if ($req->client->status == "Rejected") {
                return redirect(url()->previous())->with("error", "You cannot approve this request as the client is rejected.");
            }

            $client = $req->client;

            if ($req->iban_currency_id) {
                if ($client->type == 'individual' && (!$client->individual->city || !$client->individual->residence_country_id)) {
                    return redirect()->back()->with('error', 'Please fill in both the City and Country fields for the Individual.');
                } elseif ($client->type == 'company' && (!$client->company->company_city || !$client->company->company_country)) {
                    return redirect()->back()->with('error', 'Please fill in both the City and Country fields for the Company.');
                }
            }

            $message = '';
            switch ($req->type) {
                case 'APPLY_CARD':
                    if ("vista_money" !== $request->input('provider')) {

                        $card =  $this->createCard($req, $client->id, $request->input('provider'));
                        //dd($card);
                        if (empty($card)) {
                            $message = 'Something went wrong while creating the card. Please try again later. ';
                        } else if (is_object($card) && !empty($card) && $card->ErrorCode == 'KycFailed') {
                            $message =  $card->ErrorDescription;
                        } else if (is_object($card) && !empty($card)) {
                            //message =  $card->ErrorDescription;
                            Log::error('CASE APPLY_CARD: ' . json_encode($card));
                        } else if (((isset($card['success']) && $card['success'] == false))) {
                            Log::error('CASE APPLY_CARD RESPONSE: ' . json_encode($card));
                            $message = $card['message'];
                        }
                        if (!empty($message)) {
                            return redirect(url()->previous())->with("error", "Error: " . $message);
                        }
                        //dd($card);
                        // TODO: Do nothing for now, we will automate it in future.
                    }
                    break;
                case 'APPLY_ACCOUNT':
                case 'APPLY_CRYPTO':
                    $account = $this->createNewClientAccount($req);

                    if ($req->type === 'APPLY_ACCOUNT' && $request->get('iban_currency_id')) {
                        $openpayd = new OpenpaydApis();
                        $linked_client = $openpayd->storeOpenPaydClient($client);
                        if (!empty($linked_client->account_holder_id)) {
                            $openpayd->storeOpenPaydAccount($account, $linked_client);
                        }
                    }
                    break;
                case 'CLOSE_ACCOUNT':
                    self::closeClient($client);
                    break;
                default:
                    break;
            }

            $req->status = 'Approved';
            //$req->card_type = $request->input('card_type');
            $req->approved_by = Auth::user()->id;
            $req->approved_at = Carbon::now();

            $req->save();
            //temporary block notifications
            // if($req->type != 'APPLY_CARD' ) {
            //     if ($req->type != 'BALANCE_CERTIFICATE') {
            //         EmailNotification::notifyRequestApproval($req, $account);
            //     } else {
            //         EmailNotification::sendBalanceCertificatePDF($req);
            //     }
            if (!empty($req->client->users) && isset($req->client->users[0])) {
                $user = $req->client->users[0]->user;

                if ($user->hasRole('API')) {
                    Common::sendWebhookNotification($req, $user, 'service', 'Your service request has been Approved.');
                } else {
                    if ($req->type != 'BALANCE_CERTIFICATE') {
                        EmailNotification::notifyRequestApproval($req, $account);
                    } else {
                        EmailNotification::sendBalanceCertificatePDF($req);
                    }
                }
            }
            return redirect("/mobile-client-requests/approved-list")->with('success', "Request approved successfully.");
            // }
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getmessage());
        }
    }

    public function createCard($req, $client_id, $provider)
    {
        if ($provider == 'match_move' || $provider == 'e_money') {

            if ($provider == 'match_move') {
                if (!in_array('MatchMove', Common::getEnabledModules())) {
                    //return redirect('/')->with('error', 'Operation not allowed.');
                    return array(
                        'success' => false,
                        'message' => 'MatchMove Module is disabled, card can not be approved.'
                    );
                }
            }

            // dd($card);
            if ($provider == 'e_money') {
                if (!in_array('E-Money', Common::getEnabledModules())) {
                    //return redirect('/')->with('error', 'Operation not allowed.');
                    return array(
                        'success' => false,
                        'message' => 'E-Money Module is disabled, card can not be approved.'
                    );
                }
            }
            //echo $provider; die();

            $fingateHelper = new FingateHelper($provider);
            $card = $fingateHelper->createOrAssignCard($req, $client_id, $provider);

            if (!empty($card) &&  property_exists($card, 'IsSucceeded')) {
                //return json_decode(json_encode($card),true);
                if (!$card->IsSucceeded) {
                    return array('success' => false, 'message' => $card->ErrorDescription);
                }
            }

            if (!empty($card) && method_exists($card, 'getContent')) {
                $data = json_decode($card->getContent(), true);
                return $data;
            }
            return $card;
        }
    }

    public function rejectRequest(Request $request)
    {
        if (!in_array('MobileRequests', Common::getEnabledModules())) {
            return redirect('/')->with('error', 'Operation not allowed.');
        }

        $req = MobileRequest::find($request->get('id'));

        if ($req->status == "Approved") {
            return redirect(url()->previous())->with("error", "Something went wrong. Please try again later.");
        }

        $req->status = 'Rejected';
        $req->rejected_by = Auth::user()->id;
        $req->rejected_at = Carbon::now();
        $req->reject_reason = $request->get('reject_reason');

        $req->save();

        $user = $req->client->users[0]->user;

        if ($user->hasRole('API')) {
            Common::sendWebhookNotification($req, $user, 'service', 'Your service request has been Rejected. | Reason: ' . $request->get('reject_reason'));
        } else {
            EmailNotification::notifyRequestRejection($req);
        }

        return redirect("/mobile-client-requests/rejected-list")->with('success', "Request rejected successfully.");
    }

    private function createNewClientAccount($request)
    {
        $exists = $request->client->clientAccounts->where('account_currency', $request->currency_id)->first();

        if (!$exists) {

            $account = new Account();
            $client = $request->client;

            if ($client->type == "individual") {
                $account->individual_id = $client->individual->id;
            } else {
                $account->company_id = $client->company->id;
            }

            $account->client_id = $client->id;
            $account->type = $client->type;
            $account->title = $client->name . " (" . Common::getCurrencyShort($request->currency_id) . ")";
            $account->account_number = Common::getNewClientAccountNumber($client->type, ($request->type == 'APPLY_CRYPTO' ? true : false));
            $account->account_currency = $request->currency_id;
            $account->is_active = true;
            $account->is_crypto = Common::isCryptoCurrency($request->currency_id);
            $account->created_by = Auth::user()->id;

            $account->save();

            return $account;
        } else {
            throw new Exception("The account already exists.");
        }
    }

    private function closeClient($client)
    {
        Common::closeRestoreClient($client, "Closed upon client's request.");

        if ($client->directors->count() > 0) {
            foreach ($client->directors as $director) {
                Common::closeRestoreDirector($director->email);
            }
        }

        Common::closeRestoreLinkedUsers($client,  "Closed");
    }

    public function listServiceRequestsForClient($nid = "")
    {
        if ($nid) {
            Common::readNotification($nid);
        }

        $client_id = Common::getClientIdsByUserId(Auth::user()->id);
        $serviceRequests = MobileRequest::whereIn("client_id", $client_id)
            ->orderBy("created_at", "DESC")
            ->get();

        return view('client-portal.service-request.list', compact('serviceRequests'));
    }

    public function newServiceRequest($sub_account_id = null)
    {
        $user = Auth::user();
        $client_id = Common::getClientIdsByUserId($user->id)[0];
        $client = Client::find($client_id);

        if ($sub_account_id) {
            $client_id = $sub_account_id;
        }

        $currency_exists = Account::where('client_id', $client_id)->get();
        $mobile_request_exists = MobileRequest::where('user_id', Auth::user()->id)->whereNotNull('currency_id')->get();
        $accountExists = MobileRequest::where('status', '<>', 'Approved')->pluck('account_id')->toArray();

        $account_currency = Currency::where("is_crypto", false)
            ->whereNotIn('id', $currency_exists->pluck('account_currency'))
            ->whereNotIn('id', $mobile_request_exists->pluck('currency_id'))
            ->get();

        $crypto_currency = Currency::where('is_crypto', true)
            ->whereNotIn('id', $currency_exists->pluck('account_currency'))
            ->whereNotIn('id', $mobile_request_exists->pluck('currency_id'))
            ->get();

        $subAccounts = $client->children ? $client->children->where('status', 'Approved') : null;
        $linkedClients = $user->linkedClients->where('status', 'Approved')->whereNotIn('id', $subAccounts->pluck('id')->toArray());
        $subAccounts = null;

        return view('client-portal.service-request.new', compact('account_currency', 'crypto_currency', 'client', 'subAccounts', 'linkedClients', 'client_id', 'accountExists'));
    }

    public function storeServiceRequest(Request $request)
    {
        $this->validate($request, [
            'client_id' => 'required',
            'service_type' => 'required',
        ]);

        try {
            $belongsToClient = IDORHelper::isLinkedOrSubAccount(Auth::user(), $request->get('client_id'));

            if ($belongsToClient) {
                $user_id = Auth::user()->id;

                if ($request->get('service_type') == 'CLOSE_ACCOUNT') {
                    $isExist = MobileRequest::where('user_id', $user_id)
                        ->where('type', 'CLOSE_ACCOUNT')
                        ->first();

                    if ($isExist) {
                        return redirect()->back()->with('error', 'Your request has already been submitted. Please wait for the operations manager to approve it');
                    }
                } else if ('APPLY_ACCOUNT' === $request->get('service_type') || 'APPLY_CRYPTO' === $request->get('service_type')) {

                    if ($request->get('currency_id')) {
                        $alreadyExists = MobileRequest::where('user_id', $user_id)
                            ->where('client_id', $request->get('client_id'))
                            ->where('currency_id', $request->get('currency_id'))
                            ->first();

                        if ($alreadyExists) {
                            throw new Exception('Service with currency_id' . $request->get('currency_id') . ' already exists');
                        }
                    } else {
                        throw new Exception('currency_id not provided intentionally.');
                    }
                }

                $clientId = Common::getClientIdsByUserId($user_id);
                if (empty($clientId)) {
                    return redirect()->back()->with('error', 'You are not linked to any Client. Please contact the operations manager');
                }

                $service = new MobileRequest();
                $service->user_id = $user_id;
                $service->client_id = $request->get('client_id');
                $service->currency_id = $request->get('currency_id');
                $service->account_id = $request->get('account_id');
                $service->type = $request->get('service_type');
                $service->status = 'Pending';
                $service->platform = 'Web';
                $service->is_otp_verified = false;

                $service->save();

                EmailNotification::RequestOTP($service, 'SERVICE_REQUEST');

                return redirect("client/services/requests/otp/" . $service->id);
            }
        } catch (Exception $e) {
            Common::logError($e, __METHOD__, __LINE__);

            if (401 === $e->getCode()) {
                return back()->with('error', 'You are not authorized to create this request.');
            } else {
                return back()->with('error', 'Something went wrong, please try again later.');
            }
        }
    }

    public function viewServiceRequest($id, $nid = null)
    {
        $service = MobileRequest::find($id);

        try {
            $belongsToClient = IDORHelper::isLinkedOrSubAccount(Auth::user(), $service->client_id);

            if ($belongsToClient) {

                if ($nid) {
                    Common::readNotification($nid);
                }

                return view('client-portal.service-request.view', compact('service'));
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

    public function deleteServiceRequest(Request $request)
    {
        $request = MobileRequest::find($request->get('request_id'));

        try {
            if ($request->status == 'Rejected') {
                $request->delete();

                return back()->with('success', 'Service Request deleted successfully!');
            } else {
                return back()->with('error', 'Sorry, this action cannot be performed.');
            }
        } catch (Exception $e) {
            return back()->with('error', 'Something went wrong, please try again later.');
        }
    }
}
