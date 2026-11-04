@extends('layouts.cp')
@section('title', 'Sweep Job #' . $job->id)

@section('styles')
@include('fireblocks-sweeping::partials.stylesheets', ['sheets' => ['sweep-datagrid.css']])
@endsection

@section('content')
@php
    $historyService = $runHistory ?? app(\App\Modules\FireblocksVaultSweeping\Services\SweepRuleRunHistoryService::class);
@endphp

<div class="subheader">
    <h1 class="subheader-title">
        <span class="fw-300">Vault Sweeping</span> <i class="fal fa-angle-right"></i>
        Job #{{ $job->id }}
    </h1>
</div>

@include('fireblocks-sweeping::partials.module-nav', ['sweepNavActive' => 'dashboard'])

<div class="panel mb-3">
    <div class="panel-hdr"><h2>Job Details</h2></div>
    <div class="panel-container show">
        <div class="panel-content">
            <table class="table table-sm">
                <tr>
                    <th>Job status</th>
                    <td><span class="badge {{ $historyService->statusBadgeClass($job->status) }}">{{ $historyService->statusLabel($job->status) }}</span></td>
                </tr>
                <tr><th>Created</th><td>{{ $job->created_at?->format('Y-m-d H:i:s') ?? '—' }}</td></tr>
                <tr><th>Updated</th><td>{{ $job->updated_at?->format('Y-m-d H:i:s') ?? '—' }}</td></tr>
                <tr>
                    <th>Fireblocks status</th>
                    <td>{{ $historyService->formatFireblocksStatus($job) }}</td>
                </tr>
                <tr>
                    <th>Status last updated</th>
                    <td>{{ $historyService->formatStatusUpdatedAt($job) ?? '—' }}</td>
                </tr>
                @if ($job->completed_at)
                <tr><th>Completed at</th><td>{{ $job->completed_at->format('Y-m-d H:i:s') }}</td></tr>
                @endif
                @if ($job->failed_at)
                <tr><th>Failed at</th><td>{{ $job->failed_at->format('Y-m-d H:i:s') }}</td></tr>
                @endif
                <tr><th>Rule</th><td>
                    @if ($job->rule)
                        @if ($job->rule->trashed())
                            {{ $job->rule->name }}
                            <span class="badge badge-secondary ml-1">Archived</span>
                        @else
                            <a href="{{ route('fireblocks.vault-sweeping.rules.show', $job->rule) }}">{{ $job->rule->name }}</a>
                        @endif
                    @else
                        —
                    @endif
                </td></tr>
                <tr><th>Source Vault</th><td>{{ $historyService->vaultLabel((string) $job->source_vault_id) }}</td></tr>
                <tr><th>Destination Vault</th><td>{{ $historyService->vaultLabel((string) $job->destination_vault_id) }}</td></tr>
                <tr><th>Asset</th><td><code>{{ $job->asset_id }}</code></td></tr>
                <tr><th>Amount</th><td>{{ $job->amount ?? '—' }}</td></tr>
                <tr><th>Trigger</th><td>{{ $job->trigger_source }}</td></tr>
                <tr><th>Deposit Tx</th><td>{{ $job->deposit_fireblocks_tx_id ?? '—' }}</td></tr>
                <tr><th>Fireblocks Tx</th><td>{{ $job->fireblocks_tx_id ?? '—' }}</td></tr>
                @if ($job->gas_asset_id)
                @php $gasAsset = $job->gas_asset_id; @endphp
                <tr><th>Gas asset</th><td><code>{{ $gasAsset }}</code></td></tr>
                <tr><th>{{ $gasAsset }} required</th><td>{{ $job->gas_required_amount ?? '—' }}</td></tr>
                <tr><th>{{ $gasAsset }} feed amount</th><td>{{ $job->gas_feed_amount ?? '—' }}</td></tr>
                <tr><th>Gas funding vault</th><td>{{ $job->gas_funding_vault_id ? $historyService->vaultLabel((string) $job->gas_funding_vault_id) : '—' }}</td></tr>
                <tr><th>Gas feed tx</th><td>{{ $job->gas_feed_fireblocks_tx_id ?? '—' }}</td></tr>
                @endif
                <tr><th>Planned Sweep Amount</th><td>{{ $job->planned_sweep_amount ?? '—' }}</td></tr>
                <tr><th>External Tx ID</th><td>{{ $job->external_tx_id ?? '—' }}</td></tr>
                <tr><th>Skip Reason</th><td>{{ $job->skip_reason ?? '—' }}</td></tr>
            </table>

            @if (!empty($recoveryActions))
            <div class="alert alert-light border mb-3">
                <strong class="d-block mb-2">Recovery actions</strong>
                <p class="small text-muted mb-2">
                    Use when gas arrived late, the job failed during wait, or queue workers missed a step.
                    <strong>Reconcile</strong> polls Fireblocks (gas or token leg).
                    <strong>Requeue</strong> dispatches the async worker.
                    <strong>Execute</strong> runs the sweep immediately (resume after gas when applicable).
                </p>
                {!! app(\App\Modules\FireblocksVaultSweeping\Services\SweepJobRecoveryService::class)->renderActionButtons($job, 'btn-sm') !!}
            </div>
            @endif

            @if ($job->rule && !$job->rule->trashed())
                <a href="{{ route('fireblocks.vault-sweeping.rules.show', $job->rule) }}" class="btn btn-outline-primary">Rule detail</a>
                <a href="{{ route('fireblocks.vault-sweeping.rules.edit', $job->rule) }}" class="btn btn-outline-secondary">Edit rule</a>
            @elseif ($job->rule)
                <span class="text-muted small">Rule archived — no longer editable</span>
            @endif
        </div>
    </div>
