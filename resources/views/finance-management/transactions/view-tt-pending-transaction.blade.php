@extends('layouts.cp')
@section('title', 'TT Transaction - Finance Management')

@section('content')

    @php
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

        $beneficiary_type_map = [
            'RETAIL' => 'Individual',
            'CORPORATE' => 'Company',
            'individual' => 'Individual',
            'company' => 'Company',
        ];
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
            @php
                $disabled = $disabledZenusApproval || $disabledLorumApproval ? 'disabled' : '';

                if (
                    in_array($transactionRequest->compliance_status, $complianceStatus) &&
                    in_array('NeroData', $enabledModules)
                ) {
                    $disabled = 'disabled';
                }

            @endphp
            <h2>
                TT Transaction
            </h2>

        </div>

        @php
            $zeroize = 0;
            $amount = $transactionRequest->amount;
            $transaction_date = $transactionRequest->created_at;
            $remarks = $transactionRequest->remarks;
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
            $needle =
                $transactionRequest->status == 'Approved' ||
                $disabledZenusApproval === true ||
                $disabledLorumApproval === true
                    ? 'update/tt-with-fees'
                    : 'approve';

            $amount = isset($detailsChanges['amount'])
                ? $detailsChanges['amount']['new_value'][0]
                : $transactionRequest->amount;

            $zeroize = isset($detailsChanges['zeroize']) ? $detailsChanges['zeroize']['new_value'][0] : 0;

            $transaction_date = isset($detailsChanges['transaction_date'])
                ? $detailsChanges['transaction_date']['new_value'][0]
                : $transactionRequest->transaction_date;

            $remarks = isset($detailsChanges['remarks'])
                ? $detailsChanges['remarks']['new_value'][0]
                : $transactionRequest->remarks;
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

                <form id="approval-form" method="post"
                    action="{{ $actionUrl ? $actionUrl : url('/finance-management/pending-transaction/approve/tt') }}"
                    enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" id="id" name="id" value="{{ $transactionRequest->id }}">
                    <input type="hidden" id="platform" name="platform" value="client_portal">
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
                            @php
                                $balanceBreakdown = \App\Helpers\Common::getAccountBalanceBreakdownForTrApproval($transactionRequest);
                                $balance = $balanceBreakdown['approval_available'];
                            @endphp
                            <tr>
                                <th>Amount</th>
                                <td>
                                    <div class="row">
                                        <div class="col-md-10">
                                            <input type="number" step="any" id="amount" name="amount"
                                                onfocus="handleOnfocus('amount_message')" value="{{ $amount }}"
                                                class="form-control" required />

                                            @if (isset($detailsChanges['amount']))
                                                {!! $detailsChanges['amount']['tooltip'] !!}
                                            @endif
                                        </div>
                                        <div class="col-md-2">
                                            <div class="custom-control custom-switch mt-2" id="is_zeroize_div">
                                                <input type="checkbox" onchange="handleOnChangeIsZeroize(this)"
                                                    class="custom-control-input" id="is_zeroize" value="1"
                                                    name="is_zeroize">
                                                <label class="custom-control-label" for="is_zeroize"><b>Zeroize</b></label>
                                            </div>
                                            @if (isset($detailsChanges['zeroize']))
                                                {!! $detailsChanges['zeroize']['tooltip'] !!}
                                            @endif
                                        </div>
                                    </div>
                                </td>
                            </tr>

                            @include('global-partials.transactions.client.fee')

                            <tr>
                                <th>Request Date</th>
                                <td>{{ \Carbon\Carbon::parse($transactionRequest->created_at)->format('d M, Y') }}</td>
                            </tr>

                            <!-- check if the TT is for external transfer -->
                            @if ($transactionRequest->beneficiary_id > 0)
                                <tr>
                                    <th><label class="form-label" for="transaction_date">Transaction Date</label></th>
                                    <td>
                                        <div class="row">
                                            <div class="col-md-2">
                                                <input type="text" autocomplete="off" class="form-control"
                                                    value="{{ \Carbon\Carbon::parse($transactionRequest->transaction_date)->format('d/m/Y') }}"
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
                                    <td>
                                        <button onclick="viewBeneficiary();" type="button"
                                            class="btn btn-outline-secondary btn-sm">{{ $transactionRequest->beneficiary->name }}</button>

                                        @if ($isOpenPaydEnabled && empty($virtual_beneficaiary))
                                            <button type="button" class="mx-6 btn btn-info btn-sm"
                                                onclick="linkToOpenPayd({{ $transactionRequest->beneficiary_id }})">
                                                Link beneficiary to OpenPayd
                                            </button>
                                        @endif

                                    </td>
                                </tr>
                            @else
                                <tr>
                                    <th>Deposit Date</th>
                                    <td>
                                        <div class="row">
                                            <div class="col-md-2">
                                                <input type="text" autocomplete="off" class="form-control"
                                                    value="{{ \Carbon\Carbon::parse($transactionRequest->transaction_date)->format('d/m/Y') }}"
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
                            @endif

                            @if (in_array($transactionRequest->currency->virtual_iban_provider, ['openpayd', 'lorum', 'zenus']) &&
                                    !empty($transaction_log))
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
                                <td>{{ $transactionRequest->transaction_type }}</td>
                            </tr>
                            <tr>
                                <th>Mode</th>
                                <td>{{ $transactionRequest->transaction_nature }}</td>
                            </tr>
                            <tr>
                                <th>Remarks</th>
                                <td>
                                    {{-- @if ($transactionRequest->status == 'Pending') --}}
                                    <div class="row">
                                        <div class="col-md-12">
                                            <textarea name="remarks" id="remarks" class="form-control" cols="30" rows="2">{{ old('remarks', $transactionRequest->remarks) }}</textarea>
                                            @if (isset($detailsChanges['remarks']))
                                                {!! $detailsChanges['remarks']['tooltip'] !!}
                                            @endif
                                        </div>
                                    </div>
                                    {{-- @else
                                        {{ $transactionRequest->remarks }}
                                        @endif --}}
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
                                <th>Transaction Notes</th>
                                <td>
                                    @include('finance-management.transactions.partials.transaction-notes', [
                                        'transaction_request_id' => $transactionRequest->id,
                                    ])
                                </td>
                            </tr>

                            {{-- @if (in_array('OpenPayd', $enabledModules) && $transactionRequest->currency->virtual_iban_provider == 'openpayd' && $virtual_beneficaiary) --}}
                            @if ($isVirtualTransaction)
                                <tr>
                                    <th>Client Preffered Transaction Method</th>
                                    <td>
                                        <b> {{ $transactionRequest->method->name ?? 'N/A' }} </b>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Transaction Method</th>
                                    <td>
                                        @php
                                            $callFunc = '';
                                            if (
                                                $transactionRequest->transaction_type == 'Debit' &&
                                                !empty($transactionRequest->beneficiary->virtualBankBeneficiary)
                                            ) {
                                                $callFunc =
                                                    'getOPenPaydBankAccounts("' .
                                                    $transactionRequest->beneficiary->virtualBankBeneficiary->currency .
                                                    '")';
                                            }
                                        @endphp
                                        <div class="row">
                                            <div class="col-md-12">
                                                <select name="transaction_method_id" id="transaction_method_id"
                                                    data-select2-id="transaction_method_id" class="form-control select2"
                                                    required onchange="{{ $callFunc }}">
                                                    <option value="">Select Transaction Method</option>
                                                    @foreach ($transaction_methods as $method)
                                                        <option value="{{ $method->id }}">
                                                            {{ $method->name }}
                                                            @if (!empty($trn->method->module->name))
                                                                - {{ $trn->method->module->name }}
                                                            @elseif(!$method->is_default)
                                                                - {{ $method->module->name }}
                                                            @endif
                                                        </option>
                                                    @endforeach

                                                </select>
                                            </div>
                                        </div>
                                    </td>

                                </tr>

                                @if ($transactionRequest->transaction_type == 'Debit' && !empty($transactionRequest->beneficiary))
                                    <tr id="funding-source-row" style="display:none">
                                        <th>Main SEPA Funding Bank Accounts</th>
                                        <td>
                                            @can('approve transaction')
                                                <select name="funding_bank_account_id" id="funding_bank_account_id"
                                                    data-select2-id="funding_bank_account_id"
                                                    class="form-control select2 w-100"
                                                    @if ($isOpenPaydEnabled && !empty($virtual_beneficaiary)) required @endif>
                                                    <option value="">Select Funding Bank Account</option>
                                                </select>
                                            @endcan
                                            <span class="text-muted text-danger pt-1" style="display: none;"
                                                id="error-message-for-funding-bank-account">Please select funcding bank
                                                account.</span>
                                        </td>

                                    </tr>
                                @endif
                            @else
                                <tr>
                                    <th>Client Preffered Transaction Method</th>
                                    <td>
                                        @if ($transactionRequest->transaction_method_id && $transactionRequest->method)
                                            <input type="text" class="form-control" name="transaction_method"
                                                id="transaction_method" value="{{ $transactionRequest->method->name }}"
                                                readonly />
                                            <input type="hidden" class="form-control" name="transaction_method_id"
                                                id="transaction_method_id" value="{{ $transactionRequest->method->id }}"
                                                readonly />
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                </tr>
                            @endif

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
                                @if ($transactionRequest->status == 'Rejected')
                            </tr>
                            <tr>
                                <th>Reason</th>
                                <td>{{ $transactionRequest->reject_reason }}</td>
                            </tr>
                            @endif
                            {{-- @if (in_array('Compliance', $enabledModules))
                                <tr>
                                    <th>Case ID</th>
                                    <td>{{ $transactionRequest->case_id }}</td>
                                </tr>
                                </tr>

                                @if (in_array('Compliance', $enabledModules) && isset($transactionRequest->is_suspicious))
                                    <tr>
                                        <th>
                                            Is Suspicious
                                        </th>
                                        @php  $isSuspicious = [0 => 'No', 1 => 'Yes']; @endphp
                                        <td>
                                            <span
                                                class="badge {{ $statusBadge[$transactionRequest->is_suspicious . '_suspicious'] }}">{{ $isSuspicious[$transactionRequest->is_suspicious] }}</span>

                                        </td>
                                    </tr>
                                @endif
                            @endif --}}
                            @if ($transactionRequest->corresponding_bank_name)
                                <tr>
                                    <th colspan="2">Corresponding Bank Details</th>
                                </tr>
                                <tr>
                                    <th>Bank Name</th>
                                    <td>{{ $transactionRequest->corresponding_bank_name }}</td>
                                </tr>
                                <tr>
                                    <th>Swift Code</th>
                                    <td>{{ $transactionRequest->corresponding_bank_swift_code }}</td>
                                </tr>
                                <tr>
                                    <th>Bank Address</th>
                                    <td>{{ $transactionRequest->corresponding_bank_address }}</td>
                                </tr>
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
                            @if ($isVirtualTransaction)
                                <div class="custom-control row">
                                    <div class="custom-checkbox col-md-2">
                                        <input type="hidden" class="custom-control-input custom-checkbox" type="checkbox"
                                            value="1" name="is_virtual_bank_trn" id="is_virtual_bank_trn">
                                        {{-- <label class="custom-control-label" for="is_virtual_bank_trn">Payout using OpenPayd</label> --}}
                                    </div>
                                </div>
                            @endif

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
                                        {{-- <button type="button" onclick="approve();" class="btn btn-success" id="approveButton" {{ $disabled }} @if ($transactionRequest->is_suspicious && !$transactionRequest->is_resolved) disabled @endif>Approve</button> --}}
                                        <button type="button" onclick="approveTT();" class="btn btn-success"
                                            id="approveButton"
                                            {{ $disabledZenusApproval || $disabledLorumApproval ? 'disabled' : '' }}>Approve</button>
                                    @endif

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
                                <strong><i class="fal fa-user-circle mr-2"></i>Bank Details</strong>
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
                                        <small class="text-muted d-block">Bank Account Address</small>
                                        <strong>{{ $transactionRequest->beneficiary->bank_address ?? '--' }}</strong>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <small class="text-muted d-block">Bank City</small>
                                        <strong>{{ $transactionRequest->beneficiary->bank_city ?? '--' }}</strong>
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

    <form method="POST" name="delete-file" id="delete-file"
        action="{{ url('/finance-management/transactions/files/delete') }}">
        @csrf
        <input type="hidden" name="file_id" id="file_id" value="">
    </form>

    @include('finance-management.transactions.partials.deposit-with-sepa-warning-modal')

