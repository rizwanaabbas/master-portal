@extends('layouts.public')
@section('title', 'Verify Email Address')
@section('content')

    @include('auth.partials.about')
    <div class="col-sm-12 col-md-6 col-lg-5 col-xl-4 ml-auto">
        <h1 class="text-white fw-300 mb-3 d-sm-block d-md-none">
            Verify your Email Address
        </h1>
        <div class="card p-4 rounded-plus bg-faded">
            @include('common.error-message')
            <div class="card-header">{{ __('Verify Your Email Address') }}</div>

            <div class="card-body">
                @if (session('resent'))
                    <div class="alert alert-success" role="alert">
                        {{ __('A fresh verification link has been sent to your email address.') }}
                    </div>
                @endif

                {{ __('Before proceeding, please check your email for a verification link.') }}
                {{ __('If you did not receive the email') }},
                <form class="d-inline" method="POST" action="{{ route('verification.resend') }}">
                    @csrf
                    <button type="submit"
                        class="btn btn-link p-0 m-0 align-baseline">{{ __('click here to request another') }}</button>.
                </form>
                <hr>
                <a href="{{ url('/logout') }}" class="btn btn-primary">Logout</a>
            </div>
        </div>
    </div>
@stop
