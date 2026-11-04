@extends('layouts.cp')
@section('title', 'New Deposit Request - Crypto Transactions Requests')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Crypto Transaction Requests</span>
        </h1>
        <a href="{{ url('/crypto-management/crypto-transactions') }}" class="btn btn-primary mr-3"><i
                class="fal fa-arrow-alt-left"></i> Go Back </a>
    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false"
        data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                New Deposit Request
            </h2>
        </div>

        <form method="post" action="{{ url('/crypto-management/crypto-transactions/deposit') }}"
            enctype="multipart/form-data">
            @csrf
            <input type="hidden" id="balance" value="0">
            <div class="panel-container show">
                <div class="panel-content">
                    @include('common.error-message')
                    <div class="row">
                        <div class="col-md-6">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="form-group">
                                        <label class="form-label" for="credit_account_id">To Crypto Account</label>
                                        <select
                                            onchange="getAccountBalance('crypto', this.value, 'crypto_account_balance');"
                                            name="credit_account_id" autofocus id="credit_account_id" value=""
                                            data-select2-id="credit_account_id" class="form-control select2 w-100" required>
                                            <option value="">Choose Crypto Account</option>
                                            @foreach ($accounts as $account)
                                                @if ($account->id == old('credit_account_id'))
                                                    <option value="{{ $account->id }}" selected>{{ $account->title . ' - ' . $account->account_number }}
                                                    </option>
                                                @else
                                                    <option value="{{ $account->id }}">{{ $account->title . ' - ' . $account->account_number }}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                        @error('credit_account_id')
                                            <span class="invalid-feedback"
                                                role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="form-label" for="crypto_account_balance">Account Balance</label>
                                        <div class="form-control" id="crypto_account_balance" readonly>--</div>
                                    </div>
                                </div>
                            </div>

                            <div class="row mt-4">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label" id="crypto-amount-label" for="amount">How much Crypto
                                            Amount you want to Deposit</label>
                                        <input type="number" step="any" id="amount" name="amount" min="1"
                                            value="{{ old('amount') }}" class="form-control" required>
                                        <small id="crypto-amount-limits"></small>
                                        @error('amount')
                                            <span class="invalid-feedback"
                                                role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="form-label" for="transaction_date">Date</label>
                                        <input type="text" required autocomplete="off" class="form-control"
                                            name="transaction_date" id="transaction_date" placeholder="Select date"
                                            required>
                                        @error('transaction_date')
                                            <span class="invalid-feedback"
                                                role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="form-label" for="transaction_time">Time (HH:MM)</label>
                                        <input type="text" value="" required autocomplete="off"
                                            class="form-control" name="transaction_time" id="transaction_time"
                                            placeholder="time">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-8 mt-4">
                                    <div class="form-group">
                                        <label class="form-label" for="document_0">Supporting Documents</label>
                                        <div class="row">
                                            <div class="col-md-9">
                                                <input type="file" class="form-control"
                                                    onchange="handleChangeDocument(this.value, this)"
                                                    name="supporting_documents[]" id="document_0" value="">
                                            </div>
                                            <div class="col-md-1">
                                                <button class="btn btn-primary btn-add-document d-none"
                                                    onclick="handleAddDocument(this)" type="button"><i
                                                        class="fal fa-plus"></i></button>
                                            </div>
                                            <div class="col-md-1 ml-2">
                                                <button class="btn btn-danger btn-remove-document d-none"
                                                    onclick="handleRemoveDocument('document_0', false, this)"
                                                    type="button"><i class="fal fa-times"></i></button>
                                            </div>
                                        </div>
                                        @error('supporting_documents')
                                            <span class="invalid-feedback"
                                                role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-4">

                            </div>

                        </div>
                        <div class="col-md-6" style="border-left: 1px solid lightgray;">
                            <div class="form-group">
                                <label class="form-label" for="name">Remarks</label>
                                <textarea maxlength="92" required class="form-control" name="remarks" id="remarks" cols="30" rows="2"
                                    placeholder="Please provide relvant remarks for the transaction."></textarea>
                                <small>maximum 92 characters allowed.</small>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <h3><i class="fal fa-exclamation-triangle"></i> Warning!</h3>
                                    <p>Please double check your request details, you <strong>won't</strong> be able to
                                        change it once the transaction is created in the system.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <hr>
                                <input type="hidden" name="crypto_system" value="{{$method}}">
                                <button id="submit" type="submit" class="btn btn-primary">Submit</button>
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
        var controls = {
            leftArrow: '<i class="fal fa-angle-left" style="font-size: 1.25rem"></i>',
            rightArrow: '<i class="fal fa-angle-right" style="font-size: 1.25rem"></i>'
        }

        function handleChangeDocument(value, e) {
            if (value) {
                e.closest('.row').querySelector('.btn-add-document').classList.remove('d-none')
                e.closest('.row').querySelector('.btn-remove-document').classList.remove('d-none')
            } else {
                e.closest('.row').querySelector('.btn-add-document').classList.add('d-none')
                e.closest('.row').querySelector('.btn-remove-document').classList.add('d-none')
            }
        }

        function handleAddDocument(e) {
            e.closest('.form-group').insertAdjacentHTML(
                'beforeend',
                '<div class="row mt-2"> <div class="col-md-9"> <input type="file" class="form-control" onchange="handleChangeDocument(this.value, this)" name="supporting_documents[]" value=""> </div> <div class="col-md-1"> <button class="btn btn-primary btn-add-document d-none" onclick="handleAddDocument(this)" type="button"><i class="fal fa-plus"></i></button> </div> <div class="col-md-1 ml-2"> <button class="btn btn-danger btn-remove-document d-none" onclick="handleRemoveDocument(null, true, this)" type="button"><i class="fal fa-times"></i></button> </div> </div>'
            )
        }

        function handleRemoveDocument(inputId, deleteContainer, e) {
            if (deleteContainer) {
                e.closest('.row').remove()
            } else {
                document.getElementById(inputId).value = ""
                e.closest('.row').querySelector('.btn-add-document').classList.add('d-none')
                e.closest('.row').querySelector('.btn-remove-document').classList.add('d-none')
            }
        }

        function getAccountBalance(type, account_id, balance_field_id) {
            if (account_id) {
                $.post("{{ url('/finance-management/get-balance') }}", {
                        "account_id": account_id
                    },
                    function(data, status) {
                        $("#" + type + "_account_balance").html(data.formatted_balance);
                        if (type == "debit" && !parseFloat(data.balance) > 0) {
                            $("#amount").attr("disabled", true);
                            $("#submit").attr("disabled", true);
                            $("#balance").val();

                        } else {
                            $("#balance").val(parseFloat(data.balance));
                            $("#amount").attr("disabled", false);
                            $("#submit").attr("disabled", false);
                        }

                        if (balance_field_id == 'crypto_account_balance') {
                            $(`#crypto-amount-label`).html(`How much ${data.currency_short} Amount you want to Deposit`)
                            $("#amount").attr({
                                "min": data.min_currency_limit
                            });

                            let max = ''

                            if (data.max_currency_limit != -1) {
                                max = `, max: ${Intl.NumberFormat('en-US').format(data.max_currency_limit)}`
                                $("#amount").attr({
                                    "max": data.max_currency_limit
                                });
                            }

                            $("#crypto-amount-limits").html(
                                `<b>Limits: </b>(min: ${Intl.NumberFormat('en-US').format(data.min_currency_limit)+max})`
                            )
                        }

                        // $("#amount").val('');
                    });
            } else {
                clearCryptoHints(balance_field_id);
            }
        }

        function clearCryptoHints(balance_field_id) {
            $(`#${balance_field_id}`).html('--')
            $(`#crypto-amount-label`).html(`How much Crypto Amount you want to Deposit`)
            $("#crypto-amount-limits").html('')
        }

        $(document).ready(function() {

            // not working
            $('.select2').select2();

            // minimum setup
            $('#transaction_date').datepicker({
                format: 'dd/mm/yyyy',
                todayHighlight: true,
                orientation: "bottom left",
                templates: controls,
                // startDate: "{{ \Carbon\Carbon::today()->format('d/m/Y') }}"
            });
        })
    </script>
@stop
