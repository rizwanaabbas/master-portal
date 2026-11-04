@extends('layouts.cp')
@section('title', 'Update TT Transaction - Finance Management')

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
                Update TT Transaction
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
                                action="{{ url('/finance-management/transactions/update/tt') }}"
                                enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="balance" id="balance" value="0">
                                <input type="hidden" name="id" value="{{ $transaction->id }}">
                                @if ($transaction->status == 'Approved')
                                    <input type="hidden" name="account_id" value="{{ $transaction->account_id }}">
                                @endif
                                <div class="panel-container show">
                                    <div class="panel-content">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="row">
                                                    <div class="col-md-8">
                                                        <div class="form-group">
                                                            <label class="form-label" for="account_id">Account</label>
                                                            <select onchange="getAccountBalance(this.value);"
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
                                                                placeholder="Select date" required>
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
                                                                required autocomplete="off" class="form-control"
                                                                name="transaction_time" id="transaction_time"
                                                                placeholder="time">
                                                        </div>
                                                    </div>
                                                </div>

                                                <label class="form-label mt-4">Type: </label>
                                                <div class="custom-control custom-radio custom-control-inline mb-3">
                                                    <input onblur="verifyAmount(this.value);" type="radio"
                                                        @if ($transaction->transaction_type == 'Credit') checked @endif
                                                        class="custom-control-input" id="credit" value="Credit"
                                                        name="transaction_type" required>
                                                    <label class="custom-control-label" for="credit">Credit</label>
                                                </div>
                                                <div class="custom-control custom-radio custom-control-inline mb-3">
                                                    <input onblur="verifyAmount(this.value);" type="radio"
                                                        @if ($transaction->transaction_type == 'Debit') checked @endif
                                                        class="custom-control-input" id="debit" value="Debit"
                                                        name="transaction_type" required>
                                                    <label class="custom-control-label" for="debit">Debit</label>
                                                </div>
                                                @if (count($docs))
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label class="form-label mt-4">Supporting Documents:
                                                                </label>
                                                                <br />
                                                                @foreach ($docs as $doc)
                                                                    @if ($doc->is_payment == 0)
                                                                        <a href="{{ $doc->url }}"
                                                                            class="btn btn-sm btn-outline-secondary"
                                                                            target="_blank"><i
                                                                                class="fal fa-file-alt"></i>
                                                                            View</a>
                                                                    @endif
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="col-md-6" style="border-left: 1px solid lightgray;">
                                                <div class="form-group">
                                                    <label class="form-label" for="name">Remarks</label>
                                                    <textarea required class="form-control" name="remarks" id="remarks" cols="30" rows="2"
                                                        placeholder="Please provide relvant remarks for the transaction.">{{ $transaction->remarks }}</textarea>
                                                    <small>maximum 92 characters allowed.</small>
                                                </div>

                                                @if ($beneficiary)
                                                    <div class="form-group">
                                                        <label class="form-label mt-4">Beneficiary: </label>
                                                        <button onclick="viewBeneficiary();" type="button"
                                                            class="btn btn-outline-secondary btn-sm">{{ $beneficiary->name }}</button>
                                                    </div>
                                                @endif

                                                @if ($transaction->transaction_type == 'Debit')
                                                    <div class="form-group">
                                                        <label class="form-label mt-4" for="payment_advice_0">Payment
                                                            Advice /
                                                            MT
                                                            103 (SWIFT Copy)</label>
                                                        <br />
                                                        @if (count($docs))
                                                            @foreach ($docs as $doc)
                                                                @if ($doc->is_payment == 1)
                                                                    <a href="{{ $doc->url }}"
                                                                        class="btn btn-sm btn-outline-secondary mt-2 mr-2"
                                                                        target="_blank"><i class="fal fa-file-alt"></i>
                                                                        View</a>
                                                                    <a href="javascript:deleteFile({{ $doc->id }})"><i
                                                                            class="alert-danger fal fa-times-circle rounded-circle mr-2"></i>
                                                                    </a>
                                                                @endif
                                                            @endforeach
                                                        @endif
                                                        <div class="row mt-2">
                                                            <div class="col-md-6">
                                                                <input type="file" class="form-control"
                                                                    onchange="handleChangeDocument(this.value, this)"
                                                                    name="payment_advice[]" id="payment_advice_0"
                                                                    value="">
                                                            </div>
                                                            <div class="col-md-1">
                                                                <button class="btn btn-primary btn-add-document d-none"
                                                                    onclick="handleAddDocument(this)" type="button"><i
                                                                        class="fal fa-plus"></i></button>
                                                            </div>
                                                            <div class="col-md-1 ml-2">
                                                                <button class="btn btn-danger btn-remove-document d-none"
                                                                    onclick="handleRemoveDocument('payment_advice_0', false, this)"
                                                                    type="button"><i class="fal fa-times"></i></button>
                                                            </div>
                                                        </div>
                                                        @error('payment_advice')
                                                            <span class="invalid-feedback"
                                                                role="alert"><strong>{{ $message }}</strong></span>
                                                        @enderror
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
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
                            <form method="POST" name="delete-file" id="delete-file"
                                action="{{ url('/finance-management/transactions/files/delete') }}">
                                @csrf
                                <input type="hidden" name="file_id" id="file_id" value="">
                            </form>
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

    @if ($beneficiary)
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
                                    <td>{{ $beneficiary->name }}</td>
                                </tr>
                                <tr>
                                    <th>Account Number</th>
                                    <td>{{ $beneficiary->account_number }}</td>
                                </tr>
                                <tr>
                                    <th>IBAN Number</th>
                                    <td>{{ $beneficiary->iban }}</td>
                                </tr>
                                <tr>
                                    <th>Beneficiary Address</th>
                                    <td>{{ $beneficiary->address }}</td>
                                </tr>
                                <tr>
                                    <th>Bank Name</th>
                                    <td>{{ $beneficiary->bank_name }}</td>
                                </tr>
                                <tr>
                                    <th>Swift Code</th>
                                    <td>{{ $beneficiary->swift_code }}</td>
                                </tr>
                                <tr>
                                    <th>Bank Address</th>
                                    <td>{{ $beneficiary->bank_address }}</td>
                                </tr>
                                <tr>
                                    <th>Status</th>
                                    <td><span>{{ $beneficiary->status }}</span></td>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endif

