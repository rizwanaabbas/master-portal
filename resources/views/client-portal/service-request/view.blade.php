@php
    $typeMapping = [
        'APPLY_CARD' => 'New Card',
        'APPLY_ACCOUNT' => 'New Account',
        'APPLY_CRYPTO' => 'New Crypto Account',
        'CLOSE_ACCOUNT' => 'Close Account',
        'BALANCE_CERTIFICATE' => 'Balance Certificate',
    ];
    $requestType = $typeMapping[$service->type];
@endphp
@extends('layouts.cp')
@section('title', $requestType . ' - Service request')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class="fw-300">Services Request</span>
        </h1>
        <a href="{{ url('/client/services/requests/list') }}" class="btn btn-primary mr-3"><i
                class="fal fa-arrow-alt-left"></i>
            Go Back </a>
    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false"
        data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">

        <div class="panel-hdr">
            <h2>
                Service Request
            </h2>
        </div>
        <div class="panel-container show mb-3">
            <div class="panel-content" style="overflow: scroll">
                @include('common.error-message')
                <table class="table table-bordered table-hover m-0 shadow" id="data-grid">
                    <thead class="thead-dark">
                        <tr>
                            <th width="200">On Behalf of</th>
                            <td>{{ $service->client->name }}</td>
                        </tr>
                        @if ($service->currency)
                            <tr>
                                <th width="200">Currency</th>
                                <td>
                                    {{ $service->currency->name ?? '--' }}
                                    &nbsp;<img src="{{ $service->currency->logo }}" alt="{{ $service->currency->name }}"
                                        height="17px" style="vertical-align: middle;" />
                                </td>
                            </tr>
                        @endif
                        @if ($service->type == 'BALANCE_CERTIFICATE')
                            <tr>
                                <th width='200'>Account</th>
                                <td>
                                    @foreach ($service->client->clientAccounts as $account)
                                        @if ($account->id == $service->account_id)
                                            {{ $account->account_number }} - {{ $account->currency->name }}
                                        @endif
                                    @endforeach
                        @endif
                        </td>
                        </tr>
                        <tr>
                            <th>Request Type</th>
                            <td>{{ $requestType }}</td>
                        </tr>
                        <tr>
                            <th>Request Date</th>
                            <td>{{ \Carbon\Carbon::parse($service->created_at)->format('d M, Y') }}</td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td><span class="badge {{ $statusBadge[$service->status] }}">
                                    @if ($service->status != 'Rejected')
                                        {{ $service->status }}
                                </span>
                            @else
                                {{ $service->status }}
                                </span>
                                <i class="fal fa-question-circle rejected_tooltip" data-toggle="tooltip"
                                    data-placement="auto" title=""
                                    data-original-title="{{ $service->reject_reason }}"></i>
                                @endif
                            </td>
                        </tr>
                        @if ($service->status == 'Approved')
                            <tr>
                                <th>Approved At</th>
                                <td>{{ \Carbon\Carbon::parse($service->approved_at)->format('d M, Y') }}</td>
                            </tr>
                        @endif

                        @if (!$service->is_otp_verified)
                            <tr>
                                <td colspan="2" class="bg-warning">OTP Unverified! Wait for the team to reject it and
                                    then you can request again.</td>
                            </tr>
                        @endif
                    </thead>
                </table>


            </div>

        </div>

    </div>
@stop
