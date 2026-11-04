@extends('layouts.cp')
@section('title', 'Case - Compliance Management')
@section('content')
    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Compliance Management</span>
        </h1>
        <a href="{{ url('/dashboards/compliance-management/cases') }}" class="btn btn-primary mr-3"><i
                class="fal fa-arrow-alt-left"></i> Go
            Back </a>
    </div>
    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false"
        data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                Case
            </h2>
        </div>
        <div id="tabs">
            <ul class="nav nav-tabs nav-tabs-clean" role="tablist">
                {{--   <li class="nav-item"><a class="nav-link" data-toggle="tab" id="sanction_io_tab"
                        href="#sanctions-io-response" role="tab" aria-selected="false">Sanctions.io Response
                        @if (isset($case_sanctionsIo->results) && count($case_sanctionsIo->results))
                            {{ count($case_sanctionsIo->results) }}
                        @endif
                    </a></li> --}}
                <li class="nav-item"><a class="nav-link" data-toggle="tab" id="worldcheck_tab" href="#worldcheck-response"
                        role="tab" aria-selected="false">Worldcheck Response @if (isset($case_worldCheck->results))
                            @if (count($case_worldCheck->results))
                                {{ count($case_worldCheck->results) }}
                            @endif
                        @endif
                    </a></li>
                <li class="nav-item"><a class="nav-link" data-toggle="tab" id="worldcheck_passport_tab"
                        href="#worldcheck-passport-response" role="tab" aria-selected="false">Worldcheck Passport
                        Response
                        @if (isset($case_worldCheck_passport->results))
                            @if (count($case_worldCheck_passport->results))
                                {{ count($case_worldCheck_passport->results) }}
                            @endif
                        @endif
                    </a></li>
                @can('resolve case')
                    @if ($case->is_suspicious && !$case->resolved)
                        <li class="nav-item ml-auto mr-4 mt-1">
                            <form id="resolve-form" method="post"
                                action="{{ url('dashboards/compliance-management/cases/resolve/' . $case->id) }}">
                                @csrf
                                <button type="button" onclick="resolveCase()" class="btn btn-success"
                                    id="approveButton">Resolve </button>
                            </form>
                        </li>
                        @if (!$case->blocked)
                            <li class="nav-item  mr-4 mt-1">
                                <form id="block-form" method="post"
                                    action="{{ url('dashboards/compliance-management/cases/block/' . $case->id) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-danger" id="blockButton">Block</button>
                                </form>
                            </li>
                        @endif
                    @endif
                @endcan
            </ul>
            <div class="tab-content">
                {{-- <div class="tab-pane fade active show" id="sanctions-io-response" role="tabpanel"
                    aria-labelledby="sanctions-io-response">
                    <div class="panel-container show mb-3">
                        <div class="panel-content" style="overflow: scroll;">
                            @if ($case_sanctionsIo)
                                <div style="margin-bottom: 15px;">
                                    <button onclick="viewResponse();" type="button"
                                        class="btn btn-outline-secondary btn-sm">View
                                        Response</button>
                                </div>
                            @endif
                            @include('common.error-message')
                            <table class="table table-bordered table-hover m-0 shadow" id="data-grid">
                                <thead class="thead-themed">
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
                                        <th>Entity Number</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if (isset($case_sanctionsIo->results) && count($case_sanctionsIo->results) != 0)
                                        @foreach ($case_sanctionsIo->results as $item)
                                            <tr>
                                                <td>

                                                    @if (isset($item->name))
                                                        {{ $item->name }}
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
                                                <td>
                                                    @if (isset($item->remarks))
                                                        {{ $item->remarks }}
                                                    @endif
                                                </td>
                                                <td>
                                                    @if (isset($item->source_information_url))
                                                        <a href="{{ $item->source_information_url }}"
                                                            style="color:#36AE7C;">
                                                            <span>
                                                                {{ $item->source_information_url }}
                                                            </span>
                                                        </a>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if (isset($item->source_list_url))
                                                        <a href="{{ $item->source_list_url }}" style="color:#36AE7C;">
                                                            <span>
                                                                {{ $item->source_list_url }}
                                                            </span>
                                                        </a>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if (isset($item->entity_number))
                                                        {{ $item->entity_number }}
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
                            <div class="d-flex justify-content-end mt-3">
                                <p class="text-muted fs-base">
                                    Powered by <span class="lseg-color">LSEG</span> World-Check
                                    One</p>
                            </div>
                        </div>
                    </div>
                </div> --}}
                <div class="tab-pane fade active show" id="worldcheck-response" role="tabpanel"
                    aria-labelledby="worldcheck-response">
                    <div class="panel-container show mb-3">
                        <div class="panel-content" style="overflow: scroll;">
                            @if ($case_worldCheck)
                                <div style="margin-bottom: 15px;">
                                    {{--     @if (isset($case_worldCheck->caseSystemId))
                                        <a href="{{ route('redirect.deep.link.case', ['case_id' => $case_worldCheck->caseSystemId, 'endPoint' => 'worldcheck']) }}"
                                            target="_blank" class="btn btn-outline-secondary btn-sm">View on WorldCheck</a>
                                        <a href="{{ route('redirect.deep.link.case', ['case_id' => $case_worldCheck->caseSystemId, 'endPoint' => 'passport']) }}"
                                            target="_blank" class="btn btn-outline-secondary btn-sm"
                                            style="margin-left: 20px;">View Passport</a>
                                    @endif --}}
                                </div>
                            @endif
                            @include('common.error-message')
                            <table
                                class="table
                                            table-bordered table-hover m-0 shadow"
                                id="data-grid-worldcheck">
                                <thead class="thead-themed">
                                    <tr>
                                        <th>Name</th>
                                        <th>Matched Alias</th>
                                        <th>Match Score</th>
                                        <th>Match Strength</th>
                                        <th>Type</th>
                                        <th>Gender</th>
                                        <th>Category</th>
                                        <th>Entity Creation Date</th>
                                        <th>Pep Status</th>
                                        <th>Result ID</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if (isset($case_worldCheck->results) && count($case_worldCheck->results) != 0)
                                        @foreach ($case_worldCheck->results as $item)
                                            <tr>
                                                <td>

                                                    @if (isset($case_worldCheck->name))
                                                        {{ $case_worldCheck->name }}
                                                    @endif
                                                </td>
                                                <td>
                                                    {{ $item->matchedTerm }}
                                                </td>
                                                <td>

                                                    @if (isset($item->matchScore))
                                                        {{ $item->matchScore }}
                                                    @endif
                                                </td>
                                                <td>

                                                    @if (isset($item->matchStrength))
                                                        {{ $item->matchStrength }}
                                                    @endif
                                                </td>
                                                <td>
                                                    @if (isset($item->categories))
                                                        @foreach ($item->categories as $itemcategories)
                                                            <li>
                                                                {{ $itemcategories }}
                                                            </li>
                                                        @endforeach
                                                    @endif
                                                </td>
                                                <td>

                                                    @if (isset($item->gender))
                                                        {{ $item->gender }}
                                                    @endif
                                                </td>
                                                <td>

                                                    @if (isset($item->category))
                                                        {{ $item->category }}
                                                    @endif
                                                </td>
                                                <td>
                                                    @if (isset($item->entityCreationDate))
                                                        {{ \Carbon\Carbon::createFromFormat('Y-m-d\TH:i:s.u\Z', $item->entityCreationDate)->format('d/m/Y') }}
                                                    @endif
                                                </td>
                                                <td>
                                                    @if (isset($item->pepStatus))
                                                        {{ $item->pepStatus }}
                                                    @endif
                                                </td>
                                                <td>

                                                    @if (isset($item->resultId))
                                                        {{ $item->resultId }}
                                                    @endif
                                                </td>

                                            </tr>
                                        @endforeach
                                    @else
                                        <tr>
                                            <td colspan="10" style="text-align: center;">
                                                no records found
                                            </td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                            <div class="d-flex justify-content-end mt-3">
                                <p class="text-muted fs-base">
                                    Powered by <span class="lseg-color">LSEG</span> World-Check
                                    One</p>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="tab-pane fade" id="worldcheck-passport-response" role="tabpanel"
                    aria-labelledby="worldcheck-passport-response">
                    <div class="panel-container show mb-3">
                        <div class="panel-content" style="overflow: scroll;">
                            @include('common.error-message')

                            <div class="modal-body">
                                <table class="table table-bordered table-hover m-0 shadow">
                                    <thead class="thead-dark">
                                        <tr>
                                            <th width="150">Name</th>
                                            <td>{{ $case->name }}</td>
                                        </tr>
                                        <tr>
                                            <th>Gender</th>
                                            <td>{{ $case->gender }}</td>
                                        </tr>
                                        <tr>
                                            <th>Issue State</th>
                                            <td>{{ $case->passport_issuing_state }}</td>
                                        </tr>
                                        <tr>
                                            <th>Nationality</th>
                                            <td>{{ $case->nationality_iso2 }}</td>
                                        </tr>
                                        <tr>
                                            <th>Date of Birth</th>
                                            <td>{{ $case->dob }}</td>
                                        </tr>
                                        <tr>
                                            <th>Passport/ID Number</th>
                                            <td><span>{{ $case->passport_number }}</span></td>
                                        </tr>
                                        <tr>
                                            <th>Date of expiry</th>
                                            <td><span>{{ $case->passport_expiry_date }}</span></td>
                                        </tr>
                                        <tr>
                                            <th>Passport Report</th>
                                            <td>
                                                @if ($case_worldCheck_passport)
                                                    {{--    <div style="margin-bottom: 15px;">
                                                    <button onclick="viewResponseWorldCheckPassport();" type="button"
                                                        class="btn btn-outline-secondary btn-sm">View
                                                        Response</button>
                                                </div> --}}
                                                    <div class="row">
                                                        <div class="col-md-12">
                                                            @isset($case->report_url)
                                                                <a href="{{ $case->report_url }}"
                                                                    class="btn btn-sm btn-outline-secondary" target="_blank"><i
                                                                        class="fal fa-file-alt"></i> View
                                                                    Report</a>
                                                            @elseif (\App\Services\WorldCheckConfigService::isEnabled())
                                                                <button
                                                                    onclick="worldCheckReportGetStatusAndSave(); return false;"
                                                                    class="btn btn-primary">Request Report</button>
                                                            @endisset
                                                        </div>
                                                    </div>
                                                @else
                                                    <div style="text-align: center;">
                                                        no records found
                                                    </div>
                                                @endif
                                            </td>
                                        </tr>
                                        {{--    <tr>
                                                <th>First Line</th>
                                                <td><span>{{ $case_worldCheck_passport->lines[0] }}</span></td>
                                            </tr>
                                            <tr>
                                                <th>Second Line</th>
                                                <td><span>{{ $case_worldCheck_passport->lines[1] }}</span></td>
                                            </tr>
                                            <tr>
                                                <th>Is Conform ICAO</th>
                                                <td><span>
                                                        @if ($case_worldCheck_passport->isConformICAO)
                                                            True
                                                        @else
                                                            False
                                                        @endif
                                                    </span></td> </tr> --}}
                                    </thead>
                                </table>
                                <div class="d-flex justify-content-end mt-3">
                                    <p class="text-muted fs-base">
                                        Powered by <span class="lseg-color">LSEG</span> World-Check One
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
    {{--     <div class="modal fade" id="response-modal" tabindex="-1" role="dialog" aria-hidden="true">

        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title ">Sanctions.io Response Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true"><i class="fal fa-times"></i></span>
                    </button>
                </div>
                <div class="modal-body">
                    @php
                        $prettyJsonData = json_encode($case_sanctionsIo, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                    @endphp
                    <pre>
                    {{ $prettyJsonData }}
                </pre>
                </div>
            </div>
        </div>
    </div> --}}
    <div class="modal fade" id="response-worldCheck-modal" tabindex="-1" role="dialog" aria-hidden="true">

        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title ">WorldCheck Response Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true"><i class="fal fa-times"></i></span>
                    </button>
                </div>
                <div class="modal-body">
                    @php
                        $prettyJsonDataWorldCheck = json_encode(
                            $case_worldCheck,
                            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE,
                        );
                    @endphp
                    <pre>
                        {{ $prettyJsonDataWorldCheck }}
                    </pre>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="response-worldCheck-passport-modal" tabindex="-1" role="dialog" aria-hidden="true">

        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title ">WorldCheck Passport Response Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true"><i class="fal fa-times"></i></span>
                    </button>
                </div>
                <div class="modal-body">
                    @php
                        $prettyJsonDataWorldCheckPassport = json_encode(
                            $case_worldCheck_passport,
                            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE,
                        );
                    @endphp
                    <pre>
                        {{ $prettyJsonDataWorldCheckPassport }}
                    </pre>
                </div>
            </div>
        </div>
    </div>

    <form method="POST" name="worldCheck-report-get-status" id="worldCheck_report_get_status_and_save"
        action="{{ url('/client-management/clients/worldCheck-screening-report-get-status-and-save') }}">
        @csrf
        <input type="hidden" name="case_id" id="case_id" value="{{ $case->id }}">
    </form>

@stop

@section('scripts')
    <script>
        $(document).ready(function() {
            $('#data-grid').dataTable({
                responsive: true
            });

            $('#data-grid-worldcheck').dataTable({
                responsive: true
            });
            $('#data-grid-worldcheck-passport').dataTable({
                responsive: true
            });
        });

        function viewResponse() {
            $("#response-modal").modal('show');
        }

        function viewResponseWorldCheck() {
            $("#response-worldCheck-modal").modal('show');
        }

        function viewResponseWorldCheckPassport() {
            $("#response-worldCheck-passport-modal").modal('show');
        }

        function resolveCase() {
            var isDisabled = document.getElementById('approveButton').disabled;

            if (!isDisabled) {
                Swal.fire({
                    title: "Are you sure?",
                    text: "Case will be resolved",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonText: "Yes, Resolve it!"
                }).then(function(result) {
                    if (result.value) {
                        $('#loading-spinner').show();
                        document.getElementById('resolve-form').submit()
                    }
                });
            }

        }


        function worldCheckReportGetStatusAndSave() {
            Swal.fire({
                title: "Are you sure?",
                text: "The Client Report will be requested.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Yes, request it!"
            }).then(function(result) {
                if (result.value) {
                    $('#loading-spinner').show();
                    document.getElementById('worldCheck_report_get_status_and_save').submit()
                }
            });
        }
    </script>
@stop
