<?php

namespace App\Helpers;

use App\Helpers\WorldCheckAuthHeadersBuilder;
use App\Models\Country;
use App\Models\Module;
use App\Models\ModuleConfiguration;
use App\Models\WorldCheckApiLog;
use App\Services\WorldCheckConfigService;
use Exception;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class WorldCheckHelper
{
    private $apiUrl;
    private $gateWayUrl;
    private $apiKey;
    private $secret;
    private $hostHeader;
    private $groupId;

    /** @var array{client_id?:int|null,client_type?:string|null,client_name?:string|null,entity_label?:string|null} */
    private static array $logContext = [];

    /**
     * Set optional client/entity context attached to subsequent API log rows.
     *
     * @param  array{client_id?:int|null,client_type?:string|null,client_name?:string|null,entity_label?:string|null}  $context
     */
    public static function setLogContext(array $context): void
    {
        self::$logContext = array_merge(self::$logContext, $context);
    }

    public static function clearLogContext(): void
    {
        self::$logContext = [];
    }

    public function __construct()
    {
        // Initialize your API credentials here
        $module = Module::where('name', 'Compliance')->select('id')->first();
        $moduleConfig = ModuleConfiguration::where('module_id', $module->id)->first();

        if ($moduleConfig) {
            $complianceConfig = unserialize($moduleConfig->configurations);
            $this->apiUrl =  $complianceConfig['apiUrl_worldcheck']['value'];
            $this->gateWayUrl = $complianceConfig['gateWayUrl_worldcheck']['value'];
            $this->apiKey = $complianceConfig['apiKey_worldcheck']['value'];
            $this->secret = $complianceConfig['secret_worldcheck']['value'];
            $this->hostHeader = $complianceConfig['hostHeader_worldcheck']['value'];
            $this->groupId = $complianceConfig['groupId_worldcheck']['value'];
        } else {
            throw new Exception('No configurations found. Please feed the compliance Configurations.');
        }
    }

    public  function screenPassport($sanctionFields)
    {
        WorldCheckConfigService::assertCanScreen();

        $first_name = $sanctionFields['first-name'];
        $last_name =  $sanctionFields['last-name'];
        $entitytype = $sanctionFields['entity_type'];
        $entitytype = strtoupper($entitytype);

        $content = [
            'givenName' => $first_name,
            'lastName' => $last_name,
            'gender' => $sanctionFields['gender'],
            'dateOfBirth' => $sanctionFields['dob'],
            'issuingState' => $sanctionFields['passport_issuing_state'],
            'nationality' =>  $sanctionFields['nationality_iso3'],
            'identificationNumber' => $sanctionFields['passport_number'],
            'dateOfExpiry' => $sanctionFields['passport_expiry_date'],
            'documentType' => 'PASSPORT'
        ];

        $endPoint = "passportcheck/mrz";
        $payLoad = $this->generatePayload($content, $endPoint);
        $response = $this->makeApiCall($payLoad, true);

        return  $response;
    }

    public  function screenEntity($sanctionFields)
    {
        WorldCheckConfigService::assertCanScreen();

        if ($sanctionFields['entity_type'] != 'individual') {
            $sanctionFields['entity_type'] = 'ORGANISATION';
        }

        $caseId = "";
        $name = $sanctionFields['name'];
        $screen_mode = WorldCheckConfigService::screeningMode();
        $entitytype = $sanctionFields['entity_type'];
        $providertype = "WATCHLIST";
        $entitytype = strtoupper($entitytype);
        $secondaryFields = $this->generateSecondaryFields($sanctionFields);
        //Log::info("for entity sanctions Fields" . json_encode($secondaryFields));
        $content = [
            "groupId" => $this->groupId,
            "entityType" => $entitytype,
            "caseId" => $caseId,
            "providerTypes" => [$providertype],
            "caseScreeningState" => [
                "WATCHLIST" => $screen_mode
            ],
            "name" => $name,
            "nameTransposition" => false,
            "secondaryFields" => $secondaryFields,
            "customFields" => []
        ];

        $endPoint = "cases/screeningRequest";
        $payLoad = $this->generatePayload($content, $endPoint);
        $response = $this->makeApiCall($payLoad, true);

        return  $response;
    }

    private function generatePayload($content, $endPoint)
    {
        $payload = [
            "endpoint" => $endPoint,
            "method" => "POST",
            "content" => json_encode($content)
        ];

        return $payload;
    }

    private function makeApiCall($payload, bool $countsAsScreen = false)
    {
        WorldCheckConfigService::assertCanCallApi();

        $authHeaderBuilder = new WorldCheckAuthHeadersBuilder();
        $url = $this->apiUrl . $payload['endpoint'];
        $path = $this->gateWayUrl . $payload['endpoint'];
        $contentType = 'application/json; charset=utf-8';
        $dateHeader = $authHeaderBuilder->generateDateHeader();
        $authorisation =  $authHeaderBuilder->generateAuthHeader($this->apiKey, $this->secret, $this->hostHeader, $dateHeader, $payload['method'], $path, $contentType, $payload['content']);

        $started = microtime(true);
        $response = Http::withHeaders([
            "Authorization" => $authorisation,
            "Date" => $dateHeader,
            "Content-Type" => $contentType,
            "Content-Length" => strlen($payload['content'])
        ])->post($url, json_decode($payload['content'], true));
        $durationMs = (int) round((microtime(true) - $started) * 1000);

        $result = json_decode($response->getBody());

        $request_headers = [
            "Authorization" => $authorisation,
            "Date" => $dateHeader,
            "Content-Type" => $contentType,
            "Content-Length" => strlen($payload['content'])
        ];

        $this->logApiRequestInfo($request_headers, $response, $url, $payload, $durationMs);

        if (!$result) {
            throw new Exception('Something went wrong, please try again later.');
        } elseif ($response->status() == '200') {
            if ($countsAsScreen) {
                WorldCheckConfigService::recordScreen();
            }

            return $result;
        } elseif ($response->status() == '400') {
            Log::channel('worldchecklog')->error('API Error', [
                'message' => $result[0]->cause,
                'response_status' => 400
            ]);
            throw new Exception($result[0]->cause);
        } elseif ($response->status() == '401') {
            throw new Exception("Unauthorized");
        } else {
            throw new Exception('Something went wrong, please try again later.');
        }
    }

    private function generateSecondaryFields($sanctionFields)
    {
        $secondaryFields = [];

        if ($sanctionFields['entity_type'] == 'individual') {

            $secondaryFields = [
                [
                    'typeId' => 'SFCT_1',
                    'value' => $sanctionFields['gender']
                ],
                [
                    'typeId' => 'SFCT_2',
                    'dateTimeValue' => $sanctionFields['dob']
                ],
                [
                    'typeId' => 'SFCT_3',
                    'value' => $sanctionFields['country_residence_iso3']
                ],
                [
                    'typeId' => 'SFCT_4',
                    'value' => $sanctionFields['citizenship_iso3']
                ],
                [
                    'typeId' => 'SFCT_5',
                    'value' => $sanctionFields['nationality_iso3']
                ]
            ];
        } else {

            $secondaryFields = [
                [
                    'typeId' => 'SFCT_6',
                    'value' =>  $sanctionFields['nationality_iso3']
                ]
            ];
        }

        // Filter out entries with null 'value' or if 'value' key doesn't exist
        $filteredSecondaryFields = array_filter($secondaryFields, function ($field) {
            return array_key_exists('value', $field) && $field['value'] !== null;
        });

        // Reset keys if needed
        $filteredSecondaryFields = array_values($filteredSecondaryFields);

        return $filteredSecondaryFields;
    }

    public  function getReportID($caseId)
    {
        WorldCheckConfigService::assertCanCallApi('World-Check report');

        $content = [
            'query' =>  "caseId==$caseId",
            'filter' => "",
            'reportType' => "CASE_DOSSIER",
            'sort' => [
                [
                    'columnName' => 'caseId',
                    'order' => "ASCENDING"
                ]
            ],
            'reportName' => "",
            'reportFilter' => "includeAudit==true;includeMediaCheckFullArticles==true;includePassportCheck==true",
            'customAttributes' => "note==data"
        ];

        $endPoint = "reports";
        $payLoad = $this->generatePayload($content, $endPoint);
        $response = $this->makeApiCall($payLoad);

        return  $response;
    }

    public  function getReportStatus($reportId)
    {
        WorldCheckConfigService::assertCanCallApi('World-Check report status');

        $payLoad = [
            "endpoint" => "reports/$reportId/status",
            "method" => "GET"
        ];

        return  $this->makeApiCallGet($payLoad);
    }

    public  function downloadReport($reportId, $client, $case_id = null)
    {
        WorldCheckConfigService::assertCanCallApi('World-Check report download');

        $payLoad = [
            "endpoint" => "reports/$reportId",
            "method" => "GET"
        ];

        return  $this->makeApiCallDownloadReport($payLoad, $client, $case_id);
    }

    private function makeApiCallGet($payload, int $attempt = 0)
    {
        WorldCheckConfigService::assertCanCallApi();

        $authHeaderBuilder = new WorldCheckAuthHeadersBuilder();
        $url = $this->apiUrl . $payload['endpoint'];
        $path = $this->gateWayUrl . $payload['endpoint'];
        $contentType = 'application/json';
        $dateHeader = $authHeaderBuilder->generateDateHeader();
        $authorisation =  $authHeaderBuilder->generateAuthHeader($this->apiKey, $this->secret, $this->hostHeader, $dateHeader, $payload['method'], $path, $contentType, null);

        $started = microtime(true);
        $response = Http::withHeaders([
            "Authorization" => $authorisation,
            "Date" => $dateHeader
        ])->get($url);
        $durationMs = (int) round((microtime(true) - $started) * 1000);

        $result = json_decode($response->getBody());

        $request_headers = [
            "Date" => $dateHeader,
            "Authorization" => $authorisation,
            "Content-Type" => $contentType,
        ];

        $this->logApiRequestInfo($request_headers, $response, $url, $payload, $durationMs, $attempt);

        Log::channel('worldchecklog')->info("Response Body: " . print_r($result, true) . "\n" .
            "-----------------------------------------------------------------\n\n");

        if (!$result) {
            throw new Exception('Something went wrong, please try again later.');
        } elseif ($response->status() == '200') {
            return $result;
        } elseif ($response->status() == '404' || $response->status() == '400') {
            $maxRetries = WorldCheckConfigService::reportStatusMaxRetries();

            if ($attempt < $maxRetries) {
                sleep(1);
                Log::channel('worldchecklog')->info('Retry: ' . ($attempt + 1) . '/' . $maxRetries . ' | Helper :: ' . __LINE__);

                return $this->makeApiCallGet($payload, $attempt + 1);
            }

            throw new Exception("Report Not Found");
        } elseif ($response->status() == '401') {
            throw new Exception("Unauthorized");
        } else {
            throw new Exception('Something went wrong, please try again later.');
        }
    }

    private function makeApiCallDownloadReport($payload, $client, $case_id = null)
    {
        WorldCheckConfigService::assertCanCallApi();

        $authHeaderBuilder = new WorldCheckAuthHeadersBuilder();
        $url = $this->apiUrl . $payload['endpoint'];
        $path = $this->gateWayUrl . $payload['endpoint'];
        $contentType = 'application/json';
        $dateHeader = $authHeaderBuilder->generateDateHeader();
        $authorisation =  $authHeaderBuilder->generateAuthHeader($this->apiKey, $this->secret, $this->hostHeader, $dateHeader, $payload['method'], $path, $contentType, null);

        try {
            $started = microtime(true);
            $response = Http::withHeaders([
                "Authorization" => $authorisation,
                "Date" => $dateHeader
            ])->get($url);
            $durationMs = (int) round((microtime(true) - $started) * 1000);

            $request_headers = [
                "Date" => $dateHeader,
                "Authorization" => $authorisation,
                "Content-Type" => $contentType,
            ];

            $this->logApiRequestInfo(
                $request_headers,
                $response,
                $url,
                $payload,
                $durationMs,
                0,
                true
            );

            // Check if the request was successful
            if ($response->successful()) {

                // Create a temporary file to store the downloaded zip content
                $tempZipFile = tempnam(sys_get_temp_dir(), 'downloaded_file');
                file_put_contents($tempZipFile, $response->body());

                // Use ZipArchive to check the contents of the zip file
                $zip = new ZipArchive;
                if ($zip->open($tempZipFile) === TRUE) {

                    // Extract the single PDF file
                    $pdfContent = $zip->getFromIndex(0);
                    // Save the PDF file to S3
                    if ($client) {
                        $fileURL = S3Helper::fileUpload(null, 'client-kyc-files/' . $client->type . '/' . $client->id, $pdfContent);
                    } else {
                        $fileURL = S3Helper::fileUpload(null, 'screening-case-files/' . $case_id, $pdfContent);
                    }

                    // Close the zip file
                    $zip->close();

                    // Remove the temporary zip file
                    unlink($tempZipFile);

                    return $fileURL;
                } else {
                    return response('Error: ZIP file extracting error.', 500);
                }
            } else {
                // Handle the case where the request failed
                throw new Exception('Error fetching file from the API.', $response->status());
            }
        } catch (Exception $e) {
            throw new Exception('Something went wrong, please try again later.');
        }
    }

    public  function screenIndividualWithPassport($sanctionFields)
    {
        WorldCheckConfigService::assertCanScreen();

        $secondaryFields = $this->generateSecondaryFieldsIndividualWithPassport($sanctionFields);
        Log::info("sanctions Fields" . json_encode($secondaryFields));
        $screen_mode = WorldCheckConfigService::screeningMode();
        $content = [
            "groupId" => $this->groupId,
            "entityType" => "INDIVIDUAL",
            "providerTypes" => [
                "WATCHLIST",
                "PASSPORT_CHECK"
            ],
            "caseScreeningState" => [
                "WATCHLIST" => $screen_mode
            ],
            "name" => $sanctionFields['name'],
            "secondaryFields" => $secondaryFields
        ];

        $endPoint = "cases/screeningRequest";
        $payLoad = $this->generatePayload($content, $endPoint);
        $response = $this->makeApiCall($payLoad, true);

        return  $response;
    }

    private function generateSecondaryFieldsIndividualWithPassport($sanctionFields)
    {
        $secondaryFields = [];
        $nameParts = explode(' ', $sanctionFields['name']);
        $firstName = $nameParts[0];
        $lastName =  implode(' ', array_slice($nameParts, 1));

        $iso3Codes = [
            $sanctionFields['nationality_iso3'],
            $sanctionFields['citizenship_iso3']
        ];

        $countries = Country::select('passport_iso3', 'iso3')->whereIn('iso3', $iso3Codes)
            ->pluck('passport_iso3', 'iso3');

        $nationality = $countries[$sanctionFields['nationality_iso3']];
        $citizenship = $countries[$sanctionFields['citizenship_iso3']];

        $secondaryFields = [
            [
                'typeId' => 'SFCT_5',
                'value' => $sanctionFields['nationality_iso3']
            ],
            [
                'typeId' => 'SFCT_8',
                'value' => $firstName
            ],
            [
                'typeId' => 'SFCT_9',
                'value' => $lastName
            ],
            [
                'typeId' => 'SFCT_10',
                'value' => strtoupper($sanctionFields['gender'])
            ],
            [
                'typeId' => 'SFCT_11',
                'value' => $citizenship
            ],
            [
                'typeId' => 'SFCT_12',
                'value' => $nationality
            ],
            [
                'typeId' => 'SFCT_13',
                'dateTimeValue' => $sanctionFields['dob']
            ],
            [
                'typeId' => 'SFCT_14',
                'value' => "PASSPORT",
                'dateTimeValue' => null
            ],
            [
                'typeId' => 'SFCT_15',
                'value' => $sanctionFields['passport_number']
            ],
            [
                'typeId' => 'SFCT_16',
                'dateTimeValue' => $sanctionFields['passport_expiry_date']
            ]
        ];

        return $secondaryFields;
    }

    public static function logApiRequestInfo($request_headers, $response, $url, $payload, int $durationMs = 0, int $attempt = 0, bool $binaryResponse = false)
    {
        $response_header = array_map(function ($header) {
            return $header[0];
        }, $response->headers());

        Log::channel('worldchecklog')->info(
            "-----------------------------------------------------------------\n" .
                "WorldCheck API Call \n" .
                "URL: $url\n" .
                "Status Code: " . $response->status() . "\n" .
                "Payload: " . print_r($payload, true) . "\n" .
                "Request Headers: " . print_r($request_headers, true) . "\n" .
                "Response Headers: " . print_r($response_header, true) . "\n\n"
        );

        self::persistApiLog($request_headers, $response, $url, $payload, $durationMs, $attempt, $binaryResponse);
    }

    /**
     * Persist API call request/response for Compliance Management viewer.
     * Auth secrets are redacted from stored headers.
     */
    private static function persistApiLog($request_headers, $response, $url, $payload, int $durationMs, int $attempt, bool $binaryResponse): void
    {
        try {
            $safeHeaders = is_array($request_headers) ? $request_headers : [];
            if (isset($safeHeaders['Authorization'])) {
                $safeHeaders['Authorization'] = '[REDACTED]';
            }

            $responseHeaders = [];
            foreach ($response->headers() as $key => $values) {
                $responseHeaders[$key] = is_array($values) ? ($values[0] ?? '') : $values;
            }

            $requestPayload = null;
            if (is_array($payload)) {
                $requestPayload = $payload;
                if (isset($requestPayload['content']) && is_string($requestPayload['content'])) {
                    $decoded = json_decode($requestPayload['content'], true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $requestPayload['content'] = $decoded;
                    }
                }
            }

            $body = null;
            if ($binaryResponse) {
                $body = '[binary download, ' . strlen((string) $response->body()) . ' bytes]';
            } else {
                $raw = (string) $response->body();
                // Cap very large JSON bodies for storage safety.
                if (strlen($raw) > 500000) {
                    $body = substr($raw, 0, 500000) . "\n…[truncated]";
                } else {
                    $body = $raw;
                }
            }

            $clientId = self::$logContext['client_id'] ?? request()->get('client_id');
            $clientType = self::$logContext['client_type'] ?? null;
            $clientName = self::$logContext['client_name'] ?? null;
            $entityLabel = self::$logContext['entity_label'] ?? null;

            if ($clientId && (!$clientType || !$clientName)) {
                $client = \App\Models\Client::select('id', 'name', 'type')->find($clientId);
                if ($client) {
                    $clientType = $clientType ?: $client->type;
                    $clientName = $clientName ?: $client->name;
                }
            }

            WorldCheckApiLog::create([
                'user_id' => Auth::id(),
                'client_id' => $clientId ? (int) $clientId : null,
                'client_type' => $clientType,
                'client_name' => $clientName,
                'entity_label' => $entityLabel,
                'method' => strtoupper((string) ($payload['method'] ?? 'GET')),
                'endpoint' => (string) ($payload['endpoint'] ?? parse_url((string) $url, PHP_URL_PATH)),
                'url' => (string) $url,
                'http_status' => (int) $response->status(),
                'duration_ms' => $durationMs,
                'attempt' => $attempt,
                'request_payload' => $requestPayload !== null ? json_encode($requestPayload) : null,
                'request_headers' => json_encode($safeHeaders),
                'response_body' => $body,
                'response_headers' => json_encode($responseHeaders),
                'error_message' => $response->successful() ? null : ('HTTP ' . $response->status()),
            ]);
        } catch (\Throwable $e) {
            Log::channel('worldchecklog')->warning('Failed to persist WorldCheck API log: ' . $e->getMessage());
        }
    }
}
