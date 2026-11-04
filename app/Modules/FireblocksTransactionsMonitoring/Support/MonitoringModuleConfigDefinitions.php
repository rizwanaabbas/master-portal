<?php

namespace App\Modules\FireblocksTransactionsMonitoring\Support;

use App\Modules\Fireblocks\Helpers\FireblocksStatusHelper;
use App\Modules\Fireblocks\Support\FireblocksStatusCatalog;
use Spatie\Permission\Models\Role;

/**
 * Canonical module configuration schema for FireblocksTransactionsMonitoring.
 * Used by migrations to seed/update Settings → Module Configurations field metadata.
 */
class MonitoringModuleConfigDefinitions
{
    public static function doc(string $key): string
    {
        return self::docs()[$key] ?? 'https://developers.fireblocks.com/';
    }

    public static function docs(): array
    {
        return [
            'monitoring' => 'https://developers.fireblocks.com/reference/monitoring-transaction-status',
            'boost' => 'https://developers.fireblocks.com/docs/boost-transactions',
            'boost_api' => 'https://developers.fireblocks.com/reference/boost-transactions',
            'stuck_webhook' => 'https://developers.fireblocks.com/reference/webhooks-structures-eventtypes-transaction#transaction-alert-stuck',
            'statuses' => 'https://developers.fireblocks.com/reference/statuses',
            'substatuses' => 'https://developers.fireblocks.com/reference/sub-statuses',
            'webhooks' => 'https://developers.fireblocks.com/reference/webhooks-best-practices',
            'drop' => 'https://developers.fireblocks.com/api-reference/transactions/drop-eth-evm-transaction-by-id',
            'resend_webhooks' => 'https://developers.fireblocks.com/api-reference/webhooks/resend-webhooks-for-a-transaction-by-id',
            'create_tx' => 'https://developers.fireblocks.com/reference/create-transactions',
            'rate_limits' => 'https://developers.fireblocks.com/docs/working-with-rate-limits',
        ];
    }

    public static function defaultMonitoredStatusValue(): string
    {
        return implode(',', [
            'CONFIRMING',
            'BROADCASTING',
            'QUEUED',
            'PENDING_SIGNATURE',
            'SUBMITTED',
            'PENDING_AUTHORIZATION',
            'PENDING_AML_SCREENING',
            'PENDING_3RD_PARTY',
            'PENDING_3RD_PARTY_MANUAL_APPROVAL',
            'PENDING_ENRICHMENT',
            'PENDING_EMAIL_APPROVAL',
            'PARTIALLY_COMPLETED',
        ]);
    }

    /** Default primary statuses for manual/auto Boost (Fireblocks best practice). */
    public static function defaultBoostEligiblePrimaryStatusValue(): string
    {
        return implode(',', FireblocksStatusHelper::boostDropBestPracticePrimaryStatuses());
    }

    /** Default primary statuses for manual/auto Drop (EVM; same set as Boost). */
    public static function defaultDropEligiblePrimaryStatusValue(): string
    {
        return self::defaultBoostEligiblePrimaryStatusValue();
    }

    /** Default failed/rejected statuses + common retriable sub-statuses for Resubmit. */
    public static function defaultResubmitEligiblePrimaryStatusValue(): string
    {
        return implode(',', array_merge(
            FireblocksStatusHelper::REJECTED_STATUSES,
            ['FAIL_ON_LOW_FEE', 'REJECTED_BY_BLOCKCHAIN', 'DROPPED_BY_BLOCKCHAIN']
        ));
    }

    /**
     * Multiselect options for Boost / Drop eligibility (grouped labels).
     */
    public static function boostDropEligibleStatusOptions(): array
    {
        $options = [];

        foreach (FireblocksStatusHelper::BOOST_DROP_PROCESSING_PRIMARY as $status) {
            $options[$status] = FireblocksStatusHelper::primaryStatusLabel($status)
                . ' (' . $status . ') — On-chain processing (recommended)';
        }

        foreach (FireblocksStatusHelper::PENDING_STATUSES as $status) {
            if (!isset($options[$status])) {
                $suffix = $status === 'SIGNED'
                    ? 'Sign-only (no Boost/Drop until broadcast)'
                    : 'Pending (Boost when broadcast)';
                $options[$status] = FireblocksStatusHelper::primaryStatusLabel($status)
                    . ' (' . $status . ') — ' . $suffix;
            }
        }

        foreach (FireblocksStatusHelper::BOOST_DROP_REJECTED_PRIMARY as $status) {
            $options[$status] = FireblocksStatusHelper::primaryStatusLabel($status)
                . ' (' . $status . ') — Failed/rejected (retriable sub-status required)';
        }

        return $options;
    }

