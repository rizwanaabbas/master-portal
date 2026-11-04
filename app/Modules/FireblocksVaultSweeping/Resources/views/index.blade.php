@extends('layouts.cp')
@section('title', 'Fireblocks Vault Sweeping')

@section('styles')
@include('fireblocks-sweeping::partials.stylesheets', ['sheets' => [
    'sweep-datagrid.css',
    'sweep-dashboard.css',
]])
@endsection

@section('content')
@php
    $inPipeline = ($summary['pending'] ?? 0) + ($summary['waiting_for_gas'] ?? 0) + ($summary['submitted'] ?? 0);
    $totalJobs = $inPipeline
        + ($summary['completed'] ?? 0)
        + ($summary['skipped'] ?? 0)
        + ($summary['failed'] ?? 0);
    $statusCards = [
        ['key' => '', 'label' => 'All jobs', 'icon' => 'fa-layer-group', 'accent' => '#475569', 'soft' => '#f8fafc'],
        ['key' => 'pending', 'label' => 'Pending', 'icon' => 'fa-clock', 'accent' => '#2563eb', 'soft' => '#eff6ff'],
        ['key' => 'waiting_for_gas', 'label' => 'Waiting for gas', 'icon' => 'fa-gas-pump', 'accent' => '#0891b2', 'soft' => '#ecfeff'],
        ['key' => 'submitted', 'label' => 'Submitted', 'icon' => 'fa-paper-plane', 'accent' => '#7c3aed', 'soft' => '#f5f3ff'],
        ['key' => 'completed', 'label' => 'Completed', 'icon' => 'fa-check-circle', 'accent' => '#16a34a', 'soft' => '#f0fdf4'],
        ['key' => 'skipped', 'label' => 'Skipped', 'icon' => 'fa-forward', 'accent' => '#64748b', 'soft' => '#f8fafc'],
        ['key' => 'failed', 'label' => 'Failed', 'icon' => 'fa-times-circle', 'accent' => '#dc2626', 'soft' => '#fef2f2'],
    ];
@endphp

<div class="subheader">
    <h1 class="subheader-title">
        <span class="fw-300">Crypto Management</span> <i class="fal fa-angle-right"></i>
        <span class="fw-300">Fireblocks</span> <i class="fal fa-angle-right"></i>
        Vault Sweeping
    </h1>
</div>

@include('fireblocks-sweeping::partials.module-nav', ['sweepNavActive' => 'dashboard'])

<div class="sweep-dash-intro">
    <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
        <div>
            <h2><i class="fal fa-broom mr-2"></i>Vault sweep operations</h2>
            <p>Monitor sweep jobs across deposit webhooks, scheduled runs, and manual rule execution.</p>
            @if ($config)
                <span class="sweep-dash-pill">
                    <i class="fal fa-shield-check"></i>
                    Dry run: {{ !empty($config['dry_run_mode']) ? 'ON' : 'OFF' }}
                </span>
                <span class="sweep-dash-pill">
                    <i class="fal fa-warehouse"></i>
                    Omnibus: {{ $config['default_omnibus_vault_id'] ?: 'not set' }}
                </span>
                @if (!empty($config['dry_run_mode']))
                    <span class="sweep-dash-pill is-warn">
                        <i class="fal fa-flask"></i> Global dry run active — no live Fireblocks submissions
                    </span>
                @endif
            @endif
        </div>
       
    </div>
</div>

@include('common.error-message')
@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="sweep-dash-metrics">
    <div class="sweep-dash-metric">
        <div class="sweep-dash-metric-label">Total jobs tracked</div>
        <div class="sweep-dash-metric-value">{{ number_format($totalJobs) }}</div>
        <div class="sweep-dash-metric-sub">All statuses in the system</div>
    </div>
    <div class="sweep-dash-metric">
        <div class="sweep-dash-metric-label">In pipeline</div>
        <div class="sweep-dash-metric-value text-primary">{{ number_format($inPipeline) }}</div>
        <div class="sweep-dash-metric-sub">Pending, gas, or submitted</div>
    </div>
    <div class="sweep-dash-metric">
        <div class="sweep-dash-metric-label">Completed</div>
        <div class="sweep-dash-metric-value text-success">{{ number_format($summary['completed'] ?? 0) }}</div>
        <div class="sweep-dash-metric-sub">Successfully swept</div>
    </div>
    <div class="sweep-dash-metric">
        <div class="sweep-dash-metric-label">Active rules</div>
        <div class="sweep-dash-metric-value">{{ number_format($summary['rules'] ?? 0) }}</div>
        <div class="sweep-dash-metric-sub">Enabled sweep rules</div>
    </div>
