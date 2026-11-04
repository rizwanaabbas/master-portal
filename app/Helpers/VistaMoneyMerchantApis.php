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

class VistaMoneyMerchantApis
{
    private $baseUrl;
    private $merchantUsername;
    private $memberUsername;
    private $mpin;
    private $password;
    private $merchantId;

    public function __construct($isClientRequest = false, $linkedCard = null, $mpin = null, $assignedCard = null)
    {
        if (!session('vistaMoneyConfig')) {
            $module = Module::where('name', 'VistaMoney')->select('id')->first();
            $moduleConfig = ModuleConfiguration::where('module_id', $module->id)->first();

            if ($moduleConfig) {
                session(['vistaMoneyConfig' => unserialize($moduleConfig->configurations)]);
            } else {
                throw new Exception('No configurations found. Please feed the VistaMoney Configurations.');
            }
        }

        if ($isClientRequest) {
            if ($linkedCard) {
                $this->memberUsername = $linkedCard->cardDetails->userName;
                $this->mpin = $linkedCard->mpin;
            } elseif ($assignedCard) {
                $this->memberUsername = $assignedCard->userName;
                $this->mpin = $assignedCard->mpin;
            } else {
                $linkedCards = CardHelper::getAllClientsCardsByUser(Auth::user());
                //dd($linkedCards);
                if (!$linkedCards) {
                    throw new Exception('There is no card registered against this client.');
                }

                $this->memberUsername = $linkedCards[0]->cardDetails->userName;
                $this->mpin = $linkedCards[0]->cardDetails->mpin;
            }
        }

        if ($mpin) {
            $this->mpin = $mpin;
        }

        if (env('APP_ENV') == 'production') {
            $this->baseUrl = session('vistaMoneyConfig')['production_api_url']['value'];
            $this->merchantId = session('vistaMoneyConfig')['production_merchant_id']['value'];
            $this->merchantUsername = session('vistaMoneyConfig')['production_merchant_username']['value'];
            $this->password = session('vistaMoneyConfig')['production_merchant_password']['value'];
        } else {
            $this->baseUrl = session('vistaMoneyConfig')['sandbox_api_url']['value'];
            $this->merchantId = session('vistaMoneyConfig')['sandbox_merchant_id']['value'];
            $this->merchantUsername = session('vistaMoneyConfig')['sandbox_merchant_username']['value'];
            $this->password = session('vistaMoneyConfig')['sandbox_merchant_password']['value'];
        }

        if (!session('token')) {
            self::login();
        }
    }

    public function login()
    {
        if (!session('token')) {
            $response = Http::post($this->baseUrl . '/get-authorised', [
                'username' => $this->merchantUsername,
                'password' => $this->password
            ]);

            session(['token' => json_decode($response->getBody())->token]);
        }
    }

    public function getCardUsers($retried = false)
    {
        if (session('next_page_url')) {
            $url = session('next_page_url');
        } else {
            $url = $this->baseUrl . '/vcard/get-users/' . $this->merchantId;
        }

        $result = Http::withToken(session('token'))->get($url);

        $result = json_decode($result->getBody());

        $retry = $this->handleUnauthenticated($result, function ($retried) {
            return $this->getCardUsers($retried);
        }, $retried);

        if ($retry) return $retry;

        if (!$result) {
            throw new Exception('Something went wrong, please try again later.');
        } elseif ($result->code == '200') {
            if ($result->response->next_page_url) {
                session(['next_page_url' => $result->response->next_page_url]);
            }
            return $result->response;
        } else {
            return [];
        }
    }

    public function getAssignedCards($retried = false)
    {
        if (session('cards_next_page_url')) {
            $url = session('cards_next_page_url');
        } else {
            $url = $this->baseUrl . '/vcard/get-card-stats';
        }

        $result = Http::withToken(session('token'))->get($url);

        $result = json_decode($result->getBody());

        $retry = $this->handleUnauthenticated($result, function ($retried) {
            return $this->getAssignedCards($retried);
        }, $retried);

        if ($retry) return $retry;

        if (!$result) {
            throw new Exception('Something went wrong, please try again later.');
        } elseif ($result->code == '200') {
            if ($result->response->cardlist->next_page_url) {
                session(['cards_next_page_url' => $result->response->cardlist->next_page_url]);
            }
            return $result->response->cardlist;
        } else {
            throw new Exception('Something went wrong, please try again later.');
        }
    }

