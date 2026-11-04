@extends('layouts.cp')
@section('title', 'Update Crypto {{ $trn->transaction_nature }} Transaction - Crypto Management')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Crypto Management</span>
        </h1>
        <div class="d-flex">
            @if ($trn->crypto_system != '')
                @include('crypto-management.crypto-transactions.view.account-balances')
            @endif
            <a href="{{ url('crypto-management/crypto-transactions') }}" class="btn btn-primary ml-2 mx-md-2"><i
                    class="fal fa-arrow-alt-left"></i> Go Back </a>
            @if ($trn->status = 'Approved')
                <a href="{{ url('crypto-management/crypto-transactions/print/' . $trn->id) }}"
                    class="btn btn-primary ml-2 mx-md-2" target="_blank"><i class="fal fa-print"></i> Print </a>
            @endif
        </div>
    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false"
        data-panel-color="false" data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                @if ($trn->crypto_system != '')
                    {{ $trn->crypto_system }} >
                @endif Update Crypto {{ $trn->transaction_nature }} Transaction
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

                    <div class="tab-content tab-content p-3">
                        <form method="POST" action="{{ url('/crypto-management/crypto-transactions/update') }}"
                            enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="id" value="{{ $trn->id }}">
                            <input type="hidden" name="transaction_nature" value="{{ $trn->transaction_nature }}">
                            <div class="tab-pane fade active show" id="tab-general" role="tabpanel"
                                aria-labelledby="tab-general">

                                @if ('Approved' == $trn->status)
                                    <input type="hidden" name="account_id" value="{{ $trn->account_id }}">
                                    <div class="row bg-warning text-light p-5 my-3">
                                        <div class="col-md-12">
                                            <h2>
                                                Since this Transaction is already approved, you can only update the
                                                Supporting
                                                Documents.
                                            </h2>
                                        </div>
                                    </div>
                                @endif

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="row">
                                            <div class="col-md-8">
                                                <div class="form-group">
                                                    <label class="form-label" for="account_id">Account</label>
                                                    <select onchange="getAccountBalance(this.value);" name="account_id"
                                                        autofocus id="account_id" value=""
                                                        data-select2-id="account_id" class="form-control select2 w-100"
                                                        required>
                                                        <option value="">choose client account</option>
                                                        @foreach ($accounts as $account)
                                                            @if ($account->id == $trn->account_id)
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
                                                        value="{{ \App\Helpers\Common::toPlainDecimal($trn->amount) }}" class="form-control" required>
                                                    @foreach ($accounts as $account)
                                                        @if ($account->is_crypto)
                                                            <small id="crypto-amount-limits"></small>
                                                        @endif
                                                    @endforeach
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
                                                    <input required type="text" autocomplete="off" class="form-control"
                                                        value="{{ \Carbon\Carbon::parse($trn->transaction_date)->format('d/m/Y') }}"
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
                                                    <label class="form-label" for="transaction_time">Time (HH:MM)</label>
                                                    <input type="text"
                                                        value="{{ \Carbon\Carbon::parse($trn->transaction_date)->format('H:i') }}"
                                                        required autocomplete="off" class="form-control"
                                                        name="transaction_time" id="transaction_time" placeholder="time">
                                                </div>
                                            </div>
                                        </div>

                                        <label class="form-label mt-4">Type: </label>
                                        <div class="custom-control custom-radio custom-control-inline mb-3">
                                            <input type="radio" @if ($trn->transaction_type == 'Credit') checked @endif
                                                class="custom-control-input" id="credit" value="Credit"
                                                name="transaction_type" required>
                                            <label class="custom-control-label" for="credit">Credit</label>
                                        </div>
                                        <div class="custom-control custom-radio custom-control-inline mb-3">
                                            <input type="radio" @if ($trn->transaction_type == 'Debit') checked @endif
                                                class="custom-control-input" id="debit" value="Debit"
                                                name="transaction_type" required>
                                            <label class="custom-control-label" for="debit">Debit</label>
                                        </div>

                                        {{-- <div class="row">
                                            <div class="col-md-8 mt-4">
                                                <div class="form-group">

                                                    <div class="row">
                                                        <div class="col-md-12">
                                                            <label class="form-label"
                                                                for="supporting_documents">Supporting
                                                                Documents</label>
                                                        </div>
                                                    </div>
                                                    @if ($trn->requestedTrn->docs)
                                                        <div class="row">
                                                            @foreach ($trn->requestedTrn->docs as $doc)
                                                                <div class="col-md-1">
                                                                    <img src="{{ $doc->url }}"
                                                                        style="width: 32px; height:32px"
                                                                        class="mr-2 border border-primary profile-image rounded-circle"
                                                                        alt="Supporting Document" />
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @endif

                                                    <div class="row">
                                                        <div class="col-md-9">
                                                            <input type="file" required class="form-control"
                                                                onchange="handleChangeDocument(this.value, this)"
                                                                name="supporting_documents[]" id="document_0"
                                                                value="">
                                                        </div>
                                                        <div class="col-md-1">
                                                            <button class="btn btn-primary btn-add-document d-none"
                                                                onclick="handleAddDocument(this)" type="button"><i
                                                                    class="fal fa-plus"></i></button>
                                                        </div>
                                                        <div class="col-md-1 ml-2">
                                                            <button class="btn btn-danger btn-remove-document d-none"
                                                                onclick="handleRemoveDocument('document_0', false, this)"
                                                                type="button"><i class="fal fa-times"></i></button>
                                                        </div>
                                                    </div>
                                                    @error('supporting_documents')
                                                        <span class="invalid-feedback"
                                                            role="alert"><strong>{{ $message }}</strong></span>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div> --}}

                                        <div class="row mt-4">
                                            <div class="col-md-12">
                                                @include(
                                                    'finance-management.transactions.partials.update-supporting-documents',
                                                    ['status' => $trn->status]
                                                )
                                            </div>
                                        </div>

                                    </div>
                                    <div class="col-md-6" style="border-left: 1px solid lightgray;">
                                        <div class="form-group">
                                            <label class="form-label" for="name">Remarks</label>
                                            <textarea maxlength="92" required class="form-control" name="remarks" id="remarks" cols="30" rows="2"
                                                placeholder="Please provide relvant remarks for the transaction.">{{ $trn->remarks }}</textarea>
                                            <small>maximum 92 characters allowed.</small>

                                            @include('bitpace.transaction-log')

                                        </div>
                                        <div class="form-group mt-4">
                                            <label class="form-label" for="name">Transaction Notes</label>
                                            @include(
                                                'finance-management.transactions.partials.transaction-notes',
                                                [
                                                    'transaction_request_id' => $transactionRequest->id,
                                                ]
                                            )
                                        </div>
                                    </div>
                                </div>


                                {{-- @if (count($docs))
                                    <div class="row">
                                        <div class="col-md-12">
                                            <label class="form-label mt-4">Supporting Documents: </label>
                                            @foreach ($docs as $doc)
                                                <a href="{{ $doc->url }}" class="btn btn-sm btn-outline-secondary"
                                                    target="_blank"><i class="fal fa-file-alt"></i> View</a>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div> --}}


                                <div class="row">
                                    <div class="col-md-12">
                                        @include('crypto-management.crypto-transactions.partials.fireblocks-monitoring-panel', ['trn' => $trn, 'fireblocksMonitoring' => $fireblocksMonitoring ?? null])
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <hr>
                                            @if ($trn->crypto_system == '')
                                                <button type="submit" id="submit-button"
                                                    class="btn btn-primary">Submit</button>
                                            @endif
                                            @can('approve transaction')
                                                @if (!in_array($trn->status, ['Rejected', 'Approved']))
                                                    <button onclick="approve(); return false;"
                                                        class="btn btn-success">Approve</button>
                                                    <button onclick="reject(); return false;"
                                                        class="btn btn-danger">Reject</button>
                                                @endif
                                            @endcan
                                        </div>
                                    </div>
                                </div>
                        </form>

                        <form id="save-note" method="POST"
                            action="{{ url('/finance-management/transactions/save-note') }}">
                            @csrf
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
                    <form action="{{ url('/crypto-management/crypto-transactions/reject') }}" method="post">
                        @csrf
                        <input type="hidden" name="transaction_id" id="transaction_id" value="{{ $trn->id }}">
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

    @includeIf('liquidity::partials.order-card')

    <form method="POST" name="approve-transaction" id="approve-transaction"
        action="{{ url('/crypto-management/crypto-transactions/approve') }}">
        @csrf
        <input type="hidden" name="transaction_id" id="transaction_id" value="{{ $trn->id }}">
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
                '<div class="row mt-2"> <div class="col-md-9"> <input type="file" required class="form-control" onchange="handleChangeDocument(this.value, this)" name="supporting_documents[]" value=""> </div> <div class="col-md-1"> <button class="btn btn-primary btn-add-document d-none" onclick="handleAddDocument(this)" type="button"><i class="fal fa-plus"></i></button> </div> <div class="col-md-1 ml-2"> <button class="btn btn-danger btn-remove-document d-none" onclick="handleRemoveDocument(null, true, this)" type="button"><i class="fal fa-times"></i></button> </div> </div>'
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

        function reject() {
            $("#reject-modal").modal('show');
        }

        function getAccountBalance(account_id) {
            $.post("{{ url('/finance-management/get-balance') }}", {
                    "account_id": account_id
                },
                function(data, status) {
                    $("#account_balance").html(data.formatted_balance);
                    const trnStatus = '@php echo $trn->status @endphp'

                    if (!parseFloat(data.balance) > 0 && trnStatus != 'Approved') {
                        $("#debit").attr("disabled", true);
                        $("#debit").prop('checked', false);
                    } else {
                        $("#debit").attr("disabled", false);
                    }
                    let max = ''

                    if (data.max_currency_limit != -1) {
                        max = `, max: ${Intl.NumberFormat('en-US').format(data.max_currency_limit)}`
                        $("#amount").attr({
                            "max": data.max_currency_limit
                        });
                    }

                    $("#crypto-amount-limits").html(
                        `<b>Limits: </b>(min: ${Intl.NumberFormat('en-US').format(data.min_currency_limit)+max})`
                    )
                });
        }

        function approve(id) {

            Swal.fire({
                title: "Are you sure?",
                text: "Transaction will be approved.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Yes, Approve it!"
            }).then(function(result) {
                if (result.value) {
                    document.getElementById('approve-transaction').submit()
                }
            });
        }

        $(document).ready(function() {

            // get account balance
            getAccountBalance({{ $trn->account_id }})

            $('.select2').select2();

            // minimum setup
            $('#transaction_date').datepicker({
                format: 'dd/mm/yyyy',
                todayHighlight: true,
                orientation: "bottom left",
                templates: controls,
                // endDate: "{{ \Carbon\Carbon::today()->format('d/m/Y') }}"

            });

            var crypto_system = "{{ $trn->crypto_system }}";
            console.log(crypto_system);
            if (crypto_system != '')
                $('#account_id, #amount, #transaction_date, #transaction_time, #credit, #debit, #remarks').attr(
                    'disabled', true);

            // $('#document_0').attr('disabled', false);

        })
    </script>
    @include('crypto-management.crypto-transactions.partials.fireblocks-monitoring-scripts')
@stop
