<?php

namespace App\Helpers;

use App\Models\Account;
use App\Models\User;
use App\Models\VirtualBankAccount;
use App\Models\VirtualBankLinkedClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ZenusAccountWizard
{
    public const STEPS_INDIVIDUAL = [
        'precheck' => 'Check documents & KYC',
        'person' => 'Create Zenus person',
        'occupation' => 'Set occupation',
        'documents' => 'Upload documents',
        'activate' => 'Activate person',
        'account' => 'Create virtual account',
    ];

    public const UBO_DOCUMENTS = [
        'ubo_doc_biometric' => ['Upload UBO biometric report', 'BIOMETRIC_REPORT'],
        'ubo_doc_address' => ['Upload UBO proof of address', 'PROOF_OF_ADDRESS'],
        'ubo_doc_fatca' => ['Upload UBO FATCA', 'FATCA'],
        'ubo_doc_funds' => ['Upload UBO source of funds', 'SOURCE_OF_FUNDS'],
    ];

    public const COMPANY_DOCUMENTS = [
        'doc_incorporation' => ['Upload certificate of incorporation', 'CERTIF_INCORPORATION_REG'],
        'doc_records' => ['Upload corporate records', 'CORPORATE_RECORDS'],
        'doc_resolution' => ['Upload corporate resolution', 'CORPORATE_RESOLUTION'],
        'doc_fiscal' => ['Upload fiscal registration certificate', 'COMPANY_FISCAL_REG_CERTIF'],
        'doc_fatca' => ['Upload company FATCA', 'FATCA'],
        'doc_financials' => ['Upload financial statements', 'FINANCIAL_STATEMENTS'],
        'doc_gov_reg' => ['Upload government registration number', 'GOV_COMPANY_REG_NUMBER'],
        'doc_address' => ['Upload company proof of address', 'PROOF_OF_ADDRESS'],
        'doc_funds' => ['Upload company source of funds', 'SOURCE_OF_FUNDS'],
        'doc_articles' => ['Upload articles of association', 'ARTICLES_OF_ASSOCIATION'],
        'doc_tax' => ['Upload tax ID document', 'TAX_ID_NUMBER'],
    ];

    public static function companySteps(): array
    {
        $steps = [
            'precheck' => 'Check documents & KYC',
            'shareholder' => 'Create / update UBO',
            'ubo_occupation' => 'Set UBO occupation',
            'ubo_documents' => 'UBO documents',
            'legal_person' => 'Create / update legal person',
            'ubo_link' => 'Link UBO as shareholder',
            'signer_link' => 'Link UBO as signer',
            'company_documents' => 'Company documents',
            'sync' => 'Sync Zenus status',
        ];
        $steps['activate'] = 'Activate legal person';
        $steps['account'] = 'Create virtual account';

        return $steps;
    }

    public const STEP_INFO = [
        'precheck' => 'Confirms required files and verified KYC are on the client record before any Zenus API call.',
        'person' => 'Creates the private person at Zenus, or reuses the linked person if one already exists.',
        'occupation' => 'Sends the occupation / SOC code to Zenus for the person created in the previous step.',
        'documents' => 'Shows the files already on the client, then uploads the pending ones to Zenus in one click.',
        'ubo_documents' => 'Shows the UBO files already on the client, then uploads the pending ones in one click.',
        'company_documents' => 'Shows the company files already on the client, then uploads the pending ones in one click.',
        'activate' => 'For a company, activates the UBO shareholder and signer first, then the legal person. If Zenus is still reviewing KYC, retry this step.',
        'shareholder' => 'Creates the UBO private person, or updates that person when one is already linked.',
        'ubo_occupation' => 'Sends the UBO occupation / SOC code to Zenus.',
        'legal_person' => 'Creates the Zenus legal (company) person, or updates it when one is already linked. Does not link the UBO or upload files.',
        'ubo_link' => 'Links the UBO to the company as shareholder.',
        'signer_link' => 'Links the UBO to the company as signer.',
        'sync' => 'Reads the person and account from Zenus and applies stored webhooks.',
        'account' => 'Opens a Zenus virtual account for this ledger currency and saves the details in the portal.',
    ];

    /** @var ZenusApis */
    protected $zenus;

    public function __construct(ZenusApis $zenus)
    {
        $this->zenus = $zenus;
    }

    public function stepsFor(Account $account): array
    {
        $client = ClientHelper::getClientByAccountId($account->id);
        $syncNotes = $this->syncWebhookStatus($account, $client);
        $defs = ($client->type ?? '') === 'company' ? self::companySteps() : self::STEPS_INDIVIDUAL;
        $state = $this->state($account);
        $this->clearStaleDocumentError($account, $client, $state);
        $inferred = $this->inferredCompleted($account, $client);
        $cached = $state['completed'] ?? [];
        if (!in_array('account', $inferred, true)) {
            $cached = array_values(array_diff($cached, ['account']));
        }
        $completed = array_values(array_unique(array_merge($cached, $inferred)));
        if (!empty($state['reopen_step'])) {
            $completed = array_values(array_diff($completed, [$state['reopen_step']]));
        }
        $steps = [];
        foreach ($defs as $key => $label) {
            $info = self::STEP_INFO[$key] ?? '';
            $steps[] = [
                'key' => $key,
                'label' => $label,
                'info' => $info,
                'done' => in_array($key, $completed, true),
            ];
        }

        $next = $this->nextStepKey($defs, $completed);
        if (!empty($state['current_step']) && !in_array($state['current_step'], $completed, true) && isset($defs[$state['current_step']])) {
            $next = $state['current_step'];
        }

        return [
            'client_type' => $client->type ?? 'individual',
            'steps' => $steps,
            'next' => $next,
            'person_status' => $state['person_status'] ?? null,
            'message' => !empty($syncNotes) ? implode(' ', $syncNotes) : ($state['last_message'] ?? null),
            'last_error' => $this->readableStoredError($state['last_error'] ?? null),
            'fix' => $state['last_fix'] ?? null,
            'exists' => $this->existsChoiceFor($account, $client, $state, $next),
            'documents' => $this->isDocumentGroup($next) ? $this->documentPanel($account, $client, $next) : null,
            'previous' => $this->previousStepKey($defs, $next),
            'auto_continue' => $this->isAutoContinue(),
        ];
    }

    public function run(Account $account, string $step, ?string $action = null, ?string $socCode = null, ?string $docType = null): array
    {
        $client = ClientHelper::getClientByAccountId($account->id);
        if (!$client) {
            return $this->fail('Client not found for this account.');
        }
        if (!Common::isModuleEnabled('Zenus')) {
            return $this->fail('Zenus module is disabled.');
        }

        $defs = ($client->type ?? '') === 'company' ? self::companySteps() : self::STEPS_INDIVIDUAL;
        if (!isset($defs[$step])) {
            return $this->fail('Unknown wizard step.');
        }

        if ($action === 'back') {
            return $this->goBack($account, $client, $step);
        }
        if ($action === 'skip') {
            $this->markComplete($account, $step, 'Skipped. The existing Zenus value was kept.', null);
            $result = $this->ok('Skipped. The existing Zenus value was kept.');
            $result['progress'] = $this->stepsFor($account);

            return $result;
        }
        if ($action === 'update') {
            try {
                $result = $this->stepUpdateExisting($client, $step, $socCode);
            } catch (\Throwable $e) {
                $result = $this->fail($e->getMessage() ?: 'Update failed.');
            }
            if (!empty($result['needs_choice'])) {
                $this->rememberExists($account, $step, $result);
            } elseif (!empty($result['success'])) {
                $this->markComplete($account, $step, $result['message'] ?? null, $result['person_status'] ?? null);
            } else {
                $this->rememberFailure($account, $step, $result['message'] ?? null, $result['fix'] ?? null);
            }
            $result['progress'] = $this->stepsFor($account);

            return $result;
        }

        $syncNotes = $this->syncWebhookStatus($account, $client);
        if (in_array($step, $this->inferredCompleted($account, $client), true)) {
            $this->markComplete(
                $account,
                $step,
                !empty($syncNotes) ? implode(' ', $syncNotes) : 'Updated from Zenus / stored webhooks.',
                null
            );
            $result = $this->ok(
                !empty($syncNotes) ? implode(' ', $syncNotes) : 'This step is already complete.'
            );
            $result['progress'] = $this->stepsFor($account);

            return $result;
        }

        try {
            if ($step === 'precheck') {
                $result = $this->stepPrecheck($client);
            } elseif ($step === 'person') {
                $result = $this->stepPerson($client);
            } elseif ($step === 'occupation') {
                $result = $this->stepOccupation($client);
            } elseif ($step === 'shareholder') {
                $result = $this->stepShareholder($client);
            } elseif ($step === 'ubo_occupation') {
                $result = $this->stepUboOccupation($client);
            } elseif ($step === 'legal_person') {
                $result = $this->stepLegalPerson($client);
            } elseif ($step === 'ubo_link') {
                $result = $this->stepLinkRelation($client, 'UBO');
            } elseif ($step === 'signer_link') {
                $result = $this->stepLinkRelation($client, 'SIGNER');
            } elseif ($this->isDocumentGroup($step)) {
                $result = $this->stepDocumentGroup($account, $client, $step, $action, $docType);
            } elseif ($step === 'sync') {
                $result = $this->stepSync($account, $client);
            } elseif ($step === 'documents') {
                $result = $this->stepDocuments($client);
            } elseif ($step === 'activate') {
                $result = $this->stepActivate($client);
            } elseif ($step === 'account') {
                $result = $this->stepAccount($account, $client);
            } else {
                $result = $this->fail('Unknown wizard step.');
            }
        } catch (\Throwable $e) {
            Log::channel('zenusLogs')->error('Zenus wizard step failed', [
                'step' => $step,
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            $result = $this->fail($e->getMessage() ?: 'Something went wrong. Please try again.');
            $result['fix'] = $this->classifyFix($client, $result['message'] ?? '', $step);
            $this->rememberFailure($account, $step, $result['message'] ?? null, $result['fix'] ?? null);
            $result['progress'] = $this->stepsFor($account);

            return $result;
        }

        if (!empty($result['needs_choice'])) {
            if (!empty($result['documents'])) {
                $this->rememberDocumentPanel($account, $step, $result);
            } else {
                $this->rememberExists($account, $step, $result);
            }
            $result['progress'] = $this->stepsFor($account);

            return $result;
        }

        if (!empty($result['success'])) {
            $this->markComplete($account, $step, $result['message'] ?? null, $result['person_status'] ?? null);
        } else {
            if (empty($result['fix'])) {
                $result['fix'] = $this->classifyFix($client, $result['message'] ?? '', $step);
            }
            $this->rememberFailure($account, $step, $result['message'] ?? null, $result['fix'] ?? null);
        }

        $result['progress'] = $this->stepsFor($account);

        return $result;
    }

    protected function stepPrecheck($client): array
    {
        $check = $this->zenus->checkZenusRequiredDocuments($client);
        if (!empty($check) && !empty($check->error)) {
            return $this->fail($check->message ?? 'Missing required documents or KYC.');
        }

        return $this->ok('Documents and KYC look complete.');
    }

    protected function stepPerson($client): array
    {
        $existing = $this->zenus->getExistingLinkedClient($client->id);
        if ($existing) {
            return $this->ok('Zenus person already linked.', ['person_id' => $existing->linked_client_id, 'person_status' => $existing->status]);
        }

        $person = $this->zenus->createIndividualPersonOnly($client);
        if (!empty($person->error)) {
            return $this->fail($this->apiMessage($person));
        }

        $this->zenus->addIdentificationNumbers($client, $person->personId);
        $this->zenus->storeLinkedClient($client, $person);

        return $this->ok('Zenus person created.', ['person_id' => $person->personId ?? null, 'person_status' => $person->statusCode ?? null]);
    }

    protected function stepOccupation($client): array
    {
        $linked = $this->requireLinkedClient($client);
        if (!$linked instanceof VirtualBankLinkedClient) {
            return $linked;
        }
        $res = $this->zenus->createPrivatePersonOccupation($client, $linked->linked_client_id);

        return $this->occupationResult($res, 'Occupation saved.');
    }

    protected function stepShareholder($client): array
    {
        $existing = $this->zenus->getExistingLinkedShareholder($client->id);
        $person = $this->zenus->createOrUpdateShareholderPerson($client);
        if (is_object($person) && !empty($person->error) && empty($person->personId)) {
            return $this->fail($this->apiMessage($person));
        }

        $message = is_object($person) && !empty($person->summary)
            ? (string) $person->summary
            : ($existing ? 'UBO already exists. Continuing.' : 'UBO person created.');

        return $this->ok($message, [
            'person_id' => is_object($person) ? ($person->personId ?? null) : null,
        ]);
    }

    protected function stepUboOccupation($client): array
    {
        $linked = $this->requireShareholder($client);
        if (!$linked instanceof VirtualBankLinkedClient) {
            return $linked;
        }
        $res = $this->zenus->createPrivatePersonOccupation($client, $linked->linked_client_id);

        return $this->occupationResult($res, 'UBO occupation saved.');
    }

    protected function stepLegalPerson($client): array
    {
        $existing = $this->zenus->getExistingLinkedClient($client->id);
        $person = $this->zenus->createOrUpdateLegalPerson($client);
        if (is_object($person) && !empty($person->error) && empty($person->personId)) {
            return $this->fail($this->apiMessage($person));
        }

        $message = is_object($person) && !empty($person->summary)
            ? (string) $person->summary
            : ($existing ? 'Legal person already exists. Continuing.' : 'Legal person created.');

        return $this->ok($message, [
            'person_id' => is_object($person) ? ($person->personId ?? ($existing->linked_client_id ?? null)) : null,
        ]);
    }

    protected function stepLinkRelation($client, string $role): array
    {
        $company = $this->requireLinkedClient($client);
        if (!$company instanceof VirtualBankLinkedClient) {
            return $company;
        }
        $record = $this->zenus->getExistingLinkedShareholder($client->id);
        if (!$record) {
            return $this->fail('Create the UBO person first.');
        }
        $shareholder = json_decode($record->response_data);
        $found = $this->zenus->findPersonById($record->linked_client_id);
        $live = is_object($found) ? ($found->data ?? null) : (is_array($found) ? ($found['data'] ?? null) : null);
        if ($live) {
            $shareholder = $live;
        }
        $res = $this->zenus->linkCompanyPersonRelation($client, $company->linked_client_id, $shareholder, $role);
        if (is_object($res) && !empty($res->error)) {
            $message = strtolower($this->apiMessage($res));
            if (strpos($message, 'exist') !== false || strpos($message, 'already') !== false || strpos($message, 'duplicate') !== false) {
                return $this->ok($role === 'UBO' ? 'UBO shareholder link already exists.' : 'Signer link already exists.');
            }

            return $this->fail($this->apiMessage($res));
        }

        return $this->ok($role === 'UBO' ? 'UBO linked as shareholder.' : 'UBO linked as signer.');
    }

    protected function isDocumentGroup(?string $step): bool
    {
        return in_array($step, ['documents', 'ubo_documents', 'company_documents'], true);
    }

    protected function documentGroupMap(string $step): array
    {
        if ($step === 'documents') {
            return [
                'BIOMETRIC_REPORT' => 'Biometric report',
                'PROOF_OF_ADDRESS' => 'Proof of address',
                'FATCA' => 'FATCA',
                'SOURCE_OF_FUNDS' => 'Source of funds',
            ];
        }
        $source = $step === 'company_documents' ? self::COMPANY_DOCUMENTS : self::UBO_DOCUMENTS;
        $map = [];
        foreach ($source as $row) {
            $map[$row[1]] = $row[0];
        }

        return $map;
    }

    protected function stepDocumentGroup(Account $account, $client, string $step, ?string $action, ?string $docType): array
    {
        $linked = $step === 'ubo_documents' ? $this->requireShareholder($client) : $this->requireLinkedClient($client);
        if (!$linked instanceof VirtualBankLinkedClient) {
            return $linked;
        }
        if ($action === 'skip_file' && $docType) {
            $this->setDocStatus($account, $step, $docType, 'skipped', null);
            $action = 'upload_missing';
        }
        if ($action !== 'upload_missing' && $action !== 'reupload') {
            return $this->documentChoice($account, $client, $step, 'Review the files already on this client. Upload only what is still pending, reupload, or skip.');
        }

        $saved = $this->savedDocStatus($account, $step);
        foreach ($this->documentGroupMap($step) as $type => $label) {
            $status = $saved[$type]['status'] ?? 'pending';
            if ($action === 'upload_missing' && in_array($status, ['done', 'skipped'], true)) {
                continue;
            }
            $local = $this->zenus->localDocumentSummary($client, $type);
            if (empty($local['present'])) {
                $this->setDocStatus($account, $step, $type, 'missing', 'No file is on the client yet.');

                return $this->documentChoice($account, $client, $step, $label . ' is not on the client. Upload it, skip this file, or skip the rest.', $type);
            }
            $res = $this->zenus->uploadMappedDocument($client, $linked->linked_client_id, $type);
            if (is_object($res) && !empty($res->error)) {
                if ($this->zenus->responseAlreadyExists($res)) {
                    $this->setDocStatus($account, $step, $type, 'done', null);
                    $saved[$type] = ['status' => 'done'];
                    continue;
                }
                $message = $this->apiMessage($res);
                $this->setDocStatus($account, $step, $type, 'failed', $message);

                return $this->documentChoice($account, $client, $step, $label . ' was not accepted: ' . $message, $type);
            }
            $this->setDocStatus($account, $step, $type, 'done', null);
            $saved[$type] = ['status' => 'done'];
        }

        return $this->ok('Documents in this group are done.');
    }

    protected function documentChoice(Account $account, $client, string $step, string $message, ?string $failedType = null): array
    {
        return [
            'success' => false,
            'needs_choice' => true,
            'message' => $message,
            'failed_type' => $failedType,
            'documents' => $this->documentRows($account, $client, $step, $failedType),
        ];
    }

    protected function documentPanel(Account $account, $client, string $step): array
    {
        $state = $this->state($account);

        return [
            'step' => $step,
            'message' => $state['doc_message'][$step] ?? 'These files are already on the client. Upload the pending ones, reupload, or skip.',
            'failed_type' => $state['doc_failed'][$step] ?? null,
            'rows' => $this->documentRows($account, $client, $step, $state['doc_failed'][$step] ?? null),
        ];
    }

    protected function documentRows(Account $account, $client, string $step, ?string $failedType = null): array
    {
        $saved = $this->savedDocStatus($account, $step);
        $rows = [];
        $pendingShown = false;
        foreach ($this->documentGroupMap($step) as $type => $label) {
            $local = $this->zenus->localDocumentSummary($client, $type);
            $status = $saved[$type]['status'] ?? ($local['present'] ? 'pending' : 'missing');
            $focus = $failedType ? $type === $failedType : (!$pendingShown && !in_array($status, ['done', 'skipped'], true));
            if ($focus && !$failedType) {
                $pendingShown = true;
            }
            $rows[] = [
                'type' => $type,
                'label' => preg_replace('/^Upload /', '', $label),
                'name' => $local['name'] ?? null,
                'present' => !empty($local['present']),
                'status' => $status,
                'error' => $saved[$type]['error'] ?? null,
                'focus' => (bool) $focus,
            ];
        }

        return $rows;
    }

    protected function savedDocStatus(Account $account, string $step): array
    {
        $state = $this->state($account);
        $saved = $state['doc_status'][$step] ?? [];
        $completed = $state['completed'] ?? [];
        $source = $step === 'company_documents' ? self::COMPANY_DOCUMENTS : ($step === 'ubo_documents' ? self::UBO_DOCUMENTS : []);
        foreach ($source as $key => $row) {
            if (in_array($key, $completed, true) && empty($saved[$row[1]]['status'])) {
                $saved[$row[1]] = ['status' => 'done', 'error' => null];
            }
        }

        return $saved;
    }

    protected function setDocStatus(Account $account, string $step, string $type, string $status, ?string $error): void
    {
        $state = $this->state($account);
        $state['doc_status'][$step][$type] = ['status' => $status, 'error' => $error];
        $this->persistState($account, $state);
    }

    protected function rememberDocumentPanel(Account $account, string $step, array $result): void
    {
        $state = $this->state($account);
        $state['current_step'] = $step;
        $state['doc_message'][$step] = $result['message'] ?? null;
        $state['doc_failed'][$step] = $result['failed_type'] ?? null;
        $state['last_fix'] = !empty($result['failed_type']) ? $this->classifyFix(
            ClientHelper::getClientByAccountId($account->id),
            (string) ($result['message'] ?? ''),
            $step
        ) : null;
        if (!empty($result['failed_type'])) {
            $state['last_error'] = $result['message'] ?? null;
        } else {
            $state['last_error'] = null;
            $state['last_message'] = $result['message'] ?? null;
        }
        $this->persistState($account, $state);
    }

    protected function stepSync(Account $account, $client): array
    {
        $notes = $this->syncWebhookStatus($account, $client);

        return $this->ok(!empty($notes) ? implode(' ', $notes) : 'Synced with Zenus. No status change.');
    }

    protected function requireShareholder($client)
    {
        $linked = $this->zenus->getExistingLinkedShareholder($client->id);
        if (!$linked) {
            return $this->fail('Create the UBO person first.');
        }

        return $linked;
    }

    protected function activateShareholder($client)
    {
        $ubo = $this->requireShareholder($client);
        if (!$ubo instanceof VirtualBankLinkedClient) {
            return $ubo;
        }
        $found = $this->zenus->findPersonById($ubo->linked_client_id);
        $person = is_object($found) ? ($found->data ?? $found) : (is_array($found) ? ($found['data'] ?? $found) : null);
        $status = is_object($person) ? ($person->statusCode ?? null) : (is_array($person) ? ($person['statusCode'] ?? null) : null);
        if (strtoupper((string) $status) === 'ACTIVE') {
            $ubo->status = 'ACTIVE';
            $ubo->kyc_status = 'ACTIVE';
            $ubo->save();

            return true;
        }

        $activated = $this->zenus->activatePerson($ubo->linked_client_id);

        // Check if activation failed due to missing identification expiry date
        if (!empty($activated->error) && $this->hasIdExpirationError($activated)) {
            // 1. Add identification numbers/expiry date
            $this->zenus->addIdentificationNumbers($client, $person->personId);

            // 2. Retry person activation
            $activated = $this->zenus->activatePerson($ubo->linked_client_id);
        }

        if (is_object($activated) && !empty($activated->error)) {
            return $this->fail(
                'The company cannot be activated until the UBO is active. ' . $this->apiMessage($activated),
                ['can_retry' => true]
            );
        }
        $found = $this->zenus->findPersonById($ubo->linked_client_id);
        $person = is_object($found) ? ($found->data ?? $found) : (is_array($found) ? ($found['data'] ?? $found) : null);
        $status = is_object($person) ? ($person->statusCode ?? $ubo->status) : $ubo->status;
        $ubo->status = $status;
        $ubo->kyc_status = $status;
        $ubo->save();
        if (strtoupper((string) $status) !== 'ACTIVE') {
            return $this->fail(
                'UBO ' . $ubo->linked_client_id . ' is ' . ($status ?: 'not active') . '. Zenus must mark the shareholder and signer ACTIVE before the company can be activated. Retry this step after that review finishes.',
                ['person_status' => $status, 'can_retry' => true]
            );
        }

        return true;
    }

    protected function stepActivate($client): array
    {
        $linked = $this->requireLinkedClient($client);
        if (!$linked instanceof VirtualBankLinkedClient) {
            return $linked;
        }
        if (($client->type ?? '') === 'company') {
            $uboReady = $this->activateShareholder($client);
            if ($uboReady !== true) {
                return $uboReady;
            }
        }
        $personId = $linked->linked_client_id;
        $activated = $this->zenus->activatePerson($personId);

        // Check if activation failed due to missing identification expiry date
        if (!empty($activated->error) && $this->hasIdExpirationError($activated)) {
            // 1. Add identification numbers/expiry date
            $this->zenus->addIdentificationNumbers($client, $personId);

            // 2. Retry person activation
            $activated = $this->zenus->activatePerson($personId);
        }

        if (is_object($activated) && !empty($activated->error)) {
            return $this->fail($this->apiMessage($activated), ['can_retry' => true]);
        }

        $found = $this->zenus->findPersonById($personId);
        $person = is_object($found) ? ($found->data ?? $found) : $found;
        $status = is_object($person) ? ($person->statusCode ?? $linked->status) : $linked->status;
        $linked->status = $status;
        $linked->kyc_status = $status;
        $linked->save();

        if (strtoupper((string) $status) !== 'ACTIVE') {
            return $this->fail(
                'Zenus person status is ' . ($status ?: 'unknown') . '. Wait for onboarding to complete, then retry this step.',
                ['person_status' => $status, 'can_retry' => true]
            );
        }

        return $this->ok('Person is ACTIVE.', ['person_status' => $status]);
    }

    protected function stepAccount(Account $account, $client): array
    {
        $linked = $this->requireLinkedClient($client);
        if (!$linked instanceof VirtualBankLinkedClient) {
            return $linked;
        }
        if (strtoupper((string) $linked->status) !== 'ACTIVE') {
            return $this->fail('Person must be ACTIVE before creating the account. Retry Activate.');
        }

        $exists = VirtualBankAccount::where('client_id', $client->id)
            ->where('currency_id', $account->currency->id)
            ->where('provider_name', 'zenus')
            ->first();
        if ($exists) {
            $account->virtual_bank_account_id = $exists->id;
            $account->save();

            return $this->ok('Zenus account already exists.');
        }

        $bank = $this->zenus->storeZenusAccount($account, $linked);
        if (is_object($bank) && (!empty($bank->error) || !empty($bank->errorCode))) {
            $message = $bank->message ?? 'Account create failed.';
            if (($bank->status_code ?? null) === 404) {
                return $this->fail($message . ' Refresh later if Zenus is still provisioning.', ['can_retry' => true]);
            }

            return $this->fail($this->apiMessage($bank));
        }

        $account->virtual_bank_account_id = $bank->id;
        $account->save();

        $userWithAPIRole = User::whereHas('roles', function ($query) {
            $query->where('name', 'API');
        })->whereHas('linkClients', function ($query) use ($client) {
            $query->where('client_id', $client->id);
        })->first();
        if ($userWithAPIRole) {
            Common::sendWebhookNotification($account, $userWithAPIRole, 'account', 'The named account has been created successfully.', 'Created');
        }

        return $this->ok('Zenus virtual account created.');
    }

    protected function syncWebhookStatus(Account $account, $client): array
    {
        $notes = [];
        if (!$client) {
            return $notes;
        }

        $linked = $this->zenus->getExistingLinkedClient($client->id);
        if ($linked) {
            $personNote = $this->refreshPersonFromZenus($linked);
            if ($personNote) {
                $notes[] = $personNote;
            }
            $webhookNote = $this->applyStoredWebhooks($linked->linked_client_id, $linked, null);
            if ($webhookNote) {
                $notes[] = $webhookNote;
            }
        }

        $virtual = VirtualBankAccount::where('client_id', $client->id)
            ->where('currency_id', optional($account->currency)->id)
            ->where('provider_name', 'zenus')
            ->first();
        if ($virtual && !empty($virtual->virtual_bank_account_id)) {
            $accountNote = $this->refreshAccountFromZenus($virtual);
            if ($accountNote) {
                $notes[] = $accountNote;
            }
            $webhookNote = $this->applyStoredWebhooks($virtual->virtual_bank_account_id, $linked, $virtual);
            if ($webhookNote) {
                $notes[] = $webhookNote;
            }
        }

        return $notes;
    }

    protected function refreshPersonFromZenus(VirtualBankLinkedClient $linked): ?string
    {
        $found = $this->zenus->findPersonById($linked->linked_client_id);
        $person = is_object($found) ? ($found->data ?? $found) : $found;
        if (!is_object($person) || !empty($person->error) || empty($person->statusCode)) {
            return null;
        }
        $status = (string) $person->statusCode;
        if ((string) $linked->status === $status && (string) $linked->kyc_status === $status) {
            return null;
        }
        $linked->status = $status;
        $linked->kyc_status = $status;
        $linked->save();

        return 'Person status updated from Zenus: ' . $status . '.';
    }

    protected function refreshAccountFromZenus(VirtualBankAccount $virtual): ?string
    {
        $remote = $this->zenus->getAccountOfById($virtual->virtual_bank_account_id);
        if (!is_object($remote) || !empty($remote->error)) {
            return null;
        }
        $notes = [];
        if (!empty($remote->statusCode) && (string) $virtual->status !== (string) $remote->statusCode) {
            $virtual->status = $remote->statusCode;
            $notes[] = 'Account status updated from Zenus: ' . $remote->statusCode . '.';
        }
        if ($this->mergeAccountNumbers($virtual, $remote->accountNumbers ?? null)) {
            $notes[] = 'Account numbers / VRN updated from Zenus.';
        } else {
            $virtual->save();
        }

        return empty($notes) ? null : implode(' ', $notes);
    }

    protected function mergeAccountNumbers(VirtualBankAccount $virtual, $accountNumbers): bool
    {
        if (empty($accountNumbers)) {
            return false;
        }
        $local = json_decode($virtual->account_info, true);
        if (!is_array($local)) {
            $local = [];
        }
        $changed = false;
        $remoteList = json_decode(json_encode($accountNumbers), true);
        if (!is_array($remoteList)) {
            return false;
        }
        foreach ($remoteList as $remoteNumber) {
            if (!is_array($remoteNumber)) {
                continue;
            }
            $subtype = $remoteNumber['accountNumberSubtype'] ?? null;
            $value = $remoteNumber['accountNumber'] ?? ($remoteNumber['iban'] ?? null);
            $exists = collect($local)->first(function ($row) use ($subtype, $value) {
                if (!is_array($row)) {
                    return false;
                }
                if ($subtype && ($row['accountNumberSubtype'] ?? null) === $subtype) {
                    return true;
                }

                return $value && (($row['accountNumber'] ?? null) === $value || ($row['iban'] ?? null) === $value);
            });
            if (!$exists) {
                $local[] = $remoteNumber;
                $changed = true;
            }
        }
        if ($changed) {
            $virtual->account_info = json_encode($local);
            $virtual->save();
        }

        return $changed;
    }

    protected function applyStoredWebhooks(string $externalId, ?VirtualBankLinkedClient $linked, ?VirtualBankAccount $virtual): ?string
    {
        $rows = DB::table('crypto_transactions_log')
            ->where('crypto_system', 'Zenus')
            ->where('transaction_id', $externalId)
            ->orderByDesc('id')
            ->limit(25)
            ->get();
        if ($rows->isEmpty()) {
            return null;
        }

        $notes = [];
        foreach ($rows as $row) {
            $payload = json_decode($row->details);
            if (!$payload) {
                continue;
            }
            $data = $payload->data ?? $payload;
            $code = $payload->notificationTypeCode ?? ($data->notificationTypeCode ?? null);
            $status = $data->statusCode ?? ($payload->statusCode ?? null);
            if ($linked && $status && in_array(strtoupper((string) $code), ['PERSON_ACTIVATED', 'CUSTOMER_ACTIVATED', 'PERSON_STATUS_CHANGED'], true)) {
                if ((string) $linked->status !== (string) $status) {
                    $linked->status = $status;
                    $linked->kyc_status = $status;
                    $linked->save();
                    $notes[] = 'Person status from webhook ' . $code . ': ' . $status . '.';
                }
            }
            if ($virtual && $status && in_array(strtoupper((string) $code), ['ACCOUNT_ACTIVATED', 'ACCOUNT_BLOCKED', 'ACCOUNT_CLOSED'], true)) {
                if ((string) $virtual->status !== (string) $status) {
                    $virtual->status = $status;
                    $virtual->save();
                    $notes[] = 'Account status from webhook ' . $code . ': ' . $status . '.';
                }
            }
            if ($virtual && strtoupper((string) $code) === 'ACCOUNT_NUMBER_CREATED') {
                $numbers = $data->accountNumbers ?? ($payload->accountNumbers ?? null);
                if ($this->mergeAccountNumbers($virtual, $numbers)) {
                    $notes[] = 'Account numbers updated from ACCOUNT_NUMBER_CREATED webhook.';
                }
            }
        }

        return empty($notes) ? null : implode(' ', array_unique($notes));
    }

    protected function requireLinkedClient($client)
    {
        $linked = $this->zenus->getExistingLinkedClient($client->id);
        if (!$linked) {
            return $this->fail('Create the Zenus person first.');
        }

        return $linked;
    }

    protected function inferredCompleted(Account $account, $client): array
    {
        $done = [];
        $exists = VirtualBankAccount::where('client_id', $client->id)
            ->where('currency_id', optional($account->currency)->id)
            ->where('provider_name', 'zenus')
            ->exists();
        if ($exists && ($client->type ?? '') === 'company') {
            return array_keys(self::companySteps());
        }
        $linked = $this->zenus->getExistingLinkedClient($client->id);
        if ($linked && ($client->type ?? '') !== 'company') {
            $done[] = 'person';
        }
        if ($linked && strtoupper((string) $linked->status) === 'ACTIVE') {
            $done[] = 'activate';
        }
        if ($exists) {
            $done[] = 'account';
        }

        return $done;
    }

    public function clearState(Account $account): void
    {
        Cache::forget($this->cacheKey($account));
    }

    protected function clearStaleDocumentError(Account $account, $client, array &$state): void
    {
        $message = (string) ($state['last_error'] ?? '');
        if ($message === '' || stripos($message, 'Missing required') === false || !$client) {
            return;
        }

        $identifiers = ($client->type ?? '') === 'company'
            ? ['certificate_of_incorporation', 'memorandum_of_association', 'bank_statement', 'certificate_of_business_license']
            : ['utility_bill', 'bank_statement'];
        foreach ($identifiers as $identifier) {
            if (!$this->zenus->clientHasRequiredDocument($client, $identifier)) {
                return;
            }
        }

        $state['last_error'] = null;
        $state['last_fix'] = null;
        $state['last_message'] = null;
        $this->persistState($account, $state);
    }

    public function isAutoContinue(): bool
    {
        try {
            $cfg = Common::getModulesConfig('Zenus');
            $value = $cfg['wizard_auto_continue']['value'] ?? '0';

            return $value === '1' || $value === 1 || $value === true || $value === 'auto';
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function classifyFix($client, string $message, string $step): ?array
    {
        if (empty($this->fixFields($client, $message))) {
            return null;
        }

        return [
            'kind' => 'required',
            'client_id' => $client->id,
            'label' => 'Add missing details',
        ];
    }

    public function fixFields($client, ?string $message = null): array
    {
        $fields = $this->fieldsForMessage($client, (string) $message);
        $seen = [];
        foreach ($fields as $field) {
            $seen[$field['key']] = true;
        }
        foreach ($this->missingRequiredFields($client) as $field) {
            if (empty($seen[$field['key']])) {
                $fields[] = $field;
            }
        }

        return $fields;
    }

    public function rememberIdExpiry(Account $account, string $personId, string $expiry): void
    {
        $state = $this->state($account);
        $state['id_expiry'][$personId] = $expiry;
        $this->persistState($account, $state);
    }

    public function lastError(Account $account): ?string
    {
        $state = $this->state($account);

        return $this->readableStoredError($state['last_error'] ?? null);
    }

    public function linkedPersonId($client): ?string
    {
        $linked = $this->zenus->getExistingLinkedClient($client->id);

        return $linked->linked_client_id ?? null;
    }

    protected function storedIdExpiry($client, string $personId): ?string
    {
        if (($client->type ?? '') === 'company') {
            $expiry = optional(optional($client->company)->contact)->passport_expiry;
        } else {
            $expiry = optional($client->individual)->passport_expiry_date;
        }
        if (!$this->isBlank($expiry)) {
            return substr((string) $expiry, 0, 10);
        }
        $accounts = Account::where('client_id', $client->id)->pluck('id');
        foreach ($accounts as $id) {
            $state = Cache::get('zenus_wizard_' . $id, []);
            if (!empty($state['id_expiry'][$personId])) {
                return (string) $state['id_expiry'][$personId];
            }
        }

        return null;
    }

    protected function fieldsForMessage($client, string $message): array
    {
        if (!$client) {
            return [];
        }
        $text = strtolower($message);
        $aboutId = strpos($text, 'identification') !== false
            || strpos($text, 'expiration') !== false
            || strpos($text, 'expiry') !== false
            || strpos($text, 'passport') !== false;
        if (!$aboutId) {
            return [];
        }

        $reason = trim($message) !== '' ? $message : 'Zenus requires this identification detail.';
        $fields = [];
        if (($client->type ?? '') === 'company') {
            $contact = optional($client->company)->contact;
            $fields[] = [
                'key' => 'passport_number',
                'label' => 'UBO passport number',
                'type' => 'text',
                'reason' => $reason,
                'value' => optional($contact)->passport_number ?? '',
            ];
            $fields[] = [
                'key' => 'passport_expiry',
                'label' => 'UBO passport expiry date',
                'type' => 'date',
                'reason' => $reason,
                'value' => $this->dateValue(optional($contact)->passport_expiry),
            ];
            $fields[] = [
                'key' => 'id_expiry_date',
                'label' => 'Company identification expiry date',
                'type' => 'date',
                'reason' => $reason,
                'value' => '',
            ];
        } else {
            $individual = $client->individual;
            $fields[] = [
                'key' => 'passport_number',
                'label' => 'Passport number',
                'type' => 'text',
                'reason' => $reason,
                'value' => optional($individual)->passport_number ?? '',
            ];
            $fields[] = [
                'key' => 'passport_expiry_date',
                'label' => 'Passport expiry date',
                'type' => 'date',
                'reason' => $reason,
                'value' => $this->dateValue(optional($individual)->passport_expiry_date),
            ];
        }

        return $fields;
    }

    protected function dateValue($value): string
    {
        if ($this->isBlank($value)) {
            return '';
        }

        return substr((string) $value, 0, 10);
    }

    public function missingRequiredFields($client): array
    {
        if (!$client) {
            return [];
        }

        $missing = [];
        $invalidCharsPattern = "/[\/\-\?\:\(\)\.,'\+]/";
        $requiredReason = 'This is required';

        $identifiers = ($client->type ?? '') === 'company'
            ? ['certificate_of_incorporation', 'memorandum_of_association', 'bank_statement', 'certificate_of_business_license']
            : ['utility_bill', 'bank_statement'];
        $type = !empty($client->is_bank) ? 'bank' : ($client->type ?? 'individual');
        $docs = $this->zenus->getRequiredDocuments($client);
        $uploaded = $docs ? $docs->pluck('identifier')->all() : [];
        $labels = [
            'certificate_of_business_license' => 'Certificate of business license',
            'certificate_of_incorporation' => 'Certificate of incorporation',
            'memorandum_of_association' => 'Memorandum of association',
            'bank_statement' => 'Bank statement',
            'utility_bill' => 'Utility bill',
        ];
        foreach ($identifiers as $identifier) {
            if (in_array($identifier, $uploaded, true) || $this->zenus->clientHasRequiredDocument($client, $identifier)) {
                continue;
            }
            $conf = $this->documentConfig($identifier, $type);
            $missing[] = [
                'key' => $conf ? 'file-' . $conf->id : 'doc-' . $identifier,
                'label' => $conf->title ?? ($labels[$identifier] ?? $identifier),
                'type' => 'file',
                'reason' => $requiredReason,
                'multiple' => $conf ? !empty($conf->isMultiple) : false,
                'file_id' => $conf->id ?? null,
                'identifier' => $identifier,
            ];
        }

        $passportUrl = null;
        try {
            $passportUrl = Common::getClientPassportUrl($client->id, $client->type);
        } catch (\Throwable $e) {
            $passportUrl = null;
        }
        if ($this->isBlank($passportUrl)) {
            $passportConfig = \App\Models\ClientFileConfig::where('identifier', 'passport_copy')
                ->where('type', $client->type)
                ->first();
            if ($passportConfig) {
                $missing[] = [
                    'key' => 'file-' . $passportConfig->id,
                    'label' => $passportConfig->title ?: 'Passport copy',
                    'type' => 'file',
                    'reason' => $requiredReason,
                    'multiple' => false,
                    'file_id' => $passportConfig->id,
                ];
            }
        }

        $kyc = \App\Models\ClientKYC::where('client_id', $client->id)->whereNull('director_id')->first();
        if ($this->isBlank(optional($kyc)->selfie_photo_url)) {
            $missing[] = [
                'key' => 'selfie_photo_url',
                'label' => 'Selfie photo',
                'type' => 'file',
                'reason' => $requiredReason,
            ];
        }

        if (($client->type ?? '') === 'company') {
            $company = $client->company;
            $contact = optional($company)->contact;
            $this->pushIfBlank($missing, 'soc_code', 'Occupation (SOC)', optional($contact)->soc_code, 'select', $requiredReason);
            $this->pushIfBlank($missing, 'naics_code', 'Industry sector (NAICS)', optional($company)->naics_code, 'text', $requiredReason);
            $this->pushIfBlank($missing, 'tax_number', 'Tax number', optional($company)->tax_number, 'text', $requiredReason);
            $this->pushIfBlank($missing, 'company_city', 'Company city', optional($company)->company_city, 'text', $requiredReason);
            $this->pushIfBlank($missing, 'zip_code', 'Company ZIP / postal code', optional($company)->zip_code, 'text', $requiredReason);
            $this->pushIfBlank($missing, 'business_reg_street_address', 'Business registration street', optional($company)->business_reg_street_address, 'text', $requiredReason);
            if (!$this->isBlank(optional($company)->business_reg_street_address) && preg_match($invalidCharsPattern, (string) $company->business_reg_street_address)) {
                $missing[] = [
                    'key' => 'business_reg_street_address',
                    'label' => 'Business registration street',
                    'type' => 'text',
                    'reason' => "This is required. Remove unsupported characters (/ - ? : ( ) . , ' +).",
                    'value' => $company->business_reg_street_address,
                ];
            }
            $this->pushIfBlank($missing, 'company_region', 'Company region', optional($company)->company_region, 'text', $requiredReason);
            $this->pushIfBlank($missing, 'contact_street_address', 'Contact street address', optional($contact)->street_address, 'text', $requiredReason);
            $this->pushIfBlank($missing, 'contact_city', 'Contact city', optional($contact)->contact_city, 'text', $requiredReason);
            $this->pushIfBlank($missing, 'contact_state_region', 'Contact state / region', optional($contact)->state_region, 'text', $requiredReason);
            $this->pushIfBlank($missing, 'contact_zip_code', 'Contact ZIP / postal code', optional($contact)->zip_code, 'text', $requiredReason);
        } else {
            $individual = $client->individual;
            $employer = optional($individual)->employer;
            $this->pushIfBlank($missing, 'soc_code', 'Occupation (SOC)', optional($employer)->soc_code, 'select', $requiredReason);
            $this->pushIfBlank($missing, 'city', 'City', optional($individual)->city, 'text', $requiredReason);
            $this->pushIfBlank($missing, 'street_address', 'Street address', optional($individual)->street_address, 'text', $requiredReason);
            if (!$this->isBlank(optional($individual)->street_address) && preg_match($invalidCharsPattern, (string) $individual->street_address)) {
                $missing[] = [
                    'key' => 'street_address',
                    'label' => 'Street address',
                    'type' => 'text',
                    'reason' => "This is required. Remove unsupported characters (/ - ? : ( ) . , ' +).",
                    'value' => $individual->street_address,
                ];
            }
            $this->pushIfBlank($missing, 'zip_code', 'ZIP / postal code', optional($individual)->zip_code, 'text', $requiredReason);
            $this->pushIfBlank($missing, 'state_region', 'State / region', optional($individual)->state_region, 'text', $requiredReason);
        }

        return $missing;
    }

    public function documentConfig(string $identifier, string $type)
    {
        $conf = \App\Models\ClientFileConfig::where('identifier', $identifier)->where('type', $type)->first();
        if ($conf) {
            return $conf;
        }

        $conf = \App\Models\ClientFileConfig::where('identifier', $identifier)->first();
        if ($conf) {
            return $conf;
        }

        $needles = [
            'certificate_of_business_license' => ['business license', 'business licence'],
            'certificate_of_incorporation' => ['certificate of incorporation', 'incorporation'],
            'memorandum_of_association' => ['memorandum', 'articles of association'],
            'bank_statement' => ['bank statement'],
            'utility_bill' => ['utility bill', 'utility'],
        ];
        foreach ($needles[$identifier] ?? [] as $needle) {
            $conf = \App\Models\ClientFileConfig::where('type', $type)
                ->where('title', 'like', '%' . $needle . '%')
                ->first();
            if ($conf) {
                if ($this->isBlank($conf->identifier)) {
                    $conf->identifier = $identifier;
                    $conf->save();
                }

                return $conf;
            }
        }

        return null;
    }

    protected function pushIfBlank(array &$missing, string $key, string $label, $value, string $type, string $reason): void
    {
        if ($this->isBlank($value)) {
            $missing[] = [
                'key' => $key,
                'label' => $label,
                'type' => $type,
                'reason' => $reason,
                'value' => '',
            ];
        }
    }

    protected function isBlank($value): bool
    {
        return $value === null || trim((string) $value) === '';
    }

    protected function cacheKey(Account $account): string
    {
        return 'zenus_wizard_' . $account->id;
    }

    protected function persistTtl()
    {
        return now()->addDays(30);
    }

    protected function state(Account $account): array
    {
        return Cache::get($this->cacheKey($account), ['completed' => []]);
    }

    protected function persistState(Account $account, array $state): void
    {
        Cache::put($this->cacheKey($account), $state, $this->persistTtl());
    }

    protected function markComplete(Account $account, string $step, ?string $message, ?string $personStatus): void
    {
        $state = $this->state($account);
        $completed = $state['completed'] ?? [];
        if (!in_array($step, $completed, true)) {
            $completed[] = $step;
        }
        $state['completed'] = $completed;
        $state['last_message'] = $message;
        $state['last_error'] = null;
        $state['last_fix'] = null;
        unset($state['current_step'], $state['exists_choice'], $state['reopen_step']);
        unset($state['doc_failed'][$step], $state['doc_message'][$step]);
        if ($personStatus) {
            $state['person_status'] = $personStatus;
        }
        $this->persistState($account, $state);
    }

    protected function rememberFailure(Account $account, string $step, ?string $message, $fix): void
    {
        $state = $this->state($account);
        $state['last_message'] = $message;
        $state['last_error'] = $message;
        $state['last_fix'] = $fix;
        $state['current_step'] = $step;
        $this->persistState($account, $state);
    }

    protected function nextStepKey(array $defs, array $completed): ?string
    {
        foreach (array_keys($defs) as $key) {
            if (!in_array($key, $completed, true)) {
                return $key;
            }
        }

        return null;
    }

    protected function ok(string $message, array $extra = []): array
    {
        return array_merge(['success' => true, 'message' => $message, 'can_retry' => false], $extra);
    }

    protected function fail(string $message, array $extra = []): array
    {
        return array_merge(['success' => false, 'message' => $message, 'can_retry' => true], $extra);
    }

    protected function occupationResult($res, string $okMessage): array
    {
        if (is_object($res) && !empty($res->already_exists)) {
            return [
                'success' => false,
                'needs_choice' => true,
                'message' => 'This value already exists at Zenus.',
                'current_value' => $res->current_value ?: 'Already saved at Zenus',
                'new_value' => $res->new_value,
            ];
        }
        if (is_object($res) && !empty($res->error)) {
            return $this->fail($this->apiMessage($res));
        }

        return $this->ok(is_object($res) && !empty($res->message) ? (string) $res->message : $okMessage);
    }

    protected function stepUpdateExisting($client, string $step, ?string $socCode = null): array
    {
        if (!in_array($step, ['occupation', 'ubo_occupation'], true)) {
            return $this->fail('This step cannot be updated from here. Skip it or go back.');
        }
        $linked = $step === 'ubo_occupation'
            ? $this->requireShareholder($client)
            : $this->requireLinkedClient($client);
        if (!$linked instanceof VirtualBankLinkedClient) {
            return $linked;
        }
        $code = trim((string) $socCode);
        if ($code === '') {
            $code = ($client->type ?? '') === 'company'
                ? (string) optional(optional($client->company)->contact)->soc_code
                : (string) optional(optional($client->individual)->employer)->soc_code;
        }
        if ($code === '') {
            return $this->fail('Choose an occupation code.');
        }
        $this->saveClientSoc($client, $code);
        $res = $this->zenus->updatePrivatePersonOccupation($linked->linked_client_id, $code);
        if (is_object($res) && !empty($res->error)) {
            return $this->fail($this->apiMessage($res));
        }

        return $this->ok('Occupation updated.');
    }

    protected function saveClientSoc($client, string $socCode): void
    {
        if (($client->type ?? '') === 'company') {
            $contact = optional($client->company)->contact;
            if ($contact) {
                $contact->soc_code = $socCode;
                $contact->save();
            }

            return;
        }
        $employer = optional($client->individual)->employer;
        if ($employer) {
            $employer->soc_code = $socCode;
            $employer->save();
        }
    }

    protected function goBack(Account $account, $client, string $step): array
    {
        $defs = ($client->type ?? '') === 'company' ? self::companySteps() : self::STEPS_INDIVIDUAL;
        $keys = array_keys($defs);
        $index = array_search($step, $keys, true);
        if ($index === false || $index < 1) {
            $result = $this->fail('There is no previous step.');
            $result['progress'] = $this->stepsFor($account);

            return $result;
        }
        $previous = $keys[$index - 1];
        $state = $this->state($account);
        $state['completed'] = array_values(array_diff($state['completed'] ?? [], [$previous, $step]));
        $state['current_step'] = $previous;
        $state['reopen_step'] = $previous;
        $state['last_error'] = null;
        $state['last_message'] = null;
        $state['last_fix'] = null;
        unset($state['exists_choice']);
        $this->persistState($account, $state);
        $result = $this->ok('Back to ' . ($defs[$previous] ?? 'the previous step') . '.');
        $result['progress'] = $this->stepsFor($account);

        return $result;
    }

    protected function previousStepKey(array $defs, ?string $next): ?string
    {
        if (!$next) {
            return null;
        }
        $keys = array_keys($defs);
        $index = array_search($next, $keys, true);
        if ($index === false || $index < 1) {
            return null;
        }

        return $keys[$index - 1];
    }

    protected function rememberExists(Account $account, string $step, array $result): void
    {
        $state = $this->state($account);
        $state['current_step'] = $step;
        $state['last_error'] = $result['message'] ?? 'This value already exists at Zenus.';
        $state['last_message'] = $state['last_error'];
        $state['last_fix'] = null;
        $state['exists_choice'] = [
            'message' => $state['last_error'],
            'current_value' => $result['current_value'] ?? null,
            'new_value' => $result['new_value'] ?? null,
        ];
        $this->persistState($account, $state);
    }

    protected function existsChoiceFor(Account $account, $client, array $state, ?string $next): ?array
    {
        $step = $state['current_step'] ?? $next;
        $choice = $state['exists_choice'] ?? null;
        $message = (string) ($state['last_error'] ?? '');
        if (!$choice && stripos($message, 'already exists') === false) {
            return null;
        }
        if (!$choice) {
            $choice = [
                'message' => 'This value already exists at Zenus.',
                'current_value' => null,
                'new_value' => null,
            ];
        }
        $choice['step'] = $step;
        $choice['soc'] = in_array($step, ['occupation', 'ubo_occupation'], true);
        if ($choice['soc']) {
            if (empty($choice['new_value']) && $client) {
                $choice['new_value'] = ($client->type ?? '') === 'company'
                    ? optional(optional($client->company)->contact)->soc_code
                    : optional(optional($client->individual)->employer)->soc_code;
            }
            $choice['options'] = DB::table('soc_codes')->orderBy('job_title')->get(['soc_code', 'job_title'])
                ->map(function ($row) {
                    return ['code' => $row->soc_code, 'title' => $row->job_title];
                })->all();
        }

        return $choice;
    }

    protected function readableStoredError($message): ?string
    {
        if ($message === null || $message === '') {
            return null;
        }
        if (!is_scalar($message)) {
            return 'Zenus returned an unexpected response. Continuing is safe if this person already exists.';
        }
        $decoded = json_decode((string) $message, true);
        if (is_array($decoded)) {
            return $this->zenus->describeApiError($decoded);
        }

        return (string) $message;
    }

    protected function apiMessage($res): string
    {
        $raw = null;
        if (is_object($res) && isset($res->message)) {
            $raw = $res->message;
        } elseif (is_array($res) && isset($res['message'])) {
            $raw = $res['message'];
        }
        if (is_array($raw)) {
            return $this->zenus->describeApiError($raw);
        }
        $message = trim(is_scalar($raw) ? (string) $raw : '');
        if ($message === '') {
            return 'Zenus request failed. Please try again.';
        }
        $decoded = json_decode($message, true);
        if (is_array($decoded)) {
            return $this->zenus->describeApiError($decoded);
        }

        return $message;
    }

    /**
     * Check if the Zenus response contains a PERSON_ID_EXPIRATION_DATE validation error.
     *
     * @param  object  $response
     * @return bool
     */
    private function hasIdExpirationError($response): bool
    {
        $validationErrors = data_get($response, 'raw.validationErrors', []);

        foreach ($validationErrors as $fieldError) {
            $errors = data_get($fieldError, 'errors', []);
            foreach ($errors as $error) {
                if (data_get($error, 'code') === 'PERSON_ID_EXPIRATION_DATE') {
                    return true;
                }
            }
        }

        return false;
    }
}