    /** Multiselect options for Resubmit-eligible primary statuses (all primaries; recommended preselected). */
    public static function resubmitEligiblePrimaryStatusOptions(): array
    {
        $recommended = array_flip(FireblocksStatusHelper::REJECTED_STATUSES);
        $options = [];

        foreach (FireblocksStatusHelper::allPrimaryStatuses() as $status) {
            $label = FireblocksStatusHelper::primaryStatusLabel($status) . ' (' . $status . ')';
            if (isset($recommended[$status])) {
                $label .= ' — recommended for Resubmit';
            }
            $options[$status] = $label;
        }

        return $options;
    }

    public static function primaryStatusOptions(): array
    {
        return FireblocksStatusHelper::primaryStatusFilterOptions();
    }

    public static function defaultRetriableSubstatusValue(): string
    {
        return implode(',', FireblocksStatusCatalog::defaultRetriableSubstatuses());
    }

    public static function defaultNonRetriableSubstatusValue(): string
    {
        return implode(',', FireblocksStatusCatalog::defaultNonRetriableSubstatuses());
    }

    public static function primaryStatusConfigFilterOptions(): array
    {
        $options = ['all' => 'All'];
        foreach (self::primaryStatusOptions() as $code => $label) {
            $options[$code] = $label . ' (' . $code . ')';
        }

        return $options;
    }

    /** Flat sub-status options for default filter dropdown in module config. */
    public static function substatusFilterConfigOptions(): array
    {
        $options = ['all' => 'All'];
        foreach (FireblocksStatusHelper::substatusFilterOptions() as $group => $items) {
            foreach ($items as $code => $message) {
                $options[$code] = $code . ' — ' . $group;
            }
        }

        return $options;
    }

    /** Grouped sub-status options for module-config multiselect fields. */
    public static function substatusMultiselectGroups(): array
    {
        return FireblocksStatusHelper::substatusMultiselectGroups();
    }

