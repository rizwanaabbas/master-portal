@extends('layouts.email')
@section('title', 'Crypto Deposit Credited')

@section('content')
    @php
        $viewUrl = $url ?? url('crypto-management/crypto-pending-transactions/view/crypto-transaction/' . $transaction->id);
        $account = $transaction->account;
        $currencyShort = optional($account?->currency)->short ?? '';
        $amountFormatted = \App\Helpers\Common::formatNumber($transaction->crypto_amount ?: $transaction->amount, true);
        $postedAt = \Carbon\Carbon::parse($transaction->transaction_date)->format('d M Y, H:i') . ' (UTC)';
        $accountLabel = strip_tags($account->title ?? '');
        $accountNumber = $account->account_number ?? '';
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
                                                <h1>Crypto deposit credited</h1>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="left" style="text-align: left; line-height: 1.6;">
                                                <p>A Fireblocks incoming deposit has been posted to the client ledger.</p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="left" style="text-align: left;">
                                                <div class="summary-box">
                                                    <p style="margin: 0 0 10px;"><strong>Reference:</strong> #{{ $transaction->id }}</p>
                                                    <p style="margin: 0 0 10px;"><strong>Transaction:</strong> Deposit</p>
                                                    <p style="margin: 0 0 10px;"><strong>Status:</strong> Approved</p>
                                                    <p style="margin: 0 0 10px;"><strong>Amount:</strong> {{ $amountFormatted }}@if($currencyShort) {{ $currencyShort }}@endif</p>
                                                    <p style="margin: 0 0 10px;"><strong>Account:</strong> {{ $accountNumber }}@if($accountLabel !== '') — {{ $accountLabel }}@endif</p>
                                                    @if(!empty($transaction->crypto_network))
                                                        <p style="margin: 0 0 10px;"><strong>Network:</strong> {{ $transaction->crypto_network }}</p>
                                                    @endif
                                                    <p style="margin: 0 0 10px;"><strong>Provider:</strong> Fireblocks</p>
                                                    <p style="margin: 0;"><strong>Posted:</strong> {{ $postedAt }}</p>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="center">
                                                <p><a class="action-button" href="{{ $viewUrl }}">View transaction</a></p>
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
