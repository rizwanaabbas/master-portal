@extends('layouts.cp')
@section('title', 'Sweep Rules')

@section('styles')
@include('fireblocks-sweeping::partials.stylesheets', ['sheets' => [
    'sweep-datagrid.css',
    'sweep-rules-index.css',
]])
@endsection

@section('content')
<div class="subheader">
    <h1 class="subheader-title">
        <span class="fw-300">Vault Sweeping</span> <i class="fal fa-angle-right"></i>
        Rules
    </h1>
</div>

@include('fireblocks-sweeping::partials.module-nav', ['sweepNavActive' => 'rules'])

<div class="panel">
    <div class="panel-hdr">
        <h2>Sweep Rules</h2>
        <div class="panel-toolbar">
            <button type="button" class="btn btn-sm btn-outline-secondary mr-1" data-toggle="modal" data-target="#sweep-rule-import-modal">
                <i class="fal fa-file-import mr-1"></i> Import rule
            </button>
            <a href="{{ route('fireblocks.vault-sweeping.rules.create') }}" class="btn btn-sm btn-primary">New Rule</a>
        </div>
    </div>
    <div class="panel-container show">
        <div class="panel-content">
            @include('common.error-message')
            @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if (session('info'))
                <div class="alert alert-info">{{ session('info') }}</div>
            @endif

            @if (!$sweepingActive)
                <div class="alert alert-warning">
                    <strong>Sweeping is not active.</strong>
                    Enable <em>Sweeping Enabled</em> in
                    <a href="{{ url('/settings/module-configurations') }}">Settings → Module Configurations</a>
                    for the Fireblocks Vault Sweeping module.
                </div>
            @elseif (!empty($moduleConfig['dry_run_mode']))
                <div class="alert alert-info">
                    <strong>Global dry run is ON.</strong>
                    Sweeps evaluate rules and log jobs only — no Fireblocks transactions.
                </div>
            @endif

            <p class="text-muted mb-2">
                One rule per source asset. Deposit sweeps run after ledger credit (Approved). Dust threshold applies on cron and manual run.
                Scheduled rules run by priority (lower first) on each rule's own interval or daily time.
                Thresholds compare <strong>available</strong> balance (spendable now), not total or locked.
            </p>
            <p class="text-muted mb-3">
                <i class="fal fa-grip-vertical mr-1"></i> To reorder priority, select <strong>All</strong> in the page size dropdown, then drag rows.
            </p>
            <div class="row mb-3">
                <div class="col-md-3">
                    <label class="form-label" for="filter-enabled">Status</label>
                    <select id="filter-enabled" class="form-control w-100">
                        <option value="">All rules</option>
                        <option value="1">Active</option>
                        <option value="0">Disabled</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="filter-trigger">Trigger</label>
                    <select id="filter-trigger" class="form-control w-100">
                        <option value="">All triggers</option>
                        <option value="deposit">Deposit</option>
                        <option value="scheduled">Schedule</option>
                        <option value="both">Deposit + Schedule</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="filter-source-asset">Source asset</label>
                    <select id="filter-source-asset" class="form-control w-100">
                        <option value="">All assets</option>
                        @foreach ($sourceAssets as $assetId)
                            <option value="{{ $assetId }}">{{ $assetId }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="filter-dry-run">Rule dry run</label>
                    <select id="filter-dry-run" class="form-control w-100">
                        <option value="">Any</option>
                        <option value="1">Dry run on</option>
                        <option value="0">Dry run off</option>
                    </select>
                </div>
                <div class="col-md-1 d-flex align-items-end justify-content-md-end mb-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="filter-reset" title="Reset filters">
                        <i class="fal fa-undo"></i>
                    </button>
                </div>
            </div>

            <div id="sweep-rules-priority-status" class="mb-2"></div>
            <div class="alert alert-light border py-2 mb-3 sweep-reorder-hint d-none small">
                <i class="fal fa-info-circle text-primary mr-1"></i>
                Drag reorder is enabled while all rules are visible. Saving updates global run priority.
            </div>

            <div class="sweep-datagrid-wrap">
                <table class="table table-bordered table-striped sweep-datagrid w-100" id="sweep-rules-table">
                    <thead>
                        <tr>
                            <th style="width:2rem" title="Drag to reorder priority"></th>
                            <th data-data="priority" data-name="priority">Priority</th>
                            <th data-data="id" data-name="id">Rule ID</th>
                            <th data-data="name" data-name="name" class="sweep-cell-wrap">Name</th>
                            <th data-data="schedule_label" data-orderable="false" data-searchable="false" class="sweep-cell-wrap">Schedule</th>
                            <th data-data="source_asset" data-name="asset_ids" data-orderable="false">Source asset</th>
                            <th data-data="target_asset" data-name="target_asset_id" data-orderable="false">Target asset</th>
                            <th data-data="min_balance" data-name="min_balance">Deposit threshold</th>
                            <th data-data="scheduled_min_balance" data-name="scheduled_min_balance">Dust threshold</th>
                            <th data-data="triggers_label" data-name="trigger_modes" data-orderable="false">Triggers</th>
                            <th data-data="enabled_badge" data-name="enabled">Enabled</th>
                            <th data-data="actions" data-orderable="false" data-searchable="false">Actions</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="sweep-rule-import-modal" tabindex="-1" role="dialog" aria-labelledby="sweep-rule-import-title" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <form method="POST" action="{{ route('fireblocks.vault-sweeping.rules.import') }}" enctype="multipart/form-data" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title" id="sweep-rule-import-title">Import sweep rule</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">
                    Upload a JSON file exported from another rule (Rule detail → Export JSON). Creates a new rule; schedule last-run time is not copied.
                </p>
                <div class="form-group">
                    <label for="sweep-import-file">JSON file</label>
                    <input type="file" name="import_file" id="sweep-import-file" class="form-control-file" accept=".json,application/json">
                </div>
                <div class="form-group mb-0">
                    <label for="sweep-import-json">Or paste JSON</label>
                    <textarea name="import_json" id="sweep-import-json" class="form-control font-monospace small" rows="8" placeholder="{ &quot;export_version&quot;: 1, ... }"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Import</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
@include('fireblocks-sweeping::partials.confirm-modal')
@include('fireblocks-sweeping::partials.datagrid-init')
<script>
$(function () {
    var $status = $('#sweep-rules-priority-status');
    var $reorderHint = $('.sweep-reorder-hint');
    var reorderUrl = @json(route('fireblocks.vault-sweeping.rules.reorder-priorities'));
    var csrf = @json(csrf_token());
    var sortableInstance = null;

    function sweepRulesFilterParams(d) {
        d.enabled = $('#filter-enabled').val();
        d.trigger_mode = $('#filter-trigger').val();
        d.source_asset = $('#filter-source-asset').val();
        d.rule_dry_run = $('#filter-dry-run').val();
    }

    function initSweepRulesFilterSelect2() {
        var base = { width: '100%', allowClear: true, minimumResultsForSearch: 0 };
        $('#filter-enabled').select2({ ...base, placeholder: 'All rules' });
        $('#filter-trigger').select2({ ...base, placeholder: 'All triggers' });
        $('#filter-source-asset').select2({ ...base, placeholder: 'All assets' });
        $('#filter-dry-run').select2({ ...base, placeholder: 'Any' });
    }

    function reloadSweepRulesTable() {
        table.ajax.reload(null, false);
    }

    function showPriorityStatus(type, message) {
        $status.html('<div class="alert alert-' + type + ' py-1 px-2 mb-0 small">' + message + '</div>');
        if (type === 'success') {
            setTimeout(function () { $status.empty(); }, 2500);
        }
    }

    function canReorder(table) {
        var info = table.page.info();
        return info.length === -1 || info.recordsDisplay <= info.length;
    }

    function destroySortable() {
        if (sortableInstance && $.fn.sortable) {
            try {
                $('#sweep-rules-table tbody').sortable('destroy');
            } catch (e) {}
            sortableInstance = null;
        }
    }

    function persistPriorityOrder() {
        var order = [];
        $('#sweep-rules-table tbody tr[data-rule-id]').each(function () {
            order.push(parseInt($(this).data('rule-id'), 10));
        });

        if (order.length === 0) {
            return;
        }

        showPriorityStatus('info', '<i class="fal fa-spinner fa-spin mr-1"></i> Saving priority order…');

        $.ajax({
            method: 'POST',
            url: reorderUrl,
            data: { _token: csrf, order: order },
        }).done(function (res) {
            showPriorityStatus('success', res.message || 'Rule priorities updated.');
            table.ajax.reload(null, false);
        }).fail(function (xhr) {
            showPriorityStatus('danger', xhr.responseJSON?.message || 'Failed to save priority order.');
        });
    }

    function initSortable(table) {
        destroySortable();

        if (!$.fn.sortable || !canReorder(table)) {
            $reorderHint.addClass('d-none').removeClass('is-active');
            return;
        }

        var $tbody = $('#sweep-rules-table tbody');
        if ($tbody.find('tr[data-rule-id]').length < 2) {
            return;
        }

        $reorderHint.removeClass('d-none').addClass('is-active');

        sortableInstance = $tbody.sortable({
            items: '> tr[data-rule-id]',
            handle: '.sweep-rule-drag-handle',
            axis: 'y',
            cursor: 'grabbing',
            tolerance: 'pointer',
            helper: function (e, $tr) {
                var $helper = $tr.clone();
                $helper.children().each(function (index) {
                    $(this).width($tr.children().eq(index).width());
                });
                return $helper;
            },
            update: function () {
                persistPriorityOrder();
            },
        });
    }

    initSweepRulesFilterSelect2();

    var table = $('#sweep-rules-table').DataTable(SweepDatagrid.defaults({
        serverSide: true,
        ajax: {
            url: @json(route('fireblocks.vault-sweeping.rules.data')),
            data: sweepRulesFilterParams,
            global: false,
        },
        order: [[1, 'asc']],
        orderMulti: false,
        columns: [
            { data: 'drag_handle', name: 'drag_handle', orderable: false, searchable: false, className: 'text-center' },
            { data: 'priority', name: 'priority', className: 'text-nowrap' },
            { data: 'rule_link', name: 'id' },
            { data: 'name', name: 'name', className: 'sweep-cell-wrap' },
            { data: 'schedule_label', name: 'schedule_label', orderable: false, searchable: false, className: 'sweep-cell-wrap' },
            { data: 'source_asset', name: 'asset_ids', orderable: false },
            { data: 'target_asset', name: 'target_asset_id', orderable: false },
            { data: 'min_balance', name: 'min_balance' },
            { data: 'scheduled_min_balance', name: 'scheduled_min_balance' },
            { data: 'triggers_label', name: 'trigger_modes', orderable: false },
            { data: 'enabled_badge', name: 'enabled', orderable: true, searchable: false },
            { data: 'actions', name: 'actions', orderable: false, searchable: false },
        ],
        createdRow: function (row, data) {
            if (data.id) {
                $(row).attr('data-rule-id', data.id);
            }
        },
        drawCallback: function () {
            initSortable(table);
        },
    }));

    $('#filter-enabled, #filter-trigger, #filter-source-asset, #filter-dry-run').on('change', reloadSweepRulesTable);
    $('#filter-reset').on('click', function () {
        $('#filter-enabled, #filter-trigger, #filter-source-asset, #filter-dry-run').val('').trigger('change');
        reloadSweepRulesTable();
    });
});
</script>
@endsection
