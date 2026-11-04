@extends('layouts.cp')
@section('title', 'Onboarding Fee Report - Reports Management')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Reports Management</span>
        </h1>
    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false"
        data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                Onboarding Fee Report
            </h2>

        </div>

        <div class="panel-container show mb-3">
            <div class="panel-content" style="overflow: scroll;">
                @include('common.error-message')

                {{--                 <form name="search" id="search" method="post"
                    action="{{ url('/reports-management/client/onboarding-fee-report') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="row p-4">
                        <div class="col-md-4 bg-secondary text-white p-3">
                            <div class="form-group">
                                <label class="form-label" for="report_date">From Date</label>
                                <input type="text" autocomplete="off" class="form-control" name="report_date"
                                    value="{{ $request['report_date'] }}" id="report_date" placeholder="Select date">
                            </div>
                        </div>
                        <div class="col-md-3 bg-secondary text-white p-3">
                            <button onclick="searchReport(); return false;" class="btn btn-primary mt-4"><i
                                    class="fal fa-print-search"></i> Search</button>
                            <button onclick="resetForm(); return false;" class="btn btn-primary mt-4">Clear</button>

                        </div>
                        <div class="col-md-5 bg-secondary d-flex justify-content-end text-white p-3">
                        </div>
                    </div>
                </form> --}}
                <table class="table table-bordered table-hover m-0 shadow"
                    @if ($reports->count() > 0) id="data-grid" @endif>
                    <thead class="thead-themed">
                        <tr>
                            <th width="30"></th>
                            <th>Client Name</th>
                            <th>Client Type</th>
                            <th>Reference number</th>
                            <th>Amount</th>
                            <th>Currency</th>
                            <th>Status</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if ($reports->count())
                            @foreach ($reports as $report)
                                @php
                                    switch ($report->transaction_status) {
                                        case 'Success':
                                            $badgeClass = 'success';
                                            break;

                                        case 'Waiting For 3D Payment':
                                            $badgeClass = 'primary';
                                            break;

                                        default:
                                            $badgeClass = 'danger';
                                            break;
                                    }
                                @endphp
                                <tr>
                                    <td align="center" scope="row">
                                        <a
                                            href="{{ url('/reports-management/client/onboarding-fee-report/' . $report->client_id) }}"><i
                                                class="fal fa-tv"></i></a>
                                    </td>
                                    <td nowrap="nowrap">{{ $report->client->name }}</td>
                                    <td>{{ $report->client->type }}</td>
                                    <td>{{ $report->reference_number ?? '--' }}</td>
                                    <td>{{ $report->transaction_amount }}</td>
                                    <td>{{ $report->currency_code }}</td>

                                    <td>

                                        @if ($report->transaction_status != null)
                                            <span class="badge badge-{{ $badgeClass }}">
                                                {{ $report->transaction_status }}
                                            </span>
                                        @else
                                            <span> -- </span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($report->transaction_error_description != null)
                                            {{ $report->transaction_error_description }}
                                        @else
                                            <span> -- </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="6" align="center">No records found.</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@stop
@section('scripts')
    <script>
        var controls = {
            leftArrow: '<i class="fal fa-angle-left" style="font-size: 1.25rem"></i>',
            rightArrow: '<i class="fal fa-angle-right" style="font-size: 1.25rem"></i>'
        }

        $(document).ready(function() {
            $('#data-grid').dataTable({
                responsive: true,
                dom: "<'row mb-3'<'col-sm-12 col-md-12 d-flex align-items-center justify-content-end'B>>" +
                    "<'row mb-3'<'col-sm-12 col-md-6 d-flex align-items-center justify-content-start'f><'col-sm-10 col-md-6 pb-1 d-flex align-items-center justify-content-end'l>>" +
                    "<'row'<'col-sm-12'tr>>" +
                    "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
                buttons: [{
                    extend: 'excelHtml5',
                    text: 'Export to Excel',
                    titleAttr: 'Generate Excel',
                    className: 'btn-outline-success btn-sm mr-1'
                }]
            });

            $('.js-thead-colors a').on('click', function() {
                var theadColor = $(this).attr("data-bg");
                $('#dt-basic-example thead').removeClassPrefix('bg-').addClass(theadColor);
            });

            $('.js-tbody-colors a').on('click', function() {
                var theadColor = $(this).attr("data-bg");
                $('#dt-basic-example').removeClassPrefix('bg-').addClass(theadColor);
            });

            $('#report_date').datepicker({
                format: 'dd/mm/yyyy',
                todayHighlight: true,
                orientation: "bottom left",
                templates: controls
            });

            $('#to_date').datepicker({
                format: 'dd/mm/yyyy',
                todayHighlight: true,
                orientation: "bottom left",
                templates: controls,
                endDate: "{{ \Carbon\Carbon::today()->format('d/m/Y') }}"
            });

        });

        function resetForm() {
            return window.location.replace(
                "{{ url('/reports-management/client/onboarding-fee-report') }}");
        }

        function searchReport() {
            if ($("#report_date").val() == "") {
                alert("Please select Report Date");
                return false;
            }

            $("#search").attr("action",
                "{{ url('/reports-management/client/onboarding-fee-report') }}");
            $("#search").submit();
        }
    </script>
@stop
