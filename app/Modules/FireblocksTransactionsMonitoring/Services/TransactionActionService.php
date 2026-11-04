<?php

namespace App\Modules\FireblocksTransactionsMonitoring\Services;

use App\Models\FireblocksTransaction;
use App\Modules\FireblocksTransactionsMonitoring\Jobs\ExecuteTransactionActionJob;
use App\Modules\Fireblocks\Services\WithdrawalService;
use Fireblocks\Sdk\Facades\Fireblocks;
use Fireblocks\Sdk\Api\FireblocksClient;
use Fireblocks\Sdk\Models\TransactionRequest;
use Illuminate\Support\Facades\Log;

class TransactionActionService
{
    public function __construct(
        protected TransactionMonitoringSync $sync,
        protected StuckTransactionDetector $detector,
        protected MonitoringNotificationService $notifier,
        protected WithdrawalService $withdrawalService,
        protected TransactionActionEligibilityService $eligibility,
    ) {}

    public function queueOrExecute(string $action, FireblocksTransaction $record, string $triggerSource, ?array $config = null): array
    {
        $config = $config ?: MonitoringConfigService::get();

        if ($triggerSource !== 'manual' && in_array($action, ['boost', 'drop', 'resubmit'], true)) {
            $allowed = match ($action) {
                'boost' => $this->eligibility->canBoost($record, $config),
                'drop' => $this->eligibility->canDrop($record, $config),
                'resubmit' => $this->eligibility->canResubmit($record, $config),
                default => true,
            };

            if (!$allowed) {
                return [
                    'success' => false,
                    'message' => ucfirst($action) . ' skipped — not eligible per module configuration',
                ];
            }
        }

        if (!empty($config['manual_approval_required']) && $triggerSource !== 'manual') {
            $record->monitoring_state = 'action_pending';
            $record->pending_action = $action;
            $record->save();
            $this->sync->log($record->fireblocks_tx_id, $action . '_queued', $triggerSource, $record->status, $record->status, $record->sub_status, $record->sub_status, true, ['action' => $action]);

            return ['success' => true, 'queued' => true, 'message' => ucfirst($action) . ' queued for manual approval'];
        }

        if ($triggerSource !== 'manual' && config('queue.default') !== 'sync') {
            return $this->dispatchQueuedAction($action, $record, $triggerSource);
        }

        return match ($action) {
            'boost' => $this->boost($record, $triggerSource, $config),
            'drop' => $this->drop($record, $triggerSource, $config),
            'resubmit' => $this->resubmit($record, $triggerSource, $config),
            'sync' => $this->syncStatus($record, $triggerSource),
            default => ['success' => false, 'message' => 'Unknown action: ' . $action],
        };
    }

