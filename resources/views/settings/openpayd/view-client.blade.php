@extends('layouts.cp')
@section('title', 'Openpayd Data Sync')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Openpayd Data Sync</span>
        </h1>
    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false"
        data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                Openpayd Data
            </h2>
        </div>

        @php
            $accounts = collect($openpayd_clients_accounts)->map(function ($accounts) {
                return [
                    'account_title' => $accounts->friendlyName,
                    'currency' => $accounts->actualBalance->currency,
                ];
            });
        @endphp

        <div class="panel-container show mb-3">
            <div class="panel-content" style="overflow: scroll;">
                @include('common.error-message')
                {{-- <p>Openpayd data</p> --}}

                <table class="table table-bordered m-0 mb-4 table-responsive-xl">

                    <tbody class="text-left">
                        <tr>
                            <td style="font-weight: 500;">Openpayd Linked client name</td>
                            <td>
                                <span style="font-weight: 500;">
                                    {{ $openpayd_clients->clientType === 'BUSINESS' ? $openpayd_clients->friendlyName : $openpayd_clients->individual->firstName . ' ' . $openpayd_clients->individual->lastName }}
                                </span>
                            </td>
                        </tr>

                        <tr>
                            <td style="font-weight: 500;">Country</td>
                            <td>
                                <span style="font-weight: 500;">
                                    {{ $openpayd_clients->country }}
                                </span>
                            </td>
                        </tr>

                        <tr>
                            <td style="font-weight: 500;">Unsynced Accounts</td>
                            <td>
                                <div class="d-flex flex-column">
                                    <span style="font-weight: 500;">
                                        Total accounts: {{ count($accounts) }}
                                    </span>
                                    <div class="row p-1">
                                        @foreach ($accounts as $account)
                                            <div class="pt-1 col-md-4 border rounded p-2 m-1" style="font-weight: 500;">
                                                <span style="font-weight: 400;"> Account Title: </span>
                                                {{ $account['account_title'] }}
                                                <br>
                                                <span style="font-weight: 400;"> Currency: </span>
                                                {{ $account['currency'] }}
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </td>
                        </tr>

                        <tr>
                            <td style="font-weight: 500;">Beneficiaries</td>
                            <td>
                                <div class="d-flex flex-column">
                                    <span style="font-weight: 500;">
                                        Total beneficiaries: {{ count($openpayd_clients_beneficiaries) }}
                                    </span>
                                    <div class="row p-1">
                                        @foreach ($openpayd_clients_beneficiaries as $beneficiary)
                                            <div class="col-md-4 pt-1 border rounded p-2 m-1" style="font-weight: 500;">
                                                <span style="font-weight: 400;"> Beneficiary Name: </span>
                                                {{ $beneficiary->friendlyName }}
                                                <br>
                                                <span style="font-weight: 400;"> Type: </span>
                                                {{ $beneficiary->beneficiaryType }}
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </td>
                        </tr>

                        <tr>
                            <td style="font-weight: 500;">Client type</td>
                            <td>
                                {{ strtolower($openpayd_clients->clientType) }}
                            </td>
                        </tr>

                        <tr>
                            <td style="font-weight: 500;">Status</td>
                            <td>
                                <span class="badge {{ $statusBadge[strtolower($openpayd_clients->status)] }}">
                                    {{ strtolower($openpayd_clients->status) }}
                                </span>
                            </td>
                        </tr>

                        <tr>
                            <td style="font-weight: 500;">Is created in CAIB</td>
                            <td>
                                @if ($local_client)
                                    <span class="badge badge-success">Yes</span>
                                @else
                                    <span class="badge badge-danger">No</span>
                                @endif
                            </td>
                        </tr>

                        <tr>
                            <td style="font-weight: 500;">Created at</td>
                            <td>
                                {{ $openpayd_clients->createdDateTime }}
                            </td>
                        </tr>

                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false"
        data-panel-color="false" data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                CAIB Data
            </h2>
        </div>

        @php
            if (!empty($local_client)) {
                $client_id = $local_client->client_id;
                $local_client_type = $local_client->client->type;
                $client_name = $local_client->client->name;
                $local_accounts = $local_client->getAllAccounts;

                $local_accounts = collect($local_accounts)->filter(function ($account) {
                    return $account->openpayd_account_id !== null;
                });
                $local_client = json_decode($local_client->response_data);
            }
        @endphp

        <div class="panel-container show mb-3">
            <div class="panel-content" style="overflow: scroll;">
                {{-- @include('common.error-message') --}}
                {{-- <p>CAIB data</p> --}}
                @if (!empty($local_client))
                    <table class="table table-bordered m-0 mb-4 table-responsive-xl">
                        <tbody class="text-left">
                            <tr>
                                <td style="font-weight: 500;">Client name</td>
                                <td>
                                    <span style="font-weight: 500;">
                                        {{ $client_name }}
                                    </span>
                                </td>
                            </tr>

                            <tr>
                                <td style="font-weight: 500;">Country</td>
                                <td>
                                    <span style="font-weight: 500;">
                                        {{ $local_client->country }}
                                    </span>
                                </td>
                            </tr>

                            <tr>
                                <td style="font-weight: 500;">Accounts</td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <span style="font-weight: 500;">
                                            Total accounts: {{ count($local_accounts) }}
                                        </span>
                                        <div class="row p-1">
                                            @foreach ($local_accounts as $account)
                                                <div class="col-md-4 pt-1 border rounded p-2 m-1" style="font-weight: 500;">
                                                    <span style="font-weight: 400;"> Account Title: </span>
                                                    {{ $account->title }}
                                                    <br>
                                                    <span style="font-weight: 400;"> Currency: </span>
                                                    {{ $account->currency->short }}
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </td>
                            </tr>

                            <tr>
                                <td style="font-weight: 500;">Beneficiaries</td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <span style="font-weight: 500;">
                                            Total beneficiaries: {{ count($local_client_beneficiaries) }}
                                        </span>
                                        <div class="row p-1">
                                            @foreach ($local_client_beneficiaries as $beneficiary)
                                                <div class="col-md-4 pt-1 border rounded p-2 m-1" style="font-weight: 500;">
                                                    <span style="font-weight: 400;"> Beneficiary Name: </span>
                                                    {{ $beneficiary->friendly_name }}
                                                    <br>
                                                    <span style="font-weight: 400;"> Type: </span>
                                                    {{ $beneficiary->beneficiary_type }}
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </td>
                            </tr>

                            <tr>
                                <td style="font-weight: 500;">Client type</td>
                                <td>
                                    {{ $local_client_type }}
                                </td>
                            </tr>

                            <tr>
                                <td style="font-weight: 500;">Status</td>
                                <td>
                                    <span class="badge {{ $statusBadge[strtolower($local_client->status)] }}">
                                        {{ strtolower($local_client->status) }}
                                    </span>
                                </td>
                            </tr>

                            <tr>
                                <td style="font-weight: 500;">Is created in CAIB</td>
                                <td>
                                    @if ($local_client)
                                        <span class="badge badge-success">Yes</span>
                                    @else
                                        <span class="badge badge-danger">No</span>
                                    @endif
                                </td>
                            </tr>

                            <tr>
                                <td style="font-weight: 500;">Created at</td>
                                <td>
                                    {{ $local_client->createdDateTime }}
                                </td>
                            </tr>

                        </tbody>
                    </table>

                    <form method="post" name="sync-client" id="sync-client"
                        action="{{ url('/settings/sync-openpayd-client') }}">
                        @csrf
                        <input type="hidden" name="client_id" id="client_id" value={{ $client_id }}>
                        <input type="hidden" name="account_holder_id" id="account_holder_id"
                            value={{ $openpayd_clients->accountHolderId }}>
                    </form>
                    <button onclick="SyncOpenPaydData()" id="sync-btn" class="btn btn-primary">
                        Sync this client
                    </button>
                @else
                    <p>Client does not exist in CAIB.</p>
                    <form method="post" name="sync-client" id="sync-client"
                        action="{{ url('/settings/sync-openpayd-client') }}">
                        @csrf

                        <div class="col-md-5 mb-3">
                            <div class="form-group">
                                <label class="form-label" for="client_id">Clients (<small>Approved only</small>)</label>
                                <select name="client_id" id="client_id" data-select2-id="client_id"
                                    class="form-control select2 w-100" required>
                                    <option value="">choose client</option>
                                    @foreach ($clients as $client)
                                        @if ($client->id == old('client_id'))
                                            <option value="{{ $client->id }}" selected>{{ $client->name }}</option>
                                        @else
                                            <option value="{{ $client->id }}">{{ $client->name }}</option>
                                        @endif
                                    @endforeach
                                </select>
                                @error('client_id')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>

                        <input type="hidden" name="account_holder_id" id="account_holder_id"
                            value={{ $openpayd_clients->accountHolderId }}>
                        </form>
                        <button onclick="SyncOpenPaydData()" id="sync-btn" class="btn btn-primary">
                            Sync this client
                        </button>
                @endif
            </div>
        </div>
    </div>

@stop
@section('scripts')
    <script>
        $(document).ready(function() {
            $('#data-grid').dataTable({
                responsive: true
            });

            $('.js-thead-colors a').on('click', function() {
                var theadColor = $(this).attr("data-bg");
                console.log(theadColor);
                $('#dt-basic-example thead').removeClassPrefix('bg-').addClass(theadColor);
            });

            $('.js-tbody-colors a').on('click', function() {
                var theadColor = $(this).attr("data-bg");
                console.log(theadColor);
                $('#dt-basic-example').removeClassPrefix('bg-').addClass(theadColor);
            });
        });

        function SyncOpenPaydData() {
            Swal.fire({
                title: "Are you sure?",
                text: "The openpayd accounts, Beneficiaies data will be synced to CAIB.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Yes, sync it!"
            }).then(function(result) {
                if (result.value) {
                    document.getElementById('sync-client').submit()
                    document.getElementById('sync-btn').disabled = true;

                }
            });
        }
    </script>
@stop
