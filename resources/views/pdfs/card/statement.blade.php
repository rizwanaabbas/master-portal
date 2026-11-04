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
        $page = 1;
        $client = $cardLink->owner;
        $currency = ['840' => 'USD'];
    @endphp

    @include('pdfs.card.partials.header')
    <p class="note">Note: All items and balances shown will be considered correct unless the bank is notified in
        writing for any discrepancies within
        7 days from the date of this statement.
    </p>
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
                <th align="center" valign="top" style="border:1px solid #666;"><span>TRANSACTION DESCRIPTION</span>
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

            @foreach ($transactions as $trn)
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

                @if ($transactions->count() >= 8 && $loop->iteration % 8 == 0)
                    @php $page++; @endphp
        </table>
    </div>
    <div style="page-break-after: always"></div>
    @include('pdfs.card.partials.header')
    <p class="note">Note: All items and balances shown will be considered correct unless the bank is notified in
        writing for any discrepancies within
        7 days from the date of this statement
    </p>
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
                    <span
                        style="color:#676767; margin:0px; line-height: 20px; padding:0px; font-size:12px; font-family: Arial, Verdana,Helvetica, sans-serif;">TXN
                        DATE</span>
                </th>
                <th align="center" valign="top" style="border:1px solid #666;"><span
                        style="color:#676767; margin:0px; line-height: 20px; padding:0px; font-size:12px; font-family: Arial, Verdana,Helvetica, sans-serif;">TRANSACTION
                        DESCRIPTION</span></th>
                <th align="center" valign="top" style="border:1px solid #666;"><span
                        style="color:#676767; margin:0px; line-height: 20px; padding:0px; font-size:12px; font-family: Arial, Verdana,Helvetica, sans-serif;">CREDIT</span>
                </th>
                <th align="center" valign="top"
                    style="border-bottom: 1px solid #666; border-top: 1px solid #666; border-left: 1px solid #666;">
                    <span
                        style="color:#676767; margin:0px; line-height: 20px; padding:0px; font-size:12px; font-family: Arial, Verdana,Helvetica, sans-serif;">DEBIT</span>
                </th>
                {{-- <th align="center" valign="top"
                    style="border-bottom: 1px solid #666; border-top: 1px solid #666; border-left: 1px solid #666;">
                    <span
                        style="color:#676767; margin:0px; line-height: 20px; padding:0px; font-size:12px; font-family: Arial, Verdana,Helvetica, sans-serif;">BALANCE</span>
                </th> --}}
            </tr>
            @endif
            @endforeach
            {{-- <tr>
                <td style="border-right:1px solid #666"></td>
                <td style="border-right:1px solid #666"><span>ENDING BALANCE</span></td>
                <td style="border-right:1px solid #666"></td>
                <td style="border-right:1px solid #666"></td>
                <td align="right">
                    <span>{{ \App\Helpers\Common::formatNumber($ending_balance, false) }}</span>
                </td>
            </tr>
            <tr>
                <td style="border-right:1px solid #666"></td>
                <td style="border-right:1px solid #666"><span>TOTAL CREDIT</span></td>
                <td style="border-right:1px solid #666"></td>
                <td style="border-right:1px solid #666"></td>
                <td align="right">
                    <span>{{ \App\Helpers\Common::formatNumber($credit, false) }}</span>
                </td>
            </tr>
            <tr>
                <td style="border-right:1px solid #666; border-bottom:1px solid #666"></td>
                <td style="border-right:1px solid #666; border-bottom:1px solid #666"><span>TOTAL DEBIT</span></td>
                <td style="border-right:1px solid #666; border-bottom:1px solid #666"></td>
                <td style="border-right:1px solid #666; border-bottom:1px solid #666"></td>
                <td align="right" style="border-bottom:1px solid #666">
                    <span>{{ \App\Helpers\Common::formatNumber($debit, false) }}</span>
                </td>
            </tr> --}}
            {{-- @endif --}}
        </table>
    </div>
</body>

</html>
