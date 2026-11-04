<?php

namespace App\Helpers;

use League\Flysystem\Exception;
use App\Models\Module;
use App\Models\CardDetail;
use App\Models\AssignedCard;
use App\Models\UserCardLink;
use App\Models\Client;
use App\Models\ClientKYC;
use App\Models\Country;
use App\Models\FingateClient;
use App\Models\FingateWallet;
use App\Models\ClientFileConfig;
use App\Models\ClientCompanyFile;
use App\Models\ClientIndividualFile;
use App\Models\KycRequest;
use App\Models\CardStatementsDetail;
use Illuminate\Support\Facades\Hash;
use App\Helpers\EmailNotification;
use App\Models\Currency;
use App\Models\CardStatement;
use App\Models\ModuleConfiguration;
use Carbon\Carbon;
use GuzzleHttp\Client as GuzzleHttpClient;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

use DateTime;

class FingateHelper
{

    private  $baseURL;
    private  $apiKey;
    private  $secretKey;
    private  $masterWalletId;
    private $decryptionKeyId;
    private $productId;
    private $providerId;
    public $cardProvider;
    public $webHookHash;
    private $identifiers = array(

        'individual' => ['utility_bill', 'bank_statement'],
        'company' => ['certificate_of_incorporation', 'memorandum_of_association', 'bank_statement']
    );

    public function __construct($cardProvider = null)
    {

        $this->cardProvider = $cardProvider;
        $moduleName = 'MatchMove';

        if ($this->cardProvider == 'match_move')
            $moduleName = 'MatchMove';
        if ($this->cardProvider == 'e_money')
            $moduleName = 'E-Money';

        $this->log('Current Module: ', $moduleName);

        //$moduleName = 'MatchMove'; // Change this as needed

        if (!session("{$moduleName}Config")) {
            $module = Module::where('name', $moduleName)->select('id')->first();

            if (!$module) {
                throw new \Exception("Module [$moduleName] not found.");
            }

            $moduleConfig = ModuleConfiguration::where('module_id', $module->id)->first();

            if ($moduleConfig) {
                session(["{$moduleName}Config" => unserialize($moduleConfig->configurations)]);
            } else {
                throw new \Exception("No configurations found for [$moduleName]. Please feed the configurations.");
            }
        }

        $config = session("{$moduleName}Config");

        if (env('APP_ENV') === 'production') {
            $this->baseURL         = $config['production_api_base_url']['value'] ?? null;
            $this->apiKey          = $config['production_api_key']['value'] ?? null;
            $this->secretKey       = $config['production_secret_key']['value'] ?? null;
            $this->masterWalletId  = $config['production_master_wallet_id']['value'] ?? null;
            $this->decryptionKeyId = $config['production_decryption_key_id']['value'] ?? null;
            $this->productId = $config['production_product_id']['value'] ?? null;
            $this->providerId = $config['provider_id']['value'] ?? null;
        } else {
            $this->baseURL         = $config['sandbox_api_base_url']['value'] ?? null;
            $this->apiKey          = $config['sandbox_api_key']['value'] ?? null;
            $this->secretKey       = $config['sandbox_secret_key']['value'] ?? null;
            $this->masterWalletId  = $config['sandbox_master_wallet_id']['value'] ?? null;
            $this->decryptionKeyId = $config['sandbox_decryption_key_id']['value'] ?? null;
            $this->productId = $config['sandbox_product_id']['value'] ?? null;
            $this->providerId = $config['provider_id']['value'] ?? null;
        }

        $this->webHookHash = $config['Webhookhash']['value'] ?? null;

        //dd($this->decryptionKeyId );
    }

    public function cardTopUp($payload)
    {
        //dd($payload);
        //$payload["WalletId"] =  $this->masterWalletId;
        $this->log('Top Up Payload: ', json_encode($payload));

        //Log::info('--------------------------------------TopUpTOCard-------------------------------------');

        $topup =  $this->sendApiRequest('/Card/TopUpToCard', $payload);
        $this->log('Top Up response: ', json_encode($topup));
        return $topup;
    }

    public function UpdateCustomerEmail($payload)
    {
        //dd($payload);
        $this->log('UpdateCustomerEmail Payload: ', json_encode($payload));

        //Log::info('--------------------------------------TopUpTOCard-------------------------------------');

        $response =  $this->sendApiRequest('/Customer/UpdateCustomerEmail', $payload);
        $this->log('UpdateCustomerEmail response: ', json_encode($response));
        return $response;
    }

    public function UpdateCustomerMobileNumber($payload)
    {
        //dd($payload);
        $this->log('UpdateCustomerMobileNumber Payload: ', json_encode($payload));

        //Log::info('--------------------------------------TopUpTOCard-------------------------------------');

        $response =  $this->sendApiRequest('/Customer/UpdateCustomerMobileNumber', $payload);
        $this->log('UpdateCustomerMobileNumber response: ', json_encode($response));
        return $response;
    }

    public function cardTopUpWithHttp($payload)
    {
        $payload["WalletId"] = $this->masterWalletId;
        //echo json_encode($payload); die();
        $response = Http::withHeaders([
            'ApiKey'    => $this->apiKey,
            'SecretKey' => $this->secretKey,
            'Accept'    => 'application/json',
        ])->timeout(30)->withoutVerifying() // disable SSL verification if needed
            ->post($this->baseURL . '/Card/TopUpToCard', $payload);

        // Log full response for debugging
        Log::info('Fingate API response:', ['body' => $response->body()]);
        // dd($response->body());
        if ($response->successful() && $response->json('IsSucceeded')) {
            return $response->json();
        }

        return response()->json([
            'success' => false,
            'error' => $response->json('ErrorDescription') ?? 'Unknown API error.',
        ], 200);
    }

    public function blockCard($card_id)
    {
        $payload = array('CardId'   => $card_id);

        $result =  $this->sendApiRequest('/Card/FreezeCard', $payload);

        if (is_string($result))
            $result = json_decode($result);
        if (!$result) {
            throw new Exception('Something went wrong, please try again later.');
        } elseif ($result->ErrorCode == 'Success') {
            return $result;
        } else {
            return $result;
            // throw new Exception($result->ErrorDescription);
        }
    }

    public function unblockCard($card_id)
    {
        $payload = array('CardId'   => $card_id);

        $result =  $this->sendApiRequest('/Card/UnFreezeCard', $payload);

        if (!$result) {
            throw new Exception('Something went wrong, please try again later.');
        } elseif ($result->ErrorCode == 'Success') {
            return $result;
        } else {
            throw new Exception($result->ErrorDescription);
        }
    }
    public function createCustomerAPI($data, $client_type)
    {
        $url = '/Customer/CreateCustomer';
        if ($client_type != 'individual')
            $url = '/Customer/CreateCorporateCustomer';
        //dd($url);
        //echo json_encode($data); die();
        $customer =  $this->sendApiRequest($url, $data);
        //dd(json_decode($customer));
        if (is_string($customer))
            return json_decode($customer);
        else
            return $customer;
        //return $customer;
    }


