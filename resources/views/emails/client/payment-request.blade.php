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
                                                <h1>Payment Required</h1>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="center">
                                                <h2>We hope this message finds you well. We appreciate your interest in our
                                                    services.
                                                    It has come to our attention that your account is currently incomplete,
                                                    and we kindly request you to finalize the payment process to ensure
                                                    uninterrupted access to our services.</h2>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="center">
                                                <p style="text-align: revert;"><a class="action-button"
                                                        href="{{ url($link) }}"> Pay Now </a></p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="center">
                                                <h3>
                                                    If you have already made the payment, please disregard this message.
                                                </h3>
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
