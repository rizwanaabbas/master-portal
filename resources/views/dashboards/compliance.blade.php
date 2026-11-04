@extends('layouts.cp')
@section('title', 'Compliance Dashboard')

@section('content') @include('common.error-message')
    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Compliance Dashboard</span>
        </h1>
    </div>

    @php
        $cardColors = json_decode($ownCompany->card_colors, true) ?? [];
    @endphp

    <div class="row">
        <div class="col-sm-3 col-xl-3">
            <div class="p-3 {{ $cardColors[0] ?? 'bg-primary' }} rounded overflow-hidden position-relative text-white mb-g">
                <div class="">
                    <h3 class="display-4 d-block l-h-n m-0 fw-500">
                        {{ $suspiciousScreenings }}
                        <small class="m-0 l-h-n">Total Suspicious Screenings</small>
                    </h3>
                </div>
                <i class="fal fa-user-clock position-absolute pos-right pos-bottom opacity-15 mb-n1 mr-n4"
                    style="font-size: 6rem;"></i>
            </div>
        </div>
        <div class="col-sm-3 col-xl-3">
            <div class="p-3 {{ $cardColors[1] ?? 'bg-primary' }} rounded overflow-hidden position-relative text-white mb-g">
                <div class="">
                    <h3 class="display-4 d-block l-h-n m-0 fw-500">
                        {{ $screenings }}
                        <small class="m-0 l-h-n">Total Screenings</small>
                    </h3>
                </div>
                <i class="fal fa-money-check-alt position-absolute pos-right pos-bottom opacity-15 mb-n1 mr-n4"
                    style="font-size: 6rem;"></i>
            </div>
        </div>
        {{--  <div class="col-sm-3 col-xl-3">
            <div class="p-3 {{ $cardColors[2] ?? 'bg-primary' }} rounded overflow-hidden position-relative text-white mb-g">
                <div class="">
                    <h3 class="display-4 d-block l-h-n m-0 fw-500">
                        {{ $transactionsCount }}
                        <small class="m-0 l-h-n">Total Transactions</small>
                    </h3>
                </div>
                <i class="fal fa-money-check-alt position-absolute pos-right pos-bottom opacity-15 mb-n1 mr-n4"
                    style="font-size: 6rem;"></i>
            </div>
        </div>
        <div class="col-sm-3 col-xl-3">
            <div class="p-3 {{ $cardColors[3] ?? 'bg-primary' }} rounded overflow-hidden position-relative text-white mb-g">
                <div class="">
                    <h3 class="display-4 d-block l-h-n m-0 fw-500">
                        {{ $currenciesCount->count }}
                        <small class="m-0 l-h-n">Total Currencies</small>
                    </h3>
                </div>
                <i class="fal fa-users position-absolute pos-right pos-bottom opacity-15 mb-n1 mr-n4"
                    style="font-size: 6rem;"></i>
            </div>
        </div> --}}
    </div>

@stop
