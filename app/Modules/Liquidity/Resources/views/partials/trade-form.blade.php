{{--
    Shared Buy/Sell trade form, reused by both the B2c2 and Aquanow modules
    (App\Modules\B2c2\Resources\views\{buy,sell}.blade.php and the Aquanow
    equivalents just set $provider/$providerLabel and @include this).

    Expects:
      $side           'buy'|'sell'
      $actionLabel    'Buy'|'Sell'
      $provider       'b2c2'|'aquanow' — used to build the /{provider}/rfq
                       and /{provider}/orders endpoint URLs.
      $providerLabel  Display name, e.g. 'B2C2'|'Aquanow'.
      $cryptoAccounts, $fiatAccounts, $instrumentMap, $instrumentMeta
                       — same shape both TradeController::tradeData()
                       implementations already produce.

    Mirrors the Bitpace buy/sell UX:
      - BUY : source = Fiat account (debit)  -> target = Crypto account (credit)
      - SELL: source = Crypto account (debit) -> target = Fiat account (credit)
    The target account list is filtered to the SAME CLIENT as the selected source
    account, and the live balance is shown for both accounts — all via the existing
    /finance-management/get-balance endpoint.

    Trading itself reuses the provider's existing RFQ/Order endpoints (no
    duplicated business logic here):
      - POST /{provider}/rfq    (RFQController@requestQuote)
      - POST /{provider}/orders (OrderController@create)
--}}
@php
    if ($side === 'buy') {
        // Buy crypto: pay from a fiat account, receive into a crypto account.
        $sourceLabel   = 'Fiat Account';
        $targetLabel   = 'Crypto Account';
        $sourceAccounts = $fiatAccounts;
        $needParam     = 'needCryptoAccounts'; // ask endpoint for same-client crypto accounts
        $targetKey     = 'crypto_accounts';    // response key holding those accounts
        $sourceIsCrypto = false;
    } else {
        // Sell crypto: send from a crypto account, receive into a fiat account.
        $sourceLabel   = 'Crypto Account';
        $targetLabel   = 'Fiat Account';
        $sourceAccounts = $cryptoAccounts;
        $needParam     = 'needFiatAccounts';   // ask endpoint for same-client fiat accounts
        $targetKey     = 'fiat_accounts';
        $sourceIsCrypto = true;
    }
@endphp

@extends('layouts.cp')
@section('title', $actionLabel . ' Crypto (' . $providerLabel . ') - Crypto Management')

