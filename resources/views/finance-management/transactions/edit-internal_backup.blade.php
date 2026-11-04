@extends('layouts.cp')
@section('title', 'Update Internal Transfer Transaction - Finance Management')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Finance Management</span>
        </h1>
        <div class="d-flex">
            <a href="{{ url()->previous() }}" class="btn btn-primary ml-2 mx-md-2"><i class="fal fa-arrow-alt-left"></i> Go
                Back </a>
            <a href="{{ url('finance-management/transactions/print/' . $transaction->id) }}"
                class="btn btn-primary ml-2 mx-md-2" target="_blank"><i class="fal fa-print"></i> Print </a>
        </div>
    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false"
        data-panel-color="false" data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                Update Internal Transfer Transaction
            </h2>
        </div>

        <div class="panel-container show">
            <div class="panel-content">
                @include('common.error-message')
                <div id="tabs">
                    <ul class="nav nav-tabs nav-tabs-clean" role="tablist">
                        <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#tab-general" role="tab"
                                aria-selected="false">Transaction</a></li>
                        @if (isset($changes) && count($changes) > 0)
                            <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#changes" role="tab"
                                    aria-selected="true">Changes</a></li>
                        @endif
                    </ul>

                    <div class="tab-content p-3">
                        <div class="tab-pane fade active show" id="tab-general" role="tabpanel"
                            aria-labelledby="tab-general">
                            <form id="transaction_form" method="post"
                                action="{{ url('/finance-management/transactions/update/internal') }}"
                                enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="id" value="{{ $transaction->id }}">
                                <input type="hidden" name="credit_currency_id" id="credit_currency_id"
                                    value="{{ $linkedTransaction->account->account_currency }}">
                                <input type="hidden" name="debit_currency_id" id="debit_currency_id"
                                    value="{{ $transaction->account->account_currency }}">
                                @if ($transaction->status == 'Approved')
                                    <input type="hidden" name="account_id" value="{{ $transaction->account_id }}">
                                @endif
                                <div class="panel-container show">
                                    <div class="panel-content">
                                        <h4><strong>Debit Transaction</strong></h4>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="row">
                                                    <div class="col-md-8">
                                                        <div class="form-group">
                                                            <label class="form-label" for="account_id">Account</label>
                                                            <select
                                                                onchange="getAccountBalance(this.value, 'account_balance');"
                                                                {{ $transaction->status != 'Approved' ? '' : 'disabled' }}
                                                                name="account_id" autofocus id="account_id" value=""
                                                                data-select2-id="account_id"
                                                                class="form-control select2 w-100" required>
                                                                <option value="">choose client account</option>
                                                                @foreach ($accounts as $account)
                                                                    @if ($account->id == $transaction->account_id)
                                                                        <option value="{{ $account->id }}" selected>
                                                                            {{ $account->title . ' - ' . $account->account_number }}</option>
                                                                    @else
                                                                        <option value="{{ $account->id }}">
                                                                            {{ $account->title . ' - ' . $account->account_number }}</option>
                                                                    @endif
                                                                @endforeach
                                                            </select>
                                                            @error('account_id')
                                                                <span class="invalid-feedback"
                                                                    role="alert"><strong>{{ $message }}</strong></span>
                                                            @enderror
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="form-group">
                                                            <label class="form-label" for="account_balance">Account
                                                                Balance</label>
                                                            <div class="form-control" id="account_balance" readonly>--</div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row mt-4 exchange-rate" style="display: none">
                                                    <div class="col-md-12">
                                                        <div class="alert alert-warning">
                                                            <div class="row">
                                                                <div class="col-md-6">
                                                                    <p>Please provide the exchange rate if you are making
                                                                        cross currency
                                                                        transaction.</p>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <label class="form-label" for="ex_rate">Exchange
                                                                        Rate</label>
                                                                    <input required readonly onchange="checkIfNotZero(this)"
                                                                        min="0" type="text" autocomplete="off"
                                                                        class="form-control" name="ex_rate"
                                                                        value="{{ $transaction->ex_rate }}"
                                                                        id="ex_rate">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row mt-4">
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label class="form-label" for="amount">Transaction
                                                                Amount</label>
                                                            <input type="number" step="any" id="amount"
                                                                name="amount" min="1"
                                                                value="{{ $transaction->amount }}" class="form-control"
                                                                required>
                                                            @error('amount')
                                                                <span class="invalid-feedback"
                                                                    role="alert"><strong>{{ $message }}</strong></span>
                                                            @enderror
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="form-group">
                                                            <label class="form-label" for="transaction_date">Transaction
                                                                Date</label>
                                                            <input type="text" autocomplete="off" class="form-control"
                                                                value="{{ \Carbon\Carbon::parse($transaction->transaction_date)->format('d/m/Y') }}"
                                                                name="transaction_date" id="transaction_date"
                                                                placeholder="Select date" readonly>
                                                            @error('transaction_date')
                                                                <span class="invalid-feedback"
                                                                    role="alert"><strong>{{ $message }}</strong></span>
                                                            @enderror
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="form-group">
                                                            <label class="form-label" for="transaction_time">Time
                                                                (HH:MM)</label>
                                                            <input type="text"
                                                                value="{{ \Carbon\Carbon::parse($transaction->transaction_date)->format('H:i') }}"
                                                                readonly autocomplete="off" class="form-control"
                                                                name="transaction_time" id="transaction_time"
                                                                placeholder="time">
                                                        </div>
                                                    </div>
                                                </div>

                                                {{-- <label class="form-label mt-4">Type: </label>
                                                <div class="custom-control custom-radio custom-control-inline mb-3">
                                                    <input type="radio" @if ($transaction->transaction_type == 'Credit') checked @endif
                                                        class="custom-control-input" id="credit" value="Credit"
                                                        name="transaction_type" required>
                                                    <label class="custom-control-label" for="credit">Credit</label>
                                                </div>
                                                <div class="custom-control custom-radio custom-control-inline mb-3">
                                                    <input type="radio" @if ($transaction->transaction_type == 'Debit') checked @endif
                                                        class="custom-control-input" id="debit" value="Debit"
                                                        name="transaction_type" required>
                                                    <label class="custom-control-label" for="debit">Debit</label>
                                                </div> --}}
                                            </div>
                                            <div class="col-md-6" style="border-left: 1px solid lightgray;">
                                                <div class="form-group">
                                                    <label class="form-label" for="name">Remarks</label>
                                                    <textarea maxlength="92" class="form-control" required name="remarks" id="remarks" cols="30" rows="2"
                                                        placeholder="Please provide relvant remarks for the transaction.">{{ $transaction->remarks }}</textarea>
                                                    <small>maximum 92 characters allowed.</small>
                                                </div>

                                            </div>
                                        </div>

                                        {{-- Linked Credit Transaction Data --}}
                                        @if ($linkedTransaction)
                                            <hr class="mt-5">
                                            <h4><strong>Credit Transaction</strong></h4>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="row">
                                                        <div class="col-md-8">
                                                            <div class="form-group">
                                                                <label class="form-label"
                                                                    for="linked_transaction_account_id">Account</label>
                                                                <select
                                                                    onchange="getAccountBalance(this.value, 'linked_transaction_account_balance');"
                                                                    {{ $linkedTransaction->status != 'Approved' ? '' : 'disabled' }}
                                                                    name="linked_transaction_account_id" autofocus
                                                                    id="linked_transaction_account_id" value=""
                                                                    data-select2-id="linked_transaction_account_id"
                                                                    class="form-control select2 w-100" required>
                                                                    <option value="">choose client account</option>
                                                                    @foreach ($accounts as $account)
                                                                        @if ($account->id == $linkedTransaction->account_id)
                                                                            <option value="{{ $account->id }}" selected>
                                                                                {{ $account->title . ' - ' . $account->account_number }}</option>
                                                                        @else
                                                                            <option value="{{ $account->id }}">
                                                                                {{ $account->title . ' - ' . $account->account_number }}</option>
                                                                        @endif
                                                                    @endforeach
                                                                </select>
                                                                @error('linked_transaction_account_id')
                                                                    <span class="invalid-feedback"
                                                                        role="alert"><strong>{{ $message }}</strong></span>
                                                                @enderror
                                                            </div>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <div class="form-group">
                                                                <label class="form-label"
                                                                    for="linked_transaction_account_balance">Account
                                                                    Balance</label>
                                                                <div class="form-control"
                                                                    id="linked_transaction_account_balance" readonly>--
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row mt-4">
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label class="form-label"
                                                                    for="linked_transaction_amount">Transaction
                                                                    Amount</label>
                                                                <input type="number" step="any"
                                                                    id="linked_transaction_amount"
                                                                    name="linked_transaction_amount" readonly
                                                                    min="1"
                                                                    value="{{ $linkedTransaction->amount }}"
                                                                    class="form-control">
                                                                @error('linked_transaction_amount')
                                                                    <span class="invalid-feedback"
                                                                        role="alert"><strong>{{ $message }}</strong></span>
                                                                @enderror
                                                            </div>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <div class="form-group">
                                                                <label class="form-label"
                                                                    for="linked_transaction_date">Transaction
                                                                    Date</label>
                                                                <input type="text" autocomplete="off"
                                                                    class="form-control"
                                                                    value="{{ \Carbon\Carbon::parse($linkedTransaction->transaction_date)->format('d/m/Y') }}"
                                                                    name="linked_transaction_date"
                                                                    id="linked_transaction_date" placeholder="Select date"
                                                                    required>
                                                                @error('linked_transaction_date')
                                                                    <span class="invalid-feedback"
                                                                        role="alert"><strong>{{ $message }}</strong></span>
                                                                @enderror
                                                            </div>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <div class="form-group">
                                                                <label class="form-label"
                                                                    for="linked_transaction_time">Time
                                                                    (HH:MM)</label>
                                                                <input type="text"
                                                                    value="{{ \Carbon\Carbon::parse($linkedTransaction->transaction_date)->format('H:i') }}"
                                                                    required autocomplete="off" class="form-control"
                                                                    name="linked_transaction_time"
                                                                    id="linked_transaction_time" placeholder="time">
                                                            </div>
                                                        </div>
                                                    </div>

                                                    {{-- <label class="form-label mt-4">Type: </label>
                                                    <div class="custom-control custom-radio custom-control-inline mb-3">
                                                        <input type="radio"
                                                            @if ($linkedTransaction->transaction_type == 'Credit') checked @endif
                                                            class="custom-control-input" id="linked_transaction_credit"
                                                            value="Credit" name="linked_transaction_type" required>
                                                        <label class="custom-control-label" for="credit">Credit</label>
                                                    </div> --}}
                                                    {{-- <div class="custom-control custom-radio custom-control-inline mb-3">
                                                    <input type="radio" @if ($linkedTransaction->transaction_type == 'Debit') checked @endif
                                                        class="custom-control-input" id="debit" value="Debit"
                                                        name="transaction_type" required>
                                                    <label class="custom-control-label" for="debit">Debit</label>
                                                </div> --}}
                                                </div>
                                                <div class="col-md-6" style="border-left: 1px solid lightgray;">
                                                    <div class="form-group">
                                                        <label class="form-label" for="name">Remarks</label>
                                                        <textarea maxlength="92" class="form-control" required name="linked_transaction_remarks"
                                                            id="linked_transaction_remarks" cols="30" rows="2"
                                                            placeholder="Please provide relvant remarks for the transaction.">{{ $linkedTransaction->remarks }}</textarea>
                                                        <small>maximum 92 characters allowed.</small>
                                                    </div>

                                                </div>
                                            </div>
                                        @endif
                                        {{-- Linked Credit Transaction Data --}}

                                        @if (count($docs))
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <label class="form-label mt-4">Supporting Documents: </label>
                                                    @foreach ($docs as $doc)
                                                        <a href="{{ $doc->url }}"
                                                            class="btn btn-sm btn-outline-secondary" target="_blank"><i
                                                                class="fal fa-file-alt"></i> View</a>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </form>

                            @if ($transaction->status != 'Approved')
                                @include('global-partials.transactions.admin.fee')
                            @endif

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <hr>
                                        <button onclick="handleSubmit(); return false;"
                                            class="btn btn-primary">Submit</button>
                                        @can('approve transaction')
                                            @if (!in_array($transaction->status, ['Rejected', 'Approved']))
                                                <button onclick="approve(); return false;"
                                                    class="btn btn-success">Approve</button>
                                                <button onclick="reject(); return false;"
                                                    class="btn btn-danger">Reject</button>
                                            @endif
                                        @endcan
                                    </div>
                                </div>
                            </div>
                        </div>
                        @if (isset($changes) && count($changes) > 0)
                            <div class="tab-pane fade" id="changes" role="tabpanel" aria-labelledby="changes">
                                @if (\Illuminate\Support\Facades\Auth::user()->can('approve transaction'))
                                    @include('common.model-changes-approve')
                                @else
                                    @include('common.model-changes')
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="reject-modal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title ">Reject Client</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true"><i class="fal fa-times"></i></span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="{{ url('/finance-management/transactions/reject') }}" method="post">
                        @csrf
                        <input type="hidden" name="transaction_id" id="transaction_id" value="{{ $transaction->id }}">
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

        function handleSubmit() {
            document.getElementById('transaction_form').submit();
        }

        function reject() {
            $("#reject-modal").modal('show');
        }

        function getAccountBalance(account_id, balance_field_id) {
            $.post("{{ url('/finance-management/get-balance') }}", {
                    "account_id": account_id
                },
                function(data, status) {
                    $("#" + balance_field_id).html(data.formatted_balance);

                    if (!parseFloat(data.balance) > 0) {
                        $("#debit").attr("disabled", true);
                        $("#debit").prop('checked', false);
                    } else {
                        $("#debit").attr("disabled", false);
                    }
                });
        }

        function setExchangeRate() {

            const debit_currency_id = parseInt($("#debit_currency_id").val());
            const credit_currency_id = parseInt($("#credit_currency_id").val());

            if (debit_currency_id > 0 && credit_currency_id > 0 && debit_currency_id != credit_currency_id) {
                $(".exchange-rate").css("display", "block");
                $("#ex_rate").prop("readonly", false);
                $("#ex_rate").prop("placeholder", "please provide exchange rate");
                // $("#ex_rate").val("");
            } else {
                $(".exchange-rate").css("display", "none");
                $("#ex_rate").prop("readonly", true);
                $("#ex_rate").val(1);
            }
        }

        $(document).ready(function() {

            // get account balanc
            getAccountBalance({{ $transaction->account_id }}, 'account_balance');
            getAccountBalance({{ $linkedTransaction->account_id }}, 'linked_transaction_account_balance');
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

        function setCompanyType(type) {

            if (type == "Other") {
                $("#other-company-type").removeClass("disabled")
            } else {
                $("#other-company-type:not([class*='disabled'])").addClass("disabled");
            }
        }

        function setFile(obj) {

            var file = obj.files[0];
            var filename = file.name;
            document.getElementById("file-name-display").innerHTML = filename;
        }
    </script>
@stop
