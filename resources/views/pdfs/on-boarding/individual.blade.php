@php
    $pdfOptions = $pdfOptions ?? [];
    $pdfColors = app(\App\Services\OnboardingPdfOptionsService::class)->colors($pdfOptions);
@endphp
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>{{ $ownCompany ? $ownCompany->short_name : '' }}</title>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
    <style type="text/css">
        @page {
            margin: 30px 5px;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 13px;
            line-height: 13px;
            color: {{ $pdfColors['text'] }};
        }

        .justified-text {
            text-align: justify;
            text-justify: inter-word;
        }

        table.border {
            border-top: 1px solid #666;
            border-left: 1px solid #666;
        }

        table.border {
            border-top: 1px solid #666;
            border-left: 1px solid #666;
        }

        table.border td {
            padding: 1px 6px;
            border-right: 1px solid #666;
            border-bottom: 1px solid #666;
            font-size: 10px;
        }

        table.noborder,
        table.noborder td {
            border: transparent;
        }

        td.blueTitle,
        h3.blueTitle {
            background: {{ $pdfColors['title_bar'] }};
            color: {{ $pdfColors['title_bar_text'] }};
            padding: 2px 6px;

        }

        h2 {
            font-size: 15px;
        }

        h3 {
            font-size: 13px;
            line-height: 13px;
            margin: 3px 0px;
        }

        .blue {
            color: {{ $pdfColors['title'] }};
        }

        h5 {
            font-size: 12px;
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
                            <h2><span id="page2865R_mcid1"><span role="presentation" dir="ltr">Individual
                                        Documentation Checklist</span></span>
                            </h2>
                        </td>
                    </tr>
                    <tr>
                        <td align="left" valign="top">&nbsp;</td>
                    </tr>
                    <tr>
                        <td align="left" valign="top">
                            <table width="100%" border="0" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td width="200"><strong><span id="page2865R_mcid2"><span role="presentation"
                                                    dir="ltr">Transaction Date:</span></span><span
                                                id="page2865R_mcid3"><br role="presentation">
                                                <span role="presentation" dir="ltr">CIF No:</span></span><span
                                                id="page2865R_mcid4"><br role="presentation">
                                                <span role="presentation" dir="ltr">Product
                                                    Name:</span></span><span id="page2865R_mcid5"><br
                                                    role="presentation">
                                                <span role="presentation" dir="ltr">Customer
                                                    Name:</span></span></strong></td>
                                    <td>&nbsp;</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td align="left" valign="top">&nbsp;</td>
                    </tr>
                    <tr>
                        <td align="left" valign="top"
                            style="background-color: {{ $pdfColors['accent'] }}; color: {{ $pdfColors['title_bar_text'] }}">
                            <h3 class="bg-primary-700">&nbsp;Check for Completeness of
                                Documentation (New Account – Subscription)</h3>
                        </td>
                    </tr>
                    <tr>
                        <td align="left" valign="top"><span id="page2865R_mcid8"><span role="presentation"
                                    dir="ltr">*</span><span role="presentation" dir="ltr"> </span><span
                                    role="presentation" dir="ltr">Please</span><span role="presentation"
                                    dir="ltr"> </span><span role="presentation" dir="ltr">indicate N/A if the
                                    form is not required</span><span role="presentation" dir="ltr"> </span><span
                                    role="presentation" dir="ltr">/ applicable</span></span></td>
                    </tr>
                    <tr>
                        <td align="left" valign="top">&nbsp;</td>
                    </tr>
                    <tr>
                        <td align="left" valign="top">
                            <table width="100%" cellspacing="0" class="border" style="border-collapse: collapse; ">
                                <tr style="height: 49pt;">
                                    <td><strong><br />
                                            No</strong></td>
                                    <td><strong><br />
                                            Forms and Documentation</strong></td>
                                    <td><strong>Maker Individual/ Non- Individual</strong></td>
                                    <td><strong><br />
                                            Checker</strong></td>
                                </tr>
                                <tr style="height: 15pt;">
                                    <td colspan="2" bgcolor="{{ $pdfColors['section'] }}" style="background-color: {{ $pdfColors['section'] }}">New to Bank / New to product only
                                    </td>
                                    <td bgcolor="{{ $pdfColors['section'] }}" style="background-color: {{ $pdfColors['section'] }}"><br /></td>
                                    <td bgcolor="{{ $pdfColors['section'] }}" style="background-color: {{ $pdfColors['section'] }}"><br /></td>
                                </tr>
                                <tr style="height: 24pt;">
                                    <td>1</td>
                                    <td>Account Opening Form - Individual &amp; Corporate Photocopy of NRIC &amp;
                                        Passport
                                        and utilities bill (non-individual: directors / signatories)
                                    </td>
                                    <td><br /></td>
                                    <td><br /></td>
                                </tr>
                                <tr style="height: 15pt;">
                                    <td>2</td>
                                    <td>Foreign Account Tax Compliance Act ("FATCA")</td>
                                    <td><br /></td>
                                    <td><br /></td>
                                </tr>
                                <tr style="height: 15pt;">
                                    <td>3</td>
                                    <td>Signature Specimen</td>
                                    <td><br /></td>
                                    <td><br /></td>
                                </tr>
                                <tr style="height: 24pt;">
                                    <td>4</td>
                                    <td>Individual Suitability Assessment Form - Dated: ………………….. Note: Risk Profile
                                        expires
                                        every 12 months and ISA must be re-submitted
                                    </td>
                                    <td><br /></td>
                                    <td><br /></td>
                                </tr>
                                <tr style="height: 197pt;">
                                    <td>5</td>
                                    <td>Certified true copies of individual documents <br>
                                        <br />
                                        <table width="100%" cellspacing="0" class="border"
                                            style="border-collapse: collapse;">
                                            <tr style="height: 20pt;">
                                                <td><strong>Description</strong></td>
                                            </tr>
                                            <tr style="height: 13pt;">
                                                <td>Passport</td>
                                            </tr>
                                            <tr style="height: 13pt;">
                                                <td>Utility Bill</td>
                                            </tr>
                                            <tr style="height: 13pt;">
                                                <td>6 Months Bank Statement</td>
                                            </tr>
                                        </table>
                                        <br />
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
                            <table width="100%" cellspacing="0"
                                style="border-collapse: collapse; margin-left: 23.34pt;">
                                <tr style="height: 59pt;">
                                    <td width="50%" align="left" valign="top" style="">
                                        @if (!empty($pdfOptions['features']['show_maker_checker_labels']))
                                            {{ $pdfOptions['labels']['maker_caption'] ?? 'Attended by: Sales Representative (Maker)' }}
                                            <br>
                                        @endif
                                        Signature: <br /> <br>
                                        <br>
                                        Name: <br>
                                        Date:
                                    </td>
                                    <td align="left" valign="top" style="">
                                        @if (!empty($pdfOptions['features']['show_maker_checker_labels']))
                                            {{ $pdfOptions['labels']['checker_caption'] ?? 'Checked by: Sales Leader (Checker)' }}
                                            <br>
                                        @endif
                                        Signature: <br /> <br>
                                        <br>
                                        Name: <br>
                                        Date:
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
    <span class="page-break-after"></span>
    <table class="main-table" border="0" cellpadding="0" cellspacing="1" align="center" bgcolor="{{ $pdfColors['page_bg'] }}"
        style="padding: 0px;">
        <tr>
            <td align="left" valign="top" bgcolor="{{ $pdfColors['page_bg'] }}"
                style="padding: 0px 40px; margin: 0px; border: 0px;">
                <table border="0" cellpadding="0" cellspacing="0" align="center" style="padding: 0px;">
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
                        <td align="left" valign="top">&nbsp;</td>
                    </tr>
                    <tr>
                        <td align="left" valign="top">&nbsp;</td>
                    </tr>
                    <tr>
                        <td align="left" valign="top" style="border-right: 1px;">
                            <table width="100%" cellspacing="0" class="border">
                                <tr style="height: 71pt;">
                                    <td colspan="5">
                                        <h2><span class="blue">{{ $ownCompany ? strtoupper($ownCompany->name) : '' }}
                                                @include('pdfs.on-boarding.partials.license-suffix')
                                                </span><br>
                                            <span style="color: #000;">{{ $pdfOptions['labels']['individual_form_title'] ?? 'INDIVIDUAL ACCOUNT OPENING APPLICATION FORM' }}</span>
                                        </h2>
                                    </td>
                                </tr>
                                <tr style="height: 71pt;">
                                    <td colspan="5" align="right" style="

                    ">
                                        <table width="50%" border="0" cellpadding="0" cellspacing="0"
                                            class="border">
                                            <tr>
                                                <td align="left" valign="top">Date : day/month/year<br><br></td>
                                                <td align="left" valign="top">Bank Reference<br><br></td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr style="height: 34pt;">
                                    <td rowspan="8" align="left" valign="top" bgcolor="{{ $pdfColors['section'] }}" style="background-color: {{ $pdfColors['section'] }}">
                                        <strong>Client
                                            Information</strong>
                                    </td>
                                    <td height="30" colspan="2" align="left" valign="top" style="">
                                        <b>Name</b>
                                        <br>{{ $individual->client->name ?? '' }}
                                    </td>
                                    <td colspan="2" rowspan="3" align="left" valign="top" style="">
                                        Permanent Residential
                                        Address (Do not use P.O. Box address) Owned Leased
                                    </td>
                                </tr>
                                <tr style="height: 31pt;">
                                    <td height="30" align="left" valign="top" style="">
                                        <b>Date of Birth</b>
                                        <br>{{ \Carbon\Carbon::parse($individual->dob ?? '')->format('d M, Y') }}
                                    </td>
                                    <td height="30" align="left" valign="top" style="">
                                        <b>Gender</b>
                                        <br>{{ ucfirst($individual->gender ?? '') }}
                                    </td>
                                </tr>
                                @if (!empty($pdfOptions['features']['show_race']))
                                <tr style="height: 36pt;">
                                    <td colspan="2" align="left" valign="top"><strong>Race</strong>
                                        <ul class="square">
                                            @foreach (($pdfOptions['race_options'] ?? ['Malay', 'Indian', 'Chinese', 'Others']) as $raceOpt)
                                                <li><span class="s11">{{ $raceOpt }}
                                                        @if (strcasecmp($raceOpt, 'Others') === 0)
                                                            <u>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</u>
                                                        @endif
                                                    </span></li>
                                            @endforeach
                                        </ul>
                                    </td>
                                </tr>
                                @else
                                <tr style="height: 36pt">
                                    <td colspan="2">&nbsp;</td>
                                </tr>
                                @endif
                                <tr style="height: 46pt;">
                                    <td height="30" align="left" valign="top" style="">
                                        <strong>Nationality</strong>
                                        <br>{{ ucwords(strtolower($individual->nationality->name ?? '')) }}
                                    </td>
                                    <td height="30" align="left" valign="top" style="

                    ">
                                        Second Nationality (For Dual Citizenships)
                                    </td>
                                    <td height="30" colspan="2" rowspan="2" align="left" valign="top"
                                        style="

                    ">
                                        <b>Mailing address (if different then permanent address)</b>
                                        <br>{{ $individual->mailing_address ?? '' }}
                                    </td>
                                </tr>
                                <tr style="height: 36pt;">
                                    <td height="30" align="left" valign="top" style="

                    ">
                                        Identity Card (I.C.) Number
                                    </td>
                                    <td height="30" align="left" valign="top" style="

                    ">
                                        <b>Age</b>
                                        <br>{{ \Carbon\Carbon::parse($individual->dob ?? '')->age }} years
                                    </td>
                                </tr>
                                <tr style="height: 27pt;">
                                    <td height="30" align="left" valign="top" style="

                    ">
                                        <b>Passport No.</b>
                                        <br>{{ $individual->passport_number ?? '' }}
                                    </td>
                                    <td height="30" align="left" valign="top" style="

                    ">
                                        <b>Passport Expiry Date</b>
                                        <br>{{ \Carbon\Carbon::parse($individual->passport_expiry_date ?? '')->format('d M, Y') }}
                                    </td>
                                    <td height="30" colspan="2" align="left" valign="top"
                                        style="

                    ">Home Telephone No.
                                    </td>
                                </tr>
                                <tr style="height: 29pt;">
                                    <td height="30" colspan="2" align="left" valign="top" style="">
                                        <strong>Marital
                                            Status</strong>
                                    </td>
                                    <td colspan="2" align="left" valign="top" style="">
                                        <b>Mobile Phone No.</b>
                                        <br>{{ $individual->mobile_number ?? '' }}
                                    </td>
                                </tr>
                                <tr style="height: 28pt;">
                                    <td height="30" align="left" valign="top" style="

                    ">
                                        Mother's Maiden Name
                                    </td>
                                    <td align="left" valign="top" style="

                    ">Introducer
                                    </td>
                                    <td colspan="2" align="left" valign="top" style="

                    ">
                                        <b>Email</b>
                                        <br>{{ $individual->email_address ?? '' }}
                                    </td>
                                </tr>
                                <tr style="height: 27pt;">
                                    <td rowspan="5" align="left" valign="top" bgcolor="{{ $pdfColors['section'] }}" style="background-color: {{ $pdfColors['section'] }}; "><strong>Employment
                                            Information</strong></td>
                                    <td height="30" colspan="2" align="left" valign="top" style="">
                                        <strong>Occupation</strong>
                                        <br>{{ $individual->employer->occupation_business ?? '' }}
                                    </td>
                                    <td colspan="2" rowspan="3" align="left" valign="top"
                                        style="


                    ">
                                        <b>Applicant Employer Name and Address</b>
                                        <br>{{ $individual->employer->employer_business_name ?? '' }}
                                        <br>{{ $individual->employer->employer_address ?? '' }}
                                    </td>
                                </tr>
                                <tr style="height: 34pt;">
                                    <td height="30" colspan="2" align="left" valign="top"
                                        style="

                    ">
                                        <b>Job Title/ Present Position (if applicable)</b>
                                        <br>{{ $individual->employer->job_title ?? '' }}
                                    </td>
                                </tr>
                                <tr style="height: 27pt;">
                                    <td height="30" colspan="2" align="left" valign="top"
                                        style="

                    ">Employment Start Date (if applicable)
                                    </td>
                                </tr>
                                <tr style="height: 28pt;">
                                    <td height="30" colspan="2" align="left" valign="top"
                                        style="

                    ">Business Telephone No.
                                    </td>
                                    <td height="30" colspan="2" align="left" valign="top"
                                        style="

                    ">Fax No.
                                    </td>
                                </tr>
                                <tr style="height: 27pt;">
                                    <td height="30" colspan="4" align="left" valign="top"
                                        style="

                    ">Other Information
                                    </td>
                                </tr>
                                <tr style="height: 31pt;">
                                    <td rowspan="2" align="left" valign="top" bgcolor="{{ $pdfColors['section'] }}" style="background-color: {{ $pdfColors['section'] }}; "><strong>Asset / Financial Information</strong>
                                    </td>
                                    <td height="20" colspan="2" align="left" valign="top"
                                        style="

                    ">Primary Purpose of Account
                                    </td>
                                    <td height="20" colspan="2" align="left" valign="top"
                                        style="


                    ">Applicant(s) Estimated Gross Annual Income
                                    </td>
                                </tr>
                                <tr style="height: 37pt;">
                                    <td height="20" colspan="2" align="left" valign="top"
                                        style="

                    ">Source of Funds
                                    </td>
                                    <td height="20" colspan="2" align="left" valign="top"
                                        style="

                    ">Source of Wealth
                                    </td>
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
                                <tr style="height: 40pt;">
                                    <td align="left" valign="top" bgcolor="{{ $pdfColors['section'] }}" style="background-color: {{ $pdfColors['section'] }}; "><strong>Account Currency</strong></td>
                                    <td height="50" colspan="5" align="left" valign="middle"
                                        style="border-right: 0px;">
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
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td align="left" valign="top">&nbsp;</td>
                    </tr>
                    <tr>
                        <td align="left" valign="top">
                            <table width="100%" cellspacing="0" class="border">
                                <tr style="height: 37pt;">
                                    <td colspan="3" align="left" valign="top" style="  ">
                                        <h2 class="s16"><span
                                                class="blue">{{ $ownCompany ? strtoupper($ownCompany->name) : '' }}
                                                @include('pdfs.on-boarding.partials.license-suffix')
                                            </span><br>
                                            <span style="color: #000;">INDIVIDUAL ACCOUNT OPENING APPLICATION
                                                FORM</span>
                                        </h2>
                                    </td>
                                </tr>
                                <tr style="height: 25pt;">
                                    <td align="left" valign="top" style=" "><br /></td>
                                    <td align="left" valign="top" style="


                    ">Date :
                                        day/month/year
                                    </td>
                                    <td align="left" valign="top" style="


                    ">Bank Reference
                                    </td>
                                </tr>
                                <tr style="height: 229pt;">
                                    <td align="left" valign="top" bgcolor="{{ $pdfColors['section'] }}" style="background-color: {{ $pdfColors['section'] }}; "><strong>Declaration (Applicant)</strong></td>
                                    <td colspan="2" align="left" valign="top"
                                        style="


                    ">
                                        <ol id="l7">
                                            <li data-list-text="1."> I/We have read, understood and agreed to be bound
                                                by
                                                all the relevant terms and conditions issued by
                                                {{ $ownCompany ? $ownCompany->name : '' }}
                                                Ltd
                                                governing the above account(s) and acknowledge receipt of a copy.
                                            </li>
                                            <li data-list-text="2."> I/We hereby confirm that the information given
                                                above is
                                                correct and complete and authorize the Bank to verify the same from any
                                                source it may deem fit. I/We also undertake to notify the Bank
                                                immediately
                                                of
                                                any changes to the above information including new passport number when
                                                passport is change or renewed.
                                            </li>
                                            <li data-list-text="3."> I/We agree to abide by the rules and any such
                                                amendment, alteration, or variation thereof that may be introduced and
                                                enforced by {{ $ownCompany ? $ownCompany->short_name : '' }} from time
                                                to time.
                                            </li>
                                            <li data-list-text="4."> I/We agree to ensure correctness of the monthly
                                                statement of account by examining and notifying
                                                {{ $ownCompany ? $ownCompany->short_name : '' }} of any errors,
                                                irregularities, or discrepancies in the statement of account.
                                            </li>
                                            <li data-list-text="5."> I/We declare that I/We have not committed any act
                                                of
                                                bankruptcy at the time of application.
                                            </li>
                                        </ol>
                                        <br>
                                        <br>
                                        <img src="{{ $client->clientKYCRecord->signature_photo_url ?? '' }}"
                                            height="60" />
                                        <br /> Signature of Applicant <br />
                                        <br /> <br>
                                        <table width="80%" border="0" cellpadding="0" cellspacing="2"
                                            class="noborder">
                                            <tr>
                                                <td>&nbsp;</td>
                                                <td>&nbsp;</td>
                                                <td>&nbsp;</td>
                                            </tr>
                                            <tr>
                                                <td style="border-top: 1px solid #666;">
                                                    <b>Name: {{ $individual->client->name ?? '' }}</b>
                                                </td>
                                                <td>&nbsp;</td>
                                                <td style="border-top: 1px solid #666;">Date</td>
                                            </tr>
                                        </table>
                                        <br>
                                    </td>
                                </tr>
                                <tr style="height: 20pt;">
                                    <td rowspan="4" align="left" valign="top" bgcolor="{{ $pdfColors['section'] }}" style="background-color: {{ $pdfColors['section'] }}; "><strong>For Bank Use Only</strong></td>
                                    <td colspan="2" align="left" valign="top"
                                        style="


                    ">Account Number Account Name
                                    </td>
                                </tr>
                                <tr style="height: 64pt;">
                                    <td align="left" valign="top" style="

                    ">Application
                                        Recommended by (if applicable) <br /> <br>
                                        <br>
                                        <br>
                                        <br>
                                        <br>
                                        Name &amp; Authorised Signature
                                    </td>
                                    <td align="left" valign="top" style="

                    ">Applicant
                                        Approved by (if applicable) <br /> <br>
                                        <br>
                                        <br>
                                        <br>
                                        <br>
                                        Name &amp; Authorised Signature
                                    </td>
                                </tr>
                                <tr style="height: 27pt;">
                                    <td height="60" colspan="2" align="left" valign="top"
                                        style="

                    ">Remarks
                                    </td>
                                </tr>
                                <tr style="height: 21pt;">
                                    <td height="60" colspan="2" align="left" valign="top"
                                        style="

                    ">Date
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
    <table class="main-table" border="0" cellpadding="0" cellspacing="1" align="center" bgcolor="{{ $pdfColors['page_bg'] }}"
        style="padding: 0px;">
        <tr>
            <td align="left" valign="top" bgcolor="{{ $pdfColors['page_bg'] }}"
                style="padding: 0px 40px; margin: 0px; border: 0px;">
                <table border="0" cellpadding="0" cellspacing="0" align="center" style="padding: 0px;">
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
                            <h3 style="background-color: {{ $pdfColors['accent'] }}; color: {{ $pdfColors['title_bar_text'] }}; padding-top: 2pt; padding-left: 7pt; text-indent: 0pt; text-align: left;">
                                FOREIGN ACCOUNT TAX COMPLIANCE ACT ("FATCA") / COMMON REPORTING STANDARD ("CRS")</h3>
                            <h5 class="justified-text"><br />
                                <strong>NOTE: ONLY INDIVIDUAL INVESTORS ARE REQUIRED TO COMPLETE SECTION A – PRELIMINARY
                                    IDENTIFICATION. ALL INVESTORS (INDIVIDUAL, LEGAL ENTITY OR CORPORATE) ARE REQUIRED
                                    TO
                                    COMPLETE SECTION B AND C. LEGAL ENTITY OR CORPORATE WHICH IS NON FINANCIAL ENTITY
                                    ONLY,
                                    PLEASE COMPLETE SECTION D. </strong><br />
                            </h5>
                            <br>
                            <span style="color: black;  "><strong>SECTION A – PRELIMINARY
                                    IDENTIFICATION</strong></span>
                            <br>
                            Please complete this section and tick ("<span class="s21">&#10004;</span>") in the "Yes"
                            / "No"
                            column respectively for each of the following questions:<br />

                            <table width="100%" cellspacing="0" class="border">
                                <tr style="height: 9pt;">
                                    <td style="

                    " rowspan="2"><strong><br />
                                            No.</strong></td>
                                    <td style="

                    " rowspan="2"><strong><br />
                                            U.S. Indicia</strong></td>
                                    <td style="

                    " colspan="2"><strong>Applicant</strong></td>
                                    <td style="

                    " colspan="2"><strong>Joint Applicant</strong>
                                    </td>
                                </tr>
                                <tr style="height: 9pt;">
                                    <td style="

                    ">Yes
                                    </td>
                                    <td style="

                    ">No
                                    </td>
                                    <td style="

                    ">Yes
                                    </td>
                                    <td style="

                    ">No
                                    </td>
                                </tr>
                                <tr style="height: 9pt;">
                                    <td style="

                    ">1.
                                    </td>
                                    <td style="

                    ">Are you a U.S. citizen or resident? (U.S.
                                        Passport / green card holder, U.S. taxpayer,
                                        etc.)
                                    </td>
                                    <td style="

                    "><br /></td>
                                    <td style="

                    "><br /></td>
                                    <td style="

                    "><br /></td>
                                    <td style="

                    "><br /></td>
                                </tr>
                                <tr style="height: 9pt;">
                                    <td style="

                    ">2.
                                    </td>
                                    <td style="

                    ">Is U.S. your place of birth?
                                    </td>
                                    <td style="

                    "><br /></td>
                                    <td style="

                    "><br /></td>
                                    <td style="

                    "><br /></td>
                                    <td style="

                    "><br /></td>
                                </tr>
                                <tr style="height: 9pt;">
                                    <td style="

                    ">3.
                                    </td>
                                    <td style="

                    ">Do you hold a current U.S. residence address /
                                        mailing address and U.S. post office
                                        box?
                                    </td>
                                    <td style="

                    "><br /></td>
                                    <td style="

                    "><br /></td>
                                    <td style="

                    "><br /></td>
                                    <td style="

                    "><br /></td>
                                </tr>
                                <tr style="height: 9pt;">
                                    <td style="

                    ">4.
                                    </td>
                                    <td style="

                    ">Do you have a current U.S. telephone number?
                                    </td>
                                    <td style="

                    "><br /></td>
                                    <td style="

                    "><br /></td>
                                    <td style="

                    "><br /></td>
                                    <td style="

                    "><br /></td>
                                </tr>
                                <tr style="height: 9pt;">
                                    <td style="

                    ">5.
                                    </td>
                                    <td style="

                    ">Do you have standing instructions to pay amounts
                                        from {{ $ownCompany ? $ownCompany->short_name : '' }} account to an account
                                        maintained in the U.S.?
                                    </td>
                                    <td style="

                    "><br /></td>
                                    <td style="

                    "><br /></td>
                                    <td style="

                    "><br /></td>
                                    <td style="

                    "><br /></td>
                                </tr>
                            </table>
                            <br>
                            <span style="color: black;  "><strong>SECTION B – DECLARATION OF US PERSON</strong></span>
                            <b><br>
                                All investors </b>(individual, legal entity or corporate) are required to complete the
                            following declaration. <br>
                            Please tick ("<span class="s21">&#10004;</span>") one of the following: <br />
                            <table width="100%" cellspacing="0" class="border" style="border-collapse: collapse;">
                                <tr style="height: 10pt;">
                                    <td align="left" valign="top" style="">
                                        <ul class="square">
                                            <li>I am a U.S. person / U.S. Legal Entity(s)Please provide Form W-9 &amp;
                                                photocopy of US Passport<br />
                                            </li>
                                        </ul>
                                    </td>
                                    <td align="left" valign="top" style="">
                                        <ul class="square">
                                            <li>I am a Non-U.S. person with No U.S. indicia<br />
                                                <br />
                                            </li>
                                        </ul>
                                    </td>
                                    <td align="left" valign="top" style="">
                                        <ul class="square">
                                            <li>I am a Non-U.S. person/ Non-U.S. Legal Entity with one or more U.S.
                                                indicia.
                                                Please provide FormW-8BEN (individual) / Form W-8BEN-E (corporate)
                                            </li>
                                        </ul>
                                    </td>
                                </tr>
                            </table>
                            <span id="page11R_mcid58"><span role="presentation" dir="ltr"><strong><br>
                                        SECTION</strong></span><strong> <span role="presentation"
                                        dir="ltr">C</span> <span role="presentation" dir="ltr">–</span>
                                    <span role="presentation" dir="ltr">TAX RESIDENCY</span></strong><span
                                    role="presentation" dir="ltr"></span></span><span id="page11R_mcid59"><br
                                    role="presentation">
                                <span role="presentation" dir="ltr">All investors</span> <span
                                    role="presentation" dir="ltr">(individual, legal entity or corporate) are
                                    required to complete the following declaration.</span></span>
                            <span id="page11R_mcid62"><br role="presentation">
                                <span role="presentation" dir="ltr"><br>
                                </span></span>
                            <ul class="square">
                                <li><span><span role="presentation" dir="ltr">I</span> <span role="presentation"
                                            dir="ltr">/ We declare that I / we are tax
                                            resident in Malaysia ONLY</span></span>
                                    <span id="page11R_mcid68"><br role="presentation">
                                    </span>
                                </li>
                                <li><span><span role="presentation" dir="ltr">I</span> <span role="presentation"
                                            dir="ltr">/ We declare that I / we are Foreign
                                            tax resident (other than</span> <span role="presentation"
                                            dir="ltr">Malaysia). Please list ALL the countries in which you are
                                            foreign tax resident</span></span><br />
                                    <br />
                                </li>
                            </ul>
                            <table width="100%" cellspacing="0" class="border">
                                <tr style="height: 12pt;">
                                    <td style="

                    "><strong>Name</strong></td>
                                    <td style="

                    "><strong>Country of Tax Residence</strong></td>
                                    <td style="

                    "><strong>Tax Identification No</strong></td>
                                </tr>
                                <tr style="height: 16pt;">
                                    <td style="

                    "><br /></td>
                                    <td style="

                    "><br /></td>
                                    <td style="

                    "><br /></td>
                                </tr>
                                <tr style="height: 16pt;">
                                    <td style="

                    "><br /></td>
                                    <td style="

                    "><br /></td>
                                    <td style="

                    "><br /></td>
                                </tr>
                            </table>
                            <br>
                            <span id="page11R_mcid90"><span role="presentation"
                                    dir="ltr"><strong>SECTION</strong></span><strong> <span role="presentation"
                                        dir="ltr">D</span> <span role="presentation" dir="ltr">–</span>
                                    <span role="presentation" dir="ltr">CONTROLLING PERSON</span></strong><span
                                    role="presentation" dir="ltr"></span></span><span id="page11R_mcid91"><br
                                    role="presentation">
                                <span role="presentation" dir="ltr">For corporate which is</span> <span
                                    role="presentation" dir="ltr">a</span> <span role="presentation"
                                    dir="ltr">non-financial entity (NFE) only, please complete</span> <span
                                    role="presentation" dir="ltr">the</span> <span role="presentation"
                                    dir="ltr">declaration below.</span></span>
                            <span id="page11R_mcid93"><br role="presentation">
                                <span role="presentation" dir="ltr"><br>
                                    <strong> I / We declare that I /We are</strong></span></span> <br>
                            <ul class="square">
                                <li><span id="page11R_mcid95"> <span role="presentation" dir="ltr">Active
                                            NFE.</span></span>
                                    <span id="page11R_mcid103"><br role="presentation">
                                    </span>
                                </li>
                                <li><span><span role="presentation" dir="ltr">Passive NFE. Please list ALL
                                            Controlling Person(s) with tax residency other than Malaysia in the
                                            following table:</span></span><br>
                                    <br />
                                </li>
                            </ul>
                            <table width="100%" cellspacing="0" class="border">
                                <tr style="height: 16pt;">
                                    <td align="left" valign="top" style="

                    "><strong>Name
                                            of Controlling Person</strong></td>
                                    <td align="left" valign="top" style="

                    "><strong>Date
                                            of Birth</strong></td>
                                    <td align="left" valign="top" style="

                    ">
                                        <strong>Residence Address</strong>
                                    </td>
                                    <td align="left" valign="top" style="

                    ">
                                        <strong>Country of Tax Residence</strong>
                                    </td>
                                    <td align="left" valign="top" style="

                    "><strong>Tax
                                            Identification No</strong></td>
                                </tr>
                                <tr style="height: 16pt;">
                                    <td style="

                    "><br /></td>
                                    <td style="

                    "><br /></td>
                                    <td style="

                    "><br /></td>
                                    <td style="

                    "><br /></td>
                                    <td style="

                    "><br /></td>
                                </tr>
                                <tr style="height: 16pt;">
                                    <td style="

                    "><br /></td>
                                    <td style="

                    "><br /></td>
                                    <td style="

                    "><br /></td>
                                    <td style="

                    "><br /></td>
                                    <td style="

                    "><br /></td>
                                </tr>
                                <tr style="height: 16pt;">
                                    <td style="

                    ">
                                        <p style="text-indent: 0pt; text-align: left;"><br />
                                        </p>
                                    </td>
                                    <td style="

                    ">
                                        <p style="text-indent: 0pt; text-align: left;"><br />
                                        </p>
                                    </td>
                                    <td style="

                    ">
                                        <p style="text-indent: 0pt; text-align: left;"><br />
                                        </p>
                                    </td>
                                    <td style="

                    ">
                                        <p style="text-indent: 0pt; text-align: left;"><br />
                                        </p>
                                    </td>
                                    <td style="

                    ">
                                        <p style="text-indent: 0pt; text-align: left;"><br />
                                        </p>
                                    </td>
                                </tr>
                            </table>
                            <br>
                            <div style="page-break-before: always"></div>
                            <div class="justified-text">
                                <strong>Declaration for Foreign Account Tax Compliance Act ("FATCA") and Common
                                    Reporting
                                    Standard ("CRS"<span class="s37">)</span></strong> <br /> <br>
                                I/We declare that I have examined the information on this form and to the best of my
                                knowledge and belief, it is true, correct, and complete. I/We hereby consent for
                                {{ $ownCompany ? $ownCompany->name : '' }} Ltd
                                ({{ $ownCompany ? $ownCompany->short_name : '' }}) that
                                {{ $ownCompany ? $ownCompany->short_name : '' }} shall have right to provide my/our
                                personal data and
                                information to regulatory authorities in accordance with the requirements of Foreign
                                Account
                                Tax Compliance
                                Act (FATCA) and Common Reporting Standard (CRS) for the Automatic Exchange of Financial
                                Account Information as may be stipulated by applicable laws, regulations, agreement or
                                regulatory guidelines or directives. <br />
                                <br>
                                If there is any update to the account information / FATCA / tax residency status /
                                controlling persons or if updates reveal any change of U.S. indicia or change to FATCA
                                status or tax residency status or controlling persons,
                                I/We hereby agree to notify and furnish
                                {{ $ownCompany ? $ownCompany->short_name : '' }} with the relevant documentary evidence
                                within 30
                                Days of such change. I/We consent to and authorise
                                {{ $ownCompany ? $ownCompany->short_name : '' }} to perform any of the following,
                                if
                                applicable:
                                <ol id="l8">
                                    <li data-list-text="1."> Withhold any applicable payments in the account.</li>
                                    <li data-list-text="2."> Report or disclose all relevant information relating to
                                        or
                                        arising from the account.
                                    </li>
                                    <li data-list-text="3."> Terminate (with prior notice) my / our contractual
                                        relationship(s) with {{ $ownCompany ? $ownCompany->short_name : '' }}.
                                    </li>
                                </ol>
                                <br />
                                <strong>Definitions:</strong>
                                <ol id="l9">
                                    <li data-list-text="(1)"> The term "Financial Institution" means a Depository
                                        Institution, Custodial Institution, Investment Entity or a Specified Insurance
                                        Company. Please refer to the CRS guidance available in Lembaga Hasil Dalam
                                        Negeri
                                        (LHDN)
                                        website for further information.
                                    </li>
                                    <li data-list-text="(2)"> The term "NFE" refers to "Non-Financial Entities", which
                                        means
                                        entity that is not a Financial Institution.
                                    </li>
                                    <li data-list-text="(3)"> The term "Active NFE" means entities that:
                                        <ol id="l10" type="a">
                                            <li data-list-text="(a)"> do NOT primarily receive passive income or
                                                primarily
                                                hold amounts of assets that produce passive income (such as dividends,
                                                interest, rents etc.).
                                            </li>
                                            <li data-list-text="(b)"> include entities that are publicly traded (or
                                                related
                                                to a publicly traded entity), Governmental Entities, International
                                                Organisations, Central Banks, or a holding NFEs of non-financial groups.
                                            </li>
                                        </ol>
                                    </li>
                                    <li data-list-text="(4)"> The term "Passive NFE" means any NFE that is not an
                                        Active
                                        NFE. An Investment Entity located in a Non-Participating Jurisdiction is treated
                                        as
                                        a Passive NFE for purposes of the CRS.
                                    </li>
                                    <li data-list-text="(5)"> The term "Controlling Person" means a natural person who
                                        exercises control over an entity. If the Controlling Person(s) of Passive NFE
                                        who
                                        hold directly / indirectly more than 25% of the shares / voting rights are
                                        foreign tax resident(s), please list all the countries in which the Controlling
                                        Person(s) are foreign tax resident(s).
                                    </li>
                                    <li data-list-text="(6)"> A U.S. citizen is considered a tax resident of the
                                        United
                                        States even if he / she is a tax resident of another jurisdiction. If you are a
                                        tax
                                        resident of the United States, please provide your U.S. TIN using Form W-9.
                                    </li>
                                </ol>
                                <br /> <strong>I/We hereby acknowledge and declare that:- </strong><br>
                                ( ) I/We have read and understood the Privacy &amp; Personal Data Policy stated in this
                                form. <br>
                                ( ) I/We agree to provide the statement and transactions detail to
                                {{ $ownCompany ? $ownCompany->short_name : '' }}. <br>
                                ( ) I/We have read and understood the Risk Disclosure Statement. <br>
                                ( ) I/We am/are aware of all the fees and charges that will be incurred directly or
                                indirectly when investing in the Product(s).
                            </div>
                            <br>
                            <br>
                            <table width="100%" cellspacing="0" class="border">
                                <tr style="height: 52pt;">
                                    <td height="120" align="left" valign="top" style="">
                                        <img src="{{ $client->clientKYCRecord->signature_photo_url ?? '' }}"
                                            height="60" />
                                        <br />
                                        Signature of Principal Holder / Authorised
                                        Signatories
                                    </td>
                                    <td align="left" valign="top" style="

                    ">Signature of
                                        Joint Holder
                                    </td>
                                    <td align="left" valign="top" style="

                    ">Common Seal /
                                        Company's Stamp
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
                    <tr>
                        <td align="left" valign="top">&nbsp;</td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
    <table class="main-table" border="0" cellpadding="0" cellspacing="1" align="center" bgcolor="{{ $pdfColors['page_bg'] }}"
        style="padding: 0px;">
        <tr>
            <td align="left" valign="top" bgcolor="{{ $pdfColors['page_bg'] }}"
                style="padding: 0px 40px; margin: 0px; border: 0px;">
                <table border="0" cellpadding="0" cellspacing="0" align="center" style="padding: 0px;">
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
                            <h2>SIGNATURE SPECIMEN
                            </h2>
                            <table width="100%" cellspacing="0" class="border">
                                <tr style="height: 36pt;">
                                    <td height="50" align="left" valign="top"
                                        style="
                        width: 248pt;">Name of Account:
                                    </td>
                                    <td align="left" valign="top" style="
                        width: 248pt;">
                                        Account Number:
                                    </td>
                                </tr>
                                <tr style="height: 20pt;">
                                    <td rowspan="4" align="left" valign="top"
                                        style="
                        width: 248pt;">Address :
                                    </td>
                                    <td align="left" valign="top" style="
                        width: 248pt;">
                                        Tel Numbers:
                                    </td>
                                </tr>
                                <tr style="height: 20pt;">
                                    <td align="left" valign="top" style="
                        width: 248pt;">
                                        H/P Number :
                                    </td>
                                </tr>
                                <tr style="height: 20pt;">
                                    <td align="left" valign="top" style="
                        width: 248pt;">
                                        Fax Number :
                                    </td>
                                </tr>
                                <tr style="height: 20pt;">
                                    <td align="left" valign="top" style="
                        width: 248pt;">
                                        E-mail Address :
                                    </td>
                                </tr>
                                <tr style="height: 21pt;">
                                    <td align="left" valign="top" style="
                        width: 248pt;">
                                        <strong>Authorised Signatory
                                            1</strong>
                                    </td>
                                    <td align="left" valign="top" style="
                        width: 248pt;">
                                        <strong>Authorised Signatory
                                            2</strong>
                                    </td>
                                </tr>
                                <tr style="height: 115pt;">
                                    <td class="noborder" style="width: 176pt;"><br>
                                        <br>
                                        <br>
                                        <br>
                                        <u>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                        </u><br />
                                        Signature<br>
                                        <br>
                                        Name: <br>
                                        NRIC/Passport No: <br>
                                        Date:
                                    </td>
                                    <td class="noborder" style="width: 176pt;"><br>
                                        <br>
                                        <br>
                                        <br>
                                        <u>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                        </u><br />
                                        Signature <br>
                                        <br>
                                        Name: <br>
                                        NRIC/Passport No: <br>
                                        Date:
                                    </td>
                                </tr>
                                <tr style="height: 23pt;">
                                    <td align="left" valign="top" style="
                        width: 248pt;">
                                        <strong>Authorised Signatory
                                            3</strong>
                                    </td>
                                    <td align="left" valign="top" style="
                        width: 248pt;">
                                        <strong>Company Stamp:</strong>
                                    </td>
                                </tr>
                                <tr style="height: 115pt;">
                                    <td class="noborder" style="width: 176pt;"><br>
                                        <br>
                                        <br>
                                        <br>
                                        <u>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                        </u><br />
                                        Signature<br>
                                        <br>
                                        Name: <br>
                                        NRIC/Passport No: <br>
                                        Date:
                                    </td>
                                    <td align="left" valign="top" style="
                        width: 248pt;">
                                        <br />
                                    </td>
                                </tr>
                            </table>
                            <h5><strong><em>*Delete where NOT applicable <br>
                                        <br />
                                    </em></strong></h5>
                            <table width="100%" cellspacing="0" class="border">
                                <tr style="height: 19pt;">
                                    <td colspan="2" align="center" valign="top"
                                        style="
                        width: 495pt;">
                                        <h3><em>FOR OFFICE USE ONLY</em></h3>
                                    </td>
                                </tr>
                                <tr style="height: 25pt;">
                                    <td height="25" valign="top" style="
                        width: 252pt;">
                                        <em>Type of Account:</em>
                                    </td>
                                    <td valign="top" style="
                        width: 243pt;"><em>Type of
                                            Business / Occupation:</em></td>
                                </tr>
                                <tr style="height: 28pt;">
                                    <td height="25" colspan="2" valign="top"
                                        style="
                        width: 495pt;"><em>Condition of
                                            Signatures:</em></td>
                                </tr>
                                <tr style="height: 19pt;">
                                    <td height="25" colspan="2" valign="top"
                                        style="
                        width: 495pt;"><em>Remarks:</em></td>
                                </tr>
                                <tr style="height: 20pt;">
                                    <td height="25" valign="top" style="
                        width: 252pt;">
                                        <em>Date Account opened:</em>
                                    </td>
                                    <td valign="top" style="
                        width: 243pt;"><em><br />
                                        </em></td>
                                </tr>
                                <tr style="height: 19pt;">
                                    <td align="center" valign="top" style="
                        width: 252pt;">
                                        &nbsp;
                                    </td>
                                    <td align="center" valign="top" style="
                        width: 243pt;">
                                        &nbsp;
                                    </td>
                                </tr>
                                <tr style="height: 19pt;">
                                    <td align="center" valign="top" style="
                        width: 252pt;">
                                        <em><strong>Authorised
                                                Signatory</strong></em>
                                    </td>
                                    <td align="center" valign="top" style="
                        width: 243pt;">
                                        <em><strong>Authorised
                                                Signatory</strong></em>
                                    </td>
                                </tr>
                                <tr style="height: 99pt;">
                                    <td valign="top" class="noborder" style="width: 176pt;"><em>Sighted /
                                            Verified by:<br>
                                            <br>
                                            <br>
                                            <br>
                                            <br>
                                            <u>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                            </u><br />
                                            Signature<br>
                                            <br>
                                            Name: <br>
                                            Staff ID./NRIC: <br>
                                            Designation:</em></td>
                                    <td valign="top" class="noborder" style="width: 176pt;">
                                        <p><em>Approved by: <br>
                                                <br>
                                                <br>
                                            </em></p>
                                        <p><em><br>
                                                <u>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                                </u><br />
                                                Signature<br>
                                                <br>
                                                <span class="noborder" style="width: 176pt;">Name: <br>
                                                    Staff ID./NRIC: <br>
                                                    Designation:</span></em></p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" valign="top">&nbsp;</td>
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

    {{-- ---------------------- Page 09 ------------------------ --}}

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
                            <table cellspacing="0" class="border">
                                <tr style="height: 15pt;">
                                    <td colspan="4" align="center" bgcolor="{{ $pdfColors['section'] }}" style="background-color: {{ $pdfColors['section'] }}">
                                        <h3>INDIVIDUAL SUITABILITY ASSESSMENT FORM</h3>
                                    </td>
                                </tr>
                                <tr style="height: 62pt;">
                                    <td colspan="4"> This Individual Suitability Assessment Form will guide you in
                                        choosing the products that
                                        suit your objectives, risk tolerance, financial profile. The information you
                                        provide will
                                        form the basis of our recommendation. It is important to provide accurate and
                                        complete information to ensure that suitable products are recommended according
                                        to
                                        your individual needs and objectives. <br />
                                        <br>
                                        <h5><strong> Warning: This recommendation is made based on information obtained
                                                from
                                                the suitable assessment. Individuals are advised to exercise judgement
                                                in
                                                making an informed decision in relation to the
                                                products. </strong></h5>
                                    </td>
                                </tr>
                                <tr style="height: 33pt;">
                                    <td colspan="4">
                                        <ul class="square">
                                            <li>New Individual</li>
                                            <li>Review</li>
                                        </ul>
                                        Note: This suitability assessment form is to be completed by Principal Holder.
                                    </td>
                                </tr>
                                <tr style="height: 15pt;">
                                    <td><strong>Date*</strong></td>
                                    <td colspan="3"><strong><br />
                                        </strong></td>
                                </tr>
                                <tr style="height: 15pt;">
                                    <td><strong>Consultant Name*</strong></td>
                                    <td><strong><br />
                                        </strong></td>
                                    <td><strong>Consultant Code*</strong></td>
                                    <td><br /></td>
                                </tr>
                                <tr style="height: 15pt;">
                                    <td colspan="4"
                                        style="background-color: {{ $pdfColors['accent'] }}; color: {{ $pdfColors['title_bar_text'] }}">Part 1:
                                        Know Your Process <br>
                                        *Mandatory field
                                    </td>
                                </tr>
                                <tr style="height: 15pt;">
                                    <td><strong>Principal Holder Name*</strong></td>
                                    <td colspan="3"><br /></td>
                                </tr>
                                <tr style="height: 15pt;">
                                    <td><strong>NRIC/Passport*</strong></td>
                                    <td><br /></td>
                                    <td><strong>Age</strong></td>
                                    <td><br /></td>
                                </tr>
                                <tr style="height: 15pt;">
                                    <td><strong>Marital Status*</strong></td>
                                    <td colspan="3"> ( ) Single &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;( ) Married
                                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; (
                                        ) Others, please specify:
                                        <u>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                        </u>
                                    </td>
                                </tr>
                                <tr style="height: 15pt;">
                                    <td><strong>Gender*</strong></td>
                                    <td colspan="3">( ) Male &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ( )
                                        Female&nbsp;&nbsp;&nbsp;
                                    </td>
                                </tr>
                                <tr style="height: 15pt;">
                                    <td><strong>No. of Dependant</strong></td>
                                    <td colspan="3"><br /></td>
                                </tr>
                                <tr style="height: 15pt;">
                                    <td><strong>Highest Education</strong></td>
                                    <td colspan="3"> ( ) Degree &amp; above &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;( )
                                        Diploma &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                        ( ) STPM / A-Level &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ( ) SPM &amp; below
                                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<br>
                                        ( ) Others, please specify:<u>
                                            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                        </u>
                                    </td>
                                </tr>
                                <tr style="height: 24pt;">
                                    <td><strong>Occupation*</strong></td>
                                    <td colspan="3">( ) Employed &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ( ) Self-employed
                                        <br>
                                        Please Specified:
                                        <u>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                        </u>
                                        &nbsp;&nbsp;&nbsp;&nbsp;Nature of Business:
                                        <u>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                        </u>
                                    </td>
                                </tr>
                                <tr style="height: 24pt;">
                                    <td><strong>Annual Income*</strong></td>
                                    <td colspan="3">( ) RM30,000 &amp; below &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;( )
                                        RM60,001- RM120,000 &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<br>
                                        ( ) RM30,001- RM60,000 &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ( ) Above RM120,000
                                    </td>
                                </tr>
                                <tr style="height: 24pt;">
                                    <td><strong>Source of Income*</strong></td>
                                    <td colspan="3">( ) Employment &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;( ) Inheritance
                                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                        ( ) Business &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<br>
                                        ( ) Others, please specify:
                                        <u>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                        </u>
                                    </td>
                                </tr>
                                <tr style="height: 29pt;">
                                    <td colspan="4"
                                        style="background-color: {{ $pdfColors['accent'] }}; color: {{ $pdfColors['title_bar_text'] }}">Part 2:
                                        Individual's Financial Situation
                                    </td>
                                </tr>
                                <tr style="height: 29pt;">
                                    <td colspan="2"><strong> Monthly Disposable Income</strong></td>
                                    <td colspan="2">( ) RM5,000 &amp; below <span
                                            style="
                        width: 433pt;">
                                            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>( ) RM8,001- RM15,000 <br>
                                        ( ) RM5,001- RM8,000 <span style="
                        width: 433pt;">
                                            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>( ) RM15,001 &amp; above
                                    </td>
                                </tr>
                                <tr style="height: 29pt;">
                                    <td colspan="2"><strong>Total Monthly Commitment</strong></td>
                                    <td colspan="2">( ) RM2,000 &amp; below<span
                                            style="
                        width: 433pt;">
                                            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span> ( ) RM5,001- RM10,000 <br>
                                        ( ) RM2,001- RM5,000 <span style="
                        width: 433pt;">
                                            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>( ) RM10,001 &amp; above
                                    </td>
                                </tr>
                                <tr style="height: 19pt;">
                                    <td style="width: 105pt; " colspan="2"><strong>Percentage of your Total
                                            Asset</strong></td>
                                    <td style="width: 420pt; " colspan="2">( ) Below 10% <span
                                            style="
                        width: 433pt;">
                                            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>( ) 31%- 40%
                                    </td>
                                </tr>
                                <tr style="height: 12pt;">
                                    <td style="width: 105pt; " colspan="2"></td>
                                    <td style="width: 420pt; " colspan="2">( ) 11%- 20%<span
                                            style="
                        width: 433pt;">
                                            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span> ( ) 41%- 50%
                                    </td>
                                </tr>
                                <tr style="height: 14pt;">
                                    <td style="width: 105pt; " colspan="2"><strong><br />
                                        </strong></td>
                                    <td style="width: 420pt; " colspan="2">( ) 21%- 30% <span
                                            style="
                        width: 433pt;">
                                            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>( ) 51% &amp; above
                                    </td>
                                </tr>
                                <tr style="height: 11pt;">
                                    <td colspan="4"
                                        style="background-color: {{ $pdfColors['accent'] }}; color: {{ $pdfColors['title_bar_text'] }}; width: 170pt;">Part 3: Individual's
                                        Financial
                                        Solution <br>
                                        (Multiple answers allowed)
                                    </td>
                                </tr>
                                <tr style="height: 11pt;">
                                    <td style="width: 170pt; " colspan="2"><strong>Current Portfolio</strong><br>
                                        (if any)
                                    </td>
                                    <td style="width: 355pt; " colspan="2">( ) Unit Trust / PRS Funds<u> &nbsp;
                                        </u>years
                                        <br>
                                        ( ) Trading on Bursa Malaysia<u> &nbsp; </u>years
                                    </td>
                                </tr>
                                <tr style="height: 14pt;">
                                    <td style="width: 170pt; " colspan="2">&nbsp;</td>
                                    <td style="width: 355pt; " colspan="2">( ) PRS<u> &nbsp; </u>years<span
                                            style="
                        width: 433pt;">
                                            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span> ( ) Futures / Options<u> &nbsp;
                                        </u>years
                                    </td>
                                </tr>
                                <tr style="height: 12pt;">
                                    <td style="width: 170pt; " colspan="2"><br /></td>
                                    <td style="width: 355pt; " colspan="2">( ) Property<u> &nbsp; </u>years<span
                                            style="
                        width: 433pt;">
                                            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span> ( ) Bonds<u> &nbsp; </u>years
                                    </td>
                                </tr>
                                <tr style="height: 12pt;">
                                    <td style="width: 170pt; " colspan="2"><br /></td>
                                    <td style="width: 355pt; " colspan="2"> ( ) Others:<u>
                                            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                        </u>
                                    </td>
                                </tr>
                                <tr style="height: 24pt;">
                                    <td colspan="4"
                                        style="background-color: {{ $pdfColors['accent'] }}; color: {{ $pdfColors['title_bar_text'] }}">Part 4:
                                        Individual's Knowledge
                                        Assessment
                                    </td>
                                </tr>
                                <tr style="height: 24pt;">
                                    <td>1
                                    </td>
                                    <td colspan="3">What do you expect to benefit by this Product?
                                        <br>
                                        ( ) Capital growth <span style="width: 420pt; "><span
                                                style="
                        width: 433pt;">
                                                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span></span>( ) Regular income <span
                                            style="width: 420pt; "><span
                                                style="
                        width: 433pt;">
                                                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span></span>( ) Capital protection
                                    </td>
                                </tr>
                                <tr style="height: 47pt;">
                                    <td>2
                                    </td>
                                    <td colspan="3">What is your purpose? <br>
                                        ( ) Asset accumulation<span style="width: 420pt; "><span
                                                style="
                        width: 433pt;">
                                                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span></span> ( ) Investing for regular
                                        income<span style="width: 420pt; "><span
                                                style="
                        width: 433pt;">
                                                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span></span><br>
                                        ( ) Savings for children's education fund <span style="width: 420pt; "><span
                                                style="
                        width: 433pt;">
                                                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span></span>( ) Others:
                                        <u>&nbsp;</u><u>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</u>
                                        <span style="width: 420pt; "><span
                                                style="
                        width: 433pt;">
                                                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span></span><br>
                                        ( ) Savings for retirement
                                    </td>
                                </tr>
                                <tr style="height: 36pt;">
                                    <td>3
                                    </td>
                                    <td colspan="3">What is the reason(s) for considering this Product? <br>
                                        ( ) Meet my objective <span style="width: 420pt; "><span
                                                style="
                        width: 433pt;">
                                                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span></span>( ) Meet my overall
                                        strategy (e.g. diversification) ( ) Compatible with my risk-return expectation
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

    {{-- ---------------------- Page 10 --------------------- --}}

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
                            <table width="100%" cellspacing="0" class="border">
                                <tr style="height: 15pt;">
                                    <td colspan="8" bgcolor="{{ $pdfColors['title_bar'] }}"
                                        style="background-color: {{ $pdfColors['accent'] }}; color: {{ $pdfColors['title_bar_text'] }}">Part 5:
                                        Individual's Need
                                        Analysis / Risk Profiling
                                    </td>
                                </tr>
                                <tr style="height: 37pt;">
                                    <td align="left" valign="top">1.
                                    </td>
                                    <td colspan="6" align="left" valign="top" style="width: 114pt; ">What
                                        is your current
                                        age? <br>
                                        ( ) 51 &amp; above <br>
                                        ( ) 35 to 50 <br>
                                        ( ) Below 35
                                    </td>
                                    <td align="left" valign="top" style="width: 35pt;  "> [1] <br>
                                        [3] <br>
                                        [5]
                                    </td>
                                </tr>
                                <tr style="height: 37pt;">
                                    <td align="left" valign="top">2
                                    </td>
                                    <td colspan="6" align="left" valign="top" style="width: 155pt; ">How
                                        will you classify
                                        yourself as an individual? <br>
                                        ( ) No experience <br>
                                        ( ) 1-3 years of experience <br>
                                        ( ) Very experience <br /></td>
                                    <td align="left" valign="top" style="width: 35pt;  "> [1] <br>
                                        [3] <br>
                                        [5]
                                    </td>
                                </tr>
                                <tr style="height: 37pt;">
                                    <td align="left" valign="top">3
                                    </td>
                                    <td colspan="6" align="left" valign="top" style="width: 245pt; ">Do
                                        you have any
                                        understanding? <br>
                                        ( ) No understanding at all <br>
                                        ( ) Some understanding <br>
                                        ( ) Understand fully
                                    </td>
                                    <td align="left" valign="top" style="width: 35pt;  "> [1] <br>
                                        [3] <br>
                                        [5]
                                    </td>
                                </tr>
                                <tr style="height: 37pt;">
                                    <td align="left" valign="top">4
                                    </td>
                                    <td colspan="6" align="left" valign="top" style="width: 327pt; ">What
                                        is your
                                        objective? <br>
                                        ( ) To achieve income / returns slightly better than bank savings / fixed
                                        deposit
                                        <br>
                                        ( ) To achieve income &amp; capital growth <br>
                                        ( ) To achieve capital growth
                                    </td>
                                    <td align="left" valign="top" style="width: 35pt;  "> [1] <br>
                                        [3] <br>
                                        [5]
                                    </td>
                                </tr>
                                <tr style="height: 37pt;">
                                    <td align="left" valign="top">5
                                    </td>
                                    <td colspan="6" align="left" valign="top" style="width: 245pt; ">What
                                        is the expected
                                        duration? <br>
                                        ( ) Less than 3 years <br>
                                        ( ) 3 to 5 years <br>
                                        ( ) More than 5 years
                                    </td>
                                    <td align="left" valign="top" style="width: 35pt;  "> [1] <br>
                                        [3] <br>
                                        [5]
                                    </td>
                                </tr>

                                <tr style="height: 32pt;">
                                    <td colspan="6" align="left" valign="top" style="width: 131pt; ">
                                        <strong>Recommendation:<br />
                                            <br />
                                            <br />
                                            <br />
                                        </strong>
                                    </td>
                                    <td align="left" valign="top" style="width: 96pt; "><strong>Total
                                            Points</strong></td>
                                    <td align="left" valign="top" style="width: 35pt;  "><br /></td>
                                </tr>
                                <tr style="height: 12pt;">
                                    <td colspan="2" align="left" valign="top" bgcolor="{{ $pdfColors['border'] }}" style="background-color: {{ $pdfColors['border'] }}">
                                        <strong>Total Score</strong>
                                    </td>
                                    <td colspan="2" align="left" valign="top" bgcolor="{{ $pdfColors['border'] }}" style="background-color: {{ $pdfColors['border'] }}">
                                        <strong>6-13</strong>
                                    </td>
                                    <td colspan="2" align="left" valign="top" bgcolor="{{ $pdfColors['border'] }}" style="background-color: {{ $pdfColors['border'] }}">
                                        <strong>14-22</strong>
                                    </td>
                                    <td colspan="2" align="left" valign="top" bgcolor="{{ $pdfColors['border'] }}" style="background-color: {{ $pdfColors['border'] }}">
                                        <strong>23-30</strong>
                                    </td>
                                </tr>
                                <tr style="height: 12pt;">
                                    <td colspan="2" align="left" valign="top" bgcolor="{{ $pdfColors['border'] }}" style="background-color: {{ $pdfColors['border'] }}">
                                        <strong>Risk Profile</strong>
                                    </td>
                                    <td colspan="2" align="left" valign="top" bgcolor="{{ $pdfColors['border'] }}" style="background-color: {{ $pdfColors['border'] }}">
                                        <strong>Conservative</strong>
                                    </td>
                                    <td colspan="2" align="left" valign="top" bgcolor="{{ $pdfColors['border'] }}" style="background-color: {{ $pdfColors['border'] }}">
                                        <strong>Moderate</strong>
                                    </td>
                                    <td colspan="2" align="left" valign="top" bgcolor="{{ $pdfColors['border'] }}" style="background-color: {{ $pdfColors['border'] }}">
                                        <strong>Aggressive</strong>
                                    </td>
                                </tr>
                                <tr style="height: 12pt;">
                                    <td colspan="2" rowspan="3" align="left" valign="top"> Category of
                                        Product that match the risk level
                                    </td>
                                    <td colspan="2" align="left" valign="top">Money Market
                                    </td>
                                    <td colspan="2" align="left" valign="top">Mixed Asset
                                    </td>
                                    <td colspan="2" align="left" valign="top">Mixed Asset
                                    </td>
                                </tr>
                                <tr style="height: 12pt;">
                                    <td colspan="2" align="left" valign="top">Bond
                                    </td>
                                    <td colspan="2" align="left" valign="top">Balanced
                                    </td>
                                    <td colspan="2" align="left" valign="top">Equity
                                    </td>
                                </tr>
                                <tr style="height: 12pt;">
                                    <td colspan="2" align="left" valign="top">PRS Conservative
                                    </td>
                                    <td colspan="2" align="left" valign="top">PRS Moderate
                                    </td>
                                    <td colspan="2" align="left" valign="top">PRS Growth
                                    </td>
                                </tr>
                                <tr style="height: 47pt;">
                                    <td colspan="8" align="left" valign="top">Please comment if the
                                        recommended Product(s) does not match with the risk
                                        profile.<br>
                                        <br>
                                        <br>
                                    </td>
                                </tr>
                                <tr style="height: 13pt;">
                                    <td colspan="8" align="left" valign="top" bgcolor="{{ $pdfColors['title_bar'] }}"
                                        style="background-color: {{ $pdfColors['accent'] }}; color: {{ $pdfColors['title_bar_text'] }}; width: 524pt;">Part 6: Acknowledgement<br>
                                        Please tick (<span class="s21">&#10004;</span>) to acknowledge the
                                        appropriate
                                        statement(s) provided below.
                                    </td>
                                </tr>
                                <tr style="height: 23pt;">
                                    <td align="left" valign="top">1
                                    </td>
                                    <td colspan="6" align="left" valign="top"> All information provided is
                                        true, complete and accurate. I understand that any
                                        misleading, inaccurate or incomplete information provided by me will affect the
                                        outcome of the assessment.
                                    </td>
                                    <td align="left" valign="top"><br /></td>
                                </tr>
                                <tr style="height: 23pt;">
                                    <td align="left" valign="top">2
                                    </td>
                                    <td colspan="6" align="left" valign="top"> I hereby acknowledge that I
                                        have received a copy of Product Highlights Sheet and the
                                        relevant Disclosure Document (e.g. Prospectus, Information Memorandum, etc.)
                                    </td>
                                    <td align="left" valign="top"><br /></td>
                                </tr>
                                <tr style="height: 14pt;">
                                    <td align="left" valign="top">3
                                    </td>
                                    <td colspan="6" align="left" valign="top">The consultant has explained
                                        and I have understood the terms, features and risks of the
                                        Product.
                                    </td>
                                    <td align="left" valign="top"><br /></td>
                                </tr>
                                <tr style="height: 23pt;">
                                    <td align="left" valign="top">4
                                    </td>
                                    <td colspan="6" align="left" valign="top"> I decline to provide certain
                                        information required in this assessment form. I
                                        understand that the result of the assessment will be affected by the
                                        non-disclosure
                                        of certain information.
                                    </td>
                                    <td align="left" valign="top"><br /></td>
                                </tr>
                                <tr style="height: 23pt;">
                                    <td align="left" valign="top">5
                                    </td>
                                    <td colspan="6" align="left" valign="top"> I have decided to purchase
                                        into another category of Product(s) that does not match
                                        with my risk profile and I understand the different risk involved of the
                                        Product(s).
                                    </td>
                                    <td align="left" valign="top"><br /></td>
                                </tr>
                                <tr style="height: 14pt;">
                                    <td align="left" valign="top">6
                                    </td>
                                    <td colspan="6" align="left" valign="top">I have previously done an
                                        assessment and there has been no material change in the
                                        information provided.
                                    </td>
                                    <td align="left" valign="top"><br /></td>
                                </tr>
                                <tr style="height: 77pt;">
                                    <td colspan="8" align="left" valign="top"><strong>Principal Holder's
                                            Signature</strong> <br /> <br>

                                        <img src="{{ $client->clientKYCRecord->signature_photo_url ?? '' }}"
                                            height="60" />
                                        <br />
                                        Name: <br>
                                        Date:
                                    </td>
                                </tr>
                                <tr style="height: 12pt;">
                                    <td colspan="8" align="left" valign="top" bgcolor="{{ $pdfColors['border'] }}" style="background-color: {{ $pdfColors['border'] }}">
                                        <strong>For Office Use only</strong>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="3" align="left" valign="top" style="width: 33%; ">
                                        Checked by Customer
                                        Services / PIC:<br /> <br>
                                        <br>
                                        <br /> Name:<br />
                                        Date:
                                    </td>
                                    <td colspan="2" align="left" valign="top" style="width: 33%; ">Review
                                        by Sales
                                        Leader:<br /> <br>
                                        <br>
                                        <br />
                                        Name:<br />
                                        Date:
                                    </td>
                                    <td colspan="3" align="left" valign="top" style="width: 33%; ">Review
                                        by
                                        Compliance:<br />
                                        <br>
                                        <br>
                                        <br>
                                        <br>
                                        Name:<br /> Date: <br />
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
