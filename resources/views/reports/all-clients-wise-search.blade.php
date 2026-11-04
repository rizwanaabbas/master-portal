@extends('layouts.cp')
@section('title', 'Clients Report - Reports Management')

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
                Clients Report
            </h2>

        </div>

        <div class="panel-container show mb-3">
            <div class="panel-content" style="overflow: scroll;">
                @include('common.error-message')

                <form name="search" id="search" method="post"
                    action="{{ url('/reports-management/client/all-clients-generate') }}" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="per_page" id="per_page" value="{{ $request['per_page'] }}">
                    <input type="hidden" name="search_string" id="search_string" value="{{ $request['search_string'] }}" />
                    <div class="row mb-0 pt-4 pr-4 pb-1 pl-4">
                        <div class="col-md-4 bg-secondary text-white p-3">
                            <div class="form-group">
                                <label class="form-label" for="from_date">From Date</label>
                                <input type="text" autocomplete="off" class="form-control" name="from_date"
                                    value="" id="from_date" placeholder="Select date">
                            </div>
                        </div>
                        <div class="col-md-4 bg-secondary text-white p-3">
                            <div class="form-group">
                                <label class="form-label" for="to_date">To Date</label>
                                <input type="text" autocomplete="off" class="form-control" name="to_date" value=""
                                    id="to_date" placeholder="Select date">
                            </div>
                        </div>
                        <div class="col-md-4 bg-secondary text-white p-3">
                            <button onclick="searchTrn(); return false;" class="btn btn-primary mt-4"><i
                                    class="fal fa-print-search"></i> Search</button>
                            <button onclick="resetForm(); return false;" class="btn btn-primary mt-4">Clear</button>

                        </div>
                    </div>
                </form>

                <div class="row">
                    <div class="col-md-12 p-4">
                        <table class="table table-bordered table-hover m-0 shadow"
                            @if (count($clients->items()) > 0) id="data-grid" @endif>
                            <thead class="thead-themed">
                                <tr>
                                    <th width="30">S.NO</th>
                                    <th>Client</th>
                                    @if ($currencies->count() > 0)
                                        @foreach ($currencies as $currency)
                                            <th>{{ $currency->short }}</th>
                                        @endforeach
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @if (count($clients->items()) > 0)
                                    @php
                                        $counter = 1;
                                        $total = [];
                                    @endphp
                                    @foreach ($clients->items() as $client)
                                        <tr>
                                            <td align="center" scope="row">{{ $counter++ }}</td>
                                            <td>{{ $client->name }}</td>
                                            @if ($currencies->count() > 0)
                                                @php $balance = 0; @endphp
                                                @foreach ($currencies as $currency)
                                                    @php
                                                        $balance = \App\Helpers\Common::getClientBalanceByCurrencyId(
                                                            $client->id,
                                                            $currency->id,
                                                        );
                                                        $total[$currency->id] = isset($total[$currency->id])
                                                            ? $total[$currency->id] + $balance
                                                            : $balance;
                                                    @endphp
                                                    <th>{{ \App\Helpers\Common::formatNumber($balance, $currency->is_crypto) }}
                                                    </th>
                                                @endforeach
                                            @endif

                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="8" align="center">No records found.</td>
                                    </tr>
                                @endif
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th></th>
                                    <td align="center"><strong>Total</strong></td>
                                    @if ($currencies->count() > 0)
                                        @foreach ($currencies as $currency)
                                            <td><b>{{ \App\Helpers\Common::formatNumber($total[$currency->id], $currency->is_crypto) }}</b>
                                            </td>
                                        @endforeach
                                    @endif
                                </tr>
                            </tfoot>
                        </table>
                        {{ $clients->links('global-partials.pagination', [
                            'perPage' => $clients->perPage(),
                            'currentPage' => $clients->currentPage(),
                            'total' => $clients->total(),
                        ]) }}
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
        var recordsCount = "<?php echo count($clients->items()); ?>"

        perPage = perPage ? perPage : 10
        document.getElementById('per_page').value = perPage

        $(document).ready(function() {
            $('#data-grid').dataTable({
                responsive: true,
                dom: "<'row mb-3'<'col-sm-12 col-md-6 d-flex align-items-center justify-content-start'f><'col-sm-12 col-md-6 d-flex align-items-center justify-content-end'B>>" +
                    "<'row mb-3 d-flex justify-content-end'<'col-sm-12 col-md-6 d-flex justify-content-end'l>>" +
                    "<'row'<'col-sm-12'tr>>"
                    // + "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>"
                    ,
                // dom: 'Blfrtip', // Include Buttons with "l" for length, "B" for buttons, "f" for filtering, "r" for processing, "t" for table, "i" for information, and "p" for pagination
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
                            window.location.href =
                                "{{ url('/reports-management/client/all-clients-report?format=pdf') }}";
                        }
                    },
                    {
                        extend: 'excelHtml5',
                        text: 'Excel',
                        titleAttr: 'Generate Excel',
                        className: 'btn-outline-success btn-sm mr-1',
                        action: function(e, dt, button, config) {
                            window.location.href =
                                "{{ url('/reports-management/client/all-clients-report') }}";
                        }
                    }
                ]
            });

            $('.js-thead-colors a').on('click', function() {
                var theadColor = $(this).attr("data-bg");
                $('#dt-basic-example thead').removeClassPrefix('bg-').addClass(theadColor);
            });

            $('.js-tbody-colors a').on('click', function() {
                var theadColor = $(this).attr("data-bg");
                $('#dt-basic-example').removeClassPrefix('bg-').addClass(theadColor);
            });

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

        function parseDate(dateString) {
            var parts = dateString.split("/")
            // JavaScript's Date constructor takes arguments in the order: year, monthIndex, day
            return new Date(parts[2], parts[1] - 1, parts[0])
        }

        function resetForm() {

            return window.location.replace("{{ url('/reports-management/client/all-clients') }}");
        }

        function searchTrn() {
            if ($("#from_date").val() == "" && $("#to_date").val() == "") {
                alert("please select date range");
                return false;
            }

            $("#search").attr("action", "{{ url('/reports-management/client/all-clients-generate') }}");
            $("#search").submit();
        }

        function handleTableLengthChange(pageLength) {
            document.getElementById('per_page').value = pageLength
            document.getElementById('search').submit()
        }
    </script>
@stop
