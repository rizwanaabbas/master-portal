<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\ClientHelper;
use App\Helpers\Common;
use App\Helpers\ZenusApis;
use App\Models\Account;
use App\Models\Module;
use App\Models\TransactionMethod;
use App\Models\VirtualBankAccount;
use App\Models\VirtualBankTransaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ZenusController extends Controller
{
    /*
        Created On: 24 March 2026
        Created By: Sohail Muhammad
        POE: Client Account management Create IBAN button
    */

    public $zenus_status_map = [
        'INSERTED'        => 'Initiated',
        'AML_REVIEW'      => 'Initiated',
        'WAITING_FOR_AML' => 'Initiated',
        'PROCESSED'       => 'Approved',
        'Failed'          => 'Failed',
        // 'Submitted'       => 'Pending',
        // 'CANCELLED'       => 'Failed',
    ];

    public $zenus_payment_type_mapping = [
        "LOCAL_US_ACH" => "ACH",
        "ACH_US_ACH" => "ACH",
        "ACC2EXTERNAL" => "ACH",
        "ACC2ACC" => "FED_WIRE",
        "LOCAL_US_WIRE" => "FED_WIRE",
        // Add more mappings as needed
    ];

    public function createIBAN(Request $request)
    {

        $request->validate([
            'account_id' => 'required'
        ]);

        if (!Common::isModuleEnabled('Zenus')) {
            return redirect(url()->previous())->with('error', 'Zenus Module is disabled')->with('formnumber', 3);
        }

        // try {
        if (!empty($request->get("account_id"))) {

            //foreach ($request->get("openpayd_currency") as $currency) {
            $account = Account::find($request->get("account_id"));
            $client = ClientHelper::getClientByAccountId($account->id);
            $currency = $account->currency;
            if (!empty($currency))
                $currency_code = $currency->short;
            else
                return redirect(url()->previous())->with('error', 'Account currency not found')->with('formnumber', 3);

            $exists = VirtualBankAccount::where('client_id', $client->id)->where('currency', $currency->id)->where('provider_name', 'lorum')->first();

            if ($exists) {
                return redirect()->back()->with('error', "$currency_code currency account already exits.");
            }

            $zenusApis = new ZenusApis();
            $zenus_client = $zenusApis->storeZenusClient($client, $account);

            if ($zenus_client && !$zenus_client->error) {

                if (!empty($zenus_client) && $zenus_client->status !== "ACTIVE") {
                    $messages = 'Zenus Client created with Status: ' . $zenus_client->status . '. Please wait for the onboarding process to complete.';
                    return redirect(url()->previous())->with('warning', $messages);
                }

                $bank_account = $zenusApis->storeZenusAccount($account, $zenus_client);
                //dd($bank_account);
                if (!empty($bank_account->errorCode) || !empty($bank_account->error)) {

                    if ($bank_account->error === true && $bank_account->status_code === 404) {
                        $messages = !empty($bank_account->message) ? $bank_account->message : 'The IBAN creation request was initiated, Refresh after some time to see the IBAN details.';
                        return redirect(url()->previous())->with('warning', $messages);
                    } else {
                        $messages = !empty($bank_account->message) ? $bank_account->message : 'Something went wrong, please try again later.';
                    }

                    return redirect(url()->previous())->with('error', $messages);
                }

                $account->virtual_bank_account_id = $bank_account->id;
                $account->save();

                $userWithAPIRole = User::whereHas('roles', function ($query) {
                        $query->where('name', 'API');
                    })
                        ->whereHas('linkClients', function ($query) use ($client) {
                            $query->where('client_id', $client->id);
                        })
                        ->first();

                if ($userWithAPIRole) {
                    Common::sendWebhookNotification($account, $userWithAPIRole, 'account', 'The named account has been created successfully.', 'Created');
                }

                return redirect(url()->previous())->with('success', "IBAN has been created successfully.");
            } else if (!empty($zenus_client->message)) {

                return redirect(url()->previous())->with('error', "Zenus Error: " . $zenus_client->message);
            }
        }
        // } catch (Exception $e) {
        //     Log::error('Error creating IBAN: ' . $e->getMessage());
        //     return redirect(url()->previous())->with('error', 'Error creating IBAN: ' . $e->getMessage());
        // }
    }

    public function createZenusPayoutPayment($transaction)
    {
        $zenusApis = new ZenusApis();
        $idempotency_key = (string) Str::uuid();

        $beneficiary = $transaction->beneficiary;
        $method = strtoupper($transaction->method->name ?? '');
        $process_payout_from_child = $zenusApis->process_payout_from_child;

        if ($process_payout_from_child) {
            $client_zenus_bank_account = $zenusApis->getClientZenusBankAccount($transaction, $method);
        } else {
            // Get Master Account Details for the transaction currency
            $client_zenus_bank_account = $zenusApis->getPrimaryAccountByCurrency($transaction->currency->short, $method);

            if (!$client_zenus_bank_account || !empty($client_zenus_bank_account->error)) {
                return (object)[
                    'error' => true,
                    'message' => 'Zenus Master Account Error: ' . ($client_zenus_bank_account->message ?? 'No master account found for transaction currency')
                ];
            }
        }

        /**
         * -----------------------------------
         * Validate Beneficiary
         * -----------------------------------
         */
        $validation = $this->validateZenusBeneficiary($beneficiary, $method);

        if ($validation !== true) {
            return (object)[
                'error' => true,
                'message' => $validation
            ];
        }

        /**
         * -----------------------------------
         * Normalize Beneficiary Data
         * -----------------------------------
         */
        $isIndividual = strtoupper($beneficiary->beneficiary_type) === 'RETAIL';
        $virtual = $beneficiary->virtualBankBeneficiary;
        $routingData = [];

        if ($virtual && $virtual->bank_routing_codes) {
            $routingData = json_decode($virtual->bank_routing_codes);
            $routingData = is_array($routingData) ? ($routingData[0] ?? null) : $routingData;
        }

        $beneficiaryData = [
            'name'                 => $beneficiary->name,
            'given_name'           => $isIndividual ? ($virtual->beneficiary_first_name ?? null) : null,
            'surname'              => $isIndividual ? ($virtual->beneficiary_last_name ?? null) : null,
            'person_type'          => $isIndividual ? 'P' : 'L',
            'beneficiary_country'  => optional($beneficiary->country)->iso,
            'beneficiary_address'  => $beneficiary->address,
            'beneficiary_state'    => $beneficiary->beneficiary_state,
            'beneficiary_city'     => $beneficiary->beneficiary_city,
            'postal_code'          => $beneficiary->postal_code,

            'bank_city'            => $beneficiary->bank_city,
            'account'              => $beneficiary->account_number,
            'bank_country'         => optional($beneficiary->bankAccountCountry)->iso,
            'bank_name'            => $beneficiary->bank_name,
            'bank_address'         => $beneficiary->bank_address,
            'routing'              => $routingData->routingCodeValue ?? null,
            'bic'                  => $beneficiary['swift_code'] ?? null, // for SWIFT
            'intermediary_bic'     => $routingData->intermediary_bic ?? null,
            'intermediary_country' => $routingData->intermediary_country ?? null,
        ];

        $beneficiary_address = trim($beneficiaryData['beneficiary_address']);
        $street1 = $beneficiary_address;
        $street2 = '';

        if (strlen($beneficiary_address) > 35) {
            // Find last space before position 35
            $splitPos = strrpos(substr($beneficiary_address, 0, 35), ' ');

            // If no space found, fallback to hard split at 35
            $splitPos = $splitPos !== false ? $splitPos : 35;

            $street1 = trim(substr($beneficiary_address, 0, $splitPos));
            $street2 = trim(substr($beneficiary_address, $splitPos));

            // Ensure street2 is also max 35 chars
            $street2 = substr($street2, 0, 35);
        }

        /**
         * -----------------------------------
         * Payment Parties
         * -----------------------------------
         */

        $financialInstitutionId = $method === "CROSS_BORDER_SWIFT" ?
            $client_zenus_bank_account['bic']['value'] : $client_zenus_bank_account['routingNumber']['value'];

        if (!$financialInstitutionId) {
            // throw new \Exception("Missing financial institution ID for method: $method");
            return (object)[
                'error' => true,
                'message' => "Missing financial institution ID for method: $method"
            ];
        }

        $paymentParties = [
            [
                "typeCode" => "OUR_PARTY",
                "accountNumber" => [
                    "value" => $client_zenus_bank_account['accountNumber']['value'],
                    "type"  => $client_zenus_bank_account['accountNumber']['type']
                ],
                "accountNumberCountryCode" => $client_zenus_bank_account['accountCountryCode'],
                "financialInstitutionId"   => $financialInstitutionId
            ],
            [
                "typeCode" => "COUNTERPARTY",
                "name" => $beneficiaryData['name'],
                "personTypeCode" => $beneficiaryData['person_type'],
                "givenName" => $beneficiaryData['given_name'],
                "surname" => $beneficiaryData['surname'],
                "accountNumber" => [
                    "value" => $beneficiaryData['account'],
                    "type"  => "BBAN"
                ],
                "accountNumberCountryCode" => $beneficiaryData['bank_country'],

                // KEY DIFFERENCE
                "financialInstitutionId" => $method === 'CROSS_BORDER_SWIFT' ? $beneficiaryData['bic'] : $beneficiaryData['routing'],

                "address" => [
                    "street1"    => $street1,
                    "street2"    => $street2,
                    "cityCounty" => $beneficiaryData['beneficiary_city'],
                    "stateRegion" => $beneficiaryData['beneficiary_state'],
                    "countryCode" => $beneficiaryData['beneficiary_country'],
                    "zip"        => $beneficiaryData['postal_code'],
                    "addressLine" => implode(', ', array_filter([
                        $street1,
                        $street2,
                        $beneficiaryData['beneficiary_city'],
                        $beneficiaryData['beneficiary_state'],
                        $beneficiaryData['postal_code'],
                        $beneficiaryData['beneficiary_country'],
                    ]))
                ],
            ]
        ];

        /**
         * -----------------------------------
         * Scheme Elements
         * -----------------------------------
         */
        $schemeElements = [
            [
                "fieldValueCountryCode" => $beneficiaryData['bank_country'],
                "fieldValueTypeCode"    => "BANK_NAME",
                "fieldValue"            => $beneficiaryData['bank_name'],
                "includeToMetaInfo"     => "true"
            ],
            [
                "fieldValueCountryCode" => $beneficiaryData['bank_country'],
                "fieldValueTypeCode"    => "BANK_ADDRESS",
                "fieldValue"            => "{$beneficiaryData['bank_address']} {$beneficiaryData['bank_city']}",
                "includeToMetaInfo"     => "true"
            ],
            [
                "fieldValueTypeCode" => "PAYMENT_PURPOSE_MEMO",
                "fieldValue"         => $transaction->remarks ?? 'Payment',
                "includeToMetaInfo"  => "true"
            ]
        ];

        /**
         * -----------------------------------
         * Select Payload Builder
         * -----------------------------------
         */
        switch ($method) {

            case 'ACH':
                $payload = $this->buildAchPayload($transaction, $beneficiaryData, $paymentParties, $schemeElements);
                break;

            case 'WIRE':
            case 'FED_WIRE':
                $payload = $this->buildFedWirePayload($transaction, $beneficiaryData, $paymentParties, $schemeElements);
                break;

            case 'CROSS_BORDER_SWIFT':
                $payload = $this->buildSwiftPayload($transaction, $beneficiaryData, $paymentParties, $schemeElements);
                break;

            default:
                return (object)[
                    'error' => true,
                    'message' => "Unsupported payment method: {$method}"
                ];
        }
        // dd($payload);

        /**
         * -----------------------------------
         * Initiate Payment
         * -----------------------------------
         */
        $response = $zenusApis->initiatTransaction($payload, $idempotency_key);

        if (!$response || isset($response->error) || empty($response->paymentId)) {
            return (object)[
                'error' => true,
                'message' => 'Zenus Payment Error: ' . ($response->message ?? 'Unknown error')
            ];
        }

        /**
         * -----------------------------------
         * Confirm Payment
         * -----------------------------------
         */
        $confirm = $zenusApis->confirmPayment($response->paymentId);
        if ($confirm && isset($confirm->error)) {
            return (object)[
                'error' => true,
                'message' => 'Zenus Confirmation Error: ' . $confirm->message
            ];
        }

        /**
         * -----------------------------------
         * Store Response
         * -----------------------------------
         */
        $confirm->idempotency_key = $idempotency_key;
        $this->storeApiResponseData($confirm->paymentId, json_encode($confirm), 'Zenus');
        return $confirm;
    }


    private function validateZenusBeneficiary($beneficiary, $method)
    {
        if (!$beneficiary) {
            return 'Beneficiary not found';
        }

        if (empty($beneficiary->name)) {
            return 'Beneficiary name is required';
        }

        if (empty($beneficiary->account_number)) {
            return 'Beneficiary account number is required';
        }

        if (empty($beneficiary->bank_address)) {
            return 'Beneficiary bank address is required';
        }

        if (empty($beneficiary->bank_city)) {
            return 'Beneficiary bank city is required';
        }

        if (empty(optional($beneficiary->bankAccountCountry)->iso)) {
            return 'Beneficiary bank country is required';
        }

        // ACH specific validation
        if ($method === 'ACH') {
            $routing = optional($beneficiary->virtualBankBeneficiary)->bank_routing_codes;

            if (!$routing) {
                return 'Routing number is required for ACH payments';
            }
        }

        return true;
    }

    private function buildFedWirePayload($transaction, $beneficiaryData, $paymentParties, $schemeElements)
    {
        return [
            "money" => [
                "amount"       => $transaction->amount,
                "currencyCode" => $transaction->currency->short
            ],
            "paymentTypeCode"        => "ACC2EXTERNAL",
            "preferredPaymentScheme" => "LOCAL_US_WIRE",
            "details"                => "WIRE - {$beneficiaryData['name']}",
            "endToEndId"             => 'WIRE' . $transaction->id,
            "referenceNumber"        => (string) $transaction->id,
            "directionCode"          => "OUT",
            "purposeCode"            => "1",
            "paymentParties"         => $paymentParties,
            "paymentSchemeElements"  => $schemeElements
        ];
    }
    private function buildAchPayload($transaction, $beneficiaryData, $paymentParties, $schemeElements)
    {
        return [
            "money" => [
                "amount"       => $transaction->amount,
                "currencyCode" => $transaction->currency->short
            ],
            "paymentTypeCode"        => "ACC2EXTERNAL",
            "preferredPaymentScheme" => "LOCAL_US_ACH",
            "details"                => "ACH - {$beneficiaryData['name']}",
            "endToEndId"             => 'ACH' . $transaction->id,
            "referenceNumber"        => (string) $transaction->id,
            "directionCode"          => "OUT",
            "purposeCode"            => "1",
            "paymentParties"         => $paymentParties,
            "paymentSchemeElements"  => $schemeElements
        ];
    }

    private function buildSwiftPayload($transaction, $beneficiaryData, $paymentParties, $schemeElements)
    {
        return [
            "money" => [
                "amount"       => $transaction->amount,
                "currencyCode" => $transaction->currency->short
            ],
            "details" => "SWIFT - {$beneficiaryData['name']}",

            "paymentTypeCode"        => "ACC2EXTERNAL",
            "preferredPaymentScheme" => "SWIFT_CROSSBORDER",

            "endToEndId"      => 'SWIFT' . $transaction->id,
            "referenceNumber" => (string) $transaction->id,

            "paymentParties" => $paymentParties,

            "directionCode" => "OUT",
            "purposeCode"   => "1",

            "paymentSchemeElements" => $this->appendSwiftExtras($schemeElements, $beneficiaryData)
        ];
    }

    private function appendSwiftExtras($schemeElements, $beneficiaryData)
    {
        // Optional: Add intermediary bank if exists
        if (!empty($beneficiaryData['intermediary_bic'])) {
            $schemeElements[] = [
                "fieldValueCountryCode" => $beneficiaryData['intermediary_country'] ?? null,
                "fieldValueTypeCode"    => "INTERMEDIARY_BANK_BIC",
                "fieldValue"            => $beneficiaryData['intermediary_bic'],
                "includeToMetaInfo"     => true
            ];
        }

        return $schemeElements;
    }


    public function storeApiResponseData($transaction_id, $data, $crypto_system)
    {
        DB::table('crypto_transactions_log')->insert([
            'transaction_id' => $transaction_id,
            'details' => $data,
            'crypto_system' => $crypto_system,
            'source' => 'admin',
            'date_created' => date('Y-m-d H:i:s', time())
        ]);
    }

    public function storeZenusTransaction($data, $trn_request_id = null, $acc_trn_id = null)
    {
        // Normalize data (array → object safety)
        $data = is_array($data) ? (object) $data : $data;

        /**
         * -----------------------------------
         * Payment Type Mapping
         * -----------------------------------
         */
        $paymentType = $this->resolvePaymentType(
            $data->paymentTypeCode ?? null,
            $data->preferredPaymentScheme ?? null
        );

        /**
         * -----------------------------------
         * Transaction Type Mapping
         * -----------------------------------
         */
        $transaction_type_mapping = [
            "OUT" => "PAYOUT",
            "IN"  => "PAYIN",
        ];

        /**
         * -----------------------------------
         * Save Transaction
         * -----------------------------------
         */
        $transaction = new VirtualBankTransaction();
        $transaction->parent_transaction_id      = $acc_trn_id;
        $transaction->trn_id                     = $trn_request_id;
        $transaction->transaction_id             = $data->paymentId ?? null;
        $transaction->account_id                 = $data->accountId ?? null;
        $transaction->transaction_created_date   = isset($data->insertedDateTime)
            ? Carbon::parse($data->insertedDateTime)->format('Y-m-d H:i:s')
            : null;

        $transaction->virtual_bank_transaction_id = $data->paymentId ?? null;
        $transaction->payment_type                = $paymentType;
        $transaction->type                        = $transaction_type_mapping[$data->directionCode ?? null] ?? 'UNKNOWN';
        $transaction->provider_name               = 'Zenus';
        $transaction->status                      = $this->zenus_status_map[$data->statusCode ?? null] ?? 'UNKNOWN';
        $transaction->response                    = json_encode($data);

        $transaction->save();

        return $transaction;
    }

    private function resolvePaymentType($paymentTypeCode, $scheme)
    {
        return match (true) {

            // Internal transfer
            $paymentTypeCode === 'ACC2ACC' => 'INTERNAL',

            // External transfers
            $paymentTypeCode === 'ACC2EXTERNAL' && $scheme === 'LOCAL_US_ACH'   => 'ACH',
            $paymentTypeCode === 'ACC2EXTERNAL' && $scheme === 'LOCAL_US_WIRE'  => 'WIRE',
            $paymentTypeCode === 'ACC2EXTERNAL' && $scheme === 'SWIFT_CROSSBORDER' => 'SWIFT',
            $paymentTypeCode === 'ACC2EXTERNAL' && $scheme === 'LOCAL_STABLECOIN'  => 'CRYPTO',

            // Fallback
            default => 'UNKNOWN',
        };
    }

    public static function getZenusPaymentMethods()
    {
        $zenus_module = Module::where('is_enabled', true)->where('name', 'Zenus')->first();
        $transaction_methods = null;

        if (!empty($zenus_module)) {
            $transaction_methods = TransactionMethod::where('module_id', $zenus_module->id)->get()->pluck('name')->toArray();
        }

        return $transaction_methods;
    }

    public static function getBeneficiaryPaymentMethods()
    {
        $zenus_module = Module::where('is_enabled', true)->where('name', 'Zenus')->first();
        $transaction_methods = null;

        if (!empty($zenus_module)) {
            $transaction_methods = TransactionMethod::where('module_id', $zenus_module->id)->get();
        }

        return $transaction_methods;
    }

    public static function updatePersonAddress($client, $virtualClient)
    {
        $personId = $virtualClient->linked_client_id;
        $zenusApi = new ZenusApis();

        try {
            $response = $zenusApi->findPersonAddress($personId);

            if (!empty($response->errors)) {
                Log::error('Zenus address lookup failed', [
                    'person_id' => $personId,
                    'errors' => $response->errors,
                ]);

                return $response->errors;
            }

            $addressData = collect($response->data)->last() ?? [];

            if (empty($addressData->addressId)) {
                throw new \Exception('Address ID not found.');
            }

            $addressId = $addressData->addressId;

            $payload = [];

            if ($client->type === 'individual') {
                $individual = $client->individual;

                $payload = [
                    'street1'      => $individual->street_address,
                    'street2'      => $individual->street_2_address,
                    'stateRegion'  => $individual->state_region,
                    'cityCounty'   => $individual->city,
                    'zip'          => $individual->zip_code,
                    'countryCode'  => optional($individual->residence)->iso,
                ];
            }

            if ($client->type === 'company') {
                $company = $client->company;

                if ($virtualClient->is_uob_client) {
                    $contact = $company->contact;
                    $payload = [
                        'street1'      => $contact?->street_address,
                        'street2'      => $contact?->street_2_address,
                        'stateRegion'  => $contact?->state_region,
                        'cityCounty'   => $contact?->contact_city,
                        'zip'          => $contact?->zip_code,
                        'countryCode'  => optional($contact?->residence)->iso,
                    ];
                } else {
                    $payload = [
                        'street1'      => $company->business_reg_street_address,
                        'street2'      => $company->business_street_2_address,
                        'stateRegion'  => $company->company_region,
                        'cityCounty'   => $company->company_city,
                        'zip'          => $company->zip_code,
                        'countryCode'  => optional($company->companyCountry)->iso,
                    ];
                }
            }

            $addressResponse = $zenusApi->updatePersonAddress($personId, $addressId, $payload);

            if (!empty($addressResponse->errors)) {
                Log::error('Zenus address update failed', [
                    'person_id' => $personId,
                    'address_id' => $addressId,
                    'errors' => $addressResponse->errors,
                ]);

                return $addressResponse->errors;
            }

            return $virtualClient;

        } catch (\Throwable $e) {
            Log::error('Zenus address update exception', [
                'person_id' => $personId,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
