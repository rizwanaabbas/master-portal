@extends('layouts.cp')
@section('title', 'TT Transaction - Finance Management')

@section('content')
    @php
        $beneficiary_type_map = [
            'RETAIL' => 'Individual',
            'CORPORATE' => 'Company',
            'individual' => 'Individual',
            'company' => 'Company',
        ];
        $virtualProvider = $transactionRequest->currency->virtual_iban_provider;
        $isOpenPaydEnabled = in_array('OpenPayd', $enabledModules) && $virtualProvider == 'openpayd';
        $isLorumEnabled = in_array('Lorum', $enabledModules) && $virtualProvider == 'lorum';
        $isZenusEnabled = in_array('Zenus', $enabledModules) && $virtualProvider == 'zenus';
        $isVirtualTransaction = $isOpenPaydEnabled || $isLorumEnabled || $isZenusEnabled;

        $isPendingVirtualTransaction = !empty($virtualTransaction) && $virtualTransaction->status === 'Pending';
        $disabledZenusApproval =
            $isPendingVirtualTransaction && strtolower($virtualTransaction->provider_name) === 'zenus';
        $disabledLorumApproval =
            $isPendingVirtualTransaction && strtolower($virtualTransaction->provider_name) === 'lorum';

        $virtual_bank_beneficiary = $transactionRequest->beneficiary->virtualBankBeneficiary ?? null;
        $sort_code = null;
        $routing_number = null;
        $ben_payment_types = null;

        if (!empty($virtual_bank_beneficiary)) {
            $provider = strtolower($virtual_bank_beneficiary->provider_name);
            if ($provider === 'openpayd') {
                $sort_code = $virtual_bank_beneficiary->bank_routing_codes[0]['routingCodeValue'] ?? null;
            } elseif (in_array($provider, ['lorum', 'zenus'])) {
                $codes = json_decode($virtual_bank_beneficiary->bank_routing_codes);
                $firstCode = is_array($codes) ? $codes[0] ?? null : $codes;
                $routing_number = $firstCode->routingCodeValue ?? null;
            }
            $ben_payment_types = json_encode($virtual_bank_beneficiary->payment_types ?? []);
        }
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
                TT Transaction
            </h2>
        </div>

        <div class="panel-container show mb-3">
            <div class="panel-content" style="overflow: scroll;">
                @php
                    $disabled = $disabledZenusApproval || $disabledLorumApproval ? 'disabled' : '';

                    if (
                        in_array($transactionRequest->compliance_status, $complianceStatus) &&
                        in_array('NeroData', $enabledModules)
                    ) {
                        $disabled = 'disabled';
                    }
                    /* if($transaction->currency->virtual_iban_provider == 'openpayd' && $isOpenPaydEnabled) {
                    $disabled = 'disabled';
                 } */
                @endphp

                @if (!empty($disabled) && in_array('NeroData', $enabledModules) && $transaction->status == 'Pending')
                    <div class="alert alert-warning">Please contact compliance team to clear this transaction. Current
                        status from transaction monitoring is <strong>{{ $transactionRequest->compliance_status }}</strong>
                    </div>
                @endif
                @include('common.error-message')

                @if ($disabledZenusApproval || $disabledLorumApproval)
                    <div class="alert bg-warning-400 text-white">
                        <button class="close" type="button" data-dismiss="alert" aria-label="Close"><span
                                aria-hidden="true">×</span></button>
                        <i class="fal fa-exclamation-triangle mr-2"></i>
                        This transaction is currently waiting for confirmation of {{ $virtualTransaction->provider_name }}
                        Bank.
                        <br>
                    </div>
                @endif

                @php
                    $zeroize = 0;
                    $amount = $transaction->amount;
                    $transaction_date = $transaction->created_at;
                    $remarks = $transaction->remarks;
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
                @endphp

                {{-- @can('approve transaction') --}}
                @php
                    $needle = $transaction->status == 'Approved' ? 'update/tt-with-fees' : 'approve';

                    $amount = isset($detailsChanges['amount'])
                        ? $detailsChanges['amount']['new_value'][0]
                        : $transaction->amount;

                    $zeroize = isset($detailsChanges['zeroize']) ? $detailsChanges['zeroize']['new_value'][0] : 0;

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
                        $needle =
                            $transaction->status == 'Approved' ||
                            $disabledZenusApproval === true ||
                            $disabledLorumApproval === true
                                ? 'upload-documents'
                                : 'save-changes';
                    @endphp
                @endrole

                <form id="approval-form" method="post" action="{{ url('/finance-management/transactions/' . $needle) }}"
                    enctype="multipart/form-data">

                    @csrf
                    <input type="hidden" id="id" name="id" value="{{ $transaction->id }}">

                    <table class="table table-bordered table-hover m-0 shadow" id="data-grid">
                        <thead class="thead-dark">
                            <tr>
                                <th width="220">From Account</th>
                                <td>{{ $transaction->account->title . ' - ' . $transaction->account->account_number }}
                                </td>
                            </tr>
                            @include('finance-management.transactions.partials.tr-approval-balance-rows', [
                                'trRequest' => $transactionRequest,
                            ])
                            @php
                                $balanceBreakdown = \App\Helpers\Common::getAccountBalanceBreakdownForTrApproval($transactionRequest);
                                $balance = $balanceBreakdown['approval_available'];
                            @endphp
                            <tr>
                                <th>Amount</th>
                                <td>
                                    <div class="row">
                                        <div class="col-md-10 field-container">
                                            <input type="number" step="any" id="amount" name="amount"
                                                onfocus="handleOnfocus('amount_message')" value="{{ $amount }}"
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

                            <tr>
                                <th>Request Date</th>
                                <td>{{ \Carbon\Carbon::parse($transaction->created_at)->format('d M, Y') }}</td>
                            </tr>
                            <!-- check if the TT is for external transfer -->
                            @if ($transaction->beneficiary_id > 0)
                                <tr>
                                    <th><label class="form-label" for="transaction_date">Transaction Date</label></th>
                                    <td>
                                        <div class="row">
                                            <div class="col-md-2 field-container">
                                                <input type="text" autocomplete="off" class="form-control"
                                                    value="{{ \Carbon\Carbon::parse($transaction_date)->format('d/m/Y') }}"
                                                    name="transaction_date" id="transaction_date" placeholder="Select date"
                                                    required>

                                                @if (isset($detailsChanges['transaction_date']))
                                                    {!! $detailsChanges['transaction_date']['tooltip'] !!}
                                                @endif
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
                            @endif

                            {{-- @if (($transaction->currency->virtual_iban_provider == 'openpayd' || $transaction->currency->virtual_iban_provider == 'lorum') && !empty($transaction_log)) --}}
                            @if (in_array($transaction->currency->virtual_iban_provider, ['openpayd', 'lorum', 'zenus']) && !empty($transaction_log))
                                <tr>
                                    <th>Virtual Bank Transaction Details</th>
                                    <td>
                                        <div class="d-flex justify-content-start text-start">
                                            @include('bitpace.transaction-log')
                                        </div>
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
                                    {{-- @if ($transaction->status == 'Pending') --}}
                                    <div class="row">
                                        <div class="col-md-12 field-container">
                                            <textarea name="remarks" id="remarks" class="form-control" cols="30" rows="2">{{ old('remarks', $remarks) }}</textarea>

                                            @if (isset($detailsChanges['remarks']))
                                                {!! $detailsChanges['remarks']['tooltip'] !!}
                                            @endif
                                        </div>
                                    </div>
                                    {{-- @else
                                            {{ $transaction->remarks }}
                                    @endif --}}
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

                            @include(
                                'finance-management.transactions.partials.update-supporting-documents',
                                ['status' => $transaction->status]
                            )

                            @if ($transaction->transaction_type == 'Debit')
                                <tr>
                                    <th>Payment
                                        Advice /
                                        MT
                                        103 (SWIFT Copy)</th>
                                    <td>
                                        @if (count($docs))
                                            @foreach ($docs as $doc)
                                                @if ($doc->is_payment == 1)
                                                    <a href="{{ $doc->url }}"
                                                        class="btn btn-sm btn-outline-secondary mt-2 mr-2"
                                                        target="_blank"><i class="fal fa-file-alt"></i>
                                                        View</a>
                                                    @can('delete records')
                                                        <a href="javascript:deleteFile({{ $doc->id }})">
                                                            <i
                                                                class="alert-danger fal fa-times-circle rounded-circle mr-2"></i>
                                                        </a>
                                                    @endcan
                                                @endif
                                            @endforeach
                                        @endif
                                        <div class="form-group">
                                            <div class="row mt-2">
                                                <div class="col-md-9">
                                                    <input type="file" class="form-control"
                                                        onchange="handleChangePaymentDocument(this.value, this)"
                                                        name="payment_advice[]" id="payment_advice_0" value="">
                                                </div>
                                                <div class="col-md-1">
                                                    <button class="btn btn-primary btn-add-document d-none"
                                                        onclick="handleAddPaymentDocument(this)" type="button"><i
                                                            class="fal fa-plus"></i></button>
                                                </div>
                                                <div class="col-md-1 ml-2">
                                                    <button class="btn btn-danger btn-remove-document d-none"
                                                        onclick="handleRemovePaymentDocument('payment_advice_0', false, this)"
                                                        type="button"><i class="fal fa-times"></i></button>
                                                </div>
                                            </div>
                                            @error('payment_advice')
                                                <span class="invalid-feedback"
                                                    role="alert"><strong>{{ $message }}</strong></span>
                                            @enderror
                                        </div>
                                    </td>
                                </tr>
                            @endif

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

                            @if ($transactionRequest->senderDetails)
                                <tr>
                                    <th colspan="2">Sender Details</th>
                                </tr>
                                <tr>
                                    <th>Sender Name</th>
                                    <td>{{ $transactionRequest->senderDetails->sender_name }}</td>
                                </tr>
                                <tr>
                                    <th>Sender Address</th>
                                    <td>{{ $transactionRequest->senderDetails->sender_address }}</td>
                                </tr>
                                <tr>
                                    <th>Sender City</th>
                                    <td>{{ $transactionRequest->senderDetails->sender_city }}</td>
                                </tr>
                                <tr>
                                    <th>Sender Country</th>
                                    <td>{{ $transactionRequest->senderDetails->senderCountry->name ?? 'N/A' }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>Bank Name</th>
                                    <td>{{ $transactionRequest->senderDetails->sending_bank_name }}</td>
                                </tr>
                                <tr>
                                    <th>Bank Address</th>
                                    <td>{{ $transactionRequest->senderDetails->sending_bank_address }}</td>
                                </tr>
                                <tr>
                                    <th>Bank City</th>
                                    <td>{{ $transactionRequest->senderDetails->sending_bank_city }}</td>
                                </tr>
                                <tr>
                                    <th>Bank Country</th>
                                    <td>{{ $transactionRequest->senderDetails->sendingBankCountry->name ?? 'N/A' }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>SWIFT Code</th>
                                    <td>{{ $transactionRequest->senderDetails->sending_bank_swift }}</td>
                                </tr>

                                @if (
                                    $transactionRequest->senderDetails->intermediary_bank_name ||
                                        $transactionRequest->senderDetails->intermediary_bank_swift)
                                    <tr>
                                        <th>Intermediary Bank Name</th>
                                        <td>{{ $transactionRequest->senderDetails->intermediary_bank_name ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Intermediary SWIFT Code</th>
                                        <td>{{ $transactionRequest->senderDetails->intermediary_bank_swift ?? 'N/A' }}</td>
                                    </tr>
                                @endif
                                <tr>
                                    <th colspan="2"></th>
                                </tr>
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
                                        class="badge {{ $statusBadge[$transaction->status] }}">{{ $transaction->status }}</span>
                                </td>

                            </tr>
                            {{-- <tr>
                                <th>Client Preffered Transaction Method</th>
                                <td>
                                    {{ $transactionRequest->method->name ?? 'N/A' }}
                                </td>
                            </tr> --}}

                            @if (!$isVirtualTransaction)

                                @php
                                    // $default_transaction_method = \App\Models\TransactionMethod::where('is_default', 1)->first();
                                    $default_transaction_method = \App\Models\TransactionMethod::where(
                                        'name',
                                        'SWIFT',
                                    )->first();
                                @endphp

                                <tr>
                                    <th>Transaction Method</th>
                                    <td>
                                        <input type="text" class="form-control" name="transaction_method"
                                            id="transaction_method" value="{{ $default_transaction_method->name }}"
                                            readonly />
                                        <input type="hidden" class="form-control" name="transaction_method_id"
                                            id="transaction_method_id" value="{{ $default_transaction_method->id }}"
                                            readonly />
                                    </td>
                                </tr>
                            @elseif (!empty($transaction->transaction_method_id))
                                <tr>
                                    <th>Transaction Method</th>
                                    <td>
                                        <input type="text" class="form-control" name="transaction_method"
                                            id="transaction_method"
                                            value="{{ optional($transaction->method)->name }}{{ !optional($transaction->method)->is_default ? ' - ' . optional($transaction->method->module)->name : '' }}"
                                            readonly />
                                        <input type="hidden" class="form-control" name="transaction_method_id"
                                            id="transaction_method_id" value="{{ $transaction->method->id }}" readonly />
                                    </td>
                                </tr>
                            @else
                                <tr>
                                    <th>Transaction Method</th>
                                    <td>
                                        @php
                                            $callFunc = '';
                                            if (
                                                $transaction->transaction_type == 'Debit' &&
                                                !empty($transaction->beneficiary->virtualBankBeneficiary)
                                            ) {
                                                $callFunc =
                                                    'getOPenPaydBankAccounts("' .
                                                    $transaction->beneficiary->virtualBankBeneficiary->currency .
                                                    '")';
                                            }
                                        @endphp
                                        <div class="row">
                                            <div class="col-md-12">
                                                @can('approve transaction')
                                                    <select name="transaction_method_id" id="transaction_method_id"
                                                        data-select2-id="transaction_method_id" class="form-control select2"
                                                        required onchange="{{ $callFunc }}">
                                                        <option value="">Select Transaction Method</option>
                                                        @foreach ($transaction_methods as $method)
                                                            @if ($method->id == $transaction->transaction_method_id)
                                                                <option value="{{ $method->id }}" selected>
                                                                    {{ $method->name }}
                                                                    @if (!empty($trn->method->module->name))
                                                                        - {{ $trn->method->module->name }}
                                                                    @endif
                                                                </option>
                                                            @else
                                                                <option value="{{ $method->id }}">
                                                                    {{ $method->name }}
                                                                    @if (!empty($trn->method->module->name))
                                                                        - {{ $trn->method->module->name }}
                                                                    @elseif(!$method->is_default)
                                                                        - {{ $method->module->name }}
                                                                    @endif
                                                                </option>
                                                            @endif
                                                        @endforeach
                                                    </select>
                                                @endcan
                                                <span class="text-muted text-danger pt-1" style="display: none;"
                                                    id="error-message-for-transaction-method"></span>
                                            </div>
                                        </div>
                                    </td>

                                </tr>
                            @endif
                            @if ($transaction->status != 'Approved')
                                @if ($transaction->transaction_type == 'Debit' && !empty($transaction->beneficiary) && $isOpenPaydEnabled)
                                    <tr id="funding-source-row" style="display:none">
                                        <th>Main SEPA Funding Bank Accounts</th>
                                        <td>
                                            <div class="row">
                                                <div class="col-md-12">
                                                    @can('approve transaction')
                                                        <select name="funding_bank_account_id" id="funding_bank_account_id"
                                                            data-select2-id="funding_bank_account_id"
                                                            class="form-control select2"
                                                            @if ($isOpenPaydEnabled) required @endif>
                                                            <option value="">Select Funding Bank Account</option>
                                                        </select>
                                                    @endcan
                                                    <span class="text-muted text-danger pt-1" style="display: none;"
                                                        id="error-message-for-funding-bank-account">
                                                        Please select funcding bank account.
                                                    </span>
                                                </div>
                                            </div>
                                        </td>

                                    </tr>
                                @endif
                            @endif
                            @if ($transaction->status == 'Rejected')
                                <tr>
                                    <th>Reason</th>
                                    <td>{{ $transaction->reject_reason }}</td>
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
                        @if ($transactionRequest->status == 'Pending')
                            @if ($isVirtualTransaction)
                                <div class="custom-control row">
                                    <div class="custom-checkbox col-md-2">
                                        <input type="hidden" class="custom-control-input custom-checkbox" type="checkbox"
                                            value="1" name="is_virtual_bank_trn" id="is_virtual_bank_trn">
                                        {{-- <label class="custom-control-label" for="is_virtual_bank_trn">Payout using OpenPayd</label> --}}
                                    </div>
                                </div>
                            @endif
                        @endif
                        <div class="row">
                            <div class="col-md-12">
                                @if (!empty($disabled) && in_array('NeroData', $enabledModules) && $transaction->status == 'Pending')
                                    <div class="alert alert-warning">Please contact compliance team to clear this transaction.
                                        Current status from transaction monitoring is
                                        <strong>{{ $transactionRequest->compliance_status }} <a
                                                href="{{ $moderate_url ?? $portalTransactionsUrl . '/' . $neroDataTransactionId }}"
                                                target="_blank" rel="noopener" class="btn btn-warning ml-2 mr-2">
                                                Moderate
                                            </a></strong>
                                    </div>
                                @endif
                                @if ($transaction->status != 'Approved')
                                    {{-- <button on type="button" onclick="approve();" class="btn btn-success" {{$disabled}}>Approve</button> --}}
                                    <button on type="button" onclick="approveTT()" class="btn btn-success"
                                        {{ $disabledZenusApproval || $disabledLorumApproval ? 'disabled' : '' }}>Approve</button>
                                @elseif ($transaction->status == 'Approved')
                                    <button type="submit" class="btn btn-primary">Save</button>
                                @endif

                                @if ($transaction->status == 'Pending')
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

                    @if (empty($isSweep))
                        @include('finance-management.transactions.partials.sweep-transaction-missing-warning-modal')
                    @endif

                </form>

                <form id="save-note" method="POST" action="{{ url('/finance-management/transactions/save-note') }}">
                    @csrf
                </form>
            </div>
        </div>
    </div>
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
                    <form action="{{ url('/finance-management/pending-transaction/reject/tt') }}" method="post">
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

    @if ($transactionRequest->beneficiary_id > 0)
        <div class="modal fade" id="beneficiary-modal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                <div class="modal-content border-0 shadow">

                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">
                            <i class="fal fa-user mr-2"></i>Beneficiary Details
                        </h5>
                        <button type="button" class="close text-white" data-dismiss="modal">
                            <span><i class="fal fa-times"></i></span>
                        </button>
                    </div>

                    <div class="modal-body">

                        {{-- Beneficiary Information --}}
                        <div class="card border-0 shadow-sm mb-3">
                            <div class="card-header bg-light">
                                <strong><i class="fal fa-user-circle mr-2"></i>Beneficiary Information</strong>
                            </div>

                            <div class="card-body">
                                <div class="row">

                                    <div class="col-md-6 mb-3">
                                        <small class="text-muted d-block">Name</small>
                                        <strong>{{ $transactionRequest->beneficiary->name ?? '--' }}</strong>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <small class="text-muted d-block">Beneficiary Type</small>
                                        <strong>{{ isset($transactionRequest->beneficiary->beneficiary_type) && $transactionRequest->beneficiary->beneficiary_type ? $beneficiary_type_map[$transactionRequest->beneficiary->beneficiary_type] ?? $transactionRequest->beneficiary->beneficiary_type : '--' }}</strong>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <small class="text-muted d-block">Country</small>
                                        <strong>{{ $transactionRequest->beneficiary->country->name ?? '--' }}</strong>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <small class="text-muted d-block">State/Region</small>
                                        <strong>{{ $transactionRequest->beneficiary->beneficiary_state ?? '--' }}</strong>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <small class="text-muted d-block">City</small>
                                        <strong>{{ $transactionRequest->beneficiary->beneficiary_city ?? '--' }}</strong>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <small class="text-muted d-block">Account Currency</small>
                                        <span class="badge badge-info">
                                            {{ $transactionRequest->beneficiary->currency->short ?? '--' }}
                                        </span>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <small class="text-muted d-block">Postal Code</small>
                                        <strong>{{ $transactionRequest->beneficiary->postal_code ?? '--' }}</strong>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <small class="text-muted d-block">Beneficiary Address</small>
                                        {{ $transactionRequest->beneficiary->address ?? '--' }}
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <small class="text-muted d-block">Status</small>
                                        <span class="badge badge-success">
                                            {{ ucfirst($transactionRequest->beneficiary->status) }}
                                        </span>
                                    </div>

                                </div>
                            </div>
                        </div>

                        {{-- Banking Information --}}
                        <div class="card border-0 shadow-sm mb-3">
                            <div class="card-header bg-light">
                                <strong><i class="fal fa-university mr-2"></i>Banking Information</strong>
                            </div>

                            <div class="card-body">
                                <div class="row">

                                    <div class="col-md-6 mb-3">
                                        <small class="text-muted d-block">Account Number</small>
                                        <strong>{{ $transactionRequest->beneficiary->account_number ?? '--' }}</strong>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <small class="text-muted d-block">IBAN</small>
                                        <strong>{{ $transactionRequest->beneficiary->iban ?? '--' }}</strong>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <small class="text-muted d-block">SWIFT / BIC</small>
                                        <strong>{{ $transactionRequest->beneficiary->swift_code ?? '--' }}</strong>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <small class="text-muted d-block">Sort Code</small>
                                        <strong>{{ $sort_code ?? ($transactionRequest->beneficiary->sort_code ?? '--') }}</strong>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <small class="text-muted d-block">Routing Number</small>
                                        <strong>{{ $routing_number ?? '--' }}</strong>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <small class="text-muted d-block">Payment Types</small>
                                        <strong>{{ $ben_payment_types ?? '--' }}</strong>
                                    </div>


                                </div>
                            </div>
                        </div>

                        {{-- Bank Details --}}
                        <div class="card border-0 shadow-sm mb-3">
                            <div class="card-header bg-light">
                                <strong><i class="fal fa-user-circle mr-2"></i> Bank Details</strong>
                            </div>

                            <div class="card-body">
                                <div class="row">

                                    <div class="col-md-6 mb-3">
                                        <small class="text-muted d-block">Bank Name</small>
                                        <strong>{{ $transactionRequest->beneficiary->bank_name ?? '--' }}</strong>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <small class="text-muted d-block">Bank Account Country</small>
                                        <strong>{{ $transactionRequest->beneficiary->bankAccountCountry->name ?? '--' }}</strong>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <small class="text-muted d-block">Bank City</small>
                                        <strong>{{ $transactionRequest->beneficiary->bank_city ?? '--' }}</strong>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <small class="text-muted d-block">Bank Address</small>
                                        <strong>{{ $transactionRequest->beneficiary->bank_address ?? '--' }}</strong>
                                    </div>

                                </div>
                            </div>
                        </div>

                        {{-- OpenPayd Verification --}}
                        {{-- @if ($virtualProvider == 'openpayd' && $isOpenPaydEnabled)
                            <div class="alert alert-warning mt-3 mb-0">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox"
                                        class="custom-control-input"
                                        id="verified-beneficiary"
                                        name="verified-beneficiary">

                                    <label class="custom-control-label" for="verified-beneficiary">
                                        I confirm that
                                        <strong>{{ $transactionRequest->beneficiary->name }}</strong>
                                        has been verified.
                                    </label>
                                </div>

                                <span class="text-danger d-block mt-2"
                                    id="error-message-for-beneficiary-checked"
                                    style="display:none;">
                                </span>
                            </div>
                        @endif --}}

                    </div>
                </div>
            </div>
        </div>
    @endif

    @if (!empty($transaction_log))
        
        {{-- @include('bitpace.transaction-log') --}}

        {{-- <div class="modal fade" id="virtual-transaction-modal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title ">Virtual Bank Transaction Details</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true"><i class="fal fa-times"></i></span>
                        </button>
                    </div>
                    <div class="modal-body">
                        @include('bitpace.transaction-log')
                    </div>
                </div>
            </div>
        </div> --}}
        {{-- @else --}}
    @endif
    @include('finance-management.transactions.partials.deposit-with-sepa-warning-modal')

@stop
@section('scripts')
    <script>
        var controls = {
            leftArrow: '<i class="fal fa-angle-left" style="font-size: 1.25rem"></i>',
            rightArrow: '<i class="fal fa-angle-right" style="font-size: 1.25rem"></i>'
        }
        var is_virtual_transfer = 0;

        function getOPenPaydBankAccounts(currency) {
            showLoadingSpinner();
            var transaction_method_id = $("#transaction_method_id").val();

            $.post("{{ url('/finance-management/transactions/main-bank-accounts') }}", {
                    currency: currency,
                    transaction_method_id: transaction_method_id,
                    _token: $('meta[name="csrf-token"]').attr('content') // Ensure CSRF token is included
                })
                .done(function(response) {
                    console.log('Bank accounts fetched:', response);
                    var $select = $("#funding_bank_account_id");
                    $select.empty(); // Clear previous options
                    $select.append($("<option selected value=''>").text("Select Funding  Bank Account").attr("disabled",
                        true));
                    if (response.empty == false && Array.isArray(response.content)) {
                        is_virtual_transfer = 1;
                        $('#funding-source-row').show();
                        //console.log(response.content);
                        response.content.forEach(function(account) {
                            var availableBalance = account.availableBalance.value;
                            var accountCurrency = account.availableBalance.currency;
                            // if (availableBalance > 0) {
                            var optionText = account.bankAccountHolderName + " - " + account.friendlyName +
                                ' ( ' + availableBalance + ' ' + accountCurrency + ' )';
                            var option = $("<option>")
                                .val(account.id)
                                .text(optionText);
                            $select.append(option);
                            // }
                        });
                        hideSpinner();
                    } else {
                        is_virtual_transfer = 0;
                        $('#funding-source-row').hide();
                        // Optionally show a disabled option if no data
                        $select.append($("<option>").text("No bank accounts available").attr("disabled", true));
                        hideSpinner();
                    }
                    // You can now update your DOM or call another function with the response
                })
                .fail(function(xhr, status, error) {
                    console.error('Error fetching bank accounts:', xhr.responseText || error);
                    hideSpinner();
                });

        }

        function handleChangePaymentDocument(value, e) {
            if (value) {
                e.closest('.row').querySelector('.btn-add-document').classList.remove('d-none')
                e.closest('.row').querySelector('.btn-remove-document').classList.remove('d-none')
            } else {
                e.closest('.row').querySelector('.btn-add-document').classList.add('d-none')
                e.closest('.row').querySelector('.btn-remove-document').classList.add('d-none')
            }
        }

        function handleAddPaymentDocument(e) {
            e.closest('.form-group').insertAdjacentHTML(
                'beforeend',
                '<div class="row mt-2"> <div class="col-md-9"> <input type="file" required class="form-control" onchange="handleChangePaymentDocument(this.value, this)" name="payment_advice[]" value=""> </div> <div class="col-md-1"> <button class="btn btn-primary btn-add-document d-none" onclick="handleAddPaymentDocument(this)" type="button"><i class="fal fa-plus"></i></button> </div> <div class="col-md-1 ml-2"> <button class="btn btn-danger btn-remove-document" onclick="handleRemovePaymentDocument(null, true, this)" type="button"><i class="fal fa-times"></i></button> </div> </div>'
            )
        }

        function handleRemovePaymentDocument(inputId, deleteContainer, e) {
            if (deleteContainer) {
                e.closest('.row').remove()
            } else {
                document.getElementById(inputId).value = ""
                e.closest('.row').querySelector('.btn-add-document').classList.add('d-none')
                e.closest('.row').querySelector('.btn-remove-document').classList.add('d-none')
            }
        }

        function rejectTransaction() {
            $("#reject-modal").modal('show');
        }

        function viewBeneficiary() {
            $("#beneficiary-modal").modal('show');
        }

        function viewVirtualBankTransaction() {
            $("#virtual-transaction-modal").modal('show');
        }

        function handleOnChangeIsZeroize(checkbox) {
            if (checkbox.checked) {
                Swal.fire({
                    title: "Are you sure?",
                    text: "All of the Debit Account's amount will be used for this transaction.",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonText: "Yes, Zeroize it!"
                }).then(function(result) {
                    if (result.value) {
                        const amount = document.getElementById('amount')
                        amount.value = '{{ $balance }}'
                        // amount.disabled = true
                    } else {
                        checkbox.checked = false
                    }
                });
            } else {
                // document.getElementById('amount').disabled = false
                document.getElementById('amount').value = '{{ $amount }}'
            }
        }

        $(document).ready(function() {
            if ('Credit' === '{{ $transaction->transaction_type }}' ||
                'Approved' === '{{ $transaction->status }}') {
                document.getElementById('is_zeroize').checked = false
                document.getElementById('is_zeroize').disabled = true
                document.getElementById('is_zeroize_div').disabled = true
            }

            // minimum setup
            $('#transaction_date').datepicker({
                format: 'dd/mm/yyyy',
                todayHighlight: true,
                orientation: "bottom left",
                templates: controls
            });
        })


        @if ($isVirtualTransaction)
            $('#verified-beneficiary').on('change', function() {
                const errorMessage = document.getElementById('error-message-for-beneficiary-checked');
                if (this.checked) {
                    errorMessage.style.display = 'none';
                    $('#beneficiary-modal').modal('hide');
                } else {
                    errorMessage.style.display = 'block';
                    errorMessage.innerHTML = 'Please verify the beneficiary before approving the transaction.';
                }
            });
        @endif

        function approveTT() {
            const is_SEPA_trn = $('#is_virtual_bank_trn').val() == 1;
            const isSweepTrn = @json($isSweep);
            const isBeneficiaryTransfer = @json($transaction->beneficiary_id);

            if (is_SEPA_trn) {
                const verifiedBeneficiary = document.getElementById('verified-beneficiary');
                const errorMessage = document.getElementById('error-message-for-beneficiary-checked');

                if (verifiedBeneficiary) {
                    if (!verifiedBeneficiary.checked) {
                        viewBeneficiary();
                        errorMessage.style.display = 'block';
                        errorMessage.innerHTML = 'Please verify the beneficiary before approving the transaction.';
                        verifiedBeneficiary.focus();
                        return;
                    } else {
                        errorMessage.style.display = 'none';
                    }
                }
            }

            if (is_virtual_transfer == 1) {
                var funding_bank_account_id = $('#funding_bank_account_id').val();
                console.log(funding_bank_account_id);
                if (!funding_bank_account_id || funding_bank_account_id == '') {
                    $('#error-message-for-funding-bank-account').show();
                } else {
                    $('#error-message-for-funding-bank-account').hide();
                }
            }
            //return false;

            if (isSweepTrn == null && !isBeneficiaryTransfer) {
                const verifiedIsSweepCheckbox = document.getElementById('verified-approve-sepa-without-sweep');
                const transactionLog = @json($transaction_log);

                if (verifiedIsSweepCheckbox.value === "false" && (transactionLog.length !== 0)) {
                    $('#sweep-transaction-missing-warning').modal('show');
                    return
                }
            }

            if (is_virtual_transfer == 0 && !isBeneficiaryTransfer) {
                const transaction_method_text = $('#transaction_method_id').find("option:selected").text().trim();
                const transaction_is_confirmed = $('#confirm-deposit-transaction-with-sepa').val().trim();

                if (!["SWIFT", "Select Transaction Method"].includes(transaction_method_text) && transaction_is_confirmed ==
                    "false") {
                    $("#transaction-method").text(transaction_method_text);
                    $('#sepa-deposit-approval-warning-modal').modal('show');
                    return;
                }
            }

            Swal.fire({
                title: "Are you sure?",
                text: "You are about to approve this transaction.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Yes, Approve it!"
            }).then(function(result) {
                if (result.value) {
                    document.getElementById('approval-form').submit();
                }
            });
        }
    </script>
@stop
