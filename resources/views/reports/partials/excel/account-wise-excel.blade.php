<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
</head>

<body>
    <table>
        <thead>
            <tr>
                <th colspan="5" align="center"> Client Deposit List Report - Reports Management - {{ $ownCompany ? $ownCompany->short_name : '' }} </th>
            </tr>
            <tr></tr>
            <tr>
                <th>S.NO</th>
                <th style="width: 200px">Account Name</th>
                <th>Opening Date</th>
                <th>Balance</th>
                <th>Type</th>
            </tr>
        </thead>
        <tbody>
            @if ($clients->count() > 0)
                @php
                    $counter = 1;
                    $balance = 0;
                @endphp
                @foreach ($clients as $client)
                    @if ($client->clientAccounts)
                        @foreach ($client->clientAccounts->where('is_active', true)->where('account_currency', $currency->id) as $account)
                            @if (!in_array('Crypto', $enabledModules) && $account->currency->is_crypto)
                                @php continue; @endphp
                            @endif
                            @php $balance = \App\Helpers\Common::getAccountBalanceByCurrencyId($account->id, $account->account_currency); @endphp
                            <tr>
                                <td align="center" scope="row">{{ $counter++ }}</td>
                                <td style="word-wrap: break-word">
                                    {{ $client->name . ' - ' . \App\Helpers\Common::getCurrencyShort($account->account_currency) . ' - ' . $account->account_number }}
                                </td>
                                <td>{{ \Carbon\Carbon::parse($account->created_at)->format('d M, Y') }}</td>
                                <td align="right">
                                    {{ \App\Helpers\Common::formatNumber($balance, $account->currency ? $account->currency->is_crypto : false) }}
                                </td>
                                <td>{{ ucwords($client->type) }}</td>
                            </tr>
                        @endforeach
                    @endif
                @endforeach
            @else
                <tr>
                    <td colspan="8" align="center">No records found.</td>
                </tr>
            @endif
        </tbody>
    </table>
</body>

</html>
