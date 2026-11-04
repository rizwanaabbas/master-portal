@extends('layouts.cp')
@section('title', 'Vostro Banks Reports - Reports Management')

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
                Vostro Banks Reports
            </h2>

        </div>

        <div class="panel-container show mb-3">
            <div class="panel-content" style="overflow: scroll;">
                @include('common.error-message')

                <form name="store" id="store" method="post"
                    action="{{ url('/reports-management/vostro-banks-reports/daily-banks-balances/store') }}"
                    enctype="multipart/form-data">
                    @csrf
                    <div class="row p-4">
                        <div class="col-md-3 bg-secondary text-white p-3">
                            <div class="form-group">
                                <label class="form-label" for="report_date">Report Date</label>
                                <input type="text" autocomplete="off" class="form-control" name="report_date"
                                    value="" id="report_date" placeholder="Select date">
                            </div>
                        </div>
                        <div class="col-md-3 bg-secondary text-white p-3">
                            <div class="form-group">
                                <label class="form-label" for="file">Report</label><br />
                                <input type="file" name="file" class="mt-1" id="file" accept=".xls, .xlsx" />
                            </div>
                        </div>
                        <div class="col-md-3 bg-secondary text-white p-3">
                            <div class="form-group">
                                <div class="custom-control custom-switch mt-5">
                                    <input type="checkbox" class="custom-control-input" id="should_send_email"
                                        value="1" name="should_send_email">
                                    <label class="custom-control-label" for="should_send_email">Send Email</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 bg-secondary text-white p-3">
                            <button type="submit" class="btn btn-primary mt-4">Submit</button>
                            {{-- <button onclick="resetForm(); return false;" class="btn btn-primary mt-4">Clear</button> --}}
                        </div>
                    </div>
                </form>
                <table class="table table-bordered table-hover m-0 shadow"
                    @if (count($reports->items()) > 0) id="data-grid" @endif>
                    <thead class="thead-themed">
                        <tr>
                            <th width="30">S.NO</th>
                            <th>Date</th>
                            <th>Report Link</th>
                            <th>Is Email Sent</th>
                            <th>Uploaded By</th>
                            <th>Uploaded At</th>
                            @can('delete records')
                                <th width="30">Action</th>
                            @endcan
                        </tr>
                    </thead>
                    <tbody>
                        @if (count($reports->items()) > 0)
                            @foreach ($reports->items() as $report)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ \Carbon\Carbon::parse($report->report_date)->format('d M, Y') }}</td>
                                    <td>
                                        <a class="badge badge-info" href="{{ $report->file_url }}">Daily Report -
                                            {{ $report->report_date }}</a>
                                    </td>
                                    <td>
                                        <span class="badge {{ $statusBadge[$report->should_send_email] }}">
                                            {{ $report->should_send_email ? 'Yes' : 'No' }}
                                        </span>
                                    </td>
                                    <td>{{ $report->creator }}</td>
                                    <td>{{ \Carbon\Carbon::parse($report->created_at)->format('d M, Y') }}</td>

                                    @can('delete records')
                                        <td>
                                            <div class="d-flex justify-content-center w-100">
                                                <a href="javascript:deleteReport('{{ $report->id }}')"
                                                    title="Delete Report"><i
                                                        class="alert-danger fal fa-times-circle rounded-circle mx-auto"></i></a>
                                            </div>
                                        </td>
                                    @endcan

                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="8" align="center">No records found.</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
                @if (strpos(Request::route()->uri, 'reports-management/vostro-banks-reports/daily-banks-balances/search') !== false)
                    {{ $reports->links('global-partials.pagination-post-req', [
                        'perPage' => $reports->perPage(),
                        'currentPage' => $reports->currentPage(),
                        'total' => $reports->total(),
                        'hiddenInputs' => [
                            'per_page' => $request['per_page'],
                            'search_string' => $request['search_string'],
                        ],
                    ]) }}
                @else
                    {{ $reports->links('global-partials.pagination', [
                        'perPage' => $reports->perPage(),
                        'currentPage' => $reports->currentPage(),
                        'total' => $reports->total(),
                    ]) }}
                @endif
            </div>
        </div>
    </div>

    @php
        $search_string = isset($request) && isset($request['search_string']) ? $request['search_string'] : null;
    @endphp

    <form name="search" id="search" method="post"
        action="{{ url('/reports-management/vostro-banks-reports/daily-banks-balances/search') }}"
        enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="per_page" id="per_page"
            value="{{ isset($request->per_page) ? $request->per_page : '' }}">
        <input type="hidden" name="search_string" id="search_string"
            value="{{ isset($request->search_string) ? $request->search_string : '' }}" />
    </form>

    @can('delete records')
        <form method="POST" name="delete_report" id="delete_report"
            action="{{ url('/reports-management/vostro-banks-reports/daily-banks-balances/delete') }}">
            @csrf
            <input type="hidden" name="report_id" id="report_id" value="">
        </form>
    @endcan

@stop
@section('scripts')
    <script>
        var controls = {
            leftArrow: '<i class="fal fa-angle-left" style="font-size: 1.25rem"></i>',
            rightArrow: '<i class="fal fa-angle-right" style="font-size: 1.25rem"></i>'
        }

        var hiddenInput = document.getElementById('search_string')
        var search_string = "<?php echo $search_string; ?>"
        var perPage = "<?php echo $request['per_page']; ?>"
        var recordsCount = "<?php echo count($reports->items()); ?>"

        perPage = perPage ? perPage : 10
        document.getElementById('per_page').value = perPage

        $(document).ready(function() {
            $('#data-grid').dataTable({
                responsive: true,
                dom: "<'row mb-3'<'col-sm-12 col-md-6 d-flex align-items-center justify-content-start'f><'col-sm-12 col-md-6 d-flex justify-content-end'l>>" +
                    "<'row'<'col-sm-12'tr>>"
            });

            $('#report_date').datepicker({
                format: 'dd/mm/yyyy',
                todayHighlight: true,
                orientation: "bottom left",
                templates: controls
            });

            const pageLength = document.getElementsByName('data-grid_length')[0]
            const searchInput = document.querySelectorAll('input[type="search"]')[0]

            if (pageLength) {
                const options = Array.from(pageLength.options)
                let optionToSelect = options.find(item => item.value == perPage)
                optionToSelect.setAttribute('selected', true)

                if (perPage == -1) {
                    perPage = recordsCount
                }

                $('#data-grid').DataTable().page.len(perPage).draw();

                pageLength.addEventListener('change', function() {
                    document.getElementById('per_page').value = pageLength.value
                    document.getElementById('search').submit()
                })
            }

            // Search using debounce mechanism starts
            if (search_string && searchInput) {
                searchInput.value = search_string

            }

            if (searchInput) {
                searchInput.addEventListener('input', e => {
                    hiddenInput.value = e.target.value
                    updateDebounceText(e.target.value)
                })
            }
            // Search using debounce mechanism ends
        });

        const updateDebounceText = debounce(searchString => {
            hiddenInput.value = searchString
            document.getElementById('search').submit();
        })

        function debounce(callback, delay = 1000) {
            let timeout

            return (...args) => {
                clearTimeout(timeout)
                timeout = setTimeout(() => {
                    callback(...args)
                }, delay)
            }
        }

        function deleteReport(id) {

            Swal.fire({
                title: "Are you sure?",
                text: "Report will be deleted permanently",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Yes, delete it!"
            }).then(function(result) {
                if (result.value) {
                    document.getElementById("report_id").value = id;
                    document.getElementById('delete_report').submit()
                }
            });
        }
    </script>
@stop
