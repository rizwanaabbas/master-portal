@extends('layouts.cp')
@section('title', 'Update Currency - Finance Management')

@section('content')

    @php
        $enabled_provider_map = [
            'openpayd' => in_array('OpenPayd', $enabledModules),
            'lorum' => in_array('Lorum', $enabledModules),
            'zenus' => in_array('Zenus', $enabledModules),
        ];

        $provider_labels = [
            'openpayd' => 'OpenPayd',
            'lorum' => 'Lorum',
            'zenus' => 'Zenus Bank',
        ];
    @endphp

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Finance Management</span>
        </h1>
        <a href="{{ url('/finance-management/currencies') }}" class="btn btn-primary mr-3"><i
                class="fal fa-arrow-alt-left"></i> Go Back </a>
    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false"
        data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                Update Currency
            </h2>
        </div>

        <form method="post" action="{{ url('/finance-management/currencies/update') }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" id="id" name="id" value="{{ $currency->id }}">
            <div class="panel-container show">
                <div class="panel-content">
                    @include('common.error-message')

                    <div class="row">
                        <div class="col-md-12">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="form-label" for="short">Short</label>
                                        <input type="text" autofocus id="short" maxlength="10" name="short"
                                            value="{{ $currency->short }}" class="form-control" required>
                                        @error('short')
                                            <span class="invalid-feedback"
                                                role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="form-label" for="name">Name</label>
                                        <input type="text" autofocus id="name" name="name"
                                            value="{{ $currency->name }}" class="form-control" required>
                                        @error('name')
                                            <span class="invalid-feedback"
                                                role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-4">
                                <div class="col-md-4">
                                 <div class="form-group">
                                    <label class="form-label" for="virtual_iban_provider">Virtual IBAN provider</label>
                                    
                                    <select name="virtual_iban_provider" id="virtual_iban_provider" class="form-control select2 w-100">
                                        <option value="">Choose Virtual IBAN System</option>

                                        @foreach ($enabled_provider_map as $provider => $isEnabled)
                                            @if ($isEnabled)
                                                <option value="{{ $provider }}"
                                                    @if ($currency->virtual_iban_provider === $provider) selected @endif>
                                                    {{ $provider_labels[$provider] }}
                                                </option>
                                            @endif
                                        @endforeach

                                    </select>

                                    @error('virtual_iban_provider')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                   
                                </div>    
                               <div class="form-group">
                                        <div class="custom-control custom-switch mt-5">
                                            <input type="checkbox" class="custom-control-input"
                                                @if ($currency->is_active) checked @endif id="is_active"
                                                value="1" name="is_active">
                                            <label class="custom-control-label" for="is_active">Active</label>
                                        </div>
                                         <!-- div class="custom-control custom-switch mt-5">
                                            <input type="checkbox" class="custom-control-input"
                                                @if ($currency->is_openpayd) checked @endif id="is_openpayd"
                                                value="1" name="is_openpayd">
                                            <label class="custom-control-label" for="is_openpayd">Open Payd</label>
                                        </div>
                                        <div class="custom-control custom-switch mt-5">
                                            <input type="checkbox" class="custom-control-input"
                                                @if ($currency->is_lorum) checked @endif id="is_lorum"
                                                value="1" name="is_lorum">
                                            <label class="custom-control-label" for="is_lorum">Lorum</label>
                                        </div> -->
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="form-label" for="logo">Logo</label><br />
                                        @if ($currency->logo != '')
                                            <img src="{{ $currency ? $currency->logo : '' }}"
                                                style="width: 18px; height:18px"
                                                class="mr-2 border border-primary profile-image rounded-circle"
                                                alt="Logo" />
                                        @endif
                                        <label for="logo" class="btn btn-light">Upload</label>
                                        <span id="file-name-display"> png/svg, recommended size 18x18</span>
                                        <input type="file" name="logo" accept="image/*" onchange="setFile(this)"
                                            id="logo" class="invisible item-file d-none" />
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="custom-control custom-switch mt-5">
                                    <input type="checkbox" class="custom-control-input"
                                        @if ($currency->request_auto_approval) checked @endif id="request_auto_approval"
                                        value="1" name="request_auto_approval">
                                    <label class="custom-control-label" for="request_auto_approval">Auto approve service request</label>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <div class="custom-control custom-switch ">
                                    <input type="checkbox" class="custom-control-input"
                                        @if ($currency->hide_for_client) checked @endif id="hide_for_client"
                                        value="1" name="hide_for_client">
                                    <label class="custom-control-label" for="hide_for_client">Hide for clients</label>
                                </div>
                            </div>

                            {{-- ================= TABS NAV ================= --}}
                            <ul class="nav nav-tabs" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active"
                                    data-toggle="tab"
                                    href="#maintenance"
                                    role="tab">
                                        Maintenance Fee
                                    </a>
                                </li>

                                <li class="nav-item">
                                    <a class="nav-link"
                                    data-toggle="tab"
                                    href="#onboarding"
                                    role="tab">
                                        Onboarding Fee
                                    </a>
                                </li>

                                <li class="nav-item">
                                    <a class="nav-link"
                                    data-toggle="tab"
                                    href="#transaction"
                                    role="tab">
                                        Transaction Fees
                                    </a>
                                </li>
                            </ul>

                            {{-- ================= TAB CONTENT ================= --}}
                            <div class="tab-content mt-4">

                                {{-- ================= MAINTENANCE FEES ================= --}}
                                <div class="tab-pane fade show active" id="maintenance" role="tabpanel">

                                    <div class="row">

                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="individual_fee">Individual Maintenance Fee Amount</label>
                                                <input type="number" step="any" min="0"
                                                    id="individual_fee"
                                                    name="individual_fee"
                                                    value="{{ $currency->maintenance ? $currency->maintenance->individual_fee : 0 }}"
                                                    class="form-control" >

                                                @error('individual_fee')
                                                    <span class="invalid-feedback d-block">
                                                        <strong>{{ $message }}</strong>
                                                    </span>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="company_fee">Company Maintenance Fee Amount</label>
                                                <input type="number" step="any" min="0"
                                                    id="company_fee"
                                                    name="company_fee"
                                                    value="{{ $currency->maintenance ? $currency->maintenance->company_fee : 0 }}"
                                                    class="form-control" >

                                                @error('company_fee')
                                                    <span class="invalid-feedback d-block">
                                                        <strong>{{ $message }}</strong>
                                                    </span>
                                                @enderror
                                            </div>
                                        </div>

                                    </div>

                                </div>

                                {{-- ================= ONBOARDING FEES ================= --}}
                                <div class="tab-pane fade" id="onboarding" role="tabpanel">

                                    <div class="row">

                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="onboarding_fee_individual">
                                                    Individual Onboarding Fee Amount
                                                </label>

                                                <input type="number" step="any" min="0"
                                                    id="onboarding_fee_individual"
                                                    name="onboarding_fee_individual"
                                                    value="{{ $currency->onboarding_fee_individual ?? 0 }}"
                                                    class="form-control" >

                                                @error('onboarding_fee_individual')
                                                    <span class="invalid-feedback d-block">
                                                        <strong>{{ $message }}</strong>
                                                    </span>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="onboarding_fee_company">
                                                    Company Onboarding Fee Amount
                                                </label>

                                                <input type="number" step="any" min="0"
                                                    id="onboarding_fee_company"
                                                    name="onboarding_fee_company"
                                                    value="{{ $currency->onboarding_fee_company ?? 0 }}"
                                                    class="form-control" >

                                                @error('onboarding_fee_company')
                                                    <span class="invalid-feedback d-block">
                                                        <strong>{{ $message }}</strong>
                                                    </span>
                                                @enderror
                                            </div>
                                        </div>

                                    </div>

                                </div>

                                {{-- ================= TRANSACTION FEES ================= --}}
                                <div class="tab-pane fade" id="transaction" role="tabpanel">

                                    <div class="row">

                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="transaction_fee_rate">
                                                    Transaction Fee Amount (%)
                                                </label>

                                                <input type="number" step="any" min="0"
                                                    id="transaction_fee_rate"
                                                    name="transaction_fee_rate"
                                                    value="{{ $currency->transaction_fee_rate ?? 0 }}"
                                                    class="form-control">

                                                @error('transaction_fee_rate')
                                                    <span class="invalid-feedback d-block">
                                                        <strong>{{ $message }}</strong>
                                                    </span>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="bank_fee_rate">
                                                    Correspondent Bank Transaction Charges (%)
                                                </label>

                                                <input type="number" step="any" min="0"
                                                    id="bank_fee_rate"
                                                    name="bank_fee_rate"
                                                    value="{{ $currency->bank_fee_rate ?? 0 }}"
                                                    class="form-control">

                                                @error('bank_fee_rate')
                                                    <span class="invalid-feedback d-block">
                                                        <strong>{{ $message }}</strong>
                                                    </span>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="transaction_fee_threshold">
                                                    Transaction Fee Threshold
                                                </label>

                                                <input type="number" step="any" min="0"
                                                    id="transaction_fee_threshold"
                                                    name="transaction_fee_threshold"
                                                    value="{{ $currency->transaction_fee_threshold }}"
                                                    class="form-control">

                                                @error('transaction_fee_threshold')
                                                    <span class="invalid-feedback d-block">
                                                        <strong>{{ $message }}</strong>
                                                    </span>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="col-md-4 mt-3">
                                            <div class="form-group">
                                                <label for="bank_fee_threshold">
                                                    Correspondent Bank Fee Threshold
                                                </label>

                                                <input type="number" step="any" min="0"
                                                    id="bank_fee_threshold"
                                                    name="bank_fee_threshold"
                                                    value="0"
                                                    class="form-control">

                                                @error('bank_fee_threshold')
                                                    <span class="invalid-feedback d-block">
                                                        <strong>{{ $message }}</strong>
                                                    </span>
                                                @enderror
                                            </div>
                                        </div>

                                    </div>

                                </div>

                            </div>

                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <hr>
                                <button type="submit" class="btn btn-primary">Submit</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script>
        function setFile(obj) {

            var file = obj.files[0];
            var filename = file.name;
            document.getElementById("file-name-display").innerHTML = filename;
        }
    </script>
@stop
