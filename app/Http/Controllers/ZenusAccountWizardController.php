<?php

namespace App\Http\Controllers;

use App\Helpers\ClientHelper;
use App\Helpers\FileFirewall;
use App\Helpers\IDORHelper;
use App\Helpers\S3Helper;
use App\Helpers\ZenusAccountWizard;
use App\Helpers\ZenusApis;
use App\Models\Account;
use App\Models\ClientCompanyFile;
use App\Models\ClientFileConfig;
use App\Models\ClientIndividualEmployer;
use App\Models\ClientIndividualFile;
use App\Models\ClientKYC;
use App\Rules\NotNullAndNotEmptyRule;
use App\Rules\SupportingDocumentsRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ZenusAccountWizardController extends Controller
{
    public function show(Request $request, ZenusAccountWizard $wizard)
    {
        $request->validate(['account_id' => 'required|integer']);
        $account = Account::findOrFail($request->account_id);
        $client = ClientHelper::getClientByAccountId($account->id);

        return response()->json([
            'success' => true,
            'account_id' => $account->id,
            'account_number' => $account->account_number ?? '',
            'account_title' => $account->title,
            'currency' => optional($account->currency)->short,
            'client_name' => $client->name ?? '',
            'client_type' => $client->type ?? '',
            'progress' => $wizard->stepsFor($account),
        ]);
    }

    public function clear(Request $request, ZenusAccountWizard $wizard)
    {
        $request->validate(['account_id' => 'required|integer']);
        $account = Account::findOrFail($request->account_id);
        $client = ClientHelper::getClientByAccountId($account->id);
        if (!$client || !IDORHelper::isLinkedOrSubAccount(Auth::user(), $client->id)) {
            abort(403);
        }

        $wizard->clearState($account);

        return response()->json([
            'success' => true,
            'message' => 'Wizard progress cleared. Start again from the first step.',
            'progress' => $wizard->stepsFor($account),
        ]);
    }

    public function run(Request $request, ZenusAccountWizard $wizard)
    {
        $request->validate([
            'account_id' => 'required|integer',
            'step' => 'required|string',
            'action' => 'nullable|string|in:update,skip,back,upload_missing,reupload,skip_file',
            'soc_code' => 'nullable|string',
            'doc_type' => 'nullable|string',
        ]);
        $account = Account::findOrFail($request->account_id);
        $result = $wizard->run(
            $account,
            $request->step,
            $request->input('action'),
            $request->input('soc_code'),
            $request->input('doc_type')
        );

        $ok = !empty($result['success']) || !empty($result['needs_choice']);

        return response()->json($result, $ok ? 200 : 422);
    }

    public function fixForm(Request $request, ZenusAccountWizard $wizard, ZenusApis $zenus)
    {
        $request->validate([
            'account_id' => 'required|integer',
            'kind' => 'nullable|string',
        ]);
        $account = Account::findOrFail($request->account_id);
        $client = ClientHelper::getClientByAccountId($account->id);
        if (!$client || !IDORHelper::isLinkedOrSubAccount(Auth::user(), $client->id)) {
            abort(403);
        }

        $missing = $wizard->fixFields($client, $wizard->lastError($account));
        $needsSoc = collect($missing)->contains(function ($field) {
            return ($field['key'] ?? '') === 'soc_code';
        });
        $socCodes = $needsSoc ? DB::table('soc_codes')->orderBy('job_title')->get() : collect();

        return view('client-management.partials.zenus-wizard-fix-form', compact('client', 'missing', 'socCodes'));
    }

    public function saveFix(Request $request, ZenusAccountWizard $wizard)
    {
        $request->validate([
            'account_id' => 'required|integer',
        ]);
        $account = Account::findOrFail($request->account_id);
        $client = ClientHelper::getClientByAccountId($account->id);
        if (!$client || !IDORHelper::isLinkedOrSubAccount(Auth::user(), $client->id)) {
            abort(403);
        }

        $tooLarge = $this->uploadTooLargeReason();
        if ($tooLarge) {
            return response()->json(['success' => false, 'message' => $tooLarge], 422);
        }

        try {
            $this->saveMissing($request, $account, $client, $wizard->fixFields($client, $wizard->lastError($account)), $wizard);
        } catch (ValidationException $e) {
            $messages = collect($e->errors())->flatten()->filter()->values()->all();

            return response()->json([
                'success' => false,
                'message' => $messages ? implode(' ', $messages) : 'Please complete the required fields.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage() ?: 'Unable to save.'], 422);
        }

        return response()->json(['success' => true, 'message' => 'Required details saved. Retrying this step.']);
    }

    private function saveMissing(Request $request, Account $account, $client, array $missing, ZenusAccountWizard $wizard): void
    {
        if (empty($missing)) {
            return;
        }

        $invalidCharsPattern = "/[\/\-\?\:\(\)\.,'\+]/";
        $rules = [];
        $messages = [];
        foreach ($missing as $field) {
            $key = $field['key'];
            if (($field['type'] ?? '') === 'file') {
                $rules[$key] = ['required', new SupportingDocumentsRule(false)];
                $messages[$key . '.required'] = $field['label'] . ' is required.';
            } else {
                $rules[$key] = ['required', new NotNullAndNotEmptyRule];
                $messages[$key . '.required'] = $field['label'] . ' is required.';
                if (in_array($key, ['street_address', 'business_reg_street_address'], true)) {
                    $rules[$key][] = 'not_regex:' . $invalidCharsPattern;
                    $messages[$key . '.not_regex'] = $field['label'] . " contains unsupported characters (/ - ? : ( ) . , ' +).";
                }
            }
        }
        $request->validate($rules, $messages);

        foreach ($missing as $field) {
            $key = $field['key'];
            if (($field['type'] ?? '') === 'file') {
                $this->saveMissingFile($request, $client, $field, $wizard);
                continue;
            }

            $value = $request->input($key);
            if ($key === 'id_expiry_date' || $key === 'passport_expiry' || $key === 'passport_expiry_date') {
                $personId = $wizard->linkedPersonId($client);
                if ($personId) {
                    $wizard->rememberIdExpiry($account, $personId, $value);
                }
            }
            if ($key === 'id_expiry_date') {
                continue;
            }
            if (($client->type ?? '') === 'company') {
                $this->applyCompanyField($client, $key, $value);
            } else {
                $this->applyIndividualField($client, $key, $value);
            }
        }
    }

    private function applyIndividualField($client, string $key, $value): void
    {
        $individual = $client->individual;
        if (!$individual) {
            throw new \RuntimeException('Individual record not found.');
        }
        if ($key === 'passport_number' || $key === 'passport_expiry_date') {
            $individual->{$key} = $value;
            $individual->updated_by = Auth::user()->id;
            $individual->save();

            return;
        }
        if ($key === 'soc_code') {
            $employer = $individual->employer;
            if (!$employer) {
                $employer = new ClientIndividualEmployer();
                $employer->client_id = $client->id;
                $employer->individual_id = $individual->id;
                $employer->created_by = Auth::user()->id;
            } else {
                $employer->updated_by = Auth::user()->id;
            }
            $employer->soc_code = $value;
            $employer->save();

            return;
        }

        $individual->{$key} = $value;
        $individual->updated_by = Auth::user()->id;
        $individual->save();
    }

    private function applyCompanyField($client, string $key, $value): void
    {
        $company = $client->company;
        if (!$company) {
            throw new \RuntimeException('Company record not found.');
        }
        $contactKeys = [
            'soc_code' => 'soc_code',
            'passport_number' => 'passport_number',
            'passport_expiry' => 'passport_expiry',
            'contact_street_address' => 'street_address',
            'contact_city' => 'contact_city',
            'contact_state_region' => 'state_region',
            'contact_zip_code' => 'zip_code',
        ];
        if (isset($contactKeys[$key])) {
            $contact = $company->contact;
            if (!$contact) {
                throw new \RuntimeException('Company contact not found.');
            }
            $contact->{$contactKeys[$key]} = $value;
            $contact->updated_by = Auth::user()->id;
            $contact->save();

            return;
        }

        $company->{$key} = $value;
        $company->updated_by = Auth::user()->id;
        $company->save();
    }

    private function uploadTooLargeReason(): ?string
    {
        $length = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
        if ($length <= 0 || !empty($_POST) || !empty($_FILES)) {
            return null;
        }

        return 'The file is larger than the server upload limit (' . ini_get('upload_max_filesize') . '). Choose a smaller file.';
    }

    private function phpUploadError($file): string
    {
        $name = $file->getClientOriginalName() ?: 'file';
        $limit = ini_get('upload_max_filesize');
        switch ($file->getError()) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return 'The file "' . $name . '" is larger than the upload limit (' . $limit . ').';
            case UPLOAD_ERR_PARTIAL:
                return 'The file "' . $name . '" was only partly uploaded. Try again.';
            case UPLOAD_ERR_NO_FILE:
                return 'No file was received for "' . $name . '".';
            default:
                return 'The file "' . $name . '" could not be uploaded (error ' . $file->getError() . ').';
        }
    }

    private function saveMissingFile(Request $request, $client, array $field, ZenusAccountWizard $wizard): void
    {
        $key = $field['key'];
        $files = $request->file($key);
        if (!$files) {
            return;
        }
        if (!is_array($files)) {
            $files = [$files];
        }
        $folder = $client->type === 'company'
            ? 'client-onboarding-files/clients/company/' . $client->id
            : 'client-onboarding-files/clients/individual/' . $client->id;

        foreach ($files as $file) {
            if ($file->getError()) {
                throw new \RuntimeException($this->phpUploadError($file));
            }
            if (!FileFirewall::isAllowedType($file)) {
                throw new \RuntimeException(
                    'The file "' . $file->getClientOriginalName() . '" is not an allowed type for ' . $field['label'] . '.'
                );
            }
            $url = S3Helper::fileUpload($file, $folder);
            if (!$url) {
                throw new \RuntimeException(
                    'Storage rejected "' . $file->getClientOriginalName() . '" for ' . $field['label'] . '. Check the file size and try again.'
                );
            }

            if ($key === 'selfie_photo_url') {
                $kyc = ClientKYC::where('client_id', $client->id)->whereNull('director_id')->first();
                if (!$kyc) {
                    $kyc = new ClientKYC();
                    $kyc->client_id = $client->id;
                    $kyc->kyc_status = 'Unverified';
                }
                $kyc->selfie_photo_url = $url;
                $kyc->save();
                continue;
            }

            $conf = ClientFileConfig::find($field['file_id'] ?? null);
            if (!$conf && !empty($field['identifier'])) {
                $type = $client->is_bank ? 'bank' : $client->type;
                $conf = $wizard->documentConfig($field['identifier'], $type);
            }
            if (!$conf && !empty($field['identifier'])) {
                $conf = new ClientFileConfig();
                $conf->title = $field['label'] ?? $field['identifier'];
                $conf->identifier = $field['identifier'];
                $conf->type = $client->is_bank ? 'bank' : $client->type;
                $conf->is_active = 1;
                $conf->isMultiple = 0;
                $conf->save();
            }
            if (!$conf) {
                continue;
            }
            if ($client->type === 'company') {
                $newFile = new ClientCompanyFile();
                $newFile->client_id = $client->id;
                $newFile->company_id = $client->company->id;
                $newFile->file_id = $conf->id;
                $newFile->url = $url;
                $newFile->created_by = Auth::user()->id;
                $newFile->save();
            } else {
                $newFile = new ClientIndividualFile();
                $newFile->client_id = $client->id;
                $newFile->individual_id = $client->individual->id;
                $newFile->file_id = $conf->id;
                $newFile->url = $url;
                $newFile->created_by = Auth::user()->id;
                $newFile->save();
            }
            if ($conf->identifier === 'passport_copy') {
                $kyc = ClientKYC::where('client_id', $client->id)->whereNull('director_id')->first();
                if ($kyc) {
                    $kyc->passport_copy_url = $url;
                    $kyc->save();
                }
            }
        }
    }
}
