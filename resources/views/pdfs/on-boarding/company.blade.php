@php
    $pdfOptions = $pdfOptions ?? [];
    $pdfColors = app(\App\Services\OnboardingPdfOptionsService::class)->colors($pdfOptions);
@endphp
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>{{ $ownCompany ? $ownCompany->short_name : '' }}</title>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <!-- <link href="https://fonts.googleapis.com/css2?family=Noto+Naskh+Arabic:wght@500;600;700&family=Roboto&display=swap" rel="stylesheet"> -->

    <style type="text/css">
        @page {
            margin: 30px 5px;
        }

        body {
            /*font-family: Arial, Helvetica, sans-serif;*/
            font-family: 'Roboto', sans-serif;
            font-weight: 600;
            font-size: 14px;
            line-height: 20px;
            color: {{ $pdfColors['text'] }};
        }

        table {
            font-family: 'Roboto', sans-serif;
            font-weight: 600;
            font-size: 10px;
            line-height: 12px;
        }

        table.border {
            border-top: 1px solid #666;
            border-left: 1px solid #666;
        }

        table.large td {
            font-weight: 400;
            font-size: 10px;
            padding: 1px 6px !important;
        }

        table.border td {
            padding: 4px 6px;
            border-right: 1px solid #666;
            border-bottom: 1px solid #666;
        }

        table.noborder,
        table.noborder td {
            border: transparent;
        }

        h3.yellowTitle {
            background: {{ $pdfColors['highlight'] }};
            color: {{ $pdfColors['title_bar_text'] }};
            padding: 2px 60px 2px 6px;
            display: inline-block;
        }

        h2 {
            font-size: 18px;
            line-height: 20px;
        }

        h3 {
            font-size: 12px;
            line-height: 22px;
            margin: 3px 0px;
        }

        .blue {
            color: {{ $pdfColors['title'] }};
        }

        h5 {
            font-size: 11px;
            font-weight: normal;
            margin: 3px 0px;
        }

        ul.square {
            padding-left: 22px;
        }

        ul.square li {
            position: relative;
            list-style: none;
            margin: 4px 0;
        }

        ul.square li::before {
            content: "";
            display: block;
            width: 8px;
            height: 8px;
            border: 1px solid #666;
            position: absolute;
            left: -18px;
            top: 2px;
        }

        .main-table {
            width: 800px;
        }
</style>
    @include('pdfs.on-boarding.partials.colors')
</head>

