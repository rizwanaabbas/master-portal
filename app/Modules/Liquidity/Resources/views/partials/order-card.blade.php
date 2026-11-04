{{--
    Liquidity provider (B2c2/Aquanow) order summary + fee breakdown card.
    Expects: $b2c2Order (App\Modules\Liquidity\Models\Order), $feeCharges (collection).
    Renders nothing when there is no order. Branding (label/logo) is derived
    from the order's own `provider` column so this one template serves both.
--}}
@if (!empty($b2c2Order))
    @php
        $fiatCcy   = optional(\App\Models\Currency::find($b2c2Order->fiat_currency_id))->short;
        $cryptoCcy = optional(\App\Models\Currency::find($b2c2Order->crypto_currency_id))->short;
        $totalFee  = $feeCharges->sum('fee_amount');
        $statusBadgeMap = ['Approved' => 'badge-success', 'Pending' => 'badge-warning', 'failed' => 'badge-danger'];
        $statusClass = $statusBadgeMap[$b2c2Order->status] ?? 'badge-secondary';

        $providerLabel = $b2c2Order->provider === 'b2c2' ? 'B2C2' : 'Aquanow';
        $providerLogo = $b2c2Order->provider === 'b2c2'
            ? asset('assets/img/b2c2/logo.svg')
            : asset('assets/img/aquanow/logo.png');
    @endphp

    <style>
        .liquidity-order-card .card-header { background:#0b1f3a; color:#fff; }
        .liquidity-order-card .oc-logo { height:20px; margin-right:8px; vertical-align:middle; }
        .liquidity-order-card .oc-heads th { background:#f0f4f8; text-transform:uppercase; font-size:.7rem; letter-spacing:.04em; color:#5a6a7a; }
        .liquidity-order-card .oc-heads td { font-size:1.05rem; font-weight:600; }
        .liquidity-order-card .oc-totals td { padding:.4rem .6rem; border-bottom:1px solid #eef1f4; }
        .liquidity-order-card .oc-grand td { border-top:2px solid #0b1f3a !important; background:#f3f7fb; font-size:1.1rem; }
        .liquidity-order-card .oc-feehdr td { background:#fafbfc; font-size:.72rem; text-transform:uppercase; letter-spacing:.03em; padding-bottom:.15rem; }
        .liquidity-order-card .oc-ref { font-size:.8rem; color:#6c7a89; }
        .liquidity-order-card .oc-ref code { color:#0b1f3a; }
    </style>

    <div class="panel liquidity-order-card">
        <div class="panel-hdr card-header d-flex align-items-center justify-content-between">
            <h2 class="text-white m-0">
                <img src="{{ $providerLogo }}" class="oc-logo" alt="{{ $providerLabel }}"
                    style="filter:brightness(0) invert(1);"> {{ $providerLabel }} Order
            </h2>
            <span class="badge {{ $statusClass }}">{{ $b2c2Order->status }}</span>
        </div>

        <div class="panel-container show">
            <div class="panel-content">

                <div class="row">
                    <div class="col-md-7">
                        {{-- Order heads --}}
                        <table class="table table-bordered text-center mb-3 oc-heads">
                            <thead>
                                <tr><th>Instrument</th><th>Side</th><th>Price</th><th>Quantity</th></tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>{{ $b2c2Order->instrument }}</td>
                                    <td>
                                        <span class="badge {{ $b2c2Order->side === 'buy' ? 'badge-success' : 'badge-danger' }}">
                                            {{ strtoupper($b2c2Order->side) }}
                                        </span>
                                    </td>
                                    <td>{{ rtrim(rtrim($b2c2Order->price, '0'), '.') }}</td>
                                    <td>{{ rtrim(rtrim($b2c2Order->quantity, '0'), '.') }} {{ $cryptoCcy }}</td>
                                </tr>
                            </tbody>
                        </table>

                        <div class="oc-ref mt-2">
                            <div><strong>Order ID:</strong> <code>{{ $b2c2Order->order_id ?? '—' }}</code></div>
                            <div><strong>RFQ ID:</strong> <code>{{ $b2c2Order->rfq_id ?? '—' }}</code></div>
                            <div>
                                <strong>TR Request:</strong> <code>{{ $b2c2Order->tr_request_id ?? '—' }}</code>
                                &nbsp;·&nbsp; <strong>Dr Tx:</strong> <code>{{ $b2c2Order->dr_tr_id ?? '—' }}</code>
                                &nbsp;·&nbsp; <strong>Cr Tx:</strong> <code>{{ $b2c2Order->cr_tr_id ?? '—' }}</code>
                            </div>
                            <div><strong>Placed:</strong> {{ optional($b2c2Order->created_at)->format('Y-m-d H:i') }}</div>
                        </div>
                    </div>

                    <div class="col-md-5">
                        {{-- Totals --}}
                        <table class="table table-sm mb-0 oc-totals">
                            <tbody>
                                <tr>
                                    <td>Sub Total <small class="text-muted">(price × qty)</small></td>
                                    <td class="text-right">{{ number_format((float) $b2c2Order->sub_total, 2) }} {{ $fiatCcy }}</td>
                                </tr>

                                {{-- Itemized fee rows — always visible for ops. --}}
                                @if ($feeCharges->isNotEmpty())
                                    <tr class="oc-feehdr">
                                        <td colspan="2" class="text-muted"><strong>Fees</strong></td>
                                    </tr>
                                    @foreach ($feeCharges as $charge)
                                        <tr class="text-muted">
                                            <td class="pl-4">
                                                <i class="fal fa-plus fa-xs mr-1"></i>{{ $charge->fee_label }}
                                                <small>({{ $charge->fee_type === 'percentage' ? rtrim(rtrim($charge->fee_rate, '0'), '.') . '%' : 'flat' }})</small>
                                                @if (optional($charge->group)->name)
                                                    <span class="badge badge-light border ml-1">{{ $charge->group->name }}</span>
                                                @endif
                                            </td>
                                            <td class="text-right">{{ number_format((float) $charge->fee_amount, 2) }} {{ optional($charge->currency)->short ?? $fiatCcy }}</td>
                                        </tr>
                                    @endforeach
                                    <tr>
                                        <td class="pl-4">Total Fees</td>
                                        <td class="text-right">{{ number_format((float) $totalFee, 2) }} {{ $fiatCcy }}</td>
                                    </tr>
                                @else
                                    <tr>
                                        <td>Total Fees</td>
                                        <td class="text-right">{{ number_format((float) $b2c2Order->fee, 2) }} {{ $fiatCcy }}</td>
                                    </tr>
                                @endif

                                <tr class="oc-grand">
                                    <td><strong>{{ $b2c2Order->side === 'buy' ? 'Total Payable' : 'Total Receivable' }}</strong>
                                        <small class="text-muted">({{ $b2c2Order->side === 'buy' ? 'sub total + fees' : 'sub total − fees' }})</small>
                                    </td>
                                    <td class="text-right"><strong>{{ number_format((float) $b2c2Order->grand_total, 2) }} {{ $fiatCcy }}</strong></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Fee details — collapsed by default --}}
                @if ($feeCharges->isNotEmpty())
                    <div class="mt-3">
                        <button class="btn btn-sm btn-outline-info" type="button" data-toggle="collapse"
                            data-target="#liquidityFeeDetails" aria-expanded="false">
                            <i class="fal fa-chevron-down mr-1"></i> Fee Details ({{ $feeCharges->count() }})
                        </button>

                        <div class="collapse mt-2" id="liquidityFeeDetails">
                            <table class="table table-bordered table-sm mb-0">
                                <thead class="thead-themed">
                                    <tr>
                                        <th>#</th><th>Fee Tier Group</th><th>Fee</th><th>Type</th>
                                        <th class="text-right">Rate</th><th class="text-right">Amount</th><th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($feeCharges as $i => $charge)
                                        <tr>
                                            <td>{{ $i + 1 }}</td>
                                            <td>{{ optional($charge->group)->name ?? '—' }}</td>
                                            <td>{{ $charge->fee_label }}</td>
                                            <td>{{ ucfirst($charge->fee_type) }}</td>
                                            <td class="text-right">{{ rtrim(rtrim($charge->fee_rate, '0'), '.') }}{{ $charge->fee_type === 'percentage' ? '%' : '' }}</td>
                                            <td class="text-right">{{ number_format((float) $charge->fee_amount, 2) }} {{ optional($charge->currency)->short }}</td>
                                            <td><span class="badge {{ $charge->status === 'charged' ? 'badge-success' : 'badge-secondary' }}">{{ ucfirst($charge->status) }}</span></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="5" class="text-right">Total Fee Applied</th>
                                        <th class="text-right">{{ number_format((float) $totalFee, 2) }} {{ $fiatCcy }}</th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                @endif

            </div>
        </div>
    </div>
@endif
