@php
    $typeMapping = [
        'APPLY_CARD' => 'New Card',
        'APPLY_ACCOUNT' => 'New Account',
        'APPLY_CRYPTO' => 'New Crypto Account',
        'CLOSE_ACCOUNT' => 'Close Account',
        'BALANCE_CERTIFICATE' => 'Balance Certificate',
    ];
    $requestType = $typeMapping[$request->type];

    $isOpenPaydEnabled = in_array('OpenPayd', $enabledModules);

@endphp
@extends('layouts.cp')
@section('title', $requestType . ' - Client Service Requests Management')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Client Service Requests Management</span>
        </h1>
        <a href="{{ url('mobile-client-requests/' . strtolower($request->status) . '-list') }}"
            class="btn btn-primary mr-3"><i class="fal fa-arrow-alt-left"></i> Go Back </a>
        {{-- <a href="{{ url('crypto-management/crypto-pending-requests/print/' . $request->id) }}"
            class="btn btn-primary mr-3" target="_blank"><i class="fal fa-print"></i> Print </a> --}}

    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false"
        data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                {{ $requestType }}
            </h2>
        </div>

        <div class="panel-container show mb-3">
            <div class="panel-content" style="overflow: scroll;">
                @include('common.error-message')
                <form id="approval-form" method="post" action="{{ url('/mobile-client-requests/approve') }}">
                    @csrf
                    <input type="hidden" id="id" name="id" value="{{ $request->id }}">
                    <table class="table table-bordered table-hover m-0 shadow" id="data-grid">
                        <thead class="thead-dark">
                            <tr>
                                <th width="200">Requested By</th>
                                <td>{{ $request->maker->name }}
                                </td>
                            </tr>
                            <tr>
                                <th width="200">On Behalf of</th>
                                <td>{{ $request->client->name }}</td>
                            </tr>
                            <tr>
                                <th>Request Type</th>
                                <td>
                                    {{ $requestType }}
                                </td>
                            </tr>
                            @if ($request->currency)
                                <tr>
                                    <th>Requested Currency</th>
                                    <td>
                                        {{ $request->currency->short }}
                                        &nbsp;<img src="{{ $request->currency->logo }}" alt="{{ $request->currency->name }}"
                                            height="17px" style="vertical-align: middle;" />
                                    </td>
                                </tr>
                            @endif
                            @if ($request->type == 'BALANCE_CERTIFICATE')
                                <tr>
                                    <th width='200'>Account</th>
                                    <td>
                                        @foreach ($request->client->clientAccounts as $account)
                                            @if ($account->id == $request->account_id)
                                                {{ $account->account_number }} - {{ $account->currency->name }}
                                            @endif
                                        @endforeach
                            @endif
                            <tr>
                                <th>Platform</th>
                                <td>
                                    {{ $request->platform ? $request->platform : 'Mobile' }}
                                </td>
                            </tr>
                            @if ($request->type == 'CLOSE_ACCOUNT')
                                <tr>
                                    <th>Requested Account</th>
                                    <td>
                                        {{ $request->client->name }}
                                    </td>
                                </tr>
                            @endif
                            <tr>
                                <th>Request Date</th>
                                <td>{{ \Carbon\Carbon::parse($request->created_at)->format('d M, Y') }}</td>
                            </tr>
                            {{--
                            @if ($request->status == 'Pending' || $request->status == 'Approved')
                                <tr>
                                    <th>request Remarks</th>
                                    <td>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <textarea name="credit_remarks" id="remarks" class="form-control" cols="30" rows="2">{{ $request->reason }}</textarea>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endif --}}

                            @php
                                $otpStatusMapping = ['Unverified', 'Verified'];
                            @endphp
                            <tr>
                                <th>OTP Verified</th>
                                <td><span class="badge {{ $statusBadge[$otpStatusMapping[$request->is_otp_verified]] }}">
                                        {{ $otpStatusMapping[$request->is_otp_verified] }}</span>
                                </td>
                            </tr>

                            <tr>
                                <th>Status</th>
                                <td><span class="badge {{ $statusBadge[$request->status] }}">{{ $request->status }}</span>
                                </td>
                            </tr>
                            @if ($request->type == 'APPLY_CARD')
                                <tr>
                                    <th>Card Request Type</th>
                                    <td>
                                        {{ ucfirst(strtolower($request->sub_type)) }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>Card Provider</th>
                                    <td>
                                        @php
                                            $provider_type = '';
                                            if (!empty($request->cardDetail)) {
                                                $provider_type = $request->cardDetail->provider_type;
                                            }
                                        @endphp

                                        <select name="provider" id="provider" class="select2">
                                            <option value="">Select Crad Provider</option>
                                            @if (count($providers) == 0 || count($showModuleProviders) == 0)
                                                <option value="">No card providers are configured.</option>
                                            @else
                                                @foreach ($providers as $handle => $name)
                                                    {{-- @if ($match_move_enabled == 0 && $provider->handle == 'match_move')
                                                    @continue
                                                @endif --}}
                                                    @if (isset($showModuleProviders[$handle]))
                                                        @if ($showModuleProviders[$handle] === false && $provider_type != $handle)
                                                            @continue
                                                        @endif

                                                        <option value="{{ $handle }}"
                                                            @if ($provider_type == $handle) selected @endif>
                                                            {{ $name }}
                                                        </option>
                                                    @endif
                                                @endforeach
                                            @endif
                                        </select>

                                        <div>
                                            <div class="alert alert-danger mt-4" id="card-provider-error"
                                                style="display:none;">Please select the card provider.</div>
                                        </div>

                                    </td>
                                </tr>

                            @endif
                            @if ($request->status == 'Rejected')
                                <tr>
                                    <th>Rejection Reason</th>
                                    <td>{{ $request->reject_reason }}</td>
                                </tr>
                            @endif
                        </thead>
                    </table>

                    {{-- IBAN Checkbox --}}
                    @if ($isOpenPaydEnabled)
                        @if (
                            $request->status == 'Pending' &&
                                $request->type == 'APPLY_ACCOUNT' &&
                                in_array($request->currency->short, $openpayd_allowed_currencies))
                            <div class="custom-control custom-checkbox mt-3"
                                id="iban-container-{{ $request->currency->id }}">
                                <input type="checkbox" class="custom-control-input"
                                    id="iban-checkbox-{{ $request->currency->id }}" name="iban_currency_id"
                                    value="{{ $request->currency->id }}">
                                <label class="custom-control-label" for="iban-checkbox-{{ $request->currency->id }}">Create
                                    IBAN for
                                    <b>{{ $request->currency->short }}</b></label>
                            </div>
                        @endif
                    @endif

                    <hr>

                    @if ($request->status == 'Pending')
                        <div class="row">
                            <div class="col-md-12">
                                @if ($request->status != 'Approved')
                                    <button type="button" {{ $request->is_otp_verified ? '' : 'disabled' }}
                                        onclick="approve();" class="btn btn-success">Approve</button>
                                @endif
                                @if ($request->status != 'Rejected')
                                    <button onclick="rejectrequest(); return false;" class="btn btn-danger">Reject</button>
                                @endif
                            </div>
                        </div>
                    @endif
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="reject-modal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title ">Reject request</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true"><i class="fal fa-times"></i></span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="{{ url('/mobile-client-requests/reject') }}" method="post">
                        @csrf
                        <input type="hidden" name="id" id="id" value="{{ $request->id }}">
                        <div class="form-group">
                            <label class="form-label" for="reject_reason">Reject Reason</label>
                            <textarea required class="form-control" name="reject_reason" id="reject_reason" cols="30" rows="2"></textarea>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-danger">Reject</button>
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>

@stop
@section('scripts')
    <script>
        var controls = {
            leftArrow: '<i class="fal fa-angle-left" style="font-size: 1.25rem"></i>',
            rightArrow: '<i class="fal fa-angle-right" style="font-size: 1.25rem"></i>'
        }

        function approve() {
            if (!validateProvider())
                return false;

            Swal.fire({
                title: "Are you sure?",
                text: "request will be approved",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Yes, Approve it!"
            }).then(function(result) {
                if (result.value) {
                    document.getElementById('approval-form').submit()
                }
            });
        }

        function rejectrequest() {
            $("#reject-modal").modal('show');
        }

        $(document).ready(function() {

            // minimum setup
            $('#request_date').datepicker({
                format: 'dd/mm/yyyy',
                todayHighlight: true,
                orientation: "bottom left",
                templates: controls,
                startDate: "{{ \Carbon\Carbon::today()->format('d/m/Y') }}"
            });
        })

        function validateProvider() {
            var provider = $('#provider').val();
            $('#card-provider-error').hide();
            if (provider == '') {
                $('#card-provider-error').show();
                $('#provider').focus();
                //alert('Please select the provider');

                return false;
            } else {
                if (provider == 'match_move') {
                    //const cardTypeField = document.getElementById('card_type');
                    var card_type = $('#card_type').val();
                    if (card_type == '') {
                        $('#card-type-error').show();
                        return false;
                    } else {
                        $('#card-type-error').hide();
                    }

                }
            }
            return true;
        }
    </script>
@stop
