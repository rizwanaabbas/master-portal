@extends('layouts.email')
@section('title', 'Welcome Client Email')

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
                                                <h1>Welcome {{ $client->name }}</h1>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="center">
                                                <p>Thank you for choosing {{ $ownCompany ? $ownCompany->name : '' }} -
                                                    {{ $ownCompany ? $ownCompany->country : '' }} as your financial partner.
                                                </p>
                                                <p>We are honoured to have you as our new client and it is our pleasure to
                                                    provide you with our services.</p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="center">
                                                <p><strong>Here is what will happen next:</strong></p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <ol>
                                                    <li>You will find a PDF attachment along with this email, please fill
                                                        the information accordingly</li>
                                                    <li>You will also need to sign and stamp as mentioned in the PDF</li>
                                                    <li>Once you are done, you need to upload it back in the system</li>
                                                    <li>Login to your account and upload the signed and stamped PDF</li>
                                                </ol>
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
