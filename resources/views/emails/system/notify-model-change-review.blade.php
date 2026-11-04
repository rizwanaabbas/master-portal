@extends('layouts.email')
@section('title', 'Changes {{ $status }}')

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
                                                <h1>Changes {{ $status }}</h1>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="center">
                                                <h2>Your changes has been {{ $status }} by {{ $approver->name }}.
                                                </h2>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="center">
                                                @foreach ($updates as $change)
                                                    <p><strong>{{ $change->title }}</strong></p>
                                                    <div class="gray-bg">
                                                        <table class="data-table" cellpadding="3" cellspacing="0">
                                                            <thead>
                                                                <tr>
                                                                    <th>Field Changed</th>
                                                                    <th>Old Value</th>
                                                                    <th>New Value</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @foreach ($change->details as $detail)
                                                                    <tr>
                                                                        <td>{{ $detail->key }}</td>
                                                                        <td>{{ $detail->old_value }}</td>
                                                                        <td>{{ $detail->new_value }}</td>
                                                                    </tr>
                                                                @endforeach
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                    @if ($status == 'Rejected')
                                                        <p><strong>Reason:<br></strong> {{ $change->reject_reason }}</p>
                                                    @endif
                                                    <p><a class="action-button"
                                                            href="{{ url($change->url) }}"><strong>View</strong></a></p>
                                                    <hr>
                                                @endforeach

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
