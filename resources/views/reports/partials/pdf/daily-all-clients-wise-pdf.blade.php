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
    @if (count($data))
        @php $currencyCounter = 0; @endphp
        @foreach ($currencies as $currency)
            @php
                $filteredData = [];
                foreach ($data as $rows) {
                    foreach ($rows as $row) {
                        if (isset($row[$currency->account_currency])) {
                            $filteredData[] = $row;
                        }
                    }
                }
            @endphp
            @if (count($filteredData) > 0)
                @if ($currencyCounter > 0)
                    <div style="page-break-before: always;"></div>
                @endif
                <div>
                    <table>
                        <thead>
                            <tr>
                                <th class="theader-title" colspan="4"> Daily Clients Wise Report - Reports Management -
                                    {{ $ownCompany ? $ownCompany->short_name : '' }}
                                </th>
                            </tr>
                            <br>
                            <br>
                            <tr class="theader-content">
                                <th>S.NO</th>
                                <th>Date</th>
                                <th>Client</th>
                                <th>{{ \App\Helpers\Common::getCurrencyShort($currency->account_currency) }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $counter = 1; @endphp
                            @foreach ($filteredData as $row)
                                <tr>
                                    <td align="center">{{ $counter++ }}</td>
                                    <td nowrap="nowrap">{{ $row['date'] }}</td>
                                    <td>{{ $row['title'] }}</td>
                                    <td style="white-space: nowrap;" align="right">
                                        {{ number_format($row[$currency->account_currency], 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @php $currencyCounter++; @endphp
            @endif
        @endforeach
    @else
        <div>
            <table>
                <thead>
                    <tr>
                        <th colspan="4">
                            Daily Clients Wise Report - Reports Management - FAIB
                        </th>
                    </tr>
                    <tr>
                        <th>S.NO</th>
                        <th>Date</th>
                        <th>Client</th>
                        <th>Currency</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="4" align="center">No records found.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    @endif
</body>

</html>
