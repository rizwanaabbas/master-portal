@extends('layouts.cp')
@section('title', 'Edit Internal Transfer Request - Transactions Requests')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Transaction Requests</span>
        </h1>
        <a href="{{ url('/client/transaction-requests/list') }}" class="btn btn-primary mr-3"><i
                class="fal fa-arrow-alt-left"></i> Go Back </a>
    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false"
        data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                Edit Internal Transfer Request
            </h2>
        </div>

        <form method="post" action="{{ url('/client/transaction-requests/internal/update') }}"
            enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="id" id="id" value="{{ $transaction->id }}">
            <input type="hidden" name="balance" id="balance" value="0">
            <div class="panel-container show">
                <div class="panel-content">
                    @include('common.error-message')
                    <div class="row">
                        <div class="col-md-6">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="form-group">
                                        <label class="form-label" for="debit_account_id">From Account</label>
                                        <select onchange="getAccountBalance('credit', this.value);" name="debit_account_id"
                                            autofocus id="debit_account_id" value=""
                                            data-select2-id="debit_account_id" class="form-control select2 w-100" required>
                                            <option value="">choose client account</option>
                                            @foreach ($accounts as $account)
                                                @if ($account->id == $transaction->account_id)
                                                    <option value="{{ $account->id }}" selected>{{ $account->title . ' - ' . $account->account_number }}
                                                    </option>
                                                @else
                                                    <option value="{{ $account->id }}">{{ $account->title . ' - ' . $account->account_number }}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                        @error('debit_account_id')
                                            <span class="invalid-feedback"
                                                role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="form-label" for="credit_account_balance">Account Balance</label>
                                        <div class="form-control" id="credit_account_balance" readonly>--</div>
                                    </div>
                                </div>
                            </div>

                            <div class="row mt-4">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="form-label" for="debit_remarks">Transfer Remarks</label>
                                        <textarea maxlength="92" class="form-control" required name="debit_remarks" id="debit_remarks" cols="30"
                                            rows="2" placeholder="Please provide relvant remarks for the transaction.">{{ $transaction->remarks }}</textarea>
                                        <small>maximum 92 characters allowed.</small>
                                    </div>
                                </div>
                            </div>

                            <div class="row mt-4">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label" for="amount">Transfer Amount</label>
                                        <input onblur="verifyAmount(this.value);" type="number" step="any"
                                            id="amount" name="amount" min="0" value="{{ $transaction->amount }}"
                                            class="form-control" required>
                                        @error('amount')
                                            <span class="invalid-feedback"
                                                role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-8 mt-4">
                                    <div class="form-group">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <label class="form-label" for="supporting_documents">Supporting
                                                    Documents</label>
                                            </div>
                                        </div>
                                        @if ($transaction->docs)
                                            <div class="row">
                                                @foreach ($transaction->docs as $doc)
                                                    <div class="col-md-1">
                                                        <img src="{{ $doc->url }}" style="width: 32px; height:32px"
                                                            class="mr-2 border border-primary profile-image rounded-circle"
                                                            alt="Supporting Document" />
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif

                                        <div class="row">
                                            <div class="col-md-9">
                                                <input type="file" class="form-control"
                                                    onchange="handleChangeDocument(this.value, this)"
                                                    name="supporting_documents[]" id="document_0" value="">
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
                            </div>
                        </div>
                        <div class="col-md-6" style="border-left: 1px solid lightgray;">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class=" alert alert-warning">
                                        <h3>Receiver's Details</h3>
                                        <div class="row mt-2">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="form-label"
                                                        for="debit_account_id">{{ $ownCompany ? $ownCompany->short_name : '' }}
                                                        Account Number</label>
                                                    <input onchange="getAccountHolderName(this.value);" type="text"
                                                        step="any" id="account_number" name="account_number"
                                                        min="1"
                                                        value="{{ $transaction->receiver_account_number }}"
                                                        class="form-control" required>
                                                    @error('account_number')
                                                        <span class="invalid-feedback"
                                                            role="alert"><strong>{{ $message }}</strong></span>
                                                    @enderror
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="form-label" for="account_holder_name">Account Holder
                                                        Name</label>
                                                    <div class="form-control" id="account_holder_name" readonly>--</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <h3><i class="fal fa-exclamation-triangle"></i> Warning!</h3>
                                            <p>Please verify the receiver's account number and name, you won't be able to
                                                change it once the transaction is created in the system.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <hr>
                                <button id="submit" type="submit" class="btn btn-primary">Submit</button>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </form>
    </div>
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
                '<div class="row mt-2"> <div class="col-md-9"> <input type="file" class="form-control" onchange="handleChangeDocument(this.value, this)" name="supporting_documents[]" value=""> </div> <div class="col-md-1"> <button class="btn btn-primary btn-add-document d-none" onclick="handleAddDocument(this)" type="button"><i class="fal fa-plus"></i></button> </div> <div class="col-md-1 ml-2"> <button class="btn btn-danger btn-remove-document" onclick="handleRemoveDocument(null, true, this)" type="button"><i class="fal fa-times"></i></button> </div> </div>'
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



        $(document).ready(function() {
            getAccountBalance('credit', '@php echo $transaction->account_id @endphp', false)
            getAccountHolderName('@php echo $transaction->receiver_account_number @endphp')
        })

        function verifyAmount(amount) {
            var balance = parseFloat($("#balance").val());
            if (balance < parseFloat(amount)) {
                alert("Insufficient balance available, please use lower value than available balance (" + balance + ")");
                $("#amount").val(0);
                return false;
            } else {
                return true;
            }
        }

        function getAccountHolderName(account_number) {
            $.post("{{ url('/client/transaction-requests/account-holder-name') }}", {
                    "account_number": account_number
                },
                function(data, status) {
                    $("#account_holder_name").html(data);
                });
        }

        function getAccountBalance(type, account_id, clearAmountField = true) {
            $.post("{{ url('/finance-management/get-balance') }}", {
                    "account_id": account_id
                },
                function(data, status) {
                    $("#" + type + "_account_balance").html(data.balance);
                    if (type == "debit" && !parseFloat(data.balance) > 0) {
                        $("#amount").attr("disabled", true);
                        $("#submit").attr("disabled", true);
                        $("#balance").val();

                    } else {
                        $("#balance").val(parseFloat(data.balance));
                        $("#amount").attr("disabled", false);
                        $("#submit").attr("disabled", false);
                    }

                    if (clearAmountField) {
                        $("#amount").val('');
                    }
                });
        }

        $(document).ready(function() {

            // not working
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
