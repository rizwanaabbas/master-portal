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
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use League\Flysystem\Exception;
use Ramsey\Uuid\Uuid;

class ZenusApis
{
    private string $api_base_url;
    private string $auth_base_url;
    private string $client_id;
    private string $client_secret;
    private string $allowed_currencies;
    private ?string $zenus_allowed_ips = null;
    private ?string $zenus_scope = null;
    private ?string $app_registrations_name = null;
    private ?string $master_account_id = null;
    private ?string $master_account_id_operation = null;
    
    public string $webhook_secret;
    public bool $process_payout_from_child;
    
    private $identifiers = array(
        'individual' => ['utility_bill', 'bank_statement'],
        'company' => ['certificate_of_incorporation', 'memorandum_of_association', 'bank_statement', 'certificate_of_business_license']
    );

    /* =====================================================
     | CONSTRUCTOR
     ===================================================== */
    public function __construct()
    {
        // $config = Cache::remember('zenus.config', now()->addHours(24), function () {

        //     $module = Module::where('name', 'Zenus')->first();

        //     if (!$module) {
        //         throw new Exception('Zenus module not found');
        //     }

        //     $config = ModuleConfiguration::where('module_id', $module->id)->first();

        //     if (!$config) {
        //         throw new Exception('No Zenus configuration found');
        //     }

        //     // encrypt cached payload
        //     // return Crypt::encryptString($config->configurations);
        //     return $config->configurations;
        // });

        // // decrypt once
        // // $cfg = unserialize(Crypt::decryptString($config));
        // $cfg = unserialize($config);

        if (!session('ZenusConfig')) {
            $module = Module::where('name', 'Zenus')->first();
            $config = ModuleConfiguration::where('module_id', $module->id)->first();

            if (!$config) {
                throw new Exception('No Zenus configuration found');
            }

            session(['ZenusConfig' => unserialize($config->configurations)]);
        }

        $cfg = session('ZenusConfig');

        if (app()->environment('production')) {
            $this->api_base_url               = $cfg['base_url']['value'];
            $this->app_registrations_name     = $cfg['app_registrations_name']['value'];
            $this->client_id                  = $cfg['application_id']['value'];
            $this->client_secret              = $cfg['client_secret']['value'];
            $this->zenus_scope                = $cfg['scope']['value'];
            $this->auth_base_url              = $cfg['auth_base_url']['value'];
            $this->master_account_id          = $cfg['master_account_id']['value'];
            $this->webhook_secret             = $cfg['webhook_secret']['value'];
            $this->process_payout_from_child  = $cfg['process_payout_from_child']['value'];
        } else {
            $this->api_base_url               = $cfg['sandbox_base_url']['value'];
            $this->app_registrations_name     = $cfg['sandbox_app_registrations_name']['value'];
            $this->client_id                  = $cfg['sandbox_application_id']['value'];
            $this->client_secret              = $cfg['sandbox_client_secret']['value'];
            $this->zenus_scope                = $cfg['sandbox_scope']['value'];
            $this->auth_base_url              = $cfg['sandbox_auth_base_url']['value'];
            $this->master_account_id          = $cfg['sandbox_master_account_id']['value'];
            $this->webhook_secret             = $cfg['sandbox_webhook_secret']['value'];
            $this->process_payout_from_child  = $cfg['process_payout_from_child']['value'];
        }

        $this->allowed_currencies = Currency::where('is_active', 1)->where('virtual_iban_provider', 'zenus')->pluck('short')->implode(',');
    }

    /* =====================================================
     | AUTH & HTTP
     ===================================================== */
    public function getAllowedWebHookIps()
    {
        return $this->zenus_allowed_ips;
    }

