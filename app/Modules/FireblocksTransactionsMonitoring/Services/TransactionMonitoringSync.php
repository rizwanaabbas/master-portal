<?php

namespace App\Modules\FireblocksTransactionsMonitoring\Services;

use App\Helpers\Common;
use App\Models\FireblocksTransaction;
use App\Modules\Fireblocks\Helpers\FireblocksStatusHelper;
use App\Modules\Fireblocks\Services\FireblocksTransactionActorService;
use App\Modules\FireblocksTransactionsMonitoring\Models\FireblocksTransactionMonitoringLog;
use Illuminate\Support\Facades\Auth;

class TransactionMonitoringSync
{
    public function __construct(
        protected StuckTransactionDetector $detector,
        protected MonitoringNotificationService $notifier,
    ) {
    }

    public function syncFromWebhook(string $fireblocksTxId, string $status, ?string $subStatus, array $webhookData = []): void
    {
        if (!Common::isModuleEnabled('FireblocksTransactionsMonitoring')) {
            return;
        }

        $record = $this->findOrTouchRecord($fireblocksTxId, $webhookData);
        if (!$record) {
            return;
        }

        $previousStatus = $record->status;
        $previousSubStatus = $record->sub_status;
        $resolution = FireblocksStatusHelper::resolve($status, $subStatus);

        $record->status = $status;
        $record->sub_status = $subStatus;
        $record->internal_status = $resolution['internal_status'];
        $record->last_webhook_at = now();

        if (!empty($webhookData['externalTxId']) && empty($record->external_tx_id)) {
            $record->external_tx_id = (string) $webhookData['externalTxId'];
        }
        if (!empty($webhookData['customerRefId']) && empty($record->customer_ref_id)) {
            $record->customer_ref_id = (string) $webhookData['customerRefId'];
        }

        if (!empty($webhookData['operation'])) {
            $record->operation = strtoupper((string) $webhookData['operation']);
        }

        $inferredDirection = $this->inferDirection($webhookData);
        if ($inferredDirection !== '' && (empty($record->direction) || $record->direction !== $inferredDirection)) {
            $record->direction = $inferredDirection;
        }

        if (empty($record->root_fireblocks_tx_id) && !empty($fireblocksTxId)) {
            $record->root_fireblocks_tx_id = $fireblocksTxId;
        }

        if (MonitoringConfigService::isActive()) {
            $this->detector->evaluate($record, 'webhook');
        }

        app(ChainalysisComplianceSyncService::class)->apply($record, $webhookData, 'webhook');

        app(FireblocksTransactionActorService::class)->apply($record, $webhookData);

        $record->save();

        $this->log($record->fireblocks_tx_id, 'webhook_sync', 'webhook', $previousStatus, $status, $previousSubStatus, $subStatus, true);
    }

    public function syncFromPoll(FireblocksTransaction $record, array $apiData): FireblocksTransaction
    {
        $previousStatus = $record->status;
        $previousSubStatus = $record->sub_status;
        $status = strtoupper((string) ($apiData['status'] ?? $record->status));
        $subStatus = $apiData['subStatus'] ?? $record->sub_status;
        $resolution = FireblocksStatusHelper::resolve($status, $subStatus);

        $record->status = $status;
        $record->sub_status = $subStatus;
        $record->internal_status = $resolution['internal_status'];
        $record->last_polled_at = now();

        if (!empty($apiData['txHash'])) {
            $record->tx_hash = $apiData['txHash'];
        }

        $inferredDirection = $this->inferDirection($apiData);
        if ($inferredDirection !== '' && (empty($record->direction) || $record->direction !== $inferredDirection)) {
            $record->direction = $inferredDirection;
        }

        if (MonitoringConfigService::isActive()) {
            $this->detector->evaluate($record, 'scheduler');
        }

        app(ChainalysisComplianceSyncService::class)->apply($record, $apiData, 'scheduler');

        $existing = is_array($record->response_data) ? $record->response_data : [];
        foreach (['signedBy', 'createdBy', 'authorizationInfo', 'amountInfo', 'requestedAmount', 'netAmount'] as $actorKey) {
            if (array_key_exists($actorKey, $apiData)) {
                $existing[$actorKey] = $apiData[$actorKey];
            }
        }
        $record->response_data = $existing;
        app(FireblocksTransactionActorService::class)->apply($record, $apiData);
        \App\Modules\Fireblocks\Support\FireblocksAmount::applyToRecord($record, $apiData);

        $record->save();

        $this->log($record->fireblocks_tx_id, 'poll_sync', 'scheduler', $previousStatus, $status, $previousSubStatus, $subStatus, true);

        return $record;
    }

