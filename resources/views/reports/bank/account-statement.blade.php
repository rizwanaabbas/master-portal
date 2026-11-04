@extends('layouts.cp')
@section('title', 'Bank Account Statement - Reports Management')

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
                Bank Account Statement
            </h2>
        </div>

        <div class="panel-container show mb-3">
            <div class="panel-content" style="overflow: scroll;">
                @include('common.error-message')

                <form name="search" id="search" method="post"
                    action="{{ url('/reports-management/' . $isMaintenanceFeeReport ? 'maintenance-fee/revenue-' : 'bank-account-statement-' . 'generate') }}"
                    enctype="multipart/form-data">
                    @csrf
                    <div class="row mb-4 p-4">
                        <div class="col-md-3 bg-secondary text-white p-3">
                            <div class="form-group">
                                <label class="form-label" for="bank_id">Bank</label>
                                <select name="bank_id" id="bank_id" data-select2-id="bank_id"
                                    class="form-control select2 w-100">
                                    @if ($banks->count() > 0)
                                        @foreach ($banks as $bank)
                                            <option value="{{ $bank->id }}"
                                                @if ($bank->id == $request['bank_id']) selected @endif>{{ $bank->name }}
                                            </option>
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
                                        <option value="{{ $key }}"
                                            @if ($key == $request['period']) selected @endif>{{ $value }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2  bg-secondary text-white p-3">
                            <div class="form-group">
                                <label class="form-label" for="from_date">From Date</label>
                                <input type="text" onchange="resetPeriod();" autocomplete="off" class="form-control"
                                    name="from_date" value="{{ $request['from_date'] }}" id="from_date"
                                    placeholder="Select date">
                            </div>
                        </div>
                        <div class="col-md-2  bg-secondary text-white p-3">
                            <div class="form-group">
                                <label class="form-label" for="to_date">To Date</label>
                                <input type="text" onchange="resetPeriod();" autocomplete="off" class="form-control"
                                    name="to_date" value="{{ $request['to_date'] }}" id="to_date"
                                    placeholder="Select date">
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
                    @if ($transactions && $transactions->count() > 0) id="data-grid" @endif>
                    <thead class="thead-themed">
                        <tr>
                            <th width="30">S.No</th>
                            <th width="15%">Account Name</th>
                            <th width="10%">A/C #</th>
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
                                    <td>
                                        {{ $transaction->linkedTrn &&
                                        $transaction->linkedTrn->transaction_type == 'Debit' &&
                                        $transaction->linkedTrn->is_fee_transaction
                                            ? $transaction->linkedTrn->account->title
                                            : $transaction->account->title }}
                                    </td>
                                    <td>
                                        {{ $transaction->linkedTrn &&
                                        $transaction->linkedTrn->transaction_type == 'Debit' &&
                                        $transaction->linkedTrn->is_fee_transaction
                                            ? $transaction->linkedTrn->account->account_number
                                            : $transaction->account->account_number }}
                                    </td>
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

    <form method="POST" name="delete-transaction" id="delete-transaction"
        action="{{ url('/finance-management/transactions/delete') }}">
        @csrf
        <input type="hidden" name="trn_id" id="trn_id" value="">
    </form>

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
                        extend: 'excelHtml5',
                        text: 'Excel',
                        titleAttr: 'Generate Excel',
                        className: 'btn-outline-success btn-sm mr-1'
                    }
                ]
            });
        });

        function generatePDF() {

            $("#search").attr("action", "{{ url('/reports-management/bank-account-statement-pdf') }}");
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
            return window.location.replace("{{ url('/reports-management/bank-account-statement') }}");
        }

        function searchTrn() {
            if (!$('#period :selected').val() > 0) {
                if ($("#from_date").val() == "" && $("#to_date").val() == "") {
                    alert("please period or date range");
                    return false;
                }
            }

            $("#search").attr("action", "{{ url('/reports-management/bank-account-statement-generate') }}");
            $("#search").submit();
        }

        function deleteTransaction(id) {

            Swal.fire({
                title: "Are you sure?",
                text: "Transaction will be deleted permanently",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Yes, delete it!"
            }).then(function(result) {
                if (result.value) {
                    $("#trn_id").val(id);
                    document.getElementById('delete-transaction').submit()
                }
            });
        }
    </script>
@stop
