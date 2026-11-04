<?php

namespace App\Modules\FireblocksTransactionsMonitoring\Services;

use App\Models\FireblocksTransaction;
use App\Modules\Fireblocks\Helpers\FireblocksStatusHelper;

class StuckTransactionDetector
{
    public function __construct(
        protected MonitoringNotificationService $notifier,
    ) {}

    public function evaluate(FireblocksTransaction $record, string $triggerSource = 'system'): bool
    {
        $config = MonitoringConfigService::tryGet();
        if (!$config || empty($config['monitoring_enabled'])) {
            return false;
        }

        if (!$this->matchesScope($record, $config)) {
            if ($record->monitoring_state === 'stuck') {
                $record->monitoring_state = 'normal';
                $record->stuck_detected_at = null;
            }
            return false;
        }

        $status = strtoupper((string) $record->status);
        $wasStuck = $record->monitoring_state === 'stuck';
        $isStuck = $this->isStuck($record, $config);

        if ($isStuck) {
            if (!$wasStuck) {
                $record->monitoring_state = 'stuck';
                $record->stuck_detected_at = $record->stuck_detected_at ?: now();
                if (!empty($config['notify_on_stuck_detected'])) {
                    $this->notifier->notifyStuck($record, $triggerSource);
                }
            }
            return true;
        }

        if (in_array($status, FireblocksStatusHelper::APPROVED_STATUSES, true)
            || in_array($status, FireblocksStatusHelper::CANCELLED_STATUSES, true)
            || in_array($status, FireblocksStatusHelper::REJECTED_STATUSES, true)) {
            if ($record->monitoring_state === 'stuck') {
                $record->monitoring_state = 'normal';
                $record->stuck_detected_at = null;
            }
        }

        return false;
    }

    public function isRetriableFailure(FireblocksTransaction $record, array $config): bool
    {
        if (!in_array($record->direction ?: 'outgoing', ['outgoing', 'internal'], true)) {
            return false;
        }

        $resubmitList = $config['resubmit_eligible_primary_statuses'] ?? [];
        $userConfigured = !empty($resubmitList);

        if (FireblocksStatusHelper::isBlockedByNonRetriableForAction(
            $record->status,
            $record->sub_status,
            $config['non_retriable_substatuses'] ?? [],
            $userConfigured ? $resubmitList : FireblocksStatusHelper::defaultResubmitEligibleStatuses(),
            $userConfigured
        )) {
            return false;
        }

        return FireblocksStatusHelper::isResubmitAllowedByConfig(
            $record->status,
            $record->sub_status,
            $resubmitList,
            $config['retriable_substatuses'] ?? [],
            !$userConfigured
        );
    }

    protected function isStuck(FireblocksTransaction $record, array $config): bool
    {
        $status = strtoupper((string) $record->status);
        $monitored = $config['monitored_fireblocks_statuses'];

        if (!in_array($status, $monitored, true)) {
            return false;
        }

        $reference = $record->last_webhook_at ?: $record->updated_at ?: $record->created_at;
        if (!$reference) {
            return false;
        }

        $ageMinutes = $reference->diffInMinutes(now());

        if (in_array($status, FireblocksStatusHelper::STUCK_CONFIRMING_PRIMARY, true)) {
            return $ageMinutes >= $config['stuck_confirming_threshold_minutes'];
        }

        if (in_array($status, FireblocksStatusHelper::STUCK_SIGNATURE_PRIMARY, true)) {
            return $ageMinutes >= $config['stuck_signature_threshold_minutes'];
        }

        if (in_array($status, FireblocksStatusHelper::STUCK_OTHER_PENDING_PRIMARY, true)) {
            return $ageMinutes >= $config['stuck_confirming_threshold_minutes'];
        }

        return false;
    }

    protected function matchesScope(FireblocksTransaction $record, array $config): bool
    {
        $direction = $record->direction ?: 'outgoing';
        $directions = $config['monitored_directions'];

        if ($directions === 'outgoing' && !in_array($direction, ['outgoing', 'internal'], true)) {
            return false;
        }
        if ($directions === 'incoming' && $direction !== 'incoming') {
            return false;
        }

        if (!empty($config['monitored_asset_ids']) && !in_array(strtoupper((string) $record->asset_id), $config['monitored_asset_ids'], true)) {
            return false;
        }

        if (!empty($config['monitored_vault_account_ids'])
            && !in_array((string) $record->source_vault_account_id, $config['monitored_vault_account_ids'], true)) {
            return false;
        }

        return true;
    }
}