    protected function getAccessToken(): string
    {
        if (Cache::has('zenus_api_access_token')) {
            return Crypt::decryptString(Cache::get('zenus_api_access_token'));
        }

        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/x-www-form-urlencoded',
        ])
            ->asForm()
            ->post($this->auth_base_url, [
                'client_id'     => $this->client_id,
                'grant_type'    => 'client_credentials',
                'client_secret' => $this->client_secret,
                'scope'         => $this->zenus_scope,
            ]);

        if (!$response->successful()) {
            throw new Exception('Unable to authenticate with Zenus');
        }

        $data = $response->json();

        Log::channel('zenusLogs')->info('Get Access Token Request', [
            'method'        => 'POST',
            'url'           => $this->auth_base_url,
            'client_id'     => $this->client_id,
            'grant_type'    => 'client_credentials',
            'client_secret' => $this->client_secret,
            'scope'         => $this->zenus_scope,
        ]);

        $encrypted = Crypt::encryptString($data['access_token']);

        Cache::put('zenus_api_access_token', $encrypted, Carbon::now()->addMinutes(55));

        return $data['access_token'];
    }

    protected function get(string $endpoint, array $headers = [])
    {
        $url = $this->api_base_url . $endpoint;
        $method = "GET";

        try {
            $httpResponse = Http::withToken($this->getAccessToken())
                ->withHeaders($headers)
                ->get($url);

            $response = $this->handleResponse($httpResponse);
            $response = json_decode(json_encode($response));

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
        $url = $this->api_base_url . $endpoint;
        
        // if ($endpoint === "/v3/payments/initialise") {
        //     $url = "https://webhook.site/941cc28a-3988-425d-8102-9d555df597d2";
        // }

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
    
    protected function put(string $endpoint, array $payload = [], array $headers = [])
    {
        $url = $this->api_base_url . $endpoint;

        try {
            $response = Http::withToken($this->getAccessToken())
                ->withHeaders($headers)
                ->put($url, $payload);

            // Log using generic function
            $this->logRequestResponse('PUT', $url, $headers, $payload, $response);

            return $this->handleResponse($response);
        } catch (\Throwable $e) {
            $this->logRequestResponse('PUT', $url, $headers, $payload, null, $e);
            throw $e;
        }
    }

    protected function patch(string $endpoint, array $payload = [], array $headers = [])
    {
        $url = $this->api_base_url . $endpoint;
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

        $json = $response->json();

        return (object)[
            'error'       => true,
            'status_code' => $response->status(),
            'message'     => $this->describeZenusError($json, $this->plainText($response->json('message') ?? $response->body())),
            'errors'      => is_array($json) ? ($json['errors'] ?? []) : [],
            'raw'         => $json ?? $response->body(),
        ];
    }

    public function responseAlreadyExists($response): bool
    {
        $codes = $this->zenusErrorCodes($response);
        foreach ($codes as $code) {
            if (stripos($code, 'AlreadyExists') !== false || stripos($code, 'already exists') !== false) {
                return true;
            }
        }

        return false;
    }

    protected function zenusErrorCodes($response): array
    {
        $raw = null;
        if (is_object($response)) {
            $raw = $response->raw ?? null;
            if (is_string($response->message ?? null)) {
                $decoded = json_decode($response->message, true);
                if (is_array($decoded)) {
                    $raw = $raw ?: $decoded;
                }
            }
        } elseif (is_array($response)) {
            $raw = $response;
        } elseif (is_string($response)) {
            $raw = json_decode($response, true);
        }

        $codes = [];
        if (is_array($raw) && !empty($raw['errors']) && is_array($raw['errors'])) {
            foreach ($raw['errors'] as $code) {
                if (is_string($code)) {
                    $codes[] = $code;
                }
            }
        }
        if (is_object($response) && !empty($response->errors) && is_array($response->errors)) {
            foreach ($response->errors as $code) {
                if (is_string($code)) {
                    $codes[] = $code;
                }
            }
        }

        return array_values(array_unique($codes));
    }

    public function describeApiError($json): string
    {
        return $this->describeZenusError($json, 'Zenus request failed. Please try again.');
    }

    protected function describeZenusError($json, $fallback): string
    {
        $labels = [
            'err.fieldValueAlreadyExists' => 'This value already exists at Zenus.',
            'err.notFound' => 'Zenus could not find this record.',
            'err.invalidFieldValue' => 'Zenus rejected this field value.',
            'err.notNull' => 'A required value is missing.',
        ];
        $parts = [];
        $errors = is_array($json) ? ($json['errors'] ?? []) : [];
        foreach ($errors as $code) {
            if (!is_string($code) || $code === '') {
                continue;
            }
            $parts[] = $labels[$code] ?? trim(str_replace(['err.', '_'], ['', ' '], $code));
        }
        $validation = is_array($json) ? ($json['validationErrors'] ?? null) : null;
        if (is_array($validation)) {
            foreach ($validation as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $errorText = $this->validationErrorText($row);
                if ($errorText !== '') {
                    $parts[] = $errorText;
                }
            }
        }
        if (!empty($parts)) {
            return implode(' ', $parts);
        }
        if (is_string($fallback) && $fallback !== '') {
            $decoded = json_decode($fallback, true);
            if (is_array($decoded)) {
                return $this->describeZenusError($decoded, 'Zenus request failed. Please try again.');
            }

            return $fallback;
        }

        return 'Zenus request failed. Please try again.';
    }

    protected function validationErrorText(array $row): string
    {
        $sentences = [];
        $errors = $row['errors'] ?? [];
        if (!is_array($errors)) {
            return $this->plainText($errors);
        }
        foreach ($errors as $error) {
            if (is_string($error)) {
                $sentences[] = $error;
                continue;
            }
            if (is_object($error)) {
                $error = (array) $error;
            }
            if (!is_array($error)) {
                continue;
            }
            $english = $error['translations']['en'] ?? '';
            $code = is_string($error['code'] ?? null) ? $error['code'] : '';
            $known = [
                'PERSON_ID_EXPIRATION_DATE' => 'Zenus is missing this person\'s identification expiry date. Enter the passport or registration expiry date, then retry.',
            ];
            $text = is_string($english) && trim($english) !== '' ? trim($english) : ($known[$code] ?? '');
            if ($text === '' && $code !== '') {
                $text = trim(str_replace('_', ' ', $code));
            }
            if ($code === 'PERSON_ID_EXPIRATION_DATE' && stripos($text, 'expiry') === false) {
                $text .= ' Zenus also requires the identification expiry date.';
            }
            if ($text !== '') {
                $sentences[] = $text;
            }
        }

        return implode(' ', array_unique($sentences));
    }

    /* =====================================================
     | PUBLIC ENTRY
     ===================================================== */
    public function storeZenusClient($client, $account = null)
    {
        switch ($client->type) {
            case 'individual':
                return $this->storeZenusIndividual($client);
            case 'company':
                return $this->storeZenusCompany($client);
        }

        return (object)['error' => true, 'message' => 'Unsupported client type'];
    }

    /* =====================================================
     | INDIVIDUAL CUSTOMER
     ===================================================== */
    public function storeZenusIndividual($client)
    {
        $existing = $this->getExistingLinkedClient(client_id: $client->id);
        $person = null;

        if ($existing) {
            $person_id = $existing->linked_client_id;
            if (isset($existing)) {
                // Activate a person
                $private_person = $this->findPersonById($person_id)->data;
                
                // If the person is not Active then activate the person.
                if ($private_person->statusCode === 'PENDING') {
                    $private_person = $this->activatePerson($person_id);  
                }
                
                if ($existing->kyc_status || $existing->status) {
                    $existing->kyc_status = $private_person->statusCode;
                    $existing->status = $private_person->statusCode;
                    $existing->save();
                }
                return $existing;
            }
        } else {
            $is_checks = $this->checkZenusRequiredDocuments($client);
            if (!empty($is_checks)) return $is_checks;

            // Create Private Person.
            $person = $this->createIndividualCustomer($client);

            if (!empty($person->error)) {
                return $person;
            }

            // // Upload all the required docs for an Private Person. (BIOMETRIC_REPORT, PROOF_OF_ADDRESS, FATCA, SOURCE_OF_FUNDS)
            // $docs_status_check = $this->uploadAllPrivatePersonDocs($client, $person->personId);

            // if (!empty($docs_status_check->error)) {
            //     return $docs_status_check;
            // }
        }

        return $this->storeLinkedClient($client, $person);
    }

    /* =====================================================
     | COMPANY CUSTOMER
     ===================================================== */
    public function storeZenusCompany($client)
    {
        $existing = $this->getExistingLinkedClient($client->id);
        $shareholder = null;

        if (!empty($existing)) {
            $business_customer_id = $existing->linked_client_id;
            $existing_shareholder = $this->getExistingLinkedShareholder($client->id, true);

            if (empty($existing_shareholder)) {
                $new_shareholder_customer = $this->createShareHolderIndividualCustomer($client);

                if (isset($new_shareholder_customer->error)) {
                    return $new_shareholder_customer;
                }
                $percentage = 55;

                // Link Shareholder to Business Customer
                $this->linkShareHolderToBusiness($client, $existing->linked_client_id, $new_shareholder_customer, $percentage);
            }

            if (isset($existing)) {
                // Activate a person
                $legal_person = $this->findPersonById($business_customer_id)->data;
                
                // If the person is not Active then activate the person.
                if ($legal_person->statusCode === 'PENDING') {
                    $legal_person = $this->activatePerson($business_customer_id);  
                }
                
                if ($existing->kyc_status || $existing->status) {
                    $existing->kyc_status = $legal_person->statusCode;
                    $existing->status = $legal_person->statusCode;
                    $existing->save();
                }
            }

            return $existing;
        } else {

            // Check the Zenus Required Documents for Company Customers
            $is_checks = $this->checkZenusRequiredDocuments($client);
            if (!empty($is_checks)) return $is_checks;

            $existing_shareholder = $this->getExistingLinkedShareholder($client->id, true);

            if (empty($existing_shareholder)) {
                // Create Shareholder Individual Customer for the Company Client.
                $new_shareholder_customer = $this->createShareHolderIndividualCustomer($client);

                if (isset($new_shareholder_customer->error)) {
                    return $new_shareholder_customer;
                }

                $shareholder = $new_shareholder_customer;
            } else {
                Log::channel('zenusLogs')->info("------=============== Existing Shareholder Client found with Linked Client ID: {$existing_shareholder->personId} for Company Client ID: {$client->id}. Skipping shareholder creation.");
                $shareholder = $existing_shareholder;
            }

            log::info("Creating Business Customer for Client: {$client->name}");
            $business_customer = $this->createCompanyCustomer($client, $shareholder);

            if (!empty($business_customer->error)) {
                return $business_customer;
            }

            // // Upload KYC files for Business Customer
            // $this->UploadKYCFilesforLegalPerson($client, $business_customer->personId);

            // $business_person_id = $business_customer->personId;
            // $percentage = 55; // TODO: need to get actual holding percent

            // // Link Shareholder to Business Customer
            // $this->linkShareHolderToBusiness($client, $business_person_id, $shareholder, $percentage);
        }

        return $this->storeLinkedClient($client, $business_customer);
    }

    public function createShareHolderIndividualCustomer($client)
    {
        $contact = $client->company->contact;
        $nationality = $contact->nationality ?? null;
        $residence = $contact->residence ?? null;
        // $addressLine1 = trim($client->company->business_reg_address ?? 'N/A');

        $client_gender = strtolower($contact->gender ?? 'male') === "male" ? 'M' : 'F';

        $payload = [
            "personTypeCode" => "P",
            "givenName" => $contact->name, // Mandatory
            "middleName" => explode(' ', $contact->name)[0], // Optional
            "surname" => explode(' ', $contact->name)[1] ?? "", // Mandatory
            "birthDate" => $contact->dob, // Mandatory - YYYY-MM-DD
            "countryOfBirth" => $nationality->iso, // Mandatory - Country ISO-2
            "sex" => $client_gender, // Mandatory - Use M for Male  or F for Female or O for Other
            "nationality" => $residence->iso, // Mandatory - Country ISO-2
            "pep" => false, //TODO: Mandatory - Use true for politically exposed person or false for otherwise
            "identificationNumbers" => [
                [
                    "idNumber" => $contact->passport_number, // Mandatory - Enter a Govt issued ID number
                    "idTypeCode" => "PASSPORT", // Mandatory - Select type of ID => PASSPORT, DRIVER_LICENCE, ID_CARD
                    "idCountryCode" => $residence->iso, // Mandatory - Country ISO-2
                    "issuingDate" => null, //Optional - YYYY-MM-DD
                    "expiryDate" => $contact->passport_expiry_date //Mandatory - YYYY-MM-DD
                ]
            ],
            // TODO:
            // "taxNumbers" => [ //Mandatory ONLY if idCountryCode or addresses.countryCode is US, Optional for all others
            //     [
            //         "taxNumber" => "000-00-0000", // US only -Enter SSN in the following format XXX-XXX-XXXX
            //         "taxNumberTypeCode" => "SSN", // US only - Standard value for Social Security Number
            //         "taxCountryCode" => "US", // Mandatory - Country ISO-2
            //         "primary" => true //standard value
            //     ]
            // ],
            "addresses" => [
                [
                    "addressTypeCode" => "R", // Mandatory - Residency address
                    "countryCode" => $residence->iso, // Mandatory
                    "cityCounty" => $contact->contact_city, // Mandatory
                    "street1" => $contact->street_address, // Mandatory: Street 1
                    "street2" => $contact->street_2_address, // Optional: Street 2
                    "stateRegion" => $contact->state_region, // Mandatory
                    "zip" => $contact->zip_code // Mandatory
                ]
            ]
        ];
        // dd($payload);

        Log::channel('zenusLogs')->info("------=============== Creating Shareholder for Legal Person for Client: {$client->name} ===============------");

        $headers = [
            // 'x-request-id' => (string) Uuid::uuid5(Uuid::NAMESPACE_DNS, 'client-' . $client->id)->toString()
            'x-request-id' => (string) Str::uuid(),
        ];

        $private_person = $this->post('/v2/persons', $payload, $headers);

        if (!empty($private_person->error)) {
            // $message = $private_person->errors;
            return (object) [
                'error' => true,
                'status_code' => 400,
                'message' => 'Something went wrong. Please try again later.',
            ];
        }

        $private_person = json_decode(json_encode($private_person["data"]));

        // create Occupations
        $this->createPrivatePersonOccupation($client, $private_person->personId);

        // Activate a person
        $private_person = $this->findPersonById($private_person->personId)->data;

        // Upload all the required docs for an Private Person. (BIOMETRIC_REPORT, PROOF_OF_ADDRESS, FATCA, SOURCE_OF_FUNDS)
        $docs_status_check = $this->uploadAllPrivatePersonDocs($client, $private_person->personId);

        if (!empty($docs_status_check->error)) {
            return $docs_status_check;
        }

        // Activate a person
        $this->activatePerson($private_person->personId);

        $this->storeLinkedClient($client, $private_person, true);

        //once private_person
        return $private_person;
    }

    private function linkShareHolderToBusiness($client, $legal_person_id, $shareholder, $percentage)
    {
        $payload = [
            "relationships" => [
                // Shareholder/Director relationship
                [
                    "idNumber" => $shareholder->idNumber, // Mandatory - Private Person - ID number
                    "idCountryCode" => $shareholder->idCountryCode, // Mandatory - Private Person - ID country code
                    "relationTypeCode" => "SHAREHOLDER", // Mandatory - Use "SHAREHOLDER" for UBOs and "REPRESENTATIVE" for Signers
                    "statusCode" => "ACTIVE", // Mandatory - Standard Value
                    "role" => "UBO", // Mandatory - Use "UBO" for Shareholders and "SIGNER" for Representatives
                    "beneficiary" => true, // Mandatory - Use true for UBOs and false for SIGNERS
                    "percentageOfShares" => $percentage, // Mandatory - Percentage of ownership from 0-100. Only applicable to UBOs (SHAREHOLDER)
                    "representingRange" => [
                        "startDate" => $client->created_at->format('Y-m-d') // Mandatory - YYYY-MM-DD
                    ]
                ],
                // Representative relation (if the shareholder is also a representative/signer for the company)
                [
                    "idNumber" => $shareholder->idNumber, // Mandatory - Private Person - ID number
                    "idCountryCode" => $shareholder->idCountryCode, // Mandatory - Private Person - ID country code
                    "relationTypeCode" => "REPRESENTATIVE", // Mandatory - Use "SHAREHOLDER" for UBOs and "REPRESENTATIVE" for Signers
                    "statusCode" => "ACTIVE", // Mandatory - Standard Value
                    "role" => "SIGNER", // Mandatory - Use "SIGNER" for Representatives
                    "beneficiary" => false, // Mandatory - Use false for SIGNERS
                    "representingRange" => [
                        "startDate" => $client->created_at->format('Y-m-d') // Mandatory - YYYY-MM-DD
                    ]
                ]
            ]
        ];

        Log::channel('zenusLogs')->info("Linking Shareholder to Business Customer with Payload", [
            'payload' => $payload
        ]);

        $response = $this->post("/v1/persons/{$legal_person_id}/relations", $payload);

        if (isset($response->error)) {
            return $response;
        }

        return $response;
    }

    public function checkZenusRequiredDocuments($client)
    {
        // Check required company documents
        $docs = $this->getRequiredDocuments($client);
        $required = $this->identifiers[$client->type];
        $uploadedIdentifiers = $docs->pluck('identifier')->all();
        $missingDocs = [];
        foreach ($required as $identifier) {
            if (in_array($identifier, $uploadedIdentifiers, true) || $this->clientHasRequiredDocument($client, $identifier)) {
                continue;
            }
            $missingDocs[] = $identifier;
        }
        $invalidCharsPattern = "/[\/\-\?\:\(\)\.,'\+]/";

        if (!empty($missingDocs)) {
            return (object) [
                'error' => true,
                'status_code' => 400,
                'message' => 'Missing required company documents: ' . implode(', ', $missingDocs) . '. Please upload all required documents and try again.',
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
            if (empty($client->individual->city)) {
                return (object) [
                    'error' => true,
                    'status_code' => 400,
                    'message' => 'Client city is missing. Please add city and try again.',
                ];
            }
            if (empty($client->individual->street_address)) {
                return (object) [
                    'error' => true,
                    'status_code' => 400,
                    'message' => 'Client street address is missing. Please add street address and try again.',
                ];
            }
            if (preg_match($invalidCharsPattern, $client->individual->street_address)) {
                return (object) [
                    'error' => true,
                    'status_code' => 400,
                    'message' => "The client street address. The address contains unsupported characters (/ - ? : ( ) . , ' +). Please remove them and try again.",
                ];
            }
            if (empty($client->individual->zip_code)) {
                return (object) [
                    'error' => true,
                    'status_code' => 400,
                    'message' => 'Client zip code is missing. Please add zip code and try again.',
                ];
            }
            if (empty($client->individual->state_region)) {
                return (object) [
                    'error' => true,
                    'status_code' => 400,
                    'message' => 'Client state/region is missing. Please add state/region and try again.',
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
            if (empty($client->company->company_city)) {
                return (object) [
                    'error' => true,
                    'status_code' => 400,
                    'message' => 'Client company city is missing. Please add company city and try again.',
                ];
            }
            if (empty($client->company->zip_code)) {
                return (object) [
                    'error' => true,
                    'status_code' => 400,
                    'message' => 'Client zip code is missing. Please add zip code and try again.',
                ];
            }
            
            if (empty($client->company->contact->street_address)) {
                return (object) [
                    'error' => true,
                    'status_code' => 400,
                    'message' => 'Client contact person street address is missing. Please add the street address and try again.',
                ];
            }

            if (empty($client->company->contact->contact_city)) {
                return (object) [
                    'error' => true,
                    'status_code' => 400,
                    'message' => 'Client contact person city is missing. Please add the city and try again.',
                ];
            }

            if (empty($client->company->contact->state_region)) {
                return (object) [
                    'error' => true,
                    'status_code' => 400,
                    'message' => 'Client contact person state/region is missing. Please add the state or region and try again.',
                ];
            }

            if (empty($client->company->contact->zip_code)) {
                return (object) [
                    'error' => true,
                    'status_code' => 400,
                    'message' => 'Client contact person ZIP/postal code is missing. Please add the ZIP/postal code and try again.',
                ];
            }

            if (empty($client->company->business_reg_street_address)) {
                return (object) [
                    'error' => true,
                    'status_code' => 400,
                    'message' => 'Client business registration street address is missing. Please add the address and try again.',
                ];
            }
            if (preg_match($invalidCharsPattern, $client->company->business_reg_street_address)) {
                return (object) [
                    'error' => true,
                    'status_code' => 400,
                    'message' => "The client street address. The address contains unsupported characters (/ - ? : ( ) . , ' +). Please remove them and try again.",
                ];
            }
            if (empty($client->company->company_region)) {
                return (object) [
                    'error' => true,
                    'status_code' => 400,
                    'message' => 'Client company region is missing. Please add company region and try again.',
                ];
            }
        }
    }

    /* =====================================================
     | ACCOUNT CREATION (IBAN)
     ===================================================== */
    public function storeZenusAccount($account, VirtualBankLinkedClient $linkedClient)
    {
        $exists = VirtualBankAccount::where('client_id', $account->client_id)
            ->where('currency_id', $account->currency->id)
            ->where('provider_name', 'zenus')
            ->first();

        if ($exists) {
            return $exists;
        }

        $headers = ['x-request-id' => (string) Uuid::uuid5(Uuid::NAMESPACE_DNS, $linkedClient->linked_client_id . $account->id)->toString()];

        $master_account_id = $this->master_account_id;
        $client = $account->client;
        $account_type_code = $client->type === "individual" ? "P" : "L";
        $residency_country_code = $client->type === "individual" ? $client->individual->residence->iso : $client->company->companyCountry->iso;

        if ($client->type === "company") {
            $payload = [
                "accountTypeCode" => "VIRTUAL",
                "masterAccountId" => $master_account_id,
                "currencyCode" => $account->currency->short,
                "accountClassCode" => "CORPORATE_DDA",
                "customerGroupCode" => "CORPORATE_DDA",
                "priceListTypeCode" => "Virtual_Customer",
                "accountName" => $account->title,
                "personName" => $client->name,
                "personTypeCode" => $account_type_code,
                "jurisdictionCountryCode" => 'PR',
                "residencyCountryCode" => $residency_country_code,
                "taxResidencyCountryCode" => $residency_country_code,
                "interestMethodAssignmentCode" => "NO_INTEREST"
            ];
        } else {
            $payload = [
                "accountTypeCode" => "VIRTUAL",
                "masterAccountId" => $master_account_id,
                "currencyCode" => $account->currency->short,
                "accountClassCode" => "PERSONAL_DDA",
                "customerGroupCode" => "PERSONAL_DDA",
                "priceListTypeCode" => "Virtual_Customer",
                "accountName" => $account->title,
                "personName" => $client->name,
                "personTypeCode" => $account_type_code,
                "jurisdictionCountryCode" => 'PR',
                "residencyCountryCode" => $residency_country_code,
                "taxResidencyCountryCode" => $residency_country_code,
                "interestMethodAssignmentCode" => "NO_INTEREST"
            ];
        }

        // dd($payload);
        $accountData = $this->post("/v4/persons/{$linkedClient->linked_client_id}/accounts", $payload, $headers);

        if (isset($accountData->error)) {
            return $accountData;
        }

        Log::channel('zenusLogs')->info("\n\n ----------===================------------\n");
        // Log::channel('zenusLogs')->info("Response data", [
        //     'response_data' => $accountData['data'],
        //     // 'response_data_2' => $accountData->data,
        //     // 'accountNumbers' => $account['data']['accountNumbers'][0],
        // ]);
        $accountData = json_decode(json_encode($accountData["data"]), true);

        // Safe extraction
        $accountNumber = $accountData['accountNumbers'][0]['accountNumber']['value'] ?? null;
        $routingNumber = $accountData['accountNumbers'][0]['financialInstitutionId'] ?? null;
        $countryCode   = $accountData['taxResidencyCountryCode'] ?? null;

        // Ensure default_details exists
        $accountData['default_details'] = $accountData['default_details'] ?? [];

        // Fix typo + assign safely
        $accountData['default_details']['account_number'] = $accountNumber ?? 'N/A';
        $accountData['default_details']['routing_number'] = $routingNumber ?? 'N/A';

        return VirtualBankAccount::create([
            'linked_client_id'        => $linkedClient->linked_client_id,
            'account_id'              => $account->id,
            'client_id'               => $account->client_id,

            'virtual_bank_account_id' => $accountData['accountId'] ?? null,
            'internal_account_id'     => $accountData['accountId'] ?? null,
            'currency'                => $accountData['defaultCurrencyCode'] ?? null,

            'account_number'          => $accountData['default_details']['account_number'],
            'routing_code_entries'    => $accountData['default_details']['routing_number'],

            'currency_id'             => $account->currency->id ?? null,
            'status'                  => $accountData['statusCode'] ?? null,

            'iban'                    => $accountData['iban'] ?? 'N/A',
            'bank_country'            => $countryCode,

            'bank_name'               => 'Zenus Bank',
            'bank_account_holder_name' => $accountData['accountName'] ?? 'N/A',
            'provider_name'           => 'zenus',
            'bank_address'            => 'N/A',
            'bic'                     => $accountData['bic'] ?? 'N/A',
            // 'account_numbers'         => json_encode($accountData['accountNumbers']) ?? null,
            'account_info'         => json_encode($accountData['accountNumbers']) ?? null,
            'response_data'           => json_encode($accountData),
        ]);
    }

    /* =====================================================
     | INTERNAL HELPERS
     ===================================================== */
    public function getExistingLinkedClient($client_id)
    {
        return VirtualBankLinkedClient::where('client_id', $client_id)
            ->where('provider_name', 'zenus')
            ->where('is_uob_client', false)
            ->first();
    }

    static function getExistingLinkedClientByZenusId($zenus_customer_id)
    {
        return VirtualBankLinkedClient::where('linked_client_id', $zenus_customer_id)
            ->where('provider_name', 'zenus')
            ->where('is_uob_client', false)
            ->first();
    }

    static function getExistingVirtualAccountByZenusAccountId($lorum_account_id)
    {
        return VirtualBankAccount::where('virtual_bank_account_id', $lorum_account_id)
            ->where('provider_name', 'lorum')
            ->first();
    }

    public function getExistingLinkedShareholder($client_id, $get_response_data = false)
    {
        $virtual_bank_linked_client = VirtualBankLinkedClient::where('client_id', $client_id)
            ->where('provider_name', 'zenus')
            ->where('is_uob_client', true)
            ->first();

        if ($get_response_data && $virtual_bank_linked_client) {
            return json_decode($virtual_bank_linked_client->response_data);
        }

        return $virtual_bank_linked_client;
    }

    public function postPrivatePerson(array $payload, array $headers, string $logName = '')
    {
        $private_person = $this->post('/v2/persons', $payload, $headers);
        if (!empty($private_person->error)) {
            return (object) [
                'error' => true,
                'status_code' => $private_person->status_code ?? 400,
                'message' => $private_person->message ?? 'Zenus person create failed. Please try again.',
                'raw' => $private_person,
            ];
        }

        return json_decode(json_encode($private_person['data'] ?? $private_person));
    }

    public function createIndividualPersonOnly($client)
    {
        $client_gender = strtolower($client->individual->gender ?? 'male') === "male" ? 'M' : 'F';
        $client_individual = $client->individual;

        $payload = [
            "givenName" => $client->name, // Mandatory
            "middleName" => explode(' ', $client->name)[0], // Optional
            "surname" => explode(' ', $client->name)[1] ?? "", // Mandatory
            "birthDate" => $client_individual->dob, // Mandatory - YYYY-MM-DD
            "countryOfBirth" => $client_individual->nationality->iso, // Mandatory - Country ISO-2
            "sex" => $client_gender, // Mandatory - Use M for Male  or F for Female or O for Other
            "nationality" => $client_individual->nationality->iso, // Mandatory - Country ISO-2
            "email" => $client_individual->email_address, // Optional - Email address

            "pep" => false, //TODO: Mandatory - Use true for politically exposed person or false for otherwise
            "identificationNumbers" => [
                [
                    "idNumber" => $client_individual->passport_number, // Mandatory - Enter a Govt issued ID number
                    "idTypeCode" => "PASSPORT", // Mandatory - Select type of ID => PASSPORT, DRIVER_LICENCE, ID_CARD
                    "idCountryCode" => $client_individual->nationality->iso, // Mandatory - Country ISO-2
                    "issuingDate" => null, //Optional - YYYY-MM-DD
                    "expiryDate" => $client_individual->passport_expiry_date //Mandatory - YYYY-MM-DD
                ]
            ],

            "taxNumbers" => null,

            // TODO:
            // "taxNumbers" => [ //Mandatory ONLY if idCountryCode or addresses.countryCode is US, Optional for all others
            //     [
            //         "taxNumber" => "000-00-0000", // US only -Enter SSN in the following format XXX-XXX-XXXX
            //         "taxNumberTypeCode" => "SSN", // US only - Standard value for Social Security Number
            //         "taxCountryCode" => "US", // Mandatory - Country ISO-2
            //         "primary" => true //standard value
            //     ]
            // ],

            "addresses" => [
                [
                    "addressTypeCode" => "R", // Mandatory - Residency address
                    "countryCode" => $client_individual->nationality->iso, // Mandatory
                    "cityCounty" => $client_individual->city, // Mandatory
                    "street1" => $client_individual->street_address,
                    "street2" => $client_individual->street_2_address, // Optional: Streen 2
                    "stateRegion" => $client_individual->state_region,
                    "zip" => $client_individual->zip_code,
                ]
            ]
        ];

        // dd($payload);
        Log::channel('zenusLogs')->info("------=============== Creating Individual Customer for Client: {$client->name} ===============------");

        $headers = [
            // 'x-request-id' => (string) Uuid::uuid5(Uuid::NAMESPACE_DNS, 'client-' . $client->id)->toString()
            'x-request-id' => (string) Str::uuid(),
        ];

        return $this->postPrivatePerson($payload, $headers, $client->name);
    }


    public function addIdentificationNumbers($client, $personId)
    {
        // Resolve contact / person data based on client type
        $contact = $client->type === 'individual' ? $client->individual : $client->company->contact;
        $residence = $contact->residence ?? null;
        $countryCode = $residence->iso ?? $contact->country_code ?? null;

        $payload = [
            'idCountryCode' => strtoupper($countryCode),
            'idNumber'      => (string) $contact->passport_number,
            'idTypeCode'    => 'PASSPORT',
            'expiryDate'    => Carbon::parse($contact->passport_expiry_date)->toISOString(),
        ];

        if (!empty($contact->passport_issue_date)) {
            $payload['issuingDate'] = Carbon::parse($contact->passport_issue_date)->toISOString();
        }

        Log::channel('zenusLogs')->info("------=============== Adding Identification Number for Client: {$client->name} ===============------");

        $headers = [
            'x-request-id' => (string) Uuid::uuid5(Uuid::NAMESPACE_DNS, 'client-identification-' . $client->id)->toString(),
        ];

        return $this->put("/zv1/persons/{$personId}/identificationNumbers", $payload, $headers);
    }

    private function createIndividualCustomer($client)
    {
        $private_person = $this->createIndividualPersonOnly($client);
        if (!empty($private_person->error)) {
            return $private_person;
        }

        // create Occupations
        $this->createPrivatePersonOccupation($client, $private_person->personId);

        // Upload all the required docs for an Private Person. (BIOMETRIC_REPORT, PROOF_OF_ADDRESS, FATCA, SOURCE_OF_FUNDS)
        $docs_status_check = $this->uploadAllPrivatePersonDocs($client, $private_person->personId);

        if (!empty($docs_status_check->error)) {
            return $docs_status_check;
        }

        // Activate a person
        $this->activatePerson($private_person->personId);

        // Activate a person
        $private_person = $this->findPersonById($private_person->personId)->data;

        //once private_person
        return $private_person;
    }

    public function createPrivatePersonOccupation($client, $personId)
    {
        $soc_code = $client->type === 'individual' ? $client->individual->employer->soc_code : $client->company->contact->soc_code;
        $payload = [
            "entityName" => "PERSON.PERSON", //standard field for Person
            "entityRef" => $personId, //personID created in previous step
            "fieldId" => "bOccupation", //standard field for Occupation
            "fieldValue" => $soc_code // Occupation SOC  (see list https://docs.zenus.io/docs/occupation-soc-list)
        ];

        Log::channel('zenusLogs')->info("------=============== Creating Private Person Occupation for Client: {$client->name} ===============------");

        $headers = [
            'x-request-id' => (string) Uuid::uuid5(Uuid::NAMESPACE_DNS, 'client-occupation' . $client->id)->toString()
        ];

        $create = $this->post('/v2/persons/custom-fields/value', $payload, $headers);
        if ($this->responseAlreadyExists($create)) {
            $found = $this->get("/v1/persons/{$personId}/custom-fields");

            return (object) [
                'error' => true,
                'already_exists' => true,
                'message' => 'This value already exists at Zenus.',
                'current_value' => $this->customFieldCurrentValue($found, 'bOccupation'),
                'new_value' => $soc_code,
            ];
        }

        return $create;
    }

    public function updatePrivatePersonOccupation($personId, $socCode)
    {
        $found = $this->get("/v1/persons/{$personId}/custom-fields");
        $valueId = $this->customFieldValueId($found, 'bOccupation');
        if (!$valueId) {
            $updated = $this->put('/v2/persons/custom-fields/value', [
                'entityName' => 'PERSON.PERSON',
                'entityRef' => $personId,
                'fieldId' => 'bOccupation',
                'fieldValue' => $socCode,
            ]);
            if (is_object($updated) && !empty($updated->error)) {
                if ($this->responseAlreadyExists($updated)) {
                    return (object) [
                        'success' => true,
                        'message' => 'Occupation is already set.',
                    ];
                }

                return $updated;
            }

            return (object) [
                'success' => true,
                'message' => 'Occupation updated.',
            ];
        }

        $updated = $this->put('/v2/persons/custom-fields/value/' . $valueId, [
            'fieldValue' => $socCode,
        ]);
        if (is_object($updated) && !empty($updated->error)) {
            return $updated;
        }

        return (object) [
            'success' => true,
            'message' => 'Occupation updated.',
        ];
    }

    protected function customFieldValueId($response, string $fieldId): ?string
    {
        $rows = [];
        if (is_array($response)) {
            $rows = $response['data'] ?? $response;
        } elseif (is_object($response)) {
            $rows = $response->data ?? [];
        }
        if (is_object($rows)) {
            $rows = [$rows];
        }
        if (!is_array($rows)) {
            return null;
        }
        foreach ($rows as $row) {
            $item = is_object($row) ? (array) $row : $row;
            if (!is_array($item)) {
                continue;
            }
            if (($item['fieldId'] ?? null) !== $fieldId) {
                continue;
            }
            foreach (['valueId', 'fieldValueId', 'customFieldValueId', 'id'] as $key) {
                if (!empty($item[$key])) {
                    return (string) $item[$key];
                }
            }
        }

        return null;
    }

    protected function customFieldCurrentValue($response, string $fieldId): ?string
    {
        $rows = [];
        if (is_array($response)) {
            $rows = $response['data'] ?? $response;
        } elseif (is_object($response)) {
            $rows = $response->data ?? [];
        }
        if (is_object($rows)) {
            $rows = [$rows];
        }
        if (!is_array($rows)) {
            return null;
        }
        foreach ($rows as $row) {
            $item = is_object($row) ? (array) $row : $row;
            if (!is_array($item) || ($item['fieldId'] ?? null) !== $fieldId) {
                continue;
            }
            if (isset($item['fieldValue']) && trim((string) $item['fieldValue']) !== '') {
                return (string) $item['fieldValue'];
            }
        }

        return null;
    }

    public function activatePerson($customer_id)
    {
        try {
            Log::channel('zenusLogs')->info("------=============== Activating Person for Client: {$customer_id} ===============------");

            $response = $this->post("/v1/persons/{$customer_id}/activate");

            if (!empty($response->error)) {
                return $response;
            }
            $response = json_decode(json_encode($response));

            return $response->data ?? $response;
        } catch (\Throwable $e) {
            Log::error('Zenus person activation failed', [
                'ownerId' => $customer_id,
                'error' => $e->getMessage(),
            ]);

            return (object)[
                'error' => true,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function createLegalPersonOnly($client, $shareholder = null)
    {
        $payload = $this->legalPersonPayload($client, $shareholder, true);

        Log::channel('zenusLogs')->info("------=============== Creating Company Customer for Client: {$client->name} ===============------");

        $headers = [
            'x-request-id' => (string) Str::uuid(),
        ];

        return $this->postPrivatePerson($payload, $headers, $client->name);
    }

    public function legalPersonPayload($client, $shareholder = null, bool $withRelationships = true): array
    {
        $client_company = $client->company;
        $country_of_issuance = $client_company->companyCountry->iso;
        $percentage = 55;

        $payload = [
            "personTypeCode" => "L",
            "name" => $client->name,
            "email" => $client_company->contact->email,
            "identificationNumbers" => [
                [
                    "idNumber" => $client_company->business_reg_number,
                    "idCountryCode" => $country_of_issuance,
                    "primary" => true
                ]
            ],
            "taxNumbers" => [
                [
                    "taxNumber" => $client_company->tax_number,
                    "taxCountryCode" => $country_of_issuance,
                    "taxNumberTypeCode" => "TAX_IDENTIFICATION_NUMBER",
                    "primary" => true
                ]
            ],
            "registrationDate" => $client_company->registration_date,
            "counterpartySectorCode" => $client_company->naics_code,
            "addresses" => [
                [
                    "addressTypeCode" => "R",
                    "countryCode" => $country_of_issuance,
                    "cityCounty" => $client_company->company_city ?? null,
                    "street1" => $client_company->business_reg_street_address,
                    "street2" => $client_company->business_street_2_address,
                    "stateRegion" => $client_company->company_region ?? null,
                    "zip" => $client_company->zip_code
                ]
            ],
        ];

        if ($withRelationships && $shareholder) {
            $payload['relationships'] = [
                [
                    "idNumber" => $shareholder->idNumber,
                    "idCountryCode" => $shareholder->idCountryCode,
                    "relationTypeCode" => "SHAREHOLDER",
                    "statusCode" => "ACTIVE",
                    "role" => "UBO",
                    "beneficiary" => true,
                    "percentageOfShares" => $percentage,
                    "representingRange" => [
                        "startDate" => $client->created_at->format('Y-m-d')
                    ]
                ],
                [
                    "idNumber" => $shareholder->idNumber,
                    "idCountryCode" => $shareholder->idCountryCode,
                    "relationTypeCode" => "REPRESENTATIVE",
                    "statusCode" => "ACTIVE",
                    "role" => "SIGNER",
                    "beneficiary" => false,
                    "representingRange" => [
                        "startDate" => $client->created_at->format('Y-m-d')
                    ]
                ]
            ];
        }

        return $payload;
    }

    public function createOrUpdateLegalPerson($client)
    {
        $payload = $this->legalPersonPayload($client, null, false);
        $existing = $this->getExistingLinkedClient($client->id);
        $headers = ['x-request-id' => (string) Str::uuid()];

        if ($existing) {
            $refreshed = $this->refreshLinkedPerson($existing, $payload['addresses'][0] ?? []);
            if (empty($refreshed->recreate)) {
                return $refreshed;
            }
        }

        Log::channel('zenusLogs')->info("------=============== Creating Company Customer for Client: {$client->name} ===============------");
        $person = $this->postPrivatePerson($payload, $headers, $client->name);
        if (!empty($person->error)) {
            return $person;
        }
        $this->storeLinkedClient($client, $person);

        return $person;
    }

    public function createOrUpdateShareholderPerson($client)
    {
        $payload = $this->shareholderPersonPayload($client);
        $existing = $this->getExistingLinkedShareholder($client->id);
        $headers = ['x-request-id' => (string) Str::uuid()];

        if ($existing) {
            $refreshed = $this->refreshLinkedPerson($existing, $payload['addresses'][0] ?? []);
            if (empty($refreshed->recreate)) {
                return $refreshed;
            }
        }

        $person = $this->postPrivatePerson($payload, $headers, $client->name);
        if (!empty($person->error)) {
            return $person;
        }

        $this->addIdentificationNumbers($client, $person->personId);
        $this->storeLinkedClient($client, $person, true);

        return $person;
    }

    public function shareholderPersonPayload($client): array
    {
        $contact = $client->company->contact;
        $nationality = $contact->nationality ?? null;
        $residence = $contact->residence ?? null;
        $client_gender = strtolower($contact->gender ?? 'male') === "male" ? 'M' : 'F';

        return [
            "personTypeCode" => "P",
            "givenName" => $contact->name,
            "middleName" => explode(' ', $contact->name)[0],
            "surname" => explode(' ', $contact->name)[1] ?? "",
            "birthDate" => $contact->dob,
            "countryOfBirth" => $nationality->iso,
            "sex" => $client_gender,
            "nationality" => $residence->iso,
            "pep" => false,
            "identificationNumbers" => [
                [
                    "idNumber" => $contact->passport_number,
                    "idTypeCode" => "PASSPORT",
                    "idCountryCode" => $residence->iso,
                    "issuingDate" => null,
                    "expiryDate" => $contact->passport_expiry_date
                ]
            ],
            "addresses" => [
                [
                    "addressTypeCode" => "R",
                    "countryCode" => $residence->iso,
                    "cityCounty" => $contact->contact_city,
                    "street1" => $contact->street_address,
                    "street2" => $contact->street_2_address,
                    "stateRegion" => $contact->state_region,
                    "zip" => $contact->zip_code
                ]
            ]
        ];
    }

    public function linkCompanyPersonRelation($client, $legalPersonId, $shareholder, string $role)
    {
        $percentage = 55;
        [$idNumber, $idCountryCode] = $this->relationIdentity($shareholder);
        if ($idNumber === '' || $idCountryCode === '') {
            return (object) [
                'error' => true,
                'message' => 'UBO passport number or issuing country is missing, so the link was not sent.',
            ];
        }
        if ($role === 'UBO') {
            $relationship = [
                "idNumber" => $idNumber,
                "idCountryCode" => $idCountryCode,
                "relationTypeCode" => "SHAREHOLDER",
                "statusCode" => "ACTIVE",
                "role" => "UBO",
                "beneficiary" => true,
                "percentageOfShares" => $percentage,
                "representingRange" => [
                    "startDate" => $this->relationStartDate($client)
                ]
            ];
        } else {
            $relationship = [
                "idNumber" => $idNumber,
                "idCountryCode" => $idCountryCode,
                "relationTypeCode" => "REPRESENTATIVE",
                "statusCode" => "ACTIVE",
                "role" => "SIGNER",
                "beneficiary" => false,
                "representingRange" => [
                    "startDate" => $this->relationStartDate($client)
                ]
            ];
        }

        return $this->post('/v1/persons/' . $this->plainText($legalPersonId) . '/relations', [
            "relationships" => [$relationship]
        ]);
    }

    public function relationIdentity($person): array
    {
        $sources = [];
        if (is_object($person)) {
            $sources[] = $person;
            if (isset($person->data)) {
                $sources[] = $person->data;
            }
        } elseif (is_array($person)) {
            $sources[] = (object) $person;
            if (isset($person['data'])) {
                $sources[] = is_array($person['data']) ? (object) $person['data'] : $person['data'];
            }
        }
        foreach ($sources as $source) {
            $item = is_array($source) ? (object) $source : $source;
            if (!is_object($item)) {
                continue;
            }
            $number = $this->plainText($item->idNumber ?? '');
            $country = $this->plainText($item->idCountryCode ?? '');
            $numbers = $item->identificationNumbers ?? [];
            if ($number === '' || $country === '') {
                foreach (is_array($numbers) || is_object($numbers) ? $numbers : [] as $row) {
                    $row = is_array($row) ? (object) $row : $row;
                    if (!is_object($row)) {
                        continue;
                    }
                    if ($number === '') {
                        $number = $this->plainText($row->idNumber ?? '');
                    }
                    if ($country === '') {
                        $country = $this->plainText($row->idCountryCode ?? '');
                    }
                }
            }
            if ($number !== '' && $country !== '') {
                return [$number, $country];
            }
        }

        return ['', ''];
    }

    protected function relationStartDate($client): string
    {
        $created = $client->created_at ?? null;
        if ($created instanceof \DateTimeInterface) {
            return $created->format('Y-m-d');
        }
        $text = $this->plainText($created);

        return strlen($text) >= 10 ? substr($text, 0, 10) : date('Y-m-d');
    }

    public function uploadMappedDocument($client, $customerId, string $docType)
    {
        $file = $this->getDocumentByType($client, $docType);
        if (empty($file) || empty($file->url)) {
            return (object) [
                'error' => true,
                'message' => $docType . ' document is required.',
            ];
        }

        $issuingCountry = $client->type === 'individual'
            ? optional(optional($client->individual)->residence)->iso
            : optional(optional(optional($client->company)->contact)->residence)->iso;

        return $this->uploadZenusDocuments(
            'PERSON.PERSON',
            (string) $customerId,
            $docType,
            $file->url,
            $issuingCountry,
            strtolower($docType) . '-' . $client->id,
            $client->name
        );
    }

    private function createCompanyCustomer($client, $shareholder = null)
    {
        $private_person = $this->createLegalPersonOnly($client, $shareholder);
        if (!empty($private_person->error)) {
            return $private_person;
        }

        // Upload KYC files for Business Customer
        $this->UploadKYCFilesforLegalPerson($client, $private_person->personId);

        // Activate a person
        $this->activatePerson($private_person->personId);

        // Activate a person
        $private_person = $this->findPersonById($private_person->personId)->data;

        //once private_person
        return $private_person;
    }

    public function uploadAndSubmitCustomerProofOfAddress($client, $customerId)
    {
        $files = $this->getRequiredDocuments($client);

        // Validate file existence
        $filePath = $files?->first()?->url ?? null;

        if (empty($filePath)) {
            return (object)[
                'error' => true,
                'message' => 'Proof of Address file not found'
            ];
        }

        try {
            // Resolve issuing country
            $issuingCountry = $client->type === "individual"
                ? $client->individual->residence->iso
                : $client->company->contact->residence->iso;

            // Upload using generic function
            $upload = $this->uploadZenusDocuments(
                ownerType: 'PERSON.PERSON',
                ownerId: $customerId,
                documentType: 'PROOF_OF_ADDRESS',
                filePath: $filePath,
                issuingCountry: $issuingCountry,
                idempotencySeed: 'poa-' . $client->id,
                customerName: $client->name
            );
            return $upload;
        } catch (\Throwable $e) {
            Log::error('Proof of Address upload failed', [
                'client_id'   => $client->id,
                'customer_id' => $customerId,
                'error'       => $e->getMessage(),
            ]);

            return (object)[
                'error' => true,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function uploadAndSubmitIndividualBioMetrics($client, $person)
    {
        $kyc = ClientKYC::where('client_id', $client->id)
            ->where('compliance_status', 'Verified')
            ->whereNull('director_id')
            ->first();

        // Validate selfie
        if (!$kyc || !$kyc->selfie_photo_url) {
            return (object)[
                'error' => true,
                'message' => 'Selfie missing'
            ];
        }

        try {
            return $this->uploadZenusDocuments(
                ownerType: 'PERSON.PERSON',
                ownerId: $person->personId,
                documentType: 'BIOMETRIC_REPORT',
                filePath: $kyc->selfie_photo_url,
                issuingCountry: null,
                idempotencySeed: 'biometric-' . $client->id,
                customerName: $client->name
            );
        } catch (\Throwable $e) {
            Log::error('Biometric upload failed', [
                'client_id' => $client->id,
                'person_id' => $person->personId ?? null,
                'error'     => $e->getMessage(),
            ]);

            return (object)[
                'error' => true,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function uploadAllPrivatePersonDocs($client, $customerId)
    {
        // BIOMETRIC_REPORT: Biometric Report (ID validation report from Sumsub, Onfido, Jumio or similar)
        // PROOF_OF_ADDRESS: Proof of Address
        // FATCA: FATCA – W8BEN or W9 Filing
        // SOURCE_OF_FUNDS: Source of Funds Documentation

        // Required document types for private person
        // $requiredDocs = ["BIOMETRIC_REPORT", "PROOF_OF_ADDRESS", "FATCA", "SOURCE_OF_FUNDS"];
        $requiredDocs = ["BIOMETRIC_REPORT", "PROOF_OF_ADDRESS", "FATCA", "SOURCE_OF_FUNDS"];
        $filesPayload = [];

        foreach ($requiredDocs as $docType) {

            // You MUST implement this method based on your storage
            $file = $this->getDocumentByType($client, $docType);

            if (empty($file) || empty($file->url)) {
                // dd("Missing document: {$docType} for client: {$client->name}");
                return (object)[
                    'error' => true,
                    'message' => "{$docType} docs is required."
                ];
            }

            // Read file and encode to base64
            $fileContent = file_get_contents($file->url);
            $base64File = base64_encode($fileContent);
            // $docType = $docType === 'bank_statement' ? "SOURCE_OF_FUNDS" : strtoupper($docType);

            $filesPayload = [
                "fileName" => "{$docType} - {$client->name}", // Mandatory - Use the following structure "Biometric Report - Customer full name"
                "fileTypeCode" => $docType, // Mandatory - Standard value
                "fileBytes" => (string) $base64File, // Mandatory - Base64: The PDF file needs to be encode to Base64
                "source" => [
                    "sourceName" => "PERSON.PERSON_DOCUMENT", // Mandatory - Standard value
                    "sourceRef" => (string) $file->id // Mandatory - Document ID from original source
                ],
                "contractSource" => [
                    "sourceName" => "PERSON.PERSON", // Mandatory - Standard value
                    "sourceRef" => (string) $customerId // Mandatory - Use personId from Step 1
                ]
            ];

            try {
                $url = $this->api_base_url . '/v1/persons/files';

                log::channel('zenusLogs')->info("Uploading document to Zenus with Payload", [
                    'docType' => $docType,
                    'url' => $url
                ]);

                // Request
                $response = Http::withToken($this->getAccessToken())->post($url, $filesPayload);

                $payload['fileBytes'] = "FILE_BYTES";

                // Logging
                $this->logRequestResponse('POST', $url, [], $payload, $response);

                $this->handleResponse($response);
            } catch (\Exception $e) {
                return (object)[
                    'error' => true,
                    'message' => $e->getMessage()
                ];
            }
        }
    }

    public function UploadKYCFilesforLegalPerson($client, $customerId)
    {
        $requiredDocs = [
            "CERTIF_INCORPORATION_REG",
            "CORPORATE_RECORDS",
            "CORPORATE_RESOLUTION",
            "COMPANY_FISCAL_REG_CERTIF",
            "FATCA",
            "FINANCIAL_STATEMENTS",
            "GOV_COMPANY_REG_NUMBER",
            "PROOF_OF_ADDRESS",
            "SOURCE_OF_FUNDS",
            "ARTICLES_OF_ASSOCIATION",
            "TAX_ID_NUMBER"
        ];

        $filesPayload = [];

        log::channel('zenusLogs')->info("---------------------=========================== Preparing KYC documents for upload ===========================---------------------");

        foreach ($requiredDocs as $docType) {
            $file = $this->getDocumentByType($client, $docType);

            if (empty($file) || empty($file->url)) {
                return (object)[
                    'error' => true,
                    'message' => "{$docType} document is required."
                ];
            }

            log::channel('zenusLogs')->info("Preparing document: {$docType} for upload", [
                'file_url' => $file->url
            ]);

            $fileContent = file_get_contents($file->url);
            $base64File = base64_encode($fileContent);

            $extension = strtolower(pathinfo($file->url, PATHINFO_EXTENSION));

            $allowedTypes = ['pdf', 'jpg', 'jpeg', 'png'];

            if (!in_array($extension, $allowedTypes)) {
                return (object)[
                    'error' => true,
                    'message' => "Invalid file type for {$docType}. Allowed: PDF, JPG, PNG"
                ];
            }

            $fileType = strtoupper($extension === 'jpg' ? 'JPEG' : $extension);

            $externalId = !empty($file->id)
                ? (string) $file->id
                : "{$docType}-{$customerId}";

            $filesPayload[] = [
                "fileName" => "{$docType} - {$client->name}.{$extension}",
                "fileTypeCode" => $docType,
                "fileBytes" => (string) $base64File,
                "fileType" => $fileType,
                "externalId" => $externalId,
            ];
        }

        try {
            $url = $this->api_base_url . "/zv1/persons/{$customerId}/files";

            // Split into chunks of 5
            $chunks = array_chunk($filesPayload, 5);

            $responses = [];

            foreach ($chunks as $index => $chunk) {

                $payload = [
                    "files" => $chunk
                ];

                Log::channel('zenusLogs')->info("Uploading KYC batch #" . ($index + 1), [
                    'file_count' => count($chunk),
                    'url' => $url
                ]);

                $response = Http::withToken($this->getAccessToken())
                    ->withHeaders([
                        'x-tenant-id' => $this->tenantId ?? ''
                    ])
                    ->put($url, $payload);

                $payload['fileBytes'] = "FILE_BYTES";
                    
                // Correct logging
                $this->logRequestResponse('PUT', $url, [], $payload, $response);

                $result = $this->handleResponse($response);

                if (!empty($result->error)) {
                    return $result; // stop on first failure
                }

                $responses[] = $result;
            }

            return (object)[
                'error' => false,
                'message' => 'All KYC files uploaded successfully',
                'responses' => $responses
            ];
        } catch (\Exception $e) {
            return (object)[
                'error' => true,
                'message' => $e->getMessage()
            ];
        }
    }

    public function localDocumentSummary($client, string $docType): array
    {
        $file = $this->getDocumentByType($client, $docType);
        $url = is_object($file) ? ($file->url ?? null) : null;
        $name = null;
        if (!empty($url)) {
            $path = parse_url((string) $url, PHP_URL_PATH);
            $name = basename($path ?: (string) $url);
        }

        return [
            'present' => !empty($url),
            'name' => $name,
        ];
    }

    private function getDocumentByType($client, $docType)
    {
        $docs_map = [
            "BIOMETRIC_REPORT" => "passport_copy",
            "PROOF_OF_ADDRESS" => "certificate_of_business_license",
            "SOURCE_OF_FUNDS" => "bank_statement",
            "FATCA" => "bank_statement", // TODO: Need to add FATCA in file config 

            // TODO: Need to add these in file config and map accordingly
            "CERTIF_INCORPORATION_REG" => "certificate_of_incorporation",
            "CORPORATE_RECORDS" => "memorandum_of_association",
            "CORPORATE_RESOLUTION" => "memorandum_of_association",
            "COMPANY_FISCAL_REG_CERTIF" => "certificate_of_incorporation",
            "FINANCIAL_STATEMENTS" => "bank_statement",
            "GOV_COMPANY_REG_NUMBER" => "certificate_of_incorporation",
            "ARTICLES_OF_ASSOCIATION" => "memorandum_of_association",
            "TAX_ID_NUMBER" => "bank_statement",

            // "CERTIF_INCORPORATION_REG" => "certif_incorporation_reg",
            // "CORPORATE_RECORDS" => "certif_incorporation_reg",
            // "CORPORATE_RESOLUTION" => "certif_incorporation_reg",
            // "COMPANY_FISCAL_REG_CERTIF" => "certif_incorporation_reg",
            // "FINANCIAL_STATEMENTS" => "certif_incorporation_reg",
            // "GOV_COMPANY_REG_NUMBER" => "certif_incorporation_reg",
        ];

        if ($client->type === "individual") {
            $docs_map['PROOF_OF_ADDRESS'] = 'bank_statement';
            $files = $client->individual->files;
        } else if ($client->type === "company") {
            $files = $client->company->files;
        } else {
            return null;
        }

        if ($docs_map[$docType] === "passport_copy") {
            $passport_copy_url = Common::getClientPassportUrl($client->id, $client->type);

            if (!empty($passport_copy_url)) {
                return (object)[
                    'id' => 'passport_copy_url',
                    'url' => $passport_copy_url ?? null
                ];
            } else {
                return null;
            }
        }

        foreach ($files as $file) {
            if ($file->clientFileConfig->identifier === $docs_map[$docType]) {
                return $file;
            }
        }
    }

    public function storeLinkedClient($client, $customer, $is_uob_client = false)
    {
        return VirtualBankLinkedClient::create([
            'linked_client_id' => $customer->personId,
            'client_id'        => $client->id,
            'status'           => $customer->statusCode,
            'kyc_status'       => $customer->statusCode,
            'country'          => $customer->nationality ?? null,
            'is_uob_client'    => $is_uob_client,
            'provider_name'    => 'zenus',
            'response_data'    => json_encode($customer),
        ]);
    }

    public function clientHasRequiredDocument($client, string $identifier): bool
    {
        $type = !empty($client->is_bank) ? 'bank' : $client->type;
        $needles = [
            'certificate_of_business_license' => ['business license', 'business licence', 'trade license', 'trade licence'],
            'certificate_of_incorporation' => ['certificate of incorporation', 'incorporation'],
            'memorandum_of_association' => ['memorandum', 'articles of association'],
            'bank_statement' => ['bank statement'],
            'utility_bill' => ['utility bill', 'utility'],
        ];
        $configs = ClientFileConfig::where('type', $type)->get();
        $ids = [];
        foreach ($configs as $conf) {
            $matches = (string) $conf->identifier === $identifier;
            if (!$matches) {
                $title = strtolower((string) $conf->title);
                foreach ($needles[$identifier] ?? [] as $needle) {
                    if ($title !== '' && strpos($title, $needle) !== false) {
                        $matches = true;
                        break;
                    }
                }
            }
            if (!$matches) {
                continue;
            }
            $ids[] = $conf->id;
            if (trim((string) $conf->identifier) === '') {
                $conf->identifier = $identifier;
                $conf->save();
            }
        }
        if (empty($ids)) {
            $fallback = ClientFileConfig::where('identifier', $identifier)->pluck('id')->all();
            $ids = array_values($fallback);
        }
        if (empty($ids)) {
            return false;
        }

        $query = $client->type === 'company'
            ? ClientCompanyFile::where('client_id', $client->id)
            : ClientIndividualFile::where('client_id', $client->id);

        return $query->whereIn('file_id', $ids)
            ->where(function ($q) {
                $q->where('is_deleted', 0)->orWhereNull('is_deleted');
            })
            ->exists();
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

    public function getAllowedCurrencies($flag = false)
    {
        if ($flag && !empty($this->allowed_currencies)) {
            $currencies = array_map('trim', explode(',', strtoupper($this->allowed_currencies)));
            return $currencies;
        } else {
            return trim($this->allowed_currencies);
        }
    }

    public function findPersonById($customer_id)
    {
        try {
            $response = $this->get("/v3/persons/{$customer_id}");

            return $response;
        } catch (\Throwable $e) {
            Log::error('Zenus person not found', [
                'ownerId' => $customer_id,
                'error' => $e->getMessage(),
            ]);

            return (object)[
                'error' => true,
                'message' => $e->getMessage(),
            ];
        }
    }

    /* =====================================================
     | DOCUMENTS
     ===================================================== */

    public function uploadZenusDocuments(
        string $ownerType,
        string $ownerId,
        string $documentType,
        string $filePath,
        ?string $issuingCountry = null,
        ?string $idempotencySeed = null,
        ?string $customerName = 'Customer'
    ) {
        try {
            // Headers
            $headers = [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ];

            // Read file
            $fileContent = @file_get_contents($filePath);
            if ($fileContent === false) {
                throw new \Exception('Unable to read file');
            }

            // Base64 encode (required)
            $base64File = base64_encode($fileContent);

            // File name format (as per Zenus requirement)
            $fileName = $this->formatZenusFileName($documentType, $customerName);

            // Payload (aligned with Zenus API)
            $payload = [
                "fileName"     => $fileName,
                "fileTypeCode" => strtoupper($documentType),
                "fileBytes"    => $base64File,
                "source" => [
                    "sourceName" => "PERSON.PERSON_DOCUMENT",
                    "sourceRef"  => "CLIENT-{$ownerId}",
                ],
                "contractSource" => [
                    "sourceName" => $ownerType,
                    "sourceRef"  => $ownerId,
                ],
            ];

            // Optional
            if ($issuingCountry) {
                $payload['issuingCountry'] = $issuingCountry;
            }

            $url = $this->api_base_url . '/v1/persons/files';

            // Request
            $response = Http::withToken($this->getAccessToken())->post($url, $payload);
            
            $payload['fileBytes'] = "FILE_BYTES";

            // Logging
            $this->logRequestResponse('POST', $url, $headers, $payload, $response);

            return $this->handleResponse($response);
        } catch (\Throwable $e) {
            Log::error('Zenus document upload failed', [
                'ownerId' => $ownerId,
                'documentType' => $documentType,
                'error' => $e->getMessage(),
            ]);

            return (object)[
                'error' => true,
                'message' => $e->getMessage(),
            ];
        }
    }

    protected function formatZenusFileName(string $documentType, string $customerName): string
    {
        return match ($documentType) {
            'BIOMETRIC_REPORT'   => "Biometric Report - {$customerName}",
            'PROOF_OF_ADDRESS'   => "Proof of Address - {$customerName}",
            'SOURCE_OF_FUNDS'    => "Source Of Fund - {$customerName}",
            'PASSPORT'           => "Passport - {$customerName}",
            default              => "{$documentType} - {$customerName}",
        };
    }

    protected function logRequestResponse(string $method, string $url, array $headers = [], $payload = null, $response = null, $exception = null)
    {
        // Mask sensitive headers
        $safeHeaders = collect($headers)->map(function ($value, $key) {
            return in_array(strtolower($key), ['authorization', 'x-api-key']) ? '***' : $value;
        })->toArray();

        Log::channel('zenusLogs')->info("\n\n ----------===================------------\n");

        // Log request first
        Log::channel('zenusLogs')->info('API Request', [
            'method'  => $method,
            'url'     => $url,
            'headers' => $safeHeaders,
            'payload' => $payload,
        ]);

        if ($response) {
            Log::channel('zenusLogs')->info('API Response', [
                'method' => $method,
                'url'    => $url,
                'status' => $response->status(),
                'body'   => $response->json() ?? $response->body(),
            ]);

            if (!$response->successful()) {
                Log::channel('zenusLogs')->warning('API Request failed', [
                    'method' => $method,
                    'url'    => $url,
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
            }
        }

        if ($exception) {
            Log::channel('zenusLogs')->error('API Exception', [
                'method' => $method,
                'url'    => $url,
                'error'  => $exception->getMessage(),
            ]);
        }

        Log::channel('zenusLogs')->info("\n ----------===================------------\n\n");
    }

    public function getPaymentDetailsById($paymentId)
    {
        try {
            $response = $this->get("/v2/payments/{$paymentId}");
            return $response->data;
        } catch (\Throwable $e) {
            Log::error('Zenus payment retrieval failed', [
                'paymentId' => $paymentId,
                'error' => $e->getMessage(),
            ]);

            return (object)[
                'error' => true,
                'message' => $e->getMessage(),
            ];
        }
    }


    /* =====================================================
     | Zenus Transactions
     ===================================================== */

    public function initiatTransaction($payload, $idempotency_key = null)
    {
        // $headers = ['x-request-id' => (string) Uuid::uuid5(Uuid::NAMESPACE_DNS, json_encode($payload))->toString()];
        $headers = ['x-request-id' => $idempotency_key ?? (string) Str::uuid()];
        $response = $this->post('/v3/payments/initialise', $payload, $headers);

        if (!empty($response->error)) {
            Log::error('Zenus transaction initiation failed', [
                'payload' => $payload,
                'error' => $response->message ?? 'Unknown error',
            ]);

            return (object)[
                'error' => true,
                'message' => $response->message ?? 'Transaction initiation failed',
            ];
        }

        return json_decode(json_encode($response['data']));
    }

    public function confirmPayment($payment_id, $idempotency_key = null)
    {
        $payload = [
            "comment" => "Payment confirmation for Payment ID: {$payment_id}",
        ];

        $headers = ['x-request-id' => $idempotency_key ?? (string) Str::uuid()];
        $response = $this->post("/v3/payments/{$payment_id}/confirm", $payload, $headers);

        if (!empty($response->error) || empty($response["data"])) {
            Log::error('Zenus transaction confirmation failed', [
                'payload' => $payload,
                'error' => $response->message ?? 'Transaction confirmation failed',
            ]);

            return (object)[
                'error' => true,
                'message' => $response->message ?? 'Transaction confirmation failed',
            ];
        }

        return json_decode(json_encode($response["data"]));
    }

    public function getPrimaryAccountByCurrency($currency, $method)
    {
        $accountId = null;

        switch (strtoupper($currency)) {
            case 'USD':
                $accountId = $this->master_account_id;
                break;
            default:
                Log::channel('zenusLogs')->info('Zenus Primary Account not Found!');
                return null;
        }

        $zenus_master_account = $this->getMasterAccountOfChildAccount($accountId);
        
        if (!$zenus_master_account || empty($zenus_master_account->accountNumbers)) {
            Log::channel('zenusLogs')->info('Zenus Master Account or Account Numbers not found');
            return null;
        }

        // Extract values
        $bban = null;
        $aba  = null;
        $bic  = null;

        foreach ($zenus_master_account->accountNumbers as $acc) {

            if (($acc->accountNumber->type ?? null) === 'BBAN') {
                $bban = $acc->accountNumber->value;
            }

            if (($acc->financialInstitutionId->type ?? null) === 'ABA_CODE') {
                $aba = $acc->financialInstitutionId->value;
            }

            if (($acc->financialInstitutionId->type ?? null) === 'BIC') {
                $bic = $acc->financialInstitutionId->value;
            }
        }

        switch (strtoupper($method)) {

            case 'ACH':
                if (!$bban || !$aba) {
                    Log::channel('zenusLogs')->info('ACH details missing', compact('bban', 'aba'));
                    return null;
                }

                return [
                    'accountId' => $zenus_master_account->accountId,
                    'accountNumber' => ['value' => $bban, 'type' => 'BBAN'],
                    'routingNumber' => ['value' => $aba, 'type'  => 'ABA'],
                    'currency' => $zenus_master_account->defaultCurrencyCode,
                    'accountCountryCode' => $zenus_master_account->jurisdictionCountryCode,
                    'accountName' => $zenus_master_account->accountName,
                    'accountType' => $zenus_master_account->accountTypeCode ?? null,
                    'accountBalance' => $zenus_master_account->balances[0] ?? null,
                ];

            case 'CROSS_BORDER_SWIFT':
                if (!$bban || !$bic) {
                    Log::channel('zenusLogs')->info('SWIFT details missing', compact('bban', 'bic'));
                    return null;
                }

                return [
                    'accountId' => $zenus_master_account->accountId,
                    'accountNumber' => ['value' => $bban, 'type' => 'BBAN'],
                    'bic' => ['value' => $bic, 'type'  => 'BIC'],
                    // 'bic' => ['value' => $aba, 'type'  => 'ABA_CODE'],
                    'currency' => $zenus_master_account->defaultCurrencyCode,
                    'accountCountryCode' => $zenus_master_account->jurisdictionCountryCode,
                    'accountName' => $zenus_master_account->accountName,
                    'accountType' => $zenus_master_account->accountTypeCode ?? null,
                    'accountBalance' => $zenus_master_account->balances[0] ?? null,
                ];

            case 'FED_WIRE':
                if (!$bban || !$aba) {
                    Log::channel('zenusLogs')->info('FED_WIRE details missing', compact('bban', 'aba'));
                    return null;
                }

                return [
                    'accountId' => $zenus_master_account->accountId,
                    'accountNumber' => ['value' => $bban, 'type'  => 'BBAN'],
                    'routingNumber' => ['value' => $aba, 'type'  => 'ABA'],
                    'currency' => $zenus_master_account->defaultCurrencyCode,
                    'accountCountryCode' => $zenus_master_account->jurisdictionCountryCode,
                    'accountName' => $zenus_master_account->accountName,
                    'accountType' => $zenus_master_account->accountTypeCode ?? null,
                    'accountBalance' => $zenus_master_account->balances[0] ?? null,
                ];

            default:
                Log::channel('zenusLogs')->info('Unsupported payment method: ' . $method);
                return null;
        }
    }
    public function getClientZenusBankAccount($transaction, $method)
    {
        $bankAccount = $transaction->account->bankAccount ?? null;
        
        if (!$bankAccount || empty($bankAccount->account_info)) {
            Log::channel('zenusLogs')->info('Zenus bank account not found');
            return null;
        }

        $accountInfo = is_string($bankAccount->account_info)
            ? json_decode($bankAccount->account_info)
            : $bankAccount->account_info;
        
        if (!$accountInfo || !is_array($accountInfo)) {
            Log::channel('zenusLogs')->info('Invalid account info format');
            return null;
        }

        // ── Extract values safely ──
        $bban = null;
        $aba  = null;
        $bic  = null;

        foreach ($accountInfo as $acc) {

            $accountNumber = $acc->accountNumber->value ?? null;
            $fiType        = $acc->financialInstitutionId->type ?? null;
            $fiValue       = $acc->financialInstitutionId->value ?? null;

            if (($acc->accountNumber->type ?? null) === 'BBAN') {
                $bban = $accountNumber;
            }

            if ($fiType === 'ABA_CODE') {
                $aba = $fiValue;
                $aba_account_currency = $acc->countryCode;
            }

            if ($fiType === 'BIC') {
                $bic = $fiValue;
                $bic_account_currency = $acc->countryCode;
            }
        }

        // ── Validate method ──
        switch (strtoupper($method)) {

            case 'ACH':
                if (!$bban || !$aba) {
                    Log::channel('zenusLogs')->info('ACH details missing', compact('bban', 'aba'));
                    return null;
                }

                return [
                    'accountNumber' => ['value' => $bban, 'type' => 'BBAN'],
                    'routingNumber' => ['value' => $aba, 'type'  => 'ABA'],
                    'currency' => $acc->defaultCurrencyCode,
                    // 'accountCountryCode' => $acc->countryCode,
                    'accountCountryCode' => $aba_account_currency,
                    'accountName' => $bankAccount->bank_account_holder_name,
                ];

            case 'CROSS_BORDER_SWIFT':
                if (!$bban || !$bic) {
                    Log::channel('zenusLogs')->info('SWIFT details missing', compact('bban', 'bic'));
                    return null;
                }

                return [
                    'accountNumber' => ['value' => $bban, 'type' => 'BBAN'],
                    'bic' => ['value' => $bic, 'type'  => 'BIC'],
                    'currency' => $acc->defaultCurrencyCode,
                    'accountCountryCode' => $bic_account_currency,
                    'accountName' => $bankAccount->bank_account_holder_name,
                ];

            case 'FED_WIRE':
                if (!$bban || !$aba) {
                    Log::channel('zenusLogs')->info('FED_WIRE details missing', compact('bban', 'aba'));
                    return null;
                }
                
                return [
                    'accountNumber' => ['value' => $bban, 'type'  => 'BBAN'],
                    'routingNumber' => ['value' => $aba, 'type'  => 'ABA'],
                    'currency' => $acc->defaultCurrencyCode,
                    'accountCountryCode' => $aba_account_currency,
                    'accountName' => $bankAccount->bank_account_holder_name,
                ];

            default:
                Log::channel('zenusLogs')->info('Unsupported payment method: ' . $method);
                return null;
        }
    }

    public function getMasterAccountOfChildAccount($accountId)
    {
        try {

            $response = $this->get("/v4/accounts/{$accountId}");

            if (empty($response->error) && empty($response->data)) {
                return (object)[
                    'error' => true,
                    'message' => $response->message ?? 'Unknown error',
                ];
            }

            return $response->data;
        } catch (\Throwable $e) {
            Log::error('Zenus account retrieval failed', [
                'accountId' => $accountId,
                'error' => $e->getMessage(),
            ]);

            return (object)[
                'error' => true,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function getAccountOfById($accountId)
    {
        try {

            $response = $this->get("/v4/accounts/{$accountId}");

            if (empty($response->error) && empty($response->data)) {
                return (object)[
                    'error' => true,
                    'message' => $response->message ?? 'Unknown error',
                ];
            }

            return $response->data;
        } catch (\Throwable $e) {
            Log::error('Zenus account retrieval failed', [
                'accountId' => $accountId,
                'error' => $e->getMessage(),
            ]);

            return (object)[
                'error' => true,
                'message' => $e->getMessage(),
            ];
        }
    }

    protected function refreshLinkedPerson($linked, array $address)
    {
        try {
            return $this->refreshLinkedPersonNow($linked, $address);
        } catch (\Throwable $e) {
            Log::channel('zenusLogs')->warning('Zenus person update skipped', [
                'person_id' => $linked->linked_client_id ?? null,
                'error' => $e->getMessage(),
            ]);

            return (object) [
                'personId' => $linked->linked_client_id,
                'kept' => true,
                'summary' => 'Existing Zenus person ' . $linked->linked_client_id . ' was kept. Update was skipped.',
            ];
        }
    }

    protected function refreshLinkedPersonNow($linked, array $address)
    {
        $found = $this->findPersonById($linked->linked_client_id);
        if (!$this->personWasFound($found)) {
            $linked->delete();

            return (object) ['recreate' => true];
        }

        $addressNote = 'Existing details were kept.';
        try {
            $updated = $this->syncPersonAddress($linked->linked_client_id, $address);
            if ($updated) {
                $addressNote = 'Address updated.';
            }
        } catch (\Throwable $e) {
            Log::channel('zenusLogs')->warning('Zenus person address update skipped', [
                'person_id' => $linked->linked_client_id,
                'error' => $e->getMessage(),
            ]);
        }

        return (object) [
            'personId' => $linked->linked_client_id,
            'updated' => true,
            'kept' => true,
            'summary' => $this->personSummary($found, $linked->linked_client_id) . ' ' . $addressNote,
        ];
    }

    protected function personWasFound($found): bool
    {
        if (is_object($found) && !empty($found->error)) {
            return false;
        }
        $data = is_array($found) ? ($found['data'] ?? $found) : (is_object($found) ? ($found->data ?? $found) : null);
        if (is_object($data)) {
            return !empty($data->personId);
        }
        if (is_array($data)) {
            return !empty($data['personId']);
        }

        return false;
    }

    protected function syncPersonAddress(string $personId, array $address)
    {
        if (empty($address)) {
            return null;
        }
        $addressId = $this->firstAddressId($this->findPersonAddress($personId));
        if (!$addressId) {
            return null;
        }
        $payload = array_filter($address, function ($value) {
            return $value !== null && $value !== '';
        });
        $updated = $this->updatePersonAddress($personId, $addressId, $payload);
        if (is_object($updated) && !empty($updated->error)) {
            Log::channel('zenusLogs')->info('Zenus address update skipped', [
                'person_id' => $personId,
                'message' => $this->plainText($updated->message ?? ''),
            ]);

            return false;
        }

        return true;
    }

    protected function personSummary($found, string $personId): string
    {
        $data = is_array($found) ? ($found['data'] ?? $found) : (is_object($found) ? ($found->data ?? $found) : []);
        $item = is_object($data) ? (array) $data : (is_array($data) ? $data : []);
        $name = trim(implode(' ', array_filter([
            $this->plainText($item['givenName'] ?? ''),
            $this->plainText($item['surname'] ?? ''),
            $this->plainText($item['name'] ?? ''),
        ])));
        $status = $this->plainText($item['statusCode'] ?? '');
        $bits = ['Existing Zenus person ' . $personId];
        if ($name !== '') {
            $bits[] = $name;
        }
        if ($status !== '') {
            $bits[] = 'status ' . $status;
        }

        return implode(', ', $bits) . '.';
    }

    protected function plainText($value): string
    {
        if (is_array($value)) {
            $parts = [];
            foreach ($value as $item) {
                $text = $this->plainText($item);
                if ($text !== '') {
                    $parts[] = $text;
                }
            }

            return implode(', ', $parts);
        }
        if (is_object($value)) {
            return $this->plainText((array) $value);
        }
        if ($value === null || is_bool($value)) {
            return '';
        }

        return trim((string) $value);
    }

    protected function firstAddressId($response): ?string
    {
        $rows = is_array($response) ? ($response['data'] ?? $response) : (is_object($response) ? ($response->data ?? []) : []);
        if (is_object($rows)) {
            $rows = [$rows];
        }
        if (!is_array($rows)) {
            return null;
        }
        foreach ($rows as $row) {
            $item = is_object($row) ? (array) $row : $row;
            if (!is_array($item)) {
                continue;
            }
            foreach (['addressId', 'id', 'personAddressId'] as $key) {
                $value = $item[$key] ?? null;
                if (is_scalar($value) && trim((string) $value) !== '') {
                    return (string) $value;
                }
            }
        }

        return null;
    }

    protected function isMissingResource($response): bool
    {
        $status = is_object($response) ? (int) ($response->status_code ?? 0) : 0;
        if ($status === 404) {
            return true;
        }
        $message = strtolower($this->plainText(is_object($response) ? ($response->message ?? '') : ''));

        return strpos($message, 'resource not found') !== false
            || strpos($message, 'not found') !== false
            || in_array('err.notFound', $this->zenusErrorCodes($response), true);
    }

    public function findPersonAddress(string $person_id)
    {
        return $this->get("/v1/persons/{$person_id}/addresses");
    }
    
    public function updatePersonAddress(string $person_id, string $address_id, $payload)
    {
        return $this->put("/v2/persons/{$person_id}/addresses/{$address_id}", $payload);
    }
}
