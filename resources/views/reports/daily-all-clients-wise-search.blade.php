@extends('layouts.cp')
@section('title', 'Daily Client Wise Report - Reports Management')

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
                Daily Client Wise Report - {{ $totalDuration }}
            </h2>

        </div>

        <div class="panel-container show mb-3">
            <div class="panel-content" style="overflow: scroll;">
                @include('common.error-message')

                <form name="search" id="search" method="post"
                    action="{{ url('/reports-management/client/daily-all-clients') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="row p-4">
                        <div class="col-md-3 bg-secondary text-white p-3">
                            <div class="form-group">
                                <label class="form-label" for="from_date">From Date</label>
                                <input type="text" autocomplete="off" class="form-control" name="from_date"
                                    value="{{ $request['from_date'] }}" id="from_date" placeholder="Select date">
                            </div>
                        </div>
                        <div class="col-md-3 bg-secondary text-white p-3">
                            <div class="form-group">
                                <label class="form-label" for="to_date">To Date</label>
                                <input type="text" autocomplete="off" class="form-control" name="to_date"
                                    value="{{ $request['to_date'] }}" id="to_date" placeholder="Select date">
                            </div>
                        </div>
                        <div class="col-md-3 bg-secondary text-white p-3">
                            <div class="form-group">
                                <label class="form-label" for="to_date">Clients</label>
                                <select name="client_id[]" multiple id="client_id" class="form-control"
                                    placeholder="choose client">
                                    @foreach ($clients as $client)
                                        <option value="{{ $client->id }}">{{ $client->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3 bg-secondary text-white p-3">
                            <button onclick="searchTrn(); return false;" class="btn btn-primary mt-4"><i
                                    class="fal fa-print-search"></i> Search</button>
                            <button onclick="resetForm(); return false;" class="btn btn-primary mt-4">Clear</button>

                        </div>
                    </div>
                </form>
                <table class="table table-bordered table-hover m-0 shadow"
                    @if (count($data) > 0) id="data-grid" @endif>
                    <thead class="thead-themed">
                        <tr>
                            <th width="30">S.NO</th>
                            <th>Date</th>
                            <th>Client</th>
                            @if ($currencies->count() > 0)
                                @foreach ($currencies as $currency)
                                    <th>{{ \App\Helpers\Common::getCurrencyShort($currency->account_currency) }}</th>
                                @endforeach
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @if (count($data))
                            @php $counter = 1; @endphp
                            @foreach ($data as $date => $clients)
                                @foreach ($clients as $client => $values)
                                    <tr>
                                        <td align="center" scope="row">{{ $counter++ }}</td>
                                        <td nowrap="nowrap">{{ $date }}</td>
                                        <td>{{ $client }}</td>
                                        @foreach ($values as $currency)
                                            @if (isset($currency['balance']))
                                                <td>{{ number_format($currency['balance'], 2) }}</td>
                                            @else
                                                <td>0</td>
                                            @endif
                                        @endforeach
                                    </tr>
                                @endforeach
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

            $("select").select2({
                'placeholder': 'choose clients'
            });

            $('select').on('select2:close', function(evt) {
                var uldiv = $(this).siblings('span.select2').find('ul')
                var count = uldiv.find('li').length - 1;
                uldiv.html("<li style='color:black;padding-top:8px;'>" + count + " clients selected</li>")
            });

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
                        className: 'btn-outline-danger btn-sm mr-1'
                    },
                    {
                        extend: 'excelHtml5',
                        text: 'Excel',
                        titleAttr: 'Generate Excel',
                        className: 'btn-outline-success btn-sm mr-1'
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
            return window.location.replace("{{ url('/reports-management/client/daily-all-clients') }}");
        }

        function searchTrn() {
            if ($("#from_date").val() == "" && $("#to_date").val() == "") {
                alert("please select date range");
                return false;
            }

            $("#search").attr("action", "{{ url('/reports-management/client/daily-all-clients') }}");
            $("#search").submit();
        }
    </script>
@stop
