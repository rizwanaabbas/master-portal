@extends('layouts.cp')
@section('title', 'Edit Buy Request - Crypto Transactions Requests')

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
                Edit Buy Request
            </h2>
        </div>

        <form method="post" action="{{ url('/client/crypto-transaction-requests/buy/update') }}"
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
                                        <label class="form-label" for="debit_account_id">Fiat Account</label>
                                        <select onchange="getAccountBalance(this.value, 'account_balance');"
                                            name="debit_account_id" autofocus id="debit_account_id" value=""
                                            data-select2-id="debit_account_id" class="form-control select2 w-100" required>
                                            <option value="">Choose Account</option>
                                            @foreach ($accounts as $account)
                                                @if ($account->id == $transaction->account_id)
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
                                        <label class="form-label" for="account_balance">Account Balance</label>
                                        <div class="form-control" id="account_balance" readonly>--</div>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-4">
                                <div class="col-md-8">
                                    <div class="form-group">
                                        <label class="form-label" for="receiver_account_id">Crypto Currency</label>
                                        <select onchange="getAccountBalance(this.value, 'crypto_account_balance');"
                                            name="receiver_account_id" autofocus id="receiver_account_id" value=""
                                            data-select2-id="receiver_account_id" class="form-control select2" required>
                                            <option value="">Choose Crypto Currency</option>
                                            @foreach ($cryptoAccounts as $cryptoAccount)
                                                @if ($cryptoAccount->account_number == $transaction->receiver->account_number)
                                                    <option value="{{ $cryptoAccount->id }}" selected>
                                                        {{ $cryptoAccount->currency->short . ' - ' . $cryptoAccount->account_number }}
                                                    </option>
                                                @else
                                                    <option value="{{ $cryptoAccount->id }}">{{ $cryptoAccount->title . ' - ' . $cryptoAccount->account_number }}
                                                    </option>
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
                                        <label class="form-label" for="crypto_account_balance">Crypto Account
                                            Balance</label>
                                        <div class="form-control" id="crypto_account_balance" readonly>--</div>
                                    </div>
                                </div>
                            </div>

                            <div class="row mt-4">
                                <div class="col-md-8">
                                    <div class="form-group">
                                        <label class="form-label" id="crypto-amount-label" for="amount">How much Crypto
                                            you want to Buy</label>
                                        <input type="number" step="any" id="amount" name="amount"
                                            value="{{ $transaction->crypto_amount }}" class="form-control" required />
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
                                        <input type="text" autocomplete="off" class="form-control"
                                            value="{{ \Carbon\Carbon::parse($transaction->transaction_date)->format('d/m/Y') }}"
                                            name="transaction_date" id="transaction_date" placeholder="Select date"
                                            readonly required>
                                        @error('transaction_date')
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

        // function verifyAmount(type) {
        //     var balance = parseFloat($("#balance").val());
        //     var amount = parseFloat($("#amount").val());

        //     if (type == "Debit") {
        //         if (balance < parseFloat(amount)) {
        //             alert("Insufficient balance available, please use lower value than available balance (" + balance +
        //                 ")");
        //             $("#amount").val(0);
        //             return false;
        //         } else {
        //             return true;
        //         }
        //     } else {
        //         return true;
        //     }
        // }

        function getAccountBalance(account_id, balance_field_id) {
            if (account_id) {
                $.post("{{ url('/finance-management/get-balance') }}", {
                        "account_id": account_id
                    },
                    function(data, status) {
                        $(`#${balance_field_id}`).html(data.formatted_balance);
                        if (!parseFloat(data.balance) > 0) {
                            $("#debit").attr("disabled", true);
                            $("#debit").prop('checked', false);
                            $("#balance").val();
                        } else {
                            $("#debit").attr("disabled", false);
                            $("#balance").val(parseFloat(data.balance));
                        }

                        if (balance_field_id == 'crypto_account_balance') {
                            $(`#crypto-amount-label`).html(`How much ${data.currency_short} you want to Buy`)
                            $("#amount").attr({
                                "min": data.min_currency_limit
                            });

                            let max = ''

                            if (data.max_currency_limit != -1) {
                                max = `, max: ${data.max_currency_limit}`
                                $("#amount").attr({
                                    "max": data.max_currency_limit
                                });
                            }

                            $("#crypto-amount-limits").html(
                                `<b>Limits: </b>(min: ${data.min_currency_limit+max})`
                            )
                        }

                        $("#debit").prop('checked', false);
                        $("#credit").prop('checked', false);
                        // $("#amount").val('');
                    }
                );
            } else {
                $(`#${balance_field_id}`).html('--')
                $(`#crypto-amount-label`).html(`How much Crypto you want to Buy`)
                $("#crypto-amount-limits").html('')
            }
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
            getAccountBalance('@php echo $transaction->receiver->id @endphp', 'crypto_account_balance')
            // document.getElementById('amount').value = '@php echo $transaction->crypto_amount @endphp'
        })
    </script>
@stop

@stop
