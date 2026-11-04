@extends('layouts.cp')
@section('title', 'Investment Account Statement - Investment Management')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Investment Management</span>
        </h1>
    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false"
        data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                Investment Account Statement
            </h2>
        </div>

        <div class="panel-container show mb-3">
            <div class="panel-content" style="overflow: scroll;">
                @include('common.error-message')

                <form name="search" id="search" method="post"
                    action="{{ url('/client/dashboard/account-statement-generate') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="row p-4">
                        <div class="col-md-3 bg-secondary text-white p-3">
                            <div class="form-group">
                                <label class="form-label" for="period">Account</label>
                                <select data-select2-id="account_id" class="form-control select2 w-100" name="account_id"
                                    id="account_id">
                                    @if ($accounts->count() > 0)
                                        @foreach ($accounts as $account)
                                            <option value="{{ $account->id }}">
                                                {{-- @role('client') --}}
                                                {{ $account->title }}
                                                {{-- @endrole --}}
                                                {{-- @role('basic client') --}}
                                                {{ $account->account_number }}
                                                {{-- @endrole --}}
                                            </option>
                                        @endforeach
                                    @else
                                        <option value="">No accounts found</option>
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
                                    name="from_date" value="{{ $from_date }}" id="from_date" placeholder="Select date">
                            </div>
                        </div>
                        <div class="col-md-2  bg-secondary text-white p-3">
                            <div class="form-group">
                                <label class="form-label" for="to_date">To Date</label>
                                <input type="text" onchange="resetPeriod();" autocomplete="off" class="form-control"
                                    name="to_date" value="{{ $to_date }}" id="to_date" placeholder="Select date">
                            </div>
                        </div>
                        <div class="col-md-3 bg-secondary text-white p-3">
                            @if (count($accounts) > 0)
                                <button onclick="searchTrn(); return false;" class="btn btn-primary mt-4"><i
                                        class="fal fa-print-search"></i> Search</button>
                                <button onclick="resetForm(); return false;" class="btn btn-primary mt-4">Clear</button>
                            @endif
                        </div>
                    </div>
                </form>

                <div class="row">
                    <div class="col-md-12 p-4">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4>Account Statement</h4>
                            </div>
                            @if (count($transactions) > 0)
                                {{-- @role('client') --}}
                                <div class="mb-2"><a href="javascript:generatePDF()" class="btn btn-outline-success"><i
                                            class="fal fa-file-pdf"></i> Download Statement</a></div>
                                {{-- @endrole --}}
                            @endif
                        </div>
                        <table class="table table-bordered table-hover m-0 shadow"
                            @if (count($transactions) > 0) id="data-grid" @endif>
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
                                                <td>{{ number_format($balance, 2) }}</td>
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
                                            <td>{{ number_format($transaction->amount, 2) }}</td>
                                            <td>{{ number_format($balance, 2) }}</td>
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
                //startDate: "{{ $registered_date }}"
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
                searching: false,
            });
        });

        function generatePDF() {

            $("#search").attr("action", "{{ url('client/dashboard/account-statement-pdf') }}");
            $("#search").submit();
        }

        function resetDates() {
            $("#from_date").val("");
            $("#to_date").val("");
        }

        function resetPeriod() {
            $("#period").val("");
        }

        function resetForm() {
            return window.location.replace("{{ url('client/dashboard/account-statement') }}");
        }

        function searchTrn() {
            if (!$('#period :selected').val() > 0) {
                if ($("#from_date").val() == "" && $("#to_date").val() == "") {
                    alert("please period or date range");
                    return false;
                }
            }

            $("#search").attr("action", "{{ url('/client/dashboard/account-statement-generate') }}");
            $("#search").submit();
        }
    </script>
@stop
