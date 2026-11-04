<?php

namespace App\Http\Controllers;

use App\Helpers\CardHelper;
use Illuminate\Http\Request;
use App\Helpers\Common;
use App\Helpers\EmailNotification;
use App\Helpers\VistaMoneyMerchantApis;
use App\Helpers\IDORHelper;
use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\AssignedCard;
use App\Models\CardDetail;
use App\Models\CardRequest;
use App\Models\CardStatement;
use App\Models\ClientCompanyFile;
use App\Models\ClientIndividualFile;
use App\Models\Currency;
use App\Models\InstantCardRequest;
use App\Models\TRRequest;
use App\Models\UserCardLink;
use App\Models\VistaMoneyCountry;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ClientCardManagementController extends Controller
{
    public function requestInstantCard()
    {
        $alreadyApplied = InstantCardRequest::where('user_id', Auth::user()->id)->first();

        if ($alreadyApplied) {
            return redirect('/client/dashboard')->with('success', 'Your Instant Card Request has already been submitted. Please wait for your KYC Approval. Once the KYC is done your card will be assigned to you.');
        } else {
            $countries = VistaMoneyCountry::all();

            return view('client-portal.card-management.requests.request-card', compact('countries'));
        }
    }

    public function requestCard(Request $request)
    {
        try {
            $userId = Auth::user()->id;
            $username = null;

            if (!$username) {
                return redirect(url()->previous())->with('error', 'Username is null.');
            }

            $client = CardHelper::getLinkedClientsByUser(Auth::user(), $username);

            if ($client->type == 'individual') {
                $clientFiles = ClientIndividualFile::all();
            } elseif ($client->type == 'company') {
                $clientFiles = ClientCompanyFile::all();
            }

            if (count($clientFiles) < 4) {
                throw new Exception('The client files are not enough for ID and Address Proof.', 1);
            }

            $country = VistaMoneyCountry::find($request->get('country'));

            $data = [
                'firstName' => $request->get('firstName'),
                'lastName' => $request->get('lastName'),
                'display_name' => $request->get('firstName') . ' ' . $request->get('lastName'),
                'email' => $request->get('email'),
                'mobileNo' => $request->get('mobileNo'),
                'dateOfBirth' => $request->get('dateOfBirth'),
                'gender' => $request->get('gender'),
                'countryCode' => $country->code,
                'callingCode' => $country->phonecode,
                'state' => $request->get('state'),
                'city' => $request->get('city'),
                'postalCode' => $request->get('postalCode'),
                'address' => $request->get('address'),
                'instant_card' => 'Yes'
            ];

            $instantCardRequest = new InstantCardRequest();

            $instantCardRequest->user_id = $userId;
            $instantCardRequest->firstName = $data['firstName'];
            $instantCardRequest->lastName = $data['lastName'];
            $instantCardRequest->display_name = $data['display_name'];
            $instantCardRequest->email = $data['email'];
            $instantCardRequest->mobileNo = $data['mobileNo'];
            $instantCardRequest->dateOfBirth = $data['dateOfBirth'];
            $instantCardRequest->gender = $data['gender'];
            $instantCardRequest->countryCode = $data['countryCode'];
            $instantCardRequest->callingCode = $data['callingCode'];
            $instantCardRequest->state = $data['state'];
            $instantCardRequest->city = $data['city'];
            $instantCardRequest->postalCode = $data['postalCode'];
            $instantCardRequest->address = $data['address'];
            $instantCardRequest->instant_card = true;
            $instantCardRequest->status = 'Create User Requested';

            $vistaMoneyApis = new VistaMoneyMerchantApis();
            $response = $vistaMoneyApis->createUser($data);

            $username = $response->userName;

            $instantCardRequest->status = 'Username Assigned';
            $instantCardRequest->assigned_username_after_create_user_api = $username;

            /**
             * Call KYC ID Proof API.
             * Call KYC Address Proof API.
             * VistaMoney will Call Our KYC Approval Callback API.
             * Copy code from CardManagementController line# 89 here
             * to store the card details and link this client's user to this VistaMoney User.
             */

            $idProofFront = self::toBase64($clientFiles[0]->url);
            $idProofBack = self::toBase64($clientFiles[1]->url);

            $vistaMoneyApis->submitIdProofForKyc('XY19167174', $idProofFront, $idProofBack);

            $addressFront = self::toBase64($clientFiles[2]->url);
            $addressBack = self::toBase64($clientFiles[3]->url);

            $vistaMoneyApis->submitAddressProofForKyc('XY19167174', $addressFront, $addressBack);

            $instantCardRequest->status = 'KYC Pending';

            $instantCardRequest->save();

            return redirect('/client/dashboard')->with('success', 'Your Instant Card Request has been submitted. Please wait for your KYC Approval. Once the KYC is done your card will be assigned to you.');
        } catch (Exception $e) {
            Common::logError($e, __METHOD__, __LINE__);

            if ($e->getCode() == 1) {
                $message = $e->getMessage();
            } else {
                $message = 'Something went wrong, please try again later.';
            }

            return redirect()->back()->with('error', $message);
        }
    }

    public function viewCard($id = null, $nid = null)
    {
        try {
            if ($id) {
                $client = UserCardLink::find($id)->owner;
                $belongsToClient = IDORHelper::isCardBelongsToUser(Auth::user(), $client);
            } else {
                $belongsToClient = true;
            }

            if ($nid) {
                Common::readNotification($nid);
            }

            if ($belongsToClient) {
                //todo: need to convert to multiple cards
                $linkedCards = CardHelper::getAllClientsCardsByUser(Auth::user());
                $cardDetails = $linkedCards[0]->cardDetails;

                if ($id) {
                    $cardDetails = UserCardLink::find($id)->cardDetails;
                } else {
                    $id = $linkedCards[0]->id;
                }

                $balance = 0;


                // if (!$id) {
                //     $id = $cardDetails->userName;
                // } else {
                //     foreach ($linkedCards as $card) {
                //         if ($card->id == $username) {
                //             $cardDetails = $card;
                //         }
                //     }
                // }

                $isBalanceHidden = CardHelper::isBalanceHidden($cardDetails->mpin);

                return view("client-portal.card-management.card.view", compact('linkedCards', 'id', 'cardDetails', 'isBalanceHidden', 'balance'));
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

    public function getCardBalance(Request $request, $id = null)
    {
        if ($request->ajax()) {
            $balance = 0;

            try {
                $linkedCard = UserCardLink::find($id);
                $belongsToClient = IDORHelper::isCardBelongsToUser(Auth::user(), $linkedCard->owner);

                if ($belongsToClient) {

                    $hideBalance = CardHelper::isBalanceHidden($linkedCard->mpin);
                    if ($hideBalance) {
                        return json_encode("***");
                    }

                    $vistaMoneyApis = new VistaMoneyMerchantApis(true, $linkedCard);

                    $balance = $vistaMoneyApis->getCardBalance();

                    return $balance;
                }
            } catch (Exception $e) {
                Common::logError($e, __METHOD__, __LINE__);

                return $balance;
            }
        }
    }

    public function blockCard(Request $request)
    {
        $this->validate($request, [
            'updatable' => 'required',
            'card_id' => 'required',
            'request_reason' => 'required'
        ]);

        try {
            $linkedCard = UserCardLink::find($request->get('card_id'));
            $client = $linkedCard->owner;

            $belongsToClient = IDORHelper::isCardBelongsToUser(Auth::user(), $client);

            if ($belongsToClient) {

                // if ($request->get('request_id')) {
                $cardRequest = CardRequest::where('mpin', $linkedCard->mpin)->where('updatable', 'Block')->first();

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

                    $cardRequest->client_id = $client->id;
                    $cardRequest->mpin = $linkedCard->mpin;
                    $cardRequest->type = 'Card Block Request';
                    $cardRequest->updatable = $request->get('updatable');
                    $cardRequest->request_reason = $request->get('request_reason');
                    $cardRequest->created_by = Auth::user()->id;
                    $cardRequest->status = 'Auto';
                }

                $cardRequest->save();

                $vistaMoneyApis = new VistaMoneyMerchantApis(true, $linkedCard);
                $response = $vistaMoneyApis->blockCard();

                $vistaMoneyApis = new VistaMoneyMerchantApis();
                $response = $vistaMoneyApis->getCardDetails($linkedCard->mpin);
                self::updateCardDetails($response, $linkedCard->cardDetails);

                $card = AssignedCard::where('mpin', $linkedCard->mpin)->first();
                $card->status = 'Locked';
                $card->save();

                //notify client
                EmailNotification::notifyClientForCardBlock($client, $linkedCard->id);


                return redirect(url()->previous())->with('success', 'Card Blocked successfully!');
            }
        } catch (Exception $e) {
            Common::logError($e, __METHOD__, __LINE__);

            if (401 === $e->getCode()) {
                return back()->with('error', 'You are not authorized to access this page.');
            } else {
                return back()->with('error', $e->getMessage());
            }
        }
    }

    public function unblockCard(Request $request)
    {
        $this->validate($request, [
            'updatable' => 'required',
            'card_id' => 'required',
            'request_reason' => 'required'
        ]);

        try {
            $linkedCard = UserCardLink::find($request->get('card_id'));
            $client = $linkedCard->owner;

            $belongsToClient = IDORHelper::isCardBelongsToUser(Auth::user(), $client);

            if ($belongsToClient) {

                // if ($request->get('request_id')) {
                $cardRequest = CardRequest::where('mpin', $linkedCard->mpin)->where('updatable', 'Unblock')->first();

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

                    $cardRequest->client_id = $client->id;
                    $cardRequest->mpin = $linkedCard->mpin;
                    $cardRequest->type = 'Card Unblock Request';
                    $cardRequest->updatable = $request->get('updatable');
                    $cardRequest->request_reason = $request->get('request_reason');
                    $cardRequest->created_by = Auth::user()->id;
                    $cardRequest->status = 'Approved';
                }

                $cardRequest->save();

                $vistaMoneyApis = new VistaMoneyMerchantApis();
                $response = $vistaMoneyApis->unblockCard($linkedCard->cardDetails->userName, $linkedCard->mpin);

                $vistaMoneyApis = new VistaMoneyMerchantApis();
                $response = $vistaMoneyApis->getCardDetails($linkedCard->mpin);
                self::updateCardDetails($response, $linkedCard->cardDetails);

                $cardRequest->status = 'Approved';
                $cardRequest->save();

                $card = AssignedCard::where('mpin', $linkedCard->mpin)->first();
                $card->status = 'Active';
                $card->save();

                // notify client
                EmailNotification::notifyClientForCardUnblockApproval($client, $linkedCard->id);


                return redirect(url()->previous())->with('success', 'Card Unblocked Successfully!');
            }
        } catch (Exception $e) {
            Common::logError($e, __METHOD__, __LINE__);

            if (401 === $e->getCode()) {
                return back()->with('error', 'You are not authorized to access this page.');
            } else {
                return back()->with('error', $e->getMessage());
            }
        }
    }

    // public function changePassword(Request $request)
    // {
    //     $this->validate($request, [
    //         'username' => 'required',
    //         'new_password' => 'required',
    //         'retype_password' => 'required'
    //     ]);

    //     if (session('card-details')->userName == $request->get('username')) {
    //         $vistaMoneyApis = new VistaMoneyMemberApis();
    //         $response = $vistaMoneyApis->changePassword(
    //             $request->get('new_password'),
    //             $request->get('retype_password')
    //         );

    //         if ($response->code == '200') {
    //             $card = ClientCardLogin::where('user_id', Auth::user()->id)->first();

    //             $card->password = $request->get('new_password');

    //             $card->save();

    //             return redirect(url()->previous())->with('success', $response->message);
    //         } else {
    //             return redirect(url()->previous())->with('error', $response->message->retype_password[0]);
    //         }
    //     } else {
    //         return redirect(url()->previous())->with('error', 'Invalid Attempt!');
    //     }
    // }

    public function changePin(Request $request)
    {
        $this->validate($request, [
            'new_pin' => 'required|digits:4',
            'retype_pin' => 'required|digits:4',
            'card_id' => 'required'
        ]);

        try {
            $linkedCard = UserCardLink::find($request->get('card_id'));

            $belongsToClient = IDORHelper::isCardBelongsToUser(Auth::user(), $linkedCard->owner);

            if ($belongsToClient) {
                $client = $linkedCard->owner;
                $vistaMoneyApis = new VistaMoneyMerchantApis();
                $response = $vistaMoneyApis->changePin(
                    $linkedCard->mpin,
                    $request->get('new_pin'),
                    $request->get('retype_pin')
                );
                EmailNotification::notifyClientForCardPinChange($client);


                return redirect(url()->previous())->with('success', $response->message);
            }
        } catch (Exception $e) {
            Common::logError($e, __METHOD__, __LINE__);

            if (401 === $e->getCode()) {
                return back()->with('error', 'You are not authorized to access this page.');
            } else {
                return back()->with('error', $e->getMessage());
            }
        }
    }

    public function listTopups($card_id = null, $nid = null)
    {
        try {
            if ($nid) {
                Common::readNotification($nid);
            }

            $linkedCards = CardHelper::getAllClientsCardsByUser(Auth::user());

            if (!$card_id) {
                $card_id = $linkedCards[0]->id;
            }

            $linkedCard = UserCardLink::find($card_id);
            $client = $linkedCard->owner;

            $belongsToClient = IDORHelper::isCardBelongsToUser(Auth::user(), $client);

            if ($belongsToClient) {
                $accounts = Account::where("client_id", $client->id)->pluck("id")->toArray();

                $transactions = TRRequest::whereIn("account_id", $accounts)
                    ->where("otp_verified", true)
                    ->where("transaction_nature", "Top-up")
                    ->where("crypto_amount", null)
                    ->where("receiver_account_number", $linkedCard->mpin)
                    ->orderBy("created_at", "DESC")
                    ->get();

                return view("client-portal.card-management.requests.topups.list", compact('linkedCards', 'card_id', 'transactions'));
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

    public function newTopup($card_id = null)
    {
        // $client = UserCardLink::find($id)->owner;
        $client_ids = Common::getClientIdsByUserId(Auth::user()->id);
        $linkedCards = CardHelper::getAllClientsCardsByUser(Auth::user());

        if (!$card_id) {
            $card_id = $linkedCards[0]->id;
        }

        try {
            $card = UserCardLink::find($card_id);

            $belongsToClient = IDORHelper::isCardBelongsToUser(Auth::user(), $card->owner);

            if ($belongsToClient) {

                $currencies = Currency::where("is_active", true)
                    ->where("is_crypto", false)
                    // ->where('short', 'USD')
                    ->select("id")
                    ->get();
                $accounts = Account::where('is_active', true)->whereIn("client_id", $client_ids)->whereIn("account_currency", $currencies)->select("id", "title", "account_number")->get();

                return view("client-portal.card-management.requests.topups.new", compact('linkedCards', 'card_id', 'accounts'));
            }
        } catch (Exception $e) {
            Common::logError($e, __METHOD__, __LINE__);

            if (401 === $e->getCode()) {
                return back()->with('error', 'You are not authorized to make this transaction.');
            } else {
                return back()->with('error', $e->getMessage());
            }
        }
    }

    public function storeTopup(Request $request)
    {
        $this->validate($request, [
            'debit_account_id' => 'required',
            'card_id' => 'required',
            'amount' => 'required|numeric',
            'debit_remarks' => 'required'
        ]);

        try {
            $card = UserCardLink::find($request->get('card_id'));

            $belongsToClient = IDORHelper::isCardBelongsToUser(Auth::user(), $card->owner);

            if ($belongsToClient) {

                // Check the Credit account_id
                $belongsToClient = IDORHelper::isAccountBelongToUser(Auth::user(), $request->get("debit_account_id"));

                if ($belongsToClient) {

                    if (!$card) {
                        return redirect(url()->previous())->with('error', 'Invalid Request');
                    }

                    // Create Transaction Request
                    $debit_account = Account::find($request->get("debit_account_id"));

                    // if account is not available alert it
                    if (!$debit_account) {
                        return redirect(url()->previous())->with('error', "Account details not found.");
                    }

                    // check if the account belongs to the logged in user
                    if (!Common::checkIfOwnAccount($debit_account)) {
                        return redirect(url()->previous())->with('error', "Please choose a valid account");
                    }

                    // check if debit account is having enough balance
                    $balance = Common::getAccountBalanceById($debit_account->id);

                    if ($request->get("amount") > $balance) {
                        return redirect(url()->previous())->with('error', "You don't have enough money in your account");
                    }

                    // create debit transaction
                    $dt_trn = new TRRequest();

                    $dt_trn->account_id = $debit_account->id;
                    $dt_trn->currency_id = $debit_account->account_currency;
                    $dt_trn->transaction_type = "Debit";
                    $dt_trn->transaction_nature = "Top-up";
                    $dt_trn->transaction_date = Carbon::now()->format('Y-m-d');
                    $dt_trn->amount = $request->get("amount");
                    $dt_trn->receiver_account_number = $card->mpin;
                    $dt_trn->topup_local_ref = Carbon::now()->timestamp;
                    $dt_trn->remarks = $request->get("debit_remarks");
                    $dt_trn->status = "Pending";
                    $dt_trn->created_by = Auth::user()->id;

                    $dt_trn->save();

                    // generate OTP code and send it to user
                    EmailNotification::RequestOTP($dt_trn);

                    return redirect("client/card-management/card/topups/otp/" . $dt_trn->id);
                    // $vistaMoneyApis = new VistaMoneyMemberApis();
                    // $response = $vistaMoneyApis->blockUnblockCard();
                }
            }
        } catch (Exception $e) {
            Common::logError($e, __METHOD__, __LINE__);

            if (401 === $e->getCode()) {
                return back()->with('error', 'You are not authorized to make this transaction.');
            } else {
                return back()->with('error', $e->getMessage());
            }
        }
    }

    public function viewTopup($id, $card_id)
    {
        $transaction = TRRequest::find($id);

        try {
            $belongsToClient = IDORHelper::isAccountBelongToUser(Auth::user(), $transaction->account_id);

            if ($belongsToClient) {
                if ($transaction->status == "Approved") {
                    $transaction = AccountTransaction::where('tr_request_id', $id)->first();
                }

                return view("client-portal.card-management.requests.topups.view", compact('transaction'));
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

    public function editTopup($id, $card_id, $nid = null)
    {
        if ($nid) {
            Common::readNotification($nid);
        }

        $transaction = TRRequest::find($id);

        try {
            if ('Approved' === $transaction->status) {
                return back()->with('error', 'The transaction has already been approved!');
            }

            $belongsToClient = IDORHelper::isAccountBelongToUser(Auth::user(), $transaction->account_id);

            if ($belongsToClient) {

                if ('Approved' === $transaction->status) {
                    return back()->with('error', 'The transaction has already been approved!');
                }

                $linkedCards = CardHelper::getAllClientsCardsByUser(Auth::user());

                if (!$card_id) {
                    $card_id = $linkedCards[0]->id;
                }

                $linkedCard = UserCardLink::find($card_id);

                $belongsToClient = IDORHelper::isCardBelongsToUser(Auth::user(), $linkedCard->owner);

                if ($belongsToClient) {

                    // $client = UserCardLink::find($card_id)->owner;
                    $client_ids = Common::getClientIdsByUserId(Auth::user()->id);

                    $currencies = Currency::where("is_active", true)
                        ->where("is_crypto", false)
                        // ->where('short', 'USD')
                        ->select("id")
                        ->get();
                    $accounts = Account::where('is_active', true)->whereIn("client_id", $client_ids)->whereIn("account_currency", $currencies)->select("id", "title", "account_number")->get();

                    return view("client-portal.card-management.requests.topups.edit", compact('card_id', 'linkedCards', 'transaction', 'accounts'));
                }
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

    public function updateTopup(Request $request)
    {
        $this->validate($request, [
            'debit_account_id' => 'required',
            'card_id' => 'required',
            'amount' => 'required|numeric',
            'debit_remarks' => 'required'
        ]);

        try {
            $card = UserCardLink::find($request->get('card_id'));

            $belongsToClient = IDORHelper::isCardBelongsToUser(Auth::user(), $card->owner);

            if ($belongsToClient) {

                // Check the Credit account_id
                $belongsToClient = IDORHelper::isAccountBelongToUser(Auth::user(), $request->get("debit_account_id"));

                if ($belongsToClient) {

                    if (!$card) {
                        return redirect(url()->previous())->with('error', 'Invalid Request');
                    }

                    // Create Transaction Request
                    $debit_account = Account::find($request->get("debit_account_id"));

                    // if account is not available alert it
                    if (!$debit_account) {
                        return redirect(url()->previous())->with('error', "Account details not found.");
                    }

                    // check if the account belongs to the logged in user
                    if (!Common::checkIfOwnAccount($debit_account)) {
                        return redirect(url()->previous())->with('error', "Please choose a valid account");
                    }

                    // check if debit account is having enough balance
                    $balance = Common::getAccountBalanceById($debit_account->id);

                    if ($request->get("amount") > $balance) {
                        return redirect(url()->previous())->with('error', "You don't have enough money in your account");
                    }

                    // create client transaction
                    $dt_trn = TRRequest::find($request->get("id"));

                    $dt_trn->account_id = $debit_account->id;
                    $dt_trn->currency_id = $debit_account->account_currency;
                    $dt_trn->transaction_type = "Debit";
                    $dt_trn->transaction_nature = "Top-up";
                    $dt_trn->transaction_date = Carbon::now()->format('Y-m-d');
                    $dt_trn->amount = $request->get("amount");
                    $dt_trn->receiver_account_number = $card->mpin;
                    $dt_trn->remarks = $request->get("debit_remarks");
                    $dt_trn->status = "Pending";
                    $dt_trn->created_by = Auth::user()->id;

                    $dt_trn->save();

                    // generate OTP code and send it to user
                    EmailNotification::RequestOTP($dt_trn);

                    return redirect("client/transaction-requests/otp/" . $dt_trn->id);
                    // $vistaMoneyApis = new VistaMoneyMemberApis();
                    // $response = $vistaMoneyApis->blockUnblockCard();
                }
            }
        } catch (Exception $e) {
            Common::logError($e, __METHOD__, __LINE__);

            if (401 === $e->getCode()) {
                return back()->with('error', 'You are not authorized to update this transaction.');
            } else {
                return back()->with('error', $e->getMessage());
            }
        }
    }

    public function getCardStatement($card_id = null)
    {
        try {
            $linkedCards = CardHelper::getAllClientsCardsByUser(Auth::user());

            if (!$card_id) {
                $linkedCard = $linkedCards[0];
                $card_id = $linkedCard->id;
                $username = $linkedCards[0]->cardDetails->userName;
                $card_type = $linkedCards[0]->cardDetails->cType;
            } else {
                $linkedCard = UserCardLink::find($card_id);
                $username = $linkedCard->cardDetails->userName;
                $card_type = $linkedCard->cardDetails->cType;
            }

            $belongsToClient = IDORHelper::isCardBelongsToUser(Auth::user(), $linkedCard->owner);

            if ($belongsToClient) {

                $statements = CardStatement::where('mpin', $linkedCard->mpin)->get();

                // if (count($statements) == 0) {
                //     $vistaMoneyApis = new VistaMoneyMerchantApis(true, $linkedCard);
                //     $statements = $vistaMoneyApis->getCardStatementVistaMoneyDB();
                //     // $statements = $vistaMoneyApis->getCardStatement(
                //     //     Carbon::createFromFormat('Y-m-d', Carbon::now()->subDays(120)->toDateString())->format('d-m-Y'),
                //     //     Carbon::createFromFormat('Y-m-d', Carbon::now()->toDateString())->format('d-m-Y')
                //     // );

                //     if (count($statements) > 0) {
                //         CardHelper::saveCardStatement($statements, $linkedCard->mpin);
                //     }
                // }

                return view("client-portal.card-management.card.statement", compact('linkedCards', 'card_id', 'statements', 'card_type'));
            }
        } catch (Exception $e) {
            Common::logError($e, __METHOD__, __LINE__);

            if (401 === $e->getCode()) {
                $message = 'You are not authorized to access this page.';
            } else {
                $message = 'Something went wrong, please try again later.';
            }

            return redirect('client/card-management/card/statement')->with('error', $message);
        }
    }

    public function generateCardStatement(Request $request)
    {
        $this->validate($request, [
            'card_id' => 'required',
            'from_date' => 'required',
            'to_date' => 'required'
        ]);

        $linkedCards = CardHelper::getAllClientsCardsByUser(Auth::user());
        $linkedCard = UserCardLink::find($request->get('card_id'));

        try {
            $belongsToClient = IDORHelper::isCardBelongsToUser(Auth::user(), $linkedCard->owner);

            if ($belongsToClient) {

                $statements = CardStatement::where('mpin', $linkedCard->mpin)->get();

                if (count($statements) == 0) {
                    $vistaMoneyApis = new VistaMoneyMerchantApis(true, $linkedCard);
                    $statements = $vistaMoneyApis->getCardStatementVistaMoneyDB();
                }

                return view("client-portal.card-management.card.statement", compact('linkedCards', 'card_id', 'statements'));
            }
        } catch (Exception $e) {
            Common::logError($e, __METHOD__, __LINE__);

            if (401 === $e->getCode()) {
                $message = 'You are not authorized to access this page.';
            }

            return redirect('client/card-management/card/statement')->with('error', $message);
        }
    }

    public function clientCardStatementPDF(Request $request)
    {
        $cardLink = UserCardLink::find($request->get('card_id'));

        try {
            $belongsToClient = IDORHelper::isCardBelongsToUser(Auth::user(), $cardLink->owner);

            if ($belongsToClient) {

                $start_date = Carbon::now()->subMonth(6)->format('Y-m-d 00:00:00');
                $transactions = CardStatement::orderBy("time", "asc");
                $transactions->where('mpin', $cardLink->mpin)->where('time', '>', $start_date);
                $transactions = $transactions->get();

                $first_trn = $transactions->first();
                // $start_date = Carbon::parse($first_trn->time)->format('Y-m-d H:i:s');
                $last_trn = $transactions->last();
                $to_date = Carbon::parse($last_trn->time)->format('Y-m-d H:i:s');

                $starting_balance = self::getCardBalanceTillDate($cardLink->mpin, $start_date);
                $ending_balance = self::getCardEndingBalanceTillDate($cardLink->mpin, $to_date);
                $balance = $starting_balance;

                $credit = self::getTotalCredit($cardLink->mpin, $start_date, $to_date);
                $debit = self::getTotalDebit($cardLink->mpin, $start_date, $to_date);

                $view = view("pdfs.card.statement", compact('balance', 'transactions', 'cardLink', 'starting_balance', 'ending_balance', 'credit', 'start_date', 'to_date', 'debit'));
                $pdf = PDF::loadHTML($view);
                $file_name = $cardLink->mpin . "_" . date("dmY") . ".pdf";
                return $pdf->download($file_name);

                return $pdf->stream($file_name)->header('Content-Type', 'application/pdf');
            }
        } catch (Exception $e) {
            Common::logError($e, __METHOD__, __LINE__);

            if (401 === $e->getCode()) {
                return redirect('client/card-management/card/statement')->with('error', 'You are not authorized to access this page.');
            } else {
                return back()->with('error', 'Something went wrong, please try again later.');
            }
        }
    }

    public function unblockRequestsList()
    {
        $requests = CardRequest::all();

        return view('client-portal.card-management.requests.unblock-list', compact('requests'));
    }

    public function viewUnblockRequest($id)
    {
        $request = CardRequest::find($id);

        try {
            $belongsToClient = IDORHelper::isEntityBelongsToUser(Auth::user(), $request->created_by);

            if ($belongsToClient) {

                $linkedCardId = UserCardLink::where('mpin', $request->mpin)->first()->id;

                return view('client-portal.card-management.requests.view', compact('request', 'linkedCardId'));
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

    private function toBase64($imageUrl)
    {
        $path = $imageUrl;
        // $type = pathinfo($path, PATHINFO_EXTENSION);
        $data = file_get_contents($path);
        $base64 = base64_encode($data);

        return $base64;
    }

    private function updateCardDetails($response, $cardDetail)
    {
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
}
