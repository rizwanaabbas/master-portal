@extends('layouts.cp')
@section('title', 'Validate Card Top-up Request - Client Card Management')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Card Top-up Request</span>
        </h1>
        <a href="{{ url('/client/card-management/card/topups/list') }}" class="btn btn-primary mr-3"><i
                class="fal fa-arrow-alt-left"></i> Go Back </a>
    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false"
        data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                Validate Card Top-up Request
            </h2>
        </div>

        <form method="post" action="{{ url('/client/transaction-requests/otp/validate') }}">
            @csrf
            <input type="hidden" name="trn_id" id="trn_id" value="{{ $trn->id }}">
            <div class="panel-container show">
                <div class="panel-content">
                    @include('common.error-message')
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <p>We have sent you OTP on your register email address, please provide the OTP code and
                                    validate your Top-up request.</p>
                                <table>
                                    <tr>
                                        <td><input type="number" name="otp" id="otp" class="form-control"
                                                placeholder="provide OTP"></td>
                                        <td><button type="button" onclick="generateOTP();" class="btn btn-secondary">Re
                                                Generate</button></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <hr>
                                <button type="submit" class="btn btn-primary">Submit</button>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </form>
    </div>

    <form method="post" action="{{ url('/client/transaction-requests/generate/otp') }}" id="otp-generate">
        @csrf
        <input type="hidden" name="otp_id" id="otp_id" value="{{ $trn->id }}">
    </form>

@section('scripts')
    <script>
        function generateOTP() {

            Swal.fire({
                title: "Are you sure?",
                text: "New OTP will be generated and sent to your registered email.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Yes, Generate it!"
            }).then(function(result) {
                if (result.value) {
                    document.getElementById('otp-generate').submit()
                }
            });

        }
    </script>
@stop

@stop
