@extends('layouts.cp')
@section('title', 'Rejected Requests - Client Service Requests Management')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Client Service Requests Management</span>
        </h1>
        {{-- <a href="{{ url('/card-management/client-members/new') }}" class="btn btn-primary mr-3"><i
                class="fal fa-plus-circle"></i> Add Member </a> --}}
    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false"
        data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                Rejected Requests
            </h2>

        </div>

        <div class="panel-container show mb-3">
            <div class="panel-content" style="overflow: scroll;">
                @include('common.error-message')

                <table class="table table-bordered table-hover m-0 shadow" id="data-grid">
                    <thead class="thead-themed">
                        <tr>
                            <th width="30"></th>
                            <th>Maker Name</th>
                            <th>On Behalf of</th>
                            <th>Request Type</th>
                            <th>Requested Currency</th>
                            <th>Account</th>
                            <th>Platform</th>
                            <th>Rejector</th>
                            <th>Rejected At</th>
                            <th>OTP Status</th>
                            <th>Status</th>
                            <th>Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if ($requests)
                            @foreach ($requests as $request)
                                <tr>
                                    <td align="center" scope="row"><a
                                            href="{{ url('/mobile-client-requests/view/' . $request->id) }}"><i
                                                class="fal fa-tv"></i></a></td>
                                    <td>{{ $request->maker->name }}</td>
                                    <td>{{ $request->client->name }}</td>
                                    @php
                                        $typeMapping = [
                                            'APPLY_CARD' => 'New Card',
                                            'APPLY_ACCOUNT' => 'New Account',
                                            'APPLY_CRYPTO' => 'New Crypto Account',
                                            'CLOSE_ACCOUNT' => 'Close Account',
                                            'BALANCE_CERTIFICATE' => 'Balance Certificate',
                                        ];
                                        $requestType = $typeMapping[$request->type];
                                    @endphp
                                    <td>{{ $requestType }}</td>
                                    <td>
                                        @if ($request->currency_id)
                                            {{ $request->currency->short }}
                                            &nbsp;<img src="{{ $request->currency->logo }}"
                                                alt="{{ $request->currency->name }}" height="17px"
                                                style="vertical-align: middle;" />
                                        @else
                                            --
                                        @endif
                                    </td>
                                    <td>
                                        @if ($request->type == 'BALANCE_CERTIFICATE')
                                            @foreach ($request->client->clientAccounts as $account)
                                                @if ($account->id == $request->account_id)
                                                    {{ $account->account_number }} - {{ $account->currency->name }}
                                                @endif
                                            @endforeach
                                        @else
                                            --
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge {{ $request->platform ? 'badge-info' : 'badge-warning' }}">
                                            {{ $request->platform ? $request->platform : 'Mobile' }}
                                        </span>
                                    </td>
                                    <td>{{ $request->rejector->name ?? 'Haroon Qasim' }}</td>
                                    <td>{{ \Carbon\Carbon::parse($request->rejected_at)->format('d M, Y') }}</td>
                                    @php
                                        $otpStatusMapping = ['Unverified', 'Verified'];
                                    @endphp
                                    <td>
                                        <span
                                            class="badge {{ $statusBadge[$otpStatusMapping[$request->is_otp_verified]] }}">
                                            {{ $otpStatusMapping[$request->is_otp_verified] }}
                                        </span>
                                    </td>
                                    <td>
                                        <span
                                            class="badge {{ $statusBadge[$request->status] }}">{{ $request->status }}</span>
                                        <i class="fal fa-question-circle rejected_tooltip" data-toggle="tooltip"
                                            data-placement="auto" title=""
                                            data-original-title="{{ $request->reject_reason }}"></i>
                                    </td>
                                    <td>{{ \Carbon\Carbon::parse($request->created_at)->format('d M, Y') }}</td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="7" align="center">No records found.</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
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
        });
    </script>
@stop
