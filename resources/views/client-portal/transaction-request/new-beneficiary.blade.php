@extends('layouts.cp')
@section('title', 'New Beneficiary - Transactions Requests')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Transaction Requests</span>
        </h1>
        <a href="{{ url('/client/transaction-requests/beneficiaries') }}" class="btn btn-primary mr-3"><i
                class="fal fa-arrow-alt-left"></i> Go Back </a>
    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false"
        data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                New Beneficiary
            </h2>
        </div>

        <form method="post" action="{{ url('/client/transaction-requests/beneficiaries/store') }}"
            enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="balance" id="balance" value="0">
            <div class="panel-container show">
                <div class="panel-content">
                    @include('common.error-message')
                    <div class="row">
                        <div class="col-md-6">
                            
                            <div class="row">
                                <div class="col-md-4 ">
                                    <div class="form-group">
                                        <label class="form-label" for="currency_short_code">Account Currency</label>
                                        <select name="currency_short_code"  autofocus id="currency_short_code"
                                            value="" data-select2-id="currency_short_code" class="form-control select2 w-100" required>
                                            <option value="">Choose Currency</option>
                                                @foreach ($currencies as $currency)
                                                    <option value="{{ $currency->id }}"
>
                                                        {{ $currency->name }} - {{ $currency->short }}
                                                    </option>
                                                @endforeach
                                        </select>
                                        @error('currency_short_code')
                                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-4 ">
                                    <div class="form-group">
                                        <label class="form-label" for="beneficiary_country">Beneficiary Country</label>
                                        <select name="beneficiary_country"  autofocus id="beneficiary_country"
                                            value="" data-select2-id="beneficiary_country" class="form-control select2 w-100" required>
                                            <option value="">Choose Country</option>
                                                @foreach ($countries as $country)
                                                    <option value="{{ $country->id }}"
>
                                                        {{ $country->name }}
                                                    </option>
                                                @endforeach
                                        </select>
                                        @error('beneficiary_country')
                                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-4 ">
                                    <div class="form-group">
                                        <label class="form-label" for="currency_short_code">Bank Account Country</label>
                                        <select name="bank_account_country"  autofocus id="bank_account_country"
                                            value="" data-select2-id="bank_account_country" class="form-control select2 w-100" required>
                                            <option value="">Choose Country</option>
                                                @foreach ($countries as $country)
                                                    <option value="{{ $country->id }}"
>
                                                        {{ $country->name }}
                                                    </option>
                                                @endforeach
                                        </select>
                                        @error('currency_short_code')
                                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12 mt-4">
                                    <div class="form-group">
                                        <label class="form-label" for="name">Name</label>
                                        <input type="text" id="name" name="name" value="{{ old('name') }}"
                                            class="form-control" required>
                                        @error('name')
                                            <span class="invalid-feedback"
                                                role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-4 mt-4">
                                    <div class="form-group">
                                        <label class="form-label" for="account_number">Account Number</label>
                                        <input type="number" min="1" step="any" id="account_number"
                                            name="account_number" value="{{ old('account_number') }}" class="form-control"
                                            required>
                                        @error('account_number')
                                            <span class="invalid-feedback"
                                                role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-8 mt-4">
                                    <div class="form-group">
                                        <label class="form-label" for="iban">IBAN Number</label>
                                        <input type="text" id="iban" name="iban" value="{{ old('iban') }}"
                                            class="form-control">
                                        @error('iban')
                                            <span class="invalid-feedback"
                                                role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-12 mt-4">
                                    <div class="form-group">
                                        <label class="form-label" for="address">Beneficiary Address</label>
                                        <textarea required class="form-control" name="address" id="address" cols="30" rows="2"
                                            placeholder="Please provide the beneficiary address.">{{ old('address') }}</textarea>
                                    </div>
                                </div>

                            </div>
                        </div>
                        <div class="col-md-6" style="border-left: 1px solid lightgray;">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="form-group">
                                        <label class="form-label" for="bank_name">Bank Name</label>
                                        <input type="text" id="bank_name" name="bank_name" value="{{ old('bank_name') }}"
                                            class="form-control" required>
                                        @error('bank_name')
                                            <span class="invalid-feedback"
                                                role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="form-label" for="swift_code">Swift Code</label>
                                        <input type="text" id="swift_code" name="swift_code"
                                            value="{{ old('swift_code') }}" class="form-control">
                                        @error('swift_code')
                                            <span class="invalid-feedback"
                                                role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row mt-4">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="form-label" for="bank_address">Bank Address</label>
                                        <textarea required class="form-control" name="bank_address" id="bank_address" cols="30" rows="2"
                                            placeholder="Please provide the bank address.">{{ old('bank_address') }}</textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-4">
                                <div class="col-md-12">
                                    <h3><i class="fal fa-1x fa-info-circle"></i> Information!</h3>
                                    <p>We may take upto 24 hours to approve the beneficiary, once approved, you will get
                                        email notification accordingly.</p>
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

@section('scripts')
    <script>
        var controls = {
            leftArrow: '<i class="fal fa-angle-left" style="font-size: 1.25rem"></i>',
            rightArrow: '<i class="fal fa-angle-right" style="font-size: 1.25rem"></i>'
        }

        function verifyAmount(type) {
            var balance = parseFloat($("#balance").val());
            var amount = parseFloat($("#amount").val());

            if (type == "Debit") {
                if (balance < parseFloat(amount)) {
                    alert("Insufficient balance available, please use lower value than available balance (" + balance +
                        ")");
                    $("#amount").val(0);
                    return false;
                } else {
                    return true;
                }
            } else {
                return true;
            }
        }

        function getAccountBalance(account_id) {
            $.post("{{ url('/finance-management/get-balance') }}", {
                    "account_id": account_id
                },
                function(data, status) {
                    $("#account_balance").html(data.formatted_balance);
                    if (!parseFloat(data.balance) > 0) {
                        $("#debit").attr("disabled", true);
                        $("#debit").prop('checked', false);
                        $("#balance").val();
                    } else {
                        $("#debit").attr("disabled", false);
                        $("#balance").val(parseFloat(data.balance));
                    }

                    $("#debit").prop('checked', false);
                    $("#credit").prop('checked', false);
                    $("#amount").val(0);
                });
        }

        $(document).ready(function() {

            $('.select2').select2();

            // minimum setup
            $('#transaction_date').datepicker({
                format: 'dd/mm/yyyy',
                todayHighlight: true,
                orientation: "bottom left",
                templates: controls,
                endDate: "{{ \Carbon\Carbon::today()->format('d/m/Y') }}"
            });
        })
    </script>
@stop

@stop
