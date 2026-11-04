@extends('layouts.cp')
@section('title', 'Finance Dashboard')

@section('content')

    @include('common.error-message')
    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Finance Dashboard</span>
        </h1>
    </div>

    @php
        $cardColors = json_decode($ownCompany->card_colors, true) ?? [];
    @endphp

    <div class="row">
        <div class="col-sm-3 col-xl-3">
            <div class="p-3 {{ $cardColors[0] ?? 'bg-primary' }} rounded overflow-hidden position-relative text-white mb-g">
                <div class="">
                    <h3 class="display-4 d-block l-h-n m-0 fw-500">
                        {{ $clientsCount }}
                        <small class="m-0 l-h-n">Total Clients</small>
                    </h3>
                </div>
                <i class="fal fa-user-clock position-absolute pos-right pos-bottom opacity-15 mb-n1 mr-n4"
                    style="font-size: 6rem;"></i>
            </div>
        </div>
        <div class="col-sm-3 col-xl-3">
            <div class="p-3 {{ $cardColors[1] ?? 'bg-primary' }} rounded overflow-hidden position-relative text-white mb-g">
                <div class="">
                    <h3 class="display-4 d-block l-h-n m-0 fw-500">
                        {{ $accountsCount }}
                        <small class="m-0 l-h-n">Total Accounts</small>
                    </h3>
                </div>
                <i class="fal fa-money-check-alt position-absolute pos-right pos-bottom opacity-15 mb-n1 mr-n4"
                    style="font-size: 6rem;"></i>
            </div>
        </div>
        <div class="col-sm-3 col-xl-3">
            <div class="p-3 {{ $cardColors[2] ?? 'bg-primary' }} rounded overflow-hidden position-relative text-white mb-g">
                <div class="">
                    <h3 class="display-4 d-block l-h-n m-0 fw-500">
                        {{ $transactionsCount }}
                        <small class="m-0 l-h-n">Total Transactions</small>
                    </h3>
                </div>
                <i class="fal fa-money-check-alt position-absolute pos-right pos-bottom opacity-15 mb-n1 mr-n4"
                    style="font-size: 6rem;"></i>
            </div>
        </div>
        <div class="col-sm-3 col-xl-3">
            <div class="p-3 {{ $cardColors[3] ?? 'bg-primary' }} rounded overflow-hidden position-relative text-white mb-g">
                <div class="">
                    <h3 class="display-4 d-block l-h-n m-0 fw-500">
                        {{ $currenciesCount->count }}
                        <small class="m-0 l-h-n">Total Currencies</small>
                    </h3>
                </div>
                <i class="fal fa-money-check-alt position-absolute pos-right pos-bottom opacity-15 mb-n1 mr-n4"
                    style="font-size: 6rem;"></i>
            </div>
        </div>

    </div>

    @php $mpinArray = null @endphp
    @if (in_array('Card', $enabledModules))
        <div class="row">
            <div class="col-sm-3 col-xl-3">
                <div class="p-3 rounded vista-card overflow-hidden position-relative text-white mb-g">
                    <div class="">
                        <h3 class="display-4 d-block l-h-n m-0 fw-500">
                            <span id="total-cards">
                                <?= '<?xml version="1.0" encoding="utf-8"?>' ?>
                                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
                                    style="background: none; shape-rendering: auto;" width="28px" height="28px"
                                    viewBox="0 0 100 100" preserveAspectRatio="xMidYMid">
                                    <circle cx="50" cy="50" fill="none" stroke="#ffc800" stroke-width="13"
                                        r="38" stroke-dasharray="179.0707812546182 61.690260418206066">
                                        <animateTransform attributeName="transform" type="rotate" repeatCount="indefinite"
                                            dur="0.625s" values="0 50 50;360 50 50" keyTimes="0;1"></animateTransform>
                                    </circle>
                                </svg>
                            </span>
                            <small class="m-0 l-h-n vista-card-title">&nbsp;Total Cards&nbsp;</small>
                        </h3>
                    </div>
                    <i class="position-absolute pos-right pos-bottom opacity-15 mb-n1 mr-n4" style="font-size: 6rem;"></i>
                </div>
            </div>
            @role('client')
                @if (count($clientCards) > 0)
                    @foreach ($clientCards as $cardDetails)
                        @php $mpinArray[] = $cardDetails->mpin @endphp
                        <div class="col-sm-3 col-md-3 col-lg-3 col-xl-3">
                            <a href="{{ url('/client/card-management/card/view/' . $cardDetails->userName) }}">
                                <div class="p-3 rounded vista-card overflow-hidden position-relative text-white mb-g">
                                    <h3 class="d-block l-h-n m-0 fw-500">
                                        <h4 class="d-block m-0 l-h-n vista-card-title fw-500">
                                            &nbsp;<span id="{{ $cardDetails->mpin }}">
                                                <?= '<?xml version="1.0" encoding="utf-8"?>' ?>
                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                    xmlns:xlink="http://www.w3.org/1999/xlink"
                                                    style="background: none; display: block; float: left; margin-bottom: -2px; shape-rendering: auto;"
                                                    width="22px" height="22px" viewBox="0 0 100 100"
                                                    preserveAspectRatio="xMidYMid">
                                                    <circle cx="50" cy="50" fill="none" stroke="#ffc800"
                                                        stroke-width="13" r="38"
                                                        stroke-dasharray="179.0707812546182 61.690260418206066">
                                                        <animateTransform attributeName="transform" type="rotate"
                                                            repeatCount="indefinite" dur="0.625s" values="0 50 50;360 50 50"
                                                            keyTimes="0;1"></animateTransform>
                                                    </circle>
                                                </svg>
                                            </span>
                                            {{ $cardDetails->currency }}&nbsp;
                                        </h4>
                                        <h4>{{ $cardDetails->cardNumber }}</h4>
                                        <p class="m-0 l-h-n vista-card-title">
                                            &nbsp;{{ $cardDetails->cardHolderName }}&nbsp;
                                        </p>
                                    </h3>
                                    <i class="position-absolute pos-right pos-bottom opacity-15 mb-n1 mr-n4"
                                        style="font-size: 6rem;"></i>
                                </div>
                            </a>
                        </div>
                    @endforeach
                @endif
            @endrole
        </div>
    @endif

    @if (in_array('Crypto', $enabledModules))
        <div class="row">
            <div class="col-md-12">
                <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false"
                    data-panel-color="false" data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
                    <div class="panel-hdr">
                        <h2>Balance by Currency</h2>
                    </div>

                    <div class="panel-container show">
                        <div class="panel-content">
                            <div id="balance_by_currency" style="width: 100%; height: 100%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false"
                    data-panel-color="false" data-panel-locked="false" data-panel-refresh="false"
                    data-panel-reset="false">
                    <div class="panel-hdr">
                        <h2>Accounts By Currency</h2>
                    </div>

                    <div class="panel-container show">
                        <div class="panel-content">
                            <div id="accounts_by_currency" style="width: 100%; height: 100%"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false"
                    data-panel-color="false" data-panel-locked="false" data-panel-refresh="false"
                    data-panel-reset="false">
                    <div class="panel-hdr">
                        <h2>
                            Accounts by Crypto Currency
                        </h2>

                    </div>

                    <div class="panel-container show mb-3">
                        <div class="panel-content">
                            <div id="accounts_by_crypto_currency"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="row">
            <div class="col-md-6">
                <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false"
                    data-panel-color="false" data-panel-locked="false" data-panel-refresh="false"
                    data-panel-reset="false">
                    <div class="panel-hdr">
                        <h2>Balance by Currency</h2>
                    </div>

                    <div class="panel-container show">
                        <div class="panel-content">
                            <div id="balance_by_currency" style="width: 100%; height: 100%"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false"
                    data-panel-color="false" data-panel-locked="false" data-panel-refresh="false"
                    data-panel-reset="false">
                    <div class="panel-hdr">
                        <h2>Accounts By Currency</h2>
                    </div>

                    <div class="panel-container show">
                        <div class="panel-content">
                            <div id="accounts_by_currency" style="width: 100%; height: 100%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
    <div class="row">
        <div class="col-md-6">
            <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false"
                data-panel-color="false" data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
                <div class="panel-hdr">
                    <h2>Transactions By Type</h2>
                </div>

                <div class="panel-container show">
                    <div class="panel-content">
                        <div id="transactions_by_type" style="width: 100%; height: 100%"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false"
                data-panel-color="false" data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
                <div class="panel-hdr">
                    <h2>Transactions By Status</h2>
                </div>

                <div class="panel-container show">
                    <div class="panel-content">
                        <div id="transactions_by_status" style="width: 100%; height: 100%"></div>
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
                    <h2>
                        Transactions By Date
                    </h2>

                </div>

                <div class="panel-container show mb-3">
                    <div class="panel-content">
                        <div id="transactions_by_date"></div>
                    </div>
                </div>
            </div>

        </div>
    </div>
