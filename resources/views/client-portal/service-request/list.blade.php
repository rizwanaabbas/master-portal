@extends('layouts.cp')
@section('title', 'Service Requests List - Service request')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class="fw-300">Services Request</span>
        </h1>
        <a href="{{ url('/client/services/requests/new') }}" class="btn btn-primary mr-3"><i class="fal fa-plus-circle"></i>
            Add
            new Request </a>
    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false"
        data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                Service Requests List
            </h2>

        </div>

        <div class="panel-container show mb-3">
            <div class="panel-content" style="overflow: scroll;">
                @include('common.error-message')

                <table class="table table-bordered table-hover m-0 shadow" id="data-grid">
                    <thead class="thead-themed">
                        <tr>
                            <th width="30">View</th>
                            <th>Currency </th>
                            <th>Account</th>
                            <th>On Behalf of </th>
                            <th>Request Type</th>
                            <th>Request Date</th>
                            <th>OTP Status</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($serviceRequests as $request)
                            <tr>
                                <td align="center" scope="row"><a
                                        href="{{ url('/client/services/requests/view') . '/' . $request->id }}"><i
                                            class="fal fa-tv }}"></i></a>
                                </td>
                                <td>
                                    @if ($request->currency)
                                        <img src="{{ $request->currency->logo }}" alt="{{ $request->currency->name }}"
                                            height="18px" style="vertical-align: middle;" /> &nbsp;
                                        {{ $request->currency->name }}
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
                                    {{ $request->client->name }}
                                </td>
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
                                <td> {{ \Carbon\Carbon::parse($request->created_at)->format('d M, Y') }}</td>
                                @php
                                    $otpStatusMapping = ['Unverified', 'Verified'];
                                @endphp
                                <td>
                                    <span class="badge {{ $statusBadge[$otpStatusMapping[$request->is_otp_verified]] }}">
                                        {{ $otpStatusMapping[$request->is_otp_verified] }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge {{ $statusBadge[$request->status] }}">
                                        @if ($request->status != 'Rejected')
                                            {{ $request->status }}
                                    </span>
                                @else
                                    {{ $request->status }}
                                    </span>
                                    <i class="fal fa-question-circle rejected_tooltip" data-toggle="tooltip"
                                        data-placement="auto" title=""
                                        data-original-title="{{ $request->reject_reason }}"></i>
                                    {{-- <a href="javascript:deleteServiceRequest({{ $request->id }})"
                                        title="Delete Service Request">
                                        <i class="alert-danger fal fa-times-circle rounded-circle"></i>
                                    </a> --}}
                        @endif
                        </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <form method="POST" name="delete-service-request" id="delete_service_request"
        action="{{ url('/client/services/requests/delete') }}">
        @csrf
        <input type="hidden" name="request_id" id="request_id" value="">
    </form>

@section('scripts')
    <script>
        $(document).ready(function() {
            $('#data-grid').dataTable({
                responsive: true
            })

            $('.js-thead-colors a').on('click', function() {
                var theadColor = $(this).attr("data-bg")
                $('#dt-basic-example thead').removeClassPrefix('bg-').addClass(theadColor)
            })

            $('.js-tbody-colors a').on('click', function() {
                var theadColor = $(this).attr("data-bg")
                console.log(theadColor)
                $('#dt-basic-example').removeClassPrefix('bg-').addClass(theadColor)
            })

        })

        function deleteServiceRequest(id) {

            Swal.fire({
                title: "Are you sure?",
                text: "Service Request will be deleted permanently",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Yes, delete it!"
            }).then(function(result) {
                if (result.value) {
                    $("#request_id").val(id);
                    document.getElementById('delete_service_request').submit()
                }
            });
        }
    </script>
@stop

@stop
