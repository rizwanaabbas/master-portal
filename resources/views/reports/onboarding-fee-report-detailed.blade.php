@extends('layouts.cp')
@section('title', 'Report - Client Report')
@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Reports Management</span>
        </h1>
        <a href="{{ url('/reports-management/client/onboarding-fee-report') }}" class="btn btn-primary mr-3"><i
                class="fal fa-arrow-alt-left"></i> Go
            Back </a>
    </div>
    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false"
        data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                Client Report
            </h2>

        </div>

        <div class="panel-container show mb-3">
            <div class="panel-content" style="overflow: scroll;">
                @include('common.error-message')
                <table class="table table-bordered table-hover m-0 shadow"
                    @if (isset($report) && count($report->CompanyTransactionReportList) != 0) id="data-grid" @endif>
                    <thead class="thead-themed">
                        <tr>
                            <th>Reference Number</th>
                            <th>Amount</th>
                            <th>Currency Code</th>
                            <th>Status</th>
                            <th>Card Type</th>
                            <th>Success Date</th>
                            <th>Refund Date</th>
                            <th>Email</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>

                        @if (isset($report) && count($report->CompanyTransactionReportList) != 0)

                            @foreach ($report->CompanyTransactionReportList as $item)
                                @php
                                    switch ($item->TransactionStatus) {
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
                                    <td>

                                        @if (isset($item->ReferenceNumber))
                                            {{ $item->ReferenceNumber }}
                                        @endif
                                    </td>
                                    <td>

                                        @if (isset($item->TransactionAmount))
                                            {{ $item->TransactionAmount }}
                                        @endif
                                    </td>
                                    <td>

                                        @if (isset($item->TransactionCurrencyName))
                                            {{ $item->TransactionCurrencyName }}
                                        @endif
                                    </td>
                                    <td>

                                        @if (isset($item->TransactionStatus))
                                            <span class="badge badge-{{ $badgeClass }}">
                                                {{ $item->TransactionStatus }}
                                            </span>
                                        @endif
                                    </td>
                                    <td>

                                        @if (isset($item->CardBrand))
                                            {{ $item->CardBrand }}
                                        @endif
                                        @if (isset($item->CardType))
                                            {{ $item->CardType }}
                                        @endif
                                    </td>

                                    <td>

                                        @if (isset($item->SuccessDateTime))
                                            {{ \Carbon\Carbon::parse($item->SuccessDateTime)->format('Y-m-d H:i') }}
                                        @endif
                                    </td>
                                    <td>

                                        @if (isset($item->RefundDateTime))
                                            {{ \Carbon\Carbon::parse($item->RefundDateTime)->format('Y-m-d H:i') }}
                                        @endif
                                    </td>
                                    <td>

                                        @if (isset($item->CustomerEmail))
                                            {{ $item->CustomerEmail }}
                                        @endif
                                    </td>
                                    <td>

                                        @if (isset($item->Description))
                                            {{ $item->Description }}
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