    public function getCardsByUsername($username, $retried = false)
    {
        if (!$username) {
            $username = $this->memberUsername;
        }

        $result = Http::withToken(session('token'))->get($this->baseUrl . '/vcard/cardfunctions/find-cards/' . $username);

        $result = json_decode($result->getBody());

        $retry = $this->handleUnauthenticated($result, function ($retried) use ($username) {
            return $this->getCardsByUsername($username, $retried);
        }, $retried);

        if ($retry) return $retry;

        if (!$result) {
            throw new Exception('Something went wrong, please try again later.');
        } elseif ($result->code == '200') {
            if (count($result->response) > 0) {
                //
            } else {
                throw new Exception('No Details found against the username (' . $this->memberUsername . ').');
            }
        } else {
            throw new Exception('Something went wrong, please try again later.');
        }
    }

    public function getCardDetails($mpin, $retried = false)
    {
        if (!$mpin) {
            $mpin = $this->mpin;
        }

        $result = Http::withToken(session('token'))->post($this->baseUrl . '/vcard/find-card-details', ['mpin' => $mpin]);

        $result = json_decode($result->getBody());
        //dd($result);
        $retry = $this->handleUnauthenticated($result, function ($retried) use ($mpin) {
            return $this->getCardDetails($mpin, $retried);
        }, $retried);

        if ($retry) return $retry;

        if (!$result) {
            throw new Exception('Something went wrong, please try again later.');
        } elseif ($result->code == '200') {
            return $result->response;
        } else {
            throw new Exception('Something went wrong, please try again later.');
        }
    }

    public function blockCard($retried = false)
    {
        $result = Http::withToken(session('token'))->post($this->baseUrl . '/vcard/cardfunctions/cardwallet-blockunblock/card/block/' . $this->memberUsername, [
            'mpin' => $this->mpin,
            'merchant_id' => $this->merchantId
        ]);

        $result = json_decode($result->getBody());

        $retry = $this->handleUnauthenticated($result, function ($retried) {
            return $this->blockCard($retried);
        }, $retried);

        if ($retry) return $retry;

        if (!$result) {
            throw new Exception('Something went wrong, please try again later.');
        } elseif ($result->code == '200') {
            return $result;
        } elseif ($result->code == '400') {
            throw new Exception($result->message);
        } else {
            throw new Exception('Something went wrong, please try again later.');
        }
    }

    public function unblockCard($username, $mpin, $retried = false)
    {
        $result = Http::withToken(session('token'))->post($this->baseUrl . '/vcard/cardfunctions/cardwallet-blockunblock/card/unblock/' . $username, [
            'mpin' => $mpin,
            'merchant_id' => $this->merchantId
        ]);

        $result = json_decode($result->getBody());

        $retry = $this->handleUnauthenticated($result, function ($retried) use ($username, $mpin) {
            return $this->unblockCard($username, $mpin, $retried);
        }, $retried);

        if ($retry) return $retry;

        if (!$result) {
            throw new Exception('Something went wrong, please try again later.');
        } elseif ($result->code == '200') {
            return $result;
        } elseif ($result->code == '400') {
            throw new Exception($result->message);
        } else {
            throw new Exception('Something went wrong, please try again later.');
        }
    }

    public function getUnassignedKitNumbers($retried = false)
    {
        $result = Http::withToken(session('token'))->post($this->baseUrl . '/vcard/unassigned-instant-card', [
            'merchant_id' => $this->merchantId
        ]);

        $result = json_decode($result->getBody());

        $retry = $this->handleUnauthenticated($result, function ($retried) {
            return $this->getUnassignedKitNumbers($retried);
        }, $retried);

        if ($retry) return $retry;

        if (!$result) {
            throw new Exception('Something went wrong, please try again later.');
        } elseif ($result->code == '200') {
            return $result->response;
        } else {
            throw new Exception('Something went wrong, please try again later.');
        }
    }

