@extends('layouts.cp')
@section('title', 'Pending Top-up - Card Management')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Card Management</span>
        </h1>
        <div class="d-flex">
            <a href="{{ url('card-management/card/topup-pending-requests/list') }}" class="btn btn-primary ml-2 mx-md-2"><i
                    class="fal fa-arrow-alt-left"></i> Go Back </a>
            <a href="{{ url('finance-management/pending-transactions/print/' . $transactionRequest->id) }}"
                class="btn btn-primary ml-2 mx-md-2" target="_blank"><i class="fal fa-print"></i> Print </a>
        </div>
    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false"
        data-panel-color="false" data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            @php
                $disabled = '';

                if (
                    in_array($transactionRequest->compliance_status, $complianceStatus) &&
                    in_array('NeroData', $enabledModules)
                ) {
                    $disabled = 'disabled';
                }

            @endphp
            <h2>
                Pending Top-up
            </h2>
        </div>

        <div class="panel-container show mb-3">
            <div class="panel-content" style="overflow: scroll;">
                @if (!empty($disabled) && in_array('NeroData', $enabledModules))
                    <div class="alert alert-warning">Please contact compliance team to clear this transaction. Current
                        status from transaction monitoring is <strong>{{ $transactionRequest->compliance_status }}</strong>
                    </div>
                @endif
                @include('common.error-message')
                <form id="approval-form" method="post"
                    action="{{ url('/card-management/card/topup-pending-requests/approve') }}">
                    @csrf
                    <input type="hidden" id="id" name="id" value="{{ $transactionRequest->id }}">
                    <table class="table table-bordered table-hover m-0 shadow" id="data-grid">
                        <thead class="thead-dark">
                            <tr>
                                <th width="200">From Account</th>
                                <td>{{ $transactionRequest->account->title . ' - ' . $transactionRequest->account->account_number }}
                                </td>
                            </tr>
                            @include('finance-management.transactions.partials.tr-approval-balance-rows', [
                                'trRequest' => $transactionRequest,
                            ])
                            <tr>
                                <th>Amount ({{ $transactionRequest->account->currency->short }})</th>
                                <td>{{ number_format($transactionRequest->amount, 2) }}</td>
                            </tr>


                            @if ($convert == 1)
                                <tr>
                                    <th>Amount in {{ $card_currency }}</th>
                                    <td><input type="number" required value="" name="amount_in_usd"
                                            value="{{ old('amount_in_usd', $amount_in_usd ?? '') }}"
                                            placeholder="Conveted to {{ $card_currency }}"
                                            title="Converted amount from {{ $transactionRequest->account->currency->short }} to {{ $card_currency }}" />
                                        @error('amount_in_usd')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </td>
                                </tr>

                                {{--
                                @php $areFeesApplied = 1; @endphp
                                @include('global-partials.transactions.client.fee') --}}
                            @endif

                            <tr>
                                <th>Request Date</th>
                                <td>{{ \Carbon\Carbon::parse($transactionRequest->created_at)->format('d M, Y') }}</td>
                            </tr>
                            <tr>
                                <th>Top-up Date</th>
                                <td>
                                    <input type="text" required
                                        value="{{ \Carbon\Carbon::parse($transactionRequest->transaction_date)->format('d/m/Y') }}"
                                        autocomplete="off" class="form-control w-25" name="transaction_date"
                                        id="transaction_date" placeholder="Select date">
                                </td>
                            </tr>
                            <tr>
                                <th>Type</th>
                                <td>{{ $transactionRequest->transaction_type }}</td>
                            </tr>
                            <tr>
                                <th>Mode</th>
                                <td>{{ $transactionRequest->transaction_nature }}</td>
                            </tr>
                            <tr>
                                <th>Remarks</th>
                                <td>
                                    @if ($transactionRequest->status == 'Pending')
                                        <div class="row">
                                            <div class="col-md-12">
                                                <textarea name="remarks" id="remarks" class="form-control" cols="30" rows="2">{{ $transactionRequest->remarks }}</textarea>
                                            </div>
                                        @else
                                            {{ $transactionRequest->remarks }}
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Transaction Notes</th>
                                <td>
                                    @include('finance-management.transactions.partials.transaction-notes', [
                                        'transaction_request_id' => $transactionRequest->id,
                                    ])
                                </td>
                            </tr>
                            <tr>
                                <th>Status</th>
                                <td>
                                    <span
                                        class="badge {{ $statusBadge[$transactionRequest->status] }}">{{ $transactionRequest->status }}</span>
                                </td>

                            </tr>
                            <tr>
                                <th>Provider</th>
                                <td>{{ ucwords(str_replace('_', ' ', $transactionRequest->card->provider)) }}</td>
                            </tr>
                            @if ($transactionRequest->status == 'Rejected')
                                <tr>
                                    <th>Reason</th>
                                    <td>{{ $transactionRequest->reject_reason }}</td>
                                </tr>
                            @endif
                            @if (in_array('NeroData', $enabledModules))
                                <tr>
                                    <th>Transaction Monitoring Status</th>

                                    <td><span>{{ $transactionRequest->compliance_status }}</span>
                                        @if (!empty($rulesText))
                                            {!! $rulesText !!}
                                        @endif
                                        @php $notes = collect($amlNoteTexts ?? []); @endphp
                                        @if ($notes->isNotEmpty())
                                            <div class="mt-2">
                                                <strong>AML Notes</strong>
                                                <ul class="mb-0">
                                                    @foreach ($notes as $note)
                                                        <li>{{ $note }}</li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @endif
                        </thead>
                    </table>
                    <hr>
                    @if ($transactionRequest->status == 'Pending')
                        <div class="row">
                            <div class="col-md-12">
                                @if (!empty($disabled) && in_array('NeroData', $enabledModules))
                                    <div class="alert alert-warning">Please contact compliance team to clear this
                                        transaction. Current status from transaction monitoring is
                                        <strong>{{ $transactionRequest->compliance_status }}<a
                                                href="{{ $moderate_url ?? $portalTransactionsUrl . '/' . $neroDataTransactionId }}"
                                                target="_blank" rel="noopener" class="btn btn-warning ml-2 mr-2">
                                                Moderate
                                            </a></strong>
                                    </div>
                                @endif
                                @if ($transactionRequest->status != 'Approved')
                                    <button type="button" onclick="approve();" class="btn btn-success"
                                        {{-- {{ $disabled }}
                                        @if ($transactionRequest->is_suspicious && !$transactionRequest->is_resolved) disabled @endif --}}>Approve</button>
                                @endif

                                @if ($transactionRequest->status != 'Rejected')
                                    <button onclick="rejectTransaction(); return false;"
                                        class="btn btn-danger">Reject</button>
                                @endif
                            </div>
                        </div>
                    @endif
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
                    <form action="{{ url('/card-management/card/topup-pending-requests/reject') }}" method="post">
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

@stop
@section('scripts')
    <script>
        var controls = {
            leftArrow: '<i class="fal fa-angle-left" style="font-size: 1.25rem"></i>',
            rightArrow: '<i class="fal fa-angle-right" style="font-size: 1.25rem"></i>'
        }

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

        function rejectTransaction() {
            $("#reject-modal").modal('show');
        }

        $(document).ready(function() {

            // minimum setup
            $('#transaction_date').datepicker({
                format: 'dd/mm/yyyy',
                todayHighlight: true,
                orientation: "bottom left",
                templates: controls
            });
        })
    </script>
@stop
