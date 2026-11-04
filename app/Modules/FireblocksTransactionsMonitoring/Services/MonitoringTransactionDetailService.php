<?php

namespace App\Modules\FireblocksTransactionsMonitoring\Services;

use App\Helpers\Common;
use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\FireblocksTransaction;
use App\Models\TRRequest;
use App\Modules\FireblocksTransactionsMonitoring\Support\LedgerFireblocksTransactionResolver;
use App\Modules\Fireblocks\Helpers\FireblocksStatusHelper;
use App\Modules\Fireblocks\Support\FireblocksAmount;
use App\Modules\FireblocksTransactionsMonitoring\Models\FireblocksTransactionAlert;
use App\Modules\Fireblocks\Support\TrFireblocksBeneficiaryResolver;
use App\Modules\FireblocksTransactionsMonitoring\Models\FireblocksTransactionMonitoringLog;

class MonitoringTransactionDetailService
{
    public function __construct(
        protected TransactionActionEligibilityService $eligibility,
        protected MonitoringQueueStatusService $queueStatus,
        protected TransactionRetryChainService $retryChain,
        protected ChainalysisComplianceSyncService $chainalysisCompliance,
        protected MonitoringLedgerAccountResolver $ledgerAccountResolver,
    ) {
    }

    /**
     * Unified monitoring detail payload for dashboard modal, ledger panel, and API.
     *
     * @param  array{log_limit?: int, alert_limit?: int}  $options
     * @return array<string, mixed>
     */
    public function build(
        FireblocksTransaction $record,
        ?AccountTransaction $accountTransaction = null,
        array $options = []
    ): array {
        $logLimit = (int) ($options['log_limit'] ?? 50);
        $alertLimit = (int) ($options['alert_limit'] ?? 20);

        $config = MonitoringConfigService::tryGet() ?: [];
        $monitoringActive = Common::isModuleEnabled('Fireblocks')
            && Common::isModuleEnabled('FireblocksTransactionsMonitoring');

        if (!$accountTransaction) {
            $accountTransaction = AccountTransaction::where('crypto_transaction_id', $record->fireblocks_tx_id)
                ->orderByDesc('id')
                ->first();
        }

        $ledgerAccount = $this->ledgerAccountResolver->resolve($record);
        $account = $ledgerAccount && $ledgerAccount['account_id']
            ? Account::withoutGlobalScopes()->find($ledgerAccount['account_id'])
            : ($accountTransaction?->account_id
                ? Account::find($accountTransaction->account_id)
                : null);

        if (!$accountTransaction && !empty($ledgerAccount['account_transaction_id'])) {
            $accountTransaction = AccountTransaction::find($ledgerAccount['account_transaction_id']);
        }

        $resolution = FireblocksStatusHelper::resolveForMonitoring($record);
        $ledgerUrl = $accountTransaction
            ? url('/crypto-management/crypto-transactions/edit/crypto-transaction/' . $accountTransaction->id)
            : (!empty($ledgerAccount['tr_request_id'])
                ? url('/crypto-management/crypto-pending-transactions/view/crypto-transaction/' . $ledgerAccount['tr_request_id'])
                : null);

        $logs = FireblocksTransactionMonitoringLog::where('fireblocks_tx_id', $record->fireblocks_tx_id)
            ->orderByDesc('created_at')
            ->limit($logLimit)
            ->get();

        $alerts = FireblocksTransactionAlert::where('fireblocks_tx_id', $record->fireblocks_tx_id)
            ->orderByDesc('created_at')
            ->limit($alertLimit)
            ->get();

        $availableActions = $monitoringActive
            ? $this->eligibility->availableActions($record, $config)
            : null;

        $responseData = is_array($record->response_data) ? $record->response_data : [];
        $chainalysisResolved = $this->chainalysisCompliance->resolveForGrid($record);
        $chainalysisStatus = $chainalysisResolved['status'] ?? $record->chainalysis_status;
        $chainalysisScanUrl = $chainalysisResolved['scan_url'] ?? $record->chainalysis_scan_url;
        $chainalysisLinks = is_array($responseData['chainalysis_scan_links'] ?? null)
            ? $responseData['chainalysis_scan_links']
            : [];
        $entityPortal = $this->chainalysisCompliance->kytEntityPortalUrl((string) ($record->destination_address ?? ''));
        if ($entityPortal !== null) {
            $chainalysisLinks['kyt_portal'] = $entityPortal;
            $chainalysisLinks['primary'] = $entityPortal;
            $chainalysisScanUrl = $entityPortal;
        } elseif ($chainalysisScanUrl && empty($chainalysisLinks['primary'])) {
            $chainalysisLinks['primary'] = $chainalysisScanUrl;
        }
        $fireblocksCryptoBeneficiary = TrFireblocksBeneficiaryResolver::resolveForFireblocksTransaction($record);

        return [
            'transaction' => $record,
            'enabled' => $monitoringActive,
            'monitoring_active' => $monitoringActive,
            'config' => $config,
            'available_actions' => $availableActions,
            'resolution' => $resolution,
            'account_transaction' => $accountTransaction,
            'account' => $account,
            'account_label' => $ledgerAccount['account_label'] ?? ($account ? trim($account->title . ' — ' . $account->account_number) : null),
            'ledger_account' => $ledgerAccount,
            'ledger_url' => $ledgerUrl,
            'ledger_status' => $accountTransaction?->status ?? ($ledgerAccount['ledger_status'] ?? null),
            'primary_status_label' => FireblocksStatusHelper::primaryStatusLabel(
                FireblocksStatusHelper::resolveStoredPrimaryStatus($record) ?: ($record->status ?? '')
            ),
            'primary_status_badge' => FireblocksStatusHelper::primaryStatusBadge(
                FireblocksStatusHelper::resolveStoredPrimaryStatus($record) ?: ($record->status ?? '')
            ),
            'fireblocks_primary_status' => FireblocksStatusHelper::resolveStoredPrimaryStatus($record) ?: ($record->status ?? null),
            'fireblocks_sub_status' => FireblocksStatusHelper::resolveStoredSubStatus($record) ?? $record->sub_status,
            'substatus_message' => FireblocksStatusHelper::getSubstatusMessage(
                FireblocksStatusHelper::resolveStoredSubStatus($record) ?? $record->sub_status
            ),
            'substatus_category' => FireblocksStatusHelper::substatusCategory(
                FireblocksStatusHelper::resolveStoredSubStatus($record) ?? $record->sub_status
            ),
            'internal_status_badge' => FireblocksStatusHelper::internalStatusBadge($resolution['internal_status']),
            'pending_reason' => $record->pending_reason,
            'custody_balance_pending' => FireblocksStatusHelper::isCustodyBalancePending($record),
            'monitoring_badge' => FireblocksStatusHelper::monitoringStateBadge($record->monitoring_state ?: 'normal'),
            'queue_status' => $this->queueStatus->resolve($record),
            'retry_chain' => $this->retryChain->getChain($record),
            'retry_chain_tree' => $this->retryChain->getChainTree($record),
            'amount_fmt' => FireblocksAmount::formatHtml($record),
            'stuck_for' => $record->stuck_detected_at ? $record->stuck_detected_at->diffForHumans(null, true) : null,
            'reattempt_count' => (int) ($record->auto_action_count ?? 0),
            'last_reattempt_at' => $record->last_reattempt_at?->format('Y-m-d H:i:s'),
            'updated_at_fmt' => $record->updated_at?->format('Y-m-d H:i:s'),
            'logs' => $logs,
            'alerts' => $alerts,
            'dashboard_url' => url('/fireblocks/transaction-monitoring') . '?search=' . urlencode($record->fireblocks_tx_id),
            'default_fee_level' => strtoupper($config['boost_fee_level'] ?? 'HIGH'),
            'default_gas_price' => $config['boost_gas_price'] ?? '',
            'chainalysis' => [
                'status' => $chainalysisStatus,
                'badge' => $this->chainalysisCompliance->statusBadge($chainalysisStatus),
                'scan_url' => $chainalysisScanUrl,
                'links' => is_array($chainalysisLinks) ? $chainalysisLinks : [],
                'compliance_results' => $responseData['complianceResults'] ?? null,
                'address_screen' => $responseData['chainalysis_address_screen'] ?? null,
            ],
            'dashboard_columns' => MonitoringConfigService::dashboardColumnVisibility($config),
            'fireblocks_crypto_beneficiary' => $fireblocksCryptoBeneficiary,
            'actors' => app(\App\Modules\Fireblocks\Services\FireblocksTransactionActorService::class)->labelsFor($record),
            'actor_badges' => [
                'initiator' => app(\App\Modules\Fireblocks\Services\FireblocksTransactionActorService::class)->labelsHtml($record, 'initiator'),
                'approvers' => app(\App\Modules\Fireblocks\Services\FireblocksTransactionActorService::class)->labelsHtml($record, 'approvers'),
                'signers' => app(\App\Modules\Fireblocks\Services\FireblocksTransactionActorService::class)->labelsHtml($record, 'signers'),
            ],
        ];
    }

