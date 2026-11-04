<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>{{ $ownCompany ? $ownCompany->short_name : '' }}</title>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
        }

        #case {
            margin-top: 50px;
        }

        #case tr:nth-child(odd) {
            background-color: #f2f2f2;
        }

        #key_findings tr:nth-child(odd) {
            background-color: #f2f2f2;
        }

        #case_details {
            margin-top: 10px;
            font-size: xx-small;
        }

        #case_details th {
            background: #f2f2f2;
        }

        .text-truncate {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            max-width: 2px;
        }

        #audit tr:nth-child(odd) {
            background-color: #f2f2f2;
        }
    </style>
</head>

<body>
    <span class="pagenum"></span>

    <table width="100%" border="0" cellpadding="0" cellspacing="1" align="center" bgcolor="#ffffff"
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
                                    <td align="right" valign="top">
                                        <img src="{{ asset('assets/img/sanctions-io-logo.png') }}" alt="sanction"
                                            border="0"
                                            style="margin:0; padding:0; display:block; line-height:0; max-width:50%; height:auto">
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    <table id="case" width="100%" border="0" cellpadding="5" cellspacing="0" align="left">
        <tr>
            <td>Name</td>
            <td>{{ $case->name }}</td>
        </tr>
        <tr>
            <td>Case ID</td>
            <td>{{ $case->case_id }}</td>
        </tr>
        <tr>
            <td>Gender</td>
            <td>{{ $case->gender ? $case->gender : 'N/A' }}</td>
        </tr>
        <tr>
            <td>Entity Type</td>
            @php
                $typeMapping = ['individual' => 'Beneficiary', 'entity' => 'Beneficiary Bank'];
                $requestType = $typeMapping[$case->entity_type];
            @endphp
            <td>{{ $requestType }}</td>
        </tr>
        <tr>
            <td>Country location</td>
            <td>N/A</td>
        </tr>
        <tr>
            <td>Citizenship</td>
            <td>N/A</td>
        </tr>
        <tr>
            <td>Case Created</td>
            <td>{{ $case->created_at }}</td>
        </tr>
    </table>

    <div style="margin-top: 20px;" id="key_findings">
        <h3>KEY FINDINGS</h3>
        <table style="margin-top: 10px;" width="100%" cellspacing="0" cellpadding="2">
            <tbody>
                <tr>
                    <td>Total Matches</td>
                    <td>
                        @if (isset($case_sanctionsIo->results))
                            @if (count($case_sanctionsIo->results))
                                {{ count($case_sanctionsIo->results) }}
                            @endif
                        @endif
                    </td>
                </tr>

                <tr>
                    <td>Is Resolved</td>
                    <td>{{ $case->resolved ? 'Yes' : 'NO' }}</td>
                </tr>
                <tr>
                    <td>Is Blocked</td>
                    <td>{{ $case->blocked ? 'Yes' : 'NO' }}</td>
                </tr>

            </tbody>
        </table>
    </div>

    <div style="page-break-before: always"></div>
    <h3>Sanctions.io matches</h3>
    <div>
        <table id="case_details" width="100%" align="left" cellspacing="0" cellpadding="3">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Confidence Score</th>
                    <th>Alt Names</th>
                    <th>DOB</th>
                    <th>Place of Birth</th>
                    <th>Citizenship</th>
                    <th>Nationality</th>
                    <th>Data Source</th>
                    <th>Remarks</th>
                    <th>Source Information url</th>
                    <th>Source List url</th>
                </tr>
            </thead>
            <tbody>
                @if (isset($case_sanctionsIo->results) && count($case_sanctionsIo->results) != 0)
                    @foreach ($case_sanctionsIo->results as $item)
                        <tr>
                            <td>

                                @if (isset($case->name))
                                    {{ $case->name }}
                                @endif
                            </td>
                            <td>

                                @if (isset($item->confidence_score))
                                    {{ $item->confidence_score }}
                                @endif
                            </td>
                            <td>

                                @if (isset($item->alt_names))
                                    @foreach ($item->alt_names as $itemAltNames)
                                        <li>
                                            <span>
                                                {{ $itemAltNames }}
                                            </span>
                                        </li>
                                    @endforeach
                                @endif
                            </td>
                            <td>

                                @if (isset($item->date_of_birth))
                                    @foreach ($item->date_of_birth as $itemdate_of_birth)
                                        {{ $itemdate_of_birth }}
                                    @endforeach
                                @endif
                            </td>
                            <td>

                                @if (isset($item->place_of_birth))
                                    @foreach ($item->place_of_birth as $itemplace_of_birth)
                                        {{ $itemplace_of_birth }}
                                    @endforeach
                                @endif
                            </td>
                            <td>

                                @if (isset($item->citizenship))
                                    @foreach ($item->citizenship as $itemcitizenship)
                                        {{ $itemcitizenship }}
                                    @endforeach
                                @endif
                            </td>
                            <td>

                                @if (isset($item->nationality))
                                    @foreach ($item->nationality as $itemnationality)
                                        {{ $itemnationality }}
                                    @endforeach
                                @endif
                            </td>
                            <td>
                                @if (isset($item->data_source))
                                    @foreach ($item->data_source as $itemdata_source)
                                        <li>
                                            {{ $itemdata_source }}
                                        </li>
                                    @endforeach
                                @endif
                            </td>
                            <td class="text-truncate">
                                @if (isset($item->remarks))
                                    <p> {{ $item->remarks }}</p>
                                @endif
                            </td>
                            <td class="text-truncate">
                                @if (isset($item->source_information_url))
                                    <a href="{{ $item->source_information_url }}" style="color:#36AE7C;">
                                        <span>
                                            {{ $item->source_information_url }}
                                        </span>
                                    </a>
                                @endif
                            </td>
                            <td class="text-truncate">
                                @if (isset($item->source_list_url))
                                    <a href="{{ $item->source_list_url }}" style="color:#36AE7C;">
                                        <span>
                                            {{ $item->source_list_url }}
                                        </span>
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="12" style="text-align: center;">
                            no records found
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

    <div style="page-break-before: always"></div>
    <h3>AUDIT</h3>
    <table id="audit" width="100%" cellspacing="0" cellpadding="2">
        <tbody>
            <tr align="left">
                <th>Date</th>
                <th>Actioned by</th>
                <th>Action</th>
            </tr>
            <tr>
                <td>
                    @if ($case->resolved == 0 && $case->blocked == 0)
                        N/A
                    @else
                        {{ $case->created_at }}
                    @endif
                </td>
                <td>{{ $case->resolved != 0 && $case->resolved_by !== null ? $case->resolved_by : 'N/A' }}</td>
                <td>
                    @if ($case->resolved)
                        case resolved
                    @elseif($case->blocked)
                        case blocked
                    @else
                        N/A
                    @endif
                </td>
            </tr>
        </tbody>
    </table>

    <div style="margin-top: 100px;" id="notes">
        <h3>NOTES</h3>
        <p style="margin-top: 20px; font-size: xx-small">All timestamps in this export are in UTC and may be in variance
            with the locale time shown on the application
            <br>
            <br>
            Legal Notice <br><br>
            The contents of this record are private and confidential and should not be disclosed to third parties
            unless: (i) the terms of your agreement with Refinitiv allow you to
            do so; (ii) the record subject requests any data that you may hold on them, and such data includes their
            World-Check record; or (iii) you are under some other legal
            obligation to do so. You must consider and abide by your own obligations in relation to the data privacy
            rights of individuals and must notify them of your intention to
            search against World-Check and provide them with information contained in the World-Check privacy statement
            :<a
                style="color: #0000EE">https://www.refinitiv.com/en/products/world-check-kyc-screening/privacy-statement</a>.
            You shall not rely upon
            the content of this report without making independent checks to verify the information contained therein.
            Information correlated is necessarily brief and should be read by you in the context of the fuller details
            available in the external sources to which links are provided.
            The accuracy of the information found in the underlying sources must be verified with the record subject
            before any action is taken and you should inform us if any
            links to the sources are broken. If this record contains negative allegations, it should be assumed that
            such allegations are denied by the subject. You should not
            draw any negative inferences about individuals or entities merely because they are identified in the
            database, nor because they are shown as "Reported being linked
            to" others identified in the database. The nature of linking varies considerably. Many persons are included
            solely because they hold or have held prominent political
            positions or are connected to such individuals
        </p>
    </div>
</body>

</html>
