@extends('layouts.cp')
@section('title', 'Fireblocks ' . $currency . ' Address Management')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Fireblocks {{ $currency }} Address Management</span>
        </h1>
        <a href="{{ url('/fireblocks/vaults') }}" class="btn btn-primary mr-3"><i class="fal fa-arrow-alt-left"></i> Go Back </a>
    </div>

    <!-- Create Address Panel -->
    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false"
        data-panel-color="false" data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                Create New {{ $currency }} Deposit Address
            </h2>
        </div>

        <div class="panel-container show mb-3">
            <div class="panel-content">
                @include('common.error-message')

                <form method="post" id="create-address-form" action="{{ url('/fireblocks/vaults/' . $currency . '/deposit-addresses') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label" for="account_id">Client Account</label>
                                <select name="account_id" id="account_id" class="form-control select2 w-100">
                                    <option value="">Choose Account (Optional)</option>
                                    @foreach ($accounts as $account)
                                        <option value="{{ $account->id }}">{{ $account->title . ' - ' . $account->account_number }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label" for="description">Description</label>
                                <input type="text" name="description" id="description" class="form-control" placeholder="e.g. Customer deposit address">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label" for="customer_ref_id">Customer Ref ID</label>
                                <input type="text" name="customer_ref_id" id="customer_ref_id" class="form-control" placeholder="e.g. ref-12345">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary" id="create_address_btn">
                                    <i class="fal fa-plus-circle"></i> Create Address
                                </button>
                            </div>
                        </div>
                    </div>
                </form>

                @if (session('success_address'))
                    <div class="alert alert-success mt-3">
                        <strong>Address Created:</strong> {{ session('success_address') }}
                        @if (session('success_tag'))
                            <br><strong>Tag:</strong> {{ session('success_tag') }}
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Address List Panel -->
    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false"
        data-panel-color="false" data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                {{ $currency }} Address List
            </h2>
        </div>

        <div class="panel-container show mb-3">
            <div class="panel-content" style="overflow: scroll;">
                <table class="table table-bordered table-hover m-0 shadow" id="data-grid">
                    <thead class="thead-themed ">
                        <tr>
                            <th>Id</th>
                            <th>Address</th>
                            <th>Destination Tag</th>
                            <th>Network</th>
                            <th>Account ID</th>
                            <th>Description / Alias</th>
                            <th>Customer Ref ID</th>
                            <th>Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@stop

@section('scripts')
    <script>
        $(document).ready(function() {
            $('#data-grid').DataTable({
                processing: true,
                serverSide: false,
                ajax: {
                    url: "{{ url('/fireblocks/deposit-addresses') }}",
                    data: {
                        vault_account_id: '{{ $vaultAccountId }}',
                        asset_id: '{{ $assetId }}'
                    },
                    dataSrc: 'data'
                },
                columns: [
                    { data: 'id' },
                    { data: 'crypto_address' },
                    { data: 'destination_tag', defaultContent: '' },
                    { data: 'network' },
                    { data: 'account_id', defaultContent: '' },
                    { data: 'address_alias', defaultContent: '' },
                    { data: 'ged_external_id', defaultContent: '' },
                    { data: 'created_at' }
                ]
            });
        });
    </script>
@stop
