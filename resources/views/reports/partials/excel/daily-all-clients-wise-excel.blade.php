<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
</head>

<body>
    @if (count($filteredData))
        <table>
            <thead>
                <tr>
                    <th colspan="4" align="center">Daily Clients Wise Report - Reports Management -
                        {{ $ownCompany ? $ownCompany->short_name : '' }}
                    </th>
                </tr>
                <tr></tr>
                <tr>
                    <th>S.NO</th>
                    <th>Date</th>
                    <th style="width:300px">Client</th>
                    @if ($currency->count() > 0)
                        <th>{{ \App\Helpers\Common::getCurrencyShort($currency->account_currency) }}</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @php $counter = 1; @endphp
                @foreach ($filteredData as $row)
                    <tr>
                        <td align="center">{{ $counter++ }}</td>
                        <td nowrap="nowrap">{{ $row['date'] }}</td>
                        <td style="word-wrap: break-word">{{ $row['title'] }}</td>
                        <td align="right">{{ number_format($row[$currency->account_currency], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
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
    @endif
</body>

</html>
