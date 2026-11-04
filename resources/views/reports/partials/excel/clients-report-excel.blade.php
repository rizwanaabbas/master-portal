<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
</head>

<body>
    <table>
        <thead>
            <tr>
                <th colspan="3" align="center">Client Wise Report - Reports Management - {{ $ownCompany ? $ownCompany->short_name : '' }}</th>
            </tr>
            <tr>
                <th>S.NO</th>
                <th style="width: 360px">Client</th>
                 <th>Country/Place of Registration</th>
                @if ($currency->count() > 0)
                    <th style="width: 150px;">{{ $currency->short }}</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @if ($clients->count() > 0)
                @php
                    $counter = 1;
                    $total = [];
                @endphp
                @foreach ($clients as $client)
                    <tr>
                        <td align="center" scope="row">{{ $counter++ }}</td>
                        <td style="word-wrap: break-word">{{ $client->name }}</td>
                         <td>
                            @if($client->type=='individual')
                            @if(!empty($client->individual->residence))
                                {{$client->individual->residence->name}}
                            @endif
                            @else   
                                {{$client->company->company_place}}
                        @endif
                        </td>
                        @if ($currency->count() > 0)
                            @php $balance = 0; @endphp
                            @php
                                $balance = \App\Helpers\Common::getClientBalanceByCurrencyId($client->id, $currency->id);
                                $total[$currency->id] = isset($total[$currency->id]) ? $total[$currency->id] + $balance : $balance;
                            @endphp
                            <th align="right">{{ \App\Helpers\Common::formatNumber($balance, $currency->is_crypto) }}
                            </th>
                        @endif
                    </tr>
                @endforeach
                <tr>
                    <th></th>
                    <td><strong>Total</strong></td>
                    <td align="right">
                        <b>{{ \App\Helpers\Common::formatNumber($total[$currency->id], $currency->is_crypto) }}</b>
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
