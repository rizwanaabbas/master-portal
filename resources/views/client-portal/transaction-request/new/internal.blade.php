@extends('layouts.cp')
@section('title', 'New Internal Transfer Request - Transactions Requests')

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
                New Internal Transfer Request
            </h2>
        </div>

        <form method="post" action="{{ url('/client/transaction-requests/internal/store') }}"
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
                                        <label class="form-label" for="debit_account_id">From Account</label>
                                        <select onchange="getAccountBalance('credit', this.value);" name="debit_account_id"
                                            autofocus id="debit_account_id" value=""
                                            data-select2-id="debit_account_id" class="form-control select2 w-100" required>
                                            <option value="">choose client account</option>
                                            @foreach ($accounts as $account)
                                                @if ($account->id == old('debit_account_id'))
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

                            <div id="bank-account-card" class="mt-4"></div>

                            

                            <div class="row mt-4">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="form-label" for="debit_remarks">Transfer Remarks</label>
                                        <textarea maxlength="92" class="form-control" required name="debit_remarks" id="debit_remarks" cols="30"
                                            rows="2" placeholder="Please provide relvant remarks for the transaction.">{{ old('debit_remarks') }}</textarea>
                                        <small>maximum 92 characters allowed.</small>
                                    </div>
                                </div>
                            </div>

                            <div class="row mt-4">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label" for="amount">Transfer Amount</label>
                                        <input onblur="verifyAmount(this.value);" type="number" step="any"
                                            id="amount" name="amount" min="0" value="{{ old('amount') }}"
                                            class="form-control" required>
                                        @error('amount')
                                            <span class="invalid-feedback"
                                                role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-8 mt-4">
                                    <div class="form-group">
                                        <label class="form-label" for="document_0">Supporting Documents</label>
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
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label class="form-label"
                                                        for="credit_account_id">{{ $ownCompany ? $ownCompany->short_name : '' }}
                                                        Account</label>

                                                    <select onchange="getAccountHolderName(this.value);" name="account_number"
                                                        autofocus id="account_number" value=""
                                                        data-select2-id="account_number" class="form-control select2 w-100" required>
                                                        <option value="">choose client account</option>
                                                        {{-- @foreach ($accounts as $account)
                                                            @if ($account->id == old('account_number'))
                                                                <option value="{{ $account->account_number }}" selected>{{ $account->title . ' - ' . $account->account_number }}
                                                                </option>
                                                            @else
                                                                <option value="{{ $account->account_number }}">{{ $account->title . ' - ' . $account->account_number }}</option>
                                                            @endif
                                                        @endforeach --}}
                                                    </select>

                                                    {{-- <input onchange="getAccountHolderName(this.value);" type="text"
                                                        step="any" id="account_number" name="account_number"
                                                        min="1" value="{{ old('account_number') }}"
                                                        class="form-control" required>
                                                    @error('account_number')
                                                        <span class="invalid-feedback"
                                                            role="alert"><strong>{{ $message }}</strong></span>
                                                    @enderror --}}
                                                </div>
                                            </div>
                                            {{-- <div class="col-md-8">
                                                <div class="form-group account_holder_name">
                                                    <label class="form-label" for="account_holder_name">Account Holder
                                                        Name</label>
                                                    <div class="form-control" id="account_holder_name" readonly>--</div>
                                                </div>
                                            </div> --}}
                                            <div class="col-md-12">
                                                <div id="reciever-account-info-card" class="mt-4"></div>
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

        $("#debit_account_id").change(function() {
            var creditAccountId = $(this).val();
            if (creditAccountId) {
                updateDebitAccounts(creditAccountId);
            } else {
                $("#debit_account_id").empty();
                $("#debit_account_id").append('<option value="">choose client account</option>');
            }
        });

        function updateDebitAccounts(creditAccountId) {
            var accounts = @json($accounts);
            var creditAccount = accounts.find(account => account.id == creditAccountId);
            console.log("creditAccount", creditAccount);
            var debitSelect = $("#account_number");
            debitSelect.empty();
            debitSelect.append('<option value="">choose client account</option>');
            
            if (creditAccount) {
            var filteredAccounts = accounts.filter(account => account.title.split(' (')[1] == creditAccount.title.split(' (')[1] && account.id != creditAccountId);
            console.log("filteredAccounts", filteredAccounts);
            filteredAccounts.forEach(function(account) {
                debitSelect.append('<option value="' + account.account_number + '">' + account.title + ' - ' + account.account_number + '</option>');
            });
            }
        }

        function getAccountHolderName(account_number) {
            $.post("{{ url('/client/transaction-requests/account-holder-name') }}", {
                "account_number": account_number
            },
            function(data, status) {
                console.log(data);
                if(data.iban && data.bic) {
                   formated =  createBankBeneficiaryCard(data);
                   document.getElementById('reciever-account-info-card').innerHTML = formated;
                   document.getElementById('reciever-account-info-card').classList.remove('d-none');
                   document.getElementById('reciever-account-info-card').classList.add('d-block');
                }
            });
        }

        function getAccountBalance(type, account_id) {
            $.post("{{ url('/finance-management/get-balance') }}", {
                    "account_id": account_id
                },
                function(data, status) {
                    getIbanInfo(account_id);
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

                    $("#amount").val('');
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
                    <h5 class="mb-0">Receiver's Details</h5>
                   
                </div>
                <div class="card-body" id="bank-account-content">
                    <div class="row">
                       
                        <div class="col-md-12 mb-3">
                            <strong>Account holder name:</strong> <span id="iban">${data.title}</span>  
                        </div>
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

    </script>
@stop
