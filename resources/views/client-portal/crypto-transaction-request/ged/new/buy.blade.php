@extends('layouts.cp')
@section('title', 'New GED Buy Request - Crypto Transactions Requests')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>GED Transaction Requests</span>
        </h1>
        <a href="{{ url('/client/crypto-transaction-requests/list') }}" class="btn btn-primary mr-3"><i
                class="fal fa-arrow-alt-left"></i> Go Back </a>
    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false"
        data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                New GED Buy Request
            </h2>
        </div>

        <form method="post" action="#" enctype="multipart/form-data">
            @csrf
            <input type="hidden" id="balance" value="0">
            <div class="panel-container show">
                <div class="panel-content">
                    @include('common.error-message')
                    <div id="crypto-message"></div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="form-group">
                                        <label class="form-label" for="debit_account_id">Fiat Account</label>
                                        <select onchange="getAccountBalance(this.value, 'account_balance');"
                                            name="debit_account_id" autofocus id="debit_account_id" value=""
                                            data-select2-id="debit_account_id" class="form-control select2 w-100" required>
                                            <option value="">Choose Fiat Account</option>
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
                                        <label class="form-label" for="account_balance">Account Balance</label>
                                        <div class="form-control" id="account_balance" readonly>--</div>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-4">
                                <div class="col-md-8">
                                    <div class="form-group">
                                        <label class="form-label" for="receiver_account_id">Crypto Account</label>
                                        <select onchange="getAccountBalance(this.value, 'crypto_account_balance');"
                                            name="receiver_account_id" autofocus id="receiver_account_id" value=""
                                            data-select2-id="receiver_account_id" class="form-control select2" required>
                                            <option value="">Choose GED Account</option>
                                            @foreach ($cryptoAccounts as $cryptoAccount)
                                                <option value="{{ $cryptoAccount->id }}">{{ $cryptoAccount->title . ' - ' . $cryptoAccount->account_number }}
                                                </option>
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
                                        <label class="form-label" id="crypto-amount-label" for="amount">Amount to 
                                            Buy GED</label>
                                        <input type="number" step="any" id="amount" name="amount"
                                            value="{{ old('amount') }}" class="form-control" required /> 
                                            <small id="crypto-amount-limits"></small>
                                        @error('amount')
                                            <span class="invalid-feedback"
                                                role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6" style="border-left: 1px solid lightgray;">
                            <div class="form-group">
                                <label class="form-label" for="name">Wallet Address</label>
                                <input type="text" step="any" id="wallet_address" name="wallet_address"
                                            value="" class="form-control" required /> 
                            </div>
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
                                        <div id="price-summary"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <hr>
                                <button type="button" id="submit-button" onclick="createOrder()" class="btn btn-primary"  style="display:block;">Submit</button>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </form>
    </div>

@section('scripts')
    <script>
        // crypto order request
        function createOrder() {
            var coin_amount = $('#amount').val();
            var token = $('#token').val();
            var debit_account_id = $('#debit_account_id').val();
            var receiver_account_id = $('#receiver_account_id').val();
            var wallet_address = $('#wallet_address').val();
            var remarks = $('#remarks').val();
           
            if(receiver_account_id == '') {
                $('#crypto-message').html("Please select the client account").addClass('alert alert-danger');
                return false;
            }

            if(coin_amount != '' && receiver_account_id != '' &&  token != '') {
                $.post("{{ url('/client/crypto-transaction-requests/ged/buy') }}", {
                      
                        "amount": coin_amount,
                        "receiver_account_id": receiver_account_id,
                        "debit_account_id": debit_account_id,
                        "order_type": "BUY",
                        "token": token,
                        "remarks": remarks,
                        "wallet_address": wallet_address

                    },
                    function(data, status) {
                            if(data != '') {
                            var data = $.parseJSON(data);
                           console.log(data);
                            if(data) {
                                //console.log(data.status);
                                if(  data.type && data.status == 'DECLINED') {
                                    $('#crypto-message').text(data.message).addClass('alert alert-danger');
                                    return false;
                                }
                                if(  data.type && data.status == 'SUCCESS') {
                                    $('#crypto-message').text(data.message).addClass('alert alert-info');
                                    if(data.tr_id) {
                                        window.location = "{{ url('client/crypto-transaction-requests/otp')}}/" + data.tr_id;
                                    }
                                    return false;
                                }
                            }
                        }
                    }
                );
                return false;
            }
        } //create order 

        //preloader for every ajax request start and stop should move to common
        $(document)  
        .ajaxStart(function () {  
            $('#loading-spinner').show();  
        })  
        .ajaxStop(function () {  
            $('#loading-spinner').hide();  
        });
        // order detail info Popup
        function showConfirmPopup(title,text,button_text,url) {
            Swal.fire({
                html:text,
                title: title,
                //text: text,
                icon: "info",
                showCancelButton: false,
                confirmButtonText: button_text
            }).then(function(result) {
              if(url !== '')
                    window.location = url;
            });
       }

        var crypto_currency_short = '';
        var controls = {
            leftArrow: '<i class="fal fa-angle-left" style="font-size: 1.25rem"></i>',
            rightArrow: '<i class="fal fa-angle-right" style="font-size: 1.25rem"></i>'
        }

        function changeBuyingMode(checkbox) {
            document.getElementById('buying-mode-label').innerHTML =
                `Buying Mode (${checkbox.checked ? 'Amount in Crypto' : 'Amount in Fiat'})`
            document.getElementById(`buying-mode-desc-${!checkbox.checked}`).style.fontWeight = '400'
            document.getElementById(`buying-mode-desc-${checkbox.checked}`).style.fontWeight = '700'
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

        function getAccountBalance(account_id, balance_field_id) {
            if (account_id) {
                $.post("{{ url('/finance-management/get-balance') }}", {
                        "account_id": account_id
                    },
                    function(data, status) {
                        $(`#${balance_field_id}`).html(data.formatted_balance);
                        crypto_currency_short = data.currency_short;
                        if (!parseFloat(data.balance) > 0) {
                            $("#debit").attr("disabled", true);
                            $("#debit").prop('checked', false);
                            $("#balance").val();
                        } else {
                            $("#debit").attr("disabled", false);
                            $("#balance").val(parseFloat(data.balance));
                        }
                        if(balance_field_id == 'account_balance') {
                            
                            $('#fiat-amount-label').html(`${data.currency_short} amount to Buy Crypto`)
                        }
                        if (balance_field_id == 'crypto_account_balance') {
                            $(`#crypto-amount-label`).html(`Crypto ${data.currency_short} coins to Buy`)
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
                            getCryptoWalletAddress(account_id);
                        }

                        $("#debit").prop('checked', false);
                        $("#credit").prop('checked', false);
                        $("#amount").val('');
                    }
                );
            } else {
                $(`#${balance_field_id}`).html('--')
                $(`#crypto-amount-label`).html(`How much Crypto Amount you want to Buy`)
                $("#crypto-amount-limits").html('')
            }
        }
        function getCryptoWalletAddress(account_id) {
            $('#wallet_address').val('').attr('disabled',false);
            if (account_id) {
                $.post("{{ url('/client/crypto-transaction-requests/ged/wallet-address') }}", {
                        "account_id": account_id
                    },
                    function(data, status) {
                        var data = $.parseJSON(data);
                        if(data.wallet_address != '' && data.wallet_address != null) {
                            $('#wallet_address').val(data.wallet_address).attr('disabled',true);
                        }
                    });
            }
        }
    </script>
@stop

@stop
