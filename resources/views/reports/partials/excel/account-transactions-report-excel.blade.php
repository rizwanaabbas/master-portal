<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
</head>

<body>
    <table>
        <thead>
            <tr>
                <th colspan="7" align="center">Account Transactions Report - Reports Management -
                    {{ $ownCompany ? $ownCompany->short_name : '' }}</th>
            </tr>
            <tr></tr>
            <tr>
                <th>S.NO</th>
                <th>Date</th>
                <th>Account Name</th>
                <th>Account Number</th>
                <th style="width: 200px">Remarks</th>
                <th>Type</th>
                <th style="width: 160px">Amount</th>
                <th style="width: 60px">Currency</th>
            </tr>
        </thead>
        <tbody>
            @if ($transactions->count() > 0)
                @foreach ($transactions as $transaction)
                    <tr>
                        <td align="center" scope="row">{{ $loop->iteration }}</td>
                        <td>{{ \Carbon\Carbon::parse($transaction->transaction_date)->format('d M, Y') }}
                        </td>
                        <td>{{ $transaction->account->title }}</td>
                        <td>({{ $transaction->account->account_number }})</td>
                        <td style="word-wrap: break-word">{{ $transaction->remarks }}</td>
                        <td>{{ $transaction->transaction_type }}</td>
                        <td align="right">
                            {{ \App\Helpers\Common::formatNumber($transaction->amount, $transaction->currency->is_crypto) }}
                        </td>
                        <td align="center">{{ $transaction->currency->short }}</td>
                    </tr>
                @endforeach
            @else
                <tr>
                    <td colspan="4" align="center">No records found.</td>
                </tr>
            @endif
        </tbody>
    </table>
</body>

</html>
