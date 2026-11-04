@extends('layouts.cp')
@section('title', 'Deposit Address Management')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Deposit Address Management</span>
        </h1>
            @can('add crypto transaction')
              @if (in_array('Bitpace', $enabledModules))
                @can('bitpace management')
                <a href="{{ url('/crypto-management/crypto-transactions/bitpace/new-address') }}" class="btn btn-primary mr-3"><i
                    class="fal fa-plus-circle"></i> Add New Deposit Address (Bitpace)</a>
                @endcan
            @endif
            
             @if (in_array('Fireblocks', $enabledModules))
                @can('fireblocks management')
                <a href="{{ url('/fireblocks/new-deposit-address') }}" class="btn btn-primary mr-3"><i
                    class="fal fa-plus-circle"></i> Add New Deposit Address (Fireblocks)</a>
            @endcan
            @endif
            <!-- <a href="{{ url('/crypto-management/crypto-transactions/new/buy') }}" class="btn btn-primary mr-3"><i
                                                            class="fal fa-plus-circle"></i> Buy </a>
                                                    <a href="{{ url('/crypto-management/crypto-transactions/new/sell') }}" class="btn btn-primary mr-3"><i
                                                            class="fal fa-plus-circle"></i> Sell </a>
                                                    <a href="{{ url('/crypto-management/crypto-transactions/new/withdraw') }}" class="btn btn-primary mr-3"><i
                                                            class="fal fa-plus-circle"></i> Withdraw </a>
                                                    <a href="{{ url('/crypto-management/crypto-transactions/new/deposit') }}" class="btn btn-primary mr-3"><i
                                                            class="fal fa-plus-circle"></i> Deposit </a> -->
        @endcan
    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false"
        data-panel-color="false" data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                Address List
            </h2>

        </div>

        <div class="panel-container show mb-3">
            <div class="panel-content" style="overflow: scroll;">
                @include('common.error-message')

                <div class="row mb-3">
                    <div class="col-md-2 mb-2">
                        <label class="form-label">Provider</label>
                        <select id="filter-provider" class="form-control form-control-sm">
                            <option value="all">All</option>
                            @foreach ($providerOptions ?? [] as $provider)
                                <option value="{{ $provider }}">{{ ucwords($provider) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <label class="form-label">Network</label>
                        <select id="filter-network" class="form-control form-control-sm">
                            <option value="all">All</option>
                            @foreach ($networkOptions ?? [] as $network)
                                <option value="{{ $network }}">{{ $network }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <label class="form-label">Currency</label>
                        <select id="filter-currency" class="form-control form-control-sm">
                            <option value="all">All</option>
                            @foreach ($currencyOptions ?? [] as $currency)
                                <option value="{{ $currency }}">{{ $currency }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <label class="form-label">Client Type</label>
                        <select id="filter-type" class="form-control form-control-sm">
                            <option value="all">All</option>
                            @foreach ($typeOptions ?? [] as $type)
                                <option value="{{ $type }}">{{ ucwords($type) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 mb-2 d-flex align-items-end">
                        <button type="button" id="btn-reset-address-filters" class="btn btn-sm btn-outline-secondary">Reset</button>
                    </div>
                </div>

                <table class="table table-bordered table-hover m-0 shadow" id="data-grid" style="width:100%">
                    <thead class="thead-themed primary-700">
                        <tr>

                            <th>Id</th>
                            <th>Destination tag / Address</th>
                            <th>Code</th>
                            <th>Network</th>
                             <th>Provider</th>
                              <th>Client ID</th>
                            <th>Account ID</th>
                            <th>Title</th>
                            <th>Currency Name</th>

                            <th>Client Type</th>
                            

                            <th width="30" align="center"></th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <form method="POST" name="delete-transaction" id="delete-transaction"
        action="{{ url('/crypto-management/crypto-transactions/crypto-management/crypto-transactions/delete') }}">
        @csrf
        <input type="hidden" name="trn_id" id="trn_id" value="">
    </form>

@stop
@section('scripts')
    <script>
        $(document).ready(function() {
            function filterParams() {
                return {
                    filter_provider: $('#filter-provider').val(),
                    filter_network: $('#filter-network').val(),
                    filter_currency: $('#filter-currency').val(),
                    filter_type: $('#filter-type').val(),
                };
            }

            const table = $('#data-grid').DataTable({
                processing: true,
                serverSide: true,
                pageLength: 25,
                order: [[0, 'desc']],
                ajax: {
                    url: "{{ url('/crypto-management/crypto-transactions/deposit-addresses') }}",
                    data: function(d) {
                        Object.assign(d, filterParams());
                    }
                },
                columns: [{
                        data: 'id',
                        name: 'crypto_addresses.id'
                    },
                    {
                        data: 'crypto_address',
                        name: 'crypto_addresses.crypto_address',
                        render: function(data, type, row) {
                            var address = row.crypto_address || '';
                            if (row.destination_tag) {
                                address = address + '?dt=' + row.destination_tag;
                            }
                            return address;
                        }
                    },
                    {
                        data: 'short',
                        name: 'currencies.short',
                        orderable: false,
                        render: function(data, type, row) {
                            var logo = row.logo ? '<img src="' + row.logo + '" style="width:15px;"> ' : '';
                            return logo + (row.short || '');
                        }
                    },
                    {
                        data: 'network',
                        name: 'crypto_addresses.network'
                    },
                    {
                        data: 'provider',
                        name: 'crypto_addresses.provider'
                    },
                    {
                        data: 'client_id',
                        name: 'accounts.client_id'
                    },
                    {
                        data: 'account_id',
                        name: 'accounts.id'
                    },
                    {
                        data: 'title',
                        name: 'accounts.title',
                        orderable: false,
                        render: function(data, type, row) {
                            var title = (row.title || '').replace(/\n/g, '<br>');
                            return title;
                        }
                    },
                    {
                        data: 'name',
                        name: 'currencies.name'
                    },
                    {
                        data: 'type',
                        name: 'accounts.type'
                    },
                    {
                        data: '',
                        orderable: false,
                        searchable: false,
                        render: function(data, type, row) {
                            var account_id = row['account_id'];
                            var provider = (row['provider'] === 'fireblocks') ? 'fireblocks' : 'bitpace';
                            var url = '';
                            if (provider === 'fireblocks') {
                                url = '/fireblocks/new-deposit-address?ac=' + account_id;
                            } else {
                                url = '/crypto-management/crypto-transactions/' + provider + '/new-address?ac=' + account_id;
                            }

                            return '<a href="' + url + '"><i class="alert-success fal fa-eye"></i></a>';
                        },
                        className: 'text-center'
                    },
                ]
            });

            $('#filter-provider, #filter-network, #filter-currency, #filter-type').on('change', function() {
                table.ajax.reload();
            });

            $('#btn-reset-address-filters').on('click', function() {
                $('#filter-provider').val('all');
                $('#filter-network').val('all');
                $('#filter-currency').val('all');
                $('#filter-type').val('all');
                table.ajax.reload();
            });
        });
    </script>
@stop
