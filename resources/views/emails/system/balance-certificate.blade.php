@extends('layouts.email')
@section('title', 'Balance Certificate')

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
                                                <h1>Balance Certificate</h1>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="center">
                                                @php
                                                    $typeMapping = ['BALANCE_CERTIFICATE' => 'Balance Certificate'];
                                                    $requestType = $typeMapping[$service->type];
                                                @endphp
                                                <h2>Your {{ $requestType }} request has been approved.</h2>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="center">
                                                <p>
                                                    <a class="action-button" href="{{ $file_url }}">
                                                        View Balance Certificate
                                                    </a>
                                                </p>
                                            </td>
                                        </tr>
                                        <td align="center">
                                            <p>
                                                You can download your Balance Certificate PDF from the link below.
                                            </p>
                                            <p>
                                                <a href="{{ $file_url }}">
                                                    {{ $file_url }}
                                                </a>
                                            </p>
                                        </td>
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
        <tr>
            <td class="spacer">&nbsp;</td>
        </tr>
    </table>

@stop
