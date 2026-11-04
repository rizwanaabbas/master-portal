@extends('layouts.cp')
@section('title', 'Client Dashboard')

@section('content')

    @include('common.error-message')
    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Client Dashboard</span>
        </h1>
    </div>

    <div class="row">
        @foreach ($accounts as $account)
            <div class="col-sm-6 col-xl-3">
                <div class="p-3 bg-success-500 rounded overflow-hidden position-relative text-white mb-g">
                    <div class="">
                        <h3 class="display-4 d-block l-h-n m-0 fw-500">
                            {{ \App\Helpers\Common::formatNumber($account['balance'], $account['is_crypto']) }}
                            <small class="m-0 l-h-n"><img src="{{ $account['logo'] ? $account['logo'] : '' }}"
                                    height="15px" />&nbsp;{{ $account['short'] }}</small>
                        </h3>
                    </div>
                    <i class="fal fa-money-check-alt position-absolute pos-right pos-bottom opacity-15 mb-n1 mr-n1"
                        style="font-size:6rem"></i>
                </div>
            </div>
        @endforeach

        <div class="col-sm-6 col-xl-3">
            <div class="p-3 bg-danger-400 rounded overflow-hidden position-relative text-white mb-g">
                <div class="">
                    <h3 class="display-4 d-block l-h-n m-0 fw-500">
                        {{ \Carbon\Carbon::parse(\Illuminate\Support\Facades\Auth::user()->created_at)->format('m/Y') }}
                        <small class="m-0 l-h-n">Member Since</small>
                    </h3>
                </div>
                <i class="fal fa-user-clock position-absolute pos-right pos-bottom opacity-15 mb-n1 mr-n4"
                    style="font-size: 6rem;"></i>
            </div>
        </div>

        @php
            $cardIdsArray = null;
            $balanceVisibilityArray = null;
        @endphp
        @role('client')
            @if (in_array('VistaMoney', $enabledModules) && count($clientCards) > 0)
                @if (count($clientCards) <= 2)
                    @foreach ($clientCards as $card)
                        @php
                            $cardIdsArray[] = $card->id;
                            $cardDetails = $card->cardDetails;
                            $balanceVisibilityArray[$card->id] = $card->hide_balance;
                        @endphp
                        @if ($cardDetails)
                            <div class="col-sm-6 col-md-6 col-lg-6 col-xl-3 mb-g">
                                <a href="{{ url('/client/card-management/card/view/' . $card->id) }}">
                                    <div class="p-3 rounded vista-card overflow-hidden position-relative text-white"
                                        style="height:98px;">
                                        <h3 class="d-block l-h-n m-0 fw-500">
                                            @if ($card->hide_balance)
                                                <span>&nbsp;</span>
                                            @else
                                                <h4 class="d-block m-0 l-h-n vista-card-title fw-500">
                                                    &nbsp;<span id="{{ $card->id }}_balance">
                                                        <?= '<?xml version="1.0" encoding="utf-8"?>' ?>
                                                        <svg xmlns="http://www.w3.org/2000/svg"
                                                            xmlns:xlink="http://www.w3.org/1999/xlink"
                                                            style="background: none; display: block; float: left; margin-bottom: -2px; shape-rendering: auto;"
                                                            width="22px" height="22px" viewBox="0 0 100 100"
                                                            preserveAspectRatio="xMidYMid">
                                                            <circle cx="50" cy="50" fill="none"
                                                                stroke="#ffc800" stroke-width="13" r="38"
                                                                stroke-dasharray="179.0707812546182 61.690260418206066">
                                                                <animateTransform attributeName="transform" type="rotate"
                                                                    repeatCount="indefinite" dur="0.625s"
                                                                    values="0 50 50;360 50 50" keyTimes="0;1">
                                                                </animateTransform>
                                                            </circle>
                                                        </svg>
                                                    </span>
                                                    {{ $cardDetails->currency }}&nbsp;
                                                </h4>
                                            @endif
                                            <h4>{{ $cardDetails->cardNumber }}</h4>
                                            <p class="m-0 l-h-n vista-card-title">
                                                @if ($cardDetails->cType != 'Instant Card')
                                                    {{ $cardDetails->cardHolderName }}&nbsp;
                                                @endif
                                            </p>
                                        </h3>
                                        <i class="position-absolute pos-right pos-bottom opacity-15 mb-n1 mr-n4"
                                            style="font-size: 6rem;"></i>
                                    </div>
                                </a>
                            </div>
                        @endif
                    @endforeach
                @else
                    <div class="col-sm-6 col-md-6 col-lg-6 col-xl-3">
                        <a href="{{ url('/client/card-management/card/view/' . $clientCards[0]->id) }}">
                            <div class="p-3 rounded vista-card overflow-hidden position-relative text-white mb-g">
                                <div class="">
                                    <h3 class="display-4 d-block l-h-n m-0 fw-500">
                                        <span id="total-cards">{{ count($clientCards) }}</span>
                                        <small class="m-0 l-h-n vista-card-title">&nbsp;Total Cards&nbsp;</small>
                                    </h3>
                                </div>
                                <i class="position-absolute pos-right pos-bottom opacity-15 mb-n1 mr-n4"
                                    style="font-size: 6rem;"></i>
                            </div>
                        </a>
                    </div>
                @endif
            @endif
        @endrole
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false"
                data-panel-color="false" data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
                <div class="panel-hdr">
                    <h2>Balance By Transaction Mode</h2>
                </div>

                <div class="panel-container show">
                    <div class="panel-content">
                        <div id="trn_by_nature" style="width: 100%; height: 100%"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false"
                data-panel-color="false" data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
                <div class="panel-hdr">
                    <h2>Credit Vs Debit</h2>
                </div>

                <div class="panel-container show">
                    <div class="panel-content">
                        <div id="credit_vs_debit" style="width: 100%; height: 100%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false"
                data-panel-color="false" data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
                <div class="panel-hdr">
                    <h2>Balance By Currency</h2>
                </div>
                @php
                    $cryptoAccounts = [];
                @endphp
                <table class="table table-bordered table-hover m-0 shadow table-responsive-xl"
                    @if ($clients->count() > 0) id="data-grid" @endif>
                    <thead class="thead-themed">
                        <tr>
                            <th width="30">S.NO</th>
                            {{-- @role('client') --}}
                            <th>Account Name</th>
                            {{-- @endrole --}}
                            <th>Account Number</th>
                            <th>Currency</th>
                            <th>Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if ($clients->count() > 0)
                            @php
                                $counter = 1;
                            @endphp

                            @foreach ($clients as $client)
                                @if ($client->clientAccounts)
                                    @foreach ($client->clientAccounts->where('is_active', true) as $account)
                                        @if (!$account->currency->is_crypto)
                                            <tr>
                                                <td align="center" scope="row">{{ $counter++ }}</td>
                                                {{-- @role('client') --}}
                                                <td>{{ $client->name }}</td>
                                                {{-- @endrole --}}
                                                <td>{{ $account->account_number }}</td>
                                                <td>
                                                    @if ($account->currency->logo)
                                                        <img src="{{ $account->currency->logo }}"
                                                            alt="{{ $account->currency->name }}" height="17px" />&nbsp;
                                                    @endif
                                                    {{ $account->currency->short }}
                                                </td>
                                                <td>{{ \App\Helpers\Common::formatNumber(\App\Helpers\Common::getAccountBalanceByCurrencyId($account->id, $account->account_currency), false) }}
                                                </td>
                                            </tr>
                                        @else
                                            @php $cryptoAccounts[] = $account; @endphp
                                        @endif
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
            </div>
        </div>
    </div>

    @if (\App\Helpers\Common::showClientCrypto() && in_array('Crypto', $enabledModules))
        <div class="row">
            <div class="col-md-12">
                <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false"
                    data-panel-color="false" data-panel-locked="false" data-panel-refresh="false"
                    data-panel-reset="false">
                    <div class="panel-hdr">
                        <h2>Balance By Crypto Currency</h2>
                    </div>
                    <table class="table table-bordered table-hover m-0 shadow table-responsive-xl"
                        @if ($clients->count() > 0) id="data-grid" @endif>
                        <thead class="thead-themed">
                            <tr>
                                <th width="30">S.NO</th>
                                {{-- @role('client') --}}
                                <th>Account Name</th>
                                {{-- @endrole --}}
                                <th>Account Number</th>
                                <th>Currency</th>
                                <th>Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($cryptoAccounts as $account)
                                <tr>
                                    <td align="center" scope="row">{{ $counter++ }}</td>
                                    {{-- @role('client') --}}
                                    <td>{{ $account->client->name }}</td>
                                    {{-- @endrole --}}
                                    <td>{{ $account->account_number }}</td>
                                    <td>
                                        @if ($account->currency->logo)
                                            <img src="{{ $account->currency->logo }}"
                                                alt="{{ $account->currency->name }}" height="17px" />&nbsp;
                                        @endif
                                        {{ $account->currency->short }}
                                    </td>
                                    <td>{{ \App\Helpers\Common::formatNumber(\App\Helpers\Common::getAccountBalanceByCurrencyId($account->id, $account->account_currency), true) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <div class="row">
        <div class="col-md-6">
            <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false"
                data-panel-color="false" data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
                <div class="panel-hdr">
                    <h2>Last 3 Transactions</h2>
                </div>
                <table class="table table-bordered table-hover m-0 table-responsive-xl"
                    @if ($transactions->count() > 0) id="data-grid" @endif>
                    <thead class="thead-themed">
                        <tr>
                            <th>Account Number</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if ($transactions->count() > 0)
                            @foreach ($transactions as $transaction)
                                @if (!in_array('Crypto', $enabledModules) && $transaction->currency->is_crypto)
                                    @php continue; @endphp
                                @endif
                                <tr>
                                    <td width="100%">{{ $transaction->account->account_number }}</td>
                                    <td nowrap="nowrap">{{ $transaction->transaction_type }}</td>
                                    <td nowrap="nowrap">
                                        {{ \App\Helpers\Common::formatNumber($transaction->amount, $transaction->currency->is_crypto) . ' ' . $transaction->currency->short }}
                                    </td>
                                    <td nowrap="nowrap">
                                        {{ \Carbon\Carbon::parse($transaction->transaction_date)->format('d M') }}</td>

                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="8" align="center">No records found.</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@stop
@section('scripts')

    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script type="text/javascript">
        $(document).ready(async function() {
            google.charts.load('current', {
                packages: ['corechart']
            });
            await google.charts.setOnLoadCallback(transactionByNature);
            await google.charts.setOnLoadCallback(creditVsDebit);
            // google.charts.setOnLoadCallback(amountByCurrency);

            const enabledModules = '@php echo json_encode($enabledModules) @endphp'
            const cardsCount = '@php echo count($clientCards) @endphp'

            if (enabledModules.includes('VistaMoney') && cardsCount > 0) {
                if (cardsCount <= 2) {
                    const cardIdsArray = JSON.parse('@php echo json_encode($cardIdsArray) @endphp')
                    const balanceVisibilityArray = JSON.parse('@php echo json_encode($balanceVisibilityArray) @endphp')

                    for (let cardId of cardIdsArray) {

                        const isBalanceHidden = balanceVisibilityArray[cardId]
                        if (!isBalanceHidden) {
                            await $.getJSON("{{ url('/client/card-management/card/get-balance') }}" + "/" +
                                cardId,
                                function(
                                    jsonData) {
                                    document.getElementById(cardId + "_balance").innerHTML = jsonData
                                })
                        }
                    }
                }
            }
        });

        function transactionByNature() {

            var url = "{{ url('/client/charts/transaction-by-nature') }}";

            $.getJSON(url, function(jsonData) {
                var data = new google.visualization.DataTable();
                data.addColumn('string', 'Mode');
                data.addColumn('number', 'Credit');
                data.addColumn('number', 'Debit');

                $.each(jsonData, function(k, v) {
                    data.addRow(v)
                })

                var options = {
                    //title: 'Transactions by Nature',
                    subtitle: 'Type, Credit, Debit',
                    //title: 'Proposal Status',
                    legend: {
                        position: "top"
                    },
                    colors: ['#ffcc00', 'orange', '#b36b00'],
                    chartArea: {
                        width: "70%",
                        height: "300px"
                    },
                    animation: {
                        "startup": true,
                        duration: 500,
                        easing: 'in'
                    },
                    pieHole: 0.4
                };

                var chart = new google.visualization.BarChart(document.getElementById('trn_by_nature'));
                chart.draw(data, options);
            });
        }

        function creditVsDebit() {

            var url = "{{ url('/client/charts/credit-vs-debit') }}";

            $.getJSON(url, function(jsonData) {
                var data = new google.visualization.DataTable();
                data.addColumn('string', 'Type');
                data.addColumn('number', 'Amount');

                $.each(jsonData, function(k, v) {
                    data.addRow(v)
                })

                var options = {
                    //title: 'Transactions by Nature',
                    subtitle: 'Credit, Debit',
                    //title: 'Proposal Status',
                    legend: {
                        position: "bottom"
                    },
                    colors: ['#ffcc00', 'orange', '#b36b00'],
                    chartArea: {
                        width: "70%",
                        height: "300px"
                    },
                    animation: {
                        "startup": true,
                        duration: 500,
                        easing: 'in'
                    },
                    pieHole: 0.4
                };

                var chart = new google.visualization.PieChart(document.getElementById('credit_vs_debit'));
                chart.draw(data, options);
            });
        }

        function amountByCurrency() {

            var url = "{{ url('/client/charts/amount-by-currency') }}";

            $.getJSON(url, function(jsonData) {
                var data = new google.visualization.DataTable();
                data.addColumn('string', 'Currency');
                data.addColumn('number', 'Amount');

                $.each(jsonData, function(k, v) {
                    data.addRow(v)
                })

                var options = {
                    //title: 'Transactions by Nature',
                    subtitle: 'Credit, Debit',
                    //title: 'Proposal Status',
                    legend: {
                        position: "bottom"
                    },
                    colors: ['#ffcc00', 'orange', '#b36b00'],
                    chartArea: {
                        width: "70%",
                        height: "300px"
                    },
                    animation: {
                        "startup": true,
                        duration: 500,
                        easing: 'in'
                    },
                    pieHole: 0.4
                };

                var chart = new google.visualization.ColumnChart(document.getElementById('by_currency'));
                chart.draw(data, options);
            });
        }
    </script>
@stop
