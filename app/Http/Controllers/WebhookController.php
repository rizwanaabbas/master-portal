<?php

namespace App\Http\Controllers;

use App\Helpers\OpenpaydApis;
use App\Models\VirtualBankAccount;
use App\Models\VirtualBankTransaction;
use App\Models\TransactionMethod;
use Illuminate\Http\Request;
use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\Currency;
use App\Models\TRRequest;
use App\Models\FingateClient;
use App\Models\KycRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Helpers\BitpaceMerchantApis;
use App\Helpers\ClientHelper;
use App\Helpers\Common;
use App\Helpers\FeeHelper;
use App\Helpers\NeroDataHelper;
use App\Helpers\FingateHelper;
use Illuminate\Support\Facades\DB;
use App\Helpers\EmailNotification;
use App\Helpers\LorumApis;
use App\Helpers\ZenusApis;
use App\Helpers\S3Helper;
use App\Models\Client;
use App\Models\ClientCompany;
use App\Models\ClientCompanyContact;
use App\Models\ClientCompanyFile;
use App\Models\ClientDirector;
use App\Models\ClientFileConfig;
use App\Models\ClientIndividual;
use App\Models\ClientIndividualEmployer;
use App\Models\ClientIndividualFile;
use App\Models\ClientKYC;
use App\Models\CompanyType;
use App\Models\Country;
use App\Models\Module;
use App\Models\ModuleConfiguration;
use App\Models\RiskScoreMatrix;
use Illuminate\Support\Facades\Auth;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;
use App\Rules\SupportingDocumentsRule;
use Illuminate\Support\Facades\Validator;

class WebhookController extends Controller
{
    private const STATUS_MAP = [

        // Lorum specific status mapping
        'created'   => 'Initiated',
        'submitted' => 'Pending',
        'executed'  => 'Approved',
        'settled'   => 'Approved',
        'failed'    => 'Failed',
        'returned'  => 'Rejected',

        // Zenus specific status mapping
        'PROCESSED'                     => 'Approved',
        'WAITING_FOR_EXTERNAL_PROVIDER' => 'Pending',
        'AML_REVIEW'                    => 'Pending',
        'AML_SUBPROCESS_CREATED'        => 'Pending',
        'AML_DECLINED'                  => 'Rejected',
        'CANCELLED'                     => 'Rejected',
    ];

    private const STAGE_PRIORITY = [
        'created'   => 1,
        'submitted' => 2,
        'executed'  => 3,
        'settled'   => 4,
        'failed'    => 99,
    ];

    private const STATUS_PRIORITY = [
        'Initiated' => 1,
        'Pending'   => 2,
        'Approved'  => 3,
        'Failed'    => 99,
    ];

    private string | null $selected_payment_method = null;