    public function findCreateFingateCustomer($client_id)
    {


        $customer =  $this->sendApiRequest('/Customer/FindCustomerByCustomerNumber', ['findCustomerCustomerNumber' => $client_id, "findCustomerProcessorId" => $this->providerId], false);
        //dd($customer);
        $this->log('Found  existing created Customer: ', $customer);
        if (is_string($customer) && !is_object($customer))
            $customer = json_decode($customer);

        //dd($customer);
        // if(!empty($customer) && empty($customer->IsSucceeded))
        //     $customer = ($customer);

        //dd( $customer);
        if (!empty($customer->ErrorCode) && ($customer->IsSucceeded == false || $customer->ErrorCode === 'CustomerNotFound')) {
            $customer = $this->createCustomer($client_id);

            if ($customer->ErrorCode == 'Success') {
                $this->log('Created New Customer: ', $customer);
                $customer =  $this->sendApiRequest('/Customer/FindCustomerByCustomerNumber', ['findCustomerCustomerNumber' => $client_id], false);
                if (is_string($customer) && !is_object($customer)) {
                    $customer = json_decode($customer);
                    $this->log('Found  Newly created Customer: ', $customer);
                }
            }
        }
        //dd($customer);
        return $customer;
    }

    public function processWebhooks($data, $fingate_client)
    {
        // dd($data);
        if ($data->WebHookType == 'TreeDSecureOtpWebHook') {
            return $this->send3dSecureCode($data, $fingate_client);
            //return true;

        } elseif ($data->WebHookType == 'CardProvisioningOtpWebHook') {
            return $this->sendCardProvisioningCode($data, $fingate_client);
        } elseif ($data->WebHookType == 'CardStatusWebHook') {
            return $this->lockUnlockCard($data);
            //return true;

        } elseif ($data->WebHookType == 'CardActivationWebHook') {
            return true;
        } elseif ($data->WebHookType == 'DeclineCardTransactionWebHook') {

            return $this->processStatementTransaction($data, $fingate_client);
        } elseif ($data->WebHookType == 'ApprovedCardTransactionWebHook') {
            return $this->processStatementTransaction($data, $fingate_client);
        } elseif ($data->WebHookType == 'ReversedCardTransactionWebHook') {

            return $this->processStatementTransaction($data, $fingate_client);
        }
    }

    public function lockUnlockCard($data)
    {

        $fingateHelper = new FingateHelper();
        $block = '';
        $data = $data->WebHookPayload;
        $cardDetails = CardDetail::where('card_id', $data->CardId)->where('customer_id', $data->CustomerId)->first();
        $card = $this->findCardAPI($data->CardId);
        //dd($card);
        if (empty($cardDetails))
            return false;

        if ($card->CardStatus == $data->OldCardStatus)
            return true;

        if ($cardDetails->cardStatus == 'Active' && $card->CardStatus == 'A')
            return true;

        if ($cardDetails->cardStatus == 'Locked' && $card->CardStatus != 'A')
            return true;
        $status = '';
        if ($card->CardStatus == 'A') {
            $status = 'Active';
        } else {
            $status = 'Locked';
        }
        //$card_details = $linkedCard->cardDetails;
        $cardDetails->cardStatus = $status;
        $mpin = $cardDetails->mpin;
        $cardDetails->save();
        $assignedCard = AssignedCard::where('mpin', $mpin)->first();
        if (!empty($assignedCard)) {
            $assignedCard->status = $status;
            $assignedCard->save();
        }
        $linkedCardId = '';
        $linkedCard = $assignedCard->link;
        if (!empty($linkedCard)) {
            $linkedCardId =  $linkedCard->id;
            $client = $linkedCard->owner;
        }

        if (!empty($client->users) && isset($client->users[0]) && $status == 'Locked' && !empty($linkedCardId)) {
            EmailNotification::notifyClientForCardBlock($client, $linkedCardId);
        } elseif (!empty($client->users) && isset($client->users[0]) && !empty($linkedCardId)) {
            EmailNotification::notifyClientForCardUnblockApproval($client, $linkedCardId);
        }
        return true;
    }

    public function send3dSecureCode($data, $fingate_client)
    {
        $data = $data->WebHookPayload;
        $card_detail = CardDetail::where('card_id', $data->CardId)->first();
        if (empty($card_detail)) {
            return false;
        }
        $client = $fingate_client->client;
        if (!empty($client)) {
            EmailNotification::send3dSecureCode($client, $data);
            return true;
        }
        return false;
    }

    public function sendCardProvisioningCode($data, $fingate_client)
    {
        $data = $data->WebHookPayload;
        $card_detail = CardDetail::where('card_id', $data->CardId)->first();
        if (empty($card_detail)) {
            return false;
        }
        $client = $fingate_client->client;
        if (!empty($client)) {
            EmailNotification::sendCardProvisioningCode($client, $data);
            return true;
        }
        return false;
    }


    public function processStatementTransaction($data, $fingate_client)
    {
        $data = $data->WebHookPayload;
        //dd($data);
        $filters = [
            'findCardTransactionId' => $data->TransactionId
        ];
        //dd($filters);
        $transaction = $this->getTransaction($filters);
        //dd($transaction);
        if (!empty($transaction) && !empty($transaction->CardTransaction) && !empty($transaction->CardTransaction->TransactionId)) {
            if (
                $transaction->CardTransaction->TransactionId == $data->TransactionId &&
                //$transaction->CardTransaction->ReferenceNumber == $data->ReferenceNumber &&
                $transaction->CardTransaction->CustomerId == $data->CustomerId &&
                $transaction->CardTransaction->CardId == $data->CardId
                //$transaction->CardTransaction->OriginalTransactionAmount == $data->OriginalTransactionAmount &&
                //$transaction->CardTransaction->OriginalTransactionCurrencyCode == $data->OriginalTransactionCurrencyCode

            ) {
                return $this->addTransactionToStatement($transaction->CardTransaction, $fingate_client);
            }
        }
        return false;
    }

