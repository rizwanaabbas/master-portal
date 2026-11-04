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
                                                <h1>Welcome {{ $client->name }}</h1>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="center">
                                                <p>Thank you for choosing {{ $ownCompany ? $ownCompany->name : '' }} -
                                                    {{ $ownCompany ? $ownCompany->country : '' }}
                                                    as your financial partner.</p>
                                                <p>We are honoured to have you as our new client and it is our pleasure to
                                                    provide you with our services.</p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="center">

                                                <div class="gray-bg">
                                                    <table class="data-table" cellpadding="10" cellspacing="0">
                                                        <thead>
                                                            <tr>
                                                                <th>Title</th>
                                                                <th>Acc Number</th>
                                                                <th>Acc Currency</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach ($client->clientAccounts as $account)
                                                                <tr>
                                                                    <td>{{ $account->title }}</td>
                                                                    <td>{{ $account->account_number }}</td>
                                                                    <td>{{ $account->currency->name }}</td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="center">
                                                <p>We would like to take this opportunity to thank you once again for your
                                                    kind support. Please do not hesitate to
                                                    contact the undersigned if you have any queries.</p>
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