    public function musdBuyCallback(Request $request)
    {
        Log::info("Tonce: " . $request->header('Tonce'));
        Log::info("Signature: " . $request->header('Signature'));
        // Log::info("Headers: " . print_r($request->header(), true));
        Log::info("IP Address: " . print_r($request->ip(), true));
        Log::info("Request: " . print_r($request->all(), true));




        $validation = validator::make($request->all(), [
            'account_id' => 'required|exists:accounts,id',
            'amount' => 'numeric|gt:0',
            'remarks' => 'nullable|string|max:255',
            // 'supporting_documents' => [new SupportingDocumentsRule]
        ]);

        if ($validation->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validation->errors()
            ], 422);
        }

        $credit_account = Account::whereHas('bank', function ($query) {
            $query->where('is_reserve_bank', true);
        })->first();

        $debit_account = Account::find($request->get("account_id"));
        $client = ClientHelper::getClientByAccountId($request->get("account_id"));
        $amount = $request->get("amount");

        if ($debit_account->is_active == 0) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => ['account_id' => ['The selected account is inactive.']]
            ], 422);
        }

        $balance = Common::getAccountBalanceById($request->get("account_id"));

        if ($balance < $amount) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => ['amount' => ['Insufficient balance.']]
            ], 422);
        }

        // get db date
        $date = Carbon::now()->format('Y-m-d H:i:s');

        DB::beginTransaction();

        try {
            // create client transaction
            $dt_trn_request = new TRRequest();

            if ($request->get('credit_currency_id') != $request->get('debit_currency_id')) {
                $is_exchange = 1;
            } else {
                $is_exchange = 0;
            }

            // create debit transaction
            $dt_trn_request->idempotency_key = $request->header('Idempotency-Key');
            $dt_trn_request->account_id = $debit_account->id;
            $dt_trn_request->currency_id = $debit_account->account_currency;
            $dt_trn_request->transaction_type = "Debit";
            $dt_trn_request->transaction_nature = "MUSD Reserve";
            $dt_trn_request->transaction_date = $date;
            $dt_trn_request->amount = $amount;
            $dt_trn_request->ex_rate = 1;
            $dt_trn_request->receiver_account_number = $credit_account->account_number;
            $dt_trn_request->remarks = $request->get("remarks");
            $dt_trn_request->status = "Approved";
            $dt_trn_request->is_exchange = $is_exchange;

            $userIdWithAPIRole = User::whereHas('roles', function ($query) {
                    $query->where('name', 'API');
                })
                    ->whereHas('linkClients', function ($query) use ($client) {
                        $query->where('client_id', $client->id);
                    })
                    ->first()->id;
              
            $dt_trn_request->created_by = $userIdWithAPIRole;
            $dt_trn_request->approved_by = 0;
            $dt_trn_request->approved_at = Carbon::now();

            $dt_trn_request->save();

            $data = [
                'trn_id' => $dt_trn_request->id,
                'zeroize' => null,
                'amount' => $amount,
                'ex_rate' => 1,
                'transaction_date' => $date,
                'remarks' => $request->get("remarks"),
                'service_fee_client_account' => $credit_account->id,
                'service_fee_bank_account_id' => Common::getBankAccountIdByCurrencyId($credit_account->account_currency, 'is_service_fee_bank'),
                'service_fee_currency_id' => $credit_account->account_currency,
                'service_fee' => 0,
                'service_fee_remarks' => $request->get("remarks"),
                'is_service_fee_trn_hidden_for_client' => ($credit_account->account_currency != $debit_account->account_currency ? true : false),
                'trn_fee_client_account' => $credit_account->id,
                'trn_fee_bank_account_id' => Common::getBankAccountIdByCurrencyId($credit_account->account_currency, 'is_trn_fee_bank'),
                'trn_fee_currency_id' => $credit_account->account_currency,
                'trn_fee' => 0,
                'trn_fee_remarks' => $request->get("remarks"),
                'is_trn_fee_trn_hidden_for_client' => null
            ];

            Common::saveTransactionDetails($data, $dt_trn_request);
            // Common::uploadSupportingDocuments($request, $dt_trn_request, $client);

            // create client transaction
            $cr_trn = new AccountTransaction();

            // create credit transaction
            $cr_trn->account_id = $credit_account->id;
            $cr_trn->tr_request_id = $dt_trn_request->id;
            $cr_trn->currency_id = $credit_account->account_currency;
            $cr_trn->transaction_type = "Credit";
            $cr_trn->transaction_nature = "MUSD Reserve";
            $cr_trn->transaction_date = $date;
            $cr_trn->amount = $amount;
            $cr_trn->ex_rate = 1;
            $cr_trn->remarks = $request->get("remarks");
            $cr_trn->status = "Approved";
            $cr_trn->created_by = $userIdWithAPIRole;
            $cr_trn->approved_by = 0;
            $cr_trn->approved_at = Carbon::now();

            $cr_trn->save();

            // add 1 minute to debit transaction to avoid same time entry
            $datetime = Carbon::parse($cr_trn->transaction_date)->addMinutes(1)->format("Y-m-d H:i:s");

            // create client transaction
            $client_trn = new AccountTransaction();

            // create debit transaction
            $client_trn->account_id = $debit_account->id;
            $client_trn->link_tr_id = $cr_trn->id;
            $client_trn->tr_request_id = $dt_trn_request->id;
            $client_trn->currency_id = $debit_account->account_currency;
            $client_trn->transaction_type = "Debit";
            $client_trn->transaction_nature = "MUSD Reserve";
            $client_trn->transaction_date = $datetime;
            $client_trn->amount = $amount;
            $client_trn->ex_rate = 1;
            $client_trn->remarks = $request->get("remarks");
            $client_trn->status = "Approved";
            $client_trn->created_by = $userIdWithAPIRole;
            $client_trn->approved_by = 0;
            $client_trn->approved_at = Carbon::now();

            $client_trn->save();

            $cr_trn->link_tr_id = $client_trn->id;
            $cr_trn->save();

            $dt_trn_request->link_tr_id = $client_trn->id;

            $dt_trn_request->save();

            // Common::notifyComplianceTeamNeroData($dt_trn_request);

            // Log::info("\n --------------------------------------TRRequest----------------------------------------- \n");
            // Log::info(print_r($dt_trn_request, true));
            // Log::info("\n\n\n -------------------------------------Credit Transaction------------------------------------------ \n");
            // Log::info(print_r($cr_trn, true));
            // Log::info("\n\n\n -------------------------------------Debit Transaction------------------------------------------ \n");
            // Log::info(print_r($client_trn, true));

            // generate email notification to line manager
            // EmailNotification::notifyTransactionCreation($client_trn);

            $response_data = Common::generateResponse(
                $request,
                'TRRequest',
                [
                    'success' => true,
                    'trn_id' => $client_trn->id
                ],
                'Successfully sweeped the amount to the reserves.'
            );

            DB::commit();

            return response()->json($response_data, 200);
        } catch (Exception $e) {
            Log::error('------------------------------------------------');
            Log::error($e->getMessage() . ' | Line: ' . $e->getLine());
            Log::error($e->getTraceAsString());
            Log::error('------------------------------------------------');

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Unknown Error',
                'error' => 'Internal Server Error, please try again later or contact the Tech team',
                'data' => [
                    'account_id' => $request->get("account_id"),
                    'amount' => $request->get("amount"),
                    'remarks' => $request->get("remarks"),
                ]
            ], config("api.httpCodes.exception"));
        }
    }

    public function musdSellCallback(Request $request)
    {
        Log::info("IP Address: " . print_r($request->ip(), true));
        // Log::info("Request: " . print_r($request, true));

        $validation = validator::make($request->all(), [
            'account_id' => 'required|exists:accounts,id',
            'beneficiary_id' => 'required|exists:client_beneficiaries,id',
            'amount' => 'numeric|gt:0',
            'remarks' => 'nullable|string|max:255',
            // 'supporting_documents' => [new SupportingDocumentsRule]
        ]);

        if ($validation->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validation->errors()
            ], 422);
        }

        $debit_account = Account::whereHas('bank', function ($query) {
            $query->where('is_reserve_bank', true);
        })->first();

        $credit_account = Account::find($request->get("account_id"));
        $client = ClientHelper::getClientByAccountId($request->get("account_id"));
        $amount = $request->get("amount");

        if ($credit_account->is_active == 0) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => ['account_id' => ['The selected account is inactive.']]
            ], 422);
        }

        $accountBalance = Common::getAccountBalanceById($request->get("account_id"));
        $reservesBalance = Common::getAccountBalanceInReserves($request->get("account_id"), $debit_account->id);

        if ($reservesBalance < $amount) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => [
                    'amount' => ['Insufficient client balance in reserves.'],
                    'account_balance' => $accountBalance,
                    'balance_in_reserves' => $reservesBalance
                ]
            ], 422);
        }

        // get db date
        $date = Carbon::now()->format('Y-m-d H:i:s');

        DB::beginTransaction();

        try {
            // create client transaction
            $dt_trn_request = new TRRequest();

            if ($request->get('credit_currency_id') != $request->get('debit_currency_id')) {
                $is_exchange = 1;
            } else {
                $is_exchange = 0;
            }

            // create debit transaction
            $dt_trn_request->idempotency_key = $request->header('Idempotency-Key');
            $dt_trn_request->account_id = $debit_account->id;
            $dt_trn_request->currency_id = $debit_account->account_currency;
            $dt_trn_request->transaction_type = "Debit";
            $dt_trn_request->transaction_nature = "MUSD Reserve";
            $dt_trn_request->transaction_date = $date;
            $dt_trn_request->amount = $amount;
            $dt_trn_request->ex_rate = 1;
            $dt_trn_request->receiver_account_number = $credit_account->account_number;
            $dt_trn_request->remarks = $request->get("remarks");
            $dt_trn_request->status = "Approved";
            $dt_trn_request->is_exchange = $is_exchange;

            $userIdWithAPIRole = User::whereHas('roles', function ($query) {
                    $query->where('name', 'API');
                })
                    ->whereHas('linkClients', function ($query) use ($client) {
                        $query->where('client_id', $client->id);
                    })
                    ->first()->id;
                            
            $dt_trn_request->created_by = $userIdWithAPIRole;
            $dt_trn_request->approved_by = 0;
            $dt_trn_request->approved_at = $date;

            $dt_trn_request->save();

            $data = [
                'trn_id' => $dt_trn_request->id,
                'zeroize' => null,
                'amount' => $amount,
                'ex_rate' => 1,
                'transaction_date' => $date,
                'remarks' => $request->get("remarks"),
                'service_fee_client_account' => $credit_account->id,
                'service_fee_bank_account_id' => Common::getBankAccountIdByCurrencyId($credit_account->account_currency, 'is_service_fee_bank'),
                'service_fee_currency_id' => $credit_account->account_currency,
                'service_fee' => 0,
                'service_fee_remarks' => $request->get("remarks"),
                'is_service_fee_trn_hidden_for_client' => ($credit_account->account_currency != $debit_account->account_currency ? true : false),
                'trn_fee_client_account' => $credit_account->id,
                'trn_fee_bank_account_id' => Common::getBankAccountIdByCurrencyId($credit_account->account_currency, 'is_trn_fee_bank'),
                'trn_fee_currency_id' => $credit_account->account_currency,
                'trn_fee' => 0,
                'trn_fee_remarks' => $request->get("remarks"),
                'is_trn_fee_trn_hidden_for_client' => null
            ];

            Common::saveTransactionDetails($data, $dt_trn_request);

            // Common::uploadSupportingDocuments($request, $dt_trn_request, $client);

            // create client transaction
            $cr_trn = new AccountTransaction();

            // create credit transaction
            $cr_trn->account_id = $credit_account->id;
            $cr_trn->tr_request_id = $dt_trn_request->id;
            $cr_trn->currency_id = $credit_account->account_currency;
            $cr_trn->transaction_type = "Credit";
            $cr_trn->transaction_nature = "MUSD Reserve";
            $cr_trn->transaction_date = $date;
            $cr_trn->amount = $amount;
            $cr_trn->ex_rate = 1;
            $cr_trn->remarks = $request->get("remarks");
            $cr_trn->status = "Approved";
            $cr_trn->created_by = $userIdWithAPIRole;
            $cr_trn->approved_by = 0;
            $cr_trn->approved_at = $date;

            $cr_trn->save();

            // add 1 minute to debit transaction to avoid same time entry
            $datetime = Carbon::parse($cr_trn->transaction_date)->addMinutes(1)->format("Y-m-d H:i:s");

            // create client transaction
            $client_trn = new AccountTransaction();

            // create debit transaction
            $client_trn->account_id = $debit_account->id;
            $client_trn->link_tr_id = $cr_trn->id;
            $client_trn->tr_request_id = $dt_trn_request->id;
            $client_trn->currency_id = $debit_account->account_currency;
            $client_trn->transaction_type = "Debit";
            $client_trn->transaction_nature = "MUSD Reserve";
            $client_trn->transaction_date = $datetime;
            $client_trn->amount = $amount;
            $client_trn->ex_rate = 1;
            $client_trn->remarks = $request->get("remarks");
            $client_trn->status = "Approved";
            $client_trn->created_by = $userIdWithAPIRole;
            $client_trn->approved_by = 0;
            $client_trn->approved_at = $date;

            $client_trn->save();

            $cr_trn->link_tr_id = $client_trn->id;
            $cr_trn->save();

            $dt_trn_request->link_tr_id = $client_trn->id;

            $dt_trn_request->save();

            // Common::notifyComplianceTeamNeroData($dt_trn_request);

            // Log::info("\n --------------------------------------TRRequest----------------------------------------- \n");
            // Log::info(print_r($dt_trn_request, true));
            // Log::info("\n\n\n -------------------------------------Credit Transaction------------------------------------------ \n");
            // Log::info(print_r($cr_trn, true));
            // Log::info("\n\n\n -------------------------------------Debit Transaction------------------------------------------ \n");
            // Log::info(print_r($client_trn, true));

            // generate email notification to line manager
            // EmailNotification::notifyTransactionCreation($client_trn);

            /**
             * Transfer the amount to the provided beneficiary bank account.
             */

            $result = self::transferToBeneficiaryBankAccount($request, $date, $userIdWithAPIRole);

            if ($result["success"] == false) {
                throw new Exception("Error creating the Transfer transaction.");
            } else {
                $response_data = Common::generateResponse(
                    $request,
                    'TRRequest',
                    [
                        'success' => true,
                        'trn_id' => $result['trn_id']
                    ],
                    'Successfully created a Transfer Transaction Request.'
                );
    
                DB::commit();
    
                return response()->json($response_data, 200);
            }
        } catch (Exception $e) {
            Log::error('------------------------------------------------');
            Log::error($e->getMessage() . ' | Line: ' . $e->getLine());
            Log::error($e->getTraceAsString());
            Log::error('------------------------------------------------');

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Unknown Error',
                'error' => 'Internal Server Error, please try again later or contact the Tech team',
                'data' => [
                    'account_id' => $request->get("account_id"),
                    'amount' => $request->get("amount"),
                    'remarks' => $request->get("remarks"),
                ]
            ], config("api.httpCodes.exception"));
        }
    }

    private static function transferToBeneficiaryBankAccount($request, $date, $userIdWithAPIRole)
    {
        try {
            $account = Account::find($request->get("account_id"));

            $trn_request = new TRRequest();

            $trn_request->account_id = $request->get("account_id");
            $trn_request->currency_id = $account->account_currency;
            $trn_request->transaction_type = "Debit";
            $trn_request->transaction_nature = "TT";
            $trn_request->transaction_date = $date;
            $trn_request->amount = $request->get("amount");
            $trn_request->remarks = $request->get("remarks");
            $trn_request->status = "Pending";
            $trn_request->created_by = $userIdWithAPIRole;
            $trn_request->beneficiary_id = $request->get('beneficiary_id');

            $trn_request->save();

            $fees = self::calculateTransactionFees($account->currency, $request->get("amount"));

            $data = [
                'trn_id' => $trn_request->id,
                "amount" => isset($request['amount']) ? $request['amount'] : null,
                "transaction_date" => $trn_request->transaction_date,
                "remarks" => isset($request['remarks']) ? $request['remarks'] : null,
                'zeroize' => null,
                'service_fee_client_account' => $request->get("account_id"),
                'service_fee_bank_account_id' => Common::getBankAccountIdByCurrencyId($account->account_currency, 'is_service_fee_bank'),
                'service_fee_currency_id' => $account->account_currency,
                'service_fee' => $fees['bank_fee_amount'],
                'service_fee_remarks' => $request->get('remarks'),
                'is_service_fee_trn_hidden_for_client' => null,
                'trn_fee_client_account' => $request->get("account_id"),
                'trn_fee_bank_account_id' => Common::getBankAccountIdByCurrencyId($account->account_currency, 'is_trn_fee_bank'),
                'trn_fee_currency_id' => $account->account_currency,
                'trn_fee' => $fees['transaction_fee_amount'],
                'trn_fee_remarks' => $request->get('remarks'),
                'is_trn_fee_trn_hidden_for_client' => null
            ];

            Common::saveTransactionDetails($data, $trn_request);

            // create client transaction
            $trn = new AccountTransaction();

            $trn->account_id = $request->get("account_id");
            $trn->tr_request_id = $trn_request->id;
            $trn->currency_id = $account->account_currency;
            $trn->transaction_type = "Debit";
            $trn->transaction_nature = "TT";
            $trn->transaction_date = $date;
            $trn->amount = $request->get("amount");
            $trn->remarks = $request->get("remarks");
            $trn->status = "Pending";
            $trn->created_by = $userIdWithAPIRole;
            $trn->beneficiary_id = $request->get('beneficiary_id');

            $trn->save();

            $trn_request->link_tr_id = $trn->id;
            $trn_request->save();

            EmailNotification::notifyFinanceManagersForTRRequest($trn_request, "transfer");

            return [
                'success' => true,
                'trn_id' => $trn->id
            ];
        } catch (Exception $e) {

            Log::info("Error thrown | Message: " . $e->getMessage() . " | Line: " . __LINE__);

            return ['success' => false];
        }
    }

    public static function calculateTransactionFees($currency, $amount)
    {
        $transaction_fee_rate = $currency->transaction_fee_rate ?? 0;
        $bank_fee_rate = $currency->bank_fee_rate ?? 0;

        // calculate the fee
        $transaction_amount = $amount;
        $transaction_fee_amount = ($transaction_amount * $transaction_fee_rate) / 100;
        $bank_fee_amount = ($transaction_amount * $bank_fee_rate) / 100;

        // Currency Treshold fees
        $transaction_fee_threshold = $currency->transaction_fee_threshold ?? 0;
        $bank_fee_threshold = $currency->bank_fee_threshold ?? 0;

        // Charge calculated fee if it exceeds currency_fee_threshold; otherwise, charge the threshold.
        if ($transaction_fee_amount < $transaction_fee_threshold) {
            $transaction_fee_amount = $transaction_fee_threshold;
        }

        // Charge calculated fee if it exceeds currency_fee_threshold; otherwise, charge the threshold.
        if ($transaction_fee_amount < $transaction_fee_threshold) {
            $transaction_fee_amount = $transaction_fee_threshold;
        }

        if ($bank_fee_amount < $bank_fee_threshold) {
            $bank_fee_amount = $bank_fee_threshold;
        }

        return [
            'transaction_fee_amount' => round($transaction_fee_amount, 2),
            'bank_fee_amount' => round($bank_fee_amount, 2),
            'total_fee_amount' => round($transaction_fee_amount + $bank_fee_amount, 2)
        ];
    }

    public function zenusCallback(Request $request)
    {
        // Need to add web-hook validation.
        if (!$this->validateZenusWebhook($request)) {
            return response()->json(['error' => 'Unauthorized source'], 403);
        }

        $account_webhooks = [
            'ACCOUNT_ACTIVATED',
            'ACCOUNT_BLOCKED',
            'ACCOUNT_CLOSED',
            'ACCOUNT_NUMBER_CREATED',
        ];

        $payment_webhooks = [
            'PAYMENT_PROCESSED',
            'AML_SUBPROCESS_CREATED',
            'PAYMENT_AML_DECLINED',
            'PAYMENT_CANCELLED',
            'PAYMENT_SETTLED',
            'ACCOUNT_TRANSACTION_CREATED_V3',
        ];

        try {

            $rawPayload = $request->getContent();

            if (!empty($rawPayload)) {
                // $payload = json_decode($rawPayload);
                $payload = json_decode($rawPayload, true);
                $payload = $this->formatZenusWebhookData($payload);
                $webhook_data = $payload->data;

                if (!empty($payload->notificationTypeCode) && $payload->notificationTypeCode === "ACCOUNT_NUMBER_CREATED") {
                    return $this->processZenusAccountNumberCreatedWebhook($payload);
                }

                if (!empty($payload->notificationTypeCode) && in_array($payload->notificationTypeCode, $account_webhooks)) {
                    return $this->processZenusAccountWebhook($payload->data);
                }


                if (!empty($payload->notificationTypeCode) && in_array($payload->notificationTypeCode, $payment_webhooks)) {
                    return $this->processZenusTransaction($webhook_data);
                }
            }

            Log::channel('zenusLogs')->warning('Webhook Processed: ' . $webhook_data?->notificationTypeCode ?? "Unknown Status");

            return response()->json(['success' => 'webhook received'], 200);
        } catch (Exception $e) {
            Log::error("Transaction Error: " . $e->getMessage(), ['line' => $e->getLine(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['failed' => 'Webhook processing error.'], 400);
        }
    }

    public function sumsubCallback(Request $request)
    {
        $module = Module::where('name', 'Sumsub')->first();
        $moduleConfig = ModuleConfiguration::where('module_id', $module->id)->first();

        if (!$moduleConfig) {
            Log::error('Sumsub configuration missing');
            return response()->json(['error' => 'Configuration missing'], 500);
        }

        $sumsubConfig = unserialize($moduleConfig->configurations);
        $sumsubWebhookSecret = $sumsubConfig['sumsub_webhook_secret']['value'] ?? env('SUMSUB_WEBHOOK_SECRET');

        $webhookData = file_get_contents("php://input");
        $headers = getallheaders();

        $data = json_decode(
            str_replace(['\n', '\"'], ["\n", '"'], trim($webhookData, '"')),
            true
        );

        Log::info("\n\n\n =====================================================\n\n");
        Log::info('Sumsub callback Webhook Headers:', $headers);
        Log::info("\n\n\n =====================================================\n\n");
        Log::info('Sumsub callback Webhook Data: ' . print_r($data, true));
        Log::info("\n\n\n =====================================================\n\n");

        if (!empty($webhookData)) {
            $algoStr = $_SERVER['HTTP_X_PAYLOAD_DIGEST_ALG'];
            $digest = $_SERVER['HTTP_X_PAYLOAD_DIGEST'];

            $algo = match ($algoStr) {
                'HMAC_SHA1_HEX' => 'sha1',
                'HMAC_SHA256_HEX' => 'sha256',
                'HMAC_SHA512_HEX' => 'sha512',
                default => throw new \RuntimeException('Unsupported algorithm'),
            };

            $res = $digest === hash_hmac(
                $algo,
                $webhookData,
                $sumsubWebhookSecret
            );

            if (!$res) {
                Log::warning('Sumsub webhook signature verification failed', ['headers' => $headers]);
                return response()->json(['failed' => 'Invalid signature'], 400);
            }

            $webhookType = $data['type'] ?? null;
            $applicantId = $data['applicantId'] ?? null;
            $webhookClientId = $data['externalUserId'] ?? null;
            $isBeneficiary = isset($data['applicantMemberOf']);
            $levelName = $data['levelName'] ?? null;
            $client = Client::find($webhookClientId);

            $isQuestionnaireOnly = in_array($levelName, ['individual-questionnaire-only', 'company-questionnaire-only']);

            if (!empty($applicantId)) {
                $this->storeWebhookData($applicantId, json_encode($data), 'Sumsub');
            }

            if ($webhookType === 'applicantStepsReset' || $webhookType === 'applicantLevelChanged') {
                if ($applicantId) {

                    // Default to clearing both if it's a general reset
                    $clearPassport = false;
                    $clearSelfie = false;

                    if ($levelName === 'passport-link') {
                        $clearPassport = true; // Only clear passport
                    } elseif ($levelName === 'liveness-link') {
                        $clearSelfie = true; // Only clear selfie
                    } elseif ($levelName === 'passport-n-liveness-link') {
                        $clearPassport = true;
                        $clearSelfie = true;
                    } elseif ($isQuestionnaireOnly) {
                        // DO NOT clear ID or Selfie if we are only asking for a questionnaire reset
                        $clearPassport = false;
                        $clearSelfie = false;
                    }

                    $docsToDeactivate = [];
                    if ($clearPassport) {
                        $docsToDeactivate = array_merge($docsToDeactivate, ['PASSPORT', 'ID_CARD']);
                    }
                    if ($clearSelfie) {
                        $docsToDeactivate[] = 'SELFIE';
                    }

                    $envPrefix = app()->environment('production') ? 'production_' : 'sandbox_';
                    $appToken  = $sumsubConfig[$envPrefix . 'app_token']['value'] ?? '';
                    $secretKey = $sumsubConfig[$envPrefix . 'secret_key']['value'] ?? '';
                    $baseUrl   = rtrim($sumsubConfig['base_url']['value'] ?? 'https://api.sumsub.com', '/');

                    $urlPath = '/resources/applicants/' . $applicantId . '/metadata/resources';
                    $ts = time();
                    $signature = hash_hmac('sha256', $ts . 'GET' . $urlPath, $secretKey);

                    try {
                        $response = Http::withHeaders([
                            'Accept' => 'application/json',
                            'X-App-Token' => $appToken,
                            'X-App-Access-Sig' => $signature,
                            'X-App-Access-Ts' => $ts,
                        ])->get($baseUrl . $urlPath);

                        if ($response->successful()) {
                            $docMetaData = $response->json()['items'] ?? [];
                            foreach ($docMetaData as $doc) {
                                $imgId = $doc['id'] ?? null;
                                $docType = $doc['idDocDef']['idDocType'] ?? null;

                                // Check if Sumsub already considers this image deactivated
                                $isDeactivated = !empty($doc['deactivated']) && $doc['deactivated'] === true;

                                // deactivate if it matches our target types and is currently active
                                if ($imgId && !$isDeactivated && in_array($docType, $docsToDeactivate)) {
                                    $this->markImageInactive($applicantId, $imgId, $sumsubConfig, false);
                                    Log::info("Deactivated {$docType} (Image ID: {$imgId}) for Applicant ID: {$applicantId}");
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        Log::error("Exception fetching metadata for inactivity reset: " . $e->getMessage());
                    }

                    // --- 3. WIPE LOCAL DATABASE RECORDS ---
                    $clientKYC = ClientKYC::where('sumsub_applicant_id', $applicantId)->first();
                    if ($clientKYC) {
                        $clientId = $clientKYC->client_id;
                        $clientKYC->sumsub_kyc_status = 'init'; // Reset status
                        $clientKYC->sumsub_kyc_answer = null;

                        // Selectively clear local URLs
                        if ($clearPassport) {
                            $clientKYC->passport_copy_url = null;
                        }
                        if ($clearSelfie) {
                            $clientKYC->selfie_photo_url = null;
                        }
                        $clientKYC->save();

                        // Selectively clear related generic files (if we are clearing passports)
                        if ($clearPassport) {
                            $client = Client::find($clientId);
                            if ($client) {
                                if ($client->type === 'individual') {
                                    $config = ClientFileConfig::individual()->where('identifier', 'passport_copy')->first();
                                    if ($config) {
                                        ClientIndividualFile::where('client_id', $clientId)
                                            ->where('file_id', $config->id)
                                            ->delete();
                                    }
                                } else {
                                    $config = ClientFileConfig::company()->where('identifier', 'passport_copy')->first();
                                    if ($config) {
                                        $query = ClientCompanyFile::where('client_id', $clientId)
                                            ->where('file_id', $config->id);

                                        if (!empty($clientKYC->director_id)) {
                                            $query->where('director_id', $clientKYC->director_id);
                                        }
                                        $query->delete();
                                    }
                                }
                            }
                        }

                        Log::info("Reset local KYC state. Clear Passport: " . ($clearPassport ? 'Yes' : 'No') . " | Clear Selfie: " . ($clearSelfie ? 'Yes' : 'No') . " for Applicant ID: {$applicantId}");
                    }
                }
                return response()->json(['success' => true], 200);
            }

            if (in_array($webhookType, ['applicantPending', 'applicantReviewed'])) {

                $reviewStatus = $data['reviewStatus'] ?? 'pending';
                $reviewResult = $data['reviewResult'] ?? null;
                $reviewAnswer = $reviewResult['reviewAnswer'] ?? null;

                if ($applicantId) {

                    if (!$isBeneficiary) {
                        $applicantData = $this->fetchApplicantData($applicantId, $sumsubConfig);
                        $appType = $applicantData['type'] ?? 'individual';

                        if ($appType === 'individual') {
                            // Individual Flow
                            if ($webhookClientId) {
                                $clientKYC = ClientKYC::firstOrCreate(['client_id' => $webhookClientId]);
                                $clientKYC->sumsub_applicant_id = $applicantId;

                                if ($webhookType === 'applicantPending') {
                                    if (!$isQuestionnaireOnly && $client && $client->status === 'Pending') {
                                        Log::info("Idempotency match: Client ID {$webhookClientId} is already Pending. Skipping duplicate applicantPending payload.");
                                    } else {
                                        $clientKYC->sumsub_kyc_status = $reviewStatus;
                                        $clientKYC->save();

                                        if ($applicantData) {
                                            $this->saveApplicantData($webhookClientId, $applicantData, $sumsubConfig);
                                            if ($client) {
                                                $client->status = 'Pending';
                                                $client->save();
                                            }
                                        }
                                    }
                                } elseif ($webhookType === 'applicantReviewed') {
                                    if (!$isQuestionnaireOnly && $clientKYC->sumsub_kyc_answer === $reviewAnswer && !empty($clientKYC->passport_copy_url)) {
                                        Log::info("Idempotency match: Individual review already processed for Client ID: {$webhookClientId}. Skipping.");
                                    } else {
                                        $clientKYC->sumsub_kyc_status = $reviewStatus;
                                        $clientKYC->sumsub_kyc_answer = $reviewAnswer;

                                        if ($reviewAnswer === 'RED') {
                                            $clientKYC->sumsub_kyc_reject_reason = json_encode($reviewResult['rejectLabels'][0] ?? []);
                                            $clientKYC->kyc_status = 'Unverified';
                                            $clientKYC->compliance_status = 'Rejected';
                                        }
                                        if (strtolower($reviewStatus) === 'completed' && $reviewAnswer === 'GREEN') {
                                            $clientKYC->liveness_status = 'Liveness Done';

                                            $clientKYC->kyc_status = 'Verified';
                                            $clientKYC->compliance_status = 'Verified';
                                        }
                                        $clientKYC->save();
                                        // generate onboarding pdf and files
                                        if ($reviewAnswer === 'GREEN' && $applicantData) {
                                            $client = Client::find($webhookClientId);
                                            if ($client) {
                                                $client->is_kyc_requested = 0;
                                                $client->save();
                                            }
                                            if (!$isQuestionnaireOnly && $levelName !== 'liveness-link') {
                                                $this->updateIndividualPassportInfo($webhookClientId, $applicantData);
                                            }
                                            $this->processApplicantFiles($webhookClientId, $applicantId, $applicantData, $clientKYC, $sumsubConfig, $levelName);
                                            if ($levelName !== 'liveness-link') {
                                                $this->generateOnboardingPdfs($client, 'individual');
                                            }
                                        }
                                    }
                                }
                            }
                        } else {
                            // --- COMPANY PARENT APPLICANT FLOW ---
                            if ($webhookType === 'applicantPending') {
                                if ($applicantData && $webhookClientId) {

                                    if (!$isQuestionnaireOnly && $client && $client->status === 'Pending') {
                                        Log::info("Idempotency match: Company Client ID {$webhookClientId} is already Pending. Skipping duplicate applicantPending payload.");
                                    } else {
                                        try {
                                            $this->saveApplicantData($webhookClientId, $applicantData, $sumsubConfig);
                                            if ($client) {
                                                $client->status = 'Pending';
                                                $client->is_kyc_requested = 0;
                                                $client->save();
                                            }
                                            $this->processApplicantFiles($webhookClientId, $applicantId, $applicantData, null, $sumsubConfig, $levelName);
                                            if ($levelName !== 'liveness-link') {
                                                $this->generateOnboardingPdfs($client, 'company');
                                            }
                                        } catch (Exception $e) {
                                            Log::error("Error processing main company applicantPending for Client ID: {$webhookClientId}. Error: " . $e->getMessage());
                                            return response()->json(['success' => false, 'message' => $e->getMessage()], 200);
                                        }
                                    }
                                }
                            }
                        }
                    } else {
                        // --- Owner AND DIRECTOR FLOW ---
                        $clientKYC = ClientKYC::where('sumsub_applicant_id', $applicantId)->first();

                        if ($clientKYC) {
                            $clientId = $clientKYC->client_id;

                            if ($webhookType === 'applicantPending') {
                                $clientKYC->sumsub_kyc_status = $reviewStatus;
                                $clientKYC->save();
                            } elseif ($webhookType === 'applicantReviewed') {
                                $rejectLabels = $reviewResult['rejectLabels'][0] ?? [];

                                if (!$isQuestionnaireOnly && $clientKYC->sumsub_kyc_answer === $reviewAnswer && !empty($clientKYC->passport_copy_url)) {
                                    Log::info("Idempotency match: Beneficiary review already processed for App ID: {$applicantId}. Skipping.");
                                } else {
                                    $clientKYC->sumsub_kyc_answer = $reviewAnswer;
                                    $clientKYC->sumsub_kyc_status = $reviewStatus;

                                    if ($reviewAnswer === 'RED') {
                                        $clientKYC->sumsub_kyc_reject_reason = json_encode($rejectLabels);
                                        $clientKYC->kyc_status = 'Unverified';
                                        $clientKYC->compliance_status = 'Rejected';
                                    }

                                    if (strtolower($reviewStatus) === 'completed' && $reviewAnswer === 'GREEN') {
                                        $clientKYC->liveness_status = 'Liveness Done';
                                        $clientKYC->kyc_status = 'Verified';
                                        $clientKYC->compliance_status = 'Verified';
                                    }
                                    $clientKYC->save();
                                    // Update passport details for this specific beneficiary
                                    $benData = $this->fetchApplicantData($applicantId, $sumsubConfig);
                                    if ($benData) {
                                        if (empty($clientKYC->sumsub_beneficiary_id) && !empty($benData['externalUserId'])) {
                                            $clientKYC->sumsub_beneficiary_id = $benData['externalUserId'];
                                            $clientKYC->save();
                                        }
                                        if (!$isQuestionnaireOnly) {
                                            $this->updateBeneficiaryPassportInfo($clientId, $benData, $clientKYC);
                                        }
                                        if ($reviewAnswer === 'GREEN') {
                                            $client = Client::find($clientId);
                                            if ($client) {
                                                $client->is_kyc_requested = 0;
                                                $client->save();
                                            }
                                            if (!empty($clientKYC->director_id)) {
                                                $director = ClientDirector::find($clientKYC->director_id);
                                                if ($director) {
                                                    $director->kyc_status = 0;
                                                    $director->save();
                                                }
                                            }
                                            $this->processApplicantFiles($clientId, $applicantId, $benData, $clientKYC, $sumsubConfig, $levelName);
                                        }
                                    }
                                }
                            }
                        } else {
                            Log::warning("Could not process beneficiary webhook: ClientKYC not found for App ID: {$applicantId}");
                        }
                    }
                }
                return response()->json(['success' => true], 200);
            }
            // $data = json_decode($webhookData);

            // if (!empty($data->payload->applicantId)) {
            //     $kyc_req = KycRequest::where('kyc_id', $data->payload->applicantId)->orderBy('id', 'desc')->first();
            //     if (!empty($kyc_req)) {
            //         $client = $kyc_req->client;
            //         if (!empty($client)) {
            //             $client->is_kyc_requested = 0;
            //             $client->save();
            //             $kyc_req->status = self::STATUS_MAP[$data->payload->reviewStatus] ?? 'Pending';
            //             $kyc_req->save();
            //             return response()->json(['success' => true], 200);
            //         }
            //     }
            // }

            return response()->json(['success' => true], 200);
            // return response()->json(['failed' => 'Bad request'], 400);
        } else {
            return response()->json(['failed' => 'Bad request'], 400);
        }
    }

    private function markImageInactive($applicantId, $imageId, $sumsubConfig, $revert = false)
    {
        $envPrefix = app()->environment('production') ? 'production_' : 'sandbox_';
        $appToken  = $sumsubConfig[$envPrefix . 'app_token']['value'] ?? '';
        $secretKey = $sumsubConfig[$envPrefix . 'secret_key']['value'] ?? '';
        $baseUrl   = rtrim($sumsubConfig['base_url']['value'] ?? 'https://api.sumsub.com', '/');

        // Sumsub expects the string 'true' or 'false'
        $revertStr = $revert ? 'true' : 'false';

        // Note: applicantId is used as inspectionId in Sumsub
        $urlPath = "/resources/inspections/{$applicantId}/resources/{$imageId}?revert={$revertStr}";
        $ts = time();

        // Hash the signature using the 'DELETE' method
        $signature = hash_hmac('sha256', $ts . 'DELETE' . $urlPath, $secretKey);

        try {
            $response = Http::withHeaders([
                'X-App-Token' => $appToken,
                'X-App-Access-Sig' => $signature,
                'X-App-Access-Ts' => $ts,
            ])->delete($baseUrl . $urlPath);

            if ($response->successful()) {
                Log::info("Successfully marked Sumsub image {$imageId} as inactive. Revert: {$revertStr}");
                return true;
            } else {
                Log::error("Failed to mark image {$imageId} inactive. Status: " . $response->status() . " Body: " . $response->body());
            }
        } catch (\Exception $e) {
            Log::error("Exception marking image {$imageId} inactive: " . $e->getMessage());
        }

        return false;
    }

    private function updateIndividualPassportInfo($clientId, $applicantData)
    {
        $info = $applicantData['info'] ?? [];

        $idDoc = !empty($info['idDocs'][0]) ? $info['idDocs'][0] : [];

        if (empty($idDoc)) {
            Log::warning("No ID Documents found for Individual Client ID: {$clientId}");
            return;
        }

        $passportNumber = $idDoc['number'] ?? null;
        $passportExpiry = !empty($idDoc['validUntil']) ? date('Y-m-d', strtotime($idDoc['validUntil'])) : null;

        $nationalityIso3 = $idDoc['country'] ?? null;
        $nationalityId = $nationalityIso3 ? Country::where('iso3', $nationalityIso3)->value('id') : null;

        $individual = ClientIndividual::where('client_id', $clientId)->first();

        if ($individual) {
            if ($passportNumber) $individual->passport_number = $passportNumber;
            if ($passportExpiry) $individual->passport_expiry_date = $passportExpiry;
            if ($nationalityId) $individual->nationality_id = $nationalityId;

            $individual->save();
            Log::info("Successfully updated passport info for Individual Client ID: {$clientId}");
        }
    }

    private function generateOnboardingPdfs($client, $type)
    {

        if ($type === 'individual') {
            $configFiles = ClientFileConfig::individual()->where('is_active', 1)->where('signed_pdf', 1)->get();
        } else {
            $configFiles = ClientFileConfig::company()->where('is_active', 1)->where('signed_pdf', 1)->get();
        }

        if (!File::exists(storage_path('app/onBoardingPdfs'))) {
            File::makeDirectory(storage_path('app/onBoardingPdfs'), 0755, false, false);
        }

        if (!File::exists(storage_path('app/onBoardingPdfs/' . $client->id))) {
            File::makeDirectory(storage_path('app/onBoardingPdfs/' . $client->id), 0755, false, false);
        }

        $pdfs = Common::generateOnBoardingPDF($client, $configFiles);

        foreach ($pdfs as $file_config_id => $file) {
            if ($type === 'individual') {
                Common::uploadIndividualSignedPDF($client, $file, $file_config_id);
            } else {
                Common::uploadCompanySignedPDF($client, $file, $file_config_id);
            }
        }
        File::deleteDirectories(storage_path('app/onBoardingPdfs'));
    }

    private function updateBeneficiaryPassportInfo($clientId, $benData, $clientKYC)
    {
        // Log::info("Updating passport info for beneficiary of Client ID and ClientKyc: {$clientId} using data: " . print_r($benData, true) . " Current KYC record: " . print_r($clientKYC, true));
        $info = $benData['info'] ?? [];
        $idDoc = !empty($info['idDocs']) ? $info['idDocs'][0] : [];

        if (empty($idDoc)) {
            return;
        }

        $passportNumber = $idDoc['number'] ?? null;
        $passportExpiry = !empty($idDoc['validUntil']) ? date('Y-m-d', strtotime($idDoc['validUntil'])) : null;

        $nationalityIso3 = $idDoc['country'] ?? null;
        $nationalityId = $nationalityIso3 ? Country::where('iso3', $nationalityIso3)->value('id') : null;

        $directorId = $clientKYC->director_id;

        if ($directorId) {
            $director = ClientDirector::find($directorId);
            if ($director) {
                if ($passportNumber) $director->passport_number = $passportNumber;
                if ($passportExpiry) $director->passport_expiry = $passportExpiry;
                if ($nationalityId) $director->nationality_id = $nationalityId;
                $director->save();
                Log::info("Successfully updated passport info for Director ID: {$directorId}");
            }
        } else {
            $contact = ClientCompanyContact::where('client_id', $clientId)->first();
            if ($contact) {
                if ($passportNumber) $contact->passport_number = $passportNumber;
                if ($passportExpiry) $contact->passport_expiry = $passportExpiry;
                if ($nationalityId) $contact->nationality_id = $nationalityId;
                $contact->save();
                Log::info("Successfully updated passport info for Primary Contact of Client ID: {$clientId}");
            }
        }
    }

    private function fetchApplicantData($applicantId, $sumsubConfig)
    {
        $envPrefix = app()->environment('production') ? 'production_' : 'sandbox_';
        $appToken  = $sumsubConfig[$envPrefix . 'app_token']['value'] ?? '';
        $secretKey = $sumsubConfig[$envPrefix . 'secret_key']['value'] ?? '';
        $baseUrl   = rtrim($sumsubConfig['base_url']['value'] ?? 'https://api.sumsub.com', '/');

        if (!$appToken || !$secretKey) {
            Log::error('Sumsub API credentials are missing');
            return null;
        }

        $urlPath = '/resources/applicants/' . $applicantId . '/one';
        $method = 'GET';
        $ts = time();

        $signature = hash_hmac('sha256', $ts . $method . $urlPath, $secretKey);

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'X-App-Token' => $appToken,
                'X-App-Access-Sig' => $signature,
                'X-App-Access-Ts' => $ts,
            ])->get($baseUrl . $urlPath);

            if ($response->successful()) {
                Log::info("response from Sumsub for applicant ID: {$applicantId} - " . $response->body());
                return $response->json();
            }

            Log::error('Failed to fetch applicant data from Sumsub. Status: ' . $response->status() . ' Body: ' . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error('Exception while fetching applicant data from Sumsub: ' . $e->getMessage());
            return null;
        }
    }

    private function saveApplicantData($clientId, $applicantData, $sumsubConfig)
    {
        $type = $applicantData['type'] ?? 'individual';
        $questionnaires = $applicantData['questionnaires'] ?? [];

        $qData = [];
        foreach ($questionnaires as $q) {
            $sections = $q['sections'] ?? [];
            foreach ($sections as $sectionData) {
                $items = $sectionData['items'] ?? [];
                foreach ($items as $key => $item) {
                    $qData[$key] = $item['value'] ?? null;
                }
            }
        }

        if ($type === 'individual') {
            $info = $applicantData['info'] ?? [];
            $idDoc = !empty($info['idDocs']) ? $info['idDocs'][0] : [];

            // Country Mapping
            $nationalityIso3 = $idDoc['country'] ?? null;
            $residenceIso3 = $qData['residence_country_iso3'] ?? null;

            $nationalityId = $nationalityIso3 ? Country::where('iso3', $nationalityIso3)->value('id') : null;
            $residenceId = $residenceIso3 ? Country::where('iso3', $residenceIso3)->value('id') : null;

            // --- Individual Model ---
            $individual = ClientIndividual::firstOrCreate(['client_id' => $clientId]);

            if (!empty($info['dob'])) $individual->dob = date('Y-m-d', strtotime($info['dob']));
            if ($nationalityId) $individual->nationality_id = $nationalityId;
            if ($residenceId) $individual->residence_country_id = $residenceId;
            // country_code_id

            // remove + from mobile number if exist
            $rawMobile = $qData['mobile_number'] ?? null;
            if ($rawMobile) {
                $individual->mobile_number = ltrim($rawMobile, '+');
            }
            $individual->gender = $qData['gender'] ?? $individual->gender;
            $individual->mailing_address = $qData['mailing_address'] ?? $individual->mailing_address;
            $individual->city = $qData['city'] ?? $individual->city;

            $individual->passport_number = $idDoc['number'] ?? $individual->passport_number;
            if (!empty($idDoc['validUntil'])) {
                $individual->passport_expiry_date = date('Y-m-d', strtotime($idDoc['validUntil']));
            }

            $individual->agree_declaration = 1;
            $individual->save();

            // --- Individual Employer Model ---
            $employer = ClientIndividualEmployer::firstOrCreate([
                'client_id' => $clientId,
                'individual_id' => $individual->id
            ]);

            $employer->occupation_business = $qData['occupationBusinessTy'] ?? $employer->occupation_business;
            $employer->employer_business_name = $qData['businessName'] ?? $employer->employer_business_name;
            $employer->employer_address = $qData['employer_address'] ?? $employer->employer_address;
            $employer->job_title = $qData['job_title'] ?? $employer->job_title;
            $employer->applicant_annual_income_usd = $qData['applicant_annual_income_usd'] ?? $employer->applicant_annual_income_usd;

            $employer->save();
        } else {
            // --- Company Model ---
            $companyInfo = $applicantData['fixedInfo']['companyInfo'] ?? [];
            $beneficiaries = $companyInfo['beneficiaries'] ?? [];
            // Log::info("Beneficiaries data for company applicant (Client ID: {$clientId}): " . print_r($beneficiaries, true));

            $primaryContactInfo = [];
            $directorsToSave = [];
            $foundFirstFounder = false;

            foreach ($beneficiaries as $beneficiary) {
                $types = $beneficiary['types'] ?? [];
                $info = $beneficiary['beneficiaryInfo'] ?? [];
                $benAppId = $beneficiary['applicantId'] ?? null;

                $isFounder = in_array('founder', $types);
                $isUbo = in_array('ubo', $types);

                if ($isFounder && !$foundFirstFounder) {
                    $primaryContactInfo = $info;
                    $foundFirstFounder = true;

                    // Create KYC Record specifically for the Main Contact
                    if ($benAppId) {
                        $clientKYC = ClientKYC::firstOrCreate(
                            ['sumsub_applicant_id' => $benAppId],
                            [
                                'client_id' => $clientId,
                                'sumsub_kyc_status' => 'pending'
                            ]
                        );

                        // main company applicant ID
                        $mainApplicantId = $applicantData['id'] ?? null;
                        if ($mainApplicantId && empty($clientKYC->company_applicant_id)) {
                            $clientKYC->company_applicant_id = $mainApplicantId;
                            $clientKYC->save();
                        }

                        if (empty($clientKYC->sumsub_beneficiary_id)) {
                            $benData = $this->fetchApplicantData($benAppId, $sumsubConfig);
                            if ($benData && !empty($benData['externalUserId'])) {
                                $clientKYC->sumsub_beneficiary_id = $benData['externalUserId'];
                                $clientKYC->save();
                            }
                        }
                    }
                    continue;
                }

                if ($isFounder || $isUbo) {
                    $directorsToSave[] = [
                        'applicantId' => $benAppId,
                        'name' => trim(($info['firstName'] ?? '') . ' ' . ($info['lastName'] ?? '')),
                        'email' => $info['email'] ?? null,
                        'dob' => $info['dob'] ?? null,
                        'gender' => $info['gender'] ?? null,
                        'taxResidenceCountry' => $info['taxResidenceCountry'] ?? null,
                    ];
                }
            }

            $company = ClientCompany::firstOrCreate(['client_id' => $clientId]);

            // $companyPlaceIso3 = $qData['company_place'] ?? null;
            $companyType = $qData['company_type'] ?? null;
            $companyTypeMapped = $companyType ? ucwords(str_replace('_', ' ', $companyType)) : null;

            // $companyPlaceId = $companyPlaceIso3 ? Country::where('iso3', $companyPlaceIso3)->value('nicename') : null;
            $comppanyTypeId = $companyTypeMapped ? CompanyType::where('name', $companyTypeMapped)->value('id') : null;

            // $company->company_place = $companyPlaceId ?? $company->company_place;
            $company->company_type = $comppanyTypeId ?? $company->company_type;
            $company->business_reg_number = $qData['registrationNumber'] ?? $company->business_reg_number;
            $company->business_reg_address = $qData['business_reg_address'] ?? $company->business_reg_address;
            $natureOfBusiness = $qData['nature_of_business'] ?? null;
            $natureOfBusinessTitle = $natureOfBusiness ? RiskScoreMatrix::where('id', $natureOfBusiness)->value('title') : null;
            $company->nature_of_business = $natureOfBusinessTitle ?? $company->nature_of_business;
            $company->company_city = $qData['company_city'] ?? $company->company_city;
            $company->tax_number = $qData['tax_number'] ?? $company->tax_number;

            $companyCountryIso3 = $qData['company_country'] ?? null;
            $companyCountryId = $companyCountryIso3 ? Country::where('iso3', $companyCountryIso3)->value('id') : null;
            if ($companyCountryId) $company->company_country = $companyCountryId;

            if (!empty($qData['registration_date'])) {
                $company->registration_date = date('Y-m-d', strtotime($qData['registration_date']));
            }
            if (!empty($qData['expiry_date'])) {
                $company->expiry_date = date('Y-m-d', strtotime($qData['expiry_date']));
            }

            $company->agree_declaration = 1;
            $company->save();

            // --- Company Contact Model (Using Primary Founder) ---
            if (!empty($primaryContactInfo)) {
                $contact = ClientCompanyContact::firstOrCreate([
                    'client_id' => $clientId,
                ]);
                $contact->company_id = $company->id;

                $contact->name = trim(($primaryContactInfo['firstName'] ?? '') . ' ' . ($primaryContactInfo['lastName'] ?? ''));
                if (!empty($primaryContactInfo['dob'])) $contact->dob = date('Y-m-d', strtotime($primaryContactInfo['dob']));

                // country_code_id and mobile number and nationality_id is missing

                $rawPhone = $primaryContactInfo['phone'] ?? null;
                if ($rawPhone) {
                    $contact->mobile_number = ltrim($rawPhone, '+');
                }

                $gender = $primaryContactInfo['gender'] ?? null;
                if ($gender === 'M') $contact->gender = 'male';
                elseif ($gender === 'F') $contact->gender = 'female';

                // Extract designation
                $metadata = $primaryContactInfo['metadata'] ?? [];
                foreach ($metadata as $meta) {
                    if (($meta['key'] ?? '') === 'contact_person_designation') {
                        $contact->designation = $meta['value'];
                        break;
                    }
                }

                $contactResIso = $primaryContactInfo['taxResidenceCountry'] ?? null;
                $contactResId = $contactResIso ? Country::where('iso3', $contactResIso)->value('id') : null;
                if ($contactResId) $contact->residence_country_id = $contactResId;

                $contact->save();
            }

            // founder doesnt have email but if they have both type founder and ubo they will have email included
            foreach ($directorsToSave as $dirData) {
                if (!empty($dirData['name'])) {

                    $formattedDob = !empty($dirData['dob']) ? date('Y-m-d', strtotime($dirData['dob'])) : null;
                    $formattedGender = null;
                    if ($dirData['gender'] === 'M') {
                        $formattedGender = 'male';
                    } elseif ($dirData['gender'] === 'F') {
                        $formattedGender = 'female';
                    }
                    $resCountryId = null;
                    if (!empty($dirData['taxResidenceCountry'])) {
                        $resCountryId = Country::where('iso3', $dirData['taxResidenceCountry'])->value('id');
                    }

                    $directorAttributes = [
                        'name' => $dirData['name'],
                        'dob' => $formattedDob,
                        'gender' => $formattedGender,
                        'residence_country_id' => $resCountryId,
                    ];

                    $query = ClientDirector::where('client_id', $clientId);
                    if (!empty($dirData['email'])) {
                        $query->where('email', $dirData['email']);
                    } else {
                        $query->where('name', $dirData['name'])
                            ->whereNull('email');
                    }

                    $existingDirector = $query->first();

                    if (!empty($dirData['email'])) {
                        $email = $dirData['email'];

                        $existsInIndiv = ClientIndividual::where('email_address', $email)->exists();
                        $existsInContact = ClientCompanyContact::where('email_address', $email)->exists();

                        $dirQuery = ClientDirector::where('email', $email);
                        if ($existingDirector) {
                            $dirQuery->where('id', '!=', $existingDirector->id);
                        }
                        $existsInDir = $dirQuery->exists();

                        if ($existsInIndiv || $existsInContact || $existsInDir) {
                            $msg = "Duplicate email '{$email}' detected for Director '{$dirData['name']}'. Process stopped.";
                            Log::error($msg);
                            throw new Exception($msg);
                        }
                    }

                    $directorId = null;

                    if ($existingDirector && $existingDirector->is_deleted) {
                        $directorAttributes['is_deleted'] = 0;
                        $directorAttributes['email'] = !empty($dirData['email']) ? $dirData['email'] : $existingDirector->email;
                        $directorAttributes['updated_at'] = now();

                        $existingDirector->update($directorAttributes);
                        $directorId = $existingDirector->id;
                    } elseif (!$existingDirector) {
                        $directorAttributes['email'] = !empty($dirData['email']) ? $dirData['email'] : null;
                        $directorAttributes['client_id'] = $clientId;

                        $newDir = ClientDirector::create($directorAttributes);
                        $directorId = $newDir->id;
                    } else {
                        $directorId = $existingDirector->id;
                    }

                    // Create KYC Record for this Director using their specific applicant ID and Director ID
                    if (!empty($dirData['applicantId']) && $directorId) {
                        $clientKYC = ClientKYC::firstOrCreate(
                            ['sumsub_applicant_id' => $dirData['applicantId']],
                            [
                                'client_id' => $clientId,
                                'director_id' => $directorId,
                                'sumsub_kyc_status' => 'pending'
                            ]
                        );
                        if (empty($clientKYC->sumsub_beneficiary_id)) {
                            $benData = $this->fetchApplicantData($benAppId, $sumsubConfig);
                            if ($benData && !empty($benData['externalUserId'])) {
                                $clientKYC->sumsub_beneficiary_id = $benData['externalUserId'];
                                $clientKYC->save();
                            }
                        }
                    }
                }
            }
        }
    }
    private function processApplicantFiles($clientId, $applicantId, $applicantData, $clientKYC, $sumsubConfig, $levelName = null)
    {
        $client = Client::find($clientId);
        if (!$client) {
            Log::warning("Client not found for ID: {$clientId} while processing files for Applicant ID: {$applicantId}");
            return;
        }

        $clientType = $client->type; // 'individual' or 'company'
        $isQuestionnaireOnly = in_array($levelName, ['individual-questionnaire-only', 'company-questionnaire-only']);

        $envPrefix = app()->environment('production') ? 'production_' : 'sandbox_';
        $appToken  = $sumsubConfig[$envPrefix . 'app_token']['value'] ?? '';
        $secretKey = $sumsubConfig[$envPrefix . 'secret_key']['value'] ?? '';
        $baseUrl   = rtrim($sumsubConfig['base_url']['value'] ?? 'https://api.sumsub.com', '/');

        $urlPath = '/resources/applicants/' . $applicantId . '/metadata/resources';
        $method = 'GET';
        $ts = time();

        $signature = hash_hmac('sha256', $ts . $method . $urlPath, $secretKey);

        $docMetaData = [];
        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'X-App-Token' => $appToken,
                'X-App-Access-Sig' => $signature,
                'X-App-Access-Ts' => $ts,
            ])->get($baseUrl . $urlPath);

            if ($response->successful()) {
                $docMetaData = $response->json()['items'] ?? [];
            } else {
                Log::error("Failed to fetch document metadata from Sumsub for Applicant ID: {$applicantId}. Status: " . $response->status() . " Body: " . $response->body());
                return;
            }
        } catch (\Exception $e) {
            Log::error("Exception while fetching document metadata from Sumsub for Applicant ID: {$applicantId}. Error: " . $e->getMessage());
            return;
        }

        $imageExtensionMap = [];

        foreach ($docMetaData as $doc) {
            if (!empty($doc['deactivated']) && $doc['deactivated'] === true) {
                continue;
            }
            $imgId = $doc['id'] ?? null;
            $ext = $doc['fileMetadata']['fileType'] ?? 'jpg';

            if ($imgId) {
                $imageExtensionMap[$imgId] = $ext;
            }

            $docType = $doc['idDocDef']['idDocType'] ?? '';

            if ($clientKYC) {
                if ($docType === 'SELFIE' && empty($clientKYC->selfie_photo_url)) {
                    $url = $this->downloadAndUploadToS3($applicantId, $imgId, "client-onboarding-files/clients/{$clientType}/kyc/{$clientId}", $ext, $sumsubConfig);
                    if ($url) {
                        $clientKYC->selfie_photo_url = $url;
                        $clientKYC->save();
                        Log::info("Saved Selfie Photo URL for KYC Record: {$clientKYC->id}");
                    }
                }
                // Do NOT download/upload Passports if in questionnaire only mode
                elseif (!$isQuestionnaireOnly && in_array($docType, ['PASSPORT', 'ID_CARD']) && empty($clientKYC->passport_copy_url)) {
                    $url = $this->downloadAndUploadToS3($applicantId, $imgId, "client-onboarding-files/clients/{$clientType}/kyc/{$clientId}", $ext, $sumsubConfig);
                    if ($url) {
                        $clientKYC->passport_copy_url = $url;
                        $clientKYC->save();
                        Log::info("Saved Passport Copy URL for KYC Record: {$clientKYC->id}");

                        // 2. Save duplicate to generic file tables
                        if ($clientType === 'individual') {
                            $config = ClientFileConfig::individual()->where('identifier', 'passport_copy')->first();

                            if ($config) {
                                $exists = ClientIndividualFile::where('client_id', $clientId)->where('file_id', $config->id)->exists();
                                if (!$exists) {
                                    $newFile = new ClientIndividualFile();
                                    $newFile->client_id = $clientId;
                                    $newFile->individual_id = $client->individual->id ?? null;
                                    $newFile->file_id = $config->id;
                                    $newFile->url = $url;
                                    $newFile->created_by = $client->created_by ?? null;
                                    $newFile->save();
                                    Log::info("Saved Passport Copy to ClientIndividualFile for Client ID: {$clientId}");
                                }
                            }
                        } else {
                            $config = ClientFileConfig::company()->where('identifier', 'passport_copy')->first();

                            if ($config) {
                                $query = ClientCompanyFile::where('client_id', $clientId)->where('file_id', $config->id);
                                if (!empty($clientKYC->director_id)) {
                                    $query->where('director_id', $clientKYC->director_id);
                                }
                                $exists = $query->exists();

                                if (!$exists) {
                                    $newFile = new ClientCompanyFile();
                                    $newFile->client_id = $clientId;
                                    $newFile->company_id = $client->company->id ?? null;
                                    $newFile->file_id = $config->id;
                                    $newFile->url = $url;
                                    if (!empty($clientKYC->director_id)) {
                                        $newFile->director_id = $clientKYC->director_id; // Mapping to specific company director
                                    }
                                    $newFile->created_by = $client->created_by ?? null;
                                    $newFile->save();
                                    Log::info("Saved Passport Copy to ClientCompanyFile for Client ID: {$clientId}");
                                }
                            }
                        }
                    }
                }
            }
        }

        $questionnaires = $applicantData['questionnaires'] ?? [];
        $relatedFiles = [];

        foreach ($questionnaires as $q) {
            $sections = $q['sections'] ?? [];
            if (isset($sections['related_files']['items'])) {
                foreach ($sections['related_files']['items'] as $identifier => $item) {
                    if (!empty($item['value'])) {
                        $relatedFiles[$identifier] = $item['value']; // $item['value'] is the Sumsub Image ID
                    }
                }
            }
        }

        // upload releated files base on type
        foreach ($relatedFiles as $identifier => $imageId) {

            if ($clientType === 'individual') {
                $config = ClientFileConfig::individual()->where('identifier', $identifier)->first();
            } else {
                $config = ClientFileConfig::company()->where('identifier', $identifier)->first();
            }

            if (!$config) {
                Log::warning("No File Config found for identifier: {$identifier} and type: {$clientType}");
                continue;
            }

            // SAFE TO DOWNLOAD AND UPLOAD
            $ext = $imageExtensionMap[$imageId] ?? 'jpg';
            $s3BasePath = "client-onboarding-files/clients/{$clientType}/{$clientId}";

            $url = $this->downloadAndUploadToS3($applicantId, $imageId, $s3BasePath, $ext, $sumsubConfig);

            if ($url) {
                if ($clientType === 'individual') {
                    ClientIndividualFile::updateOrCreate(
                        ['client_id' => $clientId, 'file_id' => $config->id],
                        [
                            'individual_id' => $client->individual->id ?? null,
                            'url' => $url,
                            'created_by' => $client->created_by ?? null
                        ]
                    );
                } else {
                    ClientCompanyFile::updateOrCreate(
                        ['client_id' => $clientId, 'file_id' => $config->id],
                        [
                            'company_id' => $client->company->id ?? null,
                            'url' => $url,
                            'created_by' => $client->created_by ?? null
                        ]
                    );
                }
            }
        }
    }
    private function downloadAndUploadToS3($applicantId, $imageId, $s3Path, $ext, $sumsubConfig)
    {
        // Log::info("Downloading image from Sumsub. Applicant ID: {$applicantId}, Image ID: {$imageId}. S3 Path: {$s3Path}, Extension: {$ext}");
        $envPrefix = app()->environment('production') ? 'production_' : 'sandbox_';
        $appToken  = $sumsubConfig[$envPrefix . 'app_token']['value'] ?? '';
        $secretKey = $sumsubConfig[$envPrefix . 'secret_key']['value'] ?? '';
        $baseUrl   = rtrim($sumsubConfig['base_url']['value'] ?? 'https://api.sumsub.com', '/');

        $urlPath = "/resources/inspections/{$applicantId}/resources/{$imageId}";
        $ts = time();
        $signature = hash_hmac('sha256', $ts . 'GET' . $urlPath, $secretKey);

        try {
            $response = Http::withHeaders([
                'X-App-Token' => $appToken,
                'X-App-Access-Sig' => $signature,
                'X-App-Access-Ts' => $ts,
            ])->get($baseUrl . $urlPath);

            if ($response->successful()) {
                $content = $response->body();

                $url = S3Helper::fileUpload(null, $s3Path, $content, $ext);
                // Log::info("Successfully downloaded image from Sumsub and uploaded to S3. Applicant ID: {$applicantId}, Image ID: {$imageId}, S3 URL: {$url}");
                return $url;
            } else {
                Log::error("Failed to download image {$imageId} from Sumsub. Status: " . $response->status());
            }
        } catch (\Exception $e) {
            Log::error("Exception downloading image {$imageId} from Sumsub: " . $e->getMessage());
        }

        return null;
    }

    public function fingateCallback(Request $request)
    {
        $webhookData = file_get_contents("php://input");
        $headers = getallheaders();

        Log::info('Fingate callback Webhook Headers:', $headers);
        // dd($headers);

        $hash = request()->header('Webhookhash');
        // dd($hash);
        Log::info('Fingate callback Webhook Data:', [$webhookData]);
        if (!empty($webhookData) && !empty($hash)) {
            $fingateHelper = new FingateHelper();
            $webHookHash  = '';
            if (!empty($fingateHelper->webHookHash))
                $webHookHash = $fingateHelper->webHookHash;

            $data = json_decode($webhookData);


            if (!empty($data->WebHookPayload->CustomerId) && hash_equals((string) $webHookHash, (string) $hash)) {
                $fingateClient = FingateClient::where('customer_id', $data->WebHookPayload->CustomerId)->first();
                //dd($fingateClient);
                if (!empty($fingateClient)) {

                    $response = $fingateHelper->processWebhooks($data, $fingateClient);
                    if ($response) {
                        $this->storeWebhookData($data->WebHookUniqueId, json_encode($data), 'FinGate');
                        return response()->json(['success' => true], 200);
                    }
                }
                $this->storeWebhookData($data->WebHookUniqueId, json_encode($data), 'FinGate');
            }
            return response()->json(['failed' => 'Bad request'], 400);
        } else {
            return response()->json(['failed' => 'Bad request'], 400);
        }
        // Always return 200 OK so Fingate knows it was received

    }

    public function fingateDocumentsStatusUpdate(Request $request)
    {
        $webhookData = file_get_contents("php://input");
        $headers = getallheaders();

        Log::info('Fingate Documents status Webhook Headers:', $headers);
        Log::info('Fingate  Documents status  Webhook Data:', [$webhookData]);
        $hash = request()->header('Webhookhash');
        if (!empty($webhookData) && !empty($hash)) {
            $fingateHelper = new FingateHelper();
            $webHookHash = '';
            if (!empty($fingateHelper->webHookHash)) {
                $webHookHash = $fingateHelper->webHookHash;
            }
            $data = json_decode($webhookData);
            if (!empty($data->WebHookPayload->CustomerId) && hash_equals((string) $webHookHash, (string) $hash)) {
                $fingateClient = FingateClient::where('customer_id', $data->WebHookPayload->CustomerId)->first();
                if (!empty($fingateClient)) {
                    $client =  $fingateClient->client;
                    $client->is_kyc_requested = 0;
                    $client->save();
                    $kyc_req = KycRequest::where('client_id', $client->id)->where('kyc_provider', 'sumsub')->orderBy('id', 'desc')->first();
                    if (!empty($kyc_req)) {
                        $kyc_req->kyc_id =  $data->WebHookPayload->SumSubId;
                        $kyc_req->status = 'completed';
                        $kyc_req->save();
                    }
                    return response()->json(['success' => true], 200);
                }
            }
            return response()->json(['failed' => 'Bad request'], 400);
        } else {
            return response()->json(['failed' => 'Bad request'], 400);
        }
        // Always return 200 OK so Fingate knows it was received

    }

    public function lorumCallback(Request $request)
    {
        // Need to add web-hook validation.
        if (!$this->vlidateLorumWebhook($request)) {
            return response()->json(['error' => 'Unauthorized source'], 403);
        }

        $rawPayload = $request->getContent();

        if (!empty($rawPayload)) {
            $webhook_data = json_decode($rawPayload, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Invalid JSON received', ['error' => json_last_error_msg(), 'payload' => $rawPayload]);
                return response()->json(['error' => 'Invalid JSON payload'], 400);
            }

            $this->lorumLogs($webhook_data, $webhook_data["type"]);

            if ($webhook_data["type"] === "onboarding_completed") {
                Log::channel('lorumLogs')->info("------=============== Onboarding completed webhook received for Customer ID: {$webhook_data['onboarding_completed']['customer_id']}");
                Log::channel('lorumLogs')->info("Payload Data: \n" . json_encode($webhook_data));

                // TODO: Handle onboarding web-hook
                return $this->handleOnboardingWebHook($webhook_data);
            } else if ($webhook_data["type"] === "account_opened") {
                Log::channel('lorumLogs')->info("------=============== Account opened webhook received for Account ID: {$webhook_data['account_opened']['account_id']}");
                Log::channel('lorumLogs')->info("Payload Data: \n" . json_encode($webhook_data));

                // Handle onboarding web-hook
                return $this->handleAccountOpenedWebhook($webhook_data);
            }

            // Process Lorum Transaction by web-hook
            return $this->processLorumTransaction($rawPayload);
        }

        return response()->json(['success' => 'webhook received'], 200);
    }

    public function openPaydCallback(Request $request)
    {
        // Process webhook payload
        // Perform actions based on the webhook data
        $webhookData = file_get_contents("php://input");
        $headers = getallheaders();

        if (empty($webhookData)) {
            return response()->json(['failed' => 'Bad request'], 400);
        }

        if ($this->validateOpenpaydWebhook($headers, $webhookData)) {
            $webhookData = json_decode($webhookData, true);

            if (!empty($webhookData['type']) && in_array($webhookData['type'], ['PAYIN', 'PAYOUT', 'TRANSFER', 'SWEEP_TRANSFER', 'RETURN_IN'])) {
                Log::info("Openpayd Transaction type: " . $webhookData['type'] . " \n\n");
                Log::info("OpenPayd WebHook Data: \n" . json_encode($webhookData) . "\n\n");

                return $this->performOpenpaydTransaction($webhookData);
            } else {
                //pending Linked account activation
                if (!empty($webhookData['tenantId']) && !empty($webhookData['iban']) &&  !empty($webhookData['status']) &&  !empty($webhookData['internalAccountId'])) {
                    if ($webhookData['status'] == 'ACTIVE') {
                        $this->activateLinkedAccount($webhookData);
                        $webhookData['id'] = $webhookData['tenantId'];
                    }
                }
                Log::error("OpenPayd webhook: " . json_encode($webhookData));
                $id = $webhookData['id'] ?? 'NO_ID_' . time();
                $this->storeWebhookData($id, json_encode($webhookData), 'OpenPayd');
                return response()->json(['success' => 'webhook recieved'], 200);
            }
        }

        // Log::info("Signature verification failed.", ['headers' => $headers]);
        return response()->json(['failed' => 'Invalid request data.'], 500);
    }
    //need verification with pending status
    public function activateLinkedAccount($data)
    {
        Log::error("Webhook => Bank Account ." . json_encode($data));

        $openpayd_account = VirtualBankAccount::where('internal_account_id', $data['internalAccountId'])->first();
        $openpayd_account->iban = $data['iban'] ?? 'xxxxxx';
        $openpayd_account->bic = $data['bic'] ?? 'xxxxxx';
        $openpayd_account->account_number = $data['accountNumber'] ?? 'xxxxxx';
        $openpayd_account->status = $data['status'];
        $openpayd_account->save();
    }

    public function validateOpenpaydWebhook($headers, $requestBody)
    {
        $signature = $headers['Signature'] ?? $headers['signature'] ?? null;

        if (!$signature) {
            Log::error("Missing signature in webhook headers.");
            return false;
        }

        // Load the public key
        // $publicKeyPath = storage_path('app/keys/public_key.pem');

        $publicKeyPath = env('KEYS_PATH');

        if (!file_exists($publicKeyPath)) {
            Log::error("Public key file not found: " . $publicKeyPath);
            return false;
        }

        $publicKey = file_get_contents($publicKeyPath);
        if (!$publicKey) {
            Log::error("Failed to read public key.");
            return false;
        }

        $publicKeyResource = openssl_pkey_get_public($publicKey);
        if (!$publicKeyResource) {
            Log::error("Invalid public key format.");
            return false;
        }

        // Decode the base64-encoded signature
        $decodedSignature = base64_decode($signature);
        if (!$decodedSignature) {
            Log::error("Invalid signature encoding.");
            return false;
        }

        // Verify the signature
        $verified = openssl_verify($requestBody, $decodedSignature, $publicKeyResource, OPENSSL_ALGO_SHA256);

        Log::info("Signature verification result: " . ($verified === 1 ? "Success" : "Failure"));

        return $verified === 1;
    }

    private function vlidateLorumWebhook(Request $request)
    {
        $lorum = new LorumApis();
        $lorumAllowedIps = json_decode($lorum->getAllowedWebHookIps());

        $environment = env('APP_ENV'); // Staging, Production OR Local.
        $clientIp = $request->ip(); // real client IP

        if ($environment === 'production') {
            $allowedIps = $lorumAllowedIps;
        } else {
            $allowedIps = $lorumAllowedIps;
        }

        if (!in_array($clientIp, $allowedIps, true)) {
            // Log::warning('Unauthorized Lorum webhook IP', ['ip' => $clientIp, 'headers' => $request->headers->all()]);
            $this->lorumLogs(['ip' => $clientIp, 'headers' => $request->headers->all()], 'Unauthorized Lorum webhook IP');
            return false;
        }

        return true;
    }

    private function validateZenusWebhook(Request $request)
    {
        try {
            $zenusApi = new ZenusApis();
            $secretKey = $zenusApi->webhook_secret;

            if (!$secretKey) {
                Log::channel('zenusLogs')->error('Zenus webhook secret not configured');
                return false;
            }

            // Raw body
            $rawBody = $request->getContent();

            // Signature from header
            $receivedSignature = $request->header('x-hmac-signature');
            // Log::channel('zenusLogs')->warning('x-hmac-signature header: ' . $receivedSignature);

            if (!$receivedSignature) {
                Log::channel('zenusLogs')->warning('Missing x-hmac-signature header');
                return false;
            }

            // Compute HMAC SHA256
            $computedSignature = hash_hmac('sha256', $rawBody, $secretKey);

            // Secure comparison (prevents timing attacks)
            if (!hash_equals($computedSignature, $receivedSignature)) {
                Log::channel('zenusLogs')->error('Invalid Zenus HMAC signature', [
                    'computed' => $computedSignature,
                    'received' => $receivedSignature,
                ]);
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::channel('zenusLogs')->error('Zenus webhook validation failed', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }


    public function performOpenpaydTransaction($trData)
    {
        // TODO: handle [REVIEW, PROCESSING ,COMPLETED, FAILED, CANCLED, RETURN_OUT_COMPLETED, RETURN_IN_COMPELTED ]

        $verifyOpenpayd = $this->verifyOpenPaydWebhooksData($trData);

        // Log::info("Webhook request data verification  " . json_encode($verifyOpenpayd));

        if (!$verifyOpenpayd || $verifyOpenpayd['validate'] === false) {
            Log::info("Verification failed");
            return response()->json(['failed' => 'Invalid request data.'], 422);
        }
        $trData["status"] = $trData["status"] === 'COMPLETED' ? 'Approved' : $trData["status"];

        try {
            if ($trData['type'] === 'PAYIN') {
                $payin_transaction = $verifyOpenpayd['transaction'];
                $openpayd_account = VirtualBankAccount::where('internal_account_id', $trData['internalAccountId'])->first();

                if (empty($openpayd_account)) {
                    Log::info("Payin request failed, Account not found!");
                    return response()->json(['failed' => 'Account not found!'], 200);
                }

                $account = $openpayd_account->account;
                $user = $this->getUserByAccountId($account->id);
                $transaction_date = Carbon::parse($trData['transactionDateTime'])->format('Y-m-d H:i:s');

                DB::beginTransaction();

                // Get Transaction method
                $transaction_method = TransactionMethod::where('name', $trData['paymentType'])->first();
                // $transaction_status = $trData['status'] == "COMPLETED" ? "Approve" : "Pending";
                $transaction_status = $trData['status'];

                // create TR transaction request
                $trn_request = new TRRequest();
                $trn_request->account_id = $account->id;
                $trn_request->currency_id = $account->account_currency;
                $trn_request->transaction_type = "Credit";
                $trn_request->transaction_nature = "TT";
                $trn_request->transaction_date = $transaction_date;
                $trn_request->amount = $trData['amount']['value'];
                $trn_request->remarks = $trData['transactionReference'];
                // $trn_request->status = "Pending";
                $trn_request->status = $transaction_status;
                $trn_request->created_by = $user->id;

                if (!empty($transaction_method)) {
                    $trn_request->transaction_method_id = $transaction_method->id;
                }

                $trn_request->save();

                // create client transaction
                $trn = new AccountTransaction();
                $trn->account_id = $account->id;
                $trn->tr_request_id = $trn_request->id;
                $trn->currency_id = $account->account_currency;
                $trn->transaction_type = "Credit";
                $trn->transaction_nature = "TT";
                $trn->transaction_date = $transaction_date;
                $trn->amount = $trData['amount']['value'];
                $trn->remarks = $trData['transactionReference'];
                // $trn->status = "Pending";
                $trn->status = $transaction_status;
                $trn->created_by = $user->id;
                $trn->approved_by = $user->id;
                $trn->approved_at = Carbon::now();

                if (!empty($transaction_method)) {
                    $trn->transaction_method_id = $transaction_method->id;
                }

                $trn->save();

                $trn_request->link_tr_id = $trn->id;
                $trn_request->save();

                // Create Openpayd transaction
                $formatted_created_date = Carbon::createFromFormat('Y-m-d\TH:i:s.u\Z', $payin_transaction->createdDate)->format('Y-m-d H:i:s.u');
                $formatted_updated_date = Carbon::createFromFormat('Y-m-d\TH:i:s.u\Z', $payin_transaction->updatedDate)->format('Y-m-d H:i:s.u');

                // create openpayd transaction (Payin)
                $openpayd_transaction = new VirtualBankTransaction();
                $openpayd_transaction->transaction_id = $trData['id'];
                $openpayd_transaction->trn_id = $trn_request->id;
                $openpayd_transaction->parent_transaction_id = $trn->id;
                $openpayd_transaction->virtual_bank_transaction_id = $trData['transactionId'];
                $openpayd_transaction->short_id = $trData['shortId'];
                $openpayd_transaction->account_id = $payin_transaction->accountId;
                $openpayd_transaction->transaction_created_date = $formatted_created_date;
                $openpayd_transaction->transaction_updated_date = $formatted_updated_date;
                $openpayd_transaction->status = $trData['status'];
                $openpayd_transaction->type = $trData['type'];
                $openpayd_transaction->provider_name = 'openpayd';
                $openpayd_transaction->payment_type = $trData['paymentType'];
                $openpayd_transaction->transaction_updated_date = $trData['transactionDateTime'];
                $openpayd_transaction->transaction_category = $payin_transaction->transactionCategory;
                $openpayd_transaction->compliance_status = $payin_transaction->complianceStatus;
                $openpayd_transaction->account_holder_id = $trData['accountHolderId'];
                // $openpayd_transaction->response = json_encode($trData);
                $openpayd_transaction->save();

                $data = [
                    'trn_id' => $trn_request->id,
                    "amount" => isset($trData['amount']['value']) ? $trData['amount']['value'] : null,
                    "transaction_date" => $trn_request->transaction_date,
                    "remarks" => isset($trData['transactionReference']) ? $trData['transactionReference'] : null,
                    'zeroize' => null,
                    'service_fee_client_account' => $account->id,
                    'service_fee_bank_account_id' => Common::getBankAccountIdByCurrencyId($account->account_currency, 'is_service_fee_bank'),
                    'service_fee_currency_id' => $account->account_currency,
                    'service_fee' => 0,
                    'service_fee_remarks' => '',
                    'is_service_fee_trn_hidden_for_client' => null,
                    'trn_fee_client_account' => $account->id,
                    'trn_fee_bank_account_id' => Common::getBankAccountIdByCurrencyId($account->account_currency, 'is_trn_fee_bank'),
                    'trn_fee_currency_id' => $account->account_currency,
                    'trn_fee' => 0,
                    'trn_fee_remarks' => '',
                    'is_trn_fee_trn_hidden_for_client' => null
                ];

                Common::saveTransactionDetails($data, $trn_request);
                Common::handleFeesOnPayinTransactionApproval($trn_request, $trn);

                DB::commit();

                // notify finance team for Payin transaction.
                EmailNotification::notifyFinanceManagersForTRRequest($trn_request, 'payin');
            } else {

                // Handle Payout transaction
                $transaction_id = $trData['transactionId'];
                $remarks = null;

                // In case of RETURN_IN transaction, get original transaction by id and then update status.
                if ($trData["type"] === "RETURN_IN" || $trData['type'] === 'RETURN_OUT') {
                    $transaction_id = $trData['originalTransactionId'];
                    $trData["status"] = 'Rejected';
                    $remarks = 'Return In - Rejected from Openpayd';
                } else if ($trData["status"] === "CANCELLED" || $trData['status'] === 'FAILED') {
                    $remarks = $trData["status"] . ' from Openpayd';
                    $trData["status"] = 'Rejected';
                }

                $openpayd_transaction = VirtualBankTransaction::where('virtual_bank_transaction_id', $transaction_id)->first();
                // Log::info("\n Getting Openpayd transaction from CAIB Database  ----- \n" . $openpayd_transaction . "\n\n");

                if (!empty($openpayd_transaction)) {
                    $openpayd_transaction->status = $trData['status'];
                    $openpayd_transaction->save();

                    $update_data = ['status' => $trData['status']];
                    if ($remarks !== null) {
                        $update_data['reject_reason'] = $remarks;
                    }

                    $update_data = ['status' => $trData['status']];
                    if ($remarks !== null) {
                        $update_data['reject_reason'] = $remarks;
                    }

                    // update transaction status in TRRequest table
                    TRRequest::where('id', $openpayd_transaction->trn_id)->update($update_data);

                    // update transaction status in Account Transaction table.
                    AccountTransaction::where('id', $openpayd_transaction->parent_transaction_id)->update($update_data);

                    $account_transaction = $openpayd_transaction->accountTransaction;
                    // Log::info("Account Transaction ------- \n" . $account_transaction . "\n\n");

                    if ($trData['type'] === "TRANSFER" && !empty($account_transaction->link_tr_id) && $account_transaction->transaction_nature === 'Internal') {
                        // Log::info("Credit account transaction for Internal transfer ------- \n" . $account_transaction . "\n\n");
                        // update transaction status in Credit Account Transaction table.
                        AccountTransaction::where('id', $account_transaction->link_tr_id)->update($update_data);
                    }

                    // notify client for transaction approval
                    // dd($openpayd_transaction);
                    if ($openpayd_transaction->status == "Approved") {
                        EmailNotification::notifyTransactionApproval($account_transaction);
                    } else if ($openpayd_transaction->status == "Rejected") {
                        EmailNotification::notifyTransactionRejection($account_transaction);
                    }
                }
            }

            // DB::commit();
            return response()->json(['success' => true]);
        } catch (Exception $e) {
            Log::error("Transaction Error: " . $e->getMessage(), ['line' => $e->getLine(), 'trace' => $e->getTraceAsString()]);
            DB::rollBack();
            return response()->json(['failed' => 'Transaction processing error.'], 400);
        }
    }

    public function bitPaceCallback(Request $request)
    {
        // Process webhook payload
        // Perform actions based on the webhook data
        $webhookData = file_get_contents("php://input");
        if (!$this->verifyBitpaceCallbackSecret($request, $webhookData)) {
            return response()->json(['failed' => 'Invalid callback signature.'], 401);
        }
        $response = $this->performTransactions($webhookData);

        //file_put_contents("/var/www/html/cib/storage/logs/bitpace.log", $webhookData. '\n' .$this->getCustomHeaders());
        return $response;
    }

    public function neroDataCallback(Request $request)
    {
        //die('hello');
        $webhookData = file_get_contents("php://input");
        //dd($webhookData);
        //$data = json_decode($webhookData);
        Log::info("\n\n  Nero Data Call Back triggered: " . $webhookData . '\n');

        if (!empty($webhookData)) {
            $data = json_decode($webhookData);

            $trn = TRRequest::find($data->payload->transactionId);

            if ($trn->status == "Approved") {
                Log::info("\n\n  Nero Data webhook already processed:  \n");
                NeroDataHelper::storeApiResponseData($trn->id, $webhookData, 'webhook');
                return response()->json(['success' => 'Transaction already approved.']);
            }
            $user_id = $trn->created_by;
            $client = $trn->account->client;

            $user = User::where('id', $user_id)->first();

            $hasAPIrole = User::where('id', $user_id)->whereHas('roles', function ($query) {
                $query->where('name', 'API');
            })
                ->whereHas('linkClients', function ($query) use ($client) {
                    $query->where('client_id', $client->id);
                })
                ->exists();

            if (!empty($user)) {
                if ($hasAPIrole && ($data->payload->status == 'COMPLETED' || $data->payload->status == 'COMPLETED_AUTO') && $trn->transaction_nature == 'Internal' && !$user->is_internal_transfer_approval_required) {

                    $auth_user = User::where('code', 'BASIC_API_NERO')->first();
                    Auth::login($auth_user);
                    $approved = $this->approveInternalPendingTransaction($trn->id);
                    Log::info('NeroData: TRRequest approved via webhook', ['tr_request_id' => $trn->id]);
                }
            }

            $neroDataHelper = new NeroDataHelper();
            if ($neroDataHelper->updateTReqComplianceStatus($webhookData)) {
                Log::info("\n\n  Nero Data webhook processed successfully:  \n");
                /*  if ($data->payload->status === 'MODERATION') {
                    EmailNotification::notifyComplianceTeamForAMLTransaction($data->payload->transactionId, $data->payload->id);
                } */
                //$this->storeWebhookData($transaction_id, $data, 'Nero Data')
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return response()->json(['success' => 'Request processed successfully.']);
            }
        }
        Log::info("\n\n  Nero Data webhook failed:  \n");
        return response()->json(['failed' => 'Request failed.']);
    }

    public function performTransactions($data)
    {

        $trData = json_decode($data);
        if (!$this->verifyWebhooksData($trData)) {
            //return false;
            return response()->json(['failed' => 'Invalid Order data.']);
        }

        $currency_id = $this->getCurrencyId($trData->cryptocurrency);
        $type = $this->getTransactionType($trData->type);
        $order_id = $trData->order_id;
        $remarks = $type;


        $approved_by = null;

        if ($type == 'Deposit' || $type == 'Buy') {
            $nature = 'Credit';
            $status = 'Approved';
            $approved_by = 0;
        } else {
            $nature = 'Debit';
            $status = 'Pending';
        }


        if (!empty($trData->address) && $type == 'Deposit') {

            $wallet_address = $trData->address;
            $dt = '';
            if ($trData->cryptocurrency == 'XRP') {
                $addressArr = explode('?dt=', $wallet_address);
                if (count($addressArr) > 0) {
                    if (isset($addressArr[0]))
                        $wallet_address = $addressArr[0];
                    if (isset($addressArr[1]))
                        $dt = $addressArr[1];
                }
            }


            $account_id = $this->getAccountIdByAddress($wallet_address, $dt);
            if (empty($account_id)) {
                return response()->json(['failed' => 'Invalid request']);
                die();
            }
        } else {
            $account_id = $this->getClientAccountByReference($trData->customer_reference_id, $trData->type, $currency_id);
        }

        $response = false;
        //$order_id = 134521;
        if (!$this->orderExists($order_id) && $trData->status !== 'FAILED') {
            if ($account_id && $currency_id) {

                $user = $this->getUserByAccountId($account_id);

                if ($approved_by === null) {
                    $approved_by = $user->id;
                }

                $dt_trn_req = new TRRequest();
                $dt_trn_req->account_id = $account_id;
                //$dt_trn_req->crypto_transaction_id = $order_id;
                $dt_trn_req->currency_id = $currency_id;
                $dt_trn_req->transaction_type = $nature;
                $dt_trn_req->transaction_nature = $type;
                $dt_trn_req->transaction_date = date("Y-m-d H:i:s", time());

                $dt_trn_req->amount = $trData->order_amount;
                $dt_trn_req->crypto_amount = $trData->cryptocurrency_amount;
                //$dt_trn_req->is_crypto = 1;
                $dt_trn_req->remarks = 'Deposit';
                $dt_trn_req->status = $status;
                $dt_trn_req->crypto_system = 'Bitpace';
                $dt_trn_req->created_by = $user->id;
                $dt_trn_req->approved_by = $approved_by;
                $dt_trn_req->save();

                $dt_trn = new AccountTransaction();
                $dt_trn->account_id = $account_id;
                $dt_trn->crypto_transaction_id = $order_id;
                $dt_trn->currency_id = $currency_id;
                $dt_trn->transaction_type = $nature;
                $dt_trn->transaction_nature = $type;
                $dt_trn->transaction_date = date("Y-m-d H:i:s", time());
                $dt_trn->amount = $trData->cryptocurrency_amount;
                $dt_trn->is_crypto = 1;
                $dt_trn->remarks = $remarks;
                $dt_trn->status = $status;
                $dt_trn->crypto_system = 'Bitpace';
                $dt_trn->created_by = $user->id;
                $dt_trn->approved_by = $approved_by;
                $dt_trn->tr_request_id = $dt_trn_req->id;
                if ($dt_trn->save()) {
                    if ($status == 'Approved') {
                        EmailNotification::notifyClientForTransactionApproval($dt_trn_req);
                        if ($type == 'Deposit') {
                            // Log::info("\n\n  Transaction Request Data: " . json_encode($dt_trn_req). '\n Type '.$type);
                            EmailNotification::notifyFinanceManagersForTRRequest($dt_trn_req, $type);
                        }
                    }
                    $response = true;
                } else {
                    $response = false;
                }
            }
        } else {

            //$dt_trn_req = TRRequest::where('id','1547')->select('*')->first();
            //Log::info("\n\n  Sending Email for Deposit to FM: " . json_encode($dt_trn_req). '\n Type'.$type);
            //EmailNotification::notifyFinanceManagersForTRRequest($dt_trn_req , 'Deposit');

            $this->updateTransactionStatus($order_id, $trData->status, $type);

            $response = true;
        }
        return response()->json(['success' => $response]);
    }
    public function getAccountIdByAddress($wallet_address, $dt = '')
    {

        if (empty($dt)) {
            $address = DB::table('crypto_addresses')->where('crypto_address', $wallet_address)->first();

            //$address->where('destination_tag',$dt);
        } else {
            $address = DB::table('crypto_addresses')->where('destination_tag', $dt)->where('crypto_address', $wallet_address)->first();
        }


        if (!empty($address))
            return $address->account_id;
        else
            return '';
    }

    function getUserByAccountId($account_id)
    {

        $user = Account::where('accounts.id', $account_id)
            ->leftJoin('client_users', 'client_users.client_id', '=', 'accounts.client_id')
            ->leftJoin('clients', 'clients.id', '=', 'client_users.client_id')
            ->leftJoin('users', 'users.id', '=', 'client_users.user_id')
            ->select('users.*')
            ->first();
        return $user;
    }

    public function getOrderById($order_id)
    {
        $order = AccountTransaction::where('crypto_transaction_id', $order_id)->select('id', 'status')->first();
        return $order;
    }

    public function orderExists($order_id)
    {
        $order = AccountTransaction::where('crypto_transaction_id', $order_id)->select('id')->first();
        if ($order) {
            if ($order->id != '')
                return true;
        }
        return false;
    }

    public function getTransactionType($trType)
    {
        $type = '';
        if ($trType == 'DEPOSIT' || $trType == 'COIN_STORE') {
            $type = 'Deposit';
        }
        return $type;
    }

    public function storeWebhookData($transaction_id, $data, $crypto_system)
    {
        DB::table('crypto_transactions_log')->insert([
            'transaction_id' => $transaction_id,
            'details' => $data,
            'crypto_system' => $crypto_system,
            'source' => 'webhook',
            'date_created' => date('Y-m-d H:i:s', time()),
            'created_at' => date('Y-m-d H:i:s', time()),
            'updated_at' => date('Y-m-d H:i:s', time())
        ]);
    }

    public function verifyOpenPaydWebhooksData($webhook_data)
    {
        $OpenPaydHelper = new OpenpaydApis();
        $trn_data = $OpenPaydHelper->getTransactionById($webhook_data['id']);

        // Log::info("\n\n Inside OpenPayd API, get transaction by id: " . json_encode($trn_data));

        if (empty($trn_data->id)) {
            return false;
        }
        $transaction_id = '';
        if (!empty($webhook_data['transactionId'])) {
            $transaction_id = $webhook_data['transactionId'];
        }

        if ($webhook_data['type'] === 'TRANSFER' && $webhook_data['originalTransactionId'] !== null) {
            $op_trn = VirtualBankTransaction::where('virtual_bank_transaction_id', $webhook_data['originalTransactionId'])->first();
            if (!empty($op_trn)) {
                $transaction_id = $webhook_data['originalTransactionId'];
                $webhook_data['type'] = 'SWEEP_TRANSFER';
            } else {
                $transaction_id = $webhook_data['transactionId'];
            }
        } else {
            $transaction_id = $webhook_data['transactionId'];
        }
        $this->storeWebhookData($transaction_id, json_encode($webhook_data), 'OpenPayd');

        $commonChecks =
            $trn_data->shortId === $webhook_data['shortId'] &&
            $trn_data->transactionId === $webhook_data['transactionId'] &&
            $trn_data->accountHolderId === $webhook_data['accountHolderId'];

        switch ($webhook_data["type"]) {
            case "TRANSFER":
                return [
                    'transaction' => $trn_data,
                    'validate' => $commonChecks &&
                        $trn_data->sourceInfo->internalAccountId === $webhook_data['sourceInfo']['internalAccountId'] &&
                        $trn_data->destinationInfo->internalAccountId === $webhook_data['destinationInfo']['internalAccountId'] &&
                        $trn_data->totalAmount->value == $webhook_data['totalAmount']['value'] &&
                        $trn_data->totalAmount->currency === $webhook_data['totalAmount']['currency']
                ];

            case "PAYIN":
                return [
                    'transaction' => $trn_data,
                    'validate' => $commonChecks &&
                        $trn_data->destinationInfo->internalAccountId === $webhook_data['internalAccountId'] &&
                        $trn_data->totalAmount->value == $webhook_data['amount']['value'] &&
                        $trn_data->totalAmount->currency === $webhook_data['amount']['currency']
                ];

            case "PAYOUT":
                return [
                    'transaction' => $trn_data,
                    'validate' => $commonChecks &&
                        $trn_data->transactionId === $webhook_data['transactionId'] &&
                        $trn_data->sourceInfo->internalAccountId === $webhook_data['sourceInfo']['internalAccountId'] &&
                        $trn_data->destinationInfo->identifier === $webhook_data['destinationInfo']['identifier'] &&
                        $trn_data->totalAmount->value == $webhook_data['totalAmount']['value'] &&
                        $trn_data->totalAmount->currency === $webhook_data['totalAmount']['currency']
                ];

            case 'SWEEP_TRANSFER':
                return [
                    'transaction' => $trn_data,
                    'validate' => $commonChecks &&
                        $trn_data->sourceInfo->internalAccountId === $webhook_data['sourceInfo']['internalAccountId'] &&
                        $trn_data->destinationInfo->internalAccountId === $webhook_data['destinationInfo']['internalAccountId'] &&
                        abs($trn_data->totalAmount->value) == abs($webhook_data['totalAmount']['value']) &&
                        $trn_data->totalAmount->currency === $webhook_data['totalAmount']['currency']
                ];

            case "RETURN_IN":
                return [
                    'transaction' => $trn_data,
                    'validate' => $commonChecks &&
                        $trn_data->destinationInfo->internalAccountId === $webhook_data['destinationInfo']['internalAccountId'] &&
                        $trn_data->totalAmount->value == $webhook_data['amount']['value'] &&
                        $trn_data->totalAmount->currency === $webhook_data['amount']['currency']
                ];

            default:
                return false;
        }
    }

    /**
     * When Bitpace sends a signature header, require HMAC-SHA256 of the raw body
     * using the module callback secret. If no signature header is present, keep
     * the existing getOrderV2 verification so current callbacks still succeed.
     */
    protected function verifyBitpaceCallbackSecret(Request $request, $rawBody): bool
    {
        try {
            $secret = (new BitpaceMerchantApis())->getCallbackSecret();
        } catch (\Throwable $e) {
            Log::error('Bitpace callback secret unavailable: ' . $e->getMessage());
            return false;
        }

        if ($secret === null || $secret === '') {
            if (\App\Modules\Security\Services\SecurityConfigService::requireBitpaceHmac()) {
                return false;
            }
            return true;
        }

        $provided = $request->header('X-Signature')
            ?: $request->header('X-Bitpace-Signature')
            ?: $request->header('X-Callback-Signature');

        if ($provided === null || $provided === '') {
            if (\App\Modules\Security\Services\SecurityConfigService::requireBitpaceHmac()) {
                return false;
            }
            return true;
        }

        $expected = hash_hmac('sha256', (string) $rawBody, (string) $secret);

        return hash_equals($expected, (string) $provided);
    }

    public function verifyWebhooksData($webhook_data)
    {

        $bitpaceHelper = new BitpaceMerchantApis();
        $order_data = $bitpaceHelper->getOrderV2($webhook_data->order_id);

        //print_r($order_data);
        // print_r( $order_data->order_id );
        Log::info("\n\n  Inside verifyWebhooksData WebHook Data: " . json_encode($webhook_data));
        Log::info("\n\n  Inside verifyWebhooksData Order V2 Api Data: " . json_encode($order_data));
        //$order_data  = $order_data;

        if (!empty($order_data->order_id)) {
            $this->storeWebhookData($webhook_data->order_id, json_encode($webhook_data), 'Bitpace');



            if (
                $order_data->order_id == $webhook_data->order_id &&
                $order_data->type == $webhook_data->type &&
                $order_data->order_amount == $webhook_data->order_amount &&
                $order_data->currency == $webhook_data->currency &&
                $order_data->cryptocurrency_amount == $webhook_data->cryptocurrency_amount &&
                $order_data->cryptocurrency == $webhook_data->cryptocurrency && $webhook_data->status != 'FAILED'

            ) {
                return true;
            } else {
                return false;
            }
        } else {

            return false;
        }
    }

    public function getClientAccountByReference($reference, $type, $currency)
    {
        // email address
        ////customer_reference_id = COIN_STORE:726
        //echo $reference; die();
        //Log::info(print_r($reference, true));
        if ($type == 'COIN_STORE') {
            $customerRefArr = explode(':', $reference);

            if (isset($customerRefArr[1])) {
                $client_ref_id =  $customerRefArr[1];
            } else {
                $client_ref_id = $reference;
            }
            $account_id = $this->getAccountById($client_ref_id);
        } else {
            if (filter_var($reference, FILTER_VALIDATE_EMAIL)) {
                $account_id =  $this->getAccountByEmailandCurrency($reference, $currency);
            } else {
                $account_id  = $reference;
            }
        }

        return $account_id;
    }

    public function getAccountById($account_id)
    {
        $account = Account::where("id", $account_id)->select("id")->first();
        if ($account) {
            return $account->id;
        }
        return;
    }
    public function getAccountByEmailandCurrency($email, $currency_id)
    {
        $account  = User::where('email', $email)
            ->leftJoin('client_users', 'client_users.user_id', '=', 'users.id')
            ->leftJoin('clients', 'clients.id', '=', 'client_users.client_id')
            ->leftJoin('accounts', 'accounts.client_id', '=', 'clients.id')
            ->where('accounts.account_currency', $currency_id)
            ->select('users.id')
            ->first();
        if ($account) {
            return $account->id;
        }
        return;
    }

    public function updateTransactionStatus($order_id, $status, $type)
    {
        if ($status == 'COMPLETED')
            $status = 'Approved';

        $transaction = AccountTransaction::where("crypto_transaction_id", $order_id)->select("tr_request_id")->first();
        $tr_id = $transaction->tr_request_id;
        //$transaction_req = $this->getTransactionByOrderId($tr_id);
        $trn_req = TRRequest::where('id', $tr_id)->first();
        if (!empty($tr_id)) {
            TRRequest::where('id', $tr_id)->update(['status' => $status]);
        }

        if ($order_id != '' && $status != '') {
            AccountTransaction::where('crypto_transaction_id', "$order_id")
                ->update(['status' => $status]);
        }

        $order = $this->getOrderById($order_id);
        // echo $order->status. ' => ' .$status  . ' => ' . $trn_req->status;
        //Log::info("\n\n  Update pending transaction email: " . $order->status. ' => ' .$status  . ' => ' . $trn_req->status);
        //$order->status == 'Pending' && $status == 'Approved' &&
        if ($trn_req->status == 'Pending') {
            EmailNotification::notifyClientForTransactionApproval($trn_req);
            EmailNotification::notifyFinanceManagersForTRRequest($trn_req, $type);
        }
    }

    public function getCurrencyId($currency_code)
    {
        $currency = Currency::where("is_active", true)
            ->where('short', $currency_code)->select("id")->first();
        return $currency->id;
    }

    public function getCustomHeaders()
    {
        $headers = array();
        foreach ($_SERVER as $key => $value) {
            if (preg_match("/^HTTP_X_/", $key))
                $headers[$key] = $value;
        }
        return json_encode($headers);
    }

    public function approveInternalPendingTransaction($trn_id)
    {

        try {
            $req = TRRequest::find($trn_id);
            // dd( $req);
            if ($req) {
                $client = ClientHelper::getClientByAccountId($req->account_id);

                //$trn_date = Carbon::createFromFormat("d/m/Y", $request->get("transaction_date"));
                // $transaction_date_raw = $req->transaction_date ?: $req->created_at;
                // try {
                //     if ($transaction_date_raw instanceof Carbon) {
                //         $transaction_date_obj = $transaction_date_raw;
                //     } elseif (is_numeric($transaction_date_raw)) {
                //         // Epoch milliseconds or seconds
                //         $transaction_date_obj = strlen((string) $transaction_date_raw) >= 13
                //             ? Carbon::createFromTimestampMs((int) $transaction_date_raw)
                //             : Carbon::createFromTimestamp((int) $transaction_date_raw);
                //     } elseif (is_string($transaction_date_raw)) {
                //         // Try common exact formats then fallback to parse
                //         try {
                //             $transaction_date_obj = Carbon::createFromFormat('Y-m-d H:i:s', $transaction_date_raw);
                //         } catch (\Throwable $e1) {
                //             try {
                //                 $transaction_date_obj = Carbon::createFromFormat('d/m/Y H:i:s', $transaction_date_raw);
                //             } catch (\Throwable $e2) {
                //                 try {
                //                     $transaction_date_obj = Carbon::createFromFormat('d/m/Y', $transaction_date_raw);
                //                 } catch (\Throwable $e3) {
                //                     $transaction_date_obj = Carbon::parse($transaction_date_raw);
                //                 }
                //             }
                //         }
                //     } else {
                //         $transaction_date_obj = Carbon::now();
                //     }
                // } catch (\Throwable $e) {
                //     Log::warning('approveTTPendingTransaction: failed to parse transaction_date for TRRequest id ' . $req->id . ' using raw value: ' . json_encode($transaction_date_raw) . ' | error: ' . $e->getMessage());
                //     $transaction_date_obj = $req->created_at instanceof Carbon ? $req->created_at : Carbon::parse($req->created_at);
                // }

                // $credit_transaction_date = $transaction_date_obj->format("Y-m-d H:i:s");
                // $debit_transaction_date = $transaction_date_obj->addSeconds(10)->format("Y-m-d H:i:s");
                // $receiver_account = $req->receiver;
                // $transactionAmount = $req->amount;

                // check if the receiver account is active
                // if (!$receiver_account->is_active || !$req->account->is_active) {
                //     return;
                // }

                // if ($req->currency->is_openpayd && $receiver_account->currency->is_openpayd) {
                //     $is_openpayd_enabled = Common::isModuleEnabled('OpenPayd');
                //     if (!$is_openpayd_enabled) {
                //         return;
                //     }
                // }

                $trn_fee = 0;

                // check if there is enough balance in the client account
                // $amount =  $req->amount;
                // $balance = Common::getAccountBalanceById($req->account->id);

                // if ($amount > $balance) {
                //     return;
                // }

                $dt_trn = AccountTransaction::where('tr_request_id', $req->id)
                    ->where('transaction_type', 'Debit')
                    ->where('transaction_nature', 'Internal')
                    ->where('status', 'Pending')->first();
                //  dd( $dt_trn);
                $cr_trn = AccountTransaction::where('tr_request_id', $req->id)
                    ->where('transaction_type', 'Credit')
                    ->where('transaction_nature', 'Internal')
                    ->where('status', 'Pending')->first();
                // if (!$dt_trn || !$cr_trn) {
                // dd([
                //     'dt_trn' => $dt_trn,
                //     'cr_trn' => $cr_trn,
                //     'reason' => 'One or both transactions not found'
                // ]);


                $status = "Approved";
                $approved_by = 0;
                $approved_at = Carbon::now();
                $is_for_client = true;
                //AccountTransaction::unguard();
                // $dt_trn->approved_by = $approved_by;
                // $dt_trn->approved_at = $approved_at;
                // $dt_trn->status = $status;
                // $dt_trn->update();
                // dd($dt_trn);
                // $cr_trn->approved_by = $approved_by;
                // $cr_trn->approved_at = $approved_at;
                //$cr_trn->status = $status;
                //$cr_trn->update();
                //DB::commit();
                $dt_trn->update([
                    'approved_by' => 0,
                    'approved_at' => now(),
                    'status' => $status,
                ]);

                $cr_trn->update([
                    'approved_by' => 0,
                    'approved_at' => now(),
                    'status' => $status,
                ]);
                // dd($cr_trn);
                // DB::commit();
                //AccountTransaction::reguard();
                if ($status == "Approved") {
                    $receiver_account = Account::where("account_number", $req->receiver_account_number)->first();
                    // approve transaction request
                    // dd($req);
                    $req->status = "Approved";
                    $req->link_tr_id = $dt_trn->id;
                    $req->approved_by = 0;
                    $req->update();

                    if ($client->parent_id) {
                        Common::transferMoneyToParentAccount($cr_trn, $client->parent_id, []);
                    } else {
                        FeeHelper::reconcileMaintenanceFee($cr_trn->account_id, $cr_trn->amount, []);
                    }

                    Log::info("\n\n\n ------------------------------------- Transaction ------------------------------------------ \n");
                    Log::info('Transaction ID: ' . $cr_trn->id . ' | Status: ' . $cr_trn->status . ' | Amount: ' . $cr_trn->amount . "\n");
                    Log::info("\n\n\n ------------------------------------- Transaction Saved Completed ------------------------------------------ \n");

                    //if ($cr_trn->status === 'Approved') {
                    // notify client
                    EmailNotification::notifyClientForTransactionApproval($cr_trn);
                    //}
                } else {
                    Log::info('Failed to Approve Transaction ID: ' . $cr_trn->id . ' | Status: ' . $cr_trn->status . ' | Amount: ' . $cr_trn->amount . "\n");
                }

                // DB::beginTransaction();

                // if ($req->link_tr_id) {
                //     $transaction = AccountTransaction::find($req->link_tr_id);

                //     $transaction->transaction_date = $debit_transaction_date;
                //     $transaction->amount = $amount;
                //     $transaction->ex_rate = 1;
                //     $transaction->remarks = $req->remarks;
                //     $transaction->status = 'Approved';
                //     $transaction->approved_by = 0;
                //     $transaction->approved_at = Carbon::now();

                //     \Illuminate\Database\Eloquent\Model::withoutEvents(function () use ($transaction) {
                //         $transaction->save();
                //     });

                //     if ($transaction->link_tr_id) {
                //         $cr_trn = AccountTransaction::find($transaction->link_tr_id);

                //         $cr_trn->transaction_date = $debit_transaction_date;
                //         $cr_trn->amount = $amount;
                //         $cr_trn->ex_rate =  1;
                //         $cr_trn->remarks = $req->remarks;
                //         $cr_trn->status = 'Approved';
                //         $cr_trn->approved_by = 0;
                //         $cr_trn->approved_at = Carbon::now();
                //         \Illuminate\Database\Eloquent\Model::withoutEvents(function () use ($cr_trn) {
                //             $cr_trn->save();
                //         });
                //     }
                // } else {
                //     // create debit transaction
                //     $transaction = new AccountTransaction();

                //     $transaction->account_id = $req->account_id;
                //     $transaction->tr_request_id = $req->id;
                //     $transaction->currency_id = $req->currency_id;
                //     $transaction->transaction_type = "Debit";
                //     $transaction->transaction_nature = $req->transaction_nature;
                //     $transaction->transaction_date = $debit_transaction_date;
                //     $transaction->amount = $amount;
                //     $transaction->ex_rate = 1;
                //     $transaction->remarks = $req->remarks;
                //     $transaction->status = "Approved";
                //     $transaction->created_by = $req->created_by;
                //     $transaction->approved_by = 0;
                //     $transaction->approved_at = Carbon::now();

                //     \Illuminate\Database\Eloquent\Model::withoutEvents(function () use ($transaction) {
                //         $transaction->save();
                //     });

                //     // create credit transaction
                //     $cr_trn = new AccountTransaction();

                //     $cr_trn->account_id = $receiver_account->id;
                //     $cr_trn->parent_trn_id = $transaction->id;
                //     $cr_trn->tr_request_id = $req->id;
                //     $cr_trn->link_tr_id = $transaction->id;
                //     $cr_trn->currency_id = $receiver_account->account_currency;
                //     $cr_trn->transaction_type = "Credit";
                //     $cr_trn->transaction_nature = $req->transaction_nature;
                //     $cr_trn->transaction_date = $credit_transaction_date;
                //     $cr_trn->amount = $amount;
                //     $cr_trn->ex_rate =  1;
                //     $cr_trn->remarks = $req->remarks;
                //     $cr_trn->status = "Approved";
                //     $cr_trn->created_by = $req->created_by;
                //     $cr_trn->approved_by = 0;
                //     $cr_trn->approved_at = Carbon::now();

                //     \Illuminate\Database\Eloquent\Model::withoutEvents(function () use ($cr_trn) {
                //         $cr_trn->save();
                //     });

                //     // update link transaction id in the debit transaction
                //     $transaction->link_tr_id = $cr_trn->id;
                //     \Illuminate\Database\Eloquent\Model::withoutEvents(function () use ($transaction) {
                //         $transaction->save();
                //     });

                //     // get the client id for the credit transaction
                //     $client = ClientHelper::getClientByAccountId($cr_trn->account_id);

                //     if ($client->parent_id && $cr_trn->transaction_type == 'Credit') {
                //         Common::transferMoneyToParentAccount($cr_trn, $client->parent_id);
                //     }
                // }

                // // approve transaction request
                // $req->amount = $amount;
                // $req->link_tr_id = $transaction->id;
                // $req->ex_rate =  1;
                // $req->status = "Approved";

                // \Illuminate\Database\Eloquent\Model::withoutEvents(function () use ($req) {
                //     $req->save();
                // });

                // $feesRequest = new \Illuminate\Http\Request();
                // $feesRequest->replace([]);

                // Common::handleFeesOnTransactionApproval($feesRequest, $req, $transaction);
                // //Common::uploadSupportingDocuments($request, $req, $client);

                // if ($client instanceof \App\Models\Client && count($client->users) > 0) {
                //     if ($client->users[0]->user->hasRole('API')) {
                //         Common::sendWebhookNotification($transaction, $client->users[0]->user, 'transaction', 'Your transaction request has been Approved');
                //     }
                // }

                // DB::commit();


            } else {
                return;
            }
        } catch (Exception $e) {
            // DB::rollBack();

            return;
        }
    }

    private function processLorumTransaction(string $webhook)
    {
        $payload = json_decode($webhook);

        if (!is_object($payload) || empty($payload->type)) {
            // Log::warning('Invalid Lorum webhook payload', ['payload' => $webhook]);
            $this->lorumLogs($webhook, 'Invalid Lorum webhook payload');
            return response()->json(['failed' => 'Invalid webhook payload'], 400);
        }
        //dd($payload);
        try {
            $handlers = [
                'outbound_local_payment_created'    => fn() => $this->handleOutboundPayment($payload, 'created'),
                'outbound_local_payment_submitted'  => fn() => $this->handleOutboundPayment($payload, 'submitted'),
                'outbound_local_payment_executed'   => fn() => $this->handleOutboundPayment($payload, 'executed'),
                'outbound_local_payment_returned'   => fn() => $this->handleOutboundPaymentReturned($payload, 'returned'),
                'outbound_local_payment_failed'     => fn() => $this->handleOutboundPaymentReturned($payload, 'failed'),


                'inbound_local_payment_settled'     => fn() => $this->handleSettledInboundPayment($payload, 'settled'),
                'internal_transfer_created'         => fn() => $this->handleInternalTransferPayment($payload, 'created'),
                'internal_transfer_settled'         => fn() => $this->handleInternalTransferPayment($payload, 'settled'),
                'inbound_internal_transfer_settled' => fn() => $this->handleInboundInternalTransferPayment($payload, 'settled'),
                'fee_debit_executed'                => fn() => $this->handleFeeDebitExecuted($payload, 'executed'),
            ];

            return $handlers[$payload->type]() ?? response()->json(['message' => 'Unknown webhook type'], 400);
        } catch (Exception $e) {
            Log::error("Transaction Error: " . $e->getMessage(), ['line' => $e->getLine(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['failed' => 'Transaction processing error.'], 400);
        }
    }

    private function handleOutboundPayment(object $webhook, string $stage)
    {
        log::channel('lorumLogs')->info("Processing Lorum outbound payment [$stage]");

        $node = "outbound_local_payment_{$stage}";
        $transactionId = $webhook->{$node}->transaction_id ?? null;

        if (!$transactionId) {
            log::channel('lorumLogs')->warning('Transaction ID missing', ['stage' => $stage]);
            // $this->lorumLogs(['stage' => $stage], 'Transaction ID missing');
            return response()->json(['failed' => 'Lorum webhook validation failed!'], 400);
        }

        $transaction = VirtualBankTransaction::where('transaction_id', $transactionId)->first();

        if (!$transaction) {
            log::channel('lorumLogs')->info('Lorum transaction not found locally', ['transaction_id' => $transactionId]);
            // $this->lorumLogs(['transaction_id' => $transactionId], 'Lorum transaction not found locally');
            return response()->json(['failed' => 'Lorum webhook validation failed!'], 400);
        }

        if (!$this->verifyLorumWebhookData($transaction, $transactionId)) {
            log::channel('lorumLogs')->warning("Lorum webhook validation failed", ['stage' => $stage, 'transaction_id' => $transactionId]);
            // $this->lorumLogs(['stage' => $stage, 'transaction_id' => $transactionId], 'Lorum transaction not found locally');
            return response()->json(['failed' => 'Lorum webhook validation failed!'], 400);
        }

        log::channel('lorumLogs')->info("Lorum webhook [$stage] validated successfully", ['transaction_id' => $transactionId]);
        // $this->lorumLogs(['stage' => $stage, 'transaction_id' => $transactionId], "Lorum webhook [$stage] validated successfully");

        // Store web-hook data
        $this->storeWebhookData($transactionId, json_encode($webhook), 'Lorum');

        $currentPriority = self::STATUS_PRIORITY[$transaction->status] ?? 0;
        $newPriority = self::STAGE_PRIORITY[$stage] ?? 0;

        // log::info("Current Priority {$transaction->status} : {$currentPriority}  ----  New Priority {$stage}: {$newPriority}");

        if ($newPriority <= $currentPriority) {
            Log::info('Ignoring stale webhook', [
                'transaction_id' => $transactionId,
                'current_status' => $transaction->status,
                'incoming_stage' => $stage,
            ]);
            return response()->json(['ignored' => true], 200);
        }

        DB::beginTransaction();

        $status = self::STATUS_MAP[$stage];
        $transaction->status = $status;
        $transaction->save();

        TRRequest::where('id', $transaction->trn_id)->update(['status' => $status]);
        AccountTransaction::where('id', $transaction->parent_transaction_id)->update(['status' => $status]);

        DB::commit();

        log::channel('lorumLogs')->info("Lorum webhook Status: [$stage] updated  successfully", ['transaction_id' => $transactionId]);
        // $this->lorumLogs(['stage' => $stage, 'transaction_id' => $transactionId], "Lorum webhook [$stage] validated successfully");

        $status = self::STATUS_MAP[$stage];
        $account_transaction = $transaction->accountTransaction;

        if ($status == "Approved") {
            EmailNotification::notifyTransactionApproval($account_transaction);
        } else if ($status == "Rejected") {
            EmailNotification::notifyTransactionRejection($account_transaction);
        }
    }
    //lorum inbound
    private function handleSettledInboundPayment(object $webhook, string $stage)
    {
        // ---------------------------  Verifing In-Bound web-hook data.  ---------------------------

        $node = "inbound_local_payment_{$stage}";
        $inboundTransactionId = $webhook->{$node}->transaction_id ?? null;
        $verified_inbound_trn = $this->verifyLorumInboundWebhookData($inboundTransactionId, $webhook);
        if (empty($verified_inbound_trn)) {
            log::channel('lorumLogs')->warning("Lorum webhook validation failed", ['stage' => $stage, 'transaction_id' => $inboundTransactionId]);
            // $this->lorumLogs("Lorum webhook validation failed", ['stage' => $stage, 'transaction_id' => $inboundTransactionId]);
            return response()->json(['failed' => 'Lorum webhook validation failed!'], 400);
        }

        // Log::info("Lorum webhook [$stage] validated successfully", ['transaction_id' => $inboundTransactionId]);

        $lorum_account_id = $verified_inbound_trn->account_id;
        $lorum_account = VirtualBankAccount::where('virtual_bank_account_id', $lorum_account_id)->first();

        if (empty($lorum_account)) {
            log::channel('lorumLogs')->info("Payin/In-Bound transaction request failed, Account not found!");
            return response()->json(['failed' => 'Account not found!'], 400);
        }

        // Store In-Bound web-hook data
        $this->storeWebhookData($inboundTransactionId, json_encode($webhook), 'Lorum');

        // TODO: Need to create Sweep to Primary Transaction, if the Statement is Settled.
        $sweep_transaction  = '';
        if ($stage === 'settled') {
            // Create a Sweep to Primary Transaction.
            $sweep_transaction =  $this->manualLorumSweepTransaction($verified_inbound_trn);
        }

        $account = $lorum_account->account;
        $user = $this->getUserByAccountId($account->id);
        $transaction_date = Carbon::parse($verified_inbound_trn->created_at)->format('Y-m-d H:i:s');
        $transaction_amount = Common::toMajorAmount($verified_inbound_trn->instructed_amount); // Converting the amount to single unite.
        $transaction_reference = $verified_inbound_trn->external_reference;
        $transaction_status = self::STATUS_MAP[$stage];
        $transaction_method = isset($verified_inbound_trn->remitter->identifier->wire) ? 'WIRE' : 'IBAN';
        $transaction_method = TransactionMethod::where('name', $transaction_method)->first();

        DB::beginTransaction();

        // ---------------------------  Creating In-Bound Transaction.  ---------------------------
        // create TR transaction request
        $trn_request = new TRRequest();
        $trn_request->account_id = $account->id;
        $trn_request->currency_id = $account->account_currency;
        $trn_request->transaction_type = "Credit";
        $trn_request->transaction_nature = "TT";
        $trn_request->transaction_date = $transaction_date;
        $trn_request->amount = $transaction_amount;
        $trn_request->remarks = $transaction_reference;
        $trn_request->status = $transaction_status;
        $trn_request->created_by = $user->id;

        if (!empty($transaction_method)) {
            $trn_request->transaction_method_id = $transaction_method->id;
        }

        $trn_request->save();

        // create client transaction
        $trn = new AccountTransaction();
        $trn->account_id = $account->id;
        $trn->tr_request_id = $trn_request->id;
        $trn->currency_id = $account->account_currency;
        $trn->transaction_type = "Credit";
        $trn->transaction_nature = "TT";
        $trn->transaction_date = $transaction_date;
        $trn->amount = $transaction_amount;
        $trn->remarks = $transaction_reference;
        $trn->status = $transaction_status;
        $trn->created_by = $user->id;
        // $trn->approved_by = $user->id;
        $trn->approved_by = null;
        $trn->approved_at = Carbon::now();

        if (!empty($transaction_method)) {
            $trn->transaction_method_id = $transaction_method->id;
        }

        $trn->save();

        $trn_request->link_tr_id = $trn->id;
        $trn_request->save();

        $formatted_created_date = Carbon::createFromFormat('Y-m-d\TH:i:s.u\Z', $verified_inbound_trn->created_at)->format('Y-m-d H:i:s.u');
        $formatted_updated_date = Carbon::createFromFormat('Y-m-d\TH:i:s.u\Z', $verified_inbound_trn->created_at)->format('Y-m-d H:i:s.u');

        // create Virtual Bank transaction (Payin/In-Bound)


        $vb_transaction = new VirtualBankTransaction();
        $vb_transaction->transaction_id = $verified_inbound_trn->id;
        $vb_transaction->trn_id = $trn_request->id;
        $vb_transaction->parent_transaction_id = $trn->id;
        $vb_transaction->virtual_bank_transaction_id = $verified_inbound_trn->id;
        $vb_transaction->account_id = $verified_inbound_trn->account_id;
        $vb_transaction->transaction_created_date = $formatted_created_date;
        $vb_transaction->transaction_updated_date = $formatted_updated_date;
        $vb_transaction->status = $transaction_status;
        $vb_transaction->type = 'PAYIN';
        $vb_transaction->provider_name = 'Lorum';
        $vb_transaction->payment_type = $transaction_method->name;
        $vb_transaction->response = json_encode($webhook);
        $vb_transaction->save();
        //charge account fee and debit fee amount as corespondence charges.

        $this->debitCorespondenceCharges($sweep_transaction, $trn, $user, $account, $transaction_method, $transaction_status, $verified_inbound_trn);

        $data = [
            'trn_id' => $trn_request->id,
            "amount" => isset($transaction_amount) ? $transaction_amount : null,
            "transaction_date" => $trn_request->transaction_date,
            "remarks" => isset($transaction_reference) ? $transaction_reference : null,
            'zeroize' => null,
            'service_fee_client_account' => $account->id,
            'service_fee_bank_account_id' => Common::getBankAccountIdByCurrencyId($account->account_currency, 'is_service_fee_bank'),
            'service_fee_currency_id' => $account->account_currency,
            'service_fee' => 0,
            'service_fee_remarks' => '',
            'is_service_fee_trn_hidden_for_client' => null,
            'trn_fee_client_account' => $account->id,
            'trn_fee_bank_account_id' => Common::getBankAccountIdByCurrencyId($account->account_currency, 'is_trn_fee_bank'),
            'trn_fee_currency_id' => $account->account_currency,
            'trn_fee' => 0,
            'trn_fee_remarks' => '',
            'is_trn_fee_trn_hidden_for_client' => null
        ];

        Common::saveTransactionDetails($data, $trn_request);

        Common::handleFeesOnPayinTransactionApproval($trn_request, $trn);

        DB::commit();

        // notify finance team for Payin transaction.
        EmailNotification::notifyFinanceManagersForTRRequest($trn_request, 'payin');
        return response()->json(['success' => 'PAYIN Created Successfully'], 200);
    }

    public function debitCorespondenceCharges($sweep_transaction, $trn, $user, $account, $transaction_method, $transaction_status, $verified_inbound_trn)
    {
        $sweep_transaction_id = $sweep_transaction ? $sweep_transaction->id : null;

        if ($sweep_transaction_id) {
            // $transaction_method =  Common::getTransactionMethods();
            $transaction_amount = $sweep_transaction->instructed_amount; // Set the fee amount as needed

            $transaction_amount = Common::toMajorAmount($transaction_amount);
            $fee_amount = 0;
            if (!empty($sweep_transaction->fee_amount)) {
                $fee_amount = $sweep_transaction->fee_amount; // Set the fee amount as needed
                $fee_amount = Common::toMajorAmount($fee_amount);
            }


            if ($fee_amount > 0) {
                $transaction_amount = $fee_amount; // Add fee as the transaction amount if fee is greater than 0
            } else {
                return;
            }

            $transaction_date = Carbon::parse($verified_inbound_trn->created_at)->format('Y-m-d H:i:s');
            // update sweep transaction with the parent transaction id.
            if ($transaction_amount > 0) {

                $trn_request = new TRRequest();
                $trn_request->account_id = $account->id;
                $trn_request->currency_id = $account->account_currency;
                $trn_request->transaction_type = "Debit";
                $trn_request->transaction_nature = "TT";
                $trn_request->transaction_date = $transaction_date;
                $trn_request->amount = $transaction_amount;
                $trn_request->remarks = 'Corespondence Fee';
                $trn_request->status = $transaction_status;
                $trn_request->created_by = $user->id;

                if (!empty($transaction_method)) {
                    $trn_request->transaction_method_id = null;
                }
                //  dd($trn_request);
                $trn_request->save();

                $sweep_dt_transaction = new AccountTransaction();
                $sweep_dt_transaction->account_id = $account->id;
                $sweep_dt_transaction->link_tr_id = $trn->id;
                $sweep_dt_transaction->currency_id = $account->account_currency;
                $sweep_dt_transaction->transaction_type = "Debit";
                $sweep_dt_transaction->transaction_nature = "TT";
                $sweep_dt_transaction->transaction_date = $transaction_date;
                $sweep_dt_transaction->amount = $transaction_amount;
                $sweep_dt_transaction->remarks = 'Corespondence Fee';
                $sweep_dt_transaction->status = $transaction_status;
                $sweep_dt_transaction->created_by = $user->id;
                $sweep_dt_transaction->tr_request_id = $trn_request->id;
                $sweep_dt_transaction->is_fee_transaction = true;
                // $trn->approved_by = $user->id;
                $sweep_dt_transaction->approved_by = null;
                $sweep_dt_transaction->approved_at = Carbon::now();

                if (!empty($transaction_method)) {
                    $sweep_dt_transaction->transaction_method_id = null;
                }

                $sweep_dt_transaction->save();
                //$sweep_dt_transaction->parent_transaction_id = $vb_transaction->id;
                //$sweep_dt_transaction->save();
            }
        }
    }

    private function verifyLorumWebhookData(VirtualBankTransaction $transaction, string $transactionId): bool
    {
        log::channel('lorumLogs')->info('Verifying Lorum webhook data', ['transaction_id' => $transactionId]);

        $lorumApi = new LorumApis();
        $lorumTransaction = $lorumApi->getTransactionById($transactionId);

        if (!$lorumTransaction) {
            log::channel('lorumLogs')->warning('Lorum transaction not found via API', ['transaction_id' => $transactionId]);
            return false;
        }

        $lorumTransaction = json_decode(json_encode($lorumTransaction));
        $accountTransaction = $transaction->accountTransaction;

        if (!$accountTransaction) {
            log::channel('lorumLogs')->warning('Account transaction missing', ['transaction_id' => $transactionId]);
            return false;
        }

        $transaction_amount = Common::toMinorAmount($accountTransaction->amount);

        if ($accountTransaction->beneficiary) {
            return
                $lorumTransaction->currency === $accountTransaction->currency->short &&
                $lorumTransaction->account_id === $transaction->account_id &&
                $lorumTransaction->beneficiary->name === $accountTransaction->beneficiary->name &&
                $lorumTransaction->instructed_amount == $transaction_amount;
        }

        return
            $lorumTransaction->currency === $accountTransaction->currency->short &&
            $lorumTransaction->account_id === $transaction->account_id &&
            $lorumTransaction->instructed_amount == $transaction_amount;
    }

    private function isSameInboundTransaction(object $lorumTransaction, object $webhookData): bool
    {
        return
            // Account match
            ($lorumTransaction->account_id ?? null) === ($webhookData->account_id ?? null)

            // Transaction ID match
            && ($lorumTransaction->id ?? null) === ($webhookData->transaction_id ?? null)

            // Currency match
            && ($lorumTransaction->currency ?? null) === ($webhookData->currency ?? null)

            // Amount match (same unit, no conversion needed here)
            && (float) ($lorumTransaction->instructed_amount ?? 0) === (float) ($webhookData->instructed_amount ?? 0)

            // Beneficiary name match
            && ($lorumTransaction->beneficiary->name ?? null) === ($webhookData->beneficiary->name ?? null)

            // Remitter name match
            && ($lorumTransaction->remitter->name ?? null) === ($webhookData->remitter->name ?? null);
    }

    private function verifyLorumInboundWebhookData(string $webhook_transaction_id, $webhook_data)
    {
        log::channel('lorumLogs')->info('Verifying Lorum webhook data', ['transaction_id' => $webhook_transaction_id]);

        $exists = VirtualBankTransaction::where('virtual_bank_transaction_id', $webhook_transaction_id)->exists();
        if ($exists) {
            log::channel('lorumLogs')->info('Inbound transaction already processed', ['transaction_id' => $webhook_transaction_id]);
            return null;
        }

        $lorumApi = new LorumApis();
        $lorumTransaction = $lorumApi->getTransactionById($webhook_transaction_id);

        if (!$lorumTransaction) {
            log::channel('lorumLogs')->warning('Lorum transaction not found via API', ['transaction_id' => $webhook_transaction_id]);
            return null;
        }

        $lorumTransaction = json_decode(json_encode($lorumTransaction));

        // verify web-hook data aginst the lorum-transactions.
        $webhook_data_inbound_trn = $webhook_data->inbound_local_payment_settled;
        $isMatch = $this->isSameInboundTransaction($lorumTransaction, $webhook_data_inbound_trn);

        if ($isMatch) {
            return $lorumTransaction;
        } else {
            return null;
        }
    }

    private function handleInboundInternalTransferPayment($webhook, string $status)
    {
        $node = "inbound_internal_transfer_settled";
        $internal_transfer_trn = $webhook->{$node} ?? null;
        $original_transaction_id = $internal_transfer_trn->external_id ?? null;


        if (empty($original_transaction_id)) {
            log::channel('lorumLogs')->warning('Lorum inbound internal transfer missing external_id', ['transaction_id' => $internal_transfer_trn->id ?? null,]);
            return response()->json(['failed' => 'Lorum webhook validation failed!'], 200);
        }

        $local_transaction = VirtualBankTransaction::where('transaction_id', $original_transaction_id)->first();

        if (empty($local_transaction)) {
            log::channel('lorumLogs')->warning('Lorum inbound internal transfer original transaction not found', ['original_transaction_id' => $original_transaction_id]);
            // If the Transaction is Sweep before Payout, then we need to store the web-hook data.
            $this->storeWebhookData($original_transaction_id, json_encode($webhook), 'Lorum');
            return response()->json(['failed' => 'Lorum webhook validation failed!'], 200);
        }

        // TODO: Need to verify the internal transfer transaction data with Lorum API data.
        if (!$this->verifyLorumWebhookData($local_transaction, $original_transaction_id)) {
            log::channel('lorumLogs')->warning("Lorum webhook validation failed", ['stage' => $status, 'transaction_id' => $original_transaction_id]);
            return response()->json(['failed' => 'Lorum webhook validation failed!'], 400);
        }

        // Add SWEEP_TRANSFER type to the internal transfer transaction.
        $webhook->type = 'SWEEP_TRANSFER';
        $webhook->status = $status;

        // Store In-Bound Internal Transfer web-hook data
        $this->storeWebhookData($original_transaction_id, json_encode($webhook), 'Lorum');

        return response()->json(['success' => 'Lorum In-Bound Internal Transfer processed successfully'], 200);
    }
    private function handleInternalTransferPayment($webhook, string $status)
    {
        $node = "internal_transfer_{$status}";
        $internalTransferTrn = $webhook->{$node} ?? null;

        if (!is_object($internalTransferTrn)) {
            log::channel('lorumLogs')->warning('Lorum inbound internal transfer node missing', ['expected_node' => $node]);
            return response()->json(['failed' => 'Invalid Lorum webhook payload'], 400);
        }

        $originalTransactionId = $internalTransferTrn->external_id ?? null;

        if (empty($originalTransactionId)) {
            log::channel('lorumLogs')->warning('Lorum inbound internal transfer missing external_id', ['transaction_id' => $internalTransferTrn->transaction_id ?? null,]);
            return response()->json(['failed' => 'Lorum webhook validation failed'], 200);
        }

        $localTransaction = VirtualBankTransaction::where('transaction_id', $originalTransactionId)->first();
        if (!$localTransaction) {
            log::channel('lorumLogs')->warning('Lorum inbound internal transfer original transaction not found', ['original_transaction_id' => $originalTransactionId]);
            return response()->json(['failed' => 'Original transaction not found'], 400);
        }

        // TODO: Need to verify the internal transfer transaction data with Lorum API data.
        if (!$this->verifyLorumWebhookData($localTransaction, $originalTransactionId)) {
            log::channel('lorumLogs')->warning("Lorum webhook validation failed", ['stage' => $status, 'transaction_id' => $originalTransactionId]);
            return response()->json(['failed' => 'Lorum webhook validation failed!'], 400);
        }

        // Add SWEEP_TRANSFER type to the internal transfer transaction.
        $webhook->type = 'SWEEP_TRANSFER';
        $webhook->status = $status;

        // Store webhook data
        $this->storeWebhookData($originalTransactionId, json_encode($webhook), 'Lorum');
        return response()->json(['success' => 'Lorum In-Bound Internal Transfer processed successfully'], 200);
    }

    private function manualLorumSweepTransaction($verified_inbound_trn)
    {
        $inbound_currency = $verified_inbound_trn->currency;
        $parent_transaction_id = $verified_inbound_trn->id;

        $instructed_amount = $verified_inbound_trn->instructed_amount;
        $fee_amount = $verified_inbound_trn->fee_amount;
        //$major_fee_amoynt = Common::toMajorAmount($fee_amount);

        if ($fee_amount > 0) {
            $transaction_amount = $instructed_amount - $fee_amount;
        } else {
            $transaction_amount = $instructed_amount;
        }


        $lorum_client_account_id = $verified_inbound_trn->account_id;
        $internal_transfer_reference = 'Sweep for In-Bound Transaction ID: ' . $verified_inbound_trn->id;

        $lorumApi = new LorumApis();
        $primary_account_id = $lorumApi->getPrimaryAccountByCurrency($inbound_currency);

        if ($primary_account_id == $lorum_client_account_id) {
            log::channel('lorumLogs')->info('Lorum Sweep to Primary Account skipped: Inbound account is already the primary account', ['account_id' => $lorum_client_account_id]);
            return;
        }

        // Lorum Internal Transfer Payload
        $payload = [
            'amount' => $transaction_amount,
            'currency' => $inbound_currency,
            "external_id" => $parent_transaction_id,
            "external_reference" => $internal_transfer_reference,
            "from_account_id" => $lorum_client_account_id,
            "to_account_id" => $primary_account_id
        ];

        $headers = ['Idempotency-Key' => md5($parent_transaction_id)];
        $internal_transfer_transaction = $lorumApi->createInternalTransfer($payload, $headers);
        $sweep_transaction_id = $internal_transfer_transaction["transaction_id"] ?? null;

        if (empty($sweep_transaction_id)) {
            log::channel('lorumLogs')->warning('Lorum internal transfer creation failed', ['payload' => $payload]);
            return response()->json(['failed' => 'Lorum internal Sweep transfer creation failed!'], 400);
        }

        $internal_transfer_transaction = $lorumApi->getTransactionById($sweep_transaction_id);
        $internal_transfer_transaction = json_decode(json_encode($internal_transfer_transaction));

        $this->storeWebhookData($sweep_transaction_id, json_encode($internal_transfer_transaction), 'Lorum');
        return $internal_transfer_transaction;
    }

    private function handleFeeDebitExecuted(object $webhook, string $stage)
    {
        log::channel('lorumLogs')->info("Processing Lorum fee debit executed");

        // $node = "fee_debit_{$stage}";
        $transaction_id = $webhook->event_id ?? null;

        // Store webhook data
        $this->storeWebhookData($transaction_id, json_encode($webhook), 'Lorum');
        return response()->json(['success' => 'Lorum Fee Debit processed successfully'], 200);
    }

    private function handleOutboundPaymentReturned(object $webhook, string $stage)
    {
        log::channel('lorumLogs')->info("Processing Lorum outbound payment returned");

        $node = "outbound_local_payment_{$stage}";
        die();
        //dd($webhook);
        if ($stage === 'failed') {
            $returnedTransactionId = $webhook->{$node}->transaction_id ?? null;
        } else if ($stage === "returned") {
            $returnedTransactionId = $webhook->{$node}->returned_transaction_id ?? null;
        }

        if (!$returnedTransactionId) {
            log::channel('lorumLogs')->warning('Transaction ID missing', ['stage' => $stage]);
            return response()->json(['failed' => 'Lorum webhook validation failed!'], 400);
        }

        $transaction = VirtualBankTransaction::where('transaction_id', $returnedTransactionId)->first();

        if (!$transaction) {
            log::channel('lorumLogs')->info('Lorum transaction not found locally', ['transaction_id' => $returnedTransactionId]);
            return response()->json(['failed' => 'Lorum webhook validation failed!'], 400);
        }

        if (!$this->verifyLorumWebhookData($transaction, $returnedTransactionId)) {
            log::channel('lorumLogs')->warning("Lorum webhook validation failed", ['stage' => $stage, 'transaction_id' => $returnedTransactionId]);
            return response()->json(['failed' => 'Lorum webhook validation failed!'], 400);
        }

        // Store web-hook data
        $this->storeWebhookData($returnedTransactionId, json_encode($webhook), 'Lorum');

        DB::beginTransaction();

        $status = self::STATUS_MAP[$stage];
        $transaction->status = $status;
        $transaction->save();

        $data = [
            'reject_reason' => $webhook->{$node}->returned_reason ?? 'Rejected by Lorum',
            'status' => $status
        ];

        TRRequest::where('id', $transaction->trn_id)->update($data);
        AccountTransaction::where('id', $transaction->parent_transaction_id)->update($data);

        DB::commit();

        log::channel('lorumLogs')->info("Lorum webhook Status: [$stage] updated  successfully", ['transaction_id' => $returnedTransactionId]);

        $accountTransaction = $transaction->accountTransaction;

        if (strpos($webhook->{$node}->external_reference, strtolower('Sweep for In-Bound')) !== false) {
            //todo send finance and operations team notification for sweep transaction failure.
            // EmailNotification::notifySweepTransactionFailure($accountTransaction);
        } else {
            EmailNotification::notifyTransactionRejection($accountTransaction);
        }

        return response()->json(['success' => 'Lorum outbound payment returned processed successfully'], 200);
    }

    private function lorumLogs($webhook, $type = null, $message = "WEB-HOOK Logs: ")
    {
        // Use app timezone from config/app.php
        $tz = config('app.timezone', 'UTC');

        // Current timestamp with timezone
        $timestamp = now($tz)->format('Y-m-d H:i:s T');

        Log::channel('lorumLogs')->info("\n\n ");
        Log::channel('lorumLogs')->info("--------------============  Lorum web-hook ==================------------");

        Log::channel('lorumLogs')->info($message, [
            'timestamp' => $timestamp,
            'timezone'  => $tz,
            'type'      => $type,
            'data'      => $webhook,
        ]);

        Log::channel('lorumLogs')->info("\n\n ");
        Log::channel('lorumLogs')->info("--------------============  Lorum web-hook ==================------------");
    }

    private function zenusLogs($webhook, $type = null, $message = "WEB-HOOK Logs: ")
    {
        // Use app timezone from config/app.php
        $tz = config('app.timezone', 'UTC');

        // Current timestamp with timezone
        $timestamp = now($tz)->format('Y-m-d H:i:s T');

        Log::channel('zenusLogs')->info("\n\n ");
        Log::channel('zenusLogs')->info("--------------============  Zenus web-hook ==================------------");

        Log::channel('zenusLogs')->info($message, [
            'timestamp' => $timestamp,
            'timezone'  => $tz,
            'type'      => $type,
            'data'      => $webhook,
        ]);

        Log::channel('zenusLogs')->info("\n\n ");
        Log::channel('zenusLogs')->info("--------------============  Zenus web-hook ==================------------");
    }

    private function handleAccountOpenedWebhook($webhook)
    {
        $webhook_data = json_decode(json_encode($webhook), true);
        $lorum_customer_id = $webhook_data["account_opened"]["customer_id"];
        $lorum_account_id = $webhook_data["account_opened"]["account_id"];

        $lorumApis = new LorumApis();
        $lorum_client = $lorumApis->getExistingLinkedClientByLorumId($lorum_customer_id);
        $existing_account = $lorumApis->getExistingVirtualAccountByLorumAccountId($lorum_account_id);

        if ($existing_account) {
            Log::channel('lorumLogs')->error("Existing Lorum virtual account found for Lorum Account ID: {$lorum_account_id}");
            return response()->json(['error' => 'Existing Lorum virtual account found'], 200);
        }

        $currency = Currency::where('short', $webhook_data["account_opened"]["currency"])->first();
        $account = Account::where('client_id', $lorum_client->client_id)->where('account_currency', $currency->id)->first();

        $this->storeWebhookData($webhook_data["account_opened"]["account_id"], json_encode($webhook_data), 'Lorum');

        if ($lorum_client) {
            $bank_account = $lorumApis->storeLorumAccount($account, $lorum_client);

            if (!empty($bank_account->errorCode) || !empty($bank_account->error)) {
                $messages = !empty($bank_account->message) ? $bank_account->message : 'Something went wrong, please try again later.';
                return redirect(url()->previous())->with('error', $messages);
            }

            $account->virtual_bank_account_id = $bank_account->id;
            $account->save();
            Log::channel('lorumLogs')->info("------===============  Virtual Bank Created with {$bank_account->currency} Account ID: {$bank_account->id} =================-----");

            // Async background job
            $lorum_controller = new LorumController();
            $lorum_controller->getLorumAccountDetails($bank_account->virtual_bank_account_id);
            return response()->json(['success' => 'Virtual bank account created successfully'], 200);
        } else {
            Log::channel('lorumLogs')->error("No linked client found for Lorum Customer ID: {$lorum_customer_id}");
            return response()->json(['error' => 'No linked client found for the provided Lorum Customer ID'], 200);
        }
    }

    private function handleOnboardingWebHook($webhook)
    {
        $webhook_data = json_decode(json_encode($webhook), true);
        $onboarding_webhook_type = $webhook_data["type"];
        $onboarding_webhook = $webhook_data[$onboarding_webhook_type];
        $lorum_customer_id = $onboarding_webhook["customer_id"];

        $lorumApis = new LorumApis();
        $lorum_client = $lorumApis->getExistingLinkedClientByLorumId($lorum_customer_id);

        if (!$lorum_client) {
            Log::channel('lorumLogs')->error("No linked client found for Lorum Customer ID: {$lorum_customer_id}");
            return response()->json(['error' => 'No linked client found for the provided Lorum Customer ID'], 200);
        }

        $this->storeWebhookData($lorum_customer_id, json_encode($webhook_data), 'Lorum');

        $lorum_customer = $lorumApis->getCustomerById($lorum_customer_id);

        $lorum_client->status = 'ACTIVE';
        $lorum_client->kyc_status = 'ACTIVE';
        $lorum_client->response_data = json_encode($lorum_customer);
        $lorum_client->save();

        Log::channel('lorumLogs')->info("Lorum onboarding webhook processed for Lorum Customer ID: {$lorum_customer_id}");
        return response()->json(['success' => 'Lorum onboarding webhook processed successfully'], 200);
    }

    private function processZenusTransaction($webhook_data)
    {
        $webhook_type = $webhook_data->statusCode ?? null;
        $payment_id = $webhook_data->paymentId ?? null;
        $validate_payment_webhook = $this->validateZenusWebhookData($webhook_data, $webhook_type);

        if (!$validate_payment_webhook) {
            Log::channel('zenusLogs')->warning('Zenus payment webhook validation failed', ['payment_id' => $payment_id]);
            return response()->json(['success' => 'webhook received'], 200);
        }

        $this->storeWebhookData($payment_id, json_encode($webhook_data), 'Zenus');

        return $this->handleZenusPayment($webhook_data);
    }

    private function validateZenusWebhookData($webhook_data, $webhook_type = null)
    {
        $payment_id     = $webhook_data->paymentId ?? null;
        $direction_code = $webhook_data->directionCode ?? null;

        // Basic validation
        if (!$payment_id) {
            Log::channel('zenusLogs')->warning('Zenus webhook missing payment ID');
            return false;
        }

        if (!$webhook_type) {
            Log::channel('zenusLogs')->warning('Zenus webhook missing webhook type code', ['payment_id' => $payment_id]);
            return false;
        }

        // Fetch Zenus transaction
        $zenus = new ZenusApis();
        $zenus_transaction = $zenus->getPaymentDetailsById($payment_id);
        $this->selected_payment_method = $zenus_transaction?->selectedPaymentScheme;

        if (empty($zenus_transaction)) {
            Log::channel('zenusLogs')->warning('Failed to retrieve Zenus payment details for validation', ['payment_id' => $payment_id]);
            return false;
        }

        // Fetch local transaction
        $local_transaction = VirtualBankTransaction::where('virtual_bank_transaction_id', $payment_id)->first();
        $exists = (bool) $local_transaction;

        // PROCESSED events
        if ($webhook_type === 'PROCESSED') {
            // PAYIN (IN)
            if ($direction_code === 'IN') {
                if ($exists && $local_transaction->status === 'Approved') {
                    Log::channel('zenusLogs')->warning("Duplicate PAYIN webhook received", ['payment_id' => $payment_id]);
                    return false;
                }
            }

            // PAYOUT (OUT)
            if ($direction_code === 'OUT') {
                if (!$exists) {
                    Log::channel('zenusLogs')->warning("No local transaction found for PAYOUT webhook", ['payment_id' => $payment_id]);
                    return false;
                }
            }

            // TODO: Compare the Web-Hook Transaction fields with zenus transaction fields.
            // accountId, amount, feeAmount (if any), statusCode, directionCode, paymentTypeCode.

        }

        return true;
    }

    private function formatZenusWebhookData($payload)
    {
        // Logging (fixed missing $headers issue)
        Log::channel('zenusLogs')->info("\n\n ---------------------------------------============== Zenus Bank Web-Hook ===============---------------------------------");
        Log::channel('zenusLogs')->info("Zenus Web-Hook Payload: ", ["payload" => $payload]);
        Log::channel('zenusLogs')->info("\n\n ---------------------------------------============== Zenus Bank Web-Hook ===============---------------------------------");

        // // Extract main data safely
        // $data = $payload->data ?? [];

        // if (isset($payload->stdClass)) {
        //     $data = $payload->stdClass;
        // }

        // // Case 1: data contains stdClass wrapper
        // if (isset($data->stdClass) && is_array($data->stdClass)) {
        //     $data = $data->stdClass;
        // }

        // Convert to object (final expected format)
        $webhook = json_decode(json_encode($payload));


        // $this->zenusLogs($webhook);

        return $webhook;
    }

    private function getZenusTransactionMethod($payment_scheme)
    {
        $payment_type_map = [
            "LOCAL_US_ACH" => "ACH",
            "LOCAL_US_WIRE" => "FED_WIRE",
            "SWIFT_CROSSBORDER" => "CROSS_BORDER_SWIFT",
        ];
        $transaction_type = $payment_type_map[$payment_scheme];
        return TransactionMethod::where('name', $transaction_type)->first();
    }

    private function handleZenusPayment($webhook_data)
    {
        $payment_id = $webhook_data->paymentId;
        $direction_code = $webhook_data->directionCode;
        $payment_id = $webhook_data->paymentId;
        $transaction_status = self::STATUS_MAP[$webhook_data->statusCode] ?? 'Pending';

        try {
            // Implement logic to handle PAYMENT_PROCESSED webhook, such as updating transaction status, notifying users, etc.
            if ($direction_code === 'IN' && $transaction_status === 'Approved') {
                // Handle PAYIN payment processed logic
                Log::channel('zenusLogs')->info("Processing Zenus PAYIN payment processed webhook for Payment ID: {$payment_id}");

                $zenus_account = Common::findVirtualBankAccountById($webhook_data->accountId);

                if (empty($zenus_account)) {
                    Log::info("Payin request failed, Zenus Bank Account not found!");
                    return response()->json(['success' => 'webhook received'], 200);
                }


                $account = $zenus_account->account;
                $user = $this->getUserByAccountId($account->id);

                $transaction_date = Carbon::now(); // Carbon::parse($webhook_data->insertedDateTime)->setTimezone('UTC')->format('Y-m-d H:i:s');
                $selected_payment_scheme = $webhook_data->selectedPaymentScheme ?? $this->selected_payment_method;
                $transaction_method = $this->getZenusTransactionMethod($selected_payment_scheme);

                $payment_amount = $webhook_data->amount->amount;
                $payment_fee_amount = !empty($webhook_data->feeAmount) ? $webhook_data->feeAmount->amount : 0;

                DB::beginTransaction();

                // create TR transaction request
                $trn_request = new TRRequest();
                $trn_request->account_id = $account->id;
                $trn_request->currency_id = $account->account_currency;
                $trn_request->transaction_type = "Credit";
                $trn_request->transaction_nature = "TT";
                $trn_request->transaction_date = $transaction_date;
                $trn_request->amount = $payment_amount;
                $trn_request->remarks = null;
                $trn_request->status = $transaction_status;
                $trn_request->created_by = $user->id;
                $trn_request->approved_by = 0;
                $trn_request->approved_at = $transaction_date;

                if (!empty($transaction_method)) {
                    $trn_request->transaction_method_id = $transaction_method->id;
                }

                $trn_request->save();

                // create client transaction
                $trn = new AccountTransaction();
                $trn->account_id = $account->id;
                $trn->tr_request_id = $trn_request->id;
                $trn->currency_id = $account->account_currency;
                $trn->transaction_type = "Credit";
                $trn->transaction_nature = "TT";
                $trn->transaction_date = $transaction_date;
                $trn->amount = $payment_amount;
                $trn->remarks = null;
                $trn->status = $transaction_status;
                $trn->created_by = $user->id;
                $trn->approved_by = 0;
                $trn->approved_at = $transaction_date;

                if (!empty($transaction_method)) {
                    $trn->transaction_method_id = $transaction_method->id;
                }

                $trn->save();

                $trn_request->link_tr_id = $trn->id;
                $trn_request->save();

                // create Zenus transaction (Payin)
                $zenus_transaction = new VirtualBankTransaction();
                $zenus_transaction->transaction_id = $webhook_data->paymentId;
                $zenus_transaction->trn_id = $trn_request->id;
                $zenus_transaction->parent_transaction_id = $trn->id;
                $zenus_transaction->virtual_bank_transaction_id = $webhook_data->paymentId;
                $zenus_transaction->short_id = $webhook_data->endToEndId ?? $webhook_data->referenceNumber ?? null;
                $zenus_transaction->account_id = $webhook_data->accountId;
                $zenus_transaction->transaction_created_date = $transaction_date;
                $zenus_transaction->transaction_updated_date = $transaction_date;
                $zenus_transaction->status = $transaction_status;
                $zenus_transaction->type = "PAYIN";
                $zenus_transaction->provider_name = 'zenus';
                $zenus_transaction->payment_type = $transaction_method->name;
                $zenus_transaction->account_holder_id = $webhook_data->accountId;
                $zenus_transaction->response = json_encode($webhook_data);
                $zenus_transaction->save();

                $data = [
                    'trn_id' => $trn_request->id,
                    "amount" => $payment_amount,
                    "transaction_date" => $trn_request->transaction_date,
                    "remarks" => null,
                    'zeroize' => null,
                    'service_fee_client_account' => $account->id,
                    'service_fee_bank_account_id' => Common::getBankAccountIdByCurrencyId($account->account_currency, 'is_service_fee_bank'),
                    'service_fee_currency_id' => $account->account_currency,
                    'service_fee' => 0,
                    'service_fee_remarks' => '',
                    'is_service_fee_trn_hidden_for_client' => null,
                    'trn_fee_client_account' => $account->id,
                    'trn_fee_bank_account_id' => Common::getBankAccountIdByCurrencyId($account->account_currency, 'is_trn_fee_bank'),
                    'trn_fee_currency_id' => $account->account_currency,
                    'trn_fee' => $payment_fee_amount,
                    'trn_fee_remarks' => '',
                    'is_trn_fee_trn_hidden_for_client' => null
                ];

                Common::saveTransactionDetails($data, $trn_request);
                Common::handleFeesOnPayinTransactionApproval($trn_request, $trn);

                DB::commit();

                // notify finance team for Payin transaction.
                EmailNotification::notifyFinanceManagersForTRRequest($trn_request, 'payin');

                $client = ClientHelper::getClientByAccountId($account->id);
                
                if ($client instanceof \App\Models\Client && isset($client->users[0])) {
                    if ($client->users[0]->user) {
                        $userWithAPIRole = User::whereHas('roles', function ($query) {
                                $query->where('name', 'API');
                            })
                                ->whereHas('linkClients', function ($query) use ($client) {
                                    $query->where('client_id', $client->id);
                                })
                                ->first();

                        if ($userWithAPIRole) {
                            Common::sendWebhookNotification($trn, $userWithAPIRole, 'transaction', 'Your transaction request has been Approved');
                        } else {
                            EmailNotification::notifyTransactionApproval($trn_request);
                        }
                    }
                }
            } else if ($direction_code === 'OUT') {

                // Handle Payout transaction
                Log::channel('zenusLogs')->info("Processing Zenus PAYOUT payment webhook for Payment ID: {$payment_id} Payment StatusCode: {$webhook_data->statusCode}");

                $zenus_transaction = VirtualBankTransaction::where('virtual_bank_transaction_id', $payment_id)->first();

                if (!empty($zenus_transaction)) {
                    $zenus_transaction->status = $transaction_status;
                    $zenus_transaction->save();

                    if ($webhook_data->statusCode === 'PROCESSED') {
                        $update_data = ['status' => $transaction_status];
                    } else if (in_array($webhook_data->statusCode, ['AML_DECLINED', 'CANCELLED'])) {
                        $update_data = [
                            'status' => $transaction_status,
                            'reject_reason' => 'Zenus: Payment declined due to AML review.'
                        ];
                    } else {
                        $update_data = [
                            'status' => $transaction_status,
                            // 'remarks' => 'Zenus: Payment is waiting for external provider processing.'
                        ];
                    }

                    // update transaction status in TRRequest table
                    TRRequest::where('id', $zenus_transaction->trn_id)->update($update_data);

                    // update transaction status in Account Transaction table.
                    AccountTransaction::where('id', $zenus_transaction->parent_transaction_id)->update($update_data);
                    $account_transaction = $zenus_transaction->accountTransaction;

                    EmailNotification::notifyTransactionApproval($account_transaction);
                }
            }
            Log::channel('zenusLogs')->info("Zenus webhook processed for Payment ID: {$payment_id}");
            return response()->json(['success' => 'Zenus webhook processed successfully'], 200);
        } catch (Exception $e) {
            Log::error("Transaction Error: " . $e->getMessage(), ['line' => $e->getLine(), 'trace' => $e->getTraceAsString()]);
            DB::rollBack();
            return response()->json(['failed' => 'Transaction processing error.'], 400);
        }
    }

    private function processZenusAccountWebhook($payload)
    {
        try {
            $account_status_code = $payload->statusCode ?? null;
            $account_id = $payload->accountId ?? null;

            // Fetch Zenus transaction
            $zenus = new ZenusApis();
            $zenus_accounts = $zenus->getAccountOfById($account_id);

            if (empty($zenus_accounts)) {
                Log::channel('zenusLogs')->warning('Failed to retrieve Zenus account details for validation', ['account_id' => $account_id]);
                return response()->json(['success' => 'webhook received'], 200);
            }

            $this->storeWebhookData($account_id, json_encode($payload), 'Zenus');

            if ($account_status_code === $zenus_accounts->statusCode) {
                $virtual_account = VirtualBankAccount::where('virtual_bank_account_id', $account_id)->first();
                if (!empty($virtual_account) && $virtual_account->status !== $account_status_code) {
                    $virtual_account->status = $account_status_code;
                    $virtual_account->save();
                } else {
                    Log::channel('zenusLogs')->info("Zenus account webhook ignored for account ID: {$account_id} status: {$account_status_code}");
                    return response()->json(['success' => 'Zenus account webhook ignored, Webhook already processed'], 200);
                }
            }

            Log::channel('zenusLogs')->info("Zenus account webhook processed for account ID: {$account_id} status: {$account_status_code}");
            return response()->json(['success' => 'Zenus account webhook processed successfully'], 200);
        } catch (Exception $e) {
            Log::error("Account Error: " . $e->getMessage(), ['line' => $e->getLine(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['failed' => 'Account processing error.'], 400);
        }
    }

    private function processZenusAccountNumberCreatedWebhook($payload)
    {
        try {
            $payload_data = $payload->data;
            $account_id = $payload_data->accountId ?? null;

            if (!$account_id) {
                Log::channel('zenusLogs')->warning('Missing account_id in webhook payload');
                return response()->json(['success' => 'webhook received'], 200);
            }

            // Fetch Zenus account
            $zenus = new ZenusApis();
            $zenus_accounts = $zenus->getAccountOfById($account_id);

            if (empty($zenus_accounts)) {
                Log::channel('zenusLogs')->warning(
                    'Failed to retrieve Zenus account details',
                    ['account_id' => $account_id]
                );
                return false;
            }

            // Store webhook raw data
            $this->storeWebhookData($account_id, json_encode($payload_data), 'Zenus');

            // Get local account
            $virtual_account = VirtualBankAccount::where('virtual_bank_account_id', $account_id)->first();

            if (empty($virtual_account)) {
                Log::channel('zenusLogs')->warning("Virtual account not found: {$account_id}");
                return response()->json(['success' => 'webhook received'], 200);
            }

            $virtual_account_info = json_decode($virtual_account->account_info, true);
            $is_vrn_exists = collect($virtual_account_info)->firstWhere('accountNumberSubtype', 'VRN');

            Log::channel('zenusLogs')->warning('Processing the zenus: ACCOUNT');

            if (empty($is_vrn_exists)) {

                // get VRN object
                $zenus_account_info = $zenus_accounts->accountNumbers;
                $account_vrn = collect($zenus_account_info)->firstWhere('accountNumberSubtype', 'VRN');

                // convert to array
                $account_vrn_array = $account_vrn ? json_decode(json_encode($account_vrn), true) : null;

                // update structure
                $virtual_account_info[] = $account_vrn_array;

                // save
                $virtual_account->account_info = json_encode($virtual_account_info);
                $virtual_account->save();
                Log::channel('zenusLogs')->info("Zenus account webhook processed for account ID: {$account_id} Notification Code: {$payload->notificationTypeCode}");
            } else {
                Log::channel('zenusLogs')->info("Zenus account webhook already processed for account ID: {$account_id} Notification Code: {$payload->notificationTypeCode}");
            }


            return response()->json([
                'success' => 'Zenus account webhook processed successfully'
            ], 200);
        } catch (\Throwable $e) {

            Log::channel('zenusLogs')->error(
                "Zenus Account Error: " . $e->getMessage(),
                [
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ]
            );

            return response()->json([
                'failed' => 'Account processing error.'
            ], 400);
        }
    }
}
