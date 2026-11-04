<?php

namespace App\Modules\FireblocksTransactionsMonitoring\Support;

/**
 * Tooltip help text for the transaction monitoring dashboard (filters, KPIs, charts).
 */
final class MonitoringDashboardUiHints
{
    public static function all(): array
    {
        return [
            'summary_stuck' => 'Transactions flagged by stuck detection (monitoring_state = stuck). Count uses current transaction scope and other active filters when you change filters; card click sets Monitoring State to Stuck.',
            'summary_action_pending' => 'Automated or queued recovery actions waiting for operator approval or completion (monitoring_state = action_pending).',
            'summary_open_alerts' => 'Open rows in fireblocks_transaction_alerts (including Fireblocks transaction.alert.stuck / detector alerts). Not narrowed by grid filters.',
            'summary_pending_fb' => 'Custody rows whose Fireblocks primary status is still in-flight (e.g. CONFIRMING, BROADCASTING, PENDING_SIGNATURE). Respects transaction scope.',
            'summary_completed' => 'Fireblocks COMPLETED / SUCCESS or internal Approved. Respects transaction scope.',
            'summary_orphan' => 'Global count of custody-only deposits not linked to client ledger (unknown deposit address, etc.). Click applies Orphan transaction scope.',

            'analytics_panel' => 'Charts reload when you change filters or click Refresh. They use the same filter parameters as the grid below (scope, direction, FB status, monitoring state).',
            'chart_flow_custody' => 'Donut of direction and orphan mix for the current filter set. Incoming / Outgoing / Internal exclude orphan notes; Orphan slice counts custody-only rows when scope is All.',
            'chart_scope_total' => 'Total fireblocks_transactions rows matching current filters and transaction scope (same basis as the grid).',
            'chart_ops_pipeline' => 'Operational mix: stuck, action pending, in-progress Fireblocks primaries, and completed — within current scope and filters.',
            'chart_stuck_age' => 'Age buckets for rows currently in monitoring_state = stuck (from stuck_detected_at). Respects scope and filters except Monitoring State (always stuck here).',
            'chart_actions_7d' => 'Operator Boost, Drop, Resubmit, and Sync actions recorded in monitoring audit logs over the last 7 days (global, not filter-scoped).',

            'filter_monitoring_state' => 'Portal workflow state from stuck detection and recovery (stuck, action_pending, normal, boosted, etc.). Independent of Fireblocks primary status.',
            'filter_fb_status' => 'Fireblocks primary lifecycle status on the custody record. Click the icon to open the full status catalog.',
            'filter_fb_sub_status' => 'Fireblocks sub-status (failure reason or confirming phase). Click the icon to search the sub-status reference.',
            'filter_internal_status' => 'Portal rollup of primary + sub-status (Pending, Approved, Rejected, etc.) stored on fireblocks_transactions.internal_status. See Internal Mapping in Understand statuses.',
            'filter_direction' => 'All includes Fireblocks incoming / outgoing / internal plus Aquanow Buy and Sell when that option is enabled. Buy / Sell alone show only Aquanow orders (Buy ≈ incoming, Sell ≈ outgoing).',
            'filter_ledger_scope' => 'Which custody rows appear: ledger-linked with client account (default), all, orphan custody only, or Small incomings (small credits to registered deposit addresses; see module config thresholds).',
            'filter_stuck_only' => 'Shortcut: same as setting Monitoring State to Stuck — shows only monitoring_state = stuck.',
            'filter_reset' => 'Restore module default filters and clear saved session filter choices in this browser.',

            'grid_panel' => 'Server-side grid of fireblocks_transactions with detail modal, Sync, Boost, Drop, and Resubmit when eligible.',
            'grid_col_id' => 'TR request id (account_transaction_requests.id) linked to this custody or Aquanow row. Sorted by this id. Dash means no linked TR request yet.',
        ];
    }
}
