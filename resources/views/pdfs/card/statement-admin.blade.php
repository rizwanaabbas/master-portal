<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>{{ $ownCompany ? $ownCompany->short_name : '' }}</title>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
    <style>
        table.statement td {
            color: #676767;
            margin: 0px;
            line-height: 20px;
            padding: 0px;
            font-size: 10px;
            font-family: Arial, Verdana, Helvetica, sans-serif;
        }

        .para-text {
            color: #676767;
            margin: 0px;
            line-height: 20px;
            padding: 0px;
            font-size: 14px;
            font-family: Arial, Verdana, Helvetica, sans-serif;
        }

        .company-info {
            max-width: 300px;
            color: #676767;
            margin: 0px;
            line-height: 20px;
            padding: 0px;
            font-size: 14px;
            font-family: Arial, Verdana, Helvetica, sans-serif;
        }

        .note {
            font-style: italic;
            color: #676767;
            margin-bottom: 20px;
            line-height: 20px;
            padding: 0px;
            font-size: 12px;
            font-family: Arial, Verdana, Helvetica, sans-serif;
        }

        .water-mark {
            background: url("{{ $ownCompany ? $ownCompany->watermark : '' }}") center center no-repeat;
            background-size: 500px;
            height: 500px;
            opacity: 0.1;
        }

        table.trn span {
            color: #676767;
            margin: 0px;
            line-height: 20px;
            padding: 0px;
            font-size: 12px;
            font-family: Arial, Verdana, Helvetica, sans-serif;
        }
    </style>
</head>

