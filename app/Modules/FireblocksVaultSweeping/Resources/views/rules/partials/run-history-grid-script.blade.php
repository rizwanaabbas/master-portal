@if (!empty($rule->exists))
@php
    $historyService = $runHistoryService ?? app(\App\Modules\FireblocksVaultSweeping\Services\SweepRuleRunHistoryService::class);
    $summary = $runHistorySummary ?? ($runHistory['summary'] ?? $historyService->summaryForRule($rule));
@endphp
@if (($summary['total'] ?? 0) > 0)
<script>
$(function () {
    $('#sweep-rule-jobs-table').DataTable(SweepDatagrid.defaults({
        serverSide: true,
        ajax: @json(route('fireblocks.vault-sweeping.rules.jobs.data', $rule)),
        order: [[0, 'desc']],
        columns: [
            { data: 'created_at_fmt', name: 'created_at' },
            { data: 'trigger_badge', name: 'trigger_source', orderable: true },
            { data: 'deposit_tx', name: 'deposit_fireblocks_tx_id', orderable: false },
            { data: 'source_vault', name: 'source_vault_id' },
            { data: 'asset_code', name: 'asset_id' },
            { data: 'status_badge', name: 'status' },
            { data: 'fireblocks_status_fmt', name: 'fireblocks_status', orderable: false },
            { data: 'status_updated_fmt', name: 'fireblocks_status_updated_at' },
            { data: 'amount_fmt', name: 'amount' },
            { data: 'details', orderable: false, searchable: false, className: 'sweep-cell-wrap' },
            { data: 'view_link', orderable: false, searchable: false },
        ],
    }));
});
</script>
@endif
@endif
