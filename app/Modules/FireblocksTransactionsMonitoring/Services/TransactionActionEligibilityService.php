<?php

namespace App\Modules\FireblocksTransactionsMonitoring\Services;

use App\Models\FireblocksTransaction;
use App\Modules\Fireblocks\Helpers\FireblocksStatusHelper;

class TransactionActionEligibilityService
{
    /**
     * @return array{sync: array, boost: array, drop: array, resubmit: array}
     */
    public function availableActions(FireblocksTransaction $record, ?array $config = null): array
    {
        $config = $config ?: (MonitoringConfigService::tryGet() ?: []);

        return [
            'sync' => $this->syncAction(),
            'boost' => $this->boostAction($record, $config),
            'drop' => $this->dropAction($record, $config),
            'resubmit' => $this->resubmitAction($record, $config),
        ];
    }

    public function canBoost(FireblocksTransaction $record, ?array $config = null): bool
    {
        return ($this->boostAction($record, $config))['visible'];
    }

    public function canDrop(FireblocksTransaction $record, ?array $config = null): bool
    {
        return ($this->dropAction($record, $config))['visible'];
    }

    public function canResubmit(FireblocksTransaction $record, ?array $config = null): bool
    {
        return ($this->resubmitAction($record, $config))['visible'];
    }

    public function assertBoostAllowed(FireblocksTransaction $record, ?array $config = null): void
    {
        $action = $this->boostAction($record, $config);
        if (!$action['visible']) {
            throw new \RuntimeException($action['hidden_reason'] ?? 'Boost is not available for this transaction.');
        }
        if (!$action['enabled']) {
            throw new \RuntimeException($action['disabled_reason'] ?? 'Boost cannot be executed for this transaction.');
        }
    }

    public function assertDropAllowed(FireblocksTransaction $record, ?array $config = null): void
    {
        $action = $this->dropAction($record, $config);
        if (!$action['visible']) {
            throw new \RuntimeException($action['hidden_reason'] ?? 'Drop is not available for this transaction.');
        }
        if (!$action['enabled']) {
            throw new \RuntimeException($action['disabled_reason'] ?? 'Drop cannot be executed for this transaction.');
        }
    }

    public function assertResubmitAllowed(FireblocksTransaction $record, ?array $config = null): void
    {
        $action = $this->resubmitAction($record, $config);
        if (!$action['visible']) {
            throw new \RuntimeException($action['hidden_reason'] ?? 'Resubmit is not available for this transaction.');
        }
        if (!$action['enabled']) {
            throw new \RuntimeException($action['disabled_reason'] ?? 'Resubmit cannot be executed for this transaction.');
        }
    }

    protected function syncAction(): array
    {
        return [
            'visible' => true,
            'enabled' => true,
            'disabled_reason' => null,
            'hidden_reason' => null,
        ];
    }

    protected function boostAction(FireblocksTransaction $record, array $config): array
    {
        $eligibleList = $this->resolvedBoostEligibleStatuses($config);
        $userConfigured = $this->hasUserConfiguredList($config, 'boost_eligible_primary_statuses');

        if ($this->isBlockedBySubstatus($record, $config, $eligibleList, $userConfigured)) {
            return $this->hidden('Non-retriable sub-status blocks Boost.');
        }

        if (!$this->isBoostEligible($record, $config)) {
            return $this->hidden('Fireblocks status/sub-status is not in Boost-eligible list.');
        }

        if (empty($record->tx_hash)) {
            return $this->visibleDisabled(
                FireblocksStatusHelper::boostDisabledReason($record->sub_status, false)
            );
        }

        return $this->visibleEnabled();
    }

    protected function dropAction(FireblocksTransaction $record, array $config): array
    {
        $eligibleList = $this->resolvedDropEligibleStatuses($config);
        $userConfigured = $this->hasUserConfiguredList($config, 'drop_eligible_primary_statuses');

        if ($this->isBlockedBySubstatus($record, $config, $eligibleList, $userConfigured)) {
            return $this->hidden('Non-retriable sub-status blocks Drop.');
        }

        if (!$this->isDropEligible($record, $config)) {
            return $this->hidden('Fireblocks status/sub-status is not in Drop-eligible list.');
        }

        if (!FireblocksStatusHelper::isEvmAsset($record->asset_id)) {
            return $this->visibleDisabled(
                FireblocksStatusHelper::dropDisabledReason($record->sub_status, !empty($record->tx_hash), false)
            );
        }

        if (empty($record->tx_hash)) {
            return $this->visibleDisabled(
                FireblocksStatusHelper::dropDisabledReason($record->sub_status, false, true)
            );
        }

        return $this->visibleEnabled();
    }

