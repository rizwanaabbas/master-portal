<?php

namespace App\Modules\FireblocksTransactionsMonitoring\Services;

use App\Models\FireblocksTransaction;
use App\Modules\FireblocksTransactionsMonitoring\Models\FireblocksTransactionAlert;
use Illuminate\Support\Facades\Log;

class StuckAlertHandler
{
    public function __construct(
        protected TransactionMonitoringSync $sync,
        protected TransactionActionService $actions,
    ) {}

    public function handle(array $data, array $payload): array
    {
        $config = MonitoringConfigService::tryGet();
        if (!$config || empty($config['webhook_stuck_alert_enabled'])) {
            return ['success' => true, 'message' => 'Stuck alert webhook ignored — monitoring disabled'];
        }

        $stuckTxId = $data['recommendedAction']['stuckTxId']
            ?? $data['stuckTransactionDetails']['txId']
            ?? null;

        if (empty($stuckTxId)) {
            Log::warning('Fireblocks stuck alert missing stuckTxId', ['data' => $data]);
            return ['success' => false, 'message' => 'Missing stuck transaction ID'];
        }

        $status = $data['stuckTransactionDetails']['status'] ?? 'CONFIRMING';
        $subStatus = $data['stuckTransactionDetails']['subStatus'] ?? null;

        FireblocksTransactionAlert::create([
            'fireblocks_tx_id' => $stuckTxId,
            'event_type' => 'transaction.alert.stuck',
            'issue_type' => $data['issueType'] ?? null,
            'severity' => $data['severity'] ?? null,
            'vault_account_id' => $data['vaultAccountId'] ?? null,
            'base_asset' => $data['baseAsset'] ?? null,
            'blocked_transaction_count' => $data['stuckAccountDetails']['blockedTransactionCount'] ?? null,
            'stuck_duration_seconds' => $data['stuckAccountDetails']['stuckDurationSeconds'] ?? null,
            'recommended_action' => $data['recommendedAction'] ?? null,
            'payload' => $payload,
            'status' => 'open',
        ]);

        $this->sync->syncFromWebhook($stuckTxId, $status, $subStatus, array_merge($data, [
            'id' => $stuckTxId,
            'assetId' => $data['baseAsset'] ?? null,
            'source' => ['type' => 'VAULT_ACCOUNT', 'id' => $data['vaultAccountId'] ?? ''],
        ]));

        $record = FireblocksTransaction::where('fireblocks_tx_id', $stuckTxId)->first();
        if ($record) {
            $record->monitoring_state = 'stuck';
            $record->stuck_detected_at = $record->stuck_detected_at ?: now();
            $record->save();

            if (!empty($config['auto_boost_enabled'])) {
                $requiresAtc = !empty($config['auto_boost_requires_atc_webhook']);
                if (!$requiresAtc || ($data['recommendedAction']['action'] ?? '') === 'BOOST_TRANSACTION') {
                    $this->actions->queueOrExecute('boost', $record, 'webhook_atc', $config);
                }
            }
        }

        return [
            'success' => true,
            'message' => 'Stuck transaction alert processed',
            'fireblocks_tx_id' => $stuckTxId,
        ];
    }
}
