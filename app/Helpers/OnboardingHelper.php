<?php

namespace App\Helpers;

use League\Flysystem\Exception;
use App\Models\Module;
use App\Models\ModuleConfiguration;
use GuzzleHttp\Client as GuzzleHttpClient;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OnboardingHelper

{

    private  $baseUrlOnboarding;
    private  $apiKey;
    private  $registerIndividualEndpoint;
    private  $registerCompanyEndpoint;
    private  $uploadFilesEndpoint;
    private $updateIndividualEndpoint;
    private $updateCompanyEndpoint;
    private $registerKYCForOldClientEndpoint;
    private $photoComparisonEndpoint;

    public function __construct()
    {
        $module = Module::where('name', 'Onboarding')->select('id')->first();
        $moduleConfig = ModuleConfiguration::where('module_id', $module->id)->first();

        if ($moduleConfig) {
            $complianceConfig = unserialize($moduleConfig->configurations);

            $this->baseUrlOnboarding = $complianceConfig['base_url']['value'];
            $this->apiKey = $complianceConfig['api_key']['value'];
            $this->registerIndividualEndpoint = $complianceConfig['register_individual_endpoint']['value'];
            $this->registerCompanyEndpoint = $complianceConfig['register_company_endpoint']['value'];
            $this->uploadFilesEndpoint = $complianceConfig['upload_files_endpoint']['value'];
            $this->updateIndividualEndpoint = $complianceConfig['update_individual_endpoint']['value'];
            $this->updateCompanyEndpoint = $complianceConfig['update_company_endpoint']['value'];
            $this->registerKYCForOldClientEndpoint = $complianceConfig['register_kyc_for_old_client']['value'];
            $this->photoComparisonEndpoint = $complianceConfig['photo_comparison']['value'];
        } else {
            throw new Exception('No configurations found. Please feed the compliance Configurations.');
        }
    }

    public function getBaseUrlOnboarding()
    {
        return $this->baseUrlOnboarding;
    }

    public function getApiKey()
    {
        return $this->apiKey;
    }

    public function getRegisterIndividualEndpoint()
    {
        return $this->registerIndividualEndpoint;
    }

    public function getRegisterCompanyEndpoint()
    {
        return $this->registerCompanyEndpoint;
    }

    public function getUploadFilesEndpoint()
    {
        return $this->uploadFilesEndpoint;
    }

    public function getUpdateIndividualEndpoint()
    {
        return $this->updateIndividualEndpoint;
    }

    public function getUpdateCompanyEndpoint()
    {
        return $this->updateCompanyEndpoint;
    }

    public function getRegisterKYCForOldClientEndpoint()
    {
        return $this->registerKYCForOldClientEndpoint;
    }

    public function getPhotoComparisonEndpoint()
    {
        return $this->photoComparisonEndpoint;
    }

    public function loginAPI($email, $password)
    {
        $this->loginUserAPI($email, $password, 'user/login');
    }

    public function loginAdminAPI($email, $password)
    {
        $this->loginUserAPI($email, $password, 'user/admin-login');
    }

    public function loginUserAPI($email, $password, $endpoint)
    {
        $clientHttp = new GuzzleHttpClient();
        $timestamp = time();
        $method = 'POST';
        $url = "/api/{$endpoint}";

        $payload = [
            'email' => $email,
            'password' => $password,
        ];

        $signature = SignatureHelper::createSignature($method, $url, $timestamp, $payload);

        $response = $clientHttp->post($this->baseUrlOnboarding . $endpoint, [
            'headers' => [
                'Signature' => $signature,
                'Timestamp' => $timestamp,
                'accept'         => 'application/json'
            ],
            'multipart' => [
                [
                    'name' => 'email',
                    'contents' => $email
                ],
                [
                    'name' => 'password',
                    'contents' => $password
                ],
            ],
            ]);

        $result = json_decode($response->getBody());

        if ($result !== null && isset($result->token)) {
            session(['loginToken' => $result->token]);
        }
    }
}
