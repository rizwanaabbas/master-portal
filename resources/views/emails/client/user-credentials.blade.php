@extends('layouts.email')
@section('title', 'Client Credentials Email')

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
                                                <h1>Credentials</h1>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="center">
                                                <p>Thank you for choosing {{ $ownCompany ? $ownCompany->name : '' }} -
                                                    {{ $ownCompany ? $ownCompany->country : '' }} as your financial
                                                    partner.</p>
                                                <p>We are honoured to have you as our new client and it is our pleasure to
                                                    provide you with our services.</p>
                                                <p>Here is your login credentials, which are auto generated, please change
                                                    your password after you login.</p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="center">
                                                <div class="gray-bg">
                                                    <table class="data-table" cellpadding="10" cellspacing="0">
                                                        <tbody>
                                                            <tr>
                                                                <td><strong>URL</strong></td>
                                                                <td><a
                                                                        href="{{ env('REACT_APP_BASE_URL') }}">{{ env('REACT_APP_BASE_URL') }}</a>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td><strong>Username</strong></td>
                                                                <td>{{ $data['email'] }}</td>
                                                            </tr>
                                                            <tr>
                                                                <td><strong>Password</strong></td>
                                                                <td>{{ $data['password'] }}</td>
                                                            </tr>
                                                            <tr>
                                                                <td colspan="2">Your password is system generated, you
                                                                    can change it at your own convenience.</td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="center">
                                                <p>We would like to take this opportunity to thank you once again for your
                                                    kind support. Please do not hesitate to contact the undersigned if you
                                                    have any queries.</p>
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
