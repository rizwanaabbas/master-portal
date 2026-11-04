{{-- Travel Rule PII fields — shared by create/edit. Expects $vasp_list and optionally $beneficiary --}}
@php
    $b = $beneficiary ?? null;
    $isSendingSelf = old('is_sending_to_myself', optional($b)->is_sending_to_myself);
    $beneficiaryType = old('beneficiary_type', optional($b)->beneficiary_type);
    $walletType = old('wallet_type', optional($b)->wallet_type);
    $isNotOnVasp = old('is_not_on_vasp_list', optional($b)->is_not_on_vasp_list);
@endphp

<div class="travel-rule-wrapper p-3 mt-3 border rounded bg-light">
    <!-- Header Section -->
    <div class="d-flex align-items-center mb-3">
        <i class="fal fa-shield-check text-primary fs-lg mr-2"></i>
        <h6 class="mb-0 font-weight-bold text-dark">Travel Rule / Compliance Information</h6>
    </div>

    <!-- "Sending to Myself" Banner/Card -->
    <div class="card border-primary-100 bg-white mb-3 shadow-none">
        <div class="card-body p-3">
            <div class="custom-control custom-checkbox">
                <input type="checkbox" class="custom-control-input" id="is_sending_to_myself" name="is_sending_to_myself" value="1"
                    {{ $isSendingSelf ? 'checked' : '' }} onchange="toggleSendingToMyself()">
                <label class="custom-control-label font-weight-bold cursor-pointer text-dark" for="is_sending_to_myself">
                    Sending to myself
                </label>
                <small class="text-muted d-block mt-1">
                    Destination wallet belongs to this client entity. Automatically locks Beneficiary Type to <strong>BUSINESS</strong> and applies your company name.
                </small>
            </div>
        </div>
    </div>

    <!-- Beneficiary Entity Type & Country -->
    <div class="row" id="beneficiary_type_parent">
        <div class="col-md-6 mb-3">
            <div class="form-group">
                <label class="form-label" for="beneficiary_type">Beneficiary Type <span class="text-danger">*</span></label>
                <select name="beneficiary_type" id="beneficiary_type" class="form-control select2" required onchange="toggleBeneficiaryType()">
                    <option value="">Select Beneficiary Type</option>
                    <option value="INDIVIDUAL" {{ $beneficiaryType == 'INDIVIDUAL' ? 'selected' : '' }}>Individual</option>
                    <option value="BUSINESS" {{ $beneficiaryType == 'BUSINESS' ? 'selected' : '' }}>Business / Corporate Entity</option>
                </select>
            </div>
        </div>

        <div class="col-md-6 mb-3">
            <div class="form-group">
                <label class="form-label" for="country_code">Beneficiary Country Code</label>
                <input type="text" class="form-control text-uppercase" name="country_code" id="country_code" maxlength="2"
                    value="{{ old('country_code', optional($b)->country_code) }}" placeholder="e.g. US, GB, SG" />
                <small class="text-muted">ISO 2-letter country code.</small>
            </div>
        </div>
    </div>

    <!-- INDIVIDUAL DETAILS SECTION -->
    <div id="individual_fields" style="display: {{ $beneficiaryType == 'INDIVIDUAL' ? 'block' : 'none' }};">
        <div class="card bg-white border mb-3">
            <div class="card-body p-3">
                <div class="row">
                    <div class="col-md-4 mb-2">
                        <div class="form-group">
                            <label class="form-label" for="beneficiary_first_name">First Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="beneficiary_first_name" id="beneficiary_first_name"
                                placeholder="Enter first name"
                                value="{{ old('beneficiary_first_name', optional($b)->beneficiary_first_name) }}" />
                        </div>
                    </div>
                    <div class="col-md-4 mb-2">
                        <div class="form-group">
                            <label class="form-label" for="beneficiary_last_name">Last Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="beneficiary_last_name" id="beneficiary_last_name"
                                placeholder="Enter last name"
                                value="{{ old('beneficiary_last_name', optional($b)->beneficiary_last_name) }}" />
                        </div>
                    </div>
                    <div class="col-md-4 mb-2">
                        <div class="form-group">
                            <label class="form-label" for="dob">Date of Birth</label>
                            <input type="text" class="form-control" name="dob" id="dob"
                                value="{{ old('dob', optional(optional($b)->dob)->format('Y-m-d')) }}" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- BUSINESS DETAILS SECTION -->
    <div id="business_fields" style="display: {{ $beneficiaryType == 'BUSINESS' ? 'block' : 'none' }};">
        <div class="card bg-white border mb-3">
            <div class="card-body p-3">
                <div class="form-group mb-0" id="beneficiary_legal_name_parent">
                    <label class="form-label" for="beneficiary_legal_name">Company Legal Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="beneficiary_legal_name" id="beneficiary_legal_name"
                        placeholder="Registered legal corporate name"
                        value="{{ old('beneficiary_legal_name', optional($b)->beneficiary_legal_name) }}" />
                    <small class="text-muted d-block mt-1">Must match registered company documents.</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Wallet Type -->
    <div class="row">
        <div class="col-md-12 mb-3">
            <div class="form-group">
                <label class="form-label" for="wallet_type">Destination Wallet Custody <span class="text-danger">*</span></label>
                <select name="wallet_type" id="wallet_type" class="form-control select2" required onchange="toggleWalletType()">
                    <option value="">Select Wallet Type</option>
                    <option value="CUSTODIAL" {{ $walletType == 'CUSTODIAL' ? 'selected' : '' }}>Custodial (Exchange or 3rd-party custodian)</option>
                    <option value="SELF_HOSTED" {{ $walletType == 'SELF_HOSTED' ? 'selected' : '' }}>Self-Hosted (Private wallet / Cold storage)</option>
                </select>
            </div>
        </div>
    </div>

    <!-- VASP / Exchange Details (Custodial) -->
    <div id="vasp_section" style="display: {{ $walletType == 'CUSTODIAL' ? 'block' : 'none' }};">
        <div class="card bg-white border mb-3">
            <div class="card-body p-3">
                <div class="form-group mb-3">
                    <label class="form-label" for="beneficiary_vasp_did">Beneficiary VASP / Exchange <span class="text-danger">*</span></label>
                    <select name="beneficiary_vasp_did" id="beneficiary_vasp_did" class="form-control select2 w-100">
                        <option value="">Choose known VASP / Exchange</option>
                        @foreach ($vasp_list as $vasp)
                            <option value="{{ $vasp->name }}" {{ old('beneficiary_vasp_did', optional($b)->beneficiary_vasp_did) == $vasp->name ? 'selected' : '' }}>
                                {{ $vasp->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="custom-control custom-checkbox mb-2">
                    <input type="checkbox" class="custom-control-input" id="is_not_on_vasp_list" name="is_not_on_vasp_list" value="1"
                        {{ $isNotOnVasp ? 'checked' : '' }} onchange="toggleCustomVasp()">
                    <label class="custom-control-label cursor-pointer text-muted" for="is_not_on_vasp_list">Exchange / VASP is not on this list</label>
                </div>

                <div class="form-group mt-3 pt-2 border-top" id="custom_vasp_section" style="display: {{ $isNotOnVasp ? 'block' : 'none' }};">
                    <label class="form-label" for="beneficiary_vasp_name">Enter Custom VASP Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="beneficiary_vasp_name" id="beneficiary_vasp_name"
                        placeholder="e.g. Local Exchange Co"
                        value="{{ old('beneficiary_vasp_name', optional($b)->beneficiary_vasp_name) }}" />
                </div>
            </div>
        </div>
    </div>

    <!-- Self-Hosted Declaration -->
    <div id="ownership_section" style="display: {{ $walletType == 'SELF_HOSTED' ? 'block' : 'none' }};">
        <div class="card border-warning bg-white mb-2">
            <div class="card-body p-3">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="is_ownership_declaration" name="is_ownership_declaration" value="1"
                        {{ old('is_ownership_declaration', optional($b)->is_ownership_declaration) ? 'checked' : '' }}>
                    <label class="custom-control-label font-weight-bold text-dark cursor-pointer" for="is_ownership_declaration">
                        I declare ownership / control over this self-hosted wallet <span class="text-danger">*</span>
                    </label>
                    <small class="text-muted d-block mt-1">
                        By checking this box, you certify that the designated private key or hardware wallet is verified and owned by the beneficiary.
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>