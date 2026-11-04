@extends('layouts.cp')
@section('title', 'New Internal Transfer Transaction - Finance Management')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Finance Management</span>
        </h1>
        <a href="{{ url('/finance-management/transactions') }}" class="btn btn-primary mr-3"><i
                class="fal fa-arrow-alt-left"></i> Go Back </a>
    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false"
        data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                New Internal Transfer Transaction
            </h2>
        </div>

        <form method="post" action="{{ url('/finance-management/transactions/store/internal') }}"
            enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="balance" id="balance" value="0">
            <input type="hidden" name="credit_currency_id" id="credit_currency_id">
            <input type="hidden" name="debit_currency_id" id="debit_currency_id">
            <div class="panel-container show">
                <div class="panel-content">
                    @include('common.error-message')
                    <div class="row">
                        <div class="col-md-6">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="form-group">
                                        <label class="form-label" for="credit_account_id">Credit Account</label>
                                        <select onchange="getAccountBalance('credit', this.value);" name="credit_account_id"
                                            autofocus id="credit_account_id" value=""
                                            data-select2-id="credit_account_id" class="form-control select2 w-100" required>
                                            <option value="">Choose Client Account</option>
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
                                        <label class="form-label" for="credit_account_balance">Account Balance</label>
                                        <div class="form-control" id="credit_account_balance" readonly>--</div>
                                    </div>
                                </div>
                            </div>

                            {{-- credit account details card --}}
                            <div id="credit-account-card" class="mt-4"></div>


                            <div class="row mt-4 mb-4">
                                <div class="col-md-8">
                                    <div class="form-group">
                                        <label class="form-label" for="debit_account_id">Debit Account</label>
                                        <select onchange="getAccountBalance('debit', this.value);" name="debit_account_id"
                                            autofocus id="debit_account_id" value=""
                                            data-select2-id="debit_account_id" class="form-control select2 w-100" required>
                                            <option value="">Choose Client Account</option>
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
                                        <label class="form-label" for="debit_account_balance">Account Balance</label>
                                        <div class="form-control" id="debit_account_balance" readonly>--</div>
                                    </div>
                                </div>
                            </div>

                            {{-- debit account details card --}}
                            <div id="debit-account-card" class="my-4"></div>

                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <div class="row">
                                        <div class="col-md-7">
                                            <div class="form-group">
                                                <label class="form-label" for="amount">Transaction Amount</label>
                                                <input onblur="verifyAmount(this.value);" type="number" step="any"
                                                    id="amount" name="amount" min="0"
                                                    onkeyup="handleOnAmountChange(this)" value="{{ old('amount') }}"
                                                    class="form-control" required>
                                                @error('amount')
                                                    <span class="invalid-feedback"
                                                        role="alert"><strong>{{ $message }}</strong></span>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="col-md-5">
                                            <div class="form-group">
                                                <div class="custom-control custom-switch mt-5">
                                                    <input type="checkbox" onchange="handleOnChangeIsZeroize(this)"
                                                        class="custom-control-input" id="is_zeroize" value="1"
                                                        name="is_zeroize">
                                                    <label class="custom-control-label" for="is_zeroize">Zeroize</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="form-label" for="transaction_date">Transaction Date</label>
                                        <input type="text" value="{{ old('transaction_date') }}" autocomplete="off"
                                            class="form-control" name="transaction_date" id="transaction_date"
                                            placeholder="Select date" required>
                                        @error('transaction_date')
                                            <span class="invalid-feedback"
                                                role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="form-label" for="transaction_time">Time (HH:MM)</label>
                                        <input type="text" value="{{ old('transaction_time') }}" required
                                            autocomplete="off" class="form-control" name="transaction_time"
                                            id="transaction_time" placeholder="time">
                                    </div>
                                </div>
                            </div>

                            <div class="row exchange-rate" style="display: none">
                                <div class="col-md-12">
                                    <div class="alert alert-warning">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <p>Please provide the exchange rate if you are making cross currency
                                                    transaction.</p>
                                            </div>
                                            {{-- <div class="col-md-6">
                                                <label class="form-label" for="ex_rate">Exchange Rate</label>
                                                <input required readonly onchange="checkIfNotZero(this)" min="1"
                                                    type="text" autocomplete="off" class="form-control" name="ex_rate"
                                                    value="1" id="ex_rate">
                                            </div> --}}
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <label for="ex_rate">Exchange Rate</label>
                                                <input required readonly onchange="checkIfNotZero(this)"
                                                    onkeyup="handleOnExRateChange(this)" min="0" type="text"
                                                    autocomplete="off" class="form-control" name="ex_rate"
                                                    value="1" id="ex_rate" required />
                                            </div>
                                            <div class="col-md-6">
                                                <label for="exchanged_amount">Exchanged Amount</label>
                                                <input onkeyup="calculateExchangeRate(this.value)" type="number"
                                                    step="any" id="exchanged_amount" name="exchanged_amount"
                                                    min="1" value="{{ old('exchanged_amount') }}"
                                                    class="form-control" required />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            @include('finance-management.transactions.partials.new-supporting-documents')

                        </div>
                        <div class="col-md-6" style="border-left: 1px solid lightgray;">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="form-label" for="credit_remarks">Credit Remarks</label>
                                        <textarea maxlength="92" class="form-control" required name="credit_remarks" id="credit_remarks" cols="30"
                                            rows="2" placeholder="Please provide relvant remarks for the transaction.">{{ old('credit_remarks') }}</textarea>
                                        <small>maximum 92 characters allowed.</small>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label" for="debit_remarks">Debit Remarks</label>
                                        <textarea maxlength="92" class="form-control" required name="debit_remarks" id="debit_remarks" cols="30"
                                            rows="2" placeholder="Please provide relvant remarks for the transaction.">{{ old('debit_remarks') }}</textarea>
                                        <small>maximum 92 characters allowed.</small>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <hr>
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

        function getAccountBalance(type, account_id) {
            $.post("{{ url('/finance-management/get-balance') }}", {
                    "account_id": account_id
                },
                function(data, status) {
                    $("#" + type + "_account_balance").html(data.balance);
                    $("#" + type + "_currency_id").val(data.currency_id);
                    // getIbanInfo(account_id, type);

                    if (type == "debit") {
                        if (!parseFloat(data.balance) > 0) {
                            $("#amount").attr("disabled", true);
                            $("#submit").attr("disabled", true);
                            $("#balance").val();

                        } else {
                            $("#balance").val(parseFloat(data.balance));
                            $("#amount").attr("disabled", false);
                            $("#submit").attr("disabled", false);
                        }
                    } else {
                        $("#amount").attr("disabled", false);
                    }

                    $("#amount").val('');
                    setExchangeRate();
                });
        }

        function checkIfNotZero(obj) {
            if (!parseFloat(obj.value) > 0) {
                Swal.fire({
                    title: "Wrong Value",
                    text: "Exchange rate can't be zero, provide a value greater than zero",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonText: "Ok"
                }).then(function(result) {});

                $(obj).val('');
            }
        }

        function handleOnExRateChange(obj) {
            if (parseFloat(obj.value) > 0) {
                let amount = $("#amount").val() !== "" ? $("#amount").val() : 1
                $("#exchanged_amount").val(obj.value * amount)
            }
        }

        function calculateExchangeRate(exchangedAmount) {
            let amount = $("#amount").val() !== "" ? $("#amount").val() : exchangedAmount
            $("#ex_rate").val(exchangedAmount / amount)
        }

        function setExchangeRate() {

            const debit_currency_id = parseInt($("#debit_currency_id").val());
            const credit_currency_id = parseInt($("#credit_currency_id").val());

            if (debit_currency_id > 0 && credit_currency_id > 0 && debit_currency_id != credit_currency_id) {
                $(".exchange-rate").css("display", "block");
                $("#ex_rate").prop("required", true);
                $("#exchanged_amount").prop("required", true);
                $("#ex_rate").prop("readonly", false);
                $("#ex_rate").prop("placeholder", "please provide exchange rate");
                $("#ex_rate").val("");
            } else {
                $(".exchange-rate").css("display", "none");
                // $("#ex_rate").prop("readonly", true);
                $("#exchanged_amount").removeAttr('required');
                $("#ex_rate").removeAttr('required');
                // $("#ex_rate").val(1);
            }
        }

        // function handleOnChangeIsZeroize(checkbox) {
        //     if (checkbox.checked) {
        //         Swal.fire({
        //             title: "Are you sure?",
        //             text: "All of the Debit Account's amount will be used for this transfer.",
        //             icon: "warning",
        //             showCancelButton: true,
        //             confirmButtonText: "Yes, Zeroize it!"
        //         }).then(function(result) {
        //             if (result.value) {
        //                 const amount = document.getElementById('amount')
        //                 amount.value = 0
        //                 amount.disabled = true
        //             }
        //         });
        //     } else {
        //         document.getElementById('amount').disabled = false
        //     }
        // }

        function handleOnAmountChange(obj) {
            const debit_currency_id = parseInt($("#debit_currency_id").val());
            const credit_currency_id = parseInt($("#credit_currency_id").val());

            if (debit_currency_id > 0 && credit_currency_id > 0 && debit_currency_id != credit_currency_id) {
                $("#ex_rate").prop("readonly", false);
                let amount = obj.value
                var ex_rate = $("#ex_rate").val()

                $("#exchanged_amount").val(ex_rate * amount)
            } else {
                $("#ex_rate").prop("readonly", true);
                $("#ex_rate").val(1);
            }
        }

        function handleOnChangeIsZeroize(checkbox) {
            const amount = document.getElementById('amount')

            if (checkbox.checked) {
                Swal.fire({
                    title: "Are you sure?",
                    text: "All of the Debit Account's amount will be used for this transaction.",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonText: "Yes, Zeroize it!"
                }).then(function(result) {
                    if (result.value) {
                        amount.value = $("#balance").val()
                        handleOnAmountChange(amount)
                        amount.disabled = true
                    } else {
                        checkbox.checked = false
                    }
                });
            } else {
                document.getElementById('amount').disabled = false
                amount.value = 0
                handleOnAmountChange(amount)
            }

        }

        $(document).ready(function() {

            setExchangeRate();
            // not working
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

        function setCompanyType(type) {

            if (type == "Other") {
                $("#other-company-type").removeClass("disabled")
            } else {
                $("#other-company-type:not([class*='disabled'])").addClass("disabled");
            }
        }

        function setFile(obj) {

            var file = obj.files[0];
            var filename = file.name;
            document.getElementById("file-name-display").innerHTML = filename;
        }

        // function createBankAccountCard(data) {
        //     return `
    //     <div class="card col-md-12 shadow-sm">
    //         <div class="card-header  d-flex justify-content-between align-items-center">
    //             <h5 class="mb-0">Bank Account Details</h5>

    //         </div>
    //         <div class="card-body" id="bank-account-content">
    //             <div class="row">

    //                 <div class="col-md-6 mb-3">
    //                     <strong>IBAN:</strong> <span id="iban">${data.iban}</span>
    //                 </div>
    //                 <div class="col-md-6 mb-3">
    //                     <strong>Account Number:</strong> <span>${data.account_number}</span>
    //                 </div>
    //                 <div class="col-md-6 mb-3">
    //                     <strong>BIC:</strong> <span>${data.bic}</span>
    //                 </div>

    //                 <div class="col-md-6 mb-3">
    //                     <strong>Bank Name:</strong> <span>${data.bank_name}</span>
    //                 </div>
    //                 <div class="col-md-6">
    //                     <strong>Bank Address:</strong>
    //                     <p class="mb-0">${data.bank_address}</p>
    //                 </div>
    //             </div>
    //         </div>
    //     </div>`;
        // }

        // function getIbanInfo(account_id, type) {
        //     $.get("{{ url('/client/transaction-requests/get-iban-info') }}/"+account_id, function(response, status){
        //        var account = response.data;
        //        console.log(account);
        //        var formated = '';
        //        if(account)
        //             formated = createBankAccountCard(account);

        //         document.getElementById(type + '-account-card').innerHTML = formated;
        //     });
        // }

        function updateDebitAccounts(creditAccountId) {
            var accounts = @json($accounts);
            var creditAccount = accounts.find(account => account.id == creditAccountId);

            var debitSelect = $("#debit_account_id");
            debitSelect.empty();
            debitSelect.append('<option value="">choose client account</option>');

            if (creditAccount) {
                var filteredAccounts;
                if (creditAccount.virtual_bank_account_id) {
                    // Filter by currency and not the same account
                    filteredAccounts = accounts.filter(account => account.account_currency === creditAccount
                        .account_currency && account.id != creditAccountId);
                } else {
                    // Show all except SEPA accounts and the selected credit account
                    filteredAccounts = accounts.filter(account => account.openpayd_account_id === null && account.id !=
                        creditAccountId);
                }

                filteredAccounts.forEach(function(account) {
                    debitSelect.append('<option value="' + account.id + '">' + account.title + ' - ' + account
                        .account_number + '</option>');
                });
            }
        }

        // $("#credit_account_id").change(function() {
        //     var creditAccountId = $(this).val();
        //     if (creditAccountId) {
        //         updateDebitAccounts(creditAccountId);
        //     } else {
        //         $("#debit_account_id").empty();
        //         $("#debit_account_id").append('<option value="">choose client account</option>');
        //     }
        // });
    </script>
@stop