    public function loadCard($mpin, $localRef, $amount, $currency, $retried = false)
    {
        $result = Http::withToken(session('token'))->post($this->baseUrl . '/vcard/cardfunctions/loadcard', [
            'mpin' => $mpin,
            'merchant_id' => $this->merchantId,
            'amount' => $amount,
            'local_ref' => $localRef,
            'currency' => $currency
        ]);

        $result = json_decode($result->getBody());

        $retry = $this->handleUnauthenticated($result, function ($retried) use ($mpin, $localRef, $amount, $currency) {
            return $this->loadCard($mpin, $localRef, $amount, $currency, $retried);
        }, $retried);

        if ($retry) return $retry;

        Log::info(print_r($result, true));

        if (!$result) {
            throw new Exception('Something went wrong, please try again later.');
        } elseif ($result->code == '200') {
            return $result;
        } elseif ($result->code == '400') {
            if ($result->message == 'oops! No Active card found') {
                throw new Exception('Sorry, the Card is not active yet.');
            } elseif ($result->message == 'oops! insufficient fund') {
                throw new Exception('low balance', 1);
            } else {
                throw new Exception($result->message);
            }
        } else {
            throw new Exception('Something went wrong, please try again later.');
        }
    }

    #region Client APIs
    public function createUser($userData, $retried = false)
    {
        $result = Http::withToken(session('token'))->post($this->baseUrl . '/vcard/create-user', [
            'firstName' => $userData['firstName'],
            'lastName' => $userData['lastName'],
            'display_name' => $userData['display_name'],
            'email' => $userData['email'],
            'mobileNo' => $userData['mobileNo'],
            'dateOfBirth' => $userData['dateOfBirth'],
            'gender' => $userData['gender'],
            'countryCode' => $userData['countryCode'],
            'callingCode' => $userData['callingCode'],
            'state' => $userData['state'],
            'city' => $userData['city'],
            'postalCode' => $userData['postalCode'],
            'address' => $userData['address'],
            'merchant_id' => $this->merchantId,
            'instant_card' => $userData['instant_card']
        ]);

        $result = json_decode($result->getBody());

        $retry = $this->handleUnauthenticated($result, function ($retried) use ($userData) {
            return $this->createUser($userData, $retried);
        }, $retried);

        if ($retry) return $retry;

        if (!$result) {
            throw new Exception('Something went wrong, please try again later.');
        } elseif ($result->code == '200') {
            return $result->response[0];
        } elseif ($result->code == '400') {
            throw new Exception($result->message[0], 1);
        } else {
            throw new Exception('Something went wrong, please try again later.', 2);
        }
    }

    public function submitIdProofForKyc($username, $front, $back, $retried = false)
    {
        $result = Http::withToken(session('token'))->post($this->baseUrl . '/vcard/kyc-update/IdProof/' . $username, [
            'merchant_id' => $this->merchantId,
            'documentLanguage' => 'english',
            'idType'  => 'Passport',
            'idNumber'  => '99987654321',
            'issuedBy' => 'IN',
            'issuedDate' => '1988-10-10',
            'expirationDate' => '2023-12-10',
            'idProofFront' => $front,
            'idProofBack' => $back
        ]);

        $result = json_decode($result->getBody());

        $retry = $this->handleUnauthenticated($result, function ($retried) use ($username, $front, $back) {
            return $this->submitIdProofForKyc($username, $front, $back, $retried);
        }, $retried);

        if ($retry) return $retry;

        if (!$result) {
            throw new Exception('Something went wrong, please try again later.');
        } elseif ($result->code == '200') {
            return $result;
        } elseif ($result->code == '400') {
            throw new Exception($result->message[0], 1);
        } else {
            throw new Exception('Something went wrong, please try again later.', 2);
        }
    }

