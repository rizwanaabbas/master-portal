<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>Transaction Request - {{ $ownCompany ? $ownCompany->short_name : '' }}</title>
    <style>
        @page {
            margin: 30px 30px;
        }

        @font-face {
            font-family: 'DejaVu Sans';
            src: "{{ asset('assets/fonts/ttf/DejaVuSans.ttf') }}" format('truetype');
            font-weight: normal;
            font-style: normal;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            text-decoration: none;
            font-size: 12px;
        }

        /* .water-mark {
            background:url("{{ $ownCompany ? $ownCompany->watermark : '' }}") center center no-repeat; background-size: 500px; height:500px;
        } */
        .main-table {
            width: 660px;
        }

        table.table-bordered thead tr td {
            border: 1px solid black;
            padding: 5px 0px 5px 10px;
        }
    </style>
</head>

<body leftmargin="0" topmargin="0" marginwidth="0" marginheight="0">
    <table class="main-table" border="0" cellpadding="0" cellspacing="1" align="center" bgcolor="#ffffff"
        style="padding: 0px;">
        <tr>
            <td align="left" valign="top" bgcolor="#ffffff" style="padding: 0px 0px; margin: 0px; border: 0px;">
                <table width="100%" border="0" cellpadding="0" cellspacing="0" align="center"
                    style="padding: 0px;">
                    <tr>
                        <td align="left" valign="top" style="margin: 0px; padding: 20px 0px;">
                            <table width="100%" border="0" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td align="left" valign="top">
                                        <img src="{{ $ownCompany ? $ownCompany->logo : '' }}" alt="Menu"
                                            height="55" border="0"
                                            style="margin:0; padding:0; display:block; line-height:0;">
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false"
                data-panel-color="false" data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
                <div class="panel-hdr">
                    <h2>
                        {{ $transaction->transaction_nature }} Transaction Request
                    </h2>
                </div>

                <div class="panel-container show mb-3">
                    <div class="panel-content" style="overflow: scroll;">
                        <table width="100%" class="table table-bordered table-hover m-0 shadow" id="data-grid">
                            <thead class="thead-dark">
                                <tr>
                                    <td width="200"><strong>From Account</strong></td>
                                    <td>{{ $transaction->account->title . ' - ' . $transaction->account->account_number }}
                                    </td>
                                </tr>
                                @if ($transaction->transaction_nature == 'Internal')
                                    @php $balance = \App\Helpers\Common::getAccountBalanceById($transaction->account->id); @endphp
                                    <tr>
                                        <td><strong>From Account Balance</strong></td>
                                        <td>{{ number_format($balance, 2) }}</td>
                                    </tr>
                                    <!-- check if the internal transfer -->
                                    @if ($transaction->receiver_account_number != '')
                                        <tr>
                                            <td><strong>To Account</strong></td>
                                            <td>{{ $transaction->receiver->title . ' - ' . $transaction->receiver->account_number }}
                                            </td>
                                        </tr>
                                    @endif
                                @endif
                                <tr>
                                    <td><strong>Amount</strong></td>
                                    <td>{{ number_format($transaction->amount, 2) }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Request Date</strong></td>
                                    <td>{{ \Carbon\Carbon::parse($transaction->transaction_date)->format('d M, Y') }}
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Type</strong></td>
                                    <td>{{ $transaction->transaction_type }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Mode</strong></td>
                                    <td>{{ $transaction->transaction_nature }}</td>
                                </tr>

                                <tr>
                                    <td><strong>Remarks</strong></td>
                                    <td>
                                        {{ $transaction->remarks }}
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Status</strong></td>
                                    <td>
                                        <span
                                            class="badge {{ $statusBadge[$transaction->status] }}">{{ $transaction->status }}</span>
                                    </td>

                                </tr>
                                @if ($transaction->status == 'Rejected')
                                    <tr>
                                        <td><strong>Reason</strong></td>
                                        <td>{{ $transaction->reject_reason }}</td>
                                    </tr>
                                @endif

                                <!-- check if the TT is for external transfer -->
                                @if ($transaction->beneficiary_id > 0)
                                    <tr>
                                        <td width="150"><strong>Beneficiary Name</strong></td>
                                        <td>{{ $transaction->beneficiary->name }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Beneficiary Account Number</strong></td>
                                        <td>{{ $transaction->beneficiary->account_number }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Beneficiary IBAN Number</strong></td>
                                        <td>{{ $transaction->beneficiary->iban }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Beneficiary Address</strong></td>
                                        <td>{{ $transaction->beneficiary->address }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Beneficiary Bank Name</strong></td>
                                        <td>{{ $transaction->beneficiary->bank_name }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Beneficiary Swift Code</strong></td>
                                        <td>{{ $transaction->beneficiary->swift_code }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Beneficiary Bank Address</strong></td>
                                        <td>{{ $transaction->beneficiary->bank_address }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Beneficiary Status</strong></td>
                                        <td><span>{{ $transaction->beneficiary->status }}</span></td>
                                    </tr>
                                @endif
                                @if ($transaction->transaction_nature == 'Cheque' && !$transaction->creator->hasRole('client'))
                                    <tr>
                                        <td><strong>In Favour of</strong></td>
                                        <td>{{ $transaction->cheque->in_fav_of }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Cheque Number</strong></td>
                                        <td>{{ $transaction->cheque->cheque_number }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Cheque Date</strong></td>
                                        <td>{{ \Carbon\Carbon::parse($transaction->cheque->cheque_date)->format('d M, Y') }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Bank Name</strong></td>
                                        <td>{{ $transaction->cheque->cheque_bank }}</td>
                                    </tr>
                                @endif
                                @if (isset($docs) && count($docs) > 0)
                                    <tr>
                                        <td><strong>Supporting Documents</strong></td>
                                        <td>
                                            <br />
                                            @foreach ($docs as $doc)
                                                <img width="100px" style="margin: 5px 0px; vertical-align: middle;"
                                                    src="{{ $doc->url }}"
                                                    alt="Supporting Document" />&nbsp;&nbsp;&nbsp;
                                            @endforeach
                                        </td>
                                    </tr>
                                @endif
                                @if ($transaction->corresponding_bank_name)
                                    <tr>
                                        <td colspan="2"><strong>Corresponding Bank Details</strong></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Bank Name</strong></td>
                                        <td>{{ $transaction->corresponding_bank_name }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Swift Code</strong></td>
                                        <td>{{ $transaction->corresponding_bank_swift_code }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Bank Address</strong></td>
                                        <td>{{ $transaction->corresponding_bank_address }}</td>
                                    </tr>
                                @endif
                            </thead>
                        </table>
                    </div>
                </div>
            </div>

        </tr>
    </table>
</body>

</html>
