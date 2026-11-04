@extends('layouts.cp')
@section('title', 'Crypto Transaction Request List - Crypto Management')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Crypto Management</span>
        </h1>
    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false"
        data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                Crypto Transaction Request List
            </h2>
        </div>

        <div class="panel-container show mb-3">
            <div class="panel-content" style="overflow: scroll;">
                @include('common.error-message')

                <div class="row mb-3">
                    <div class="col-md-2 mb-2">
                        <label class="form-label">Mode</label>
                        <select id="filter-mode" class="form-control form-control-sm">
                            <option value="all">All</option>
                            @foreach ($modeOptions ?? [] as $mode)
                                <option value="{{ $mode }}">{{ $mode }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <label class="form-label">Portal</label>
                        <select id="filter-portal" class="form-control form-control-sm">
                            <option value="all">All</option>
                            @foreach ($portalOptions ?? [] as $portal)
                                <option value="{{ $portal }}">{{ ucwords(str_replace('_', ' ', $portal)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <label class="form-label">Status</label>
                        <select id="filter-status" class="form-control form-control-sm">
                            <option value="all">{{ $statusAllLabel ?? 'All in progress' }}</option>
                            @foreach ($statusOptions ?? [] as $status)
                                <option value="{{ $status }}" @if ($status === ($defaultStatus ?? 'Pending')) selected @endif>{{ $status }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <label class="form-label">From</label>
                        <input type="date" id="filter-date-from" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-2 mb-2">
                        <label class="form-label">To</label>
                        <input type="date" id="filter-date-to" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-2 mb-2 d-flex align-items-end">
                        <button type="button" id="btn-reset-pending-filters" class="btn btn-sm btn-outline-secondary">Reset</button>
                    </div>
                </div>

                <table class="table table-bordered table-hover m-0 shadow" id="data-grid" style="width:100%">
                    <thead class="thead-themed">
                        <tr>
                            <th width="30"></th>
                            <th>Request ID</th>
                            <th>Debit Account</th>
                            <th>Credit Account / Wallet Address</th>
                            <th>Mode</th>
                            <th>Crypto Amount</th>
                            <th>Creator</th>
                            <th>Portal</th>
                            <th>Request Date</th>
                            <th>Status</th>
                            @can('delete records')
                                <th width="30" align="center"></th>
                            @endcan
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <form method="POST" name="delete-transaction" id="delete-transaction"
        action="{{ url('/crypto-management/crypto-pending-transaction/delete') }}">
        @csrf
        <input type="hidden" name="id" id="id" value="">
    </form>

@stop
@section('scripts')

    <script>
        const canDeleteDocuments = @json(auth()->user()->can('delete records'));
        const defaultStatus = @json($defaultStatus ?? 'Pending');

        $(document).ready(function() {
            function filterParams() {
                return {
                    filter_mode: $('#filter-mode').val(),
                    filter_portal: $('#filter-portal').val(),
                    filter_status: $('#filter-status').val(),
                    filter_date_from: $('#filter-date-from').val(),
                    filter_date_to: $('#filter-date-to').val(),
                };
            }

            let columns = [{
                    data: 'view',
                    orderable: false,
                    searchable: false,
                    className: 'text-center'
                },
                {
                    data: 'id',
                    name: 'account_transaction_requests.id'
                },
                {
                    data: 'debit_account',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'credit_or_wallet',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'transaction_nature',
                    name: 'account_transaction_requests.transaction_nature'
                },
                {
                    data: 'crypto_amount_fmt',
                    name: 'account_transaction_requests.crypto_amount'
                },
                {
                    data: 'creator_name',
                    name: 'creator_user.name',
                    defaultContent: ''
                },
                {
                    data: 'portal',
                    name: 'account_transaction_requests.crypto_system',
                    searchable: false
                },
                {
                    data: 'request_date',
                    name: 'account_transaction_requests.transaction_date'
                },
                {
                    data: 'status_badge',
                    name: 'account_transaction_requests.status',
                    searchable: false
                }
            ];

            if (canDeleteDocuments) {
                columns.push({
                    data: 'actions',
                    orderable: false,
                    searchable: false,
                    className: 'text-center'
                });
            }

            const table = $('#data-grid').DataTable({
                processing: true,
                serverSide: true,
                pageLength: 25,
                order: [[1, 'desc']],
                ajax: {
                    url: "{{ url('/crypto-management/crypto-pending-transactions') }}",
                    data: function(d) {
                        Object.assign(d, filterParams());
                    }
                },
                columns: columns
            });

            $('#filter-mode, #filter-portal, #filter-status, #filter-date-from, #filter-date-to').on('change', function() {
                table.ajax.reload();
            });

            $('#btn-reset-pending-filters').on('click', function() {
                $('#filter-mode').val('all');
                $('#filter-portal').val('all');
                $('#filter-status').val(defaultStatus);
                $('#filter-date-from').val('');
                $('#filter-date-to').val('');
                table.ajax.reload();
            });
        });

        function deleteTransaction(id) {

            Swal.fire({
                title: "Are you sure?",
                text: "Transaction Request will be deleted permanently",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Yes, delete it!"
            }).then(function(result) {
                if (result.value) {
                    $("#id").val(id);
                    document.getElementById('delete-transaction').submit()
                }
            });
        }
    </script>
@stop
