<?php

namespace App\Helpers;

use App\Models\Module;
use App\Models\ModuleConfiguration;
use App\Models\VirtualBankAccount;
use App\Models\VirtualBankLinkedClient;
use App\Models\ClientKYC;
use App\Models\Currency;
use App\Models\ClientFileConfig;
use App\Models\ClientCompanyFile;
use App\Models\ClientIndividualFile;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use League\Flysystem\Exception;


class LorumApis
{
    private string $apiBaseUrl;
    private string $authBaseUrl;
    private string $clientId;
    private string $clientSecret;
    private string $allowedCurrencies;
    private string $lorum_usd_primary_account_id;
    private string $lorum_aed_primary_account_id;
    private ?string $lorum_allowed_ips = null;
    public bool $process_payout_from_child;

    private $identifiers = array(

        'individual' => ['utility_bill', 'bank_statement'],
        'company' => ['certificate_of_incorporation', 'memorandum_of_association', 'bank_statement']
    );

    /* =====================================================
     | CONSTRUCTOR
     ===================================================== */
    public function __construct()
    {
        // $cfg = Cache::remember('lorum.config', 86400, function () {
        //     $module = Module::where('name', 'Lorum')->first();
            
        //     if (!$module) {
        //         throw new Exception('Lorum module not found');
        //     }

        //     $config = ModuleConfiguration::where('module_id', $module->id)->first();

        //     if (!$config) {
        //         throw new Exception('No Lorum configuration found');
        //     }

        //     // Encrypt full config payload
        //     // return Crypt::encryptString(json_encode($config->configurations));
        //     return $config->configurations;
        // });

        // // decrypt + unserialize once
        // // $cfg = unserialize(Crypt::decryptString($cfg));
        // $cfg = unserialize($cfg);

        if (!session('LorumConfig')) {
            $module = Module::where('name', 'Lorum')->first();
            $config = ModuleConfiguration::where('module_id', $module->id)->first();

            if (!$config) {
                throw new Exception('No Lorum configuration found');
            }

            session(['LorumConfig' => unserialize($config->configurations)]);
        }

        $cfg = session('LorumConfig');

        if (app()->environment('production')) {
            $this->apiBaseUrl   = $cfg['base_url']['value'];
            $this->authBaseUrl  = $cfg['auth_base_url']['value'];
            $this->clientId     = $cfg['client_id']['value'];
            $this->clientSecret = $cfg['client_secret']['value'];
            $this->lorum_usd_primary_account_id = $cfg['usd_primary_account_id']['value'];
            $this->lorum_aed_primary_account_id = $cfg['eur_primary_account_id']['value'];
            $this->lorum_allowed_ips = $cfg['production_web_hook_ips']['value'];
            $this->process_payout_from_child = $cfg['process_payout_from_child']['value'];
        } else {
            $this->apiBaseUrl   = $cfg['sandbox_base_url']['value'];
            $this->authBaseUrl  = $cfg['sandbox_auth_base_url']['value'];
            $this->clientId     = $cfg['sandbox_client_id']['value'];
            $this->clientSecret = $cfg['sandbox_client_secret']['value'];
            $this->lorum_usd_primary_account_id = $cfg['sandbox_usd_primary_account_id']['value'];
            $this->lorum_aed_primary_account_id = $cfg['sandbox_eur_primary_account_id']['value'];
            $this->lorum_allowed_ips = $cfg['sandbox_web_hook_ips']['value'];
            $this->process_payout_from_child = $cfg['process_payout_from_child']['value'];
        }

        $this->allowedCurrencies = Currency::where('is_active', 1)
            ->where('virtual_iban_provider', 'lorum')
            ->pluck('short')
            ->implode(',');
    }

    /* =====================================================
     | AUTH & HTTP
       ===================================================== 
    */
    public function getAllowedWebHookIps()
    {
        return $this->lorum_allowed_ips;
    }

    protected function getAccessToken(): string
    {
        $cacheKey = 'lorum_api_access_token';

        if (Cache::has($cacheKey)) {
            return Crypt::decryptString(Cache::get($cacheKey));
        }

        $response = Http::post($this->authBaseUrl . '/oauth/token', [
            'audience'      => 'https://api.fuse.me',
            'grant_type'    => 'client_credentials',
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
        ]);

        if (!$response->successful()) {
            throw new Exception('Unable to authenticate with Lorum');
        }

        $data = $response->json();

        Log::channel('lorumLogs')->info('Get Access Token Request', [
            'method' => 'POST',
            'url'    => $this->authBaseUrl . '/oauth/token',
        ]);

        $expiresIn = $data['expires_in'] ?? 3600;
        $ttl = max($expiresIn - 300, 60); // 5 min safety buffer
        $encrypted = Crypt::encryptString($data['access_token']);
        Cache::put($cacheKey, $encrypted, Carbon::now()->addSeconds($ttl));

        return $data['access_token'];
    }

    protected function get(string $endpoint, array $headers = [])
    {
        $url = $this->apiBaseUrl . $endpoint;
        $method = "GET";

        try {
            $httpResponse = Http::withToken($this->getAccessToken())
                ->withHeaders($headers)
                ->get($url);

            $response = $this->handleResponse($httpResponse);

            // Use generic logger
            $this->logRequestResponse($method, $url, $headers, null, $httpResponse);

            return $response;
        } catch (\Throwable $e) {
            $this->logRequestResponse($method, $url, $headers, null, null, $e);
            throw $e;
        }
    }

    protected function post(string $endpoint, array $payload = [], array $headers = [])
    {
        $url = $this->apiBaseUrl . $endpoint;

        try {
            $response = Http::withToken($this->getAccessToken())
                ->withHeaders($headers)
                ->post($url, $payload);

            // Log using generic function
            $this->logRequestResponse('POST', $url, $headers, $payload, $response);

            return $this->handleResponse($response);
        } catch (\Throwable $e) {
            $this->logRequestResponse('POST', $url, $headers, $payload, null, $e);
            throw $e;
        }
    }

    protected function patch(string $endpoint, array $payload = [], array $headers = [])
    {
        $url = $this->apiBaseUrl . $endpoint;
        $method = 'PATCH';

        try {
            $httpResponse = Http::withToken($this->getAccessToken())
                ->withHeaders($headers)
                ->patch($url, $payload);

            // Use generic logger
            $this->logRequestResponse($method, $url, $headers, $payload, $httpResponse);

            if ($httpResponse->successful()) {
                return [
                    'success' => true,
                    'data'    => $httpResponse->json()
                ];
            }

            return [
                'success' => false,
                'status'  => $httpResponse->status(),
                'error'   => $httpResponse->body()
            ];
        } catch (\Throwable $e) {
            $this->logRequestResponse($method, $url, $headers, $payload, null, $e);
            throw $e;
        }
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
            return $response->json();
        }

