<?php

namespace App\Helpers;

use App\Models\Account;
use App\Models\Client;
use App\Models\ClientCompany;
use App\Models\ClientCompanyContact;
use App\Models\ClientFileConfig;
use App\Models\ClientIndividual;
use App\Models\ClientKYC;
use App\Models\ClientUser;
use App\Models\User;
use App\Models\KycRequest;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use GuzzleHttp\Client as GuzzleHttpClient;
use Illuminate\Support\Facades\Log;

class ClientHelper
{
    public static function registerClient($data, $user)
    {
        // create client
        $client = new Client();

        $client->name = $user->name;
        $client->type = $data["account_type"];
        $client->status = "Draft";
        $client->created_by = $user->id;

        $client->save();

        // create individual / company
        if ($data["account_type"] == "individual") {
            self::createIndividualProfile($data, $client);
        } else {
            self::createCompanyProfile($data, $client);
        }

        // create client user relation
        $link = new ClientUser();

        $link->client_id = $client->id;
        $link->user_id = $user->id;

        $link->save();
    }

    private static function createIndividualProfile($data, Client $client)
    {
        $individual = new ClientIndividual();

        $individual->client_id = $client->id;
        $individual->country_code_id = $data["country_code_id"];
        $individual->mobile_number =  $data["mobile_number"];
        $individual->email_address = $data["email"];
        $individual->created_by = $client->created_by;

        $individual->save();
    }

    private static function createCompanyProfile($data, Client $client)
    {
        $company = new ClientCompany();

        $company->client_id = $client->id;
        $company->created_by = $client->created_by;

        $company->save();

        // create contact person
        $contact = new ClientCompanyContact();

        $contact->client_id = $client->id;
        $contact->company_id = $company->id;
        $contact->country_code_id = $data["country_code_id"];
        $contact->mobile_number =  $data["mobile_number"];
        $contact->email_address = $data["email"];
        $contact->created_by = $client->created_by;

        $contact->save();
    }

    public static function getClientByUserId($user_id)
    {
        $cu = ClientUser::where("user_id", $user_id)->first();

        return ($cu && $cu->client) ? $cu->client : null;
    }
    public static function getClientByKycHash($hash)
    {
        $request = KycRequest::where("hash", $hash)->first();

        return ($request && $request->client) ? $request->client : null;
    }

    public static function getClientByAccountId($account_id)
    {
        $account = Account::where("id", $account_id)->first();

        $result = ($account && $account->client) ? $account->client : null;

        if (!$result) {
            $result = ($account && $account->bank) ? $account->bank : null;
        }

        return $result;
    }

    public static function getUserByClientId($client_id)
    {
        $cu = ClientUser::where("client_id", $client_id)->first();

        return ($cu && $cu->user) ? $cu->user : null;
    }

    public static function isDraftClient($client_id)
    {
        $cu = ClientUser::where("client_id", $client_id)->first();

        return ($cu && ($cu->user && $cu->user->hasRole('draft client')) || Client::find($client_id)->status != 'Approved') ? true : false;
    }

    public static function linkUserToClient($client, $parent_id = 0, $user_id,)
    {
        try {
            $cu = ClientUser::where("client_id", $client->id);

            if ($parent_id) {
                $subAccountUser = User::where("email", $client->type == 'company' ? $client->company->contact->email_address : $client->individual->email_address)->first();

                if ($subAccountUser) {
                    $cu->where("user_id", "<>", $subAccountUser->id);
                }
            } else {
                $cu->where("user_id", $user_id);
            }

            $cu = $cu->first();

            if (!$cu) {

                if ($parent_id) {

                    if ($subAccountUser && !$subAccountUser->locked) {
                        $subAccountUser->locked = true;
                        $subAccountUser->save();
                    }
                }

                $cu = new ClientUser();

                $cu->client_id = $client->id;
                $cu->user_id = $user_id;

                $cu->save();

                // attach client role if not existing
                $user = $cu->user;


                if ($user && !$user->hasRole("client") && !$user->hasRole('basic client')) {
                    $user->assignRole("client");
                }
                return ["type" => "success", "message" => "User linked to client successfully"];
            } else {
                if ($cu->user) {
                    return ["type" => "error", "message" => "User with email " . $cu->user->email . " is already linked with the client " . $cu->client->name];
                } else {
                    return ["type" => "error", "message" => "This client is already linked with the client " . $cu->client->name];
                }
            }
        } catch (Exception $e) {
            Log::error('Exception thrown while linking User to a Client.' . $e->getMessage());
        }
    }

    public static function prefexAccountNumbersOfSubAccount($accounts, $parent_id)
    {
        try {
            $parent = Client::find($parent_id);
            $parentAccountCurrencies = $parent->clientAccounts->pluck('account_currency')->toArray();
            $prefex = $parent->sub_accounts_prefix ? $parent->sub_accounts_prefix : substr($parent->name, 0, 3);
            $ownCompany = Common::getOwnCompanyData();
            $ownCompanyPrefix = '';

            foreach ($accounts as $account) {
                if (!in_array($account->account_currency, $parentAccountCurrencies)) {
                    $account->is_active = false;

                    $account->save();
                }

                $accountNumber = preg_replace('/^[a-zA-Z]+/', '', $account->account_number);

                if ($ownCompany->account_number_prefix) {
                    $ownCompanyPrefix = $ownCompany->account_number_prefix;
                }

                $accountNumber = $ownCompanyPrefix . $prefex . $accountNumber;

                if ($associatedTransactions = $account->associatedTransactions) {
                    foreach ($associatedTransactions as $trn) {
                        if ($trn->receiver_account_number && $trn->receiver_account_number == $account->account_number) {
                            $trn->receiver_account_number = $accountNumber;

                            $trn->save();
                        }
                    }
                }

                $account->account_number = $accountNumber;
                $account->save();
            }

            return true;
        } catch (Exception $e) {
            Log::error('Exception thrown while Prefexing the Account-Numbers. ' . $e->getMessage());
            return false;
        }
    }

