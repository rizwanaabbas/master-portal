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
    <table class="myFormat">
        <thead>
            <tr>
                <th class="theader-title" colspan={{ count($currencies) + 3 }} align="center">Daily All Banks Report -
                    Reports Management - {{ $ownCompany ? $ownCompany->short_name : '' }}
                </th>
            </tr>
            <tr></tr>
            <tr class="theader-content">
                <th>S.NO</th>
                <th>Date</th>
                <th style="width: 200px">Bank</th>
                @if ($currencies->count() > 0)
                    @foreach ($currencies as $currency)
                        <th>{{ \App\Helpers\Common::getCurrencyShort($currency->account_currency) }}</th>
                    @endforeach
                @endif
            </tr>
        </thead>
        <tbody>
            @if (count($data))
                @php $counter = 1; @endphp
                @foreach ($data as $key => $rows)
                    @foreach ($rows as $row)
                        <tr>
                            <td align="center" scope="row">{{ $counter++ }}</td>
                            <td nowrap="nowrap">{{ $row['date'] }}</td>
                            <td>{{ $row['title'] }}</td>
                            @foreach ($currencies as $currency)
                                @if (isset($row[$currency->account_currency]))
                                    <td>{{ number_format($row[$currency->account_currency], 2) }}</td>
                                @else
                                    <td>0.00</td>
                                @endif
                            @endforeach
                        </tr>
                    @endforeach
                @endforeach
        </tbody>
    </table>
@else
    <tr>
        <td colspan="3" align="center">No records found.</td>
    </tr>
    @endif
</body>

</html>
