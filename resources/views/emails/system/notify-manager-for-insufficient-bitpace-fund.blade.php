@extends('layouts.email')
@section('title', 'Insufficient Wallet Balance')

@section('content')
    @php
        $currencyShort = $transaction->account->currency->short ?? '';
        $currencyName = $transaction->account->currency->name ?? $currencyShort;
        $amountRequired = $transaction->crypto_amount > 0
            ? \App\Helpers\Common::formatNumber($transaction->crypto_amount, false)
            : \App\Helpers\Common::formatNumber($transaction->amount, false);
        $alertAt = \Carbon\Carbon::parse($transaction->created_at)->format('d M Y, H:i') . ' (UTC)';
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
                                                <h1>Liquidity alert — Bitpace</h1>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="left" style="text-align: left; line-height: 1.6;">
                                                <p>Bitpace {{ $currencyName }} ({{ $currencyShort }}) wallet balance is insufficient to complete a pending client transaction. Please top up the provider wallet.</p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="left" style="text-align: left;">
                                                <div class="gray-bg" style="padding: 16px 20px; text-align: left; line-height: 1.65;">
                                                    <p style="margin: 0 0 10px;"><strong>Reference:</strong> #{{ $transaction->id }}</p>
                                                    <p style="margin: 0 0 10px;"><strong>Transaction:</strong> {{ $transaction->transaction_nature }}</p>
                                                    <p style="margin: 0 0 10px;"><strong>Amount required:</strong> {{ $amountRequired }}@if($currencyShort) {{ $currencyShort }}@endif</p>
                                                    <p style="margin: 0 0 10px;"><strong>Client account:</strong> {{ $transaction->account->account_number }}</p>
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
