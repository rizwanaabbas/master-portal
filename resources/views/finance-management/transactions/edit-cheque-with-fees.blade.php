@extends('layouts.cp')
@section('title', 'Update Cheque Transaction - Finance Management')

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
                Update Cheque Transaction
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
                                action="{{ url('/finance-management/transactions/update/cheque') }}"
                                enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="id" value="{{ $transaction->id }}">
                                @if ($transaction->status == 'Approved')
                                    <input type="hidden" name="account_id" value="{{ $transaction->account_id }}">
                                @endif
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="row">
                                            <div class="col-md-8">
                                                <div class="form-group">
                                                    <label class="form-label" for="account_id">Account</label>
                                                    <select onchange="getAccountBalance(this.value);" name="account_id"
                                                        {{ $transaction->status != 'Approved' ? '' : 'disabled' }} autofocus
                                                        id="account_id" value="" data-select2-id="account_id"
                                                        class="form-control select2 w-100" required>
                                                        <option value="">choose client account</option>
                                                        @foreach ($accounts as $account)
                                                            @if ($account->id == $transaction->account_id)
                                                                <option value="{{ $account->id }}" selected>
                                                                    {{ $account->title . ' - ' . $account->account_number }}</option>
                                                            @else
                                                                <option value="{{ $account->id }}">{{ $account->title . ' - ' . $account->account_number }}
                                                                </option>
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
                                                    <label class="form-label" for="account_balance">Account Balance</label>
                                                    <div class="form-control" id="account_balance" readonly>--</div>
                                                </div>
                                            </div>
                                        </div>


                                        <div class="row mt-4">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="form-label" for="amount">Transaction Amount</label>
                                                    <input type="number" step="any" id="amount" name="amount"
                                                        min="1" value="{{ $transaction->amount }}"
                                                        class="form-control" required>
                                                    @error('amount')
                                                        <span class="invalid-feedback"
                                                            role="alert"><strong>{{ $message }}</strong></span>
                                                    @enderror
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="form-label" for="transaction_date">Transaction
                                                        Date</label>
                                                    <input required type="text" autocomplete="off" class="form-control"
                                                        value="{{ \Carbon\Carbon::parse($transaction->transaction_date)->format('d/m/Y') }}"
                                                        name="transaction_date" id="transaction_date"
                                                        placeholder="Select date" required>
                                                    @error('transaction_date')
                                                        <span class="invalid-feedback"
                                                            role="alert"><strong>{{ $message }}</strong></span>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>

                                        <label class="form-label mt-4">Type: </label>
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
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label" for="name">Remarks</label>
                                            <textarea maxlength="92" required class="form-control" name="remarks" id="remarks" cols="30" rows="2"
                                                placeholder="Please provide relvant remarks for the transaction.">{{ $transaction->remarks }}</textarea>
                                            <small>maximum 92 characters allowed.</small>
                                        </div>

                                        @include(
                                            'finance-management.transactions.partials.update-supporting-documents',
                                            ['status' => $transaction->status]
                                        )

                                    </div>
                                    <div class="col-md-6" style="border-left: 1px solid lightgray;">
                                        <div class="form-group">
                                            <label class="form-label" for="in_fav_of">In Fav Of</label>
                                            <input type="text" id="in_fav_of" name="in_fav_of"
                                                value="{{ $transaction->cheque->in_fav_of }}" class="form-control" required>
                                            @error('in_fav_of')
                                                <span class="invalid-feedback"
                                                    role="alert"><strong>{{ $message }}</strong></span>
                                            @enderror
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="form-label" for="cheque_number">Cheque Number</label>
                                                    <input type="text" id="cheque_number" name="cheque_number"
                                                        value="{{ $transaction->cheque->cheque_number }}"
                                                        class="form-control" required>
                                                    @error('cheque_number')
                                                        <span class="invalid-feedback"
                                                            role="alert"><strong>{{ $message }}</strong></span>
                                                    @enderror
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="form-label" for="cheque_date">Cheque Date</label>
                                                    <input type="text" autocomplete="off" class="form-control"
                                                        value="{{ \Carbon\Carbon::parse($transaction->cheque->cheque_date)->format('d/m/Y') }}"
                                                        name="cheque_date" id="cheque_date" placeholder="Select date"
                                                        required>
                                                    @error('cheque_date')
                                                        <span class="invalid-feedback"
                                                            role="alert"><strong>{{ $message }}</strong></span>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>


                                        <div class="form-group mt-4">
                                            <label class="form-label" for="cheque_bank">Bank Name</label>
                                            <input type="text" id="name" name="cheque_bank"
                                                value="{{ $transaction->cheque->cheque_bank }}" class="form-control"
                                                required>
                                            @error('cheque_bank')
                                                <span class="invalid-feedback"
                                                    role="alert"><strong>{{ $message }}</strong></span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                {{--
                                @if (count($docs))
                                    <div class="row">
                                        <div class="col-md-12">
                                            <label class="form-label mt-4">Supporting Documents: </label>
                                            @foreach ($docs as $doc)
                                                <a href="{{ $doc->url }}" class="btn btn-sm btn-outline-secondary"
                                                    target="_blank"><i class="fal fa-file-alt"></i> View</a>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif --}}

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
                            <input type="hidden" name="transaction_id" id="transaction_id"
                                value="{{ $transaction->id }}">
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

            function getAccountBalance(account_id) {
                $.post("{{ url('/finance-management/get-balance') }}", {
                        "account_id": account_id
                    },
                    function(data, status) {
                        $("#account_balance").html(data.balance);
                        const trnStatus = '@php echo $transaction->status @endphp'

                        if (!parseFloat(data.balance) > 0 && trnStatus != 'Approved') {
                            $("#debit").attr("disabled", true);
                            $("#debit").prop('checked', false);
                        } else {
                            $("#debit").attr("disabled", false);
                        }
                    });
            }

            $(document).ready(function() {

                // get account balance
                getAccountBalance({{ $transaction->account_id }})

                $('.select2').select2();

                // minimum setup
                $('#transaction_date').datepicker({
                    format: 'dd/mm/yyyy',
                    todayHighlight: true,
                    orientation: "bottom left",
                    templates: controls,
                    endDate: "{{ \Carbon\Carbon::today()->format('d/m/Y') }}"

                });

                $('#cheque_date').datepicker({
                    format: 'dd/mm/yyyy',
                    todayHighlight: true,
                    orientation: "bottom left",
                    templates: controls
                });
            })
        </script>
    @stop
