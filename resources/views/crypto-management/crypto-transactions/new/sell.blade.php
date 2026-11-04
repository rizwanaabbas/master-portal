@extends('layouts.cp')
@section('title', 'New Sell Request - Crypto Management')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Crypto Management</span>
        </h1>
        <a href="{{ url('/crypto-management/crypto-transactions') }}" class="btn btn-primary mr-3"><i
                class="fal fa-arrow-alt-left"></i> Go Back </a>
    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false"
        data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                New Sell Request
            </h2>
        </div>

        <form method="post" action="{{ url('/crypto-management/crypto-transactions/sell') }}"
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
                                        <label class="form-label" for="debit_account_id">From Crypto Account</label>
                                        <select
                                            onchange="getAccountBalance('crypto', true, this.value, 'crypto_account_balance');"
                                            name="debit_account_id" autofocus id="debit_account_id" value=""
                                            data-select2-id="debit_account_id" class="form-control select2 w-100" required>
                                            <option value="">Choose Crypto Account</option>
                                            @foreach ($accounts as $account)
                                                @if ($account->id == old('debit_account_id'))
                                                    <option value="{{ $account->id }}" selected>{{ $account->title . ' - ' . $account->account_number }}
                                                    </option>
                                                @else
                                                    <option value="{{ $account->id }}">{{ $account->title . ' - ' . $account->account_number }}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                        @error('debit_account_id')
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

                            {{-- <div class="custom-control custom-switch mt-4">
                                <input type="checkbox" class="custom-control-input" value="1" id="selling-mode"
                                    name="selling-mode" onclick='changeSellingMode(this);'>
                                <label class="custom-control-label" id="selling-mode-label" for="selling-mode">Selling
                                    Mode (Amount in Fiat)</label>
                                <br />
                                <small>
                                    <span id="selling-mode-desc-false">
                                        Amount in Fiat: Sell Crypto that worths the following <i>Amount</i> in the selected
                                        Fiat Currency.
                                    </span>
                                    <br />
                                    <span id="selling-mode-desc-true">
                                        Amount in Crypto: Sell Crypto of the following <i>Amount</i>.</small>
                                </span>
                            </div> --}}

                            <div class="row mt-4">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label" for="receiver_account_id">To Fiat Account</label>
                                        <select name="receiver_account_id" autofocus id="receiver_account_id" value=""
                                            data-select2-id="receiver_account_id" class="form-control select2 w-100"
                                            required>
                                            <option value="">Choose Fiat Account</option>
                                        </select>
                                        @error('receiver_account_id')
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
                            <div class="row mt-4">
                                <div class="col-md-8">
                                    <div class="form-group">
                                        <label class="form-label" id="crypto-amount-label" for="amount">How much Crypto
                                            Amount you want to Sell</label>
                                        <input type="number" step="any" id="amount" name="amount" min="1"
                                            onblur="verifyAmount(this.value);" value="{{ old('amount') }}"
                                            class="form-control" required>
                                        <small id="crypto-amount-limits"></small>
                                        @error('amount')
                                            <span class="invalid-feedback"
                                                role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
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

                        </div>
                        <div class="col-md-6" style="border-left: 1px solid lightgray;">
                            <div class="form-group">
                                <label class="form-label" for="name">Remarks</label>
                                <textarea maxlength="92" required class="form-control" name="remarks" id="remarks" cols="30" rows="2"
                                    placeholder="Please provide relvant remarks for the transaction."></textarea>
                                <small>maximum 92 characters allowed.</small>
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

        // function changeSellingMode(checkbox) {
        //     document.getElementById('selling-mode-label').innerHTML =
        //         `Selling Mode (${checkbox.checked ? 'Amount in Crypto' : 'Amount in Fiat'})`
        //     document.getElementById(`selling-mode-desc-${!checkbox.checked}`).style.fontWeight = '400'
        //     document.getElementById(`selling-mode-desc-${checkbox.checked}`).style.fontWeight = '700'
        // }

        function verifyAmount(amount) {
            var balance = parseFloat($("#balance").val());
            if (balance < parseFloat(amount)) {
                alert("Insufficient balance available, please use lower value than available balance (" + balance + ")");
                $("#amount").val(0);
                return false;
            } else {
                return true;
            }
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

        function getAccountBalance(type, fetchFiatAccounts, account_id, balance_field_id) {
            if (account_id) {
                $.post("{{ url('/finance-management/get-balance') }}", {
                        "account_id": account_id,
                        "needFiatAccounts": fetchFiatAccounts,
                        "source": ''
                    },
                    function(data, status) {
                        if (fetchFiatAccounts) {
                            clearCryptoHints("crypto_account_balance")
                            populateFiatAccounts(data.fiat_accounts)
                        }

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
                            $(`#crypto-amount-label`).html(`How much ${data.currency_short} Amount you want to Sell`)
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

                        $("#amount").val('');
                    });
            } else {
                clearCryptoHints(balance_field_id)
            }
        }

        function clearCryptoHints(balance_field_id) {
            $(`#${balance_field_id}`).html('--')
            $(`#crypto-amount-label`).html(`How much Crypto Amount you want to Sell`)
            $("#crypto-amount-limits").html('')
        }

        function populateFiatAccounts(fiatAccounts) {

            if (fiatAccounts.length > 0) {
                var fiatAccountsDropdown = document.getElementById("receiver_account_id")

                // remove all existing options
                if (fiatAccountsDropdown.options.length > 1) {
                    let index = fiatAccountsDropdown.options.length;
                    while (index > 0) {
                        fiatAccountsDropdown.remove(index)
                        index--
                    }
                }

                fiatAccounts.map(function(fiatAccount) {
                    let option = document.createElement("option")
                    option.value = fiatAccount.id
                    option.innerHTML = fiatAccount.title + " - " + fiatAccount.account_number

                    fiatAccountsDropdown.appendChild(option)
                })
            } else {
                document.getElementById('crypto-account-message').innerHTML =
                    'No Crypto Accounts found for the selected Client.'
                setTimeout(() => {
                    document.getElementById('crypto-account-message').innerHTML = ''
                }, 5000);
            }

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
