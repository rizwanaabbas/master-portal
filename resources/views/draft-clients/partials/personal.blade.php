{{-- <form action="{{ url('/client-profile/individual/update') }}" method="post" enctype="multipart/form-data">
    @csrf --}}
<input type="hidden" name="client_id" value="{{ $client->id }}">
<input type="hidden" name="individual_id" value="{{ $client->individual->id }}">
<div class="main active">
    <div class="text">
        <h2>Your Personal Information</h2>
        <p>We are not sharing personal information with any 3rd party.</p>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label class="form-label" for="passport_file">Passport</label>
                <input type="file" name="passport_file" {{-- accept="image/*" --}} required
                    data-error-message="Passport file is required.">

                <span class="error-input " role="alert"><strong></strong></span>

            </div>
            <div class="form-group" hidden>
                <label class="form-label" for="name">Name</label>
                <input type="text" autofocus id="name" name="name" value="{{ $client->name }}"
                    class="form-control" required>

                <span class="error-input" role="alert"><strong></strong></span>

            </div>
            <div class="form-group">
                <label class="form-label" for="dob">Date of Birth</label>
                <input type="text" autocomplete="off" class="form-control" name="dob"
                    value="{{ $client->individual->dob ? \Carbon\Carbon::parse($client->individual->dob)->format('d/m/Y') : '' }}"
                    id="dob" placeholder="Select date" required>

                <span class="error-input" role="alert"><strong></strong></span>

            </div>

            <div class="form-group">
                <label class="form-label" for="passport_expiry_date">Passport Expiry Date</label>
                <input autocomplete="off" required type="text" class="form-control" name="passport_expiry_date"
                    value="{{ $client->individual->passport_expiry_date ? \Carbon\Carbon::parse($client->individual->passport_expiry_date)->format('d/m/Y') : '' }}"
                    id="passport_expiry_date" placeholder="Select date">

                <span class="error-input" role="alert"><strong></strong></span>

            </div>
            {{-- <div class="form-group">
                    <label class="form-label" for="mailing_address">Mailing Address</label>
                    <input type="text" autofocus id="mailing_address" name="mailing_address" value="{{ $client->individual->mailing_address }}" class="form-control" required>

                </div> --}}
            <div class="form-group">
                <label class="form-label" for="gender">Gender</label><br>
                <div class="custom-control custom-radio custom-control-inline mb-3">
                    <input type="radio" class="custom-control-input" id="male"
                        @if ($client->individual->gender == 'male') checked @endif value="male" name="gender" required>
                    <label class="custom-control-label" for="male">Male</label>
                </div>
                <div class="custom-control custom-radio custom-control-inline mb-3">
                    <input type="radio" class="custom-control-input" id="female"
                        @if ($client->individual->gender == 'female') checked @endif value="female" name="gender" required>
                    <label class="custom-control-label" for="female">Female</label>
                </div>
                <span class="error-input" role="alert"><strong></strong></span>
            </div>
        </div>

        <div class="col-md-6">
            <div class="form-group">
                <label class="form-label" for="mobile_number">Mobile Number</label>
                <input type="hidden" name="country_code_id" value="{{ $client->individual->country_code_id }}">
                <input type="number" id="mobile_number" name="mobile_number"
                    value="{{ $client->individual->mobile_number }}" class="form-control" required>

                <span class="error-input" role="alert"><strong></strong></span>

            </div>
            <div class="form-group" hidden>
                <label class="form-label" for="email_address">Email Address</label>
                <input readonly type="email" id="email_address" name="email_address"
                    value="{{ $client->individual->email_address }}" class="form-control" required>

                <span class="error-input" role="alert"><strong></strong></span>

            </div>
            <div class="form-group">
                <label class="form-label" for="passport_number">Passport Number</label>
                <input type="text" id="passport_number" name="passport_number"
                    value="{{ $client->individual->passport_number }}" class="form-control" required>

                <span class="error-input" role="alert"><strong></strong></span>

            </div>
            <div class="form-group">
                <label class="form-label" for="nationality_id">Nationality</label>
                <select name="nationality_id" id="nationality_id" value="{{ $client->individual->nationality_id }}"
                    data-select2-id="nationality_id" class="form-control select2 w-100" required>
                    <option value="">Choose nationality</option>
                    @foreach ($nationalities as $nationality)
                        @if ($nationality->id == $client->individual->nationality_id)
                            <option value="{{ $nationality->id }}" selected>{{ $nationality->name }}</option>
                        @else
                            <option value="{{ $nationality->id }}">{{ $nationality->name }}</option>
                        @endif
                    @endforeach
                </select>

                <span class="error-input" role="alert"><strong></strong></span>

            </div>
            <div class="form-group">
                <label class="form-label" for="nationality_id">Country of Residence</label>
                <select name="residence_country_id" id="residence_country_id" 
                    data-select2-id="residence_country_id" class="form-control select2 w-100" required>
                    <option value="">Choose country of residence</option>
                    @foreach ($nationalities as $nationality)
                        @if ($nationality->id == $client->individual->residence_country_id)
                            <option value="{{ $nationality->id }}" selected>{{ $nationality->name }}</option>
                        @else
                            <option value="{{ $nationality->id }}">{{ $nationality->name }}</option>
                        @endif
                    @endforeach
                </select>

                <span class="error-input" role="alert"><strong></strong></span>

            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="form-group">
                <hr>
                <button type="button" class="btn btn-primary next_step_button">Next <i
                        class="fal fa-arrow-right"></i></button>
            </div>
        </div>
    </div>
</div>
{{-- </form> --}}