</div>

<div class="panel mb-3">
    <div class="panel-hdr"><h2>Execution timeline</h2></div>
    <div class="panel-container show">
        <div class="panel-content">
            <div class="sweep-datagrid-wrap">
                <table class="table table-sm mb-0 sweep-datagrid w-100" id="sweep-job-timeline-table">
                    <thead>
                        <tr>
                            <th>Date & time</th>
                            <th>Event</th>
                            <th class="sweep-cell-wrap">Details</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="panel">
    <div class="panel-hdr"><h2>Audit Log</h2></div>
    <div class="panel-container show">
        <div class="panel-content">
            <div class="sweep-datagrid-wrap">
                <table class="table table-sm sweep-datagrid w-100" id="sweep-job-audit-table">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Event</th>
                            <th>Level</th>
                            <th class="sweep-cell-wrap">Message</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
@include('fireblocks-sweeping::partials.datagrid-init')
<script>
$(function () {
    var logsBaseUrl = @json(route('fireblocks.vault-sweeping.jobs.logs.data', $job));

    $('#sweep-job-timeline-table').DataTable(SweepDatagrid.defaults({
        serverSide: true,
        ajax: { url: logsBaseUrl, data: function (d) { d.scope = 'timeline'; } },
        order: [[0, 'desc']],
        columns: [
            { data: 'created_at_fmt', name: 'created_at' },
            { data: 'event_code', name: 'event' },
            { data: 'message_fmt', name: 'message', orderable: false, className: 'sweep-cell-wrap' },
        ],
    }));

    $('#sweep-job-audit-table').DataTable(SweepDatagrid.defaults({
        serverSide: true,
        ajax: logsBaseUrl,
        order: [[0, 'desc']],
        columns: [
            { data: 'created_at_fmt', name: 'created_at' },
            { data: 'event_code', name: 'event' },
            { data: 'level_label', name: 'level' },
            { data: 'message_fmt', name: 'message', orderable: false, className: 'sweep-cell-wrap' },
        ],
    }));
});
</script>
@endsection
