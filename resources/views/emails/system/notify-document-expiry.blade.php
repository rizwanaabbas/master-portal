@extends('layouts.email')
@section('title', 'Document Expiry')

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
                                                <h1>Document Expiry</h1>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="center">
                                                <h2>The following {{ count($clientsData) }} clients' documents are getting
                                                    expired or are already expired.</h2>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="center">
                                                <h3>Please take the necessary actions to renew them on time.</h3>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <div class="gray-bg">
                                                    <table class="data-table" cellpadding="10" cellspacing="0">
                                                        <thead>
                                                            <tr>
                                                                <th>S.No.</th>
                                                                <th align="center">Client Name</th>
                                                                <th align="left">Document Type</th>
                                                                <th align="left">Expiry Date</th>
                                                                <th align="center">Action</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach ($clientsData as $index => $clientData)
                                                                <tr>
                                                                    <td align="center">{{ $index + 1 }}</td>
                                                                    <td align="left">
                                                                        {{ $clientData['clientname'] }}</td>
                                                                    <td align="left">{{ $clientData['document'] }}</td>
                                                                    <td align="left">{{ $clientData['expiryDate'] }}</td>
                                                                    <td align="center">
                                                                        <a href="{{ $clientData['url'] }}"
                                                                            class="action-button"><strong>View
                                                                                Client</strong></a>
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
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
                    </table>
                </div>
            </td>
        </tr>
        <tr>
            <td class="spacer">&nbsp;</td>
        </tr>
    </table>
@stop
