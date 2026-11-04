@extends('layouts.public')
@section('title', 'Liveness Check')
@section('styles')
    <link rel="stylesheet" media="screen, print"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="{{ asset('css/client-on-boarding.css') }}">
@stop
@section('content')
    <div id="error-case" style="display: block">
        <div class="text">
            <h2 style="color: red;">Kyc successfull</h2>
            <h4>Thank your for completeing the Kyc, You can close this page now.</h4>
        </div>
        <hr>       
    </div>
    <div id="show-case">
    </div>
@stop
