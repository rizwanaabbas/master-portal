@extends('layouts.email')
@section('title', 'Request Approved')

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
                                                <h1>Pending Request</h1>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="center">
                                                @php
                                                    $typeMapping = ['APPLY_CARD' => 'New Card', 'APPLY_ACCOUNT' => 'New Account', 'APPLY_CRYPTO' => 'New Crypto Account', 'CLOSE_ACCOUNT' => 'Close Account', 'BALANCE_CERTIFICATE' => 'Balance Certificate'];
                                                    $requestType = $typeMapping[$request->type];
                                                @endphp
                                                <h2>The client service request is pending for approval.</h2>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="center">
                                                <div class="gray-bg">
                                                    <table class="data-table" cellpadding="10" cellspacing="0">
                                                        <tbody>
                                                            <tr>
                                                                <td><strong>Client</strong></td>
                                                                <td>{{ $request->maker->name }}
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td><strong>On Behalf of</strong></td>
                                                                <td>{{ $request->client->name }}
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td><strong>Platform</strong></td>
                                                                <td>{{ $request->platform }}
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td><strong>Request Type</strong></td>
                                                                <td>{{ $requestType }}
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
                                        <tr>
                                            <td align="center">
                                                <p>Please review the service-request and approve accordingly.</p>
                                                <p><a class="action-button"
                                                        href="{{ url('mobile-client-requests/view/' . $request->id) }}">View
                                                        Request</a></p>
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
