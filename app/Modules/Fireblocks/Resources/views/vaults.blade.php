@extends('layouts.cp')
@section('title', 'Client vaults')

@section('content')
    <div class="subheader">
        <h1 class="subheader-title"><span class='fw-300'>Client vaults</span></h1>
    </div>

    <div class="panel">
        <div class="panel-hdr"><h2>Vault Accounts</h2></div>
        <div class="panel-container show">
            <div class="panel-content">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label" for="filter-client">Client</label>
                        <select id="filter-client" class="form-control w-100"></select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <div class="custom-control custom-checkbox mb-2">
                            <input type="checkbox" class="custom-control-input" id="filter-has-addresses">
                            <label class="custom-control-label" for="filter-has-addresses">Has deposit addresses only</label>
                        </div>
                    </div>
                    <div class="col-md-4 d-flex align-items-end justify-content-md-end mb-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="filter-reset">
                            <i class="fal fa-undo"></i> Reset filters
                        </button>
                    </div>
                </div>

                <table class="table table-bordered table-hover w-100" id="vaults-table">
                    <thead class="thead-themed">
                        <tr>
                            <th>#</th>
                            <th>Vault ID</th>
                            <th>Title</th>
                            <th>Client</th>
                            <th class="text-right">Deposit Addresses</th>
                            <th>Created</th>
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
        let vaultsTable;

        function vaultFilterParams(d) {
            d.client_id = $('#filter-client').val();
            d.has_addresses = $('#filter-has-addresses').is(':checked') ? 1 : 0;
        }

        function reloadVaultsTable() {
            if (vaultsTable) {
                vaultsTable.ajax.reload(null, false);
            }
        }

        $(function () {
            $('#filter-client').select2({
                width: '100%',
                allowClear: true,
                placeholder: 'All clients',
                minimumInputLength: 0,
                ajax: {
                    url: "{{ url('/fireblocks/vaults/clients-suggest') }}",
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

            vaultsTable = $('#vaults-table').DataTable({
                processing: true,
                serverSide: true,
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                responsive: true,
                autoWidth: false,
                order: [[5, 'desc']],
                ajax: {
                    url: "{{ url('/fireblocks/vaults/data') }}",
                    data: vaultFilterParams,
                    global: false,
                },
                columns: [
                    { data: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'vault_id', name: 'vault_id' },
                    { data: 'vault_title', name: 'vault_title' },
                    { data: 'client_name', name: 'client_name' },
                    { data: 'address_count', name: 'address_count', className: 'text-right' },
                    { data: 'created_fmt', name: 'created_at' },
                ],
            });

            $('#filter-client').on('change', reloadVaultsTable);
            $('#filter-has-addresses').on('change', reloadVaultsTable);
            $('#filter-reset').on('click', function () {
                $('#filter-client').val(null).trigger('change');
                $('#filter-has-addresses').prop('checked', false);
                reloadVaultsTable();
            });
        });
    </script>
@stop
