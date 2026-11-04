@extends('layouts.cp')
@section('title', 'Maintenance Fee Report - Reports Management')

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
                Maintenance Fee Report
            </h2>

        </div>

        <div class="panel-container show mb-3">
            <div class="panel-content" style="overflow: scroll;">
                @include('common.error-message')

                <form name="search" id="search" method="post"
                    action="{{ url('/reports-management/maintenance-fee-report') }}" enctype="multipart/form-data">
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
                            {{-- <button onclick="chargeMaintenanceFee(); return false;" class="btn btn-primary mt-4"><i
                                    class="fal fa-badge-dollar"></i> Charge Fee </button> --}}

                        </div>
                    </div>
                </form>
                <table class="table table-bordered table-hover m-0 shadow"
                    @if ($reports->count() > 0) id="data-grid" @endif>
                    <thead class="thead-themed">
                        <tr>
                            {{-- <th width="30">S.NO</th> --}}
                            <th width="400">Client Name</th>
                            <th>Client Type</th>
                            <th>Acc Currency</th>
                            <th>Acc number</th>
                            <th>Amount</th>
                            <th>Date</th>
                            <th>Paid</th>
                            <th>Pending Months</th>
                            {{-- <th>Date</th> --}}
                        </tr>
                    </thead>
                    <tbody>
                        @if ($reports->count())
                            @php
                                $havePaidMapping = [0 => 'No', 1 => 'Yes'];
                                $badgeMapping = [0 => 'danger', 1 => 'success'];
                            @endphp

                            @foreach ($reports as $report)
                                <tr>
                                    {{-- <td align="center" scope="row">{{ $loop->iteration }}</td> --}}
                                    <td>{{ $report->client->name }}</td>
                                    <td>{{ $report->client->type }}</td>
                                    <td>{{ $report->account ? $report->account->currency->short : '--' }}</td>
                                    <td>{{ $report->account ? $report->account->account_number : '--' }}</td>
                                    <td>{{ ($report->have_paid ? '' : '-') . number_format($report->amount, 2) }}</td>
                                    <td>{{ $report->date_charged }}</td>
                                    <td>
                                        <span class="badge {{ 'badge-' . $badgeMapping[$report->have_paid] }}">
                                            {{ $havePaidMapping[$report->have_paid] }}
                                        </span>
                                    </td>
                                    <td>{{ $report->previous_pending_months }}</td>
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

        const selectedDate = 'Report Date: ' + document.getElementById('report_date').value

        $(document).ready(function() {
            $('#data-grid').dataTable({
                responsive: true,
                dom: "<'row mb-3'<'col-sm-12 col-md-12 d-flex align-items-center justify-content-end'B>>" +
                    "<'row mb-3'<'col-sm-12 col-md-6 d-flex align-items-center justify-content-start'f><'col-sm-10 col-md-6 pb-1 d-flex align-items-center justify-content-end'l>>" +
                    "<'row'<'col-sm-12'tr>>" +
                    "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
                buttons: [{
                    extend: 'excelHtml5',
                    messageTop: selectedDate,
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
                "{{ url('/reports-management/maintenance-fee-report') }}");
        }

        function searchReport() {
            if ($("#report_date").val() == "") {
                alert("Please select Report Date");
                return false;
            }

            $("#search").attr("action",
                "{{ url('/reports-management/maintenance-fee-report') }}");
            $("#search").submit();
        }

        function chargeMaintenanceFee() {
            Swal.fire({
                title: "Are you sure?",
                text: "All accounts of the clients, who didn't pay the Maintenance fee, will be searched and will be deducted the corresponding fee amount if found balance.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Yes, Charge Fee!"
            }).then(function(result) {
                if (result.value) {
                    $("#search").attr("action",
                        "{{ url('/reports-management/client/charge-maintenance-fee') }}");
                    $("#search").submit();
                }
            });
        }
    </script>
@stop