    public function submitAddressProofForKyc($username, $front, $back, $retried = false)
    {
        $result = Http::withToken(session('token'))->post($this->baseUrl . '/vcard/kyc-update/AddressProof/' . $username, [
            'merchant_id' => $this->merchantId,
            'documentLanguage' => 'english',
            'addressType'  => 'Bank Statement',
            'issuedBy'  => 'Bank of Test',
            'issuedDate' => '1988-10-10',
            'addressProofFront' => $front,
            'addressProofBack' => $back
        ]);

        $result = json_decode($result->getBody());

        $retry = $this->handleUnauthenticated($result, function ($retried) use ($username, $front, $back) {
            return $this->submitAddressProofForKyc($username, $front, $back, $retried);
        }, $retried);

        if ($retry) return $retry;

        if (!$result) {
            throw new Exception('Something went wrong, please try again later.');
        } elseif ($result->code == '200') {
            return $result;
        } elseif ($result->code == '400') {
            throw new Exception($result->message);
        } else {
            throw new Exception('Something went wrong, please try again later.');
        }
    }

    public function getClientCardDetails($retried = false)
    {
        if (!session('card-details')) {
            $result = Http::withToken(session('token'))->get($this->baseUrl . '/vcard/cardfunctions/find-cards/' . $this->memberUsername);

            $result = json_decode($result->getBody());

            $retry = $this->handleUnauthenticated($result, function ($retried) {
                return $this->getClientCardDetails($retried);
            }, $retried);

            if ($retry) return $retry;

            if (!$result) {
                throw new Exception('Something went wrong, please try again later.');
            } elseif ($result->code == '200') {
                if (count($result->response) > 0) {

                    $result = Http::withToken(session('token'))->post($this->baseUrl . '/vcard/find-card-details', [
                        'mpin' => $result->response[(count($result->response) - 1)]->mpin
                    ]);

                    $result = json_decode($result->getBody());

                    if (!$result) {
                        throw new Exception('Something went wrong, please try again later.');
                    } elseif ($result->code == '200') {
                        session(['card-details' => $result->response]);
                        return $result->response;
                    } else {
                        throw new Exception('Something went wrong, please try again later.');
                    }
                } else {
                    throw new Exception('Something went wrong, please try again later.');
                }
            } else {
                throw new Exception('No Details found against the username (' . $this->memberUsername . ').');
            }
        } else {
            return session('card-details');
        }
    }

    public function getCardBalance($retried = false)
    {
        $result = Http::withToken(session('token'))
            ->withHeaders(['Accept' => 'application/json'])
            ->get($this->baseUrl . '/vcard/cardfunctions/card-balance/' . $this->mpin);

        $result = json_decode($result->getBody());

        //Log::info("---------------------- API Response ------------------------" . __METHOD__);
        //Log::info(print_r($result, true));
        //Log::info("----------------------------------------------\n");

        $retry = $this->handleUnauthenticated($result, function ($retried) {
            return $this->getCardBalance($retried);
        }, $retried);


        if ($retry) {
            return $retry;
        }

        if (!$result) {
            throw new Exception('Something went wrong, please try again later.');
        } elseif ($result->code == '200') {
            return $result->response;
        } elseif ($result->code == '400') {
            log::info('Error Code: ' . $result->code . ' | Message: ' . $result->message);
            throw new Exception($result->message);
        } else {
            throw new Exception('Something went wrong, please try again later.');
        }
    }

    public function changePin($mpin, $pin, $retype_pin, $retried = false)
    {
        $result = Http::withToken(session('token'))->post($this->baseUrl . '/vcard/cardfunctions/setPin', [
            'merchant_id' => $this->merchantId,
            'mpin' => $mpin,
            'pin' => $pin,
            'confirmPin' => $retype_pin
        ]);

        $result = json_decode($result->getBody());

        $retry = $this->handleUnauthenticated($result, function ($retried) use ($mpin, $pin, $retype_pin) {
            return $this->changePin($mpin, $pin, $retype_pin, $retried);
        }, $retried);

        if ($retry) return $retry;

        if (!$result) {
            throw new Exception('Something went wrong, please try again later.');
        } elseif ($result->code == '200') {
            if ($result->message == 'processor failed!can not set pin!') {
                throw new Exception($result->message);
            }
            return $result;
        } elseif ($result->code == '400') {
            throw new Exception($result->message[0]);
        } else {
            throw new Exception('Something went wrong, please try again later.');
        }
    }

