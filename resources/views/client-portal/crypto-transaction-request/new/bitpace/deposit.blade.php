@extends('layouts.cp')
@section('title', 'Deposit Address Details - Bitpace Address Management')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Bitpace Address Management</span>
        </h1>
        <a href="{{ url('/client/crypto-transaction-requests/list') }}" class="btn btn-primary mr-3"><i
                class="fal fa-arrow-alt-left"></i> Go Back </a>
    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false"
        data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                Crypto Deposit Address Details
            </h2>
        </div>

        <form method="post" action="{{ url('/client/crypto-transaction-requests/bitpace/store-address') }}"
            enctype="multipart/form-data">
            @csrf
            <div class="panel-container show">
                <div class="panel-content">
                    @include('common.error-message')
                    <div class="row">
                        <div class="col-md-12">
                            <div class="row mt-4">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="form-label" for="account">Client Account</label>
                                        <select onchange="getNetworkList(this.value);" name="account_id" autofocus
                                            id="account_id" data-select2-id="account_id" class="form-control select2 w-100"
                                            required>
                                            <option value="">Choose Account</option>
                                            @foreach ($accounts as $account)
                                                @if ($account->id == $account_id)
                                                    )
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
                                        <label class="form-label" for="network">Network</label>
                                        <select onchange="getAccountAddress('')" name="network" autofocus id="network"
                                            data-select2-id="network" class="form-control  w-100" required>
                                            <option value="">Select Network</option>
                                        </select>
                                        @error('network')
                                            <span class="invalid-feedback"
                                                role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!--  address data container -->
                    <div class="crypto-address-data-container w-50 ml-0 mr-0 mx-auto text-center" style="display:none">
                        <div class="crypto-address-info alert alert-warning">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="row mt-4">
                                        <div class="col-md-12 reset-data align-items-center justify-content-center"
                                            style= "text-align:center" id="qr-code-section">

                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-12 text-center">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="row mt-4">
                                            <div class="col-md-4">
                                                <label class="form-label" for="crypto-address">Address</label>
                                            </div>
                                            <div class="col-md-8">
                                                <span class="reset-data copyable" id="crypto-address">xxxxxxx</span> <span
                                                    title="Copy Address"
                                                    onclick="copy_text('crypto-address','Crypto Address')">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                        fill="currentColor" class="bi bi-copy" viewBox="0 0 16 16">
                                                        <path fill-rule="evenodd"
                                                            d="M4 2a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2zm2-1a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1zM2 5a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1v-1h1v1a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h1v1z" />
                                                    </svg>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row xrp-row" style="display:none">
                                    <div class="col-md-12">
                                        <div class="row mt-4">
                                            <div class="col-md-4">
                                                <label class="form-label" for="destination-tag">Destination Tag</label>
                                            </div>
                                            <div class="col-md-8">
                                                <span class="reset-data copyable" id="destination-tag">xxxxxxx</span> <span
                                                    title="Copy Destination Tag"
                                                    onclick="copy_text('destination-tag','Destination Tag')">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                        fill="currentColor" class="bi bi-copy" viewBox="0 0 16 16">
                                                        <path fill-rule="evenodd"
                                                            d="M4 2a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2zm2-1a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1zM2 5a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1v-1h1v1a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h1v1z" />
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
                                                <label class="form-label" for="data-network">Network</label>
                                            </div>
                                            <div class="col-md-8  reset-data" id="data-network">
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
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="row mt-4">
                                            <div class="col-md-4">
                                                <label class="form-label" for="create-date">Create Date</label>
                                            </div>
                                            <div class="col-md-8  reset-data" id="create-date">
                                                xxxxxxx
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div> <!-- end address data container -->

                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <hr>
                                <input type="hidden" name="type" value="client" id="client">
                                <button type="submit" class="btn btn-primary" style="display:none"
                                    id="create_address_btn">Submit</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
    <style>
        svg.bi.bi-copy {
            color: #ffc800;
            font-weight: bolder;
            cursor: pointer;
            margin-left: 10px;
        }

        .copyable {
            font-weight: bold;
        }
    </style>
    <script>
        var selected_network = '';

        function getAccountAddress(account_id) {

            if (account_id == '') {
                selected_network = $('#network').val();
                account_id = $('#account_id').val();
            }

            if (account_id) {
                $.post("{{ url('/client/crypto-transaction-requests/bitpace/get-address-data') }}", {
                        "account_id": account_id,
                        "network": selected_network
                    },
                    function(data, status) {

                        if (data != '') {
                            address = jQuery.parseJSON(data);
                            if (address.short == 'XRP')
                                $('.xrp-row').show();
                            else
                                $('.xrp-row').hide();

                            if (address.crypto_address_id == '' || address.crypto_address == '') {
                                resetAddressData();
                            } else {
                                $('#create_address_btn').hide();
                                $('.crypto-address-data-container').show();
                                getAddressQRCode(address.crypto_address, address.logo);

                                $("#crypto-address").text(address.crypto_address);
                                $("#destination-tag").html(address.destination_tag);
                                var coin_type = address.name + ' (' + address.short + ') ' + '<img src="' + address
                                    .logo + '" width="25px">';
                                $("#coin-type").html(coin_type);
                                $("#account-title").html(address.title);
                                $("#data-network").html(address.network);
                                $("#email").html(address.email);
                                $("#create-date").html(address.created_at);

                            }
                        } else {
                            resetAddressData();
                        }
                    }
                );
            }
        }

        function getAddressQRCode(wallet_address, currency_logo_url) {
            $.post("{{ url('/client/crypto-transaction-requests/bitpace/get-address-data-qrcode') }}", {
                    "wallet_address": wallet_address,
                    "logo": currency_logo_url
                },
                function(data, status) {
                    if (data != '') {
                        var qr_data = jQuery.parseJSON(data);
                        // Assuming qr_data.qr_code contains the base64 image data
                        $('#qr-code-section').html('<img id="qr-code-image" src="data:image/png;base64,' + qr_data
                            .qr_code + '" alt="QR Code Image">');
                    }
                }
            );
        }

        function getNetworkList(account_id = '', currency_code = '') {
            resetAddressData();

            $.post("{{ url('/client/crypto-transaction-requests/bitpace/get-currency-networks') }}", {
                    "currency_code": currency_code,
                    "account_id": account_id
                },
                function(data, status) {

                    var networks = jQuery.parseJSON(data);
                    $("#network").empty();
                    $("#network").append(new Option('Select Network', ''));
                    $.each(networks, function(key, val) {
                        $("#network").append(new Option(val, val)).prop('selected');
                    });
                    var selected_network = "{{ $network }}";
                    var selected_account_id = $('#account_id').val();
                    if (selected_network != '' && curr_account_id == selected_account_id)
                        $("#network").val(selected_network).trigger('change');
                }
            );
        }

        function resetAddressData() {
            $("#create_address_btn").show();
            $(".reset-data").html('');
            $('.crypto-address-data-container').hide();
        }

        function copy_text(eId, what) {
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
                title: what + " " + copyText.textContent + " has been copied",
                showConfirmButton: false,
                timer: 1500
            });
        }
        var curr_account_id = '';
        setTimeout(function() {
            curr_account_id = '{{ $account_id }}';
            //console.log(selected_account_id);
            if (curr_account_id) {
                getNetworkList(curr_account_id);
            }
            $('#create_address_btn').on('click', function() {
                if ($('#account_id').val() == '' || $('#network').val() == '') {
                    return false;
                } else {

                    $('#create_address_btn').addClass('btn-secondary, disabled').hide();
                    $('#loading-spinner').show();
                    $('#create-address-form').submit();
                }
            });
        }, 300);

        //    function getgetAccountAddress(account_id) {

        //     if (account_id) {
        //                 $.post("{{ url('/client/crypto-transaction-requests/bitpace/get-address-data') }}", {
        //                         "account_id": account_id
        //                     },
        //                     function(data, status) {

        //                         if(data != '') {
        //                             address = jQuery.parseJSON(data);
        //                             //$("#network").val(address.network).attr("readonly", true);
        //                             if(address.short == 'XRP')
        //                                 $('.xrp-row').show();
        //                             else
        //                                 $('.xrp-row').hide();


        //                             //console.log(address);
        //                             if((address.adress == '' && address.short == 'XRP') || !address.crypto_address_id  || address.bitpace_address == '' ) {
        //                                 $("#create_address_btn").show();
        //                                 $(".reset-data").html('');
        //                                 $('.crypto-address-data-container').hide();
        //                             } else {
        //                                 $('.crypto-address-data-container').show();
        //                                 var qr_code_url = 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data='+address.bitpace_address;

        //                                 $('#qr-code-section').html('<img id="qr-code-image" src="'+qr_code_url+'">');

        //                                 //$('#download-qrcode-btn').attr('download','address-qrcode.png');
        //                                 //$('#download-qrcode-btn').attr('href',qr_code_url);
        //                                 $("#crypto-address").text(address.bitpace_address);
        //                                 $("#destination-tag").html(address.address);
        //                                 var coin_type = address.name + ' ('+address.short+') '+'<img src="'+address.logo+'" width="25px">';
        //                                 //console.log(coin_type);
        //                                 $("#coin-type").html(coin_type);
        //                                 $("#account-title").html(address.title);
        //                                 $("#data-network").html(address.network);
        //                                 $("#network").val(address.network);
        //                                 $("#email").html(address.email);
        //                                 $("#create-date").html(address.created_at);

        //                             }
        //                             if(address.short) {
        //                                 getNetworkList(address.short);
        //                             }
        //                         }
        //                     }
        //                 );
        //             }
        //         }
        // function getNetworkList(currency_code) {

        //     $.post("{{ url('/client/crypto-transaction-requests/bitpace/get-currency-networks') }}", {
        //             "currency_code": currency_code
        //         },
        //         function(data, status) {

        //              var networks = jQuery.parseJSON(data);
        //             // console.log(networks);
        //             $("#network").empty();
        //             //$("#network").append(new Option('Select Network', ''));
        //             $.each(networks, function(key,val) {
        //                 $("#network").append(new Option(val, val)).prop('selected');
        //             });
        //             //return data;
        //         }
        //     );
        // }

        // function copy_text( eId) {
        //     var copyText = document.getElementById(eId);
        //     var textArea = document.createElement("textarea");
        //     textArea.value = copyText.textContent;
        //     document.body.appendChild(textArea);
        //     textArea.select();
        //     document.execCommand("Copy");
        //     textArea.remove();
        // }
        // setTimeout(function(){
        //     var selected_account_id = '{{ $account_id }}';
        //         console.log(selected_account_id);
        //         if(selected_account_id) {
        //             getgetAccountAddress(selected_account_id);

        //         }

        // },200);
    </script>
@stop