    public static function deletePrefexFromAccNumsOfSubAccount($accounts)
    {
        try {
            $ownCompany = Common::getOwnCompanyData();
            $ownCompanyPrefix = '';

            foreach ($accounts as $account) {
                $accountNumber = preg_replace('/^[a-zA-Z]+/', '', $account->account_number);

                if ($ownCompany->account_number_prefix && strpos($account->account_number, $ownCompany->account_number_prefix)) {
                    $ownCompanyPrefix = $ownCompany->account_number_prefix;
                }

                $accountNumber = $ownCompanyPrefix . $accountNumber;

                if ($associatedTransactions = $account->associatedTransactions) {
                    foreach ($associatedTransactions as $trn) {
                        if ($trn->receiver_account_number && $trn->receiver_account_number == $account->account_number) {
                            $trn->receiver_account_number = $accountNumber;

                            $trn->save();
                        }
                    }
                }

                $account->account_number = $accountNumber;
                $account->save();
            }

            return true;
        } catch (Exception $e) {
            Log::error('Exception thrown while Prefexing the Account-Numbers. ' . $e->getMessage());
            return false;
        }
    }

    public static function onboardingRegisterClient($baseUrl, $endPoint, $apiKey, $data, $user = '')
    {
        $clientHttp = new GuzzleHttpClient();

        //generating token to access the registration call
        //dd($user);
        if (!empty($user)) {
            //Log::info("In api User");
            $token = $user->createToken(env('TOKEN_NAME'))->plainTextToken;
            //dd($token);
        } else {
            $token = auth()->user()->createToken(env('TOKEN_NAME'))->plainTextToken;
        }

        $clientHttp->post($baseUrl . $endPoint, [
            'headers'   => [
                'api-key'        => $apiKey,
                'accept'         => 'application/json',
                'Authorization' => 'Bearer ' . $token,
            ],
            'multipart' => $data,
        ]);
    }

    public static function compareClientPhotos($selfiPhoto_url, $passport_url, $client, $director_id = '')
    {
        try {
            $onboardingHelper = new OnboardingHelper();
            $baseUrl = $onboardingHelper->getBaseUrlOnboarding();

            $endPoint = $onboardingHelper->getPhotoComparisonEndpoint();
            $url = "/api/{$endPoint}";

            $data = [
                [
                    'name'     => 'selfie_photo',
                    'contents' => $selfiPhoto_url
                ],
                [
                    'name'     => 'passport_copy',
                    'contents' => $passport_url
                ],
                [
                    'name'     => 'client_id',
                    'contents' => $client->id
                ],
                [
                    'name'     => 'director_id',
                    'contents' => $director_id
                ]

            ];

            if (!empty($client)) {
                if (count($client->users) > 0) {
                    $user = User::find($client->users[0]->user_id);
                } else {
                    $user = User::where('code', 'kyc-user')->first();
                }
            }

            //generating token for the user associated with this client
            if (empty(auth()->user())) {
                $token = $user->createToken(env('TOKEN_NAME'))->plainTextToken;
            } else {
                $token = auth()->user()->createToken(env('TOKEN_NAME'))->plainTextToken;
            }

            $clientHttp = new GuzzleHttpClient();

            $timestamp = time();
            $method = 'POST';

            $signature = SignatureHelper::createSignature($method, $url, $timestamp, [
                'selfie_photo'  => $selfiPhoto_url,
                'passport_copy' => $passport_url,
                'client_id'     => (string) $client->id,
                'director_id'   => $director_id ? (string) $director_id : null,
            ]);


            $clientHttp->post($baseUrl . $endPoint, [
                'headers'   => [
                    'accept'         => 'application/json',
                    'Authorization' => 'Bearer ' . $token,
                    'Signature' => $signature,
                    'Timestamp' => $timestamp,
                ],
                'multipart' => $data,
            ]);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());

            $message = "Client passport just uploaded didn't quite match the  selfie photo.";
            return redirect(url()->previous())->with('error', $message);
        }
    }

    public static function addPassportUrlToClientKYC($passport_url, $client, $conf)
    {
        try {
            $passport_id = ClientFileConfig::where('type', ($client->is_bank == 1 ? 'bank' : $client->type))->where('identifier', 'passport_copy')->value('id');

            if ($passport_id == $conf->id) {
                $kyc = ClientKYC::where('client_id', $client->id)->first();

                if ($kyc) {
                    $kyc->passport_copy_url = $passport_url;
                    $kyc->kyc_status = 'Unverified';
                    $kyc->save();
                    return true;
                }
            }
        } catch (Exception $e) {
            Log::error('Exception thrown while adding Passport URL to Client KYC. ' . $e->getMessage());

            return false;
        }
    }
}
