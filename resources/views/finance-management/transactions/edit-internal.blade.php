@extends('layouts.cp')
@section('title', 'Internal Pending Transfer Transaction - Finance Management')

@section('content')

    @php
        $isOpenPaydEnabled = in_array('OpenPayd', $enabledModules);
    @endphp

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
            <h2>
                Internal Pending Transfer Transaction
            </h2>
        </div>

        <div class="panel-container show mb-3">
            <div class="panel-content" style="overflow: scroll;">
                @php
                    $disabled = '';

                    if (
                        in_array($transactionRequest->compliance_status, $complianceStatus) &&
                        in_array('NeroData', $enabledModules)
                    ) {
                        $disabled = 'disabled';
                    }

                @endphp

                @if (!empty($disabled) && in_array('NeroData', $enabledModules) && $transaction->status == 'Pending')
                    <div class="alert alert-warning">Please contact compliance team to clear this transaction. Current
                        status from transaction monitoring is <strong>{{ $transactionRequest->compliance_status }}</strong>
                    </div>
                @endif

                @include('common.error-message')
                @php
                    $sender = $transaction->account;
                    $receiver = $linkedTransaction->account;
                    $balanceBreakdown = \App\Helpers\Common::getAccountBalanceBreakdownForTrApproval($transactionRequest);
                    $balance = $balanceBreakdown['approval_available'];
                    $reviewer = isset($detailsChanges['maker']) ? $detailsChanges['maker'] : null;
                    $reviewed_at = null;

                    if (
                        !$reviewer &&
                        $transactionRequest->transactionDetails &&
                        $transactionRequest->transactionDetails->user_id
                    ) {
                        $reviewer = $transactionRequest->transactionDetails->reviewer->name;
                        $reviewed_at = $transactionRequest->transactionDetails->updated_at;
                    }

                    $approver =
                        $transaction->status == 'Approved' && $transaction->approved_by != 0
                            ? $transaction->approver->name
                            : null;

                    $amount = $transaction->amount;
                    $zeroize = 0;
                    $ex_rate = $transaction->ex_rate;
                    $transaction_date = $transaction->created_at;
                    $debit_remarks = $transaction->remarks;
                @endphp

                {{-- @can('approve transaction') --}}
                @php
                    $needle = $transaction->status == 'Approved' ? 'update/internal-with-fees' : 'approve';

                    $amount = isset($detailsChanges['amount'])
                        ? $detailsChanges['amount']['new_value'][0]
                        : $transaction->amount;

                    $zeroize = isset($detailsChanges['zeroize']) ? $detailsChanges['zeroize']['new_value'][0] : 0;

                    $ex_rate = isset($detailsChanges['ex_rate'])
                        ? $detailsChanges['ex_rate']['new_value'][0]
                        : $transaction->ex_rate;

                    $transaction_date = isset($detailsChanges['transaction_date'])
                        ? $detailsChanges['transaction_date']['new_value'][0]
                        : $transactionRequest->transaction_date;

                    $debit_remarks = isset($detailsChanges['remarks'])
                        ? $detailsChanges['remarks']['new_value'][0]
                        : $transaction->remarks;

                    if ($zeroize) {
                        $amount = $balance;
                    }
                @endphp
                {{-- @endcan --}}

                @role('finance')
                    @php
                        $needle = $transaction->status == 'Approved' ? 'upload-documents' : 'save-changes';
                    @endphp
                @endrole

                <form id="approval-form" method="post" action="{{ url('/finance-management/transactions/' . $needle) }}"
                    enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" id="id" name="id" value="{{ $transaction->id }}">
                    <input type="hidden" name="credit_currency_id" id="credit_currency_id"
                        value="{{ $sender->account_currency }}">
                    <input type="hidden" name="debit_currency_id" id="debit_currency_id"
                        value="{{ $receiver->account_currency }}">
                    <input type="hidden" id="amount_value" value="{{ $amount }}">
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
                            <tr>
                                <th>To Account</th>
                                <td>{{ $receiver->title . ' - ' . $receiver->account_number }}</td>
                            </tr>
                            <tr>
                                <th>Amount</th>
                                <td>
                                    <div class="row">
                                        <div class="col-md-10 field-container">
                                            <input type="number" step="any" id="amount" name="amount"
                                                onfocus="handleOnfocus('amount_message')"
                                                onkeyup="handleOnAmountChange(this)" value="{{ $amount }}"
                                                class="form-control" required />

                                            @if (isset($detailsChanges['amount']))
                                                {!! $detailsChanges['amount']['tooltip'] !!}
                                            @endif
                                        </div>
                                        <div class="col-md-2 field-container">
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

                            @if ($transaction->status == 'Approved')
                                @include('global-partials.transactions.client.approved-fee')
                            @elseif ($transaction->status == 'Pending')
                                @include('global-partials.transactions.client.fee')
                            @endif

                            @if ($sender->account_currency != $receiver->account_currency)
                                <tr class="alert alert-warning">
                                    <th>Exchange Rate / Amount</th>
                                    <td>
                                        <div class="row">
                                            <div class="col-md-6 field-container">
                                                <label for="ex_rate">Exchange Rate</label>
                                                <input required readonly onchange="checkIfNotZero(this)"
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

                            {{-- {{dd($transaction_log)}} --}}
                            @if (!empty($transaction_log))
                                <tr>
                                    <th>Openpayd transaction details</th>
                                    <td>
                                        <button onclick="viewOpenpaydTransaction();" type="button"
                                            class="btn btn-outline-secondary btn-sm">
                                            View transaction details
                                        </button>
                                    </td>
                                </tr>
                            @endif

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
                                    <textarea name="credit_remarks" id="credit_remarks" class="form-control" cols="30" rows="2">{{ old('credit_remarks', $linkedTransaction->remarks) }}</textarea>
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
                                <th>Transaction Notes</th>
                                <td>
                                    @include('finance-management.transactions.partials.transaction-notes', [
                                        'transaction_request_id' => $transactionRequest->id,
                                    ])
                                </td>

                            </tr>


                            @include(
                                'finance-management.transactions.partials.update-supporting-documents',
                                ['status' => $transaction->status]
                            )

                            @if ($approver)
                                <tr>
                                    <th>Approver</th>
                                    <td>
                                        {{ $approver . ' - ' . \Carbon\Carbon::parse($transaction->approved_at)->format('d M, Y H:i:s') }}
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
                                @if ($transaction->status != 'Approved')
                                    <tr>
                                        <th>Note</th>
                                        <td>
                                            Fields that are been changed, are highlighted by the Red Dot.
                                        </td>
                                    </tr>
                                @endif
                            @endif

                            <tr>
                                <th>Rejected By</th>
                                <td>
                                    {{ $transaction->rejector ? $transaction->rejector->name : '--' }}
                                </td>
                            </tr>

                            <tr>
                                <th>Status</th>
                                <td><span
                                        class="badge {{ $statusBadge[$transactionRequest->status] }}">{{ $transactionRequest->status }}</span>
                                </td>
                            </tr>
                            <tr>
                                <th>Transaction Method</th>
                                <td>

                                    @if (!$isOpenPaydEnabled)
                                        @php
                                            // $default_transaction_method = \App\Models\TransactionMethod::where('is_default', 1)->first();
                                            $default_transaction_method = \App\Models\TransactionMethod::where(
                                                'name',
                                                'SWIFT',
                                            )->first();
                                        @endphp

                                        <input type="text" class="form-control" name="transaction_method"
                                            id="transaction_method" value="{{ $default_transaction_method->name }}"
                                            readonly />
                                        <input type="hidden" class="form-control" name="transaction_method_id"
                                            id="transaction_method_id" value="{{ $default_transaction_method->id }}"
                                            readonly />
                                    @else
                                        <div class="row">
                                            <div class="col-md-12">
                                                @can('approve transaction')
                                                    <select name="transaction_method_id" id="transaction_method_id"
                                                        data-select2-id="transaction_method_id"
                                                        class="form-control select2 w-100" required>
                                                        <option value="">Select Transaction Method</option>
                                                        @foreach ($transaction_methods as $method)
                                                            @if ($method->id == $transaction->transaction_method_id)
                                                                <option value="{{ $method->id }}" selected>
                                                                    {{ $method->name }} @if (!empty($trn->method->module->name))
                                                                        - {{ $trn->method->module->name }}
                                                                    @endif
                                                                </option>
                                                            @else
                                                                <option value="{{ $method->id }}">{{ $method->name }}
                                                                    @if (!empty($trn->method->module->name))
                                                                        - {{ $trn->method->module->name }}
                                                                    @endif
                                                                </option>
                                                            @endif
                                                        @endforeach
                                                    </select>
                                                @endcan
                                            </div>
                                        </div>
                                    @endif
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

                    @can('approve transaction')
                        @if ($transaction->status == 'Pending')
                            @if (in_array('OpenPayd', $enabledModules) &&
                                    $transactionRequest->currency->virtual_iban_provider == 'openpayd' &&
                                    $transactionRequest->receiver->currency->virtual_iban_provider == 'openpayd')
                                <input type="hidden" value="1" name="is_open_payd" id="is_open_payd">
                            @endif
                        @endif
                        <div class="row">
                            <div class="col-md-12">
                                @if (!empty($disabled) && in_array('NeroData', $enabledModules) && $transaction->status == 'Pending')
                                    <div class="alert alert-warning">Please contact compliance team to clear this transaction.
                                        Current status from transaction monitoring is
                                        <strong>{{ $transactionRequest->compliance_status }}<a
                                                href="{{ $moderate_url ?? $portalTransactionsUrl . '/' . $neroDataTransactionId }}"
                                                target="_blank" rel="noopener" class="btn btn-warning ml-2 mr-2">
                                                Moderate
                                            </a></strong>
                                    </div>
                                @endif
                                @if ($transaction->status != 'Approved')
                                    {{-- <button type="button" onclick="approve();" class="btn btn-success" --}}
                                    <button type="button" onclick="approveTransaction();" class="btn btn-success"
                                        {{--  {{ $disabled }} --}}>Approve</button>
                                @elseif ($transaction->status == 'Approved')
                                    <button type="submit" class="btn btn-primary">Save</button>
                                @endif

                                @if ($transactionRequest->status == 'Pending')
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
                </form>

                <form id="save-note" method="POST" action="{{ url('/finance-management/transactions/save-note') }}">
                    @csrf
                </form>

            </div>
        </div>
    </div>

    @if (!empty($transaction_log))
        <div class="modal fade" id="openpayd-transaction-modal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title ">Openpayd Transaction Details</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true"><i class="fal fa-times"></i></span>
                        </button>
                    </div>
                    <div class="modal-body">
                        @include('bitpace.transaction-log')
                    </div>
                </div>
            </div>
        </div>
    @endif

    <form method="POST" name="delete-file" id="delete-file"
        action="{{ url('/finance-management/transactions/files/delete') }}">
        @csrf
        <input type="hidden" name="file_id" id="file_id" value="">
    </form>

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

    @include('finance-management.transactions.partials.deposit-with-sepa-warning-modal')
