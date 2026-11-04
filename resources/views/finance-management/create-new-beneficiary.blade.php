<div class="alert alert-danger" id="error-alert-box" style="display: none;">
    <i class="fal fa-exclamation-triangle"></i>
    <span id="alert-error-message"></span>
    <button class="close" onclick="closeAlert('error-alert-box')"><span aria-hidden="true">×</span></button>
</div>

<div class="alert bg-success-400 text-white" id="success-alert-box" style="display: none;">
    <i class="fal fa-check mr-2"></i>
    <span id="alert-success-message"></span>
    <button class="close" onclick="closeAlert('success-alert-box')"><span aria-hidden="true">×</span></button>
</div>


<div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false" data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false" data-panel-toolbar="false">
    <div class="panel-hdr">
        <h2>
            New Beneficiary
        </h2>
    </div>

    <div>
        @csrf
        <input type="hidden" name="balance" id="balance" value="0">
        <div class="panel-container show">
            <div class="panel-content">
                @include('common.error-message')
                <div class="row">
                    
                    <div class="col-md-8">

                        <div class="row">
                            <div class="col-md-4 ">
                                <div class="form-group">
                                    <input type="hidden" name="client_id" id="client_id" value="">

                                    <label class="form-label" for="currency_short_code">Account Currency</label>
                                    <select name="currency_short_code" autofocus id="currency_short_code" value=""
                                        data-select2-id="currency_short_code" class="form-control select2 w-100"
                                        required>
                                        <option value="">Choose Currency</option>
                                    </select>
                                    @error('currency_short_code')
                                        <span class="invalid-feedback"
                                            role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4 ">
                                <div class="form-group">
                                    <label class="form-label" for="beneficiary_country">Beneficiary Country</label>
                                    <select name="beneficiary_country" autofocus id="beneficiary_country" value=""
                                        data-select2-id="beneficiary_country" class="form-control select2 w-100"
                                        required>
                                        <option value="">Choose Country</option>
                                        @foreach ($countries as $country)
                                            <option value="{{ $country->id }}">
                                                {{ $country->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('beneficiary_country')
                                        <span class="invalid-feedback"
                                            role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4 ">
                                <div class="form-group">
                                    <label class="form-label" for="bank_account_country">Bank Account Country</label>
                                    <select name="bank_account_country" autofocus id="bank_account_country"
                                        value="" data-select2-id="bank_account_country"
                                        class="form-control select2 w-100" required>
                                        <option value="">Choose Country</option>
                                        @foreach ($countries as $country)
                                            <option value="{{ $country->id }}">
                                                {{ $country->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('bank_account_country')
                                        <span class="invalid-feedback"
                                            role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mt-4">
                                <div class="form-group">
                                    <label class="form-label" for="beneficiary_type">Beneficiary Type</label>
                                    <select name="beneficiary_type" autofocus id="beneficiary_type" value=""
                                        data-select2-id="beneficiary_type" class="form-control select2 w-100" required>
                                        <option value="">Choose beneficiary type</option>
                                        <option value="individual">Individual</option>
                                        <option value="company">Company</option>
                                    </select>
                                    @error('beneficiary_type')
                                        <span class="invalid-feedback"
                                            role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6 mt-4">
                                <div class="form-group">
                                    <label class="form-label" for="name">Name</label>
                                    <input type="text" id="name" name="name" value="{{ old('name') }}"
                                        class="form-control" required>
                                    @error('name')
                                        <span class="invalid-feedback"
                                            role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4 mt-4">
                                <div class="form-group">
                                    <label class="form-label" for="account_number">Account Number</label>
                                    <input type="number" min="1" step="any" id="account_number"
                                        name="account_number" value="{{ old('account_number') }}"
                                        class="form-control" required>
                                    @error('account_number')
                                        <span class="invalid-feedback"
                                            role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-8 mt-4" id="account-iban">
                                <div class="form-group">
                                    <label class="form-label" for="account-iban">IBAN Number</label>
                                    <input type="text" id="account-iban" name="account-iban"
                                        value="{{ old('iban') }}" class="form-control">
                                    @error('iban')
                                        <span class="invalid-feedback"
                                            role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-12 mt-4">
                                <div class="form-group">
                                    <label class="form-label" for="address">Beneficiary Address</label>
                                    <textarea required class="form-control" name="address" id="address" cols="30" rows="2"
                                        placeholder="Please provide the beneficiary address.">{{ old('address') }}</textarea>
                                </div>
                            </div>

                        </div>
                    </div>
                    


                    <div class="col-md-4" style="border-left: 1px solid lightgray;">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="form-label" for="bank_name">Bank Name</label>
                                    <input type="text" id="bank_name" name="bank_name"
                                        value="{{ old('bank_name') }}" class="form-control" required>
                                    @error('bank_name')
                                        <span class="invalid-feedback"
                                            role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label" for="bank_city">Bank City</label>
                                    <input type="text" id="bank_city" name="bank_city"
                                        value="{{ old('bank_city') }}" class="form-control">
                                    @error('bank_city')
                                        <span class="invalid-feedback"
                                            role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label" for="postal_code">Postal/Zip Code</label>
                                    <input type="text" id="postal_code" name="postal_code"
                                        value="{{ old('postal_code') }}" class="form-control">
                                    @error('postal_code')
                                        <span class="invalid-feedback"
                                            role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mt-4">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label" for="swift_code">Swift Code</label>
                                    <input type="text" id="swift_code" name="swift_code"
                                        value="{{ old('swift_code') }}" class="form-control">
                                    @error('swift_code')
                                        <span class="invalid-feedback"
                                            role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>
                            {{-- <div class="col-md-6" id="sort_code_dev">
                                <div class="form-group">
                                    <label class="form-label" for="sort_code">Sort Code</label>
                                    <input type="text" id="sort_code" name="sort_code"
                                        value="{{ old('sort_code') }}" class="form-control">
                                    @error('sort_code')
                                        <span class="invalid-feedback"
                                            role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div> --}}
                            
                            <div class="col-md-6" id="routing_number_dev">
                                <div class="form-group">
                                    <label class="form-label" for="routing_number">Routing Number</label>
                                    <input type="text" id="routing_number" name="routing_number"
                                        value="{{ old('routing_number') }}" class="form-control">
                                    @error('routing_number')
                                        <span class="invalid-feedback"
                                            role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="form-label" for="bank_address">Bank Address</label>
                                    <textarea required class="form-control" name="bank_address" id="bank_address" cols="30" rows="2"
                                        placeholder="Please provide the bank address.">{{ old('bank_address') }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <hr>
                            <button id="add-beneficiary-button" onclick="javascript:addBeneficiary();"
                                class="btn btn-primary d-flex" type="button">
                                <span id="submit-text">Submit</span>
                                <span id="spinner" style="display: none;"
                                    class="spinner-border spinner-border-sm ml-2" role="status"
                                    aria-hidden="true"></span>
                            </button>

                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
    var controls = {
        leftArrow: '<i class="fal fa-angle-left" style="font-size: 1.25rem"></i>',
        rightArrow: '<i class="fal fa-angle-right" style="font-size: 1.25rem"></i>'
    }

    const currencies = @json($currencies);

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

    function closeAlert(id) {
        console.log("id: " + id);
        document.getElementById(id).style.display = "none";
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
    })

    function addBeneficiary() {
        const spinner = $('#spinner');
        const submitBtn = $('#add-beneficiary-button');

        spinner.show();
        submitBtn.prop('disabled', true);

        // All form fields in one place
        const fields = {
            currency_short_code: '#currency_short_code',
            beneficiary_country: '#beneficiary_country',
            bank_account_country: '#bank_account_country',
            name: '#name',
            account_number: '#account_number',
            iban: '#account-iban',
            address: '#address',
            bank_name: '#bank_name',
            swift_code: '#swift_code',
            bank_address: '#bank_address',
            sort_code: '#sort_code',
            routing_number: '#routing_number',
            client_id: '#client_id',
            beneficiary_type: '#beneficiary_type',
            bank_city: '#bank_city',
            postal_code: '#postal_code'
        };

        const requiredFields = [
            'currency_short_code',
            'beneficiary_country',
            'bank_account_country',
            'name',
            // 'account_number',
            // 'iban',
            'address',
            'bank_name',
            'bank_address',
            'beneficiary_type'
        ];

        // Validate required fields
        const isValid = requiredFields.every(key => {
            const value = $(fields[key]).val();
            return value !== '' && value !== null;
        });

        if (!isValid) {
            showError('Please fill all required fields.');
            resetState();
            
            return;
        }

        // Build FormData dynamically
        const formData = new FormData();
        Object.keys(fields).forEach(key => {
            formData.append(key, $(fields[key]).val());
            $(fields[key]).prop('disabled', true);
        });

        $.ajax({
            url: "{{ url('/finance-management/transactions/new/beneficiary') }}",
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,

            success(response) {
                if (response.status === 'success') {
                    showSuccess(response.message);
                    resetForm();
                    getClientBeneficiries();

                    setTimeout(() => {
                        $("#add-new-sepa-beneficiary").modal('hide');
                    }, 1500);
                }
            },

            error(xhr) { 
                showError(xhr.responseJSON?.message || 'Something went wrong.');
                resetState();
            },
            complete() {
                resetState();
            }
        });
        
        /* ---------- Helpers ---------- */
    
        function resetForm() {
            Object.values(fields).forEach(selector => {
                $(selector).val('').trigger('change');
            });
        }
    
        function resetState() {
            spinner.hide();
            submitBtn.prop('disabled', false);
            Object.values(fields).forEach(selector => {
                $(selector).prop('disabled', false);
            });
        }
    
        function showError(message) {
            $('#error-alert-box').show();
            $('#alert-error-message').text(message);
        }
    
        function showSuccess(message) {
            $('#success-alert-box').show();
            $('#alert-success-message').text(message);
        }
    }
</script>