<body leftmargin="0" topmargin="0" marginwidth="0" marginheight="0">
    @php
        $currency = ['840' => 'USD'];
        $chunks = $transactions->chunk(8);
    @endphp

    @foreach ($chunks as $index => $chunk)
        @php
            $page = $index + 1;
            $cardNumber = $transactions[0]->card_number;
            $masked = substr($cardNumber, 0, 4) . str_repeat('*', strlen($cardNumber) - 8) . substr($cardNumber, -4);
        @endphp

        <table width="100%" border="0" cellpadding="0" cellspacing="1" align="center" bgcolor="#ffffff"
            style="padding: 0px;">
            <tr>
                <td align="left" valign="top" bgcolor="#ffffff" style="padding: 0px 0px; margin: 0px; border: 0px;">
                    <table width="100%" border="0" cellpadding="0" cellspacing="0" align="center"
                        style="padding: 0px;">
                        <tr>
                            <td align="left" valign="top" style="margin: 0px; padding: 20px 0px;">
                                <table width="100%" border="0" cellspacing="0" cellpadding="0">
                                    <tr>
                                        <td align="left" valign="top">
                                            <img src="{{ $ownCompany ? $ownCompany->logo : '' }}" alt="Menu"
                                                height="55" border="0"
                                                style="margin:0; padding:0; display:block; line-height:0;">
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
                        ({{ $ownCompany ? $ownCompany->license_number : '' }})
                        <br>
                        @if (strlen($ownCompany->address) > 80)
                            {{ substr($ownCompany->address, 0, 37) .
                                '<br />' .
                                substr($ownCompany->address, 37, 38) .
                                '<br />' .
                                substr($ownCompany->address, 75) }}
                        @else
                            {{ substr($ownCompany->address, 0, 37) . '<br />' . substr($ownCompany->address, 37) }}
                        @endif
                    </span><br><br>
                    <div class="company-info">
                        <strong>{{ $card->cardHolderName }}</strong>
                        <p>{{ $card->userName }}</p>
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
                            <td align="right">{{ $masked }}</td>
                        </tr>
                        <tr>
                            <td>CURRENCY</td>
                            <td>:</td>
                            <td align="right"> {{ $currency[$transactions[0]->processing_currency] ?? 'USD' }}</td>
                        </tr>
                        <tr>
                            <td>BALANCE</td>
                            <td>:</td>
                            <td align="right">
                                {{ \App\Helpers\Common::formatNumber($balance, false) }}
                            </td>
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
        <p class="note">Note: All items and balances shown will be considered correct unless the bank is notified in
            writing for any discrepancies within
            7 days from the date of this statement.
        </p>

        {{-- Transactions Data --}}
        <div class="water-mark">
            <table class="trn" width="100%" border="0" cellspacing="0" cellpadding="8">
                <tr>
                    <th colspan="4" style="border-top: 1px solid #666; text-align:center;"><span
                            style="color:#676767; margin:0px; line-height: 20px; padding:0px; font-size:12px; font-family: Arial, Verdana,Helvetica, sans-serif; font-weight:bold;">CARD
                            TRANSACTIONS</span></th>
                </tr>
                <tr>
                    <th align="center" valign="top"
                        style="border-bottom: 1px solid #666; border-top: 1px solid #666; border-right: 1px solid #666;">
                        <span>TXN DATE</span>
                    </th>
                    <th align="center" valign="top" style="border:1px solid #666;"><span>TRANSACTION
                            DESCRIPTION</span>
                    </th>
                    <th align="center" valign="top" style="border:1px solid #666;"><span>CREDIT</span></th>
                    <th align="center" valign="top"
                        style="border-bottom: 1px solid #666; border-top: 1px solid #666; border-left: 1px solid #666;">
                        <span>DEBIT</span>
                    </th>
                    {{-- <th align="center" valign="top"
                    style="border-bottom: 1px solid #666; border-top: 1px solid #666; border-left: 1px solid #666;">
                    <span>BALANCE</span>
                </th> --}}
                </tr>
                {{-- @if ($starting_balance >= 0) --}}
                {{-- <tr>
                <td style="border-right:1px solid #666"></td>
                <td style="border-right:1px solid #666"><span>OPENING BALANCE</span></td>
                <td style="border-right:1px solid #666"></td>
                <td style="border-right:1px solid #666"></td>
                <td align="right">
                    <span>{{ \App\Helpers\Common::formatNumber($balance, false) }}</span>
                </td>
            </tr> --}}

                @foreach ($chunk as $trn)
                    <tr>
                        <td width="15%" align="left" valign="top" style="border-right: 1px solid #666;">
                            <span>{{ \Carbon\Carbon::parse($trn->time)->format('d M, Y') }}</span>
                        </td>
                        <td width="40%" align="left" valign="top" style="border-right:1px solid #666;">
                            <span>{{ \Illuminate\Support\Str::limit($trn->type, 92, '...') }}</span>
                        </td>
                        @if ($trn->transaction_nature == 'Credit')
                            <td width="15%" align="right" valign="top" style="border-right: 1px solid #666">
                                <span>{{ \App\Helpers\Common::formatNumber($trn->terminal_amount, false) }}</span>
                            </td>
                            <td width="15%" align="right" valign="top" style=""><span>0.00</span></td>
                        @else
                            <td width="15%" align="right" valign="top" style="border-right: 1px solid #666">
                                <span>0.00</span>
                            </td>
                            <td width="15%" align="right" valign="top" style="">
                                <span>{{ \App\Helpers\Common::formatNumber($trn->terminal_amount, false) }}</span>
                            </td>
                        @endif

                        {{-- @php
                        if ($trn->transaction_nature == 'Credit') {
                            $balance = $balance + $trn->terminal_amount;
                        } else {
                            $balance = $balance - $trn->terminal_amount;
                        }
                    @endphp

                    <td width="15%" align="right" valign="top" style=" border-left: 1px solid #666;">
                        <span>{{ \App\Helpers\Common::formatNumber($balance, false) }}</span>
                    </td> --}}

                    </tr>
                @endforeach
            </table>
        </div>
        @if (!$loop->last)
            <div style="page-break-after: always;"></div>
        @endif
    @endforeach
</body>

</html>
