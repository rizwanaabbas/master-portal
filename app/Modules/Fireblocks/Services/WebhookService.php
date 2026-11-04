<?php

namespace App\Modules\Fireblocks\Services;

use App\Helpers\Common;
use App\Helpers\CryptoLogHelper;
use App\Helpers\EmailNotification;
use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\CryptoAddress;
use App\Models\FireblocksTransaction;
use App\Models\TRRequest;
use App\Modules\Fireblocks\Helpers\FireblocksStatusHelper;
use App\Modules\Fireblocks\Models\CryptoBeneficiary;
use App\Modules\FeeEngine\Models\FeeTransactionCharge;
use App\Modules\FeeEngine\Services\ConfigService;
use App\Modules\FeeEngine\Services\FeeCalculationService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookService
{
    /**
     * Validate a Fireblocks webhook signature.
     * Only Fireblocks V2 detached JWS signatures are accepted.
     *
     * @param string $payload   Raw request body
     * @param string $signature Signature value from the header
     * @return bool
     */
    public function validateSignature(string $payload, string $signature): bool
    {
        return $this->validateJwsV2($payload, $signature);
    }

    /**
     * JWKS endpoints per Fireblocks environment.
     * @see https://developers.fireblocks.com/reference/validating-webhooks
     */
    const JWKS_ENDPOINTS = [
        'production' => 'https://keys.fireblocks.io/.well-known/jwks.json',
        'eu'         => 'https://eu-keys.fireblocks.io/.well-known/jwks.json',
        'eu2'        => 'https://eu2-keys.fireblocks.io/.well-known/jwks.json',
        'sandbox'    => 'https://sandbox-keys.fireblocks.io/.well-known/jwks.json',
    ];

    /**
     * Validate Fireblocks Webhooks V2 signature (JWS with detached payload).
     * Format: base64url(header) + ".." + base64url(signature)
     * Signing input: base64url(header) + "." + base64url(payload)
     *
     * Fireblocks V2 webhooks are signed with an RS512 key published at a JWKS
     * endpoint and selected by the `kid` in the protected JWS header.
     *
     * @param string $payload
     * @param string $jws
     * @return bool
     */
    protected function validateJwsV2(string $payload, string $jws): bool
    {
        $parts = explode('.', $jws);
        if (count($parts) !== 3 || !empty($parts[1])) {
            Log::error('Fireblocks webhook V2 JWS format is invalid');
            return false;
        }

        $headerB64 = $parts[0];
        $signatureB64 = $parts[2];

        // Decode JWS header
        $headerJson = $this->base64urlDecode($headerB64);
        if ($headerJson === false) {
            Log::error('Fireblocks webhook V2 JWS header is not valid base64url');
            return false;
        }

        $header = json_decode($headerJson, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($header)) {
            Log::error('Fireblocks webhook V2 JWS header is not valid JSON');
            return false;
        }

        $alg = $header['alg'] ?? null;
        $kid = $header['kid'] ?? null;

        if ($alg !== 'RS512' || !is_string($kid) || $kid === '') {
            Log::warning('Fireblocks webhook V2 JWS header is not supported', [
                'alg' => $alg,
                'kid_present' => is_string($kid) && $kid !== '',
            ]);
            return false;
        }

        Log::info('Fireblocks webhook V2 JWS header parsed', [
            'alg' => $alg,
            'kid' => $kid,
        ]);

        // Reconstruct the signing input: base64url(header) + "." + base64url(payload)
        $payloadB64 = $this->base64urlEncode($payload);
        $signingInput = $headerB64 . '.' . $payloadB64;

        $decodedSignature = $this->base64urlDecode($signatureB64);
        if ($decodedSignature === false) {
            Log::error('Fireblocks webhook V2 signature is not valid base64url');
            return false;
        }

        $jwkPem = $this->resolveJwksPublicKey($kid);
        if (!$jwkPem) {
            Log::warning('Fireblocks webhook V2: no JWKS key found for kid', ['kid' => $kid]);
            return false;
        }

        if (openssl_verify($signingInput, $decodedSignature, $jwkPem, OPENSSL_ALGO_SHA512) === 1) {
            Log::info('Fireblocks webhook V2 signature verified via JWKS', ['kid' => $kid, 'alg' => $alg]);
            return true;
        }

        Log::warning('Fireblocks webhook V2 signature verification failed', [
            'alg'             => $alg,
            'kid'             => $kid,
            'payload_length'  => strlen($payload),
            'payload_hash'    => hash('sha256', $payload),
        ]);

        return false;
    }

    /**
     * Fetch the Fireblocks JWKS for the configured environment and return the PEM
     * public key matching the given `kid`, or null if not found / fetch failed.
     * Cached for 1 hour, honouring the JWKS endpoint's own Cache-Control window.
     */
    protected function resolveJwksPublicKey(string $kid): ?string
    {
        $envType = $this->resolveJwksEnvironment();
        if ($envType === null) {
            return null;
        }

        $endpoint = self::JWKS_ENDPOINTS[$envType];

        $cacheKey = 'fireblocks_jwks_' . $envType;
        $jwks = Cache::remember($cacheKey, 3600, function () use ($endpoint) {
            try {
                $response = Http::timeout(10)->get($endpoint);
                if (!$response->successful()) {
                    Log::warning('Fireblocks JWKS fetch failed', ['endpoint' => $endpoint, 'status' => $response->status()]);
                    return null;
                }
                return $response->json();
            } catch (\Throwable $e) {
                Log::warning('Fireblocks JWKS fetch error: ' . $e->getMessage());
                return null;
            }
        });

        if (!is_array($jwks) || empty($jwks['keys'])) {
            Cache::forget($cacheKey);
            return null;
        }

        $jwk = collect($jwks['keys'])->firstWhere('kid', $kid);

        // Key rotated since last cache — force a refresh and retry once.
        if (!$jwk) {
            Cache::forget($cacheKey);
            $jwks = Cache::remember($cacheKey, 3600, function () use ($endpoint) {
                try {
                    $response = Http::timeout(10)->get($endpoint);
                    return $response->successful() ? $response->json() : null;
                } catch (\Throwable $e) {
                    return null;
                }
            });
            $jwk = is_array($jwks) ? collect($jwks['keys'] ?? [])->firstWhere('kid', $kid) : null;
        }

        if (!$jwk) {
            return null;
        }

        return $this->jwkToPem($jwk);
    }

    /**
     * Resolve the Fireblocks workspace environment from Laravel configuration.
     * This is backed by FIREBLOCKS_WEBHOOK_PUBLIC_KEY_TYPE in the deployed .env.
     */
    protected function resolveJwksEnvironment(): ?string
    {   
        $rawType = FireblocksConfigService::get()['webhook_public_key_type'] ?? config('fireblocks.webhook_public_key_type', '');
        $envType = strtolower(trim((string) $rawType));

        if (!array_key_exists($envType, self::JWKS_ENDPOINTS)) {
            Log::error('Fireblocks webhook JWKS environment is invalid', [
                'configured_environment' => $envType,
                'allowed_environments' => array_keys(self::JWKS_ENDPOINTS),
            ]);

            return null;
        }

        return $envType;
    }

    /**
     * Convert an RSA JWK (kty/n/e) to a PEM public key using phpseclib3.
     */
    protected function jwkToPem(array $jwk): ?string
    {
        if (!class_exists(\phpseclib3\Crypt\PublicKeyLoader::class)) {
            Log::error('Fireblocks webhook: phpseclib3 not available to parse JWKS key');
            return null;
        }

        try {
            $key = \phpseclib3\Crypt\PublicKeyLoader::load(json_encode($jwk));
            return $key->toString('PKCS8');
        } catch (\Throwable $e) {
            Log::error('Fireblocks webhook: failed to convert JWK to PEM — ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Base64URL encode (RFC 4648).
     *
     * @param string $data
     * @return string
     */
    protected function base64urlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Base64URL decode (RFC 4648).
     *
     * @param string $data
     * @return string|false
     */
    protected function base64urlDecode(string $data)
    {
        $pad = 4 - (strlen($data) % 4);
        if ($pad !== 4) {
            $data .= str_repeat('=', $pad);
        }
        return base64_decode(strtr($data, '-_', '+/'), true);
    }

    /**
     * Process a Fireblocks webhook event.
     *
     * @param array $payload Decoded JSON payload
     * @return array
     */
    public function handleEvent(array $payload): array
    {
        $eventType = $payload['event_type'] ?? $payload['eventType'] ?? $payload['type'] ?? 'UNKNOWN';
        $tenantId  = $payload['tenant_id'] ?? null;
        $data      = $payload['data'] ?? [];
        //dd($data);
        Log::info('Fireblocks webhook received', [
            'event_type' => $eventType,
            'tenant_id'  => $tenantId,
            'data'       => $data,
        ]);

        if ($this->isVaultAssetAddedEvent($eventType)) {
            $syncResult = app(VaultDepositAddressSyncService::class)->syncFromVaultAssetAdded($data);

            return [
                'success' => true,
                'message' => $syncResult['message'] ?? 'Vault asset webhook processed',
                'sync' => $syncResult,
            ];
        }

        // Transaction lifecycle events
        if (in_array($eventType, ['transaction.created', 'transaction.status.updated'], true)) {
            return $this->handleTransactionEvent($eventType, $data, $payload);
        }

        // Account Traffic Control — stuck transaction alert
        if ($eventType === 'transaction.alert.stuck') {
            if (\App\Helpers\Common::isModuleEnabled('FireblocksTransactionsMonitoring')) {
                return app(\App\Modules\FireblocksTransactionsMonitoring\Services\StuckAlertHandler::class)
                    ->handle($data, $payload);
            }

            return [
                'success' => true,
                'message' => 'Stuck alert received — monitoring module disabled',
            ];
        }

        // External wallet / whitelisted address (beneficiary) approval events
        if ($this->isExternalWalletEvent($eventType)) {
            return $this->handleBeneficiaryEvent($eventType, $data, $payload);
        }

        return [
            'success' => true,
            'message' => 'Event type ignored: ' . $eventType,
        ];
    }

    /**
     * Determine whether the event relates to an external wallet whitelisted address.
     * Matches both V2 dotted names (e.g. "external_wallet.asset.status.updated")
     * and legacy V1 uppercase names (e.g. "EXTERNAL_WALLET_ASSET_ADDED").
     */
    protected function isExternalWalletEvent(string $eventType): bool
    {
        $normalized = strtolower($eventType);

        return str_contains($normalized, 'external_wallet')
            || str_contains($normalized, 'external.wallet');
    }

    protected function isVaultAssetAddedEvent(string $eventType): bool
    {
        $normalized = strtolower(str_replace('_', '.', $eventType));

        return in_array($normalized, [
            'vault.account.asset.added',
            'vault_account.asset.added',
        ], true) || strtoupper($eventType) === 'VAULT_ACCOUNT_ASSET_ADDED';
    }

    /**
     * Handle transaction lifecycle webhook events.
     */
    protected function handleTransactionEvent(string $eventType, array $data, array $payload): array
    {
        $fireblocksTxId = $data['id'] ?? null;
        // Fireblocks lifecycle status only — never treat AML/compliance "Completed" as on-chain COMPLETED.
        $lifecycleStatus = strtoupper(trim((string) ($data['status'] ?? '')));
        $status = $lifecycleStatus !== '' ? $lifecycleStatus : null;
        $complianceStatus = trim((string) ($data['complianceResults']['status'] ?? ''));

        $subStatus      = $data['subStatus'] ?? null;
        $txHash         = $data['txHash'] ?? null;
        $fee            = $data['fee'] ?? null;
        $feeCurrency    = $data['feeCurrency'] ?? null;
        $amount         = $data['amount'] ?? null;
        $resolvedAmount = \App\Modules\Fireblocks\Support\FireblocksAmount::fromPayload($data);
        $numOfConfirmations = $data['numOfConfirmations'] ?? null;

        if (empty($fireblocksTxId) || empty($status)) {
            Log::warning('Fireblocks webhook missing required fields', [
                'event_type' => $eventType,
                'data'       => $data,
            ]);
            return [
                'success' => false,
                'message' => 'Missing transaction ID or status in webhook payload',
            ];
        }

        if ($this->isIncomingDeposit($data)) {
            $syncResult = app(VaultDepositAddressSyncService::class)->syncFromTransaction($data);

            if (!$syncResult['success']) {
                return [
                    'success' => false,
                    'message' => $syncResult['message'] ?? 'Failed to sync vault deposit addresses before processing transaction',
                    'sync' => $syncResult,
                ];
            }
        }

        // Resolve internal status using the helper
        $resolution = FireblocksStatusHelper::resolve($status, $subStatus);
        $internalStatus = $resolution['internal_status'];

        Log::info('Fireblocks webhook status resolved', [
            'fireblocks_tx_id' => $fireblocksTxId,
            'event_type'       => $eventType,
            'lifecycle_status' => $lifecycleStatus,
            'compliance_status'=> $complianceStatus !== '' ? $complianceStatus : null,
            'sub_status'       => $subStatus,
            'internal_status'  => $internalStatus,
            'message'          => $resolution['message'],
        ]);

        // Update an existing custody record. Inbound records are created atomically
        // with their ledger credit below so this update cannot claim idempotency early.
        $fbTransaction = FireblocksTransaction::where('fireblocks_tx_id', $fireblocksTxId)->first();
        if ($fbTransaction) {
            $fbTransaction->status = $status;
            $fbTransaction->sub_status = $subStatus;
            $fbTransaction->internal_status = $internalStatus;
            $fbTransaction->last_webhook_at = now();
            $fbTransaction->destination_tag = $data['destinationTag'] ?? $fbTransaction->destination_tag;

            if ($txHash) {
                $fbTransaction->tx_hash = $txHash;
            }
            if ($fee !== null) {
                $fbTransaction->fee = $fee;
            }
            if ($resolvedAmount !== null) {
                $fbTransaction->amount = $resolvedAmount;
            } elseif ($amount !== null && \App\Modules\Fireblocks\Support\FireblocksAmount::fromRecord($fbTransaction) === null) {
                $fbTransaction->amount = $amount;
            }
            if (!empty($data['assetId']) && (empty($fbTransaction->asset_id) || strtoupper((string) $fbTransaction->asset_id) === 'UNKNOWN')) {
                $fbTransaction->asset_id = (string) $data['assetId'];
            }
            \App\Modules\Fireblocks\Support\FireblocksAmount::applyToRecord($fbTransaction, $data);

            if (!empty($data['externalTxId']) && empty($fbTransaction->external_tx_id)) {
                $fbTransaction->external_tx_id = (string) $data['externalTxId'];
            }
            if (!empty($data['customerRefId']) && empty($fbTransaction->customer_ref_id)) {
                $fbTransaction->customer_ref_id = (string) $data['customerRefId'];
            }

            if (!empty($data['operation'])) {
                $fbTransaction->operation = strtoupper((string) $data['operation']);
            }

            if (
                empty($fbTransaction->direction)
                && \App\Helpers\Common::isModuleEnabled('FireblocksTransactionsMonitoring')
            ) {
                $fbTransaction->direction = app(\App\Modules\FireblocksTransactionsMonitoring\Services\TransactionMonitoringSync::class)
                    ->inferDirectionFromPayload($data);
            }

            if (is_array($data['complianceResults'] ?? null) && ($data['complianceResults'] ?? []) !== []) {
                $existingForCompliance = is_array($fbTransaction->response_data) ? $fbTransaction->response_data : [];
                $existingForCompliance['complianceResults'] = $data['complianceResults'];
                $fbTransaction->response_data = $existingForCompliance;
            }

            $existingResponseData = is_array($fbTransaction->response_data) ? $fbTransaction->response_data : [];
            $fbTransaction->response_data = array_merge(
                $existingResponseData,
                [
                    'webhook_event_type' => $eventType,
                    'webhook_status'     => $status,
                    'webhook_subStatus'  => $subStatus,
                    'webhook_txHash'     => $txHash,
                    'webhook_fee'        => $fee,
                    'webhook_feeCurrency'=> $feeCurrency,
                    'webhook_numOfConfirmations' => $numOfConfirmations,
                    'webhook_timestamp'  => now()->toIso8601String(),
                ]
            );
            foreach (['signedBy', 'createdBy', 'authorizationInfo', 'amountInfo', 'requestedAmount', 'netAmount'] as $actorKey) {
                if (array_key_exists($actorKey, $data)) {
                    $merged = is_array($fbTransaction->response_data) ? $fbTransaction->response_data : [];
                    $merged[$actorKey] = $data[$actorKey];
                    $fbTransaction->response_data = $merged;
                }
            }
            app(\App\Modules\Fireblocks\Services\FireblocksTransactionActorService::class)
                ->apply($fbTransaction, $data);
            $actorService = app(\App\Modules\Fireblocks\Services\FireblocksTransactionActorService::class);
            $extracted = $actorService->extract($actorService->flattenPayload($fbTransaction, $data));
            if ($extracted['signers'] === [] && $extracted['approvers'] === []) {
                $actorService->enrichFromApi($fbTransaction);
            }

            if (
                \App\Modules\FireblocksVaultSweeping\Services\TronGasFundingService::isGasFeedTransaction($data)
                && !FireblocksTransaction::isCustodyOnlyWithoutLedger($fbTransaction)
            ) {
                $fbTransaction->note = FireblocksTransaction::CUSTODY_ONLY_SWEEP_GAS_FEED_NOTE;
            }

            $fbTransaction->save();

            if ($fbTransaction->exists && !FireblocksTransaction::isCustodyOnlyWithoutLedger($fbTransaction)) {
                try {
                    app(\App\Modules\LiveTransactions\Services\LiveTransactionFeedService::class)->syncFromFireblocks($fbTransaction, 'Fireblocks Webhook');
                } catch (\Throwable $e) {
                    Log::warning('Live transaction feed webhook sync failed', [
                        'fireblocks_tx_id' => $fireblocksTxId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        } elseif (!$this->isIncomingDeposit($data)) {
            Log::warning('Fireblocks webhook: local transaction not found', [
                'fireblocks_tx_id' => $fireblocksTxId,
            ]);
        }

        $this->syncTransactionRequestCryptoReference(
            $fireblocksTxId,
            $data,
            FireblocksTransaction::where('fireblocks_tx_id', $fireblocksTxId)->first()
        );

        $fireblocksRef = $this->normalizeCryptoTransactionReference($fireblocksTxId);

        // Update related AccountTransaction (crypto_transaction_id matches Fireblocks ref string)
        $accountTransaction = $fireblocksRef !== ''
            ? AccountTransaction::where('crypto_transaction_id', $fireblocksRef)
                ->orderByDesc('id')
                ->first()
            : null;

        if (!$accountTransaction) {
            $externalTxId = $data['externalTxId']
                ?? FireblocksTransaction::where('fireblocks_tx_id', $fireblocksTxId)->value('external_tx_id');
            if ($this->isNumericTransactionRequestId($externalTxId)) {
                $accountTransaction = AccountTransaction::where('tr_request_id', (int) $externalTxId)
                    ->orderByDesc('id')
                    ->first();
            }
        }

        $ledgerCreditApplied = false;
        $orphanDeposit = false;

        if ($accountTransaction && $fireblocksRef !== '' && $accountTransaction->crypto_transaction_id !== $fireblocksRef) {
            $accountTransaction->crypto_transaction_id = $fireblocksRef;
        }

        if ($accountTransaction) {
            $wasApproved = $accountTransaction->status === 'Approved';

            if (
                !$wasApproved
                && $internalStatus === 'Approved'
                && $accountTransaction->transaction_type === 'Debit'
            ) {
                $ledger = Common::getApprovedLedgerBalanceByCurrencyId(
                    $accountTransaction->account_id,
                    $accountTransaction->currency_id
                );

                if ((float) $ledger < (float) $accountTransaction->amount) {
                    Log::warning('Fireblocks webhook: insufficient ledger to approve debit — marking Rejected', [
                        'account_transaction_id' => $accountTransaction->id,
                        'tr_request_id' => $accountTransaction->tr_request_id,
                        'account_id' => $accountTransaction->account_id,
                        'ledger_balance' => $ledger,
                        'debit_amount' => $accountTransaction->amount,
                        'fireblocks_tx_id' => $fireblocksTxId,
                    ]);
                    $internalStatus = 'Rejected';
                }
            }

            $accountTransaction->status = $internalStatus;
            $accountTransaction->approved_by = $internalStatus === 'Approved' ? 0 : $accountTransaction->approved_by;
            $accountTransaction->approved_at = $internalStatus === 'Approved' ? now() : $accountTransaction->approved_at;
            $accountTransaction->save();

            if ($accountTransaction->tr_request_id) {
                $trRequest = TRRequest::find($accountTransaction->tr_request_id);
                if ($trRequest) {
                    $trRequest->status = $internalStatus;
                    if ($fireblocksRef !== '') {
                        $trRequest->crypto_transaction_id = $fireblocksRef;
                    }
                    $trRequest->approved_by = $internalStatus === 'Approved' ? 0 : $trRequest->approved_by;
                    $trRequest->approved_at = $internalStatus === 'Approved' ? now() : $trRequest->approved_at;
                    $trRequest->save();
                }
            }

            if (
                !$wasApproved
                && $internalStatus === 'Approved'
                && $this->isIncomingDeposit($data)
            ) {
                $ledgerCreditApplied = true;
            }
        } elseif (
            $internalStatus === 'Approved'
            && \App\Modules\FireblocksVaultSweeping\Services\TronGasFundingService::isGasFeedTransaction($data)
        ) {
            $this->recordCustodyOnlyDeposit($data, $payload, FireblocksTransaction::CUSTODY_ONLY_SWEEP_GAS_FEED_NOTE);
            Log::info('Fireblocks TRON Station gas feed — custody recorded, no client ledger credit', [
                'fireblocks_tx_id' => $fireblocksTxId,
                'destination_vault_id' => $data['destination']['id'] ?? null,
                'asset_id' => $data['assetId'] ?? null,
                'external_tx_id' => $data['externalTxId'] ?? null,
            ]);
        } elseif ($internalStatus === 'Approved' && $this->isIncomingDeposit($data)) {
            if ($this->isUnresolvedDepositAddress($data)) {
                // Address not linked to any client account — custody + sweep only; never credit ledger.
                $orphanDeposit = true;
                $this->recordCustodyOnlyDeposit($data, $payload);
                Log::info('Fireblocks deposit address not in ledger — custody recorded, no ledger entry; sweep may proceed if rule allows', [
                    'fireblocks_tx_id' => $fireblocksTxId,
                    'vault_id' => $data['destination']['id'] ?? null,
                    'asset_id' => $data['assetId'] ?? null,
                ]);
            } else {
                $depositResult = $this->creditIncomingDeposit($data, $payload);
                if (!$depositResult['success']) {
                    return $depositResult;
                }

                $ledgerCreditApplied = !empty($depositResult['ledger_credited']);
            }
        }

        if (!$orphanDeposit) {
            CryptoLogHelper::logCryptoTransaction(
                $fireblocksTxId,
                $details = $payload,
                $cryptoSystem = 'Fireblocks',
                $source = 'webhook'
            );
        }

        if (\App\Helpers\Common::isModuleEnabled('FireblocksTransactionsMonitoring')) {
            try {
                app(\App\Modules\FireblocksTransactionsMonitoring\Services\TransactionMonitoringSync::class)
                    ->syncFromWebhook($fireblocksTxId, $status, $subStatus, $data);
            } catch (\Throwable $e) {
                Log::warning('Fireblocks transaction monitoring sync failed', [
                    'fireblocks_tx_id' => $fireblocksTxId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if (
            \App\Helpers\Common::isModuleEnabled('FireblocksVaultSweeping')
        ) {
            try {
                app(\App\Modules\FireblocksVaultSweeping\Services\TronGasFundingService::class)
                    ->handleFeedWebhook((string) $fireblocksTxId, (string) $status, $data);
            } catch (\Throwable $e) {
                Log::warning('Fireblocks sweep gas feed webhook handling failed', [
                    'fireblocks_tx_id' => $fireblocksTxId,
                    'error' => $e->getMessage(),
                ]);
            }

            try {
                app(\App\Modules\FireblocksVaultSweeping\Services\SweepTransactionWebhookService::class)
                    ->handleTransactionWebhook((string) $fireblocksTxId, (string) $status, $subStatus, $data);
            } catch (\Throwable $e) {
                Log::warning('Fireblocks sweep transaction webhook handling failed', [
                    'fireblocks_tx_id' => $fireblocksTxId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($this->shouldDispatchDepositSweepEvaluation(
            $fireblocksTxId,
            $internalStatus,
            $data,
            $ledgerCreditApplied,
            $orphanDeposit,
            $accountTransaction
        )) {
            try {
                $ledgerAlreadyApproved = $accountTransaction !== null && $accountTransaction->status === 'Approved';

                $sweepContext = array_merge($data, [
                    '_sweep_context' => [
                        'ledger_credited' => $ledgerCreditApplied,
                        'ledger_already_approved' => $ledgerAlreadyApproved,
                        'orphan_deposit' => $orphanDeposit,
                        'internal_status' => $internalStatus,
                    ],
                ]);

                Log::info('[Sweep deposit webhook] running deposit sweep evaluation (sync)', [
                    'fireblocks_tx_id' => $fireblocksTxId,
                    'trigger' => 'deposit',
                    'vault_id' => $data['destination']['id'] ?? null,
                    'asset_id' => $data['assetId'] ?? null,
                    // True only when deposit address is not linked to a client ledger (custody-only).
                    // Rule "sweep orphan deposits" is separate; normal ledger deposits show false here.
                    'deposit_is_orphan' => $orphanDeposit,
                    'orphan_deposit' => $orphanDeposit,
                    'ledger_credited' => $ledgerCreditApplied,
                    'ledger_already_approved' => $ledgerAlreadyApproved,
                    'webhook_tag_ids' => collect($data['destination']['tags'] ?? [])
                        ->map(fn ($tag) => is_array($tag) ? ($tag['id'] ?? null) : null)
                        ->filter()
                        ->values()
                        ->all(),
                ]);

                // Run inline so gas check + TRX feed + sweep submit are not blocked on queue workers.
                \App\Modules\FireblocksVaultSweeping\Jobs\EvaluateDepositSweepJob::dispatchSync($fireblocksTxId, $sweepContext);
            } catch (\Throwable $e) {
                Log::warning('[Sweep deposit webhook] evaluation dispatch failed', [
                    'fireblocks_tx_id' => $fireblocksTxId,
                    'error' => $e->getMessage(),
                ]);
            }
        } elseif ($internalStatus === 'Approved' && $this->isIncomingDeposit($data)) {
            Log::info('[Sweep deposit webhook] sweep not dispatched', [
                'fireblocks_tx_id' => $fireblocksTxId,
                'trigger' => 'deposit',
                'vault_id' => $data['destination']['id'] ?? null,
                'asset_id' => $data['assetId'] ?? null,
                'ledger_credited' => $ledgerCreditApplied,
                'orphan_deposit' => $orphanDeposit,
                'module_enabled' => \App\Helpers\Common::isModuleEnabled('FireblocksVaultSweeping'),
                'sweeping_active' => \App\Modules\FireblocksVaultSweeping\Services\SweepConfigService::isActive(),
                'reason' => $this->depositSweepSkipReason(
                    $fireblocksTxId,
                    $internalStatus,
                    $data,
                    $ledgerCreditApplied,
                    $orphanDeposit,
                    $accountTransaction
                ),
            ]);
        }

        return [
            'success' => true,
            'message' => 'Webhook processed successfully',
            'fireblocks_tx_id' => $fireblocksTxId,
            'internal_status'  => $internalStatus,
        ];
    }

    protected function shouldDispatchDepositSweepEvaluation(
        string $fireblocksTxId,
        string $internalStatus,
        array $data,
        bool $ledgerCreditApplied,
        bool $orphanDeposit,
        ?AccountTransaction $accountTransaction
    ): bool {
        if ($internalStatus !== 'Approved' || !$this->isIncomingDeposit($data)) {
            return false;
        }

        if (!\App\Helpers\Common::isModuleEnabled('FireblocksVaultSweeping')
            || !\App\Modules\FireblocksVaultSweeping\Services\SweepConfigService::isActive()) {
            return false;
        }

        $ledgerReady = $ledgerCreditApplied
            || $orphanDeposit
            || ($accountTransaction !== null && $accountTransaction->status === 'Approved');

        if (!$ledgerReady) {
            return false;
        }

        return !\App\Modules\FireblocksVaultSweeping\Models\FireblocksSweepJob::query()
            ->where('deposit_fireblocks_tx_id', $fireblocksTxId)
            ->where('trigger_source', 'deposit')
            ->exists();
    }

    protected function depositSweepSkipReason(
        string $fireblocksTxId,
        string $internalStatus,
        array $data,
        bool $ledgerCreditApplied,
        bool $orphanDeposit,
        ?AccountTransaction $accountTransaction
    ): string {
        if ($internalStatus !== 'Approved' || !$this->isIncomingDeposit($data)) {
            return 'not_approved_incoming_deposit';
        }

        if (!\App\Helpers\Common::isModuleEnabled('FireblocksVaultSweeping')) {
            return 'module_disabled';
        }

        if (!\App\Modules\FireblocksVaultSweeping\Services\SweepConfigService::isActive()) {
            return 'sweeping_inactive';
        }

        $ledgerReady = $ledgerCreditApplied
            || $orphanDeposit
            || ($accountTransaction !== null && $accountTransaction->status === 'Approved');

        if (!$ledgerReady) {
            return 'no_ledger_credit_and_not_orphan';
        }

        if (\App\Modules\FireblocksVaultSweeping\Models\FireblocksSweepJob::query()
            ->where('deposit_fireblocks_tx_id', $fireblocksTxId)
            ->where('trigger_source', 'deposit')
            ->exists()) {
            return 'deposit_sweep_already_queued';
        }

        return 'unknown';
    }

    /**
     * An incoming deposit is an external-to-vault transfer. Vault-to-vault
     * transactions are internal custody movements and must not credit a client.
     */
    protected function isIncomingDeposit(array $data): bool
    {
        if (\App\Modules\FireblocksVaultSweeping\Services\TronGasFundingService::isGasFeedTransaction($data)) {
            return false;
        }

        $sourceType = strtoupper((string) ($data['source']['type'] ?? ''));
        $destinationType = strtoupper((string) ($data['destination']['type'] ?? ''));

        return $destinationType === 'VAULT_ACCOUNT'
            && $sourceType !== ''
            && $sourceType !== 'VAULT_ACCOUNT';
    }

    /**
     * Resolve a Fireblocks-owned deposit address to exactly one local account.
     */
    protected function resolveDepositAddress(array $data): ?CryptoAddress
    {
        $address = trim((string) ($data['destinationAddress'] ?? ''));
        $assetId = trim((string) ($data['assetId'] ?? ''));
        $tag = trim((string) ($data['destinationTag'] ?? ''));

        if ($address === '' || $assetId === '') {
            return null;
        }

        $query = CryptoAddress::query()
            ->where('provider', 'fireblocks')
            ->where('asset_id', $assetId)
            ->where(function ($addressQuery) use ($address) {
                $addressQuery->where('crypto_address', $address)
                    ->orWhere('legacy_address', $address);
            });

        if ($tag !== '') {
            $query->where('destination_tag', $tag);
        } else {
            $query->where(function ($tagQuery) {
                $tagQuery->whereNull('destination_tag')
                    ->orWhere('destination_tag', '');
            });
        }

        $matches = $query->limit(2)->get();

        if ($matches->count() !== 1) {
            Log::warning('Fireblocks deposit address resolution failed', [
                'asset_id' => $assetId,
                'address_hash' => hash('sha256', $address),
                'tag_present' => $tag !== '',
                'matches' => $matches->count(),
            ]);
            return null;
        }

        $depositAddress = $matches->first();
        $destinationVaultId = (string) ($data['destination']['id'] ?? '');

        if (
            $destinationVaultId !== ''
            && !empty($depositAddress->vault_account_id)
            && (string) $depositAddress->vault_account_id !== $destinationVaultId
        ) {
            Log::warning('Fireblocks deposit vault does not match registered address', [
                'crypto_address_id' => $depositAddress->id,
                'destination_vault_id' => $destinationVaultId,
            ]);
            return null;
        }

        return $depositAddress;
    }

    /**
     * True when the inbound deposit address cannot be mapped to exactly one client ledger address.
     */
    protected function isUnresolvedDepositAddress(array $data): bool
    {
        return $this->resolveDepositAddress($data) === null;
    }

    /**
     * Persist Fireblocks custody metadata only — never creates AccountTransaction, TRRequest, or balance credit.
     */
    protected function recordCustodyOnlyDeposit(array $data, array $payload, ?string $note = null): void
    {
        $fireblocksTxId = (string) ($data['id'] ?? '');
        $grossAmount = $data['amountInfo']['amount'] ?? $data['netAmount'] ?? $data['amount'] ?? null;

        if ($fireblocksTxId === '' || !is_numeric($grossAmount) || (float) $grossAmount <= 0) {
            return;
        }

        $custodyNote = $note ?? FireblocksTransaction::CUSTODY_ONLY_NO_LEDGER_NOTE;

        FireblocksTransaction::withoutEvents(function () use ($fireblocksTxId, $data, $payload, $grossAmount, $custodyNote) {
            $attributes = [
                'asset_id' => (string) ($data['assetId'] ?? ''),
                'source_vault_account_id' => (string) ($data['source']['id'] ?? 'external'),
                'destination_address' => (string) ($data['destinationAddress'] ?? $data['destination']['id'] ?? ''),
                'destination_tag' => $data['destinationTag'] ?? null,
                'amount' => $grossAmount,
                'status' => (string) ($data['status'] ?? 'COMPLETED'),
                'tx_hash' => $data['txHash'] ?? null,
                'fee' => $this->normalizeWebhookScalar($data['fee'] ?? null),
                'note' => $custodyNote,
                'response_data' => $payload,
            ];
            $extracted = app(\App\Modules\Fireblocks\Services\FireblocksTransactionActorService::class)->extract($data);
            if ($extracted['initiator']) {
                $attributes['initiator_user_id'] = $extracted['initiator'];
            }
            if ($extracted['signers'] !== []) {
                $attributes['signed_by_user_ids'] = $extracted['signers'];
            }
            if ($extracted['approvers'] !== []) {
                $attributes['tap_approver_user_ids'] = $extracted['approvers'];
            }
            if (!empty($data['externalTxId'])) {
                $attributes['external_tx_id'] = (string) $data['externalTxId'];
            }
            if (!empty($data['customerRefId'])) {
                $attributes['customer_ref_id'] = (string) $data['customerRefId'];
            }

            FireblocksTransaction::updateOrCreate(
                ['fireblocks_tx_id' => $fireblocksTxId],
                $attributes
            );
        });
    }

    /**
     * @deprecated Use recordCustodyOnlyDeposit()
     */
    protected function recordOrphanDepositTransaction(array $data, array $payload): void
    {
        $this->recordCustodyOnlyDeposit($data, $payload);
    }

    /**
     * Create the complete, fee-adjusted incoming deposit ledger workflow.
     */
    protected function creditIncomingDeposit(array $data, array $payload): array
    {
        if (\App\Modules\FireblocksVaultSweeping\Services\TronGasFundingService::isGasFeedTransaction($data)) {
            return [
                'success' => true,
                'ledger_credited' => false,
                'message' => 'TRON Station gas feed — no client ledger credit',
            ];
        }

        $fireblocksTxId = (string) ($data['id'] ?? '');
        $grossAmount = $data['amountInfo']['amount'] ?? $data['netAmount'] ?? $data['amount'] ?? null;
        $depositAddress = $this->resolveDepositAddress($data);

        if (!$depositAddress || !is_numeric($grossAmount) || (float) $grossAmount <= 0) {
            return [
                'success' => false,
                'message' => 'Unable to resolve a valid Fireblocks deposit account or amount',
                'failure_reason' => !$depositAddress ? 'orphan_deposit_address' : 'invalid_deposit_amount',
            ];
        }

        $account = Account::withoutGlobalScopes()->with('currency')->find($depositAddress->account_id);
        if (!$account || !$account->is_active) {
            Log::warning('Fireblocks deposit account is missing or inactive', [
                'account_id' => $depositAddress->account_id,
                'fireblocks_tx_id' => $fireblocksTxId,
            ]);
            return [
                'success' => false,
                'message' => 'Fireblocks deposit account is missing or inactive',
            ];
        }

        $existing = AccountTransaction::where('crypto_transaction_id', $fireblocksTxId)
            ->where('crypto_system', 'fireblocks')
            ->first();
        if ($existing) {
            return [
                'success' => true,
                'message' => 'Fireblocks deposit already credited',
                'ledger_credited' => false,
            ];
        }

        try {
            $created = DB::transaction(function () use (
                $account,
                $data,
                $depositAddress,
                $fireblocksTxId,
                $grossAmount,
                $payload
            ) {
                $existingCredit = AccountTransaction::where('crypto_transaction_id', $fireblocksTxId)
                    ->where('crypto_system', 'fireblocks')
                    ->lockForUpdate()
                    ->first();
                if ($existingCredit) {
                    return ['replay' => true, 'tr_request' => null];
                }

                $custodyAttributes = [
                    'asset_id' => (string) ($data['assetId'] ?? ''),
                    'source_vault_account_id' => (string) ($data['source']['id'] ?? 'external'),
                    'destination_address' => (string) ($data['destinationAddress'] ?? $data['destination']['id'] ?? ''),
                    'destination_tag' => $data['destinationTag'] ?? null,
                    'amount' => $grossAmount,
                    'status' => (string) ($data['status'] ?? 'COMPLETED'),
                    'tx_hash' => $data['txHash'] ?? null,
                    'fee' => $this->normalizeWebhookScalar($data['fee'] ?? null),
                    'note' => 'Incoming client deposit',
                    'response_data' => $payload,
                ];
                $extracted = app(\App\Modules\Fireblocks\Services\FireblocksTransactionActorService::class)->extract($data);
                if ($extracted['initiator']) {
                    $custodyAttributes['initiator_user_id'] = $extracted['initiator'];
                }
                if ($extracted['signers'] !== []) {
                    $custodyAttributes['signed_by_user_ids'] = $extracted['signers'];
                }
                if ($extracted['approvers'] !== []) {
                    $custodyAttributes['tap_approver_user_ids'] = $extracted['approvers'];
                }
                if (!empty($data['externalTxId'])) {
                    $custodyAttributes['external_tx_id'] = (string) $data['externalTxId'];
                }
                if (!empty($data['customerRefId'])) {
                    $custodyAttributes['customer_ref_id'] = (string) $data['customerRefId'];
                }

                $custodyTransaction = FireblocksTransaction::updateOrCreate(
                    ['fireblocks_tx_id' => $fireblocksTxId],
                    $custodyAttributes
                );

                $feeResult = $this->applyDepositFees(
                    $account,
                    (float) $grossAmount,
                    $fireblocksTxId,
                    $depositAddress,
                    $data
                );
                $netAmount = (float) ($feeResult['net_amount'] ?? $grossAmount);
                if ($netAmount <= 0) {
                    throw new \RuntimeException('Fireblocks deposit fee is greater than or equal to the deposit amount.');
                }

                $createdBy = DB::table('client_users')
                    ->where('client_id', $account->client_id)
                    ->value('user_id');
                $transactionDate = now();

                // USDC (Network name) Incoming Deposit
                $remarks = "{$account->currency->short} ({$depositAddress->network}) Incoming Deposit";

                $trRequest = new TRRequest();
                $trRequest->account_id = $account->id;
                $trRequest->currency_id = $account->account_currency;
                $trRequest->transaction_type = 'Credit';
                $trRequest->transaction_nature = 'Deposit';
                $trRequest->transaction_date = $transactionDate;
                $trRequest->amount = $grossAmount;
                $trRequest->crypto_amount = $grossAmount;
                $trRequest->remarks = $remarks;
                $trRequest->status = 'Approved';
                $trRequest->crypto_system = 'fireblocks';
                $trRequest->crypto_transaction_id = $fireblocksTxId;
                $trRequest->to_wallet_address = $depositAddress->crypto_address;
                $trRequest->destination_tag = $depositAddress->destination_tag;
                $trRequest->crypto_network = $depositAddress->network;
                $trRequest->created_by = $createdBy;
                $trRequest->approved_by = 0;
                $trRequest->approved_at = $transactionDate;
                $trRequest->save();

                $credit = new AccountTransaction();
                $credit->account_id = $account->id;
                $credit->tr_request_id = $trRequest->id;
                $credit->currency_id = $account->account_currency;
                $credit->transaction_type = 'Credit';
                $credit->transaction_nature = 'Deposit';
                $credit->transaction_date = $transactionDate;
                $credit->amount = $netAmount;
                $credit->is_crypto = true;
                $credit->remarks = $remarks;
                $credit->status = 'Approved';
                $credit->crypto_system = 'fireblocks';
                $credit->crypto_transaction_id = $fireblocksTxId;
                $credit->crypto_details_id = $custodyTransaction->id;
                $credit->to_wallet_address = $depositAddress->crypto_address;
                $credit->crypto_network = $depositAddress->network;
                $credit->created_by = $createdBy;
                $credit->approved_by = 0;
                $credit->approved_at = $transactionDate;
                $credit->save();

                $trRequest->link_tr_id = $credit->id;
                $trRequest->save();

                $chargeIds = $feeResult['charge_ids'] ?? [];
                if (!empty($chargeIds)) {
                    FeeTransactionCharge::whereIn('id', $chargeIds)
                        ->update(['tr_request_id' => $trRequest->id]);
                }

                EmailNotification::notifyClientForTransactionApproval($trRequest);

                return [
                    'replay' => false,
                    'tr_request' => $trRequest,
                    'credit' => $credit,
                ];
            }, 3);
        } catch (\Illuminate\Database\QueryException $e) {
            if (
                AccountTransaction::where('crypto_transaction_id', $fireblocksTxId)
                    ->where('crypto_system', 'fireblocks')
                    ->exists()
            ) {
                return [
                    'success' => true,
                    'message' => 'Fireblocks deposit already credited',
                    'ledger_credited' => false,
                ];
            }
            throw $e;
        }

        if (!$created['replay']) {
            $this->sendDepositNotifications($created['tr_request'], $created['credit']);
        }

        return [
            'success' => true,
            'message' => $created['replay']
                ? 'Fireblocks deposit already credited'
                : 'Fireblocks deposit credited successfully',
            'ledger_credited' => !$created['replay'],
        ];
    }

    protected function normalizeWebhookScalar(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return null;
    }

    protected function applyDepositFees(
        Account $account,
        float $amount,
        string $fireblocksTxId,
        CryptoAddress $depositAddress,
        array $data
    ): array {
        if (!ConfigService::isEnabled()) {
            return ['net_amount' => $amount, 'charge_ids' => []];
        }
        return app(FeeCalculationService::class)->charge(
            $account,
            $amount,
            'credit',
            $fireblocksTxId,
            'fireblocks_deposit',
            [
                'fireblocks_tx_id' => $fireblocksTxId,
                'asset_id' => $data['assetId'] ?? null,
                'crypto_address_id' => $depositAddress->id,
                'destination_vault_id' => $data['destination']['id'] ?? null,
            ]
        );
    }

    protected function sendDepositNotifications(TRRequest $trRequest, AccountTransaction $credit): void
    {
        try {
            if ($trRequest->created_by) {
                EmailNotification::notifyClientForTransactionApproval($trRequest);
            }
            EmailNotification::notifyFinanceManagersForTRRequest($trRequest, 'Deposit');
        } catch (\Throwable $e) {
            Log::error('Fireblocks deposit notification failed', [
                'tr_request_id' => $trRequest->id,
                'account_transaction_id' => $credit->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle external wallet whitelisted-address events — used to resolve the
     * approval status of a pending Fireblocks beneficiary.
     *
     * Fireblocks whitelisted-address statuses: WAITING_FOR_APPROVAL, APPROVED,
     * REJECTED, CANCELLED, FAILED.
     */
    protected function handleBeneficiaryEvent(string $eventType, array $data, array $payload): array
    {
        // Extract identifiers defensively — key names differ between V1 and V2 payloads.
        $walletId = $data['walletId'] ?? $data['wallet_id'] ?? null;
        $assetId  = $data['assetId'] ?? $data['asset_id'] ?? $data['id'] ?? null;
        $address  = $data['address'] ?? $data['wallet_address'] ?? null;
        $status   = $data['status'] ?? $data['assetStatus'] ?? null;
        $activationTime = $data['activationTime'] ?? null;

        // Some V2 event types (e.g. external_wallet.asset.added/.removed) never carry a
        // `status` field — the event firing itself IS the status signal. Infer it when
        // Fireblocks doesn't send one explicitly.
        if ($status === null) {
            $normalizedEvent = strtolower($eventType);
            if (str_contains($normalizedEvent, 'asset.added') || str_contains($normalizedEvent, 'asset_added')) {
                $status = 'APPROVED';
            } elseif (str_contains($normalizedEvent, 'asset.removed') || str_contains($normalizedEvent, 'asset_removed')) {
                $status = 'REJECTED';
            }
        }

        Log::info('Fireblocks beneficiary webhook received', [
            'event_type' => $eventType,
            'wallet_id'  => $walletId,
            'asset_id'   => $assetId,
            'address'    => $address,
            'status'     => $status,
        ]);

        // Locate the beneficiary: prefer wallet + asset, then address, then wallet alone.
        $beneficiary = null;

        if ($walletId && $assetId) {
            $beneficiary = CryptoBeneficiary::where('fireblocks_wallet_id', $walletId)
                ->where('fireblocks_asset_id', $assetId)
                ->first();
        }

        if (!$beneficiary && $address) {
            $query = CryptoBeneficiary::where('wallet_address', $address);
            if ($walletId) {
                $query->where('fireblocks_wallet_id', $walletId);
            }
            $beneficiary = $query->first();
        }

        if (!$beneficiary && $walletId && $assetId) {
            // Fall back to matching just the asset under the wallet.
            $beneficiary = CryptoBeneficiary::where('fireblocks_wallet_id', $walletId)
                ->where('fireblocks_asset_id', $assetId)
                ->withTrashed()
                ->first();
        }

        if (!$beneficiary) {
            Log::warning('Fireblocks beneficiary webhook: no matching beneficiary found', [
                'wallet_id' => $walletId,
                'asset_id'  => $assetId,
                'address'   => $address,
            ]);

            // Acknowledge so Fireblocks does not retry indefinitely.
            return [
                'success' => true,
                'message' => 'No matching beneficiary for external wallet event',
            ];
        }

        if ($status !== null) {
            $beneficiary->fireblocks_asset_status = $status;
        }

        if ($activationTime) {
            try {
                $beneficiary->fireblocks_activation_time = \Carbon\Carbon::parse($activationTime);
            } catch (\Throwable $e) {
                Log::warning('Fireblocks beneficiary webhook: bad activationTime — ' . $e->getMessage());
            }
        }

        // Reflect the approval decision on the beneficiary's active flag.
        $normalizedStatus = strtoupper((string) $status);
        if (in_array($normalizedStatus, ['REJECTED', 'CANCELLED', 'FAILED'], true)) {
            $beneficiary->is_active = false;
        } elseif ($normalizedStatus === 'APPROVED') {
            $beneficiary->is_active = true;
        }

        $beneficiary->save();

        CryptoLogHelper::logCryptoTransaction(
            $beneficiary->logKey(),
            $details = $payload,
            $cryptoSystem = 'Fireblocks',
            $source = 'webhook'
        );

        Log::info('Fireblocks beneficiary status updated from webhook', [
            'beneficiary_id' => $beneficiary->id,
            'status'         => $status,
            'is_active'      => $beneficiary->is_active,
        ]);

        return [
            'success'        => true,
            'message'        => 'Beneficiary webhook processed successfully',
            'beneficiary_id' => $beneficiary->id,
            'status'         => $status,
        ];
    }

    /**
     * Link Fireblocks custody id to the portal transaction request (TR).
     *
     * Fireblocks externalTxId is the numeric TR primary key (account_transaction_requests.id).
     * crypto_transaction_id on TR / account_transactions stores the provider reference as a
     * string (Fireblocks UUID, liquidity order id, or numeric string — column is string).
     */
    protected function syncTransactionRequestCryptoReference(
        string $fireblocksTxId,
        array $data,
        ?FireblocksTransaction $fbTransaction = null
    ): void {
        $fireblocksRef = $this->normalizeCryptoTransactionReference($fireblocksTxId);
        if ($fireblocksRef === '') {
            return;
        }

        $externalTxId = $data['externalTxId'] ?? $fbTransaction?->external_tx_id ?? null;
        if (!$this->isNumericTransactionRequestId($externalTxId)) {
            return;
        }

        $tr = TRRequest::query()->find((int) $externalTxId);
        if (!$tr || !$tr->account_id) {
            return;
        }

        $existingTrRef = $this->normalizeCryptoTransactionReference($tr->crypto_transaction_id);
        if ($existingTrRef === '') {
            $tr->crypto_transaction_id = $fireblocksRef;
            if (trim((string) ($tr->crypto_system ?? '')) === '') {
                $tr->crypto_system = 'fireblocks';
            }
            $tr->save();
        } elseif ($existingTrRef !== $fireblocksRef) {
            Log::warning('Fireblocks webhook TR crypto_transaction_id already set to a different reference', [
                'tr_request_id' => $tr->id,
                'existing_crypto_transaction_id' => $existingTrRef,
                'fireblocks_tx_id' => $fireblocksRef,
            ]);
        }

        AccountTransaction::query()
            ->where('tr_request_id', $tr->id)
            ->orderByDesc('id')
            ->get()
            ->each(function (AccountTransaction $at) use ($fireblocksRef) {
                $existingAtRef = $this->normalizeCryptoTransactionReference($at->crypto_transaction_id);
                if ($existingAtRef !== '' && $existingAtRef !== $fireblocksRef) {
                    return;
                }
                if ($existingAtRef === $fireblocksRef) {
                    return;
                }

                $at->crypto_transaction_id = $fireblocksRef;
                if (trim((string) ($at->crypto_system ?? '')) === '') {
                    $at->crypto_system = 'fireblocks';
                }
                $at->save();
            });
    }

    /** Portal TR id from Fireblocks externalTxId (digits only). */
    protected function isNumericTransactionRequestId(mixed $value): bool
    {
        $value = trim((string) $value);

        return $value !== '' && ctype_digit($value);
    }

    /** Provider reference stored in crypto_transaction_id (always persisted as string). */
    protected function normalizeCryptoTransactionReference(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        return trim((string) $value);
    }

    protected function isBlankCryptoTransactionReference(mixed $value): bool
    {
        return $this->normalizeCryptoTransactionReference($value) === '';
    }
}
