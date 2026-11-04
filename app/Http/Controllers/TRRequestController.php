<?php

namespace App\Http\Controllers;

use App\Helpers\ClientHelper;
use App\Helpers\Common;
use App\Helpers\EmailNotification;
use App\Helpers\FileFirewall;
use App\Helpers\IDORHelper;
use App\Helpers\S3Helper;
use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\ClientBeneficiary;
use App\Models\OTPCode;
use App\Models\TRRequest;
use App\Models\TRRequestDoc;
use App\Models\UserCode;
use App\Models\Currency;
use App\Rules\SupportingDocumentsRule;
use Carbon\Carbon;
use App\Helpers\NeroDataHelper;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TRRequestController extends Controller
{
    public function index($nid = "")
    {
        if ($nid) {
            Common::readNotification($nid);
        }

        $client_ids = Common::getClientIdsByUserId(Auth::user()->id);
        $accounts = Account::whereIn("client_id", $client_ids)->pluck("id")->toArray();

        $transactions = TRRequest::whereIn("account_id", $accounts)
            ->where("otp_verified", true)
            ->whereIn("status", ["Pending", "Approved", "Rejected", "Initial Review"])
            ->where("crypto_amount", null)
            ->whereIn("transaction_nature", ["Cheque", "TT", "Internal"])
            ->orderBy("updated_at", "DESC")
            ->get();

        return view("client-portal.transaction-request.list", compact('transactions'));
    }

    public function viewTransaction($id)
    {
        $transaction = TRRequest::find($id);

        try {
            $belongsToClient = IDORHelper::isAccountBelongToUser(Auth::user(), $transaction->account_id);
            $rulesText = '';
            $amlNoteTexts = collect();
            $neroDataTransactionId = 0;
            $portalTransactionsUrl = "";
            if (Common::isModuleEnabled('NeroData')) {
                $neroDataHelper = new NeroDataHelper();
                $rulesText = $neroDataHelper->getNeroDataLogText($id);


                $notesJson = DB::table('crypto_transactions_log')
                    ->where('transaction_id', $id)
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

                $neroDataTransactionId = $neroDataHelper->getNeroDataTransactionIdByTrId($id);
                $portalTransactionsUrl = $neroDataHelper->getPortalTransactionsUrl() . '/' . $neroDataTransactionId;
                //dd( $portalTransactionsUrl);
            }
            if ($belongsToClient) {
                return view("client-portal.transaction-request.view." . strtolower($transaction->transaction_nature), compact('transaction', 'rulesText',  'amlNoteTexts',  'portalTransactionsUrl'));
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

    public function deposit()
    {
        $client_ids = Common::getClientIdsByUserId(Auth::user()->id);
        $currencies = Currency::where("is_active", true)->where("is_crypto", false)->select("id")->get();
        $accounts = Account::where('is_active', true)->whereIn("client_id", $client_ids)->whereIn("account_currency", $currencies)->select("id", "title", "account_number")->get();

        return view("client-portal.transaction-request.new.tt", compact('accounts'));
    }

    public function selectTransferType()
    {
        //$client_ids = Common::getClientIdsByUserId(Auth::user()->id);
        //$currencies = Currency::where("is_active", true)->where("is_crypto", false)->select("id")->get();
        //$accounts = Account::where('is_active', true)->whereIn("client_id", $client_ids)->whereIn("account_currency", $currencies)->select("id", "title", "account_number")->get();

        return view("client-portal.transaction-request.select-transfer-type");
    }

    public function internalTransfer()
    {
        $client_ids = Common::getClientIdsByUserId(Auth::user()->id, true);
        $currencies = Currency::where("is_active", true)->where("is_crypto", false)->select("id")->get();
        $accounts = Account::where('is_active', true)->whereIn("client_id", $client_ids)->whereIn("account_currency", $currencies)->select("id", "title", "account_number")->get();

        return view("client-portal.transaction-request.new.internal", compact('accounts'));
    }

    // public function getToAccountsForExchange(Request $request)
    // {

    // }

    public function exchange()
    {
        $client_ids = Common::getClientIdsByUserId(Auth::user()->id);
        $currencies = Currency::where("is_active", true)->where("is_crypto", false)->select("id")->get();
        $accounts = Account::where('is_active', true)->whereIn("client_id", $client_ids)->whereIn("account_currency", $currencies)->select("id", "title", "account_number")->get();

        return view("client-portal.transaction-request.new.exchange", compact('accounts'));
    }

    public function newTransfer()
    {
        $client_ids = Common::getClientIdsByUserId(Auth::user()->id);
        $currencies = Currency::where("is_active", true)->where("is_crypto", false)->select("id")->get();
        $accounts = Account::where('is_active', true)->whereIn("client_id", $client_ids)->whereIn("account_currency", $currencies)->select("id", "title", "account_number")->get();
        $beneficiaries = ClientBeneficiary::where([["status", "=", "Approved"], ["user_id", "=", Auth::user()->id]])->get();

        return view("client-portal.transaction-request.new.transfer", compact('accounts', 'beneficiaries'));
    }

    public function storeTransfer(Request $request)
    {
        $this->validate($request, [
            'account_id' => 'required',
            'amount' => 'required|numeric|min:0',
            'transaction_date' => 'required',
            'remarks' => 'required',
            'beneficiary_id' => 'required',
            'supporting_documents' => [new SupportingDocumentsRule],
        ]);

        try {
            $belongsToClient = IDORHelper::isAccountBelongToUser(Auth::user(), $request->get("account_id"));

            if ($belongsToClient) {

                $beneficiary = ClientBeneficiary::find($request->get("beneficiary_id"));
                $belongsToClient = IDORHelper::isEntityBelongsToUser(Auth::user(), $beneficiary->user_id);

                if ($belongsToClient) {

                    $debit_account = Account::find($request->get("account_id"));
                    $client = ClientHelper::getClientByAccountId($request->get("account_id"));
                    if ($client->status == "Rejected") {
                        return redirect(url()->previous())->with('error', "You can't make transaction request as your account is rejected.");
                    }
                    // if account is not available alert it
                    if (!$debit_account) {
                        return redirect(url()->previous())->with('error', "Account details not found.");
                    }

                    if ($debit_account->is_active == 0) {
                        return redirect(url()->previous())->with('error', "Something went wrong, please try again later.");
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
                    $dt_trn->beneficiary_id = $request->get("beneficiary_id");
                    $dt_trn->currency_id = $debit_account->account_currency;
                    $dt_trn->transaction_type = "Debit";
                    $dt_trn->transaction_nature = "TT";
                    $dt_trn->transaction_date = Carbon::createFromFormat('d/m/Y', $request->get('transaction_date'))->format('Y-m-d H:i:s');
                    $dt_trn->amount = $request->get("amount");
                    $dt_trn->remarks = $request->get("remarks");

                    $dt_trn->corresponding_bank_name = $request->get("corresponding_bank_name");
                    $dt_trn->corresponding_bank_swift_code = $request->get("corresponding_bank_swift_code");
                    $dt_trn->corresponding_bank_address = $request->get("corresponding_bank_address");

                    $dt_trn->status = "Pending";
                    $dt_trn->created_by = Auth::user()->id;

                    $dt_trn->case_id = Common::generateUniqueString(10);

                    $dt_trn->save();

                    // upload supporting documents
                    Common::uploadSupportingDocuments($request, $dt_trn, $client);

                    // generate OTP code and send it to user
                    EmailNotification::RequestOTP($dt_trn);

                    return redirect("client/transaction-requests/otp/" . $dt_trn->id);
                }
            }
        } catch (Exception $e) {
            Common::logError($e, __METHOD__, __LINE__);

            if (401 === $e->getCode()) {
                return back()->with('error', 'You are not authorized to make this transaction.');
            } else {
                return back()->with('error', 'Something went wrong, please try again later.');
            }
        }
    }

    public function editTransaction($id)
    {
        $transaction = TRRequest::find($id);

        try {
            $belongsToClient = IDORHelper::isAccountBelongToUser(Auth::user(), $transaction->account_id);

            if ($belongsToClient) {

                if ('Approved' === $transaction->status) {
                    return back()->with('error', 'The transaction has already been approved!');
                }

                $client_ids = Common::getClientIdsByUserId(Auth::user()->id);

                $currencies = Currency::where("is_active", true)->where("is_crypto", false)->select("id")->get();
                $accounts = Account::whereIn("client_id", $client_ids)->whereIn("account_currency", $currencies)->where("is_active", true)->select("id", "title", "account_number")->get();

                $beneficiaries = null;
                $transactionNature = $transaction->transaction_nature;

                if ($transaction->beneficiary_id) {
                    $beneficiaries = ClientBeneficiary::where([["status", "=", "Approved"], ["user_id", "=", Auth::user()->id]])->get();
                    $transactionNature = 'transfer';
                }

                return view("client-portal.transaction-request.edit." . strtolower($transactionNature), compact('transaction', 'accounts', 'beneficiaries'));
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

    public function internalTransferStore(Request $request)
    {
        $client = ClientHelper::getClientByAccountId($request->get("debit_account_id"));
        if ($client->status == "Rejected") {
            return redirect(url()->previous())->with('error', "You can't make transaction request as your account is rejected.");
        }
        $this->validate($request, [
            'debit_account_id' => 'required',
            'amount' => 'required|numeric|min:0',
            'debit_remarks' => 'required',
            'supporting_documents' => [new SupportingDocumentsRule],
        ]);

        try {
            $belongsToClient = IDORHelper::isAccountBelongToUser(Auth::user(), $request->get("debit_account_id"));

            if ($belongsToClient) {
                if ($request->has('is_exchange')) {
                    $credit_account = Account::find($request->get('credit_account_id'));
                } else {
                    $credit_account = Account::where("account_number", $request->get("account_number"))->first();
                }

                $debit_account = Account::find($request->get("debit_account_id"));
                // $credit_account = Account::where("account_number", $request->get("account_number"))->first();

                if ($debit_account->is_active == 0) {
                    return redirect('/client/transaction-requests/internal/new')->with('error', "Something went wrong, please try again later.");
                }

                if ($credit_account->is_crypto == 1) {
                    return redirect('/client/transaction-requests/internal/new')->with('error', "You can't make transaction request as the receiver account is crypto.");
                }

                if ($credit_account->is_active == 0) {
                    return redirect('/client/transaction-requests/internal/new')->with('error', "You can't make this transaction request as the receiver account is inactive.");
                }

                // if account is not available alert it
                if (!$credit_account || !$debit_account) {
                    return redirect(url()->previous())->with('error', "Account details not found.");
                }

                // check if same account is selected for transaction
                if ($credit_account->id == $debit_account->id) {
                    return redirect(url()->previous())->with('error', "Please choose different accounts");
                }

                // check if the account belongs to the logged in user
                if (!Common::checkIfOwnAccount($debit_account)) {
                    return redirect(url()->previous())->with('error', "Please choose a valid account");
                }

                // check if debit account is having enough balance
                //todo: we need to check the balance as per the transaction date to check if there is a backdate entry and there is enough funds.
                $balance = Common::getAccountBalanceById($debit_account->id);

                if ($request->get("amount") > $balance) {
                    return redirect(url()->previous())->with('error', "You don't have enough money in your account");
                }

                // create client transaction
                $dt_trn = new TRRequest();

                // create debit transaction
                $dt_trn->account_id = $debit_account->id;
                $dt_trn->currency_id = $debit_account->account_currency;
                $dt_trn->transaction_type = "Debit";
                $dt_trn->transaction_nature = "Internal";
                $dt_trn->transaction_date = Carbon::today();
                $dt_trn->amount = $request->get("amount");
                $dt_trn->receiver_account_number = $credit_account->account_number;
                $dt_trn->remarks = $request->get("debit_remarks");
                $dt_trn->status = "Pending";
                $dt_trn->created_by = Auth::user()->id;

                $dt_trn->save();


                Common::uploadSupportingDocuments($request, $dt_trn, $debit_account);


                // generate OTP code and send it to user
                EmailNotification::RequestOTP($dt_trn);

                return redirect("client/transaction-requests/otp/" . $dt_trn->id);
            }
        } catch (Exception $e) {
            Common::logError($e, __METHOD__, __LINE__);

            if (401 === $e->getCode()) {
                return back()->with('error', 'You are not authorized to make this transaction.');
            } else {
                return back()->with('error', 'Something went wrong, please try again later.');
            }
        }
    }

    public function getAccountHolderName(Request $request)
    {
        $account = Account::where("account_number", $request->get("account_number"))
            ->where('is_active', 1)->first();
        // ->select("client_id", "title")->first();
        if ($account) {

            // {   Log::info($account->bankAccount->iban);
            return [
                "title" => $account->title,
                "account_id" => $account->id,
                "iban" => !empty($account->bankAccount->iban) ? $account->bankAccount->iban : null,
                "bic" => !empty($account->bankAccount->bic) ? $account->bankAccount->bic : null,
            ];
        } else {
            return ['error' => "Invalid account number"];
        }
        // if ($account) {
        //     return $account->client->parent_id ? "Invalid account number" : $account->title;
        // } else {
        //     return "Invalid account number";
        // }
    }

    private function isBelowToday($date)
    {
        $today = Carbon::today();
        $ndate = Carbon::createFromFormat('d/m/Y h:m:s', $date . " 00:00:00");

        return $today->gte($ndate);
    }

    public function depositStore(Request $request)
    {
        // validate input data
        $this->validate($request, [
            'account_id' => 'required',
            'amount' => 'required|numeric|min:0',
            'transaction_date' => 'required',
            'supporting_documents' => [new SupportingDocumentsRule],
            'remarks' => 'required'
        ]);

        try {
            $belongsToClient = IDORHelper::isAccountBelongToUser(Auth::user(), $request->get("account_id"));

            if ($belongsToClient) {

                $client = ClientHelper::getClientByAccountId($request->get("account_id"));
                if ($client->status == "Rejected") {
                    return redirect(url()->previous())->with('error', "You can't make transaction request as your account is rejected.");
                }
                $account = Account::find($request->get("account_id"));

                if ($account->is_active == 0) {
                    return redirect(url()->previous())->with('error', "Something went wrong, please try again later.");
                }

                // restrict future date
                if (!self::isBelowToday($request->get("transaction_date"))) {
                    return redirect(url()->previous())->with('error', "Please provide past or current date.");
                }

                // create transaction
                $trn = new TRRequest();

                $trn->account_id = $request->get("account_id");
                $trn->currency_id = $account->account_currency;
                $trn->transaction_type = "Credit";
                $trn->transaction_nature = "TT";
                $trn->transaction_date = Carbon::createFromFormat('d/m/Y', $request->get('transaction_date'))->format('Y-m-d H:i:s');
                $trn->amount = $request->get("amount");
                $trn->remarks = $request->get("remarks");
                $trn->status = "Initial Review";
                $trn->created_by = Auth::user()->id;

                $trn->save();

                Common::uploadSupportingDocuments($request, $trn, $client);

                // generate OTP code and send it to user
                EmailNotification::RequestOTP($trn);

                return redirect("client/transaction-requests/otp/" . $trn->id);
            }
        } catch (Exception $e) {
            Common::logError($e, __METHOD__, __LINE__);

            if (401 === $e->getCode()) {
                return back()->with('error', 'You are not authorized to make this transaction.');
            } else {
                return back()->with('error', 'Something went wrong, please try again later. ' . $e->getMessage());
            }
        }
    }

    public function updateDepositTransaction(Request $request)
    {
        $client = ClientHelper::getClientByAccountId($request->get("account_id"));
        $account = Account::find($request->get("account_id"));

        if ($client->status == "Rejected") {
            return redirect(url()->previous())->with('error', "You can't make transaction request as your account is rejected.");
        }

        if ($account->is_active == 0) {
            return redirect(url()->previous())->with('error', "Something went wrong, please try again later.");
        }

        // validate input data
        $this->validate($request, [
            'account_id' => 'required',
            'amount' => 'required|numeric|min:0',
            'transaction_date' => 'required',
            'remarks' => 'required',
            'supporting_documents' => [new SupportingDocumentsRule],
        ]);

        // restrict future date
        if (!self::isBelowToday($request->get("transaction_date"))) {
            return redirect(url()->previous())->with('error', "Please provide past or current date.");
        }

        try {
            $belongsToClient = IDORHelper::isAccountBelongToUser(Auth::user(), $request->get("account_id"));

            if ($belongsToClient) {

                // create transaction
                $trn = TRRequest::find($request->get("id"));

                $trn->account_id = $request->get("account_id");
                $trn->currency_id = $account->account_currency;
                $trn->amount = $request->get("amount");
                $trn->remarks = $request->get("remarks");
                $trn->status = "Initial Review";
                $trn->created_by = Auth::user()->id;

                $trn->save();

                Common::uploadSupportingDocuments($request, $trn, $client);

                // generate OTP code and send it to user
                EmailNotification::RequestOTP($trn);

                return redirect("client/transaction-requests/otp/" . $trn->id);
            }
        } catch (Exception $e) {
            Common::logError($e, __METHOD__, __LINE__);

            if (401 === $e->getCode()) {
                return back()->with('error', 'You are not authorized to update this transaction.');
            } else {
                return back()->with('error', 'Something went wrong, please try again later.');
            }
        }
    }

    public function updateInternalTransaction(Request $request)
    {
        $client = ClientHelper::getClientByAccountId($request->get("debit_account_id"));
        if ($client->status == "Rejected") {
            return redirect(url()->previous())->with('error', "You can't make transaction request as your account is rejected.");
        }
        $this->validate($request, [
            'debit_account_id' => 'required',
            'amount' => 'required|numeric|min:0',
            'debit_remarks' => 'required',
            'supporting_documents' => [new SupportingDocumentsRule],
        ]);

        try {
            $belongsToClient = IDORHelper::isAccountBelongToUser(Auth::user(), $request->get("debit_account_id"));

            if ($belongsToClient) {

                $debit_account = Account::find($request->get("debit_account_id"));
                $credit_account = Account::where("account_number", $request->get("account_number"))->first();

                if ($debit_account->is_active == 0) {
                    return redirect(url()->previous())->with('error', "Something went wrong, please try again later.");
                }

                if ($credit_account->is_crypto == 1) {
                    return redirect(url()->previous())->with('error', "You can't make transaction request as the receiver account is crypto.");
                }

                if ($credit_account->is_active == 0) {
                    return redirect(url()->previous())->with('error', "You can't make this transaction request as the receiver account is inactive.");
                }

                // if account is not available alert it
                if (!$credit_account || !$debit_account) {
                    return redirect(url()->previous())->with('error', "Account details not found.");
                }

                // check if same account is selected for transaction
                if ($credit_account->id == $debit_account->id) {
                    return redirect(url()->previous())->with('error', "Please choose different accounts");
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

                // create debit transaction
                $dt_trn->account_id = $debit_account->id;
                $dt_trn->currency_id = $debit_account->account_currency;
                // $dt_trn->transaction_date = Carbon::today();
                $dt_trn->amount = $request->get("amount");
                $dt_trn->receiver_account_number = $request->get("account_number");
                $dt_trn->remarks = $request->get("debit_remarks");
                $dt_trn->status = "Pending";
                $dt_trn->created_by = Auth::user()->id;

                $dt_trn->save();

                Common::uploadSupportingDocuments($request, $dt_trn, $debit_account);


                // generate OTP code and send it to user
                EmailNotification::RequestOTP($dt_trn);

                return redirect("client/transaction-requests/otp/" . $dt_trn->id);
            }
        } catch (Exception $e) {
            Common::logError($e, __METHOD__, __LINE__);

            if (401 === $e->getCode()) {
                return back()->with('error', 'You are not authorized to update this transaction.');
            } else {
                return back()->with('error', 'Something went wrong, please try again later.');
            }
        }
    }

    public function updateTransferTransaction(Request $request)
    {
        $this->validate($request, [
            'account_id' => 'required',
            'amount' => 'required|numeric|min:0',
            'transaction_date' => 'required',
            'remarks' => 'required',
            'beneficiary_id' => 'required',
            'supporting_documents' => [new SupportingDocumentsRule],

        ]);

        try {
            $belongsToClient = IDORHelper::isAccountBelongToUser(Auth::user(), $request->get("account_id"));

            if ($belongsToClient) {

                $debit_account = Account::find($request->get("account_id"));
                $client = ClientHelper::getClientByAccountId($request->get("account_id"));
                if ($client->status == "Rejected") {
                    return redirect(url()->previous())->with('error', "You can't make transaction request as your account is rejected.");
                }
                // if account is not available alert it
                if (!$debit_account) {
                    return redirect(url()->previous())->with('error', "Account details not found.");
                }

                if ($debit_account->is_active == 0) {
                    return redirect(url()->previous())->with('error', "Something went wrong, please try again later.");
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
                $dt_trn = TRRequest::find($request->get("id"));

                $dt_trn->account_id = $debit_account->id;
                $dt_trn->beneficiary_id = $request->get("beneficiary_id");
                $dt_trn->currency_id = $debit_account->account_currency;
                $dt_trn->amount = $request->get("amount");
                $dt_trn->remarks = $request->get("remarks");

                $dt_trn->corresponding_bank_name = $request->get("corresponding_bank_name");
                $dt_trn->corresponding_bank_swift_code = $request->get("corresponding_bank_swift_code");
                $dt_trn->corresponding_bank_address = $request->get("corresponding_bank_address");

                $dt_trn->status = "Pending";
                $dt_trn->created_by = Auth::user()->id;

                $dt_trn->save();


                // upload supporting documents
                Common::uploadSupportingDocuments($request, $dt_trn, $client);

                // generate OTP code and send it to user
                EmailNotification::RequestOTP($dt_trn);

                return redirect("client/transaction-requests/otp/" . $dt_trn->id);
            }
        } catch (Exception $e) {
            Common::logError($e, __METHOD__, __LINE__);

            if (401 === $e->getCode()) {
                return back()->with('error', 'You are not authorized to update this transaction.');
            } else {
                return back()->with('error', 'Something went wrong, please try again later.');
            }
        }
    }
}
