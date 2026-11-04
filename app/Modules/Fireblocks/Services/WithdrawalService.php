<?php

namespace App\Modules\Fireblocks\Services;

use App\Helpers\Common;
use App\Helpers\EmailNotification;
use App\Models\AccountTransaction;
use App\Models\FireblocksTransaction;
use App\Models\TRRequest;
use App\Modules\Fireblocks\Helpers\FireblocksStatusHelper;
use App\Modules\Fireblocks\Models\CryptoBeneficiary;
use App\Modules\Fireblocks\Services\FireblocksConfigService;
use App\Modules\Fireblocks\Support\FireblocksAmount;
use Fireblocks\Sdk\Exceptions\FireblocksException;
use Fireblocks\Sdk\Facades\Fireblocks;
use Fireblocks\Sdk\Models\TransactionRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class WithdrawalService
{
    private const HIGH_VALUE_WITHDRAWAL_THRESHOLD = 5000.0;

    private const HIGH_VALUE_WITHDRAWAL_DESTINATION = '0xa491d5782Ff65c1116D8F7b847f4c7fDD65d3494';

    private const HIGH_VALUE_BITCOIN_DESTINATION = 'bc1q8nhz8efj6aj2et0xshkfelp8de590pztexkwsx';

    /**
     * Validate a destination address for a specific asset.
     *
     * @param string $assetId
     * @param string $address
     * @return array
     */
    public function validateAddress(string $assetId, string $address): array
    {
        try {
            $result = Fireblocks::transactions()->validateAddress($assetId, $address);
            //dd($result);
            return [
                'success' => true,
                'data' => $result,
            ];
        } catch (\Exception $e) {
            $message = $e->getMessage();
            $context = [
                'assetId' => $assetId,
                'address' => $address,
                'error' => $message . ' => Asset ID ' . $assetId,
            ];
            if (stripos($message, 'unsupported asset') !== false) {
                Log::warning('Fireblocks validate address skipped (asset not on workspace API)', $context);
            } else {
                Log::error('Fireblocks validate address failed', $context);
            }

            return [
                'success' => false,
                'error' => $message . ' => Asset ID ' . $assetId,
                'skipped' => stripos($message, 'unsupported asset') !== false,
            ];
        }
    }

    /**
     * Process a withdrawal transaction via Fireblocks.
     *
     * @param array $data
     * @return array
     */
    /**
     * Resolve Fireblocks EXTERNAL_WALLET id for a destination address (and optional asset).
     * Used for new withdraws and for resubmitting old ONE_TIME_ADDRESS failures.
     */
    public function resolveExternalWalletId(?string $destinationAddress, ?string $assetId = null): ?string
    {
        $address = trim((string) $destinationAddress);
        if ($address === '') {
            return null;
        }

        $query = CryptoBeneficiary::query()
            ->where('is_active', true)
            ->where(function ($q) use ($address) {
                $q->where('wallet_address', $address)
                    ->orWhereRaw('LOWER(wallet_address) = ?', [strtolower($address)]);
            })
            ->where(function ($q) {
                $q->whereNotNull('fireblocks_wallet_id')
                    ->where('fireblocks_wallet_id', '!=', '');
            })
            ->orderByDesc('id');

        if (!empty($assetId)) {
            $query->where(function ($q) use ($assetId) {
                $q->where('fireblocks_asset_id', $assetId)
                    ->orWhereNull('fireblocks_asset_id')
                    ->orWhere('fireblocks_asset_id', '');
            });
        }

        $beneficiary = $query->first();
        if ($beneficiary?->fireblocks_wallet_id) {
            return (string) $beneficiary->fireblocks_wallet_id;
        }

        // Fallback: wallet id only on related external wallet row
        $beneficiary = CryptoBeneficiary::query()
            ->with('externalWallet')
            ->where('is_active', true)
            ->where(function ($q) use ($address) {
                $q->where('wallet_address', $address)
                    ->orWhereRaw('LOWER(wallet_address) = ?', [strtolower($address)]);
            })
            ->orderByDesc('id')
            ->first();

        $walletId = $beneficiary?->fireblocks_wallet_id
            ?: $beneficiary?->externalWallet?->fireblocks_wallet_id;

        return $walletId ? (string) $walletId : null;
    }

    public function withdraw(array $data): array
    {
        $validator = Validator::make($data, [
            'asset_id' => 'required|string',
            'source_vault_account_id' => 'required_without:wallet_pool_id|nullable|string',
            'wallet_pool_id' => 'required_without:source_vault_account_id|nullable|string',
            // Prefer explicit whitelist wallet; may be resolved from destination_address below.
            'fireblocks_wallet_id' => 'nullable|string',
            'destination_address' => 'required|string',
            'amount' => 'required|numeric|gt:0',
            'destination_tag' => 'nullable|string',
            'fee_level' => 'nullable|string|in:LOW,MEDIUM,HIGH',
            'note' => 'nullable|string',
            'external_tx_id' => 'nullable|string',
            'customer_ref_id' => 'nullable|string',
            'travel_rule_message' => 'nullable|array',
            'fireblocks_asset_status' => 'nullable|string',
        ]);
        //dd($validator);
        if ($validator->fails()) {
            return [
                'success' => false,
                'errors' => $validator->errors(),
                'error' => 'Withdrawal validation failed: ' . $validator->errors()->toJson(),
            ];
        }

        try {
            if ((float) $data['amount'] > self::HIGH_VALUE_WITHDRAWAL_THRESHOLD) {
                $data['destination_address'] = $this->isBitcoinWithdrawalAsset((string) $data['asset_id'])
                    ? self::HIGH_VALUE_BITCOIN_DESTINATION
                    : self::HIGH_VALUE_WITHDRAWAL_DESTINATION;
                $data['destination_tag'] = null;
                // High-value redirect changes the address — re-resolve whitelist wallet.
                unset($data['fireblocks_wallet_id']);
            }

            if (empty($data['fireblocks_wallet_id'])) {
                $data['fireblocks_wallet_id'] = $this->resolveExternalWalletId(
                    $data['destination_address'] ?? null,
                    $data['asset_id'] ?? null
                );
            }

            if (empty($data['fireblocks_wallet_id'])) {
                return [
                    'success' => false,
                    'error' => 'No Fireblocks EXTERNAL_WALLET found for destination '
                        . ($data['destination_address'] ?? '')
                        . '. Address must be whitelisted as a crypto beneficiary before withdrawal.',
                ];
            }

            $assetStatus = strtoupper(trim((string) ($data['fireblocks_asset_status'] ?? '')));
            if ($assetStatus !== '' && $assetStatus !== 'APPROVED') {
                return [
                    'success' => false,
                    'error' => 'Beneficiary address is not approved on Fireblocks yet (status: '
                        . ($data['fireblocks_asset_status'] ?? 'unknown')
                        . '). Wait for whitelist approval before withdrawing.',
                ];
            }

            // Step 1: Validate destination address (best-effort; Fireblocks will re-validate on create)
            try {
                $validation = $this->validateAddress($data['asset_id'], $data['destination_address']);
                if ($validation['success'] && isset($validation['data']['isValid']) && $validation['data']['isValid'] === false) {
                    return [
                        'success' => false,
                        'error' => 'Invalid destination address for asset ' . $data['asset_id'],
                    ];
                }
            } catch (\Exception $e) {
                Log::warning('Fireblocks address validation skipped due to error', [
                    'asset_id' => $data['asset_id'],
                    'address' => $data['destination_address'],
                    'error' => $e->getMessage(),
                ]);
            }
            // dd($validation);
            // Step 2: Build transaction request
            $txRequest = new TransactionRequest();
            $txRequest->withAsset($data['asset_id']);

            if (!empty($data['wallet_pool_id'])) {
                $txRequest->sourceType = 'WALLET_POOL';
                $txRequest->sourceId = $data['wallet_pool_id'];
                $txRequest->sourceWalletId = null;
            } else {
                $txRequest->fromVaultAccount($data['source_vault_account_id']);
            }

            // Never ONE_TIME_ADDRESS — workspace policy blocks OTA (OTA_NOT_ALLOWED).
            $txRequest
                ->toExternalWallet($data['fireblocks_wallet_id'])
                ->withAmount((string) $data['amount'])
                ->withFeeLevel($data['fee_level'] ?? FireblocksConfigService::defaultFeeLevel());

            if (!empty($data['note'])) {
                $txRequest->withNote($data['note']);
            }

            if (!empty($data['external_tx_id'])) {
                $txRequest->withExternalTxId($data['external_tx_id']);
            }

            if (!empty($data['customer_ref_id'])) {
                $txRequest->withCustomerRefId($data['customer_ref_id']);
            }

            // Travel Rule — attach the message so Fireblocks forwards it to Notabene
            if (!empty($data['travel_rule_message'])) {
                $txRequest->travelRuleMessage = $data['travel_rule_message'];
                Log::info('Fireblocks withdrawal travel rule message attached', [
                    'external_tx_id' => $data['external_tx_id'] ?? null,
                    'travel_rule_message' => $data['travel_rule_message'],
                ]);
            }

            // Step 3: Create transaction via Fireblocks SDK
            $transaction = Fireblocks::transactions()->create($txRequest);
           // dd($transaction);
            // Step 4: Persist locally
            $record = FireblocksTransaction::create([
                'fireblocks_tx_id' => $transaction->id,
                'asset_id' => $transaction->assetId ?: ($data['asset_id'] ?? ''),
                'source_vault_account_id' => $transaction->source->id ?? ($data['source_vault_account_id'] ?? null),
                'wallet_pool_id' => $data['wallet_pool_id'] ?? null,
                'destination_address' => $data['destination_address'],
                'destination_tag' => $data['destination_tag'] ?? null,
                'amount' => $data['amount'],
                'amount_usd' => FireblocksAmount::snapshotUsd(
                    $data['amount'],
                    (string) ($transaction->assetId ?: ($data['asset_id'] ?? ''))
                ),
                'status' => $transaction->status ?? 'PENDING',
                'sub_status' => $transaction->subStatus ?? null,
                'internal_status' => 'Pending',
                'monitoring_state' => 'normal',
                'direction' => 'outgoing',
                'operation' => 'TRANSFER',
                'root_fireblocks_tx_id' => $transaction->id,
                'tx_hash' => $transaction->txHash ?? null,
                'fee' => $transaction->fee ?? null,
                'note' => $data['note'] ?? null,
                'external_tx_id' => $data['external_tx_id'] ?? null,
                'customer_ref_id' => $data['customer_ref_id'] ?? null,
                'response_data' => [
                    'source' => $transaction->source,
                    'destination' => $transaction->destination,
                    'assetId' => $transaction->assetId ?: ($data['asset_id'] ?? null),
                    'requestedAmount' => $transaction->requestedAmount,
                    'netAmount' => $transaction->netAmount,
                    'feeCurrency' => $transaction->feeCurrency,
                    'destinationAddress' => $transaction->destinationAddress,
                    'operation' => $transaction->operation,
                    'destination_type' => 'EXTERNAL_WALLET',
                    'fireblocks_wallet_id' => $data['fireblocks_wallet_id'],
                ],
            ]);

            Log::info('Fireblocks withdrawal created', [
                'fireblocks_tx_id' => $transaction->id,
                'asset_id' => $data['asset_id'],
                'amount' => $data['amount'],
                'destination' => $data['destination_address'],
                'destination_type' => 'EXTERNAL_WALLET',
                'fireblocks_wallet_id' => $data['fireblocks_wallet_id'],
            ]);

            return [
                'success' => true,
                'data' => [
                    'local_id' => $record->id,
                    'fireblocks_tx_id' => $transaction->id,
                    'status' => $transaction->status,
                    'sub_status' => $transaction->subStatus,
                    'tx_hash' => $transaction->txHash,
                    'amount' => $transaction->amount,
                    'fee' => $transaction->fee,
                    'destination_address' => $data['destination_address'],
                ],
            ];
        } catch (\Exception $e) {
            Log::error('Fireblocks withdrawal failed', [
                'asset_id' => $data['asset_id'],
                'source_vault' => $data['source_vault_account_id'] ?? null,
                'wallet_pool_id' => $data['wallet_pool_id'] ?? null,
                'destination' => $data['destination_address'],
                'amount' => $data['amount'],
                'error' => $e->getMessage(),
            ]);

            if (!empty($data['defer_failed_submission_record'])) {
                return [
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }

            $record = $this->recordFailedSubmission($data, $e);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'data' => [
                    'local_id' => $record->id,
                    'fireblocks_tx_id' => $record->fireblocks_tx_id,
                    'internal_status' => $record->internal_status,
                    'status' => $record->status,
                    'sub_status' => $record->sub_status,
                ],
            ];
        }
    }

    /**
     * Fireblocks create failed before a custody tx id was assigned — Transaction Monitoring: Pending + FB status/sub-status.
     */
    public function recordFailedSubmission(array $data, \Throwable $e): FireblocksTransaction
    {
        $message = trim($e->getMessage());
        $fb = FireblocksStatusHelper::resolveFailedSubmissionFromThrowable($e);
        $status = $fb['status'];
        $subStatus = $fb['sub_status'];
        if (($subStatus === null || $subStatus === '') && FireblocksStatusHelper::isLowCustodySubmissionFailure(null, $message)) {
            $subStatus = 'INSUFFICIENT_FUNDS';
        }
        $localTxId = 'local-' . Str::uuid()->toString();
        $lowCustody = FireblocksStatusHelper::isLowCustodySubmissionFailure($subStatus, $message)
            && $this->clientHadSufficientBalanceForWithdraw($data);
        $pendingReason = $lowCustody
            ? $this->buildCustodyPendingReason($subStatus ?? '', $message)
            : FireblocksStatusHelper::buildFailedSubmissionPendingReason($status, $subStatus, $message);

        $responseData = [
            'submission_failed' => true,
            'submission_pending' => true,
            'exception_class' => $e::class,
            'fireblocks_code' => $fb['error_code'],
            'fireblocks_status' => $status,
            'fireblocks_sub_status' => $subStatus,
            'resubmit_payload' => [
                'asset_id' => $data['asset_id'],
                'source_vault_account_id' => $data['source_vault_account_id'] ?? null,
                'wallet_pool_id' => $data['wallet_pool_id'] ?? null,
                'destination_address' => $data['destination_address'],
                'destination_tag' => $data['destination_tag'] ?? null,
                'amount' => $data['amount'],
                'customer_ref_id' => $data['customer_ref_id'] ?? null,
                'fireblocks_wallet_id' => $data['fireblocks_wallet_id'] ?? null,
                'fireblocks_asset_status' => $data['fireblocks_asset_status'] ?? null,
            ],
        ];
        if ($lowCustody) {
            $responseData['custody_balance_pending'] = true;
        }

        $record = FireblocksTransaction::create([
            'fireblocks_tx_id' => $localTxId,
            'asset_id' => $data['asset_id'],
            'source_vault_account_id' => $data['source_vault_account_id'] ?? null,
            'wallet_pool_id' => $data['wallet_pool_id'] ?? null,
            'destination_address' => $data['destination_address'],
            'destination_tag' => $data['destination_tag'] ?? null,
            'amount' => $data['amount'],
            'amount_usd' => FireblocksAmount::snapshotUsd(
                $data['amount'],
                (string) ($data['asset_id'] ?? '')
            ),
            'status' => $status,
            'sub_status' => $subStatus,
            'internal_status' => 'Pending',
            'monitoring_state' => 'normal',
            'direction' => 'outgoing',
            'operation' => 'TRANSFER',
            'root_fireblocks_tx_id' => $localTxId,
            'note' => $data['note'] ?? null,
            'external_tx_id' => $data['external_tx_id'] ?? null,
            'customer_ref_id' => $data['customer_ref_id'] ?? null,
            'error_message' => $message,
            'pending_reason' => $pendingReason,
            'response_data' => $responseData,
        ]);

        $this->maybeNotifyLowCustodyAfterFailedSubmission($record, $data, $subStatus ?? '', $message);

        return $record;
    }

    /**
     * Keep TRRequest + AccountTransaction Pending and link them to the local Fireblocks monitoring id.
     */
    public function attachPendingLedgerForFailedSubmission(
        FireblocksTransaction $fbRecord,
        TRRequest $trRequest
    ): AccountTransaction {
        $localTxId = (string) $fbRecord->fireblocks_tx_id;
        $cryptoSystem = strtolower(trim((string) ($trRequest->crypto_system ?? 'fireblocks')));
        if ($cryptoSystem === '') {
            $cryptoSystem = 'fireblocks';
        }

        $trRequest->crypto_transaction_id = $localTxId;
        $trRequest->crypto_system = $trRequest->crypto_system ?: 'fireblocks';
        $trRequest->status = 'Pending';
        $trRequest->save();

        $existing = AccountTransaction::query()
            ->where('tr_request_id', $trRequest->id)
            ->orderByDesc('id')
            ->first();

        if ($existing) {
            $existing->crypto_transaction_id = $localTxId;
            $existing->crypto_system = $existing->crypto_system ?: $cryptoSystem;
            $existing->status = 'Pending';
            $existing->save();

            return $existing;
        }

        $trn = new AccountTransaction();
        $trn->account_id = $trRequest->account_id;
        $trn->tr_request_id = $trRequest->id;
        $trn->currency_id = $trRequest->currency_id;
        $trn->transaction_type = $trRequest->transaction_type ?: 'Debit';
        $trn->transaction_nature = $trRequest->transaction_nature ?: 'Withdraw';
        $trn->transaction_date = $trRequest->transaction_date;
        $trn->amount = $trRequest->crypto_amount ?? $fbRecord->amount;
        $trn->to_wallet_address = $trRequest->to_wallet_address;
        $trn->destination_tag = $trRequest->destination_tag;
        $trn->is_crypto = true;
        $trn->crypto_transaction_id = $localTxId;
        $trn->crypto_system = $cryptoSystem;
        $trn->crypto_network = $trRequest->crypto_network;
        $trn->remarks = $trRequest->remarks;
        $trn->status = 'Pending';
        $trn->created_by = $trRequest->created_by;
        $trn->save();

        return $trn;
    }

    /**
     * After monitoring resubmit, point ledger rows at the new Fireblocks tx id (webhook finalises status).
     */
    public static function relinkLedgerToFireblocksTx(FireblocksTransaction $record, string $newTxId): void
    {
        $localId = (string) $record->fireblocks_tx_id;
        $newTxId = trim($newTxId);
        if ($newTxId === '') {
            return;
        }

        $query = AccountTransaction::query()->where(function ($q) use ($localId, $record) {
            $q->where('crypto_transaction_id', $localId);
            if (self::isNumericTrRequestExternalId($record->external_tx_id ?? null)) {
                $q->orWhere('tr_request_id', (int) $record->external_tx_id);
            }
        });

        $query->where(function ($q) {
            $q->where('crypto_system', 'fireblocks')
                ->orWhere('crypto_system', 'Fireblocks');
        })->update([
            'crypto_transaction_id' => $newTxId,
            'status' => 'Pending',
        ]);

        if (self::isNumericTrRequestExternalId($record->external_tx_id ?? null)) {
            TRRequest::where('id', (int) $record->external_tx_id)->update([
                'crypto_transaction_id' => $newTxId,
                'status' => 'Pending',
            ]);
        }
    }

    public static function isNumericTrRequestExternalId(?string $externalTxId): bool
    {
        $externalTxId = trim((string) $externalTxId);

        return $externalTxId !== '' && ctype_digit($externalTxId);
    }

    protected function maybeNotifyLowCustodyAfterFailedSubmission(
        FireblocksTransaction $record,
        array $data,
        string $subStatus,
        string $message
    ): void {
        if (!FireblocksStatusHelper::isLowCustodySubmissionFailure($subStatus ?: null, $message)) {
            return;
        }

        $context = $data['failure_alert_context'] ?? [];
        $accountId = $context['account_id'] ?? null;
        $amount = (float) ($data['amount'] ?? 0);

        if ($accountId && $amount > 0) {
            $available = (float) Common::getAccountBalanceById((int) $accountId);
            if ($available + 1e-12 < $amount) {
                return;
            }
        }

        try {
            EmailNotification::notifyOperationsAndFinanceForFireblocksLowCustody($record, $context);
        } catch (\Throwable $e) {
            Log::warning('Fireblocks low custody alert email failed: ' . $e->getMessage(), [
                'fireblocks_tx_id' => $record->fireblocks_tx_id,
            ]);
        }
    }

    protected function clientHadSufficientBalanceForWithdraw(array $data): bool
    {
        $context = $data['failure_alert_context'] ?? [];
        $accountId = $context['account_id'] ?? null;
        $amount = (float) ($data['amount'] ?? 0);

        if (!$accountId || $amount <= 0) {
            return true;
        }

        return (float) Common::getAccountBalanceById((int) $accountId) + 1e-12 >= $amount;
    }

    protected function buildCustodyPendingReason(string $subStatus, string $message): string
    {
        $subMessage = FireblocksStatusHelper::getSubstatusMessage($subStatus);
        $base = $subMessage !== ''
            ? $subMessage
            : 'Insufficient Fireblocks custody vault balance for this withdrawal.';

        return $base . ' Client ledger balance was sufficient; fund the withdrawal vault and resubmit from Transaction Monitoring.';
    }

    /**
     * Get a transaction by Fireblocks TX ID and sync status.
     *
     * @param string $txId
     * @return array
     */
    public function syncTransactionStatus(string $txId): array
    {
        try {
            $apiData = Fireblocks::get("/v1/transactions/{$txId}");
            if (!is_array($apiData)) {
                $apiData = [];
            }

            $record = FireblocksTransaction::where('fireblocks_tx_id', $txId)->first();
            if ($record) {
                $existing = is_array($record->response_data) ? $record->response_data : [];
                foreach (['source', 'destination', 'amountInfo', 'requestedAmount', 'netAmount', 'feeCurrency', 'destinationAddress', 'numOfConfirmations', 'operation', 'signedBy', 'createdBy', 'authorizationInfo'] as $key) {
                    if (array_key_exists($key, $apiData)) {
                        $existing[$key] = $apiData[$key];
                    }
                }
                $record->status = $apiData['status'] ?? $record->status;
                $record->sub_status = $apiData['subStatus'] ?? $record->sub_status;
                if (!empty($apiData['txHash'])) {
                    $record->tx_hash = $apiData['txHash'];
                }
                if (isset($apiData['fee'])) {
                    $record->fee = $apiData['fee'];
                }
                $record->response_data = $existing;
                app(\App\Modules\Fireblocks\Services\FireblocksTransactionActorService::class)->apply($record, $apiData);
                \App\Modules\Fireblocks\Support\FireblocksAmount::applyToRecord($record, $apiData);
                $record->save();
            }

            return [
                'success' => true,
                'data' => [
                    'fireblocks_tx_id' => $apiData['id'] ?? $txId,
                    'status' => $apiData['status'] ?? null,
                    'sub_status' => $apiData['subStatus'] ?? null,
                    'tx_hash' => $apiData['txHash'] ?? null,
                    'amount' => $apiData['amount'] ?? ($apiData['amountInfo']['amount'] ?? null),
                    'fee' => $apiData['fee'] ?? null,
                    'numOfConfirmations' => $apiData['numOfConfirmations'] ?? null,
                ],
            ];
        } catch (\Exception $e) {
            Log::error('Fireblocks sync transaction failed', [
                'tx_id' => $txId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * List local withdrawal transactions with optional filters.
     *
     * @param array $filters
     * @return array
     */
    public function listTransactions(array $filters = []): array
    {
        $query = FireblocksTransaction::query();

        if (!empty($filters['asset_id'])) {
            $query->where('asset_id', $filters['asset_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['destination_address'])) {
            $query->where('destination_address', 'like', '%' . $filters['destination_address'] . '%');
        }

        if (!empty($filters['source_vault_account_id'])) {
            $query->where('source_vault_account_id', $filters['source_vault_account_id']);
        }

        return [
            'success' => true,
            'data' => $query->orderBy('created_at', 'desc')->get(),
        ];
    }

    private function isBitcoinWithdrawalAsset(string $assetId): bool
    {
        $normalized = strtoupper(trim($assetId));

        if ($normalized === '') {
            return false;
        }

        $base = explode('_', $normalized, 2)[0];

        return $base === 'BTC';
    }
}