<body leftmargin="0" topmargin="0" marginwidth="0" marginheight="0">
    <br>

    <!----------------- Page 01 --------------------->

    <table class="main-table page-break-after" border="0" cellpadding="0" cellspacing="1" align="center"
        bgcolor="{{ $pdfColors['page_bg'] }}" style="padding: 0px;">
        <tr>
            <td align="left" valign="top" bgcolor="{{ $pdfColors['page_bg'] }}" style="padding: 0px 40px; margin: 0px; border: 0px;">
                <table width="100%" border="0" cellpadding="0" cellspacing="0" align="center"
                    style="padding: 0px;">
                    <tr>
                        <td align="left" valign="top" style="margin: 0px; padding: 20px 0px;">
                            <table width="100%" border="0" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td align="left" valign="top">
                                        <img src="{{ $ownCompany ? $ownCompany->logo : '' }}" height="70"
                                            border="0" style="margin:0; padding:0; display:block; line-height:0;">
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" valign="top">
                            <h4><strong>Company Documentation Checklist</strong></h4>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" valign="top">
                            <table width="100%" border="0" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td align="left" valign="top"><strong>Transaction Date :<br>
                                            CIF No. :<br>
                                            Product Name :<br>
                                            Customer Name :</strong></td>
                                    <td align="left" valign="top">&nbsp;</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" valign="top">
                            <table width="100%" border="0" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td style="background-color: {{ $pdfColors['accent'] }}; color: {{ $pdfColors['title_bar_text'] }}">
                                        <h3>&nbsp;Check for Completeness of Documentation (New Account
                                            –
                                            Subscription) </h3>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td align="left" valign="top">
                            <h5>* Please indicate N/A if the form is not required /
                                applicable </h5>
                        </td>
                    </tr>
                    <tr>
                        <td align="left" valign="top">
                            <table width="100%" cellspacing="0" class="border large">
                                <tr>
                                    <td><strong> No</strong></td>
                                    <td><strong> Forms and Documentation</strong></td>
                                    <td><strong>Maker Individual/ Non- Individual</strong></td>
                                    <td><strong> Checker</strong></td>
                                </tr>
                                <tr>
                                    <td colspan="2" bgcolor="{{ $pdfColors['section'] }}" style="background-color: {{ $pdfColors['section'] }}"><strong>New to Bank / New to product
                                            only</strong>
                                    </td>
                                    <td bgcolor="{{ $pdfColors['section'] }}" style="background-color: {{ $pdfColors['section'] }}"><br /></td>
                                    <td bgcolor="{{ $pdfColors['section'] }}" style="background-color: {{ $pdfColors['section'] }}"><br /></td>
                                </tr>
                                <tr>
                                    <td valign="top">1</td>
                                    <td>Account Opening Form - Individual &amp; Corporate Photocopy of NRIC &amp;
                                        Passport
                                        and utilities bill (non-individual: directors / signatories)
                                    </td>
                                    <td><br /></td>
                                    <td><br /></td>
                                </tr>
                                <tr>
                                    <td valign="top">2</td>
                                    <td>Foreign Account Tax Compliance Act ("FATCA")</td>
                                    <td><br /></td>
                                    <td><br /></td>
                                </tr>
                                <tr>
                                    <td valign="top">3</td>
                                    <td>Signature Specimen</td>
                                    <td><br /></td>
                                    <td><br /></td>
                                </tr>
                                <tr>
                                    <td valign="top">4</td>
                                    <td>
                                        <ul class="square">
                                            <li>Certified true copies of company's constitutional documents</li>
                                            <li>Certificate Of Incorporation of Public Co.</li>
                                            <li>Certificate Of Incorporation of Private Co.</li>
                                            <li>Change of company name</li>
                                            <li>Return of allotment of share</li>
                                            <li>Notice of situation of registered office</li>
                                            <li>Register of director, manager, secretarial and change of particulars
                                            </li>
                                            <li> Memorandum and Articles of Association</li>
                                            <li> Latest Annual Return</li>
                                            <li> Certificate of Incumbency / Letter of Information / Letter of Good
                                                Standing
                                                (latest 6 months)
                                            </li>
                                            Note: For company incorporated other than Malaysia or Labuan may provide
                                            documents
                                            base on above description or its equivalent
                                        </ul>
                                    </td>
                                    <td><br /></td>
                                    <td><br /></td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td align="left" valign="top">&nbsp;</td>
                    </tr>
                    <tr>
                        <td align="left" valign="top">
                            <table width="100%" border="0" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td>
                                        Signature:
                                    </td>
                                    <td>
                                        Signature:
                                    </td>
                                </tr>
                                <tr>
                                    <td>&nbsp;</td>
                                    <td>&nbsp;</td>
                                </tr>
                                <tr>
                                    <td>Name: <br>
                                        Date:
                                    </td>
                                    <td>Name: <br>
                                        Date:
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td align="left" valign="top">&nbsp;</td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>

    <br><br>
    <!----------------- Page 02 --------------------->

    <table class="main-table page-break-after" border="0" cellpadding="0" cellspacing="1" align="center"
        bgcolor="{{ $pdfColors['page_bg'] }}" style="padding: 0px;">
        <tr>
            <td align="left" valign="top" bgcolor="{{ $pdfColors['page_bg'] }}" style="padding: 0px 40px; margin: 0px; border: 0px;">
                <table width="100%" border="0" cellpadding="0" cellspacing="0" align="center"
                    style="padding: 0px;">
                    <tr>
                        <td align="left" valign="top" style="margin: 0px; padding: 20px 0px;">
                            <table width="100%" border="0" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td align="left" valign="top">
                                        <img src="{{ $ownCompany ? $ownCompany->logo : '' }}" height="70"
                                            border="0" style="margin:0; padding:0; display:block; line-height:0;">
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td align="left" valign="top">
                            <h2><span class="blue">{{ $ownCompany ? strtoupper($ownCompany->name) : '' }}
                                    @include('pdfs.on-boarding.partials.license-suffix')</span><br>
                                <span style="color:#000;">{{ $pdfOptions['labels']['company_form_title'] ?? 'COMPANY ACCOUNT OPENING APPLICATION FORM' }}</span>
                            </h2>
                        </td>
                    </tr>
                    <tr>
                        <td align="left" valign="top">
                            <table width="100%" cellspacing="0" class="border">
                                <tr>
                                    <td colspan="2"><strong>Branch</strong></td>
                                    <td><strong>Application Ref</strong></td>
                                    <td><strong>Application Date (dd-mm-yyyy)</strong></td>
                                </tr>
                                <tr>
                                    <td style="width:517pt;" colspan="4"><br /></td>
                                </tr>
                                <tr style="height: 25pt;">
                                    <td align="left" valign="top" bgcolor="{{ $pdfColors['section'] }}" style="background-color: {{ $pdfColors['section'] }}"
                                    ><strong>Account Type</strong></td>
                                    <td height="50" colspan="2" align="left" valign="middle"
                                        style="border-right: none;">
                                        <ul class="square" id="l2">
                                            @foreach (($pdfOptions['account_types_left'] ?? ['Customer Term Loan', 'Wealth Management', 'Customer Reserve Funds']) as $acctType)
                                                <li>{{ $acctType }}</li>
                                            @endforeach
                                        </ul>
                                    </td>
                                    <td height="50" colspan="2" align="left" valign="middle">
                                        <ul class="square">
                                            @foreach (($pdfOptions['account_types_right'] ?? ['Customer Investment', 'Financial Advisory', 'Customer Guarantee Funds']) as $acctType)
                                                <li>{{ $acctType }}</li>
                                            @endforeach
                                        </ul>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="left" valign="top" bgcolor="{{ $pdfColors['section'] }}" style="background-color: {{ $pdfColors['section'] }}"><strong>Account
                                            Currency</strong></td>
                                    <td align="left" valign="top" colspan="3">
                                        <table width="50%" cellpadding="4" cellspacing="4" border="0">
                                            <tr>
                                                <td nowrap style="border: none;">
                                                    @foreach ($client->draftCurrencies as $account)
                                                        <br><span style="margin-top:2px;"><img
                                                                src="{{ public_path('/assets/img/pdf/tick.png') }}"
                                                                style="width:10px; height:10px;"
                                                                alt=""></span>
                                                        @if ($account->currency->logo)
                                                            <img src="{{ $account->currency->logo }}"
                                                                alt="{{ $account->currency->name }}" height="10px"
                                                                style="vertical-align: middle;" />&nbsp;
                                                        @endif
                                                        {{ $account->currency->name . ' (' . $account->currency->short . ')' }}
                                                        @if ($loop->iteration % 3 == 0)
                                                </td>
                                                <td nowrap style="border: none;">
                                                    @endif
                                                    @endforeach
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="left" valign="top" bgcolor="{{ $pdfColors['section'] }}" style="background-color: {{ $pdfColors['section'] }}"><strong>Type of
                                            Company</strong></td>
                                    <td align="left" valign="top" colspan="3">
                                        {{ $client->company->type->name ?? '' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td rowspan="10" align="left" valign="top" bgcolor="{{ $pdfColors['section'] }}" style="background-color: {{ $pdfColors['section'] }}">
                                        <strong>Applicant's
                                            Particular</strong>
                                    </td>
                                    <td height="21" colspan="3" align="left" valign="top">
                                        <strong>Company Name</strong><br />
                                        {{ $client->name ?? '' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td height="21" colspan="2" align="left" valign="top">
                                        <strong>Business Registration Number</strong><br />
                                        {{ $client->company->business_reg_number ?? '' }}
                                    </td>
                                    <td height="21" align="left" valign="top">
                                        <strong>Date of Incorporation (dd-mm-yyyy)</strong><br />
                                        {{ \Carbon\Carbon::parse($client->company->registration_date ?? '')->format('d M, Y') }}
                                    </td>
                                </tr>
                                <tr>
                                    <td height="21" colspan="2" align="left" valign="top">
                                        <strong>Place of Registration</strong><br />
                                        {{ $client->company->company_place ?? '' }}
                                    </td>
                                    <td height="21" align="left" valign="top">In Operation Since (dd-mm-yyyy)
                                    </td>
                                </tr>
                                <tr>
                                    <td height="60" colspan="3" align="left" valign="top">
                                        <strong>Business Registered Address (Do not use P.O Box address) Owned
                                            Leased</strong><br />
                                        {{ $client->company->business_reg_address ?? '' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td height="40" colspan="3" align="left" valign="top">Correspondence
                                        Address</td>
                                </tr>
                                <tr>
                                    <td height="21" colspan="2" align="left" valign="top">Office Telephone
                                        No.</td>
                                    <td height="21" align="left" valign="top">Office Facsimile No.</td>
                                </tr>
                                <tr>
                                    <td height="21" colspan="2" align="left" valign="top">Company Email
                                        Address</td>
                                    <td height="21" align="left" valign="top">Company Website</td>
                                </tr>
                                <tr>
                                    <td height="21" colspan="2" align="left" valign="top">Authorised
                                        Capital</td>
                                    <td height="21" align="left" valign="top">Paid Up Capital</td>
                                </tr>
                                <tr>
                                    <td height="21" colspan="2" align="left" valign="top">Average last 3
                                        years Annual Sales
                                        Turnover</td>
                                    <td height="21" align="left" valign="top">Average Number of Full Time
                                        Employees for last
                                        12 months
                                    </td>
                                </tr>
                                <tr>
                                    <td height="21" colspan="3" align="left" valign="top">
                                        <strong>Nature of Business</strong><br />
                                        {{ $client->company->nature_of_business ?? '' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td align="left" valign="top" bgcolor="{{ $pdfColors['section'] }}" style="background-color: {{ $pdfColors['section'] }}">&nbsp;</td>
                                    <td colspan="3" align="left" valign="top">
                                        <table width="100%" border="0" cellpadding="0" cellspacing="0"
                                            class="border">
                                            <tr>
                                                <td colspan="4" align="left" valign="top"><strong>Company
                                                        Contact Person
                                                        :</strong></td>
                                            </tr>
                                            <tr>
                                                <td align="left" valign="top"><strong>Name</strong></td>
                                                <td align="left" valign="top"><strong>Designation</strong></td>
                                                <td align="left" valign="top"><strong>Email</strong></td>
                                                <td align="left" valign="top"><strong>Mobile</strong> Number</td>
                                            </tr>
                                            <tr>
                                                <td height="21" align="left" valign="top">
                                                    {{ $client->company->contact->name ?? '' }}</td>
                                                <td height="21" align="left" valign="top">
                                                    {{ $client->company->contact->designation ?? '' }}</td>
                                                <td height="21" align="left" valign="top">
                                                    {{ $client->company->contact->email_address ?? '' }}</td>
                                                <td height="21" align="left" valign="top">
                                                    {{ $client->company->contact->mobile_number ?? '' }}</td>
                                            </tr>
                                            <tr>
                                                <td height="21" align="left" valign="top">&nbsp;</td>
                                                <td height="21" align="left" valign="top">&nbsp;</td>
                                                <td height="21" align="left" valign="top">&nbsp;</td>
                                                <td height="21" align="left" valign="top">&nbsp;</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td align="left" valign="top">&nbsp;</td>
                    </tr>
                    <tr>
                        <td align="left" valign="top">&nbsp;</td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>

    <br><br>
    <!----------------- Page 03 --------------------->

    <table class="main-table page-break-after" border="0" cellpadding="0" cellspacing="1" align="center"
        bgcolor="{{ $pdfColors['page_bg'] }}" style="padding: 0px;">
        <tr>
            <td align="left" valign="top" bgcolor="{{ $pdfColors['page_bg'] }}"
                style="padding: 0px 40px; margin: 0px; border: 0px;">
                <table width="100%" border="0" cellpadding="0" cellspacing="0" align="center"
                    style="padding: 0px;">
                    <tr>
                        <td align="left" valign="top" style="margin: 0px; padding: 20px 0px;">
                            <table width="100%" border="0" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td align="left" valign="top">
                                        <img src="{{ $ownCompany ? $ownCompany->logo : '' }}" height="70"
                                            border="0" style="margin:0; padding:0; display:block; line-height:0;">
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td align="left" valign="top">
                            <h2><span class="blue">{{ $ownCompany ? $ownCompany->name : '' }}
                                    @include('pdfs.on-boarding.partials.license-suffix') <br>
                                </span>COMPANY ACCOUNT OPENING APPLICATION FORM</h2>
                        </td>
                    </tr>
                    <tr>
                        <td align="left" valign="top">&nbsp;</td>
                    </tr>
                    <tr>
                        <td align="left" valign="top">
                            <table width="100%" cellspacing="0" class="border">
                                <tr>
                                    <td align="left" valign="top" bgcolor="{{ $pdfColors['section'] }}" style="background-color: {{ $pdfColors['section'] }}">
                                        <h3>Applicant's Declaration </h3>
                                    </td>
                                    <td align="left" valign="top">I/We hereby: -
                                        <ol>
                                            <li> Declare that all information provided herein is complete, true and
                                                correct;
                                                agreed that it is my/our responsibility to notify
                                                {{ $ownCompany ? $ownCompany->short_name : '' }} immediately of any
                                                changes to the above information.<br>
                                                <br>
                                            </li>
                                            <li> Agree that {{ $ownCompany ? $ownCompany->short_name : '' }} to do
                                                checking on the information given above from any
                                                source it may deem fit.<br>
                                                <br>
                                            </li>
                                            <li> Acknowledge to have read and fully understood the terms and conditions
                                                governing the conduct of account applied above, which is obtained from
                                                the
                                                {{ $ownCompany ? $ownCompany->short_name : '' }}'s branch and agree to
                                                be bound by the same. <br>
                                                <br>
                                            </li>
                                            <li> Agree to abide by the rules and any such amendment, alteration or
                                                variation
                                                thereof that may be introduced and enforced by
                                                {{ $ownCompany ? $ownCompany->short_name : '' }} from time to
                                                time.<br>
                                                <br>
                                            </li>
                                            <li> Agree to ensure correctness of the monthly statement of account; by
                                                examining and notifying
                                                {{ $ownCompany ? $ownCompany->short_name : '' }} of any errors,
                                                irregularities or
                                                discrepancies
                                                in the statement of account within 14 days of receipts of such.<br>
                                                <br>
                                            </li>
                                            <li> Declare that I/We have not committed any act of bankruptcy and/or no
                                                petition to wind up the company has been filed at the time of
                                                application.
                                            </li>
                                        </ol>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="left" valign="top" bgcolor="{{ $pdfColors['section'] }}" style="background-color: {{ $pdfColors['section'] }}">
                                        <h3>Applicant's Signature</h3>
                                    </td>
                                    <td align="left" valign="top">
                                        <table width="100%" border="0" cellpadding="0" cellspacing="0"
                                            class="noborder">
                                            <tr>
                                                <td width="50%" height="80" align="left" valign="top">
                                                    <h3><em>Signed by
                                                            Director(s):</em></h3>
                                                </td>
                                                <td height="60" align="left" valign="top"><br /></td>
                                            </tr>
                                            <tr>
                                                <td height="60" align="left" valign="top">
                                                    <img src="{{ $client->clientKYCRecord->signature_photo_url ?? '' }}"
                                                        height="60"
                                                        style="position: absolute; margin-top: -40px;" />
                                                    Signature<u>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</u><br>
                                                    Name: <br>
                                                    NRIC/Passport No:
                                                </td>
                                                <td height="60" align="left" valign="top">
                                                    Signature<u>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</u><br>
                                                    Name : <br>
                                                    NRIC/Passport No:
                                                </td>
                                            </tr>
                                            <tr>
                                                <td height="60" align="left" valign="top">
                                                    Signature<u>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</u><br>
                                                    Name : <br>
                                                    NRIC/Passport No:
                                                </td>
                                                <td height="60" align="left" valign="top">
                                                    Signature<u>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</u><br>
                                                    Name : <br>
                                                    NRIC/Passport No:
                                                </td>
                                            </tr>
                                            <tr>
                                                <td height="110" align="left" valign="top">
                                                    <h3>Company Stamp</h3>
                                                </td>
                                                <td height="60" align="left" valign="top"><br /></td>
                                            </tr>
                                            <tr>
                                                <td height="60" align="left" valign="top"> Date (dd-mm-yyyy) :
                                                    <u>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                                    </u>
                                                </td>
                                                <td height="60" align="left" valign="top"><br /></td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td align="left" valign="top">&nbsp;</td>
                    </tr>
                    <tr>
                        <td align="left" valign="top">&nbsp;</td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>

    <br><br>
    <!----------------- Page  04 --------------------->

    <table class="main-table page-break-after" border="0" cellpadding="0" cellspacing="1" align="center"
        bgcolor="{{ $pdfColors['page_bg'] }}" style="padding: 0px;">
        <tr>
            <td align="left" valign="top" bgcolor="{{ $pdfColors['page_bg'] }}"
                style="padding: 0px 40px; margin: 0px; border: 0px;">
                <table width="100%" border="0" cellpadding="0" cellspacing="0" align="center"
                    style="padding: 0px;">
                    <tr>
                        <td align="left" valign="top" style="margin: 0px; padding: 20px 0px;">
                            <table width="100%" border="0" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td align="left" valign="top">
                                        <img src="{{ $ownCompany ? $ownCompany->logo : '' }}" height="70"
                                            border="0" style="margin:0; padding:0; display:block; line-height:0;">
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td align="left" valign="top">
                            <h2><span class="blue">{{ $ownCompany ? strtoupper($ownCompany->name) : '' }}
                                    @include('pdfs.on-boarding.partials.license-suffix') <br>
                                </span>COMPANY ACCOUNT OPENING APPLICATION FORM</h2>
                        </td>
                    </tr>
                    <tr>
                        <td align="left" valign="top">&nbsp;</td>
                    </tr>
                    <tr>
                        <td align="left" valign="top">
                            <table width="100%" cellspacing="0" class="border">
                                <tr>
                                    <td rowspan="5" align="left" valign="top" bgcolor="{{ $pdfColors['section'] }}" style="background-color: {{ $pdfColors['section'] }}">
                                        <h3>Introducer's Details
                                        </h3>
                                        <h3>&nbsp;</h3>
                                    </td>
                                    <td height="30" align="left" valign="top"><strong>Name</strong></td>
                                </tr>
                                <tr>
                                    <td height="30" align="left" valign="top"><strong>IC/ Passport/ Business
                                            Registration
                                            Number </strong></td>
                                </tr>
                                <tr>
                                    <td height="30" align="left" valign="top">
                                        <strong>{{ $ownCompany ? $ownCompany->short_name : '' }} Account Number
                                        </strong>
                                    </td>
                                </tr>
                                <tr>
                                    <td height="30" align="left" valign="top"><strong>Mobile Phone No.
                                        </strong></td>
                                </tr>
                                <tr>
                                    <td height="80" align="left" valign="top">
                                        <table width="100%" border="0" cellpadding="0" cellspacing="3"
                                            class="noborder">
                                            <tr>
                                                <td align="left" valign="top"><strong>Signed By Introducer
                                                    </strong></td>
                                                <td align="left" valign="top">&nbsp;</td>
                                                <td align="left" valign="top">&nbsp;</td>
                                                <td align="left" valign="top">&nbsp;</td>
                                            </tr>
                                            <tr>
                                                <td align="left" valign="top">&nbsp;</td>
                                                <td align="left" valign="top">&nbsp;</td>
                                                <td align="left" valign="top">&nbsp;</td>
                                                <td align="left" valign="top">&nbsp;</td>
                                            </tr>
                                            <tr>
                                                <td align="left" valign="top">
                                                    <u>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</u>
                                                </td>
                                                <td align="left" valign="top">
                                                    <u>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</u>
                                                </td>
                                                <td align="left" valign="top">
                                                    <u>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</u>
                                                </td>
                                                <td align="left" valign="top">
                                                    <u>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</u>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td align="left" valign="top">Signature</td>
                                                <td align="left" valign="top">Name</td>
                                                <td align="left" valign="top">Company Stamp</td>
                                                <td align="left" valign="top">Date (dd-mm-yyyy)</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="left" valign="top" bgcolor="{{ $pdfColors['section'] }}" style="background-color: {{ $pdfColors['section'] }}">
                                        <h3>For Bank Use Only</h3>
                                    </td>
                                    <td align="left" valign="top">
                                        <table width="100%" border="0" cellpadding="0" cellspacing="3"
                                            class="noborder">
                                            <tr>
                                                <td align="left" valign="top"><strong><em>Attended by:
                                                        </em></strong></td>
                                                <td align="left" valign="top"><strong><em>Verified by:
                                                        </em></strong></td>
                                                <td align="left" valign="top"><strong><em>Approved by:
                                                        </em></strong></td>
                                            </tr>
                                            <tr>
                                                <td height="120" align="left" valign="top">&nbsp;</td>
                                                <td align="left" valign="top">&nbsp;</td>
                                                <td align="left" valign="top">&nbsp;</td>
                                            </tr>
                                            <tr>
                                                <td align="left" valign="top">
                                                    <u>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</u>
                                                </td>
                                                <td align="left" valign="top">
                                                    <u>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</u>
                                                </td>
                                                <td align="left" valign="top">
                                                    <u>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</u>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td align="left" valign="top">Name: <br>
                                                    <br>
                                                    Date: <br>
                                                    <br>
                                                    Designation:
                                                </td>
                                                <td align="left" valign="top">Name: <br>
                                                    <br>
                                                    Date: <br>
                                                    <br>
                                                    Designation:
                                                </td>
                                                <td align="left" valign="top">Name: <br>
                                                    <br>
                                                    Date: <br>
                                                    <br>
                                                    Designation:
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td height="40" align="left" valign="top" bgcolor="{{ $pdfColors['section'] }}" style="background-color: {{ $pdfColors['section'] }}">
                                        &nbsp;</td>
                                    <td height="40" align="left" valign="top" bgcolor="#eeeeee">Assigned
                                        Customer Account
                                        Name ：
                                    </td>
                                </tr>
                                <tr>
                                    <td height="40" align="left" valign="top" bgcolor="{{ $pdfColors['section'] }}" style="background-color: {{ $pdfColors['section'] }}">
                                        &nbsp;</td>
                                    <td height="40" align="left" valign="top" bgcolor="#eeeeee">Assigned
                                        Customer Account
                                        Number ：
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td align="left" valign="top">&nbsp;</td>
                    </tr>
                    <tr>
                        <td align="left" valign="top">&nbsp;</td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>


    <br><br>
    <!----------------- Page 07 --------------------->

    <table width="735px" border="0" cellpadding="0" cellspacing="1" align="center" bgcolor="{{ $pdfColors['page_bg'] }}"
        style="padding: 0px;">
        <tr>
            <td align="left" valign="top" bgcolor="{{ $pdfColors['page_bg'] }}" style="padding: 0px; margin: 0px; border: 0px;">
                <table width="100%" border="0" cellpadding="0" cellspacing="0" align="center"
                    style="padding: 0px;">
                    <tr>
                        <td align="left" valign="top">
                            <h3 style="background-color: {{ $pdfColors['accent'] }}; color: {{ $pdfColors['title_bar_text'] }}">&nbsp;FOREIGN
                                ACCOUNT
                                TAX COMPLIANCE ACT ("FATCA") / COMMON REPORTING
                                STANDARD
                                ("CRS")</h3>

                            <p style="font-size: 9px; text-align: justify; text-justify: inter-word;">NOTE: ONLY
                                INDIVIDUAL
                                INVESTORS ARE REQUIRED TO COMPLETE SECTION A – PRELIMINARY IDENTIFICATION. ALL INVESTORS
                                (INDIVIDUAL, LEGAL ENTITY OR CORPORATE) ARE REQUIRED TO COMPLETE SECTION B AND C. LEGAL
                                ENTITY OR CORPORATE WHICH IS NON FINANCIAL ENTITY ONLY, PLEASE COMPLETE SECTION D.</p>
                    <tr>
                        <td style="border: 1px solid black; margin-top: 5px; padding: 5px;">
                            <strong><u>SECTION A – PRELIMINARY IDENTIFICATION</u></strong>
                            <p>
                                Please complete this section and tick ("<span class="s25"></span>") in the "Yes" /
                                "No"
                                column respectively for each of the following questions:
                            </p>
                            <table width="100%" cellspacing="0" class="border">
                                <tr>
                                    <td rowspan="2"><strong><br />No.</strong></td>
                                    <td rowspan="2"><strong><br />U.S. Indicia</strong></td>
                                    <td colspan="2"><strong>Applicant</strong></td>
                                    <td colspan="2"><strong>Joint Applicant</strong></td>
                                </tr>
                                <tr>
                                    <td><strong>Yes</strong></td>
                                    <td><strong>No</strong></td>
                                    <td><strong>Yes</strong></td>
                                    <td><strong>No</strong></td>
                                </tr>
                                <tr>
                                    <td>1.</td>
                                    <td>Are you a U.S. citizen or resident? (U.S. Passport / green card holder, U.S.
                                        taxpayer, etc.)
                                    </td>
                                    <td><br /></td>
                                    <td><br /></td>
                                    <td><br /></td>
                                    <td><br /></td>
                                </tr>
                                <tr>
                                    <td>2.</td>
                                    <td>Is U.S. your place of birth?</td>
                                    <td><br /></td>
                                    <td><br /></td>
                                    <td><br /></td>
                                    <td><br /></td>
                                </tr>
                                <tr>
                                    <td>3.</td>
                                    <td>Do you hold a current U.S. residence address / mailing address and U.S. post
                                        office
                                        box?
                                    </td>
                                    <td><br /></td>
                                    <td><br /></td>
                                    <td><br /></td>
                                    <td><br /></td>
                                </tr>
                                <tr>
                                    <td>4.</td>
                                    <td>Do you have a current U.S. telephone number?</td>
                                    <td><br /></td>
                                    <td><br /></td>
                                    <td><br /></td>
                                    <td><br /></td>
                                </tr>
                                <tr>
                                    <td>5.</td>
                                    <td>Do you have standing instructions to pay amounts from
                                        {{ $ownCompany ? $ownCompany->short_name : '' }} account to an account
                                        maintained in the U.S.?
                                    </td>
                                    <td><br /></td>
                                    <td><br /></td>
                                    <td><br /></td>
                                    <td><br /></td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <br />

                    <tr>
                        <td style="border: 1px solid black; margin-top: 5px; padding: 5px;">
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td>
                                        <strong><u>SECTION B – DECLARATION OF US PERSON</u></strong>
                                        <p><b>All investors </b>(individual, legal entity or corporate) are required to
                                            complete the following declaration. Please tick ("<span
                                                class="s25"></span>")
                                            one of the following:</p>
                                        <table width="100%" border="0" cellpadding="0" cellspacing="0"
                                            class="noborder">
                                            <tr>
                                                <td align="left" valign="top">
                                                    <ul class="square">
                                                        <li>I am a U.S. person / U.S. Legal Entity(s) <em>Please provide
                                                                Form W-9 &amp; photocopy of US Passport</em></li>
                                                    </ul>
                                                </td>
                                                <td align="left" valign="top">
                                                    <ul class="square">
                                                        <li>I am a Non-U.S. person with No U.S. indicia</li>
                                                    </ul>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td align="left" valign="top">
                                                    <ul class="square" colspan="2">
                                                        <li>I am a Non-U.S. person/ Non-U.S. Legal Entity with <em>one
                                                                or
                                                                more U.S. indicia. Please provide Form W-8BEN
                                                                (individual) /
                                                                Form W-8BEN-E (corporate)</em></li>
                                                    </ul>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <br>
                    <tr>
                        <td style="border: 1px solid black; margin-top: 5px; padding: 5px;">
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td>
                                        <strong><u>SECTION C – TAX RESIDENCY</u></strong>
                                        <p><b>All investors </b><span class="s24">(individual, legal entity or
                                                corporate) are required to complete the following declaration.</span>
                                        </p>
                                        <ul class="square">
                                            <li> I / We declare that I / we are tax resident in Malaysia ONLY <br />
                                            </li>
                                            <li>I / We declare that I / we are Foreign tax resident (other than
                                                Malaysia).
                                                Please list ALL the countries in which you are foreign tax resident:<br>
                                                <br>
                                            </li>
                                        </ul>
                                        <table width="100%" cellspacing="0" class="border">
                                            <tr>
                                                <td><strong>Name</strong></td>
                                                <td><strong>Country of Tax Residence</strong></td>
                                                <td><strong>Tax Identification No</strong></td>
                                            </tr>
                                            <tr>
                                                <td><br /></td>
                                                <td><br /></td>
                                                <td><br /></td>
                                            </tr>
                                            <tr>
                                                <td><br /></td>
                                                <td><br /></td>
                                                <td><br /></td>
                                            </tr>
                                        </table>
                                        <br />
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <br />
                    <tr>
                        <td style="border: 1px solid black; margin-top: 5px; padding: 5px;">
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td>
                                        <strong><u>SECTION D – CONTROLLING PERSON</u></strong>
                                        <p>
                                            For corporate which is a <b>non-financial entity (NFE) only</b>, please
                                            complete
                                            the declaration below.
                                        </p>
                                        <table>
                                            <tr>
                                                <td align="left" valign="top" style="padding: 0px;">
                                                    <strong>I / We declare that I /We are</strong>
                                                </td>
                                                <td align="left" valign="top" style="padding: 0px;">
                                                    <ul class="square" style="margin: 0px">
                                                        <li>Active NFE.</li>
                                                        <li>Passive NFE. Please list ALL Controlling Person(s) with tax
                                                            residency other than Malaysia in the following table:
                                                        </li>
                                                    </ul>
                                                </td>
                                            </tr>
                                        </table>
                                        <table width="100%" cellspacing="0" class="border">
                                            <tr>
                                                <td><strong>Name of Controlling Person</strong></td>
                                                <td><strong>Date of Birth</strong></td>
                                                <td><strong>Residence Address</strong></td>
                                                <td><strong>Country of Tax Residence</strong></td>
                                                <td><strong>Tax Identification No</strong></td>
                                            </tr>
                                            <tr>
                                                <td><br /></td>
                                                <td><br /></td>
                                                <td><br /></td>
                                                <td><br /></td>
                                                <td><br /></td>
                                            </tr>
                                            <tr>
                                                <td><br /></td>
                                                <td><br /></td>
                                                <td><br /></td>
                                                <td><br /></td>
                                                <td><br /></td>
                                            </tr>
                                            <tr>
                                                <td><br /></td>
                                                <td><br /></td>
                                                <td><br /></td>
                                                <td><br /></td>
                                                <td><br /></td>
                                            </tr>
                                        </table>
                                        <span class="s41"> </span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
            </td>
        </tr>
    </table>
    </td>
    </tr>
    </table>


    <table width="740px" border="0" cellpadding="0" cellspacing="1" align="center" bgcolor="{{ $pdfColors['page_bg'] }}"
        style="page-break-before: always; padding: 0px;">
        <tr>
            <td align="left" valign="top" bgcolor="{{ $pdfColors['page_bg'] }}" style="padding: 0px; margin: 0px; border: 0px;">
                <table width="100%" border="0" cellpadding="0" cellspacing="0" align="center"
                    style="padding: 0px;">
                    <tr style="text-align: justify; text-justify: inter-word;">
                        <td>
                            <p style="text-indent: 0pt; text-align: left;"><strong>Declaration for Foreign Account Tax
                                    Compliance Act ("FATCA") and Common Reporting Standard ("CRS")</strong></p>
                            <p>I/We declare that I have examined the information on this form and to the best of my
                                knowledge and belief, it is true, correct, and complete. I/We hereby consent for
                                {{ $ownCompany ? $ownCompany->name : '' }} Ltd
                                ({{ $ownCompany ? $ownCompany->short_name : '' }}) that
                                {{ $ownCompany ? $ownCompany->short_name : '' }} shall have right to provide my/our
                                personal data and
                                information to regulatory authorities in accordance with the requirements of Foreign
                                Account
                                Tax Compliance Act (FATCA) and Common Reporting Standard (CRS) for the Automatic
                                Exchange of
                                Financial Account Information as may be stipulated by applicable laws, regulations,
                                agreement or regulatory guidelines or directives.</p>
                            <p>If there is any update to the account information / FATCA / tax residency status /
                                controlling persons or if updates reveal any change of U.S. indicia or change to FATCA
                                status or tax residency status or controlling persons, I/We hereby agree to notify and
                                furnish {{ $ownCompany ? $ownCompany->short_name : '' }} with the relevant documentary
                                evidence within 30 Days of such change. I/We
                                consent to and authorise {{ $ownCompany ? $ownCompany->short_name : '' }} to perform
                                any of the following, if applicable:</p>
                            <ol id="l6">
                                <li data-list-text="1.">Withhold any applicable payments in the account.</li>
                                <li data-list-text="2.">Report or disclose all relevant information relating to or
                                    arising
                                    from the account.
                                </li>
                                <li data-list-text="3.">Terminate (with prior notice) my / our contractual
                                    relationship(s)
                                    with {{ $ownCompany ? $ownCompany->short_name : '' }}.
                                </li>
                            </ol>
                            <strong>Definitions:</strong>
                            <ol id="l7">
                                <li data-list-text="(1)">The term "Financial Institution" means a Depository
                                    Institution,
                                    Custodial Institution, Investment Entity or a Specified Insurance Company. Please
                                    refer
                                    to the CRS guidance available in Lembaga Hasil Dalam Negeri (LHDN) website for
                                    further
                                    information.
                                </li>
                                <li data-list-text="(2)">The term "NFE" refers to "Non Financial Entities", which means
                                    entity that is not a Financial Institution.
                                </li>
                                <li data-list-text="(3)">The term "Active NFE" means entities that:
                                    <ol id="l8" type="a">
                                        <li data-list-text="(a)">do NOT primarily receive passive income or primarily
                                            hold
                                            amounts of assets that produce passive income (such as dividends, interest,
                                            rents etc.).
                                        </li>
                                        <li data-list-text="(b)">include entities that are publicly traded (or related
                                            to a
                                            publicly traded entity), Governmental Entities, International Organisations,
                                            Central Banks, or a holding NFEs of non financial groups.
                                        </li>
                                    </ol>
                                </li>
                                <li data-list-text="(4)">The term "Passive NFE" means any NFE that is not an Active
                                    NFE. An
                                    Investment Entity located in a Non-Participating Jurisdiction is treated as a
                                    Passive
                                    NFE for purposes of the CRS.
                                </li>
                                <li data-list-text="(5)">The term "Controlling Person" means a natural person who
                                    exercises
                                    control over an entity. If the Controlling Person(s) of Passive NFE who hold
                                    directly /
                                    indirectly more than 25% of the shares / voting rights are foreign tax resident(s),
                                    please list all the countries in which the Controlling Person(s) are foreign tax
                                    resident(s).
                                </li>
                                <li data-list-text="(6)">A U.S. citizen is considered a tax resident of the United
                                    States
                                    even if he / she is a tax resident of another jurisdiction. If you are a tax
                                    resident of
                                    the United States, please provide your U.S. TIN using Form W-9.
                                </li>
                            </ol>
                            <strong>I/We hereby acknowledge and declare that:-</strong><br>
                            ( ) I/We have read and understood the Privacy &amp; Personal Data Policy stated in this
                            form.
                            <br>
                            ( ) I/We agree to provide the statement and transactions detail to
                            {{ $ownCompany ? $ownCompany->short_name : '' }}. <br>
                            ( ) I/We have read and understood the Risk Disclosure Statement. <br>
                            ( ) I/We am/are aware of all the fees and charges that will be incurred directly or
                            indirectly
                            when investing in the Product(s). <br />
                            <br>
                            <table width="100%" cellspacing="0" class="border">
                                <tr>
                                    <td height="80" align="left" valign="top">Signature of Principal Holder /
                                        Authorised
                                        Signatories
                                        <img src="{{ $client->clientKYCRecord->signature_photo_url ?? '' }}"
                                            height="60" style="position: absolute;" />
                                    </td>
                                    <td align="left" valign="top">Signature of Joint Holder</td>
                                    <td align="left" valign="top">Common Seal / Company's Stamp</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    <!----------------- Page 09 --------------------->

    <table class="main-table" border="0" cellpadding="0" cellspacing="1" align="center" bgcolor="{{ $pdfColors['page_bg'] }}"
        style="padding: 0px;">
        <tr>
            <td align="left" valign="top" bgcolor="{{ $pdfColors['page_bg'] }}"
                style="padding: 0px 40px; margin: 0px; border: 0px;">
                <table width="100%" border="0" cellpadding="0" cellspacing="0" align="center"
                    style="padding: 0px;">
                    <tr>
                        <td align="left" valign="top" style="margin: 0px; padding: 20px 0px;">
                            <table width="100%" border="0" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td align="left" valign="top">
                                        <img src="{{ $ownCompany ? $ownCompany->logo : '' }}" height="70"
                                            border="0" style="margin:0; padding:0; display:block; line-height:0;">
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td align="left" valign="top">
                            <h2><span id="page93R_mcid2"><span role="presentation" dir="ltr">S</span><span
                                        role="presentation" dir="ltr">IGNATURE SPECIMEN</span></span></h2>
                            <table width="100%" cellspacing="0" class="border" style="border-collapse: collapse;">
                                <tr>
                                    <td height="30" align="left" valign="top">Name of Account:
                                    </td>
                                    <td align="left" valign="top">Account Number:
                                    </td>
                                </tr>
                                <tr>
                                    <td rowspan="4" align="left" valign="top">Address :
                                    </td>
                                    <td height="10" align="left" valign="middle">Tel Numbers:
                                    </td>
                                </tr>
                                <tr style="height: 20pt;">
                                    <td height="10" align="left" valign="middle">H/P Number :
                                    </td>
                                </tr>
                                <tr style="height: 20pt;">
                                    <td height="10" align="left" valign="middle">Fax Number :
                                    </td>
                                </tr>
                                <tr style="height: 20pt;">
                                    <td height="10" align="left" valign="middle">E-mail Address :
                                    </td>
                                </tr>
                                <tr style="height: 21pt;">
                                    <td align="center" valign="top"><strong>Authorised Signatory 1</strong></td>
                                    <td align="center" valign="top"><strong>Authorised Signatory 2</strong></td>
                                </tr>
                                <tr>
                                    <td>
                                        <table width="100%" border="0" cellpadding="0" cellspacing="0"
                                            class="noborder">
                                            <tr>
                                                <td height="30">&nbsp;</td>
                                            </tr>
                                            <tr>
                                                <td align="center" style="border-top: 1px solid #333;">Signature</td>
                                            </tr>
                                            <tr>
                                                <td><span id="page93R_mcid62"><span role="presentation"
                                                            dir="ltr">Name:</span></span><span
                                                        id="page93R_mcid63"><br role="presentation">
                                                        <span role="presentation" dir="ltr">NRIC/Passport
                                                            No:</span></span><span id="page93R_mcid64"><br
                                                            role="presentation">
                                                        <span role="presentation"
                                                            dir="ltr">Designation:</span></span></td>
                                            </tr>
                                        </table>
                                    </td>
                                    <td>
                                        <table width="100%" border="0" cellpadding="0" cellspacing="0"
                                            class="noborder">
                                            <tr>
                                                <td height="30">&nbsp;</td>
                                            </tr>
                                            <tr>
                                                <td align="center" style="border-top: 1px solid #333;">Signature</td>
                                            </tr>
                                            <tr>
                                                <td><span id="page93R_mcid"><span role="presentation"
                                                            dir="ltr">Name:</span></span><span
                                                        id="page93R_mcid3"><br role="presentation">
                                                        <span role="presentation" dir="ltr">NRIC/Passport
                                                            No:</span></span><span id="page93R_mcid4"><br
                                                            role="presentation">
                                                        <span role="presentation"
                                                            dir="ltr">Designation:</span></span></td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr style="height: 23pt;">
                                    <td align="center"><strong>Authorised Signatory 3</strong></td>
                                    <td align="center"><strong>Company Stamp:</strong></td>
                                </tr>
                                <tr style="height: 115pt;">
                                    <td>
                                        <table width="100%" border="0" cellpadding="0" cellspacing="0"
                                            class="noborder">
                                            <tr>
                                                <td height="30">&nbsp;</td>
                                            </tr>
                                            <tr>
                                                <td align="center" style="border-top: 1px solid #333;">Signature
                                                </td>
                                            </tr>
                                            <tr>
                                                <td><span id="page93R_mcid5"><span role="presentation"
                                                            dir="ltr">Name:</span></span><span
                                                        id="page93R_mcid6"><br role="presentation">
                                                        <span role="presentation" dir="ltr">NRIC/Passport
                                                            No:</span></span><span id="page93R_mcid7"><br
                                                            role="presentation">
                                                        <span role="presentation"
                                                            dir="ltr">Designation:</span></span></td>
                                            </tr>
                                        </table>
                                    </td>
                                    <td><br /></td>
                                </tr>
                                <tr>
                                    <td height="15" colspan="2" align="center"><em><strong>FOR OFFICE USE
                                                ONLY</strong></em></td>
                                </tr>
                                <tr>
                                    <td height="15"><em>Type of Account:</em></td>
                                    <td><em>Type of Business / Occupation:</em></td>
                                </tr>
                                <tr>
                                    <td height="15" colspan="2"><em>Condition of Signatures:</em></td>
                                </tr>
                                <tr>
                                    <td height="15" colspan="2"><em>Remarks:</em></td>
                                </tr>
                                <tr>
                                    <td height="15" colspan="2"><em><span id="page93R_mcid15"><span
                                                    role="presentation" dir="ltr">Date Account
                                                    opened:</span></span></em>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center"><strong>Authorised Signatory</strong></td>
                                    <td align="center"><strong>Authorised Signatory</strong></td>
                                </tr>
                                <tr>
                                    <td>
                                        <table width="100%" border="0" cellpadding="0" cellspacing="0"
                                            class="noborder">
                                            <tr>
                                                <td align="center"><em><span id="page93R_mcid21"><span
                                                                role="presentation" dir="ltr">Sighted</span>
                                                            <span role="presentation" dir="ltr">/</span> <span
                                                                role="presentation" dir="ltr">Verified
                                                                by:</span></span></em></td>
                                            </tr>
                                            <tr>
                                                <td height="30"><img
                                                        src="{{ $client->clientKYCRecord->signature_photo_url ?? '' }}"
                                                        height="60" style="position: absolute;" />
                                                </td>
                                            </tr>
                                            <tr>
                                                <td align="center" style="border-top: 1px solid #333;">
                                                    <em>Signature</em>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td><span id="page93R_mcid26"><span role="presentation"
                                                            dir="ltr">Name:</span></span><span
                                                        id="page93R_mcid27"><br role="presentation">
                                                        <span role="presentation" dir="ltr">Staff
                                                            ID</span><span role="presentation"
                                                            dir="ltr">./NRIC:</span></span><span
                                                        id="page93R_mcid28"><br role="presentation">
                                                        <span role="presentation"
                                                            dir="ltr">Designation:</span></span></td>
                                            </tr>
                                        </table>
                                    </td>
                                    <td>
                                        <table width="100%" border="0" cellpadding="0" cellspacing="0"
                                            class="noborder">
                                            <tr>
                                                <td align="center"><em><span id="page93R_mcid29"><span
                                                                role="presentation" dir="ltr">Approved
                                                                by:</span></span></em>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td height="30">&nbsp;</td>
                                            </tr>
                                            <tr>
                                                <td align="center" style="border-top: 1px solid #333;">
                                                    <em>Signature</em>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td><span id="page93R_mcid8"><span role="presentation"
                                                            dir="ltr">Name:</span></span><span
                                                        id="page93R_mcid9"><br role="presentation">
                                                        <span role="presentation" dir="ltr">Staff
                                                            ID</span><span role="presentation"
                                                            dir="ltr">./NRIC:</span></span><span
                                                        id="page93R_mcid10"><br role="presentation">
                                                        <span role="presentation"
                                                            dir="ltr">Designation:</span></span></td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td align="left" valign="top">&nbsp;</td>
                    </tr>
                    <tr>
                        <td align="left" valign="top">&nbsp;</td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>

    <br><br>
    <!----------------- Page 10 --------------------->

    <table class="main-table" border="0" cellpadding="0" cellspacing="1" align="center" bgcolor="{{ $pdfColors['page_bg'] }}"
        style="padding: 0px;">
        <tr>
            <td align="left" valign="top" bgcolor="{{ $pdfColors['page_bg'] }}"
                style="padding: 0px 40px; margin: 0px; border: 0px;">
                <table width="100%" border="0" cellpadding="0" cellspacing="0" align="center"
                    style="padding: 0px;">
                    <tr>
                        <td align="left" valign="top" style="margin: 0px; padding: 20px 0px;">
                            <table width="100%" border="0" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td align="left" valign="top">
                                        <img src="{{ $ownCompany ? $ownCompany->logo : '' }}" height="70"
                                            border="0" style="margin:0; padding:0; display:block; line-height:0;">
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td align="right" valign="top"><span class="s2"
                                style="padding-top: 2pt;  text-indent: 0pt; text-align: left;">CIF NO.:
                                <u>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</u></span>
                        </td>
                    </tr>
                    <tr>
                        <td align="left" valign="top">
                            <h3><span style=" text-indent: 0pt; text-align: left;">RISK PROFILE
                                    QUESTIONNAIRE</span>
                            </h3>
                        </td>
                    </tr>
                    <tr>
                        <td align="left" valign="top"
                            style="padding-left:5px; border:1px solid #333; background: {{ $pdfColors['section'] }};">
                            <h3>IMPORTANT NOTES TO CUSTOMERS</h3>
                        </td>
                    </tr>
                    <tr>
                        <td align="left" valign="top" style="text-align: justify; text-justify: inter-word;">
                            <br>
                            The Risk Profile Questionnaire is designed as a Customer Suitability assessment tool to help
                            you
                            identify an appropriate investment approach. Please be reminded that information disclosed
                            in
                            the Questionnaire pertaining to your risk appetite, financial goals, protection needs and
                            financial positions will be taken into consideration by
                            {{ $ownCompany ? $ownCompany->name : '' }} Ltd
                            ["{{ $ownCompany ? $ownCompany->short_name : '' }}"] Sales
                            Representatives in assessing suitable product(s) for you.
                            Therefore, any change to the information provided should be communicated to your Sales
                            Representatives on a timely basis. Any incomplete
                            or incorrect or outdated information provided by you will affect their assessment and/or the
                            suitability of your product selection accordingly. <br /> <br>
                            You are advised to read and understand the product features, fees and charges, details of
                            the
                            product/s and objectives as stated in the product brochures / fund fact sheets / prospectus
                            /
                            master prospectus / term sheet /
                            information memorandum / product highlight sheet / risk disclosure statement before making
                            any
                            investment decisions. <br /> <br>
                            We do not guarantee any returns as projected in the Questionnaire, Past Performance is not
                            an
                            indicative of future performance. If u make any investment decision based on any product
                            suitability assessment herein, you accept all
                            risks and losses arising out of it and will not hold
                            {{ $ownCompany ? $ownCompany->short_name : '' }}, its officers and the Sales
                            Representatives liable in any manner whatsoever. <br /> <br>
                            All the information you reveal to us in this Questionnaire will be kept strictly private and
                            confidential and used solely for the purpose of customer suitability assessment. <br /> <br>
                            In case of inconsistency between English and Chinese or Bahasa Malaysia versions of this
                            Questionnaire, English version will prevail.
                        </td>
                    </tr>
                    <tr>
                        <td align="left" valign="top">&nbsp;</td>
                    </tr>
                    <tr>
                        <td align="left" valign="top">
                            <table width="100%" border="0" cellpadding="0" cellspacing="0" class="border">
                                <tr>
                                    <td align="left" valign="top" bgcolor="{{ $pdfColors['section'] }}" style="background-color: {{ $pdfColors['section'] }}">
                                        <h3><span id="page95R_mcid24"><span role="presentation" dir="ltr">(A)
                                                    Customer Particulars and Information</span></span></h3>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <table width="100%" border="0" cellpadding="0" cellspacing="0"
                                            class="noborder">
                                            <tr>
                                                <td width="220" height="50" align="left" valign="middle">
                                                    Name of Customer
                                                    :
                                                </td>
                                                <td align="left" valign="middle">
                                                    <u>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</u>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td height="50" align="left" valign="middle">Company
                                                    Registration No. :</td>
                                                <td align="left" valign="middle">
                                                    <u>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</u>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td align="left" valign="top">&nbsp;</td>
                    </tr>
                    <tr>
                        <td align="left" valign="top">
                            <table width="100%" border="0" cellpadding="0" cellspacing="0" class="border">
                                <tr>
                                    <td align="left" valign="top" bgcolor="{{ $pdfColors['section'] }}" style="background-color: {{ $pdfColors['section'] }}">
                                        <h3><span id="page95R_mcid37"><span role="presentation" dir="ltr">(B)
                                                    Customer's Acknowledgements</span></span></h3>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <table width="100%" border="0" cellpadding="0" cellspacing="0"
                                            class="noborder">
                                            <tr>
                                                <td align="left" valign="top">
                                                    <ul class="square">
                                                        <li><strong><span id="page95R_mcid40"><span
                                                                        role="presentation" dir="ltr">Opt Not
                                                                        To Be Risk Profiled</span></span></strong>
                                                        </li>
                                                    </ul>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td align="left" valign="top">
                                                    <ul>
                                                        <li style="text-align: justify; text-justify: inter-word;">
                                                            <span id="page95R_mcid41"><span role="presentation"
                                                                    dir="ltr">We do not wish to disclose all
                                                                    relevant</span><span role="presentation"
                                                                    dir="ltr"> </span><span role="presentation"
                                                                    dir="ltr">information required and do not
                                                                    wish to receive the product suitability</span><br
                                                                    role="presentation">
                                                                <span role="presentation" dir="ltr">assessment
                                                                    from the Sales Representative. We have read the
                                                                    above</span><span role="presentation"
                                                                    dir="ltr">-</span><span role="presentation"
                                                                    dir="ltr">mentioned reminder on risks, fully
                                                                    and</span><br role="presentation">
                                                                <span role="presentation" dir="ltr">clearly
                                                                    understand the risks associated with this product
                                                                    and we are</span><span role="presentation"
                                                                    dir="ltr"> </span><span role="presentation"
                                                                    dir="ltr">willing to bear the
                                                                    relevant</span><br role="presentation">
                                                                <span role="presentation"
                                                                    dir="ltr">risks.</span></span>
                                                        </li>
                                                        <li style="text-align: justify; text-justify: inter-word;">
                                                            <span><span role="presentation" dir="ltr">We do not
                                                                    want to go through the Risk Profile Questionnaire
                                                                    and we have decided to purchase the
                                                                    product</span><br role="presentation">
                                                                <span role="presentation" dir="ltr">based on
                                                                    our own judgement.</span></span>
                                                        </li>
                                                    </ul>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td align="left" valign="top">
                                                    <ul class="square">
                                                        <li><span id="page95R_mcid45"><span role="presentation"
                                                                    dir="ltr">Require Risk Profiled</span></span>
                                                        </li>
                                                    </ul>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td align="left" valign="top">
                                                    <ul>
                                                        <li><strong><span id="page95R_mcid46"><span
                                                                        role="presentation" dir="ltr">Please
                                                                        proceed to Section (C).</span></span></strong>
                                                        </li>

                                                    </ul>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td align="left" valign="top">&nbsp;</td>
                    </tr>
                    <tr>
                        <td align="left" valign="top">&nbsp;</td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>

    <br><br>
    <!----------------- Page 11 --------------------->

    <table class="main-table" border="0" cellpadding="0" cellspacing="1" align="center"
        bgcolor="{{ $pdfColors['page_bg'] }}" style="padding: 0px;">
        <tr>
            <td align="left" valign="top" bgcolor="{{ $pdfColors['page_bg'] }}"
                style="padding: 0px 40px; margin: 0px; border: 0px;">
                <table width="100%" border="0" cellpadding="0" cellspacing="0" align="center"
                    style="padding: 0px;">
                    <tr>
                        <td align="left" valign="top" style="margin: 0px; padding: 20px 0px;">
                            <table width="100%" border="0" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td align="left" valign="top">
                                        <img src="{{ $ownCompany ? $ownCompany->logo : '' }}" height="70"
                                            border="0"
                                            style="margin:0; padding:0; display:block; line-height:0;">
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td align="left" valign="top">
                            <h3><span id="page97R_mcid2"><span role="presentation" dir="ltr">RISK
                                        PROFILE</span><span role="presentation" dir="ltr"> </span><span
                                        role="presentation" dir="ltr">QUESTIONNAIRE</span></span>
                            </h3>
                        </td>
                    </tr>
                    <tr>
                        <td align="left" valign="top">
                            <table width="100%" cellspacing="0" class="border">
                                <tr>
                                    <td colspan="2" bgcolor="{{ $pdfColors['section'] }}" style="background-color: {{ $pdfColors['section'] }}">
                                        <h3>(C) Customer Risk Profile</h3>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2" style="text-align: justify; text-justify: inter-word;">
                                        <u>Disclaimer </u><span class="s6"> This Risk Profile Questionnaire is
                                            designed to help clients or potential clients of
                                            {{ $ownCompany ? $ownCompany->short_name : '' }} identify an investment
                                            approach that generally suits them. The results of the Questionnaire are for
                                            general consideration
                                            only and do not in any way constitute solicitation of offer or offer to
                                        </span> sell / buy / switch funds.
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2">1. What is the estimated size of the portfolio?
                                        <br /> RM <span class="s2"
                                            style="padding-top: 2pt;  text-indent: 0pt; text-align: left;"><u>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</u></span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>2. What is the portfolio time horizon?
                                        </strong>
                                        <ul class="square">
                                            <li class="square"> – 3 years</li>
                                            <li class="square"> – 5 years</li>
                                            <li class="square">&gt; 5 years</li>
                                        </ul>
                                    </td>
                                    <td><strong>3. What is your benchmark?
                                        </strong>
                                        <ul class="square" id="l14">
                                            <li> Better than the 3-month Fixed Deposit Rate</li>
                                            <li> Better than the Composite index</li>
                                            <li> Others:<u>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</u>
                                            </li>
                                        </ul>
                                    </td>
                                </tr>

                                <tr>
                                    <td><strong>4. What are your objectives and risk appetite?
                                        </strong><br />
                                        <ul class="square" id="l15">
                                            <li> We are conservative. Our investment must be 100% principal protected.
                                            </li>
                                            <li> We are willing to take a little investment risk with some potential
                                                capital
                                                gains.
                                            </li>
                                            <li> We are willing to take large amount of investment risk in exchange for
                                                higher potential capital gains.
                                            </li>
                                        </ul>
                                    </td>
                                    <td><strong>5. What products have you invested in? Number of Years of Investment
                                            Experience? </strong><br />
                                        <ul class="square" id="l16">
                                            <li> Money Market Instrument</li>
                                            <li> Bonds and commercial papers</li>
                                            <li> Equities</li>
                                            <li> Unit Trusts</li>
                                            <li> Investment Linked Derivative (ILD)/Structured Products</li>
                                            <li> Negotiation Instrument Deposit (NID/FRNID)</li>
                                        </ul>
                                    </td>
                                </tr>
                                <tr>
                                    <td bgcolor="{{ $pdfColors['section'] }}" style="background-color: {{ $pdfColors['section'] }}">Risk Profile
                                    </td>
                                    <td bgcolor="{{ $pdfColors['section'] }}" style="background-color: {{ $pdfColors['section'] }}">Your Profile Description
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <ul class="square">
                                            <li><strong>Conservative</strong></li>
                                        </ul>
                                    </td>
                                    <td>
                                        <ul id="l17">
                                            <li data-list-text=""> Concerned with capital preservation.</li>
                                            <li data-list-text=""> Have a preference for low risk investments over a
                                                moderate time frame.
                                            </li>
                                            <li data-list-text=""> Investments should be liquid / easily convertible
                                                into
                                                cash. Not willing to accept much fluctuation in the value of the
                                                investment.
                                            </li>
                                        </ul>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <ul class="square">
                                            <li><strong>Moderate</strong></li>
                                        </ul>
                                    </td>
                                    <td>
                                        <ul id="l18">
                                            <li data-list-text=""> Seek stable income through balanced investments
                                                over
                                                medium to long term.
                                            </li>
                                            <li data-list-text=""> Comfortable with a higher level of risk and
                                                willing to
                                                accept mild fluctuation to achieve fair return on investment.
                                            </li>
                                        </ul>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <ul class="square">
                                            <li class="square"><strong>Aggressive</strong></li>
                                        </ul>
                                    </td>
                                    <td>
                                        <ul id="l19">
                                            <li data-list-text=""> Generally concerned with maximizing capital growth
                                                over
                                                short to medium term. Willing to accept higher volatility and risk
                                                levels in
                                                order to achieve higher capital growth.
                                            </li>
                                        </ul>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td align="left" valign="top">&nbsp;</td>
                    </tr>
                    <tr>
                        <td align="left" valign="top">
                            <table width="100%" cellspacing="0" class="border">
                                <tr>
                                    <td colspan="4" bgcolor="{{ $pdfColors['section'] }}" style="background-color: {{ $pdfColors['section'] }}">
                                        <h3>(D) Customer's Product Selection</h3>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Name of Product(s)</strong></td>
                                    <td colspan="3"><strong>Risk Class</strong></td>
                                </tr>
                                <tr>
                                    <td>i)
                                    </td>
                                    <td><span class="s53"> </span>
                                        <ul class="square">
                                            <li><span class="s6">Conservative</span></li>
                                        </ul>
                                    </td>
                                    <td><span class="s53"> </span>
                                        <ul class="square">
                                            <li><span class="s6">Moderate</span></li>
                                        </ul>
                                    </td>
                                    <td><span class="s53"> </span>
                                        <ul class="square">
                                            <li><span class="s6">Aggressive</span></li>
                                        </ul>
                                    </td>
                                </tr>
                                <tr>
                                    <td>ii)
                                    </td>
                                    <td><span class="s53"> </span>
                                        <ul class="square">
                                            <li><span class="s6">Conservative</span></li>
                                        </ul>
                                    </td>
                                    <td><span class="s53"> </span>
                                        <ul class="square">
                                            <li><span class="s6">Moderate</span></li>
                                        </ul>
                                    </td>
                                    <td><span class="s53"> </span>
                                        <ul class="square">
                                            <li><span class="s6">Aggressive</span></li>
                                        </ul>
                                    </td>
                                </tr>
                                <tr>
                                    <td>iii)
                                    </td>
                                    <td><span class="s53"> </span>
                                        <ul class="square">
                                            <li><span class="s6">Conservative</span></li>
                                        </ul>
                                    </td>
                                    <td><span class="s53"> </span>
                                        <ul class="square">
                                            <li><span class="s6">Moderate</span></li>
                                        </ul>
                                    </td>
                                    <td><span class="s53"> </span>
                                        <ul class="square">
                                            <li><span class="s6">Aggressive</span></li>
                                        </ul>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td align="left" valign="top">&nbsp;</td>
                    </tr>
                    <tr>
                        <td align="left" valign="top">&nbsp;</td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>

    <br><br>
    <!----------------- Page 12 --------------------->

    <table class="main-table" border="0" class="large" cellpadding="0" cellspacing="1" align="center"
        bgcolor="{{ $pdfColors['page_bg'] }}" style="padding: 0px;">
        <tr>
            <td align="left" valign="top" bgcolor="{{ $pdfColors['page_bg'] }}"
                style="padding: 0px 40px; margin: 0px; border: 0px;">
                <table width="100%" border="0" cellpadding="0" cellspacing="0" align="center"
                    style="padding: 0px;">
                    <tr>
                        <td align="left" valign="top" style="margin: 0px; padding: 20px 0px;">
                            <table width="100%" border="0" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td align="left" valign="top">
                                        <img src="{{ $ownCompany ? $ownCompany->logo : '' }}" height="70"
                                            border="0"
                                            style="margin:0; padding:0; display:block; line-height:0;">
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td align="left" valign="top">
                            <h3><span id="page99R_mcid2"><span role="presentation" dir="ltr">RISK</span><span
                                        role="presentation" dir="ltr"> </span><span role="presentation"
                                        dir="ltr">PROFILE QUESTIONNAIRE</span></span>
                            </h3>
                        </td>
                    </tr>
                    <tr>
                        <td align="left" valign="top">
                            <table width="100%" cellspacing="0" class="border">
                                <tr>
                                    <td width="640px" colspan="2" bgcolor="{{ $pdfColors['section'] }}" style="background-color: {{ $pdfColors['section'] }}">
                                        <h3><span id="page99R_mcid6"><span role="presentation" dir="ltr">(E)
                                                    Customer's Acknowledgements</span></span>
                                        </h3>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2">
                                        <ul class="square">
                                            <li><span id="page99R_mcid10"><span role="presentation"
                                                        dir="ltr">The information on product features, fees and
                                                        charges, investment risks and other terms and conditions,
                                                        including Shariah</span></span><span id="page99R_mcid11">
                                                </span><span id="page99R_mcid12"><br role="presentation">
                                                    <span role="presentation" dir="ltr">principles (where
                                                        applicable) has been disclosed to us through the brochures /
                                                        fund fact sheet / prospectus / master
                                                        prospect</span></span><span id="page99R_mcid13"><span
                                                        role="presentation" dir="ltr">us</span></span><span
                                                    id="page99R_mcid14"> <span role="presentation"
                                                        dir="ltr">term sheet / product disclosure sheet / risk
                                                        disclosure statement / relevant product literature by the Sales
                                                        Representatives</span></span><span id="page99R_mcid15">
                                                </span><span id="page99R_mcid16"> </span><span
                                                    id="page99R_mcid17"><br role="presentation">
                                                </span></li>
                                            <li><span><span role="presentation" dir="ltr">W</span><span
                                                        role="presentation" dir="ltr">e have read and
                                                        understood the Important Notes to Customers and the disclaimers
                                                        in the Risk Profile Questionnaire. We</span><br
                                                        role="presentation">
                                                    <span role="presentation" dir="ltr">hereby accept the risk
                                                        profile and the results of the product suitability
                                                        assessment.</span></span>
                                            </li>
                                        </ul>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2">
                                        <ul class="square">
                                            <li><strong><span id="page99R_mcid20"><span role="presentation"
                                                            dir="ltr">Risk profile
                                                            mismatch</span></span></strong>
                                            </li>
                                        </ul>
                                        <table width="100%" border="0" cellpadding="0" cellspacing="0"
                                            class="noborder">
                                            <tr>
                                                <td height="15"><span id="page99R_mcid22"><span
                                                            role="presentation" dir="ltr">Name of</span><span
                                                            role="presentation" dir="ltr"> </span><span
                                                            role="presentation" dir="ltr">mismatch
                                                            product(s)</span><span role="presentation"
                                                            dir="ltr"> </span><span role="presentation"
                                                            dir="ltr">purchased</span></span>
                                                </td>
                                                <td>&nbsp;</td>
                                            </tr>
                                            <tr>
                                                <td height="15"><span id="page99R_mcid24"><span
                                                            role="presentation" dir="ltr">(1)</span></span>
                                                    <u>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</u><u>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</u>
                                                </td>
                                                <td><span id="page99R_mcid"><span role="presentation"
                                                            dir="ltr">(2)</span></span>
                                                    <u>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</u><u>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</u>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td height="15"><span id="page99R_mcid3"><span
                                                            role="presentation" dir="ltr">(3)</span></span>
                                                    <u>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</u><u>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</u>
                                                </td>
                                                <td>&nbsp;</td>
                                            </tr>
                                        </table>
                                        <ul>
                                            <li><span id="page99R_mcid27"><span role="presentation"
                                                        dir="ltr">We understand and acknowledge that this product
                                                        is of a higher risk than ou</span><span role="presentation"
                                                        dir="ltr">r
                                                        (conservative/moderate)*</span><br role="presentation">
                                                    <span role="presentation" dir="ltr">risk
                                                        appetite.</span></span></li>
                                            <li><span><span role="presentation" dir="ltr">We wish to proceed
                                                        with the product after careful consideration for the
                                                        following reason(s) (please tick):</span></span><br
                                                    role="presentation"></li>
                                            <ul class="square">
                                                <li><span role="presentation" dir="ltr">Financial objectives
                                                        and needs</span><br role="presentation"></li>
                                                <li><span role="presentation" dir="ltr">Potential Regular
                                                        Income / Savings</span><br role="presentation"></li>
                                                <li><span role="presentation" dir="ltr">Diversification /
                                                        Re</span><span role="presentation"
                                                        dir="ltr">-</span><span role="presentation"
                                                        dir="ltr">balancing of inv</span><span
                                                        role="presentation" dir="ltr">estments *by addition of
                                                        asset class / sector / country*</span><br role="presentation">
                                                </li>
                                                <li><span role="presentation" dir="ltr">Potential capital
                                                        appreciation</span><br role="presentation"></li>
                                                <li><span role="presentation" dir="ltr">Others, please specify
                                                        :
                                                        <u>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</u></span>
                                                </li>
                                            </ul>
                                            <li><span> <span role="presentation" dir="ltr">We fully understand
                                                        the product features, fees</span><span role="presentation"
                                                        dir="ltr"> </span><span role="presentation"
                                                        dir="ltr">&amp; charges, as well as the risks</span><span
                                                        role="presentation" dir="ltr"> </span><span
                                                        role="presentation" dir="ltr">associated with the
                                                        investment.</span></span>
                                            </li>
                                        </ul>
                                        <p>&nbsp;</p>
                                    </td>
                                </tr>
                                <tr>
                                    <td bgcolor="{{ $pdfColors['section'] }}" style="background-color: {{ $pdfColors['section'] }}"><strong><span id="page99R_mcid41"><span
                                                    role="presentation" dir="ltr">Authorised
                                                    Signatory</span></span></strong>
                                    </td>
                                    <td bgcolor="{{ $pdfColors['section'] }}" style="background-color: {{ $pdfColors['section'] }}">&nbsp;
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <table width="100%" border="0" cellpadding="0" cellspacing="0"
                                            class="noborder">
                                            <tr>
                                                <td height="30"><img
                                                        src="{{ $client->clientKYCRecord->signature_photo_url ?? '' }}"
                                                        height="60" style="position: absolute;" /></td>
                                            </tr>
                                            <tr>
                                                <td align="left" style="border-top: 1px solid #333;"><span
                                                        id="page99R_mcid48"><span role="presentation"
                                                            dir="ltr">Authorised Signatory</span><span
                                                            role="presentation" dir="ltr"> </span><span
                                                            role="presentation" dir="ltr">(1)</span></span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Name:<br>
                                                    NRIC/Passport No:<br>
                                                    Designation:<br>
                                                    Date:
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                    <td>
                                        <table width="100%" border="0" cellpadding="0" cellspacing="0"
                                            class="noborder">
                                            <tr>
                                                <td height="30">&nbsp;</td>
                                            </tr>
                                            <tr>
                                                <td align="left" style="border-top: 1px solid #333;"><span
                                                        id="page99R_mcid4"><span role="presentation"
                                                            dir="ltr">Authorised Signatory</span><span
                                                            role="presentation" dir="ltr"> </span><span
                                                            role="presentation" dir="ltr">(2)</span></span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Name:<br>
                                                    NRIC/Passport No:<br>
                                                    Designation:<br>
                                                    Date:
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <table width="100%" border="0" cellpadding="0" cellspacing="0"
                                            class="noborder">
                                            <tr>
                                                <td height="30">&nbsp;</td>
                                            </tr>
                                            <tr>
                                                <td align="left" style="border-top: 1px solid #333;"><span
                                                        id="page99R_mcid5"><span role="presentation"
                                                            dir="ltr">Authorised Signatory</span><span
                                                            role="presentation" dir="ltr"> </span><span
                                                            role="presentation" dir="ltr">(3)</span></span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Name:<br>
                                                    NRIC/Passport No:<br>
                                                    Designation:<br>
                                                    Date:
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                    <td>
                                        <table width="100%" border="0" cellpadding="0" cellspacing="0"
                                            class="noborder">
                                            <tr>
                                                <td height="30">&nbsp;</td>
                                            </tr>
                                            <tr>
                                                <td align="left" style="border-top: 1px solid #333;"><span
                                                        id="page99R_mcid80"><span role="presentation"
                                                            dir="ltr">Company Stamp</span></span>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="

                    " bgcolor="{{ $pdfColors['section'] }}" style="background-color: {{ $pdfColors['section'] }}"><strong><span
                                                id="page99R_mcid84"><span role="presentation" dir="ltr">For
                                                    Official Use</span></span> <span id="page99R_mcid86"> <span
                                                    role="presentation" dir="ltr">Only</span></span></strong>
                                    </td>
                                    <td bgcolor="{{ $pdfColors['section'] }}" style="background-color: {{ $pdfColors['section'] }}">&nbsp;
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <table width="100%" border="0" cellpadding="0" cellspacing="0"
                                            class="noborder">
                                            <tr>
                                                <td><span id="page99R_mcid89"><span role="presentation"
                                                            dir="ltr">Sales Representative
                                                            Signature</span></span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td height="30">&nbsp;</td>
                                            </tr>
                                            <tr>
                                                <td align="left" style="border-top: 1px solid #333;">Date:</td>
                                            </tr>
                                        </table>
                                    </td>
                                    <td>
                                        <table width="100%" border="0" cellpadding="0" cellspacing="0"
                                            class="noborder">
                                            <tr>
                                                <td><span id="page99R_mcid97"><span role="presentation"
                                                            dir="ltr">Reviewed by (Designated
                                                            Officer)</span></span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td height="30">&nbsp;</td>
                                            </tr>
                                            <tr>
                                                <td align="left" style="border-top: 1px solid #333;">Date:</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td height="40"><span id="page99R_mcid106"><span role="presentation"
                                                dir="ltr">Staff Name:</span></span><span id="page99R_mcid107">
                                        </span><span id="page99R_mcid108"><br role="presentation">
                                            <span role="presentation" dir="ltr">Staff No./NRIC :</span></span>
                                    </td>
                                    <td><span id="page99R_mcid7"><span role="presentation" dir="ltr">Staff
                                                Name:</span></span><span id="page99R_mcid8"> </span><span
                                            id="page99R_mcid9"><br role="presentation">
                                            <span role="presentation" dir="ltr">Staff No./NRIC :</span></span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

</body>

</html>