    public static function all(): array
    {
        return self::applyUsageMetadata([
            'monitoring_enabled' => [
                'value' => '0',
                'type' => 'checkbox',
                'label' => 'Monitoring Enabled (recommended: enable after review)',
                'hint' => 'Master switch for the Transaction Monitoring module. When disabled, the scheduler job, stuck detection, auto-actions, and dashboard alerts are all paused. Existing Fireblocks transactions are not modified.',
                'doc_url' => self::doc('monitoring'),
            ],
            'include_aquanow_buy_sell' => [
                'value' => '0',
                'type' => 'checkbox',
                'label' => 'Include Aquanow Buy / Sell in this dashboard',
                'hint' => 'When enabled (and the Aquanow module is on), Transaction Monitoring lists Aquanow Buy and Sell ledger trades in a separate table. Fireblocks Boost / Drop / Resubmit are not available on those rows. B2c2 and other venues are not included.',
                'doc_url' => self::doc('monitoring'),
            ],
            'webhook_stuck_alert_enabled' => [
                'value' => '1',
                'type' => 'checkbox',
                'label' => 'Process transaction.alert.stuck Webhooks — ATC (recommended: on)',
                'hint' => 'ATC = Automatic Transaction Confirmation — Fireblocks’ service that watches the mempool and notifies you when a transaction appears stuck. When enabled, incoming transaction.alert.stuck webhooks mark transactions as stuck, update the dashboard, and can trigger auto-boost / auto-drop (if configured). Requires this event type to be subscribed on your Fireblocks webhook.',
                'doc_url' => self::doc('stuck_webhook'),
            ],
            'polling_fallback_enabled' => [
                'value' => '1',
                'type' => 'checkbox',
                'label' => 'Polling Fallback Enabled (recommended: on)',
                'hint' => 'Runs a scheduled background check (see Polling Interval) against open Fireblocks transactions even when no webhook is received. Catches missed or delayed webhooks and keeps the dashboard in sync. Does not replace webhooks — use both for best coverage.',
                'doc_url' => self::doc('monitoring'),
            ],
            'polling_interval_minutes' => [
                'value' => '5',
                'type' => 'select',
                'label' => 'Polling Interval (minutes)',
                'options' => [
                    '3' => '3 minutes',
                    '5' => '5 minutes (recommended)',
                    '10' => '10 minutes',
                    '15' => '15 minutes',
                    '30' => '30 minutes',
                ],
                'hint' => 'How often the fireblocks:monitor-transactions artisan command runs when Polling Fallback is enabled. Shorter intervals detect stuck transactions sooner but increase API usage. Effect: controls scheduler frequency only; does not change stuck thresholds.',
                'sample' => '5',
                'doc_url' => self::doc('monitoring'),
            ],
            'stuck_confirming_threshold_minutes' => [
                'value' => '15',
                'type' => 'select',
                'label' => 'Stuck Threshold — CONFIRMING / BROADCASTING (minutes)',
                'options' => [
                    '5' => '5 minutes',
                    '10' => '10 minutes',
                    '15' => '15 minutes (recommended)',
                    '30' => '30 minutes',
                    '60' => '60 minutes',
                ],
                'hint' => 'Transactions in CONFIRMING or BROADCASTING longer than this value are flagged as potentially stuck during polling. CONFIRMING = broadcast to the network, awaiting block inclusion. BROADCASTING = being propagated. Lower values alert earlier; higher values reduce false positives on slow networks.',
                'sample' => '15',
                'doc_url' => self::doc('statuses'),
            ],
            'stuck_signature_threshold_minutes' => [
                'value' => '60',
                'type' => 'select',
                'label' => 'Alert Threshold — PENDING_SIGNATURE / QUEUED (minutes)',
                'options' => [
                    '30' => '30 minutes',
                    '60' => '60 minutes (recommended)',
                    '120' => '120 minutes',
                    '180' => '180 minutes',
                ],
                'hint' => 'Transactions waiting for co-signer approval (PENDING_SIGNATURE) or internal Fireblocks queue (QUEUED) longer than this value generate a “stalled approval” alert. These are operational delays, not blockchain mempool issues — no boost/drop is attempted automatically.',
                'sample' => '60',
                'doc_url' => self::doc('statuses'),
            ],
            'auto_boost_enabled' => [
                'value' => '0',
                'type' => 'checkbox',
                'label' => 'Auto Boost (RBF) Enabled (recommended: off until UAT)',
                'hint' => 'RBF = Replace-By-Fee — resubmits a stuck transaction with a higher fee so miners pick it up. On UTXO chains (BTC, LTC, etc.) Fireblocks uses RBF; on EVM chains (ETH, Polygon, BSC, etc.) it increases gas price. When enabled, the system calls the Fireblocks Boost API after stuck detection (subject to max attempts and approval settings).',
                'doc_url' => self::doc('boost'),
            ],
            'auto_boost_requires_atc_webhook' => [
                'value' => '1',
                'type' => 'checkbox',
                'label' => 'Auto Boost Only When ATC Webhook Received (recommended: on)',
                'hint' => 'When enabled, auto-boost runs only if Fireblocks ATC has explicitly sent a transaction.alert.stuck webhook for that transaction. Polling-only stuck detection will alert operators but will not auto-boost. Reduces accidental fee bumps on transactions that are slow but not truly stuck.',
                'doc_url' => self::doc('stuck_webhook'),
            ],
            'auto_drop_enabled' => [
                'value' => '0',
                'type' => 'checkbox',
                'label' => 'Auto Drop EVM After Max Boosts (recommended: off)',
                'hint' => 'EVM = Ethereum Virtual Machine — blockchains such as Ethereum, Polygon, Arbitrum, Avalanche C-Chain, and other smart-contract networks. Drop cancels a stuck EVM transaction by replacing it with a zero-value self-transfer at a higher gas price, freeing the nonce for a resubmit. Runs only after Max Auto Boost Attempts is exhausted. Not available on UTXO chains.',
                'doc_url' => self::doc('drop'),
            ],
            'auto_cpfp_enabled' => [
                'value' => '0',
                'type' => 'checkbox',
                'label' => 'Auto CPFP (BTC) Enabled (recommended: off — manual first)',
                'hint' => 'CPFP = Child Pays For Parent — a Bitcoin technique where a new “child” transaction spends an output of the stuck “parent” and pays a high fee, incentivizing miners to confirm both together. Only applies to BTC (UTXO). Fireblocks supports CPFP only while the parent transaction is within the CPFP Max Transaction Age limit.',
                'doc_url' => self::doc('boost'),
            ],
            'auto_resubmit_mode' => [
                'value' => 'manual',
                'type' => 'select',
                'label' => 'Failed Withdrawal Resubmit Mode',
                'options' => [
                    'manual' => 'Manual — operator triggers (recommended)',
                    'auto' => 'Automatic — system resubmits retriable failures',
                ],
                'hint' => 'Controls what happens after a withdrawal reaches FAILED with a retriable sub-status (e.g. REJECTED_BY_BLOCKCHAIN). Manual: operator reviews and clicks Resubmit on the dashboard. Auto: system creates a new Fireblocks transaction with the same destination and amount, up to Max Resubmit Attempts.',
                'sample' => 'manual',
                'doc_url' => self::doc('create_tx'),
            ],
            'manual_approval_required' => [
                'value' => '1',
                'type' => 'checkbox',
                'label' => 'Require Manual Approval Before Auto Actions (recommended: on)',
                'hint' => 'When enabled, auto-boost, auto-drop, auto-CPFP, and auto-resubmit are queued as “pending approval” on the dashboard instead of executing immediately. An operator must approve each action. Effect: adds a human gate before any Fireblocks write API call from automation.',
                'doc_url' => self::doc('boost'),
            ],
            'dry_run_mode' => [
                'value' => '0',
                'type' => 'checkbox',
                'label' => 'Dry Run Mode — log only, no Fireblocks API calls (recommended: on for UAT)',
                'hint' => 'When enabled, stuck detection, alerts, and audit logs run normally but boost, drop, CPFP, and resubmit actions are simulated and written to the monitoring log only — no calls to Fireblocks. Use during UAT to validate thresholds and notification flow without spending fees or altering transactions.',
                'doc_url' => self::doc('boost_api'),
            ],
            'boost_fee_level' => [
                'value' => 'HIGH',
                'type' => 'select',
                'label' => 'Boost / Drop Fee Level (Fireblocks feeLevel)',
                'options' => [
                    'LOW' => 'LOW',
                    'MEDIUM' => 'MEDIUM',
                    'HIGH' => 'HIGH (recommended for stuck transactions)',
                ],
                'hint' => 'Fireblocks feeLevel parameter passed to Boost and Drop API calls. LOW / MEDIUM / HIGH map to network-specific fee estimates. Used when Optional Boost Gas Price Override is empty. Effect: higher levels increase the chance of confirmation but cost more in network fees.',
                'sample' => 'HIGH',
                'doc_url' => self::doc('boost'),
            ],
            'boost_gas_price' => [
                'value' => '',
                'type' => 'text',
                'label' => 'Optional Boost Gas Price Override (EVM)',
                'placeholder' => 'e.g. 120',
                'sample' => '120',
                'required' => false,
                'hint' => 'EVM = Ethereum Virtual Machine chains (ETH, MATIC, BNB, etc.). Optional fixed gas price in gwei sent to the Fireblocks Boost API instead of using feeLevel. Leave empty to let Fireblocks calculate fees from Boost / Drop Fee Level. Effect: overrides automatic fee estimation; use when network congestion requires an explicit gas price.',
                'doc_url' => self::doc('boost_api'),
            ],
            'max_auto_boost_attempts' => [
                'value' => '2',
                'type' => 'select',
                'label' => 'Max Auto Boost Attempts Per Transaction',
                'options' => [
                    '1' => '1',
                    '2' => '2 (recommended)',
                    '3' => '3',
                ],
                'hint' => 'Maximum number of automatic boost (RBF) calls allowed per Fireblocks transaction ID. After this limit, the transaction is marked as “boost exhausted” and auto-drop may run (if enabled). Effect: prevents runaway fee spending on persistently stuck transactions.',
                'sample' => '2',
                'doc_url' => self::doc('boost'),
            ],
            'max_auto_drop_attempts' => [
                'value' => '1',
                'type' => 'select',
                'label' => 'Max Auto Drop Attempts Per Transaction',
                'options' => [
                    '1' => '1 (recommended)',
                    '2' => '2',
                ],
                'hint' => 'Maximum Drop API calls per stuck EVM transaction. Drop replaces the pending transaction to free the nonce. Usually one drop is sufficient; a second attempt is rarely needed. Effect: caps EVM cancellation retries.',
                'sample' => '1',
                'doc_url' => self::doc('drop'),
            ],
            'max_resubmit_attempts' => [
                'value' => '1',
                'type' => 'select',
                'label' => 'Max Resubmit Attempts Per Failed Withdrawal',
                'options' => [
                    '1' => '1 (recommended)',
                    '2' => '2',
                    '3' => '3',
                ],
                'hint' => 'Maximum number of times a failed withdrawal can be automatically or manually resubmitted as a new Fireblocks transaction. Applies only to sub-statuses listed under Retriable Sub-Statuses. Effect: prevents duplicate payouts beyond this cap.',
                'sample' => '1',
                'doc_url' => self::doc('create_tx'),
            ],
            'cpfp_max_age_days' => [
                'value' => '10',
                'type' => 'select',
                'label' => 'CPFP Max Transaction Age (days)',
                'options' => [
                    '7' => '7 days',
                    '10' => '10 days (recommended)',
                    '14' => '14 days',
                ],
                'hint' => 'CPFP = Child Pays For Parent (Bitcoin only). Fireblocks allows CPFP only while the parent transaction is younger than this age limit (platform maximum is typically ~10 days). Transactions older than this threshold are excluded from auto-CPFP and manual CPFP actions. Effect: filters BTC boost candidates by age.',
                'sample' => '10',
                'doc_url' => self::doc('boost'),
            ],
            'monitored_fireblocks_statuses' => [
                'value' => self::defaultMonitoredStatusValue(),
                'type' => 'multiselect',
                'label' => 'Monitored Fireblocks Primary Statuses',
                'options' => self::primaryStatusOptions(),
                'hint' => 'Primary Fireblocks transaction statuses included in polling and dashboard visibility. Only transactions in selected statuses are evaluated for stuck detection. COMPLETED and FAILED are typically excluded. Effect: narrows which open transactions the module tracks.',
                'sample' => 'CONFIRMING,BROADCASTING,QUEUED',
                'doc_url' => self::doc('statuses'),
            ],
            'retriable_substatuses' => [
                'value' => self::defaultRetriableSubstatusValue(),
                'type' => 'textarea',
                'label' => 'Retriable Sub-Statuses (comma-separated)',
                'placeholder' => 'REJECTED_BY_BLOCKCHAIN,DROPPED_BY_BLOCKCHAIN,FAIL_ON_LOW_FEE,TIMEOUT',
                'hint' => 'Comma-separated Fireblocks sub-status codes considered safe to retry. Used as a fallback for Resubmit when the transaction primary is FAILED/REJECTED/BLOCKED/TIMEOUT and the sub-status is listed here but not covered by Resubmit-eligible above. Does not control Boost/Drop. Codes also listed in a Resubmit-eligible textarea take precedence over this list.',
                'sample' => 'REJECTED_BY_BLOCKCHAIN,DROPPED_BY_BLOCKCHAIN,FAIL_ON_LOW_FEE,TIMEOUT,CONNECTIVITY_ERROR',
                'doc_url' => self::doc('substatuses'),
            ],
            'non_retriable_substatuses' => [
                'value' => self::defaultNonRetriableSubstatusValue(),
                'type' => 'textarea',
                'label' => 'Non-Retriable Sub-Statuses (comma-separated)',
                'placeholder' => 'INSUFFICIENT_FUNDS,BLOCKED_BY_POLICY,REJECTED_AML_SCREENING',
                'hint' => 'Comma-separated Fireblocks sub-status codes that block Boost, Drop, and Resubmit by default. If you also list the same code in a Boost/Drop/Resubmit textarea, that action is allowed when the transaction matches.',
                'sample' => 'INSUFFICIENT_FUNDS,BLOCKED_BY_POLICY,INSUFFICIENT_FUNDS_FOR_FEE,REJECTED_AML_SCREENING',
                'doc_url' => self::doc('substatuses'),
            ],
            'monitored_asset_ids' => [
                'value' => '',
                'type' => 'textarea',
                'label' => 'Monitored Asset IDs (empty = all assets, comma-separated)',
                'placeholder' => 'e.g. ETH,BTC,USDC_ETH',
                'sample' => 'ETH,BTC,USDC_ETH',
                'hint' => 'Fireblocks asset IDs to include in monitoring. Leave empty to monitor all assets. Use this to limit auto-actions to high-value or production assets only. Effect: transactions for unlisted assets are ignored by polling and webhooks.',
                'doc_url' => self::doc('monitoring'),
            ],
            'monitored_vault_account_ids' => [
                'value' => '',
                'type' => 'textarea',
                'label' => 'Monitored Vault Account IDs (empty = all vaults, comma-separated)',
                'placeholder' => 'e.g. 0,123,456',
                'sample' => '0,123,456',
                'hint' => 'Fireblocks vault account IDs to include. Leave empty for all vaults. Useful when only specific operational vaults should trigger auto-boost or alerts. Effect: transactions from other vaults are excluded from detection and actions.',
                'doc_url' => self::doc('monitoring'),
            ],
            'monitored_directions' => [
                'value' => 'outgoing',
                'type' => 'select',
                'label' => 'Monitored Transaction Directions',
                'options' => [
                    'outgoing' => 'Outgoing only (recommended)',
                    'incoming' => 'Incoming only',
                    'both' => 'Both incoming and outgoing',
                ],
                'hint' => 'Which transaction direction to monitor. Outgoing (withdrawals) are most commonly stuck in the mempool and benefit from boost/drop. Incoming deposits rarely need fee bumping. Effect: filters transactions by source/destination relative to your vault.',
                'sample' => 'outgoing',
                'doc_url' => self::doc('monitoring'),
            ],
            'resend_webhook_on_stale' => [
                'value' => '1',
                'type' => 'checkbox',
                'label' => 'Resend Fireblocks Webhooks Before Polling Stale Txs (recommended: on)',
                'hint' => 'Before polling a transaction whose last webhook is older than Stale Webhook Threshold, calls the Fireblocks Resend Webhooks API to refresh status from Fireblocks. Reduces stale local state when webhooks were missed. Effect: extra API call per stale transaction before each poll cycle.',
                'doc_url' => self::doc('resend_webhooks'),
            ],
            'stale_webhook_threshold_minutes' => [
                'value' => '30',
                'type' => 'select',
                'label' => 'Stale Webhook Threshold (minutes)',
                'options' => [
                    '15' => '15 minutes',
                    '30' => '30 minutes (recommended)',
                    '60' => '60 minutes',
                    '120' => '120 minutes',
                ],
                'hint' => 'If no webhook has been received for a monitored transaction within this many minutes, the system treats local status as potentially stale and triggers a webhook resend (when that option is enabled) before polling. Effect: defines “stale” for webhook refresh logic.',
                'sample' => '30',
                'doc_url' => self::doc('webhooks'),
            ],
            'notify_on_stuck_detected' => [
                'value' => '1',
                'type' => 'checkbox',
                'label' => 'Email Notify On Stuck Detection (recommended: on)',
                'hint' => 'Sends an email to Notification Emails when a transaction is newly flagged as stuck (via webhook or polling). Includes transaction ID, asset, and status. Effect: operational alert only — does not trigger Fireblocks actions.',
                'doc_url' => self::doc('stuck_webhook'),
            ],
            'notify_on_auto_action' => [
                'value' => '1',
                'type' => 'checkbox',
                'label' => 'Email Notify On Auto Action (recommended: on)',
                'hint' => 'Sends an email when an automated boost, drop, CPFP, or resubmit is executed or queued for approval. Includes action type, result, and Fireblocks transaction ID. Effect: audit trail for automated fee operations.',
                'doc_url' => self::doc('boost'),
            ],
            'notification_emails' => [
                'value' => '',
                'type' => 'textarea',
                'label' => 'Notification Emails (comma-separated)',
                'placeholder' => 'e.g. ops@example.com,admin@example.com',
                'sample' => 'ops@example.com,admin@example.com',
                'hint' => 'Recipient email addresses for stuck and auto-action notifications. Comma-separated list. Leave empty to disable email delivery even if notification toggles are on. Effect: determines who receives operational alerts from this module.',
            ],
            'low_balance_alert_recipient_roles' => [
                'value' => '',
                'type' => 'multiselect',
                'label' => 'Low Balance Alert Recipient Roles (liquidity & custody)',
                'options' => self::spatieRoleOptions(),
                'hint' => 'Users with any selected role receive email when Aquanow liquidity is too low (client balance OK) or Fireblocks custody vault balance rejects a withdrawal (client balance OK). Values are Spatie role IDs. Leave empty to use the finance role.',
                'sample' => '',
            ],
            'dashboard_page_size' => [
                'value' => '15',
                'type' => 'select',
                'label' => 'Dashboard Page Size',
                'options' => [
                    '10' => '10',
                    '15' => '15 (recommended)',
                    '25' => '25',
                    '50' => '50',
                    '100' => '100',
                ],
                'hint' => 'Number of rows per page on the Transaction Monitoring dashboard DataTable (latest first). Effect: UI pagination only; does not affect detection or API behavior.',
                'sample' => '15',
            ],
            'dashboard_column_queue' => [
                'value' => '0',
                'type' => 'checkbox',
                'label' => 'Dashboard — show Queue column',
                'hint' => 'When off (default), the Queue column is hidden on the grid and in transaction details.',
            ],
            'dashboard_column_reattempts' => [
                'value' => '0',
                'type' => 'checkbox',
                'label' => 'Dashboard — show Reattempts column',
                'hint' => 'When off (default), hides auto boost/drop/resubmit attempt count on the grid and in details.',
            ],
            'dashboard_column_last_reattempt' => [
                'value' => '0',
                'type' => 'checkbox',
                'label' => 'Dashboard — show Last Reattempt column',
                'hint' => 'When off (default), hides the last auto-action timestamp on the grid and in details.',
            ],
            'dashboard_column_stuck_for' => [
                'value' => '0',
                'type' => 'checkbox',
                'label' => 'Dashboard — show Stuck For column',
                'hint' => 'When off (default), hides human-readable stuck duration on the grid; Stuck Since in details is hidden too.',
            ],
            'dashboard_column_ledger' => [
                'value' => '0',
                'type' => 'checkbox',
                'label' => 'Dashboard — show Ledger column',
                'hint' => 'When off (default), hides ledger posting status (TR / account_transactions) on the grid and Ledger Status in details. Account column still shows the linked client account; Internal column remains for Fireblocks custody rollup.',
            ],
            'audit_retention_days' => [
                'value' => '365',
                'type' => 'select',
                'label' => 'Audit Log Retention (days)',
                'options' => [
                    '90' => '90 days',
                    '180' => '180 days',
                    '365' => '365 days (recommended)',
                    '730' => '730 days',
                ],
                'hint' => 'How long monitoring action logs (boost, drop, sync, alerts) are kept in fireblocks_transaction_monitoring_logs before cleanup. Effect: storage and compliance retention for the audit trail; older rows may be purged by a scheduled job.',
                'sample' => '365',
            ],
            'boost_eligible_primary_statuses' => [
                'value' => self::defaultBoostEligiblePrimaryStatusValue(),
                'type' => 'textarea',
                'label' => 'Boost-Eligible Fireblocks Statuses (primary or sub-status, comma-separated)',
                'placeholder' => 'CONFIRMING,BROADCASTING,SUBMITTED,FAILED,FAIL_ON_LOW_FEE',
                'hint' => 'Comma-separated primary statuses and/or sub-statuses that show the Boost button. Keep processing primaries (CONFIRMING, BROADCASTING, SUBMITTED) for stuck on-chain txs. Sub-status codes (e.g. INSUFFICIENT_FUNDS) match only when that sub-status is on the transaction — include FAILED too if needed. Boost requires a tx hash; funding failures usually need Resubmit instead.',
                'sample' => 'CONFIRMING,BROADCASTING,SUBMITTED,FAILED,REJECTED_BY_BLOCKCHAIN',
                'doc_url' => self::doc('boost'),
            ],
            'drop_eligible_primary_statuses' => [
                'value' => self::defaultDropEligiblePrimaryStatusValue(),
                'type' => 'textarea',
                'label' => 'Drop-Eligible Fireblocks Statuses (primary or sub-status, comma-separated)',
                'placeholder' => 'CONFIRMING,BROADCASTING,SUBMITTED,FAILED,DROPPED_BY_BLOCKCHAIN',
                'hint' => 'Comma-separated primary statuses and/or sub-statuses that show the Drop button (EVM only at runtime). Same rules as Boost: keep CONFIRMING/BROADCASTING/SUBMITTED for stuck txs; sub-status codes need that sub-status on the tx; Drop requires a tx hash.',
                'sample' => 'CONFIRMING,BROADCASTING,SUBMITTED',
                'doc_url' => self::doc('drop'),
            ],
            'resubmit_eligible_primary_statuses' => [
                'value' => self::defaultResubmitEligiblePrimaryStatusValue(),
                'type' => 'textarea',
                'label' => 'Resubmit-Eligible Fireblocks Statuses (primary or sub-status, comma-separated)',
                'placeholder' => 'FAILED,REJECTED,BLOCKED,TIMEOUT,FAIL_ON_LOW_FEE,REJECTED_BY_BLOCKCHAIN',
                'hint' => 'Comma-separated primary statuses and/or sub-statuses that show the Resubmit button for outgoing/internal withdrawals. Example: list FAILED plus FAIL_ON_LOW_FEE to allow resubmit only for that failure reason.',
                'sample' => 'FAILED,REJECTED,TIMEOUT,BLOCKED,FAIL_ON_LOW_FEE,REJECTED_BY_BLOCKCHAIN',
                'doc_url' => self::doc('create_tx'),
            ],
            'poisoning_deposit_max_amount' => [
                'value' => '1',
                'type' => 'text',
                'label' => 'Poisoning deposit max amount (default per asset)',
                'placeholder' => '1',
                'hint' => 'Transaction scope “Small incomings” shows incoming ledger-linked deposits at or below this amount (same units as on-chain asset). Tune per asset below for ETH/BTC dust.',
                'sample' => '1',
            ],
            'poisoning_deposit_asset_max_amounts' => [
                'value' => '',
                'type' => 'textarea',
                'label' => 'Poisoning deposit max amount overrides (optional)',
                'placeholder' => 'ETH:0.00005,BTC:0.00001,USDC_ETH:1',
                'hint' => 'Comma-separated ASSET_ID:max pairs. Overrides the default max for matching Fireblocks asset IDs (e.g. small native-unit thresholds).',
                'sample' => 'ETH:0.00005,BTC:0.00001,USDC_ETH:1',
            ],
            'default_filter_monitoring_state' => [
                'value' => 'all',
                'type' => 'select',
                'label' => 'Default Dashboard Filter: Monitoring State',
                'options' => [
                    'all' => 'All',
                    'stuck' => 'Stuck',
                    'action_pending' => 'Action Pending',
                    'boosted' => 'Boosted',
                    'dropped' => 'Dropped',
                    'resubmitted' => 'Resubmitted',
                    'normal' => 'Normal',
                ],
                'hint' => 'Pre-selected Monitoring State when operators open the dashboard. Fireblocks status, sub-status, and direction filters default to All (or follow Monitored Transaction Directions for direction). Operator choices persist in browser session until cleared.',
            ],
        ]);
    }

