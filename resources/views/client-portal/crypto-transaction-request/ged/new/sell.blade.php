@extends('layouts.cp')
@section('title', 'New GED Sell Request - Crypto Transactions Requests')

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
                New GED Sell Request
            </h2>
        </div>

        
        
            <div class="panel-container show">
                <div class="panel-content">
                    @include('common.error-message')
                    <div id="crypto-message"></div>
                    <!-- <div class="row">
                        <div class="col-md-12">
                            <div class="row mt-4" id="form-fields">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="form-label" for="account">Client Account</label>
                                        <select onchange="getAccountAddress(this.value);"
                                            name="account_id" autofocus id="account_id"
                                            data-select2-id="account_id" class="form-control select2 w-100" required>
                                            <option value="">Choose Account</option>
                                            @foreach ($accounts as $account)
                                                @if ($account->id == $account_id))
                                                    <option value="{{ $account->id }}" selected>
                                                        {{ $account->title . ' - ' . $account->account_number }}
                                                    </option>
                                                @else
                                                    <option value="{{ $account->id }}">{{ $account->title . ' - ' . $account->account_number }}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label" for="name">Wallet Address</label>
                                    <input type="text" step="any" id="wallet_address" name="wallet_address"
                                            value="" class="form-control"  /> 
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div> -->
                    <!--  address data container -->
                    <div class="crypto-address-data-container w-50 ml-0 mr-0 mx-auto text-center" style="display:none">
                        <div class="crypto-address-info alert alert-warning">
                        <div class="row">
                                <div class="col-md-12">
                                    <div class="row mt-4">
                                        <div class="col-md-12 reset-data align-items-center justify-content-center" style= "text-align:center" id="qr-code-section">
                                            
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-12 text-center">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="row mt-4">
                                            <div class="col-md-4">
                                                <label class="form-label" for="crypto-address">Redemption Address</label>
                                            </div>
                                            <div class="col-md-8" >
                                                <span  class="reset-data copyable" id="crypto-address">xxxxxxx</span> <span title="Copy Address" onclick="copy_text('crypto-address', 'Crypto Address ')">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-copy" viewBox="0 0 16 16">
                                                        <path fill-rule="evenodd" d="M4 2a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2zm2-1a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1zM2 5a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1v-1h1v1a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h1v1z"/>
                                                    </svg>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="row mt-4">
                                            <div class="col-md-4">
                                                <label class="form-label" for="crypto-redemption-address-status">Address Status</label>
                                            </div>
                                            <div class="col-md-8  reset-data" id="crypto-redemption-address-status">
                                                xxxxxxx
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="row mt-4">
                                            <div class="col-md-4">
                                                <label class="form-label" for="coin-type">Coin Type</label>
                                            </div>
                                            <div class="col-md-8  reset-data" id="coin-type">
                                                xxxxxxx
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="row mt-4">
                                            <div class="col-md-4">
                                                <label class="form-label" for="account-title">Account Title</label>
                                            </div>
                                            <div class="col-md-8  reset-data" id="account-title">
                                                xxxxxxx
                                            </div>
                                        </div>
                                    </div>
                                </div>
                               
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="row mt-4">
                                            <div class="col-md-4">
                                                <label class="form-label" for="email">Email</label>
                                            </div>
                                            <div class="col-md-8  reset-data" id="email">
                                                xxxxxxx
                                            </div>
                                        </div>
                                    </div>
                                </div>                           
                            </div>
                        </div>
                    </div> <!-- end address data container -->

                    <!-- <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <hr>
                                <button type="button" class="btn btn-primary" style="display:none" id="create_address_btn">Submit</button>
                            </div>
                        </div>
                    </div> -->
                </div>
            </div>
    </div>
<style>

svg.bi.bi-copy {
    color: #ffc800;
    font-weight: bolder;
    cursor: pointer;
    margin-left: 10px;
}
.copyable{
    font-weight:bold;
}
</style>
<script>
   
    var selected_network = '';
   function getAccountAddress(account_id) {
        
        if(account_id == '') {
            selected_network = $('#network').val();
            account_id = $('#account_id').val();
        }

        if (account_id) {
                    $.post("{{ url('/client/crypto-transaction-requests/ged/wallet-address') }}", {
                            "account_id": account_id,
                          
                        },
                        function(data, status) {
                        
                            if(data != '') {
                                address = jQuery.parseJSON(data);
                                // if( address.wallet_address == '' || address.wallet_address == null ) {
                                //     resetAddressData();
                                // } else 
                                if(address.redemption_address_status == 'Active' && address.redemption_address != '' ) {
                                    $('#crypto-message').html('<div class="alert alert-info"> Please scan the Qr code below to redeem.</div>');
                                    $('#form-fields').hide();

                                    $('#create_address_btn').hide();
                                    $("#wallet_address").hide().attr('required', false );
                                    $('.crypto-address-data-container').show();
                                    var qr_code_url = 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data='+address.redemption_address;
                                    
                                    $('#qr-code-section').html('<img id="qr-code-image" src="'+qr_code_url+'">');
                                    $("#crypto-address").text(address.redemption_address);
                                    $("#crypto-redemption-address-status").html(address.redemption_address_status);
                                    var coin_type = address.name + ' ('+address.short+') '+'<img src="'+address.logo+'" width="25px">';
                                    $("#coin-type").html(coin_type);
                                    $("#account-title").html(address.title);
                                    $("#email").html(address.email); 
                                } else {
                                    $('#crypto-message').html('<div class="alert alert-danger"> You dont have enough balance, Please buy some GED before selling.</div>');
                                }
                            } else {
                                resetAddressData();
                            }
                        }
                    );
                } 
        }

      

function resetAddressData() {
    $('#crypto-message').html('');
    $("#create_address_btn").show();
    $("#wallet_address").show().attr('required',true);
    $(".reset-data").html('');
    $('.crypto-address-data-container').hide();
} 

function copy_text( eId,what) {
    var copyText = document.getElementById(eId);
    var textArea = document.createElement("textarea");
    textArea.value = copyText.textContent;
    document.body.appendChild(textArea);
    textArea.select();
    document.execCommand("Copy");
    textArea.remove();
    Swal.fire({
        position: "top-center",
        icon: "success",
        title: what+" " +copyText.textContent+ " has been copied",
        showConfirmButton: false,
        timer: 1500
    });
}
var curr_account_id = '';
setTimeout(function(){
    curr_account_id = '{{$account_id}}';
        //console.log(selected_account_id);
    if(curr_account_id) {
        getAccountAddress(curr_account_id);
    } else {
        $('#crypto-message').html('<div class="alert alert-danger"> You dont have enough balance, Please buy some GED before selling.</div>');
    }
    $('#create_address_btn').on('click',function() {
        if($('#account_id').val() == '') {
            return false;
        } else {
            
            $('#create_address_btn').addClass('btn-secondary, disabled').hide();
            $('#loading-spinner').show();
            //$('#create-address-form').submit();
        }
    });
},300);
</script>
@stop
