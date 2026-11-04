@extends('layouts.public')
@section('title', 'Client Registeration')
@section('content')

    @php
        $onboardingEnabled = in_array('Onboarding', $enabledModules);
    @endphp
    <div class="container">
        <div class="user-have-camera" id="user-have-camera" style="display: none">
            <h1 class="">
                Client Registeration
            </h1>
            <div class="card p-4 rounded-plus bg-faded mt-4">
                <form method="POST" action="/register-new-client">
                    @csrf

                    <div class="row">
                        <div class="col-sm-6 col-md-6 col-lg-6 col-xl-6 col-xxl-6 ">
                            <div class="form-group">
                                <label class="form-label" for="name">Your Name / Business Name</label>
                                <input tabindex="1" id="name" type="input"
                                    class="form-control form-control-lg @error('name') is-invalid @enderror"
                                    placeholder="your full name or business name" name="name" value="{{ old('name') }}"
                                    required autofocus>
                                @error('business_name')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-6 col-lg-6 col-xl-6 col-xxl-6 mt-3 mt-sm-0">
                            <div class="form-group">
                                <label class="form-label" for="account_type">Account Type</label>
                                <select tabindex="2" name="account_type" id="account_type"
                                    class="form-control form-control-lg select2">
                                    <option value="individual" @if (old('account_type') == 'individual') selected @endif>Individual
                                    </option>
                                    <option value="company" @if (old('account_type') == 'company') selected @endif>Company
                                    </option>
                                </select>
                                @error('email')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>
                    </div>


                    <div class="row mt-4">
                        <div class="col-sm-6 col-md-6 col-lg-6 col-xl-6 col-xxl-6">
                            <div class="form-group">
                                <label class="form-label" for="username">Email Address</label>
                                <input tabindex="3" id="email" type="email"
                                    class="form-control form-control-lg @error('email') is-invalid @enderror"
                                    placeholder="email address" name="email"
                                    @if (isset($invite->notify_email)) readonly @endif
                                    value="@if (isset($invite->notify_email)) {{ $invite->notify_email }} @else {{ old('email') }} @endif"
                                    required>
                                @error('email')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-sm-6 col-md-6 col-lg-6 col-xl-6 col-xxl-6 mt-3 mt-sm-0"
                            id="contact_number_container">
                            @include('auth.partials.contact-number')
                        </div>
                    </div>

                    <div class="row ">
                        <div class="col-sm-6 col-md-6 col-lg-6 col-xl-6 col-xxl-6">
                            <div class="form-group">
                                <label class="form-label" for="password">Password</label>
                                <input tabindex="5" id="password" type="password"
                                    class="form-control form-control-lg @error('password') is-invalid @enderror"
                                    name="password" required>
                                @error('password')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-6 col-lg-6 col-xl-6 col-xxl-6 mt-3 mt-sm-0">
                            <div class="form-group">
                                <label class="form-label" for="password_confirmation">Confirm Password</label>
                                <input tabindex="6" id="password_confirmation" type="password"
                                    class="form-control form-control-lg @error('password_confirmation') is-invalid @enderror"
                                    name="password_confirmation" required>
                                @error('password_confirmation')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>
                    </div>


                    {{--  <div class="col-sm-6 col-md-6 col-lg-6 col-xl-6 col-xxl-6">
                        <div class="form-group" id="g-recaptcha">

                            {{ htmlFormSnippet() }}
                            @error('g-recaptcha-response')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div> --}}

                    <div class=" col-sm-6 col-md-6 col-lg-6 col-xl-6 col-xxl-6">
                        <div class="form-group">
                            <div class="custom-control custom-checkbox mt-6">
                                <input type="checkbox" class="custom-control-input" id="rememberme">
                                <input class="custom-control-input" type="checkbox" required value="true"
                                    name="agree_terms" id="agree_terms" {{ old('remember') ? 'checked' : '' }}>
                                <label class="custom-control-label" for="agree_terms"> I agree to <a
                                        href="{{ $ownCompany->terms_and_conditions_url }}" target="_blank">terms &
                                        conditions</a></label>
                                @error('agree_terms')
                                    <span class="invalid-feedback" role="alert"><strong>You must agree to terms &
                                            conditions</strong></span>
                                @enderror
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">

                        </div>
                    </div>

                    <div class="row no-gutters  mt-4">
                        <div class="col-lg-6 pr-lg-1 my-2">
                            <button type="submit" class="btn btn-primary waves-effect waves-themed">Register</button>
                            <a href="/login" class="btn btn-default">Back to Login</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <div class="user-doesnt-have-camera" id="user-doesnt-have-camera" style="display: none;">
            <div class="row">
                <div class="col-md-12">
                    <div class="card p-6 mt-5 rounded-plus">
                        <div class="row">
                            <div class="col-md-12">
                                <h1 class="text-center">Client Registeration</h1>
                                <p class="text-center">If you want to register, please download the
                                    {{ $ownCompany ? $ownCompany->short_name : '' }} Mobile
                                    application and signup there.
                                </p>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12 text-center">
                                <div class="d-flex flex-column flex-md-row justify-content-center align-items-center">
                                    <a href="{{ $ownCompany ? $ownCompany->app_store_url : '' }}"
                                        class="btn btn-primary mb-3 mb-md-0">
                                        <img src="{{ asset('assets/img/apple.png') }}" alt="google play" width="40">
                                        <span class="d-inline-block text-left align-middle">
                                            <small class="font-weight-normal d-block">Download on the</small>
                                            <b class="text-uppercase">App Store</b>
                                        </span>
                                    </a>
                                    <a href="{{ $ownCompany ? $ownCompany->play_store_url : '' }}"
                                        class="btn btn-primary ml-0 ml-md-2 mt-3 mt-md-0">
                                        <img src="{{ asset('assets/img/google-play.png') }}" alt="google play"
                                            width="40">
                                        <span class="d-inline-block text-left text-uppercase align-middle">
                                            <small class="font-weight-normal d-block">Get it on the</small>
                                            <b class="text-uppercase">Google Play</b>
                                        </span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop
@section('scripts')
    <script>
        var onboardingEnabled = '<?php echo json_encode($onboardingEnabled); ?>';

        document.addEventListener("DOMContentLoaded", function() {
            // Check if the browser supports the necessary API
            if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia && onboardingEnabled) {
                // The user has a camera
                document.getElementById('user-have-camera').style.display = 'block';
            } else {
                // The user doesn't have a camera
                document.getElementById('user-doesnt-have-camera').style.display = 'block';
            }
        });

        /*    window.addEventListener('load', () => {
                   var parentElement = document.getElementById('contact_number_container')
                   parentElement.getElementsByClassName('form-group')[0].style.marginBottom = '0px'
                   var firstInnerContainer = parentElement.getElementsByClassName(
                       'select2-selection select2-selection--single')[0]
                   firstInnerContainer.style.height = '50px'
                   parentElement.getElementsByClassName('select2-selection__rendered')[0].style.paddingTop = '7px'

                   const $recaptcha = document.querySelector('#g-recaptcha-response');
                   if ($recaptcha) {
                       $recaptcha.setAttribute('required', 'required');
                   }
               }) */
    </script>
@stop
