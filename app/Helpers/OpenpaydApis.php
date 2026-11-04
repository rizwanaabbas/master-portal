<?php

/**
 * Created by VS Code.
 * User: Jehangir Wahid
 * Date: 01/12/2022
 * Time: 11:55 AM
 */

namespace App\Helpers;

use App\Models\Module;
use App\Models\ModuleConfiguration;
use App\Models\VirtualBankTransaction;
use App\Models\TransactionMethod;
use Carbon\Carbon;
use League\Flysystem\Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\VirtualBankAccount;
use App\Models\VirtualBankLinkedClient;
use App\Models\Currency;
use App\Models\Country;
use App\Models\ClientBeneficiary;
use App\Models\VirtualBankBeneficiary;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class OpenpaydApis
{
    private $baseUrl;
    private $api_user;
    private $api_password;
    private $allowed_currencies;
    private $main_account_holder_id;
    private $provider_name = "openpayd";
    public bool $process_payout_from_child = false;

    public function __construct()
    {
        // $config = Cache::remember('openpayd.config', now()->addHours(24), function () {

        //     $module = Module::where('name', 'OpenPayd')->select('id')->first();

        //     if (!$module) {
        //         throw new Exception('OpenPayd module not found');
        //     }

        //     $moduleConfig = ModuleConfiguration::where('module_id', $module->id)->first();

        //     if (!$moduleConfig) {
        //         throw new Exception('No configurations found. Please feed the OpenPayd Configurations.');
        //     }

        //     // Encrypt cached payload
        //     // return Crypt::encryptString($moduleConfig->configurations);
        //     return $moduleConfig->configurations;
        // });

        // // // decrypt once
        // // $config = unserialize(Crypt::decryptString($config));
        // $config = unserialize($config);

        if (!session('OpenpaydConfig')) {
            $module = Module::where('name', 'OpenPayd')->select('id')->first();
            $moduleConfig = ModuleConfiguration::where('module_id', $module->id)->first();

            if ($moduleConfig) {
                session(['OpenpaydConfig' => unserialize($moduleConfig->configurations)]);
            } else {
                throw new Exception('No configurations found. Please feed the OpenPayd Configurations.');
            }
        }

        $config = session('OpenpaydConfig');

        if (app()->environment('production')) {
            $this->baseUrl                    = $config['api_base_url']['value'];
            $this->api_user                   = $config['api_user']['value'];
            $this->api_password               = $config['api_password']['value'];
            $this->process_payout_from_child  = $config['process_payout_from_child']['value'];
        } else {
            $this->baseUrl                    = $config['sandbox_api_base_url']['value'];
            $this->api_user                   = $config['sandbox_api_user']['value'];
            $this->api_password               = $config['sandbox_api_password']['value'];
            $this->process_payout_from_child  = $config['process_payout_from_child']['value'];
        }

        $this->allowed_currencies = Currency::where('is_active', 1)->where('virtual_iban_provider', 'openpayd')->pluck('short')->implode(',');
    }

    /* =====================================================
     | AUTH & HTTP
       ===================================================== 
    */
    // public function getAccessToken()
    // {

    //     $credentials = base64_encode($this->api_user . ":" . $this->api_password);
    //     $response = Http::withHeaders([
    //         'Authorization' => 'Basic ' . $credentials,
    //     ])
    //         ->asForm()
    //         ->post($this->baseUrl . '/oauth/token', [
    //             'grant_type' => 'client_credentials',
    //         ]);

    //     // Handle the response
    //     if ($response->successful()) {
    //         $data = $response->json();
    //         //dd($response);
    //         // Assuming 'access_token' and 'account_holder_id' are present in the response
    //         $accessToken = $data['access_token'] ?? null;
    //         $accountHolderId = $data['accountHolderId'] ?? null;
    //         $this->main_account_holder_id = $accountHolderId;
    //         // return $data;
    //         return response()->json([
    //             'access_token' => $accessToken,
    //             'account_holder_id' => $accountHolderId,
    //         ]);
    //     } else {
    //         // Handle error, you can log the error message or return a response
    //         return response()->json(['error' => 'Unable to fetch access token'], 500);
    //     }
    // }

    public function getAccessToken(): array
    {
        $cacheKey = 'openpayd_api_auth';

        if (Cache::has($cacheKey)) {
            return json_decode(Crypt::decryptString(Cache::get($cacheKey)), true);
        }

        $credentials = base64_encode($this->api_user . ":" . $this->api_password);
        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . $credentials,
        ])
        ->asForm()
        ->post($this->baseUrl . '/oauth/token', [
            'grant_type' => 'client_credentials',
        ]);

        if (!$response->successful()) {
            throw new Exception('Unable to authenticate with OpenPayd');
        }

        $auth = $response->json();
        $data = [
            'access_token' => $auth['access_token'],
            'account_holder_id' => $auth['accountHolderId'] ?? null,
        ];

        $expiresIn = $auth['expires_in'] ?? 3600;
        $ttl = max($expiresIn - 300, 60); // 5 min buffer
        Cache::put($cacheKey, Crypt::encryptString(json_encode($data)), now()->addSeconds($ttl));

        return $data;
    }

    public function getAllowedCurrencies($flag = false)
    {
        //dd($this->allowed_currencies );
        if ($flag && !empty($this->allowed_currencies)) {
            $currencies = array_map('trim', explode(',', strtoupper($this->allowed_currencies)));
            return $currencies;
        } else {
            return trim($this->allowed_currencies);
        }
    }

    public function getErrorByErrorCode($errorCode)
    {
        $error = '';
        switch ($errorCode) {
            case 'EMAIL_ALREADY_USED_FOR_CURRENT_PARENT_CLIENT':
                $error = 'Email is already used for the current parent client.';
                break;
            case 'COUNTRY_NOT_ENABLED_FOR_LINKED_CLIENT':
                $error = 'Country is not enabled for linked client.';
                break;
            case "Invalid phone number":
                $error = 'Please enter a valid phone number.';
                break;
            case "ACCOUNT_ALREADY_EXISTS":
                $error = 'Account already exists.';
                break;
            case "RESTRICTION_INSUFFICIENT_BALANCE_EXCEPTION":
                $error = 'Insufficient funds in Client Account.';
                break;
            case "INVALID_BANK_BENEFICIARY":
                $error = 'Provided beneficiary is invalid';
                break;
            case "BENEFICIARY_VALIDATION_ERROR":
                $error = 'The provide beneficiary is invalid';
                break;
            default:
                $error = 'Something went wrong, please try again later.';
        }
        return $error;
    }




    public function createLinkedClientAccount($client)
    {
        $endpoint =  $this->baseUrl . '/linkedClient';
        $data = $this->prepareLinkedClientPayload($client);
        // dd($data);
        return $this->post($endpoint, $data);
    }

    public function getLinkedClients($account_holder_id = null)
    {
        $endpoint =  $account_holder_id ? "{$this->baseUrl}/linkedClient/{$account_holder_id}" : "{$this->baseUrl}/linkedClient?pageSize=100&size=100";
        return $this->get($endpoint);
    }

    public function createClientBankAccount($account, $account_holder_id)
    {
        $endpoint =  $this->baseUrl . '/accounts';
        $data = $this->prepareClientAccountPayload($account);

        return $this->post($endpoint, $data, $account_holder_id);
    }

    public function getClientBankAccountList($internalAccountId, $account_holder_id)
    {
        $endpoint =  $this->baseUrl . '/bank-accounts?internalAccountId=' . $internalAccountId;
        return $this->get($endpoint, $account_holder_id);
    }

    public function getAllClientAccountList($account_holder_id)
    {
        $endpoint =  $this->baseUrl . '/accounts?sort=createdDate,DESC';
        return $this->get($endpoint, $account_holder_id);
    }

    public function getClientBeneficiaries($account_holder_id)
    {
        $endpoint =  $this->baseUrl . '/beneficiaries?sort=createdDate,DESC';
        return $this->get($endpoint, $account_holder_id);
    }

    public function getClientBankBeneficiaries($account_holder_id)
    {
        $endpoint =  $this->baseUrl . '/bank-beneficiaries?pageSize=30&size=40&sort=createdDate,DESC';
        return $this->get($endpoint, $account_holder_id);
    }

    public function getBeneficiaryRequiredDetails($beneficiary_country_code, $beneficiary_currency_code, $beneficiaryType)
    {
        $endpoint =  $this->baseUrl . '/beneficiary-required-details?bankAccountCountry=' . $beneficiary_country_code . '&bankAccountCurrency=' . strtoupper($beneficiary_currency_code) . '&beneficiaryType=' . $beneficiaryType;
        return $this->get($endpoint, $this->main_account_holder_id);
    }

    public function prepareLinkedClientPayload($client)
    {
        if ($client->type == 'company') {
            // dd($client->company->contact);
            //dd($client->company->type->name);
            $company_country = Country::where('id', $client->company->company_country)->first();
            $data = [
                'clientType' => 'BUSINESS',
                'industrySectorValueMandatory' => true,
                'identificationTypeValueMandatory' => true,
                'friendlyName' => $client->name,

                'company' => [
                    'companyName' => $client->name,
                    'companyType' => 'PARTNERSHIP', //$client->company->type->name,
                    'registrationNumber' => $client->company->business_reg_number,
                    'registeredAddress' => [
                        'addressLine1' => $client->company->business_reg_address,
                        'city' => $client->company->company_city,
                        'country' => $company_country->iso
                        //'addressLine2' => '',
                        //'postCode' => 'EC34 1OG',
                    ],
                    'tradingAddress' => [
                        'addressLine1' => $client->company->business_reg_address,
                        'city' => $client->company->company_city,
                        'country' => $company_country->iso
                        // 'addressLine2' => 'London',
                        //'postCode' => 'EC34 1OG',
                    ],
                    'contactName' => $client->company->contact->name,
                    'email' => $client->company->contact->email_address,
                    // 'phone' => $client->company->contact->mobile_number,
                ],


                // 'endToEndReference' => '6f0e673c-02a8-4b3d-89aa-c212cbf9ad34'
            ];
            //  echo (json_encode($data)); die();
        }

        if ($client->type == 'individual') {
            $address_line_1 = $client->individual->mailing_address;

           
            $data = [
                'clientType' => 'INDIVIDUAL',
                'individual' => [
                    'address' => [
                        'addressLine1' => $address_line_1,
                        'addressLine2' => $client->individual->employer->employer_address,
                        'city' => $client->individual->city,
                        'country' => $client->individual->residence->iso,
                        // 'postCode' => 'UK7 {{ $randomInt }}',
                        // 'state' => 'state',
                    ],
                    'dateOfBirth' => $client->individual->dob,
                    'email' => $client->individual->email_address,
                    'identificationType' => 'PASSPORT',
                    'identificationValue' => $client->individual->passport_number,
                    'firstName' => explode(' ', $client->name)[0],
                    'lastName' => explode(' ', $client->name)[1] ?? ' ',
                    // 'middleName' => '{{ $randomFirstName }}',
                    // 'phone' => '+' . $client->individual->country->phonecode . $client->individual->mobile_number,
                ],
            ];
            // dd($data);
        }
        return $data;
    }

    public function prepareClientAccountPayload($account)
    {
        $currency = trim(explode(' ', $account->currency->short)[0]);
        $data = [
            "currency" => $currency,
            "friendlyName" => $account->title,
            "ibanCountry" => "MT",
            // "domestic" => false
        ];
        return $data;
    }

    public function storeOpenPaydClient($client)
    {
        try {
            $open_payd_client = VirtualBankLinkedClient::where("client_id", $client->id)->where('provider_name', 'openpayd')->first();

            if ($open_payd_client) {
                Log::info('Openpayd Linked Client Account already exists');
                return $open_payd_client;
            }

            $data = $this->createLinkedClientAccount($client);

            if (!empty($data->errorCode)) {
                Log::error('Error in creating open payd client ' . json_encode($data));
                return $data;
            }

            Log::info('Openpayd Linked Client Account: ' . json_encode($data));

            $open_payd_client = new VirtualBankLinkedClient();
            $open_payd_client->account_holder_id = $data->accountHolderId;
            $open_payd_client->status = $data->status;
            $open_payd_client->kyc_status = $data->kycStatus;
            $open_payd_client->linked_client_id = $data->id;
            $open_payd_client->client_id = $client->id;
            $open_payd_client->provider_name = $this->provider_name;
            $open_payd_client->response_data = json_encode($data);

            if ($data->clientType == 'INDIVIDUAL') {
                $open_payd_client->country = $data->individual->address->country;
            } else if ($data->clientType == 'BUSINESS') {
                $open_payd_client->country = $data->company->registeredAddress->country;
            }

            $open_payd_client->save();

            return $open_payd_client;
        } catch (Exception $e) {
            Log::error('Error in creating open payd client: ' . $e->getMessage());
            return ['errorCode' => "Something went wrong, please try again later."];
        }
    }

    public function storeOpenPaydAccount($account, $openpayd_linked_client)
    {
        try {
            $openpaydHelper = new OpenpaydApis();
            $openpayd_linked_account_holder_id = $openpayd_linked_client->account_holder_id;
            $allowedCurrencies = $openpaydHelper->getAllowedCurrencies(true);
            $currencyShort = Common::getCurrencyShort($account->account_currency);

            if (in_array(strtoupper($currencyShort), $allowedCurrencies)) {
                if (!VirtualBankAccount::where('account_id', $account->id)->exists()) {
                    $openpay_account = $openpaydHelper->createClientBankAccount($account, $openpayd_linked_account_holder_id);
                    //dd($openpay_account);
                    Log::info('Openpayd Bank Account: ' . json_encode($openpay_account));

                    if (!empty($openpay_account) && !empty($openpay_account->internalAccountId)) {
                        $bank_accounts = $openpaydHelper->getClientBankAccountList($openpay_account->internalAccountId, $openpayd_linked_account_holder_id);

                        Log::info('Getting Bank Account info: ' . json_encode($bank_accounts));

                        if (!empty($bank_accounts)) {
                            $bank_account = $bank_accounts[0];
                            $iban = $bank_account->iban ?? $bank_account->status;
                            $open_payd_bank_account = new VirtualBankAccount([
                                'linked_client_id' => $openpayd_linked_client->id,
                                'account_id' => $account->id,
                                'client_id' => $account->client_id,
                                'virtual_bank_account_id' => $openpay_account->id,
                                'currency' => $bank_account->currency,
                                'currency_id' => $account->currency->id,
                                'status' => $bank_account->status,
                                'internal_account_id' => $bank_account->internalAccountId,
                                'bank_country' => $bank_account->bankCountry,
                                'bank_address' => $bank_account->bankAddress,
                                'iban' => $iban ?? 'xxxxxx',
                                'bic' => $bank_account->bic ?? 'xxxxxx',
                                'account_number' => $bank_account->accountNumber ?? 'xxxxxx',
                                'bank_name' => $bank_account->bankName,
                                'bank_account_holder_name' => $bank_account->bankAccountHolderName,
                                'provider' => $bank_account->provider,
                                'payment_type' => $bank_account->paymentType,
                                'domestic' => $bank_account->domestic,
                                'provider_name' => $this->provider_name,
                                'routing_code_entries' => json_encode($bank_account->routingCodeEntries),
                                'pay_in_reference' => $bank_account->payInReference,
                            ]);

                            $open_payd_bank_account->save();

                            return $open_payd_bank_account;
                        }
                    }
                } else {
                    Log::info('Openpayd Bank Account already exists');
                    return (object) ['errorCode' => "ACCOUNT_ALREADY_EXISTS"];
                }
            }
            return $openpay_account ?? null;
        } catch (Exception $e) {
            Log::error('Error in creating open payd account: ' . $e->getMessage());
        }
    }

    public function createBeneficiary($beneficiary, $bank_beneficiary_data)
    {
        $beneficiary_type = $bank_beneficiary_data["beneficiary_type"];
        $company_name = null;
        $friendlyName = trim($beneficiary['name']);
        $first_name = $friendlyName;
        $last_name = $friendlyName;
        
        if ($beneficiary_type == "CORPORATE") {
            $company_name = $beneficiary['company_name'] ?? null;
        } else {   
            $nameParts = preg_split('/\s+/', $friendlyName); // Split by one or more spaces
            
            if (count($nameParts) >= 2) {
                // If more than one word: take the first as firstName, and the rest as lastName
                $first_name = array_shift($nameParts);
                $last_name = implode(' ', $nameParts);
            }
        }

        $payload = [
            "friendlyName" => $friendlyName,
            "companyName" => $company_name,
            "firstName" => $first_name,
            "lastName" => $last_name,
            "beneficiaryType" => $beneficiary_type
        ];

        $endpoint =  $this->baseUrl . '/beneficiaries';

        $check_sepa_supported = Country::where('id', $beneficiary['beneficiary_country'])->first();
        if ($check_sepa_supported->sepa_supported == 0) {
            return (object) ['errorCode' => 'COUNTY_NOT_SUPPORTED', 'message' => ' Beneficiary country is not supported.'];
        }

        //create main beneficairy
        $response = $this->post($endpoint, $payload, $this->main_account_holder_id);
        
        if (!empty($response->id)) {
            $account_currency_id = $beneficiary['account_currency'];
            $beneficiary_country_id = $beneficiary['beneficiary_country'];
            $bank_account_country_id = $beneficiary['bank_account_country'];
            $currency_code = null;
            $beneficiary_country_code = null;
            $bank_account_country_code = null;

            if (!empty($account_currency_id)) {
                $currency = Currency::where('id', $account_currency_id)->first();
                $currency_code = $currency ? $currency->short : null;
                $currency_code = trim(explode(' ', $currency_code)[0]);
            }

            if (!empty($beneficiary_country_id)) {
                $country = Country::where('id', $beneficiary_country_id)->first();
                $beneficiary_country_code = $country ? $country->iso : null;
            }

            if (!empty($bank_account_country_id)) {
                $country = Country::where('id', $bank_account_country_id)->first();
                $bank_account_country_code = $country ? $country->iso : null;
            }

            $is_chaps = $beneficiary['bank_city'] && $beneficiary['postal_code'];
            $paymentTypes = $this->getPaymentTypes($beneficiary_country_code, $currency_code, $beneficiary_type, $is_chaps);

            $payload_2 = [
                // "accountNumber" => $beneficiary["account_number"],
                // "iban" => $beneficiary['iban'],
                // "bic" => $beneficiary['swift_code'],
                "bankAccountCurrency" => strtoupper($currency_code),
                "paymentTypes" => $paymentTypes,
                "beneficiaryType" => $beneficiary_type,
                "beneficiaryCountry" => $beneficiary_country_code,
                "bankAccountCountry" => $bank_account_country_code,
                "beneficiaryFirstName" => $first_name,
                "beneficiaryLastName" => $last_name,
                "friendlyName"  => $beneficiary['name'],
                "bankAccountHolderName" =>  $beneficiary['name'],
            ];

            if ($beneficiary_type == "CORPORATE") {
                $payload_2["companyName"] = $beneficiary['name'];
            }

            if (in_array('FASTER_PAYMENTS', $paymentTypes)) {

                // If CHAPS required details is available.
                if ($is_chaps) {
                    $payload_2["beneficiaryCity"] = $beneficiary['bank_city'];
                    $payload_2["beneficiaryPostalCode"] = $beneficiary['postal_code'];
                }

                $payload_2['bankRoutingCodes'] = [[
                    'routingCodeKey' => 'SORT_CODE', 
                    'routingCodeValue' => $bank_beneficiary_data["sort_code"]
                ]];
                $payload_2["accountNumber"] = $beneficiary["account_number"];
            } else {
                $payload_2["iban"] = $beneficiary['iban'];
                $payload_2["bic"] = $beneficiary['swift_code'];
            }

            //$main_account_holder_id
            $endpoint =  $this->baseUrl . '/beneficiaries/' . $response->id . '/bank-beneficiaries';
            $response2 = $this->post($endpoint, $payload_2, $this->main_account_holder_id);
            
            if (!empty($response2->id)) {
                $finalBeneficiary_id = $response2->id;
                $endpoint = $this->baseUrl . '/bank-beneficiaries/' . $finalBeneficiary_id;
                $bank_beneficiary = $this->get($endpoint, $this->main_account_holder_id);

                if (!empty($bank_beneficiary)) {

                    $beneficiaryData = [
                        'user_id' => auth()->id(), // or set the relevant user ID
                        'linked_client_id' => $open_payd_client['linked_client_id'] ?? null, // Assign appropriate value
                        'account_holder_id' => $this->main_account_holder_id, // linking beneficiary to Openpayd main account.
                        'beneficiary_id' => $bank_beneficiary->beneficiaryId,
                        'bank_beneficiary_id' => $bank_beneficiary->id,
                        'external_id' => $bank_beneficiary->externalId,
                        'status' => $bank_beneficiary->status,
                        'provider_name' => $this->provider_name,
                        'friendly_name' => $bank_beneficiary->friendlyName,
                        'beneficiary_first_name' => $bank_beneficiary->beneficiaryFirstName,
                        'beneficiary_last_name' => $bank_beneficiary->beneficiaryLastName,
                        'beneficiary_type' => $bank_beneficiary->beneficiaryType,
                        'beneficiary_country' => $bank_beneficiary->beneficiaryCountry,
                        'beneficiary_state' => $bank_beneficiary->beneficiaryState,
                        'beneficiary_postal_code' => $bank_beneficiary->beneficiaryPostalCode,
                        'beneficiary_city' => $bank_beneficiary->beneficiaryCity,
                        'beneficiary_address_line' => $bank_beneficiary->beneficiaryAddressLine,
                        'account_number' => $beneficiary['accountNumber'] ?? null,
                        'iban' => $bank_beneficiary->iban ?? 'None',
                        'bic' => $bank_beneficiary->bic ?? 'None',
                        'currency' => $bank_beneficiary->currency,
                        'bank_name' => $beneficiary['bankName'] ?? null,
                        'bank_address' => $beneficiary['bankAddressLine'] ?? null,
                        'bank_account_country' => $bank_beneficiary->bankAccountCountry,
                        'bank_account_holder_name' => $bank_beneficiary->bankAccountHolderName,
                        'company_name' => $bank_beneficiary->companyName,
                        'bank_account_alias' => $bank_beneficiary->bankAccountAlias,
                        'document_id' => $bank_beneficiary->documentId,
                        'intermediary_bank_account_country' => $bank_beneficiary->intermediaryBankAccountCountry,
                        'intermediary_name' => $bank_beneficiary->intermediaryName,
                        'intermediary_town_name' => $bank_beneficiary->intermediaryTownName,
                        'intermediary_postal_code' => $bank_beneficiary->intermediaryPostalCode,
                        'bban' => $bank_beneficiary->bban,
                        'payment_types' => $bank_beneficiary->paymentTypes,
                        'bank_routing_codes' => $bank_beneficiary->bankRoutingCodes,
                        'class' => $bank_beneficiary->class,
                        'tax_id' => $bank_beneficiary->taxId,
                        'phone_number' => $bank_beneficiary->phoneNumber,
                        'title' => $bank_beneficiary->title,
                        'beneficiary_birth_date' => $bank_beneficiary->beneficiaryBirthDate,
                        'bank_account_type' => $bank_beneficiary->bankAccountType,
                        'uploaded_kyc_document' => $bank_beneficiary->uploadedKycDocument,
                        'metadata' => $bank_beneficiary->metadata,
                    ];

                    VirtualBankBeneficiary::create($beneficiaryData);
                }
            }
            return $response2;
        } else {
            return $response;
        }


        // }

    }

    public function getPaymentTypes($beneficiary_country_code,$currency_code, $beneficiary_type, $is_chaps=false) {
        $allowedPaymentMethods = [
            "SEPA",
            "SEPA_INSTANT",
            "FASTER_PAYMENTS",
            "TARGET2",
        ];

        if ($is_chaps) $allowedPaymentMethods[] = "CHAPS";

        // Getting the payment types for beneficiary account
        $endpoint_for_payment_types =  $this->baseUrl
            . '/beneficiary-required-details?bankAccountCountry=' . $beneficiary_country_code
            . '&bankAccountCurrency=' . strtoupper($currency_code)
            . '&beneficiaryType=' . $beneficiary_type;

        $get_payment_types = $this->get($endpoint_for_payment_types, $this->main_account_holder_id);

        // Extract payment types
        $paymentTypes = array_map(fn($item) => $item->paymentType ?? null, $get_payment_types);

        // Remove nulls
        $paymentTypes = array_filter($paymentTypes);

        // Normalize to uppercase
        $paymentTypes = array_map('strtoupper', $paymentTypes);

        // ✅ FILTER ONLY allowed payment methods
        $paymentTypes = array_values(array_filter($paymentTypes, function ($type) use ($allowedPaymentMethods) {
            return in_array($type, $allowedPaymentMethods);
        }));

        // If nothing allowed, return error
        if (empty($paymentTypes)) {
            return (object)[
                'errorCode' => 'Selected currency does not support allowed payment methods.',
            ];
        }

        return $paymentTypes;
    }

    public function createInternalTransfer($trn_request, $acc_trn_id)
    {
        $source_account = $trn_request->account;
        $destination_account = $trn_request->receiver;

        $source_identifier = $source_account->bankAccount->openpayd_account_id;
        $destination_identifier = $destination_account->bankAccount->openpayd_account_id;

        $currency = Common::getCurrencyShort($source_account->account_currency);
        $currency = trim(explode(' ', $currency)[0]);

        $payload = [
            "type" => "TRANSFER",
            "source" => [
                "identifier" => $source_identifier,
                "type" => "ACCOUNT"
            ],
            "destination" => [
                "identifier" => $destination_identifier,
                "type" => "ACCOUNT"
            ],
            "amount" => [
                "currency" => $currency,
                "value" => $trn_request->amount
            ],
            "comment" => $trn_request->remarks
        ];

        $account_holder_id = $source_account->linkedClient->account_holder_id;
        $endpoint =  $this->baseUrl . '/transactions';
        $idempotency_key = $acc_trn_id;
        $response = $this->post($endpoint, $payload, $account_holder_id, $idempotency_key);

        if (!empty($response->id)) {
            $response->createdDate = Carbon::createFromFormat('Y-m-d\TH:i:s.u\Z', $response->createdDate)->format('Y-m-d H:i:s.u');
            $response->updatedDate = Carbon::createFromFormat('Y-m-d\TH:i:s.u\Z', $response->updatedDate)->format('Y-m-d H:i:s.u');

            // store transaction request in crypto transaction log.
            $this->storeApiResponseData($response->transactionId, json_encode($response), "OpenPayd");
            $transaction = $this->storeTransaction($response, $trn_request->id, $acc_trn_id);

            Log::info('Openpayd Internal Transfer Response : ' . json_encode($response));
            return $transaction;
        } else {
            if (!empty($response->errorCode)) {
                $response->message = $this->getErrorByErrorCode($response->errorCode);
            }
            Log::info('Error in creating internal transfer: ' . json_encode($response));
            return $response;
        }
    }

    public function createInternalTransferFromMainToClient($trn_request, $tr_id, $transaction_id)
    {
        $source_account_identifier = $trn_request->destinationInfo->identifier;
        $destination_account_identifier = $trn_request->sourceInfo->identifier;
        $currency = $trn_request->amount->currency;
        $amount = abs($trn_request->amount->value);

        $payload = [
            "type" => "TRANSFER",
            "source" => [
                "identifier" => $source_account_identifier,
                "type" => "ACCOUNT"
            ],
            "destination" => [
                "identifier" => $destination_account_identifier,
                "type" => "ACCOUNT"
            ],
            "amount" => [
                "currency" => $currency,
                "value" => $amount
            ],
            "comment" => 'Sweep Transfer from Main Account to Client Account'
        ];

        $account_holder_id = $trn_request->destinationInfo->accountHolderId;
        $endpoint =  $this->baseUrl . '/transactions';
        $response = $this->post($endpoint, $payload, $account_holder_id);

        if (!empty($response->id)) {
            $formatted_created_date = Carbon::createFromFormat('Y-m-d\TH:i:s.u\Z', $response->createdDate)->format('Y-m-d H:i:s.u');
            $formatted_updated_date = Carbon::createFromFormat('Y-m-d\TH:i:s.u\Z', $response->updatedDate)->format('Y-m-d H:i:s.u');
            $response->createdDate = $formatted_created_date;
            $response->updatedDate = $formatted_updated_date;

            // store transaction request in crypto transaction log.
            $this->storeApiResponseData($response->transactionId, json_encode($response), "OpenPayd");
            $this->storeTransaction($response, $tr_id, $transaction_id);

            Log::info('Openpayd transaction saved, in OpenpaydTransaction table : ' . json_encode($response) . "\n\n");
            return $response;
        } else {
            if (!empty($response->errorCode)) {
                $response->message = $this->getErrorByErrorCode($response->errorCode);
            }
            Log::info('Error in creating internal transfer: ' . json_encode($response));
            return $response;
        }
    }

    public function getTransactionLogByTrnId($trn_id)
    {
        $openpayd_transaction = VirtualBankTransaction::where('parent_transaction_id', $trn_id)->first();

        if (empty($openpayd_transaction)) {
            return (object) ['errorCode' => 'TRANSACTION_NOT_FOUND', 'message' => 'Transaction not found.'];
        }

        $transaction_log = DB::table('crypto_transactions_log')->where('transaction_id', $openpayd_transaction->virtual_bank_transaction_id)->get();

        foreach ($transaction_log as $log) {
            $log_details = json_decode($log->details);
            // if ($log_details->type === 'TRANSFER' && !empty($log_details->originalTransactionId) && $log_details->originalTransactionId === $openpayd_transaction->virtual_bank_transaction_id && !empty($log_details->destination) && $log_details->destination === 'CAIB') {
            if ($log_details->type === 'SWEEP_TRANSFER' && !empty($log_details->originalTransactionId) && $log_details->originalTransactionId === $openpayd_transaction->virtual_bank_transaction_id && !empty($log_details->destination)) {
                return $log_details;
            }
        }

        return null;
    }

    public function ibanInfo($account_id)
    {

        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'User not authenticated'], 401);
        }

        if (!empty($account_id)) {
            $bank_account = VirtualBankAccount::where('account_id', $account_id)
                ->select('iban', 'bic', 'currency', 'account_number', 'bank_account_holder_name', 'bank_name', 'bank_address')
                ->first();
            return response()->json([
                'status' => 'success',
                'data' => $bank_account,
            ], 200);
        }
    }


    public function baneficiaryInfo($beneficiary_id)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'User not authenticated'], 401);
        }

        if (!empty($beneficiary_id)) {
            $beneficiary = ClientBeneficiary::where('id', $beneficiary_id)->first();
            $bank_beneficiary = VirtualBankBeneficiary::where('parent_beneficiary_id', $beneficiary['id'])
                //->select('iban', 'bic', 'currency', 'account_number', 'bank_account_holder_name', 'bank_name', 'bank_address')
                ->first();
            return response()->json([
                'status' => 'success',
                'data' => $bank_beneficiary,
            ], 200);
        }
    }

    public function newPayoutTransaction($acc_trn, $tr_req_id, $payment_type, $funding_bank_account_id)
    {

        // $parent_account_id = $acc_trn['account_id'];
        // $transaction_date = $acc_trn['transaction_date'];
        $currency_id = $acc_trn['currency_id'];
        $reference = $acc_trn['remarks'];
        $parent_beneficiary_id = $acc_trn['beneficiary_id'];
        $currency = Currency::where('id', $currency_id)->first();
        $currency_short =  trim(explode(' ', strtoupper($currency->short))[0]);

        $amount = $acc_trn['amount'];
        $ben = $this->getBeneficiary($parent_beneficiary_id);
        $bank_beneficiary_id = $ben->bank_beneficiary_id;

        //currency_id
        $payload = [
            "beneficiaryId" => $bank_beneficiary_id,
            "reference" => $reference,
            "reasonCode" => "FUNDS",
            "paymentType" => $payment_type,
            "amount" => [
                "currency" => $currency_short,
                "value" => $amount
            ],
            "accountId" => $funding_bank_account_id
        ];

        $endpoint =  $this->baseUrl . '/transactions/bank-payouts';
        $response = $this->post($endpoint, $payload);

        if (!empty($response->id)) {
            $response->createdDate = Carbon::createFromFormat('Y-m-d\TH:i:s.u\Z', $response->createdDate)->format('Y-m-d H:i:s.u');
            $response->updatedDate = Carbon::createFromFormat('Y-m-d\TH:i:s.u\Z', $response->updatedDate)->format('Y-m-d H:i:s.u');

            // store transaction request in crypto transaction log.
            $this->storeApiResponseData($response->transactionId, json_encode($response), "OpenPayd");
            $this->storeTransaction($response, $tr_req_id, $acc_trn->id);

            Log::info('Openpayd transaction saved, in OpenpaydTransaction table : ' . json_encode($response) . "\n\n");
            return $response;
        } else {
            if (!empty($response->errorCode)) {
                $response->message = $this->getErrorByErrorCode($response->errorCode);
            }
        }

        return  $response;
    }

    public function storeTransaction($data, $trn_request_id = null, $acc_trn_id = null)
    {
        // Save the transaction to the database
        $transaction = new VirtualBankTransaction();
        $transaction->parent_transaction_id = $acc_trn_id;
        $transaction->trn_id = $trn_request_id;
        $transaction->transaction_id = $data->id;
        $transaction->short_id = $data->shortId;
        $transaction->account_id = $data->accountId;
        $transaction->transaction_created_date = $data->createdDate ?? Carbon::now();
        $transaction->transaction_updated_date = $data->updatedDate ?? Carbon::now();
        $transaction->virtual_bank_transaction_id = $data->transactionId;
        $transaction->transaction_category = $data->transactionCategory ?? "other";
        $transaction->payment_type = $data->paymentType;
        $transaction->type = $data->type;
        $transaction->provider_name = $this->provider_name;
        $transaction->status = $data->status === "COMPLETED" ? "Approved" : $data->status;
        $transaction->compliance_status = $data->complianceStatus ?? null;
        $transaction->account_holder_id = $data->accountHolderId;
        // $transaction->response = json_encode($data); // Store full response as JSON
        $transaction->save();
        return $transaction;
    }

    public function getTransactionById($transaction_id)
    {
        $endpoint = $this->baseUrl . '/transactions/' . $transaction_id;
        return $this->get($endpoint);
    }

    public function getBeneficiary($parent_beneficiary_id)
    {
        $ben =  VirtualBankBeneficiary::where('parent_beneficiary_id', $parent_beneficiary_id)->first();
        return $ben;
    }

    public function getLinkedAccount($parent_account_id)
    {
        $bank_account =  VirtualBankAccount::where('account_id', $parent_account_id)->first();
        return $bank_account;
    }
    //returns the main bank accounts
    public function getOpenPaydBankAccounts($currency_code)
    {
        $endpoint = $this->baseUrl . '/accounts?currency=' . $currency_code;
        return $this->get($endpoint);
    }

    public function getOpenPaydBankAccount($account_id)
    {
        $endpoint = $this->baseUrl . '/accounts/' . $account_id;
        return $this->get($endpoint);
    }

    // public function get($endpoint, $account_holder_id = null)
    // {
    //     $auth = $this->getAccessToken();
    //     $headers = [
    //         'X-ACCOUNT-HOLDER-ID' => $account_holder_id ?? $auth['account_holder_id'],
    //         'Authorization' => "Bearer {$auth['access_token']}",
    //     ];

    //     $result = Http::withHeaders($headers)->get($endpoint);
    //     return $this->parseResult(json_decode($result->body()));
    // }

    // public function post($endpoint, $data, $account_holder_id = null, $idempotency_key = null)
    // {
    //     Log::info('Post Url: ' . $endpoint);
        
    //     try {

    //         $auth = $this->getAccessToken();
    //         $headers = [
    //         'X-ACCOUNT-HOLDER-ID' => $account_holder_id ?? $auth['account_holder_id'],
    //         'Authorization' => "Bearer {$auth['access_token']}",
    //         ];

    //     if ($idempotency_key) {
    //         $headers['idempotency-key'] = $idempotency_key;
    //     }

    //     $result = Http::withHeaders($headers)->post($endpoint, $data);
    //     return $this->parseResult(json_decode($result->body()));

    //     } catch (\Throwable $e) {
    //         // $this->logRequestResponse('POST', $url, $headers, $payload, null, $e);
    //         throw $e;
    //     }
    // }

    protected function get(string $endpoint, $account_holder_id = null)
    {
        // $url = $this->baseUrl . $endpoint;
        $auth = $this->getAccessToken();
        $headers = [
            'X-ACCOUNT-HOLDER-ID' => $account_holder_id ?? $auth['account_holder_id'],
            'Authorization' => "Bearer {$auth['access_token']}",
        ];

        try {
            $response = Http::withHeaders($headers)->get($endpoint);

            // Log using generic function
            $this->logRequestResponse('POST', $endpoint, $headers, null, $response);

            return $this->handleResponse($response);
        } catch (\Throwable $e) {
            $this->logRequestResponse('POST', $endpoint, $headers, null, $e);
            throw $e;
        }
    }

    protected function post(string $endpoint, array $payload = [], $account_holder_id = null, $idempotency_key = null)
    {
        // $url = $this->baseUrl . $endpoint;
        $auth = $this->getAccessToken();
        $headers = [
            'X-ACCOUNT-HOLDER-ID' => $account_holder_id ?? $auth['account_holder_id'],
            'Authorization' => "Bearer {$auth['access_token']}",
        ];

        if ($idempotency_key) {
            $headers['idempotency-key'] = $idempotency_key;
        }

        try {
            $response = Http::withHeaders($headers)->post($endpoint, $payload);

            // Log using generic function
            $this->logRequestResponse('POST', $endpoint, $headers, $payload, $response);

            return $this->handleResponse($response);
        } catch (\Throwable $e) {
            $this->logRequestResponse('POST', $endpoint, $headers, $payload, null, $e);
            throw $e;
        }
    }

    protected function logRequestResponse(string $method, string $url, array $headers = [], $payload = null, $response = null, $exception = null)
    {
        // Mask sensitive headers
        $safeHeaders = collect($headers)->map(function ($value, $key) {
            return in_array(strtolower($key), ['authorization', 'x-api-key']) ? '***' : $value;
        })->toArray();

        Log::info("\n\n ----------===================------------\n");

        // Log request first
        Log::info('API Request', [
            'method'  => $method,
            'url'     => $url,
            'headers' => $safeHeaders,
            'payload' => $payload,
        ]);

        if ($response) {
            Log::info('API Response', [
                'method' => $method,
                'url'    => $url,
                'status' => $response->status(),
                'body'   => $response->json() ?? $response->body(),
            ]);

            if (!$response->successful()) {
                Log::warning('API Request failed', [
                    'method' => $method,
                    'url'    => $url,
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
            }
        }

        if ($exception) {
            Log::error('API Exception', [
                'method' => $method,
                'url'    => $url,
                'error'  => $exception->getMessage(),
            ]);
        }

        Log::info("\n ----------===================------------\n\n");
    }

    protected function handleResponse($response)
    {
        //dd($response);
        if ($response->successful() && is_null($response->body())) {
            return [
                'success' => true,
                'data' => $response->json()
            ];
        }

        if ($response->successful()) {
            // return $response->json();
            return json_decode($response->body());
        }

        return (object)[
            'error'       => true,
            'status_code' => $response->status(),
            'message'     => $response->json('message') ?? $response->body(),
            'raw'         => $response->json() ?? $response->body(),
        ];
    }

    // protected function handleResponse($response)
    // {
    //     //dd($response);
    //     if ($response->successful() && is_null($response->body())) {
    //         return [
    //             'success' => true,
    //             'data' => $response->json()
    //         ];
    //     }

    //     if ($response->successful()) {
    //         return $response->json();
    //     }

    //     return (object)[
    //         'error'       => true,
    //         'status_code' => $response->status(),
    //         'message'     => $response->json('message') ?? $response->body(),
    //         'raw'         => $response->json() ?? $response->body(),
    //     ];
    // }

    
    public function parseResult($result)
    {
        if (!$result) {
            log::info('Openpayd Error: ', $result);
            throw new Exception('Something went wrong, please try again later.');
        } elseif (!empty($result->data)) {
            return $result->data;
        } else {
            return $result;
        }
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

    public static function getDefaultTransactionMethod($system = null)
    {
        // if ($system === 'OpenPayd') {
            $default_method = TransactionMethod::where('is_default', true)->first();
        // }

        return $default_method ?? null;
    }

    public function createSweepPayout($transaction, $trRequest,  $funding_bank_account_id, $payment_type)
    {   
        
        try {
            $beneficiary = $transaction->beneficiary;
            $virtualBankbeneficiary = $transaction->beneficiary->virtualBankBeneficiary;
            $openpayd_child_account = $transaction->account->bankAccount;
            // $openpayd_linked_client = $transaction->account?->linkedClient ?? null;
            $openpayd_linked_client = $openpayd_child_account?->linkedClient ?? null;

            if (!$openpayd_linked_client && $openpayd_child_account) {
                $openpayd_linked_client = VirtualBankLinkedClient::where('client_id', $openpayd_child_account->client_id)
                ->where('provider_name', 'openpayd')
                ->first();
            }

            if (!$openpayd_child_account) {
                Log::error('Openpayd child account not found', [
                    'transaction_id' => $transaction->id,
                    'account_id'     => $transaction->account_id,
                ]);

                throw new \Exception('Openpayd child account not found.');
            }

            $beneficiary_type_map = [
                "RETAIL"     => "RETAIL",
                "CORPORATE"  => "CORPORATE",
                "individual" => "RETAIL",
                "company"    => "CORPORATE",
            ];
            
            $payload = [
                "sweepSourceAccountId" => $funding_bank_account_id,
                "linkedAccountHolderId" => $openpayd_linked_client->account_holder_id,
                "accountId" => $openpayd_child_account->virtual_bank_account_id,
                "beneficiary" => [
                    "bankAccountCountry" => $virtualBankbeneficiary->bank_account_country,
                    "customerType" => $beneficiary_type_map[$virtualBankbeneficiary->beneficiary_type],
                    "companyName" => $virtualBankbeneficiary->company_name,
                    "firstName" => $virtualBankbeneficiary->beneficiary_first_name,
                    "lastName" => $virtualBankbeneficiary->beneficiary_last_name,
                    "beneficiaryAddressLine" => $virtualBankbeneficiary->beneficiary_address_line ?? $beneficiary->address,
                    "beneficiaryCity" => $virtualBankbeneficiary->beneficiary_city,
                    "beneficiaryCountry" => $virtualBankbeneficiary->beneficiary_country,
                    "beneficiaryPostalCode" => $virtualBankbeneficiary->beneficiary_postal_code,
                    "bic" => $beneficiary->swift_code,
                ],
                "purposeCode" => "ACCT",
                "paymentType" => $payment_type,
                "amount" => [
                    "currency" => $openpayd_child_account->currency,
                    "value" => $transaction->amount
                ],
                // "reference" => $transaction->remarks,
                "reference" => "Account Transfer",
            ];

            $iban = $beneficiary->iban ?? $virtualBankbeneficiary->iban;

            match ($openpayd_child_account->currency) {
                'EUR' => $payload['beneficiary']['iban'] = $iban,
                'GBP' => $payload['beneficiary']['accountNumber'] = $beneficiary->account_number,
                default => [
                    $payload['beneficiary']['iban'] = $iban,
                    $payload['beneficiary']['accountNumber'] = $beneficiary->account_number,
                ]
            };

            if ($beneficiary->sort_code) {
                $payload['beneficiary']['routingCodes'] = [
                    "SORT_CODE" => $beneficiary->sort_code
                ];
            }
            $endpoint =  $this->baseUrl . '/transactions/sweepPayout';
            $response = $this->post($endpoint, $payload, null, $transaction->id);
            // $response = $this->post($endpoint, $payload);

            if (!empty($response->transactionId)) {
                // assign transactionId to id properly
                $response->id = $response->transactionId;
                $response->type = "PAYOUT";
                $response->accountId = $openpayd_child_account->virtual_bank_account_id;
                $response->accountHolderId = $openpayd_linked_client->account_holder_id;
                $response->createdDate = Carbon::now()->format('Y-m-d H:i:s.u');
                $response->updatedDate = Carbon::now()->format('Y-m-d H:i:s.u');
                
                // store transaction request in crypto transaction log.
                $this->storeApiResponseData($response->transactionId, json_encode($response), "OpenPayd");
                $this->storeTransaction($response, $trRequest->id, $transaction->id);

                Log::info('Openpayd Sweep transaction saved, in OpenpaydTransaction table : ' . json_encode($response) . "\n\n");
                return $response;
            } else {
                if (!empty($response->errorCode)) {
                    $response->message = $this->getErrorByErrorCode($response->errorCode);
                }
            }

            return  $response;

        } catch (\Throwable $e) {
            log::error('Exception during Openpayd sweep payout creation', ['error' => $e->getMessage()]);
            log::error('Exception: ', ['error' => $e]);
            return (object) [
                'errorCode' => "Something went wrong, please try again later",
                'message' => 'Exception during Openpayd sweep payout creation!'
            ];
        }
    }

    private function validatePaymentPayload(string $paymentType, string $currency, $beneficiary, $virtualBankBeneficiary, $transaction): object {

        $remarks = trim($transaction->remarks ?? '');

        // =======================
        // Currency Validation
        // =======================
        if ($currency === 'EUR') {
            $iban = $beneficiary->iban ?? $virtualBankBeneficiary->iban ?? null;

            if (empty($iban)) {
                return (object) [
                    'status' => false,
                    'message' => 'IBAN is required for EUR payments.'
                ];
            }
        }

        switch ($paymentType) {
            case 'FASTER_PAYMENTS':
                return $this->validateFasterPayments(
                    $beneficiary,
                    $remarks
                );

            case 'CHAPS': return $this->validateChapsReference($remarks);
            case 'SEPA':
            case 'SEPA_INSTANT':
                return $this->validateSepaReference(
                    $remarks,
                    $paymentType
                );

            case 'TARGET2':
                return $this->validateTarget2(
                    $beneficiary,
                    $virtualBankBeneficiary,
                    $remarks
                );
        }

        return (object) [
            'status' => true,
            'message' => null
        ];
    }

    private function validateFasterPayments($beneficiary, string $remarks): object {

        if (empty($beneficiary->account_number) || empty($beneficiary->sort_code) || empty($remarks)) {
            return (object) [
                'status' => false,
                'message' => 'Faster Payments validation failed. Account number, sort code and reference are required.'
            ];
        }

        return (object) [
            'status' => true,
            'message' => null
        ];
    }

    private function validateChapsReference(string $reference): object
    {
        if (empty($reference)) {
            return (object) [
                'status' => false,
                'message' => 'Reference is required for CHAPS payments.'
            ];
        }

        if (strlen($reference) > 35) {
            return (object) [
                'status' => false,
                'message' => 'CHAPS reference must not exceed 35 characters.'
            ];
        }

        if (!preg_match('/^[A-Za-z0-9\s,\.-]+$/', $reference)) {
            return (object) [
                'status' => false,
                'message' => 'CHAPS reference can contain only letters, numbers, spaces, commas, full stops and hyphens.'
            ];
        }

        return (object) [
            'status' => true,
            'message' => null
        ];
    }

    private function validateSepaReference(string $reference, string $paymentType): object {

        if (strlen($reference) < 2 || strlen($reference) > 140) {
            return (object) [
                'status' => false,
                'message' => "{$paymentType} reference must contain between 2 and 140 characters."
            ];
        }

        if (!preg_match('/^[A-Za-z0-9\s+\-\/\?\.\:,\(\)]+$/', $reference)) {
            return (object) [
                'status' => false,
                'message' => "{$paymentType} reference contains invalid characters."
            ];
        }

        if (preg_match('/([+\-\/\?\.\:,\(\)])\1+/', $reference)) {
            return (object) [
                'status' => false,
                'message' => "{$paymentType} reference cannot contain the same special character repeatedly."
            ];
        }

        return (object) [
            'status' => true,
            'message' => null
        ];
    }

    private function validateTarget2($beneficiary, $virtualBankBeneficiary, string $remarks): object {

        $requiredFields = [
            'Beneficiary Address'     => $virtualBankBeneficiary->beneficiary_address_line ?? null,
            'Beneficiary City'        => $virtualBankBeneficiary->beneficiary_city ?? null,
            'Beneficiary Postal Code' => $virtualBankBeneficiary->beneficiary_postal_code ?? null,
            'Beneficiary Country'     => $virtualBankBeneficiary->beneficiary_country ?? null,
            'IBAN'                    => $beneficiary->iban ?? $virtualBankBeneficiary->iban ?? null,
            'BIC/SWIFT Code'          => $beneficiary->swift_code ?? null,
        ];

        foreach ($requiredFields as $field => $value) {
            if (empty($value)) {
                return (object) [
                    'status' => false,
                    'message' => "{$field} is required for TARGET2 payments."
                ];
            }
        }

        // TARGET2 uses same reference rules as CHAPS
        return $this->validateChapsReference($remarks);
    }
}
