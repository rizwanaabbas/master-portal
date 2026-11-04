@extends('layouts.cp')
@section('title', 'TT Transaction - Finance Management')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Finance Management</span>
        </h1>
        <div class="d-flex">
            <a href="{{ url('finance-management/pending-transactions') }}" class="btn btn-primary ml-2 mx-md-2"><i
                    class="fal fa-arrow-alt-left"></i> Go Back </a>
            <a href="{{ url('finance-management/pending-transactions/print/' . $transaction->id) }}"
                class="btn btn-primary ml-2 mx-md-2" target="_blank"><i class="fal fa-print"></i> Print </a>

        </div>
    </div>
    @php
        $disabled = '';

        if (in_array($transaction->compliance_status, $complianceStatus) && in_array('NeroData', $enabledModules)) {
            $disabled = 'disabled';
        }

    @endphp
    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false"
        data-panel-color="false" data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                TT Transaction
            </h2>

        </div>

        <div class="panel-container show mb-3">
            <div class="panel-content" style="overflow: scroll;">
                @if (!empty($disabled) && in_array('NeroData', $enabledModules))
                    <div class="alert alert-warning">Please contact compliance team to clear this transaction. Current
                        status from transaction monitoring is <strong>{{ $transaction->compliance_status }}</strong> </div>
                @endif
                @include('common.error-message')
                <form id="approval-form" method="post"
                    action="{{ url('/finance-management/pending-transaction/approve/tt') }}" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" id="id" name="id" value="{{ $transaction->id }}">
                    <table class="table table-bordered table-hover m-0 shadow" id="data-grid">
                        @php
                            $fromTo = $transaction->transaction_type == 'Debit' ? 'From' : 'To';
                        @endphp
                        <thead class="thead-dark">
                            <tr>
                                <th width="200">{{ $fromTo }} Account</th>
                                <td>{{ $transaction->account->title . ' - ' . $transaction->account->account_number }}</td>
                            </tr>
                            @include('finance-management.transactions.partials.tr-approval-balance-rows', [
                                'trRequest' => $transaction,
                            ])
                            <tr>
                                <th>Amount</th>
                                <td>{{ number_format($transaction->amount, 2) }}</td>
                            </tr>
                            <tr>
                                <th>Request Date</th>
                                <td>{{ \Carbon\Carbon::parse($transaction->updated_at)->format('d M, Y') }}</td>
                            </tr>

                            <!-- check if the TT is for external transfer -->
                            @if ($transaction->beneficiary_id > 0)
                                <tr>
                                    <th><label class="form-label" for="transaction_date">Transaction Date</label></th>
                                    <td>
                                        <div class="row">
                                            <div class="col-md-2">
                                                <input type="text" autocomplete="off" class="form-control"
                                                    value="{{ \Carbon\Carbon::parse($transaction->transaction_date)->format('d/m/Y') }}"
                                                    name="transaction_date" id="transaction_date" placeholder="Select date"
                                                    required>
                                                @error('transaction_date')
                                                    <span class="invalid-feedback"
                                                        role="alert"><strong>{{ $message }}</strong></span>
                                                @enderror
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Beneficiary</th>
                                    <td><button onclick="viewBeneficiary();" type="button"
                                            class="btn btn-outline-secondary btn-sm">{{ $transaction->beneficiary->name }}</button>
                                    </td>
                                </tr>
                            @else
                                <tr>
                                    <th>Deposit Date</th>
                                    <td>
                                        @if ($transaction->status != 'Initial Review')
                                            <div class="row">
                                                <div class="col-md-2">
                                                    <input type="text" autocomplete="off" class="form-control"
                                                        value="{{ \Carbon\Carbon::parse($transaction->transaction_date)->format('d/m/Y') }}"
                                                        name="transaction_date" id="transaction_date"
                                                        placeholder="Select date" required>
                                                    @error('transaction_date')
                                                        <span class="invalid-feedback"
                                                            role="alert"><strong>{{ $message }}</strong></span>
                                                    @enderror
                                                </div>
                                            </div>
                                            @else{{ \Carbon\Carbon::parse($transaction->transaction_date)->format('d M, Y') }}
                                        @endif
                                    </td>
                                </tr>
                            @endif
                            <tr>
                                <th>Type</th>
                                <td>{{ $transaction->transaction_type }}</td>
                            </tr>
                            <tr>
                                <th>Mode</th>
                                <td>{{ $transaction->transaction_nature }}</td>
                            </tr>
                            <tr>
                                <th>Remarks</th>
                                <td>
                                    @if ($transaction->status == 'Pending')
                                        <div class="row">
                                            <div class="col-md-12">
                                                <textarea name="remarks" id="remarks" class="form-control" cols="30" rows="2">{{ $transaction->remarks }}</textarea>
                                            </div>
                                        @else
                                            {{ $transaction->remarks }}
                                    @endif
                                </td>
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
                                <th>Supporting Documents</th>
                                <td>
                                    @php
                                        $docs = $transaction->docs;
                                    @endphp

                                    @if ($transaction->status == 'Rejected' || $transaction->status == 'Initial Review')
                                        @php
                                            if ($transaction->linkedTrn && $transaction->linkedTrn->is_for_client) {
                                                $docs = $transaction->linkedTrn->is_for_client
                                                    ? $transaction->linkedTrn->docs
                                                    : $transaction->docs;
                                            }
                                        @endphp

                                        @if ($docs)
                                            @foreach ($docs as $doc)
                                                <a href="{{ $doc->url }}" class="btn btn-sm btn-outline-secondary"
                                                    target="_blank"><i class="fal fa-file-alt"></i> View</a>
                                            @endforeach
                                        @endif
                                    @else
                                        @include(
                                            'finance-management.transactions.partials.update-supporting-documents',
                                            ['status' => $transaction->status]
                                        )
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Status</th>
                                <td><span
                                        class="badge {{ $statusBadge[$transaction->status] }}">{{ $transaction->status }}</span>
                                </td>
                                @if ($transaction->status == 'Rejected')
                            <tr>
                                <th>Reason</th>
                                <td>{{ $transaction->reject_reason }}</td>
                            </tr>
                            @endif
                            {{-- @if (in_array('Compliance', $enabledModules))
                                <tr>
                                    <th>Case ID</th>
                                    <td>{{ $transaction->case_id }}</td>
                                </tr>
                                </tr>

                                @if (in_array('Compliance', $enabledModules) && isset($transaction->is_suspicious))
                                    <tr>
                                        <th>
                                            Is Suspicious
                                        </th>
                                        @php  $isSuspicious = [0 => 'No', 1 => 'Yes']; @endphp
                                        <td>
                                            <span
                                                class="badge {{ $statusBadge[$transaction->is_suspicious . '_suspicious'] }}">{{ $isSuspicious[$transaction->is_suspicious] }}</span>

                                        </td>
                                    </tr>
                                @endif
                            @endif --}}
                            @if ($transaction->corresponding_bank_name)
                                <tr>
                                    <th colspan="2">Corresponding Bank Details</th>
                                </tr>
                                <tr>
                                    <th>Bank Name</th>
                                    <td>{{ $transaction->corresponding_bank_name }}</td>
                                </tr>
                                <tr>
                                    <th>Swift Code</th>
                                    <td>{{ $transaction->corresponding_bank_swift_code }}</td>
                                </tr>
                                <tr>
                                    <th>Bank Address</th>
                                    <td>{{ $transaction->corresponding_bank_address }}</td>
                                </tr>
                            @endif
                            @if ($transaction->senderDetails)
                                <tr>
                                    <th colspan="2">Sender Details</th>
                                </tr>
                                <tr>
                                    <th>Sender Name</th>
                                    <td>{{ $transaction->senderDetails->sender_name }}</td>
                                </tr>
                                <tr>
                                    <th>Sender Address</th>
                                    <td>{{ $transaction->senderDetails->sender_address }}</td>
                                </tr>
                                <tr>
                                    <th>Sender City</th>
                                    <td>{{ $transaction->senderDetails->sender_city }}</td>
                                </tr>
                                <tr>
                                    <th>Sender Country</th>
                                    <td>{{ $transaction->senderDetails->senderCountry->name ?? 'N/A' }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>Bank Name</th>
                                    <td>{{ $transaction->senderDetails->sending_bank_name }}</td>
                                </tr>
                                <tr>
                                    <th>Bank Address</th>
                                    <td>{{ $transaction->senderDetails->sending_bank_address }}</td>
                                </tr>
                                <tr>
                                    <th>Bank City</th>
                                    <td>{{ $transaction->senderDetails->sending_bank_city }}</td>
                                </tr>
                                <tr>
                                    <th>Bank Country</th>
                                    <td>{{ $transaction->senderDetails->sendingBankCountry->name ?? 'N/A' }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>SWIFT Code</th>
                                    <td>{{ $transaction->senderDetails->sending_bank_swift }}</td>
                                </tr>

                                @if ($transaction->senderDetails->intermediary_bank_name || $transaction->senderDetails->intermediary_bank_swift)
                                    <tr>
                                        <th>Intermediary Bank Name</th>
                                        <td>{{ $transaction->senderDetails->intermediary_bank_name ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Intermediary SWIFT Code</th>
                                        <td>{{ $transaction->senderDetails->intermediary_bank_swift ?? 'N/A' }}</td>
                                    </tr>
                                @endif
                                <tr>
                                    <th colspan="2"></th>
                                </tr>
                            @endif
                            @if (in_array('NeroData', $enabledModules))
                                <tr>
                                    <th>Transaction Monitoring Status</th>
                                    <td><span>{{ $transaction->compliance_status }}</span>
                                        @if (!empty($rulesText))
                                            {!! $rulesText !!}
                                        @endif
                                    </td>
                                </tr>
                            @endif
                        </thead>
                    </table>
                    <hr>

                    @if (auth()->user()->hasAnyRole(['finance', 'finance manager', 'super admin']) &&
                            ($transaction->status == 'Pending' || $transaction->status == 'Initial Review'))
                        <div class="row">
                            <div class="col-md-12">
                                @if (!empty($disabled) && in_array('NeroData', $enabledModules))
                                    <div class="alert alert-warning">Please contact compliance team to clear this
                                        transaction. Current status from transaction monitoring is
                                        <strong>{{ $transaction->compliance_status }} <a
                                                href="{{ $moderate_url ?? $portalTransactionsUrl . '/' . $neroDataTransactionId }}"
                                                target="_blank" rel="noopener" class="btn btn-warning ml-2 mr-2">
                                                Moderate
                                            </a></strong>
                                    </div>
                                @endif

                                @can('approve transaction')
                                    @if ($transaction->status != 'Approved' && $transaction->status != 'Initial Review')
                                        <button type="button" onclick="approve();" class="btn btn-success" id="approveButton"
                                            {{-- @if ($transaction->is_suspicious && !$transaction->is_resolved) disabled @endif --}}>Approve</button>
                                    @endif

                                    @if ($transaction->status != 'Rejected')
                                        <button onclick="rejectTransaction(); return false;"
                                            class="btn btn-danger">Reject</button>
                                    @endif
                                @endcan
                                @if ($transaction->status == 'Initial Review')
                                    <button type="button" onclick="approveReview();" class="btn btn-info"
                                        id="approveReviewButton" {{-- {{ $disabled }}
                                        @if ($transaction->is_suspicious && !$transaction->is_resolved) disabled @endif --}}>Approve
                                        Review</button>
                                @endif
                            </div>
                        </div>
                    @endif
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
                    <form action="{{ url('/finance-management/pending-transaction/reject/tt') }}" method="post">
                        @csrf
                        <input type="hidden" name="id" id="id" value="{{ $transaction->id }}">
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

    @if ($transaction->beneficiary_id > 0)
        <div class="modal fade" id="beneficiary-modal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title ">Beneficiary Details</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true"><i class="fal fa-times"></i></span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <table class="table table-bordered table-hover m-0 shadow">
                            <thead class="thead-dark">
                                <tr>
                                    <th width="150">Name</th>
                                    <td>{{ $transaction->beneficiary->name }}</td>
                                </tr>
                                <tr>
                                    <th>Account Number</th>
                                    <td>{{ $transaction->beneficiary->account_number }}</td>
                                </tr>
                                <tr>
                                    <th>IBAN Number</th>
                                    <td>{{ $transaction->beneficiary->iban }}</td>
                                </tr>
                                <tr>
                                    <th>Beneficiary Address</th>
                                    <td>{{ $transaction->beneficiary->address }}</td>
                                </tr>
                                <tr>
                                    <th>Bank Name</th>
                                    <td>{{ $transaction->beneficiary->bank_name }}</td>
                                </tr>
                                <tr>
                                    <th>Swift Code</th>
                                    <td>{{ $transaction->beneficiary->swift_code }}</td>
                                </tr>
                                <tr>
                                    <th>Bank Address</th>
                                    <td>{{ $transaction->beneficiary->bank_address }}</td>
                                </tr>
                                <tr>
                                    <th>Status</th>
                                    <td><span>{{ $transaction->beneficiary->status }}</span></td>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endif
    <form method="POST" name="review-approval-form" id="review-approval-form"
        action="{{ url('/finance-management/initial-review-transactions/approve-review') }}">
        @csrf
        <input type="hidden" id="id" name="id" value="{{ $transaction->id }}">
    </form>

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

        function approve() {
            var isDisabled = document.getElementById('approveButton').disabled;

            if (!isDisabled) {
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

        }

        function approveReview() {
            var isDisabled = document.getElementById('approveReviewButton').disabled;

            if (!isDisabled) {
                Swal.fire({
                    title: "Are you sure?",
                    text: "Transaction will be moved to Pending.",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonText: "Yes, Approve the Review!"
                }).then(function(result) {
                    if (result.value) {
                        document.getElementById('review-approval-form').submit()
                    }
                });
            }

        }

        function rejectTransaction() {
            $("#reject-modal").modal('show');
        }

        function viewBeneficiary() {
            $("#beneficiary-modal").modal('show');
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
