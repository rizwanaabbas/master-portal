@extends('layouts.cp')
@section('title', $providerLabel . ' Balances - Crypto Management')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Crypto Management</span> &mdash; {{ $providerLabel }} Balances
        </h1>
    </div>

    <div class="panel">
        <div class="panel-hdr">
            <h2>Balances</h2>
            <div class="panel-toolbar">
                <button type="button" class="btn btn-primary btn-sm" id="sync-button" onclick="syncBalances()">
                    <i class="fal fa-sync"></i> Sync {{ $providerLabel }} Balances
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
                    <div class="col-md-3">
                        <label class="form-label" for="filter-asset-type">Type</label>
                        <select id="filter-asset-type" class="form-control w-100">
                            <option value="">All types</option>
                            <option value="crypto">Crypto</option>
                            <option value="fiat">Fiat</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <div class="custom-control custom-checkbox mb-2">
                            <input type="checkbox" class="custom-control-input" id="filter-show-all-available">
                            <label class="custom-control-label" for="filter-show-all-available">Show all provider currencies</label>
                        </div>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <div class="custom-control custom-checkbox mb-2">
                            <input type="checkbox" class="custom-control-input" id="filter-hide-zero">
                            <label class="custom-control-label" for="filter-hide-zero">Hide zero balances</label>
                        </div>
                    </div>
                    <div class="col-md-12 d-flex align-items-end justify-content-md-end mb-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="filter-reset">
                            <i class="fal fa-undo"></i> Reset filters
                        </button>
                    </div>
                </div>

                <table id="balances-table" class="table table-bordered table-hover table-striped w-100">
                    <thead class="thead-themed">
                        <tr>
                            <th>#</th>
                            <th>Currency</th>
                            <th>Name</th>
                            <th>Code</th>
                            <th class="text-right">Amount</th>
                            <th class="text-right">Hold</th>
                            <th class="text-right">Available</th>
                            <th>Last Synced</th>
                            <th id="th-currency-status" style="display: none;">Portal status</th>
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

        let balancesTable;

        const balanceCurrencyOptions = @json($balanceCurrencies);

        function balanceFilterParams(d) {
            d.currency_code = $('#filter-currency').val();
            d.asset_type = $('#filter-asset-type').val();
            d.hide_zero = $('#filter-hide-zero').is(':checked') ? 1 : 0;
            d.show_all_available = $('#filter-show-all-available').is(':checked') ? 1 : 0;
        }

        function toggleCurrencyStatusColumn(show) {
            const table = balancesTable;
            if (!table) {
                return;
            }
            const col = table.column('.currency-status-col');
            col.visible(show);
            $('#th-currency-status').toggle(show);
        }

        function rebuildCurrencyFilterOptions(showAll) {
            const $select = $('#filter-currency');
            const current = $select.val();
            $select.empty().append('<option value="">' + (showAll ? 'All provider currencies' : 'All enabled') + '</option>');

            if (showAll) {
                balanceCurrencyOptions.forEach(function (code) {
                    $select.append('<option value="' + code + '">' + code + '</option>');
                });
            } else {
                @foreach ($activeCurrencies as $currency)
                    $select.append('<option value="{{ $currency->short }}">{{ $currency->short }} — {{ $currency->name }}</option>');
                @endforeach
            }

            if (current) {
                $select.val(current);
            }
            $select.trigger('change.select2');
        }

        function reloadBalancesTable() {
            if (balancesTable) {
                balancesTable.ajax.reload(null, false);
            }
        }

        function syncBalances() {
            $('#sync-button').prop('disabled', true);
            $('#sync-message').html('').removeClass('alert alert-danger alert-success');

            $.ajax({
                url: "{{ $gridUrls['sync'] }}",
                method: 'GET',
                success: function (res) {
                    if (!res.success) {
                        $('#sync-message').html(res.message || 'Sync failed.').addClass('alert alert-danger');
                        $('#sync-button').prop('disabled', false);
                        return;
                    }
                    $('#sync-message').html('Balances synced successfully.').addClass('alert alert-success');
                    reloadBalancesTable();
                    $('#sync-button').prop('disabled', false);
                },
                error: function (xhr) {
                    const res = xhr.responseJSON || {};
                    $('#sync-message').html(res.message || res.error || 'Sync failed.').addClass('alert alert-danger');
                    $('#sync-button').prop('disabled', false);
                }
            });
        }

        function initBalanceFilterSelect2() {
            const base = { width: '100%', allowClear: true, minimumResultsForSearch: 0 };
            $('#filter-currency').select2({ ...base, placeholder: 'All enabled' });
            $('#filter-asset-type').select2({ ...base, placeholder: 'All types' });
        }

        $(function () {
            initBalanceFilterSelect2();

            balancesTable = $('#balances-table').DataTable({
                processing: true,
                serverSide: true,
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                responsive: true,
                autoWidth: false,
                order: [[4, 'desc']],
                ajax: {
                    url: "{{ $gridUrls['data'] }}",
                    data: balanceFilterParams,
                    global: false,
                },
                columns: [
                    { data: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'currency', name: 'currency' },
                    { data: 'name', name: 'name' },
                    { data: 'code', name: 'code' },
                    { data: 'amount_fmt', name: 'amount', className: 'text-right' },
                    { data: 'hold_fmt', name: 'hold', className: 'text-right' },
                    { data: 'available_fmt', name: 'available', className: 'text-right' },
                    { data: 'last_synced', name: 'last_synced' },
                    { data: 'currency_status', name: 'currency_status', className: 'currency-status-col', visible: false, orderable: true, searchable: false },
                ],
            });

            $('#filter-currency, #filter-asset-type').on('change', reloadBalancesTable);
            $('#filter-hide-zero').on('change', reloadBalancesTable);
            $('#filter-show-all-available').on('change', function () {
                const showAll = $(this).is(':checked');
                rebuildCurrencyFilterOptions(showAll);
                toggleCurrencyStatusColumn(showAll);
                reloadBalancesTable();
            });
            $('#filter-reset').on('click', function () {
                $('#filter-currency, #filter-asset-type').val('').trigger('change');
                $('#filter-hide-zero').prop('checked', false);
                $('#filter-show-all-available').prop('checked', false);
                rebuildCurrencyFilterOptions(false);
                toggleCurrencyStatusColumn(false);
                reloadBalancesTable();
            });
        });

    </script>
@stop
