@extends('layouts.cp')
@section('title', 'Pending Maintenance Fee Report - Reports Management')

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
                Pending Maintenance Fee Report
            </h2>

        </div>

        <div class="panel-container show mb-3">
            <div class="panel-content" style="overflow: scroll;">
                @include('common.error-message')
                <div class="row">
                    <div class="col-md-12 d-flex justify-content-center">
                        <h2>
                            <span class="badge {{ $statusBadge['Approved'] }}">
                                Paid Accounts: {{ $paidCount }}
                            </span> &nbsp;&nbsp; | &nbsp;&nbsp; <span class="badge {{ $statusBadge['Rejected'] }}">
                                Unpaid Accounts: {{ $unpaidCount }}
                            </span>
                        </h2>
                    </div>
                </div>
                <table class="table table-bordered table-hover m-0 shadow"
                    @if ($unpaidCount > 0) id="data-grid" @endif>
                    @php $button = 'N/A'; @endphp

                    @can('approve client')
                        @php
                            $button =
                                '<button onclick="closeClient(this); return false;" class="btn btn-danger ml-2 mx-md-2"> Close Account </button>';
                        @endphp
                    @endcan
                    <thead class="thead-themed">
                        <tr>
                            {{-- <th width="30">S.NO</th> --}}
                            <th width="350">Client Name</th>
                            <th>Client Type</th>
                            <th>Acc Currency</th>
                            <th>Acc number</th>
                            <th>Date</th>
                            <th>Paid</th>
                            <th>Pending Months</th>
                            <th>Client Status</th>
                            <th>Balance</th>
                            <th width="122">Action</th>
                            {{-- <th>Date</th> --}}
                        </tr>
                    </thead>
                    <tbody>
                        @if ($unpaidCount)
                            @php
                                $havePaidMapping = [0 => 'No', 1 => 'Yes'];
                                $badgeMapping = [0 => 'danger', 1 => 'success'];
                            @endphp

                            @foreach ($unpaidReports as $report)
                                <tr>
                                    {{-- <td align="center" scope="row">{{ $loop->iteration }}</td> --}}
                                    <td>{{ $report->client->name }}</td>
                                    <td>{{ $report->client->type }}</td>
                                    <td>{{ $report->account ? $report->account->currency->short : '--' }}</td>
                                    <td>{{ $report->account ? $report->account->account_number : '--' }}</td>
                                    <td>{{ $report->date_charged }}</td>
                                    <td>
                                        <span class="badge {{ 'badge-' . $badgeMapping[$report->have_paid] }}">
                                            {{ $havePaidMapping[$report->have_paid] }}
                                        </span>
                                    </td>
                                    <td>{{ $report->previous_pending_months }}</td>
                                    <td>
                                        <span class="badge {{ $statusBadge[$report->client->status] }}">
                                            {{ $report->client->status }}
                                        </span>
                                    </td>
                                    @php
                                        // dd($report->id);
                                    @endphp
                                    <td>{{ number_format($report->amount, 2) }}</td>
                                    <td align="center" scope="row" data-client-id="{{ $report->client_id }}">
                                        @if ($report->client->status == 'Approved')
                                            {{ $button }}
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

    @can('approve client')
        <div class="modal fade" id="close-modal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title ">Close Client Profile</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true"><i class="fal fa-times"></i></span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form action="{{ url('/client-management/clients/close') }}" method="post">
                            @csrf
                            <input type="hidden" name="client_id" id="client_id" value="">
                            <div class="form-group">
                                <label class="form-label" for="close_reason">Closing Reason</label>
                                <textarea required class="form-control" name="close_reason" id="close_reason" cols="30" rows="2"></textarea>
                            </div>

                            <div class="form-group">
                                <button type="submit" class="btn btn-danger">Close</button>
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    @endcan

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
                    // messageTop: selectedDate,
                    text: 'Export to Excel',
                    titleAttr: 'Generate Excel',
                    className: 'btn-outline-success btn-sm mr-1',
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5, 6, 7, 8] // Columns to Export
                    }
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
        });

        function closeClient(button) {
            document.getElementById('client_id').value = button.parentElement.dataset.clientId
            $("#close-modal").modal('show');
        }
    </script>
@stop
