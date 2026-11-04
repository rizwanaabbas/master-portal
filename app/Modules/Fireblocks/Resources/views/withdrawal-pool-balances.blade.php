@extends('layouts.cp')
@section('title', $isOmnibus ? 'Fireblocks Omnibus Balances' : 'Fireblocks Withdrawal Pool Balances')

@section('content')
    <div class="subheader">
        <h1 class="subheader-title">
            <span class="fw-300">Fireblocks</span> &mdash; {{ $isOmnibus ? 'Omnibus Balances' : 'Withdrawal Pool Balances' }}
        </h1>
    </div>

    <div class="panel">
        <div class="panel-hdr">
            <h2>{{ $isOmnibus ? 'Omnibus vault assets' : 'Withdrawal pool assets' }}</h2>
            <div class="panel-toolbar">
                @can('manage fireblocks withdrawal pool balances')
                <button type="button" class="btn btn-primary btn-sm" id="sync-button" onclick="syncPoolBalances()">
                    <i class="fal fa-sync"></i> Sync Fireblocks Balances
                </button>
                @endcan
            </div>
        </div>

        <div class="panel-container show">
            <div class="panel-content">
                <p class="text-muted mb-3">
                    @if ($isOmnibus)
                        Balances fetched from Omnibus Vault ID
                        <code>{{ $configuredOmnibusVaultId }}</code>.
                        Enabled portal assets on that Fireblocks vault.
                    @else
                        Wallet pool balances for vault accounts tagged to the module
                        <strong>Wallet Pool ID</strong>@if(!empty($configuredWalletPoolId)) (<code>{{ $configuredWalletPoolId }}</code>)@else (not configured)@endif.
                    @endif
                </p>
                <div id="sync-message"></div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label" for="filter-vault">Vault account</label>
                        <select id="filter-vault" class="form-control w-100">
                            <option value="">All vaults</option>
                            @foreach ($vaultIds as $vaultId)
                                <option value="{{ $vaultId }}">{{ $vaultId }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <div class="custom-control custom-checkbox mb-2">
                            <input type="checkbox" class="custom-control-input" id="filter-hide-zero">
                            <label class="custom-control-label" for="filter-hide-zero">Hide zero balances</label>
                        </div>
                    </div>
                    <div class="col-md-4 d-flex align-items-end justify-content-md-end">
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="filter-reset">
                            <i class="fal fa-undo"></i> Reset filters
                        </button>
                    </div>
                </div>

                <table id="pool-balances-table" class="table table-bordered table-hover table-striped w-100">
                    <thead class="thead-themed">
                        <tr>
                            <th>#</th>
                            <th>Vault ID</th>
                            <th>Network asset</th>
                            <th class="text-right">Total</th>
                            <th class="text-right">Available</th>
                            <th class="text-right">Pending</th>
                            <th>Last synced</th>
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

        let poolBalancesTable;

        function poolFilterParams(d) {
            d.vault_account_id = $('#filter-vault').val();
            d.hide_zero = $('#filter-hide-zero').is(':checked') ? 1 : 0;
            d.balance_source = @json($balanceSource);
        }

        function reloadPoolTable() {
            if (poolBalancesTable) {
                poolBalancesTable.ajax.reload(null, false);
            }
        }

        function syncPoolBalances() {
            $('#sync-button').prop('disabled', true);
            $('#sync-message').html('').removeClass('alert alert-danger alert-success');

            $.ajax({
                url: "{{ $isOmnibus ? url('/fireblocks/omnibus-balances/sync') : url('/fireblocks/withdrawal-pool-balances/sync') }}",
                method: 'POST',
                success: function (res) {
                    if (!res.success) {
                        let msg = res.message || 'Sync failed.';
                        if (res.errors && res.errors.length) {
                            msg += ' ' + res.errors.slice(0, 3).join(' ');
                        }
                        $('#sync-message').html(msg).addClass('alert alert-danger');
                        $('#sync-button').prop('disabled', false);
                        return;
                    }
                    $('#sync-message').html(res.message || 'Balances synced successfully.').addClass('alert alert-success');
                    reloadPoolTable();
                    $('#sync-button').prop('disabled', false);
                },
                error: function (xhr) {
                    const res = xhr.responseJSON || {};
                    $('#sync-message').html(res.message || res.error || 'Sync failed.').addClass('alert alert-danger');
                    $('#sync-button').prop('disabled', false);
                }
            });
        }

        $(function () {
            $('#filter-vault').select2({ width: '100%', allowClear: true, placeholder: 'All vaults' });

            poolBalancesTable = $('#pool-balances-table').DataTable({
                processing: true,
                serverSide: true,
                pageLength: 25,
                order: [[3, 'desc']],
                ajax: {
                    url: "{{ $isOmnibus ? url('/fireblocks/omnibus-balances/data') : url('/fireblocks/withdrawal-pool-balances/data') }}",
                    data: poolFilterParams,
                },
                columns: [
                    { data: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'vault_account_id', name: 'vault_account_id' },
                    { data: 'network_asset', name: 'network_asset', orderable: false, searchable: true },
                    { data: 'total_fmt', name: 'total', className: 'text-right' },
                    { data: 'available_fmt', name: 'available', className: 'text-right' },
                    { data: 'pending_fmt', name: 'pending', className: 'text-right' },
                    { data: 'last_synced', name: 'last_synced' },
                ],
            });

            $('#filter-vault, #filter-hide-zero').on('change', reloadPoolTable);
            $('#filter-reset').on('click', function () {
                $('#filter-vault').val('').trigger('change');
                $('#filter-hide-zero').prop('checked', false);
                reloadPoolTable();
            });
        });
    </script>
@stop
