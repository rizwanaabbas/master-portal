@php
    $pdfOptions = $pdfOptions ?? [];
    $pdfColors = app(\App\Services\OnboardingPdfOptionsService::class)->colors($pdfOptions);
    $labels = $pdfOptions['labels'] ?? [];
    $features = $pdfOptions['features'] ?? [];
    $suffix = $labels['legal_entity_suffix'] ?? 'Ltd.';
    $bankLegal = $bankLegal ?? (($ownCompany->name ?? 'Bank') . ($suffix ? ' ' . $suffix : ''));
    $loiTitle = $labels['loi_title'] ?? 'LETTER OF INDEMNITY';
    $salutation = $labels['loi_salutation'] ?? 'Dear Sir,';
    $acctLabel = ($client->type ?? '') === 'company'
        ? ($labels['company_account_label'] ?? 'Corporate')
        : ($labels['individual_account_label'] ?? 'Individual Investment Account');
@endphp
<!DOCTYPE html
    PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>{{ $loiTitle }}</title>
    <style type="text/css">
        @page {
            margin: 90px 5px 0px 5px;
        }

        .main {
            width: 600px;
            margin: 0px auto;
        }

        p {
            color: black;
            font-family: "Segoe UI", sans-serif;
            font-style: normal;
            font-weight: normal;
            text-decoration: none;
            font-size: 10pt;
            margin: 0pt;
        }

        .letter-paragraph {
            margin-bottom: 35px;
        }

        h1 {
            color: black;
            font-family: "Segoe UI", sans-serif;
            font-style: normal;
            font-weight: bold;
            text-decoration: none;
            font-size: 10pt;
        }

        .s2 {
            color: black;
            font-family: "Segoe UI", sans-serif;
            font-style: italic;
            font-weight: normal;
            text-decoration: none;
            font-size: 10pt;
        }

        .s3 {
            color: black;
            font-family: "Segoe UI", sans-serif;
            font-style: italic;
            font-weight: normal;
            text-decoration: none;
            font-size: 11pt;
        }
</style>
    @include('pdfs.on-boarding.partials.colors')
</head>