@stop
@section('scripts')
    <script>
        var controls = {
            leftArrow: '<i class="fal fa-angle-left" style="font-size: 1.25rem"></i>',
            rightArrow: '<i class="fal fa-angle-right" style="font-size: 1.25rem"></i>'
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

        var is_sepa_transfer = 0;

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
                        is_sepa_transfer = 1;
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
                        is_sepa_transfer = 0;
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
                document.getElementById('amount').value = '{{ $transactionRequest->amount }}'
            }
        }

        $(document).ready(function() {
            if ('Credit' === '{{ $transactionRequest->transaction_type }}' ||
                'Approved' === '{{ $transactionRequest->status }}') {
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

        @if ($virtualProvider == 'openpayd' && $isOpenPaydEnabled)
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
            const isOpenpaydBeneficiary = @json($virtual_beneficaiary);

            if (is_SEPA_trn) {
                const verifiedBeneficiary = document.getElementById('verified-beneficiary');
                const errorMessage = document.getElementById('error-message-for-beneficiary-checked');

                // if (verifiedBeneficiary) {
                //     if (!verifiedBeneficiary.checked) {
                //         viewBeneficiary();
                //         errorMessage.style.display = 'block';
                //         errorMessage.innerHTML = 'Please verify the beneficiary before approving the transaction.';
                //         verifiedBeneficiary.focus();
                //         return;
                //     } else {
                //         errorMessage.style.display = 'none';
                //     }
                // }
            }

            if (is_sepa_transfer == 1) {
                var funding_bank_account_id = $('#funding_bank_account_id').val();
                console.log(funding_bank_account_id);
                if (!funding_bank_account_id || funding_bank_account_id == '') {
                    $('#error-message-for-funding-bank-account').show();
                } else {
                    $('#error-message-for-funding-bank-account').hide();
                }
            }
            //return false;

            if (is_sepa_transfer == 0 && !isOpenpaydBeneficiary) {
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

        function linkToOpenPayd(ben_id) {

            Swal.fire({
                title: "Are you sure?",
                text: "You want to link this beneficiary to OpenPayd?",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Yes, Submit it!"
            }).then(function(result) {
                if (result.value) {
                    window.location = "{{ url('/finance-management/beneficiaries/link-to-virtual-bank') }}/" +
                        ben_id;
                }
            });
        }
    </script>
@stop
