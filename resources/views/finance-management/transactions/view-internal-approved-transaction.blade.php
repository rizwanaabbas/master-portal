@extends('layouts.cp')
@section('title', 'Internal Transfer Transaction - Finance Management')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Finance Management</span>
        </h1>
        <div class="d-flex">
            <a href="{{ url('finance-management/pending-transactions') }}" class="btn btn-primary ml-2 mx-md-2"><i
                    class="fal fa-arrow-alt-left"></i> Go Back </a>
            @if ($transactionRequest->status == 'Pending' || $transactionRequest->status == 'Rejected')
                <a href="{{ url('finance-management/pending-transactions/print/' . $transactionRequest->id) }}"
                    class="btn btn-primary ml-2 mx-md-2" target="_blank"><i class="fal fa-print"></i> Print </a>
            @else
                @php
                    $trn_id = $transactionRequest->transactions->where('transaction_type', 'Debit')->first()->id;
                @endphp
                <a href="{{ url('finance-management/transactions/print/' . $trn_id) }}" class="btn btn-primary mr-3"
                    target="_blank"><i class="fal fa-print"></i> Print </a>
            @endif

        </div>
    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false"
        data-panel-color="false" data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                Internal Transfer Transaction
            </h2>
        </div>

        <div class="panel-container show mb-3">
            <div class="panel-content" style="overflow: scroll;">
                @include('common.error-message')
                @php
                    $sender = $transactionRequest->account;
                    $receiver = $transactionRequest->receiver;

                @endphp
                <form id="approval-form" method="post"
                    action="{{ url('/finance-management/pending-transaction/approve/internal') }}"
                    enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" id="id" name="id" value="{{ $transactionRequest->id }}">
                    <input type="hidden" name="credit_currency_id" id="credit_currency_id"
                        value="{{ $sender->account_currency }}">
                    <input type="hidden" name="debit_currency_id" id="debit_currency_id"
                        value="{{ $receiver->account_currency }}">

                    <table class="table table-bordered table-hover m-0 shadow" id="data-grid">
                        <thead class="thead-dark">
                            <tr>
                                <th width="200">From Account</th>
                                <td>
                                    {{ $sender->title . ' - ' . $sender->account_number }}
                                </td>
                            </tr>

                            <!-- check if the internal transfer -->
                            @if ($transactionRequest->receiver_account_number != '')
                                <tr>
                                    <th>To Account</th>
                                    <td>{{ $receiver->title . ' - ' . $receiver->account_number }}</td>
                                </tr>
                            @endif
                            <tr>
                                <th>Amount</th>
                                <td>{{ number_format($transactionRequest->amount, 2) }}</td>
                            </tr>
                            <tr>
                                <th>Request Date</th>
                                <td>{{ \Carbon\Carbon::parse($transactionRequest->created_at)->format('d/m/Y') }}</td>
                            </tr>
                            <tr>
                                <th>Transaction Date</th>
                                <td>{{ \Carbon\Carbon::parse($transactionRequest->transaction_date)->format('d/m/Y') }}
                                </td>
                            </tr>
                            <tr>
                                <th>Mode</th>
                                <td>{{ $transactionRequest->transaction_nature }}</td>
                            </tr>

                            <tr>
                                <th>Remarks</th>
                                <td>{{ $transactionRequest->remarks }}</td>
                            </tr>

                            <tr>
                                <th>Payment Type </th>
                                @if ($payment_type)
                                    <td>{{ $payment_type->value }}</td>
                                @else
                                    <td>N/A</td>
                                @endif
                            </tr>

                            <tr>
                                <th>Transaction Notes</th>
                                <td>
                                    @include('finance-management.transactions.partials.transaction-notes', [
                                        'transaction_request_id' => $transactionRequest->id,
                                    ])
                                </td>
                            </tr>
                            @php
                                $docs = $transactionRequest->docs;
                            @endphp

                            <tr>
                                <th>Supporting Documents</th>
                                <td>
                                    @if ($transactionRequest->status == 'Rejected')
                                        @if ($docs)
                                            @foreach ($docs as $doc)
                                                <a href="{{ $doc->url }}" class="btn btn-sm btn-outline-secondary"
                                                    target="_blank"><i class="fal fa-file-alt"></i> View</a>
                                            @endforeach
                                        @endif
                                    @else
                                        @include(
                                            'finance-management.transactions.partials.update-supporting-documents',
                                            ['status' => $transactionRequest->status]
                                        )
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Rejected By</th>
                                <td>
                                    {{ $transactionRequest->rejector ? $transactionRequest->rejector->name : '--' }}
                                </td>
                            </tr>
                            <tr>
                                <th>Status</th>
                                <td><span
                                        class="badge {{ $statusBadge[$transactionRequest->status] }}">{{ $transactionRequest->status }}</span>
                                </td>
                            </tr>
                            @if ($transactionRequest->status == 'Rejected')
                                <tr>
                                    <th>Reject Reason</th>
                                    <td>{{ $transactionRequest->reject_reason }}</td>
                                </tr>
                            @endif
                        </thead>
                    </table>
                    <hr>
                    @can('approve transaction')
                        @if ($transactionRequest->status == 'Pending')
                            <div class="row">
                                <div class="col-md-12">
                                    <button type="submit" class="btn btn-success">Approve</button>
                                    <button onclick="rejectTransaction(); return false;" class="btn btn-danger">Reject</button>
                                </div>
                            </div>
                        @endif
                    @endcan
                </form>

                <form id="save-note" method="POST" action="{{ url('/finance-management/transactions/save-note') }}">
                    @csrf
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="reject-modal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title ">Reject Transaction</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true"><i class="fal fa-times"></i></span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="{{ url('/finance-management/pending-transaction/reject/internal') }}" method="post">
                        @csrf
                        <input type="hidden" name="id" id="id" value="{{ $transactionRequest->id }}">
                        <div class="form-group">
                            <label class="form-label" for="reject_reason">Reject Reason</label>
                            <textarea required class="form-control" name="reject_reason" id="reject_reason" cols="30" rows="2"></textarea>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-danger">Reject</button>
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>

    <form method="POST" name="delete-file" id="delete-file"
        action="{{ url('/finance-management/transactions/files/delete') }}">
        @csrf
        <input type="hidden" name="file_id" id="file_id" value="">
    </form>