@section('scripts')
    <script>
        var controls = {
            leftArrow: '<i class="fal fa-angle-left" style="font-size: 1.25rem"></i>',
            rightArrow: '<i class="fal fa-angle-right" style="font-size: 1.25rem"></i>'
        }

        function handleChangeDocument(value, e) {
            if (value) {
                e.closest('.row').querySelector('.btn-add-document').classList.remove('d-none')
                e.closest('.row').querySelector('.btn-remove-document').classList.remove('d-none')
            } else {
                e.closest('.row').querySelector('.btn-add-document').classList.add('d-none')
                e.closest('.row').querySelector('.btn-remove-document').classList.add('d-none')
            }
        }

        function handleAddDocument(e) {
            e.closest('.form-group').insertAdjacentHTML(
                'beforeend',
                '<div class="row mt-2"> <div class="col-md-6"> <input type="file" required class="form-control" onchange="handleChangeDocument(this.value, this)" name="payment_advice[]" value=""> </div> <div class="col-md-1"> <button class="btn btn-primary btn-add-document d-none" onclick="handleAddDocument(this)" type="button"><i class="fal fa-plus"></i></button> </div> <div class="col-md-1 ml-2"> <button class="btn btn-danger btn-remove-document d-none" onclick="handleRemoveDocument(null, true, this)" type="button"><i class="fal fa-times"></i></button> </div> </div>'
            )
        }

        function handleRemoveDocument(inputId, deleteContainer, e) {
            if (deleteContainer) {
                e.closest('.row').remove()
            } else {
                document.getElementById(inputId).value = ""
                e.closest('.row').querySelector('.btn-add-document').classList.add('d-none')
                e.closest('.row').querySelector('.btn-remove-document').classList.add('d-none')
            }
        }

        function handleSubmit() {
            document.getElementById('transaction_form').submit();
        }

        function reject() {
            $("#reject-modal").modal('show');
        }

        function verifyAmount(type) {
            var balance = parseFloat($("#balance").val());
            var amount = parseFloat($("#amount").val());

            if (type == "Debit") {
                if (balance < parseFloat(amount)) {
                    alert("Insufficient balance available, please use lower value than available balance (" + balance +
                        ")");
                    $("#amount").val(0);
                    return false;
                } else {
                    return true;
                }
            } else {
                return true;
            }
        }

        function getAccountBalance(account_id) {
            $.post("{{ url('/finance-management/get-balance') }}", {
                    "account_id": account_id
                },
                function(data, status) {
                    $("#account_balance").html(data.formatted_balance);
                });
        }

        function viewBeneficiary() {
            $("#beneficiary-modal").modal('show');
        }

        $(document).ready(function() {

            // get account balance
            getAccountBalance({{ $transaction->account_id }});

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

        function deleteFile(id) {
            Swal.fire({
                title: "Are you sure?",
                text: "File will be deleted permanently",
                type: "danger",
                icon: "danger",
                dangerMode: true,
                showCancelButton: true,
                confirmButtonText: "Yes, delete it!"
            }).then(function(result) {
                if (result.value) {
                    $("#file_id").val(id);
                    document.getElementById('delete-file').submit()
                }
            });
        }
    </script>
@stop

@stop
