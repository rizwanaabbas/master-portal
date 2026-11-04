@extends('layouts.email')
@section('title', 'Client Incomplete Profile')

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
                                                <h1>Client Incomplete Profile</h1>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="center">
                                                <h2>We are sorry to inform you that your account is incomplete. Please
                                                    receive the detailed reason following.</h2>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="center">
                                                <h2>Incompleteness</h2>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="center">
                                                <h1 class="gray-bg gray-highlighting">{{ $client->reject_reason }}</h1>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="center">
                                                <p>Please <a href="{{ url('/login') }}"
                                                        style="text-decoration: underline"><strong>login</strong></a> and
                                                    fulfill the
                                                    above mentioned requirements.</p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="center">
                                                <h2>The {{ $ownCompany ? $ownCompany->name : '' }} Team</h2>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="center">@include('emails.client.partial.email-footer')</td>
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
