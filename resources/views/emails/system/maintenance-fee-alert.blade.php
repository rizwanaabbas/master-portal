@extends('layouts.email')
@section('title', 'Maintenance Fee Alert')

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
                                                <h1 class="text-danger">Maintenance Fee Alert</h1>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="center">
                                                <h2>
                                                    The following is the report for the Monthly Maintenance Fee deduction.
                                                </h2>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="center">
                                                <div class="gray-bg">
                                                    <table class="data-table" cellpadding="10" cellspacing="0">
                                                        <thead>
                                                            <tr>
                                                                <th>Paid Clients Count</th>
                                                                <th>Unpaid Clients Count</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr>
                                                                <td>{{ $data['paid_count'] }}</td>
                                                                <td>{{ $data['unpaid_count'] }}</td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </td>
                                        </tr>

                                        @php
                                            $count = is_array($clientsPendingFor3Months)
                                                ? count($clientsPendingFor3Months)
                                                : $clientsPendingFor3Months->count();
                                        @endphp

                                        @if ($count > 0)
                                            <tr>
                                                <td align="center">
                                                    <h2>
                                                        The following client(s) have not paid the Maintenance Fee for 3
                                                        months.
                                                    </h2>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td align="center">
                                                    <div class="gray-bg">
                                                        <table class="data-table" cellpadding="10" cellspacing="0">
                                                            <thead>
                                                                <tr>
                                                                    <th>S.No.</th>
                                                                    <th>Client Name</th>
                                                                    <th>Status</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @foreach ($clientsPendingFor3Months as $client)
                                                                    <tr>
                                                                        <td>{{ $loop->iteration }}</td>
                                                                        <td>{{ $client->name }}</td>
                                                                        <td>{{ $client->status }}</td>
                                                                    </tr>
                                                                @endforeach
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endif
                                        <tr>
                                            <td align="center">
                                                <a href="{{ $url }}" class="action-button">
                                                    <strong>View Report</strong>
                                                </a>
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
