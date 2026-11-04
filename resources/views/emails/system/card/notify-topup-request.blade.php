@extends('layouts.email')
@section('title', 'Card Top-up Request')

@section('content')
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
                                                <h1>Top-up Request</h1>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="center">
                                                <h2>The following Top-up request has been created by
                                                    {{ $transaction->creator->name }}.</h2>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="center">
                                                <div class="gray-bg">
                                                    <table class="data-table" cellpadding="10" cellspacing="0">
                                                        <tbody>
                                                            <tr>
                                                                <td><strong>Account</strong></td>
                                                                <td>{{ $transaction->account->title }}</td>
                                                            </tr>
                                                            <tr>
                                                                <td><strong>Number</strong></td>
                                                                <td>{{ $transaction->account->account_number }}</td>
                                                            </tr>
                                                            <tr>
                                                                <td><strong>Mode</strong></td>
                                                                <td>{{ $transaction->transaction_nature }}</td>
                                                            </tr>
                                                            <tr>
                                                                <td><strong>Type</strong></td>
                                                                <td>{{ $transaction->transaction_type }}</td>
                                                            </tr>
                                                            <tr>
                                                                <td><strong>Amount</strong></td>
                                                                <td>&nbsp;{{ \App\Helpers\Common::formatNumber($transaction->amount, false) }}
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td><strong>Request Date</strong></td>
                                                                <td>{{ \Carbon\Carbon::parse($transaction->created_at)->format('d M, Y') }}
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td><strong>Top-up Date</strong></td>
                                                                <td>{{ \Carbon\Carbon::parse($transaction->transaction_date)->format('d M, Y') }}
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td colspan="2">
                                                                    <p><strong>Remarks:</strong><br>{{ $transaction->remarks }}
                                                                    </p>
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="center">
                                                <p>Please review the transaction and approve accordingly.</p>
                                                <p><a class="action-button"
                                                        href="{{ url('card-management/card/topup-pending-requests/view/' . $transaction->id) }}">View
                                                        Transaction</a></p>
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