@stop
@section('scripts')
    <script>
        var controls = {
            leftArrow: '<i class="fal fa-angle-left" style="font-size: 1.25rem"></i>',
            rightArrow: '<i class="fal fa-angle-right" style="font-size: 1.25rem"></i>'
        }

        $("#approval-form").on('submit', function(e) {
            e.preventDefault();
            Swal.fire({
                title: "Are you sure?",
                text: "Transaction will be approved",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Yes, Approve it!"
            }).then(function(result) {
                if (result.value) {
                    document.getElementById('approval-form').submit()
                }
            });
        });

        function approve() {

            Swal.fire({
                title: "Are you sure?",
                text: "Transaction will be approved",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Yes, Approve it!"
            }).then(function(result) {
                if (result.value) {
                    document.getElementById('approval-form').submit()
                }
            });
        }

        function checkIfNotZero(obj) {
            if (!parseFloat(obj.value) > 0) {
                Swal.fire({
                    title: "Wrong Value",
                    text: "Exchange rate can't be zero, provide a value greater than zero",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonText: "Ok"
                }).then(function(result) {});

                $(obj).val('');
            }
        }

        function setExchangeRate() {

            const debit_currency_id = parseInt($("#debit_currency_id").val());
            const credit_currency_id = parseInt($("#credit_currency_id").val());

            if (debit_currency_id > 0 && credit_currency_id > 0 && debit_currency_id != credit_currency_id) {
                $("#ex_rate").prop("readonly", false);
                $("#ex_rate").prop("placeholder", "please provide exchange rate");
                $("#ex_rate").val("");
            } else {
                $("#ex_rate").prop("readonly", true);
                $("#ex_rate").val(1);
            }
        }

        $(document).ready(function() {

            setExchangeRate();

            $('.select2').select2();

            // minimum setup
            $('#transaction_date').datepicker({
                format: 'dd/mm/yyyy',
                todayHighlight: true,
                orientation: "bottom left",
                templates: controls,
                endDate: "{{ \Carbon\Carbon::today()->format('d/m/Y') }}"
            });
        })


        function rejectTransaction() {
            $("#reject-modal").modal('show');
        }
    </script>
@stop
