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
use App\Models\UserCardLink;

use Carbon\Carbon;
use League\Flysystem\Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Log;

class BitpaceMerchantApis
{
    private $baseUrl;
    private $callbacksecret;
    private $memberUsername;
    private $mpin;
    private $password;
    private $merchantCode;
    private  $chainanalysis_api_key;
    private  $travel_rules_enabled;

    public function __construct()
    {
        $module = Module::where('name', 'Bitpace')->select('id', 'is_enabled')->first();
        if (!session('bitpaceConfig')) {
            $moduleConfig = ModuleConfiguration::where('module_id', $module->id)->first();
            if ($moduleConfig) {
                session(['bitpaceConfig' => unserialize($moduleConfig->configurations)]);
            } else {
                throw new Exception('No configurations found. Please feed the Bitpace Configurations.');
            }
        }
        //dd($moduleConfig);
        if (env('APP_ENV') == 'production' || env('BITPACE_APP_ENV') == 'production') {
            $this->baseUrl = session('bitpaceConfig')['production_api_merchant_url']['value'];
            $this->merchantCode = session('bitpaceConfig')['production_api_merchant_code']['value'];
            $this->callbacksecret = session('bitpaceConfig')['production_api_merchant_callback_secret']['value'];
            $this->password = session('bitpaceConfig')['production_api_merchant_password']['value'];
            // $this->chainanalysis_api_key = session('bitpaceConfig')['chainanalysis_api_key']['value'];
        } else {
           // print_r(session('bitpaceConfig'));
            $this->baseUrl = session('bitpaceConfig')['sandbox_api_merchant_url']['value'];
            $this->merchantCode = session('bitpaceConfig')['sandbox_api_merchant_code']['value'];
            $this->callbacksecret = session('bitpaceConfig')['sandbox_api_merchant_callback_secret']['value'];
            $this->password = session('bitpaceConfig')['sandbox_api_merchant_password']['value'];
        }

        $this->chainanalysis_api_key = session('bitpaceConfig')['chainanalysis_api_key']['value'];
        $this->travel_rules_enabled = session('bitpaceConfig')['travel_rules_enabled']['value'];

        if ($module->is_enabled) {
            self::login();
        }
    }

    public function isTravelRuleEnabled() {
        return $this->travel_rules_enabled == 1;
    }

    public function getConfigs() {
        return session('bitpaceConfig');
    }

    public function getCallbackSecret()
    {
        return $this->callbacksecret;
    }

    public function login()
    {
        //Log::info($this->password);
        //if (!session('token')) {
        $response = Http::post($this->baseUrl . '/auth/token', [
            'merchant_code' => $this->merchantCode,
            'password' => $this->password
        ]);
        session(['token' => json_decode($response->getBody())->data->token]);
        Log::info("\n\n Token Inside If: " . session(['token']));
        // }
    }
    public function getChainAnalysisInfo($wallet_address)
    {
        $url = 'https://public.chainalysis.com/api/v1/address/' . $wallet_address;
        $result = Http::withHeaders(
            [
                'X-API-Key' => $this->chainanalysis_api_key
            ]
        )->get($url);
        if ($result->getBody()) {
            $result = json_decode($result->getBody());
        }
        return $this->parseResult($result);
    }

    public function getWithdarwPrice($amount,$currency,$network) {
       
        
        $payload = ' {
            "order_type": "WITHDRAW",
            "cryptocurrency": "' . $currency . '",';
        
            $payload .=    ' "cryptocurrency_amount": ' . $amount . ',';
            $payload .=    ' "network": "' . $network . '",';
        

        $payload .=  ' "currency": "EUR",
            "show_fees": true,
            "exclude_withdraw_fee": false
        }';
    
        //echo $payload;
        $payload = json_decode($payload, true);
       
        $data =  $this->getOrderPrice($payload);
        return $data;
    }
    public function createBitpaceAddress($data)
    {

        $endpoint =  '/customer/deposit/address';
        return $this->post($endpoint, $data);
    }
    public function createBitpaceOrder($data)
    {
        $endpoint =  '/orders';
        return $this->post($endpoint, $data);
    }
    public function getOrderPrice($data)
    {
        $endpoint =  '/orders/prices';
        return $this->post($endpoint, $data);
    }
    public function getOrderV2($order_id)
    {
        $endpoint =  '/orders/' . $order_id;
        return $this->get($endpoint);
    }
    public function getCurrencyNetworks($currency_code)
    {
        // print_r($currency_code);
        $endpoint =  '/customer/deposit/networks/' . $currency_code;
        return $this->get($endpoint);
    }
    public function getAccountBalance()
    {

        $endpoint =  '/balance/currency';
        return $this->get($endpoint);
    }

    public function withdrawCoin($payload)
    {
        //$endpoint =  '/orders/coin-withdraw';
        $endpoint = '/orders';
       //echo  json_encode($payload); die();
        return $this->post($endpoint, $payload);
    }

    public function get($endpoint)
    {
        $url = $this->baseUrl . $endpoint;
        $result = Http::withToken(session('token'))->get($url);

        if ($result->getBody()) {
            $result = json_decode($result->getBody());
        }
        return $this->parseResult($result);
    }


    public function post($endpoint, $data)
    {
       
        $url = $this->baseUrl . $endpoint;
        $result = Http::withToken(session('token'))->post($url, $data);

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
}
