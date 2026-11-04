@extends('layouts.email')
@section('title', 'Login OTP')

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
                                                <h1>Confirm your login</h1>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="center">
                                                <p style="margin: 0 0 8px; color: #555;">Your one-time verification code</p>
                                                <h1 class="otp gray-highlighting">{{ $data['otp'] }}</h1>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="center" style="color: #555; font-size: 13px;">
                                                <p>This code expires shortly. Do not share it with anyone, including {{ $ownCompany ? $ownCompany->name : 'us' }} staff.</p>
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
