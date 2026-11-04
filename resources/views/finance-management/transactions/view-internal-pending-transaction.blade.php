@extends('layouts.cp')
@section('title', 'Internal Pending Transfer Transaction - Finance Management')

@section('content')

    @php $areFeesApplied = 0; @endphp
    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Finance Management</span>
        </h1>
        <div class="d-flex">
            <a href="{{ url('finance-management/pending-transactions') }}" class="btn btn-primary ml-2 mx-md-2"><i
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
                Internal Pending Transfer Transaction
            </h2>
        </div>

        @php
            $sender = $transactionRequest->account;
            $balanceBreakdown = \App\Helpers\Common::getAccountBalanceBreakdownForTrApproval($transactionRequest);
            $balance = $balanceBreakdown['approval_available'];

            $amount = $transactionRequest->amount;
            $zeroize = 0;
            $ex_rate = $transactionRequest->ex_rate ?? 0;
            $transaction_date = $transactionRequest->created_at;
            $debit_remarks = $transactionRequest->remarks;
            $reviewer = isset($detailsChanges['maker']) ? $detailsChanges['maker'] : null;
            $reviewed_at = null;

            if (
                !$reviewer &&
                $transactionRequest->transactionDetails &&
                $transactionRequest->transactionDetails->reviewer
            ) {
                $reviewer = $transactionRequest->transactionDetails->reviewer->name;
                $reviewed_at = $transactionRequest->transactionDetails->updated_at;
            }

            $approver = $transactionRequest->status == 'Approved' ? $transactionRequest->approver->name : null;
            $actionUrl = null;
        @endphp

        {{-- @can('approve transaction') --}}
        @php
            $needle = $transactionRequest->status == 'Approved' ? 'update/tt-with-fees' : 'approve';

            $amount = isset($detailsChanges['amount'])
                ? $detailsChanges['amount']['new_value'][0]
                : $transactionRequest->amount;

            $zeroize = isset($detailsChanges['zeroize']) ? $detailsChanges['zeroize']['new_value'][0] : 0;

            $ex_rate = isset($detailsChanges['ex_rate'])
                ? $detailsChanges['ex_rate']['new_value'][0]
                : $transactionRequest->ex_rate;

            $ex_rate = $ex_rate ?? 0;

            $transaction_date = isset($detailsChanges['transaction_date'])
                ? $detailsChanges['transaction_date']['new_value'][0]
                : $transactionRequest->transaction_date;

            $debit_remarks = isset($detailsChanges['remarks'])
                ? $detailsChanges['remarks']['new_value'][0]
                : $transactionRequest->remarks;

            if ($zeroize) {
                $amount = $balance;
            }
        @endphp
        {{-- @endcan --}}

        @role('finance')
            @php
                $actionUrl =
                    $transactionRequest->status === 'Approved'
                        ? '#'
                        : url('/finance-management/transactions/save-changes');
            @endphp
        @endrole


        <div class="panel-container show mb-3">
            <div class="panel-content" style="overflow: scroll;">
                @if (!empty($disabled) && in_array('NeroData', $enabledModules))
                    <div class="alert alert-warning">Please contact compliance team to clear this transaction. Current
                        status from transaction monitoring is <strong>{{ $transactionRequest->compliance_status }}</strong>
                    </div>
                @endif
                @include('common.error-message')
                @php
                    $sender = $transactionRequest->account;
                    $receiver = $transactionRequest->receiver;

                @endphp
                <form id="approval-form" method="post"
                    action="{{ $actionUrl ? $actionUrl : url('/finance-management/pending-transaction/approve/internal') }}"
                    enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" id="id" name="id" value="{{ $transactionRequest->id }}">
                    <input type="hidden" id="platform" name="platform" value="client_portal">
                    <input type="hidden" name="credit_currency_id" id="credit_currency_id"
                        value="{{ $sender->account_currency }}">
                    <input type="hidden" name="debit_currency_id" id="debit_currency_id"
                        value="{{ $receiver->account_currency }}">
                    <table class="table table-bordered table-hover m-0 shadow" id="data-grid">
                        <thead class="thead-dark">
                            <tr>
                                <th width="220">From Account</th>
                                <td>
                                    {{ $sender->title . ' - ' . $sender->account_number }}
                                </td>
                            </tr>
                            @include('finance-management.transactions.partials.tr-approval-balance-rows', [
                                'trRequest' => $transactionRequest,
                            ])
                            <!-- check if the internal transfer -->
                            @if ($transactionRequest->receiver_account_number != '')
                                <tr>
                                    <th>To Account</th>
                                    <td>{{ $receiver->title . ' - ' . $receiver->account_number }}</td>
                                </tr>
                            @endif
                            <tr>
                                <th>Amount</th>
                                <td>
                                    <div class="row">
                                        <div class="col-md-10">
                                            <input type="number" step="any" id="amount" name="amount"
                                                onfocus="handleOnfocus('amount_message')"
                                                onkeyup="handleOnAmountChange(this)" value="{{ $amount }}"
                                                class="form-control" required />
                                            @if (isset($detailsChanges['amount']))
                                                {!! $detailsChanges['amount']['tooltip'] !!}
                                            @endif
                                        </div>
                                        <div class="col-md-2">
                                            <div class="custom-control custom-switch mt-2" id="is_zeroize_div">
                                                <input type="checkbox" @if ($zeroize) checked @endif
                                                    onchange="handleOnChangeIsZeroize(this)" class="custom-control-input"
                                                    id="is_zeroize" value="1" name="is_zeroize">
                                                <label class="custom-control-label" for="is_zeroize"><b>Zeroize</b></label>
                                            </div>
                                            @if (isset($detailsChanges['zeroize']))
                                                {!! $detailsChanges['zeroize']['tooltip'] !!}
                                            @endif
                                        </div>
                                    </div>
                                </td>
                            </tr>

                            {{-- @include('global-partials.transactions.client.fee') --}}

                            @if ($transactionRequest->status == 'Approved')
                                @include('global-partials.transactions.client.approved-fee')
                            @elseif ($transactionRequest->status == 'Pending')
                                @include('global-partials.transactions.client.fee')
                            @endif

                            @if ($sender->account_currency != $receiver->account_currency)
                                <tr class="alert alert-warning">
                                    <th>Exchange Rate</th>
                                    <td>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <label for="ex_rate">Exchange Rate</label>
                                                <input required onchange="checkIfNotZero(this)"
                                                    onkeyup="handleOnExRateChange(this)" min="0" type="text"
                                                    autocomplete="off" class="form-control" name="ex_rate"
                                                    value="{{ $ex_rate }}" id="ex_rate" />

                                                @if (isset($detailsChanges['ex_rate']))
                                                    {!! $detailsChanges['ex_rate']['tooltip'] !!}
                                                @endif
                                            </div>
                                            <div class="col-md-6">
                                                <label for="exchanged_amount">Exchanged Amount</label>
                                                <input onkeyup="calculateExchangeRate(this.value)" type="number"
                                                    step="any" id="exchanged_amount" name="exchanged_amount"
                                                    min="1" value="{{ old('exchanged_amount') }}"
                                                    class="form-control" required />
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endif
                            <tr>
                                <th>Request Date</th>
                                <td>{{ \Carbon\Carbon::parse($transactionRequest->created_at)->format('d/m/Y') }}</td>
                            </tr>
                            <tr>
                                <th>Transaction Date</th>
                                <td class="field-container">
                                    <input type="text" required
                                        value="{{ \Carbon\Carbon::parse($transaction_date)->format('d/m/Y') }}"
                                        autocomplete="off" class="form-control w-25" name="transaction_date"
                                        id="transaction_date" placeholder="Select date">

                                    @if (isset($detailsChanges['transaction_date']))
                                        {!! $detailsChanges['transaction_date']['tooltip'] !!}
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Mode</th>
                                <td>{{ $transactionRequest->transaction_nature }}</td>
                            </tr>

                            <tr>
                                <th>Debit Remarks</th>
                                <td class="field-container">
                                    <textarea name="debit_remarks" id="debit_remarks" class="form-control" cols="30" rows="2">{{ old('debit_remarks', $debit_remarks) }}</textarea>

                                    @if (isset($detailsChanges['remarks']))
                                        {!! $detailsChanges['remarks']['tooltip'] !!}
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Credit Remarks</th>
                                <td>
                                    <textarea name="credit_remarks" id="credit_remarks" class="form-control" cols="30" rows="2">{{ old('credit_remarks', $transactionRequest->remarks) }}</textarea>
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
                                <th>Payment Type </th>
                                @if ($payment_type)
                                    <td>{{ $payment_type->value }}</td>
                                @else
                                    <td>N/A</td>
                                @endif
                            </tr>

                            @php
                                $docs = $transactionRequest->docs;
                            @endphp

                            @if ($transactionRequest->status == 'Rejected')
                                @if ($docs)
                                    <tr>
                                        <th>Supporting Documents</th>
                                        <td>
                                            @foreach ($docs as $doc)
                                                <a href="{{ $doc->url }}" class="btn btn-sm btn-outline-secondary"
                                                    target="_blank"><i class="fal fa-file-alt"></i> View</a>
                                            @endforeach
                                        </td>
                                    </tr>
                                @endif
                            @else
                                @include(
                                    'finance-management.transactions.partials.update-supporting-documents',
                                    ['status' => $transactionRequest->status]
                                )
                            @endif

                            @if ($approver)
                                <tr>
                                    <th>Approver</th>
                                    <td>
                                        {{ $approver . ' - ' . \Carbon\Carbon::parse($transactionRequest->approved_at)->format('d M, Y H:i:s') }}
                                    </td>
                                </tr>
                            @endif

                            @if ($reviewer)
                                <tr>
                                    <th>Reviewer</th>
                                    <td>
                                        {{ $reviewer . ' - ' . \Carbon\Carbon::parse($reviewed_at)->format('d M, Y H:i:s') }}
                                    </td>
                                </tr>
                                @if ($transactionRequest->status != 'Approved')
                                    <tr>
                                        <th>Note</th>
                                        <td>
                                            Fields that are been changed, are highlighted by the Red Dot.
                                        </td>
                                    </tr>
                                @endif
                            @endif

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

                    @role('finance')
                        @if ($transactionRequest->status != 'Approved')
                            <div class="row">
                                <div class="col-md-12">
                                    <button type="submit" class="btn btn-primary">Save</button>
                                </div>
                            </div>
                        @endif
                    @endrole

                    @can('approve transaction')
                        @if ($transactionRequest->status == 'Pending')
                            @if (in_array('OpenPayd', $enabledModules) &&
                                    $transactionRequest->currency->virtual_iban_provider == 'openpayd' &&
                                    $transactionRequest->receiver->currency->virtual_iban_provider == 'openpayd')
                                <input type="hidden" value="1" name="is_open_payd" id="is_open_payd">
                            @endif
                            <div class="row">
                                <div class="col-md-12">
                                    @if (!empty($disabled) && in_array('NeroData', $enabledModules))
                                        <div class="alert alert-warning">Please contact compliance team to clear this
                                            transaction. Current status from transaction monitoring is
                                            <strong>{{ $transactionRequest->compliance_status }} <a
                                                    href="{{ $moderate_url ?? $portalTransactionsUrl . '/' . $neroDataTransactionId }}"
                                                    target="_blank" rel="noopener" class="btn btn-warning ml-2 mr-2">
                                                    Moderate
                                                </a></strong>
                                        </div>
                                    @endif
                                    {{-- @if ($transactionRequest->status != 'Approved')
                                        <button type="button" onclick="{{ 'approve()' }}"
                                            class="btn btn-success">Approve</button>
                                    @endif --}}
                                    <button type="button" onclick="approve();" class="btn btn-success" id="approveButton"
                                        {{-- {{ $disabled }} @if ($transactionRequest->is_suspicious && !$transactionRequest->is_resolved) disabled @endif --}}>Approve</button>
                                    @if ($transactionRequest->status != 'Rejected')
                                        <button onclick="rejectTransaction(); return false;"
                                            class="btn btn-danger">Reject</button>
                                    @endif
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

        function approveTransaction() {
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
            } else {
                // $("#exchanged_amount").val(obj.value * {{ $transactionRequest->amount }})
            }
        }

        function handleOnExRateChange(obj) {
            if (parseFloat(obj.value) > 0) {
                $("#exchanged_amount").val(obj.value * document.getElementById('amount').value)
            }
        }

        function calculateExchangeRate(exchangedAmount) {
            $("#ex_rate").val(exchangedAmount / document.getElementById('amount').value)
        }

        function setExchangeRate() {

            const debit_currency_id = parseInt($("#debit_currency_id").val());
            const credit_currency_id = parseInt($("#credit_currency_id").val());

            if (debit_currency_id > 0 && credit_currency_id > 0 && debit_currency_id != credit_currency_id) {
                $("#ex_rate").prop("readonly", false);
                $("#ex_rate").prop("placeholder", "please provide exchange rate");
                $("#exchanged_amount").val({{ $ex_rate }} * document.getElementById('amount').value)
                // $("#ex_rate").val("");
            } else {
                $("#ex_rate").prop("readonly", true);
                $("#ex_rate").val(1);
            }
        }

        function handleOnAmountChange(obj) {
            const debit_currency_id = parseInt($("#debit_currency_id").val());
            const credit_currency_id = parseInt($("#credit_currency_id").val());

            if (debit_currency_id > 0 && credit_currency_id > 0 && debit_currency_id != credit_currency_id) {
                $("#ex_rate").prop("readonly", false);
                let trn_amount = {{ $amount }} ?? 0
                let amount = obj.value ?? trn_amount
                var trn_ex_rate = {{ $ex_rate }} ?? 1

                if ($("#ex_rate").val()) {
                    var ex_rate = $("#ex_rate").val()
                } else {
                    var ex_rate = trn_ex_rate
                    $("#ex_rate").val(ex_rate)
                }

                $("#exchanged_amount").val(ex_rate * amount)
            } else {
                $("#ex_rate").prop("readonly", true);
                $("#ex_rate").val(1);
            }
        }

        function handleOnChangeIsZeroize(checkbox) {
            const amount = document.getElementById('amount')

            if (checkbox.checked) {
                Swal.fire({
                    title: "Are you sure?",
                    text: "All of the Debit Account's amount will be used for this transaction.",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonText: "Yes, Zeroize it!"
                }).then(function(result) {
                    if (result.value) {
                        amount.value = '{{ $balance }}'
                        handleOnAmountChange(amount)
                        document.getElementById('amount').value = {{ $balance }}
                        {{ $amount = $balance }}
                        amount.disabled = true
                    } else {
                        checkbox.checked = false
                        {{ $amount = $transactionRequest->amount }}
                        document.getElementById('amount').value = {{ $transactionRequest->amount }}
                    }
                });
            } else {
                document.getElementById('amount').disabled = false
                amount.value = '{{ $amount }}'
                handleOnAmountChange(amount)
            }
        }


        $(document).ready(function() {
            if ('Approved' === '{{ $transactionRequest->status }}') {
                document.getElementById('is_zeroize').checked = false
                document.getElementById('is_zeroize').disabled = true
                document.getElementById('is_zeroize_div').disabled = true
            }

            if ({{ $zeroize }}) {
                document.getElementById('amount').disabled = true
            }

            setExchangeRate();

            $('.select2').select2();

            // minimum setup
            $('#transaction_date').datepicker({
                format: 'dd/mm/yyyy',
                todayHighlight: true,
                orientation: "bottom left",
                templates: controls
            });
        })


        function rejectTransaction() {
            $("#reject-modal").modal('show');
        }
    </script>
@stop
