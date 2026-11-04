<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
</head>

<body>
    <table>
        <thead>
            <tr>
                <th colspan="3" align="center">Daily Currency Deposit Report - Reports Management - {{ $ownCompany ? $ownCompany->short_name : '' }} - ({{$date}})</th>
            </tr>
            <tr></tr>
            <tr>
                <th>S.NO</th>
                <th style="width: 200px;">Currency</th>
                <th align="right" style="width: 200px;">Balance</th>
            </tr>
        </thead>
        <tbody>
            @if (count($values))
                @foreach ($values as $value)
                    <tr>
                        <td align="center">{{ $loop->iteration }}</td>
                        <td>{{ $value['currency'] }}</td>
                        <td align="right">{{ number_format($value['balance'], 2) }}</td>
                    </tr>
                @endforeach
            @else
                <tr>
                    <td colspan="3" align="center">No records found.</td>
                </tr>
            @endif
        </tbody>
    </table>
</body>

</html>
