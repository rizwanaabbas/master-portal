@extends('layouts.cp')
@section('title', 'Account Transactions Report - Reports Management')

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
                Account Transactions Report
            </h2>

        </div>

        <div class="panel-container show mb-3">
            <div class="panel-content" style="overflow: scroll;">
                @include('common.error-message')

                <form name="search" id="search" method="POST"
                    action="{{ url('/reports-management/client/account-transactions-search') }}"
                    enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="per_page" id="per_page" value="{{ $request['per_page'] }}">
                    <input type="hidden" name="search_string" id="search_string" value="{{ $request['search_string'] }}" />
                    <div class="row mb-0 pt-4 pr-4 pb-1 pl-4">
                        <div class="col-md-5 bg-secondary text-white p-3">
                            <div class="form-group">
                                <label class="form-label" for="period">Account</label>
                                <select name="account_id" id="account_id" data-select2-id="account_id"
                                    class="form-control select2 w-100">
                                    <option value="">choose account</option>
                                    @if ($accounts->count() > 0)
                                        @foreach ($accounts as $account)
                                            @if (count($request) > 0)
                                                <option value="{{ $account->id }}"
                                                    @if ($account->id == $request['account_id']) selected @endif>
                                                    {{ $account->title . ' ' . $account->account_number }}</option>
                                            @else
                                                <option value="{{ $account->id }}">{{ $account->title . ' ' . $account->account_number }}</option>
                                            @endif
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2  bg-secondary text-white p-3">
                            <div class="form-group">
                                <label class="form-label" for="from_date">From Date</label>
                                <input type="text" autocomplete="off" class="form-control" name="from_date"
                                    @if (isset($request['from_date'])) value="{{ $request['from_date'] }}" @endif
                                    id="from_date" placeholder="Select date">
                            </div>
                        </div>
                        <div class="col-md-2  bg-secondary text-white p-3">
                            <div class="form-group">
                                <label class="form-label" for="to_date">To Date</label>
                                <input type="text" autocomplete="off" class="form-control" name="to_date"
                                    @if (isset($request['to_date'])) value="{{ $request['to_date'] }}" @endif
                                    id="to_date" placeholder="Select date">
                            </div>
                        </div>
                        <div class="col-md-3 bg-secondary text-white p-3">
                            <button class="btn btn-primary mt-4"><i class="fal fa-print-search"></i> Search</button>
                            <button onclick="resetForm(); return false;" class="btn btn-primary mt-4">Clear</button>
                        </div>
                    </div>
                </form>

                <div class="row">
                    <div class="col-md-12 p-4">
                        <table class="table table-bordered table-hover m-0 shadow"
                            @if (count($transactions->items()) > 0) id="data-grid" @endif>
                            <thead class="thead-themed">
                                <tr>
                                    <th width="30">S.NO</th>
                                    <th>Date</th>
                                    <th>Account Name</th>
                                    <th>Account Number</th>
                                    <th>Remarks</th>
                                    <th>Mode</th>
                                    <th>Type</th>
                                    <th>Amount</th>

                                </tr>
                            </thead>
                            <tbody>
                                @if (count($transactions->items()) > 0)
                                    @foreach ($transactions->items() as $transaction)
                                        <tr>
                                            <td align="center" scope="row">{{ $loop->iteration }}</td>
                                            <td>{{ \Carbon\Carbon::parse($transaction->transaction_date)->format('d M, Y') }}
                                            </td>
                                            <td>{{ $transaction->account->title }}</td>
                                            <td>{{ $transaction->account->account_number }}</td>
                                            <td width="30%">{{ $transaction->remarks }}</td>
                                            <td>{{ $transaction->transaction_nature }}</td>
                                            <td>{{ $transaction->transaction_type }}</td>
                                            <td>{{ \App\Helpers\Common::formatNumber($transaction->amount, $transaction->currency->is_crypto) . ' ' . $transaction->currency->short }}
                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="8" align="center">No records found.</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                        @if (strpos(Request::route()->uri, 'reports-management/client/account-transactions-search') !== false)
                            {{ $transactions->links('global-partials.pagination-post-req', [
                                'perPage' => $transactions->perPage(),
                                'currentPage' => $transactions->currentPage(),
                                'total' => $transactions->total(),
                                'hiddenInputs' => [
                                    'account_id' => $request['account_id'],
                                    'from_date' => $request['from_date'],
                                    'to_date' => $request['to_date'],
                                    'per_page' => $request['per_page'],
                                    'search_string' => $request['search_string'],
                                ],
                            ]) }}
                        @else
                            {{ $transactions->links('global-partials.pagination', [
                                'perPage' => $transactions->perPage(),
                                'currentPage' => $transactions->currentPage(),
                                'total' => $transactions->total(),
                            ]) }}
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    @php
        $search_string = isset($request) && isset($request['search_string']) ? $request['search_string'] : null;
    @endphp

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
        var recordsCount = "<?php echo count($transactions->items()); ?>"

        perPage = perPage ? perPage : 10
        document.getElementById('per_page').value = perPage

        function resetForm() {
            return window.location.replace("{{ url('reports-management/client/account-transactions') }}");
        }

        $(document).ready(function() {
            $('.select2').select2();

            $('#data-grid').dataTable({
                responsive: true,
                // lengthChange: false,
                dom: "<'row mb-3'<'col-sm-12 col-md-6 d-flex align-items-center justify-content-start'f><'col-sm-12 col-md-6 d-flex align-items-center justify-content-end'B>>" +
                    "<'row mb-3 d-flex justify-content-end'<'col-sm-12 col-md-6 d-flex justify-content-end'l>>" +
                    "<'row'<'col-sm-12'tr>>"
                    // + "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>"
                    ,
                buttons: [
                    /*{
                        extend:    'colvis',
                        text:      'Column Visibility',
                        titleAttr: 'Col visibility',
                        className: 'mr-sm-3'
                    },*/
                    {
                        extend: 'pdfHtml5',
                        text: 'PDF',
                        titleAttr: 'Generate PDF',
                        className: 'btn-outline-danger btn-sm mr-1',
                        action: function(e, dt, button, config) {
                            var account_id = $('#account_id').val();
                            var from_date = $('#from_date').val();
                            var to_date = $('#to_date').val();
                            if (account_id != "" || from_date != "" || to_date != "") {
                                window.location.href =
                                    "{{ url('/reports-management/client/account-transactions-documents/true?format=pdf') }}";
                            } else {
                                window.location.href =
                                    "{{ url('/reports-management/client/account-transactions-documents?format=pdf') }}";
                            }
                        }
                    },
                    {
                        extend: 'excelHtml5',
                        text: 'Excel',
                        titleAttr: 'Generate Excel',
                        className: 'btn-outline-success btn-sm mr-1',
                        action: function(e, dt, button, config) {
                            var account_id = $('#account_id').val();
                            var from_date = $('#from_date').val();
                            var to_date = $('#to_date').val();
                            if (account_id != "" || from_date != "" || to_date != "") {
                                window.location.href =
                                    "{{ url('/reports-management/client/account-transactions-documents/true') }}";
                            } else {
                                window.location.href =
                                    "{{ url('/reports-management/client/account-transactions-documents') }}";
                            }
                        }
                    }
                ]
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

            $('#from_date').datepicker({
                format: 'dd/mm/yyyy',
                todayHighlight: true,
                orientation: "bottom left",
                templates: controls,
                endDate: "{{ \Carbon\Carbon::today()->format('d/m/Y') }}"
            });

            $('#to_date').datepicker({
                format: 'dd/mm/yyyy',
                todayHighlight: true,
                orientation: "bottom left",
                templates: controls,
                endDate: "{{ \Carbon\Carbon::today()->format('d/m/Y') }}"
            });

            $('#from_date').on('change', function() {
                var fromDateValue = $(this).val()
                var toDateValue = $('#to_date').val() == undefined ?
                    "{{ \Carbon\Carbon::today()->format('d/m/Y') }}" : $('#to_date').val()

                var fromDate = parseDate(fromDateValue)
                var toDate = parseDate(toDateValue)

                $('#to_date').datepicker('setStartDate', fromDateValue);

                if (fromDate > toDate) {
                    $('#from_date_error').show();
                    $('#search_btn').hide();
                } else {
                    $('#from_date_error').hide();
                    $('#search_btn').show();
                }
            });

            $('#to_date').on('change', function() {
                var toDateValue = $(this).val()
                var fromDateValue = $('#from_date').val() == undefined ?
                    $(this).val() : $('#from_date').val()

                var fromDate = parseDate(fromDateValue)
                var toDate = parseDate(toDateValue)

                $('#from_date').datepicker('setEndDate', toDateValue);

                if (fromDate > toDate) {
                    $('#from_date_error').show();
                    $('#search_btn').hide();
                } else {
                    $('#from_date_error').hide();
                    $('#search_btn').show();
                }
            });

            $('#from_date_error').hide();
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

        function parseDate(dateString) {
            var parts = dateString.split("/")
            // JavaScript's Date constructor takes arguments in the order: year, monthIndex, day
            return new Date(parts[2], parts[1] - 1, parts[0])
        }
    </script>
@stop
