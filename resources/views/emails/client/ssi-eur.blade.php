@extends('layouts.email')
@section('title', 'Welcome Client Email')

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
                                                <h2><u>STANDARD SETTLEMENT INSTRUCTION</u><br>(Currency: EUR)</h2>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="center">
                                                <p><strong>56A: Intermediary Institution - BIC</strong></p>
                                                <p>SWIFT Code: <strong>COBADEFFXXX</strong></p>
                                                <p>Bank Name: <strong>Commerzbank AG</strong></p>
                                                <p>Bank Address: <strong>Frankfurt AM Main</strong></p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="center">
                                                <p><strong>57A: Account with the Institution</strong></p>
                                                <p>SWIFT Code: <strong>MBBEMYKLXXX</strong></p>
                                                <p>Bank Name: <strong>Maybank, Labuan Branch</strong></p>
                                                <p>Bank Address: <strong>FPL, JLN Merdeka, 87000, Labuan FT,
                                                        Malaysia</strong></p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="center">
                                                <p><strong>59: Beneficiary Customer Name & Address</strong></p>
                                                <p>Account Number: <strong>765125000724</strong></p>
                                                <p>Account Name: <strong>{{ $ownCompany ? $ownCompany->name : '' }}
                                                        Ltd.</strong></p>
                                                <p>Address: <strong>{{ $ownCompany ? $ownCompany->address : '' }}</strong>
                                                </p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="center">
                                                <p><strong>70: Remittance Information:</strong></p>
                                                <p>Investment fund placement with
                                                    {{ $ownCompany ? $ownCompany->short_name : '' }}:</p>
                                                <p>Customer Name: <strong>{{ $client->name }}</strong></p>
                                                <p>Account Number: <strong>{{ $account->account_number }}</strong></p>
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
