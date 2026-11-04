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

use League\Flysystem\Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class VistaMoneyMemberApis
{
    private $configurations;
    private $baseUrl;
    private $username;
    private $password;
    private $merchantId;

    public function __construct($direct)
    {
        if (!$direct) {
            if (!session('vistaMoneyMemberConfig') && !session('noConfigData')) {

                $card = UserCardLink::where('user_id', Auth::user()->id)->first();
                $module = Module::where('name', 'VistaMoney')->select('id')->first();
                $moduleConfig = ModuleConfiguration::where('module_id', $module->id)->first();

                if ($card && $moduleConfig) {
                    $this->configurations = unserialize($moduleConfig->configurations);
                    $this->configurations['username'] = $card->username;
                    $this->configurations['password'] = $card->password;
                    session(['noConfigData' => false]);
                } else {
                    session(['noConfigData' => true]);
                    throw new Exception('Something went wrong, please try again later. If the issue persists, then please contact with the Operations Team.');
                }

                session(['vistaMoneyMemberConfig' => $this->configurations]);
            } else {
                $this->configurations = session('vistaMoneyMemberConfig');
            }

            if (env('APP_ENV') == 'production') {
                $this->baseUrl = $this->configurations['production_api_url'];
                $this->merchantId = $this->configurations['production_merchant_id'];
            } else {
                $this->baseUrl = $this->configurations['sandbox_api_url'];
                $this->merchantId = $this->configurations['sandbox_merchant_id'];
            }

            $this->username = $this->configurations['username'];
            $this->password = $this->configurations['password'];

            if (!session('x-signature')) {
                self::login();
            }
        }
    }

    public function login()
    {
        if (!session('x-signature')) {
            $response = Http::post($this->baseUrl . '/user-login', [
                'username' => $this->username,
                'password' => $this->password
            ]);

            $response = json_decode($response->getBody());

            if ($response->code == '200') {
                session(['x-signature' => $response->token]);
            } else {
                throw new Exception('Invalid Credentials! Please contact the operations team to generate new Credentials for you.');
            }
        }
    }

    public function getCardDetails($username)
    {
        if (!session('card-details')) {
            $result = Http::withHeaders(['x-signature' => session('x-signature')])->get($this->baseUrl . '/get-card-details/' . $username);

            $result = json_decode($result->getBody());

            if ($result->code == '200') {
                session(['card-details' => $result->response]);
                return $result->response;
            } else {
                throw new Exception('Something went wrong, please try again later.');
            }
        } else {
            return session('card-details');
        }
    }

    public function getCardBalance()
    {
        $result = Http::withHeaders(['x-signature' => session('x-signature')])->get($this->baseUrl . '/card-balance/' . session('card-details')->mpin);

        $result = json_decode($result->getBody());

        if ($result->code == '200') {
            return $result->response;
        } else {
            return [];
        }
    }

    public function blockUnblockCard()
    {
        $result = Http::withHeaders(['x-signature' => session('x-signature')])->post($this->baseUrl . '/cardwallet-blockunblock/card/' . (session('card-details')->status == 'Active' ? 'block' : 'unblock') . '/' . session('card-details')->userName, [
            'merchant_id' => $this->merchantId
        ]);

        $result = json_decode($result->getBody());

        if ($result->code == '200') {
            return $result;
        } else {
            throw new Exception('Something went wrong, please try again later.');
        }
    }

    public function changePassword($password, $retype_password)
    {
        $result = Http::withHeaders(['x-signature' => session('x-signature')])->post($this->baseUrl . '/update-password', [
            'username' => session('card-details')->userName,
            'password' => $password,
            'retype_password' => $retype_password
        ]);

        $result = json_decode($result->getBody());

        return $result;
    }

    public function changePin($pin, $retype_pin)
    {
        $result = Http::withHeaders(['x-signature' => session('x-signature')])->post($this->baseUrl . '/setPin', [
            'mpin' => session('card-details')->mpin,
            'pin' => $pin,
            'confirmPin' => $retype_pin,
            'merchant_id' => $this->merchantId
        ]);

        $result = json_decode($result->getBody());

        if ($result->code == '200') {
            return $result;
        } elseif ($result->code == '400') {
            throw new Exception($result->message[0]);
        } else {
            throw new Exception('Something went wrong, please try again later.');
        }
    }

    public function getCardStatement($startDate, $endDate, $username, $mpin)
    {
        $result = Http::withHeaders(['x-signature' => session('x-signature')])->post($this->baseUrl . '/card-statement1', [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'username' => $username,
            'mpin' => $mpin,
            'merchant_id' => $this->merchantId
        ]);

        $result = json_decode($result->getBody());

        if ($result->code == '200') {
            return $result->response;
        } elseif ($result->code == '400') {
            throw new Exception($result->message);
        } else {
            throw new Exception('Something went wrong, please try again later.');
        }
    }

    public function getCountries($username, $password)
    {
        $response = Http::post('https://uat-wwgc.herokuapp.com/api/user-login', [
            'username' => $username,
            'password' => $password
        ]);

        $response = json_decode($response->getBody());

        if ($response->code == '200') {
            $result = Http::withHeaders(['x-signature' => $response->token])->get('https://uat-wwgc.herokuapp.com/api/get-country-codes1');
            $result = json_decode($result->getBody());

            return $result->response;
        } else {
            throw new Exception('Invalid Credentials! Please contact the operations team to generate new Credentials for you.');
        }
    }
}
