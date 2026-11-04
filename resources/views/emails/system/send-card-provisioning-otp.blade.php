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
                                    <table cellspacing="0" cellpadding="10" width="100%" style="font-family: Arial, sans-serif;">
                                        <tr>
                                            <td align="center">
                                                <h1 style="margin: 0; font-size: 28px;">Confirm Your Card</h1>
                                            </td>
                                        </tr>

                                        <tr>
                                            <td align="center">
                                                <h2 style="margin: 10px 0;">
                                                    Your card ending with {{$data->LastFourDigit}} provisioning code 
                                                </h2>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="center">
                                                <p style="margin: 0 0 8px; color: #555;">Verification code</p>
                                                <h1 class="otp gray-highlighting">{{ $data->Otp }}</h1>
                                            </td>
                                        </tr>

                                        <tr>
                                            <td align="center">
                                                <p style="font-size: 16px; margin: 20px 0;">
                                                    Enter this code to complete verification. It expires shortly. Do not share it with anyone.
                                                </p>
                                            </td>
                                        </tr>

                                        <tr>
                                            <td align="center">
                                                <h2 style="margin: 30px 0 10px 0;">The {{ $ownCompany ? $ownCompany->name : '' }} Team</h2>
                                            </td>
                                        </tr>

                                        <tr>
                                            <td align="center">
                                                @include('emails.system.partial.email-footer')
                                            </td>
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
