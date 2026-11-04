@extends('layouts.cp')
@section('title', 'Sell-Crypto Transaction - Crypto Management')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Crypto Management</span>
        </h1>
        <div class="d-flex">
            <a href="{{ url('crypto-management/crypto-pending-transactions') }}" class="btn btn-primary ml-2 mx-md-2"><i
                    class="fal fa-arrow-alt-left"></i> Go Back </a>
            <a href="{{ url('crypto-management/crypto-pending-transactions/print/' . $transaction->id) }}"
                class="btn btn-primary ml-2 mx-md-2" target="_blank"><i class="fal fa-print"></i> Print </a>
        </div>
    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false"
        data-panel-color="false" data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            @php
                $disabled = '';

                if (
                    in_array($transaction->compliance_status, $complianceStatus) &&
                    $transaction->status == 'Pending' &&
                    in_array('NeroData', $enabledModules)
                ) {
                    $disabled = 'disabled';
                }

            @endphp
            <h2>
                Crypto Sell Transaction
            </h2>
        </div>

        <div class="panel-container show mb-3">
            <div class="panel-content" style="overflow: scroll;">
                @if (!empty($disabled) && $transaction->status == 'Pending' && in_array('NeroData', $enabledModules))
                    <div class="alert alert-warning">Please contact compliance team to clear this transaction. Current
                        status from transaction monitoring is <strong>{{ $transaction->compliance_status }}</strong>
                    </div>
                @endif
                @include('common.error-message')

                @php
                    $amount = $transaction->amount;
                    $transaction_date = $transaction->created_at;
                    $remarks = $transaction->remarks;
                    $reviewer = isset($detailsChanges['maker']) ? $detailsChanges['maker'] : null;
                    $approver = $transaction->displayApproverName();
                @endphp

                {{-- @can('approve transaction') --}}
                @php
                    $needle = 'approve/sell';

                    $amount = isset($detailsChanges['amount'])
                        ? $detailsChanges['amount']['new_value'][0]
                        : $transaction->amount;

                    $transaction_date = isset($detailsChanges['transaction_date'])
                        ? $detailsChanges['transaction_date']['new_value'][0]
                        : $transaction->transaction_date;

                    $remarks = isset($detailsChanges['remarks'])
                        ? $detailsChanges['remarks']['new_value'][0]
                        : $transaction->remarks;
                @endphp
                {{-- @endcan --}}

                @role('finance')
                    @php
                        $needle = 'save-changes';
                    @endphp
                @endrole

                <form id="approval-form" method="post"
                    action="{{ url('/crypto-management/crypto-pending-transaction/' . $needle) }}"
                    enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" id="id" name="id" value="{{ $transaction->id }}">
                    <table class="table table-bordered table-hover m-0 shadow" id="data-grid">
                        <thead class="thead-dark">
                            <tr>
                                <th width="200">Fiat Account</th>
                                <td>{{ $transaction->receiver->title . ' - ' . $transaction->receiver->account_number }}
                                </td>
                            </tr>
                            @include('finance-management.transactions.partials.tr-approval-balance-rows', [
                                'trRequest' => $transaction,
                            ])
                            <tr>
                                <th width="200">Amount in Fiat</th>
                                <td>
                                    <div class="row">
                                        <div class="col-md-3 field-container">
                                            <div class="form-group">
                                                <input type="number" step="any" id="amount" name="amount"
                                                    value="{{ $amount }}" class="form-control" required />
                                                <span id="amount-message" style="color: red;"></span>
                                                @error('amount')
                                                    <span class="invalid-feedback"
                                                        role="alert"><strong>{{ $message }}</strong></span>
                                                @enderror
                                            </div>

                                            @if (isset($detailsChanges['amount']))
                                                {!! $detailsChanges['amount']['tooltip'] !!}
                                            @endif
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <th width="200">Crypto Account</th>
                                <td>{{ $transaction->account->title . ' - ' . $transaction->account->account_number }}
                                </td>
                            </tr>
                            <tr>
                                <th>Amount in Crypto</th>
                                <td><img src="{{ $transaction->account->currency->logo }}"
                                        alt="{{ $transaction->account->currency->name }}" height="17px"
                                        style="vertical-align: middle;" />&nbsp;{{ \App\Helpers\Common::formatNumber($transaction->crypto_amount, true) }}
                                </td>
                            </tr>
                            <tr>
                                <th>Request Date</th>
                                <td>{{ \Carbon\Carbon::parse($transaction->created_at)->format('d M, Y') }}</td>
                            </tr>

                            <tr>
                                <th>Selling Date</th>
                                <td>
                                    <div class="row">
                                        <div class="col-md-3 field-container">
                                            <input type="text" autocomplete="off" class="form-control"
                                                value="{{ \Carbon\Carbon::parse($transaction_date)->format('d/m/Y') }}"
                                                name="transaction_date" id="transaction_date" placeholder="Select date"
                                                required>
                                            @error('transaction_date')
                                                <span class="invalid-feedback"
                                                    role="alert"><strong>{{ $message }}</strong></span>
                                            @enderror

                                            @if (isset($detailsChanges['transaction_date']))
                                                {!! $detailsChanges['transaction_date']['tooltip'] !!}
                                            @endif
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <th>Mode</th>
                                <td>{{ $transaction->transaction_nature }}</td>
                            </tr>
                            <tr>
                                <th>Debit Remarks</th>
                                <td>
                                    @if ($transaction->status == 'Pending')
                                        <div class="row">
                                            <div class="col-md-12 field-container">
                                                <textarea name="debit_remarks" id="remarks" class="form-control" cols="30" rows="2">{{ $remarks }}</textarea>

                                                @if (isset($detailsChanges['remarks']))
                                                    {!! $detailsChanges['remarks']['tooltip'] !!}
                                                @endif
                                            </div>
                                        </div>
                                    @else
                                        {{ $transaction->remarks }}
                                    @endif
                                </td>
                            </tr>
                            @if ($transaction->status == 'Pending')
                                <tr>
                                    <th>Credit Remarks</th>
                                    <td>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <textarea name="credit_remarks" id="remarks" class="form-control" cols="30" rows="2">{{ $transaction->remarks }}</textarea>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endif
                            <tr>
                                <th>Transaction Notes</th>
                                <td>
                                    @include('finance-management.transactions.partials.transaction-notes', [
                                        'transaction_request_id' => $transaction->id,
                                    ])
                                </td>
                            </tr>
                            <tr>
                                <th>Supporting Documents</th>
                                <td>
                                    @if ($transaction->docs)
                                        @foreach ($transaction->docs as $doc)
                                            <a href="{{ $doc->url }}" class="btn btn-sm btn-outline-secondary"
                                                target="_blank"><i class="fal fa-file-alt"></i> View</a>
                                        @endforeach
                                    @else
                                        --
                                    @endif

                                    <div class="row">
                                        <div class="col-md-6">
                                            @include('finance-management.transactions.partials.new-supporting-documents')
                                        </div>
                                    </div>

                                </td>
                            </tr>
                            <tr>
                                <th>Status</th>
                                <td><span
                                        class="badge {{ $statusBadge[$transaction->status] }}">{{ $transaction->status }}</span>
                                </td>

                            </tr>
                            @if ($transaction->status == 'Rejected')
                                <tr>
                                    <th>Reason</th>
                                    <td>{{ $transaction->reject_reason }}</td>
                                </tr>
                            @endif
                            @if ($transaction->crypto_system != '')
                                <tr>
                                    <th>Crypto Portal</th>
                                    <td>{{ ucwords(str_replace('_', ' ', $transaction->crypto_system)) }}</td>
                                </tr>
                            @endif
                            @if (count($aml_results) > 0)
                                <tr>
                                    <th>AML Results</th>
                                    <td>
                                        <div class="mt-2">
                                            @foreach ($aml_results as $aml)
                                                <span class="badge badge-{{ $aml['badge'] }}">
                                                    {{ $aml['status'] }}</span>
                                                <i class="fal fa-question-circle {{ $aml['badge'] }}" data-toggle="tooltip"
                                                    data-placement="auto" title=""
                                                    data-original-title="{{ $aml['rule_description'] }}"></i>
                                            @endforeach
                                        </div>
                                    </td>
                                </tr>
                            @endif
                            @if ($approver)
                                <tr>
                                    <th>Approver</th>
                                    <td>
                                        {{ $approver }}
                                    </td>
                                </tr>
                            @endif

                            @if ($reviewer)
                                <tr>
                                    <th>Reviewer</th>
                                    <td>
                                        {{ $reviewer }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>Note</th>
                                    <td>
                                        Fields that are been changed, are highlighted by the Red Dot.
                                    </td>
                                </tr>
                                <tr>
                                    <th>Transaction Monitoring Status</th>

                                    <td><span>{{ $transaction->compliance_status }}</span>
                                        @if (!empty($rulesText))
                                            {!! $rulesText !!}
                                        @endif
                                    </td>
                                </tr>

                            @endif
                            @if (in_array('NeroData', $enabledModules))
                                <tr>
                                    <th>Transaction Monitoring Status</th>

                                    <td><span>{{ $transaction->compliance_status }}</span>
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
                    @if ($transaction->status == 'Pending')
                        @can('approve crypto transaction')
                            @if (!empty($disabled) && $transaction->status == 'Pending' && in_array('NeroData', $enabledModules))
                                <div class="alert alert-warning">Please contact compliance team to clear this
                                    transaction. Current status from transaction monitoring is
                                    <strong>{{ $transaction->compliance_status }}<a
                                            href="{{ $moderate_url ?? $portalTransactionsUrl . '/' . $neroDataTransactionId }}"
                                            target="_blank" rel="noopener" class="btn btn-warning ml-2 mr-2">
                                            Moderate
                                        </a></strong>
                                </div>
                            @endif
                            <div class="row">
                                <div class="col-md-12">
                                    @if ($transaction->status != 'Approved')
                                        <button type="button" onclick="approve();" class="btn btn-success"
                                            {{-- {{ $disabled }}
                                            @if ($transaction->is_suspicious && !$transaction->is_resolved) disabled @endif --}}>Approve</button>
                                    @endif

                                    @if ($transaction->status != 'Rejected')
                                        <button onclick="rejectTransaction(); return false;"
                                            class="btn btn-danger">Reject</button>
                                    @endif
                                </div>
                            </div>
                        @endcan

                        @role('finance')
                            <div class="row">
                                <div class="col-md-12">
                                    <button type="submit" class="btn btn-primary">Save</button>
                                </div>
                            </div>
                        @endrole
                    @endif
                </form>
                <form id="save-note" method="POST" action="{{ url('/finance-management/transactions/save-note') }}">
                    @csrf
                </form>
            </div>
        </div>
    </div>

    @include('crypto-management.crypto-transactions.partials.fireblocks-monitoring-panel', [
        'transactionRequest' => $transaction,
        'fireblocksMonitoring' => $fireblocksMonitoring ?? null,
    ])

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
                    <form action="{{ url('/crypto-management/crypto-pending-transaction/reject/crypto-transaction') }}"
                        method="post">
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

@stop
@section('scripts')
    <script>
        var controls = {
            leftArrow: '<i class="fal fa-angle-left" style="font-size: 1.25rem"></i>',
            rightArrow: '<i class="fal fa-angle-right" style="font-size: 1.25rem"></i>'
        }

        function approve() {

            if (document.getElementById('amount').value == 0) {
                document.getElementById('amount-message').innerHTML = 'Amount is required!'
                setTimeout(() => {
                    document.getElementById('amount-message').innerHTML = ''
                }, 3000);
            } else {
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

        function rejectTransaction() {
            $("#reject-modal").modal('show');
        }

        $(document).ready(function() {

            // minimum setup
            $('#transaction_date').datepicker({
                format: 'dd/mm/yyyy',
                todayHighlight: true,
                orientation: "bottom left",
                templates: controls,
                //startDate: "{{ \Carbon\Carbon::today()->format('d/m/Y') }}"
            });
        })
    </script>
    @include('crypto-management.crypto-transactions.partials.fireblocks-monitoring-scripts')
@stop
