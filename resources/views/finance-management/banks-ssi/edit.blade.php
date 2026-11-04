@extends('layouts.cp')
@section('title', 'Edit Bank SSI - Finance Management')
@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Finance Management</span>
        </h1>
        <a href="{{ url('/finance-management/bank-ssi') }}" class="btn btn-primary mr-3"><i class="fal fa-arrow-alt-left"></i>
            Go Back </a>
    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false"
        data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                Edit Bank SSI
            </h2>
        </div>


        <form method="post" action="{{ url('/finance-management/bank-ssi/update') }}" enctype="multipart/form-data">
            @csrf
            <div class="panel-container show">
                <div class="panel-content">
                    @include('common.error-message')
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label" for="bank_id">Bank</label>
                                <!-- Display the bank name (disabled input) -->
                                <input type="text" id="bank" name="bank_name" value="{{ $bank->name }}"
                                    class="form-control" disabled>

                                <!-- Hidden input to store the bank ID -->
                                <input type="hidden" id="bank_id" name="bank_id" value="{{ $bank->id }}">
                                <input type="hidden" id="bankssi_id" name="bankssi_id" value="{{ $bankSSI->id }}">
                                <!-- Display validation error for bank_id -->
                                @error('bank_id')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror

                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label" for="currency_id">Currencies</label>
                                <select name="currency_id" autofocus id="currency_id" data-select2-id="currency_id"
                                    class="form-control select2 w-100" required>
                                    <option value="">Choose Currency</option>
                                    @foreach ($currencies as $currency)
                                        <option value="{{ $currency->id }}"
                                            {{ $bankSSI->currency_id == $currency->id ? 'selected' : '' }}
                                            data-currency-short-name="{{ $currency->short }}">
                                            {{ $currency->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <input type="hidden" name="currency_short_name" id="currency_short_name" value="">
                                @error('currency_id')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>
                    </div>
                    <fieldset class="border p-2 mt-4">
                        <legend class="w-auto">Intermediary Institution - BIC</legend>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label" for="swift_code">SWIFT Code</label>
                                            <input type="text" autofocus id="swift_code" name="swift_code"
                                                value="{{ $ssiDetails['56A: Intermediary Institution - BIC']['SWIFT Code'] }}"
                                                class="form-control" required>
                                            @error('swift_code')
                                                <span class="invalid-feedback"
                                                    role="alert"><strong>{{ $message }}</strong></span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label" for="bank_name">Bank Name</label>
                                            <input type="text" autofocus id="bank_name" name="bank_name"
                                                value="{{ $ssiDetails['56A: Intermediary Institution - BIC']['Bank Name'] }}"
                                                class="form-control" required>
                                            @error('bank_name')
                                                <span class="invalid-feedback"
                                                    role="alert"><strong>{{ $message }}</strong></span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label" for="bank_address">Bank Address</label>
                                            <input type="text" autofocus id="bank_address" name="bank_address"
                                                value="{{ $ssiDetails['56A: Intermediary Institution - BIC']['Bank Address'] }}"
                                                class="form-control" required>
                                            @error('bank_address')
                                                <span class="invalid-feedback"
                                                    role="alert"><strong>{{ $message }}</strong></span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </fieldset>
                    <fieldset class="border p-2 mt-4">
                        <legend class="w-auto">Account with the Institution - BIC</legend>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label" for="57a_swift_code">SWIFT Code</label>
                                            <input type="text" autofocus id="57a_swift_code" name="57a_swift_code"
                                                value="{{ $ssiDetails['57A: Account with the Institution - BIC']['SWIFT Code'] }}"
                                                class="form-control" required>
                                            @error('57a_swift_code')
                                                <span class="invalid-feedback"
                                                    role="alert"><strong>{{ $message }}</strong></span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label" for="57a_bank_name">Bank Name</label>
                                            <input type="text" autofocus id="57a_bank_name" name="57a_bank_name"
                                                value="{{ $ssiDetails['57A: Account with the Institution - BIC']['Bank Name'] }}"
                                                class="form-control" required>
                                            @error('57a_bank_name')
                                                <span class="invalid-feedback"
                                                    role="alert"><strong>{{ $message }}</strong></span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label" for="57a_bank_address">Bank Address</label>
                                            <input type="text" autofocus id="57a_bank_address" name="57a_bank_address"
                                                value="{{ $ssiDetails['57A: Account with the Institution - BIC']['Bank Address'] }}"
                                                class="form-control" required>
                                            @error('57a_bank_address')
                                                <span class="invalid-feedback"
                                                    role="alert"><strong>{{ $message }}</strong></span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </fieldset>
                    <fieldset class="border p-2 mt-4">
                        <legend class="w-auto">Beneficiary Customer - Name & Address</legend>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label" for="account_number">Account Number</label>
                                            <input type="text" autofocus id="account_number" name="account_number"
                                                value="{{ $ssiDetails['59: Beneficiary Customer - Name & Address']['Account Number'] }}"
                                                class="form-control" required>
                                            @error('account_number')
                                                <span class="invalid-feedback"
                                                    role="alert"><strong>{{ $message }}</strong></span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label" for="account_name">Account Name</label>
                                            <input type="text" autofocus id="account_name" name="account_name"
                                                value="{{ $ssiDetails['59: Beneficiary Customer - Name & Address']['Account Name'] }}"
                                                class="form-control" required>
                                            @error('account_name')
                                                <span class="invalid-feedback"
                                                    role="alert"><strong>{{ $message }}</strong></span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label" for="address">Address</label>
                                            <input type="text" autofocus id="address" name="address"
                                                value="{{ $ssiDetails['59: Beneficiary Customer - Name & Address']['Address'] }}"
                                                class="form-control" required>
                                            @error('address')
                                                <span class="invalid-feedback"
                                                    role="alert"><strong>{{ $message }}</strong></span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </fieldset>
                    <fieldset class="border p-2 mt-4">
                        <legend class="w-auto">Remittance Information</legend>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label" for="remittance_information">Remittance
                                                Information</label>
                                            <input type="text" autofocus id="remittance_information"
                                                name="remittance_information"
                                                value="{{ $ssiDetails['70: Remittance Information']['Remittance Information'] }}"
                                                class="form-control" required>
                                            @error('remittance_information')
                                                <span class="invalid-feedback"
                                                    role="alert"><strong>{{ $message }}</strong></span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </fieldset>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <div class="custom-control custom-switch mt-3">
                                    <input type="checkbox" class="custom-control-input"
                                        @if ($bankSSI->is_active) checked @endif id="is_active" value="1"
                                        name="is_active">
                                    <label class="custom-control-label" for="is_active">Is Active</label>
                                    <br />
                                    <small>Making this SSI active will automatically deactivate the
                                        currently active SSI for this Currency.</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <hr>
                                <button type="submit" class="btn btn-primary">Save</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

@stop
@section('scripts')
    <script>
        $(document).ready(function() {
            var banksSSIs = @json($bankSSIs);
            var banksSSI = @json($bankSSI);

            var bankSelectedValue = $('#bank_id').val();
            console.log("Bank selected: " + banksSSIs.length);
            // Filter out currencies that are already associated with the selected bank
            for (var i = 0; i < banksSSIs.length; i++) {
                if (bankSelectedValue == banksSSIs[i].bank_id && banksSSIs[i].currency_id != banksSSI.currency_id) {
                    $('#currency_id option').filter(function() {
                        return $(this).val() == banksSSIs[i].currency_id;
                    }).remove();
                }
            }

            $('#data-grid').dataTable({
                responsive: true,
                lengthChange: false,
                searching: false,
                paging: false,
                stateSave: true
            });

            var currencyShortName = $('#currency_id option:selected').data('currency-short-name');
            $('#currency_short_name').val(currencyShortName);

            $('#currency_id').on('change', function() {
                var currencyShortName = $('#currency_id option:selected').data('currency-short-name');
                $('#currency_short_name').val(currencyShortName);
            });

            $('.js-thead-colors a').on('click', function() {
                var theadColor = $(this).attr("data-bg");
                console.log(theadColor);
                $('#dt-basic-example thead').removeClassPrefix('bg-').addClass(theadColor);
            });

            $('.js-tbody-colors a').on('click', function() {
                var theadColor = $(this).attr("data-bg");
                console.log(theadColor);
                $('#dt-basic-example').removeClassPrefix('bg-').addClass(theadColor);
            });
        });
    </script>
    <style>
        .deleted-row {
            height: 1px;
            display: none;
        }

        fieldset.border.p-2,
        .table-bg-grey {
            background: #f9f9f9;
        }

        .directors-data .select2-container--open {
            width: 200px !important;
            position: relative;
        }

        .directors-data .select2-container--open .select2-dropdown--below {
            width: 200px;
            ;
        }
    </style>
@stop