@section('styles')
    <style>
        @keyframes liquidityPulse {
            0%   { transform: scale(1);    opacity: 1; }
            50%  { transform: scale(1.12); opacity: 0.75; }
            100% { transform: scale(1);    opacity: 1; }
        }
        #quote-timer .badge { font-size: 0.95rem; padding: 0.5em 0.9em; }

        .liquidity-quote { box-shadow: 0 2px 10px rgba(0,0,0,.08); }
        .liquidity-heads thead th { background:#f0f4f8; font-weight:600; text-transform:uppercase; font-size:.72rem; letter-spacing:.04em; color:#5a6a7a; }
        .liquidity-heads tbody td { font-size:1.05rem; font-weight:600; }
        .liquidity-totals td { padding:.35rem .5rem; border:0; border-bottom:1px solid #eef1f4; }
        .liquidity-grandtotal td { border-top:2px solid #17a2b8 !important; border-bottom:0; font-size:1.05rem; background:#f3fbfd; }
        .liquidity-feehdr td { background:#fafbfc; font-size:.8rem; text-transform:uppercase; letter-spacing:.03em; color:#5a6a7a; padding-bottom:.15rem; }
    </style>
@stop

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Crypto Management</span> &mdash; {{ $providerLabel }} {{ $actionLabel }}
        </h1>
        <a href="{{ url('/crypto-management/crypto-transactions') }}" class="btn btn-primary mr-3">
            <i class="fal fa-arrow-alt-left"></i> Go Back
        </a>
    </div>

    <div class="panel">
        <div class="panel-hdr">
            <h2>{{ $actionLabel }} Crypto via {{ $providerLabel }}</h2>
        </div>

        <div class="panel-content bg-subtlelight-fade">
            @include('common.error-message')
            <div id="trade-message"></div>
        </div>

        <div class="panel-container show">
            <div class="panel-content">

                <input type="hidden" id="side" value="{{ $side }}">
                <input type="hidden" id="source_is_crypto" value="{{ $sourceIsCrypto ? 1 : 0 }}">
                <input type="hidden" id="instrument" value="">
                <input type="hidden" id="rfq_id" value="">
                <input type="hidden" id="quote_price" value="">
                <input type="hidden" id="quote_total_fee" value="0">
                <input type="hidden" id="quote_valid_until" value="">

                <div class="row">
                    <div class="col-md-6">

                        {{-- Source account + balance --}}
                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label class="form-label" for="source_account_id">{{ $sourceLabel }} <small class="text-muted">(source)</small></label>
                                    <select id="source_account_id" class="form-control select2 w-100"
                                        onchange="onSourceChange(this.value)" required>
                                        <option value="">Choose {{ $sourceLabel }}</option>
                                        @foreach ($sourceAccounts as $account)
                                            <option value="{{ $account->id }}" data-currency="{{ $account->account_currency }}">
                                                {{ $account->title }} - {{ $account->account_number }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label">Balance</label>
                                    <div class="form-control" id="source_balance" readonly>--</div>
                                </div>
                            </div>
                        </div>

                        {{-- Target account (filtered by source client) + balance --}}
                        <div class="row mt-2">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label class="form-label" for="target_account_id">{{ $targetLabel }} <small class="text-muted">(target)</small></label>
                                    <select id="target_account_id" class="form-control select2 w-100"
                                        onchange="onTargetChange(this.value)" required>
                                        <option value="">Choose {{ $targetLabel }}</option>
                                    </select>
                                    <span id="target-account-message" style="color: red;"></span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label">Balance</label>
                                    <div class="form-control" id="target_balance" readonly>--</div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group mt-2">
                            <label class="form-label">Instrument</label>
                            <div class="form-control" id="instrument-display" readonly>
                                Select both accounts to determine the instrument.
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="quantity">Quantity (crypto)</label>
                            <input type="number" step="any" min="0" id="quantity" class="form-control" required>
                            <small id="quantity-limits" class="text-muted"></small>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="remarks">Remarks</label>
                            <textarea maxlength="500" class="form-control" id="remarks" rows="2"
                                placeholder="Optional remarks for this transaction."></textarea>
                        </div>
                    </div>

                    <div class="col-md-6" style="border-left: 1px solid lightgray;">
                        <div class="alert alert-warning">
                            Request a live quote from {{ $providerLabel }}, then place the order. The quoted price is only
                            valid for a short window.
                        </div>
                        <div id="price-summary"></div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <hr>
                            <button type="button" class="btn btn-primary" id="quote-button" onclick="getQuote()">
                                <i class="fal fa-repeat"></i> Get Quote
                            </button>
                            <button type="button" class="btn btn-success" id="order-button" style="display:none;" onclick="placeOrder()">
                                {{ $actionLabel }} Now
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@stop

@section('scripts')
    <script>
        $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } });

        const SIDE = $('#side').val();
        const SOURCE_IS_CRYPTO = $('#source_is_crypto').val() === '1';
        const TARGET_KEY = "{{ $targetKey }}";
        const PROVIDER = "{{ $provider }}";
        const PROVIDER_LABEL = "{{ $providerLabel }}";

        // Active instruments keyed by "<cryptoCurrencyId>-<fiatCurrencyId>".
        const INSTRUMENT_MAP = @json($instrumentMap);
        // Trade constraints keyed by instrument identifier.
        const INSTRUMENT_META = @json($instrumentMeta);

        function showError(msg) { $('#trade-message').html(msg).addClass('alert alert-danger'); }
        function clearError() { $('#trade-message').html('').removeClass('alert alert-danger'); }

        // Source account chosen: show its balance and load same-client target accounts.
        function onSourceChange(accountId) {
            resetTarget();
            resetQuote();
            if (!accountId) {
                $('#source_balance').html('--');
                return;
            }

            const payload = { account_id: accountId };
            payload["{{ $needParam }}"] = true;
            @if (! $sourceIsCrypto)
                // no-op
            @else
                payload["source"] = 'crypto';
            @endif

            $.post("{{ url('/finance-management/get-balance') }}", payload, function (data) {
                $('#source_balance').html(data.formatted_balance);
                $('#source_account_id').data('rawBalance', parseFloat(data.raw_balance || data.balance || 0));
                $('#source_account_id').data('currencyShort', data.currency_short || '');
                populateTargetAccounts(data[TARGET_KEY] || []);
            });
        }

        // Target account chosen: show its balance and resolve the instrument.
        function onTargetChange(accountId) {
            resetQuote();
            if (!accountId) {
                $('#target_balance').html('--');
                resolveInstrument();
                return;
            }
            $.post("{{ url('/finance-management/get-balance') }}", { account_id: accountId }, function (data) {
                $('#target_balance').html(data.formatted_balance);
                resolveInstrument();
            });
        }

        function populateTargetAccounts(accounts) {
            const $dd = $('#target_account_id');
            $dd.empty().append('<option value="">Choose {{ $targetLabel }}</option>');

            if (accounts.length > 0) {
                accounts.forEach(function (acc) {
                    $dd.append(
                        $('<option>')
                            .val(acc.id)
                            .attr('data-currency', acc.account_currency)
                            .text(acc.title + ' - ' + acc.account_number)
                    );
                });
            } else {
                $('#target-account-message').text('No matching accounts found for the selected client.');
                setTimeout(function () { $('#target-account-message').text(''); }, 5000);
            }
        }

        function resetTarget() {
            $('#target_account_id').empty().append('<option value="">Choose {{ $targetLabel }}</option>');
            $('#target_balance').html('--');
            $('#instrument').val('');
            $('#instrument-display').text('Select both accounts to determine the instrument.');
            $('#quantity-limits').text('');
        }

        function resetQuote() {
            stopExpiryTimer();
            $('#rfq_id').val('');
            $('#quote_price').val('');
            $('#quote_total_fee').val('0');
            $('#quote_valid_until').val('');
            $('#order-button').hide();
            $('#price-summary').html('');
        }

        // ---- Quote expiry countdown ----
        let expiryInterval = null;

        function stopExpiryTimer() {
            if (expiryInterval) {
                clearInterval(expiryInterval);
                expiryInterval = null;
            }
        }

        // Parse the provider's valid_until, which may be an ISO string, epoch
        // seconds, or epoch milliseconds. Returns a JS timestamp (ms) or NaN.
        function parseExpiry(validUntil) {
            if (validUntil == null || validUntil === '') return NaN;

            // Numeric epoch (seconds or ms)?
            if (typeof validUntil === 'number' || /^\d+(\.\d+)?$/.test(validUntil)) {
                const n = Number(validUntil);
                return n < 1e12 ? n * 1000 : n; // < 1e12 => seconds
            }
            return new Date(validUntil).getTime();
        }

        function startExpiryTimer(validUntil) {
            stopExpiryTimer();

            const $timer = $('#quote-timer');
            const expiry = parseExpiry(validUntil);
            const initialRemaining = isNaN(expiry) ? NaN : Math.round((expiry - Date.now()) / 1000);

            // If we can't derive a real future expiry (missing/unparseable/already
            // past due to format or clock skew), don't run a countdown and DON'T
            // falsely mark the fresh quote as expired — just leave the order button.
            if (isNaN(initialRemaining) || initialRemaining <= 0) {
                $timer.html('');
                return;
            }

            function tick() {
                const remaining = Math.max(0, Math.round((expiry - Date.now()) / 1000));
                if (remaining <= 0) {
                    stopExpiryTimer();
                    // Quote expired: hide the order button and force a new quote.
                    $('#order-button').hide();
                    resetQuote();
                    $timer.html('');
                    showError('Quote expired. Please get a new quote.');
                    return;
                }
                renderTimerBadge($timer, remaining);
            }

            tick();
            expiryInterval = setInterval(tick, 1000);
        }

        // Nice pill badge: green -> yellow -> red as the quote runs out, with a
        // subtle pulse in the final seconds.
        function renderTimerBadge($timer, remaining) {
            let cls = 'badge-success';
            if (remaining <= 5) cls = 'badge-danger';
            else if (remaining <= 10) cls = 'badge-warning';

            const pulse = remaining <= 5 ? ' style="animation: liquidityPulse 1s infinite;"' : '';
            $timer.html(
                '<span class="badge badge-pill ' + cls + '" ' + pulse + '>' +
                '<i class="fal fa-clock"></i> Expires in ' + remaining + 's</span>'
            );
        }

        // The crypto/fiat side of each account depends on the trade direction.
        function selectedAccountIds() {
            const source = $('#source_account_id').val();
            const target = $('#target_account_id').val();
            return SOURCE_IS_CRYPTO
                ? { crypto_account_id: source, fiat_account_id: target }
                : { crypto_account_id: target, fiat_account_id: source };
        }

        function selectedCurrencies() {
            const srcCcy = $('#source_account_id option:selected').data('currency');
            const tgtCcy = $('#target_account_id option:selected').data('currency');
            // key = "<cryptoCurrencyId>-<fiatCurrencyId>"
            return SOURCE_IS_CRYPTO ? { crypto: srcCcy, fiat: tgtCcy } : { crypto: tgtCcy, fiat: srcCcy };
        }

        // Derive the instrument from the selected account pair.
        function resolveInstrument() {
            const ccy = selectedCurrencies();
            const $display = $('#instrument-display');
            $('#instrument').val('');

            if (!ccy.crypto || !ccy.fiat) {
                $display.text('Select both accounts to determine the instrument.');
                return;
            }

            const instrument = INSTRUMENT_MAP[ccy.crypto + '-' + ccy.fiat];
            if (!instrument) {
                $display.html('<span class="text-danger">No active ' + PROVIDER_LABEL + ' instrument exists for the selected account pair.</span>');
                $('#quantity-limits').text('');
                return;
            }

            $('#instrument').val(instrument);
            $display.html('<strong>' + instrument + '</strong>');
            applyInstrumentMeta(instrument);
            clearError();
        }

        // Show min/max trade + price precision for the resolved instrument and
        // constrain the quantity input accordingly.
        function applyInstrumentMeta(instrument) {
            const meta = INSTRUMENT_META[instrument] || {};
            const $q = $('#quantity');
            const parts = [];

            if (meta.min_quantity != null) {
                $q.attr('min', meta.min_quantity);
                parts.push('Min: ' + meta.min_quantity);
            } else {
                $q.removeAttr('min');
            }
            if (meta.max_quantity != null) {
                $q.attr('max', meta.max_quantity);
                parts.push('Max: ' + meta.max_quantity);
            } else {
                $q.removeAttr('max');
            }
            if (meta.price_significant_digits != null) {
                parts.push('Price sig. digits: ' + meta.price_significant_digits);
            }

            $('#quantity-limits').html(parts.length ? '<b>Limits:</b> ' + parts.join(' &nbsp;|&nbsp; ') : '');
        }

        function collectBase() {
            const ids = selectedAccountIds();
            return {
                instrument: $('#instrument').val(),
                crypto_account_id: ids.crypto_account_id,
                fiat_account_id: ids.fiat_account_id,
                quantity: $('#quantity').val(),
            };
        }

        function fmt(n) {
            const v = parseFloat(n);
            if (isNaN(v)) return n;
            return v.toFixed(8).replace(/\.?0+$/, '');
        }

        // Build the full tabular quote card.
        function buildQuoteHtml(q) {
            const showFees = q.fee && q.fee.applied;
            const isSummed = showFees && q.fee.display === 'summed';

            let html = '' +
                '<div class="card border-info liquidity-quote">' +
                '  <div class="card-header bg-info text-white d-flex align-items-center justify-content-between py-2">' +
                '    <h4 class="m-0"><i class="fal fa-file-invoice-dollar mr-1"></i> Quote</h4>' +
                '    <span id="quote-timer"></span>' +
                '  </div>' +
                '  <div class="card-body p-3">' +
                '    <div class="d-flex justify-content-between text-muted mb-2">' +
                '      <span><strong>Instrument:</strong> ' + q.instrument + '</span>' +
                '      <span class="badge ' + (SIDE === 'buy' ? 'badge-success' : 'badge-danger') + '">' + SIDE.toUpperCase() + '</span>' +
                '    </div>';

            // The 3 identifiable heads. In `summed` mode the fee is folded into the
            // Approx. Total shown (single number); in `separate` it stays the pure
            // quoted amount and the fees are itemized below.
            const headTotal = isSummed ? q.grandTotal : q.fiatTotal;
            html += '' +
                '    <table class="table table-bordered text-center mb-3 liquidity-heads">' +
                '      <thead><tr>' +
                '        <th>Price</th><th>Quantity</th><th>Approx. Total</th>' +
                '      </tr></thead>' +
                '      <tbody><tr>' +
                '        <td>' + fmt(q.price) + '</td>' +
                '        <td>' + fmt(q.qty) + '</td>' +
                '        <td>' + headTotal.toFixed(2) + (q.feeCcy ? ' ' + q.feeCcy : '') + '</td>' +
                '      </tr></tbody>' +
                '    </table>';

            // Totals table.
            html += '<table class="table table-sm mb-0 liquidity-totals"><tbody>';

            if (showFees && !isSummed) {
                // SEPARATE: itemize each fee under the quoted amount.
                html += '<tr><td>Quoted Amount</td><td class="text-right">' + q.fiatTotal.toFixed(2) + ' ' + q.feeCcy + '</td></tr>';
                html += '<tr class="liquidity-feehdr"><td colspan="2"><strong>Fees</strong></td></tr>';
                q.fee.charges.forEach(function (c) {
                    const rate = c.type === 'percentage' ? (c.rate + '%') : 'flat';
                    html += '<tr class="text-muted"><td class="pl-4"><i class="fal fa-plus fa-xs mr-1"></i>' + c.label + ' (' + rate + ')</td>' +
                            '<td class="text-right">' + fmt(c.amount) + ' ' + q.feeCcy + '</td></tr>';
                });
                html += '<tr><td class="pl-4">Total Fees</td><td class="text-right">' + fmt(q.totalFee) + ' ' + q.feeCcy + '</td></tr>';
            }
            // SUMMED (or no fees): no breakdown — just the single final number below.

            const totalLabel = SIDE === 'buy' ? 'Total Payable' : 'Total Receivable';
            html += '<tr class="liquidity-grandtotal"><td><strong>' + totalLabel + '</strong>' +
                    (isSummed && showFees ? ' <small class="text-muted">(incl. fees)</small>' : '') + '</td>' +
                    '<td class="text-right"><strong>' + q.grandTotal.toFixed(2) + ' ' + q.feeCcy + '</strong></td></tr>';
            html += '</tbody></table>';

            html += '  </div></div>';
            return html;
        }

        // Fetch a live quote via the provider's RFQ endpoint.
        function getQuote() {
            clearError();
            const base = collectBase();

            if (!$('#source_account_id').val() || !$('#target_account_id').val()) {
                showError('Please select both the source and target accounts.');
                return false;
            }
            if (!base.instrument) {
                showError('No active ' + PROVIDER_LABEL + ' instrument exists for the selected account pair.');
                return false;
            }
            if (!(parseFloat(base.quantity) > 0)) {
                showError('Please enter a quantity greater than zero.');
                return false;
            }

            $.ajax({
                url: "{{ url('/') }}/" + PROVIDER + "/rfq",
                method: 'POST',
                data: { instrument: base.instrument, side: SIDE, quantity: base.quantity, crypto_account_id: base.crypto_account_id, fiat_account_id: base.fiat_account_id },
                success: function (res) {
                    const data = res.data || {};
                    if (!res.success || !data.price) {
                        showError(res.message || 'Could not retrieve a quote for the given amount.');
                        $('#order-button').hide();
                        return;
                    }
                    if (!data.rfq_id) {
                        showError('Quote did not return a reference id. Please try again.');
                        $('#order-button').hide();
                        return;
                    }
                    $('#rfq_id').val(data.rfq_id);
                    $('#quote_price').val(data.price);
                    $('#quote_valid_until').val(data.valid_until || '');

                    const qty = parseFloat(base.quantity);
                    const price = parseFloat(data.price);
                    const fiatTotal = price * qty;                 // quoted fiat amount
                    const sourceCcy = $('#source_account_id').data('currencyShort') || '';
                    const sourceBalance = parseFloat($('#source_account_id').data('rawBalance') || 0);

                    // Fee is charged in fiat. Total = quoted + fee (buy) / quoted - fee (sell).
                    const fee = res.fee;
                    const feeCcy = (fee && fee.currency_short) ? fee.currency_short : '';
                    const totalFee = (fee && fee.applied) ? parseFloat(fee.total_fee) : 0;
                    const grandTotal = SIDE === 'buy' ? (fiatTotal + totalFee) : (fiatTotal - totalFee);
                    $('#quote_total_fee').val(totalFee);

                    $('#price-summary').html(buildQuoteHtml({
                        instrument: base.instrument,
                        price: data.price,
                        qty: base.quantity,
                        fiatTotal: fiatTotal,
                        fee: fee,
                        feeCcy: feeCcy,
                        totalFee: totalFee,
                        grandTotal: grandTotal
                    }));

                    // Balance guard on the SOURCE account.
                    //   buy  -> source is fiat: must cover quoted + fee
                    //   sell -> source is crypto: must cover the crypto quantity
                    const requiredFromSource = SOURCE_IS_CRYPTO ? qty : (fiatTotal + totalFee);
                    if (requiredFromSource > sourceBalance) {
                        stopExpiryTimer();
                        $('#order-button').hide();
                        showError('Insufficient balance in the source account. Required: '
                            + requiredFromSource.toFixed(8).replace(/\.?0+$/, '') + ' ' + sourceCcy
                            + ', available: ' + sourceBalance + ' ' + sourceCcy + '.');
                        return;
                    }

                    $('#order-button').show();
                    startExpiryTimer(data.valid_until);
                },
                error: function (xhr) {
                    const res = xhr.responseJSON || {};
                    showError(res.message || res.error || 'Failed to request quote.');
                    $('#order-button').hide();
                }
            });
        }

        // Execute the order via the provider's Order endpoint.
        function placeOrder() {
            clearError();
            const base = collectBase();
            const price = $('#quote_price').val();
            const validUntil = $('#quote_valid_until').val();

            if (!price) {
                showError('Please get a quote first.');
                return false;
            }

            // Client-side balance guard. The amount debited from the SOURCE account:
            //   buy  -> fiat total + fee (fee charged in fiat)
            //   sell -> crypto qty
            const qty = parseFloat(base.quantity);
            const feeTotal = parseFloat($('#quote_total_fee').val() || 0);
            const requiredFromSource = SOURCE_IS_CRYPTO ? qty : (parseFloat(price) * qty + feeTotal);
            const sourceBalance = parseFloat($('#source_account_id').data('rawBalance') || 0);
            const sourceCcy = $('#source_account_id').data('currencyShort') || '';

            if (requiredFromSource > sourceBalance) {
                $('#order-button').hide();
                showError('Insufficient balance in the source account. Required: '
                    + requiredFromSource + ' ' + sourceCcy
                    + ', available: ' + sourceBalance + ' ' + sourceCcy + '.');
                return false;
            }

            $('#order-button').prop('disabled', true);

            $.ajax({
                url: "{{ url('/') }}/" + PROVIDER + "/orders",
                method: 'POST',
                data: {
                    rfq_id: $('#rfq_id').val(),
                    instrument: base.instrument,
                    side: SIDE,
                    quantity: base.quantity,
                    crypto_account_id: base.crypto_account_id,
                    fiat_account_id: base.fiat_account_id,
                    remarks: $('#remarks').val(),
                },
                success: function (res) {
                    if (!res.success) {
                        showError(res.message || 'Failed to place order.');
                        $('#order-button').prop('disabled', false);
                        return;
                    }
                    Swal.fire({
                        title: 'Order Placed',
                        html: 'Your ' + PROVIDER_LABEL + ' ' + SIDE + ' order was submitted successfully.',
                        icon: 'success',
                        confirmButtonText: 'Close'
                    }).then(function () {
                        window.location = "{{ url('/crypto-management/crypto-transactions') }}";
                    });
                },
                error: function (xhr) {
                    const res = xhr.responseJSON || {};
                    let msg = res.message || res.error || 'Failed to place order.';
                    if (res.errors) {
                        msg = Object.values(res.errors).flat().join('<br>');
                    }
                    showError(msg);
                    $('#order-button').prop('disabled', false);
                }
            });
        }

        $(document).ajaxStart(function () { $('#loading-spinner').show(); })
                   .ajaxStop(function () { $('#loading-spinner').hide(); });
    </script>
@stop
