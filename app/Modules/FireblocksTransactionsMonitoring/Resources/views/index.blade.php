@extends('layouts.cp')
@section('title', 'Fireblocks Transaction Monitoring')

@section('content')
@php
    $uiHints = $uiHints ?? \App\Modules\FireblocksTransactionsMonitoring\Support\MonitoringDashboardUiHints::all();
@endphp
@include('fireblocks-monitoring::partials.status-badge-styles')
<style>
    .fb-summary-card { cursor: pointer; transition: transform .15s ease; }
    .fb-summary-card:hover { transform: translateY(-2px); }
    .fb-row-stuck { background-color: rgba(220, 53, 69, 0.08) !important; }
    .fb-row-action-pending { background-color: rgba(255, 193, 7, 0.12) !important; }
    .fb-chart-box { min-height: 150px; position: relative; max-height: 170px; }
    .fb-chart-card { border: 1px solid rgba(0,0,0,.08); border-radius: .5rem; background: #fff; }
    .fb-chart-card .fb-chart-title { font-size: .8rem; font-weight: 600; margin-bottom: 0; }
    .fb-chart-card .fb-chart-meta { font-size: .65rem; color: #6c757d; line-height: 1.25; }
    .fb-chart-center-total {
        position: absolute; left: 50%; top: 52%; transform: translate(-50%, -50%);
        text-align: center; pointer-events: none; line-height: 1.1;
    }
    .fb-chart-center-total strong { font-size: 1.35rem; display: block; color: #2c3e50; }
    .fb-chart-center-total span { font-size: .7rem; color: #6c757d; text-transform: uppercase; letter-spacing: .04em; }
    .fb-summary-card .fb-detail-field-hint.text-white-50:hover,
    .fb-summary-card .fb-detail-field-hint.text-white-50:focus { color: #fff !important; }
    .panel-hdr .fb-detail-field-hint { font-size: 0.75rem; vertical-align: middle; }
    #monitoring-grid_wrapper .dataTables_filter {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        flex-wrap: wrap;
        gap: .5rem;
    }
    #monitoring-grid_wrapper .dataTables_filter label {
        margin-bottom: 0;
    }
    .fb-filters {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(230px, 1fr));
        gap: 1rem;
        align-items: end;
        margin-bottom: 1rem;
    }
    .fb-filters > * {
        margin: 0;
        min-width: 0;
        width: 100%;
    }
    .fb-filters .form-label {
        display: block;
        margin-bottom: .4rem;
        font-size: .75rem;
        font-weight: 600;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .fb-filters .fb-filter-actions {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 1rem;
        min-height: 31px;
    }
    .fb-filters .fb-filter-actions .custom-control {
        margin-bottom: 0;
        padding-top: .15rem;
    }
    .fb-filters .select2-container {
        width: 100% !important;
    }
    .fb-filters .select2-container--default .select2-selection--single {
        height: 31px;
        display: flex;
        align-items: center;
    }
    .fb-filters .select2-container--default .select2-selection--single .select2-selection__rendered {
        white-space: nowrap !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
        word-break: keep-all;
        line-height: 29px;
        padding-right: 20px;
    }
    .select2-container.fb-filter-dropdown .select2-results__option {
        white-space: nowrap;
    }
</style>

<div class="subheader">
    <h1 class="subheader-title">
        <span class="fw-300">Crypto Management</span> <i class="fal fa-angle-right"></i>
        <span class="fw-300">Fireblocks</span> <i class="fal fa-angle-right"></i>
        Transaction Monitoring
    </h1>
</div>

<div id="fb-monitoring-dashboard">
<div class="row mb-3">
    <div class="col-md col-6 col-lg mb-2">
        <div class="p-3 bg-danger-100 rounded border-left-lg border-danger fb-summary-card h-100" data-filter-monitoring-state="stuck">
            <div class="text-white fw-bold">Stuck @include('fireblocks-monitoring::partials.dashboard-ui-hint', ['hint' => $uiHints['summary_stuck'] ?? '', 'light' => true])</div>
            <div class="text-white font-semi-bold h2 mb-0" id="summary-stuck">{{ $summary['stuck'] }}</div>
        </div>
    </div>
    <div class="col-md col-6 col-lg mb-2">
        <div class="p-3 bg-warning-100 rounded border-left-lg border-warning fb-summary-card h-100" data-filter-monitoring-state="action_pending">
            <div class="text-white fw-bold">Action Pending @include('fireblocks-monitoring::partials.dashboard-ui-hint', ['hint' => $uiHints['summary_action_pending'] ?? '', 'light' => true])</div>
            <div class="text-white font-semi-bold h2 mb-0" id="summary-action-pending">{{ $summary['action_pending'] }}</div>
        </div>
    </div>
    <div class="col-md col-6 col-lg mb-2">
        <div class="p-3 bg-info-100 rounded border-left-lg border-info fb-summary-card h-100" data-filter-open-alerts="1">
            <div class="text-white fw-bold">Open ATC Alerts @include('fireblocks-monitoring::partials.dashboard-ui-hint', ['hint' => $uiHints['summary_open_alerts'] ?? '', 'light' => true])</div>
            <div class="text-white font-semi-bold h2 mb-0" id="summary-open-alerts">{{ $summary['open_alerts'] }}</div>
        </div>
    </div>
    <div class="col-md col-6 col-lg mb-2">
        <div class="p-3 bg-primary-100 rounded border-left-lg border-primary fb-summary-card h-100" data-filter-fb-status="CONFIRMING">
            <div class="text-white fw-bold">Pending @include('fireblocks-monitoring::partials.dashboard-ui-hint', ['hint' => $uiHints['summary_pending_fb'] ?? '', 'light' => true])</div>
            <div class="text-white font-semi-bold h2 mb-0" id="summary-pending-fb">{{ $summary['pending_fb'] }}</div>
        </div>
    </div>
    <div class="col-md col-6 col-lg mb-2">
        <div class="p-3 bg-success-100 rounded border-left-lg border-success fb-summary-card h-100" data-filter-fb-status="COMPLETED">
            <div class="text-white fw-bold">Completed @include('fireblocks-monitoring::partials.dashboard-ui-hint', ['hint' => $uiHints['summary_completed'] ?? '', 'light' => true])</div>
            <div class="text-white font-semi-bold h2 mb-0" id="summary-completed">{{ $summary['completed'] ?? 0 }}</div>
        </div>
    </div>
    <div class="col-md col-6 col-lg mb-2">
        <div class="p-3 rounded border-left-lg border-secondary fb-summary-card h-100" style="background: linear-gradient(135deg, #5a6268 0%, #343a40 100%);" data-filter-show-orphans="1">
            <div class="text-white fw-bold">Orphan Transactions @include('fireblocks-monitoring::partials.dashboard-ui-hint', ['hint' => $uiHints['summary_orphan'] ?? '', 'light' => true])</div>
            <div class="text-white font-semi-bold h2 mb-0" id="summary-orphan">{{ $summary['orphan'] ?? 0 }}</div>
            <small class="text-white-50">Not in ledger</small>
        </div>
    </div>
</div>

<div class="panel mb-3">
    <div class="panel-hdr">
        <h2>Analytics @include('fireblocks-monitoring::partials.dashboard-ui-hint', ['hint' => $uiHints['analytics_panel'] ?? ''])</h2>
        <div class="panel-toolbar">
            <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-refresh-charts"><i class="fal fa-sync"></i> Refresh</button>
        </div>
    </div>
    <div class="panel-container show">
        <div class="panel-content">
            <div class="row">
                <div class="col-lg-3 col-md-6 mb-2">
                    <div class="fb-chart-card p-2 h-100">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <div>
                                <div class="fb-chart-title">Flow &amp; custody @include('fireblocks-monitoring::partials.dashboard-ui-hint', ['hint' => $uiHints['chart_flow_custody'] ?? ''])</div>
                                <div class="fb-chart-meta">Incoming · Outgoing · Internal · Orphan</div>
                            </div>
                            <div class="d-flex align-items-center flex-shrink-0">
                                <span class="badge badge-light badge-sm mr-1" id="chart-scope-total" style="font-size:.65rem;">—</span>
                                @include('fireblocks-monitoring::partials.dashboard-ui-hint', ['hint' => $uiHints['chart_scope_total'] ?? ''])
                            </div>
                        </div>
                        <div class="fb-chart-box">
                            <canvas id="chart-direction-orphan" height="130"></canvas>
                            <div class="fb-chart-center-total d-none" id="chart-direction-orphan-center">
                                <strong id="chart-direction-orphan-total">0</strong>
                                <span>Total</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-2">
                    <div class="fb-chart-card p-2 h-100">
                        <div class="mb-1">
                            <div class="fb-chart-title">Ops pipeline @include('fireblocks-monitoring::partials.dashboard-ui-hint', ['hint' => $uiHints['chart_ops_pipeline'] ?? ''])</div>
                            <div class="fb-chart-meta">Stuck · Action pending · In progress · Done</div>
                        </div>
                        <div class="fb-chart-box">
                            <canvas id="chart-pipeline" height="130"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-2">
                    <div class="fb-chart-card p-2 h-100">
                        <div class="mb-1">
                            <div class="fb-chart-title">Stuck queue age @include('fireblocks-monitoring::partials.dashboard-ui-hint', ['hint' => $uiHints['chart_stuck_age'] ?? ''])</div>
                            <div class="fb-chart-meta">Time in stuck monitoring state</div>
                        </div>
                        <div class="fb-chart-box">
                            <canvas id="chart-stuck-age" height="130"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-2">
                    <div class="fb-chart-card p-2 h-100">
                        <div class="mb-1">
                            <div class="fb-chart-title">Actions (7d) @include('fireblocks-monitoring::partials.dashboard-ui-hint', ['hint' => $uiHints['chart_actions_7d'] ?? ''])</div>
                            <div class="fb-chart-meta">Boost · Drop · Resubmit · Sync</div>
                        </div>
                        <div class="fb-chart-box">
                            <canvas id="chart-actions-7d" height="130"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="panel">
    <div class="panel-hdr">
        <h2>Monitored Transactions @include('fireblocks-monitoring::partials.dashboard-ui-hint', ['hint' => $uiHints['grid_panel'] ?? ''])</h2>
        <div class="panel-toolbar d-flex flex-wrap align-items-center" style="gap:.35rem;">
            @if (!empty($showCurrencyRates))
                <button type="button" class="btn btn-sm btn-outline-success mr-1" id="btn-currency-rates"><i class="fal fa-dollar-sign"></i> Currency Rates</button>
            @endif
            @if (!empty($showFbBalances))
                <button type="button" class="btn btn-sm btn-outline-dark mr-1" id="btn-fb-balances"><i class="fal fa-wallet"></i> FB Balances</button>
            @endif
            @if (!empty($showAquanowBalances))
                <button type="button" class="btn btn-sm btn-outline-dark mr-1" id="btn-aquanow-balances"><i class="fal fa-university"></i> AquaNow Balances</button>
            @endif
            <button type="button" class="btn btn-sm btn-outline-info mr-1" id="btn-understand-statuses"><i class="fal fa-book"></i> Understand statuses</button>
            <a href="{{ route('fireblocks.transaction-monitoring.manual') }}" class="btn btn-sm btn-outline-secondary mr-1" target="_blank"><i class="fal fa-file-alt"></i> Ops manual</a>
            <button type="button" class="btn btn-sm btn-outline-primary" id="btn-refresh-grid"><i class="fal fa-sync"></i> Refresh</button>
        </div>
    </div>
    <div class="panel-container show">
        <div class="panel-content">
            @include('common.error-message')

            <div class="fb-filters">
                <div>
                    <label class="form-label">Monitoring State @include('fireblocks-monitoring::partials.dashboard-ui-hint', ['hint' => $uiHints['filter_monitoring_state'] ?? ''])</label>
                    <select id="filter-monitoring-state" class="form-control form-control-sm fb-filter-select w-100">
                        <option value="all">All</option>
                        <option value="stuck">Stuck</option>
                        <option value="action_pending">Action Pending</option>
                        <option value="boosted">Boosted</option>
                        <option value="dropped">Dropped</option>
                        <option value="resubmitted">Resubmitted</option>
                        <option value="normal">Normal</option>
                    </select>
                </div>
                @if (!empty($showAquanowBuySell))
                <div>
                    <label class="form-label">Provider</label>
                    <select id="filter-provider" class="form-control form-control-sm fb-filter-select w-100">
                        <option value="all">All</option>
                        <option value="fireblocks">Fireblocks</option>
                        <option value="aquanow">Aquanow</option>
                    </select>
                </div>
                @endif
                <div>
                    <label class="form-label">Status
                        @include('fireblocks-monitoring::partials.dashboard-ui-hint', ['hint' => $uiHints['filter_fb_status'] ?? ''])
                        <a href="#" class="fb-open-status-ref text-muted ml-1" data-tab="primary" title="Open status catalog"><i class="fal fa-book"></i></a>
                    </label>
                    <select id="filter-fb-status" class="form-control form-control-sm fb-filter-select w-100">
                        <option value="all">All</option>
                        @foreach ($primaryStatusFilterOptions as $code => $label)
                            <option value="{{ $code }}">{{ $label }} ({{ $code }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Sub-status
                        @include('fireblocks-monitoring::partials.dashboard-ui-hint', ['hint' => $uiHints['filter_fb_sub_status'] ?? ''])
                        <a href="#" class="fb-open-status-ref text-muted ml-1" data-tab="substatus" title="Open sub-status catalog"><i class="fal fa-book"></i></a>
                    </label>
                    <select id="filter-fb-sub-status" class="form-control form-control-sm fb-filter-select w-100">
                        <option value="all">All</option>
                        @foreach ($substatusFilterOptions as $group => $items)
                            <optgroup label="{{ $group }}">
                                @foreach ($items as $code => $message)
                                    <option value="{{ $code }}">{{ $code }} — {{ Str::limit($message, 60) }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Direction @include('fireblocks-monitoring::partials.dashboard-ui-hint', ['hint' => $uiHints['filter_direction'] ?? ''])</label>
                    <select id="filter-direction" class="form-control form-control-sm fb-filter-select w-100">
                        <option value="all">All</option>
                        <option value="outgoing">Outgoing</option>
                        <option value="incoming">Incoming</option>
                        <option value="internal">Internal</option>
                        @if (!empty($showAquanowBuySell))
                            <option value="buy">Buy</option>
                            <option value="sell">Sell</option>
                        @endif
                    </select>
                </div>
                <div>
                    <label class="form-label">Internal Status
                        @include('fireblocks-monitoring::partials.dashboard-ui-hint', ['hint' => $uiHints['filter_internal_status'] ?? ''])
                        <a href="#" class="fb-open-status-ref text-muted ml-1" data-tab="internal" title="Open internal status mapping"><i class="fal fa-book"></i></a>
                    </label>
                    <select id="filter-internal-status" class="form-control form-control-sm fb-filter-select w-100">
                        <option value="all">All</option>
                        @foreach ($internalStatusFilterOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Transaction scope @include('fireblocks-monitoring::partials.dashboard-ui-hint', ['hint' => $uiHints['filter_ledger_scope'] ?? ''])</label>
                    <select id="filter-ledger-scope" class="form-control form-control-sm fb-filter-select w-100">
                        <option value="ledger">Ledger-linked (has account)</option>
                        <option value="all">All transactions</option>
                        <option value="orphan">Orphan custody only</option>
                        <option value="poisoning">Small incomings</option>
                    </select>
                </div>
                <div class="fb-filter-actions">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="filter-stuck-only">
                        <label class="custom-control-label" for="filter-stuck-only">Stuck only @include('fireblocks-monitoring::partials.dashboard-ui-hint', ['hint' => $uiHints['filter_stuck_only'] ?? ''])</label>
                    </div>
                    <span class="d-inline-flex align-items-center">
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-reset-filters">
                            <i class="fal fa-undo"></i> Reset filters
                        </button>
                        @include('fireblocks-monitoring::partials.dashboard-ui-hint', ['hint' => $uiHints['filter_reset'] ?? ''])
                    </span>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-12">
                    <small class="text-muted">Default scope shows ledger-linked rows with a resolved client account. Orphan = custody-only, not in ledger. Small incomings = small credits to registered deposit addresses (bot dust); thresholds in module config. If the grid is empty, try <strong>Transaction scope → All transactions</strong>, <strong>Direction → All</strong>, or <strong>Reset filters</strong>. <a href="#" id="link-understand-statuses">Understand statuses</a></small>
                </div>
            </div>

            @if (!empty($config) && !empty($config['dry_run_mode']))
                <div class="alert alert-warning"><i class="fal fa-info-circle"></i> Dry run mode is enabled — actions are logged but not sent to Fireblocks.</div>
            @endif

            <div id="monitoring-grid-error" class="alert alert-danger d-none mb-2" role="alert"></div>

            <div id="auto-refresh-controls" class="d-none align-items-center ml-2 mb-0" style="gap:.45rem;">
                <div class="custom-control custom-checkbox d-inline-flex align-items-center mb-0" style="padding-left:1.4rem;">
                    <input type="checkbox" class="custom-control-input" id="auto-refresh-grid">
                    <label class="custom-control-label mb-0" for="auto-refresh-grid">Auto refresh</label>
                </div>
                <select id="auto-refresh-interval" class="form-control form-control-sm d-inline-block" style="width:auto;min-width:7.5rem;" disabled title="Refresh interval">
                    <option value="1" selected>Every 1 min</option>
                    <option value="2">Every 2 min</option>
                    <option value="5">Every 5 min</option>
                </select>
                <span id="auto-refresh-countdown" class="text-muted small d-none font-weight-bold" style="min-width:5.5rem;white-space:nowrap;"></span>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover m-0" id="monitoring-grid" style="width:100%">
                    <thead class="thead-themed">
                        <tr>
                            <th>ID @include('fireblocks-monitoring::partials.dashboard-ui-hint', ['hint' => $uiHints['grid_col_id'] ?? 'TR request id (account_transaction_requests.id).'])</th>
                            <th>Provider</th>
                            <th>Transaction ID</th>
                            <th>Asset / Amount</th>
                            <th title="Linked client account">Account</th>
                            <th>Direction</th>
                            <th>Status</th>
                            <th>Sub-status</th>
                            <th title="Portal rollup of Fireblocks primary + sub-status (Pending, Approved, Rejected, etc.)">Internal</th>
                            <th title="Chainalysis KYT / AML screening on this custody tx">Chainalysis</th>
                            <th>Tree</th>
                            @if (!empty($dashboardColumns['queue']))
                                <th>Queue</th>
                            @endif
                            @if (!empty($dashboardColumns['reattempts']))
                                <th>Reattempts</th>
                            @endif
                            @if (!empty($dashboardColumns['last_reattempt']))
                                <th>Last Reattempt</th>
                            @endif
                            @if (!empty($dashboardColumns['stuck_for']))
                                <th>Stuck For</th>
                            @endif
                            @if (!empty($dashboardColumns['ledger']))
                                <th title="Ledger posting status (TR / account_transactions)">Ledger</th>
                            @endif
                            <th>Created</th>
                            <th>Updated</th>
                            <th width="90">Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</div>{{-- #fb-monitoring-dashboard --}}

<div class="modal fade" id="tx-detail-modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Transaction Details</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body" id="tx-detail-body">
                <div class="text-center p-5"><i class="fal fa-spinner fa-spin fa-2x"></i></div>
            </div>
            <div class="modal-footer flex-wrap justify-content-between">
                <div class="fb-monitoring-actions mb-0 mr-auto" id="tx-detail-actions-wrap" data-tx-id="">
                    <div id="tx-action-response" class="w-100 mb-2 d-none fb-action-response"></div>
                    <div class="btn-group btn-group-sm flex-wrap fb-monitoring-action-buttons">
                        <button type="button" class="btn btn-outline-info fb-btn-sync" data-tx-id="">
                            <i class="fal fa-sync"></i> Sync
                        </button>
                        <button type="button" class="btn btn-outline-success fb-btn-boost d-none" data-tx-id="">
                            <i class="fal fa-rocket"></i> Boost
                        </button>
                        <button type="button" class="btn btn-outline-warning fb-btn-drop d-none" data-tx-id="">
                            <i class="fal fa-times"></i> Drop
                        </button>
                        <button type="button" class="btn btn-outline-dark fb-btn-resubmit d-none" data-tx-id="">
                            <i class="fal fa-redo"></i> Resubmit
                        </button>
                    </div>
                </div>
                <div class="d-flex flex-wrap align-items-center ml-auto">
                    <a href="#" target="_blank" class="btn btn-outline-secondary mb-2 d-none mr-2" id="tx-detail-ledger-link"><i class="fal fa-external-link"></i> Ledger</a>
                    <button type="button" class="btn btn-secondary mb-2" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>

@include('fireblocks-monitoring::partials.monitoring-actions', [
    'defaultFeeLevel' => $config['boost_fee_level'] ?? 'HIGH',
    'defaultGasPrice' => $config['boost_gas_price'] ?? '',
])
@include('fireblocks-monitoring::partials.status-reference-modal', ['statusCatalog' => $statusCatalog])
@include('fireblocks-monitoring::partials.snapshot-modal')
@include('fireblocks-monitoring::partials.retry-chain-tree-styles')
@include('crypto-management.crypto-transactions.partials.fireblocks-beneficiary-modal')
@endsection

@section('scripts')
@include('fireblocks-monitoring::partials.monitoring-actions-script', [
    'defaultFeeLevel' => $config['boost_fee_level'] ?? 'HIGH',
])
<script>
$(function () {
    const csrf = '{{ csrf_token() }}';
    const baseUrl = '{{ url('/fireblocks/transaction-monitoring') }}';
    const defaultFilters = @json($defaultFilters ?? []);
    const filterStorageKey = 'fbMonitoringFilters';
    let activeTxId = null;

    function loadSavedFilters() {
        try {
            const saved = sessionStorage.getItem(filterStorageKey);
            if (saved) return JSON.parse(saved);
        } catch (e) {}
        return null;
    }

    function saveFilters() {
        sessionStorage.setItem(filterStorageKey, JSON.stringify({
            monitoring_state: $('#filter-monitoring-state').val(),
            fb_status: $('#filter-fb-status').val(),
            fb_sub_status: $('#filter-fb-sub-status').val(),
            internal_status: $('#filter-internal-status').val(),
            direction: $('#filter-direction').val(),
            stuck_only: $('#filter-stuck-only').is(':checked') ? 1 : 0,
            ledger_scope: $('#filter-ledger-scope').val() || 'ledger',
            provider: $('#filter-provider').val() || 'all',
        }));
    }

    function applyFilters(filters) {
        $('#filter-monitoring-state').val(filters.monitoring_state || 'all');
        if ($('#filter-provider').length) {
            $('#filter-provider').val(filters.provider || 'all');
        }
        $('#filter-fb-status').val(filters.fb_status || 'all');
        $('#filter-fb-sub-status').val(filters.fb_sub_status || 'all');
        $('#filter-internal-status').val(filters.internal_status || 'all');
        $('#filter-direction').val(filters.direction || 'all');
        $('#filter-stuck-only').prop('checked', !!filters.stuck_only);
        $('#filter-ledger-scope').val(filters.ledger_scope || (filters.show_orphans ? 'orphan' : 'ledger'));
    }

    const initialFilters = loadSavedFilters() || defaultFilters;
    applyFilters(initialFilters);

    function initMonitoringFilterSelect2() {
        const select2Opts = {
            width: '100%',
            minimumResultsForSearch: 0,
            dropdownAutoWidth: true,
            dropdownCssClass: 'fb-filter-dropdown',
        };
        $('#filter-monitoring-state').select2({ ...select2Opts, placeholder: 'Monitoring state' });
        if ($('#filter-provider').length) {
            $('#filter-provider').select2({ ...select2Opts, placeholder: 'Provider' });
        }
        $('#filter-fb-status').select2({ ...select2Opts, placeholder: 'Status' });
        $('#filter-fb-sub-status').select2({ ...select2Opts, placeholder: 'Sub-status' });
        $('#filter-internal-status').select2({ ...select2Opts, placeholder: 'Internal status' });
        $('#filter-direction').select2({ ...select2Opts, placeholder: 'Direction' });
        $('#filter-ledger-scope').select2({ ...select2Opts, placeholder: 'Transaction scope' });
    }

    initMonitoringFilterSelect2();

    function filterParams() {
        return {
            monitoring_state: $('#filter-monitoring-state').val(),
            fb_status: $('#filter-fb-status').val(),
            fb_sub_status: $('#filter-fb-sub-status').val(),
            internal_status: $('#filter-internal-status').val(),
            direction: $('#filter-direction').val(),
            stuck_only: $('#filter-stuck-only').is(':checked') ? 1 : 0,
            ledger_scope: $('#filter-ledger-scope').val() || 'ledger',
            provider: $('#filter-provider').val() || 'all',
        };
    }

    function loadSummary() {
        $.get(baseUrl + '/summary', filterParams()).done(function (res) {
            if (!res.success || !res.summary) return;
            $('#summary-stuck').text(res.summary.stuck ?? 0);
            $('#summary-action-pending').text(res.summary.action_pending ?? 0);
            $('#summary-open-alerts').text(res.summary.open_alerts ?? 0);
            $('#summary-pending-fb').text(res.summary.pending_fb ?? 0);
            $('#summary-completed').text(res.summary.completed ?? 0);
            $('#summary-orphan').text(res.summary.orphan ?? 0);
        });
    }

    const dashboardColumns = @json($dashboardColumns ?? []);
    const gridColumns = [
        { data: 'id', name: 'fireblocks_transactions.id' },
        { data: 'provider_badge', orderable: false, searchable: false },
        { data: 'fireblocks_tx_id', name: 'fireblocks_transactions.fireblocks_tx_id' },
        { data: 'amount_fmt', orderable: false, searchable: false },
        { data: 'account_label', orderable: false, searchable: false },
        { data: 'direction', name: 'fireblocks_transactions.direction' },
        { data: 'fb_status_badge', orderable: false, searchable: false },
        { data: 'sub_status', orderable: false },
        { data: 'internal_status_badge', orderable: false, searchable: false },
        { data: 'chainalysis_badge', name: 'fireblocks_transactions.chainalysis_status', orderable: {{ !empty($chainalysisStatusColumnReady) ? 'true' : 'false' }}, searchable: false },
        { data: 'retry_chain_badge', orderable: false, searchable: false },
    ];
    if (dashboardColumns.queue) {
        gridColumns.push({ data: 'queue_status_badge', orderable: false, searchable: false });
    }
    if (dashboardColumns.reattempts) {
        gridColumns.push({ data: 'reattempt_count', name: 'fireblocks_transactions.auto_action_count', searchable: false });
    }
    if (dashboardColumns.last_reattempt) {
        gridColumns.push({ data: 'last_reattempt_fmt', name: 'fireblocks_transactions.last_reattempt_at', searchable: false });
    }
    if (dashboardColumns.stuck_for) {
        gridColumns.push({ data: 'stuck_for', name: 'fireblocks_transactions.stuck_detected_at', searchable: false });
    }
    if (dashboardColumns.ledger) {
        gridColumns.push({ data: 'ledger_status_badge', orderable: false, searchable: false });
    }
    gridColumns.push({ data: 'created_at', name: 'fireblocks_transactions.created_at', searchable: false });
    gridColumns.push({ data: 'updated_at', name: 'fireblocks_transactions.updated_at', searchable: false });
    gridColumns.push({ data: 'actions', orderable: false, searchable: false });
    const idColIndex = gridColumns.findIndex(function (col) { return col.data === 'id'; });

    const table = $('#monitoring-grid').DataTable({
        processing: true,
        serverSide: true,
        pageLength: {{ (int) ($pageSize ?? 15) }},
        lengthMenu: [[10, 15, 25, 50, 100], [10, 15, 25, 50, 100]],
        // ID column = TR request id; server sorts by tr_request_id
        order: [[ idColIndex > -1 ? idColIndex : 0, 'desc' ]],
        ajax: {
            url: baseUrl + '/data',
            data: function (d) { Object.assign(d, filterParams()); },
            error: function (xhr) {
                let msg = 'Failed to load transactions.';
                try {
                    const body = xhr.responseJSON || JSON.parse(xhr.responseText);
                    if (body && body.message) msg = body.message;
                    else if (body && body.error) msg = body.error;
                } catch (e) {
                    if (xhr.status) msg += ' (HTTP ' + xhr.status + ')';
                }
                $('#monitoring-grid-error').removeClass('d-none').text(msg);
            }
        },
        columns: gridColumns,
        drawCallback: function () {
            $('#monitoring-grid-error').addClass('d-none').text('');
        },
        initComplete: function () {
            const $filter = $('#monitoring-grid_wrapper .dataTables_filter');
            $('#auto-refresh-controls').appendTo($filter).removeClass('d-none').addClass('d-inline-flex');
        },
    });

    function reloadAll(resetPaging) {
        saveFilters();
        table.ajax.reload(null, resetPaging === true);
        loadCharts();
        loadSummary();
    }

    let autoRefreshTimer = null;
    let autoRefreshCountdownTimer = null;
    let autoRefreshDeadline = 0;

    function formatRefreshCountdown(ms) {
        const total = Math.max(0, Math.ceil(ms / 1000));
        const m = Math.floor(total / 60);
        const s = total % 60;
        return m + ':' + (s < 10 ? '0' : '') + s;
    }

    function tickRefreshCountdown() {
        const left = autoRefreshDeadline - Date.now();
        $('#auto-refresh-countdown').text('Refresh in ' + formatRefreshCountdown(left));
    }

    function stopAutoRefresh() {
        if (autoRefreshTimer) {
            clearInterval(autoRefreshTimer);
            autoRefreshTimer = null;
        }
        if (autoRefreshCountdownTimer) {
            clearInterval(autoRefreshCountdownTimer);
            autoRefreshCountdownTimer = null;
        }
        $('#auto-refresh-countdown').addClass('d-none').text('');
    }

    function startAutoRefresh() {
        stopAutoRefresh();
        if (!$('#auto-refresh-grid').is(':checked')) {
            $('#auto-refresh-interval').prop('disabled', true);
            return;
        }
        $('#auto-refresh-interval').prop('disabled', false);
        $('#auto-refresh-countdown').removeClass('d-none');
        const minutes = Math.max(1, parseInt($('#auto-refresh-interval').val(), 10) || 1);
        const intervalMs = minutes * 60 * 1000;

        function armCountdown() {
            autoRefreshDeadline = Date.now() + intervalMs;
            tickRefreshCountdown();
        }

        armCountdown();
        autoRefreshCountdownTimer = setInterval(tickRefreshCountdown, 250);
        autoRefreshTimer = setInterval(function () {
            if (!document.hidden && !$('.modal.show').length) {
                reloadAll(false);
            }
            armCountdown();
        }, intervalMs);
    }

    $('#auto-refresh-grid').on('change', startAutoRefresh);
    $('#auto-refresh-interval').on('change', startAutoRefresh);

    $('#filter-monitoring-state, #filter-provider, #filter-fb-status, #filter-fb-sub-status, #filter-internal-status, #filter-direction, #filter-stuck-only, #filter-ledger-scope').on('change', function () {
        reloadAll(true);
    });

    $('#btn-reset-filters').on('click', function () {
        applyFilters($.extend({}, defaultFilters, { provider: 'all' }));
        $('#filter-monitoring-state, #filter-provider, #filter-fb-status, #filter-fb-sub-status, #filter-internal-status, #filter-direction, #filter-ledger-scope').trigger('change.select2');
        sessionStorage.removeItem(filterStorageKey);
        reloadAll(true);
    });
    $('#btn-refresh-grid, #btn-refresh-charts').on('click', function () {
        reloadAll(false);
    });

    $('.fb-summary-card').on('click', function () {
        if ($(this).data('filter-monitoring-state')) {
            $('#filter-monitoring-state').val($(this).data('filter-monitoring-state')).trigger('change.select2');
            $('#filter-stuck-only').prop('checked', false);
        }
        if ($(this).data('filter-fb-status')) {
            $('#filter-fb-status').val($(this).data('filter-fb-status')).trigger('change.select2');
        }
        if ($(this).data('filter-show-orphans')) {
            $('#filter-ledger-scope').val('orphan').trigger('change.select2');
            $('#filter-monitoring-state').val('all').trigger('change.select2');
            $('#filter-fb-status').val('all').trigger('change.select2');
            $('#filter-stuck-only').prop('checked', false);
        }
        reloadAll(true);
    });

    function snapshotEscape(value) {
        return $('<div>').text(value == null ? '' : String(value)).html();
    }

    function snapshotAssetCell(row) {
        const short = snapshotEscape(row.short || '');
        const name = snapshotEscape(row.name || row.short || '');
        let logo = '<span class="fb-snap-logo-fallback">' + short.substring(0, 3) + '</span>';
        if (row.logo) {
            logo = '<img class="fb-snap-logo" src="' + snapshotEscape(row.logo) + '" alt="' + name + '">';
        }
        return '<div class="fb-snap-asset">' + logo
            + '<div><div class="fb-snap-name">' + name + '</div>'
            + '<div class="fb-snap-symbol">' + short + '</div></div></div>';
    }

    function formatUsdRate(value) {
        if (value == null || value === '' || isNaN(Number(value))) {
            return '<span class="text-muted">—</span>';
        }
        return '<span class="fb-snap-usd">$' + Number(value).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + '</span>';
    }

    function formatSnapQty(value) {
        return '<span class="fb-snap-num">' + snapshotEscape(value == null || value === '' ? '—' : value) + '</span>';
    }

    let snapshotState = null;

    function applySnapshotMeta(res) {
        $('#fb-snapshot-last-refresh').text('Last refresh: ' + (res && res.last_refreshed_at ? res.last_refreshed_at : '—'));
        $('#fb-snapshot-interval').text(res && res.refresh_interval ? res.refresh_interval : '');
    }

    function renderSnapshotRows(res, columns, rowMapper) {
        applySnapshotMeta(res);
        if (snapshotState) {
            snapshotState.rows = (res && res.rows) ? res.rows : [];
            if (columns) {
                snapshotState.columns = columns;
            }
            if (rowMapper) {
                snapshotState.rowMapper = rowMapper;
            }
        }
        paintSnapshotRows();
    }

    function snapshotVisibleRows(rows) {
        rows = rows || [];
        if (!snapshotState || !snapshotState.balanceFilter || snapshotState.showNonPositive) {
            return rows;
        }
        return rows.filter(function (row) {
            const amount = Number(row.total_amount);
            return !isNaN(amount) && amount > 0;
        });
    }

    function paintSnapshotRows() {
        if (!snapshotState) {
            return;
        }
        const columns = snapshotState.columns;
        const source = snapshotState.rows || [];
        const rows = snapshotVisibleRows(source);
        if (!rows.length) {
            let empty = snapshotState.emptyMessage || 'No enabled crypto currencies found.';
            if (snapshotState.balanceFilter && !snapshotState.showNonPositive && source.length) {
                empty = 'No positive balances. Show zero and negative balances to see the rest.';
            }
            $('#fb-snapshot-body').html('<tr><td colspan="' + columns.length + '" class="text-muted px-3 py-4">' + snapshotEscape(empty) + '</td></tr>');
            return;
        }
        $('#fb-snapshot-body').html(rows.map(snapshotState.rowMapper).join(''));
    }

    function loadSnapshot(url) {
        if (!snapshotState) {
            return;
        }
        $('#fb-snapshot-error').addClass('d-none').text('');
        $('#fb-snapshot-body').html('<tr><td colspan="' + snapshotState.columns.length + '" class="text-muted px-3 py-4">Loading…</td></tr>');
        $.getJSON(url).done(function (res) {
            renderSnapshotRows(res, snapshotState.columns, snapshotState.rowMapper);
        }).fail(function (xhr) {
            let msg = 'Failed to load.';
            try {
                const body = xhr.responseJSON || JSON.parse(xhr.responseText);
                if (body && body.message) msg = body.message;
            } catch (e) {}
            $('#fb-snapshot-error').removeClass('d-none').text(msg);
            $('#fb-snapshot-body').html('');
        });
    }

    function openSnapshotModal(title, hint, url, syncUrl, columns, rowMapper, options) {
        options = options || {};
        snapshotState = {
            url: url,
            syncUrl: syncUrl,
            columns: columns,
            rowMapper: rowMapper,
            rows: [],
            source: options.source || '',
            balanceFilter: !!options.balanceFilter,
            showNonPositive: false,
            emptyMessage: options.emptyMessage || 'No enabled crypto currencies found.',
        };
        $('#fb-snapshot-title').text(title);
        $('#fb-snapshot-hint').text(hint);
        $('#fb-snapshot-kicker').text(options.kicker || 'Enabled crypto currencies');
        $('#fb-snapshot-show-zero').prop('checked', false);
        $('#fb-snapshot-zero-filter').toggleClass('d-none', !snapshotState.balanceFilter);
        $('#fb-snapshot-error').addClass('d-none').text('');
        $('#fb-snapshot-last-refresh').text('Last refresh: —');
        $('#fb-snapshot-interval').text('');
        $('#fb-snapshot-source-tabs').toggleClass('d-none', !options.balanceTabs);
        if (options.balanceTabs) {
            setFbBalanceSource('wallet_pool', false);
        }
        const rightColumns = options.rightColumns || [];
        $('#fb-snapshot-head').html('<tr>' + columns.map(function (c) {
            const isAmount = rightColumns.indexOf(c) >= 0 || /available|total|pending|usd|rate|amount|qty/i.test(c);
            return '<th' + (isAmount ? ' class="text-right"' : '') + '>' + snapshotEscape(c) + '</th>';
        }).join('') + '</tr>');
        $('#fb-snapshot-modal').modal('show');
        loadSnapshot(snapshotState.url);
    }

    $('#fb-snapshot-show-zero').on('change', function () {
        if (!snapshotState) {
            return;
        }
        snapshotState.showNonPositive = $(this).is(':checked');
        paintSnapshotRows();
    });

    $('#fb-snapshot-sync').on('click', function () {
        if (!snapshotState || !snapshotState.syncUrl) {
            return;
        }
        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="fal fa-spinner fa-spin"></i> Syncing…');
        $('#fb-snapshot-error').addClass('d-none').text('');
        $.ajax({
            url: snapshotState.syncUrl,
            method: 'POST',
            data: { _token: csrf, source: snapshotState.source || '' }
        }).done(function (res) {
            renderSnapshotRows(res, snapshotState.columns, snapshotState.rowMapper);
        }).fail(function (xhr) {
            let msg = 'Sync failed.';
            try {
                const body = xhr.responseJSON || JSON.parse(xhr.responseText);
                if (body && body.message) msg = body.message;
            } catch (e) {}
            $('#fb-snapshot-error').removeClass('d-none').text(msg);
        }).always(function () {
            $btn.prop('disabled', false).html('<i class="fal fa-sync"></i> Sync');
        });
    });

    $('#btn-currency-rates').on('click', function () {
        openSnapshotModal(
            'Currency Rates',
            'Live USD spots from CoinGecko for enabled crypto currencies.',
            baseUrl + '/currency-rates',
            baseUrl + '/currency-rates/sync',
            ['Currency', 'USD Rate', 'Source'],
            function (row) {
                return '<tr>'
                    + '<td>' + snapshotAssetCell(row) + '</td>'
                    + '<td class="text-right">' + formatUsdRate(row.usd_rate) + '</td>'
                    + '<td><span class="fb-snap-pill">' + snapshotEscape(row.source || '—') + '</span></td>'
                    + '</tr>';
            }
        );
    });

    function setFbBalanceSource(source, reload) {
        if (!snapshotState) {
            return;
        }
        snapshotState.source = source;
        snapshotState.url = baseUrl + '/fireblocks-balances?source=' + encodeURIComponent(source);
        $('.fb-balance-source').removeClass('btn-primary').addClass('btn-outline-secondary');
        $('.fb-balance-source[data-source="' + source + '"]').removeClass('btn-outline-secondary').addClass('btn-primary');
        $('#fb-snapshot-hint').text(source === 'omnibus'
            ? 'Omnibus vault balances for enabled crypto currencies. Synced from the Fireblocks Omnibus Vault ID.'
            : 'Withdrawal pool balances for enabled crypto currencies.');
        if (reload) {
            loadSnapshot(snapshotState.url);
        }
    }

    $(document).on('click', '.fb-balance-source', function () {
        setFbBalanceSource($(this).data('source'), true);
    });

    $('#btn-fb-balances').on('click', function () {
        openSnapshotModal(
            'Fireblocks Balances',
            'Withdrawal pool balances for enabled crypto currencies.',
            baseUrl + '/fireblocks-balances?source=wallet_pool',
            baseUrl + '/fireblocks-balances/sync',
            ['Currency', 'Asset', 'Available', 'Available USD', 'Total', 'Pending'],
            function (row) {
                return '<tr>'
                    + '<td>' + snapshotAssetCell(row) + '</td>'
                    + '<td><code class="fb-snap-asset-id">' + snapshotEscape(row.asset_id) + '</code></td>'
                    + '<td class="text-right">' + formatSnapQty(row.available) + '</td>'
                    + '<td class="text-right">' + formatUsdRate(row.available_usd) + '</td>'
                    + '<td class="text-right">' + formatSnapQty(row.total) + '</td>'
                    + '<td class="text-right">' + formatSnapQty(row.pending) + '</td>'
                    + '</tr>';
            },
            { balanceTabs: true, source: 'wallet_pool', rightColumns: ['Available', 'Available USD', 'Total', 'Pending'] }
        );
    });

    $('#btn-aquanow-balances').on('click', function () {
        openSnapshotModal(
            'AquaNow Balances',
            'Liquidity balances for enabled crypto and fiat currencies.',
            baseUrl + '/aquanow-balances',
            baseUrl + '/aquanow-balances/sync',
            ['Currency', 'Total', 'Total USD'],
            function (row) {
                return '<tr>'
                    + '<td>' + snapshotAssetCell(row) + '</td>'
                    + '<td class="text-right">' + formatSnapQty(row.total) + '</td>'
                    + '<td class="text-right">' + formatUsdRate(row.total_usd) + '</td>'
                    + '</tr>';
            },
            {
                kicker: 'Crypto and fiat',
                balanceFilter: true,
                emptyMessage: 'No AquaNow balances found.',
            }
        );
    });

    $('#btn-understand-statuses, #link-understand-statuses').on('click', function (e) {
        e.preventDefault();
        $('#fb-status-reference-modal').modal('show');
    });
    $('.fb-open-status-ref').on('click', function (e) {
        e.preventDefault();
        const tabMap = {
            primary: '#fb-ref-primary',
            substatus: '#fb-ref-substatus',
            internal: '#fb-ref-internal',
        };
        const tab = tabMap[$(this).data('tab')] || '#fb-ref-primary';
        $('#fb-status-reference-modal').modal('show');
        $('#fb-status-reference-modal a[href="' + tab + '"]').tab('show');
    });
    $('#fb-ref-substatus-search').on('input', function () {
        const q = $(this).val().toLowerCase();
        $('#fb-ref-substatus-table tbody tr').each(function () {
            $(this).toggle($(this).text().toLowerCase().indexOf(q) >= 0);
        });
    });

    function applyActionButtons(actions, txId) {
        FireblocksMonitoringActions.applyButtonState($('#tx-detail-actions-wrap'), actions || {}, txId);
    }

    window.fbMonitoringReloadGrid = function () { table.ajax.reload(null, false); };
    window.fbMonitoringReloadDetail = function (txId) { loadTransactionDetail(txId || activeTxId); };

    function loadTransactionDetail(txId) {
        activeTxId = txId;
        $('#tx-detail-body').html('<div class="text-center p-5"><i class="fal fa-spinner fa-spin fa-2x"></i></div>');

        $.get(baseUrl + '/' + encodeURIComponent(txId)).done(function (res) {
            if (!res.success) {
                $('#tx-detail-body').html('<div class="alert alert-danger">Unable to load details.</div>');
                return;
            }

            $('#tx-detail-body').html(res.detail_html || '<div class="alert alert-warning">No detail content returned.</div>');
            if (typeof window.fbInitMonitoringDetailTooltips === 'function') {
                window.fbInitMonitoringDetailTooltips('#tx-detail-body');
            }
            applyActionButtons(res.available_actions || {}, txId);

            if (res.ledger_url) {
                $('#tx-detail-ledger-link').attr('href', res.ledger_url).removeClass('d-none');
            } else {
                $('#tx-detail-ledger-link').addClass('d-none');
            }
        }).fail(function () {
            $('#tx-detail-body').html('<div class="alert alert-danger">Failed to load transaction details.</div>');
        });
    }

    $(document).on('click', '.btn-view-tx, .btn-chain-view', function () {
        $('#tx-action-response').addClass('d-none').empty();
        activeTxId = $(this).data('tx-id');
        $('#tx-detail-modal').modal('show');
        loadTransactionDetail(activeTxId);
    });

    $(document).on('click', '.fb-copy', function () {
        const val = $(this).data('copy');
        if (val && navigator.clipboard) navigator.clipboard.writeText(val);
    });

    const fbChartInstances = {};

    function chartHasData(series) {
        const values = series && series.values ? series.values : [];
        return values.some(function (v) { return Number(v) > 0; });
    }

    function destroyChart(key) {
        if (fbChartInstances[key]) {
            fbChartInstances[key].destroy();
            delete fbChartInstances[key];
        }
    }

    function renderDoughnut(canvasId, series, options) {
        const canvas = document.getElementById(canvasId);
        if (!canvas || typeof Chart === 'undefined') return;

        destroyChart(canvasId);

        if (!chartHasData(series)) {
            const wrap = canvas.closest('.fb-chart-box');
            if (wrap) {
                wrap.querySelectorAll('.fb-chart-empty').forEach(function (n) { n.remove(); });
                const empty = document.createElement('div');
                empty.className = 'fb-chart-empty text-muted text-center pt-5';
                empty.innerHTML = '<small>No data for current filters</small>';
                canvas.classList.add('d-none');
                wrap.appendChild(empty);
            }
            return;
        }

        canvas.classList.remove('d-none');
        const wrap = canvas.closest('.fb-chart-box');
        if (wrap) wrap.querySelectorAll('.fb-chart-empty').forEach(function (n) { n.remove(); });

        const cfg = Object.assign({
            type: 'doughnut',
            data: {
                labels: series.labels || [],
                datasets: [{
                    data: series.values || [],
                    backgroundColor: series.colors || [],
                    borderWidth: 2,
                    borderColor: '#fff',
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutoutPercentage: 62,
                legend: {
                    position: 'bottom',
                    labels: { boxWidth: 8, fontSize: 9, padding: 6 },
                },
                tooltips: {
                    callbacks: {
                        label: function (item, data) {
                            const label = data.labels[item.index] || '';
                            const val = data.datasets[0].data[item.index] || 0;
                            const sum = data.datasets[0].data.reduce(function (a, b) { return a + Number(b); }, 0);
                            const pct = sum ? Math.round((val / sum) * 100) : 0;
                            return label + ': ' + val + ' (' + pct + '%)';
                        },
                    },
                },
            },
        }, options || {});

        fbChartInstances[canvasId] = new Chart(canvas.getContext('2d'), cfg);
    }

    function renderBar(canvasId, series, horizontal) {
        const canvas = document.getElementById(canvasId);
        if (!canvas || typeof Chart === 'undefined') return;

        destroyChart(canvasId);

        if (!chartHasData(series)) {
            const wrap = canvas.closest('.fb-chart-box');
            if (wrap) {
                wrap.querySelectorAll('.fb-chart-empty').forEach(function (n) { n.remove(); });
                const empty = document.createElement('div');
                empty.className = 'fb-chart-empty text-muted text-center pt-5';
                empty.innerHTML = '<small>No data for current filters</small>';
                canvas.classList.add('d-none');
                wrap.appendChild(empty);
            }
            return;
        }

        canvas.classList.remove('d-none');
        const wrap = canvas.closest('.fb-chart-box');
        if (wrap) wrap.querySelectorAll('.fb-chart-empty').forEach(function (n) { n.remove(); });

        fbChartInstances[canvasId] = new Chart(canvas.getContext('2d'), {
            type: horizontal ? 'horizontalBar' : 'bar',
            data: {
                labels: series.labels || [],
                datasets: [{
                    data: series.values || [],
                    backgroundColor: series.colors || [],
                    borderWidth: 0,
                    barPercentage: 0.65,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                legend: { display: false },
                scales: {
                    xAxes: [{ ticks: { beginAtZero: true, precision: 0, fontSize: 10 } }],
                    yAxes: [{ ticks: { fontSize: 10 } }],
                },
            },
        });
    }

    function loadCharts() {
        $.get(baseUrl + '/charts', filterParams()).done(function (res) {
            if (!res.success) return;

            $('#chart-scope-total').text((res.scope_total ?? 0) + ' in grid scope');

            renderDoughnut('chart-direction-orphan', res.direction_orphan || {});
            const dirTotal = (res.direction_orphan && res.direction_orphan.center_total) ? res.direction_orphan.center_total : 0;
            if (dirTotal > 0) {
                $('#chart-direction-orphan-total').text(dirTotal);
                $('#chart-direction-orphan-center').removeClass('d-none');
            } else {
                $('#chart-direction-orphan-center').addClass('d-none');
            }

            renderDoughnut('chart-pipeline', res.pipeline || {});
            renderBar('chart-stuck-age', res.stuck_age || {}, false);
            renderBar('chart-actions-7d', res.actions_last_7 || {}, false);
        });
    }

    if (typeof Chart !== 'undefined') {
        loadCharts();
        loadSummary();
    }
});
</script>
@include('fireblocks-monitoring::partials.detail-field-hint-script')
<script>
@include('crypto-management.crypto-transactions.partials.fireblocks-beneficiary-modal-script')
</script>
@endsection
