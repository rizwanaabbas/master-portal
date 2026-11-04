<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
</head>

<body>
    <table>
        <thead>
            <tr>
                <th colspan={{ $currencies->count() + 2 }} align="center"> Client Wise Report - Reports Management -
                    {{ $ownCompany ? $ownCompany->short_name : '' }}
                </th>
            </tr>
            <tr></tr>
            <tr>
                <th>S.NO</th>
                <th style="width: 200px;">Client</th>
                @if ($currencies->count() > 0)
                    @foreach ($currencies as $currency)
                        <th>{{ $currency->short }}</th>
                    @endforeach
                @endif
            </tr>
        </thead>
        <tbody>
            @if ($users && $users->count() > 0)
                @php
                    $counter = 1;
                    $balance = 0;
                    $total = [];
                    $hideTotal = $clients->count() === 1;
                @endphp
                @foreach ($clients as $client)
                    <tr>
                        <td align="center" scope="row">{{ $counter++ }}</td>
                        <td style="word-wrap: break-word">{{ $client->name }}</td>
                        @if ($currencies->count() > 0)
                            @foreach ($currencies as $currency)
                                @php
                                    $balance = \App\Helpers\Common::getClientBalanceByCurrencyId($client->id, $currency->id);
                                    $total[$currency->id] = isset($total[$currency->id]) ? $total[$currency->id] + $balance : $balance;
                                @endphp
                                <th align="right">
                                    {{ \App\Helpers\Common::formatNumber($balance, $currency->is_crypto) }}
                                </th>
                            @endforeach
                        @endif
                    </tr>
                @endforeach
                @if (!$hideTotal)
                    <tr>
                        <th></th>
                        <td><strong>Total</strong></td>
                        @if ($currencies->count() > 0)
                            @foreach ($currencies as $currency)
                                <td align="right">
                                    <b>{{ \App\Helpers\Common::formatNumber($total[$currency->id], $currency->is_crypto) }}</b>
                                </td>
                            @endforeach
                        @endif
                    </tr>
                @endif
            @else
                <tr>
                    <td colspan="8" align="center">No records found.</td>
                </tr>
            @endif
        </tbody>
    </table>
</body>

</html>
