<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
        }

        .logo {
            float: right;
            max-width: 200px;
            height: auto;
            position: fixed;
        }

        .balance-certificate {
            margin-top: 100px;
        }

        .private-confidential {
            float: right;
            color: gray;
            text-align: right;
        }

        .certificate-paragraph {
            margin-top: 30px;
            color: gray;
        }

        .water-mark {
            background: url("{{ $ownCompany ? $ownCompany->watermark : ''  }}") center center no-repeat;
            background-size: 300px;
            height: 300px;
        }

        .table-account {
            width: 100%;
            margin-top: 50px;
            border: 1px solid gray;
        }

        .table-account th {
            background-color: rgb(191, 224, 255);
            padding: 8px;
            text-align: left;
        }

        .table-account td {
            padding: 8px;
            text-align: left;
        }

        .stamp-container {
            position: absolute;
            float: right;
            top: 450;
            right: 0;
        }

        .stamp {
            max-width: 100px;
            height: auto;
        }

        .footer {
            margin-top: 30px;
        }
    </style>
</head>

<body>
    <div style="font-size: small">
        <div>
            <img class="logo" src="{{ $ownCompany ? $ownCompany->logo : '' }}"
                alt="{{ $ownCompany ? $ownCompany->name : '' }}" aria-roledescription="logo">
        </div>

        <div class="balance-certificate">
            <div>
                <h3 style="display: inline;">Balance Certificate</h3>
                <div class="private-confidential">
                    <p>Private & Confidential</p>
                    <p>Date of issue: <b style="color: black">{{ $service->approved_at->format('d,M,Y') }}</b></p>
                </div>
            </div>
            <div class="account-info">
                @foreach ($service->client->clientAccounts as $account)
                    @if ($account->id == $service->account_id)
                        <span>{{ $account->title }} - {{ $account->account_number }}</span>
                    @endif
                @endforeach
                <br>
                <table style="width: 20%">
                    <thead>
                        <tr>
                            <th style="width:10px"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td style="word-wrap: break-word; color: gray">
                                {{ $service->client->individual ? $service->client->individual->mailing_address : $service->client->company->business_reg_address }}
                            </td>
                        </tr>
                        <tr></tr>
                        <tr>
                            <td>TO, <br><span style="color: gray">{{ $service->client->name }}</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="certificate-paragraph">
            <p>
                This letter is to certify that {{ $service->client->name }} (the 'Customer') is maintaining accounts
                with {{ $ownCompany ? $ownCompany->name : '' }} (the 'Bank'). The balance as on close of business dated
                {{ $service->approved_at->format('d-m-Y') }} is as follows:
            </p>
        </div>
        <div class="water-mark">
            <table class="table-account">
                <thead>
                    <tr>
                        <th>Details of Account</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $balance = 0;
                    @endphp
                    @foreach ($service->client->clientAccounts as $account)
                        @if ($account->id == $service->account_id)
                            <tr>
                                <td>{{ $account->account_number }}</td>
                                @php
                                    $balance = \App\Helpers\Common::getAccountBalanceByCurrencyId($account->id, $account->account_currency);
                                @endphp
                                <td>{{ $account->currency ? $account->currency->short : '' }}
                                    {{ \App\Helpers\Common::formatNumber($balance, $account->currency ? $account->currency->is_crypto : false) }}
                                </td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="stamp-container">
            <img class="stamp" src="{{ $ownCompany ? $ownCompany->stamp : '' }}"
                alt="{{ $ownCompany ? $ownCompany->name : '' }}">
        </div>

        <div class="footer">
            <p style="font-weight: bold">This is a computer-generated letter issued in standard format. No signature is
                required</p>

            <p>
                This letter is issued at the request of the Customer and is without any risk or responsibility on the
                Bank or any of its officers. This letter has been issued at the request of the Customer and should not
                be construed as a financial guarantee or any other liability on the part of the Bank or any of its
                employees. This letter is intended solely for the addressee hereto and is issued for information
                purposes only. This letter shall not be construed as an obligation, commitment, guarantee or warranty of
                the Bank or any of its employees nor does it create or purport to create any legally binding obligations
                on behalf of the Bank (including its employees) or any of its affiliates (including their employees) to
                grant or continue to grant the Customer or any third party with any facilities whatsoever.


            </p>

            <div style="font-size: 10px; color: gray">
                <p>
                    CONFIDENTIALITY NOTE: This letter/document//report/certificate is intended only for the person or
                    the entity to which tie addressed and may contain information that is privileged, confidential or
                    otherwise protected. Therefore, disclosure, dissemination, distribution, or copying the information
                    herein by anyone other than the intended recipient is prohibited. If you have received this
                    letter/document/report/certificate erronously, please notify the bank immediately.

                </p>

                <p>
                    Note: You should verify the information shown on this document and report any discrepancies to the
                    bank in writing within 30 days of the document date, otherwise the content will be assumed to be
                    incorrect.
                </p>
            </div>
        </div>
    </div>
</body>

</html>
