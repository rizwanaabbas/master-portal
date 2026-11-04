<?php

namespace App\Helpers;

use App\Models\Currency;
use Illuminate\Support\Facades\Http;
use League\Flysystem\Exception;
use App\Models\Module;
use App\Models\ModuleConfiguration;
use App\Models\OnboardingFeeRecords;

class OnboardingFeeHelper

{

    private  $baseUrl;
    private  $apikey;
    private  $secretKey;
    private  $description;

    public function __construct()
    {
        if (!session('onboardingFeeConfig')) {
            $module = Module::where('name', 'OnboardingFee')->select('id')->first();
            $moduleConfig = ModuleConfiguration::where('module_id', $module->id)->first();

            if ($moduleConfig) {
                session(['onboardingFeeConfig' => unserialize($moduleConfig->configurations)]);
            } else {
                throw new Exception('No configurations found. Please feed the OnboardingFee Configurations.');
            }
        }
        $this->baseUrl = session('onboardingFeeConfig')['base_url']['value'];
        $this->apikey = session('onboardingFeeConfig')['api_key']['value'];
        $this->secretKey = session('onboardingFeeConfig')['secret_key']['value'];
        $this->description = session('onboardingFeeConfig')['description']['value'];
    }

    private function sendRequest($url, $body)
    {
        $response = Http::withOptions([
            'verify' => false,
        ])->withHeaders([
            'accept' => 'text/plain',
            'Language' => 'en',
            'ApiKey' => $this->apikey,
            'SecretKey' => $this->secretKey,
            'Content-Type' => 'application/json'
        ])->post($this->baseUrl . $url, $body);

        $result = json_decode($response->getBody());

        if (!$result) {
            throw new Exception('Something went wrong, please try again later.');
        } elseif ($response->status() == '200') {
            return $result;
        } elseif ($response->status() == '400' || $response->status() == '401') {
            throw new Exception($result->ErrorDescription ?? 'Unauthorized');
        } else {
            throw new Exception('Something went wrong, please try again later.');
        }
    }

    public function createPaymentLink($client, $email, $currencyID)
    {
        $currency = Currency::find($currencyID);
        $orderID = Common::generateUniqueString(10);
        $amount = $client->type == "individual" ? $currency->onboarding_fee_individual : $currency->onboarding_fee_company;

        $body = [
            "OrderId" =>  $orderID,
            "CustomerEmail" => $email,
            "CustomerId" => $client->id,
            "Description" => $this->description,
            "TransactionAmount" => $amount,
            "CurrencyCode" => $currency->short,
            "HasExpiryDate" => "false"
        ];

        $result = $this->sendRequest("/paylink/integrationapi/v1.0/PaymentLink/CreatePaymentLink", $body);

        $onboardingFee = OnboardingFeeRecords::where("client_id", $client->id)->first() ?? new OnboardingFeeRecords();

        $onboardingFee->client_id = $client->id;
        $onboardingFee->order_id = $orderID;
        $onboardingFee->transaction_amount = $amount;
        $onboardingFee->currency_code = $currency->short;
        $onboardingFee->Payment_link_url = $result->PaymentLinkUrl;
        $onboardingFee->payment_link_token = $result->PaymentLinkToken;
        $onboardingFee->transaction_status = null;
        $onboardingFee->save();

        return $result;
    }

    public function findPaymentLinkTransaction($clientID)
    {
        $onboardingFee = OnboardingFeeRecords::where("client_id", $clientID)->first();

        $body = [
            "OrderId" =>  $onboardingFee->order_id,
            "PaymentLinkToken" => $onboardingFee->payment_link_token
        ];

        $result = $this->sendRequest("/paylink/integrationapi/v1.0/PaymentLink/FindPaymentLinkTransaction", $body);
        if ($result->TransactionStatus) {

            $onboardingFee->transaction_status = $result->TransactionStatus;
            $onboardingFee->reference_number = $result->ReferenceNumber;
            $onboardingFee->is_transaction_succeeded = $result->IsTransactionSucceeded;
            $onboardingFee->transaction_error_description = $result->TransactionErrorDescription;
            $onboardingFee->error_description = $result->ErrorDescription;
            $onboardingFee->transaction_date = \DateTime::createFromFormat('YmdHis', $result->TransactionDateTime);
            $onboardingFee->save();
        }

        return $result;
    }

    public function onboardingFeeRefundPayment($clientID)
    {
        $onboardingFee = OnboardingFeeRecords::where("client_id", $clientID)->first();

        $body = [
            "ReferenceNumber" => $onboardingFee->reference_number,
            "OrderId" =>  $onboardingFee->order_id
        ];

        $result = $this->sendRequest("/paylink/integrationapi/v1.0/Vpos/ReversePayment", $body);

        if ($result->IsSucceeded) {
            $onboardingFee->transaction_status = "Refunded";
            $onboardingFee->save();
        } elseif (!$result->IsSucceeded) {
            throw new Exception($result->ErrorDescription);
        }

        return $result;
    }

    public function searchTransactionByClientID($clientID)
    {
        $body = [
            "CustomerId" => $clientID
        ];

        return $this->sendRequest("/paylink/integrationapi/v1.0/Report/SearchTransaction", $body);
    }
}
