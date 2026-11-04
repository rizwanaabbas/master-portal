@extends('layouts.email')
@section('title', 'Crypto Transaction Request')

@section('content')
    @php
        $viewUrl = $url ?? url('crypto-management/crypto-pending-transactions/view/crypto-transaction/' . $transaction->id);
        $targetAccount = ($transaction->transaction_nature !== 'Deposit' && !empty($transaction->receiver))
            ? $transaction->receiver
            : $transaction->account;
        $currencyShort = optional($targetAccount->currency)->short ?? '';
        $amountFormatted = \App\Helpers\Common::formatNumber($transaction->crypto_amount, true);
        $submittedAt = \Carbon\Carbon::parse($transaction->transaction_date)->format('d M Y, H:i') . ' (UTC)';
        $provider = $transaction->crypto_system ?? null;
        $accountLabel = strip_tags($transaction->account->title ?? '');
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
                                                <h1>Crypto transaction — approval required</h1>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="left" style="text-align: left; line-height: 1.6;">
                                                <p>A digital asset transaction submitted by <strong>{{ $transaction->creator->name ?? 'the client' }}</strong> is awaiting finance review.</p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="left" style="text-align: left;">
                                                <div class="summary-box">
                                                    <p style="margin: 0 0 10px;"><strong>Reference:</strong> #{{ $transaction->id }}</p>
                                                    <p style="margin: 0 0 10px;"><strong>Transaction:</strong> {{ $transaction->transaction_nature }}</p>
                                                    <p style="margin: 0 0 10px;"><strong>Amount:</strong> {{ $amountFormatted }}@if($currencyShort) {{ $currencyShort }}@endif</p>
                                                    <p style="margin: 0 0 10px;"><strong>Account:</strong> {{ $transaction->account->account_number }}@if($accountLabel !== '') — {{ $accountLabel }}@endif</p>
                                                    @if($transaction->transaction_nature !== 'Deposit' && ($transaction->to_wallet_address || !empty($transaction->receiver)))
                                                        <p style="margin: 0 0 10px;"><strong>Destination:</strong>
                                                            @if(!empty($transaction->receiver))
                                                                {{ $transaction->receiver->account_number }}
                                                            @else
                                                                {{ $transaction->to_wallet_address }}
                                                            @endif
                                                        </p>
                                                    @endif
                                                    @if(!empty($transaction->crypto_network))
                                                        <p style="margin: 0 0 10px;"><strong>Network:</strong> {{ $transaction->crypto_network }}</p>
                                                    @endif
                                                    @if(!empty($provider))
                                                        <p style="margin: 0 0 10px;"><strong>Provider:</strong> {{ $provider }}</p>
                                                    @endif
                                                    <p style="margin: 0;"><strong>Submitted:</strong> {{ $submittedAt }}</p>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="center">
                                                <p>Please verify the details and approve or reject in the portal.</p>
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
