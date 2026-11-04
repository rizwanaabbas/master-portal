@extends('layouts.email')
@section('title', 'Transaction Approved')

@section('content')
    @php
        $nature = $data->transaction_nature ?? 'Transaction';
        $isSwap = $nature === 'Swap';
        $fromAccount = $data->account;
        $toAccount = $data->receiver ?? null;
        $fromCurrency = optional($fromAccount?->currency)->short ?? '';
        $toCurrency = $toAccount ? optional($toAccount?->currency)->short ?? '' : null;
        $isCryptoDebit = in_array($nature, ['Sell', 'Withdraw'], true);
        $debitAmount = $isCryptoDebit ? ($data->crypto_amount ?? 0) : ($data->amount ?? 0);
        $creditAmount = $isCryptoDebit ? ($data->amount ?? 0) : ($data->crypto_amount ?? 0);
        $debitFormatted = \App\Helpers\Common::formatNumber($debitAmount, (bool) optional($fromAccount?->currency)->is_crypto);
        $creditFormatted = $toAccount
            ? \App\Helpers\Common::formatNumber($creditAmount, (bool) optional($toAccount?->currency)->is_crypto)
            : \App\Helpers\Common::formatNumber($creditAmount, true);
        $cryptoFormatted = \App\Helpers\Common::formatNumber($data->crypto_amount, true);
        $fiatFormatted = $data->amount > 0
            ? \App\Helpers\Common::formatNumber($data->amount, false)
            : null;
        $completedAt = \Carbon\Carbon::parse($data->transaction_date)->format('d M Y, H:i') . ' (UTC)';
        $accountLabel = strip_tags($fromAccount->title ?? '');
        $toAccountLabel = strip_tags($toAccount->title ?? '');
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
                                                <h2>Your crypto transaction has been approved</h2>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="left" style="text-align: left; line-height: 1.6;">
                                                <p>This confirms that the following digital asset transaction has been processed on your account.</p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="left" style="text-align: left;">
                                                <div class="summary-box">
                                                    <p style="margin: 0 0 10px;"><strong>Reference:</strong> #{{ $data->id }}</p>
                                                    <p style="margin: 0 0 10px;"><strong>Transaction:</strong> {{ $nature }}</p>
                                                    <p style="margin: 0 0 10px;"><strong>Status:</strong> Approved</p>
                                                    @if($isSwap)
                                                        <p style="margin: 0 0 10px;"><strong>From asset:</strong> {{ $debitFormatted }}@if($fromCurrency) {{ $fromCurrency }}@endif</p>
                                                        <p style="margin: 0 0 10px;"><strong>From account:</strong> {{ $fromAccount->account_number }}@if($accountLabel !== '') — {{ $accountLabel }}@endif</p>
                                                        <p style="margin: 0 0 10px;"><strong>To asset:</strong> {{ $creditFormatted }}@if($toCurrency) {{ $toCurrency }}@endif</p>
                                                        @if($toAccount)
                                                            <p style="margin: 0 0 10px;"><strong>To account:</strong> {{ $toAccount->account_number }}@if($toAccountLabel !== '') — {{ $toAccountLabel }}@endif</p>
                                                        @elseif(!empty($data->to_wallet_address))
                                                            <p style="margin: 0 0 10px;"><strong>To account:</strong> {{ $data->to_wallet_address }}</p>
                                                        @endif
                                                    @else
                                                        @if($data->crypto_amount > 0)
                                                            <p style="margin: 0 0 10px;"><strong>Digital asset amount:</strong> {{ $cryptoFormatted }}@if($fromCurrency) {{ $fromCurrency }}@endif</p>
                                                        @endif
                                                        @if($fiatFormatted !== null && in_array($nature, ['Buy', 'Sell'], true))
                                                            <p style="margin: 0 0 10px;"><strong>Fiat leg:</strong> {{ $fiatFormatted }}</p>
                                                        @endif
                                                        <p style="margin: 0 0 10px;"><strong>Account:</strong> {{ $fromAccount->account_number }}@if($accountLabel !== '') — {{ $accountLabel }}@endif</p>
                                                    @endif
                                                    @if(!empty($data->crypto_network))
                                                        <p style="margin: 0 0 10px;"><strong>Network:</strong> {{ $data->crypto_network }}</p>
                                                    @endif
                                                    <p style="margin: 0;"><strong>Date:</strong> {{ $completedAt }}</p>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="left" style="text-align: left; line-height: 1.5; font-size: 11px; color: #555;">
                                                <p>If you did not authorise this transaction, contact us immediately using the details in the footer below.</p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="center">
                                                <p><a class="action-button" href="{{ $url }}">View transaction</a></p>
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
