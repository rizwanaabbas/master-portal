@extends('layouts.email')
@section('title', 'Card Assigned')

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
                                                <h1>Card Assigned</h1>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="center">
                                                <h2>Your have been assigned the following card(s). Please login to the <a
                                                        href="{{ url('/') }}">portal</a> and setup your card(s) PIN.
                                                </h2>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="center">
                                                <div class="gray-bg">
                                                    <table class="data-table" cellpadding="10" cellspacing="0">
                                                        <thead>
                                                            <th>Card Holder Name</th>
                                                            <th>Card Number</th>
                                                        </thead>
                                                        <tbody>
                                                            @foreach ($links as $link)
                                                                <tr>
                                                                    <td>
                                                                        {{ $link->linkedCard->cardHolderName ? $link->linkedCard->cardHolderName : 'N/A' }}
                                                                    </td>
                                                                    <td>
                                                                        {{ $link->linkedCard->cardNumber ? $link->linkedCard->cardNumber : 'N/A' }}
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                            <tr>
                                                                <td colspan="2"><strong>Date:
                                                                    </strong>{{ \Carbon\Carbon::parse($link->created_at)->format('d M, Y') }}
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
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
