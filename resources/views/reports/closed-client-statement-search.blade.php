@extends('layouts.cp')
@section('title', 'Closed-Client Account Statement - Reports Management')

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
                Closed-Client Account Statement
            </h2>
        </div>

        <div class="panel-container show mb-3">
            <div class="panel-content" style="overflow: scroll;">
                @include('common.error-message')
                <div id="from_date_error" class="alert alert-danger">From Date must be earlier than or equal to To Date.
                </div>

                <form name="search" id="search" method="post"
                    action="{{ url('/reports-management/client/closed-client-account-statement-generate') }}"
                    enctype="multipart/form-data">
                    @csrf
                    <div class="row p-4">
                        <div class="col-md-3 bg-secondary text-white p-3">
                            <div class="form-group">
                                <label class="form-label" for="period">Account</label>
                                <select name="account_id" id="account_id" data-select2-id="account_id"
                                    class="form-control select2 w-100">
                                    <option value="">choose account</option>
                                    @if ($accounts->count() > 0)
                                        @foreach ($accounts as $account)
                                            <option value="{{ $account->id }}">{{ $account->title }}</option>
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
                            <button id="search_btn" onclick="searchTrn(); return false;" class="btn btn-primary mt-4"><i
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
                                    <th>Date</th>
                                    <th>Remarks</th>
                                    <th>Mode</th>
                                    <th>Type</th>
                                    <th>Amount</th>
                                    <th>Balance</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if ($transactions && $transactions->count() > 0)
                                    @foreach ($transactions as $transaction)
                                        @if ($loop->first)
                                            <tr>
                                                <td align="center">--</td>
                                                <td>--</td>
                                                <td>Opening Balance</td>
                                                <td>--</td>
                                                <td>--</td>
                                                <td>{{ \App\Helpers\Common::formatNumber($balance, $transaction->currency->is_crypto) }}
                                                </td>
                                            </tr>
                                        @endif
                                        @php
                                            if ($transaction->transaction_type == 'Credit') {
                                                $balance = $balance + $transaction->amount;
                                            } else {
                                                $balance = $balance - $transaction->amount;
                                            }
                                        @endphp

                                        <tr>
                                            <td align="center" scope="row">{{ $loop->iteration }}</td>
                                            <td>{{ \Carbon\Carbon::parse($transaction->transaction_date)->format('d M, Y') }}
                                            </td>
                                            <td width="30%">{{ $transaction->remarks }}</td>
                                            <td>{{ $transaction->transaction_nature }}</td>
                                            <td>{{ $transaction->transaction_type }}</td>
                                            <td>{{ \App\Helpers\Common::formatNumber($transaction->amount, $transaction->currency->is_crypto) }}
                                            </td>
                                            <td>{{ \App\Helpers\Common::formatNumber($balance, $transaction->currency->is_crypto) }}
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
                templates: controls,
                endDate: "{{ \Carbon\Carbon::today()->format('d/m/Y') }}"
                //startDate: "{{ $start_date }}"
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

                var fromDate = parseDate(fromDateValue)
                var toDate = parseDate($('#to_date').val())

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

            $('#data-grid').dataTable({
                responsive: true,
                lengthChange: false,
                searching: false,
            });
        });

        function parseDate(dateString) {
            var parts = dateString.split("/")
            // JavaScript's Date constructor takes arguments in the order: year, monthIndex, day
            return new Date(parts[2], parts[1] - 1, parts[0])
        }

        function resetDates() {
            $("#from_date").val("");
            $("#to_date").val("");
        }

        function resetPeriod() {
            $("#period").val("");
        }

        function resetForm() {
            return window.location.replace("{{ url('/reports-management/client/closed-client-account-statement') }}");
        }

        function searchTrn() {
            var fromDate = $("#from_date").val();
            var toDate = $("#to_date").val();
            var accountId = $("#account_id").val();

            if (fromDate == "" && toDate !== "" || fromDate !== "" && toDate == "") {
                alert("please select both date range.");
                return false;
            }

            if (accountId == "") {
                alert("please choose an account.")
                return false;
            }

            if (!$('#period :selected').val() > 0) {
                if (fromDate == "" && $toDate == "") {
                    alert("please period or date range");
                    return false;
                }
            }

            $("#search").attr("action",
                "{{ url('/reports-management/client/closed-client-account-statement-generate') }}");
            $("#search").submit();
        }
    </script>
@stop