    protected function resubmitAction(FireblocksTransaction $record, array $config): array
    {
        $eligibleList = $this->resolvedResubmitEligibleStatuses($config);
        $userConfigured = $this->hasUserConfiguredList($config, 'resubmit_eligible_primary_statuses');

        if ($this->isBlockedBySubstatus($record, $config, $eligibleList, $userConfigured)) {
            return $this->hidden('Non-retriable sub-status blocks Resubmit.');
        }

        if (!$this->isResubmitEligible($record, $config)) {
            return $this->hidden('Fireblocks status/sub-status is not in Resubmit-eligible list.');
        }

        $direction = $record->direction ?: 'outgoing';
        if (!in_array($direction, ['outgoing', 'internal'], true)) {
            return $this->hidden('Resubmit is only available for outgoing/internal withdrawals.');
        }

        return $this->visibleEnabled();
    }

    protected function isBoostEligible(FireblocksTransaction $record, array $config): bool
    {
        if (in_array(strtoupper((string) $record->status), FireblocksStatusHelper::BOOST_DROP_EXCLUDED_PRIMARY, true)) {
            return false;
        }

        return FireblocksStatusHelper::matchesActionEligibleList(
            $record->status,
            $record->sub_status,
            $this->resolvedBoostEligibleStatuses($config)
        );
    }

    protected function isDropEligible(FireblocksTransaction $record, array $config): bool
    {
        if (in_array(strtoupper((string) $record->status), FireblocksStatusHelper::BOOST_DROP_EXCLUDED_PRIMARY, true)) {
            return false;
        }

        return FireblocksStatusHelper::matchesActionEligibleList(
            $record->status,
            $record->sub_status,
            $this->resolvedDropEligibleStatuses($config)
        );
    }

    protected function isResubmitEligible(FireblocksTransaction $record, array $config): bool
    {
        $userConfigured = $this->hasUserConfiguredList($config, 'resubmit_eligible_primary_statuses');
        $eligibleList = $userConfigured
            ? ($config['resubmit_eligible_primary_statuses'] ?? [])
            : $this->resolvedResubmitEligibleStatuses($config);

        return FireblocksStatusHelper::isResubmitAllowedByConfig(
            $record->status,
            $record->sub_status,
            $eligibleList,
            $config['retriable_substatuses'] ?? [],
            !$userConfigured
        );
    }

    /** @return array<int, string> */
    protected function resolvedBoostEligibleStatuses(array $config): array
    {
        $configured = $config['boost_eligible_primary_statuses'] ?? [];

        return !empty($configured)
            ? $configured
            : FireblocksStatusHelper::boostDropBestPracticePrimaryStatuses();
    }

    /** @return array<int, string> */
    protected function resolvedDropEligibleStatuses(array $config): array
    {
        $configured = $config['drop_eligible_primary_statuses'] ?? [];

        return !empty($configured)
            ? $configured
            : FireblocksStatusHelper::boostDropBestPracticePrimaryStatuses();
    }

    /** @return array<int, string> */
    protected function resolvedResubmitEligibleStatuses(array $config): array
    {
        $configured = $config['resubmit_eligible_primary_statuses'] ?? [];

        return !empty($configured)
            ? $configured
            : FireblocksStatusHelper::defaultResubmitEligibleStatuses();
    }

    /**
     * @param  array<int, string>  $actionEligibleList
     */
    protected function isBlockedBySubstatus(
        FireblocksTransaction $record,
        array $config,
        array $actionEligibleList,
        bool $userConfiguredActionList
    ): bool {
        return FireblocksStatusHelper::isBlockedByNonRetriableForAction(
            $record->status,
            $record->sub_status,
            $config['non_retriable_substatuses'] ?? [],
            $actionEligibleList,
            $userConfiguredActionList
        );
    }

    protected function hasUserConfiguredList(array $config, string $key): bool
    {
        return !empty($config[$key]);
    }

    protected function visibleEnabled(): array
    {
        return [
            'visible' => true,
            'enabled' => true,
            'disabled_reason' => null,
            'hidden_reason' => null,
        ];
    }

    protected function visibleDisabled(string $reason): array
    {
        return [
            'visible' => true,
            'enabled' => false,
            'disabled_reason' => $reason,
            'hidden_reason' => null,
        ];
    }

    protected function hidden(string $reason): array
    {
        return [
            'visible' => false,
            'enabled' => false,
            'disabled_reason' => null,
            'hidden_reason' => $reason,
        ];
    }
}
