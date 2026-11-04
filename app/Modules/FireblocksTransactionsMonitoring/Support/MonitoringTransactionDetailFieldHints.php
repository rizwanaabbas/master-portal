<?php

namespace App\Modules\FireblocksTransactionsMonitoring\Support;

/**
 * Help text for Fireblocks Transaction Monitoring detail rows (tooltips).
 */
final class MonitoringTransactionDetailFieldHints
{
    public static function all(): array
    {
        return [
            'fireblocks_tx_id' => 'Fireblocks platform UUID for this transfer. Click the value to copy.',
            'asset_amount' => 'Asset ID and quantity for this Fireblocks transaction.',
            'direction' => 'Outgoing = withdrawal from a vault; incoming = deposit to a vault.',
            'initiator' => 'Fireblocks user who created the transaction (createdBy). Names resolve from the synced Fireblocks users table.',
            'tap_approvers' => 'TAP users who approved this transaction (authorizationInfo groups with status APPROVED).',
            'signers' => 'Fireblocks users who signed the transaction (signedBy).',
            'primary_status' => 'Top-level Fireblocks lifecycle state (e.g. COMPLETED, CONFIRMING, FAILED). The code in parentheses is the raw API value.',
            'sub_status' => 'Finer-grained Fireblocks phase or failure reason. The category badge groups related sub-status codes for ops.',
            'internal_status' => 'Portal rollup of primary + sub-status for custody lifecycle. This is not ledger posting status (see Ledger Status when enabled).',
            'tx_hash' => 'On-chain transaction hash after broadcast. Empty until the transfer is submitted to the network. Click to copy.',
            'vault_account' => 'Fireblocks source vault account ID for this transfer.',
            'destination' => 'Destination blockchain address (or identifier) for outgoing transfers.',
            'beneficiary' => 'Registered Fireblocks crypto beneficiary linked via transaction request or destination wallet. Click the link to open beneficiary details when resolved.',
            'external_tx_id' => 'Reference sent to Fireblocks (often the portal transaction request id). Used with crypto_transaction_id to link ledger records.',
            'chainalysis' => 'Chainalysis KYT/AML screening result for this transfer. Separate from boost/drop/resubmit monitoring workflow.',
            'queue_status' => 'State of automated monitoring actions (e.g. queued, pending operator approval, completed).',
            'ledger_status' => 'Posting status on account_transaction_requests / account_transactions (Pending, Approved, Rejected, Failed). Independent of Fireblocks Internal status.',
            'account' => 'Client ledger account (title and number) from the linked transaction request when external_tx_id or crypto_transaction_id matches.',
            'stuck_for' => 'Time since this transaction was flagged as stuck (from stuck_detected_at).',
            'reattempts' => 'Number of automated monitoring attempts (boost, drop, CPFP, resubmit) recorded for this Fireblocks transaction id.',
            'last_reattempt' => 'Timestamp of the most recent automated monitoring action attempt.',
            'pending_action' => 'Action Fireblocks reports as pending on the transaction, from the last webhook or poll.',
            'last_webhook' => 'When the portal last processed a Fireblocks webhook for this transaction.',
            'last_polled' => 'When the monitoring job last called the Fireblocks API to refresh this transaction.',
            'parent_tx' => 'Immediate parent Fireblocks transaction id (e.g. transaction this resubmit or replacement was created from).',
            'root_tx' => 'First transaction id in a resubmit / replacement chain.',
            'replacement_tx' => 'Fireblocks id of the transaction that replaced this one (RBF, drop, or resubmit).',
            'resolution' => 'Monitoring module summary of how this case was classified or resolved for operators.',
            'alert_time' => 'When the monitoring alert was created.',
            'alert_type' => 'Alert category (e.g. stuck detected, boost failed).',
            'alert_status' => 'Whether the alert is open, acknowledged, or cleared.',
            'alert_message' => 'Human-readable alert detail from the monitoring module.',
            'log_time' => 'When the monitoring action was recorded.',
            'log_action' => 'Action type (sync, boost, drop, chainalysis, resubmit, etc.).',
            'log_source' => 'What triggered the action (webhook, polling, manual operator).',
            'log_success' => 'Whether the Fireblocks or integration API call succeeded.',
            'log_detail' => 'Error message or expandable request/response payload for compliance actions.',
        ];
    }

    public static function get(string $key): string
    {
        return self::all()[$key] ?? '';
    }
}
