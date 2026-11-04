@extends('layouts.email')
@section('title', 'New Client Account')

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
                                                <h1>New Client Account</h1>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="center">
                                                <h2>New client account has been created by {{ $user->name }}.</h2>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="center">
                                                <div class="gray-bg">
                                                    <table class="data-table" cellpadding="10" cellspacing="0">
                                                        <tbody>
                                                            <tr>
                                                                <td><strong>Title</strong></td>
                                                                <td>{{ $account->title }}</td>
                                                            </tr>
                                                            <tr>
                                                                <td><strong>Account Number</strong></td>
                                                                <td>{{ $account->account_number }}</td>
                                                            </tr>
                                                            <tr>
                                                                <td><strong>Currency</strong></td>
                                                                <td>{{ \App\Helpers\Common::getCurrencyNameWithShort($account->account_currency) }}
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="center">
                                                <p>Click on below link and go to "Accounts" tab.</p>
                                                <p><a href="{{ url('/client-management/clients/' . $client->type . '/edit/' . $client->id) }}"
                                                        class="action-button"><strong>View Client</strong></a></p>
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
