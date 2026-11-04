@extends('layouts.cp')
@section('title', 'Address Details - Crypto Address Management')

@section('content')

<div class="subheader">
    <h1 class="subheader-title">
        <span class="fw-300">Crypto Address Management</span>
    </h1>
    @include('crypto-management.crypto-transactions.view.account-balances')
    <a href="{{ url('/crypto-management/crypto-transactions') }}" class="btn btn-primary mr-3">
        <i class="fal fa-arrow-alt-left"></i> Go Back
    </a>
</div>

{{-- ── Selection panel ────────────────────────────────────────────────── --}}
<div class="panel" data-panel-lock="false" data-panel-close="false"
     data-panel-collapsed="false" data-panel-color="false"
     data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
    <div class="panel-hdr">
        <h2>Generate Deposit Address</h2>
    </div>

    <form method="post" id="create-address-form"
          action="{{ url('/fireblocks/store-deposit-address') }}"
          enctype="multipart/form-data">
        @csrf
        <div class="panel-container show">
            <div class="panel-content">
                @include('common.error-message')

                <div class="row">
                    {{-- Client Account --}}
                    <div class="col-md-5">
                        <div class="form-group">
                            <label class="form-label" for="account_id">
                                <i class="fal fa-user-circle mr-1"></i> Client Account
                            </label>
                            <select onchange="getNetworkList(this.value);"
                                    name="account_id"  id="account_id"
                                    data-select2-id="account_id"
                                    class="form-control select2 w-100" required>
                                <option value="">Choose Account</option>
                                @foreach ($accounts as $account)
                                    @if ($account->id == $account_id))
                                        <option value="{{ $account->id }}" selected>
                                            {{ $account->title . ' — ' . $account->account_number }}
                                        </option>
                                    @else
                                        <option value="{{ $account->id }}">
                                            {{ $account->title . ' — ' . $account->account_number }}
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Network card-picker --}}
                    <div class="col-md-7">
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fal fa-network-wired mr-1"></i> Network
                            </label>

                            {{-- Trigger button --}}
                            <button type="button"
                                    id="network-picker-btn"
                                    class="btn btn-outline-secondary w-100 text-left d-flex align-items-center justify-content-between"
                                    onclick="toggleNetworkPicker()">
                                <span id="network-picker-label" class="text-muted">Select Network</span>
                                <i class="fal fa-chevron-down"></i>
                            </button>

                            {{-- Dropdown panel --}}
                            <div id="network-picker-panel"
                                 class="border rounded bg-white shadow-sm"
                                 style="display:none; position:absolute; z-index:1050; width:calc(100% - 30px);">

                                <div class="p-2 border-bottom">
                                    <div class="input-group input-group-sm">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fal fa-search"></i></span>
                                        </div>
                                        <input type="text"
                                               id="network-search"
                                               class="form-control"
                                               placeholder="Search network…"
                                               oninput="filterNetworkCards(this.value)">
                                    </div>
                                </div>

                                <div id="network-cards-container"
                                     class="p-2 d-flex flex-wrap"
                                     style="max-height:260px; overflow-y:auto;">
                                    <div class="w-100 text-center text-muted py-3 small">
                                        Select a client account first
                                    </div>
                                </div>
                            </div>

                            {{-- Hidden select kept for JS + form submission compatibility --}}
                            <select name="network" id="network" class="d-none" required
                                    onchange="getAccountAddress('')">
                                <option value="">Select Network</option>
                            </select>

                            @error('network')
                                <span class="invalid-feedback d-block" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <hr>
                            <button type="button" class="btn btn-primary"
                                    style="display:none" id="create_address_btn">
                                <i class="fal fa-plus-circle mr-1"></i> Create Deposit Address
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </form>
</div>

