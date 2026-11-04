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
                                    style="width: auto !important; max-height: 55px !important;"
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
                                                <h1 style="text-align: revert;">Hi {{ $user->name }}</h1>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="center">
                                                <p style="text-align: revert;">Please click the button below to verify your
                                                    email address.</p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="center">
                                                <p style="text-align: revert;"><a class="action-button"
                                                        href="{{ $url }}">Verify Email
                                                        Address</a></p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="center">
                                                <p style="text-align: revert;">If you did not create an account, no further
                                                    action is required.</p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="center">
                                                <h2 style="text-align: revert;">The
                                                    {{ $ownCompany ? $ownCompany->name : '' }} Team</h2>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="center">Thanks & best regards.
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="center">
                                                <p style="text-align: revert; margin-block-end: 0px;">
                                                    {{ $ownCompany ? $ownCompany->poc_name : '' }}
                                                </p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="center">
                                                <p style="text-align: revert;">
                                                    <b>{{ $ownCompany ? $ownCompany->name : '' }}</b>
                                                    <br><b>{{ $ownCompany ? $ownCompany->country : '' }}</b>
                                                    <br><small>Email: <a
                                                            href="mailto:{{ $ownCompany ? $ownCompany->poc_email : '' }}">{{ $ownCompany ? $ownCompany->poc_email : '' }}</a></small>
                                                </p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="center">
                                                <p style="text-align: revert;">
                                                    <small>
                                                        Disclaimer:<br>
                                                        This email and its attachment (s) transmitted herewith (the
                                                        “Email”), is confidential and intended solely for the use of the
                                                        individual or entity to whom it is addressed to. You should not
                                                        disseminate, distribute or copy this email and/or any part thereof,
                                                        to any person/entity. If you have received this email by mistake,
                                                        please notify {{ $ownCompany ? $ownCompany->name : '' }} (the
                                                        “Addresser”- {{ $ownCompany ? $ownCompany->short_name : '' }})
                                                        immediately and delete it from your possession. You are hereby
                                                        informed that the email might not be virus-free and the information
                                                        contained herein could be intercepted, corrupted, lost, destroyed,
                                                        arrive late or incomplete,
                                                        {{ $ownCompany ? $ownCompany->short_name : '' }} therefore is not
                                                        responsible directly or indirectly for any loss or damage as a
                                                        consequence therefrom.
                                                        The {{ $ownCompany ? $ownCompany->short_name : '' }} gives no
                                                        warranty as to the integrity, accuracy or security of this email and
                                                        any attachment(s), nor does it accept responsibility for any errors
                                                        or omissions in the content.
                                                        <br><span style="color:green">Please consider the environment before
                                                            printing this email.</span>
                                                    </small>
                                                </p>
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
