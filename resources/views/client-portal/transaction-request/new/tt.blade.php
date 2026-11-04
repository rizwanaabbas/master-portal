@extends('layouts.cp')
@section('title', 'New Deposit Request - Transactions Requests')

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
                New Deposit Request
            </h2>
        </div>

        <form method="post" action="{{ url('/client/transaction-requests/deposit/store') }}" enctype="multipart/form-data"
            id="deposit_form">
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
                                        <label class="form-label" for="account_id">Account</label>
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

                            <div class="row mt-4">
                                <div class="col-md-8">
                                    <div class="form-group">
                                        <label class="form-label" for="amount">Deposit Amount</label>
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
                                        <label class="form-label" for="transaction_date">Deposit Date</label>
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
                                <div class="col-md-8 mt-4">
                                    <div class="form-group">
                                        <label class="form-label" for="document_0">Supporting Documents</label>
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
                        </div>
                        <div class="col-md-6" style="border-left: 1px solid lightgray;">
                            <div class="form-group">
                                <label class="form-label" for="name">Remarks</label>
                                <textarea maxlength="92" required class="form-control" name="remarks" id="remarks" cols="30" rows="2"
                                    placeholder="Please provide relvant remarks for the transaction."></textarea>
                                <small>maximum 92 characters allowed.</small>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <h3><i class="fal fa-exclamation-triangle"></i> Warning!</h3>
                                    <p>Please double check your deposit details, you <strong>won't</strong> be able to
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
                    <div class="row">
                            <div  class="col-md-12 mt-4" id="bank-account-card"></div>
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
                    console.log(data);
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
            <div class="card col-md-6 shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Bank Account Details</h5>
                   
                </div>
                <div class="card-body" id="bank-account-content">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <strong>Account Name:</strong> <span>${data.bank_account_holder_name}</span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>IBAN:</strong> <span id="iban">${data.iban}</span>  
                          
                                <i class="fal fa-copy text-primary" style="font-size:18px"  onclick="copyIban()" title="Copy IBAN"></i>
                           
                            
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>Account Number:</strong> <span>${data.account_number}</span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>BIC:</strong> <span>${data.bic}</span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>Currency:</strong> <span>${data.currency}</span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>Bank Name:</strong> <span>${data.bank_name}</span>
                        </div>
                        <div class="col-md-12">
                            <strong>Bank Address:</strong>
                            <p class="mb-0">${data.bank_address}</p>
                        </div>
                    </div>
                </div>
            </div>`;
        }

        // Function to copy only the IBAN
        function copyIban() {
            const iban = document.getElementById('iban').innerText;
            navigator.clipboard.writeText(iban).then(() => {
                alert("IBAN copied to clipboard!");
            }).catch(err => {
                alert("Failed to copy IBAN. Please try again.");
                console.error(err);
            });
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

        $(document).ready(function() {

            $('.select2').select2();

            // minimum setup
            $('#transaction_date').datepicker({
                format: 'dd/mm/yyyy',
                todayHighlight: true,
                orientation: "bottom left",
                templates: controls,
                endDate: "{{ \Carbon\Carbon::today()->format('d/m/Y') }}"
            });
            $('#transaction_date').attr('disabled', true);
        })
    </script>
@stop

@stop