        return (object)[
            'error'       => true,
            'status_code' => $response->status(),
            'message'     => $response->json('message') ?? $response->body(),
            'raw'         => $response->json() ?? $response->body(),
        ];
    }

    /* =====================================================
     | PUBLIC ENTRY
     ===================================================== */
    public function storeLorumdClient($client, $account = null)
    {
        switch ($client->type) {
            case 'individual':
                return $this->storeLorumIndividual($client, $account);
            case 'company':
                return $this->storeLorumCompany($client, $account);
        }

        return (object)['error' => true, 'message' => 'Unsupported client type'];
    }

    /* =====================================================
     | INDIVIDUAL CUSTOMER
     ===================================================== */
    public function storeLorumIndividual($client, $account = null)
    {
        $existing = $this->getExistingLinkedClient(clientId: $client->id);

        if ($existing) {
            $customer_id = $existing->linked_client_id;
        } else {
            $is_checks = $this->checkLorumRequiredDocuments($client, strtolower($account->currency->short) ?? null);
            if (!empty($is_checks)) return $is_checks;

            // Check Client By in Lorum System
            // $lorum_client = $this->get("/v1/customers?reference={$client->id}&customer_type=individual");

            $customer = $this->createIndividualCustomer($client, $account);
            if (isset($customer->error) && $customer->raw['code'] != 'missing_document') {
                return $customer;
            }

            $customer_id = $customer['id'];
        }

        // $customer = $this->createIndividualCustomer($client);
        // if (isset($customer->error)) {
        //     return $customer;
        // }

        // Currency onboarding FIRST
        if (strtolower($account->currency->short)  == 'usd') {
            $status = $this->handleCurrencyOnboarding($customer_id, $client, $account);
            // dd($status);
            if (!empty($status->error) && $status->error == true) {
                if (!empty($status->error) && $status->error === true  && isset($status->onboarding_status)  && $status->onboarding_status === 'pending') {
                    // Store the client with pending status and return message to wait for onboarding completion
                    if (empty($customer)) {
                        $customer = $existing;
                    }
                    $customer['onboarding_status'] = 'PENDING';
                    $this->storeLinkedClient($client, $customer);
                }
                return $status;
            }
        } else if (strtolower($account->currency->short)  == 'aed') {
            $currency = strtolower($account?->currency->short);
            $status = $this->checkLorumCurrencyOnboarded($customer_id, $currency);
            $baorded = '';
            
            if (is_array($status) && isset($status['status'])) {
                $baorded =  $status['status'];
                
                if ($status['status'] == 'pending') {
                    log::channel('lorumLogs')->info("=============================== Onboarding status: {$status['status']} ===============================");
                    log::channel('lorumLogs')->info("Customer ID: {$customer_id} has pending onboarding for currency: {$currency}. Attempting to complete onboarding.");
                    log::channel('lorumLogs')->info("=============================== =============================== ===============================");

                    return (object)[
                        'error' => true,
                        'status_code' => 400,
                        'currency' => $currency,
                        'onboarding_status' => $status['status'],
                        'message' => "Customer has pending onboarding for currency: {$currency}. Please wait for the onboarding process to complete",
                    ];
                }
            }

            if (empty($baorded) || (!empty($status->error) && $status->raw['code'] == 'onboarding_not_found') || !empty($status->failed)) {
                $currency_data = $this->updateIndividualCustomerCurrencyData($customer_id, $currency, $client);
                
                if (!isset($currency_data['success']) || !$currency_data['success']) {
                    return (object)[
                        'error'       => true,
                        'status_code' => $currency_data['status'],
                        'message'     => $currency_data['error'],
                    ];
                }
            }
            
            if (!empty($status->error)) {
                $status =  $this->onboardCustomerCurrency($customer_id, $currency);
            }
        }
        //dd($status);

        // KYC upload

        if (isset($existing)) {
            return $existing;
        }
        return $this->storeLinkedClient($client, $customer);
    }

    /* =====================================================
     | COMPANY CUSTOMER
     ===================================================== */
    public function storeLorumCompany($client, $account = null)
    {
        $existing = $this->getExistingLinkedClient($client->id);
        $shareholder_id = null;
                
        if ($existing) {
            $business_customer_id = $existing->linked_client_id;
            $existing_shareholder = $this->getExistingLinkedShareholder($client->id);

            if ($existing_shareholder) {
                $shareholder = $this->getCustomerById($existing_shareholder->linked_client_id);

                if (strtolower($account->currency->short)  == 'usd') {
                    // Check if Shareholder has currency extra for USD
                    $shareholder_extra_currency_exists = $shareholder["individual"]["currency_extra"] ?? [];

                    if (empty($shareholder_extra_currency_exists)) {
                        // Add currency extra for shareholder if not exists 
                        $status = $this->handleShareholderCurrencyOnboarding($shareholder["id"], $client, $account);

                        if (!empty($status->error) && $status->error == true) {
                            return $status;
                        }
                    }
                }
            } else if (strtolower($account->currency->short)  == 'usd') {
                // If no existing shareholder and currency is USD, create shareholder first
                $shareholder = $this->addCompanyDirectorsAndShareholders($client, $account);

                if (isset($shareholder->error) && $shareholder->error == true) {
                    return $shareholder;
                } else if (isset($shareholder->linked_client_id)) {
                    $shareholder_id = $shareholder->linked_client_id;
                }

                if (isset($shareholder_id) && isset($business_customer_id)) {
                    // TODO: need to get actual holding percent
                    $percentage = 55;
                    Log::channel('lorumLogs')->info("Linking Shareholder ID: {$shareholder_id} to Business Customer ID: {$business_customer_id}");
                    $link_shareholder = $this->linkShareholderToBusiness($business_customer_id, $shareholder_id, $percentage);
                    if (isset($link_shareholder->error)) {
                        return $link_shareholder;
                    }
                }
            }
        } else {

            // Check the Lorum Required Documents for Company Customers
            $is_checks = $this->checkLorumRequiredDocuments($client, strtolower($account->currency->short) ?? null);
            if (!empty($is_checks)) return $is_checks;

            $is_usd_currency = strtolower($account->currency->short)  == 'usd';

            if ($is_usd_currency) {
                // Check if Directors or Shareholders are linked, if not create one first
                $shareholder = $this->addCompanyDirectorsAndShareholders($client, $account);

                if (isset($shareholder->error) && $shareholder->error == true) {
                    return $shareholder;
                } else if (isset($shareholder->linked_client_id)) {
                    $shareholder_id = $shareholder->linked_client_id;
                }
            }

            Log::channel('lorumLogs')->info("Creating Business Customer for Client: {$client->name}");
            $business_customer = $this->createCompanyCustomer($client, $account);

            if (isset($business_customer->error) && $business_customer->raw['code'] != 'missing_document') {
                return $business_customer;
            }

            $business_customer_id = $business_customer['id'];

            //TODO: Handle the Duplicate Linking of shareholder, 
            //  Link Shareholder to Business Customer
            if (isset($shareholder_id) && isset($business_customer_id)) {
                // TODO: need to get actual holding percent
                $percentage = 55;
                Log::channel('lorumLogs')->info("Linking Shareholder ID: {$shareholder_id} to Business Customer ID: {$business_customer_id}");
                $link_shareholder = $this->linkShareholderToBusiness($business_customer_id, $shareholder_id, $percentage);
                if (isset($link_shareholder->error)) {
                    return $link_shareholder;
                }
            }
        }

        // ✅ Currency onboarding FIRST
        if (strtolower($account->currency->short)  == 'usd') {
            Log::channel('lorumLogs')->info("Handling Currency Onboarding for Business Customer ID: {$business_customer_id}");
            $status = $this->handleCurrencyOnboarding($business_customer_id, $client, $account);
            if (!empty($status->error) && $status->error == true) {
                return $status;
            }
        } else if (strtolower($account->currency->short)  == 'aed') {
            $currency = strtolower($account?->currency->short);
            $status = $this->checkLorumCurrencyOnboarded($business_customer_id, $currency);
            $isOnboarded = is_array($status) && isset($status['status']) ? $status['status'] : null;
            $hasOnboardingError = !empty($status?->error) && ($status->raw['code'] ?? null) === 'onboarding_not_found';

            // Update currency extra if not onboarded
            if (empty($isOnboarded) || $hasOnboardingError) {
                $currency_data = $this->updateCompanyCustomerCurrencyData($business_customer_id, $currency, $client);
                if (!isset($currency_data['success']) || !$currency_data['success']) {
                    return (object)[
                        'error'       => true,
                        'status_code' => $currency_data['status'],
                        'message'     => $currency_data['error'],
                    ];
                }
            }
            
            if (!empty($status->error)) {
                $status =  $this->onboardCustomerCurrency($business_customer_id, $currency);
            }
        }

        if (isset($existing)) {
            return $existing;
        }

        return $this->storeLinkedClient($client, $business_customer);
    }

    private function checkLorumRequiredDocuments($client, $currency_short = null)
    {
        // Check required company documents
        $docs = $this->getRequiredDocuments($client);
        $required = $this->identifiers[$client->type];

        if ($currency_short === "usd" && $docs->isEmpty()) {
            return (object) [
                'error' => true,
                'status_code' => 400,
                'message' => 'Missing required company documents. Please upload all required documents and try again.',
            ];
        }

        $uploadedIdentifiers = $docs->pluck('identifier')->all();
        $missingDocs = array_diff($required, $uploadedIdentifiers);

        if (!empty($missingDocs)) {
            return (object) [
                'error' => true,
                'status_code' => 400,
                'message' => 'Missing required company documents: ' .
                    implode(', ', $missingDocs) .
                    '. Please upload all required documents and try again.',
            ];
        }

        // Check verified KYC
        $kyc = ClientKYC::where('client_id', $client->id)
            ->where('compliance_status', 'Verified')
            ->whereNull('director_id')
            ->first();

        if (!$kyc) {
            return (object) [
                'error' => true,
                'status_code' => 400,
                'message' => 'Client KYC not verified. Please complete KYC and try again.',
            ];
        }

        if (empty($kyc->selfie_photo_url)) {
            return (object) [
                'error' => true,
                'status_code' => 400,
                'message' => 'Client selfie photo is missing. Please upload selfie photo and try again.',
            ];
        }

        $passport_url = Common::getClientPassportUrl($client->id, $client->type);

        if ($client->type == 'individual') {
            if (empty($passport_url)) {
                if (empty($passport)) {
                    return (object) [
                        'error' => true,
                        'status_code' => 400,
                        'message' => 'Client passport copy is missing. Please upload passport copy and try again.',
                    ];
                }
            }

            if (empty($client->individual->employer->soc_code)) {
                return (object) [
                    'error' => true,
                    'status_code' => 400,
                    'message' => 'Client occupation code is missing. Please add occupation code and try again.',
                ];
            }
        } else {
            if (empty($client->company->contact->soc_code)) {
                return (object) [
                    'error' => true,
                    'status_code' => 400,
                    'message' => 'Client occupation code is missing. Please add occupation code and try again.',
                ];
            }
            if (empty($client->company->naics_code)) {
                return (object) [
                    'error' => true,
                    'status_code' => 400,
                    'message' => 'Client Industry sector code (NAICS) is missing. Please select NAICS code and try again.',
                ];
            }

            if (empty($client->company->tax_number)) {
                return (object) [
                    'error' => true,
                    'status_code' => 400,
                    'message' => 'Client tax number is missing. Please add tax number and try again.',
                ];
            }
        }
    }

    /* =====================================================
     | ACCOUNT CREATION (IBAN)
     ===================================================== */
    public function storeLorumAccount($account, VirtualBankLinkedClient $linkedClient)
    {
        $exists = VirtualBankAccount::where('client_id', $account->client_id)
            ->where('currency_id', $account->currency->id)
            ->where('provider_name', 'lorum')
            ->first();

        if ($exists) {
            return $exists;
        }

        $headers = ['Idempotency-Key' => md5($linkedClient->linked_client_id . '_' . $account->id)];

        $payload = [
            'account_type' => 'virtual',
            'currency'     => $account->currency->short,
            'account_name' => $account->title ?? $account->client->name,
            'external_id'  => (string)$account->id,
        ];
        // dd($payload);
        $create = $this->post("/v1/customers/{$linkedClient->linked_client_id}/accounts", $payload, $headers);

        if (isset($create->error)) {
            return $create;
        }

        $accountData = $this->get("/v1/accounts/{$create['account_id']}");
        // dd($accountData);
        if (isset($accountData->error)) {
            return $accountData;
        }

        $accountData['default_details']['account_nuber'] = null;
        $accountData['default_details']['routing_number'] = null;
        $routing_code = '';

        if (!isset($accountData['default_details']['iban'])) {

            if (isset($accountData['default_details']['wire'])) {
                $routing_code = $accountData['default_details']['wire']['routing_number'];
                //die();
                $accountData['default_details']['account_number']  = $accountData['default_details']['wire']['account_number'];
                $accountData['default_details']['routing_number']  = json_encode([["routingCodeKey" => "ROUTING_NUMBER", "routingCodeValue" => $routing_code]]);
                $accountData['default_details']['iban'] = 'N/A';
            } else {
                return (object)[
                    'error'       => true,
                    'status_code' => 500,
                    'message'     => 'IBAN not generated yet, please try again later.',
                ];
            }
        }
        //dd($accountData);
        return VirtualBankAccount::create([
            'linked_client_id'        => $linkedClient->linked_client_id,
            'account_id'              => $account->id,
            'client_id'               => $account->client_id,
            'virtual_bank_account_id' => $accountData['id'],
            'internal_account_id'     => $accountData['id'] ?? null,
            'currency'                => $accountData['currency'],
            'account_number'          => $accountData['default_details']['account_number'] ?? 'N/A',
            'routing_code_entries'    => $accountData['default_details']['routing_number'] ?? 'N/A',
            'currency_id'             => $account->currency->id,
            'status'                  => $accountData['status'],
            // 'sort_code'                  => $accountData['default_details']['sort_code'] ?? null,
            'iban'                    => $accountData['default_details']['iban'] ?? null,
            'bank_country'            => substr($accountData['default_details']['iban'] ?? '', 0, 2),
            'bank_name'               => 'Lorum',
            'bank_account_holder_name' => $accountData['account_owner'] ?? 'N/A',
            'provider_name'           => 'lorum',
            'bank_address'            => 'N/A',
            'bic'                     => 'N/A',
            'response_data'           => json_encode($accountData),
        ]);
    }

    /* =====================================================
     | INTERNAL HELPERS
     ===================================================== */
    private function getExistingLinkedClient($clientId)
    {
        return VirtualBankLinkedClient::where('client_id', $clientId)
            ->where('provider_name', 'lorum')
            ->where('is_uob_client', false)
            ->first();
    }

    static function getExistingLinkedClientByLorumId($lorum_customer_id)
    {
        return VirtualBankLinkedClient::where('linked_client_id', $lorum_customer_id)
            ->where('provider_name', 'lorum')
            ->where('is_uob_client', false)
            ->first();
    }

    static function getExistingVirtualAccountByLorumAccountId($lorum_account_id)
    {
        return VirtualBankAccount::where('virtual_bank_account_id', $lorum_account_id)
            ->where('provider_name', 'lorum')
            ->first();
    }

    private function getExistingLinkedShareholder($clientId)
    {
        return VirtualBankLinkedClient::where('client_id', $clientId)
            ->where('provider_name', 'lorum')
            ->where('is_uob_client', true)
            ->first();
    }

    private function createIndividualCustomer($client, $account = null)
    {
        
        $individual = $client->individual;
        $nationality = $individual->nationality ?? null;
        $residence = $individual->residence ?? null;
        $currency_short = strtolower($account->currency->short);
        $addressLine1 = trim($client->individual->mailing_address ?? 'N/A');
        
        $payload = [
            'reference'     => (string)$client->id,
            'display_name'  => $client->name,
            'first_name'    => explode(' ', $client->name)[0],
            'last_name'     => explode(' ', $client->name)[1] ?? '-',
            'gender'        => strtolower($individual->gender ?? 'male'),
            'date_of_birth' => $individual->dob,
            'nationality'   => $individual->nationality->iso,
            'country_of_birth'   => $individual->nationality->iso,
            'address'       => [
                'country_code' => $individual->residence->iso,
                'line_1' => substr($addressLine1, 0, 50),
                'line_2' => substr($addressLine1, 50, 100) ?: null,
            ]
        ];

        if ($currency_short === 'usd') {
            $payload['currency_extra'] = [
                [
                    'currency'             => $currency_short,
                    'country_of_birth'     => $nationality->iso ?? null,
                    'country_of_issuance'  => $residence->iso ?? null,
                    'id_number'            => $individual->passport_number ?? null,
                    'occupation_code'      => $individual->soc_code ?? null,
                ]
            ];
        } 
        else if ($currency_short === 'aed') {
            $payload['identification'] = [
                'country_of_issuance'  => $residence->iso ?? null,
                'expiry_date'          => $individual->passport_expiry_date ?? null,
                'number'               => $individual->passport_number ?? null,
                'type'                 => 'passport',
            ];
        }

        //dd(json_encode($payload));
        Log::channel('lorumLogs')->info("------=============== Creating Individual Customer for Client: {$client->name} ===============------");

        $headers = ['Idempotency-Key' => md5(json_encode($payload))];
        $create = $this->post('/v1/customers/individuals', $payload, $headers);
        //once created 

        return isset($create->error) ? $create : $this->get("/v1/customers/{$create['id']}");
    }

    private function createShareHolderIndividualCustomer($client, $account = null)
    {
        $contact = $client->company->contact;
        $nationality = $contact->nationality ?? null;
        $residence = $contact->residence ?? null;
        $nationality = $contact->nationality ?? null;
        $currency_short = strtolower($account->currency->short);
        $addressLine1 = trim($client->company->business_reg_address ?? 'N/A');

        [$firstName, $lastName] = array_pad(explode(' ', trim($contact->name), 2), 2, '-');

        $payload = [
            'reference'     => (string) $client->id,
            'display_name'  => $contact->name,
            'first_name'    => $firstName,
            'last_name'     => $lastName,
            'gender'        => strtolower($contact->gender ?? 'male'),
            'date_of_birth' => $contact->dob ?? null,
            'nationality'   => $nationality->iso ?? null,
            'country_of_birth' => $nationality->iso ?? null,

            'address' => [
                'country_code' => $residence->iso ?? null,
                'line_1' => substr($addressLine1, 0, 50),
                'line_2' => substr($addressLine1, 50, 100) ?: null,
            ],
        ];

        if ($currency_short === 'usd') {
            $payload['currency_extra'] = [
                [
                    'currency'             => $currency_short,
                    'country_of_birth'     => $nationality->iso ?? null,
                    'country_of_issuance'  => $nationality->iso ?? null,
                    'id_number'            => $contact->passport_number ?? null,
                    'occupation_code'      => $contact->soc_code ?? null,
                ]
            ];
        } 
        
        else if ($currency_short === 'aed') {
            $payload['identification'] = [   
                'country_of_issuance'  => $nationality->iso ?? null,
                'expiry_date'          => $contact->passport_expiry ?? null,
                'number'               => $contact->passport_number ?? null,
                'type'                 => 'passport',
            ];
        }

        // dd($payload);
        // $headers = ['Idempotency-Key' => md5($client->id . time())];

        Log::channel('lorumLogs')->info("------=============== Creating Shareholder Individual Customer for Client: {$client->name} ===============------");

        $headers = ['Idempotency-Key' => md5(json_encode($payload))];
        $create = $this->post('/v1/customers/individuals', $payload, $headers);
        return isset($create->error) ? $create : $this->get("/v1/customers/{$create['id']}");
    }

    private function handleShareholderCurrencyOnboarding($customerId, $client, $account = null)
    {
        // Guard: account & currency must exist
        if (!$account?->currency?->short) {
            return null;
        }

        $currency = strtolower($account->currency->short);

        // Guard: currency must be supported
        if (!in_array(strtoupper($currency), $this->getAllowedCurrencies(true), true)) {
            return (object) [
                'error'       => true,
                'status_code' => 400,
                'message'     => 'Lorum: Currency not supported for onboarding',
            ];
        }

        $status = $this->checkLorumCurrencyOnboarded($customerId, $currency);
        $isOnboarded = is_array($status) && isset($status['status']) ? $status['status'] : null;
        $hasOnboardingError = !empty($status?->error) && ($status->raw['code'] ?? null) === 'onboarding_not_found';

        // Update currency extra if not onboarded
        if (empty($isOnboarded) || $hasOnboardingError) {
            Log::channel('lorumLogs')->info("Updating currency extra for Shareholder Customer ID: {$customerId} with currency: {$currency}");
            $contact = $client->company->contact;
            $payload = [
                'individual' => [
                    'currency_extra' => [[
                        'currency'             => $currency,
                        'country_of_birth'     => $contact->nationality->iso ?? null,
                        'country_of_issuance'  => $contact->nationality->iso ?? null,
                        'id_number'            => $contact->passport_number ?? null,
                        'occupation_code'      => $contact->soc_code ?? null,
                    ]],
                ],
            ];

            $response = $this->patch("/v1/customers/{$customerId}", $payload);

            if (!($response['success'] ?? false)) {
                return (object) [
                    'error'       => true,
                    'status_code' => $response['status'] ?? 400,
                    'message'     => $response['error'] ?? 'Currency update failed',
                ];
            }
            Log::channel('lorumLogs')->info("Uploading required documents for Shareholder Customer ID: {$customerId} after currency update");
            // Required documents after currency update
            foreach (
                [
                    fn() => $this->uploadAndSubmitPassport($client, $customerId),
                    fn() => $this->uploadAndSubmitCustomerProofOfAddress($client, $customerId),
                    fn() => $this->uploadAndSubmitIndividualBioMetrics($client, $customerId),
                ] as $upload
            ) {
                $result = $upload();
                if (isset($result->error)) {
                    return $result;
                }
            }
        }

        // Final onboarding if still not onboarded
        if (!empty($status?->error)) {
            Log::channel('lorumLogs')->info("Final onboarding for Shareholder Customer ID: {$customerId} with currency: {$currency}");
            return $this->onboardCustomerCurrency($customerId, $currency);
        }

        return $status;
    }

    
    private function createCompanyCustomer($client, $account = null)
    {
        $client_company = $client->company;
        // $nationality = $client_company->nationality ?? null;
        // $residence = $client_company->residence ?? null;
        $currency_short = strtolower($account->currency->short);
        $country_of_issuance = $client_company->companyCountry->iso;
        $addressLine1 = trim($client_company->business_reg_address ?? 'N/A');
        
        $payload = [
            'reference'     => (string)$client->id,
            'display_name'  => $client->name,
            'relationship_type' => 'direct', // TODO: need to handle indirect
            'business_name' => $client->name,
            'country_of_issuance'   => $country_of_issuance,
            'registration_number' => $client_company->business_reg_number,
            'registration_date' => $client_company->registration_date,
            'address'       => [
                'country_code' => $country_of_issuance,
                'line_1' => substr($addressLine1, 0, 50),
                'line_2' => substr($addressLine1, 50, 100) ?: null,
            ]
        ];
        
        if ($currency_short === 'usd') {
            $payload['currency_extra'] = [
                [
                    // 'currency'             => $currency_short,
                    'country_of_birth'     => $nationality?->iso ?? null,
                    'country_of_issuance'  => $residence?->iso ?? null,
                    'id_number'            => $client_company->passport_number ?? null,
                    'occupation_code'      => $client_company->soc_code ?? null,
                    
                    'currency'      => $currency_short ?? null,
                    'tax_number'    => $client_company->tax_number ?? null,
                    'industry_code' => $client_company->soc_code ?? null,
                ]
            ];
        } else if ($currency_short === 'aed') {
            $payload['currency_extra'] = [
                [   
                    "currency" => $currency_short,
                    "license_issuer" => 'freezone',
                    "trade_license_expiry" => $client->company->expiry_date
                ]
            ];
        }

        Log::channel('lorumLogs')->info("------=============== Creating Company Customer for Client: {$client->name} ===============------");
        
        // $headers = ['Idempotency-Key' => md5($client->id . time())];
        $headers = ['Idempotency-Key' => md5(json_encode($payload))];
        $create = $this->post('/v1/customers/businesses', $payload, $headers);
        return isset($create->error) ? $create : $this->get("/v1/customers/{$create['id']}");
    }

    private function handleCurrencyOnboarding($customerId, $client, $account = null)
    {
        if (!$account?->currency) {
            return;
        }

        $currency = strtolower($account->currency->short);

        if (!in_array(strtoupper($currency), $this->getAllowedCurrencies(true))) {
            return;
        }
        
        $status = $this->checkLorumCurrencyOnboarded($customerId, $currency);
        $baorded = '';
        
        if (is_array($status) && isset($status['status'])) {
            $baorded =  $status['status'];
            
            if ($status['status'] == 'pending') {
                log::channel('lorumLogs')->info("=============================== Onboarding status: {$status['status']} ===============================");
                log::channel('lorumLogs')->info("Customer ID: {$customerId} has pending onboarding for currency: {$currency}. Attempting to complete onboarding.");
                log::channel('lorumLogs')->info("=============================== =============================== ===============================");

                return (object)[
                    'error' => true,
                    'status_code' => 400,
                    'currency' => $currency,
                    'onboarding_status' => $status['status'],
                    'message' => "Customer has pending onboarding for currency: {$currency}. Please wait for the onboarding process to complete",
                ];
            }
        }

        if (empty($baorded) || (!empty($status->error) && $status->raw['code'] == 'onboarding_not_found') || (!empty($status->failed) && $status->failed['code'] == 'missing_document')) {

            $existingDocs = $this->checkClientExistingDocs($customerId);

            if ($client->type === 'individual') {
                $currency_data = $this->updateIndividualCustomerCurrencyData($customerId, $currency, $client);

                if (!isset($currency_data['success']) || !$currency_data['success']) {
                    return (object)[
                        'error'       => true,
                        'status_code' => $currency_data['status'],
                        'message'     => $currency_data['error'],
                    ];
                }

                // Upload Passport
                if (!in_array('passport', $existingDocs)) {
                    $doc1 = $this->uploadAndSubmitPassport($client, $customerId);
                    if (isset($doc1->error)) {
                        return $doc1;
                    }
                }

                // Upload Proof of Address 
                if (!in_array('proof_of_address', $existingDocs)) {
                    $doc2 = $this->uploadAndSubmitCustomerProofOfAddress($client, $customerId);
                    if (isset($doc2->error)) {
                        return $doc2;
                    }
                }

                // Upload Biometrics
                if (!in_array('identity_verification_biometrics', $existingDocs)) {
                    $doc3 = $this->uploadAndSubmitIndividualBioMetrics($client, $customerId);
                    if (isset($doc3->error)) {
                        return $doc3;
                    }
                }
            } else {
                $currency_data = $this->updateCompanyCustomerCurrencyData($customerId, $currency, $client);

                if (!isset($currency_data['success']) || !$currency_data['success']) {
                    return (object)[
                        'error'       => true,
                        'status_code' => $currency_data['status'],
                        'message'     => $currency_data['error'],
                    ];
                }

                // Upload proof of address for company
                if (!in_array('proof_of_address', $existingDocs)) {
                    $company_proof_of_address = $this->uploadAndSubmitCustomerProofOfAddress($client, $customerId);
                    if (isset($company_proof_of_address->error)) {
                        return $company_proof_of_address;
                    }
                }

                // upload Source of fund for company if not exist
                if (!in_array('source_of_fund', $existingDocs)) {
                    $company_source_of_fund = $this->uploadAndSubmitCustomerSourceOfFund($client, $customerId);
                    if (isset($company_source_of_fund->error)) {
                        return $company_source_of_fund;
                    }
                }

                $company_require_docs = $this->uploadAndSubmitCompanyFiles($client, $customerId, $existingDocs);
                if (isset($company_require_docs->error)) {
                    return $company_require_docs;
                }

                // Step 4: Upload Mandatory Documents For All Financial Institutions
                // $this->uploadMandatoryDocumentsForFinancialInstitutions($client, $customerId);

            }
        }

        if (!empty($status->error)) {
            $status =  $this->onboardCustomerCurrency($customerId, $currency);
        }

        return $status;
    }

    public function updateIndividualCustomerCurrencyData($customerId, $currency_code, $client)
    {

        if (strtolower($currency_code) === 'usd') {
            $payload = [
                'individual' => [
                    'currency_extra' => [
                        [
                            "currency" => $currency_code,
                            "country_of_birth" => $client->individual->nationality->iso,
                            "country_of_issuance" => $client->individual->nationality->iso,
                            "id_number" => $client->individual->passport_number,
                            "occupation_code" => $client->individual->employer->soc_code,
                        ],
                    ],
                ]
            ];
        } 
        else if (strtolower($currency_code) === 'aed') {
            // $payload = [];
            $payload = [
                'individual' => [
                    'identification' => [
                        'country_of_issuance'  => $client->individual->residence->iso ?? null,
                        'expiry_date'          => $client->individual->passport_expiry_date ?? null,
                        'number'               => $client->individual->passport_number ?? null,
                        'type'                 => 'passport',
                    ],
                ]
            ];
        }

        $currency_data = $this->patch("/v1/customers/{$customerId}", $payload);
        return $currency_data;
    }

    public function updateCompanyCustomerCurrencyData($customerId, $currency_code, $client)
    {
        if (strtolower($currency_code) === 'usd') {
            $payload = [
                'business' => [
                    'currency_extra' => [
                        [
                            "currency" => strtolower($currency_code),
                            "industry_code" => $client->company->naics_code,
                            "tax_number" => $client->company->tax_number
                        ],
                    ],
                ]
            ];
        } else if (strtolower($currency_code) === 'aed') {
            $payload = [
                'business' => [
                    'currency_extra' => [
                        [
                            "currency" => strtolower($currency_code),
                            "license_issuer" => 'freezone',
                            "trade_license_expiry" => $client->company->expiry_date
                        ],
                    ],
                ]
            ];
        }

        $currency_data = $this->patch("/v1/customers/{$customerId}", $payload);
        return $currency_data;
    }

    public function addCompanyDirectorsAndShareholders($client, $account = null)
    {
        // check shareholder client in our system
        Log::channel('lorumLogs')->info("------=============== Checking existing Shareholder Client for Company Client ID: {$client->id} and Name: {$client->name}");
        $shareholder_client = VirtualBankLinkedClient::where('client_id', $client->id)
            ->where('provider_name', 'lorum')
            ->where('is_uob_client', true)
            ->first();

        if ($shareholder_client) {
            Log::channel('lorumLogs')->info("------=============== Existing Shareholder Client found with Linked Client ID: {$shareholder_client->linked_client_id} for Company Client ID: {$client->id}. Skipping shareholder creation.");
            return $shareholder_client;
        }

        // Create Shareholder Individual Customer for the Company Client.
        Log::channel('lorumLogs')->info("------=============== Creating Shareholder Individual Customer for Client: {$client->name} as part of Company Customer onboarding");
        $shareholder_customer = $this->createShareHolderIndividualCustomer($client, $account);

        if (isset($shareholder_customer->error) && $shareholder_customer->raw['code'] != 'missing_document') {
            return $shareholder_customer;
        }

        $shareholder_customer_id = $shareholder_customer['id'];

        // ✅ Currency onboarding FIRST
        if (strtolower($account->currency->short)  == 'usd') {
            Log::channel('lorumLogs')->info("------=============== Handling Currency Onboarding for Shareholder Customer ID: {$shareholder_customer_id} with currency: {$account->currency->short} as part of Company Customer onboarding");
            $status = $this->handleShareholderCurrencyOnboarding($shareholder_customer_id, $client, $account);
            // dd($status);
            if (!empty($status->error) && $status->error == true) {
                return $status;
            }
        }

        // Trun on is_uob_client flag to true for Shareholders.
        $is_uob_client = true;
        $linked_client = $this->storeLinkedClient($client, $shareholder_customer, $is_uob_client);
        return $linked_client;
    }
    public function uploadAndSubmitCustomerProofOfAddress($client, $customerId)
    {
        $files = $this->getRequiredDocuments($client);
        //dd($files);
        $file_path = '';
        if (!empty($files) && count($files) > 0) {
            $file_path = $files->first()->url;
        } else {
            return (object)['error' => true, 'message' => 'Proof of Address file not found'];
        }
        if (empty($file_path)) {
            return (object)['error' => true, 'message' => 'Proof of Address file not found'];
        }

        $country_of_issuance = $client->type === "individual" ? $client->individual->residence->iso : $client->company->contact->residence->iso;

        // TODO: Need to handle larger files and multiple files per identifier.
        $upload = $this->uploadLorumDocument(
            'customer',
            $customerId,
            'proof_of_address',
            $file_path,
            $country_of_issuance,
            'proof_of_address'
        );

        return isset($upload->error) ? $upload : $this->submitLorumCustomerDocument($customerId, $upload['document_id']);
    }

    public function uploadAndSubmitCustomerSourceOfFund($client, $customerId)
    {
        $files = $this->getRequiredDocuments($client);

        if ($files->isEmpty()) {
            return (object)[
                'error' => true,
                'message' => 'No documents found for Source of Funds'
            ];
        }

        // Prefer source_of_funds, fallback to bank_statement
        $file = $files->firstWhere('identifier', 'bank_statement');

        if (!$file || empty($file->url)) {
            return (object)[
                'error' => true,
                'message' => 'Source of Funds (Bank Statement) file not found'
            ];
        }

        $file_path = $file->url;

        // Resolve country
        $country_of_issuance = $client->type === "individual"
            ? $client->individual->residence->iso
            : $client->company->contact->residence->iso;

        Log::channel('lorumLogs')->info("------=============== Uploading Source of Funds for customer ID: {$customerId}");

        // Correct identifier
        $upload = $this->uploadLorumDocument(
            'customer',
            $customerId,
            'source_of_funds',
            $file_path,
            $country_of_issuance,
            'source_of_funds'
        );

        if (isset($upload->error)) {
            return $upload;
        }

        return $this->submitLorumCustomerDocument(
            $customerId,
            $upload['document_id']
        );
    }

    public function uploadAndSubmitCompanyFiles($client, $customerId, $existingDocs)
    {
        $files = $this->getRequiredDocuments($client);

        if ($files->isEmpty()) {
            return (object)['error' => true, 'message' => 'No required company files found'];
        }
        $docs_type_mapping = [
            'memorandum_of_association' => 'articles_of_association',
            'bank_statement' => 'financial_statements',
            'certificate_of_incorporation' => 'incorporation_cert',
        ];

        // TODO: Need to handle larger files and multiple files per identifier.
        foreach ($files as $file) {
            // Ensure identifier exists (from JOIN or relationship)
            $file_identifier = $file->identifier ?? null;

            if (empty($file_identifier)) {
                return (object)['error' => true, 'message' => 'File identifier missing'];
            }

            if (empty($file->url)) {
                return (object)['error' => true, 'message' => "{$file_identifier} file not found"];
            }

            // Map identifier if necessary
            if (isset($docs_type_mapping[$file_identifier])) {
                $file_identifier = $docs_type_mapping[$file_identifier];
                if (in_array($file_identifier, $existingDocs)) continue;
            }
            Log::channel('lorumLogs')->info("------=============== Uploading company document: {$file_identifier} for customer ID: {$customerId} as part of Company Customer onboarding for Client: {$client->name}");
            // Upload file
            $upload = $this->uploadLorumDocument(
                'customer',
                $customerId,
                $file_identifier,
                $file->url,
                $client->company->contact->residence->iso,
                $file_identifier
            );

            if (isset($upload->error)) {
                return $upload;
            }

            // Submit uploaded document
            $this->submitLorumCustomerDocument($customerId, $upload['document_id']);
        }
    }


    private function uploadMandatoryDocumentsForFinancialInstitutions($client, $customerId)
    {
        // TODO: Need to handle this case.
        // Upload Mandatory Documents 
        // 1.AML CFT Sanctions Policy
        // 2.AML CFT Sanctions Audit
        // 3.Regulatory License
        // 4.Compliance Officer Resume
        // 5.Share Certificate
        // 6.Wolfsberg Questionairre
        // For All these Financial Institutions
        
        $naicsCodes = [
            '425120' => 'Wholesale Trade Agents and Brokers',
            '441110' => 'New Car Dealers',
            '441120' => 'Used Car Dealers',
            '522190' => 'Other Depository Credit Intermediation',
            '522220' => 'Sales Financing',
            '522291' => 'Consumer Lending',
            '522292' => 'Real Estate Credit',
            '522293' => 'International Trade Financing',
            '522320' => 'Financial Transactions Processing, Reserve, and Clearinghouse Activities',
            '522390' => 'Other Credit Intermediation',
            '523120' => 'Securities Brokerage',
            '523130' => 'Commodity Contracts Dealing',
            '523210' => 'Securities and Commodity Exchanges',
            '523920' => 'Portfolio Management',
            '523930' => 'Investment Advice',
            '523999' => 'Miscellaneous Financial Investment Activities',
            '524113' => 'Direct Life Insurance Carriers',
            '524210' => 'Insurance Agencies and Brokerages',
            '525120' => 'Other Insurance Funds',
            '525910' => 'Open-End Investment Funds',
            '525990' => 'Other Financial Vehicles',
            '531210' => 'Offices of Real Estate Agents and Brokers',
            '533110' => 'Lessors of Nonfinancial Intangible Assets (except Copyrighted Works)',
            '541214' => 'Payroll Services',
            '551111' => 'Offices of Bank Holding Companies',
            '561450' => 'Credit Bureaus',
        ];

        $file_identifier = 'general_financial_institution_document';

        if (isset($naicsCodes[$client->company->naics_code])) {
            $config = ClientFileConfig::whereIn('identifier', $file_identifier)->where('type', $client->type)->get();
            $configIds = $config->pluck('id')->toArray();
            $files = ClientCompanyFile::where('client_id', $client->id)->whereIn('file_id', $configIds)->get();

            $file_path = '';
            if (!empty($files) && count($files) > 0) {
                $file_path = $files->first()->url;
            } else {
                return (object)['error' => true, 'message' => "{$file_identifier} file not found"];
            }
            if (empty($file_path)) {
                return (object)['error' => true, 'message' => "{$file_identifier} file not found"];
            }

            $upload = $this->uploadLorumDocument(
                'customer',
                $customerId,
                $file_identifier,
                $file_path,
                $client->company->contact->residence->iso,
                $file_identifier
            );

            return isset($upload->error)
                ? $upload
                : $this->submitLorumCustomerDocument($customerId, $upload['document_id']);
        }
    }

    public function getExcludeNAICSCodes()
    {
        $naicsCodes = [
            '425120' => 'Wholesale Trade Agents and Brokers',
            '441110' => 'New Car Dealers',
            '441120' => 'Used Car Dealers',
            '522190' => 'Other Depository Credit Intermediation',
            '522220' => 'Sales Financing',
            '522291' => 'Consumer Lending',
            '522292' => 'Real Estate Credit',
            '522293' => 'International Trade Financing',
            '522320' => 'Financial Transactions Processing, Reserve, and Clearinghouse Activities',
            '522390' => 'Other Credit Intermediation',
            '523120' => 'Securities Brokerage',
            '523130' => 'Commodity Contracts Dealing',
            '523210' => 'Securities and Commodity Exchanges',
            '523920' => 'Portfolio Management',
            '523930' => 'Investment Advice',
            '523999' => 'Miscellaneous Financial Investment Activities',
            '524113' => 'Direct Life Insurance Carriers',
            '524210' => 'Insurance Agencies and Brokerages',
            '525120' => 'Other Insurance Funds',
            '525910' => 'Open-End Investment Funds',
            '525990' => 'Other Financial Vehicles',
            '531210' => 'Offices of Real Estate Agents and Brokers',
            '533110' => 'Lessors of Nonfinancial Intangible Assets (except Copyrighted Works)',
            '541214' => 'Payroll Services',
            '551111' => 'Offices of Bank Holding Companies',
            '561450' => 'Credit Bureaus',
        ];

        return array_keys($naicsCodes);
    }
    
    public function uploadAndSubmitIndividualBioMetrics($client, $customerId)
    {
        $kyc = ClientKYC::where('client_id', $client->id)
            ->where('compliance_status', 'Verified')
            ->whereNull('director_id')
            ->first();

        if (!$kyc?->selfie_photo_url) {
            return (object)['error' => true, 'message' => 'Selfie missing'];
        }

        $country_of_issuance = $client->type === "individual" ? $client->individual->residence->iso : $client->company->contact->residence->iso;
        // TODO: Need to handle larger files and multiple files per identifier.
        $upload = $this->uploadLorumDocument(
            'customer',
            $customerId,
            'identity_verification_biometrics',
            $kyc->selfie_photo_url,
            $country_of_issuance,
            'identity_verification_biometrics'
        );

        return isset($upload->error)
            ? $upload
            : $this->submitLorumCustomerDocument($customerId, $upload['document_id']);
    }
    private function uploadAndSubmitPassport($client, $customerId)
    {
        // $kyc = ClientKYC::where('client_id', $client->id)
        //     ->where('compliance_status', 'Verified')
        //     ->whereNull('director_id')
        //     ->first();

        $passport_url = Common::getClientPassportUrl($client->id, $client->type);

        if (!$passport_url) {
            if (empty($passport_url)) {
                return (object) [
                    'error' => true,
                    'status_code' => 400,
                    'message' => 'Client passport copy is missing. Please upload passport copy and try again.',
                ];
            }
        }

        if ($client->type === 'individual') {
            $issuingCountry = $client->individual->nationality->iso;
        } else {
            $issuingCountry = $client->company->contact->nationality->iso;
        }
        // TODO: Need to handle larger files and multiple files per identifier.
        $upload = $this->uploadLorumDocument(
            'customer',
            $customerId,
            'passport',
            $passport_url,
            $issuingCountry,
            'passport'
        );

        return isset($upload->error)
            ? $upload
            : $this->submitLorumCustomerDocument($customerId, $upload['document_id']);
    }

    private function storeLinkedClient($client, $customer, $is_uob_client = false)
    {
        $status = $customer['approval_status'] === 'auto_approved' ? 'ACTIVE' : 'PENDING';
        $onboarding_status = !empty($customer['onboarding_status']) === 'PENDING' ? 'PENDING' : $status;

        return VirtualBankLinkedClient::create([
            'linked_client_id' => $customer['id'],
            'client_id'        => $client->id,
            'status'           => $status,
            'kyc_status'       => $onboarding_status,
            'country'          => $customer['address']['country_code'] ?? null,
            'is_uob_client'    => $is_uob_client,
            'provider_name'    => 'lorum',
            'response_data'    => json_encode($customer),
        ]);
    }

    public function getRequiredDocuments($client)
    {
        $identifiers = $this->identifiers[$client->type];
        $client_config_type = $client->type;
        
        if ($client->is_bank) {
            $client_config_type = 'bank';
        }

        // Fetch config entries matching the identifiers
        $config = ClientFileConfig::whereIn('identifier', $identifiers)->where('type', $client_config_type)->get();
        // dd($config);

        // Extract the config IDs
        $configIds = $config->pluck('id')->toArray();

        // Check client type and fetch corresponding files
        if ($client->type === 'individual') {
            $files = ClientIndividualFile::select(
                'client_individual_files.*',
                'client_files_config.identifier'
            )
                ->join(
                    'client_files_config',
                    'client_files_config.id',
                    '=',
                    'client_individual_files.file_id'
                )
                ->where('client_individual_files.client_id', $client->id)
                ->whereIn('client_individual_files.file_id', $configIds)
                ->get();
        } elseif ($client->type === 'company') {
            $files = ClientCompanyFile::select(
                'client_company_files.*',
                'client_files_config.identifier'
            )
                ->join(
                    'client_files_config',
                    'client_files_config.id',
                    '=',
                    'client_company_files.file_id'
                )
                ->where('client_company_files.client_id', $client->id)
                ->whereIn('client_company_files.file_id', $configIds)
                ->get();
        } else {
            $files = collect(); // Return empty collection if type is unknown
        }
        // dd($files);
        return $files;
    }

    /* =====================================================
     | CURRENCY
     ===================================================== */
    // public function getAllowedCurrencies($flag = false)
    // {
    //     return $flag
    //         ? array_map('trim', explode(',', strtoupper($this->allowedCurrencies)))
    //         : $this->allowedCurrencies;
    // }

    public function getAllowedCurrencies($flag = false)
    {
        if ($flag && !empty($this->allowedCurrencies)) {
            $currencies = array_map('trim', explode(',', strtoupper($this->allowedCurrencies)));
            return $currencies;
        } else {
            return trim($this->allowedCurrencies);
        }
    }

    public function checkLorumCurrencyOnboarded($customerId, $currency)
    {
        return $this->get("/v1/customers/{$customerId}/onboarding/{$currency}");
    }

    public function onboardCustomerCurrency($customerId, $currency)
    {
        return $this->post("/v1/customers/{$customerId}/onboarding/{$currency}");
    }

    /* =====================================================
     | DOCUMENTS
     ===================================================== */
    protected function uploadLorumDocument(
        string $ownerType,
        string $ownerId,
        string $documentType,
        string $filePath,
        ?string $issuingCountry = null,
        ?string $idempotencySeed = null
    ) {
        $headers = [
            'Idempotency-Key' => $idempotencySeed ? md5($ownerId . $idempotencySeed) : md5($ownerId),
            'Accept' => 'application/json',
        ];

        $file = file_get_contents($filePath);
        $name = basename(parse_url($filePath, PHP_URL_PATH));

        $request = Http::withToken($this->getAccessToken())
            ->withHeaders($headers)
            ->attach('file', $file, $name);

        $payload = [
            'owner_type'    => $ownerType,
            'owner_id'      => $ownerId,
            'document_type' => $documentType,
            'reference'     => $documentType,
            'description'   => $documentType . ' ' .  $ownerId,
        ];

        if ($issuingCountry) {
            $payload['issuing_country'] = $issuingCountry;
        }
        $url = $this->apiBaseUrl . '/v1/documents';
        $response = $request->post($url, $payload);
        $this->logRequestResponse('POST', $url, $headers, $payload, $response);
        return $this->handleResponse($response);
    }

    public function submitLorumCustomerDocument($customerId, $documentId)
    {
        return $this->post("/v1/customers/{$customerId}/documents", [
            'document_id' => $documentId
        ]);
    }

    public function checkShareholderOfBusiness($BusinessCustomerId)
    {
        return $this->get("/v2/customers/businesses/{$BusinessCustomerId}/shareholders");
    }

    public function linkShareholderToBusiness($BusinessCustomerId, $shareholderId, $percentageShare)
    {
        $payload = [
            'customer_id' => $shareholderId,
            'ownership_percentage' => $percentageShare,
        ];
        return $this->post("/v2/customers/businesses/{$BusinessCustomerId}/shareholders", $payload);
    }

    public function getShareholderById($shareholder_customer_id)
    {
        return $this->get("/v1/customers/businesses/shareholders/{$shareholder_customer_id}");
    }

    public function getCustomerById($customer_id)
    {
        return $this->get("/v1/customers/{$customer_id}");
    }
    
    public function getAllCustomer()
    {
        return $this->get("/v1/customers?page_size=500");
    }

    public function checkClientExistingDocs($customer_id)
    {
        $customer_docs = $this->getCustomerById($customer_id);
        $customer_docs = json_decode(json_encode($customer_docs))->documents;

        // extract document types
        return array_unique(array_map(function ($doc) {
            return $doc->document_type;
        }, $customer_docs));
    }

    public function accountConfirmationLetter($accountId)
    {
        $url = "{$this->apiBaseUrl}/v1/accounts/{$accountId}/confirmation-letter";
        $method = 'GET';
        $headers = ['Accept' => 'application/pdf'];

        try {
            // Make the GET request
            $httpResponse = Http::withToken($this->getAccessToken())
                ->withHeaders($headers)
                ->get($url);

            // Log using the generic logger
            $this->logRequestResponse($method, $url, $headers, null, null);

            // Check if the response is successful
            if (!$httpResponse->successful()) {
                abort(404, 'Confirmation letter not found');
            }

            // Return PDF response
            return response($httpResponse->body(), 200)
                ->header('Content-Type', 'application/pdf')
                ->header(
                    'Content-Disposition',
                    'inline; filename="account-confirmation-letter.pdf"'
                );
        } catch (\Throwable $e) {
            // Log exceptions
            $this->logRequestResponse($method, $url, $headers, null, null, $e);
            throw $e;
        }
    }

    public function createOutboundPaymentApi($payload, $lorum_account_id, $headers)
    {
        return $this->post("/v1/accounts/{$lorum_account_id}/payments", $payload, $headers);
    }

    public function getTransactionById($lorum_transaction_id)
    {
        return $this->get("/v1/transactions/{$lorum_transaction_id}");
    }

    public function getAccountById($lorum_account_id)
    {
        return $this->get("/v1/accounts/{$lorum_account_id}");
    }
    
    public function getClientAccounts($lorum_customer_id)
    {
        return $this->get("/v1/customers/{$lorum_customer_id}/accounts");
    }

    public function createInternalTransfer($payload, $headers)
    {
        return $this->post("/v1/internal-transfers", $payload, $headers);
    }

    public function getPrimaryAccountByCurrency($currency)
    {
        $accountId = null;
        switch (strtoupper($currency)) {
            case 'USD':
                $accountId = $this->lorum_usd_primary_account_id;
                break;
            case 'AED':
                $accountId = $this->lorum_aed_primary_account_id;
                break;
            default:
                Log::channel('lorumLogs')->info('Lorum Primary Account not Found!');
                return null;
        }
        return $accountId;
    }

    protected function logRequestResponse(string $method, string $url, array $headers = [], $payload = null, $response = null, $exception = null)
    {
        // Mask sensitive headers
        $safeHeaders = collect($headers)->map(function ($value, $key) {
            return in_array(strtolower($key), ['authorization', 'x-api-key']) ? '***' : $value;
        })->toArray();

        Log::channel('lorumLogs')->info("\n\n ----------===================------------\n");

        // Log request first
        Log::channel('lorumLogs')->info('API Request', [
            'method'  => $method,
            'url'     => $url,
            'headers' => $safeHeaders,
            'payload' => $payload,
        ]);

        if ($response  && !str_contains($url, '?page_size=500')) {
            Log::channel('lorumLogs')->info('API Response', [
                'method' => $method,
                'url'    => $url,
                'status' => $response->status(),
                'body'   => $response->json() ?? $response->body(),
            ]);

            if (!$response->successful()) {
                Log::channel('lorumLogs')->warning('API Request failed', [
                    'method' => $method,
                    'url'    => $url,
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
            }
        }

        if ($exception) {
            Log::channel('lorumLogs')->error('API Exception', [
                'method' => $method,
                'url'    => $url,
                'error'  => $exception->getMessage(),
            ]);
        }

        Log::channel('lorumLogs')->info("\n ----------===================------------\n\n");
    }

    public function manualLorumSweepTransaction($transaction, $lorum_child_account_id)
    {   
        $outbound_currency = $transaction->currency->short;
        $parent_transaction_id = $transaction->id;
        $transaction_amount = Common::toMinorAmount($transaction->amount);
        $internal_transfer_reference = 'Sweep for Out-Bound Transaction ID: ' . $transaction->id;
        
        try {
            $lorumApi = new LorumApis();
            $primary_account_id = $lorumApi->getPrimaryAccountByCurrency($outbound_currency);

            // Lorum Internal Transfer Payload
            $payload = [
                'amount' => $transaction_amount,
                'currency' => $outbound_currency,
                "external_id" => "{$parent_transaction_id}",
                "external_reference" => $internal_transfer_reference,
                "from_account_id" => $primary_account_id, // Primary account based on currency
                "to_account_id" => $lorum_child_account_id // Child's Lorum account
            ];

            $headers = ['Idempotency-Key' => md5($parent_transaction_id . 'lorum_sweep_1')];
            $internal_transfer_transaction = $lorumApi->createInternalTransfer($payload, $headers);
            
            $sweep_transaction_id = $internal_transfer_transaction["transaction_id"] ?? null;

            if (empty($sweep_transaction_id)) {
                log::channel('lorumLogs')->warning('Lorum internal transfer creation failed', ['payload' => $payload]);
                return (object) [
                    'status' => false,
                    'message' => 'Lorum internal Sweep transfer creation failed!'
                ];
            }

            $internal_transfer_transaction = $lorumApi->getTransactionById($sweep_transaction_id);
            $internal_transfer_transaction = json_decode(json_encode($internal_transfer_transaction));

            DB::table('crypto_transactions_log')->insert([
                'transaction_id' => $sweep_transaction_id,
                'details' => json_encode($internal_transfer_transaction),
                'crypto_system' => "Lorum",
                'source' => 'admin',
                'date_created' => date('Y-m-d H:i:s', time())
            ]);
            
            return (object) [
                'status' => true, 
                'data' => $internal_transfer_transaction
            ];
        } catch (\Throwable $e) {
            log::channel('lorumLogs')->error('Exception during Lorum internal transfer creation', ['error' => $e->getMessage()]);
            return (object) [
                'status' => false,
                'message' => 'Exception during Lorum internal transfer creation!'
            ];
        }
    }
}
