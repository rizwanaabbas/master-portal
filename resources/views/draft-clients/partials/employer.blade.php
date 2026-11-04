{{-- <form action="{{ url('/client-profile/employer/update') }}" method="post">
    @csrf --}}
<input type="hidden" name="client_id" value="{{ $client->id }}">
<input type="hidden" name="individual_id" value="{{ $client->individual->id }}">
<div class="main">
    <div class="text">
        <h2>Employer</h2>
        <p>Tell us about your employer if you are working for a company.</p>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label class="form-label" for="occupation_business">Occupation / Business</label>
                <input type="text" id="occupation_business" name="occupation_business"
                    value="{{ $client->individual->employer ? $client->individual->employer->occupation_business : old('occupation_business') }}"
                    class="form-control" required>

                <span class="error-input" role="alert"><strong></strong></span>

            </div>
            <div class="form-group">
                <label class="form-label" for="employer_business_name">Applicant's Employer Business Name</label>
                <input type="text" id="employer_business_name" name="employer_business_name"
                    value="{{ $client->individual->employer ? $client->individual->employer->employer_business_name : old('employer_business_name') }}"
                    class="form-control" required>

                <span class="error-input" role="alert"><strong></strong></span>

            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label class="form-label" for="job_title">Job Title</label>
                <input type="text" id="job_title" name="job_title"
                    value="{{ $client->individual->employer ? $client->individual->employer->job_title : old('job_title') }}"
                    class="form-control" required>

                <span class="error-input" role="alert"><strong></strong></span>

            </div>
            <div class="form-group">
                <label class="form-label" for="$applicant_annual_income_usd">Applicant's Estimated Gross Annual
                    Income (USD)</label>
                <input type="number" id="$applicant_annual_income_usd" name="applicant_annual_income_usd"
                    value="{{ $client->individual->employer ? $client->individual->employer->applicant_annual_income_usd : old('applicant_annual_income_usd') }}"
                    class="form-control" required>

                <span class="error-input" role="alert"><strong></strong></span>

            </div>
        </div>
    </div>
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="form-group">
                <label class="form-label" for="employer_address">Employer Address</label>
                <input type="text" id="employer_address" name="employer_address"
                    value="{{ $client->individual->employer ? $client->individual->employer->employer_address : old('employer_address') }}"
                    class="form-control" required>

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
