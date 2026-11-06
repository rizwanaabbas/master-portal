@php
    $pdfOptions = $pdfOptions ?? [];
    $pdfColors = app(\App\Services\OnboardingPdfOptionsService::class)->colors($pdfOptions);
    $labels = $pdfOptions['labels'] ?? [];
    $features = $pdfOptions['features'] ?? [];
    $suffix = $labels['legal_entity_suffix'] ?? 'Ltd.';
    $bankLegal = $bankLegal ?? (($ownCompany->name ?? 'Bank') . ($suffix ? ' ' . $suffix : ''));
    $resolvedHeading = $labels['board_resolved_heading'] ?? 'RESOLVED:';
    $directorsHeading = $labels['board_directors_heading'] ?? 'BOARD OF DIRECTORS';
    $miaText = $labels['board_mia_text'] ?? 'Master Investment Agreement';
@endphp
<!DOCTYPE html
    PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Board Resolution</title>
    <style type="text/css">
        @page {
            margin: 60px 5px 0px 5px;
        }

        .main {
            width: 600px;
            margin: 0px auto;
        }

        h1 {
            color: black;
            font-family: "Segoe UI Historic", sans-serif;
            font-style: normal;
            font-weight: bold;
            text-decoration: none;
            font-size: 11pt;
        }

        .s1 {
            color: black;
            font-family: Arial, sans-serif;
            font-style: normal;
            font-weight: bold;
            text-decoration: none;
            font-size: 11pt;
        }

        p {
            color: black;
            font-family: "Segoe UI Historic", sans-serif;
            font-style: normal;
            font-weight: normal;
            text-decoration: none;
            font-size: 11pt;
            margin: 0pt;
            text-align: justify;
            text-justify: inter-word;
        }

        .s4 {
            color: {{ $pdfColors['alert'] }};
            font-family: "Segoe UI Historic", sans-serif;
            font-style: italic;
            font-weight: normal;
            text-decoration: none;
            font-size: 11.5pt;
        }

        .s5 {
            color: black;
            font-family: "Segoe UI Historic", sans-serif;
            font-style: normal;
            font-weight: normal;
            text-decoration: none;
            font-size: 11pt;
        }

        .s6 {
            color: black;
            font-family: "Segoe UI Historic", sans-serif;
            font-style: normal;
            font-weight: normal;
            text-decoration: none;
            font-size: 10pt;
        }

        .spaced-borders {
            border-spacing: 10px;
        }
</style>
    @include('pdfs.on-boarding.partials.colors')
</head>

<body>
    <table class="main container" cellpadding="0" cellspacing="0">
        <tr>
            <td>
                <h1 style="padding-top: 3pt;text-align: center;">{{ $client->name ?? '' }}</h1>
                <h1 style="text-align: center;">(Company Number / Registration Number: <span
                        class="s1"><strong>{{ $client->company->business_reg_number ?? '' }}</strong></span>)</h1>
                <p style="text-align: left;"><br /></p>
                <p style="line-height: 14pt;text-align: justify; text-justify: inter-word;">In accordance with our
                    Company's Board of Directors&#39; Resolution in writing passed on<u>
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    </u><span class="s4">[date]</span> <span style=" color: #000;">and in accordance with the
                        Company&#39;s Constitution, we hereby</span></p>
                <p style="text-align: left;"><br /></p>
                <h1 style="text-align: left;">{{ $resolvedHeading }}</h1>
                <p style="text-align: left;"><br /></p>
                <table width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td width="50px" rowspan="5"><span style="text-align: left">(1)</span></td>
                        <td colspan="2">
                            <p>That it is in the best interests of the Company to:</p><br />
                        </td>
                    </tr>
                    <tr>
                        <td width="50px"><span style="text-align: left">(a)</span></td>
                        <td>
                            <p>open, maintain and/or hold any accounts in any currency(ies) ("<b>Accounts</b>") with
                                {{ $bankLegal }} (referred to as the "<b>Bank</b>") for and on behalf of the
                                Company subject to and in accordance with the terms and conditions governing the
                                Accounts, as may be amended from time to time by the Bank;</p><br />
                        </td>
                    </tr>
                    <tr>
                        <td width="50px"><span style="text-align: left">(b)</span></td>
                        <td>
                            <p>obtain and accept from the Bank the offer of any banking and/or electronic banking
                                service(s) or such other services of a similar nature ("<b>Services</b>") subject to and
                                in accordance with the terms and conditions of the Bank as set out in any relevant
                                application form or letter of offer or such other document having similar effect for the
                                Services;@if (!isset($features['show_board_signatory_clause']) || !empty($features['show_board_signatory_clause']) || !isset($features['show_board_mia_clause']) || !empty($features['show_board_mia_clause'])) and@else.@endif</p><br />
                        </td>
                    </tr>
                    @if (!isset($features['show_board_signatory_clause']) || !empty($features['show_board_signatory_clause']))
                    <tr>
                        <td width="50px"><span style="text-align: left">(c)</span></td>
                        <td>
                            <p>agreed to appoint <strong>{{ $client->company->contact->name ?? '' }}</strong> (name),…………….
                                (passport number) as authorised signatory to sign all the financial acts including
                                opening, maintaining and closing of bank account, applying for online banking, withdraw
                                the money and other banking acts;@if (!isset($features['show_board_mia_clause']) || !empty($features['show_board_mia_clause'])) and@else.@endif</p><br />
                        </td>
                    </tr>
                    @endif
                    @if (!isset($features['show_board_mia_clause']) || !empty($features['show_board_mia_clause']))
                    <tr>
                        <td width="50px"><span style="text-align: left">({{ (!isset($features['show_board_signatory_clause']) || !empty($features['show_board_signatory_clause'])) ? 'd' : 'c' }})</span></td>
                        <td>
                            <p>agreed to bind the Company with the Bank in the <b>{{ $miaText }}.</b></p>
                            <br />
                        </td>
                    </tr>
                    @endif
                </table>

                <br /><br /><br />
                <h1 style="text-align: center;">{{ $directorsHeading }}</h1>
                <br /><br /><br />
                <table width="100%" class="spaced-borders">
                    <tr style="height:15pt">
                        <td style="border-top-style:solid;border-top-width:0.5pt">
                            <p class="s5" style="line-height: 14pt;text-align: left;">Name:</p>
                            <p class="s5" style="line-height: 14pt;text-align: left;">Designation:</p>
                        </td>
                        <td style="">
                            <p style="text-align: left;"><br /></p>
                        </td>
                        <td style=";border-top-style:solid;border-top-width:0.5pt">
                            <p class="s5" style="line-height: 14pt;text-align: left;">Name:</p>
                            <p class="s5" style="line-height: 14pt;text-align: left;">Designation:</p>
                        </td>
                    </tr>
                </table>
                <p style="text-align: left;"><br /></p>
                <p style="padding-top: 5pt;text-align: left;">Date:</p>
                <p style="text-align: left;"><br /></p>
                @if (!isset($features['show_board_letterhead_note']) || !empty($features['show_board_letterhead_note']))
                    <p class="s6" style="padding-top: 10pt;text-align: left;">{{ $labels['board_letterhead_note'] ?? '*Please prepare on Company Letterhead & attach with the Company stamp.' }}</p>
                @endif
            </td>
        </tr>
    </table>
</body>

</html>
