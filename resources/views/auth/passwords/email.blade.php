@extends('layouts.public')
@section('title', 'Forgot Password')
@section('content')

    @include('auth.partials.about')
    <div class="col-sm-12 col-md-6 col-lg-5 col-xl-4 ml-auto">
        <h1 class="text-white fw-300 mb-3 d-sm-block d-md-none">
            Forgot Password
        </h1>
        <div class="card p-4 rounded-plus bg-faded">
            @if (session('status'))
                <div class="alert alert-success" role="alert">
                    {{ session('status') }}
                </div>
            @endif
            <form method="POST" action="{{ route('password.email') }}">
                @csrf
                <h5>Please provide your registered email address</h5>
                <hr>
                <div class="form-group">
                    <label for="email" class="form-label">{{ __('Email Address') }}</label>
                    <input id="email" type="email" class="form-control @error('email') is-invalid @enderror"
                        name="email" value="{{ old('email') }}" required autocomplete="email" autofocus>
                    @error('email')
                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                    @enderror
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary">{{ __('Send Password Reset Link') }}</button>
                    <a href="{{ url('/login') }}" class="ml-2"><u>Back to Login</u></a>
                </div>
            </form>
        </div>
    </div>
@stop
