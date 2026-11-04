{{-- <form method="post" action="{{ url('/client-profile/company-contact/update') }}">
    @csrf --}}
<input type="hidden" name="client_id" value="{{ $client->id }}">
<input type="hidden" name="company_id" value="{{ $client->company->id }}">
<div class="main active">
    <div class="text">
        <h2>Your Personal Information</h2>
        <p>We are not sharing personal information with any 3rd party.</p>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label class="form-label" for="passport">Passport</label>
                <input type="file" name="passport_file" {{-- accept="image/*" --}} required class="form-control">

                <span class="error-input" role="alert"><strong></strong></span>

            </div>
            <div class="form-group">
                <label class="form-label" for="passport_expiry_date">Passport Expiry Date</label>
                <input autocomplete="off" required type="text" class="form-control" name="passport_expiry_date"
                    value="{{ $client->company->passport_expiry_date ? \Carbon\Carbon::parse($client->company->contact->passport_expiry_date)->format('d/m/Y') : '' }}"
                    id="passport_expiry_date" placeholder="Select date">

                <span class="error-input" role="alert"><strong></strong></span>

            </div>
            <div class="form-group">
                <label class="form-label" for="residence_country_id">Country of Residence</label>
                <select name="residence_country_id" id="residence_country_id" value="{{ $client->company->contact->residence_country_id }}"
                    data-select2-id="nationality_id" class="form-control select2 w-100" required>
                    <option value="">Choose nationality</option>
                    @foreach ($nationalities as $nationality)
                        @if ($nationality->id == $client->company->contact->residence_country_id)
                            <option value="{{ $nationality->id }}" selected>{{ $nationality->name }}</option>
                        @else
                            <option value="{{ $nationality->id }}">{{ $nationality->name }}</option>
                        @endif
                    @endforeach
                </select>

                <span class="error-input" role="alert"><strong></strong></span>

            </div>
            <div class="form-group">
                <label class="form-label" for="contact_person_name">Name</label>
                <input type="text" autofocus id="contact_person_name" name="contact_person_name" value="{{ $client->company->contact->name }}"
                    class="form-control" required>

                <span class="error-input" role="alert"><strong></strong></span>

            </div>
            
            <div class="form-group">
                <label class="form-label" for="dob">Date of Birth</label>
                <input type="text" autocomplete="off" class="form-control" name="dob"
                    value="{{ $client->company->dob ? \Carbon\Carbon::parse($client->company->contact->dob)->format('d/m/Y') : '' }}"
                    id="dob" placeholder="Select date" required>

                <span class="error-input" role="alert"><strong></strong></span>

            </div>
            
            
            {{-- <div class="form-group">
                    <label class="form-label" for="mailing_address">Mailing Address</label>
                    <input type="text" autofocus id="mailing_address" name="mailing_address" value="{{  $client->company->contact->mailing_address }}" class="form-control" required>

                </div> --}}
           
        </div>

        <div class="col-md-6">
            {{--  <div class="form-group">
                    <label class="form-label" for="mobile_number">Mobile Number</label>
                    <input type="hidden" name="country_code_id" value="{{ $client->company->contact->country_code_id }}">
                    <input type="number" id="mobile_number" name="mobile_number"
                        value="{{ $client->company->contact->mobile_number }}" class="form-control" required>

                        <span class="error-input" role="alert"><strong></strong></span>

                </div> --}}
            {{--             <div class="form-group" hidden>
                <label class="form-label" for="email_address">Email Address</label>
                <input readonly type="email" id="email_address" name="email_address"
                    value="{{ $client->company->contact->email_address }}" class="form-control" required>

                <span class="error-input" role="alert"><strong></strong></span>

            </div> --}}
            
            <div class="form-group">
                <label class="form-label" for="passport_number">Passport Number</label>
                <input type="text" id="passport_number" name="passport_number"
                    value="{{ $client->company->contact->passport_number }}" class="form-control" required>

                <span class="error-input" role="alert"><strong></strong></span>

            </div>
            
            <div class="form-group">
                <label class="form-label" for="nationality_id">Nationality</label>
                <select name="nationality_id" id="nationality_id" value="{{ $client->company->contact->nationality_id }}"
                    data-select2-id="nationality_id" class="form-control select2 w-100" required>
                    <option value="">choose nationality</option>
                    @foreach ($nationalities as $nationality)
                        @if ($nationality->id == $client->company->nationality_id)
                            <option value="{{ $nationality->id }}" selected>{{ $nationality->name }}</option>
                        @else
                            <option value="{{ $nationality->id }}">{{ $nationality->name }}</option>
                        @endif
                    @endforeach
                </select>

                <span class="error-input" role="alert"><strong></strong></span>

            </div>
            
            <div class="form-group">
                <label class="form-label" for="contact_person_designation">Designation</label>
                <input type="text" autofocus id="contact_person_designation" name="contact_person_designation" value="{{ $client->company->contact->designation }}"
                    class="form-control" required>

                <span class="error-input" role="alert"><strong></strong></span>

            </div>
            <div class="form-group">
                <label class="form-label" for="gender">Gender</label><br>
                <div class="custom-control custom-radio custom-control-inline mb-3">
                    <input type="radio" required class="custom-control-input" id="male"
                        @if ($client->company->contact->gender == 'male') checked @endif value="male" name="gender">
                    <label class="custom-control-label" for="male">Male</label>
                </div>
                <div class="custom-control custom-radio custom-control-inline mb-3">
                    <input type="radio" required class="custom-control-input" id="female"
                        @if ($client->company->contact->gender == 'female') checked @endif value="female" name="gender">
                    <label class="custom-control-label" for="female">Female</label>

                </div>
                <div>
                    <span class="error-input" role="alert"><strong></strong></span>

                </div>
            </div>
            
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <hr>
            <h2>Client Directors / UBO's</h2>
                                            
                <div id="directorsContainer">
                </div>
                <button type="button" class="btn btn-primary mb-3" id="addDirectorBtn" onclick="addDirector()">
                    <i class="fal fa-plus"></i> Add Director
                </button>
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
<script>
    let directorIndex = 0;
    function addDirector() {
        $('#no-directors-message').hide();
        const directorRow = `
            <div class="row director-row-${directorIndex} mb-4" data-index="${directorIndex}">
                <div class="col-md-6">
                    <div class="form-group">
                        <input type="hidden" class="form-control" name="directors[${directorIndex}][id]" value="">
                        <input type="hidden" class="form-control" name="directors[${directorIndex}][delete]" id="delete-${directorIndex}" value="0">
                        <input type="text" class="form-control director-name" name="directors[${directorIndex}][name]" placeholder="Full Name" required>
                        <span class="error-input" role="alert" id="invalid-feedback-name-${directorIndex}"></span>
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="form-group">
                        <input type="email" class="form-control director-email" name="directors[${directorIndex}][email]" placeholder="Email" required>
                        <span class="error-input" role="alert" id="invalid-feedback-email-${directorIndex}"></span>
                    </div>
                </div>
                
                <div class="col-md-1">
                    <a href="javascript:void(0)" onclick="deleteDirectorRow(${directorIndex})" title="Delete director">
                        <i class="fal fa-trash" aria-hidden="true"></i>
                    </a>
                </div>
            </div>`;

        $('#directorsContainer').append(directorRow);
        directorIndex++;
        // Reinitialize select2 for the newly added select elements
        $(`.select2`).select2();
    }

    function deleteDirectorRow(index) {
        $('.director-row-'+index).remove();
    }
</script>