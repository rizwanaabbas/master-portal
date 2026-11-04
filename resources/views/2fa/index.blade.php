@extends('layouts.public')
@section('title', 'Client Login')
@section('content')

@include('auth.partials.about')

@include('common.error-message')
<div class="subheader">
    <h1 class="subheader-title">
        <span class='fw-300'>Admin Dashboard</span>
    </h1>
</div>
    
    <h1>Setup 2FA</h1>
    <p><a href="/user/2fa" class="btn btn-primary">SETUP</a></p>
    @endsection

