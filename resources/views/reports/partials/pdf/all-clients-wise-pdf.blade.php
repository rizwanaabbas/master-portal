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

        .myFormat {
            font-size: 10px;
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
            font-size: 12px;
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
    <table class="myFormat">
        <thead>
            <tr class="theader-title">
                <th colspan={{ $currencies->count() + 2 }}>Client Wise Report - Reports Management
                    - {{ $ownCompany ? $ownCompany->short_name : '' }}</th>
            </tr>
            <br>
            <br>
            <tr class="theader-content">
                <th>S.NO</th>
                <th>Client</th>
                @if ($currencies->count() > 0)
                    @foreach ($currencies as $currency)
                        <th>{{ $currency->short }}</th>
                    @endforeach
                @endif
            </tr>
        </thead>
        <tbody>
            @if ($clients->count() > 0)
                @php
                    $counter = 1;
                    $total = [];
                @endphp
                @foreach ($clients as $client)
                    <tr class="myFormat">
                        <td align="center" scope="row">{{ $counter++ }}</td>
                        <td>{{ $client->name }}</td>
                        @if ($currencies->count() > 0)
                            @php $balance = 0; @endphp
                            @foreach ($currencies as $currency)
                                @php
                                    $balance = \App\Helpers\Common::getClientBalanceByCurrencyId($client->id, $currency->id);
                                    $total[$currency->id] = isset($total[$currency->id]) ? $total[$currency->id] + $balance : $balance;
                                @endphp
                                <th>{{ \App\Helpers\Common::formatNumber($balance, $currency->is_crypto) }}
                                </th>
                            @endforeach
                        @endif
                    </tr>
                @endforeach
                <tr>
                    <th></th>
                    <td style="font-size: 12px" align="center"><strong>Total</strong></td>
                    @if ($currencies->count() > 0)
                        @foreach ($currencies as $currency)
                            <td class="myFormat">
                                <b>{{ \App\Helpers\Common::formatNumber($total[$currency->id], $currency->is_crypto) }}</b>
                            </td>
                        @endforeach
                    @endif
                </tr>
            @else
                <tr>
                    <td colspan="8" align="center">No records found.</td>
                </tr>
            @endif
        </tbody>
    </table>
</body>

</html>
