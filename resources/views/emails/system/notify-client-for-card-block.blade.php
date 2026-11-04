@extends('layouts.email')
@section('title', 'Card Blocked')

@section('content')

<table cellpadding="0" cellspacing="0">
    <tr>
        <td class="spacer">&nbsp;</td>
    </tr>
    <tr>
        <td class="container-td">
              
            <div align="center" class="container-div">
                    <table >
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
                                                <h1>Card Blocked</h1>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="center">
                                                <h2>Your card has been blocked.</h2>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="center">
                                                <a href="{{ $url }}" class="action-button"><strong>View
                                                        Card Details</strong></a>
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