{{-- ── Address detail panel (hidden until data loads) ─────────────────── --}}
<div class="crypto-address-data-container panel"
     data-panel-lock="false" data-panel-close="false"
     data-panel-collapsed="false" data-panel-color="false"
     style="display:none">
    <div class="panel-hdr">
        <h2>
            <span class="reset-data" id="coin-type-header"></span> Deposit Address
        </h2>
    </div>



    <div class="panel-container show">
        <div class="panel-content">
            <div class="row">

                {{-- QR column --}}
                <div class="col-md-3 d-flex flex-column align-items-center">
                    <div class="border rounded p-3 bg-white text-center" id="qr-code-section"></div>
                    <p class="text-muted small mt-2 text-center mb-0">
                        Scan to copy deposit address
                    </p>
                </div>

                {{-- Details column --}}
                <div class="col-md-9">

                    {{-- Coin badge --}}
                    <div class="d-flex align-items-center mb-4 reset-data" id="coin-type"></div>

                    {{-- Deposit address --}}
                    <div class="form-group">
                        <label class="form-label text-uppercase small font-weight-bold text-muted">
                            Deposit Address
                        </label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fal fa-link"></i></span>
                            </div>
                            <span class="form-control text-monospace reset-data copyable"
                                  id="crypto-address"
                                  style="height:auto; word-break:break-all; white-space:normal;"></span>
                            <div class="input-group-append">
                                <button class="btn btn-outline-primary" type="button"
                                        title="Copy Address"
                                        onclick="copy_text('crypto-address','Deposit Address')">
                                    <i class="fal fa-copy mr-1"></i> Copy
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Destination tag – XRP only --}}
                    <div class="form-group xrp-row" style="display:none">
                        <label class="form-label text-uppercase small font-weight-bold text-muted">
                            Destination Tag
                        </label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fal fa-tag"></i></span>
                            </div>
                            <span class="form-control text-monospace reset-data copyable"
                                  id="destination-tag"
                                  style="height:auto;"></span>
                            <div class="input-group-append">
                                <button class="btn btn-outline-primary" type="button"
                                        title="Copy Destination Tag"
                                        onclick="copy_text('destination-tag','Destination Tag')">
                                    <i class="fal fa-copy mr-1"></i> Copy
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Meta grid --}}
                    <div class="row border-top pt-3 mt-2">
                        <div class="col-md-4 mb-3">
                            <div class="text-uppercase small font-weight-bold text-muted mb-1">Network</div>
                            <div class="reset-data" id="data-network"></div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="text-uppercase small font-weight-bold text-muted mb-1">Provider</div>
                            <div class="reset-data" id="provider"></div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="text-uppercase small font-weight-bold text-muted mb-1">Account Title</div>
                            <div class="reset-data" id="account-title"></div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="text-uppercase small font-weight-bold text-muted mb-1">Email</div>
                            <div class="reset-data" id="email"></div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-uppercase small font-weight-bold text-muted mb-1">Created</div>
                            <div class="reset-data" id="create-date"></div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<script>
var selected_network = '';
var allNetworkData   = [];

/* ── Network card-picker ──────────────────────────────────────────── */
function toggleNetworkPicker() {
    var panel = document.getElementById('network-picker-panel');
    if (panel.style.display === 'none') {
        panel.style.display = 'block';
        setTimeout(function(){ document.getElementById('network-search').focus(); }, 50);
    } else {
        panel.style.display = 'none';
    }
}

document.addEventListener('click', function (e) {
    var btn   = document.getElementById('network-picker-btn');
    var panel = document.getElementById('network-picker-panel');
    if (btn && panel && !btn.contains(e.target) && !panel.contains(e.target)) {
        panel.style.display = 'none';
    }
});

function renderNetworkCards(networks) {
    allNetworkData = networks;
    buildNetworkCards(networks);
}

function buildNetworkCards(networks) {
    var container = $('#network-cards-container');
    container.empty();

    if (!networks || networks.length === 0) {
        container.html('<div class="w-100 text-center text-muted py-3 small">No networks available</div>');
        return;
    }

    var currentVal = $('#network').val();
    $.each(networks, function (key, val) {
        var isSelected = (String(val.network_id) === String(currentVal));
        var cardClass  = isSelected ? 'border-primary bg-primary text-white' : 'border';
        var badgeClass = isSelected ? 'badge-light'                          : 'badge-secondary';
        container.append(
            '<div class="p-1 network-card-item" style="width:100%; min-width:140px"' +
                ' data-id="' + val.network_id + '" data-name="' + val.name + '" data-code="' + val.code + '">' +
                '<div class="rounded p-2 network-card h-100 ' + cardClass + '" style="cursor:pointer;">' +
                    '<div class="font-weight-bold small">' + val.name + '</div>' +
                    '<span class="badge mt-1 ' + badgeClass + '">' + val.code + '</span>' +
                '</div>' +
            '</div>'
        );
    });

    container.find('.network-card-item').on('click', function () {
        var id   = $(this).data('id');
        var name = $(this).data('name');
        var code = $(this).data('code');

        container.find('.network-card').removeClass('border-primary bg-primary text-white').addClass('border');
        container.find('.badge').removeClass('badge-light').addClass('badge-secondary');
        $(this).find('.network-card').addClass('border-primary bg-primary text-white').removeClass('border');
        $(this).find('.badge').removeClass('badge-secondary').addClass('badge-light');

        $('#network').val(id).trigger('change');
        $('#network-picker-label')
            .removeClass('text-muted')
            .html('<strong>' + name + '</strong> <span class="badge badge-secondary ml-1">' + code + '</span>');
        $('#network-picker-panel').hide();
    });
}

