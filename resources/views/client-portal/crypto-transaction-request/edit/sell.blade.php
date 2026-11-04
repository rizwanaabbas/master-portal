@extends('layouts.cp')
@section('title', 'Edit Sell Request - Crypto Transactions Requests')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Crypto Transaction Requests</span>
        </h1>
        <a href="{{ url('/client/crypto-transaction-requests/list') }}" class="btn btn-primary mr-3"><i
                class="fal fa-arrow-alt-left"></i> Go Back </a>
    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false"
        data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                Edit Sell Request
            </h2>
        </div>

        <form method="post" action="{{ url('/client/crypto-transaction-requests/sell/update') }}"
            enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="id" id="id" value="{{ $transaction->id }}">
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
                                            onchange="getAccountBalance('crypto', this.value, 'crypto_account_balance');"
                                            name="debit_account_id" autofocus id="debit_account_id" value=""
                                            data-select2-id="debit_account_id" class="form-control select2 w-100" required>
                                            <option value="">Choose Crypto Account</option>
                                            @foreach ($cryptoAccounts as $cryptoAccount)
                                                @if ($cryptoAccount->id == $transaction->account_id)
                                                    <option value="{{ $cryptoAccount->id }}" selected>
                                                        {{ $cryptoAccount->title . ' - ' . $cryptoAccount->account_number }}
                                                    </option>
                                                @else
                                                    <option value="{{ $cryptoAccount->id }}">{{ $cryptoAccount->title . ' - ' . $cryptoAccount->account_number }}
                                                    </option>
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
                                <div class="col-md-8">
                                    <div class="form-group">
                                        <label class="form-label" for="receiver_account_id">To Fiat Account</label>
                                        <select name="receiver_account_id" autofocus id="receiver_account_id" value=""
                                            data-select2-id="receiver_account_id" class="form-control select2 w-100"
                                            required>
                                            <option value="">Choose Fiat Account</option>
                                            @foreach ($accounts as $fiatAccount)
                                                @if ($fiatAccount->account_number == $transaction->receiver->account_number)
                                                    <option value="{{ $fiatAccount->id }}" selected>{{ $fiatAccount->title . ' - ' . $fiatAccount->account_number }}
                                                    </option>
                                                @else
                                                    <option value="{{ $fiatAccount->id }}">{{ $fiatAccount->title . ' - ' . $fiatAccount->account_number }}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                        @error('receiver_account_id')
                                            <span class="invalid-feedback"
                                                role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="form-label" for="transaction_date">Date</label>
                                        <input type="hidden" id="transaction_date">
                                        <input type="text" required autocomplete="off" class="form-control"
                                            value="{{ \Carbon\Carbon::parse($transaction->transaction_date)->format('d/m/Y') }}"
                                            name="transaction_date" id="transaction_date" placeholder="Select date" readonly
                                            required>
                                        @error('transaction_date')
                                            <span class="invalid-feedback"
                                                role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-4">
                                <div class="col-md-8">
                                    <div class="form-group">
                                        <label class="form-label" id="crypto-amount-label" for="amount">How much Crypto
                                            Amount you want to Sell</label>
                                        <input type="number" step="any" id="amount" name="amount" min="1"
                                            value="{{ $transaction->crypto_amount }}" class="form-control" required>
                                        <small id="crypto-amount-limits"></small>
                                        @error('amount')
                                            <span class="invalid-feedback"
                                                role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                                @include('client-portal.partials.transactions.edit.supporting-documents')

                            </div>

                        </div>
                        <div class="col-md-6" style="border-left: 1px solid lightgray;">
                            <div class="form-group">
                                <label class="form-label" for="name">Remarks</label>
                                <textarea maxlength="92" required class="form-control" name="remarks" id="remarks" cols="30" rows="2"
                                    placeholder="Please provide relvant remarks for the transaction.">{{ $transaction->remarks }}</textarea>
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

        // function verifyAmount(amount) {
        //     var balance = parseFloat($("#balance").val());
        //     if (balance < parseFloat(amount)) {
        //         alert("Insufficient balance available, please use lower value than available balance (" + balance + ")");
        //         $("#amount").val(0);
        //         return false;
        //     } else {
        //         return true;
        //     }
        // }

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

                        // $("#amount").val('');
                    });
            } else {

                clearCryptoHints(balance_field_id);
            }
        }

        function clearCryptoHints(balance_field_id) {
            $(`#${balance_field_id}`).html('--')
            $(`#crypto-amount-label`).html(`How much Crypto Amount you want to Sell`)
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
                startDate: "{{ \Carbon\Carbon::today()->format('d/m/Y') }}"
            });
            $('#transaction_date').attr('disabled', true);

            getAccountBalance('crypto', '@php echo $transaction->account_id @endphp', 'crypto_account_balance')
        })
    </script>
@stop
