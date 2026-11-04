@extends('layouts.cp')
@section('title', 'Own Banks Account Statement - Reports Management')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Finance Management</span>
        </h1>
    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false"
        data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                {{-- Own Banks Account Statement --}}
                {{ $isMaintenanceFeeReport ? 'Maintenance Fee Revenue Report' : 'Own Banks Account Statement' }}
            </h2>
        </div>

        <div class="panel-container show mb-3">
            <div class="panel-content" style="overflow: scroll;">
                @include('common.error-message')

                @php
                    $url = url(
                        '/reports-management/' .
                            ($isMaintenanceFeeReport ? 'maintenance-fee/revenue' : 'bank-account-statement'),
                    );
                @endphp

                <form name="search" id="search" method="post" action="{{ $url }}"
                    enctype="multipart/form-data">
                    @csrf
                    <div class="row p-4">
                        <div class="col-md-3 bg-secondary text-white p-3">
                            <div class="form-group">
                                <label class="form-label" for="bank_id">Bank</label>
                                <select name="bank_id" id="bank_id" data-select2-id="bank_id"
                                    class="form-control select2 w-100">
                                    @if ($banks->count() > 0)
                                        @foreach ($banks as $bank)
                                            <option value="{{ $bank->id }}">{{ $bank->name }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2 bg-secondary text-white p-3">
                            <div class="form-group">
                                <label class="form-label" for="period">Statement Period</label>
                                <select onchange="resetDates();" class="form-control" name="period" id="period">
                                    <option value="">choose period</option>
                                    @foreach ($periods as $key => $value)
                                        <option value="{{ $key }}">{{ $value }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2  bg-secondary text-white p-3">
                            <div class="form-group">
                                <label class="form-label" for="from_date">From Date</label>
                                <input type="text" onchange="resetPeriod();" autocomplete="off" class="form-control"
                                    name="from_date" value="" id="from_date" placeholder="Select date">
                            </div>
                        </div>
                        <div class="col-md-2  bg-secondary text-white p-3">
                            <div class="form-group">
                                <label class="form-label" for="to_date">To Date</label>
                                <input type="text" onchange="resetPeriod();" autocomplete="off" class="form-control"
                                    name="to_date" value="" id="to_date" placeholder="Select date">
                            </div>
                        </div>
                        <div class="col-md-3 bg-secondary text-white p-3">
                            <button onclick="searchTrn(); return false;" class="btn btn-primary mt-4"><i
                                    class="fal fa-print-search"></i> Search</button>
                            <button onclick="resetForm(); return false;" class="btn btn-primary mt-4">Clear</button>

                        </div>
                    </div>
                </form>
                <div class="row">
                    <div class="col-md-12 p-4">
                        <table class="table table-bordered table-hover m-0 shadow"
                            @if ($transactions && $transactions->count() > 0) id="data-grid" @endif>
                            <thead class="thead-themed">
                                <tr>
                                    <th width="30">S.No</th>
                                    <th width="15%">Account Name</th>
                                    <th width="12%">A/C #</th>
                                    <th>Date</th>
                                    <th width="7%">Currency</th>
                                    <th>Transaction Amount</th>
                                    <th>Fee Amount</th>
                                    <th>Mode</th>
                                    <th>Type</th>
                                    <th width="30%">Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if ($transactions && $transactions->count() > 0)
                                    @foreach ($transactions as $transaction)
                                        <tr>
                                            <td align="center" scope="row">{{ $loop->iteration }}</td>
                                            <td>{{ $transaction->account->title }}</td>
                                            <td>{{ $transaction->account->account_number }}</td>
                                            <td>{{ \Carbon\Carbon::parse($transaction->transaction_date)->format('d M, Y') }}
                                            </td>
                                            <td>{{ $transaction->account->currency->short }}</td>
                                            <td>{{ \App\Helpers\Common::formatNumber($transaction->requestedTrn->amount, $transaction->currency->is_crypto) }}
                                            </td>
                                            <td>{{ \App\Helpers\Common::formatNumber($transaction->amount, $transaction->currency->is_crypto) }}
                                            </td>
                                            <td>{{ $transaction->transaction_nature }}</td>
                                            <td>{{ $transaction->transaction_type }}</td>
                                            <td>{{ $transaction->remarks }}</td>
                                        </tr>
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
            $('.select2').select2();

            $('#from_date').datepicker({
                format: 'dd/mm/yyyy',
                todayHighlight: true,
                orientation: "bottom left",
                templates: controls
                //startDate: "{{ $start_date }}"
            });

            $('#to_date').datepicker({
                format: 'dd/mm/yyyy',
                todayHighlight: true,
                orientation: "bottom left",
                templates: controls,
                endDate: "{{ \Carbon\Carbon::today()->format('d/m/Y') }}"
            });

            $('#data-grid').dataTable({
                responsive: true,
                lengthChange: false,
                // searching: false,
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
        });

        function resetDates() {
            $("#from_date").val("");
            $("#to_date").val("");
        }

        function resetPeriod() {
            $("#period").val("");
        }

        function resetForm() {
            return window.location.replace("{{ $url }}");
        }

        function searchTrn() {
            if (!$('#period :selected').val() > 0) {
                if ($("#from_date").val() == "" && $("#to_date").val() == "") {
                    alert("please period or date range");
                    return false;
                }
            }

            $("#search").attr("action", "{{ $url . '-generate' }}");
            $("#search").submit();
        }
    </script>
@stop
