{{-- <form method="post" action="{{ url('/client-profile/company/update') }}">
    @csrf --}}
<input type="hidden" name="client_id" value="{{ $client->id }}">
<div class="main">
    <div class="text">
        <h2>Your Company Information</h2>
        <p>We are not sharing company information with any 3rd party.</p>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label class="form-label" for="name">Company Name</label>
                <input type="text" autofocus id="name" name="name" value="{{ $client->name }}"
                    class="form-control" required>

                <span class="error-input" role="alert"><strong></strong></span>

            </div>
            <div class="form-group">
                <label class="form-label" for="registration_date">Date of Incorporation</label>
                <input type="text" autocomplete="off" class="form-control" name="registration_date"
                    id="registration_date" required
                    value="{{ $client->company->registration_date ? \Carbon\Carbon::parse($client->company->registration_date)->format('d/m/Y') : '' }}"
                    placeholder="Select date">

                <span class="error-input" role="alert"><strong></strong></span>

            </div>

            <div class="form-group">
                <label class="form-label" for="expiry_date">License Expiry Date</label>
                <input required type="text" autocomplete="off" class="form-control" name="expiry_date"
                    id="expiry_date"
                    value="{{ $client->company->expiry_date ? \Carbon\Carbon::parse($client->company->expiry_date)->format('d/m/Y') : '' }}"
                    placeholder="Select date">

                <span class="error-input" role="alert"><strong></strong></span>

            </div>
            <div class="form-group">
                <label class="form-label">Type of Company</label>
                <hr>
                @foreach ($companyTypes as $type)
                    <div class="custom-control custom-radio mb-3">
                        <input required type="radio" @if ($type->id == $client->company->company_type) checked @endif
                            class="custom-control-input" id="{{ $type->id }}" value="{{ $type->id }}"
                            name="company_type">
                        <label class="custom-control-label" for="{{ $type->id }}">{{ $type->name }}</label>
                    </div>
                @endforeach
                <span class="error-input" role="alert"><strong></strong></span>
            </div>
        </div>

        <div class="col-md-6">
            <div class="form-group">
                <label class="form-label" for="company_place">Place of Registration of Company</label>
                <input type="text" id="company_place" name="company_place"
                    value="{{ $client->company->company_place }}" class="form-control" required>

                <span class="error-input" role="alert"><strong></strong></span>

            </div>

            <div class="form-group">
                <label class="form-label" for="business_reg_number">Business Registration Number</label>
                <input type="text" id="business_reg_number" name="business_reg_number"
                    value="{{ $client->company->business_reg_number }}" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="business_reg_address">Business Registration Address</label>
                <input type="text" id="business_reg_address" name="business_reg_address"
                    value="{{ $client->company->business_reg_address }}" class="form-control" required>

                <span class="error-input" role="alert"><strong></strong></span>

            </div>
            <div class="form-group">
                <label class="form-label" for="nature_of_business">Nature of Business</label>
                <input type="text" id="nature_of_business" name="nature_of_business"
                    value="{{ $client->company->nature_of_business }}" class="form-control" required>

                <span class="error-input" role="alert"><strong></strong></span>

            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="form-group">
                <hr>
                <button type="button" class="btn btn-outline-secondary back_button"><i class="fal fa-arrow-left"></i>
                    Back</button>
                <button type="button" class="btn btn-primary next_step_button">Next <i
                        class="fal fa-arrow-right"></i></button>
            </div>
        </div>
    </div>
</div>
{{-- </form> --}}
