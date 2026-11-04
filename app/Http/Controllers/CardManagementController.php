<?php

namespace App\Http\Controllers;

use App\Helpers\CardHelper;
use Artisan;
use App\Helpers\ChangeLogger;
use Illuminate\Http\Request;
use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\AssignedCard;
use App\Models\CardRequest;
use App\Models\CardDetail;
use App\Models\Currency;
use App\Models\FingateClient;
use App\Models\TRRequest;
use App\Models\UserCardLink;
use App\Models\UnassignedCard;
use App\Helpers\ClientHelper;
use App\Helpers\FingateHelper;
use App\Helpers\Common;
use App\Helpers\EmailNotification;
use App\Helpers\NeroDataHelper;
use App\Helpers\VistaMoneyMerchantApis;
use App\Models\Bank;
use App\Models\CardStatement;
use App\Models\Client;
use App\Models\MobileRequest;
use App\Models\Module;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Schema;
use Yajra\DataTables\DataTables;
use Barryvdh\DomPDF\Facade\Pdf;

class CardManagementController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:manage cards', ['only' => [
            'getTotalCards',
            'listCards',
            'blockCard',
            'unblockCard',
            'viewCard',
            'listUnassignedCards',
            'listLinks',
            'newLink',
            'storeLink',
            'editLink',
            'updateLink',
            'topupRequestsList',
            'editTopupTransaction',
            'updateTopup',
            'topupPendingRequestsList',
            'backOfficePendingTopupRequests',
            'viewTopupPendingRequest',
            'approveTopupTransaction',
            'rejectTopupTransaction',
            'unblockRequestsList',
            'viewUnblockRequest',
            'approveUnblockRequest',
            'rejectUnblockRequest'
        ]]);
        // $this->middleware('permission:crypto pending transactions list', ['only' => 'getTransactionRequests']);
        // // $this->middleware('permission:add crypto transaction', ['only' => ['addCheque', 'addTT', 'addInternal', 'storeCheque', 'storeTT', 'storeInternal']]);
        // $this->middleware('permission:edit crypto transaction', ['only' => ['editCryptoTransaction', 'updateCryptoTransaction']]);
        // $this->middleware('permission:delete crypto transaction', ['only' => 'deleteCryptoPendingTransaction']);
        // $this->middleware('permission:view crypto transaction', ['only' => ['viewCryptoTransactionRequest', 'printTransactionRequest']]);
        // $this->middleware('permission:approve crypto transaction', ['only' => ['approveCryptoTransactionRequest', 'approveWithdrawCryptoTransactionRequest', 'rejectCryptoTransaction']]);
    }

    public function getTotalCards(Request $request)
    {
        if ($request->ajax()) {
            try {
                // Artisan::call('get:assigned-cards');

                $cards = AssignedCard::whereIn('status', ['Active', 'Inactive', 'Locked'])->count();
                return $cards;
            } catch (Exception $e) {
                return 0;
            }
        }
    }

    public function listCards()
    {
        $assignedCards = AssignedCard::all();
        $modules = Module::whereIn('name', ['MatchMove', 'E-Money'])
            ->pluck('is_enabled', 'name');
        $match_move_enabled = $modules->get('MatchMove', 0);
        $emoney_enabled     = $modules->get('E-Money', 0);

        return view('card-management.cards.assigned-list', compact('assignedCards', 'match_move_enabled', 'emoney_enabled'));
    }

    public function  verifyFingateDocuments($detail_id, $customer_id, $client_id)
    {
        if (!empty($customer_id) && !empty($client_id)) {
            $finGateHelper = new FingateHelper();
            $result = $finGateHelper->reverifyDocuments($customer_id, $client_id);
            $card_detail = CardDetail::where('id', $detail_id)->first();
            $result_json =  json_encode($result);
            $card_detail->kyc_remark =   $result_json;
            $card_detail->save();
            return response()->json(
                $result,
                200
            );
            // dd($result);
        }
    }

    public function verifyFingateDocumentsStatus($detail_id, $client_id, $response = true)
    {

        if (!empty($detail_id)) {
            $finGateHelper = new FingateHelper();
            $card_detail = CardDetail::where('id', $detail_id)->first();
            //dd($card_detail);
            $client = Client::find($client_id);
            if (!empty($client)) {
                $customer_id = $card_detail->customer_id;
                $client_type = $client->type;
                $result = $finGateHelper->verifyKYCDocuments($customer_id, $client_type);

                if (is_object($result) || is_array($result)) {
                    $result_json = json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                } else {
                    $result_json = $result;
                }

                $card_detail->kyc_remark =   $result_json;

                $card_detail->save();
                if ($response) {
                    return response()->json(
                        json_decode($result_json),
                        200
                    );
                }
            }
        }
    }

    public function getBalance(Request $request, $id = null)
    {
        $balance = 0;
        if ($request->ajax()) {

            try {

                $assignedCard = AssignedCard::find($id);
                $card_id = '';
                if (!empty($assignedCard->link->detail_id)) {
                    $detail_id =  $assignedCard->link->detail_id;
                    $card_detail = CardDetail::where('id', $detail_id)->first();
                    $card_id = $card_detail->card_id;
                }

                if (!empty($card_id) && $card_detail->provider_type != 'vista_money') {
                    $finGateHelper = new FingateHelper();
                    $details = $finGateHelper->findCardAPI($card_id);

                    if (!empty($details) && !empty($details->CardBalance)) {
                        return $details->CardBalance;
                    }
                } else {

                    $vistaMoneyApis = new VistaMoneyMerchantApis(false, null, $assignedCard->mpin);

                    $balance = $vistaMoneyApis->getCardBalance();
                }

                return $balance;
            } catch (Exception $e) {
                // dd("exception: " . $e->getLine() . " " . $e->getMessage());
                return 0;
            }
        }
    }

    public function blockCard(Request $request)
    {
        $this->validate($request, [
            'updatable' => 'required',
            'request_reason' => 'required'
        ]);

        try {
            $clientId = null;
            $client = null;
            $linkedCardId = null;
            $assignedCard = AssignedCard::find($request->get('id'));
            $linkedCard = $assignedCard->link;
            // dd($assignedCard);
            if ($linkedCard) {
                $linkedCardId = $linkedCard->id;
                $client = $linkedCard->owner;
                $clientId = $client->id;
            }

            // if ($request->get('request_id')) {
            $cardRequest = CardRequest::where('mpin', $assignedCard->mpin)->where('updatable', 'Block')->first();

            if ($cardRequest) {
                $cardRequest->request_reason = $request->get('request_reason');
                $cardRequest->status = 'Auto';
                // }
            } else {
                // $client = CardHelper::getLinkedClientByUser(Auth::user());
                // $linkedUser = UserCardLink::where('client_id', $client->id)->first();

                // $alreadyExists = CardRequest::where('username', $card->username)->first();

                // if ($alreadyExists) {
                //     return redirect('/client/card-management/pending-requests/list')->with('error', 'You have already requested for Card Unblock, please wait for the Unblock process.');
                // }

                $cardRequest = new CardRequest();

                $cardRequest->client_id = $clientId;
                $cardRequest->mpin = $assignedCard->mpin;
                $cardRequest->type = 'Card Block Request';
                $cardRequest->updatable = $request->get('updatable');
                $cardRequest->request_reason = $request->get('request_reason');
                $cardRequest->created_by = Auth::user()->id;
                $cardRequest->status = 'Auto';
            }

            $provider = 'visa_money';
            if (!empty($assignedCard->provider))
                $provider = $assignedCard->provider;

            if ($provider == 'visa_money') {
                $vistaMoneyApis = new VistaMoneyMerchantApis(true, null, null, $assignedCard);
                $response = $vistaMoneyApis->blockCard();

                if ($client) {
                    $vistaMoneyApis = new VistaMoneyMerchantApis();
                    $response = $vistaMoneyApis->getCardDetails($assignedCard->mpin);

                    self::updateCardDetails($response, $assignedCard->link->cardDetails);

                    //notify client
                    EmailNotification::notifyClientForCardBlock($client, $linkedCardId);
                }
            } else {
                $fingateHelper = new FingateHelper();
                $block = '';
                if (!empty($linkedCard->cardDetails->card_id)) {
                    $block = $fingateHelper->blockCard($linkedCard->cardDetails->card_id);
                }
                //dd($block);
                if (empty($block) || $block->ErrorCode !== 'Success') {
                    $error = '';
                    if (!empty($block->ErrorDescription))
                        $error = $block->ErrorDescription;
                    return redirect(url()->previous())->with('success', 'Card can not be blocked at the moment. ' . $error);
                }
                if (!empty($client->users) && isset($client->users[0])) {
                    EmailNotification::notifyClientForCardBlock($client, $linkedCardId);
                }
                //dd($block);
                $card_details = $linkedCard->cardDetails;
                $card_details->cardStatus = 'Locked';
                $card_details->save();
            }

            $cardRequest->save();
            $assignedCard->status = 'Locked';
            $assignedCard->save();

            return redirect(url()->previous())->with('success', 'Card Blocked successfully!');
        } catch (Exception $e) {
            return redirect(url()->previous())->with('error', $e->getMessage());
        }
    }

    public function unblockCard(Request $request)
    {
        $this->validate($request, [
            'updatable' => 'required',
            'request_reason' => 'required'
        ]);

        try {
            $clientId = null;
            $client = null;
            $linkedCardId = null;
            $assignedCard = AssignedCard::find($request->get('id'));
            $linkedCard = $assignedCard->link;

            if ($linkedCard) {
                $linkedCardId = $linkedCard->id;
                $client = $linkedCard->owner;
                $clientId = $client->id;
            }

            // if ($request->get('request_id')) {
            $cardRequest = CardRequest::where('mpin', $assignedCard->mpin)->where('updatable', 'Unblock')->first();

            if ($cardRequest) {
                $cardRequest->request_reason = $request->get('request_reason');
                $cardRequest->status = 'Approved';
                // }
            } else {
                // $clientId = ClientHelper::getClientIdByUserIdAndName(Auth::user()->id, Auth::user()->name);
                // $linkedUser = UserCardLink::where('client_id', $clientId)->first();

                // $alreadyExists = CardRequest::where('username', $card->username)->first();

                // if ($alreadyExists) {
                //     return redirect('/client/card-management/pending-requests/list')->with('error', 'You have already requested for Card Unblock, please wait for the Unblock process.');
                // }

                $cardRequest = new CardRequest();

                $cardRequest->client_id = $clientId;
                $cardRequest->mpin = $assignedCard->mpin;
                $cardRequest->type = 'Card Unblock Request';
                $cardRequest->updatable = $request->get('updatable');
                $cardRequest->request_reason = $request->get('request_reason');
                $cardRequest->created_by = Auth::user()->id;
                $cardRequest->status = 'Approved';
            }


            $provider = 'visa_money';
            if (!empty($assignedCard->provider))
                $provider = $assignedCard->provider;

            if ($provider == 'visa_money') {
                $vistaMoneyApis = new VistaMoneyMerchantApis();
                $response = $vistaMoneyApis->unblockCard($assignedCard->userName, $assignedCard->mpin);

                if ($client) {
                    $vistaMoneyApis = new VistaMoneyMerchantApis();
                    $response = $vistaMoneyApis->getCardDetails($assignedCard->mpin);
                    self::updateCardDetails($response, $assignedCard->link->cardDetails);

                    // notify client
                    if (!empty($client->users) && isset($client->users[0])) {
                        EmailNotification::notifyClientForCardUnblockApproval($client, $linkedCardId);
                    }
                }
            } else {
                $fingateHelper = new FingateHelper();
                $block = '';
                if (!empty($linkedCard->cardDetails->card_id)) {
                    $block = $fingateHelper->unblockCard($linkedCard->cardDetails->card_id);
                }

                if (empty($block) || $block->ErrorCode !== 'Success') {
                    $error = '';
                    if (!empty($block->ErrorDescription))
                        $error = $block->ErrorDescription;
                    return redirect(url()->previous())->with('success', 'Card can not be Unblocked at the moment. ' . $error);
                }
                $card_details = $linkedCard->cardDetails;
                $card_details->cardStatus = 'Active';
                $card_details->save();
                if (!empty($client->users) && isset($client->users[0])) {
                    EmailNotification::notifyClientForCardUnblockApproval($client, $linkedCardId);
                }
                //dd($block);
            }


            $cardRequest->save();
            $cardRequest->status = 'Approved';
            $cardRequest->save();

            $assignedCard->status = 'Active';
            $assignedCard->save();

            return redirect(url()->previous())->with('success', 'Card Unblocked Successfully!');
        } catch (Exception $e) {
            return redirect(url()->previous())->with('error', $e->getMessage());
        }
    }

    public function downloadStatement($id)
    {
        try {
            $card = AssignedCard::find($id);

            Log::info("Calling the helper function.");
            // Check and update the statements if available.
            // CardHelper::checkAndDownloadStatements($card, true);

            // $start_date = Carbon::now()->subMonth(6)->format('Y-m-d 00:00:00');
            $transactions = CardStatement::orderBy("time", "asc")
                ->where("mpin", $card->mpin)
                ->where('type', '<>', 'Local Tax')
                ->where('type', 'not like', 'COMISIÓN%')
                ->where('type', 'not like', 'COMISION%')
                ->whereNotIn('result', ['Decline', 'Declined', 'Void', 'Reversed', 'Expired'])
                // ->where('time', '>', $start_date)
                ->get();
            // $transactions = $transactions->get();

            $first_trn = $transactions->first();
            //dd($first_trn);
            if (!$first_trn) {
                return redirect(url()->previous())->with('error', 'No Transactions available for this card.');
            }

            $start_date = Carbon::parse($first_trn->time)->format('Y-m-d H:i:s');
            $last_trn = $transactions->last();
            $to_date = Carbon::parse($last_trn->time)->format('Y-m-d H:i:s');

            $fingateController = new FingateController();

            $balance = $fingateController->getCardBalance($card->cardDetails);

            // $transactions = $transactions->get();

            // $starting_balance = self::getCardBalanceTillDate($card->mpin, $start_date);
            // $ending_balance = self::getCardEndingBalanceTillDate($card->mpin, $to_date);
            // $balance = $starting_balance;

            // $credit = self::getTotalCredit($card->mpin, $start_date, $to_date);
            // $debit = self::getTotalDebit($card->mpin, $start_date, $to_date);

            // $view = view("pdfs.card.statement-admin", compact('balance', 'transactions', 'card', 'starting_balance', 'ending_balance', 'credit', 'start_date', 'to_date', 'debit'));
            $view = view("pdfs.card.statement-admin", compact('transactions', 'card', 'start_date', 'to_date', 'balance'));

            $pdf = PDF::loadHTML($view);
            $file_name = $card->mpin . "_" . date("dmY") . ".pdf";
            // return $pdf->download($file_name);

            return $pdf->stream($file_name)->header('Content-Type', 'application/pdf');
        } catch (Exception $e) {
            Log::error("Exception thrown during creating the PDF. Exception Message: " . $e->getMessage() . " | Line: " . $e->getLine());

            return redirect(url()->previous())->with('error', $e->getMessage());
        }
    }

    public function viewCard($id = null)
    {
        try {

            //$details = CardDetail::find($id);
            //dd($details);

            $assignedCard = AssignedCard::find($id);
            $assigned_card_id = $assignedCard->id;
            $assignedTo = UserCardLink::where('assigned_card_id', $assignedCard->id)->first();

            if ($assignedCard->provider != 'vista_money') {
                $detail_id =  $assignedTo->detail_id;
                $card =  CardDetail::select('card_id', 'cardNumber', 'provider_type')->where('id', $detail_id)->first();
                $card_id = $card->card_id;
                $finGateHelper = new FingateHelper();
                $details = $finGateHelper->findCardAPI($card_id);

                $cardStatus     = 'Locked';
                $cardType       = $details->CardType ?? 'Unknown';
                $programeType   = 'Visa';
                $physicalCard   = 'Physical Card';

                // Update values based on conditions
                if (($details->CardStatus ?? '') === 'A') {
                    $details->CardStatus = 'Active';
                }

                if (($details->CardType ?? '') === 'P') {
                    $details->CardType = 'Personalised Card';
                }

                if (($details->CardBrand ?? '') === 'M') {
                    $details->CardBrand = 'Master';
                }

                if (($details->CardPhysicalType ?? '') === 'V') {
                    $details->CardPhysicalType = 'Virtual Card';
                }

                //echo  $details->PhysicalCard; die();


                return view('card-management.cards.fingate-assigned-view', compact('details', 'assignedTo', 'assigned_card_id', 'assignedCard', 'card'));
                // dd($details);

            } else {
                $vistaMoneyApis = new VistaMoneyMerchantApis();
                $details = $vistaMoneyApis->getCardDetails($assignedCard->mpin);
                return view('card-management.cards.assigned-view', compact('details', 'assignedTo', 'assigned_card_id'));
            }
        } catch (Exception $e) {
            return redirect(url()->previous())->with('error', $e->getMessage());
        }
    }

    public function syncUnassignedCards()
    {
        try {
            $unassignedCards = $this->getUnassignedCards();

            return redirect(url()->previous())->with('success', 'Unassigned Cards Synced successfully. Total Cards: ' . $unassignedCards);
        } catch (Exception $e) {
            Log::error("Exception thrown during syncing the unassigned cards. Exception Message: " . $e->getMessage() . " | Line: " . $e->getLine());
            Log::error($e->getTraceAsString());

            return redirect(url()->previous())->with('error', $e->getMessage());
        }
    }

    public function listUnassignedCards()
    {
        $providers = [];

        if (in_array("MatchMove", Common::getEnabledModules())) {
            $providers[] = 'match_move';
        }

        try {
            $unassignedCards = UnassignedCard::where('is_assigned', false)->whereIn('provider', $providers)->get();
            $clients = Client::where("status", "Approved")->where('parent_id', 0)->select('name', 'id')->get();

            if (count($unassignedCards) === 0) {
                $unassignedCards = $this->getUnassignedCards();

                if ($unassignedCards > 0) {
                    $unassignedCards = UnassignedCard::where('is_assigned', false)->whereIn('provider', $providers)->get();
                } else {
                    $unassignedCards = [];
                    $clients = [];
                    return view('card-management.cards.unassigned-cards-list', compact('unassignedCards', 'clients'));
                }
            }

            return view('card-management.cards.unassigned-cards-list', compact('unassignedCards', 'clients'));
        } catch (Exception $e) {
            Log::error("Exception thrown during listing the unassigned cards. Exception Message: " . $e->getMessage() . " | Line: " . $e->getLine());
            Log::error($e->getTraceAsString());

            return redirect(url()->previous())->with('error', $e->getMessage());
        }
    }

    public function listLinks()
    {
        // $links = DB::table('user_card_links')->distinct()->get(['client_id']);
        $links = UserCardLink::all();
        $links = collect($links)->unique('client_id');

        return view("card-management.link-users.list", compact('links'));
    }

    public function newLink($assigned_card_id = null)
    {
        // $linkIds = UserCardLink::select('client_id')->get();

        $clients = Client::where("status", "Approved")
            // ->whereNotIn('id', $linkIds)
            ->select('name', 'id')
            ->get();

        $alreadyLinkedCards = UserCardLink::select('assigned_card_id')->get();
        $unlinkedCards = AssignedCard::whereNotIn('id', $alreadyLinkedCards)->get();

        return view("card-management.link-users.new", compact('clients', 'unlinkedCards', 'assigned_card_id'));
    }

    public function assignAndLinkCard(Request $request)
    {
        // var_dump($request->all());
        // dd();
        $rules = [
            'card_code' => [
                'required',
                'string',
                Rule::exists('unassigned_cards', 'card_code')->where(
                    fn($q) =>
                    $q->where('is_assigned', false)
                ),
            ],
        ];

        if (!session('client_id')) {
            $rules['client_id'] = ['required', 'numeric', 'exists:clients,id'];
        }

        $this->validate($request, $rules);
        try {
            $clientId = session('client_id') ?? $request->get('client_id');

            if (in_array("MatchMove", Common::getEnabledModules())) {
                $fingateHelper = new FingateHelper();
                $response = $fingateHelper->createOrAssignCard($request, $clientId, 'match_move', true);

                if (isset($response->IsSucceeded)) {
                    $isSucceeded = $response->IsSucceeded;
                } elseif (isset($response->ErrorCode) && $response->ErrorCode !== 'KycFailed') {
                    $isSucceeded = true;
                } else {
                    $isSucceeded = false;
                }

                if (!$isSucceeded) {
                    throw new Exception($response->ErrorDescription);
                } else {
                    $assignedCard = $fingateHelper->savePhysicalCardDetails($response, $response->CustomerId, $request);

                    if (!$assignedCard) {
                        throw new Exception("Error saving the card details.");
                    } else {

                        //mark the unassigned card as assigned
                        $unassignedCard = UnassignedCard::where('card_code', $request->get('card_code'))->first();
                        $unassignedCard->is_assigned = true;
                        $unassignedCard->assigned_to = $clientId;
                        $unassignedCard->save();
                    }
                }

                if (session('is_physical_card') === true) {
                    $serviceRequestId = session('service_request_id');

                    if ($serviceRequestId) {
                        $mobileReq = MobileRequest::find($serviceRequestId);

                        if ($mobileReq && $mobileReq->status == "Pending") {
                            $mobileReq->status = 'Approved';
                            $mobileReq->approved_by = Auth::user()->id;
                            $mobileReq->approved_at = Carbon::now();
                            $mobileReq->save();
                        }
                    }

                    session()->forget(['service_request_id', 'client_id', 'is_physical_card']);

                    return redirect('/mobile-client-requests/approved-list')
                        ->with('success', 'Card assigned and request approved successfully.');
                }

                return redirect("/card-management/link-users/list")->with('success', "User linked to the Card successfully.");
            } else {
                return redirect(url()->previous())->with('error', "Assigning and linking the card is not possible as MatchMove module is not enabled.");
            }
        } catch (\Exception $e) {
            Log::error("Exception thrown during assigning and linking the card. Exception Message: " . $e->getMessage() . " | Line: " . $e->getLine());
            Log::error($e->getTraceAsString());

            return redirect(url()->previous())->with('error', $e->getMessage());
        }
    }

    public function storeLink(Request $request)
    {
        // dd($request->all());
        if ($request->get('hide_balance')) {
            $hideBalance = true;
        } else {
            $hideBalance = false;
        }

        $alreadyExists = UserCardLink::where('client_id', $request->get('client_id'))
            ->where('assigned_card_id', $request->get('assigned_card_id'))
            ->first();

        if (!$alreadyExists) {
            try {
                $assignedCard = AssignedCard::find($request->get('assigned_card_id'));

                //todo:: DB::beginTransaction();, DB::commit(); and DB::rollback(); in below case the card link
                // is getting stored without detail_id as the API is return null
                $link = new UserCardLink();

                $link->client_id = $request->get('client_id');
                $link->assigned_card_id = $request->get('assigned_card_id');
                $link->mpin = $assignedCard->mpin;
                $link->hide_balance = $hideBalance;
                $link->status = 1; // $request->get('status');
                $link->created_by = Auth::user()->id;

                $link->save();

                $vistaMoneyApis = new VistaMoneyMerchantApis();

                $response = $vistaMoneyApis->getCardDetails($assignedCard->mpin);

                $cardDetail_id = self::storeCardDetails($response);

                $link->detail_id = $cardDetail_id;
                $link->save();
            } catch (\Exception $e) {
                return redirect(url()->previous())->with('error', $e->getMessage());
            }
        } else {
            return redirect(url()->previous())->with('error', "Link already exists.");
        }
        return redirect("/card-management/link-users/list")->with('success', "User linked to the Card successfully.");
    }

    public function editLink($client_id)
    {
        // $link = UserCardLink::find($id);

        $linkedIds = UserCardLink::where('client_id', $client_id)->select('assigned_card_id')->pluck('assigned_card_id')->toArray();
        $clientCards = AssignedCard::whereIn('id', $linkedIds)->get();

        // $linkIds = UserCardLink::select('client_id')->whereNotIn('id', [$id])->get();
        $clients = Client::where("status", "Approved")
            // ->whereNotIn('id', $linkIds)
            ->select('name', 'id')
            ->get();

        // $linkIds = UserCardLink::select('assigned_card_id')->whereNotIn('id', [$link->id])->get();

        $alreadyLinkedCards = UserCardLink::select('assigned_card_id')->get();
        $unlinkedCards = AssignedCard::whereNotIn('id', $alreadyLinkedCards)->get();

        // $cardUsers = AssignedCard::whereNotIn('id', $linkIds)->get();

        if ($client_id) {
            return view("card-management.link-users.edit", compact('clients', 'client_id', 'clientCards', 'unlinkedCards', 'linkedIds'));
        } else {
            return redirect(url()->previous())->with('error', "Link doesn't exist.");
        }
    }

    public function updateLink(Request $request)
    {
        // dd($request->all());
        $linked_card_ids = $request->get('linked_card_ids') ? $request->get('linked_card_ids') : [];
        $hide_balance_array = $request->get('hide_balance_ids') ? $request->get('hide_balance_ids') : [];
        $alreadyLinkedCards = UserCardLink::where('client_id', $request->get('client_id'));
        $alreadyLinkedCardIds = $alreadyLinkedCards->select('assigned_card_id')->pluck('assigned_card_id')->toArray();

        // try {
        if ($linked_card_ids) {
            $links = [];
            foreach ($linked_card_ids as $id) {
                if (!in_array($id, $alreadyLinkedCardIds)) {
                    $link = new UserCardLink();

                    if (isset($hide_balance_array) && in_array($id, $hide_balance_array)) {
                        $hideBalance = true;
                    } else {
                        $hideBalance = false;
                    }

                    $assignedCard = AssignedCard::find($id);

                    //todo:: DB::beginTransaction();, DB::commit(); and DB::rollback(); in below case the card link
                    // is getting stored without detail_id as the API is return null
                    $link = new UserCardLink();

                    $link->client_id = $request->get('client_id');
                    $link->assigned_card_id = $id;
                    $link->mpin = $assignedCard->mpin;
                    $link->hide_balance = $hideBalance;
                    $link->status = 1; // $request->get('status');
                    $link->created_by = Auth::user()->id;

                    $link->save();

                    $vistaMoneyApis = new VistaMoneyMerchantApis();

                    $response = $vistaMoneyApis->getCardDetails($assignedCard->mpin);
                    //dd($response);
                    $cardDetail_id = self::storeCardDetails($response);

                    $link->detail_id = $cardDetail_id;
                    $link->save();

                    $statements = CardStatement::where('mpin', $assignedCard->mpin)->get();

                    if (count($statements)) {
                        foreach ($statements as $statement) {
                            $statement->local_card_id = $cardDetail_id;
                            $statement->save();
                        }
                    }

                    $links[] = $link;

                    // If we don't have any statement yet, then call the API and fetch & store statement against the card(s).
                    // if (count($statements) == 0) {

                    //     Log::info("Fetching the Card statement using the API. MPIN: " . $assignedCard->mpin);

                    //     $vistaMoneyApis = new VistaMoneyMerchantApis(true, $link);
                    //     $statements = $vistaMoneyApis->getCardStatementVistaMoneyDB();
                    //     // dd($statements);

                    //     // $statements = $vistaMoneyApis->getCardStatement(
                    //     //     Carbon::createFromFormat('Y-m-d', Carbon::now()->subDays(120)->toDateString())->format('d-m-Y'),
                    //     //     Carbon::createFromFormat('Y-m-d', Carbon::now()->toDateString())->format('d-m-Y')
                    //     // );

                    //     if (count($statements) > 0) {

                    //         Log::info("Storing the statements been fetched.");

                    //         CardHelper::saveCardStatement($statements, $link->mpin);
                    //     }
                    // }
                }
            }

            if (count($links) > 0) {
                EmailNotification::notifyClientForCardAssignment($links);
            }
        }

        // Change the Balance visibility to shown.
        $balancesToShow = array_diff($alreadyLinkedCardIds, $hide_balance_array);

        foreach ($balancesToShow as $cardId) {
            $link = UserCardLink::where('assigned_card_id', $cardId)->first();

            $link->hide_balance = false;
            $link->save();
        }

        // Change the Balance visibility to hidden.
        foreach ($alreadyLinkedCardIds as $cardId) {
            if (!in_array($cardId, $balancesToShow)) {
                $link = UserCardLink::where('assigned_card_id', $cardId)->first();

                $link->hide_balance = true;
                $link->save();
            }
        }

        $linksToDelete = array_diff($alreadyLinkedCardIds, $linked_card_ids);

        foreach ($linksToDelete as $cardId) {
            $link = UserCardLink::where('assigned_card_id', $cardId)->first();
            if ($link->detail_id) {
                $detail_id = $link->detail_id;
                $cardDetails = CardDetail::find($detail_id);

                $cardDetails->delete();
            }

            $link->delete();
        }
        // } catch (\Exception $e) {
        //     return redirect(url()->previous())->with('error', $e->getMessage());
        // }


        // if ($request->get('hide_balance')) {
        //     $hideBalance = true;
        // } else {
        //     $hideBalance = false;
        // }

        // $link = UserCardLink::find($request->get('id'));

        // if ($link) {
        //     try {
        //         $assignedCard = AssignedCard::find($request->get('assigned_card_id'));
        //         $cardDetail_id = $link->detail_id;


        //         $vistaMoneyApis = new VistaMoneyMerchantApis();

        //         $response = $vistaMoneyApis->getCardDetails($assignedCard->mpin);

        //         $cardDetail_id = self::updateCardDetails($response, $link->cardDetails);

        //         $link->client_id = $request->get('client_id');
        //         $link->detail_id = $cardDetail_id;
        //         $link->assigned_card_id = $assignedCard->id;
        //         $link->mpin = $assignedCard->mpin;
        //         $link->hide_balance = $hideBalance;
        //         $link->created_by = Auth::user()->id;
        //         $link->status = 1; // $request->get('status');

        //         $link->save();
        //     } catch (\Exception $e) {
        //         return redirect(url()->previous())->with('error', "Something went wrong, please try again later.");
        //     }
        // } else {
        //     return redirect(url()->previous())->with('error', "Link doesn't exist.");
        // }

        return redirect("/card-management/link-users/list")->with('success', "User Link updated successfully.");
    }

    public function topupRequestsList(Request $request)
    {

        if ($request->ajax()) {
            $transactions = AccountTransaction::select(
                "account_transactions.id",
                "account_transactions.transaction_type",
                "account_transactions.transaction_nature",
                "account_transactions.amount",
                "account.title",
                "account_transactions.status",
                "cards.cardNumber",
                "account.account_number",
                "account_transactions.transaction_date",
                "user.name",
                "approver_user.name as approver",
                "cards.provider"
            )
                ->leftJoin("accounts as account", "account.id", "=", "account_transactions.account_id")
                ->leftJoin("users as user", "account_transactions.created_by", "=", "user.id")
                ->leftJoin("users as approver_user", "account_transactions.approved_by", "=", "approver_user.id")
                ->leftJoin("assigned_cards as cards", "account_transactions.mpin", "=", "cards.mpin")
                ->where("account_transactions.status", "Approved")
                ->where("account_transactions.transaction_nature", "Top-up")
                ->orderBy('account_transactions.created_at', 'desc')
                ->get();

            return Datatables::of($transactions)->make(true);
        }

        $transactions = [];
        return view("card-management.requests.topup.list", compact('transactions'));
    }

    public function editTopupTransaction($id, $nid = null)
    {
        $transaction_methods = Common::getTransactionMethods();
        $transaction = AccountTransaction::find($id);
        $transactionRequest = TRRequest::find($transaction->tr_request_id);

        $card_details = '';
        $card_currency = '';
        $convert = 1;
        if (!empty($transactionRequest->card)) {
            $card_details = $transactionRequest->card->link->cardDetails;
            $card_currency = $card_details->currency;
            if ($transactionRequest->account->currency->short == $card_currency) {
                $convert = 0;
            }
        }


        $rulesText = '';
        $amlNoteTexts = collect();
        $neroDataTransactionId = 0;
        $portalTransactionsUrl = '';
        if (Common::isModuleEnabled('NeroData')) {
            $neroDataHelper = new NeroDataHelper();
            $rulesText = $neroDataHelper->getNeroDataLogText($transactionRequest->id);

            $notesJson = DB::table('crypto_transactions_log')
                ->where('transaction_id', $transactionRequest->id)
                ->orderByDesc('id')
                ->limit(1)
                ->value(DB::raw(
                    "COALESCE(JSON_EXTRACT(details, '$.payload.notes'), JSON_EXTRACT(details, '$.response.notes'))"
                ));
            $notesArray = json_decode($notesJson, true);
            if (is_string($notesArray)) {
                // handles cases where DB returns JSON as a quoted string
                $notesArray = json_decode($notesArray, true);
            }

            $amlNoteTexts = collect($notesArray ?: [])
                ->map(fn($n) => is_array($n) ? ($n['text'] ?? null) : $n)
                ->filter()
                ->values();
            $neroDataTransactionId = $neroDataHelper->getNeroDataTransactionIdByTrId($transactionRequest->id);
            $portalTransactionsUrl = $neroDataHelper->getPortalTransactionsUrl();
        }

        return view("card-management.requests.topup.edit", compact("transactionRequest", 'convert', 'card_currency', 'rulesText',  'amlNoteTexts', 'neroDataTransactionId', 'portalTransactionsUrl'));
    }

    public function updateTopup(Request $request)
    {
        // dd($request->all());
        $this->validate($request, [
            'debit_account_id' => 'required',
            'amount' => 'required',
            'transaction_date' => 'required',
            'debit_remarks' => 'required'
        ]);

        $client = ClientHelper::getClientByAccountId($request->get('debit_account_id'));
        $linkedUser = UserCardLink::where('client_id', $client->id)->first();

        if (!$linkedUser) {
            return redirect(url()->previous())->with('error', 'Invalid Request');
        }

        // Create Transaction Request
        $debit_account = Account::find($request->get('debit_account_id'));
        // dd($debit_account->id);

        // if account is not available alert it
        if (!$debit_account) {
            return redirect(url()->previous())->with('error', 'Account details not found.');
        }

        // // check if the account belongs to the logged in user
        // if (!Common::checkIfOwnAccount($debit_account)) {
        //     return redirect(url()->previous())->with('error', 'Please choose a valid account');
        // }

        // check if debit account is having enough balance
        $balance = Common::getAccountBalanceById($debit_account->id);

        if ($request->get('amount') > $balance) {
            return redirect(url()->previous())->with('error', "You don't have enough money in your account");
        }

        // create client transaction
        $dt_trn = AccountTransaction::find($request->get('id'));

        $dt_trn->account_id = $debit_account->id;
        $dt_trn->currency_id = $debit_account->account_currency;
        $dt_trn->transaction_type = 'Debit';
        $dt_trn->transaction_nature = 'Top-up';
        $dt_trn->transaction_date = Carbon::createFromFormat('d/m/Y', $request->get('transaction_date'))->format('Y-m-d');
        $dt_trn->amount = $request->get('amount');
        $dt_trn->mpin = $linkedUser->cardDetails->mpin;
        $dt_trn->remarks = $request->get('debit_remarks');
        $dt_trn->created_by = Auth::user()->id;

        $dt_trn->save();



        if (!Common::canUserMakeChange()) {
            return redirect()->back()->with('success', 'Changes has been submitted to your line manager for approval.');
        } else {
            return redirect('/card-management/card/topup-requests/list')->with('success', 'Transaction details updated successfully.');
        }
    }

    public function topupPendingRequestsList($nid = null)
    {
        if ($nid) {
            Common::readNotification($nid);
        }

        $transactions = TRRequest::with('transactions')
            ->where('otp_verified', true)
            ->where('status', 'Pending')
            ->where("transaction_nature", "Top-up")
            ->orderBy('created_at', 'desc')
            ->get();

        return view('card-management.requests.topup.pending-list', compact('transactions'));
    }

    public function topupRejectedRequestsList($nid = null)
    {
        if ($nid) {
            Common::readNotification($nid);
        }

        $transactions = TRRequest::with('transactions')
            ->where('otp_verified', true)
            ->where('status', 'Rejected')
            ->where("transaction_nature", "Top-up")
            ->orderBy('created_at', 'desc')
            ->get();

        return view('card-management.requests.topup.rejected-list', compact('transactions'));
    }

    public function backOfficePendingTopupRequests(Request $request)
    {
        if ($request->ajax()) {
            $transactions = AccountTransaction::select(
                "account_transactions.id",
                "account_transactions.tr_request_id",
                "account_transactions.transaction_type",
                "account_transactions.transaction_nature",
                "account_transactions.amount",
                "account_transactions.status",
                "account_transactions.transaction_date",
                "account_transactions.created_at as request_date",
                "account.title as account_title",
                "account.account_number",
                "user.name as creator",
                "cards.cardNumber",
                "cards.provider"
            )
                ->where("account_transactions.is_crypto", false)
                ->leftJoin("account_transaction_requests as tr", "tr.id", "=", "account_transactions.tr_request_id")
                ->leftJoin("accounts as account", function ($join) {
                    $join->on('account.id', '=', DB::raw('COALESCE(account_transactions.account_id, tr.account_id)'));
                })
                ->leftJoin("users as user", function ($join) {
                    $join->on('user.id', '=', DB::raw('COALESCE(account_transactions.created_by, tr.created_by)'));
                })
                ->leftJoin("assigned_cards as cards", function ($join) {
                    $join->on('cards.mpin', '=', DB::raw('COALESCE(account_transactions.mpin, tr.receiver_account_number)'));
                })
                ->where("account_transactions.transaction_nature", "Top-up")
                ->where("account_transactions.status", "Pending");

            if (Schema::hasColumn('account_transactions', 'is_for_client')) {
                $transactions = $transactions->where('account_transactions.is_for_client', false);
            }

            $transactions = $transactions
                ->orderBy('account_transactions.created_at', 'desc')
                ->get();

            return DataTables::of($transactions)->make(true);
        }

        $transactions = [];
        return view("card-management.requests.topup.pending-list", compact('transactions'));
    }

    public function viewTopupRequest($id)
    {
        $transaction = AccountTransaction::find($id);
        return view("card-management.requests.topup.view", compact("transaction"));
    }

    public function viewTopupPendingRequest($id, $nid = null)
    {
        if ($nid) {
            Common::readNotification($nid);
        }

        $transactionRequest = TRRequest::find($id);
        $card_details = '';
        $card_currency = '';
        $convert = 1;
        if (!empty($transactionRequest->card)) {
            $card_details = $transactionRequest->card->link->cardDetails;
            $card_currency = $card_details->currency;
            if ($transactionRequest->account->currency->short == $card_currency) {
                $convert = 0;
            }
        }


        $serviceFeeBankAccount = Bank::where('is_service_fee_bank', true)->first()->accounts->where('account_currency', $transactionRequest->currency_id)->first()->id;
        $accounts = false;
        $rulesText = '';
        $amlNoteTexts = collect();
        $neroDataTransactionId = 0;
        $portalTransactionsUrl = '';
        if (Common::isModuleEnabled('NeroData')) {
            $neroDataHelper = new NeroDataHelper();
            $rulesText = $neroDataHelper->getNeroDataLogText($transactionRequest->id);

            $notesJson = DB::table('crypto_transactions_log')
                ->where('transaction_id', $transactionRequest->id)
                ->orderByDesc('id')
                ->limit(1)
                ->value(DB::raw(
                    "COALESCE(JSON_EXTRACT(details, '$.payload.notes'), JSON_EXTRACT(details, '$.response.notes'))"
                ));
            $notesArray = json_decode($notesJson, true);
            if (is_string($notesArray)) {
                // handles cases where DB returns JSON as a quoted string
                $notesArray = json_decode($notesArray, true);
            }

            $amlNoteTexts = collect($notesArray ?: [])
                ->map(fn($n) => is_array($n) ? ($n['text'] ?? null) : $n)
                ->filter()
                ->values();
            $neroDataTransactionId = $neroDataHelper->getNeroDataTransactionIdByTrId($transactionRequest->id);
            $portalTransactionsUrl = $neroDataHelper->getPortalTransactionsUrl();
        }

        return view("card-management.requests.topup.view-pending", compact("transactionRequest", "serviceFeeBankAccount", "accounts", 'convert', 'card_currency', 'rulesText',  'amlNoteTexts', 'neroDataTransactionId', 'portalTransactionsUrl'));
    }

    public function approveTopupTransaction(Request $request)
    {
        $this->validate($request, [
            'transaction_date' => 'required',
            'remarks' => 'required'
        ]);

        $req = TRRequest::find($request->id);

        $amount = $req->amount;
        $divisor = 1;
        // $card_details = '';
        // $card_currency = '';
        $convert = 1;
        if (!empty($req->card)) {
            $card_details = $req->card->link->cardDetails;
            $card_currency = $card_details->currency;
            if ($req->account->currency->short == $card_currency) {
                $convert = 0;
            }
        }

        if ($convert == 1) {
            $this->validate($request, [
                'amount_in_usd' => 'required'
            ], [
                'amount_in_usd.required' => 'Please enter the amount in ' . $card_currency . '.',
            ]);

            $amount = $request->get('amount_in_usd');
            $divisor = $amount;

            // if ($request->has('service_fee_bank_account_id')) {

            // $this->validate($request, [
            //     'service_fee_bank_account_id' => 'required',
            //     'service_fee_remarks' => 'required',
            //     'service_fee_currency_id' => 'required',
            //     'service_fee_client_account' => 'required',
            //     'service_fee' => 'required',
            //     // 'trn_fee_bank_account_id' => 'required',
            //     // 'trn_fee_remarks' => 'required',
            //     // 'trn_fee_currency_id' => 'required',
            //     // 'trn_fee_client_account' => 'required',
            //     // 'trn_fee' => 'required'
            // ]);

            // $service_fee = $request->get('service_fee');
            // $trn_fee = $request->get('trn_fee');

            // check if there is enough balance in the client account
            // // if (($req->amount + $service_fee + $trn_fee) > Common::getAccountBalanceById($req->account_id)) {
            // if ($req->amount > Common::getAccountBalanceById($req->account_id)) {
            //     return redirect(url()->previous())->with("error", "Insufficient balance in the client account");
            // }

            // Common::handleFeesOnTransactionApproval($request, $req);
            // }
        }

        if (! Common::trApprovalHasSufficientBalance($req, (float) $req->amount)) {
            return redirect(url()->previous())->with("error", Common::trApprovalInsufficientBalanceMessage($req, (float) $req->amount))->withInput();
        }

        $transaction_date = Carbon::createFromFormat("d/m/Y", $request->get("transaction_date"))->format("Y-m-d H:i:s");

        try {
            DB::beginTransaction();
            Common::neroDataComplianceStatus($req->compliance_status);
            if ($req) {
                if ($req->status == "Rejected") {
                    return redirect(url()->previous())->with("error", "Something went wrong. Please try again later.")->withInput();
                }
                $provider = 'vista_money';
                $card_details = $req->card->link->cardDetails;
                //dd($card_details);
                if (!empty($card_details)) {
                    $provider = $req->card->link->cardDetails->provider_type;
                }
                if ($provider == 'vista_money') {
                    $vistaMoneyApis = new VistaMoneyMerchantApis();
                    $vistaMoneyApis->loadCard($req->receiver_account_number, $req->topup_local_ref, $amount, "USD");
                } else {
                    $finGateHelper = new FingateHelper();
                    $currency_code = 'USD';
                    if ($provider == 'e_money') {
                        $currency_code = $card_details->currency;
                    }

                    $topUp = '';
                    if (!empty($card_details->card_id)) {

                        // if(!empty($req->currency->short))
                        //     $currency_code = $req->currency->short;

                        $payload = array(
                            "CardId" => $card_details->card_id,
                            "Amount" => $amount,
                            "CurrencyCode" => $currency_code,
                            "ReferenceNumber" => $req->topup_local_ref,
                            "Description" => $req->remarks,

                        );
                        //dd($payload);
                        $topUp =  $finGateHelper->cardTopUp($payload);
                    }

                    if (!empty($topUp) && empty($topUp->TransactionId)) {
                        // $transaction_id = $topUp->TransactionId;
                        $message = "Top up request failed, please try again later.";
                        if (is_string($topUp))
                            $topUp = json_decode($topUp);
                        if (!empty($topUp->ErrorDescription))
                            $message = $topUp->ErrorDescription;
                        return redirect(url()->previous())->with("error", $message);
                    }
                }

                $req->remarks = $request->remarks;
                $req->save();

                $trn = new AccountTransaction();
                $trn->account_id = $req->account_id;
                $trn->tr_request_id = $req->id;
                $trn->currency_id = $req->currency_id;
                $trn->transaction_type = $req->transaction_type;
                $trn->transaction_nature = $req->transaction_nature;
                $trn->transaction_date = $transaction_date;
                $trn->mpin = $req->receiver_account_number;
                $trn->topup_local_ref = $req->topup_local_ref;
                $trn->amount = $req->amount;
                $trn->ex_rate = ($req->amount / $divisor);
                $trn->remarks = $req->remarks;
                $trn->status = "Approved";
                $trn->created_by = $req->created_by;
                $trn->approved_by = Auth::user()->id;
                $trn->approved_at = Carbon::now();

                $trn->save();
                //dd($trn);
                //credit Card required
                $req->link_tr_id = $trn->id;
                $req->status = "Approved";
                $req->save();

                DB::commit();

                // notify client
                EmailNotification::notifyClientForTransactionApproval($trn);

                return redirect("/card-management/card/topup-pending-requests/list")->with('success', "Transaction approved successfully.");
            } else {
                DB::rollBack();
                return redirect("/card-management/card/topup-pending-requests/list")->with('error', "Transaction not found.")->withInput();
            }
        } catch (Exception $e) {
            DB::rollBack();
            return redirect("/card-management/card/topup-pending-requests/list")->with('error', $e->getMessage())->withInput();
        }
    }

    public function rejectTopupTransaction(Request $request)
    {
        $transaction = TRRequest::find($request->get('id'));

        if ($transaction) {

            $transaction->status = "Rejected";
            $transaction->rejected_at = Carbon::now();
            $transaction->rejected_by = Auth::user()->id;
            $transaction->reject_reason = $request->get("reject_reason");

            $transaction->save();

            // notify creator
            EmailNotification::notifyTransactionRejection($transaction);

            return redirect("/card-management/card/topup-pending-requests/list")->with('success', "Transaction rejected successfully.");
        } else {
            return redirect("/card-management/card/topup-pending-requests/list")->with('error', "Transaction not found.");
        }
    }

    public function updateTopupTransaction(Request $request)
    {
        $this->validate($request, [
            'transaction_date' => 'required'
        ]);

        $transaction = AccountTransaction::find($request->id);

        $transaction->transaction_date = Carbon::createFromFormat("d/m/Y", $request->get("transaction_date"))->format("Y-m-d H:i:s");
        $transaction->updated_at = Carbon::now();
        $transaction->updated_by = Auth::user()->id;

        $transaction->save();

        return redirect(url()->previous())->with(compact("transaction"))->with('success', "Transaction updated successfully!");
    }

    public function unblockRequestsList()
    {
        $requests = CardRequest::all();

        return view('card-management.requests.unblock.list', compact('requests'));
    }

    public function viewUnblockRequest($id)
    {
        $request = CardRequest::find($id);

        return view('card-management.requests.unblock.view', compact('request'));
    }

    public function approveUnblockRequest(Request $request)
    {
        $request = CardRequest::find($request->get('id'));

        if ($request) {
            try {
                $vistaMoneyApis = new VistaMoneyMerchantApis();
                $card = AssignedCard::where('mpin', $request->mpin)->first();
                $response = $vistaMoneyApis->unblockCard($card->username, $request->mpin);

                $request->status = 'Approved';
                $request->save();

                $card->status = 'Active';
                $card->save();

                $cardDetail = CardDetail::where('mpin', $request->mpin)->first();
                $cardDetail->cardStatus = 'Active';
                $cardDetail->save();

                $client = UserCardLink::where('mpin', $request->mpin)->first()->owner;

                // notify client
                EmailNotification::notifyClientForCardUnblockApproval($client);

                return redirect("/card-management/card/unblock-requests/list")->with('success', "Request successfully approved!");
            } catch (Exception $e) {
                return redirect(url()->previous())->with('error', $e->getMessage());
            }
        } else {
            return redirect(url()->previous())->with('error', "Request doesn't exist.");
        }
    }

    public function rejectUnblockRequest(Request $request)
    {
        $req = CardRequest::find($request->get('id'));

        if ($req) {
            $req->request_reason = $request->get('reject_reason');
            $req->status = 'Rejected';
            $req->updated_at = Carbon::now();

            $req->save();

            $client = UserCardLink::where('mpin', $req->mpin)->first()->owner;

            // notify client
            EmailNotification::notifyClientforCardUnblockRejection($client);

            return redirect('/card-management/card/unblock-requests/list')->with('success', 'Request successfully rejected!');
        } else {
            return redirect(url()->previous())->with("error", 'Request not Found!');
        }
    }

    private function storeCardDetails($response)
    {
        $alreadyExists = CardDetail::where('mpin', $response->mpin)->first();
        $cardDetail = new CardDetail();

        if (!$alreadyExists) {
            $cardDetail->cardNumber = $response->cardNumber;
            $cardDetail->cardImage = $response->cardImage;
            $cardDetail->programType = $response->programType;
            $cardDetail->expiryDate = $response->expiryDate;
            $cardDetail->cardStatus = $response->status;
            $cardDetail->remarks = $response->remarks;
            $cardDetail->cardHolderName = $response->cardHolderName;
            $cardDetail->mpin = $response->mpin;
            $cardDetail->max_balance_limit = $response->max_balance_limit;
            $cardDetail->currency = $response->currency;
            $cardDetail->cType = $response->cType;
            $cardDetail->firstName = $response->get_member->firstName;
            $cardDetail->lastName = $response->get_member->lastName;
            $cardDetail->userName = $response->get_member->userName;
            $cardDetail->email = $response->get_member->email;
            $cardDetail->mobileNo = $response->get_member->mobileNo;
            $cardDetail->countryCode = $response->get_member->countryCode;
            $cardDetail->dateOfBirth = $response->get_member->dateOfBirth;
            $cardDetail->callingCode = $response->get_member->callingCode;
            $cardDetail->gender = $response->get_member->gender;
            $cardDetail->state = $response->get_member->state;
            $cardDetail->city = $response->get_member->city;
            $cardDetail->address = $response->get_member->address;
            $cardDetail->postalCode = $response->get_member->postalCode;
            $cardDetail->id_proof_front = $response->get_member->id_proof_front;
            $cardDetail->id_proof_back = $response->get_member->id_proof_back;
            $cardDetail->address_proof_front = $response->get_member->address_proof_front;
            $cardDetail->address_proof_back = $response->get_member->address_proof_back;
            $cardDetail->id_proof_submission_date = $response->get_member->id_proof_submission_date;
            $cardDetail->address_proof_submission_date = $response->get_member->address_proof_submission_date;
            $cardDetail->kyc_remark = $response->get_member->kyc_remark;
            $cardDetail->kyc_remark_date = $response->get_member->kyc_remark_date;
            $cardDetail->cardUserStatus = $response->get_member->status;
            $cardDetail->merchant_id = $response->get_member->merchant_id;
            $cardDetail->processor_message = $response->get_member->processor_message;
            $cardDetail->wallet_id = $response->get_member->wallet_id;
            $cardDetail->unique_id = $response->get_member->unique_id;

            $cardDetail->save();

            return $cardDetail->id;
        } else {
            throw new Exception('The Card User, with the Card # (' . $response->cardNumber . '), is already assigned to another FAIB User.');
        }
    }

    private function updateCardDetails($response, $cardDetail)
    {
        // $cardDetail = CardDetail::where('mpin', $existingMpin)->first();

        $cardDetail->cardNumber = $response->cardNumber;
        $cardDetail->cardImage = $response->cardImage;
        $cardDetail->programType = $response->programType;
        $cardDetail->expiryDate = $response->expiryDate;
        $cardDetail->cardStatus = $response->status;
        $cardDetail->remarks = $response->remarks;
        $cardDetail->cardHolderName = $response->cardHolderName;
        $cardDetail->mpin = $response->mpin;
        $cardDetail->max_balance_limit = $response->max_balance_limit;
        $cardDetail->currency = $response->currency;
        $cardDetail->cType = $response->cType;
        $cardDetail->firstName = $response->get_member->firstName;
        $cardDetail->lastName = $response->get_member->lastName;
        $cardDetail->userName = $response->get_member->userName;
        $cardDetail->email = $response->get_member->email;
        $cardDetail->mobileNo = $response->get_member->mobileNo;
        $cardDetail->countryCode = $response->get_member->countryCode;
        $cardDetail->dateOfBirth = $response->get_member->dateOfBirth;
        $cardDetail->callingCode = $response->get_member->callingCode;
        $cardDetail->gender = $response->get_member->gender;
        $cardDetail->state = $response->get_member->state;
        $cardDetail->city = $response->get_member->city;
        $cardDetail->address = $response->get_member->address;
        $cardDetail->postalCode = $response->get_member->postalCode;
        $cardDetail->id_proof_front = $response->get_member->id_proof_front;
        $cardDetail->id_proof_back = $response->get_member->id_proof_back;
        $cardDetail->address_proof_front = $response->get_member->address_proof_front;
        $cardDetail->address_proof_back = $response->get_member->address_proof_back;
        $cardDetail->id_proof_submission_date = $response->get_member->id_proof_submission_date;
        $cardDetail->address_proof_submission_date = $response->get_member->address_proof_submission_date;
        $cardDetail->kyc_remark = $response->get_member->kyc_remark;
        $cardDetail->kyc_remark_date = $response->get_member->kyc_remark_date;
        $cardDetail->cardUserStatus = $response->get_member->status;
        $cardDetail->merchant_id = $response->get_member->merchant_id;
        $cardDetail->processor_message = $response->get_member->processor_message;
        $cardDetail->wallet_id = $response->get_member->wallet_id;
        $cardDetail->unique_id = $response->get_member->unique_id;

        $cardDetail->save();

        return $cardDetail->id;
    }

    private static function getChanges($model)
    {
        $transaction = ChangeLogger::get($model);
        $cheque = ($model->cheque) ? ChangeLogger::get($model->cheque) : null;

        $changes = [];
        if ($transaction) {
            $changes[] = $transaction;
        }

        if ($cheque) {
            $changes[] = $cheque;
        }

        return $changes;
    }

    private function getCardBalanceTillDate($mpin, $date)
    {
        if ($date == "") {
            $date = Carbon::now();
        }

        $credit = CardStatement::where("mpin", $mpin)
            ->where("transaction_nature", "Credit")
            ->where("time", "<", $date)
            ->sum("terminal_amount");

        $debit = CardStatement::where("mpin", $mpin)
            ->where("transaction_nature", "Debit")
            ->where("time", "<", $date)
            ->sum("terminal_amount");

        return $credit - $debit;
    }

    private function getCardEndingBalanceTillDate($mpin, $date)
    {
        if ($date == "") {
            $date = Carbon::now();
        }

        $credit = CardStatement::where("mpin", $mpin)
            ->where("transaction_nature", "Credit")
            ->where("time", "<=", $date)
            ->sum("terminal_amount");

        $debit = CardStatement::where("mpin", $mpin)
            ->where("transaction_nature", "Debit")
            ->where("time", "<=", $date)
            ->sum("terminal_amount");

        return $credit - $debit;
    }

    private function getTotalCredit($mpin, $from, $to)
    {
        return CardStatement::where("mpin", $mpin)
            ->where("transaction_nature", "Credit")
            ->where("time", ">=", $from)
            ->where("time", "<=", $to)
            ->sum("terminal_amount");
    }

    private function getTotalDebit($mpin, $from, $to)
    {
        return CardStatement::where("mpin", $mpin)
            ->where("transaction_nature", "Debit")
            ->where("time", ">=", $from)
            ->where("time", "<=", $to)
            ->sum("terminal_amount");
    }

    private function getUnassignedCards()
    {
        try {
            if (in_array("MatchMove", Common::getEnabledModules())) {
                $fingateHelper = new FingateHelper();
                $response = $fingateHelper->getUnassignedCards();

                if ($response->IsSucceeded == true) {
                    foreach ($response->NoNameCardList as $card) {
                        $exists = UnassignedCard::where('card_code', $card->CardCode)->first();

                        if (!$exists) {
                            $newCard = new UnassignedCard();
                            $newCard->card_code = $card->CardCode;
                            $newCard->masked_card_number = $card->MaskedCardNumber;
                            $newCard->card_type = 'Physical Card';
                            $newCard->is_assigned = false;
                            $newCard->assigned_to = null;
                            $newCard->provider = 'match_move';

                            $newCard->save();
                        }
                    }

                    return count($response->NoNameCardList);
                } else {
                    throw new \Exception("Error: " . $response->ErrorDescription);
                }
            } else {
                return 0;
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
        }
    }

    public function topUpCardBackOffice()
    {

        if (Auth::user()->hasRole(["operations manager", "finance manager", "super admin",]) !== true) {
            return redirect(url()->previous())->with('error', 'You dont have the required permissions.');
        }

        try {
            // Get all assigned cards with their link (for client_id) and card details (for display)
            $linkedCards = AssignedCard::with(['link', 'cardDetails'])->get();

            // Get all active, non-crypto currencies
            $currencies = Currency::where("is_active", true)->where("is_crypto", false)->pluck("id");

            // Batch-load all accounts for all linked clients (avoids N+1 queries per card)
            $clientIds = $linkedCards
                ->map(fn($card) => optional($card->link)->client_id)
                ->filter()
                ->unique()
                ->values();

            $accountsByClientId = collect();
            if ($clientIds->isNotEmpty()) {
                $allAccounts = Account::where('is_active', true)
                    ->whereIn('client_id', $clientIds)
                    ->whereIn('account_currency', $currencies)
                    ->select('id', 'client_id', 'title', 'account_number')
                    ->get()
                    ->map(function ($account) {
                        $account->amount = Common::getAccountBalanceById($account->id);
                        return $account;
                    });

                $accountsByClientId = $allAccounts->groupBy('client_id');
            }

            $cardsJson = $linkedCards
                ->map(function ($card) use ($accountsByClientId) {
                    $client_id = optional($card->link)->client_id;
                    $accounts = collect($accountsByClientId->get($client_id, []))
                        ->map(function ($account) {
                            return [
                                'id' => $account->id,
                                'label' => trim(($account->title ?? '') . ' - ' . ($account->account_number ?? '')),
                                'amount' => $account->amount,
                            ];
                        })
                        ->values();

                    // Get card type (physical/virtual)
                    $assigned_Card = AssignedCard::where('mpin', $card->mpin)->first();
                    $card_type = null;
                    if ($assigned_Card) {
                        $card_type = $assigned_Card->physical ? 'Physical' : 'Virtual';
                    }

                    return [
                        'card_id' => optional($card)->id,
                        'card_number' => data_get($card, 'cardDetails.cardNumber'),
                        'card_holder_name' => data_get($card, 'cardDetails.cardHolderName'),
                        'client_id' => $client_id,
                        'accounts' => $accounts,
                        'card_type' => $card_type,
                    ];
                })
                ->filter(fn($row) => !empty($row['card_id']) && !empty($row['client_id']))
                ->values();

            return view("card-management.cards.topup", compact('cardsJson'));
        } catch (Exception $e) {
            Common::logError($e, __METHOD__, __LINE__);
        }
    }

    public function approveBackOfficeTransaction(Request $request)
    {
        $this->validate($request, [
            'transaction_date' => 'required',
            'remarks' => 'required'
        ]);
        // The view sends TRRequest id; fetch the request first
        $req = TRRequest::find($request->get('id'));
        if (!$req) {
            return redirect(url()->previous())->with('error', 'Transaction request not found.');
        }

        // Find the linked AccountTransaction by TRRequest id
        $transaction = AccountTransaction::where('tr_request_id', $req->id)->first();
        if (!$transaction) {
            return redirect(url()->previous())->with('error', 'Linked account transaction not found.');
        }
        if (Auth::user()->id == $req->created_by) {
            return redirect(url()->previous())->with('error', "You cannot approve your own transaction.");
        }


        //for now we allow the transaction to be approved even if neroData status is not completed,we will uncomment this code in the future
        // if (($req->compliance_status == "MODERATION" || $req->compliance_status == "CANCELLED") && Common::isModuleEnabled('NeroData')) {
        //     return redirect(url()->previous())->with("error", "Something went wrong. Please try again later.");
        // }

        $amount = $req->amount;
        $divisor = 1;
        // $card_details = '';
        // $card_currency = '';
        $convert = 1;
        if (!empty($req->card)) {
            $card_details = $req->card->link->cardDetails;
            $card_currency = $card_details->currency;
            if ($req->account->currency->short == $card_currency) {
                $convert = 0;
            }
        }

        if ($convert == 1) {
            $this->validate($request, [
                'amount_in_usd' => 'required'
            ], [
                'amount_in_usd.required' => 'Please enter the amount in ' . $card_currency . '.',
            ]);

            $amount = $request->get('amount_in_usd');
            $divisor = $amount;
        }

        if (! Common::trApprovalHasSufficientBalance($req, (float) $req->amount)) {
            return redirect(url()->previous())->with("error", Common::trApprovalInsufficientBalanceMessage($req, (float) $req->amount))->withInput();
        }

        $transaction_date = Carbon::createFromFormat("d/m/Y", $request->get("transaction_date"))->format("Y-m-d H:i:s");

        try {
            if ($req) {
                if ($req->status == "Rejected") {
                    return redirect(url()->previous())->with("error", "Something went wrong. Please try again later.")->withInput();
                }
                $provider = 'vista_money';
                $card_details = $req->card->link->cardDetails;
                //dd($card_details);
                if (!empty($card_details)) {
                    $provider = $req->card->link->cardDetails->provider_type;
                }
                if ($provider == 'vista_money') {
                    $vistaMoneyApis = new VistaMoneyMerchantApis();
                    $vistaMoneyApis->loadCard($req->receiver_account_number, $req->topup_local_ref, $amount, "USD");
                } else {
                    $finGateHelper = new FingateHelper();
                    $currency_code = 'USD';
                    if ($provider == 'e_money') {
                        $currency_code = $card_details->currency;
                    }

                    $topUp = '';
                    if (!empty($card_details->card_id)) {

                        // if(!empty($req->currency->short))
                        //     $currency_code = $req->currency->short;

                        $payload = array(
                            "CardId" => $card_details->card_id,
                            "Amount" => $amount,
                            "CurrencyCode" => $currency_code,
                            "ReferenceNumber" => $req->topup_local_ref,
                            "Description" => $req->remarks,

                        );
                        //dd($payload);
                        $topUp =  $finGateHelper->cardTopUp($payload);
                    }

                    if (!empty($topUp) && empty($topUp->TransactionId)) {
                        // $transaction_id = $topUp->TransactionId;
                        $message = "Top up request failed, please try again later.";
                        if (is_string($topUp))
                            $topUp = json_decode($topUp);
                        if (!empty($topUp->ErrorDescription))
                            $message = $topUp->ErrorDescription;
                        return redirect(url()->previous())->with("error", $message);
                    }
                }

                $req->remarks = $request->remarks;
                $req->save();

                //$trn = new AccountTransaction();
                $transaction->account_id = $req->account_id;
                $transaction->tr_request_id = $req->id;
                $transaction->currency_id = $req->currency_id;
                $transaction->transaction_type = $req->transaction_type;
                $transaction->transaction_nature = $req->transaction_nature;
                $transaction->transaction_date = $transaction_date;
                $transaction->mpin = $req->receiver_account_number;
                $transaction->topup_local_ref = $req->topup_local_ref;
                $transaction->amount = $req->amount;
                $transaction->ex_rate = ($req->amount / $divisor);
                $transaction->remarks = $req->remarks;
                $transaction->status = "Approved";
                $transaction->created_by = $req->created_by;
                $transaction->approved_by = Auth::user()->id;
                $transaction->approved_at = Carbon::now();

                $transaction->save();
                //dd($trn);
                //credit Card required
                $req->link_tr_id = $transaction->id;
                $req->status = "Approved";
                $req->save();

                DB::commit();

                // notify client
                EmailNotification::notifyClientForTransactionApproval($transaction);

                return redirect("/card-management/card/topup-pending-requests/list")->with('success', "Transaction approved successfully.");
            } else {
                DB::rollBack();
                return redirect("/card-management/card/topup-pending-requests/list")->with('error', "Transaction not found.")->withInput();
            }
        } catch (Exception $e) {
            DB::rollBack();
            return redirect("/card-management/card/topup-pending-requests/list")->with('error', $e->getMessage())->withInput();
        }
    }

    public function rejectTopupTransactionBackOffice(Request $request)
    {
        $transaction = TRRequest::find($request->get('id'));

        $req = AccountTransaction::where('tr_request_id', $transaction->id)->first();

        if ($transaction) {

            $transaction->status = "Rejected";
            $transaction->rejected_at = Carbon::now();
            $transaction->rejected_by = Auth::user()->id;
            $transaction->reject_reason = $request->get("reject_reason");

            $transaction->save();

            $req->status = "Rejected";
            $req->rejected_at = Carbon::now();
            $req->rejected_by = Auth::user()->id;
            $req->reject_reason = $request->get("reject_reason");
            $req->save();

            // notify creator
            EmailNotification::notifyTransactionRejection($transaction);

            return redirect("/card-management/card/topup-pending-requests/list")->with('success', "Transaction rejected successfully.");
        } else {
            return redirect("/card-management/card/topup-pending-requests/list")->with('error', "Transaction not found.");
        }
    }

    public function backOfficeRejectedTopupRequests(Request $request)
    {
        if ($request->ajax()) {
            $transactions = AccountTransaction::select(
                "account_transactions.id",
                "account_transactions.tr_request_id",
                "account_transactions.transaction_type",
                "account_transactions.transaction_nature",
                "account_transactions.amount",
                "account_transactions.status",
                "account_transactions.transaction_date",
                "account_transactions.created_at as request_date",
                "account.title as account_title",
                "account.account_number",
                "user.name as creator",
                "cards.cardNumber",
                "cards.provider"
            )
                ->where("account_transactions.is_crypto", false)
                ->leftJoin("account_transaction_requests as tr", "tr.id", "=", "account_transactions.tr_request_id")
                ->leftJoin("accounts as account", function ($join) {
                    $join->on('account.id', '=', DB::raw('COALESCE(account_transactions.account_id, tr.account_id)'));
                })
                ->leftJoin("users as user", function ($join) {
                    $join->on('user.id', '=', DB::raw('COALESCE(account_transactions.created_by, tr.created_by)'));
                })
                ->leftJoin("assigned_cards as cards", function ($join) {
                    $join->on('cards.mpin', '=', DB::raw('COALESCE(account_transactions.mpin, tr.receiver_account_number)'));
                })
                ->where("account_transactions.transaction_nature", "Top-up")
                ->where("account_transactions.status", "Rejected");

            if (Schema::hasColumn('account_transactions', 'is_for_client')) {
                $transactions = $transactions->where('account_transactions.is_for_client', false);
            }

            $transactions = $transactions
                ->orderBy('account_transactions.created_at', 'desc')
                ->get();
            return Datatables::of($transactions)->make(true);
        }

        $transactions = [];
        return view("card-management.requests.topup.rejected-list", compact('transactions'));
    }

    public function storeTopupBackOffice(Request $request)
    {
        $this->validate($request, [
            'debit_account_id' => 'required',
            'card_id' => 'required',
            'amount' => 'required|numeric',
            'debit_remarks' => 'required'
        ]);


        try {

            $card = AssignedCard::find($request->get('card_id'));

            if (!$card) {
                return redirect(url()->previous())->with('error', 'Invalid Request');
            }

            $cardClientId = optional($card->link)->client_id;

            if (!$cardClientId) {
                return redirect(url()->previous())->with('error', 'Invalid Request');
            }
            $debit_account = Account::find($request->get("debit_account_id"));
            if (!$debit_account) {
                return redirect(url()->previous())->with('error', "Account details not found.");
            }
            if ((int) $debit_account->client_id !== (int) $cardClientId) {
                return redirect(url()->previous())->with('error', 'Please choose an account that belongs to the selected card client.');
            }

            // check if debit account is having enough balance
            $balance = Common::getAccountBalanceById($debit_account->id);
            if ($request->get("amount") > $balance) {
                return redirect(url()->previous())->with('error', "You don't have enough money in your account");
            }
            DB::beginTransaction();
            // create debit transaction
            $trn = new TRRequest();

            $trn->account_id = $debit_account->id;
            $trn->currency_id = $debit_account->account_currency;
            $trn->transaction_type = "Debit";
            $trn->transaction_nature = "Top-up";
            $trn->transaction_date = Carbon::now()->format('Y-m-d');
            $trn->amount = $request->get("amount");
            $trn->receiver_account_number = $card->mpin;
            $trn->topup_local_ref = Carbon::now()->timestamp;
            $trn->remarks = $request->get("debit_remarks");
            $trn->status = "Pending";
            $trn->created_by = Auth::user()->id;
            $trn->save();

            // create client transaction
            $ac_trn = new AccountTransaction();
            $account_currency = $debit_account->account_currency;
            $ac_trn->account_id = $debit_account->id;
            $ac_trn->tr_request_id = $trn->id;
            $ac_trn->currency_id = $account_currency;
            $ac_trn->transaction_type = "Debit";
            //$ac_trn->receiver_account_number = $card->mpin;
            $ac_trn->transaction_nature = "Top-up";
            $ac_trn->transaction_date = Carbon::now()->format('Y-m-d');
            $ac_trn->mpin = $card->mpin;
            $ac_trn->amount = $request->get("amount");
            $ac_trn->remarks = $request->get("debit_remarks");
            $ac_trn->status = "Pending";
            $ac_trn->created_by = Auth::user()->id;

            // differentiate in account_transactions table (backoffice = 0)
            $ac_trn->is_for_client = 0;

            $ac_trn->save();

            DB::commit();

            Common::notifyComplianceTeamNeroData($trn);

            return redirect("/card-management/card/topup-pending-requests/list")->with('success', "topup request created successfully.");
        } catch (Exception $e) {
            Common::logError($e, __METHOD__, __LINE__);
            DB::rollBack();
            if (401 === $e->getCode()) {
                return back()->with('error', 'You are not authorized to make this transaction.');
            } else {
                return back()->with('error', $e->getMessage());
            }
        }
    }
}
