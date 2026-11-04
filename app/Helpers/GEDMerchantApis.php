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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
class GEDMerchantApis
{
    private $baseUrl;
    private $accountNumber;
    private $mid;
    private $secret_key;
    private $allowed_fiat_currencies;

    public function __construct()
    {
        if (!session('GEDConfig')) {
            $module = Module::where('name', 'GED')->select('id')->first();
            $moduleConfig = ModuleConfiguration::where('module_id', $module->id)->first();
            //$config = unserialize($moduleConfig->configurations);
            //$config['production_secret_key'] = array('type'=>'text','label'=>'Production Secret Key','value'=> 'xxx');
            //echo serialize($config);
           // print_r($config);
            if ($moduleConfig) {
                session(['GEDConfig' => unserialize($moduleConfig->configurations)]);
            } else {
                throw new Exception('No configurations found. Please feed the GED Configurations.');
            }
        }
        
        if (env('APP_ENV') == 'production') {
            $this->baseUrl = session('GEDConfig')['production_api_base_url']['value'];
            $this->accountNumber = session('GEDConfig')['production_account_number']['value'];
            $this->mid = session('GEDConfig')['production_mid']['value'];
            $this->secret_key = session('GEDConfig')['production_secret_key']['value'];
           
        } else {
          
            $this->baseUrl = session('GEDConfig')['sandbox_api_base_url']['value'];
            $this->accountNumber = session('GEDConfig')['sandbox_account_number']['value'];
            $this->mid = session('GEDConfig')['sandbox_mid']['value'];
            $this->secret_key = session('GEDConfig')['sandbox_secret_key']['value'];
        }
        $this->allowed_fiat_currencies = session('GEDConfig')['allowed_fiat_currencies']['value'];
        //if (!session('token')) {
            //self::login();
        //}
    }

    public function getAllowedFiatCurrencies($flag = false) {
        
        if($flag && !empty($this->allowed_fiat_currencies)) {
          $currencies =   explode(',', $this->allowed_fiat_currencies);
            if(count($currencies) <= 0)
                $currencies =  array($this->allowed_fiat_currencies);
            return $currencies;
        } else {
            return trim($this->allowed_fiat_currencies);
        }

    }

    public function getCustomerExternalId($account_number = '') {
      
        if(!empty($account_number)) {
            Log::info('Account Number: ' . $account_number);
            Log::info('Secret Key: ' . $this->secret_key);
            $external_id = hash_hmac('sha256', $account_number, $this->secret_key);
            $external_id = bin2hex($external_id);
            $external_id = substr($external_id, 0, 50);
            //return $external_id = '46f07bc4bcde8adb674ff75681a724f1fb7f3c3bf6c3e12e82';
            return $external_id;
        }
        return '';

    }
 
    public function getApiAuthKey($account_number,$method = 'GET') {   
        
        if(!empty($account_number)) {
            $external_id = $this->getCustomerExternalId( $account_number );
            Log::info('MID: ' . $this->mid);
            Log::info('External ID: ' . $external_id);
           
            $salt = $this->secret_key . '-' . $this->mid  . '-' . $method  . '-' . $external_id;
            Log::info('Salt: ' . $salt);
            $api_auth_key = hash_hmac('sha256', $salt, $this->secret_key);
            Log::info('api_auth_key: ' . $api_auth_key);
            return $api_auth_key;
        }
        return '';
    }

    public function getCustomerDetails( $account_number ) {

        $external_id = $this->getCustomerExternalId($account_number);
        $endpoint =  '/onboard/customer?external_id=' . $external_id . '&mid='  . $this->mid;
        return $this->get($endpoint,$account_number);
    }

    public function getBlockchainAccount( $credit_account,  $wallet_address = '', $memo = '' ) {
        $account_number = $credit_account->account_number;
        $external_id = $this->getCustomerExternalId($account_number);
        $endpoint =  '/onboard/blockchain_account?external_id=' . $external_id . '&mid='  . $this->mid;
        $address = DB::table('crypto_addresses')->where('account_id',$credit_account->id)->orderBy('id','DESC')->first();
        if(!empty($address->id)) {
            $wallet_address = $address->crypto_address;
        }
        //api call
        $blockchain_account =  $this->get($endpoint,$account_number);
       
        if($blockchain_account->code == 400) {
            $blockchain_account = $this->createBlockchainAccount( $account_number, $wallet_address, $memo);
            if($blockchain_account->code == 200) { 
               //save address for account
               $this->storeCryptoAddress($credit_account->id,$wallet_address,$external_id);
            } else {
                return $blockchain_account;
            }
        }

        return $blockchain_account;
    }
    public function getRedemptionAddress ( $account_number ) {
        $external_id = $this->getCustomerExternalId($account_number);
        
        $endpoint =  '/onboard/redemption_address?external_id=' . $external_id . '&mid='  . $this->mid;
        return $this->get($endpoint,$account_number);
       
    }
    