@stop
@section('scripts')
    <script>
        var controls = {
            leftArrow: '<i class="fal fa-angle-left" style="font-size: 1.25rem"></i>',
            rightArrow: '<i class="fal fa-angle-right" style="font-size: 1.25rem"></i>'
        }

        function approveTransaction() {
            const transaction_method_text = $('#transaction_method_id').find("option:selected").text().trim();
            const transaction_is_confirmed = $('#confirm-deposit-transaction-with-sepa').val().trim();

            if (["SEPA", "SEPA_INSTANT"].includes(transaction_method_text) && transaction_is_confirmed == "false") {
                $('#sepa-deposit-approval-warning-modal').modal('show');
                return;
            }

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

        function handleOnAmountChange(obj) {
            const debit_currency_id = parseInt($("#debit_currency_id").val());
            const credit_currency_id = parseInt($("#credit_currency_id").val());

            if (debit_currency_id > 0 && credit_currency_id > 0 && debit_currency_id != credit_currency_id) {
                $("#ex_rate").prop("readonly", false);
                let trn_amount = {{ $amount !== null ? $amount : 0 }}
                let amount = obj.value ? obj.value : trn_amount
                var trn_ex_rate = {{ $ex_rate !== null ? $ex_rate : 1 }}

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
                        document.getElementById('amount_value').value = {{ $balance }}
                        {{ $amount = $balance }}
                        amount.disabled = true
                    } else {
                        checkbox.checked = false
                        {{ $amount = $transaction->amount }}
                        document.getElementById('amount_value').value = {{ $transaction->amount }}
                    }
                });
            } else {
                document.getElementById('amount').disabled = false
                amount.value = '{{ $amount }}'
                handleOnAmountChange(amount)
            }
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

        function handleOnExRateChange(obj) {
            const objValue = obj.value ? obj.value : 1;
            if (parseFloat(obj.value) > 0) {
                $("#exchanged_amount").val(objValue * document.getElementById('amount_value').value)
            }
        }

        function calculateExchangeRate(exchangedAmount) {
            $("#ex_rate").val(exchangedAmount / document.getElementById('amount_value').value)
        }

        function setExchangeRate() {

            const debit_currency_id = parseInt($("#debit_currency_id").val());
            const credit_currency_id = parseInt($("#credit_currency_id").val());

            if (debit_currency_id > 0 && credit_currency_id > 0 && debit_currency_id != credit_currency_id) {
                $("#ex_rate").prop("readonly", false);
                $("#ex_rate").prop("placeholder", "please provide exchange rate");
                $("#exchanged_amount").val({{ $ex_rate !== null ? $ex_rate : 1 }} * document.getElementById('amount_value')
                    .value)
                // $("#ex_rate").val("");
            } else {
                $("#ex_rate").prop("readonly", true);
                $("#ex_rate").val(1);
            }
        }

        $(document).ready(function() {
            if ('Approved' === '{{ $transaction->status }}') {
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

        function viewOpenpaydTransaction() {
            $("#openpayd-transaction-modal").modal('show');
        }
    </script>
@stop
