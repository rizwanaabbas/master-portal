<?php

namespace App\Http\Controllers;

use App\Helpers\Common;
use App\Helpers\EmailNotification;
//use App\Helpers\SanctionsHelper;
use App\Helpers\FingateHelper;
use App\Helpers\VistaMoneyMerchantApis;
use App\Models\AccountTransaction;
use App\Models\ClientBeneficiary;
use App\Models\Currency;
use App\Models\CardDetail;
use App\Models\Module;
use App\Models\TRRequest;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;

class TROTPController extends Controller
{
    public function showOTP($id)
    {
        $trn = TRRequest::find($id);

        if (strpos(\Request::getRequestUri(), 'crypto')) {
            $needle = 'crypto-transaction-request';
        } elseif (strpos(\Request::getRequestUri(), 'topups')) {
            $needle = 'card-management.requests.topups';
        } else {
            $needle = 'transaction-request';
        }

        return view("client-portal." . $needle . ".otp", compact('trn'));
    }

    public function validateOTP(Request $request)
    {
        $trn = TRRequest::find($request->get("trn_id"));
        $otp = Common::verifyRequestOTP($request->get("otp"), $trn->id);
        if ($otp) {

            $trn->status = $trn->status;
            $trn->otp_verified = true;
            $trn->save();


            // delete otp after validation
            $otp->delete();

            if ($trn->crypto_amount > 0 || $trn->transaction_nature == "Deposit") {
                $type = $trn->transaction_nature;
                $needle = "crypto-transaction-requests";
            } elseif ($trn->transaction_nature == 'Top-up') {
                $type = $trn->transaction_nature;
                $needle = "card-management/card/topups";

                if ($trn->account->currency->short == "USD") {
                    $results = self::approveTopup($trn);

                    if ($results['status']) {
                        return redirect("/client/" . $needle . "/list")->with($results["return_type"], $results["message"]);
                    }
                }
            } else {
                $type = $this->getTransactionType($trn);
                $needle = "transaction-requests";
            }

            $isSanctioned = false;

            /* if ($trn->beneficiary_id) {
                $module = Module::where('name', 'Compliance')->first();

                if ($module->is_enabled) {

                    //beneficiary sanctions screening
                    $beneficiary = ClientBeneficiary::find($trn->beneficiary_id);
                    $isSanctioned = SanctionsHelper::getSanctionsForTransactionRequest($beneficiary, $trn);
                }
            } */

            if (!$isSanctioned) {
                EmailNotification::notifyFinanceManagersForTRRequest($trn, $type);
            }

            return redirect("/client/" . $needle . "/list")->with("success", "Transaction created successfully");
        } else {
            return back()->with("error", "Invalid OTP provided, please try again with correct OTP");
        }
    }

    public function generateOTP(Request $request)
    {
        $trn = TRRequest::find($request->get("otp_id"));

        if ($trn->crypto_amount > 0 || $trn->transaction_nature == "Deposit") {
            $needle = "crypto-transaction-requests/";
        } elseif ($trn->transaction_nature == 'Top-up') {
            $needle = "card-management/card/topups/";
        } else {
            $needle = "transaction-requests/";
        }

        if ($trn) {
            // delete previous OTP code
            Common::deletePreviousOTP($trn->id);

            // generate OTP code and send it to user
            EmailNotification::RequestOTP($trn);

            return redirect("client/" . $needle . "otp/" . $trn->id)->with("success", "OTP has been generated and sent to your registered email");
        } else {
            return redirect("client/" . $needle . "otp/" . $trn->id)->with("error", "Transaction not found");
        }
    }

    private function getTransactionType($trn)
    {
        if ($trn->beneficiary_id > 0) {
            return "transfer";
        }

        if ($trn->receiver_account_number != "") {
            return "internal";
        }

        return "deposit";
    }

    private function approveTopup($transactionRequest)
    {
        $trn = new AccountTransaction();

        try {
            $transaction_id = '';
            //dd($transactionRequest); die();
            //if($transactionRequest->receiver_account_number) {
            $card_details = CardDetail::where('mpin', $transactionRequest->receiver_account_number)->first();
            //dd($card_details);
            //}
            if (!empty($card_details) && $card_details->provider_type == 'match_move') {
                $currency = Currency::where('id', $transactionRequest->currency_id)->first();
                $finGateHelper = new FingateHelper();
                $card_id = $card_details->card_id;
                $currency_code =  $currency->short;
                //   "CardId": 23,
                //   "WalletId": "0c96c521-7efe-44d6-9802-ade9d74dee4f",// from Wallet ID
                //   "Amount": 11,
                //   "CurrencyCode": "USD",
                //   "ReferenceNumber": "001",
                //   "Description": "TEst Top UP"
                $payload = array(
                    "CardId" => $card_id,
                    "Amount" => $transactionRequest->amount,
                    "CurrencyCode" => $currency_code,
                    "ReferenceNumber" => $transactionRequest->topup_local_ref,
                    "Description" => $transactionRequest->remarks,

                );
                $topUp =  $finGateHelper->cardTopUp($payload);
                if (empty($topUp) && empty($topUp->TransactionId)) {
                    // $transaction_id = $topUp->TransactionId;
                    return ["status" => false, "return_type" => "failed", "message" => "Top to card failed."];
                }
                //dd($topUp);
            } else {

                $vistaMoneyApis = new VistaMoneyMerchantApis();
                $vistaMoneyApis->loadCard($transactionRequest->receiver_account_number, $transactionRequest->topup_local_ref, $transactionRequest->amount, "USD");
            }

            $trn->account_id = $transactionRequest->account_id;
            $trn->tr_request_id = $transactionRequest->id;
            $trn->currency_id = $transactionRequest->currency_id;
            $trn->transaction_type = $transactionRequest->transaction_type;
            $trn->transaction_nature = $transactionRequest->transaction_nature;
            $trn->transaction_date = $transactionRequest->transaction_date;
            $trn->mpin = $transactionRequest->receiver_account_number;
            $trn->topup_local_ref = $transactionRequest->topup_local_ref;
            $trn->amount = $transactionRequest->amount;
            $trn->remarks = $transactionRequest->remarks;
            $trn->status = "Approved";
            $trn->created_by = $transactionRequest->created_by;
            $trn->approved_by = Auth::user()->id;
            $trn->approved_at = Carbon::now();

            $trn->save();

            $transactionRequest->status = "Approved";
            $transactionRequest->save();

            // notify client
            EmailNotification::notifyClientForTransactionApproval($trn);
            return ["status" => true, "return_type" => "success", "message" => "Your card has been topped-up successfully"];
        } catch (Exception $e) {
            if ($e->getCode() == 1) {
                EmailNotification::notifyManagerForInsufficientFund($transactionRequest);
                return ["status" => true, "return_type" => "success", "message" => "Transaction created successfully"];
            }
            return ["status" => false];
        }
    }
}