</div>

<p class="sweep-dash-filter-hint mb-2">
    <i class="fal fa-filter mr-1"></i> Click a status card to filter the jobs grid.
    <strong id="sweep-active-filter-label">Showing all jobs</strong>
</p>

<div class="sweep-dash-status-grid mb-3">
    @foreach ($statusCards as $card)
        @php
            $count = $card['key'] === ''
                ? $totalJobs
                : ($summary[$card['key']] ?? 0);
        @endphp
        <div class="sweep-dash-status-card js-sweep-status-filter {{ $card['key'] === '' ? 'is-active' : '' }}"
            data-status="{{ $card['key'] }}"
            style="--sweep-accent: {{ $card['accent'] }}; --sweep-accent-soft: {{ $card['soft'] }};">
            <span class="sweep-dash-status-icon"><i class="fal {{ $card['icon'] }}"></i></span>
            <div class="sweep-dash-status-label">{{ $card['label'] }}</div>
            <div class="sweep-dash-status-value">{{ number_format($count) }}</div>
        </div>
    @endforeach
</div>

<div class="panel">
    <div class="panel-hdr">
        <h2>Sweep Jobs</h2>
        <div class="panel-toolbar">
            <button type="button" class="btn btn-sm btn-outline-secondary" id="sweep-clear-status-filter">
                <i class="fal fa-times mr-1"></i> Clear filter
            </button>
        </div>
    </div>
    <div class="panel-container show">
        <div class="panel-content">
            <div class="sweep-datagrid-wrap">
                <table id="sweep-jobs-table" class="table table-bordered table-striped sweep-datagrid w-100">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Rule ID</th>
                            <th>Rule</th>
                            <th>Source</th>
                            <th>Destination</th>
                            <th>Asset</th>
                            <th>Amount</th>
                            <th>Trigger</th>
                            <th>Status</th>
                            <th>FB status</th>
                            <th>Status updated</th>
                            <th>FB Tx</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
@include('fireblocks-sweeping::partials.confirm-modal')
@include('fireblocks-sweeping::partials.datagrid-init')
<script>
$(function () {
    var activeStatus = '';
    var statusLabels = @json(collect($statusCards)->pluck('label', 'key'));

    var table = $('#sweep-jobs-table').DataTable(SweepDatagrid.defaults({
        serverSide: true,
        ajax: {
            url: '{{ route('fireblocks.vault-sweeping.data') }}',
            data: function (d) {
                d.status = activeStatus;
            },
        },
        columns: [
            { data: 'id' },
            { data: 'rule_id' },
            { data: 'rule_name', orderable: false },
            { data: 'source_vault_id' },
            { data: 'destination_vault_id' },
            { data: 'asset_id' },
            { data: 'amount' },
            { data: 'trigger_source' },
            { data: 'status' },
            { data: 'fireblocks_status_display', name: 'fireblocks_status', defaultContent: '—', orderable: false },
            { data: 'fireblocks_status_updated_at', defaultContent: '—' },
            { data: 'fireblocks_tx_id', defaultContent: '—', orderable: false },
            { data: 'created_at' },
            { data: 'actions', orderable: false, searchable: false },
        ],
        order: [[0, 'desc']],
    }));

    function updateFilterLabel() {
        var label = statusLabels[activeStatus] || 'All jobs';
        $('#sweep-active-filter-label').text(activeStatus === '' ? 'Showing all jobs' : ('Filtered by: ' + label));
    }

    $('.js-sweep-status-filter').on('click', function () {
        activeStatus = String($(this).data('status') || '');
        $('.js-sweep-status-filter').removeClass('is-active');
        $(this).addClass('is-active');
        updateFilterLabel();
        table.ajax.reload();
    });

    $('#sweep-clear-status-filter').on('click', function () {
        activeStatus = '';
        $('.js-sweep-status-filter').removeClass('is-active');
        $('.js-sweep-status-filter[data-status=""]').addClass('is-active');
        updateFilterLabel();
        table.ajax.reload();
    });
});
</script>
@endsection
