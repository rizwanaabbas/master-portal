@extends('layouts.cp')
@section('title', 'Case - Compliance Management')
@section('content')
    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Compliance Management</span>
        </h1>
        <a href="{{ url('/dashboards/compliance-management/transactions-cases') }}" class="btn btn-primary mr-3"><i
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
            {{-- <ul class="nav nav-tabs nav-tabs-clean" role="tablist">
                <li class="nav-item"><a class="nav-link" data-toggle="tab" id="sanction_io_tab"
                        href="#sanctions-io-response" role="tab" aria-selected="false">Sanctions.io Response
                        @if (isset($case_sanctionsIo->results))
                            @if (count($case_sanctionsIo->results))
                                {{ count($case_sanctionsIo->results) }}
                            @endif
                        @endif
                    </a></li> --}}

            {{--  @can('resolve case')
                    @if ($case->is_suspicious && !$case->resolved)
                        <li class="nav-item ml-auto mr-4 mt-1">
                            <form id="resolve-form" method="post"
                                action="{{ url('dashboards/compliance-management/transactions-detailed-case/reslove/' . $id) }}">
                                @csrf
                                <button type="button" onclick="resolveCase()" class="btn btn-success"
                                    id="approveButton">Resolve </button>
                            </form>
                        </li>
                        @if (!$case->blocked)
                            <li class="nav-item  mr-4 mt-1">
                                <form id="block-form" method="post"
                                    action="{{ url('dashboards/compliance-management/transactions-detailed-case/block/' . $id) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-danger" id="blockButton">Block</button>
                                </form>
                            </li>
                        @endif
                    @endif
                @endcan --}}
            {{-- </ul> --}}

            <div class="tab-content">
                <div class="tab-pane fade active show" id="sanctions-io-response" role="tabpanel"
                    aria-labelledby="sanctions-io-response">
                    <div class="panel-container show mb-3">
                        <div class="panel-content" style="overflow: scroll;">
                            @if ($case_sanctionsIo)
                                <div style="margin-bottom: 15px;">
                                    {{-- <button onclick="viewResponse();" type="button"
                                        class="btn btn-outline-secondary btn-sm">View
                                        Response</button> --}}
                                    <a class="btn btn-danger text-white"
                                        href="{{ url('/dashboards/compliance-management/transactions/pdf/' . $case->id) }}">
                                        Export to PDF
                                    </a>
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
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    {{-- <div class="modal fade" id="response-modal" tabindex="-1" role="dialog" aria-hidden="true">

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
@stop
@section('scripts')
    <script>
        $(document).ready(function() {
            $('#data-grid').dataTable({
                responsive: true
            });
        });

        function viewResponse() {
            $("#response-modal").modal('show');
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
                        document.getElementById('resolve-form').submit()
                    }
                });
            }

        }
    </script>
@stop
