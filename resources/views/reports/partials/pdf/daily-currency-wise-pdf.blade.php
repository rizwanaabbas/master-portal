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
        @foreach ($data as $date => $values)
            <div>
                <table>
                    <thead>
                        <tr>
                            <th class="theader-title" colspan="2">Daily Currency Deposit Report - Reports Management - {{ $ownCompany ? $ownCompany->short_name : '' }}
                                -
                                ({{ $date }})
                            </th>
                        </tr>
                        <br>
                        <br>
                        <tr class="theader-content">
                            <th>Currency</th>
                            <th>Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($values as $value)
                            <tr>
                                <td align="left">{{ $value['currency'] }}</td>
                                <td align="right">{{ number_format($value['balance'], 2) }}</td>
                            </tr>
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
