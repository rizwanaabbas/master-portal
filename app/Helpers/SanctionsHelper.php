<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;
use League\Flysystem\Exception;
use App\Models\Module;
use App\Models\ModuleConfiguration;
use App\Models\Sanctions;

/* class SanctionsHelper

{

    private  $baseUrlSanctionsIo;
    private  $tokenSanctionsIo;

    public function __construct()
    {
        $module = Module::where('name', 'Compliance')->select('id')->first();
        $moduleConfig = ModuleConfiguration::where('module_id', $module->id)->first();

        if ($moduleConfig) {
            $complianceConfig = unserialize($moduleConfig->configurations);

            $this->baseUrlSanctionsIo = $complianceConfig['sandbox_api_url_sanction_io']['value'];
            $this->tokenSanctionsIo = $complianceConfig['token_sanctions_io']['value'];
        } else {
            throw new Exception('No configurations found. Please feed the compliance Configurations.');
        }
    }

    public  function getSanctionsForUsers($formfield)
    {
        $params = SanctionsHelper::getParams($formfield);

        $response = Http::withToken($this->tokenSanctionsIo)->withHeaders([
            'Accept' => 'application/json',
            'api_version' => '2.2',
        ])->get($this->baseUrlSanctionsIo . '/search/?data_source=SANCTIONS&name=' . $params);

        $result = json_decode($response->getBody());

        if (!$result) {
            throw new Exception('Something went wrong, please try again later.');
        } elseif ($response->status() == '200') {
            return $result;
        } elseif ($response->status() == '400') {
            throw new Exception($result->message);
        } elseif ($response->status() == '401') {
            throw new Exception("Unauthorized");
        } else {
            throw new Exception('Something went wrong, please try again later.');
        }
    }

    public static  function getParams($formfield)
    {
        // Use array_filter to remove null values from the params array
        $formfield = array_filter($formfield);

        // Join the parameters into a string
        $paramsString = implode('&', array_map(function ($key, $value) {

            return "$key=$value";
        }, array_keys($formfield), $formfield));

        return $paramsString;
    }

    public static function getSanctionsForTransactionRequest($beneficiary, $trn)
    {
        $sanctionsHelper = new SanctionsHelper();

        try {
            $confidanceScoreForIndividual = 0;
            $confidanceScoreForBank = 0;
            $confidanceScoreForIndividual =  self::processSanctionsForType($beneficiary, $trn, 'individual', $sanctionsHelper);
            $confidanceScoreForBank = self::processSanctionsForType($beneficiary, $trn, 'entity', $sanctionsHelper);

            if ($confidanceScoreForIndividual || $confidanceScoreForBank) {
                EmailNotification::notifyComplianceTeamForSanctions($trn);

                return true;
            } else {

                return false;
            }
        } catch (Exception $e) {

            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    private static function processSanctionsForType($beneficiary, $trn, $entityType, $sanctionsHelper)
    {

        $beneficiaryName = $entityType === 'individual' ? $beneficiary->name : $beneficiary->bank_name;
        $sanctionFields = [
            'user_id' => $beneficiary->user_id,
            'case_id' => $trn->case_id,
            'entity_type' => $entityType,
            'name' => $beneficiaryName,
            'address' => $entityType === 'individual' ? $beneficiary->address : $beneficiary->bank_address,
            'sanctions_io' => true
        ];

        $result = $sanctionsHelper->getSanctionsForUsers($sanctionFields);
        $sanctionFields['s_response'] = serialize($result);
        $confidenceScore = 0;

        if (isset($result->results)) {
            foreach ($result->results as $result) {
                if ($result->name == $sanctionFields['name']) {
                    $confidenceScore = $result->confidence_score;
                    break;
                }
            }
            $sanctionFields['is_suspicious'] = $confidenceScore >= 0.7 ? 1 : 0;
        }

        if ($sanctionFields['is_suspicious']) {
            $sanctionFields['resolved'] = 0;
            $sanctionFields['blocked'] = 0;
        }

        Sanctions::create($sanctionFields);

        return $confidenceScore;
    }
} */
