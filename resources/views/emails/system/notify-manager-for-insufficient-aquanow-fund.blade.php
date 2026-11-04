@extends('layouts.email')
@section('title', 'Insufficient Wallet Balance')

@section('content')
    @php
        $debitCurrency = $transaction->account->currency->short ?? '';
        $isCryptoQuoted = ($transaction->quote_currency_type ?? '') === 'crypto';
        $creditCurrency = $transaction->to_account->currency->short ?? '';
        $enteredQty = $isCryptoQuoted ? ($transaction->crypto_amount ?? 0) : ($transaction->amount ?? 0);
        $enteredCcy = $transaction->currency_code ?? ($isCryptoQuoted ? $creditCurrency : $debitCurrency);
        $amountFormatted = \App\Helpers\Common::formatNumber($enteredQty, false);
        $alertAt = \Carbon\Carbon::parse($transaction->created_at ?? now())->format('d M Y, H:i') . ' (UTC)';
        $rfqRef = $transaction->rfq_id ?? $transaction->id ?? '—';
        $instrument = $transaction->instrument ?? '—';
        $tradeType = $transaction->transaction_nature ?? 'Trade';
        $clientName = trim((string) ($transaction->account->client->name ?? ''));
    @endphp
    <table cellpadding="0" cellspacing="0">
        <tr>
            <td class="spacer">&nbsp;</td>
        </tr>
        <tr>
            <td class="container-td">
                <div align="center" class="container-div">
                    <table cellpadding="0" cellspacing="0">
                        <tr>
                            <td>&nbsp;</td>
                        </tr>
                        <tr>
                            <td align="center" class="logo-td"><img class="logo"
                                    src="{{ $ownCompany ? $ownCompany->logo : '' }}"
                                    alt="{{ $ownCompany ? $ownCompany->name : '' }}" aria-roledescription="logo">
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div align="center" class="inner-div">
                                    <table cellspacing="0" cellpadding="10">
                                        <tr>
                                            <td align="center">
                                                <h1>Liquidity alert — Aquanow</h1>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="left" style="text-align: left; line-height: 1.6;">
                                                <p>Aquanow {{ $debitCurrency }} liquidity is insufficient to fill a client quote while the client account had adequate balance. Please replenish the Aquanow wallet.</p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="left" style="text-align: left;">
                                                <div class="gray-bg" style="padding: 16px 20px; text-align: left; line-height: 1.65;">
                                                    <p style="margin: 0 0 10px;"><strong>Quote reference:</strong> {{ $rfqRef }}</p>
                                                    <p style="margin: 0 0 10px;"><strong>Instrument:</strong> {{ $instrument }}</p>
                                                    <p style="margin: 0 0 10px;"><strong>Side:</strong> {{ $tradeType }}</p>
                                                    <p style="margin: 0 0 10px;"><strong>Order size:</strong> {{ $amountFormatted }} {{ $enteredCcy }}</p>
                                                    <p style="margin: 0 0 10px;"><strong>Funding currency (Aquanow):</strong> {{ $debitCurrency }}</p>
                                                    @if($clientName !== '')
                                                        <p style="margin: 0 0 10px;"><strong>Client:</strong> {{ $clientName }}</p>
                                                    @endif
                                                    <p style="margin: 0 0 10px;"><strong>Client account:</strong> {{ $transaction->account->account_number ?? 'N/A' }}</p>
                                                    <p style="margin: 0;"><strong>Detected:</strong> {{ $alertAt }}</p>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="center">
                                                <h2>The {{ $ownCompany ? $ownCompany->name : '' }} Team</h2>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="center">@include('emails.system.partial.email-footer')</td>
                                        </tr>

                                    </table>
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
        <tr>
            <td class="spacer">&nbsp;</td>
        </tr>
    </table>
@stop
