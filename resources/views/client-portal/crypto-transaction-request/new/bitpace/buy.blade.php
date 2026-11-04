@extends('layouts.cp')
@section('title', 'New Buy Request - Crypto Transactions Requests')

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
                New Buy Request
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
                                            <option value="">Choose Crypto Account</option>
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

                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="form-label" id="crypto-amount-label" for="amount">Crypto
                                            coins to
                                            Buy</label>
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
                                        <label class="form-label" id="fiat-amount-label" for="amount">Fiat amount to Buy Crypto
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
                                <input type="hidden" name="token" id="token" value="">
                                <button type="button" class="btn btn-primary" id="refresh-button" style="display:none;" onclick="getQuote()"><i class="fal fa-repeat"></i></button>
                                <button type="button" id="submit-button" onclick="createOrder()" class="btn btn-primary"  style="display:none;">Submit</button>
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
            var remarks = $('#remarks').val();
           
            if(receiver_account_id == '') {
                alert("Please select the client account");
                return false;
            }

            if(coin_amount != '' && receiver_account_id != '' &&  token != '') {
                $.post("{{ url('/client/crypto-transaction-requests/buy') }}", {
                      
                        "amount": coin_amount,
                        "receiver_account_id": receiver_account_id,
                        "debit_account_id": debit_account_id,
                        "order_type": "BUY",
                        "token": token,
                        "remarks": remarks,

                    },
                    function(data, status) {
                            if(data != '') {
                            var data = $.parseJSON(data);
                           console.log(data);
                            if(data) {
                                //console.log(data.status);
                                if(  data.type && data.status == 'DECLINED') {
                                    $('#crypto-message').text(data.message).addClass('alert alert-danger');
                                    $('#submit-button').show();
                                    $('#price-button').hide();
                                    return false;
                                }
                                if(data.status == 'DECLINED') {
                                    $('#crypto-message').text(data.message+'! Please click refresh button to get new price.').addClass('alert alert-danger');
                                    $('#submit-button').hide();
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
                                    showConfirmPopup('Crypto Order completed',html,'Close', "{{ url('client/crypto-transaction-requests/list') }}");
                                }
                            
                            }
                        }
                    }
                );
                return false;
            }
        } //create order 
  
        //send request to get latest price quote for order
        function getQuote() {

            var fiat_account_id = $('#debit_account_id').val();
            var crypto_account_id = $('#receiver_account_id').val();
            var coin = crypto_currency_short;
            var coin_amount = $('#amount').val();
            var fiat_amount = $('#fiat_amount').val();
            
            if(fiat_account_id != '' && crypto_account_id != '') {
                if(coin_amount <= 0 && fiat_amount <= 0) {
                    $('#crypto-message').html('Please fill Either crypto of Fiat amount to trade.').addClass('alert alert-danger');
                    return false;
                }
                $.post("{{ url('/client/crypto-transaction-requests/bitpace/price-quote') }}", {
                        "fiat_account_id": fiat_account_id,
                        "crypto_account_id": crypto_account_id,
                        "coin": coin,
                        "coin_amount": coin_amount,
                        "fiat_amount": fiat_amount,
                        "order_type": "BUY",
                        "currency_amount_field": currency_amount_field,

                    },
                    function(data, status) {
                        var data = $.parseJSON(data);

                        if(data) {
                            //console.log(data.status);
                            if(data.status == 'DECLINED') {
                               // alert(data.message);
                               $('#submit-button').hide();
                               $('#price-button').show();
                               $('#price-summary').html('');
                                $('#crypto-message').text(data.message).addClass('alert alert-danger');
                                return false;
                            }
                            var commission_percentage_applied = parseFloat((data.merchant_commission_fee/data.total_amount)*100);
                            commission_percentage_applied = commission_percentage_applied.toFixed(2);
                            var amount_to_sell = parseFloat((data.total_amount)).toFixed(2);
                            $('#fiat_amount').val(amount_to_sell);
                            $('#amount').val(data.cryptocurrency_amount);
                            $('#token').val(data.token);
                            $('#coin_amount').val(data.cryptocurrency_amount);
                            var html = ' <div class="crypto_price_quote alert alert-info"><h3> Price Summary</h3> <strong>Rate: '+data.cryptocurrency_amount+' '+data.cryptocurrency+' = '+data.total_amount+' '+data.currency+' </strong><br>Commission Rate:'+commission_percentage_applied+'% <br>Commission Fee:'+data.merchant_commission_fee
+' '+data.currency+' <br>Approx FIAT Amount:'+amount_to_sell+' '+data.currency+' <br></div>';
                            $('#price-summary').html(html);
                         
                            $('#submit-button').show();
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
        function validateHhMm(inputField) {
            var isValid = /^([0-1]?[0-9]|2[0-4]):([0-5][0-9])(:[0-5][0-9])?$/.test(inputField.value);

            if (isValid) {
                inputField.style.backgroundColor = '#bfa';
            } else {
                inputField.style.backgroundColor = '#fba';
            }
            return isValid;
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
        })
    </script>
@stop

@stop
