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
            $local_account_holders_ids = collect($local_clients)
                ->map(function ($client) {
                    return $client->account_holder_id;
                })
                ->toArray();
            // dd($local_account_holders_ids);
            // dd($openpayd_clients);
        @endphp

        <div class="panel-container show mb-3">
            <div class="panel-content" style="overflow: scroll;">
                @include('common.error-message')
                <table class="table table-bordered table-hover m-0 shadow" id="data-grid">
                    <thead class="thead-themed">
                        <tr>
                            <th></th>
                            {{-- <th width="30">S.NO</th> --}}
                            <th>Client</th>
                            {{-- <th>list of client</th> --}}
                            <th>Country</th>
                            {{-- <th>Accounts</th> --}}
                            {{-- <th>beneficiaries</th> --}}
                            {{-- <th>Transactions</th> --}}
                            <th>Status</th>
                            <th>is created localy</th>
                            <th>Created Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($openpayd_clients as $index => $op_client)
                            <tr>
                                <td align="center"><a
                                        href="{{ url('/settings/openpayd-sync-data/' . $op_client->accountHolderId) }}"><i
                                            class="fal fa-tv"></i></a></td>
                                {{-- <td>{{ $index + 1 }}</td> --}}
                                <td>{{ $op_client->clientType === 'BUSINESS' ? $op_client->friendlyName : $op_client->individual->firstName . ' ' . $op_client->individual->lastName }}
                                </td>
                                <td>{{ $op_client->country }}</td>
                                <td><span
                                        class="badge {{ $statusBadge[strtolower($op_client->status)] }}">{{ strtolower($op_client->status) }}</span>
                                </td>
                                <td>
                                    @if (in_array($op_client->accountHolderId, $local_account_holders_ids))
                                        <span class="badge badge-success">Yes</span>
                                    @else
                                        <span class="badge badge-danger">No</span>
                                    @endif
                                </td>
                                </td>
                                <td>{{ $op_client->createdDateTime }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- <form method="POST" name="delete-beneficiary" id="delete-beneficiary" action="{{ url('/finance-management/beneficiaries/delete') }}">
        @csrf
        <input type="hidden" name="id" id="id" value="">
    </form> --}}

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
    </script>
@stop
