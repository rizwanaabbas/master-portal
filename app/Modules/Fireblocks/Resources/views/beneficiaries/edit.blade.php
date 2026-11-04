@extends('layouts.cp')
@section('title', 'View Fireblocks Beneficiary')

@section('content')
    <div class="subheader">
        <h1 class="subheader-title"><span class='fw-300'>View Fireblocks Beneficiary</span></h1>
        <a href="{{ url('/fireblocks/beneficiaries') }}" class="btn btn-primary ml-auto"><i class="fal fa-arrow-alt-left"></i> Go Back</a>
    </div>

    <div class="panel">
        <div class="panel-hdr"><h2>Beneficiary #{{ $beneficiary->id }}</h2></div>
        <div class="panel-container show">
            <div class="panel-content">
                @include('common.error-message')
                @include('bitpace.transaction-log', ['transaction_log' => $transaction_log])
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Account</label>
                            <div class="form-control" readonly>{{ optional($beneficiary->account)->title }} - {{ optional($beneficiary->account)->account_number }}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Network</label>
                            <div class="form-control" readonly>{{ optional($beneficiary->network)->name }} ({{ optional($beneficiary->network)->code }})</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Wallet Address</label>
                            <div class="form-control" readonly style="word-break: break-all;">{{ $beneficiary->wallet_address }}</div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-5">
                        <div class="form-group">
                            <label class="form-label">Label</label>
                            <div class="form-control" readonly>{{ $beneficiary->label ?: '—' }}</div>
                        </div>
                    </div>
                    @php($requiresTag = \App\Modules\Fireblocks\Controllers\BeneficiaryCrudController::requiresDestinationTag(optional($beneficiary->network)->code))
                    @if ($requiresTag)
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label">Destination Tag / Memo</label>
                                <div class="form-control" readonly>{{ $beneficiary->destination_tag ?: '—' }}</div>
                            </div>
                        </div>
                    @endif
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label">Active</label>
                            <div class="form-control" readonly>{{ $beneficiary->is_active ? 'Enabled' : 'Disabled' }}</div>
                        </div>
                    </div>
                </div>

                @if (!empty($travelRulesEnabled))
                    <fieldset disabled>
                        @include('fireblocks::beneficiaries._travel-rule-fields')
                    </fieldset>
                @endif
            </div>
        </div>
    </div>
@stop