    /**
     * Build detail context for crypto ledger edit pages.
     *
     * @return array<string, mixed>|null
     */
    public function buildForLedger(?AccountTransaction $trn = null, array $options = []): ?array
    {
        if (!Common::isModuleAvailable('Fireblocks')) {
            return null;
        }

        /** @var TRRequest|null $trRequest */
        $trRequest = $options['tr_request'] ?? null;
        if (!$trRequest && $trn?->tr_request_id) {
            $trRequest = TRRequest::query()->find($trn->tr_request_id);
        }

        if (!$trn && !$trRequest) {
            return null;
        }

        if (!$trn && $trRequest) {
            $trn = AccountTransaction::query()
                ->where('tr_request_id', $trRequest->id)
                ->orderByDesc('id')
                ->first();
        }

        $resolved = app(LedgerFireblocksTransactionResolver::class)->resolve($trn, $trRequest);
        if ($resolved === null || empty($resolved['should_show'])) {
            return null;
        }

        $transaction = $resolved['fireblocks_record'];
        if (!$transaction) {
            return [
                'transaction' => null,
                'enabled' => false,
                'monitoring_active' => false,
                'missing_record' => true,
                'crypto_transaction_id' => $resolved['linked_reference'],
            ];
        }

        return $this->build($transaction, $trn, array_merge([
            'log_limit' => 25,
            'alert_limit' => 10,
        ], $options));
    }
}