<body>
    <table class="main">
        <tr>
            <td>
                <p style="margin-top: 90px;text-indent: 0pt;text-align: center;">{{ $loiTitle }}</p>
                <p style="text-indent: 0pt;text-align: left;"><br /><br /></p>
            </td>
        </tr>
        <tr>
            <td>
                <table width="100%">
                    <tr>
                        <td width="70%">
                            <p style="text-indent: 0pt;line-height: 13pt;text-align: left;">To: <b
                                    class="text-uppercase">{{ strtoupper($bankLegal) }}</b></p>
                            <p style="text-indent: 0pt;text-align: left; margin-left: 23px;">
                                @php $addr = $ownCompany->address ?? ''; @endphp
                                @if (strlen($addr) > 80)
                                    {!! substr($addr, 0, 37) . '<br />' . substr($addr, 37, 23) . '<br />' . substr($addr, 60) !!}
                                @elseif (strlen($addr) > 37)
                                    {!! substr($addr, 0, 37) . '<br />' . substr($addr, 37) !!}
                                @else
                                    {{ $addr }}
                                @endif
                            </p>
                            <p style="text-indent: 0pt;text-align: left;"><br /></p>
                        </td>
                        <td style="text-indent: 0pt; text-align: left; vertical-align: top;">
                            <p>Date:</p>
                        <td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td style="border-bottom: 1px solid black;">
                <p style="text-indent: 0pt;text-align: left;">{{ $salutation }}</p>
                <p style="padding-top: 10pt;text-indent: 0pt;line-height: 83%;text-align: left;">Name of Account:
                    <strong>{{ $acctLabel }}</strong>
                </p>
                <p>Account Applicant's Name: <strong>{{ $client->name ?? '' }}</strong></p>
                <p style="text-indent: 0pt;text-align: left;"><br /></p>
            </td>
        </tr>
        <tr>
            <td>
                <p style="text-indent: 0pt;line-height: 1pt;text-align: left;"></p>
                <p class="letter-paragraph"
                    style="padding-top: 7pt;text-indent: 0pt;line-height: 150%;text-align: justify;">I/We hereby
                    authorize {{ $bankLegal }} or any of its officers to act in
                    accordance with my/our
                    instructions given via the telephone, facsimile, email, postal mail or any other mode of
                    communication from time to time in respect of all transactions in relation to the above account,
                    including but not limited to instructions for placement, deposit, withdrawal or transfer of all or
                    any moneys in the said account. The Bank and its officers shall be under no duty to verify and check
                    the accuracy and correctness of the and contents and/or detect errors in the contents of such
                    instructions received and notwithstanding the fact that the identity of the person issuing such
                    instructions and/or the sources of the instruction cannot be verified or authenticated by Bank and
                    its officers.</p>
                <p class="letter-paragraph" style="text-indent: 0pt;line-height: 150%;text-align: justify;">I/We hereby
                    agree that the Bank and any of its officers may, in its reasonable discretion, decline to act on or
                    in accordance with the whole or any part of any such instructions received in the event that Bank
                    and any of its officers are of the view that such instructions communicated are suspicious in
                    nature.</p>
                <p class="letter-paragraph" style="text-indent: 0pt;line-height: 150%;text-align: justify;">I/We hereby
                    authorize the Bank and/ or any of its officers to communicate to me/us with our authorized
                    signatories by such mode of communication that Bank deems necessary relating to my/our instructions
                    and/or transactions referred to above.</p>
                <p class="letter-paragraph"
                    style="padding-top: 3pt;text-indent: 0pt;line-height: 150%;text-align: justify;">I/We am/are fully
                    aware and understand the various problems and risks of misunderstandings, unauthorized alterations
                    or instructions errors and risk of operational failure involved in me/us sending my/our instructions
                    to you by way of telephone/ facsimile/email/ postal mail/ any mode of communication and the Bank
                    receiving your instructions and hereby agree to accept full and total responsibility for all the
                    above mentioned transactions and instructions and risks associated with the abovementioned
                    transactions and instructions.</p>
            </td>
        </tr>
        <tr style="page-break-after: always;">
            <td>
                <p class="letter-paragraph" style="text-indent: 0pt;line-height: 150%;text-align: justify;">The Bank and
                    its officers shall not be responsible for ensuring the authenticity, validity, proprietary or
                    sources of such telephone, facsimile, email, postal mail or any mode of communication and shall not
                    be liable in the event the same turns out to be unauthorized, erroneous, improper or fraudulent.</p>
                <p class="letter-paragraph" style="text-indent: 0pt;line-height: 150%;text-align: justify;">Whereas at
                    our request and upon the express condition that this instrument be executed, the Bank has agreed
                    upon our request, until further notice, to act on or execute our instructions which are transmitted
                    to you via telephone and/or facsimile and/or email and/ or via postal mail and/ or any other mode of
                    communication.</p>
                <p class="letter-paragraph" style="text-indent: 0pt;line-height: 150%;text-align: justify;">Now,
                    therefore in consideration of the above, I/we hereby agree and undertake that I/we shall at all
                    times hereafter unconditionally indemnify the Bank and keep the Bank indemnified from and against
                    any and/or all liability, loss, costs, damages, fees and expenses of whatever kind of nature which
                    the Bank may sustain or incur by reason of or in consequence of receiving, acting in accordance with
                    or declining the instructions contemplated herein or for any reasons whatsoever in relation to the
                    above accounts.</p>
                <p class="letter-paragraph" style="text-indent: 0pt;line-height: 150%;text-align: justify;">For the
                    avoidance of doubt, it is expressly agreed that the Bank is under no obligation whatever to check
                    the authenticity of such telephone and/or facsimile and/or email and/or postal mail and/or any mode
                    of communication. I/We will undertake to send you the original documents as a follow up towards our
                    telephone and/or facsimile and/or email, and/or any other mode of communication.</p>
                <p class="letter-paragraph"
                    style="padding-top: 3pt;text-indent: 0pt;line-height: 150%;text-align: justify;">The Bank's
                    authority to accept telephone and/or facsimile and/or email, and/or postal mail and/or any other
                    mode of instructions is continuing and shall remain in force until a written termination notice from
                    me/us terminating such authority has been received by the Bank and confirmed by the Bank to be in
                    order.</p>
            </td>
        </tr>
        <tr>
            <td>
                <table width="100%" style="border-spacing: 60px;">
                    <tr>
                        <td style="padding-top: 6pt;border-top: 1.3pt solid black; width: 50%; vertical-align: top;">
                            <h1 style="text-indent: 0pt;text-align: left;">Authorized Signature</h1>
                            <br />
                            <p style="padding-top: 5pt;text-indent: 0pt;text-align: left;">Authorized Signature Name:
                            </p>
                            <p style="text-indent: 0pt;text-align: left;"><br /></p>
                            <p style="text-indent: 0pt;text-align: left;">Date:</p>
                            <p style="text-indent: 0pt;text-align: left;"><br /></p>
                            @if (!isset($features['show_loi_company_position']) || !empty($features['show_loi_company_position']))
                                <p style="text-indent: 0pt;text-align: left;">*Position In Company:</p>
                            @endif
                        </td>
                        <td style="border-top: 1.3pt solid black;">
                            <h1 style="padding-top: 3pt;text-indent: 0pt;text-align: left;">Authorized Signature</h1>
                            <br />
                            <p style="padding-top: 5pt;text-indent: 0pt;text-align: left;">Authorized Signature Name:
                            </p>
                            <p style="text-indent: 0pt;text-align: left;"><br /></p>
                            <p style="text-indent: 0pt;text-align: left;">Date:</p>
                            <p style="text-indent: 0pt;text-align: left;"><br /></p>
                            @if (!isset($features['show_loi_company_position']) || !empty($features['show_loi_company_position']))
                                <p style="text-indent: 0pt;text-align: left;">*Position In Company:</p>
                            @endif
                            <br /><br /><br /><br />
                        </td>
                    </tr>
                    @if (!isset($features['show_loi_witness']) || !empty($features['show_loi_witness']))
                    <tr>
                        <td style="border-top: 0.5pt solid black;">
                            <p style="padding-top: 7pt;text-indent: 0pt;line-height: 169%;text-align: left;">Witness
                                Signature</p>
                            <p style="text-indent: 0pt;text-align: left;"><br /></p>
                            <p style="text-indent: 0pt;line-height: 12pt;text-align: left;">Name:</p>
                            <p style="text-indent: 0pt;text-align: left;"><br /></p>
                            <p style="text-indent: 0pt;line-height: 12pt;text-align: left;">Date:</p>
                            <p style="text-indent: 0pt;text-align: left;"><br /></p>
                            <p style="text-indent: 0pt;line-height: 12pt;text-align: left;">I/C / Passport Number:</p>
                            <p class="s2"
                                style="padding-top: 1pt;text-indent: 0pt;line-height: 15px;text-align: left;">
                                <i>*(Applicable to Company's Account only)</i>
                            </p>
                        </td>
                        <td>
                        </td>
                    </tr>
                    @endif
                </table>
            </td>
        </tr>
        <tr>
            <td>
                @if (!isset($features['show_loi_letterhead_note']) || !empty($features['show_loi_letterhead_note']))
                    <p class="s3" style="text-indent: 0pt;text-align: left;"><i>{{ $labels['loi_letterhead_note'] ?? "*For Corporate Investment Account, letter should be printed on Company's letter head" }}</i></p>
                @endif
                <p class="s3" style="padding-top: 2pt;text-indent: 0pt;text-align: left;"><i>{{ $labels['loi_delete_note'] ?? '*Delete which not applicable.' }}</i></p>
            </td>
        </tr>
    </table>
</body>

</html>
