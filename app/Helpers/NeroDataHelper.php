<?php

/**
 * Created by VS Code.
 * User: Rizwan Abbas
 * Date: 10/03/2025
 * Time: 1:28 PM
 */

namespace App\Helpers;

use App\Models\Module;
use App\Models\ModuleConfiguration;
use App\Models\TRRequest;
#use App\Models\AccountTransaction;
use Carbon\Carbon;
use League\Flysystem\Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Log;

class NeroDataHelper
{
    private $baseUrl;
    private $userName;
    private $password;
    private $portalTransactionsURL;

    public function __construct()
    {

        $module = Module::where('name', 'NeroData')->select('id', 'is_enabled')->first();
        if (!session('neroDataConfig')) {
            $moduleConfig = ModuleConfiguration::where('module_id', $module->id)->first();
            if ($moduleConfig) {
                session(['neroDataConfig' => unserialize($moduleConfig->configurations)]);
            } else {
                throw new Exception('No configurations found. Please feed the Nero data Configurations.');
            }
        }

        if (env('APP_ENV') == 'production') {
            $this->baseUrl = session('neroDataConfig')['production_api_base_url']['value'];
            $this->userName = session('neroDataConfig')['production_api_username']['value'];
            $this->password = session('neroDataConfig')['production_api_password']['value'];
            $this->portalTransactionsURL = session('neroDataConfig')['production_portal_transactions_url']['value'];
        } else {
            $this->baseUrl = session('neroDataConfig')['sandbox_api_base_url']['value'];
            $this->userName = session('neroDataConfig')['sandbox_api_username']['value'];
            $this->password = session('neroDataConfig')['sandbox_api_password']['value'];
            $this->portalTransactionsURL = session('neroDataConfig')['sandbox_portal_transactions_url']['value'];
        }
    }

    public function getConfigs()
    {
        return session('neroDataConfig');
    }


    public function sendTransaction($payload, $beneficiary = '')
    {
        try {
            $module = Module::where('name', 'NeroData')->select('id', 'is_enabled')->first();
            if (!empty($module)) {
                if (!$module->is_enabled)
                    return;
            }

            $endpoint =  '/fraud/transactions';
            // dd($beneficiary);

            $aml_response = $this->sendAmlQuery($payload['transaction']['customerFirstName'], $payload['transaction']['transactionId'], $beneficiary);
            $payload['transaction']['transactionMetadata']['hasAmlMatch'] = false;
            if (count($aml_response) > 0) {
                if (in_array('MODERATION_SANCTIONS', $aml_response)) {
                    $payload['transaction']['transactionMetadata']['hasAmlMatch'] = true;
                }
            }

            $response =  $this->post($endpoint, $payload);
            $this->updateTransactionStatus($payload['transaction']['transactionId'], $response->response);
            $this->storeApiResponseData($payload['transaction']['transactionId'], json_encode($response));
        } catch (\Exception $e) {
            Log::error('NeroData sendTransaction error: ' . $e->getMessage(), [
                'payload' => $payload,
                'beneficiary' => $beneficiary,
            ]);
        }
    }

    public function sendAmlQuery($NameSurname, $transactionId, $beneficiary)
    {


        $compliance_status = [];

        if (!empty($beneficiary)) {
            $compliance_status[] =  $this->beneficiaryAml($transactionId, $beneficiary->name);
            $compliance_status[] = $this->beneficiaryAml($transactionId, $beneficiary->bank_name);
        }

        return $compliance_status;
    }

    public function beneficiaryAml($transactionId, $beneficiary)
    {
        $endpoint =  '/aml/Search';
        $payload = '{
                "filters": [
                    {
                    "key": "NameSurname",
                    "value": "' . $beneficiary . '",
                    "required": false
                    }
                ],
                "applicationId": "NerodataWebApplication",
                "threshold": "0"
                }';
        //Log::error("\n\n===============================\n\n".'API Payload : ' , ['data' => $payload]);