    public function syncStatus(FireblocksTransaction $record, string $triggerSource = 'manual'): array
    {
        if (empty($record->fireblocks_tx_id)) {
            return ['success' => false, 'message' => 'Missing Fireblocks transaction ID'];
        }

        try {
            $result = $this->withdrawalService->syncTransactionStatus($record->fireblocks_tx_id);
            if (!$result['success']) {
                throw new \RuntimeException($result['error'] ?? 'Sync failed');
            }

            $apiData = Fireblocks::get('/v1/transactions/' . $record->fireblocks_tx_id);
            if (!is_array($apiData)) {
                $apiData = [];
            }
            $pollPayload = [
                'status' => $apiData['status'] ?? $record->status,
                'subStatus' => $apiData['subStatus'] ?? $record->sub_status,
                'txHash' => $apiData['txHash'] ?? $record->tx_hash,
                'destinationAddress' => $apiData['destinationAddress'] ?? $record->destination_address,
                'assetId' => $apiData['assetId'] ?? $record->asset_id,
                'operation' => $apiData['operation'] ?? $record->operation,
                'source' => $apiData['source'] ?? null,
                'destination' => $apiData['destination'] ?? null,
                'signedBy' => $apiData['signedBy'] ?? null,
                'createdBy' => $apiData['createdBy'] ?? null,
                'authorizationInfo' => $apiData['authorizationInfo'] ?? null,
            ];
            if (!empty($apiData['complianceResults'])) {
                $pollPayload['complianceResults'] = $apiData['complianceResults'];
            }
            $this->sync->syncFromPoll($record->fresh(), $pollPayload);

            $record = $record->fresh();
            $chainalysisRefreshed = app(ChainalysisComplianceSyncService::class)
                ->refreshChainalysisIfMissing($record, $triggerSource);

            $message = 'Transaction synced';
            if ($chainalysisRefreshed) {
                $message .= ' (Chainalysis screening updated)';
            }

            return ['success' => true, 'message' => $message, 'data' => $result['data'] ?? []];
        } catch (\Throwable $e) {
            $this->sync->log($record->fireblocks_tx_id, 'sync', $triggerSource, $record->status, $record->status, $record->sub_status, $record->sub_status, false, null, null, $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function boost(FireblocksTransaction $record, string $triggerSource = 'manual', ?array $config = null): array
    {
        $config = $config ?: MonitoringConfigService::get();

        if ($triggerSource === 'manual') {
            try {
                $this->eligibility->assertBoostAllowed($record, $config);
            } catch (\RuntimeException $e) {
                return ['success' => false, 'message' => $e->getMessage()];
            }
        }

        if ($record->auto_action_count >= $config['max_auto_boost_attempts'] && $triggerSource !== 'manual') {
            if (!empty($config['auto_drop_enabled'])) {
                return $this->drop($record, $triggerSource, $config);
            }
            return ['success' => false, 'message' => 'Max boost attempts reached'];
        }

        if (!empty($config['dry_run_mode'])) {
            $this->sync->log($record->fireblocks_tx_id, 'boost_dry_run', $triggerSource, $record->status, $record->status, $record->sub_status, $record->sub_status, true, [
                'feeLevel' => $config['boost_fee_level'] ?? 'HIGH',
                'gasPrice' => $config['boost_gas_price'] ?? null,
            ]);
            return ['success' => true, 'dry_run' => true, 'message' => 'Dry run: boost would be executed with fee level ' . ($config['boost_fee_level'] ?? 'HIGH')];
        }

        try {
            $apiTx = Fireblocks::transactions()->get($record->fireblocks_tx_id);
            $txHash = $apiTx->txHash ?? $record->tx_hash;

            if (empty($txHash)) {
                throw new \RuntimeException('Cannot boost: transaction has no blockchain hash yet');
            }

            $attempt = $record->auto_action_count + 1;
            $idempotencyKey = \App\Modules\Fireblocks\Support\FireblocksTransactionReferences::monitorBoostIdempotencyKey(
                $record->fireblocks_tx_id,
                $attempt
            );

            $txRequest = new TransactionRequest();
            $txRequest->assetId = $record->asset_id;
            $txRequest->amount = (string) $record->amount;
            $txRequest->feeLevel = $config['boost_fee_level'];
            $txRequest->replaceTxByHash = $txHash;
            $txRequest->note = 'Operations auto-boost attempt ' . $attempt . ' for ' . $record->fireblocks_tx_id;

            if (!empty($config['boost_gas_price'])) {
                $txRequest->requestedGasPrice = $config['boost_gas_price'];
            }

            if (!empty($record->source_vault_account_id)) {
                $txRequest->fromVaultAccount($record->source_vault_account_id);
            }

            $walletId = data_get($record->response_data, 'fireblocks_wallet_id')
                ?: data_get($record->response_data, 'resubmit_payload.fireblocks_wallet_id')
                ?: $this->withdrawalService->resolveExternalWalletId(
                    $record->destination_address,
                    $record->asset_id
                );

            if (!empty($walletId)) {
                // Never ONE_TIME_ADDRESS — even when boosting old OTA withdrawals.
                $txRequest->toExternalWallet($walletId);
            } elseif (!empty($record->destination_address)) {
                throw new \RuntimeException(
                    'Cannot boost: no Fireblocks EXTERNAL_WALLET found for destination '
                    . $record->destination_address
                    . '. Address must be whitelisted as a crypto beneficiary.'
                );
            } else {
                $txRequest->toVaultAccount('0');
            }

            $replacement = Fireblocks::transactions()->create($txRequest);

            $record->auto_action_count = $attempt;
            $record->monitoring_state = 'boosted';
            $record->replaced_by_fireblocks_tx_id = $replacement->id ?? null;
            $record->pending_action = null;
            $record->last_reattempt_at = now();
            $record->save();

            $this->sync->log($record->fireblocks_tx_id, 'boost', $triggerSource, $record->status, $record->status, $record->sub_status, $record->sub_status, true, ['replaceTxByHash' => $txHash], ['replacement_tx_id' => $replacement->id ?? null], null, $idempotencyKey);

            if (!empty($config['notify_on_auto_action'])) {
                $this->notifier->notifyAction($record, 'boost', $triggerSource);
            }

            return ['success' => true, 'message' => 'Boost (RBF) submitted', 'replacement_tx_id' => $replacement->id ?? null];
        } catch (\Throwable $e) {
            Log::error('Fireblocks boost failed', ['tx_id' => $record->fireblocks_tx_id, 'error' => $e->getMessage()]);
            $this->sync->log($record->fireblocks_tx_id, 'boost', $triggerSource, $record->status, $record->status, $record->sub_status, $record->sub_status, false, null, null, $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function drop(FireblocksTransaction $record, string $triggerSource = 'manual', ?array $config = null): array
    {
        $config = $config ?: MonitoringConfigService::get();

        if ($triggerSource === 'manual') {
            try {
                $this->eligibility->assertDropAllowed($record, $config);
            } catch (\RuntimeException $e) {
                return ['success' => false, 'message' => $e->getMessage()];
            }
        }

        if (!empty($config['dry_run_mode'])) {
            $this->sync->log($record->fireblocks_tx_id, 'drop_dry_run', $triggerSource, $record->status, $record->status, $record->sub_status, $record->sub_status, true);
            return ['success' => true, 'dry_run' => true, 'message' => 'Dry run: drop would be executed'];
        }

        try {
            $gasPrice = !empty($config['boost_gas_price']) ? $config['boost_gas_price'] : null;
            $replacement = Fireblocks::transactions()->dropReplace($record->fireblocks_tx_id, $config['boost_fee_level'], $gasPrice);

            $record->monitoring_state = 'dropped';
            $record->auto_action_count++;
            $record->replaced_by_fireblocks_tx_id = $replacement->id ?? null;
            $record->pending_action = null;
            $record->last_reattempt_at = now();
            $record->save();

            $this->sync->log($record->fireblocks_tx_id, 'drop', $triggerSource, $record->status, $record->status, $record->sub_status, $record->sub_status, true, ['feeLevel' => $config['boost_fee_level']], ['replacement_tx_id' => $replacement->id ?? null]);

            if (!empty($config['notify_on_auto_action'])) {
                $this->notifier->notifyAction($record, 'drop', $triggerSource);
            }

            return ['success' => true, 'message' => 'Drop submitted', 'replacement_tx_id' => $replacement->id ?? null];
        } catch (\Throwable $e) {
            $this->sync->log($record->fireblocks_tx_id, 'drop', $triggerSource, $record->status, $record->status, $record->sub_status, $record->sub_status, false, null, null, $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function resubmit(FireblocksTransaction $record, string $triggerSource = 'manual', ?array $config = null): array
    {
        $config = $config ?: MonitoringConfigService::get();

        if ($triggerSource === 'manual') {
            try {
                $this->eligibility->assertResubmitAllowed($record, $config);
            } catch (\RuntimeException $e) {
                return ['success' => false, 'message' => $e->getMessage()];
            }
        } elseif (!$this->eligibility->canResubmit($record, $config)) {
            return ['success' => false, 'message' => 'Transaction failure is not retriable'];
        }

        if ($record->auto_action_count >= $config['max_resubmit_attempts'] && $triggerSource !== 'manual') {
            return ['success' => false, 'message' => 'Max resubmit attempts reached'];
        }

        if (!empty($config['dry_run_mode'])) {
            $this->sync->log($record->fireblocks_tx_id, 'resubmit_dry_run', $triggerSource, $record->status, $record->status, $record->sub_status, $record->sub_status, true);
            return ['success' => true, 'dry_run' => true, 'message' => 'Dry run: resubmit would be executed'];
        }

        try {
            $attempt = $record->auto_action_count + 1;
            $externalTxId = ($record->external_tx_id ?: $record->fireblocks_tx_id) . '-retry-' . $attempt;

            $record = $this->hydrateRecordForResubmit($record);

            // Old OTA failures resolve EXTERNAL_WALLET from beneficiary whitelist via WithdrawalService.
            $walletId = data_get($record->response_data, 'fireblocks_wallet_id')
                ?: data_get($record->response_data, 'resubmit_payload.fireblocks_wallet_id')
                ?: $this->withdrawalService->resolveExternalWalletId(
                    $record->destination_address,
                    $record->asset_id
                );

            $result = $this->withdrawalService->withdraw([
                'asset_id' => $record->asset_id,
                'source_vault_account_id' => $record->source_vault_account_id,
                'wallet_pool_id' => $record->wallet_pool_id,
                'destination_address' => $record->destination_address,
                'destination_tag' => $record->destination_tag,
                'amount' => $record->amount,
                'external_tx_id' => $externalTxId,
                'customer_ref_id' => $record->customer_ref_id,
                'fireblocks_wallet_id' => $walletId,
                'note' => 'Operations resubmit attempt ' . $attempt . ' for failed tx ' . $record->fireblocks_tx_id,
                'fee_level' => $config['boost_fee_level'],
            ]);

            if (empty($result['success'])) {
                throw new \RuntimeException($this->formatWithdrawFailure($result));
            }

            $newTxId = $result['data']['fireblocks_tx_id'] ?? null;

            $record->auto_action_count = $attempt;
            $record->monitoring_state = 'resubmitted';
            $record->replaced_by_fireblocks_tx_id = $newTxId;
            $record->pending_action = null;
            $record->last_reattempt_at = now();
            $record->save();

            if ($newTxId) {
                $newRecord = FireblocksTransaction::where('fireblocks_tx_id', $newTxId)->first();
                if ($newRecord) {
                    $newRecord->parent_fireblocks_tx_id = $record->fireblocks_tx_id;
                    $newRecord->root_fireblocks_tx_id = $record->root_fireblocks_tx_id ?: $record->fireblocks_tx_id;
                    $newRecord->direction = $record->direction ?: 'outgoing';
                    $newRecord->save();
                }

                WithdrawalService::relinkLedgerToFireblocksTx($record, $newTxId);
            }

            $this->sync->log($record->fireblocks_tx_id, 'resubmit', $triggerSource, $record->status, $record->status, $record->sub_status, $record->sub_status, true, ['external_tx_id' => $externalTxId], $result['data'] ?? []);

            if (!empty($config['notify_on_auto_action'])) {
                $this->notifier->notifyAction($record, 'resubmit', $triggerSource);
            }

            return ['success' => true, 'message' => 'Withdrawal resubmitted', 'new_tx_id' => $newTxId];
        } catch (\Throwable $e) {
            $this->sync->log($record->fireblocks_tx_id, 'resubmit', $triggerSource, $record->status, $record->status, $record->sub_status, $record->sub_status, false, null, null, $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function resendWebhooks(FireblocksTransaction $record): array
    {
        if (empty($record->fireblocks_tx_id)) {
            return ['success' => false, 'message' => 'Missing transaction ID'];
        }

        try {
            /** @var FireblocksClient $client */
            $client = app(FireblocksClient::class);
            $response = $client->post('/v1/webhooks/resend/' . $record->fireblocks_tx_id, [
                'resendCreated' => false,
                'resendStatusUpdated' => true,
            ]);

            $this->sync->log($record->fireblocks_tx_id, 'resend_webhook', 'scheduler', $record->status, $record->status, $record->sub_status, $record->sub_status, true, null, $response);

            return ['success' => true, 'message' => 'Webhook resend requested'];
        } catch (\Throwable $e) {
            $this->sync->log($record->fireblocks_tx_id, 'resend_webhook', 'scheduler', $record->status, $record->status, $record->sub_status, $record->sub_status, false, null, null, $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    protected function dispatchQueuedAction(string $action, FireblocksTransaction $record, string $triggerSource): array
    {
        $record->pending_action = $action;
        $record->save();

        ExecuteTransactionActionJob::dispatch($action, $record->id, $triggerSource);
        $this->sync->log($record->fireblocks_tx_id, $action . '_queued', $triggerSource, $record->status, $record->status, $record->sub_status, $record->sub_status, true, ['action' => $action, 'async' => true]);

        return [
            'success' => true,
            'queued' => true,
            'message' => ucfirst($action) . ' queued for background execution',
        ];
    }

    protected function hydrateRecordForResubmit(FireblocksTransaction $record): FireblocksTransaction
    {
        if ($this->isLocalSubmissionTxId($record->fireblocks_tx_id)) {
            $stored = $record->response_data['resubmit_payload'] ?? null;
            if (is_array($stored)) {
                $record = $this->applyResubmitPayload($record, $stored);
            }
        }

        if (!$this->recordMissingResubmitFields($record)) {
            return $record;
        }

        if ($this->isLocalSubmissionTxId($record->fireblocks_tx_id)) {
            throw new \RuntimeException('Failed submission record is missing withdrawal details required for resubmit.');
        }

        $apiTx = Fireblocks::transactions()->get($record->fireblocks_tx_id);

        if (empty($record->destination_address)) {
            $record->destination_address = $this->resolveApiDestinationAddress($apiTx);
        }
        if (empty($record->source_vault_account_id) && empty($record->wallet_pool_id)) {
            $record->source_vault_account_id = $apiTx->sourceId();
        }
        if (empty($record->asset_id)) {
            $record->asset_id = $apiTx->assetId;
        }
        if (empty($record->amount)) {
            $record->amount = $apiTx->amount ?? $apiTx->requestedAmount ?? $apiTx->netAmount;
        }
        if (empty($record->destination_tag) && !empty($apiTx->destinationTag)) {
            $record->destination_tag = $apiTx->destinationTag;
        }

        $record->save();

        return $record->fresh() ?: $record;
    }

    protected function isLocalSubmissionTxId(?string $txId): bool
    {
        return is_string($txId) && str_starts_with($txId, 'local-');
    }

    protected function recordMissingResubmitFields(FireblocksTransaction $record): bool
    {
        if (empty($record->destination_address) || empty($record->asset_id) || empty($record->amount)) {
            return true;
        }

        return empty($record->source_vault_account_id) && empty($record->wallet_pool_id);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function applyResubmitPayload(FireblocksTransaction $record, array $payload): FireblocksTransaction
    {
        foreach (['asset_id', 'source_vault_account_id', 'wallet_pool_id', 'destination_address', 'destination_tag', 'amount', 'customer_ref_id'] as $field) {
            if (empty($record->{$field}) && !empty($payload[$field])) {
                $record->{$field} = $payload[$field];
            }
        }

        if (!empty($payload['fireblocks_wallet_id'])) {
            $responseData = is_array($record->response_data) ? $record->response_data : [];
            $responseData['fireblocks_wallet_id'] = $payload['fireblocks_wallet_id'];
            $responseData['destination_type'] = 'EXTERNAL_WALLET';
            $record->response_data = $responseData;
        }

        $record->save();

        return $record->fresh() ?: $record;
    }

    protected function resolveApiDestinationAddress(object $apiTx): ?string
    {
        if (!empty($apiTx->destinationAddress)) {
            return $apiTx->destinationAddress;
        }

        $destination = $apiTx->destination ?? null;
        if (!is_array($destination)) {
            return null;
        }

        return $destination['oneTimeAddress']['address']
            ?? $destination['address']
            ?? null;
    }

    protected function formatWithdrawFailure(array $result): string
    {
        if (!empty($result['error'])) {
            return (string) $result['error'];
        }

        if (!empty($result['errors'])) {
            $errors = $result['errors'];
            if (is_object($errors) && method_exists($errors, 'toJson')) {
                return 'Withdrawal validation failed: ' . $errors->toJson();
            }

            return 'Withdrawal validation failed: ' . json_encode($errors);
        }

        return 'Withdrawal resubmit failed';
    }
}