    public function sendRedumptionUpdate($account_number ,$crypto_transaction_id , $status) {
        
        $external_id = $this->getCustomerExternalId($account_number);
        
        $data = array(
            'external_id' => $external_id,
            'redemption_external_id' => $crypto_transaction_id,
            'redemption_status' => $status

        );
        $endpoint = '/api/callback/redemption_status';
        $response =  $this->post($endpoint, $data, $account_number);
        dd($response);

    }
    function storeCryptoAddress($account_id,$wallet_address,$external_id) {
        // DB::table('crypto_addresses')->insert([
        //     'account_id' => $account_id,
        //     'network'=> 'ETH', 
        //     'crypto_address'=> $wallet_address,
        //     'created_at' => date('Y-m-d H:i:s',time()) 
        // ]);
        DB::table('crypto_addresses')
        ->updateOrInsert(
            ['account_id' => $account_id,'external_id'=>$external_id],
            ['account_id' => $account_id,'crypto_address'=> $wallet_address,'external_id'=>$external_id,
            "network"=>'ETH','created_at'=>DB::raw('CURRENT_TIMESTAMP')]
        );
    }

    public function createBlockchainAccount( $account_number,  $address, $memo ) {
        
        $external_id = $this->getCustomerExternalId($account_number);
        $endpoint =  '/onboard/blockchain_account?external_id=' . $external_id . '&mid='  . $this->mid;
        $data = array(
            "blockchain_address"=> $address,
            "blockchain_id" => "ETH",
            "memo" => $memo,
            "external_id" =>$external_id,
            "mid" => $this->mid
        );

        return $this->post($endpoint, $data, $account_number);
    }
    
    public function getMintExternalID ( $account_number ) {
        if(!empty($account_number)) {
            //Log::info('Account Number: ' . $account_number);
            //Log::info('Secret Key: ' . $this->secret_key);
            $mint_external_id = hash_hmac('sha256', $account_number . time(), $this->secret_key);
            $mint_external_id = bin2hex($mint_external_id);
            $mint_external_id = substr($mint_external_id, 0, 30);
            return $mint_external_id;
        }
        return '';
    }

    public function mint( $account_number,  $cents) {
        
        $external_id = $this->getCustomerExternalId($account_number);
        $mint_external_id = $this->getMintExternalID ( $account_number );
        $endpoint =  '/transactions/mint';
        
        $data = array(
            "amount_in_cents"=> $cents,
            "mint_external_id" => $mint_external_id,
            "product" => 'GED',
            "external_id" =>$external_id,
            "mid" => $this->mid
        );
        Log::info('Mint Data: ' . json_encode($data));
        return $this->post($endpoint, $data, $account_number);
    }
    public function customer($credit_account) {
       
        $ged_customer = $this->getCustomerDetails( $credit_account->account_number );
       
        if($ged_customer->code == 400) {
            $external_id =  $this->getCustomerExternalId($credit_account->account_number);
            
            $client = $credit_account->client;
            //echo $client->type;
            if($client->type == 'individual')
                $info = $credit_account->client->individual;
            $company_name = 'test_comp_48818xx';
            if($client->type == 'company') {
                $info = $credit_account->client->company;
                $company_name =  $client->name;
            }
            
            
            $name =  $client->name;
            $email_address =  $info->email_address;
            $mobile_number =  $info->mobile_number;
            $first_name = $name;
            $last_name = '';
            $middle_name = '';
            if(!empty($name)) {
                $nameArr = explode(' ',$name);
                if(count($nameArr) > 0) {
                    
                    if(isset($nameArr[0]))
                        $first_name = $nameArr[0];
                    if(isset($nameArr[1]))
                        $middle_name = $nameArr[1];
                    if(isset($nameArr[2]))
                        $last_name = $nameArr[2];
                    
                    if(count($nameArr) == 2) {
                        if(isset($nameArr[1])) {
                            $last_name = $nameArr[1];
                            $middle_name  = '';
                        }
                    }
                }
            }
            $data = array(
                'first_name' => $first_name,
                'middle_name' => $middle_name,
                'last_name' => $last_name,
                'email' => $email_address,
                'mobile_no' => $mobile_number,
                'external_id' => $external_id,
                'mid' => $this->mid,
                'company' => $company_name,

            );
            $payload = json_encode($data);
            $ged_customer = $this->createCustomer( $data , $credit_account->account_number);
            return $ged_customer;
        } else {
            return $ged_customer;
        }
    }
    public function createCustomer( $data ,$account_number) {

       $endpoint =  '/onboard/customer';
       return $this->post($endpoint,$data, $account_number);
    }


    public function get( $endpoint , $account_number) {
        
        $api_auth_key = $this->getApiAuthKey($account_number, 'GET');
        $url = $this->baseUrl . $endpoint;
        $result = Http::withHeaders(['api-auth-key' =>  $api_auth_key])->get($url);
        
        if($result->getBody())   {
            $result = json_decode($result->getBody());
        }
        return $this->parseResult($result);
    }


    public function post( $endpoint, $data , $account_number) {
        $url = $this->baseUrl . $endpoint;
        Log::info('Post Url: ' . $url);
        $api_auth_key = $this->getApiAuthKey($account_number, 'POST');
        $result = Http::withHeaders(['api-auth-key' =>  $api_auth_key])->post($url,$data);
        if($result->getBody())   {
            $result = json_decode($result->getBody());
            
        }
        
        return $this->parseResult($result);
   }
   public function parseResult( $result ) { 
        if (!$result) {
            throw new Exception('Something went wrong, please try again later.');
        } elseif (!empty($result->data)) {
            return $result->data;
        } else {
            return $result;
        }

   }
   public function storeApiResponseData($transaction_id ,$data , $crypto_system) {
       
        DB::table('crypto_transactions_log')->insert([
            'transaction_id' => $transaction_id,
            'details'=> $data, 
            'crypto_system'=> $crypto_system,
            'source'=> 'admin', 
            'date_created' => date('Y-m-d H:i:s',time()) 
        ]);
    }
}
