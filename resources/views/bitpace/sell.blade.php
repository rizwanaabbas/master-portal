@extends('layouts.cp')
@section('title', 'Sell New Crypto - Crypto Management')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Crypto Management</span>
        </h1>
        @include('crypto-management.crypto-transactions.view.account-balances')
        <a href="{{ url('/crypto-management/crypto-transactions') }}" class="btn btn-primary mr-3"><i
                class="fal fa-arrow-alt-left"></i> Go Back </a>
    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false"
        data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                Sell New Crypto
            </h2>
        </div>

        <form method="post" action="" onsubmit="event.preventDefault();"
            enctype="multipart/form-data">
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
                                <div class="col-md-8">
                                    <div class="form-group">
                                        <label class="form-label" for="receiver_account_id">To Fiat Account</label>
                                        <select name="receiver_account_id" autofocus id="receiver_account_id" value=""
                                            data-select2-id="receiver_account_id" class="form-control select2 w-100"
                                            required  onchange="getAccountBalance('fiat', false, this.value, 'fiat_account_balance');">
                                            <option value="">Choose Fiat Account</option>
                                        </select>
                                        @error('receiver_account_id')
                                            <span class="invalid-feedback"
                                                role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                        <span id="fiat-account-message" style="color: red;"></span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="form-label" for="fiat_account_balance">Fiat Account
                                            Balance</label>
                                        <div class="form-control" id="fiat_account_balance" readonly>--</div>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-4">

                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="form-label" id="crypto-amount-label" for="amount">Crypto
                                            coins to
                                            Sell</label>
                                        <input type="number" step="any" id="amount" name="amount"
                                            value="{{ old('amount') }}" class="form-control" required /> 
                                            <small id="crypto-amount-limits"></small>
                                        @error('amount')
                                            <span class="invalid-feedback"
                                                role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-1">
                                    <div class="currency-exchange-icon"><i class="fal fa-exchange"></i></div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="form-label" id="fiat-amount-label" for="amount">Fiat amount to Sell Crypto
                                            </label>
                                        <input type="number" step="any" id="fiat_amount" name="fiat_amount"
                                            value="{{ old('amount') }}" class="form-control" required /> 
                                           
                                        @error('amount')
                                            <span class="invalid-feedback"
                                                role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-3 mt-4">
                                    <div class="form-group">
                                        <button type="button" class="btn btn-primary" id="price-button" style="display:none;" onclick="getQuote()">Get Price</button>
                                    </div>
                                </div>
                            </div>

                        </div>
                        <div class="col-md-6" style="border-left: 1px solid lightgray;">
                           
                                <label class="form-label" for="name">Remarks</label>
                                <textarea maxlength="92"  class="form-control" name="remarks" id="remarks" cols="30" rows="2"
                                    placeholder="Please provide relvant remarks for the transaction."></textarea>
                                <small>maximum 92 characters allowed.</small>
                                <div class="form-group crypto-summary">
                                <div class="alert alert-warning">When you sell cryptocurrency , amount of coins and commission fee will be deducted from your merchant balance.</div>
                              
                                <small id="price-summary">
                                
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <hr>
                                <input type="hidden" name="token" id="token" value="">
                                <button button="submit" class="btn btn-primary" id="refresh-button" style="display:none;" onclick="getQuote()"><i class="fal fa-repeat"></i></button>
                                <button id="submit-button" type="button" onclick="createOrder()" style="display:none"  class="btn btn-primary">Sell Now</button>
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

    
        function createOrder() {
        
            var coin_amount = $('#coin_amount').val();
            var token = $('#token').val();
            var fiat_account_id = $('#receiver_account_id').val();
            var crypto_account_id = $('#debit_account_id').val();
           
            if(crypto_account_id == '') {
                alert("Please select the client account");
                return false;
            }

            if(coin_amount != '' && crypto_account_id != '' &&  token != '') {
                $.post("{{ url('/crypto-management/crypto-transactions/bitpace/buy-save') }}", {
                      
                        "coin_amount": coin_amount,
                        "crypto_account_id": crypto_account_id,
                        "fiat_account_id": fiat_account_id,
                        "order_type": "SELL",
                        "token": token,

                    },
                    function(data, status) {
                            if(data != '') {
                            var data = $.parseJSON(data);
                            if(data) {
                                //console.log(data);
                                if(data.status == 'DECLINED') {
                                    $('#crypto-message').text(data.message+'! Please click Get price button to get new price.').addClass('alert alert-danger');
                                    $('#submit-button').hide();
                                   // $('#refresh-button').show();
                                    $('#price-button').show();
                                    return false;
                                }
                                if(data.order_id) {

                                    var html =  '  <div class="container mt-2 crypto_order_details_pop"><h3>Following are the Order Details</h3><div class="row mt-2" id="orderDetails">';


                                    $.each(data, function(key, val) {
                                        var formattedKey = key.replace(/_/g, ' ').replace(/\b\w/g, function(char) {
                                            return char.toUpperCase();
                                        });

                                        html += '<div class="col-md-6">';
                                        html += '<div class="row mb-3">';
                                        html += '<div class="col-md-6"><strong>' + formattedKey + ':</strong></div>';
                                        html += '<div class="col-md-6">' + (val ? val : '---') + '</div>'; // Check for null values
                                        html += '</div>';
                                        html += '</div>';
                                    });
                                    html += '</div></div>';

                                    $('#submit-button').hide();
                                    $('#refresh-button').hide();
                                    $('#price-button').hide();
                                    showConfirmPopup('Crypto Order completed',html,'Close', "{{ url('/crypto-management/crypto-transactions') }}");
                                }
                            
                            }
                        }
                    }
                );
                return false;
            }
        } //create order 

        function getQuote() {
 
            var fiat_account_id = $('#receiver_account_id').val();
            var crypto_account_id = $('#debit_account_id').val();
            var coin = crypto_currency_short;
            var coin_amount = $('#amount').val();
            var fiat_amount = $('#fiat_amount').val();
           
            if(fiat_account_id != '' && crypto_account_id != '' ) {
                if(coin_amount <= 0 && fiat_amount <= 0) {
                    $('#crypto-message').html('Please fill Either crypto of Fiat amount to trade.').addClass('alert alert-danger');
                    return false;
                }
                
                $.post("{{ url('/crypto-management/crypto-transactions/bitpace/price-quote') }}", {
                        "fiat_account_id": fiat_account_id,
                        "crypto_account_id": crypto_account_id,
                        "coin": coin,
                        "coin_amount": coin_amount,
                        "fiat_amount": fiat_amount,
                        "order_type": "SELL",
                        "currency_amount_field": currency_amount_field,

                    },
                    function(data, status) {
                        var data = $.parseJSON(data);
                        if(data) {
                            console.log(data.status);
                            if(data.status == 'DECLINED') {
                               // alert(data.message);
                               $('#submit-button').hide();
                               $('#refresh-button').hide();
                               $('#price-button').show();
                               $('#price-summary').html('');
                                $('#crypto-message').text(data.message).addClass('alert alert-danger');
                                return false;
                            }
                            var amount_to_sell = parseFloat((data.total_amount)).toFixed(2);
                            $('#token').val(data.token);
                            $('#coin_amount').val(data.cryptocurrency_amount);
                            $('#fiat_amount').val(amount_to_sell);
                            $('#amount').val(data.cryptocurrency_amount);
                            var commission_percentage_applied = parseFloat((data.merchant_commission_fee/data.total_amount)*100);
                            commission_percentage_applied = commission_percentage_applied.toFixed(2);
                            var html = ' <div class="crypto_price_quote alert alert-info"><h3> Price Summary</h3> <strong>Rate: '+data.cryptocurrency_amount+' '+data.cryptocurrency+' = '+data.total_amount+' '+data.currency+' </strong><br>Commission Rate:'+commission_percentage_applied+'% <br>Commission Fee:'+data.merchant_commission_fee
+' '+data.currency+' <br>Approx FIAT Amount:'+amount_to_sell+' '+data.currency+' <br></div>';
                            $('#price-summary').html(html);
                         
                            $('#submit-button').show();
                            $('#refresh-button').hide();
                            $('#price-button').hide();
                            $('#crypto-message').html('').removeClass('alert alert-danger');
                        } else {
                            $('#submit-button').hide();
                            $('#crypto-message').html('Sorry! we could not find any prices for given amount.').addClass('alert alert-danger');
                        }
                    }
                );
            } else {
                $('#crypto-message').html("Please select account's to trade.").addClass('alert alert-danger').focus();
            }
        } //get quote 

         //debounce to block multiple keystroke requests
         let timer;
         var currency_amount_field = 'amount';
         $('#amount, #fiat_amount').on('keyup', function() {
           

            window.clearTimeout(timer);
            currency_amount_field = this.id;
            // here is the closures javascript magic happens.
            const value = $(this).val();
            timer = setTimeout(() => {
                if(value === $(this).val() && $(this).val()!== ''){
                    if(currency_amount_field == 'amount')
                        $('#fiat_amount').val('');
                    if(currency_amount_field == 'fiat_amount')
                        $('#amount').val('');
                   // getQuote();
                   $('#price-button').show();
                   $('#refresh-button').hide();
                   $('#submit-button').hide();
                }
            }, 500);
        });
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
var crypto_currency_short = '';
var account_balance = 0;
        function getAccountBalance(type, fetchFiatAccounts, account_id, balance_field_id) {
            //getAccountBalance('crypto', true, this.value, 'crypto_account_balance');
            if (account_id) {
                $.post("{{ url('/finance-management/get-balance') }}", {
                        "account_id": account_id,
                        "needFiatAccounts": fetchFiatAccounts,
                        "source": 'crypto'
                    },
                    function(data, status) {
                        account_balance = parseFloat(data.balance);
                        crypto_currency_short = data.currency_short;
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
                       
                        if(balance_field_id == 'fiat_account_balance') {
                            $('#fiat-amount-label').html(`${data.currency_short} amount to sell Crypto`)
                        }
                        if (balance_field_id == 'crypto_account_balance') {
                            $(`#crypto-amount-label`).html(`Crypto ${data.currency_short} coins to Sell`)
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

                        $("#amount, #fiat_amount").val('');
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
            $('#receiver_account_id').empty().append('<option value="">Choose Fiat Account</option>');
            if (fiatAccounts.length > 0) {
                var fiatAccountsDropdown = document.getElementById("receiver_account_id");
                fiatAccounts.map(function(fiatAccount) {
                    let option = document.createElement("option")
                    fiatAccountsDropdown.appendChild(option);
                    option.value = fiatAccount.id
                    option.innerHTML = fiatAccount.title + " - " + fiatAccount.account_number

                    fiatAccountsDropdown.appendChild(option)
                })
            } else {
                $('#fiat-account-message').html(
                    'No Fiat Accounts found for the selected Client.');
                setTimeout(() => {
                    $('#fiat-account-message').html('');
                }, 5000);
            }

        }

       
    </script>
@stop