    /**
     * @param  array<string, array<string, mixed>>  $definitions
     * @return array<string, array<string, mixed>>
     */
    protected static function applyUsageMetadata(array $definitions): array
    {
        $usage = self::usageMetadata();

        foreach ($definitions as $key => $definition) {
            if (isset($usage[$key])) {
                $definitions[$key]['usage'] = $usage[$key];
            }
        }

        return $definitions;
    }

    /**
     * @return array<string, string>
     */
    protected static function usageMetadata(): array
    {
        return [
            'monitoring_enabled' => 'MonitoringConfigService, scheduler fireblocks:monitor-transactions, middleware fireblocks.monitoring.active, StuckTransactionDetector gating.',
            'webhook_stuck_alert_enabled' => 'StuckAlertHandler on transaction.alert.stuck webhooks in WebhookService.',
            'polling_fallback_enabled' => 'PollStuckTransactionsJob / MonitorFireblocksTransactions artisan command.',
            'polling_interval_minutes' => 'Console Kernel schedule interval for fireblocks:monitor-transactions.',
            'stuck_confirming_threshold_minutes' => 'StuckTransactionDetector for CONFIRMING/BROADCASTING primaries.',
            'stuck_signature_threshold_minutes' => 'StuckTransactionDetector alerts for PENDING_SIGNATURE / QUEUED.',
            'auto_boost_enabled' => 'StuckAlertHandler / ExecuteTransactionActionJob auto boost path.',
            'auto_boost_requires_atc_webhook' => 'Auto boost gate — requires prior transaction.alert.stuck webhook.',
            'auto_drop_enabled' => 'Auto drop after max boosts on EVM (TransactionActionService).',
            'auto_cpfp_enabled' => 'BTC CPFP auto path (when implemented/enabled).',
            'auto_resubmit_mode' => 'Failed withdrawal resubmit — manual dashboard vs automatic detector.',
            'manual_approval_required' => 'Queues auto actions as action_pending until operator approves on dashboard.',
            'dry_run_mode' => 'TransactionActionService — logs actions without Fireblocks write API calls.',
            'boost_fee_level' => 'Boost/Drop API feeLevel; dashboard detail default fee display.',
            'boost_gas_price' => 'Optional EVM gas override on Boost API calls.',
            'max_auto_boost_attempts' => 'Caps automatic boost attempts per fireblocks_tx_id.',
            'max_auto_drop_attempts' => 'Caps automatic EVM drop attempts per transaction.',
            'max_resubmit_attempts' => 'Resubmit chain limit (TransactionActionService / detector).',
            'cpfp_max_age_days' => 'BTC CPFP eligibility age filter.',
            'monitored_fireblocks_statuses' => 'PollStuckTransactionsJob query filter; stuck evaluation scope.',
            'retriable_substatuses' => 'TransactionActionEligibilityService resubmit fallback rules.',
            'non_retriable_substatuses' => 'TransactionActionEligibilityService — blocks boost/drop/resubmit unless overridden in action textareas.',
            'monitored_asset_ids' => 'StuckTransactionDetector / sync scope filter when non-empty.',
            'monitored_vault_account_ids' => 'StuckTransactionDetector / sync scope filter when non-empty.',
            'monitored_directions' => 'Detector direction filter; dashboard default Direction filter (MonitoringConfigService).',
            'resend_webhook_on_stale' => 'Poll path — Fireblocks resend webhooks before API poll when stale.',
            'stale_webhook_threshold_minutes' => 'Defines stale last_webhook_at for resend-before-poll logic.',
            'notify_on_stuck_detected' => 'MonitoringNotificationService email on new stuck flag.',
            'notify_on_auto_action' => 'MonitoringNotificationService email after automated/queued actions.',
            'notification_emails' => 'Recipient list for monitoring notification emails.',
            'low_balance_alert_recipient_roles' => 'Spatie role IDs for Aquanow liquidity and Fireblocks custody low-balance alert emails (mobile-api + portal).',
            'dashboard_page_size' => 'Transaction Monitoring dashboard DataTables pageLength.',
            'dashboard_column_queue' => 'Grid column + detail Queue status visibility.',
            'dashboard_column_reattempts' => 'Grid/detail auto_action_count column visibility.',
            'dashboard_column_last_reattempt' => 'Grid/detail last_reattempt_at column visibility.',
            'dashboard_column_stuck_for' => 'Grid/detail stuck duration column visibility.',
            'dashboard_column_ledger' => 'Grid/detail ledger posting status column visibility.',
            'audit_retention_days' => 'Retention hint for fireblocks_transaction_monitoring_logs cleanup jobs.',
            'boost_eligible_primary_statuses' => 'TransactionActionEligibilityService — Boost button and auto boost.',
            'drop_eligible_primary_statuses' => 'TransactionActionEligibilityService — Drop button and auto drop.',
            'resubmit_eligible_primary_statuses' => 'TransactionActionEligibilityService — Resubmit button and auto resubmit.',
            'poisoning_deposit_max_amount' => 'PoisoningDepositScope — Transaction scope “Small incomings” on monitoring dashboard.',
            'poisoning_deposit_asset_max_amounts' => 'PoisoningDepositScope per-asset max amount overrides.',
            'default_filter_monitoring_state' => 'MonitoringConfigService::dashboardDefaultFilters — initial Monitoring State on dashboard load.',
        ];
    }

