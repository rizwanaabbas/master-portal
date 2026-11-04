@extends('layouts.email')
@section('title', 'Transaction Approved')

@section('content')
    @php
        $cryptoNatures = ['Buy', 'Sell', 'Swap', 'Deposit', 'Withdraw'];
        $isCryptoTxn = !empty($trn->crypto_system)
            || ($trn->crypto_amount ?? 0) > 0
            || in_array($trn->transaction_nature ?? '', $cryptoNatures, true);
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
                                                <h1>{{ $title_needle }} approved</h1>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="left" style="text-align: left; line-height: 1.6;">
                                                <p>Your request has been approved @if($isCryptoTxn) and recorded on your account @endif.</p>
                                            </td>
                                        </tr>
                                        @if($isCryptoTxn)
                                            <tr>
                                                <td align="left" style="text-align: left;">
                                                    @include('emails.partials.crypto-transaction-details', ['transaction' => $trn])
                                                    <p style="margin: 10px 0 0;"><strong>Status:</strong> Approved</p>
                                                </td>
                                            </tr>
                                        @endif
                                        <tr>
                                            <td align="center">
                                                <a href="{{ $url }}" class="action-button"><strong>View transaction</strong></a>
                                            </td>
                                        </tr>
                                        @if($isCryptoTxn)
                                            <tr>
                                                <td align="left" style="text-align: left; line-height: 1.5; font-size: 11px; color: #555;">
                                                    <p>If you did not authorise this transaction, contact us immediately.</p>
                                                </td>
                                            </tr>
                                        @endif
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
