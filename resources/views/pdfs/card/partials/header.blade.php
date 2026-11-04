<table width="100%" border="0" cellpadding="0" cellspacing="1" align="center" bgcolor="#ffffff" style="padding: 0px;">
    <tr>
        <td align="left" valign="top" bgcolor="#ffffff" style="padding: 0px 0px; margin: 0px; border: 0px;">
            <table width="100%" border="0" cellpadding="0" cellspacing="0" align="center" style="padding: 0px;">
                <tr>
                    <td align="left" valign="top" style="margin: 0px; padding: 20px 0px;">
                        <table width="100%" border="0" cellspacing="0" cellpadding="0">
                            <tr>
                                <td align="left" valign="top">
                                    <img src="{{ $ownCompany ? $ownCompany->logo : '' }}" alt="Menu" height="55"
                                        border="0" style="margin:0; padding:0; display:block; line-height:0;">
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<table style="width:100%; margin-bottom:30px;">
    <tr>
        <td style="width:60%; bg-color:red; vertical-align:top;">
            <span class="para-text">
                {{ $ownCompany ? $ownCompany->name : '' }} Limited
                ({{ $ownCompany ? $ownCompany->license_number : '' }}) <br>
                <div style="width: 80%">
                    {{ $ownCompany->address }}
                </div>
            </span><br><br>
            <div class="company-info">
                <strong>{{ $client->name }}</strong>
                @if ($client->type == 'individual')
                    <p>{{ $client->individual->mailing_address }}</p>
                @else
                    <p>{{ $client->company->business_reg_address }}</p>
                @endif
            </div>
        </td>
        <td style="width:40%;">
            <p
                style="color:rgb(51, 124, 203); margin:0px; line-height: 25px; padding:0px; font-size:22px; font-family: Arial, Verdana,Helvetica, sans-serif;">
                <strong>CARD STATEMENT</strong>
            </p>
            <table class="statement">
                <tr>
                    <td>PAGE</td>
                    <td>:</td>
                    <td align="right">{{ $page }}</td>
                </tr>
                <tr>
                    <td>STATEMENT PERIOD</td>
                    <td>:</td>
                    <td align="right">
                        {{ \Carbon\Carbon::parse($start_date)->format('d/m/Y') . ' - ' . \Carbon\Carbon::parse($to_date)->format('d/m/Y') }}
                    </td>
                </tr>
                <tr>
                    <td>CARD NUMBER</td>
                    <td>:</td>
                    <td align="right">{{ $transactions[0]->card_number }}</td>
                </tr>
                <tr>
                    <td>CURRENCY</td>
                    <td>:</td>
                    <td align="right">{{ $currency[$transactions[0]->processing_currency] }}</td>
                </tr>
                {{-- <tr>
                    <td>BEGINNING BALANCE</td>
                    <td>:</td>
                    <td align="right">
                        {{ \App\Helpers\Common::formatNumber($starting_balance, false) }}
                    </td>
                </tr>
                <tr>
                    <td>ENDING BALANCE</td>
                    <td>:</td>
                    <td align="right">
                        {{ \App\Helpers\Common::formatNumber($ending_balance, false) }}</td>
                </tr> --}}
                <tr>
                    <td>STATEMENT DATE</td>
                    <td>:</td>
                    <td align="right">{{ date('d M, Y') }}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>
