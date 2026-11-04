@extends('layouts.cp')
@section('title', 'Crypto Transactions List - Crypto Management')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Crypto Management</span>
        </h1>
        <div class="d-flex flex-wrap">
            @can('add crypto transaction')
                <!-- <a href="{{ url('/crypto-management/crypto-transactions/new/buy') }}" class="btn btn-primary mr-3"><i
                                                                                                    class="fal fa-plus-circle"></i> Buy </a>
                                                                                            <a href="{{ url('/crypto-management/crypto-transactions/new/sell') }}" class="btn btn-primary mr-3"><i
                                                                                                    class="fal fa-plus-circle"></i> Sell </a>
                                                                                            <a href="{{ url('/crypto-management/crypto-transactions/new/withdraw') }}" class="btn btn-primary mr-3"><i
                                                                                                    class="fal fa-plus-circle"></i> Withdraw </a> -->
                <a href="{{ url('/crypto-management/crypto-transactions/select-method?m=new-buy') }}"
                    class="btn btn-primary mr-3"><i class="fal fa-plus-circle"></i> Buy </a>
                <a href="{{ url('/crypto-management/crypto-transactions/select-method?m=new-sell') }}"
                    class="btn btn-primary mr-3"><i class="fal fa-plus-circle"></i> Sell </a>
                <a href="{{ url('/crypto-management/crypto-transactions/select-method?m=new-withdraw') }}"
                    class="btn btn-primary mr-3"><i class="fal fa-plus-circle"></i> Withdraw </a>

                <a href="{{ url('/crypto-management/crypto-transactions/select-method?m=new-deposit') }}"
                    class="btn btn-primary mr-3"><i class="fal fa-plus-circle"></i> Deposit </a>
            @endcan
        </div>
    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false"
        data-panel-color="false" data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                Crypto Transactions List
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
                        <label class="form-label">Type</label>
                        <select id="filter-type" class="form-control form-control-sm">
                            <option value="all">All</option>
                            @foreach ($typeOptions ?? [] as $type)
                                <option value="{{ $type }}">{{ $type }}</option>
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
                            <option value="all">All</option>
                            @foreach ($statusOptions ?? [] as $status)
                                <option value="{{ $status }}" @if ($status === 'Approved') selected @endif>{{ $status }}</option>
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
                    <div class="col-md-12 mb-2">
                        <button type="button" id="btn-reset-txn-filters" class="btn btn-sm btn-outline-secondary">Reset</button>
                    </div>
                </div>

                <table class="table table-bordered table-hover m-0 shadow" id="data-grid" style="width:100%">
                    <thead class="thead-themed">
                        <tr>
                            <th width="30"></th>
                            <th>ID</th>
                            <th>Account Title</th>
                            <th>Mode</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Date</th>
                            <th>Creator</th>
                            <th>Approver</th>
                            <th>Portal</th>
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
        action="{{ url('/crypto-management/crypto-transactions/delete') }}">
        @csrf
        <input type="hidden" name="trn_id" id="trn_id" value="">
    </form>

@stop

@php
    $isBitpaceEnabled = in_array('Bitpace', $enabledModules);
@endphp

