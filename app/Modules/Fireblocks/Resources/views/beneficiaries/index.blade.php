@extends('layouts.cp')
@section('title', 'Fireblocks Beneficiaries')

@section('content')
    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Fireblocks Beneficiaries</span>
        </h1>
        <a href="{{ url('/fireblocks/beneficiaries/create') }}" class="btn btn-primary mr-3">
            <i class="fal fa-plus-circle"></i> Add Beneficiary
        </a>
    </div>

    <div class="panel">
        <div class="panel-hdr">
            <h2>Whitelisted Fireblocks Beneficiaries</h2>
        </div>
        <div class="panel-container show">
            <div class="panel-content">
                @include('common.error-message')

                <div class="row mb-3">
                    <div class="col-md-2">
                        <label class="form-label" for="filter-currency">Currency</label>
                        <select id="filter-currency" class="form-control filter-select w-100">
                            <option value="">All enabled</option>
                            @foreach ($activeCurrencies as $currency)
                                <option value="{{ $currency->id }}">{{ $currency->short }} — {{ $currency->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="filter-asset">Asset</label>
                        <select id="filter-asset" class="form-control filter-select-ajax w-100"></select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="filter-fb-status">Fireblocks status</label>
                        <select id="filter-fb-status" class="form-control filter-select w-100">
                            <option value="">All</option>
                            @foreach ($fbAssetStatuses as $status)
                                <option value="{{ $status }}">{{ $status }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="filter-internal-status">Status</label>
                        <select id="filter-internal-status" class="form-control filter-select w-100">
                            <option value="">All</option>
                            <option value="Pending">Pending</option>
                            <option value="Approved">Approved</option>
                            <option value="Rejected">Rejected</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="filter-risk">CA risk</label>
                        <select id="filter-risk" class="form-control filter-select w-100">
                            <option value="">All</option>
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                            <option value="severe">Severe</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="filter-ben-type">Beneficiary type</label>
                        <select id="filter-ben-type" class="form-control filter-select w-100">
                            <option value="">All</option>
                            <option value="INDIVIDUAL">Individual</option>
                            <option value="BUSINESS">Business</option>
                        </select>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-12 text-right">
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="filter-reset">
                            <i class="fal fa-undo"></i> Reset filters
                        </button>
                    </div>
                </div>

                <table class="table table-bordered table-hover m-0 shadow w-100" id="beneficiaries-table">
                    <thead class="thead-themed">
                        <tr>
                            <th>ID</th>
                            <th>Fireblocks ID</th>
                            <th>Beneficiary Name</th>
                            <th>From Account</th>
                            <th>Currency</th>
                            <th>Asset</th>
                            <th>Wallet Address</th>
                            <th>CA Status</th>
                            <th>FB Status</th>
                            <th>Status</th>
                            <th width="30">View</th>
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
        let beneficiariesTable;

        function beneficiaryFilterParams(d) {
            d.currency_id = $('#filter-currency').val();
            d.asset_id = $('#filter-asset').val();
            d.fb_asset_status = $('#filter-fb-status').val();
            d.internal_status = $('#filter-internal-status').val();
            d.compliance_risk = $('#filter-risk').val();
            d.beneficiary_type = $('#filter-ben-type').val();
        }

        function reloadBeneficiariesTable() {
            if (beneficiariesTable) {
                beneficiariesTable.ajax.reload(null, false);
            }
        }

        function initBeneficiaryFilterSelects() {
            const base = { width: '100%', allowClear: true };
            $('#filter-currency').select2({ ...base, placeholder: 'All enabled' });
            $('#filter-fb-status').select2({ ...base, placeholder: 'All Fireblocks statuses' });
            $('#filter-internal-status').select2({ ...base, placeholder: 'All statuses' });
            $('#filter-risk').select2({ ...base, placeholder: 'All CA risks' });
            $('#filter-ben-type').select2({ ...base, placeholder: 'All types' });

            $('#filter-asset').select2({
                width: '100%',
                allowClear: true,
                placeholder: 'All assets',
                minimumInputLength: 0,
                ajax: {
                    url: "{{ url('/fireblocks/beneficiaries/assets-suggest') }}",
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return { q: params.term || '' };
                    },
                    processResults: function (data) {
                        return { results: data.results || [] };
                    },
                    cache: true,
                },
            });
        }

        function resetBeneficiaryFilters() {
            $('#filter-currency, #filter-fb-status, #filter-internal-status, #filter-risk, #filter-ben-type')
                .val('').trigger('change');
            $('#filter-asset').val(null).trigger('change');
            reloadBeneficiariesTable();
        }

        $(function () {
            initBeneficiaryFilterSelects();

            beneficiariesTable = $('#beneficiaries-table').DataTable({
                processing: true,
                serverSide: true,
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                responsive: true,
                autoWidth: false,
                order: [[0, 'desc']],
                ajax: {
                    url: "{{ url('/fireblocks/beneficiaries/data') }}",
                    data: beneficiaryFilterParams,
                    global: false,
                },
                columns: [
                    { data: 'id', name: 'id' },
                    { data: 'fireblocks_wallet_id', name: 'fireblocks_wallet_id' },
                    { data: 'beneficiary_name', name: 'beneficiary_name' },
                    { data: 'account_label', name: 'account_title' },
                    { data: 'currency_short', name: 'currency_short' },
                    { data: 'asset_label', name: 'network_code' },
                    { data: 'wallet_cell', name: 'wallet_address', orderable: false },
                    { data: 'ca_status_cell', name: 'ca_status', orderable: false },
                    { data: 'fb_asset_status', name: 'fb_asset_status' },
                    { data: 'portal_status_badge', name: 'portal_status', searchable: false },
                    { data: 'view_action', orderable: false, searchable: false, className: 'text-center' },
                ],
            });

            $('#filter-currency, #filter-asset, #filter-fb-status, #filter-internal-status, #filter-risk, #filter-ben-type')
                .on('change', reloadBeneficiariesTable);
            $('#filter-reset').on('click', resetBeneficiaryFilters);
        });
    </script>
@stop
