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
    @if ($groupedTransactions->count() > 0)
        @foreach ($groupedTransactions as $nature => $transactionsByNature)
            <div>
                <table>
                    <thead>
                        <tr>
                            <th class="theader-title" colspan="7"> Account Transactions Report -
                                Reports Management - {{ $ownCompany ? $ownCompany->short_name : '' }} -
                                ({{ $nature }})
                            </th>
                        </tr>
                        <br>
                        <br>
                        <tr class="theader-content">
                            <th>S.NO</th>
                            <th>Date</th>
                            <th>Account Name</th>
                            <th>Account Number</th>
                            <th>Remarks</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Currency</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($transactionsByNature as $transaction)
                            <tr>
                                <td align="center" scope="row">{{ $loop->iteration }}</td>
                                <td>{{ \Carbon\Carbon::parse($transaction->transaction_date)->format('d M, Y') }}
                                </td>
                                <td>{{ $transaction->account->title }}</td>
                                <td>{{ $transaction->account->account_number }}</td>
                                <td width="30%">{{ $transaction->remarks }}</td>
                                <td>{{ $transaction->transaction_type }}</td>
                                <td>
                                    {{ \App\Helpers\Common::formatNumber($transaction->amount, $transaction->currency->is_crypto) }}
                                </td>
                                <td>
                                    {{ $transaction->currency->short }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                @if (!$loop->last)
                    <div style="page-break-after: always;"></div>
                @else
                    <div style="page-break-after: avoid;"></div>
                @endif
        @endforeach
        </div>
    @else
        <tr>
            <td colspan="8" align="center">No records found.</td>
        </tr>
    @endif
    </tbody>
    </table>
</body>

</html>
