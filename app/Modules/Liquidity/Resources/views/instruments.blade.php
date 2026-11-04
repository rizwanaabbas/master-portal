@extends('layouts.cp')
@section('title', $providerLabel . ' Instruments - Crypto Management')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Crypto Management</span> &mdash; {{ $providerLabel }} Instruments
        </h1>
    </div>

    <div class="panel">
        <div class="panel-hdr">
            <h2>Instruments</h2>
            <div class="panel-toolbar">
                <button type="button" class="btn btn-primary btn-sm" id="sync-button" onclick="syncInstruments()">
                    <i class="fal fa-sync"></i> Sync {{ $providerLabel }} Instruments
                </button>
            </div>
        </div>

        <div class="panel-container show">
            <div class="panel-content">
                <div id="sync-message"></div>

                <div class="row mb-3">
                    <div class="col-md-3">
                        <label class="form-label" for="filter-currency">Currency</label>
                        <select id="filter-currency" class="form-control w-100">
                            <option value="">All enabled</option>
                            @foreach ($activeCurrencies as $currency)
                                <option value="{{ $currency->short }}">{{ $currency->short }} — {{ $currency->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="filter-instrument-type">Instrument type</label>
                        <select id="filter-instrument-type" class="form-control w-100">
                            <option value="">All types</option>
                            @foreach ($instrumentTypes as $type)
                                <option value="{{ $type }}">{{ $type }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="filter-portal-status">Portal status</label>
                        <select id="filter-portal-status" class="form-control w-100">
                            <option value="">All</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="filter-asset-type">Leg type</label>
                        <select id="filter-asset-type" class="form-control w-100">
                            <option value="">Any leg</option>
                            <option value="crypto">Involves crypto</option>
                            <option value="fiat">Fiat pair only</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end justify-content-md-end mb-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="filter-reset">
                            <i class="fal fa-undo"></i> Reset filters
                        </button>
                    </div>
                </div>

                <table id="instruments-table" class="table table-bordered table-hover table-striped w-100">
                    <thead class="thead-themed">
                        <tr>
                            <th>#</th>
                            <th>Instrument</th>
                            <th>Name</th>
                            <th>Type</th>
                            <th>From Currency</th>
                            <th>To Currency</th>
                            <th class="text-right">Min Trade</th>
                            <th class="text-right">Max Trade</th>
                            <th class="text-right">Price Sig. Digits</th>
                            <th>Status</th>
                            <th>Action</th>
                            <th>Last Synced</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

@stop

@section('scripts')
    <script>
        $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } });

        let instrumentsTable;

        function instrumentFilterParams(d) {
            d.currency_code = $('#filter-currency').val();
            d.instrument_type = $('#filter-instrument-type').val();
            d.portal_status = $('#filter-portal-status').val();
            d.asset_type = $('#filter-asset-type').val();
        }

        function reloadInstrumentsTable() {
            if (instrumentsTable) {
                instrumentsTable.ajax.reload(null, false);
            }
        }

        function syncInstruments() {
            $('#sync-button').prop('disabled', true);
            $('#sync-message').html('').removeClass('alert alert-danger alert-success');

            $.ajax({
                url: "{{ $gridUrls['sync'] }}",
                method: 'GET',
                global: false,
                success: function (res) {
                    if (!res.success) {
                        $('#sync-message').html(res.message || 'Sync failed.').addClass('alert alert-danger');
                        $('#sync-button').prop('disabled', false);
                        return;
                    }
                    $('#sync-message').html('Instruments synced successfully.').addClass('alert alert-success');
                    reloadInstrumentsTable();
                    $('#sync-button').prop('disabled', false);
                },
                error: function (xhr) {
                    const res = xhr.responseJSON || {};
                    $('#sync-message').html(res.message || res.error || 'Sync failed.').addClass('alert alert-danger');
                    $('#sync-button').prop('disabled', false);
                }
            });
        }

        function toggleStatus(id) {
            const $btn = $('#toggle-' + id);
            $btn.prop('disabled', true);

            $.ajax({
                url: "{{ $gridUrls['togglePrefix'] }}/" + id + "/toggle-status",
                method: 'POST',
                global: false,
                success: function (res) {
                    if (!res.success) {
                        $btn.prop('disabled', false);
                        return;
                    }
                    if (res.is_active) {
                        $('#status-' + id).html('<span class="badge badge-success">Active</span>');
                        $btn.removeClass('btn-outline-success').addClass('btn-outline-danger').text('Disable');
                    } else {
                        $('#status-' + id).html('<span class="badge badge-secondary">Inactive</span>');
                        $btn.removeClass('btn-outline-danger').addClass('btn-outline-success').text('Enable');
                    }
                    $btn.prop('disabled', false);
                },
                error: function () {
                    $btn.prop('disabled', false);
                }
            });
        }

        function initInstrumentFilterSelect2() {
            const base = { width: '100%', allowClear: true, minimumResultsForSearch: 0 };
            $('#filter-currency').select2({ ...base, placeholder: 'All enabled' });
            $('#filter-instrument-type').select2({ ...base, placeholder: 'All types' });
            $('#filter-portal-status').select2({ ...base, placeholder: 'All statuses' });
            $('#filter-asset-type').select2({ ...base, placeholder: 'Any leg' });
        }

        $(function () {
            initInstrumentFilterSelect2();

            instrumentsTable = $('#instruments-table').DataTable({
                processing: true,
                serverSide: true,
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                responsive: true,
                autoWidth: false,
                order: [[6, 'desc']],
                ajax: {
                    url: "{{ $gridUrls['data'] }}",
                    data: instrumentFilterParams,
                    global: false,
                },
                columns: [
                    { data: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'instrument', name: 'instrument' },
                    { data: 'name', name: 'name', defaultContent: '—' },
                    { data: 'instrument_type', name: 'instrument_type', defaultContent: '—' },
                    { data: 'from_currency_label', name: 'from_currency' },
                    { data: 'to_currency_label', name: 'to_currency' },
                    { data: 'min_trade_fmt', name: 'min_trade', className: 'text-right' },
                    { data: 'max_trade_fmt', name: 'max_trade', className: 'text-right' },
                    { data: 'price_sig_fmt', name: 'price_sig', className: 'text-right' },
                    { data: 'status_badge', name: 'portal_status', orderable: true, searchable: false },
                    { data: 'action_btn', orderable: false, searchable: false },
                    { data: 'last_synced', name: 'last_synced' },
                ],
            });

            $('#filter-currency, #filter-instrument-type, #filter-portal-status, #filter-asset-type').on('change', reloadInstrumentsTable);
            $('#filter-reset').on('click', function () {
                $('#filter-currency, #filter-instrument-type, #filter-portal-status, #filter-asset-type')
                    .val('').trigger('change');
                reloadInstrumentsTable();
            });
        });
    </script>
@stop
