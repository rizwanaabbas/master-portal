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
                                                @php
                                                    $targetName = $isSubAccount
                                                        ? "Sub-Account ({$client->name})"
                                                        : 'Your Profile';
                                                @endphp

                                                @if (isset($reason) && !empty($reason))
                                                    <h1>Action Required: Update {{ $targetName }}</h1>
                                                @else
                                                    <h1>{{ $isSubAccount ? 'Sub-Account ' : 'Client ' }} Identity
                                                        Verification Required</h1>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="center">
                                                @if (isset($reason) && !empty($reason))
                                                    <h2>{{ $reason }}</h2>
                                                @else
                                                    <h2>We are sorry to inform you that
                                                        {{ $isSubAccount ? 'your sub-account' : 'your account' }} needs a
                                                        liveness check.
                                                        Please perform the liveness check by clicking the button below.</h2>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="center">
                                                <p style="text-align: revert;">
                                                    <a class="action-button" href="{{ $sumsubUrl }}">
                                                        @if (isset($reason) && !empty($reason))
                                                            Update Information
                                                        @else
                                                            Perform Liveness
                                                        @endif
                                                    </a>
                                                </p>
                                                {{-- <p><small>If you prefer, you can also log into your Client Portal, and you
                                                        will be redirected automatically.</small></p> --}}
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