@section('scripts')
    <script>
        const canDeleteDocuments = @json(auth()->user()->can('delete records'));
        const isBitpaceEnabled = {{ json_encode($isBitpaceEnabled) }};
        $(document).ready(function() {
            function filterParams() {
                return {
                    filter_mode: $('#filter-mode').val(),
                    filter_type: $('#filter-type').val(),
                    filter_portal: $('#filter-portal').val(),
                    filter_status: $('#filter-status').val(),
                    filter_date_from: $('#filter-date-from').val(),
                    filter_date_to: $('#filter-date-to').val(),
                };
            }

            let columns = [{
                    data: '',
                    orderable: false,
                    searchable: false,
                    render: function(data, type, row) {
                        var id = row['id'];
                        var url = "{{ url('crypto-management/crypto-transactions/edit') }}";
                        return '<a href="' + url + '/crypto-transaction/' + id +
                            '"><i class="fal fa-edit"></i></a>'
                    },
                    className: 'text-center'
                },
                {
                    data: 'id',
                    name: 'account_transactions.id'
                },
                {
                    data: 'title',
                    name: 'account.title',
                    render: function(data, type, row) {
                        var title = (row['title'] || '').replace("&amp;amp;", "&");
                        title = title.replace("&amp;", "&");
                        return title + '<br><small><i>' + (row['account_number'] || '') +
                            '</i></small>';
                    }
                },
                {
                    data: 'transaction_nature',
                    name: 'account_transactions.transaction_nature'
                },
                {
                    data: 'transaction_type',
                    name: 'account_transactions.transaction_type'
                },
                {
                    data: 'amount',
                    name: 'account_transactions.amount'
                },
                {
                    data: 'transaction_date',
                    name: 'account_transactions.transaction_date',
                    render: function(data, type, row) {
                        if (!data) {
                            return '';
                        }
                        var date = new Date(Date.parse(String(data).replace(/-/g, '/')));
                        var dd = date.toLocaleDateString('en-US', {
                            day: 'numeric'
                        });
                        var mm = date.toLocaleDateString('en-US', {
                            month: 'short'
                        });
                        var yy = date.toLocaleDateString('en-US', {
                            year: 'numeric'
                        });
                        return dd + ' ' + mm + ', ' + yy;
                    }
                },
                {
                    data: 'name',
                    name: 'user.name',
                    defaultContent: ''
                },
                {
                    data: 'approver',
                    name: 'approver_user.name',
                    defaultContent: '',
                    render: function(data, type, row) {
                        var system = (row['crypto_system'] || '').toString().toLowerCase();
                        if (system === 'fireblocks' || system === 'aquanow') {
                            return 'Automated Approval';
                        }
                        if (isBitpaceEnabled && row['approved_by'] == 0) {
                            return 'Automated Approval';
                        }
                        return data ? data : '';
                    }
                },
                {
                    data: 'crypto_system',
                    name: 'account_transactions.crypto_system',
                    render: function(data, type, row) {
                        var portal = data;
                        if (data)
                            portal = data.replace('_', ' ');
                        else
                            portal = '';
                        return '<span class="badge" style="text-transform: capitalize;">' +
                            portal + '</span>'
                    }
                },
                {
                    data: 'status',
                    name: 'account_transactions.status',
                    render: function(data, type, row) {
                        var colors = {
                            'Pending': 'badge-primary',
                            'Approved': 'badge-success',
                            'Rejected': 'badge-danger',
                            'Cancelled': 'badge-secondary',
                            'Needs Review': 'badge-warning'
                        }
                        return '<span class="badge ' + (colors[data] || 'badge-secondary') + '">' + data +
                            '</span>'
                    }
                }
            ];

            if (canDeleteDocuments) {
                columns.push({
                    data: '',
                    orderable: false,
                    searchable: false,
                    render: function(data, type, row) {
                        var id = row['id'];
                        return '<a href="javascript:deleteTransaction(' + id +
                            ')"><i class="alert-danger fal fa-times-circle rounded-circle"></i></a>'
                    },
                    className: 'text-center'
                });
            }

            const table = $('#data-grid').DataTable({
                processing: true,
                serverSide: true,
                pageLength: 25,
                order: [[1, 'desc']],
                ajax: {
                    url: "{{ url('/crypto-management/crypto-transactions') }}",
                    data: function(d) {
                        Object.assign(d, filterParams());
                    }
                },
                columns: columns
            });

            $('#filter-mode, #filter-type, #filter-portal, #filter-status, #filter-date-from, #filter-date-to').on('change', function() {
                table.ajax.reload();
            });

            $('#btn-reset-txn-filters').on('click', function() {
                $('#filter-mode').val('all');
                $('#filter-type').val('all');
                $('#filter-portal').val('all');
                $('#filter-status').val('Approved');
                $('#filter-date-from').val('');
                $('#filter-date-to').val('');
                table.ajax.reload();
            });
        });

        function deleteTransaction(id) {

            Swal.fire({
                title: "Are you sure?",
                text: "Transaction and linked Transaction, if any, will be deleted permanently.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Yes, delete it!"
            }).then(function(result) {
                if (result.value) {
                    $("#trn_id").val(id);
                    document.getElementById('delete-transaction').submit()
                }
            });
        }
    </script>
@stop
