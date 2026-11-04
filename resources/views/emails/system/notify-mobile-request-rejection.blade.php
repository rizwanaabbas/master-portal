@extends('layouts.email')
@section('title', 'Request Declined')

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
                                                <h1>Request Declined</h1>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="center">
                                                @php
                                                    $typeMapping = ['APPLY_CARD' => 'new Card', 'APPLY_ACCOUNT' => 'new Fiat Account', 'APPLY_CRYPTO' => 'new Crypto Account', 'CLOSE_ACCOUNT' => 'Close Account', 'BALANCE_CERTIFICATE' => 'Balance Certificate'];
                                                    $requestType = $typeMapping[$request->type];
                                                @endphp
                                                <h2>Your
                                                    {{ $requestType }} request has been declined.</h2>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="center">
                                                <div class="gray-bg">
                                                    <table class="data-table" cellpadding="10" cellspacing="0">
                                                        <tbody>
                                                            <tr>
                                                                <td colspan="2">
                                                                    <p><strong>Decline Reason:
                                                                        </strong>{{ $request->reject_reason }}
                                                                    </p>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td><strong>Request Date</strong></td>
                                                                <td>{{ \Carbon\Carbon::parse($request->created_at)->format('d M, Y') }}
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </td>
                                        </tr>
                                        {{-- <tr>
                                            <td align="center">
                                                <p>Please review the Request and change it accordingly.</p>
                                                <p><a class="action-button" href="{{ $url }}">View
                                                        Request</a></p>
                                            </td>
                                        </tr> --}}
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
