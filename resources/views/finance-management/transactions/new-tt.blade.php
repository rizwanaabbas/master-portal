@extends('layouts.cp')
@section('title', 'New TT Transaction - Finance Management')

@section('content')

    @php
        $isOpenPaydEnabled = in_array('OpenPayd', $enabledModules);
        $isLorumEnabled = in_array('Lorum', $enabledModules);
        $isVirtualAccount = $isOpenPaydEnabled || $isLorumEnabled;
    @endphp

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Finance Management</span>
        </h1>
        <a href="{{ url('/finance-management/transactions') }}" class="btn btn-primary mr-3"><i
                class="fal fa-arrow-alt-left"></i> Go Back </a>
    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false"
        data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                New TT Transaction
            </h2>
        </div>

        <form method="post" action="{{ url('/finance-management/transactions/store/tt') }}" enctype="multipart/form-data">
            <input type="hidden" name="temp_balance" id="temp_balance" />
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

                            {{-- credit account details card --}}
                            {{-- <div id="credit-account-card" class=""></div> --}}

                            <label class="form-label mt-4">Type: </label>
                            <div class="custom-control custom-radio custom-control-inline">
                                <input onclick="onChangeTransactionType('credit')" onblur="verifyAmount(this.value);"
                                    type="radio" class="custom-control-input" id="credit" value="Credit"
                                    name="transaction_type" required>
                                <label class="custom-control-label" for="credit">Credit</label>
                            </div>
                            <div class="custom-control custom-radio custom-control-inline">
                                <input onclick="onChangeTransactionType('debit')" onblur="verifyAmount(this.value);"
                                    type="radio" class="custom-control-input" id="debit" value="Debit"
                                    name="transaction_type" required>
                                <label class="custom-control-label" for="debit">Debit</label>
                            </div>

                            @if ($isVirtualAccount)
                                <div class="row mt-3" id="transaction_method_select" style="display: none;">
                                    <div class="col-md-8">
                                        <div class="form-group">
                                            <label class="form-label" for="account_id">Transaction Method</label>
                                            <select name="transaction_method_id" id="transaction_method_id"
                                                data-select2-id="transaction_method_id" class="form-control select2"
                                                required onchange="onChangeTransactionMethod(this.value);">
                                                <option value="">Select Transaction Method</option>
                                                <option value="SWIFT_PAYMENT">
                                                    Swift Payment
                                                </option>
                                                <option value="INSTANT_PAYMENT">
                                                    Instant Payment
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <div id="trn-inputs">
                                <div class="row mt-4">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="form-label" for="amount">Transaction Amount</label>
                                            <input type="number" step="any" id="amount" name="amount"
                                                min="0" value="{{ old('amount') }}" class="form-control" required>
                                            @error('amount')
                                                <span class="invalid-feedback"
                                                    role="alert"><strong>{{ $message }}</strong></span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="custom-control custom-switch mt-5" id="is_zeroize_div">
                                            <input type="checkbox" onchange="handleOnChangeIsZeroize(this)"
                                                class="custom-control-input" id="is_zeroize" value="1"
                                                name="is_zeroize">
                                            <label class="custom-control-label" for="is_zeroize">Zeroize</label>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label class="form-label" for="transaction_date">Transaction Date</label>
                                            <input type="text" required autocomplete="off" class="form-control"
                                                name="transaction_date" id="transaction_date" placeholder="Select date"
                                                required>
                                            @error('transaction_date')
                                                <span class="invalid-feedback"
                                                    role="alert"><strong>{{ $message }}</strong></span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label class="form-label" for="transaction_time">Time (HH:MM)</label>
                                            <input type="text" value="" required autocomplete="off"
                                                class="form-control" name="transaction_time" id="transaction_time"
                                                placeholder="time">
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group mt-4">
                                    <label class="form-label" for="name">Remarks</label>
                                    <textarea maxlength="92" required class="form-control" name="remarks" id="remarks" cols="30" rows="2"
                                        placeholder="Please provide relvant remarks for the transaction."></textarea>
                                    <small>maximum 92 characters allowed.</small>
                                </div>

                                @include('finance-management.transactions.partials.new-supporting-documents')
                            </div>

                        </div>

                        <div id="trn-inputs" class="col-md-6" style="border-left: 1px solid lightgray;">

                            {{-- credit account details card --}}
                            <div id="credit-account-card" class=""></div>

                            {{-- SENDER DETAILS SECTION     --}}
                            <div id="credit-sender-details" class="mt-4" style="display: none;">
                                <h5 class="mb-3 text-primary border-bottom pb-2">Sender Details</h5>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <div class="form-group">
                                            <label class="form-label" for="sender_name">Sender Name <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" id="sender_name" name="sender_name"
                                                class="form-control credit-req" placeholder="Enter Sender Name">
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <div class="form-group">
                                            <label class="form-label" for="sender_address">Sender Address <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" id="sender_address" name="sender_address"
                                                class="form-control credit-req" placeholder="Enter Sender Address">
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <div class="form-group">
                                            <label class="form-label" for="sender_city">Sender City <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" id="sender_city" name="sender_city"
                                                class="form-control credit-req" placeholder="Enter Sender City">
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <div class="form-group">
                                            <label class="form-label" for="sender_country">Sender Country <span
                                                    class="text-danger">*</span></label>
                                            <select id="sender_country" name="sender_country"
                                                class="form-control select2 credit-req" style="width:100%;">
                                                <option value="">Choose Country</option>
                                                @foreach ($countries as $country)
                                                    <option value="{{ $country->id }}">{{ $country->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <h5 class="mb-3 mt-3 text-primary border-bottom pb-2">Sending Bank Details</h5>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <div class="form-group">
                                            <label class="form-label" for="sending_bank_name">Bank Name <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" id="sending_bank_name" name="sending_bank_name"
                                                class="form-control credit-req" placeholder="Enter Bank Name">
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <div class="form-group">
                                            <label class="form-label" for="sending_bank_address">Bank Address <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" id="sending_bank_address" name="sending_bank_address"
                                                class="form-control credit-req" placeholder="Enter Bank Address">
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <div class="form-group">
                                            <label class="form-label" for="sending_bank_city">Bank City <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" id="sending_bank_city" name="sending_bank_city"
                                                class="form-control credit-req" placeholder="Enter Bank City">
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <div class="form-group">
                                            <label class="form-label" for="sending_bank_country">Bank Country <span
                                                    class="text-danger">*</span></label>
                                            <select id="sending_bank_country" name="sending_bank_country"
                                                class="form-control select2 credit-req" style="width:100%;">
                                                <option value="">Choose Country</option>
                                                @foreach ($countries as $country)
                                                    <option value="{{ $country->id }}">{{ $country->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-12 mb-3">
                                        <div class="form-group">
                                            <label class="form-label" for="sending_bank_swift">SWIFT Code <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" id="sending_bank_swift" name="sending_bank_swift"
                                                class="form-control credit-req" placeholder="Enter SWIFT Code">
                                        </div>
                                    </div>
                                </div>

                                <h5 class="mb-3 mt-3 text-primary border-bottom pb-2">Intermediary Bank (Optional)</h5>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <div class="form-group">
                                            <label class="form-label" for="intermediary_bank_name">Intermediary Bank
                                                Name</label>
                                            <input type="text" id="intermediary_bank_name"
                                                name="intermediary_bank_name" class="form-control"
                                                placeholder="Enter Intermediary Bank Name">
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <div class="form-group">
                                            <label class="form-label" for="intermediary_bank_swift">Intermediary SWIFT
                                                Code</label>
                                            <input type="text" id="intermediary_bank_swift"
                                                name="intermediary_bank_swift" class="form-control"
                                                placeholder="Enter Intermediary SWIFT">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            {{-- =============================================== --}}

                            {{-- debit section card --}}
                            <div id="debit-section-card" class="mt-4">

                                <div id="beneficiary-section" class="mt-4" style="display: none;">

                                    <div class="row">
                                        <div class="col-md-8">
                                            <div class="form-group">
                                                <label class="form-label" for="op_beneficiary_id">Beneficiary</label>
                                                <select name="op_beneficiary_id" onchange="getBeneficiaryDetails(this)"
                                                    autofocus id="op_beneficiary_id" value=""
                                                    data-select2-id="op_beneficiary_id"
                                                    class="form-control select2 w-100">
                                                    <option value="">choose beneficiary</option>

                                                </select>
                                                @error('op_beneficiary_id')
                                                    <span class="invalid-feedback"
                                                        role="alert"><strong>{{ $message }}</strong></span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <button type="button" onclick="viewAddNewBeneficiaryModal()"
                                                class="btn btn-primary mt-4">
                                                <i class="fal fa-plus-circle"></i> Add New
                                            </button>
                                        </div>

                                    </div>
                                    <div id="bank-beneficiary-card" class="mt-4"></div>

                                </div>

                                {{-- beneficiary details section --}}
                                <div id="beneficiary-details-section" class="mt-4"></div>
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


    {{-- Add beneficiary modal --}}

    @if ($isVirtualAccount)
        <div class="modal fade" id="add-new-sepa-beneficiary" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title ">Create Virtual Bank Beneficiary</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true"><i class="fal fa-times"></i></span>
                        </button>
                    </div>
                    <div class="modal-body">
                        @include('finance-management.create-new-beneficiary')
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

        $(document).ajaxStart(function() {
            $('#loading-spinner').show();
        });

        $(document).ajaxStop(function() {
            $('#loading-spinner').hide();
        });

        var selectedAccount = null;
        var selectedMethod = null;

        const el = id => document.getElementById(id);
        const toggle = (id, show) => el(id).style.display = show ? 'block' : 'none';
        const clearHTML = (...ids) => ids.forEach(id => el(id).innerHTML = '');

        const safeJSON = (value) => {
            try {
                return JSON.parse(value);
            } catch {
                return null;
            }
        };

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

            $('#add-new-sepa-beneficiary').on('shown.bs.modal', function() {
                $('#beneficiary_type').select2({
                    dropdownParent: $(
                        '#add-new-sepa-beneficiary') // Ensure dropdown is inside modal
                });
                $('#currency_short_code').select2({
                    dropdownParent: $(
                        '#add-new-sepa-beneficiary') // Ensure dropdown is inside modal
                });
                $('#beneficiary_country').select2({
                    dropdownParent: $(
                        '#add-new-sepa-beneficiary') // Ensure dropdown is inside modal
                });
                $('#bank_account_country').select2({
                    dropdownParent: $(
                        '#add-new-sepa-beneficiary') // Ensure dropdown is inside modal
                });
            });
        });

        function verifyAmount(type) {
            var balance = parseFloat($("#balance").val());
            var amount = parseFloat($("#amount").val());
            const is_zeroize = document.getElementById('is_zeroize')
            const is_zeroize_div = document.getElementById('is_zeroize_div')

            if (type == "Debit") {
                is_zeroize.disabled = false
                is_zeroize_div.disabled = false

                if (balance < parseFloat(amount) && !is_zeroize.checked) {
                    alert("Insufficient balance available, please use lower value than available balance (" + balance +
                        ")");
                    $("#amount").val(0);
                    return false;
                } else {
                    return true;
                }
            } else {
                is_zeroize.checked = false
                is_zeroize.disabled = true
                is_zeroize_div.disabled = true

                return true;
            }
        }

        function onChangeTransactionMethod(method) {
            selectedMethod = method;
            credit_type = el('credit').checked;
            const hasVirtualAccount = selectedAccount?.virtual_bank_account_id;

            if (method === "INSTANT_PAYMENT") {
                getIbanInfo(selectedAccount.id);
                if (hasVirtualAccount && credit_type == false) {
                    el('op_beneficiary_id').required = true;
                    getClientBeneficiries();
                }
            } else {
                toggle('beneficiary-section', false);
                clearHTML('credit-account-card', 'beneficiary-details-section');
                removeBeneficiarySelect();
            }
        }

        function onChangeTransactionType(type) {
            // const submitBtn = document.querySelector('button[type="submit"]');
            const debitSection = el('debit-section-card');
            const creditCard = el('credit-account-card');
            const trnInputs = el('trn-inputs');
            const beneficiarySection = el('beneficiary-section');
            const beneficiarySelect = el('op_beneficiary_id');
            const beneficiaryDetails = el('beneficiary-details-section');
            const senderDetailsSection = el('credit-sender-details');

            const hasVirtualAccount = selectedAccount?.virtual_bank_account_id ;
            const isInstant = selectedMethod === 'INSTANT_PAYMENT';

            // Defaults
            // submitBtn.style.display = 'block';
            debitSection.style.display = 'block';
            trnInputs.style.display = 'block';

            if (type === 'credit' && !hasVirtualAccount) {
                senderDetailsSection.style.display = 'block';
                $('.credit-req').prop('required', true);
            } else {
                senderDetailsSection.style.display = 'none';
                $('.credit-req').prop('required', false);
            }

            if (!hasVirtualAccount) return;

            if (type === 'credit') {
                beneficiarySection.style.display = 'none';
                beneficiarySelect.required = false;
                beneficiarySelect.value = '';
                beneficiaryDetails.innerHTML = '';

                clearHTML('beneficiary-details-section');
                removeBeneficiarySelect();
                return;
            }

            // Debit + virtual account
            if (type === 'debit' && isInstant) {
                getIbanInfo(selectedAccount.id);
                creditCard.style.display = 'block';
                beneficiarySection.style.display = 'block';
                getClientBeneficiries();
            }
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
                        amount.value = 0
                        document.getElementById('credit').disabled = true
                        document.getElementById('debit').checked = true
                        amount.disabled = true
                    } else {
                        checkbox.checked = false
                    }
                });
            } else {
                document.getElementById('amount').disabled = false
                document.getElementById('credit').disabled = false
            }
        }

        function getAccountBalance(account_id) {
            document.getElementById('is_zeroize').checked = false
            $.post("{{ url('/finance-management/get-balance') }}", {
                    "account_id": account_id
                },
                function(data, status) {
                    $("#account_balance").html(data.balance);

                    document.getElementById('temp_balance').value = data.raw_balance
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

                    // Reset Sender Details UI on account change
                    el('credit-sender-details').style.display = 'none';
                    $('.credit-req').prop('required', false);

                    $("#amount").val(0);
                    $("#amount").prop('disabled', false);
                    $("#credit").prop('disabled', false);
                }
            );
        }

        function createBankAccountCard(data) {
            const account_info = data.account_info ? safeJSON(data.account_info) : null;
            let BBAN = '-';
            let ALIAS = '-';
            let ABA = '-';
            let BIC = data.bic || '-';
            let routingEntries = null;

            const fields_map = {
                "SORT_CODE": "Sort Code",
                "ROUTING_CODE": "Routing Code"
            };

            // =============================
            // ZENUS HANDLING
            // =============================
            if (data.provider_name?.toLowerCase() === 'zenus' && Array.isArray(account_info)) {
                account_info.forEach(acc => {
                    const type = acc?.accountNumber?.type;
                    const value = acc?.accountNumber?.value;

                    if (type === 'BBAN') BBAN = value;
                    if (type === 'ALIAS') ALIAS = value;

                    const finType = acc?.financialInstitutionId?.type;
                    const finValue = acc?.financialInstitutionId?.value;

                    if (finType === 'ABA_CODE') ABA = finValue;
                    if (finType === 'BIC') BIC = finValue;
                });
            }

            // =============================
            // OPENPAYD HANDLING
            // =============================
            if (data.provider_name?.toLowerCase() === 'openpayd' && data.bank_routing_codes) {
                try {
                    // double parse fix
                    let parsed = safeJSON(data.bank_routing_codes);
                    if (typeof parsed === 'string') {
                        parsed = safeJSON(parsed);
                    }

                    routingEntries = Array.isArray(parsed) ? parsed[0] : parsed;

                } catch (e) {
                    console.error("Routing parse error", e);
                }
            }

            // =============================
            // UI HELPER
            // =============================
            const field = (label, value) => value && value !== 'N/A' && value !== '-' ?
                `<div class="col-md-6 mb-3"><strong>${label}:</strong> <span>${value}</span></div>` : '';

            return `
                <div class="card col-md-12 shadow-sm mt-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Bank Account Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">

                            ${field('Account Holder Name', data.bank_account_holder_name)}

                            ${field('Bank Name', data.bank_name)}

                            ${field('Account Number', data.account_number)}

                            ${field('IBAN', data.iban)}

                            ${field('BIC / SWIFT', BIC)}

                            ${
                                data.provider_name?.toLowerCase() === 'zenus'
                                    ? `
                                            ${field('BBAN', BBAN)}
                                            ${field('Alias (VRN)', ALIAS)}
                                            ${field('ABA Code', ABA)}
                                        `
                                    : ''
                            }

                            ${
                                data.provider_name?.toLowerCase() === 'openpayd' && routingEntries
                                    ? field(fields_map[routingEntries.routingCodeKey], routingEntries.routingCodeValue)
                                    : ''
                            }

                            ${field('Currency', data.currency)}

                            ${data.bank_address && data.bank_address !== 'N/A'
                                ? `<div class="col-md-6 mb-3">
                                            <strong>Bank Address:</strong>
                                            <p class="mb-0">${data.bank_address}</p>
                                    </div>`
                                : ''}

                        </div>
                    </div>
                </div>
            `;
        }

        function getIbanInfo(account_id) {
            $.get("{{ url('/finance-management/transactions/get-iban-info') }}/" + account_id, function(response, status) {
                const account = response.data;
                let formated = '';
                if (account) formated = createBankAccountCard(account);
                el('credit-account-card').innerHTML = formated;
            });
        }

        function getBeneficiaryDetails(th) {
            const beneficiary_id = th.value;
            toggle('beneficiary-details-section', false);

            if (!beneficiary_id) return;

            $.get("{{ url('/finance-management/transactions/get-baneficiary-info') }}/" + beneficiary_id, function(
                response, status) {
                const account = response.data;
                let formated = '';

                if (account) {
                    formated = createBankAccountCard(account);
                    toggle('beneficiary-details-section', true);
                }

                el('beneficiary-details-section').innerHTML = formated;
            });
        }

        function getClientBeneficiries() {
            const {
                client_id,
                account_currency
            } = selectedAccount;

            toggle('beneficiary-section', true);
            $.get(`/finance-management/transactions/get-client-beneficiaries/${client_id}/${account_currency}`,
                ({
                    data
                }) => {
                    if (!data?.length) return removeBeneficiarySelect();

                    $('#op_beneficiary_id')
                        .html('<option value="">Choose beneficiary</option>')
                        .append(data.map(b => `<option value="${b.id}">${b.name}</option>`));
                });
        }

        $('#account_id').change(function() {
            const accountId = this.value;
            const accounts = Object.values(@json($accounts->toArray()));
            selectedAccount = accounts.find(a => a.id == accountId);
            const currencies = @json($currencies);
            const currency = currencies.find(c => c.id == selectedAccount.account_currency);
            const provider = currency.virtual_iban_provider ?? '';
            const isLorumEnabled = @json($isLorumEnabled);
            const isOpenPaydEnabled = @json($isOpenPaydEnabled);
            const isVirtualAccount = @json($isVirtualAccount);

            if (provider.toLowerCase() === 'lorum' && !isLorumEnabled) return;
            if (provider.toLowerCase() === 'openpayd' && !isOpenPaydEnabled) return;

            const hasVirtual = selectedAccount?.virtual_bank_account_id;

            $("#debit").prop('checked', false);
            $("#credit").prop('checked', false);

            // Reset Sender Details Display 
            el('credit-sender-details').style.display = 'none';
            $('.credit-req').prop('required', false);

            if (isVirtualAccount) {
                toggle('transaction_method_select', hasVirtual);
                toggle('beneficiary-section', false);
                $('#transaction_method_id').prop('required', false).val('').change();
                el('client_id').value = selectedAccount.client_id;
            }

            clearHTML('credit-account-card', 'beneficiary-details-section');
            $('#op_beneficiary_id').prop('required', false).val('').change();

            removeBeneficiarySelect();

            $('#currency_short_code').html(
                `<option value="${currency.id}">${currency.short} - ${currency.name}</option> <option value="">Choose Currency</option>`
            );
        });

        function removeBeneficiarySelect() {
            $("#op_beneficiary_id").empty();
            $("#op_beneficiary_id").append('<option value="">No Beneficiaries Found</option>');
        }

        function viewAddNewBeneficiaryModal() {
            if (!selectedAccount || !selectedAccount.virtual_bank_account_id && selectedMethod !== "INSTANT_PAYMENT") {
                return;
            }

            $("#add-new-sepa-beneficiary").modal('show');

            const currencies = @json($currencies);
            const selectedCurrency = currencies.find(c => c.id == selectedAccount.account_currency);

            // if (selectedCurrency && selectedCurrency.virtual_iban_provider === 'lorum') {
            if (selectedCurrency.short === "USD") {
                // el('sort_code_dev').style.display = 'none';
                el('routing_number_dev').style.display = 'block';
                el('account-iban').style.display = 'none';
            } else if (selectedCurrency.short === "AED") {
                el('account-iban').style.display = 'block';
                // el('sort_code_dev').style.display = 'block';
                el('routing_number_dev').style.display = 'none';
            }
            // } else {
            //     el('routing_number_dev').style.display = 'none';
            // }
            $('.panel-toolbar').hide();
        }
    </script>
@stop

@stop
