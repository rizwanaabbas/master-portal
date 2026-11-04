@extends('layouts.cp')
@section('title', 'Edit Withdraw Request - Crypto Transactions Requests')

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
                Edit Withdraw Request
            </h2>
        </div>

        <form method="post" action="{{ url('/client/crypto-transaction-requests/withdraw/update') }}"
            enctype="multipart/form-data" id="withdraw-crypto-form">
            @csrf
            <input type="hidden" name="id" id="id" value="{{ $transaction->id }}">
            <input type="hidden" name="balance" id="balance" value="0">
            <div class="panel-container show">
                <div class="panel-content">
                    @include('common.error-message')
                    <div class="row">
                        <div class="col-md-6">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="form-group">
                                        <label class="form-label" for="account_id">From Crypto Account</label>
                                        <select onchange="getAccountBalance(this.value, 'account_balance');"
                                            name="account_id" autofocus id="account_id" value=""
                                            data-select2-id="account_id" class="form-control select2 w-100" required>
                                            <option value="">Choose Crypto account</option>
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
                                        @error('account_id')
                                            <span class="invalid-feedback"
                                                role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="form-label" for="account_balance">Account Balance</label>
                                        <div class="form-control" id="account_balance" readonly>--</div>
                                    </div>
                                </div>
                            </div>

                            <div class="row mt-4">
                                <div class="col-md-8">
                                    <div class="form-group">
                                        <label class="form-label" id="crypto-amount-label" for="amount">Crypto
                                            Amount to Withdraw</label>
                                        <input type="number" verifyAmount(this.value); step="any" id="amount"
                                            name="amount" min="1" value="{{ $transaction->crypto_amount }}"
                                            class="form-control" required>
                                        <small id="crypto-amount-limits"></small>
                                        @error('amount')
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
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="form-label" for="name">Remarks</label>
                                        <textarea maxlength="92" required class="form-control" name="remarks" id="remarks" cols="30" rows="2"
                                            placeholder="Please provide relvant remarks for the transaction.">{{ $transaction->remarks }}</textarea>
                                        <small>maximum 92 characters allowed.</small>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-4">
                                @include('client-portal.partials.transactions.edit.supporting-documents')
                            </div>
                        </div>
                        <div class="col-md-6" style="border-left: 1px solid lightgray;">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="form-label" for="to_wallet_address">To Wallet
                                            Address</label>
                                        <input type="text" class="form-control" autocomplete="off"
                                            name="to_wallet_address" id="to_wallet_address"
                                            placeholder="Please provide the Wallet Address for Crypto Deposit."
                                            value="{{ $transaction->to_wallet_address }}" required />
                                    </div>
                                </div>
                            </div>
                            <div class="row  mt-4" id="destination-tag-section" style="display:none">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="form-label" for="destination_tag">Destination Tag</label>
                                        <input type="text" class="form-control" autocomplete="off"
                                            name="destination_tag" id="destination_tag"
                                            placeholder="Please provide the Wallet Destination Tag."
                                            value="{{ $transaction->destination_tag }}"  />
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-4">
                                <div class="col-md-12">
                                    <h3 style="color: red;"><i class="fal fa-exclamation-triangle"></i> Warning!</h3>
                                    <p>Please double check your Wallet address, you <strong>won't</strong> be able
                                        to
                                        change it once the transaction is created in the system.</p>
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

        $("#withdraw-crypto-form").on('submit', function(e) {
            e.preventDefault();

            let walletAddress = document.getElementById('to_wallet_address').value

            Swal.fire({
                title: "Are you sure?",
                text: `Please double check the Wallet Address (${walletAddress}). Once the amount is deposited to a wrong Wallet, can never be reversed.`,
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Yes, Submit!"
            }).then(function(result) {
                if (result.value) {
                    document.getElementById('withdraw-crypto-form').submit()
                }
            });
        })

        function getAccountBalance(account_id, balance_field_id) {
            if (account_id) {
                $.post("{{ url('/finance-management/get-balance') }}", {
                        "account_id": account_id
                    },
                    function(data, status) {
                        if(data.currency_short == 'XRP' || data.currency_short == 'EOS' || data.currency_short == 'XLM') {
                            $("#destination-tag-section").show();
                            $("#destination_tag").attr('required',true);
                        } else {
                            $("#destination_tag").removeAttr('required');
                            $("#destination-tag-section").hide();
                        }
                        $("#account_balance").html(data.formatted_balance);
                        if (!parseFloat(data.balance) > 0) {
                            $("#debit").attr("disabled", true);
                            $("#debit").prop('checked', false);
                            $("#balance").val();
                        } else {
                            $("#debit").attr("disabled", false);
                            $("#balance").val(parseFloat(data.balance));
                        }

                        if (balance_field_id == 'account_balance') {
                            $(`#crypto-amount-label`).html(`${data.currency_short} Amount to Withdraw`)
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

                        $("#debit").prop('checked', false);
                        $("#credit").prop('checked', false);
                        // $("#amount").val('');
                    });
            } else {
                clearCryptoHints(balance_field_id);
            }
        }

        function clearCryptoHints(balance_field_id) {
            $(`#${balance_field_id}`).html('--')
            $(`#crypto-amount-label`).html(`Crypto Amount to Withdraw`)
            $("#crypto-amount-limits").html('')
        }

        $(document).ready(function() {

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

            getAccountBalance('@php echo $transaction->account_id @endphp', 'account_balance')
        })
    </script>
@stop

@stop
