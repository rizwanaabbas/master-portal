@extends('layouts.cp')
@section('title', 'New Transfer Request - Transactions Requests')

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
                New Transfer Request
            </h2>
        </div>

        <form method="post" action="{{ url('/client/transaction-requests/transfer/store') }}"
            enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="balance" id="balance" value="0">
            <div class="panel-container show">
                <div class="panel-content">
                    @include('common.error-message')
                    <div class="row">
                        <div class="col-md-6">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="form-group">
                                        <label class="form-label" for="account_id">From Account</label>
                                        <select onchange="getAccountBalance(this.value);" name="account_id" autofocus
                                            id="account_id" value="" data-select2-id="account_id"
                                            class="form-control select2 w-100" required>
                                            <option value="">choose client account</option>
                                            @foreach ($accounts as $account)
                                                @if ($account->id == old('account_id'))
                                                    <option value="{{ $account->id }}" selected>{{ $account->title . ' - ' . $account->account_number }}
                                                    </option>
                                                @else
                                                    <option value="{{ $account->id }}">{{ $account->title . ' - ' . $account->account_number }}</option>
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
                            <div id="bank-account-card" class="mt-4"></div>

                            <div class="row mt-4">
                                <div class="col-md-8">
                                    <div class="form-group">
                                        <label class="form-label" for="amount">Transfer Amount</label>
                                        <input type="number" step="any" id="amount" name="amount" min="0"
                                            value="{{ old('amount') }}" class="form-control" required>
                                        @error('amount')
                                            <span class="invalid-feedback"
                                                role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="form-label" for="transaction_date">Transfer Date</label>
                                        <input type="hidden" id="transaction_date">
                                        <input type="text" required autocomplete="off" class="form-control"
                                            name="transaction_date" id="transaction_date" placeholder="Select date"
                                            value="{{ date('d/m/Y') }}" readonly required>
                                        @error('transaction_date')
                                            <span class="invalid-feedback"
                                                role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-4">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="form-label" for="name">Remarks</label>
                                        <textarea maxlength="92" required class="form-control" name="remarks" id="remarks" cols="30" rows="2"
                                            placeholder="Please provide relvant remarks for the transaction."></textarea>
                                        <small>maximum 92 characters allowed.</small>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-4">
                                <div class="form-group">
                                    <div class="col-md-12">
                                        <label class="form-label" for="supporting_documents">Supporting Documents</label>
                                        <p>(If you are transferring money to your own account, please provide latest bank
                                            statement).</p>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-9">
                                            <input type="file" required class="form-control"
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
                        <div class="col-md-6" style="border-left: 1px solid lightgray;">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="form-group">
                                        <label class="form-label" for="beneficiary_id">Beneficiary</label>
                                        <select name="beneficiary_id" onchange="getBeneficiaryDetails(this)" autofocus id="beneficiary_id" value=""
                                            data-select2-id="beneficiary_id" class="form-control select2 w-100" required >
                                            <option value="">choose beneficiary</option>
                                            @foreach ($beneficiaries as $ben)
                                                @if ($ben->id == old('beneficiary_id'))
                                                    <option value="{{ $ben->id }}" selected>{{ $ben->name . ' - ' . $ben->account_number }}
                                                    </option>
                                                @else
                                                    <option value="{{ $ben->id }}">{{ $ben->name . ' - ' . $ben->account_number }}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                        @error('beneficiary_id')
                                            <span class="invalid-feedback"
                                                role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <a href="{{ url('/client/transaction-requests/beneficiaries/new') }}"
                                        class="btn btn-primary mt-4"><i class="fal fa-plus-circle"></i> Add New </a>
                                </div>
                               
                            </div>
                            <div id="bank-beneficiary-card" class="mt-4"></div>
                            <hr />
                            <div class="row mt-4">
                                <div class="col-md-12">
                                    <legend>
                                        <h3>Corresponding Bank Details</h3>
                                    </legend>
                                </div>
                            </div>
                            <div class="row mt-4">
                                <div class="col-md-8">
                                    <div class="form-group">
                                        <label class="form-label" for="corresponding_bank_name">Corresponding Bank
                                            Name</label>
                                        <input type="text" id="corresponding_bank_name" name="corresponding_bank_name"
                                            value="{{ old('corresponding_bank_name') }}" class="form-control">
                                        @error('corresponding_bank_name')
                                            <span class="invalid-feedback"
                                                role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="form-label" for="corresponding_bank_swift_code">Corresponding Swift
                                            Code</label>
                                        <input type="text" id="corresponding_bank_swift_code"
                                            name="corresponding_bank_swift_code"
                                            value="{{ old('corresponding_bank_swift_code') }}" class="form-control">
                                        @error('corresponding_bank_swift_code')
                                            <span class="invalid-feedback"
                                                role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-4">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="form-label" for="corresponding_bank_address">Corresponding Bank
                                            Address</label>
                                        <textarea class="form-control" name="corresponding_bank_address" id="corresponding_bank_address" cols="30"
                                            rows="2" placeholder="Please provide the bank address.">{{ old('corresponding_bank_address') }}</textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-4">
                                <div class="col-md-12">
                                    <h3><i class="fal fa-exclamation-triangle"></i> Warning!</h3>
                                    <p>Please double check your beneficiary details, you <strong>won't</strong> be able to
                                        change it once the transaction is created in the system.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <hr>
                                <button type="submit" class="btn btn-primary">Submit</button>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </form>
    </div>

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
                '<div class="row mt-2"> <div class="col-md-9"> <input type="file" required class="form-control" onchange="handleChangeDocument(this.value, this)" name="supporting_documents[]" value=""> </div> <div class="col-md-1"> <button class="btn btn-primary btn-add-document d-none" onclick="handleAddDocument(this)" type="button"><i class="fal fa-plus"></i></button> </div> <div class="col-md-1 ml-2"> <button class="btn btn-danger btn-remove-document" onclick="handleRemoveDocument(null, true, this)" type="button"><i class="fal fa-times"></i></button> </div> </div>'
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
                    getIbanInfo(account_id);
                    $("#account_balance").html(data.balance);
                    if (!parseFloat(data.balance) > 0) {
                        $("#debit").attr("disabled", true);
                        $("#debit").prop('checked', false);
                        $("#balance").val();
                    } else {
                        $("#debit").attr("disabled", false);
                        $("#balance").val(parseFloat(data.balance));
                    }

                    $("#debit").prop('checked', false);
                    $("#credit").prop('checked', false);
                    $("#amount").val('');
                });
        }
        function createBankAccountCard(data) {
            return `
            <div class="card col-md-12 shadow-sm">
                <div class="card-header  d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Bank Account Details</h5>
                   
                </div>
                <div class="card-body" id="bank-account-content">
                    <div class="row">
                       
                        <div class="col-md-6 mb-3">
                            <strong>IBAN:</strong> <span id="iban">${data.iban}</span>  
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>Account Number:</strong> <span>${data.account_number}</span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>BIC:</strong> <span>${data.bic}</span>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <strong>Bank Name:</strong> <span>${data.bank_name}</span>
                        </div>
                        <div class="col-md-6">
                            <strong>Bank Address:</strong>
                            <p class="mb-0">${data.bank_address}</p>
                        </div>
                    </div>
                </div>
            </div>`;
        }

        function createBankBeneficiaryCard(data) {
            return `
            <div class="card col-md-12 shadow-sm">
                <div class="card-header  d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Bank Beneficiary Details</h5>
                   
                </div>
                <div class="card-body" id="bank-account-content">
                    <div class="row">
                       
                        <div class="col-md-12 mb-3">
                            <strong>IBAN:</strong> <span id="iban">${data.iban}</span>  
                        </div>
                        
                        <div class="col-md-12 mb-3">
                            <strong>BIC:</strong> <span>${data.bic}</span>
                        </div>
                    </div>
                </div>
            </div>`;
        }

        function getIbanInfo(account_id) {
            $.get("{{ url('/client/transaction-requests/get-iban-info') }}/"+account_id, function(response, status){
               var account = response.data;
               console.log(account);
               var formated = '';
               if(account)
                    formated = createBankAccountCard(account);

                document.getElementById('bank-account-card').innerHTML = formated;
            });
        }

        function getBeneficiaryDetails(th) {
            var beneficiary_id = th.value;
            console.log(beneficiary_id);
            $.get("{{ url('/client/transaction-requests/get-baneficiary-info') }}/"+beneficiary_id, function(response, status){
               var account = response.data;
               console.log(account);
               var formated = '';
               if(account)
                    formated = createBankBeneficiaryCard(account);

                document.getElementById('bank-beneficiary-card').innerHTML = formated;
            });
        }

        $(document).ready(function() {

            $('.select2').select2();

            // minimum setup
            $('#transaction_date').datepicker({
                format: 'dd/mm/yyyy',
                todayHighlight: true,
                orientation: "bottom left",
                templates: controls,
                startDate: "{{ \Carbon\Carbon::today()->format('d/m/Y') }}"
            });
            $('#transaction_date').attr('disabled', true);
        })
    </script>
@stop

@stop
