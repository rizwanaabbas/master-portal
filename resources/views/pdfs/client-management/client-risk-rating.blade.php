<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>{{ $ownCompany ? $ownCompany->short_name : '' }}</title>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            margin: 0;
            padding: 0;
        }

        .header {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            background-color: white;
            padding-top: 20px;
            text-align: right;
        }

        .logo-container img {
            width: 150px;
            height: auto;
        }

        .hr-line {
            width: 80%;
            height: 3px;
            background-color: black;
            margin: 5px auto;
        }

        .title {
            font-size: 20px;
            margin: 0pt;
            font-weight: bold;
            padding-top: 8pt;
            text-align: center
        }

        .h4 {
            margin: 0pt;
            font-weight: bold;
            text-align: center
        }

        .form {
            text-align: left;
            margin-top: 20px;
        }

        .form-section {
            margin: 0;
            padding: 0;
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .table_risk_factors th {
            border: 1px solid black;
            padding: 8px;
            text-align: center;
            background-color: #AABBCC
        }

        .table_risk_factors td {
            border: 1px solid black;
            padding: 8px;
            text-align: left;
        }

        .table_total th {
            border: 1px solid black;
            padding: 8px;
            text-align: center;
            background-color: #AABBCC
        }

        .table_total td {
            border: 1px solid black;
            padding: 8px;
            text-align: left;
        }

        .table_risk_rating tr {
            border: 1px solid black;
            text-align: center
        }

        .table_risk_rating td {
            border: 1px solid black;
            padding: 8px;
        }

        .page-break {
            page-break-before: always;
        }

        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            background-color: white;
            text-align: center;
            padding-bottom: 10px;
        }

        .footer h4 {
            margin: 5px 0;
        }

        /* Page Break */
        .page-break {
            page-break-before: always;
            display: block;
            height: 0;
        }

        @page {
            margin: 10mm;
        }

        body {
            padding-top: 80px;
            padding-bottom: 60px;
        }
    </style>
</head>

<body>

    <div class="header">
        <div class="logo-container">
            <img src="{{ $ownCompany ? $ownCompany->logo : '' }}" alt="{{ $ownCompany ? $ownCompany->name : '' }}">
        </div>
    </div>

    <h1 class="title">
        {{ $client->type == 'individual' ? 'Individual Customer Risk Profiling' : 'Entitle Customer Risk Profiling' }}
    </h1>

    <div class="form">
        <h1 class="form-section">
            Name: <span style="font-weight: normal;">{{ $client->name }}</span>
        </h1>
        <h1 class="form-section">
            Date: <span style="font-weight: normal;"> {{ $currentDate }}</span>
        </h1>
    </div>

    <table class="table_risk_factors">
        <thead>
            <tr>
                <th>Risk Factors</th>
                <th>Risk Score</th>
            </tr>
        </thead>
        <tbody>
            @php
                $sectionNames = [
                    'customer_risk' => 'Customer Risk',
                    'country_geographical_risk' => 'Country / Geographic Risks',
                    'product_services' => 'Product / Services',
                    'delivery_channel' => 'Channel',
                    'transactional_risk' => 'Transactional Risk',
                ];
            @endphp

            @foreach ($sectionNames as $key => $label)
                @if (isset($sectionScores[$key]))
                    <tr>
                        <td>{{ $label }}</td>
                        <td style="text-align: center">
                            {{ rtrim(rtrim(number_format($sectionScores[$key], 2, '.', ''), '0'), '.') }}</td>
                    </tr>
                @endif
            @endforeach
        </tbody>
    </table>

    <table style="margin-top: 40px;" class="table_total">
        <thead>
            <tr>
                <th>Total Risk Rating</th>
                <th>Risk Level</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="text-align: center"><strong>{{ $clientRiskRating->risk_score }} / 100</strong></td>
                <td style="text-align: center">
                    @php
                        $aml_risk_results = [];
                        if (!empty($clientRiskRating->risk_score)) {
                            $score_data = ['total_risk_score' => $clientRiskRating->risk_score];
                            $aml_risk_results = App\Modules\Aml\Controllers\AmlRuleController::applyRules(
                                $score_data,
                                'Risk Score',
                            );
                        }
                    @endphp
                    @if (!empty($aml_risk_results) && count($aml_risk_results) > 0)
                        @foreach ($aml_risk_results as $aml)
                            <span style="font-weight: bold"> {{ $aml['rule_name'] }} </span>
                        @endforeach
                    @endif
                </td>
            </tr>
        </tbody>
    </table>

    <div style="margin-top: 40px; text-align: left;">
        <h4>
            Risk Profiling Matrix
        </h4>
    </div>

    <table style="margin-top: 20px;" class="table_risk_rating">
        <thead>
            <tr style="background-color: #AABBCC;">
                <th><strong>Risk Rating</strong></th>
                <th><strong>Points</strong></th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <div>
                        @php
                            $data = App\Modules\Aml\Controllers\AmlRuleController::getDataByName('Risk Score');
                            $rules = $data->amlRules;
                        @endphp
                        @if (!empty($rules))
                            @foreach ($rules as $rule)
                                {{ $rule->rule_name }} <br>
                            @endforeach
                        @endif
                    </div>
                </td>
                <td>
                    <div>
                        @if (!empty($rules))
                            @foreach ($rules as $rule)
                                @php $values = explode(',', $rule->value); @endphp

                                @if (is_array($values))
                                    @if ($loop->last)
                                        {{ $values[0] }} and more
                                    @else
                                        {{ $values[0] }} – {{ $values[1] }} <br>
                                    @endif
                                @endif
                            @endforeach
                        @endif
                    </div>
                </td>
            </tr>
            @if ($clientRiskRating->remarks)
                <tr>
                    <td colspan="2" style="text-align: center">
                        <strong>Remarks:</strong>
                        <div style="margin-top: 10px">

                            {{ $clientRiskRating->remarks }}

                        </div>
                    </td>
                </tr>
            @endif
            <tr style="background-color: #AABBCC;text-align: center">
                <td colspan="2"><b>Prepared by:</b>&nbsp;{{ $user->name }}</td>
            </tr>
        </tbody>
    </table>


    <div class="footer">
        <div class="hr-line"></div>
        <h4>COMPLIANCE DEPARTMENT</h4>
    </div>
</body>

</html>
