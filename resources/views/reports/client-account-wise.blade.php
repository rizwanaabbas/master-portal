@extends('layouts.cp')
@section('title', 'Client Deposit List Report - Reports Management')

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
                Client Deposit List Report
            </h2>

        </div>

        <div class="panel-container show mb-3">
            <div class="panel-content" style="overflow: scroll;">
                @include('common.error-message')

                <form name="search" id="search" method="POST"
                    action="{{ url('/reports-management/client/account-wise-generate') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="row p-4">
                        <div class="col-md-4 bg-secondary text-white p-3">
                            <div class="form-group">
                                <label class="form-label" for="from_date">From Date</label>
                                <input type="text"
                                    @if (isset($request['from_date'])) value="{{ $request['from_date'] }}" @endif
                                    autocomplete="off" class="form-control" name="from_date" value="" id="from_date"
                                    placeholder="Select date">
                            </div>
                        </div>
                        <div class="col-md-4 bg-secondary text-white p-3">
                            <div class="form-group">
                                <label class="form-label" for="to_date">To Date</label>
                                <input type="text"
                                    @if (isset($request['to_date'])) value="{{ $request['to_date'] }}" @endif
                                    autocomplete="off" class="form-control" name="to_date" value="" id="to_date"
                                    placeholder="Select date">
                            </div>
                        </div>
                        <div class="col-md-4 bg-secondary text-white p-3">
                            <button onclick="searchTrn(); return false;" class="btn btn-primary mt-4"><i
                                    class="fal fa-print-search"></i> Search</button>
                            <button onclick="resetForm(); return false;" class="btn btn-primary mt-4">Clear</button>

                        </div>
                    </div>
                </form>

                <table class="table table-bordered table-hover m-0 shadow"
                    @if ($clients->count() > 0) id="data-grid" @endif>
                    <thead class="thead-themed">
                        <tr>
                            <th width="30">S.NO</th>
                            <th>Account Name</th>
                            <th>Account Number</th>
                            <th>Opening Date</th>
                            <th>Currency</th>
                            <th>Balance</th>
                            <th>Type</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if ($clients->count() > 0)
                            @php
                                $counter = 1;
                                $balance = 0;
                            @endphp
                            @foreach ($clients as $client)
                                @if ($client->clientAccounts)
                                    @foreach ($client->clientAccounts->where('is_active', true) as $account)
                                        @if (!in_array('Crypto', $enabledModules) && $account->currency->is_crypto)
                                            @php continue; @endphp
                                        @endif
                                        @php $balance = \App\Helpers\Common::getAccountBalanceByCurrencyId($account->id, $account->account_currency); @endphp
                                        <tr>
                                            <td align="center" scope="row">{{ $counter++ }}</td>
                                            <td>{{ $client->name }}</td>
                                            <td>
                                                <a
                                                    href="{{ url('/reports-management/client/account-transactions/' . $account->id) }}">
                                                    {{ $account->account_number }}
                                                </a>
                                            </td>
                                            <td>{{ \Carbon\Carbon::parse($account->created_at)->format('d M, Y') }}</td>
                                            <td>{{ \App\Helpers\Common::getCurrencyShort($account->account_currency) }}</td>
                                            <td>{{ \App\Helpers\Common::formatNumber($balance, $account->currency ? $account->currency->is_crypto : false) }}
                                            </td>
                                            <td>{{ ucwords($client->type) }}</td>
                                        </tr>
                                    @endforeach
                                @endif
                            @endforeach
                        @else
                            <tr>
                                <td colspan="8" align="center">No records found.</td>
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
                lengthChange: false,
                dom: "<'row mb-3'<'col-sm-12 col-md-6 d-flex align-items-center justify-content-start'f><'col-sm-12 col-md-6 d-flex align-items-center justify-content-end'lB>>" +
                    "<'row'<'col-sm-12'tr>>" +
                    "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
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
                        footer: true,
                        action: function(e, dt, button, config) {
                            let from_date = $("#from_date").val();
                            let to_date = $("#to_date").val();
                            window.location.href =
                                `{{ url('/reports-management/client/account-wise-documents') }}?format=pdf&from_date=${from_date}&to_date=${to_date}`;
                        }
                    },
                    {
                        extend: 'excelHtml5',
                        text: 'Excel',
                        titleAttr: 'Generate Excel',
                        footer: true,
                        className: 'btn-outline-success btn-sm mr-1',
                        action: function(e, dt, button, config) {
                            var from_date = $("#from_date").val();
                            var to_date = $("#to_date").val();

                            if (from_date != "" && to_date != "") {
                                window.location.href =
                                    `{{ url('/reports-management/client/account-wise-documents') }}?format=excel&from_date=${from_date}&to_date=${to_date}`;
                            } else {
                                window.location.href =
                                    "{{ url('/reports-management/client/account-wise-documents?format=excel') }}";
                            }
                        }

                    },
                ]
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

            $('.js-thead-colors a').on('click', function() {
                var theadColor = $(this).attr("data-bg");
                console.log(theadColor);
                $('#dt-basic-example thead').removeClassPrefix('bg-').addClass(theadColor);
            });

            $('.js-tbody-colors a').on('click', function() {
                var theadColor = $(this).attr("data-bg");
                console.log(theadColor);
                $('#dt-basic-example').removeClassPrefix('bg-').addClass(theadColor);
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

        function parseDate(dateString) {
            var parts = dateString.split("/")
            // JavaScript's Date constructor takes arguments in the order: year, monthIndex, day
            return new Date(parts[2], parts[1] - 1, parts[0])
        }

        function resetForm() {
            return window.location.replace("{{ url('/reports-management/client/account-wise') }}");
        }

        function searchTrn() {
            if ($("#from_date").val() == "" && $("#to_date").val() == "") {
                alert("please select date range");
                return false;
            }

            // $("#search").attr("action", "{{ url('/reports-management/client/account-wise-generate') }}");
            $("#search").submit();
        }
    </script>
@stop
