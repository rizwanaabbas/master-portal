@extends('layouts.cp')
@section('title', 'New Withdraw Crypto Request - Crypto Management')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Crypto Management</span>
        </h1>
        @include('crypto-management.crypto-transactions.view.account-balances')
        <a href="{{ url('/crypto-management/crypto-transactions') }}" class="btn btn-primary mr-3"><i
                class="fal fa-arrow-alt-left"></i> Go Back </a>
    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false"
        data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                New Withdraw Crypto Request
            </h2>
        </div>

        <form method="post" action="{{ url('/crypto-management/crypto-transactions/bitpace/withdraw-save') }}"
            enctype="multipart/form-data" id="withdraw-crypto-form">
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
                                        <label class="form-label" for="account_id">From Crypto Account</label>
                                        <select onchange="getAccountBalance(this.value, 'account_balance');"
                                            name="account_id" autofocus id="account_id" value=""
                                            data-select2-id="account_id" class="form-control select2 w-100" required>
                                            <option value="">Choose Crypto account</option>
                                            @foreach ($cryptoAccounts as $cryptoAccount)
                                                @if ($cryptoAccount->id == old('account_id'))
                                                    <option value="{{ $cryptoAccount->id }}" selected>
                                                        {{ $cryptoAccount->title . ' - ' . $cryptoAccount->account_number }}
                                                    </option>
                                                @else
                                                    <option value="{{ $cryptoAccount->id }}">{{ $cryptoAccount->title . ' - ' . $cryptoAccount->account_number }}
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
                                <div class="col-md-8">
                                    <div class="form-group">
                                        <label class="form-label" id="crypto-amount-label" for="amount">Crypto Amount to
                                            Withdraw</label>
                                        <input type="number" verifyAmount(this.value); step="any" id="amount"
                                            name="amount" min="1" value="{{ old('amount') }}" class="form-control"
                                            required>
                                        <small id="crypto-amount-limits"></small>
                                        @error('amount')
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
                                            placeholder="Please provide relvant remarks for the transaction.">{{ old('remarks') }}</textarea>
                                        <small>maximum 92 characters allowed.</small>
                                    </div>
                                </div>
                                
                            </div>
                            <div class="row mt-4">
                                    @include('client-portal.partials.transactions.new.supporting-documents')
                                </div>
                        </div>
                        <div class="col-md-6" style="border-left: 1px solid lightgray;">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="form-label" for="to_wallet_address">To Wallet
                                            Address</label>
                                        <input type="text" class="form-control" autocomplete="off"
                                            name="to_wallet_address" id="to_wallet_address"
                                            placeholder="Please provide the Wallet Address for Crypto Deposit."
                                            value="{{ old('to_wallet_address') }}" required />
                                    </div>
                                </div>
                                
                            </div>
                            <div class="row  mt-4" id="destination-tag-section" style="display:none">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="form-label" for="destination_tag">Destination Tag</label>
                                        <input type="text" class="form-control" autocomplete="off"
                                            name="destination_tag" id="destination_tag"
                                            placeholder="Please provide the Wallet Destination Tag."
                                            value="{{ old('destination_tag') }}"  />
                                    </div>
                                </div>
                               
                            </div>
                            <div class="row  mt-4">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="form-label" for="network">Network</label>
                                        <select 
                                            name="network" autofocus id="network" 
                                            data-select2-id="network" class="form-control select2 w-100" required>
                                            <option value="">Select Network</option>
                                        </select>
                                       
                                    </div>
                                </div>
                            </div>

                            <!-- Beneficiary Details Section -->
                            <div class="row mt-4">
                                <div class="col-md-12">
                                    <h5 class="fw-500 mb-3" style="color: #886ab5;">
                                        <i class="fal fa-user-circle"></i> Beneficiary Information
                                    </h5>
                                </div>
                            </div>
                            
                            <!-- Sending to Myself Checkbox -->
                            <div class="row mt-2">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input" id="is_sending_to_myself" name="is_sending_to_myself" value="1" {{ old('is_sending_to_myself') ? 'checked' : '' }} onchange="toggleSendingToMyself()">
                                            <label class="custom-control-label" for="is_sending_to_myself">
                                                <strong><i class="fal fa-check-circle"></i> Sending to myself</strong>
                                            </label>
                                            <small class="text-muted d-block">Check this box if the destination wallet is owned and controlled by your entity. The system will automatically set the Beneficiary Type to BUSINESS and populate the Beneficiary Legal Name with your registered Merchant Name.</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Beneficiary Type -->
                            <div class="row mt-3" id="beneficiary_type_parent">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="form-label" for="beneficiary_type">Beneficiary Type <span class="text-danger">*</span></label>
                                        <select name="beneficiary_type" id="beneficiary_type" class="form-control select2 w-100" required onchange="toggleBeneficiaryType()">
                                            <option value="">Select Beneficiary Type</option>
                                            <option value="INDIVIDUAL" {{ old('beneficiary_type') == 'INDIVIDUAL' ? 'selected' : '' }}>Individual</option>
                                            <option value="BUSINESS" {{ old('beneficiary_type') == 'BUSINESS' ? 'selected' : '' }}>Business</option>
                                        </select>
                                        @error('beneficiary_type')
                                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            
                            <!-- INDIVIDUAL Fields -->
                            <div id="individual_fields" style="display: none;">
                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label" for="beneficiary_first_name">Beneficiary First Name <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="beneficiary_first_name" id="beneficiary_first_name"
                                                value="{{ old('beneficiary_first_name') }}" />
                                            @error('beneficiary_first_name')
                                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label" for="beneficiary_last_name">Beneficiary Last Name <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="beneficiary_last_name" id="beneficiary_last_name"
                                                value="{{ old('beneficiary_last_name') }}" />
                                            @error('beneficiary_last_name')
                                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                                <!-- <div class="row mt-3">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label" for="dob">Date of Birth</label>
                                            <input type="date" class="form-control" name="dob" id="dob"
                                                value="{{ old('dob') }}" placeholder="YYYY-MM-DD" />
                                            <small class="text-muted">Required for certain jurisdictions</small>
                                            @error('dob')
                                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                            @enderror
                                        </div>
                                    </div>
                                </div> -->
                            </div>
                            
                            <!-- BUSINESS Fields -->
                            <div id="business_fields" style="display: none;">
                                <div class="row mt-3" id="beneficiary_legal_name_parent">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label class="form-label" for="beneficiary_legal_name">Beneficiary Legal Name <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="beneficiary_legal_name" id="beneficiary_legal_name"
                                                value="{{ old('beneficiary_legal_name') }}" />
                                            <small class="text-muted">Full registered legal name of the corporate entity</small>
                                            @error('beneficiary_legal_name')
                                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                                 <!--  <div class="row mt-3">
                                  <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label" for="registration_number">Registration Number</label>
                                            <input type="text" class="form-control" name="registration_number" id="registration_number"
                                                value="{{ old('registration_number') }}" />
                                            <small class="text-muted">Official business registration number</small>
                                            @error('registration_number')
                                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                            @enderror
                                        </div>
                                    </div> -->
                                    <!-- <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label" for="lei_code">LEI Code</label>
                                            <input type="text" class="form-control" name="lei_code" id="lei_code" maxlength="20"
                                                value="{{ old('lei_code') }}" placeholder="20-character alphanumeric" />
                                            <small class="text-muted">Legal Entity Identifier (ISO 17442)</small>
                                            @error('lei_code')
                                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                            @enderror
                                        </div>
                                    </div>
                                </div> -->
                            </div>
                            
                            <!-- Country Code (Common) -->
                            <!-- <div class="row mt-3">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label" for="country_code">Country Code</label>
                                        <input type="text" class="form-control" name="country_code" id="country_code" maxlength="2"
                                            value="{{ old('country_code') }}" placeholder="e.g., AT, SG, ZA" style="text-transform: uppercase;" />
                                        <small class="text-muted">ISO 3166-1 alpha-2 country code (2 characters)</small>
                                        @error('country_code')
                                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                            </div> -->
                            
                            <!-- Wallet Type -->
                            <div class="row mt-3">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="form-label" for="wallet_type">Wallet Type <span class="text-danger">*</span></label>
                                        <select name="wallet_type" id="wallet_type" class="form-control select2 w-100" required onchange="toggleWalletType()">
                                            <option value="">Select Wallet Type</option>
                                            <option value="CUSTODIAL" {{ old('wallet_type') == 'CUSTODIAL' ? 'selected' : '' }}>Custodial (Exchange/Third-party)</option>
                                            <option value="SELF_HOSTED" {{ old('wallet_type') == 'SELF_HOSTED' ? 'selected' : '' }}>Self-Hosted (Ledger, MetaMask, etc.)</option>
                                        </select>
                                        <small class="text-muted">CUSTODIAL: Wallet managed by third-party (e.g., Binance, Coinbase). SELF-HOSTED: Wallet managed directly by user (e.g., Ledger, MetaMask, Trust Wallet).</small>
                                        @error('wallet_type')
                                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            
                            <!-- VASP Selection (Only for Custodial) -->
                            <div class="row mt-3" id="vasp_section" style="display: none;">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="form-label" for="beneficiary_vasp_did">Beneficiary VASP (Exchange) <span class="text-danger">*</span></label>
                                        <select name="beneficiary_vasp_did" id="beneficiary_vasp_did" class="form-control select2 w-100">
                                            <option value="">Select VASP</option>
            
                                            @foreach($vasp_list as $vasp)
                                                <option value="{{ $vasp->name  }}" {{ old('beneficiary_vasp_did') == $vasp->did ? 'selected' : '' }}>{{ $vasp->name }}</option>
                                            @endforeach
                                           
                                        </select>
                                        @error('beneficiary_vasp_did')
                                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                    
                                    <!-- Not on list checkbox -->
                                    <div class="form-group mt-2">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input" id="is_not_on_vasp_list" name="is_not_on_vasp_list" value="1" {{ old('is_not_on_vasp_list') ? 'checked' : '' }} onchange="toggleCustomVasp()">
                                            <label class="custom-control-label" for="is_not_on_vasp_list">Not on list</label>
                                        </div>
                                    </div>
                                    
                                    <!-- Custom VASP Name (shown when not on list) -->
                                    <div class="form-group mt-2" id="custom_vasp_section" style="display: none;">
                                        <label class="form-label" for="beneficiary_vasp_name">Enter VASP Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="beneficiary_vasp_name" id="beneficiary_vasp_name"
                                            value="{{ old('beneficiary_vasp_name') }}" />
                                        @error('beneficiary_vasp_name')
                                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Ownership Declaration (Only for Self-Hosted) -->
                            <div class="row mt-3" id="ownership_section" style="display: none;">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input" id="is_ownership_declaration" name="is_ownership_declaration" value="1" {{ old('is_ownership_declaration') ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="is_ownership_declaration">
                                                <strong>I declare ownership of this wallet</strong> <span class="text-danger">*</span>
                                            </label>
                                            <small class="text-muted d-block">You must check this box to certify the validity and ownership of the private wallet address.</small>
                                        </div>
                                        @error('is_ownership_declaration')
                                            <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
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
                                <button type="submit" id="submit-button" style="display:none;" class="btn btn-primary">Submit Request</button>
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
        let timer;
        $('#amount, #to_wallet_address').on('keyup', function() {
            window.clearTimeout(timer);
            // here is the closures javascript magic happens.
            const value = $(this).val();
            timer = setTimeout(() => {
                if(value === $(this).val() && $(this).val()!== ''){
                    showSubmit(); 
                }
            }, 500);
        });
        function showSubmit() {
            //console.log('Wallet Address: ' + $('#to_wallet_address').val());
            //console.log('Account ID: '+$('#account_id').val());
            //console.log('Balance: '+$('#balance').val());
            if($('#to_wallet_address').val() != '' && $('#account_id').val() != '' && $("#balance").val() > 0) {
                $('#submit-button').show();
            }
        }
        //preloader for every ajax request start and stop should move to common
        $(document)  
        .ajaxStart(function () {  
            $('#loading-spinner').show();  
        })  
        .ajaxStop(function () {  
            $('#loading-spinner').hide();  
        });
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

        // Toggle Sending to Myself checkbox
        function toggleSendingToMyself() {
            const isSendingToMyself = $('#is_sending_to_myself').is(':checked');
            const merchantName = $('#merchant_name').val();

            if (isSendingToMyself) {
                // Set Beneficiary Type to BUSINESS
                $('#beneficiary_type').val('BUSINESS').trigger('change');
                $('#beneficiary_type').attr('disabled', true);
                $('#beneficiary_type').attr('readonly', true).css('pointer-events', 'none');
                
                $('#beneficiary_type_parent').attr('style', 'display:none !important');
                $('#beneficiary_legal_name_parent').attr('style', 'display:none !important');

                // Set Beneficiary Legal Name to Merchant Name
                $('#beneficiary_legal_name').val(merchantName);
                $('#beneficiary_legal_name').attr('readonly', true);
            } else {
                // Reset fields
                $('#beneficiary_type').val('').trigger('change');
                $('#beneficiary_type').attr('disabled', false);
                $('#beneficiary_type').removeAttr('readonly').css('pointer-events', '');

                $('#beneficiary_type_parent').attr('style', 'display:block !important');
                $('#beneficiary_legal_name_parent').attr('style', 'display:block !important');

                $('#beneficiary_legal_name').val('');
                $('#beneficiary_legal_name').removeAttr('readonly');
            }
        }

        // Toggle Beneficiary Type fields
        function toggleBeneficiaryType() {
            const beneficiaryType = $('#beneficiary_type').val();
            
            if (beneficiaryType === 'INDIVIDUAL') {
                $('#individual_fields').show();
                $('#business_fields').hide();
                $('#beneficiary_first_name, #beneficiary_last_name').attr('required', true);
                $('#beneficiary_legal_name, #registration_number, #lei_code').removeAttr('required');
            } else if (beneficiaryType === 'BUSINESS') {
                $('#individual_fields').hide();
                $('#business_fields').show();
                $('#beneficiary_legal_name').attr('required', true);
                $('#beneficiary_first_name, #beneficiary_last_name').removeAttr('required');
            } else {
                $('#individual_fields').hide();
                $('#business_fields').hide();
            }
        }

        // Toggle Wallet Type fields
        function toggleWalletType() {
            const walletType = $('#wallet_type').val();
            if (walletType === 'CUSTODIAL') {
                $('#vasp_section').show();
                $('#ownership_section').hide();
                $('#is_ownership_declaration').removeAttr('required');
                
                // Check if not on list is checked
                if ($('#is_not_on_vasp_list').is(':checked')) {
                    $('#custom_vasp_section').show();
                    $('#beneficiary_vasp_name').attr('required', true);
                    $('#beneficiary_vasp_did').removeAttr('required');
                } else {
                    $('#custom_vasp_section').hide();
                    $('#beneficiary_vasp_name').removeAttr('required');
                    $('#beneficiary_vasp_did').attr('required', true);
                }
            } else if (walletType === 'SELF_HOSTED') {
                $('#vasp_section').hide();
                $('#ownership_section').show();
                $('#is_ownership_declaration').attr('required', true);
                $('#beneficiary_vasp_did, #beneficiary_vasp_name').removeAttr('required');
            } else {
                $('#vasp_section').hide();
                $('#ownership_section').hide();
                $('#is_ownership_declaration').removeAttr('required');
                $('#beneficiary_vasp_did, #beneficiary_vasp_name').removeAttr('required');
            }
        }

        // Toggle Custom VASP field
        function toggleCustomVasp() {
            if ($('#is_not_on_vasp_list').is(':checked')) {
                $('#custom_vasp_section').show();
                $('#beneficiary_vasp_name').attr('required', true);
                $('#beneficiary_vasp_did').removeAttr('required');
            } else {
                $('#custom_vasp_section').hide();
                $('#beneficiary_vasp_name').removeAttr('required');
                $('#beneficiary_vasp_did').attr('required', true);
            }
        }

        $("#withdraw-crypto-form").on('submit', function(e) {
            e.preventDefault();
             // Validate beneficiary fields
            const beneficiaryType = $('#beneficiary_type').val();
            const walletType = $('#wallet_type').val();
            const isSendingToMyself = $('#is_sending_to_myself').is(':checked');
            
            if (!beneficiaryType) {
                Swal.fire('Error', 'Please select Beneficiary Type.', 'error');
                return false;
            }
            
            if (beneficiaryType === 'INDIVIDUAL') {
                if (!$('#beneficiary_first_name').val() || !$('#beneficiary_last_name').val()) {
                    Swal.fire('Error', 'Please enter Beneficiary First and Last Name.', 'error');
                    return false;
                }
            }
            
            if (beneficiaryType === 'BUSINESS' && !isSendingToMyself) {
                if (!$('#beneficiary_legal_name').val()) {
                    Swal.fire('Error', 'Please enter Beneficiary Legal Name.', 'error');
                    return false;
                }
            }
            
            if (!walletType) {
                Swal.fire('Error', 'Please select Wallet Type.', 'error');
                return false;
            }
            
            if (walletType === 'CUSTODIAL') {
                if ($('#is_not_on_vasp_list').is(':checked')) {
                    if (!$('#beneficiary_vasp_name').val()) {
                        Swal.fire('Error', 'Please enter VASP Name.', 'error');
                        return false;
                    }
                } else {
                    if (!$('#beneficiary_vasp_did').val()) {
                        Swal.fire('Error', 'Please select Beneficiary VASP.', 'error');
                        return false;
                    }
                }
            }
            
            if (walletType === 'SELF_HOSTED' && !$('#is_ownership_declaration').is(':checked')) {
                Swal.fire('Error', 'You must declare ownership of this wallet for Self-Hosted wallets.', 'error');
                return false;
            }
            let walletAddress = document.getElementById('to_wallet_address').value

            Swal.fire({
                title: "Are you sure?",
                text: `Please double check the Wallet Address (${walletAddress}). Once the amount is deposited to a wrong Wallet, can never be reversed.`,
                type: "danger",
                icon: "warning",
                dangerMode: true,
                showCancelButton: true,
                confirmButtonText: "Yes, Submit!"
            }).then(function(result) {
                if (result.value) {
                    document.getElementById('withdraw-crypto-form').submit()
                }
            });
        })

        function getAccountBalance(account_id, balance_field_id) {
            if (account_id) {
                getNetworkList(account_id);
                $.post("{{ url('/finance-management/get-balance') }}", {
                        "account_id": account_id
                    },
                    function(data, status) {
                        //XRP, EOS and XLM orders
                        if(data.currency_short == 'XRP' || data.currency_short == 'EOS' || data.currency_short == 'XLM') {
                            $("#destination-tag-section").show();
                            $("#destination_tag").attr('required',true);
                        } else {
                            $("#destination_tag").removeAttr('required');
                            $("#destination-tag-section").hide();
                        }
                        $(`#${balance_field_id}`).html(data.formatted_balance);
                        if (!parseFloat(data.balance) > 0) {
                            $("#debit").attr("disabled", true);
                            $("#debit").prop('checked', false);
                            $("#balance").val();
                        } else {
                            $("#debit").attr("disabled", false);
                            $("#balance").val(parseFloat(data.balance));
                        }

                        if (balance_field_id == 'account_balance') {
                            $(`#crypto-amount-label`).html(`${data.currency_short} Amount to Withdraw`)
                            $("#amount").attr({
                                "min": data.min_currency_limit
                            });

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
                        }

                        $("#debit").prop('checked', false);
                        $("#credit").prop('checked', false);
                        $("#amount").val('');
                    });
            } else {
                clearCryptoHints(balance_field_id)
            }
        }

        function clearCryptoHints(balance_field_id) {
            $(`#${balance_field_id}`).html('--')
            $(`#crypto-amount-label`).html(`Crypto Amount to Withdraw`)
            $("#crypto-amount-limits").html('')
        }
        function getNetworkList(account_id= '', currency_code ='') {
           // resetAddressData();
            
            $.post("{{ url('/crypto-management/crypto-transactions/bitpace/get-currency-networks') }}", {
                    "currency_code": currency_code,
                    "account_id": account_id
                },
                function(data, status) {
                
                    var networks = jQuery.parseJSON(data);
                    $("#network").empty();
                    $("#network").append(new Option('Select Network', ''));
                    $.each(networks, function(key,val) {             
                        $("#network").append(new Option(val, val)).prop('selected');     
                    });
                }
            );
        }
       $(document).ready(function(){
            setTimeout(function() {
                $('#account_id').trigger('change');
               //console.log($('#account_id').val());
               //getAccountBalance($('#account_id').val(), 'account_balance')
               // Initialize all toggles
                toggleSendingToMyself();
                toggleBeneficiaryType();
                toggleWalletType();
            },2000);
       });
    </script>
@stop

@stop
