@extends("layouts.cp")
@section('title', 'Transaction - {{ $trn->transaction_nature }} - Finance Management')

@section("content")

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Finance Management</span>
        </h1>
        <a href="{{ url()->previous() }}" class="btn btn-primary mr-3"><i class="fal fa-arrow-alt-left"></i> Go Back </a>
    </div>

    <div  class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false" data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                Transaction - {{ $trn->transaction_nature }}
            </h2>
        </div>

        <div class="panel-container show">
            <div class="panel-content">
                @include("common.error-message")
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label" for="account_id">Account</label>
                            <span class="form-control">{{ $trn->account->title. " - ".$trn->account->account_number }}</span>
                        </div>

                        <div class="row">

                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label" for="amount">{{ $trn->transaction_type }} Amount</label>
                            <span class="form-control">{{ number_format($trn->amount) }}</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label" for="transaction_date">Transaction Date</label>
                            <span class="form-control">{{ \Carbon\Carbon::parse($trn->transaciton_date)->format("d M, Y") }}</span>
                        </div>
                    </div>
                </div>

                @if ($trn->transaction_nature == "Cheque")
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label" for="account_id">In Fav Of</label>
                                <span class="form-control">{{ $trn->cheque->in_fav_of }}</span>
                            </div>

                            <div class="row">

                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="form-label" for="amount">Cheque Number</label>
                                <span class="form-control">{{ $trn->cheque->cheque_number }}</span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="form-label" for="transaction_date">Cheque Date</label>
                                <span class="form-control">{{ \Carbon\Carbon::parse($trn->cheque->cheque_date)->format("d M, Y") }}</span>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="row pb-5">
                    <div class="col-md-10">
                        <div class="form-group">
                            <label class="form-label" for="name">Remarks</label>
                            <br><span>{{ $trn->remarks }}</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

@stop