        try {
            $payload = json_decode($payload, true);
            $response =  $this->post($endpoint, $payload);
            $compliance_status = '';
            if (!empty($response)) {
                if (count($response->response->matches)) {
                    $compliance_status = 'MODERATION_SANCTIONS';
                }

                $this->storeApiResponseData($transactionId, '', '', $response, 1);
            }


            return  $compliance_status;
        } catch (\Exception $e) {
            Log::error('NeroData beneficiaryAml error: ' . $e->getMessage(), [
                'transactionId' => $transactionId,
                'beneficiary' => $beneficiary
            ]);
            return '';
        }
    }
    

    public function verifyComplinace($data)
    {

        $complianceId = $data->payload->id;
        $transactionId = $data->payload->transactionId;
        $transactionAmount = $data->payload->transactionAmount;
        $transactionCurrency = $data->payload->transactionCurrency;
        $transactionType = $data->payload->transactionType;
        $status = $data->payload->status;
        if (!empty($complianceId)) {
            $compliance = $this->getComplianceResultById($complianceId);
            if (empty($compliance))
                return false;

            if (
                $complianceId == $compliance->id &&
                $transactionId  == $compliance->transactionId &&
                $transactionAmount == $compliance->transactionAmount &&
                $transactionType == $compliance->transactionType &&
                $status == $compliance->status
            ) {
                return true;
            }

            return false;
        }
    }

    public function getComplianceResultById($complianceId)
    {
        $endpoint = '/fraud/transactions/' . $complianceId;

        try {
            $data = $this->get($endpoint);
            return $data;
        } catch (\Exception $e) {
            Log::error('NeroData getComplianceResultById error: ' . $e->getMessage(), [
                'complianceId' => $complianceId,
            ]);
            return null;
        }
    }
    //Used in wenhookContoller
    public function updateTReqComplianceStatus($data)
    {
        if (!empty($data)) {
            $data = json_decode($data);
            $transactionId = $data->payload->transactionId;
            $trRequest = TRRequest::where('id', $transactionId)->first();
            if (!empty($trRequest)) {
                if ($this->verifyComplinace($data)) {
                    $trRequest->compliance_status = $data->payload->status;
                    $trRequest->save();
                    if($trRequest->compliance_status != 'MODERATION' && $data->payload->status == 'MODERATION') {
                        // send email
                        $portalTransactionsUrl = $this->getPortalTransactionsUrl();
                        EmailNotification::notifyComplianceTeamForAMLTransaction($trRequest->id, $data->payload->id, $portalTransactionsUrl);

                    }
                    $this->storeApiResponseData($transactionId, json_encode($data), 'webhook');
                    return true;
                }
            }
        }
        return false;
    }
    //Used in TrReq Model
    public function updateTransactionStatus($tr_id, $response, $compliance_status = '')
    {
        // dd($response);
        if (!empty($response)) {

            if ($tr_id != $response->transactionId)
                return false;

            $trRequest = TRRequest::where('id', $tr_id)->first();
            $status = $response->status;
            if (!empty($status)) {
                $trRequest->compliance_status = $status;
                $trRequest->save();
                if($trRequest->compliance_status != 'MODERATION' && $status == 'MODERATION') {
                    // send email

                        $portalTransactionsUrl = $this->getPortalTransactionsUrl();
                        EmailNotification::notifyComplianceTeamForAMLTransaction($trRequest->id, $response->id, $portalTransactionsUrl);

                }
            }
        }
    }

    public function get($endpoint)
    {
        $url = $this->baseUrl . $endpoint;
        //$result = Http::withToken(session('token'))->get($url);
        $result = Http::withBasicAuth($this->userName, $this->password)->get($url);
        if ($result->getBody()) {
            $result = json_decode($result->getBody());
        }
        return $this->parseResult($result);
    }


    public function post($endpoint, $data)
    {
        $url = $this->baseUrl . $endpoint;

        $result = Http::withBasicAuth($this->userName, $this->password)->post($url, $data);

        if ($result->getBody()) {
            $result = json_decode($result->getBody());
        }

        return $this->parseResult($result);
    }

    public function parseResult($result)
    {

        if (!$result) {
            throw new Exception('Something went wrong, please try again later.');
        } elseif (!empty($result->data)) {
            return $result->data;
        } else {
            return $result;
        }
    }

    public function getNeroDataLogText($tr_req_id)
    {
        $amlRules =  DB::table('crypto_transactions_log')
            ->where('transaction_id', $tr_req_id)
            ->orderBy('id', 'desc')
            ->get();
        // dd($amlRules);
        $ruleStr = '';
        if (!empty($amlRules)) {

            $response = '';
            foreach ($amlRules as $rules) {
                if (!empty($rules->details)) {
                    $details = json_decode($rules->details);

                    if (!empty($details->response))
                        $response = $details->response;
                    if (!empty($details->payload))
                        $response = $details->payload;
                }


                // dd($details->response->rules);
                if (!empty($response->rules)) {
                    $rules = $response->rules;
                    $ruleStr = '<br><br><strong>Rules</strong>';
                    $ruleStr .= '<ul>';
                    foreach ($rules as $rule) {
                        $ruleStr .= '<li>' . e($rule->id) . ' - ' . e($rule->name) . '</li>';
                    }

                    $ruleStr .= '</ul>';
                }
                /*  if (!empty($response->notes)) {
                    $notes = $response->notes;
                    $ruleStr .= '<br><strong>Notes</strong>';
                    $ruleStr .= '<ol>';
                    foreach ($notes as $note) {
                        $ruleStr .= '<li>' . $note->text . '</li>';
                    }

                    $ruleStr .= '</ol>';
                } */
                if (!empty($response->matches)) {
                    $matches = $response->matches;

                    $ruleStr .= '<br><strong style="color:red;">Sanctions</strong>';
                    $ruleStr .= '<ul>';
                    foreach ($matches as $entry) {
                        //dd($entry->entry);
                        if (!empty($entry->entry->notes))
                            $ruleStr .= '<li><strong>' . $entry->entry->firstName . " " . $entry->entry->lastName . " (" . $entry->entry->type . "):</strong> <br>" . $entry->entry->notes . '</li>';
                        else
                            $ruleStr .= '<li><strong>' . $entry->entry->firstName . " " . $entry->entry->lastName . " (" . $entry->entry->type . "):</strong> <br>" . $entry->entry->list->name . '</li>';
                    }

                    $ruleStr .= '</ul>';
                }
            } //foreach
        } //if
        return $ruleStr;
    }

    public static function storeApiResponseData($transaction_id, $data, $source = 'admin', $amlResponse = '', $isAml = 0)
    {

        if (!empty($amlResponse) && $isAml == 1) {
            if (count($amlResponse->response->matches) > 0)
                $data = json_encode($amlResponse);
            else
                return;
        }

        //Log::error('API Response : ' , ['data' => $data]);

        try {
            DB::table('crypto_transactions_log')->insert([
                'transaction_id' => $transaction_id,
                'details' => ($data), // Ensure data is stored properly
                'crypto_system' => 'NeroData',
                'source' => $source,
                'date_created' => now() // Laravel helper for timestamps
            ]);

            return response()->json(['message' => 'Data stored successfully'], 201);
        } catch (Exception $e) {
            // Log the error for debugging
            Log::error('Failed to store API response data: ' . $e->getMessage(), [
                'transaction_id' => $transaction_id,
                'data' => $data,
            ]);

            return response()->json(['error' => 'Failed to store data'], 500);
        }
    }

    public function getNeroDataTransactionIdByTrId(string|int $transactionId, bool $tryRemote = true): ?string
    {

        $id = $this->extractIdFromLocalLogs($transactionId);
        if ($id) {
            return $id;
        }

        if (!$tryRemote) {
            return null;
        }

        try {

            $res = $this->get('/fraud/transactions?transactionId=' . urlencode((string)$transactionId));
            $id = $this->extractIdFromAny($res);
            Log::info("\n\n  Nero Data id from transaction id: " . json_encode($res) . '\n');

            if ($id) return $id;
        } catch (\Throwable $e) {
            Log::warning('NeroData remote lookup failed', [
                'transactionId' => (string)$transactionId,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    protected function extractIdFromLocalLogs(string|int $transactionId): ?string
    {
        $details = DB::table('crypto_transactions_log')
            ->where('transaction_id', $transactionId)
            ->orderByDesc('id')
            ->value('details');

        if (!$details) {
            return null;
        }

        $obj = json_decode($details);
        $id = $this->extractIdFromAny($obj);

        return $id ? (string) $id : null;
    }

    protected function extractIdFromAny($data): ?string
    {
        if (!$data) return null;

        if (is_array($data)) {
            foreach ($data as $item) {
                $id = $this->extractIdFromAny($item);
                if ($id) return (string)$id;
            }
            return null;
        }

        if (is_object($data)) {
            // API response wrapper with "response"
            if (isset($data->response)) {
                $id = $this->extractIdFromAny($data->response);
                if ($id) return (string)$id;
            }
            // Webhook payload shape
            if (isset($data->payload) && is_object($data->payload) && isset($data->payload->id)) {
                return (string)$data->payload->id;
            }
            // Direct id on object
            if (isset($data->id)) {
                return (string)$data->id;
            }
        }

        return null;
    }
    public function getPortalTransactionsUrl()
    {
        return  $this->portalTransactionsURL;
    }
}
