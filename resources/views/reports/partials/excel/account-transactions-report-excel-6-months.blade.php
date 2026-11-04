<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
</head>

<body>
    <table>
        <thead>
            <tr>
                <th colspan="7" align="center"> {{ $currency }} Transactions Report - 1st June 2024 to 31st Dec
                    2024 - {{ $ownCompany ? $ownCompany->short_name : '' }}</th>
            </tr>
            <tr></tr>
            <tr>
                <th>S.NO</th>
                <th>Date</th>
                <th>Account Name</th>
                {{-- <th>Account Number</th>
                <th style="width: 200px">Remarks</th> --}}
                <th>Type</th>
                <th style="width: 160px">Amount</th>
                @if ($transactionType == 'Debit')
                    <th>Beneficiary Name</th>
                    <th>Beneficiary Address</th>
                    <th>Bank Name</th>
                    <th>Bank Address</th>
                @endif
                {{-- <th style="width: 60px">Currency</th> --}}
            </tr>
        </thead>
        <tbody>
            @if ($transactions->count() > 0)
                @php
                    $totalAmount = 0;
                @endphp
                @foreach ($transactions as $transaction)
                    <tr>
                        <td align="center" scope="row">{{ $loop->iteration }}</td>
                        <td>{{ \Carbon\Carbon::parse($transaction->transaction_date)->format('d M, Y') }}
                        </td>
                        <td>{{ $transaction->account->title }}</td>
                        {{-- <td>({{ $transaction->account->account_number }})</td>
                    <td style="word-wrap: break-word">{{ $transaction->remarks }}</td> --}}
                        <td>{{ $transaction->transaction_nature }}</td>
                        <td align="right">
                            {{ \App\Helpers\Common::formatNumber($transaction->amount, $transaction->currency->is_crypto) }}
                        </td>
                        @if ($transaction->beneficiary_id > 0 && $transaction->beneficiary && $transactionType == 'Debit')
                            <td>{{ $transaction->beneficiary->name ? $transaction->beneficiary->name : '' }}
                            </td>
                            <td>{{ $transaction->beneficiary->address ? preg_replace('/[^a-zA-Z]/', ' ', $transaction->beneficiary->address) : '' }}
                            </td>
                            <td>{{ $transaction->beneficiary->bank_name ? preg_replace('/[^a-zA-Z]/', ' ', $transaction->beneficiary->bank_name) : '' }}
                            </td>
                            <td>{{ $transaction->beneficiary->bank_address ? preg_replace('/[^a-zA-Z]/', ' ', $transaction->beneficiary->bank_address) : '' }}
                            </td>
                        @endif
                        {{-- <td align="center">{{ $transaction->currency->short }}</td> --}}
                    </tr>
                    @php
                        $totalAmount = $totalAmount + $transaction->amount;
                    @endphp
                @endforeach

                <tr>
                    <td colspan="4" align="right"><b>Total</b></td>
                    <td align="right"><b>{{ \App\Helpers\Common::formatNumber($totalAmount, false) }}</b></td>
                </tr>
            @else
                <tr>
                    <td colspan="4" align="center">No records found.</td>
                </tr>
            @endif
        </tbody>
    </table>
</body>

</html>
