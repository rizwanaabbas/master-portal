<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        table tbody tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        table {
            border-collapse: collapse;
            margin: 0 auto;
        }

        .theader-title {
            font-size: 22px;
        }

        .theader-content {
            background-color: rgb(45, 65, 84);
            color: white;
        }

        th {
            padding: 8px;
            text-align: center;
        }

        td {
            padding: 8px;
        }
    </style>
</head>

<body>

    @if ($clients->count() > 0)
        @foreach ($currencies as $currency)
            <div>
                <table>
                    <thead>
                        <tr>
                            <th class="theader-title" colspan="5" align="center"> Client Deposit List Report -
                                Reports Management -{{ $ownCompany ? $ownCompany->short_name : '' }} - ({{ $currency->short }})</th>
                            </th>
                        </tr>
                        <br>
                        <br>
                        <tr class="theader-content">
                            <th>S.NO</th>
                            <th>Account Name</th>
                            <th style="width:100px">Opening Date</th>
                            <th>Balance</th>
                            <th>Type</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $counter = 1;
                            $balance = 0;
                        @endphp
                        @foreach ($clients as $client)
                            @if ($client->clientAccounts)
                                @foreach ($client->clientAccounts->where('is_active', true)->where('account_currency', $currency->id) as $account)
                                    @if (!in_array('Crypto', $enabledModules) && $account->currency->is_crypto)
                                        @php continue; @endphp
                                    @endif
                                    @php $balance = \App\Helpers\Common::getAccountBalanceByCurrencyId($account->id, $account->account_currency); @endphp
                                    <tr>
                                        <td align="center" scope="row">{{ $counter++ }}</td>
                                        <td style="word-wrap: break-word">
                                            {{ $client->name . ' - ' . \App\Helpers\Common::getCurrencyShort($account->account_currency) . ' - ' . $account->account_number }}
                                        </td>
                                        <td>{{ \Carbon\Carbon::parse($account->created_at)->format('d M, Y') }}</td>
                                        <td align="right">
                                            {{ \App\Helpers\Common::formatNumber($balance, $account->currency ? $account->currency->is_crypto : false) }}
                                        </td>
                                        <td>{{ ucwords($client->type) }}</td>
                                    </tr>
                                @endforeach
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if (!$loop->last)
                <div style="page-break-after: always;"></div>
            @else
                <div style="page-break-after: avoid;"></div>
            @endif
        @endforeach
    @else
        <tr>
            <td colspan="8" align="center">No records found.</td>
        </tr>
    @endif
</body>

</html>