    /**
     * Merge schema into stored configuration, preserving user values.
     */
    /**
     * Refresh multiselect options that depend on live database data (Spatie roles).
     *
     * @param  array<string, array<string, mixed>>  $configurations
     * @return array<string, array<string, mixed>>
     */
    public static function refreshDynamicOptions(array $configurations): array
    {
        $configurations = self::mergeInto($configurations);

        if (isset($configurations['low_balance_alert_recipient_roles'])) {
            $configurations['low_balance_alert_recipient_roles']['options'] = self::spatieRoleOptions();
        }

        return $configurations;
    }

    /** @return array<string, string> */
    public static function spatieRoleOptions(): array
    {
        try {
            return Role::query()->orderBy('name')->pluck('name', 'id')->all();
        } catch (\Throwable) {
            return [
                'finance' => 'finance',
                'finance manager' => 'finance manager',
                'operations manager' => 'operations manager',
            ];
        }
    }

    public static function mergeInto(array $existing): array
    {
        $definitions = self::all();
        $merged = [];

        foreach ($definitions as $key => $definition) {
            if (isset($existing[$key])) {
                $merged[$key] = array_merge($definition, [
                    'value' => $existing[$key]['value'] ?? $definition['value'],
                ]);
            } else {
                $merged[$key] = $definition;
            }
        }

        return $merged;
    }
}
