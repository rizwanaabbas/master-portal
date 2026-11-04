@extends('layouts.public')
@section('title', 'Admin Login')
@section('content')

    @include('auth.partials.about')
    <div class="col-sm-12 col-md-6 col-lg-5 col-xl-4 ml-auto">
        <h1 class="text-white fw-300 mb-3 d-sm-block d-md-none">
            Secure login
        </h1>
        <div class="card p-4 rounded-plus bg-faded">
            @include('common.error-message')
            <form method="POST" action="{{ route('login') }}">
                @csrf
                <div class="form-group">
                    <label class="form-label" for="username">Email</label>
                    <input id="email" type="email"
                        class="form-control form-control-lg @error('email') is-invalid @enderror"
                        placeholder="your login email" name="email" value="{{ old('email') }}" required
                        autocomplete="email" autofocus>
                    @error('email')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <input id="password" type="password"
                        class="form-control form-control-lg @error('password') is-invalid @enderror" name="password"
                        required autocomplete="current-password">
                    @error('password')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
                <div class="form-group text-left">
                    <div class="custom-control custom-checkbox">
                        <input class="custom-control-input" type="checkbox" name="remember" id="remember"
                            {{ old('remember') ? 'checked' : '' }}>
                        <label class="custom-control-label" for="remember"> Remember me.</label>
                    </div>
                </div>
                <div class="form-group text-left">
                    <p>Dont't have an account? <a href="{{ url('/register') }}"
                            class="text-primary font-weight-bold"><b>Register Now</b></a></p>
                </div>
                <div class="row no-gutters">
                    <div class="">
                        <button type="submit" class="btn btn-primary waves-effect waves-themed">Login</button>
                        <a href="{{ url('/password/reset') }}" class="ml-2"><u>Forgot Password?</u></a>
                    </div>
                </div>
            </form>
        </div>
    </div>
@stop