function filterNetworkCards(query) {
    query = query.toLowerCase();
    var filtered = !query ? allNetworkData : allNetworkData.filter(function (n) {
        return n.name.toLowerCase().indexOf(query) !== -1 || n.code.toLowerCase().indexOf(query) !== -1;
    });
    buildNetworkCards(filtered);
}

/* ── Core logic (unchanged) ───────────────────────────────────────── */
function getAccountAddress(account_id) {
    if (account_id == '') {
        selected_network = $('#network').val();
        account_id       = $('#account_id').val();
    }
    if (account_id && selected_network) {
        $.post("{{ url('/fireblocks/get-address-data') }}", {
            "account_id": account_id,
            "network":    selected_network
        }, function (data, status) {
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

                    // --- Fetch QR Code from internal Bitpace endpoint ---
                    $.post("{{ url('/client/crypto-transactions/generate-address-qr') }}", {
                        _token: "{{ csrf_token() }}",
                        wallet_address: address.crypto_address,
                        destination_tag: address.destination_tag || '',
                        network: address.short || address.network || '',
                        logo: address.logo
                    }, function (qrResponse) {
                        // Case A: If your upstream returns a Base64 string or JSON with a URL
                        var qrSrc = (typeof qrResponse === 'object' && qrResponse.qr_code)
                            ? qrResponse.qr_code
                            : (qrResponse.startsWith('data:image') || qrResponse.startsWith('http')
                                ? qrResponse
                                : 'data:image/png;base64,' + qrResponse);

                        $('#qr-code-section').html('<img id="qr-code-image" src="' + qrSrc + '" class="rounded">');
                    }).fail(function() {
                        $('#qr-code-section').html('<span class="text-danger">Failed to generate QR Code</span>');
                    });

                    $("#crypto-address").text(address.crypto_address);
                    $("#destination-tag").html(address.destination_tag);
                    $("#provider").html(address.provider);

                    var coin_html = '<img src="' + address.logo + '" width="28" height="28"' +
                                   ' class="rounded-circle mr-2">' +
                                   '<strong class="h5 mb-0">' + address.name + '</strong>' +
                                   '<span class="badge badge-secondary ml-2">' + address.short + '</span>';
                    $("#coin-type").html(coin_html);
                    $("#coin-type-header").html(
                        '<img src="' + address.logo + '" width="16" height="16"' +
                        ' class="rounded-circle mr-1 align-middle">' +
                        address.name + ' (' + address.short + ') &mdash;'
                    );

                    $("#account-title").html(address.address_alias);
                    $("#data-network").html(address.network);
                    $("#email").html(address.email);
                    $("#create-date").html(address.created_at);
                }
            } else {
                resetAddressData();
            }
        });
    }
}

function getNetworkList(account_id, currency_code) {
    account_id    = account_id    || '';
    currency_code = currency_code || '';
    resetAddressData('network');

    $.post("{{ url('/fireblocks/currency-networks') }}", {
        "currency_code": currency_code,
        "account_id":    account_id
    }, function (data, status) {
        var networks = data;

        // keep hidden select in sync for form submission
        $("#network").empty();
        $("#network").append(new Option('Select Network', ''));
        $.each(networks, function (key, val) {
            $("#network").append(new Option(val.name + ' - (' + val.code + ')', val.network_id));
        });

        // reset picker label
        $('#network-picker-label').addClass('text-muted').text('Select Network');

        // render card picker
        renderNetworkCards(networks);

        var preselect_network    = "{{$network}}";
        var selected_account_id = $('#account_id').val();
        if (preselect_network != '' && curr_account_id == selected_account_id) {
            $("#network").val(preselect_network);
            // highlight matching card + update label
            var card = $('#network-cards-container .network-card-item[data-id="' + preselect_network + '"]');
            if (card.length) {
                card.find('.network-card').addClass('border-primary bg-primary text-white').removeClass('border');
                card.find('.badge').removeClass('badge-secondary').addClass('badge-light');
                $('#network-picker-label')
                    .removeClass('text-muted')
                    .html('<strong>' + card.data('name') + '</strong>' +
                          ' <span class="badge badge-secondary ml-1">' + card.data('code') + '</span>');
            }
            $("#network").trigger('change');
        }
    });
}

function resetAddressData(area = 'address') {
    //console.log(area);
    if(area == 'address')
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
setTimeout(function () {
   
    curr_account_id = '{{$account_id}}';
    if (curr_account_id) {
        getNetworkList(curr_account_id);
        getAccountAddress(curr_account_id);
    }
    $('#create_address_btn').on('click', function () {
        $('#create_address_btn').addClass('btn-secondary disabled').hide();
        $('#loading-spinner').show();
        $('#create-address-form').submit();
    });
   $(window).on('load', function () {
        $('#loading-spinner').fadeOut();
    });
}, 5000);

</script>

@stop
