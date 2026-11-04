@extends('layouts.email')
@section('title', 'Transaction Request')

@section('content')
    @php
        $nature = strtolower($transaction->transaction_nature ?? 'deposit');
        $viewUrl = $url ?? url('finance-management/pending-transactions/view/' . $nature . '/' . $transaction->id);
        $cryptoNatures = ['Buy', 'Sell', 'Swap', 'Deposit', 'Withdraw'];
        $isCryptoTxn = !empty($transaction->crypto_system)
            || ($transaction->crypto_amount ?? 0) > 0
            || in_array($transaction->transaction_nature ?? '', $cryptoNatures, true);
        $currencyShort = optional($transaction->account->currency)->short ?? '';
        $fiatAmount = \App\Helpers\Common::formatNumber($transaction->amount ?? 0, true);
        $submittedAt = \Carbon\Carbon::parse($transaction->transaction_date ?? $transaction->updated_at)->format('d M Y, H:i') . ' (UTC)';
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
                                                <h1>Transaction — approval required</h1>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="left" style="text-align: left; line-height: 1.6;">
                                                <p>A transaction submitted by <strong>{{ $transaction->creator->name ?? 'the client' }}</strong> requires finance review.</p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="left" style="text-align: left;">
                                                @if($isCryptoTxn)
                                                    @include('emails.partials.crypto-transaction-details', ['transaction' => $transaction])
                                                @else
                                                    <div class="gray-bg" style="padding: 16px 20px; text-align: left; line-height: 1.65;">
                                                        <p style="margin: 0 0 10px;"><strong>Reference:</strong> #{{ $transaction->id }}</p>
                                                        <p style="margin: 0 0 10px;"><strong>Transaction:</strong> {{ $transaction->transaction_nature }}</p>
                                                        <p style="margin: 0 0 10px;"><strong>Amount:</strong> {{ $fiatAmount }}@if($currencyShort) {{ $currencyShort }}@endif</p>
                                                        <p style="margin: 0 0 10px;"><strong>Account:</strong> {{ $transaction->account->account_number }}</p>
                                                        <p style="margin: 0;"><strong>Submitted:</strong> {{ $submittedAt }}</p>
                                                    </div>
                                                    @if($transaction->transaction_type == 'Credit' && !empty($transaction->senderDetails))
                                                        <p style="margin: 12px 0 0; font-size: 11px; color: #555; text-align: left;">Sender and bank details for this deposit are recorded in the portal.</p>
                                                    @endif
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="center">
                                                <p>Please verify and approve or reject in the portal.</p>
                                                <p><a class="action-button" href="{{ $viewUrl }}">Review transaction</a></p>
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
