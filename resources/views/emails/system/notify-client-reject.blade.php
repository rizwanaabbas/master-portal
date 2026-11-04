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
                                                <h1>Client Incomplete Profile</h1>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="center">
                                                <h2>The client profile ({{ $client->name }}) is incomplete.</h2>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="center">
                                                <h2>Incompleteness</h2>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="center">
                                                <h1 class="gray-bg gray-highlighting">{{ $client->reject_reason }}</h1>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="center">
                                                <a href="{{ url('/client-management/clients/' . $client->type . '/edit/' . $client->id) }}"
                                                    class="action-button"><strong>View
                                                        Client</strong></a>
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