    // API for getting statement from Via Cart
    public function getCardStatement($startDate, $endDate, $retried = false)
    {
        echo  "Start Date: " . $startDate . "\n";
        echo  "End  Date: " . $endDate . "\n";
        // dd('Username: ' . $this->memberUsername . ' | MPIN: ' . $this->mpin . ' | Merchant ID: ' . $this->merchantId);
        $result = Http::withToken(session('token'))->post($this->baseUrl . '/vcard/cardfunctions/card-statement', [
            'username' => $this->memberUsername,
            'mpin' => $this->mpin,
            'merchant_id' => $this->merchantId,
            'startDate' => $startDate,
            'endDate' => $endDate
        ]);

        $result = json_decode($result->getBody());

        $retry = $this->handleUnauthenticated($result, function ($retried) use ($startDate, $endDate) {
            return $this->getCardStatement($startDate, $endDate, $retried);
        }, $retried);

        if ($retry) return $retry;

        if (!$result) {
            throw new Exception('Something went wrong, please try again later.');
        } elseif ($result->code == '200') {
            return $result->response;
        } elseif ($result->code == '400') {
            throw new Exception($result->message);
        } else {
            throw new Exception('Something went wrong, please try again later.');
        }
    }

    // New API for getting Month-wise and settled statement from Via Cart
    public function getSetteledCardStatementMonthWise($month, $year, $retried = false)
    {
        // echo "\n**************I got called getSetteledCardStatementMonthWise ***********\n";

        // echo  "**Month: " .$month . "\n";
        // echo  "** YEar: " .$year . "\n";
        //return;
        // dd('Username: ' . $this->memberUsername . ' | MPIN: ' . $this->mpin . ' | Merchant ID: ' . $this->merchantId);

        $result = Http::withToken(session('token'))->post($this->baseUrl . '/vcard/cardfunctions/card-statement-historical/settled', [
            'username' => $this->memberUsername,
            'mpin' => $this->mpin,
            'merchant_id' => $this->merchantId,
            'month' => $month,
            'year' => $year
        ]);

        $result = json_decode($result->getBody());

        $retry = $this->handleUnauthenticated($result, function ($retried) use ($month, $year) {
            return $this->getSetteledCardStatementMonthWise($month, $year, $retried);
        }, $retried);

        if ($retry) return $retry;

        //dd($result);
        if (!$result) {
            throw new Exception('Something went wrong, please try again later.');
        } elseif (!isset($result->code)) {
            return [];
        } elseif ($result->code == '200') {
            return $result->response;
        } elseif ($result->code == '400') {
            throw new Exception($result->message);
        } else {
            throw new Exception('Something went wrong, please try again later.');
        }
    }

    // API for getting statement from VistaMoney's Database.
    public function getCardStatementVistaMoneyDB($retried = false)
    {
        $result = Http::withToken(session('token'))->get($this->baseUrl . '/vcard/cardHistory/' . $this->memberUsername);
        $result = json_decode($result->getBody());

        $retry = $this->handleUnauthenticated($result, function ($retried) {
            return $this->getCardStatementVistaMoneyDB($retried);
        }, $retried);

        if ($retry) return $retry;

        if (!$result) {
            throw new Exception('Something went wrong, please try again later.');
        } elseif ($result->code == '200') {
            return $result->response;
        } elseif ($result->code == '400') {
            throw new Exception($result->message);
        } else {
            throw new Exception('Something went wrong, please try again later.');
        }
    }
    #endregion

    private function handleUnauthenticated($result, $retryCallback, $retried)
    {
        if (isset($result->message) && $result->message == "Unauthenticated.") {
            if (!$retried) {
                Log::info("Retrying to login again.");
                $this->login();
                return $retryCallback(true);
            } else {
                throw new \Exception('Authentication failed after retry.');
            }
        }
        return null;
    }
}
