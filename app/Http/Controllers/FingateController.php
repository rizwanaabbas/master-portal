<?php

namespace App\Http\Controllers;

use App\Helpers\CardHelper;
use App\Helpers\FingateHelper;
use App\Models\CardDetail;
use App\Models\Client;
use App\Models\Country;
use App\Models\FingateClient;
use App\Models\FingateWallet;
use DateTime;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class FingateController extends Controller
{
    public function storeCard(Request $request)
    {

        //Log::info('--------------------------------------store function-------------------------------------');

        $validation = $this->validateRequest($request, ['client_id' => 'required']);
        if ($validation->fails()) {
            return $this->errorResponse('Validation error', $validation->errors(), config('api.httpCodes.data-error'));
        }

        $fingateCustomer =  $this->findFingateCustomer($request->input('client_id'));

        //return $this->createCustomer($request->input('client_id'));
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

    public function findFingateCustomer($client_id)
    {
        $fingateHelper = new FingateHelper();
        $response = $fingateHelper->findFingateCustomer($client_id);
    }

    public function getWalletBalance($provider)
    {
        $fingateHelper = new FingateHelper($provider);
    
        
        $response = $fingateHelper->getWalletBalanceAPI();
        
        $balance = 0;
        $currency = 'USD';
        if($provider == 'e_money')
            $currency = 'EUR';

        if (!empty($response->WalletBalance)) {
            $balance = $response->WalletBalance;
            $currency = $response->CurrencyCode;
        }
        return response()->json([
            'balance' => $balance,
            'currency' => $currency,

        ], 200);
    }

    public function createCustomer($clientID)
    {
        try {
            // $fingateClient = FingateClient::where('client_id', $clientID)->first();
            // if ($fingateClient) {
            //     return $this->errorResponse('Client Already Exists', null, 404);
            // }

            $client = Client::find($clientID);
            $personalInfo = $this->getPersonalInfo($client);

            if (!$personalInfo) {
                return $this->errorResponse('Client information is incomplete.', null, 400);
            }

            $customerData = $this->prepareCustomerData($personalInfo);
            $fingateHelper = new FingateHelper();
            $response = $fingateHelper->createCustomerAPI($customerData);

            if (isset($response->CustomerId)) {
                $this->saveFingateClient($clientID, $response->CustomerId);
                return $this->createWallet($client, $personalInfo, $response->CustomerId);
            }

            return $this->handleApiError($response, 'Failed to create customer');
        } catch (\Throwable $th) {
            Log::error('Error in createCustomer: ' . $th->getMessage());
            return $this->errorResponse('An unexpected error occurred: ' . $th->getMessage());
        }
    }

    private function getPersonalInfo($client)
    {
        if ($client->type === 'individual') {
            return [
                'CustomerId' => $client->id,
                'InternationalPhoneCode' => Country::find($client->individual->country_code_id)->phonecode,
                'cellPhoneNumber' => $client->individual->mobile_number,
                'name' => $client->name,
                'identityNumber' => $client->individual->passport_number,
                'identityExpiry' => $client->company->contact->passport_expiry,
                'birthDate' => $client->individual->dob,
                'email' => $client->individual->email_address,
                'countryCode' => Country::find($client->individual->residence_country_id),
                'gender' => $client->individual->gender,
            ];
        } elseif ($client->type === 'company') {
            return [
                'CustomerId' => $client->id,
                'InternationalPhoneCode' => Country::find($client->company->contact->country_code_id)->phonecode,
                'cellPhoneNumber' => $client->company->contact->mobile_number,
                'name' => $client->company->contact->name,
                'identityNumber' => $client->company->contact->passport_number,
                'identityExpiry' => $client->company->contact->passport_expiry,
                'birthDate' => $client->company->contact->dob,
                'email' => $client->company->contact->email_address,
                'countryCode' => Country::find($client->company->contact->residence_country_id),
                'gender' => $client->company->contact->gender,
            ];
        }

        return null;
    }

    private function prepareCustomerData($personalInfo)
    {
        $datetime = (new DateTime())->format('YmdHisv');
        $data =  [
            'CustomerType' => 'I',
            'CustomerNumber' => $personalInfo['CustomerId'],
            'CellPhoneNumber' => $personalInfo['cellPhoneNumber'],
            'CompanyName' => '',
            'Name' => $personalInfo['name'],
            'MiddleName' => '',
            'CompanyName' => '',
            'Surname' => '',
            'Gender' => '',
            'Title' => '',
            'IdentityType' => 'P',
            'IdentityNumber' => $personalInfo['identityNumber'],

            'IdentityExpiryDate' => $personalInfo['identityExpiry'] . "T00:00:00Z",

            'BirthDate' => $personalInfo['birthDate'] . "T00:00:00Z",
            'BirthOfPlace' => 'Pombia',
            'Email' => $personalInfo['email'],
            //replace all static data with dynamic
            'InternationalPhoneCode' => '1',
            "IdentityIssueCountryCode" => "IT",
            "NationalityCountryCode" => "IT",
            'CountryCode' => 'IT', // Placeholder for country code
            'CityName' => 'Pombia',
            'Address' => 'Emirates Towers',
            'ZipCode' => '28050',
        ];

        $this->deleteCustomer($personalInfo['email']);
        // $json = '{
        //     "CustomerType": "I",
        //     "CustomerNumber": "CUST'.$datetime.'",
        //     "InternationalPhoneCode": "39",
        //     "CellPhoneNumber": "5001111111",
        //     "CompanyName": "",
        //     "Name": "Selami",
        //     "MiddleName": "",
        //     "Surname": "AKPINAR",
        //     "Gender": "M",
        //     "Title": "Mr",
        //     "IdentityType": "P",
        //     "IdentityNumber": "20240809",
        //     "IdentityIssueCountryCode": "IT",
        //     "IdentityExpiryDate": "2035-01-01T00:00:00.000Z",
        //     "NationalityCountryCode": "IT",
        //     "BirthDate": "1980-08-09T07:25:13.882Z",
        //     "BirthOfPlace": "Turkiye",
        //     "Email": "selami.akpinar@icloud.com",
        //     "CountryCode": "IT",
        //     "StateCode": "",
        //     "CityName": "Pombia",
        //     "TownName": "",
        //     "Address": "Via Donnalbina 129",
        //     "ZipCode": "28050"
        //     }';
        //    $data =  json_decode($json,true);
        // Log::error('Customer Payload: ' . json_encode($data));
        return $data;
    }
    /*
{
  "CustomerType": "I",
  "CustomerNumber": "1",
  "InternationalPhoneCode": "39",
  "CellPhoneNumber": "5001111111",
  "CompanyName": "",
  "Name": "Selami",
  "MiddleName": "",
  "Surname": "AKPINAR",
  "Gender": "M",
  "Title": "Mr",
  "IdentityType": "P",
  "IdentityNumber": "20240809",
  "IdentityIssueCountryCode": "IT",
  "IdentityExpiryDate": "2035-01-01T00:00:00.000Z",
  "NationalityCountryCode": "IT",
  "BirthDate": "1980-08-09T07:25:13.882Z",
  "BirthOfPlace": "Turkiye",
  "Email": "selami.akpinar@icloud.com",
  "CountryCode": "IT",
  "StateCode": "",
  "CityName": "Pombia",
  "TownName": "",
  "Address": "Via Donnalbina 129",
  "ZipCode": "28050"
}

*/
    private function saveFingateClient($clientID, $customerID)
    {

        $client = FingateClient::where('client_id', $clientID)->first();
        // if ($client) {
        //     $client->delete(); // Ensures only 1 record is deleted
        // }

        $fingateClient = new FingateClient();

        $fingateClient->client_id = $clientID;
        $fingateClient->customer_id = $customerID;
        $fingateClient->save();
    }

    public function createWallet($client, $personalInfo, $customerID)
    {
        $data = [
            'CustomerId' => $customerID,
            'CurrencyCode' => 'USD',
            'WalletName' => $personalInfo['name']
        ];

        $fingateHelper = new FingateHelper();


        // Log::info('Wallet Payload: =>'. json_encode($data));

        $response = $fingateHelper->createWalletAPI($data);
        // Log::info('Wallet Response: => '. json_encode($response));


        if (isset($response->WalletId)) {
            $this->saveFingateWallet($client->id, $customerID, $response->WalletId);
            return $this->createVirtualCard($client, $personalInfo, $customerID, $response->WalletId);
        }

        return $this->handleApiError($response, 'Failed to create wallet');
    }

    private function saveFingateWallet($clientID, $customerID, $walletID)
    {
        $fingateWallet = new FingateWallet();
        $fingateWallet->client_id = $clientID;
        $fingateWallet->wallet_id = $walletID;
        $fingateWallet->customer_id = $customerID;
        $fingateWallet->currency_code = 'USD';
        $fingateWallet->wallet_name = 'Personal Wallet';
        $fingateWallet->save();
    }

    public function createVirtualCard($client, $personalInfo, $customerID, $walletID)
    {
        $datetime = (new DateTime())->format('YmdHisv');
        $cardReferenceNumber = "CRN$datetime";

        $data = [
            'KeyId' => '1',
            'CardReferenceNumber' => $cardReferenceNumber,
            'WalletId' => $walletID,
            'EmbossName' => $personalInfo['name'],
            'CustomerId' => $customerID,
            'ProductId' => '8',
            'LimitResetPeriod' => 'D',
            'LimitUsageBeginDateTime' => '2024-12-01T09:00:00Z',
            'LimitUsageEndDateTime' => '2024-12-31T23:59:59Z',
            'CardLimit' => '100',
        ];

        $fingateHelper = new FingateHelper();
        $response = $fingateHelper->createVirtualCardAPI($data);

        if (isset($response->CardId)) {
            $this->saveCardDetails($data, $customerID, $response, $personalInfo, $walletID,$client->type);
            return response()->json([
                'success' => true,
                'message' => $response->EncryptedCardNumber,
            ], 200);
        }

        return $this->handleApiError($response, 'Failed to create virtual card');
    }

    private function saveCardDetails($data, $customerID, $response, $personalInfo, $walletID, $client_type)
    {
        $cardDetail = new CardDetail();

        $cardDetail->key_id = $data["KeyId"];
        $cardDetail->card_reference_number = $data["CardReferenceNumber"];
        $cardDetail->customer_id = $customerID;
        $cardDetail->product_id = $data["ProductId"];
        $cardDetail->limit_reset_period = $data["LimitResetPeriod"];
        $cardDetail->limit_usage_begin_date_time = $data["LimitUsageBeginDateTime"];
        $cardDetail->limit_usage_end_date_time = $data["LimitUsageEndDateTime"];
        $cardDetail->card_limit = $data["CardLimit"];
        $cardDetail->encrypted_card_number =  $response->EncryptedCardNumber;
        $cardDetail->card_token_number = $response->CardTokenNumber;
        $cardDetail->provider_type = "fingate";
        $cardDetail->card_id = $response->CardId;
        $cardDetail->encrypted_expiry_date_yymm = $response->EncryptedExpiryDateYYMM;
        $cardDetail->encrypted_cvv2 = $response->EncryptedCvv2;
        $cardDetail->cardImage = "https://viacartetest.s3.amazonaws.com/cardimage/card_image_1657657717960";
        $cardDetail->programType = $response->CardBrand;
        $cardDetail->cardStatus = "Active";
        $cardDetail->cardHolderName = $personalInfo['name'];
        $cardDetail->max_balance_limit = $data["CardLimit"];
        $cardDetail->currency = "USD";
        $cardDetail->cType = "Personalised Card";
        $cardDetail->firstName = $personalInfo['name'];
        $cardDetail->email = $personalInfo['email'];
        $cardDetail->mobileNo = $personalInfo['cellPhoneNumber'];
        $cardDetail->countryCode = "US";
        $cardDetail->dateOfBirth =  $personalInfo['birthDate'];
        $cardDetail->callingCode = "1";
        $cardDetail->gender = $personalInfo['gender'];
        $cardDetail->city = "Alberta";
        $cardDetail->address = "Emirates Towers";
        $cardDetail->postalCode = "10001";
        $cardDetail->wallet_id = $walletID;
        $cardDetail->save();
        //dd($cardDetail);
        $fingateHelper = new FingateHelper();
        $result = $finGateHelper->verifyKYCDocuments($customerID, $client_type);
        //dd($result);
       // $result_json =  $result;
        $cardDetail->kyc_remark =   $result;
        $cardDetail->save();
    }

    public function deleteCustomer($email)
    {
        $fingateHelper = new FingateHelper();
        $response = $fingateHelper->deleteCustomerByEmail($email);

        // if (isset($response)) {
        //     return response()->json([
        //         'success' => true,
        //         'message' => $response,
        //     ], 200);
        // }

        // return $this->handleApiError($response, 'Failed to find virtual card');
    }

    private function handleApiError($response, $defaultMessage)
    {
        // dd($response);
        $responseArray = json_decode($response, true);
        return $this->errorResponse($defaultMessage . ': ' . ($responseArray['ErrorDescription'] ?? 'Unknown error'), null, 400);
    }

    public function findCard(Request $request)
    {

        $validation = $this->validateRequest($request, ['card_id' => 'required']);
        if ($validation->fails()) {
            return $this->errorResponse('Validation error', $validation->errors(), config('api.httpCodes.data-error'));
        }

        $fingateHelper = new FingateHelper();
        $response = $fingateHelper->findCardAPI($request->input('card_id'));

        if (isset($response->CardId)) {
            return response()->json([
                'success' => true,
                'message' => $response,
            ], 200);
        }

        return $this->handleApiError($response, 'Failed to find virtual card');
    }

    public function runTransactionFingate()
    {
        try {
            $exitCode = Artisan::call('transactions:fetch-fingate');
            if ($exitCode === 0) {
                return response()->json([
                    'success' => true,
                    'message' => 'Fingate transactions fetched successfully.',
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to fetch Fingate transactions.'
                ], 500);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Unexpected error: ' . $th->getMessage(),
            ], 500);
        }
    }
    public function listCardProviders() {
         $fingateHelper = new FingateHelper();
         $providers = $fingateHelper->getCardProviders();
         
         //dd($providers->ParameterList);
          if (!empty($providers->ParameterList)) {
            return response()->json([
                'success' => true,
                'data' =>$providers->ParameterList,
                'message' => 'Found Card Providers list.'
            ], 200);
        } else {
            return response()->json([
                    'success' => false,
                    'message' => 'Failed to fetch Fingate transactions.'
            ], 500);
        }
         //dd($providers);
    }

    public function getCardBalance($card)
    {
        $card_balance = 0;

        if ($card->provider_type == 'match_move') {
            $fingateHelper = new FingateHelper();

            $card_details = $fingateHelper->getCardDetails($card);
            
            $card_balance = $card_details->CardBalance;
        } elseif ($card->provider_type == 'vista_money' || $card->provider_type == null) {

            $cardHelper = new CardHelper();

            $card_balance = $cardHelper->getCardBalance($card->mpin);
        }
        return $card_balance;
    }
}
