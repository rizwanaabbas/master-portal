@extends('layouts.public')
@section('title', '2 Factor Authentication')
@section('content')

    @include('auth.partials.about')
    <div class="col-sm-12 col-md-6 col-lg-5 col-xl-4 ml-auto">
        <h1 class="text-white fw-300 mb-3 d-sm-block d-md-none">
            2 Factor Authentication
        </h1>
        <div class="card p-4 rounded-plus bg-faded">
            @if (session('error'))
                <div class="row">
                    <div class="col-md-12">
                        <p class="alert alert-danger">{{ session('error') }}</p>
                    </div>
                </div>
            @endif
            <h5>OTP Code has been sent to your email</h5>
            <hr>
            <form method="POST" action="{{ route('2fa.post') }}">
                @csrf
                <div class="form-group">
                    <label class="form-label" for="username">OTP</label>
                    <input id="code" type="number" class="form-control @error('code') is-invalid @enderror"
                        name="code" value="{{ old('code') }}" required autocomplete="code" autofocus>
                    @error('code')
                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                    @enderror
                </div>

                <div class="row no-gutters">
                    <div class="">
                        <button type="submit" class="btn btn-primary waves-effect waves-themed">Submit</button>
                        <a href="{{ url('/2fa/cancel') }}" class="btn btn-outline-default">Back to Login</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
@stop