    public function addTransactionToStatement($txn, $fingate_client)
    {

        $card_detail = CardDetail::where('card_id', $txn->CardId)->first();
        if (empty($card_detail)) {
            return false;
        }

        $transaction_nature = $txn->TransactionEffect === 'D' ? 'Debit' : 'Credit';
        $base_currency = Currency::where('short', $txn->OriginalTransactionCurrencyCode)->first();
        $terminal_currency = Currency::where('short', $txn->SettlementTransactionCurrencyCode)->first();
        //dd($base_currency);
        if (
            $txn->FeeAmount == $txn->BillingAmount &&
            strpos(strtolower($txn->Description), 'transaction fee') !== false
        ) {
            $txn->TransactionId = $txn->TransactionId . '-fee';
            $txn->SettlementTransactionAmount = $txn->BillingAmount;
        }
        $tr_description = 'NA';
        $is_reversal = 0;
        if (!empty($txn->Description)) {
            $tr_description = $txn->Description;
            if (!empty($txn->TransactionCodeName) && strtolower($txn->TransactionCodeName) == 'Refund') {
                $tr_description .= ' - ' . ucfirst($txn->TransactionCodeName);
                $is_reversal  = strtolower($txn->TransactionCodeName);
            }
        }
        $settlementStatus = self::mapSettlementStatus($txn->SettlementStatus ?? null);
        $transactionStatus = self::mapTransactionStatus($txn->TransactionStatus ?? null);
        //dd($transactionStatus);

        $tr = [
            'local_card_id'         => $card_detail->id ?? 0,
            'currency_code'         => $txn->BillingCurrencyCode ?? 'USD',
            'mpin'                  => $card_detail->mpin ?? 0, //$txn->ReferenceNumber ?? null,
            'terminal_owner_country'    => 'NA',
            'terminal_type' => 'NA',
            'processing_currency'   => $base_currency->id ?? '',
            'debit_credit'          => $tr_description ?? null,
            'type'                  => $txn->Description ?? null,
            'txn_id'                => $txn->TransactionId ?? 0,
            //'card_holder'           => $txn->CustomerId ?? null,
            'card_holder'         => $card_detail->cardHoldername ?? null,
            //'visa_reference_no'     => $txn->Rrn ?? null,
            'visa_reference_no'         => $txn->ReferenceNumber ?? 0,
            'base_amount'           => $txn->OriginalTransactionAmount ?? 0,
            'cardholder_fee'           => $txn->FeeAmount ?? 0,
            'terminal_id'           =>  $txn->Rrn ?? null,
            'base_currency'         => $base_currency->id ?? 0,
            'approval_code'         => $txn->AuthorizationCode ?? null,
            'card_number'           =>  $txn->CardTokenNumber ?? null,
            'terminal_currency'     => $terminal_currency->id ?? 0,
            'terminal_amount'     => $txn->SettlementTransactionAmount ?? 0,
            'MCC'                   => $txn->Mcc ?? null,
            'type_of_operation'     => $transaction_nature,
            'card_id'               => $txn->CardId  ?? null,
            'is_reversal'           => $is_reversal,
            'processing_amount'     => $txn->BillingAmount ?? 0,

            'card_token'            => $txn->CardTokenNumber ?? null,
            'transaction_state'     => $settlementStatus,
            'time'                  => Carbon::parse($txn->LocalTransactionDateTime)->toDateTimeString(),
            'terminal_owner_title'           => 'NA',
            'transaction_nature'    => $transaction_nature,
            'result'                => $transactionStatus,
            'cardholder_fee'        => $txn->FeeAmount ?? 0,
            'terminal_id'           => $txn->Rrn ?? null,
            //'created_at'            => now(),
            'updated_at'            => now(),
        ];
        $trx_hash = md5(implode('-', $tr));
        $tr['trx_hash'] = $trx_hash;
        $statement = CardStatement::updateOrCreate(
            ['txn_id' => $txn->TransactionId],
            $tr
        );
        //dd($statement);
        // if(!empty($statement))
        //     continue;
        //$this->info("Stetement ID: ". $statement->id . " => Card Detail ID: ". $card_detail->id );
        //dd($statement);
        $statement_details = CardStatementsDetail::updateOrCreate(
            [
                // Criteria to find the record — adjust as needed
                'transaction_id' => $txn->TransactionId,
            ],
            [
                'statement_id'   => $statement->id ?? null,
                'card_id'        => $txn->CardId ?? null,
                'json_data'      => json_encode($txn),
            ]
        );
        if (!empty($statement) && !empty($statement->id)) {
            $client = $fingate_client->client;
            $text = '';
            $card_number = '**** **** **** ' . substr($tr['card_number'], -4);
            if ($is_reversal && $transactionStatus == 'Success') {
                $text = "<p>A refund of " . $tr['terminal_amount'] . " " . $tr['currency_code'] . " has been successfully issued to the card ending with " . $card_number . ".</p>
                        <p>Please review your card statement for further details.</p>";
            } elseif ($transactionStatus == 'Success') {
                $text = "<p>A new transaction of " . $tr['terminal_amount'] . " " . $tr['currency_code'] . " has been successfully processed at " . $tr['debit_credit'] . " card ending with " . $card_number . ".</p>
                        <p>Please review your card statement for further details.</p>";
            } elseif ($transactionStatus == 'Reversed') {
                $text = "<p>The transaction of " . $tr['terminal_amount'] . " " . $tr['currency_code'] . " on the card ending with " . $card_number . " has been reversed.</p>
                        <p>Please review your card statement for further details.</p>";
            } elseif ($transactionStatus == 'Expired') {
                $text = "<p>The transaction of " . $tr['terminal_amount'] . " " . $tr['currency_code'] . " on the card ending with " . $card_number . " has been reversed because the settlement window expired.</p>
                        <p>Please review your card statement for further details.</p>";
            } elseif ($transactionStatus == 'Declined') {
                $text = "<p>The transaction of " . $tr['terminal_amount'] . " " . $tr['currency_code'] . " on the card ending with " . $card_number . " has been declined.</p>
                        <p>Please review your card statement for further details.</p>";
            }
            if (!empty($text))
                EmailNotification::notifyClientCardTransactionStatus($client, $tr['txn_id'], $text);

            return true;
        }
        return false;
    }

    public static function mapSettlementStatus(?string $status): string
    {
        return match ($status) {
            'N' => 'UnSettled',
            'Y' => 'Settled',
            'D' => 'Declined',
            'R' => 'Reversed',
            default => 'UnSettled',
        };
    }

    public static function mapTransactionStatus(?string $status): string
    {
        return match ($status) {
            'S' => 'Success',
            'D' => 'Declined',
            'E' => 'Expired',
            'R' => 'Reversed',
            default => 'Declined',
        };
    }

    public function findWalletAPI($data)
    {

        return $this->sendApiRequest('/Wallet/SearchWallet', $data);
    }

    public function getWalletBalanceAPI()
    {
        $data  = [
            'findPreFundAccountProcessorId' => $this->providerId
        ];
        return $this->sendApiRequest('/Tenant/GetTenantCardPreFundAccount', $data, false);
    }

    public function createVirtualCardAPI($data)
    {

        return $this->sendApiRequest('/Card/CreateVirtualCard', $data);
    }

    public function findCardAPI($cardId)
    {

        return $this->sendApiRequest('/Card/FindCard', ['findCardId' => $cardId], false);
    }

    public function getTransaction($filters)
    {
        return $this->sendApiRequest('/Card/FindCardTransaction', $filters, false);
    }

    public function getAllTransactions($filters)
    {

        return $this->sendApiRequest('/Card/SearchCardTransactions', $filters);
    }

    public function uploadKycDocument($data)
    {

        return $this->sendApiRequest('/Kyc/UploadKycBase64Document', $data);
    }

    public function uploadAllKycDocuments($data)
    {
        //print(json_encode($data)); die();
        $this->log('uploadAllKycDocuments: Uploading');
        return $this->sendApiRequest('/Kyc/UploadAllKycDocuments', $data);
    }
    public function uploadAllKYBDocuments($data)
    {
        //print(json_encode($data)); die();
        $this->log('uploadAllKybDocuments: Uploading KYB Documents');
        return $this->sendApiRequest('/Kyb/UploadAllKybDocuments', $data);
    }


