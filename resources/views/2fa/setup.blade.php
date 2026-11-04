@extends('layouts.public')
@section('title', 'Google Authenticator')
@section('content')
    @include('global-partials.loading-spinner')
    <div class="container d-flex justify-content-center align-items-center min-vh-100">
        <div class="card p-4 rounded-lg shadow-lg" style="max-width: 400px; width: 100%;">
            <div class="text-center mb-4">
                <h1>
                    <svg viewBox="0 -11.000000000000046 960 876" xmlns="http://www.w3.org/2000/svg" width="45"
                        height="45">
                        <path
                            d="M960 427c0 44.7-36.2 80.9-80.9 80.9H600L480 265.2 609.5 40.9C631.9 2.2 681.3-11 720 11.3c38.7 22.4 51.9 71.8 29.6 110.5L620.1 346.1h259c44.7 0 80.9 36.2 80.9 80.9z"
                            fill="#1a73e8" />
                        <path
                            d="M720 842.7c-38.7 22.3-88.1 9.1-110.5-29.6L480 588.8 350.5 813.1c-22.4 38.7-71.8 51.9-110.5 29.6-38.7-22.4-51.9-71.8-29.6-110.5l129.5-224.3 140.1-5.3 140.1 5.3 129.5 224.3c22.3 38.7 9.1 88.1-29.6 110.5z"
                            fill="#ea4335" />
                        <path
                            d="M480 265.2l-36.5 99.2-103.6-18.3-129.5-224.3c-22.3-38.7-9.1-88.1 29.6-110.5 38.7-22.3 88.1-9.1 110.5 29.6z"
                            fill="#fbbc04" />
                        <path d="M459.1 346.1l-93.9 161.8H80.9C36.2 507.9 0 471.7 0 427s36.2-80.9 80.9-80.9z"
                            fill="#34a853" />
                        <path d="M620.1 507.9H339.9L480 265.2z" fill="#185db7" />
                    </svg>
                    Two-Factor Authentication
                </h1>
            </div>
            <div class="text-center">
                @if (!$verified)
                    <h3>Setup</h3>
                    <p>Scan this QR code with your Google Authenticator app:</p>
                    <div class="mb-3">
                        <img src="{{ $QR_Image }}" alt="2FA QR code" width="200" height="200">
                    </div>
                    <p>Or enter this secret key manually: <strong>{{ $secret }}</strong></p>
                @else
                    <h3>Verify OTP</h3>
                @endif
            </div>
            @include('common.error-message')
            <form action="{{ route('2fa.verify') }}" method="POST" class="mt-3">
                @csrf
                <div class="form-group">
                    <label for="secret">Enter the code from the Google Authenticator app:</label>
                    <input type="text" name="secret" autofocus id="secret" class="form-control" autocomplete="off"
                        required>
                </div>
                @if (env('MFA'))
                    @if (empty($transaction_req_id))
                        <div class="form-group">
                            <p class="mt-2">Having problems with the Authenticator? <a href="{{ route('2fa.sendOtp') }}"
                                    class="text-primary font-weight-bold">
                                    Send OTP to your E-mail for verification.
                                </a></p>
                        </div>
                    @endif

                @endif
                <div class="form-group d-flex justify-content-between align-items-center mt-3">
                    <a href="{{ url('/2fa/cancel') }}" class="btn btn-outline-secondary btn-sm">
                        Back to Login
                    </a>
                    <button type="submit" id="otp-button" class="btn btn-primary btn-w-l"
                        onclick="showLoadingSpinner(); disableButton()">
                        Verify
                    </button>
                </div>

            </form>
        </div>
    </div>
@stop
<style>
    .height-10.w-100.shadow-lg.px-4.bg-brand-gradient {
        display: none;
    }

    .position-absolute.pos-bottom.pos-left.pos-right.p-4.text-center.text-white {
        display: none;
    }
</style>
<script>
    jQuery(document).ready(function() {
        jQuery('#').on('submit', function() {
            alert('submitted');
        });
    });

    function disableButton() {
        if (jQuery('#secret').val() != '') {
            jQuery('#otp-button').addClass('disabled');
        } else {
            hideSpinner();
        }
    }
</script>
