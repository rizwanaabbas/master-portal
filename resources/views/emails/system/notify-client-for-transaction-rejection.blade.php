@extends('layouts.email')
@section('title', 'Transaction Rejected')

@section('content')
    @php
        $cryptoNatures = ['Buy', 'Sell', 'Swap', 'Deposit', 'Withdraw'];
        $isCryptoTxn = !empty($trn->crypto_system)
            || ($trn->crypto_amount ?? 0) > 0
            || in_array($trn->transaction_nature ?? '', $cryptoNatures, true);
        $reason = trim($trn->reject_reason ?? '') !== '' ? $trn->reject_reason : 'No reason was provided.';
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
                                                <h1>@if($isCryptoTxn) Crypto transaction declined @else Transaction declined @endif</h1>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="left" style="text-align: left; line-height: 1.6;">
                                                <p>Your {{ $isCryptoTxn ? 'digital asset' : '' }} transaction request could not be approved.</p>
                                            </td>
                                        </tr>
                                        @if($isCryptoTxn)
                                            <tr>
                                                <td align="left" style="text-align: left;">
                                                    @include('emails.partials.crypto-transaction-details', ['transaction' => $trn])
                                                </td>
                                            </tr>
                                        @else
                                            <tr>
                                                <td align="left" style="text-align: left;">
                                                    <div class="gray-bg" style="padding: 16px 20px; text-align: left; line-height: 1.65;">
                                                        <p style="margin: 0 0 10px;"><strong>Reference:</strong> #{{ $trn->id }}</p>
                                                        <p style="margin: 0 0 10px;"><strong>Transaction:</strong> {{ $trn->transaction_nature ?? '—' }}</p>
                                                        <p style="margin: 0;"><strong>Status:</strong> Rejected</p>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endif
                                        <tr>
                                            <td align="left" style="text-align: left;">
                                                <p style="margin: 12px 0 0;"><strong>Reason:</strong> {{ $reason }}</p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="center">
                                                <a href="{{ $url }}" class="action-button"><strong>View details</strong></a>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="left" style="text-align: left; line-height: 1.5; font-size: 11px; color: #555;">
                                                <p>Contact us if you believe this decision was made in error.</p>
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