    public function deleteCustomerByEmail($email)
    {

        $this->sendApiRequest('/Card/FindCard', '', false);
    }

    public function simulateCardTransactionAPI($data)
    {
        return $this->sendApiRequest('/Simulator/SimulateCardTransaction', $data);
    }

    public function getCardProviders()
    {
        return $this->sendApiRequest('/Parameter/GetCardProcessorList', '', false);

        //return  $providers;
    }

    public function sendApiRequest($endpoint, $data = [], $isPost = true)
    {
        //echo  $this->baseURL  . $endpoint; die();
        $this->log('Endpoint Hit : ', $this->baseURL . $endpoint);

        try {
            $clientHttp = new GuzzleHttpClient();
            $options = [
                'headers' => [
                    'ApiKey'    => $this->apiKey,
                    'SecretKey' => $this->secretKey,
                    'accept'    => 'application/json',
                ],
                'verify' => false,
                'timeout'  => 60,
                'connect_timeout' => 15,
            ];
            //dd($options);
            //echo $isPost . '<br>';

            if ($isPost === 'PUT') {
                //$options['query'] = $data; // Add query parameters for GET request
                if (!empty($data)) {
                    $options['json'] = $data;
                }
                //dd($options);
                //  echo $this->baseURL . $endpoint; die();

                $response = $clientHttp->put($this->baseURL . $endpoint, $options);
                //$response =  $respons->getResponse()->getBody()->getContents();
                //$result = ($response->getBody()->getContents());
                //echo time().'<br>'; die();
                //dd($response);
            } else if ($isPost == true) {
                //$options['json'] = $data; // Add JSON body for POST request
                $options['json'] = !empty($data) ? $data : new \stdClass();
                $response = $clientHttp->post($this->baseURL . $endpoint, $options);
            } else {
                if (!empty($data))
                    $options['query'] = $data; // Add query parameters for GET request

                $response = $clientHttp->get($this->baseURL . $endpoint, $options);
            }

            $result = json_decode($response->getBody());

            // Log::info("----------------------------------------------------------------------------------\n\n\n");
            // Log::info('Response: ' . print_r($result, true));
            // Log::info("----------------------------------------------------------------------------------\n\n\n");

            if (!$result) {
                return response()->json([
                    'success' => false,
                    'error' => "Something went wrong, please try again later.",
                ], 200);
            } elseif ($result->IsSucceeded) {
                return $result;
            } else {
                return response()->json([
                    'success' => false,
                    'error' => $result->ErrorDescription,
                ], 200);
            }
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            // Handle 400 errors and log the response body
             if($e->getCode() === 400) {
                 $responseBody = $e->getResponse()->getBody()->getContents();
                $this->log('Response Body: ', $responseBody);
                return $responseBody;
            }
            //dd($e);
            // $responseBody = $e->getResponse()->getBody()->getContents();
            // $this->log('ClientException: ', $e->getResponse()->getBody());
            // $this->log('Response Body: ', $responseBody);

            throw new \Exception("GuzzleHttp Error: " . $e->getMessage());
            // return $e->getResponse()->getBody();
        } catch (\Throwable $th) {
            // Catch any other exceptions
            $this->log('Unexpected Error: ', $th->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'An unexpected error occurred. Please try again later.',
            ], 500);
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            if ($e->getCode() === 0 && strpos($e->getMessage(), 'timed out') !== false) {
                $this->log('Request timed out: ', $e->getMessage());
                throw new \Exception("Request timed out: " . $e->getMessage());
            }
            throw $e; // re-throw other request errors
        }
    }
    public function reverifyDocuments($customer_id, $client_id)
    {
        $client = Client::find($client_id);
        $fingateCustomer =  $this->findCreateFingateCustomer($client->id);
        //dd($fingateCustomer);
        if ($client->type  == 'individual') {
            $EmbossName = $fingateCustomer->Customer->Name . ' ' . $fingateCustomer->Customer->MiddleName . ' ' . $fingateCustomer->Customer->Surname;
            $documents = $this->sendKYCDocuments($client->id, $fingateCustomer->Customer->CustomerId, $EmbossName);
        } else {

            $EmbossName = substr($fingateCustomer->Customer->CompanyName, 0, 25);
            $documents = $this->sendKYBDocuments($client->id, $fingateCustomer->Customer->CustomerId, $EmbossName);
        }

        if (!empty($documents) && $documents->ErrorCode !== 'KycFailed') {
            //$card = $this->createVirtualCard($EmbossName, $fingateCustomer->Customer->CustomerId, $wallet->WalletId, $request);
            $this->log('KYC/KYB Success Response: ', $documents);
            return $documents;
        } else {
            $this->log('KYC documents Failed: ', $documents);
            return $documents;
        }
    }



    //Step 1
    public function createOrAssignCard($request, $client_id, $provider = 'match_move', $is_physical_card = false)
    {
        if (empty($client_id)) {
            return $this->errorResponse('Validation error', 'Client is missing.', config('api.httpCodes.data-error'));
        }
        $client = Client::find($client_id);
        $wallet = '';

        $fingateCustomer =  $this->findCreateFingateCustomer($client_id);
        //dd($fingateCustomer);
        //test data
        // $fingateCustomer = (object) [
        //     "CustomerId"          => 500000160,
        //     "HasOnBoardingUrl"    => true,
        //     "OnBoardingUrl"       => "https://test-onboarding.emoney.ge/api/auth?access_token=721F091DZ5VWUGTP3CG5ELQXD2B66C76&theme=dark&language=en",
        //     "IsSucceeded"         => true,
        //     "ErrorCode"           => "Success",
        //     "ErrorDescription"    => "Operation Completed Successfully.",
        //     "ProcessDateTime"     => "2025-09-08T11:46:24.5726503Z",
        //     "IsInApproval"        => false,
        //     "ApprovalTransactionId" => 0,
        // ];
        //dd($fingateCustomer);
        if (!empty($fingateCustomer->HasOnBoardingUrl) && $fingateCustomer->HasOnBoardingUrl) {
            //if onboarding is required by emoney

            $client->is_kyc_requested = 1;
            $client->save();
            $hash = str_replace('/', '', Hash::make($client->id . '-' . time()));
            $kyc_req  =  new KycRequest();
            $kyc_req->created_by = Auth::user()->id;
            $kyc_req->expiry = date("Y-m-d H:i:s", strtotime("+4 week"));
            $kyc_req->hash = $hash;
            $kyc_req->client_id =  $client->id;
            $kyc_req->request_selfie =  1;
            $kyc_req->request_passport =  1;
            $kyc_req->OnBoardingUrl =  $fingateCustomer->OnBoardingUrl;
            $kyc_req->kyc_provider = 'sumsub';
            $kyc_req->save();

            EmailNotification::notifyClientKYCRequest($client, $hash, $kyc_req->OnBoardingUrl);
            //return redirect(url()->previous())->with('success', 'Client KYC has been requested successfully!')->with('formnumber', 3);
            $error = array('ErrorCode' => 'KycFailed', 'ErrorDescription' => "Client KYC has been requested successfully, Card will created once client KYC is completed.");
            $this->log('Customer KYC is Required by E-Money: ', $kyc_req->OnBoardingUrl);
            return json_decode(json_encode($error));
        }

        //dd($fingateCustomer);

        if (!empty($fingateCustomer->Customer->CustomerNumber) && !empty($fingateCustomer->Customer->CustomerId)) {
            $wallet = $this->createFindWallet($fingateCustomer->Customer->CustomerId, $client_id);
            $wallet = $wallet->WalletList[0];
            $this->log('Customer Created with Wallet: ', $wallet, false);
        } else {
            //dd($fingateCustomer);
            $this->log('Could not create or find Customer: ', $fingateCustomer);
            return  $fingateCustomer;
        }

        if (!empty($wallet)) {
            $EmbossName = '';

            Log::info('Wallet Found: ' . json_encode($wallet));

            if ($provider == 'match_move') {
                if (isset($fingateCustomer) && isset($fingateCustomer->Customer)) {
                    $EmbossName = substr($client->name, 0, 25);

                    if (isset($fingateCustomer->Customer->KycStatus) && $fingateCustomer->Customer->KycStatus != 'Success') {

                        if ($client->type  == 'individual') {
                            $documents = $this->sendKYCDocuments($client_id, $fingateCustomer->Customer->CustomerId, $EmbossName);
                        } else {
                            //dd( $fingateCustomer);
                            // $EmbossName = substr($fingateCustomer->Customer->CompanyName, 0, 25);
                            $documents = $this->sendKYBDocuments($client_id, $fingateCustomer->Customer->CustomerId, $EmbossName);
                        }
                    } else {
                        Log::info('KYC Already Verified for CustomerId: ' . $fingateCustomer->Customer->CustomerId . ' with Status: ' . $fingateCustomer->Customer->KycStatus);

                        $documents = (object) array('ErrorCode' => 'Success', 'ErrorDescription' => 'KYC Already Verified');
                    }
                }
                if($documents->ErrorCode == 'KycFailed') {
                    //$error = array('ErrorCode' => 'KycFailed', 'ErrorDescription' => "Client KYC has been requested, Card will created once client KYC is completed.");
                    return $documents;
                }
                //dd($EmbossName);
                //echo "I am here";
                //dd($documents);

                if (!empty($documents) && $documents->ErrorCode !== 'KycFailed') {
                    if ($is_physical_card && $provider == 'match_move') {
                        $response = $this->assignCard($request->get('card_code'), $fingateCustomer->Customer->CustomerId);

                        $this->log('Create Physical Card Response: ', $response);

                        if (is_string($response) && !is_object($response))
                            $response = json_decode($response);

                        $response->CustomerId = $fingateCustomer->Customer->CustomerId;

                        return $response;
                    } else {
                        $card = $this->createVirtualCard($EmbossName, $fingateCustomer->Customer->CustomerId, $wallet->WalletId, $request);
                        $this->log('Create Card Response: ', $card);
                        return $card;
                    }
                }
            } elseif ($provider == 'e_money' && $fingateCustomer->Customer->KycStatus == 'Success') {
                if ($client->type  == 'individual') {
                    $EmbossName = $fingateCustomer->Customer->Name . ' ' . $fingateCustomer->Customer->MiddleName . ' ' . $fingateCustomer->Customer->Surname;
                } else {

                    $EmbossName = substr($fingateCustomer->Customer->CompanyName, 0, 25);
                }
                $card = $this->createVirtualCard($EmbossName, $fingateCustomer->Customer->CustomerId, $wallet->WalletId, $request);
                $this->log('Create Card Response: ', $card);
                //dd($card);
                return $card;
            } else {
                $error = array('ErrorCode' => 'KycFailed', 'ErrorDescription' => "Client KYC has been requested, Card will created once client KYC is completed.");
                $this->log('Customer KYC is Pending by E-Money: ', $fingateCustomer->Customer->KycStatus);
                return json_decode(json_encode($error));
            }

            $this->log('Wallet Found: ', $wallet);
        }
        return '';
    }

    public function getProofOfAddressFiles($client)
    {

        $identifiers = $this->identifiers[$client->type];

        // Fetch config entries matching the identifiers
        $config = ClientFileConfig::whereIn('identifier', $identifiers)->where('type', $client->type)->get();
        //dd($config);

        // Extract the config IDs
        $configIds = $config->pluck('id')->toArray();

        // Check client type and fetch corresponding files
        if ($client->type === 'individual') {
            $files = ClientIndividualFile::where('client_id', $client->id)
                ->whereIn('file_id', $configIds)
                ->get();
        } elseif ($client->type === 'company') {
            $files = ClientCompanyFile::where('client_id', $client->id)
                ->whereIn('file_id', $configIds)
                ->get();
        } else {
            $files = collect(); // Return empty collection if type is unknown
        }
        // dd($files);
        return $files;
    }

    public function sendKYCDocuments($client_id, $customer_id, $embos_name)
    {
        $client = Client::where('id', $client_id)->first();
        $kyc = $client->clientKYC;
        $documents = ClientKYC::where('client_id', $client_id)->where('compliance_status', 'Verified')->whereNull('director_id')->first();
        //dd($documents);

        if (!empty($documents)) {

            $IdBase64File = '';
            $SelfieBase64File = '';
            $AddressBase64File = '';
            $error = '';
            if (!empty($documents->passport_copy_url)) {

                $url = $documents->passport_copy_url;
                $IdBase64File = base64_encode(file_get_contents($url));
                $pathInfo = pathinfo(parse_url($url, PHP_URL_PATH));
                $IdFileName = $pathInfo['basename'];  // e.g., selfie.jpg

            } else {
                $error = "Passport copy or ID is missing.";
            }
            if (!empty($documents->selfie_photo_url)) {
                $url = $documents->selfie_photo_url;
                $SelfieBase64File = base64_encode(file_get_contents($url));
                $pathInfo = pathinfo(parse_url($url, PHP_URL_PATH));
                $SelfieFileName = $pathInfo['basename'];  // e.g., selfie.jpg

            } else {
                $error = "Selfie picture is missing.";
            }
            $files = $this->getProofOfAddressFiles($client);

            if (!empty($files)) {
                $file = $files->first();
                if (!empty($file->url)) {
                    $url = $file->url;
                    $AddressBase64File = base64_encode(file_get_contents($url));
                    $pathInfo = pathinfo(parse_url($url, PHP_URL_PATH));
                    $AddressFileName = $pathInfo['basename'];  // e.g., selfie.jpg
                } else {
                    $error = "Proof of address document is missing.";
                }
            } else {
                $error = "Proof of address document is missing.";
            }
            if (!empty($error)) {
                $error = array('ErrorCode' => 'KycFailed', 'ErrorDescription' => $error);
                return json_decode(json_encode($error));
            }

            // if( !empty($documents->passport_copy_url)) {
            //     $IdBase64File = base64_encode(file_get_contents($documents->passport_copy_url));
            //    // echo $IdBase64File; die();
            // }

            $payload = array(
                "CustomerId" => $customer_id,
                "SelfieBase64File" => $SelfieBase64File,
                "SelfieFileName" => $SelfieFileName,
                "IdBase64File" => $IdBase64File,
                "IdFileName" => $IdFileName,
                "AddressBase64File" =>  $AddressBase64File,
                "AddressFileName" => $AddressFileName

            );

            //print json_encode($payload); die();
            //dd($documents);
            $response =  $this->uploadAllKycDocuments($payload);
            if (is_string($response) && !is_object($response))
                $response = json_decode($response);
            //dd($response);
            if (!empty($response) && ($response->ErrorCode == 'Success' || $response->ErrorCode == 'KycInProgress')) {
                $kyc_verify =   $this->verifyKYCDocuments($customer_id);
                if (is_string($kyc_verify)) {

                    $kyc_verify = json_decode($kyc_verify);
                    if (!empty($kyc_verify)) {
                        Log::error('KYC Verified');
                        //dd($kyc_verify);
                        return $kyc_verify;
                    }
                }
            }
            //dd($response);
            return $response;
        } else {
            $error = array('ErrorCode' => 'KycFailed', 'ErrorDescription' => "No verified KYC documnets found, Please upload and verify documents.");
            return json_decode(json_encode($error));
        }
    }

    public function verifyKYCDocuments($customer_id, $client_type = 'individual')
    {
        if (!empty($customer_id)) {
            if ($client_type == 'individual')
                return $this->sendApiRequest('/Kyc/ConfirmKycDocuments?confirmKycDocumentCustomerId=' . $customer_id, '', 'PUT');
            else
                return $this->sendApiRequest('/Kyb/ConfirmKybDocuments?confirmKybDocumentCustomerId=' . $customer_id, '', 'PUT');
        }
        return false;
    }

    //KYB Documents

    public function sendKYBDocuments($client_id, $customer_id, $embos_name)
    {

        $client = Client::where('id', $client_id)->first();
        $kyc = $client->clientKYC;
        $documents = ClientKYC::where('client_id', $client_id)
            ->where('compliance_status', 'Verified')
            ->whereNull('director_id')
            ->first();
        if (!empty($documents)) {
            $identifiers = $this->identifiers[$client->type];
            //$error = '';

            $files = $this->getProofOfAddressFiles($client);
            //dd($files);
            $prepared_files = [];
            $error = '';
            $errorArr = [];
            if (!empty($files)) {
                // Convert files to an array keyed by identifier for easy lookup
                $filesByIdentifier = [];
                foreach ($files as $file) {
                    if (!empty($file->clientFileConfig->identifier)) {
                        $filesByIdentifier[$file->clientFileConfig->identifier] = $file;
                    }
                }

                // Loop through required identifiers
                foreach ($identifiers as $requiredIdentifier) {
                    if (isset($filesByIdentifier[$requiredIdentifier])) {
                        $file = $filesByIdentifier[$requiredIdentifier];
                        $url  = $file->url;

                        if (!empty($url)) {
                            $pathinfo = pathinfo(parse_url($url, PHP_URL_PATH));
                            $prepared_files[$requiredIdentifier]['Base64File'] = base64_encode(file_get_contents($url));
                            $prepared_files[$requiredIdentifier]['FileName']   = $pathinfo['basename'] ?? 'unknown';
                        } else {
                            $errorArr[] = "{$requiredIdentifier} ";
                        }
                    } else {
                        $errorArr[] = " {$requiredIdentifier}";
                    }
                }
            } else {
                $error = "Company related files are missing for KYB.";
            }
            if (count($errorArr) > 0) {
                $error = 'Required document`s ' . implode(', ', $errorArr) . ' is missing.';
            }
            // dd($error);
            if (!empty($error)) {
                $error = array('ErrorCode' => 'KycFailed', 'ErrorDescription' => $error);
                return json_decode(json_encode($error));
            }



            $payload = array(
                "CustomerId" => $customer_id,
                "CertificationOfIncorporationBase64File" => $prepared_files['certificate_of_incorporation']['Base64File'],
                "CertificationOfIncorporationFileName" => $prepared_files['certificate_of_incorporation']['FileName'],

                "MemorandumBase64File" => $prepared_files['memorandum_of_association']['Base64File'],
                "MemorandumFileName" => $prepared_files['memorandum_of_association']['FileName'],

                "StatementBase64File" => $prepared_files['bank_statement']['Base64File'],
                "StatementFileName" =>  $prepared_files['bank_statement']['FileName']

            );

            $response =  $this->uploadAllKYBDocuments($payload);
            if (is_string($response) && !is_object($response))
                $response = json_decode($response);
            //dd($response);
            if (!empty($response) && ($response->ErrorCode == 'Success' || $response->ErrorCode == 'KycInProgress')) {
                $kyc_verify =   $this->verifyKYCDocuments($customer_id, $client->type);
                if (is_string($kyc_verify)) {
                    $kyc_verify = json_decode($kyc_verify);
                    if (!empty($kyc_verify)) {
                        //dd($kyc_verify);
                        Log::error('KYB Verified');
                        return $kyc_verify;
                    }
                }
            }
            //dd($response);
            return $response;
        } else {
            $error = array('ErrorCode' => 'KycFailed', 'ErrorDescription' => "Compliance Status not verified.");
            return json_decode(json_encode($error));
        }
    }

    private function validateRequest(Request $request, array $rules)
    {
        return Validator::make($request->all(), $rules);
    }

    private function errorResponse($message, $errors = null, $statusCode = 400)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            //'errors' => $errors
        ], $statusCode);
    }

    private function handleApiError($response, $defaultMessage)
    {
        return $this->errorResponse($defaultMessage . ': ' . ($response->ErrorDescription ?? 'Unknown error'), null, 400);
    }


    public function createCustomer($clientID)
    {
        try {

            $client = Client::find($clientID);
            $personalInfo = $this->getPersonalInfo($client);
            if (!$personalInfo) {
                return $this->errorResponse('Client information is incomplete.', null, 400);
            }
            //dd($personalInfo);
            $customerData = $this->prepareCustomerData($personalInfo, $client);
            $customerData['ProcessorId'] = $this->providerId;
            //dd($customerData);
            $this->log('Create Customer Payload: ', json_encode($customerData));
            $response = $this->createCustomerAPI($customerData, $client->type);
            //dd($response);
            if ($response->IsSucceeded ==  true) {
                $this->saveFingateClient($clientID, $response->CustomerId);
                return $response;
            } else {
                return $response;
            }
            return $this->handleApiError($response, 'Failed to create customer');
        } catch (\Throwable $th) {
            $this->log('Error in createCustomer: ', $th->getMessage());
            return $this->errorResponse('An unexpected error occurred: ' . $th->getMessage());
        }
    }

    private function getPersonalInfo($client)
    {
        $phone_code = '';
        $nationality_code = '';
        $residence_code = '';
        $gender = 'M';

        if ($client->type === 'individual') {
            //dd($client);
            //dd(Country::find($client->individual->country_code_id)->first());
            $phone_country = Country::find($client->individual->country_code_id);
            $residence_country = Country::find($client->individual->residence_country_id);
            $nationality_country = Country::find($client->individual->nationality_id);

            if (!empty($phone_country))
                $phone_code = $phone_country->phonecode;

            if (!empty($nationality_country))
                $nationality_code = $nationality_country->iso;
            if (!empty($residence_country))
                $residence_code = $residence_country->iso;

            if (strtolower($client->individual->gender) == 'male')
                $gender = 'M';
            else
                $gender = 'F';
            return [
                'CustomerId' => $client->id,
                'InternationalPhoneCode' =>  $phone_code,
                'cellPhoneNumber' => $client->individual->mobile_number,
                'name' => $client->name,
                'identityNumber' => $client->individual->passport_number,
                'identityExpiry' => $client->individual->passport_expiry_date,
                'birthDate' => $client->individual->dob,
                'email' => $client->individual->email_address,
                'residenceCountryCode' => $residence_code,
                'nationalityCountryCode' => $nationality_code,
                'gender' => $gender,
                'city' => $client->individual->city,
            ];
        } elseif ($client->type != 'individual') {

            $phone_country = Country::find($client->company->contact->country_code_id);

            if (!empty($phone_country))
                $phone_code = $phone_country->phonecode;

            $nationality_code = '';
            $country_id = $client->company->company_country;
            $country = Country::find($country_id);
            if (!empty($country))
                $nationality_code = $country->iso;

            return [
                'CustomerNumber' => $client->id,
                'CompanyName' => $client->name,
                'CompanyEmbossName' => substr($client->name, 0, 25),
                'email' => $client->company->contact->email_address,
                'InternationalPhoneCode' => $phone_code,
                'cellPhoneNumber' => $client->company->contact->mobile_number,
                'CountryCode' => $nationality_code,
                'nationalityCountryCode' => $nationality_code,
                'gender' => $gender,
                'city' => $client->company->company_city,
                'CompanyFoundationDate' => $client->company->registration_date,
                'CityName' => $client->company->company_city,
                'TownName' => $client->company->company_region,
                'Address' => $client->company->business_reg_street_address,
                'StateCode' => $nationality_code,
                'ZipCode' => $client->company->zip_code,
                'TaxNumber' => $client->company->tax_number,
                'TaxIssuanceCountryCode' => $nationality_code,
                'TaxNumberIssueDate' => $client->company->registration_date,
                'TaxNumberExpiryDate' => $client->company->expiry_date
            ];
        }

        return null;
    }

    private function prepareCustomerData($personalInfo, $client)
    {
        // dd($personalInfo);
        if ($client->type != 'individual')
            return $personalInfo;

        $datetime = (new DateTime())->format('YmdHisv');
        $code = $personalInfo['InternationalPhoneCode'] ?? '';

        // Replace starting "00" with "+"
        if (strpos($code, '00') === 0) {
            $code = '+' . substr($code, 2);
        }
        $code = (string) $code;
        // If it doesn't start with "+", add it
        if ($code !== '' && $code[0] !== '+') {
            $code = '+' . $code;
        }
        //dd($code);

        if (empty($personalInfo['city']))
            $personalInfo['city'] = "None";

        if ($client->type == 'individual') {
            $data =  [
                'CustomerType' => 'I',
                'CustomerNumber' => $personalInfo['CustomerId'],
                'CellPhoneNumber' => $personalInfo['cellPhoneNumber'],
                'Name' => $personalInfo['name'],
                'MiddleName' => '',
                'CompanyName' => '',
                'Surname' => '',
                'Gender' => $personalInfo['gender'],
                'Title' => '',
                'IdentityType' => 'P',
                'IdentityNumber' => $personalInfo['identityNumber'],
                'IdentityExpiryDate' => $personalInfo['identityExpiry'] . "T00:00:00Z",
                'BirthDate' => $personalInfo['birthDate'] . "T00:00:00Z",
                'BirthOfPlace' => $personalInfo['nationalityCountryCode'],
                'Email' => $personalInfo['email'],
                //replace all static data with dynamic
                'InternationalPhoneCode' => $code,
                "IdentityIssueCountryCode" => $personalInfo['nationalityCountryCode'],
                "NationalityCountryCode" => $personalInfo['nationalityCountryCode'],
                'CountryCode' => $personalInfo['residenceCountryCode'], // Placeholder for country code
                'CityName' => $personalInfo['city'],
                'Address' => 'Nill',
                'ZipCode' => '00000',
            ];
        }
        //dd($data);

        return $data;
    }



    private function saveFingateClient($clientID, $customerID)
    {

        $client = FingateClient::where('client_id', $clientID)->first();
        $fingateClient = new FingateClient();
        $fingateClient->client_id = $clientID;
        $fingateClient->customer_id = $customerID;
        $fingateClient->save();
    }

    public function createFindWallet($customerId, $client_id)
    {
        $data = [
            'CustomerId' => $customerId,
            'ProcessorId' => $this->providerId
        ];


        $response = $this->findWalletAPI($data);

        if (isset($response->WalletList[0])) {
            $this->saveFingateWallet($client_id, $customerId, $response->WalletList[0]->WalletId);
        }
        return $response;

        return $this->handleApiError($response, 'Failed to create wallet');
    }

    private function saveFingateWallet($clientId, $customerId, $walletId)
    {
        $wallet = FingateWallet::where('wallet_id', $walletId)->first();
        if (!empty($wallet->id)) {
            $fingateWallet = new FingateWallet();
            $fingateWallet->client_id = $clientId;
            $fingateWallet->wallet_id = $walletId;
            $fingateWallet->customer_id = $customerId;
            $fingateWallet->currency_code = 'USD';
            $fingateWallet->wallet_name = 'Personal Wallet';
            $fingateWallet->save();
        }
    }

    public function createVirtualCard($EmbossName, $customerId, $walletId, $req)
    {

        $datetime = (new DateTime())->format('YmdHisv');
        $cardReferenceNumber = $customerId . '-' . $req->id . '-' . $datetime;


        $data = [
            'KeyId' =>  $this->decryptionKeyId,
            'CardReferenceNumber' => $cardReferenceNumber,
            'WalletId' => $walletId,
            'EmbossName' => $EmbossName,
            'CustomerId' => $customerId,
            // 'ProductId' => '3',
            'ProductId' => $this->productId ?? 3,
            'LimitResetPeriod' => 'N',
            'CardLimit' => '1000',
        ];
        // dd($data);

        $this->log('Create Card Payload : ', $data);

        $response = $this->createVirtualCardAPI($data);
        if (isset($response->CardId)) {
            $card_details = $this->findCardAPI($response->CardId);
            //dd($card_details);
            $client = Client::where('id', $req->client_id)->first();

            $personalInfo = $this->getPersonalInfo($client);
            $this->saveCardDetails($card_details, $data, $customerId, $response, $personalInfo, $walletId, $req);

            // return response()->json([
            //     'success' => true,
            //     'message' => "Card has been created successfully",
            //     "cardId" =>    $response->CardId,
            // ], 200);
            return $card_details;
        }
        $response = json_decode($response);
        return response()->json([
            'success' => false,
            'message' => $response->ErrorDescription,
            "cardId" =>    0,
        ], 400);
    }

    private function saveCardDetails($card_details, $data, $customerID, $response, $personalInfo, $walletID = null, $req = null, $is_physical_card = false)
    {
        DB::beginTransaction();
        try {
            $created_at = Carbon::now('UTC')->format('Y-m-d\TH:i:s.u\Z');
            $cardStatus = 'Locked';
            $cardType   = $card_details->CardType;
            $programeType = "M";

            $cardStatusMapping = [
                "A" => "Active",
                "T" => "Temporary Closed",
                "L" => "Lost",
                "S" => "Stolen",
                "D" => "Damaged",
                "F" => "Fraud",
                "TC" => "Terminated Card",
                "W" => "Waiting For Activation"
            ];

            $cardStatus = $cardStatusMapping[$card_details->CardStatus];

            if ($card_details->CardType == 'P' && !$is_physical_card)
                $cardType = 'Personalised Card';
            else
                $cardType = 'Instant Card';

            if ($response->CardBrand == 'M')
                $programeType = 'Master';
            else
                $programeType = 'Visa';

            $physicalCard = 1;
            if ($card_details->CardPhysicalType == 'V' && !$is_physical_card)
                $physicalCard = 0;

            $first_name = '';
            if (isset($personalInfo['name']))
                $first_name = $personalInfo['name'];

            $country_code = '';
            if (isset($personalInfo['residenceCountryCode']))
                $first_name = $personalInfo['residenceCountryCode'];

            $cardDetail = new CardDetail();
            $cardDetail->key_id = $data["KeyId"];
            $cardDetail->mpin = $data["CardReferenceNumber"];
            $cardDetail->customer_id = $customerID;
            $cardDetail->cardNumber = $card_details->MaskedCardNumber;
            $cardDetail->product_id = $data["ProductId"];
            $cardDetail->limit_reset_period = $data["LimitResetPeriod"];
            $cardDetail->card_limit = $data["CardLimit"];
            $cardDetail->encrypted_card_number =  isset($response->EncryptedCardNumber) ? $response->EncryptedCardNumber : null;
            $cardDetail->provider_type = "match_move";
            $cardDetail->card_id = isset($response->CardId) ? $response->CardId : $card_details->CardId;
            $cardDetail->encrypted_expiry_date_yymm = isset($response->EncryptedExpiryDateYYMM) ? $response->EncryptedExpiryDateYYMM : null;
            $cardDetail->encrypted_cvv2 = isset($response->EncryptedCvv2) ? $response->EncryptedCvv2 : null;
            $cardDetail->cardImage = "";
            $cardDetail->programType = $programeType;
            $cardDetail->cardStatus = $cardStatus;
            $cardDetail->cardHolderName = $card_details->EmbossName;
            $cardDetail->max_balance_limit = $data["CardLimit"];
            $cardDetail->currency = $card_details->WalletCurrencyCode;
            $cardDetail->cType =  $cardType;
            $cardDetail->firstName = $first_name;
            $cardDetail->email = $card_details->Email;
            $cardDetail->mobileNo = $card_details->FullCellPhoneNumber;
            $cardDetail->countryCode = $country_code;
            $cardDetail->wallet_id = $card_details->WalletId;
            $cardDetail->mobile_request_id = ($req !== null) ? $req->id : null;
            $cardDetail->save();

            $assignedCard = new AssignedCard();
            $assignedCard->userName = '';
            $assignedCard->cardHolderName =  $cardDetail->cardHolderName;
            $assignedCard->cardName = '';
            $assignedCard->cardNumber =  $cardDetail->cardNumber;
            $assignedCard->physical =  $physicalCard;
            $assignedCard->programType =   $cardDetail->programType;
            $assignedCard->mpin =  $cardDetail->mpin;
            $assignedCard->status = $cardDetail->cardStatus;
            $assignedCard->provider = $cardDetail->provider_type;
            $assignedCard->cType = $cardDetail->cType;
            $assignedCard->card_created_at = $created_at;
            $assignedCard->save();

            $status = 0;
            if ($cardStatus == 'Active')
                $status = 1;
            $userCardLink = new UserCardLink();
            $userCardLink->client_id = $card_details->CustomerNumber;
            $userCardLink->assigned_card_id =  $assignedCard->id;
            $userCardLink->detail_id = $cardDetail->id;
            $userCardLink->mpin = $cardDetail->mpin;
            $userCardLink->status = $status;
            $userCardLink->hide_balance = 0;
            $userCardLink->created_by = Auth::user()->id;
            $userCardLink->save();

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->log('Error saving card details: ', $e->getMessage());

            return false;
        }
    }

    public function savePhysicalCardDetails($card_details, $customerId, $request)
    {
        $datetime = (new DateTime())->format('YmdHisv');
        $cardReferenceNumber = $customerId . '-' . $request->get('client_id') . '-' . $datetime;

        $data = [
            'KeyId' =>  $this->decryptionKeyId,
            'CardReferenceNumber' => $cardReferenceNumber,
            'CustomerId' => $customerId,
            'ProductId' => $this->productId ?? 3,
            'LimitResetPeriod' => 'N',
            'CardLimit' => '1000',
        ];

        $this->log('Create Card Payload : ', $data);

        $client = Client::where('id', $request->client_id)->first();

        $personalInfo = $this->getPersonalInfo($client);

        return $this->saveCardDetails($card_details, $data, $customerId, $card_details, $personalInfo, null, null, true);
    }

    function log($text = '', $data = '', $isError = true)
    {
        if (is_array($data) || is_object($data)) {
            $dataString = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } else {
            $dataString = (string) $data;
        }

        if ($isError)
            Log::error($text . ' : ' . $dataString);
        else
            Log::info($text . ' : ' . $dataString);
    }

    public function getCardDetails($card)
    {
        $card_id = $card->card_id;
        $key_id = $card->key_id;
        $encrypted_card_number = $card->encrypted_card_number;
        $encrypted_expiry_date_yymm = $card->encrypted_expiry_date_yymm;
        $encrypted_cvv2 = $card->encrypted_cvv2;
        if (!empty($card_id)) {
            $card_details = $this->findCardAPI($card_id);
        }
        return $card_details;
    }

    public function getUnassignedCards()
    {
        return $this->sendApiRequest('/Card/GetUnAssignedNoNameCards', [], false);
    }

    public function assignCard($cardCode, $customerId)
    {
        $data = [
            'CardCode' => $cardCode,
            'CustomerId' => $customerId
        ];

        return $this->sendApiRequest('/Card/AssignNoNameCardToCustomer', $data);
    }
}
