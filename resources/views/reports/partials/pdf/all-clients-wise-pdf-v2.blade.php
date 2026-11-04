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

        .theader-title {
            font-size: 22px;
        }

        .theader-content {
            background-color: rgb(45, 65, 84);
            color: white;
        }

        table {
            border-collapse: collapse;
            margin: 0 auto;
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
                            <th class="theader-title" colspan="3">Client Wise Report - Reports Management - {{ $ownCompany ? $ownCompany->short_name : '' }}</th>
                        </tr>
                        <br>
                        <br>
                        <tr class="theader-content">
                            <th>S.NO</th>
                            <th>Client</th>
                            @if ($currency->count() > 0)
                                <th>{{ $currency->short }}</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $counter = 1;
                            $total = [];
                        @endphp
                        @foreach ($clients as $client)
                            <tr>
                                <td align="center">{{ $counter++ }}</td>
                                <td>{{ $client->name }}</td>
                                @if ($currency->count() > 0)
                                    @php $balance = 0; @endphp
                                    @php
                                        $balance = \App\Helpers\Common::getClientBalanceByCurrencyId($client->id, $currency->id);
                                        $total[$currency->id] = isset($total[$currency->id]) ? $total[$currency->id] + $balance : $balance;
                                    @endphp
                                    <th align="right">
                                        {{ \App\Helpers\Common::formatNumber($balance, $currency->is_crypto) }}
                                    </th>
                                @endif
                            </tr>
                        @endforeach
                        <tr>
                            <th></th>
                            <td><strong>Total</strong></td>
                            <td>
                                <b>{{ \App\Helpers\Common::formatNumber($total[$currency->id], $currency->is_crypto) }}</b>
                            </td>
                        </tr>
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