    protected function findOrTouchRecord(string $fireblocksTxId, array $webhookData): ?FireblocksTransaction
    {
        $record = FireblocksTransaction::where('fireblocks_tx_id', $fireblocksTxId)->first();

        if (!$record) {
            $amount = \App\Modules\Fireblocks\Support\FireblocksAmount::fromPayload($webhookData) ?? 0;
            $record = FireblocksTransaction::create([
                'fireblocks_tx_id' => $fireblocksTxId,
                'asset_id' => $webhookData['assetId'] ?? 'UNKNOWN',
                'source_vault_account_id' => (string) ($webhookData['source']['id'] ?? ''),
                'destination_address' => $webhookData['destinationAddress'] ?? ($webhookData['destination']['id'] ?? ''),
                'destination_tag' => $webhookData['destinationTag'] ?? null,
                'amount' => $amount,
                'status' => strtoupper((string) ($webhookData['status'] ?? 'PENDING')),
                'sub_status' => $webhookData['subStatus'] ?? null,
                'tx_hash' => $webhookData['txHash'] ?? null,
                'external_tx_id' => $webhookData['externalTxId'] ?? null,
                'customer_ref_id' => $webhookData['customerRefId'] ?? null,
                'direction' => $this->inferDirection($webhookData),
                'operation' => strtoupper((string) ($webhookData['operation'] ?? 'TRANSFER')),
                'monitoring_state' => 'normal',
                'root_fireblocks_tx_id' => $fireblocksTxId,
                'response_data' => ['webhook_bootstrap' => $webhookData],
            ]);
            app(FireblocksTransactionActorService::class)->apply($record, $webhookData);
            \App\Modules\Fireblocks\Support\FireblocksAmount::applyToRecord($record, $webhookData);
            $record->save();
        }

        return $record;
    }

    public function inferDirectionFromPayload(array $data): string
    {
        return $this->inferDirection($data);
    }

    protected function inferDirection(array $data): string
    {
        $sourceType = strtoupper((string) ($data['source']['type'] ?? ''));
        $destinationType = strtoupper((string) ($data['destination']['type'] ?? ''));

        $vaultLike = ['VAULT_ACCOUNT', 'VAULT'];
        $externalLike = [
            'EXTERNAL_WALLET',
            'UNMANAGED_WALLET',
            'ONE_TIME_ADDRESS',
            'WHITELISTED',
            'NETWORK_CONNECTION',
            'FIAT_ACCOUNT',
            'EXCHANGE_ACCOUNT',
            'INTERNAL_WALLET',
            'UNKNOWN',
        ];

        $sourceIsVault = in_array($sourceType, $vaultLike, true);
        $destIsVault = in_array($destinationType, $vaultLike, true);
        $sourceIsExternal = in_array($sourceType, $externalLike, true) || ($sourceType !== '' && !$sourceIsVault);
        $destIsExternal = in_array($destinationType, $externalLike, true) || ($destinationType !== '' && !$destIsVault);

        if ($destIsVault && $sourceIsExternal) {
            return 'incoming';
        }

        if ($sourceIsVault && $destIsExternal) {
            return 'outgoing';
        }

        if ($sourceIsVault && $destIsVault) {
            return 'internal';
        }

        if (!empty($data['destinationAddress']) && $sourceIsVault) {
            return 'outgoing';
        }

        if (!empty($data['sourceAddress']) && $destIsVault) {
            return 'incoming';
        }

        return 'outgoing';
    }

    public function log(
        ?string $fireblocksTxId,
        string $action,
        string $triggerSource,
        ?string $previousStatus,
        ?string $newStatus,
        ?string $previousSubStatus,
        ?string $newSubStatus,
        bool $success,
        ?array $requestPayload = null,
        ?array $responsePayload = null,
        ?string $errorMessage = null,
        ?string $idempotencyKey = null,
    ): FireblocksTransactionMonitoringLog {
        return FireblocksTransactionMonitoringLog::create([
            'fireblocks_tx_id' => $fireblocksTxId,
            'action' => $action,
            'trigger_source' => $triggerSource,
            'previous_status' => $previousStatus,
            'new_status' => $newStatus,
            'previous_sub_status' => $previousSubStatus,
            'new_sub_status' => $newSubStatus,
            'request_payload' => $requestPayload,
            'response_payload' => $responsePayload,
            'success' => $success,
            'error_message' => $errorMessage,
            'performed_by' => Auth::id(),
            'idempotency_key' => $idempotencyKey,
        ]);
    }
}
