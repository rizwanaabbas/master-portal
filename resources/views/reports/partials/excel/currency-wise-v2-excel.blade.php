<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
</head>

<body>
    <table>
        <thead>
            <tr>
                <th colspan="4" align="center"> Currency Deposit Report - Reports Management - {{ $ownCompany ? $ownCompany->short_name : '' }} </th>
            </tr>
            <tr></tr>
            <tr>
                <th>S.NO</th>
                <th>Currency</th>
                <th align="center">No. of Accounts</th>
                <th align="right">Total Outstanding Balance</th>
            </tr>
        </thead>
        <tbody>
            @php $total=0 @endphp
            @if (count($data))
                @foreach ($data as $row)
                    <tr>
                        <td align="center" scope="row">{{ $loop->iteration }}</td>
                        <td>{{ $row['currency'] }}</td>
                        <td align="center">{{ $row['accounts'] }}</td>
                        <td align="right">{{ \App\Helpers\Common::formatNumber($row['balance'], $row['is_crypto']) }}
                        </td>

                    </tr>
                    @php $total += $row['accounts'] @endphp
                @endforeach
                <tr>
                    <th></th>
                    <td><strong>Total No. of Accounts</strong></td>
                    <td align="center">
                        <b>{{ $total }}</b>
                    </td>
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