@stop
@section('scripts')

    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script type="text/javascript">
        const chartColor = @json($ownCompany->chart_color ?? '#ffcc00');

        $(document).ready(async function() {
            google.charts.load('current', {
                packages: ['corechart']
            });
            await google.charts.setOnLoadCallback(balanceByCurrency);
            await google.charts.setOnLoadCallback(accountsByCurrency);
            await google.charts.setOnLoadCallback(accountsByCryptoCurrency);
            await google.charts.setOnLoadCallback(transactionsByType);
            await google.charts.setOnLoadCallback(transactionsByStatus);
            await google.charts.setOnLoadCallback(transactionsByDate);

            const enabledModules = '@php echo json_encode($enabledModules) @endphp'
            const hasRoleClient = "@php echo Auth::user()->hasRole('client') @endphp"
            const cardsCount = '@php echo count($clientCards) @endphp'

            if (enabledModules.includes('Card')) {
                $.getJSON("{{ url('/card-management/cards/get-total-cards') }}", function(jsonData) {
                    $("#total-cards").html(jsonData);
                })

                if (hasRoleClient && cardsCount > 0) {
                    const mpinArray = JSON.parse('@php echo json_encode($mpinArray) @endphp')

                    for (let mpin of mpinArray) {

                        await $.getJSON("{{ url('/client/card-management/card/get-balance') }}" + "/" + mpin,
                            function(
                                jsonData) {
                                document.getElementById(mpin).innerHTML = jsonData
                            })
                    }
                }
            }
        });

        function balanceByCurrency() {

            var url = "{{ url('/dashboards/finance/charts/balance-by-currency') }}";

            $.getJSON(url, function(jsonData) {
                var data = new google.visualization.DataTable();
                data.addColumn('string', 'Status');
                data.addColumn('number', 'Count');

                $.each(jsonData, function(k, v) {
                    data.addRow(v)
                })

                var options = {
                    //title: 'Transactions by Nature',
                    subtitle: 'Currency, Balance',
                    //title: 'Proposal Status',
                    legend: {
                        position: "top"
                    },
                    colors: [chartColor, 'orange', '#b36b00'],
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

                var chart = new google.visualization.BarChart(document.getElementById('balance_by_currency'));
                chart.draw(data, options);
            });
        }

        function accountsByCurrency() {

            var url = "{{ url('/dashboards/finance/charts/accounts-by-currency') }}";

            $.getJSON(url, function(jsonData) {
                var data = new google.visualization.DataTable();
                data.addColumn('string', 'Currency');
                data.addColumn('number', 'Count');

                $.each(jsonData, function(k, v) {
                    data.addRow(v)
                })

                var options = {
                    //title: 'Transactions by Nature',
                    subtitle: 'Currency, Count',
                    //title: 'Proposal Status',
                    legend: {
                        position: "top"
                    },
                    colors: [chartColor, 'orange', '#b36b00'],
                    chartArea: {
                        width: "70%",
                        height: "300px"
                    },
                    animation: {
                        "startup": true,
                        duration: 500,
                        easing: 'in'
                    },
                    pieHole: 0.2
                };

                var chart = new google.visualization.BarChart(document.getElementById('accounts_by_currency'));
                chart.draw(data, options);
            });
        }

        function accountsByCryptoCurrency() {

            var url = "{{ url('/dashboards/finance/charts/accounts-by-crypto-currency') }}";

            $.getJSON(url, function(jsonData) {
                var data = new google.visualization.DataTable();
                data.addColumn('string', 'Currency');
                data.addColumn('number', 'Count');

                $.each(jsonData, function(k, v) {
                    data.addRow(v)
                })

                var options = {
                    //title: 'Transactions by Nature',
                    subtitle: 'Currency, Count',
                    //title: 'Proposal Status',
                    legend: {
                        position: "top"
                    },
                    colors: [chartColor, 'orange', '#b36b00'],
                    chartArea: {
                        width: "70%",
                        height: "70%"
                    },
                    hAxis: {
                        minValue: 0
                    },
                    animation: {
                        "startup": true,
                        duration: 500,
                        easing: 'in'
                    },
                    pieHole: 0.2
                };

                var chart = new google.visualization.BarChart(document.getElementById(
                    'accounts_by_crypto_currency'));
                chart.draw(data, options);
            });
        }

        function transactionsByType() {

            var url = "{{ url('/dashboards/finance/charts/transactions-by-nature') }}";

            $.getJSON(url, function(jsonData) {
                var data = new google.visualization.DataTable();
                data.addColumn('string', 'Currency');
                data.addColumn('number', 'Count');

                $.each(jsonData, function(k, v) {
                    data.addRow(v)
                })

                var options = {
                    //title: 'Transactions by Nature',
                    subtitle: 'Credit, Debit',
                    //title: 'Proposal Status',
                    legend: {
                        position: "top"
                    },
                    colors: [chartColor, 'orange', '#b36b00'],
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

                var chart = new google.visualization.ColumnChart(document.getElementById('transactions_by_type'));
                chart.draw(data, options);
            });
        }

        function transactionsByStatus() {

            var url = "{{ url('/dashboards/finance/charts/transactions-by-status') }}";

            $.getJSON(url, function(jsonData) {
                var data = new google.visualization.DataTable();
                data.addColumn('string', 'Status');
                data.addColumn('number', 'Count');

                $.each(jsonData, function(k, v) {
                    data.addRow(v)
                })

                var options = {
                    //title: 'Transactions by Nature',
                    subtitle: 'Credit, Debit',
                    //title: 'Proposal Status',
                    legend: {
                        position: "top"
                    },
                    colors: [chartColor, 'orange', '#b36b00'],
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

                var chart = new google.visualization.ColumnChart(document.getElementById('transactions_by_status'));
                chart.draw(data, options);
            });
        }

        function transactionsByDate() {

            var url = "{{ url('/dashboards/finance/charts/transactions-by-date') }}";

            $.getJSON(url, function(jsonData) {
                var data = new google.visualization.DataTable();
                data.addColumn('string', 'Date');
                data.addColumn('number', 'Count');

                $.each(jsonData, function(k, v) {
                    data.addRow(v)
                })

                var options = {
                    //title: 'Transactions by Nature',
                    subtitle: 'Transactions by Date',
                    //title: 'Proposal Status',
                    legend: {
                        position: "top"
                    },
                    colors: [chartColor, 'orange', '#b36b00'],
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

                var chart = new google.visualization.LineChart(document.getElementById('transactions_by_date'));
                chart.draw(data, options);
            });
        }
    </script>
@stop
