@extends('layouts.cp')
@section('title', 'New Screening - Compliance Management')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Compliance Management</span>
        </h1>
    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false"
        data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                New Screening
            </h2>
        </div>

        <form method="post" action="{{ url('/dashboards/compliance-management/cases/new') }}" id="sreening-form"
            enctype="multipart/form-data">
            @csrf
            <div class="panel-container show">
                <div class="panel-content">
                    @include('common.error-message')

                    @php $worldCheckEnabled = \App\Services\WorldCheckConfigService::isEnabled(); @endphp
                    @if (!$worldCheckEnabled)
                        <div class="alert alert-warning m-3">
                            World-Check is disabled. Enable the <strong>WorldCheck</strong> module (and its config checkbox) in System Settings to run new screenings.
                            Existing cases and reports remain available.
                        </div>
                    @endif

                    <div class="tab-content tab-content-client p-3" @if (!$worldCheckEnabled) style="pointer-events:none;opacity:.55" @endif>
                        <div class="tab-pane fade active show" id="tab-general" role="tabpanel"
                            aria-labelledby="tab-general">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="form-label" for="entity-type">Entity Type </label><br>
                                        <div class="custom-control custom-radio custom-control-inline mb-3">
                                            <input type="radio" class="custom-control-input" id="individual"
                                                value="individual" @if (old('entity-type') == 'individual') checked @endif
                                                name="entity-type" onclick="showHideEntityType(true)">
                                            <label class="custom-control-label" for="individual">Individual</label>
                                        </div>
                                        <div class="custom-control custom-radio custom-control-inline mb-3">
                                            <input type="radio" class="custom-control-input" id="entity"
                                                @if (old('entity-type') == 'entity') checked @endif value="entity"
                                                name="entity-type" onclick="showHideEntityType(false)">
                                            <label class="custom-control-label" for="entity">Company</label>
                                        </div>
                                    </div>
                                </div>
                                {{--    <div class="col-md-4 ">
                                    <div class="form-group ">
                                        <label class="form-label" for="entity-type">Search Source </label><br>
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input" id="sanctions_io"
                                                name="search-source[]"
                                                value="sanctions_io"@if (is_array(old('search-source')) && in_array('sanctions_io', old('search-source'))) checked @endif>
                                            <label class="custom-control-label" for="sanctions_io">Sanctions.io
                                            </label>
                                            <span class="ml-6">
                                                <input type="checkbox" class="custom-control-input" id="worldCheck"
                                                    name="search-source[]" value="worldCheck" onclick="disablePassport()"
                                                    @if (is_array(old('search-source')) && in_array('worldCheck', old('search-source'))) checked @endif>
                                                <label class="custom-control-label" for="worldCheck">WorldCheck</label>
                                            </span>

                                        </div>
                                    </div>
                                </div> --}}
                                <div class="col-md-4 toggle-entity-type" style="display: none">
                                    <div class="form-group ">
                                        <label class="form-label" for="entity-type">Screen By </label><br>
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input" id="screen-by-name"
                                                name="screen-by[]" value="screen-by-name" onclick="showHideScreenByName()"
                                                @if (is_array(old('screen-by')) && in_array('screen-by-name', old('screen-by'))) checked @endif>
                                            <label class="custom-control-label" for="screen-by-name">Name
                                            </label>
                                            <span class="ml-6">
                                                <input type="checkbox" class="custom-control-input" id="screen-by-passport"
                                                    name="screen-by[]" value="screen-by-passport"
                                                    onclick="showHideScreenByName()"
                                                    @if (is_array(old('screen-by')) && in_array('screen-by-passport', old('screen-by'))) checked @endif
                                                    @if (is_array(old('search-source')) && in_array('worldCheck', old('search-source'))) {{-- @else disabled --}} @endif>
                                                <label class="custom-control-label"
                                                    for="screen-by-passport">Passport</label>
                                            </span>

                                        </div>
                                    </div>
                                </div>
                            </div>
                            <br>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label toggle-entity-type-individual" for="first-name">
                                            First Name
                                        </label>
                                        <label class="form-label toggle-entity-type-company" style="display: none"
                                            for="first-name">
                                            Company Name
                                        </label>
                                        <input type="text" autofocus id="first-name" name="first-name"
                                            value="{{ old('first-name') }}" class="form-control" required>
                                        @error('first-name')
                                            <span class="invalid-feedback"
                                                role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                    <div class="form-group toggle-entity-type">
                                        <label class="form-label" for="last-name">Last Name</label>
                                        <input type="text" autofocus id="last-name" name="last-name"
                                            value="{{ old('last-name') }}" class="form-control">
                                        @error('last-name')
                                            <span class="invalid-feedback"
                                                role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                    <div class="toggle-entity-type">
                                        <div class="form-group">
                                            <label class="form-label" for="dob">Date of Birth</label>
                                            <input type="text" value="{{ old('dob') }}" autocomplete="off"
                                                class="form-control" name="dob" id="dob"
                                                placeholder="Select date">
                                            @error('dob')
                                                <span class="invalid-feedback"
                                                    role="alert"><strong>{{ $message }}</strong></span>
                                            @enderror
                                        </div>
                                    </div>
                                    <br>
                                    <div class="form-group">
                                        <label class="form-label" for="nationality_iso">Nationality</label>
                                        <select name="nationality_iso" id="nationality_iso"
                                            data-select2-id="nationality_iso" class="form-control select2 w-100"
                                            onchange="updateHiddenField('nationality')">
                                            <option value="">choose nationality</option>
                                            @foreach ($nationalities as $nationality)
                                                @if ($nationality->iso == old('nationality_iso'))
                                                    <option value="{{ $nationality->iso }}" selected
                                                        data-iso3={{ $nationality->iso3 }}>
                                                        {{ $nationality->name }}</option>
                                                @else
                                                    <option value="{{ $nationality->iso }}"
                                                        data-iso3={{ $nationality->iso3 }}>
                                                        {{ $nationality->name }}
                                                    </option>
                                                @endif
                                            @endforeach
                                        </select>
                                        <input type="hidden" id="nationality_iso3" name="nationality_iso3"
                                            value="{{ old('nationality_iso3') }}">
                                        @error('nationality_iso')
                                            <span class="invalid-feedback"
                                                role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>



                                    <div class="form-group">
                                        <label class="form-label" for="address">Address</label>
                                        <input type="text" autofocus id="address" name="address"
                                            value="{{ old('address') }}" class="form-control">
                                        @error('address')
                                            <span class="invalid-feedback"
                                                role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label" for="email_address">Email Address</label>
                                        <input type="email" id="email_address" name="email_address"
                                            value="{{ old('email_address') }}" class="form-control">
                                        @error('email_address')
                                            <span class="invalid-feedback"
                                                role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                    <div class="toggle-entity-type " style="display: none">
                                        <label class="form-label" for="gender">Gender</label><br>
                                        <div class="custom-control custom-radio custom-control-inline mb-3">
                                            <input type="radio" class="custom-control-input" id="male"
                                                value="MALE" @if (old('gender') == 'MALE') checked @endif
                                                name="gender">
                                            <label class="custom-control-label" for="male">Male</label>
                                        </div>
                                        <div class="custom-control custom-radio custom-control-inline mb-3">
                                            <input type="radio" class="custom-control-input" id="female"
                                                @if (old('gender') == 'FEMALE') checked @endif value="FEMALE"
                                                name="gender">
                                            <label class="custom-control-label" for="female">Female</label>
                                        </div>
                                    </div>

                                </div>

                                <div class="col-md-6 toggle-screen-by-show" style="display: none">
                                    <div class="toggle-entity-type ">
                                        <div class="toggle-screen-by-passport">
                                            <div class="form-group ">
                                                <label class="form-label" for="passport_number">Passport Number</label>
                                                <input type="text" id="passport_number" name="passport_number"
                                                    value="{{ old('passport_number') }}" class="form-control">
                                                @error('passport_number')
                                                    <span class="invalid-feedback"
                                                        role="alert"><strong>{{ $message }}</strong></span>
                                                @enderror
                                            </div>
                                            <div class="form-group">
                                                <label class="form-label" for="passport_issuingState">Passport
                                                    issuing state</label>
                                                <select name="passport_issuingState" id="passport_issuingState"
                                                    data-select2-id="passport_issuingState"
                                                    class="form-control select2 w-100">
                                                    <option value="">choose Passport issuing state</option>
                                                    @foreach ($nationalities as $nationality)
                                                        @if ($nationality->iso3 != null && $nationality->iso3 == old('passport_issuingState'))
                                                            <option value="{{ $nationality->iso3 }}" selected>
                                                                {{ $nationality->name }}</option>
                                                        @else
                                                            <option value="{{ $nationality->iso3 }}">
                                                                {{ $nationality->name }}
                                                            </option>
                                                        @endif
                                                    @endforeach
                                                </select>
                                                @error('passport_issuingState')
                                                    <span class="invalid-feedback"
                                                        role="alert"><strong>{{ $message }}</strong></span>
                                                @enderror
                                            </div>
                                            <div class="form-group">
                                                <label class="form-label" for="passport_expiry_date">Passport Expiry
                                                    Date</label>
                                                <input type="text" autocomplete="off" class="form-control"
                                                    value="{{ old('passport_expiry_date') }}"name="passport_expiry_date"
                                                    id="passport_expiry_date" placeholder="Select date">
                                                @error('passport_expiry_date')
                                                    <span class="invalid-feedback"
                                                        role="alert"><strong>{{ $message }}</strong></span>
                                                @enderror
                                            </div>
                                        </div>
                                        <br class="toggle-screen-by-passport">
                                        <div class="form-group">
                                            <label class="form-label" for="citizenship_iso">Citizenship</label>
                                            <select name="citizenship_iso" id="citizenship_iso"
                                                data-select2-id="citizenship_iso" class="form-control select2 w-100"
                                                onchange="updateHiddenField('citizenship')">
                                                <option value="">choose Citizenship</option>
                                                @foreach ($nationalities as $nationality)
                                                    @if ($nationality->iso == old('citizenship_iso'))
                                                        <option value="{{ $nationality->iso }}" selected
                                                            data-iso3={{ $nationality->iso3 }}>
                                                            {{ $nationality->name }}</option>
                                                    @else
                                                        <option value="{{ $nationality->iso }}"
                                                            data-iso3={{ $nationality->iso3 }}>
                                                            {{ $nationality->name }}
                                                        </option>
                                                    @endif
                                                @endforeach
                                            </select>
                                            <input type="hidden" id="citizenship_iso3" name="citizenship_iso3"
                                                value="{{ old('citizenship_iso3') }}">
                                            @error('citizenship_iso')
                                                <span class="invalid-feedback"
                                                    role="alert"><strong>{{ $message }}</strong></span>
                                            @enderror
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label" for="place_of_birth_iso">Place of
                                                birth</label>
                                            <select name="place_of_birth_iso" id="place_of_birth_iso"
                                                data-select2-id="place_of_birth_iso" class="form-control select2 w-100"
                                                onchange="updateHiddenField('place_of_birth')">
                                                <option value="">choose Place of birth</option>
                                                @foreach ($nationalities as $nationality)
                                                    @if ($nationality->iso == old('place_of_birth_iso'))
                                                        <option value="{{ $nationality->iso }}" selected
                                                            data-iso3={{ $nationality->iso3 }}>
                                                            {{ $nationality->name }}</option>
                                                    @else
                                                        <option value="{{ $nationality->iso }}"
                                                            data-iso3={{ $nationality->iso3 }}>
                                                            {{ $nationality->name }}
                                                        </option>
                                                    @endif
                                                @endforeach
                                            </select>
                                            <input type="hidden" id="place_of_birth_iso3" name="place_of_birth_iso3"
                                                value="{{ old('place_of_birth_iso3') }}">
                                            @error('place_of_birth_iso')
                                                <span class="invalid-feedback"
                                                    role="alert"><strong>{{ $message }}</strong></span>
                                            @enderror
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label" for="country_residence_iso">Country
                                                residence</label>
                                            <select name="country_residence_iso" id="country_residence_iso"
                                                data-select2-id="country_residence_iso" class="form-control select2 w-100"
                                                onchange="updateHiddenField('country_residence')">
                                                <option value="">choose Country residence</option>
                                                @foreach ($nationalities as $nationality)
                                                    @if ($nationality->iso == old('country_residence_iso'))
                                                        <option value="{{ $nationality->iso }}" selected
                                                            data-iso3={{ $nationality->iso3 }}>
                                                            {{ $nationality->name }}</option>
                                                    @else
                                                        <option value="{{ $nationality->iso }}"
                                                            data-iso3={{ $nationality->iso3 }}>
                                                            {{ $nationality->name }}
                                                        </option>
                                                    @endif
                                                @endforeach
                                            </select>
                                            <input type="hidden" id="country_residence_iso3"
                                                name="country_residence_iso3"
                                                value="{{ old('country_residence_iso3') }}">
                                            @error('country_residence_iso')
                                                <span class="invalid-feedback"
                                                    role="alert"><strong>{{ $message }}</strong></span>
                                            @enderror
                                        </div>
                                    </div>


                                </div>


                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group d-flex justify-content-between align-items-center">
                                    <button type="button" onclick="submitFunction()"
                                        class="btn btn-primary">Screen</button>
                                    <span class="text-muted fs-base">
                                        Powered by <span class="lseg-color">LSEG</span> World-Check One
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

        </form>
    </div>
@stop
@section('scripts')
    <script>
        function submitFunction() {
            $('#loading-spinner').show();
            $('#sreening-form').submit();
        }
        // setting the iso3 value for country
        function updateHiddenField(fieldId) {
            const dropdown = document.getElementById(fieldId + '_iso');
            document.getElementById(fieldId + '_iso3').value = dropdown.options[dropdown.selectedIndex].dataset.iso3;
        }
        //end of setting the iso3 value for country
        const oldYourField = "{{ old('entity-type') }}";
        if (oldYourField == 'individual') {
            showHideEntityType(true);
        }

        var controls = {
            leftArrow: '<i class="fal fa-angle-left" style="font-size: 1.25rem"></i>',
            rightArrow: '<i class="fal fa-angle-right" style="font-size: 1.25rem"></i>'
        }

        $(document).ready(function() {

            $('.select2').select2();

            // minimum setup
            $('#dob').datepicker({
                format: 'dd/mm/yyyy',
                todayHighlight: true,
                orientation: "bottom left",
                templates: controls,
                endDate: "{{ \Carbon\Carbon::today()->format('d/m/Y') }}"
            });

            // minimum setup
            $('#passport_expiry_date').datepicker({
                format: 'dd/mm/yyyy',
                todayHighlight: true,
                orientation: "bottom left",
                templates: controls,
                startDate: "{{ \Carbon\Carbon::today()->format('d/m/Y') }}"
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

        //show fields for individual or Entity function
        function showHideEntityType(individual) {
            var elements = document.getElementsByClassName("toggle-entity-type");
            var companyElement = document.getElementsByClassName("toggle-entity-type-company");
            var individualElement = document.getElementsByClassName("toggle-entity-type-individual");

            for (var i = 0, length = elements.length; i < length; i++) {

                if (individual) {
                    elements[i].style.display = "block";
                    companyElement[0].style.display = "none";
                    individualElement[0].style.display = "block";

                } else {
                    elements[i].style.display = "none";
                    companyElement[0].style.display = "block";
                    individualElement[0].style.display = "none";
                }
            }


        }
        showHideScreenByName();
        // by name
        function showHideScreenByName() {
            var elements = document.getElementsByClassName("toggle-screen-by-show");
            for (var i = 0, length = elements.length; i < length; i++) {

                elements[i].style.display = "block";
            }
            var elements = document.getElementsByClassName("toggle-screen-by-passport");
            var screenByPassportCheckbox = document.getElementById('screen-by-passport');
            for (var i = 0, length = elements.length; i < length; i++) {
                if (screenByPassportCheckbox.checked) {
                    elements[i].style.display = "block";
                } else {
                    elements[i].style.display = "none";
                }
            }

        }

        function disablePassport() {
            var passportCheckbox = document.getElementById('screen-by-passport');
            var worldcheckCheckbox = document.getElementById('worldCheck');

            if (worldcheckCheckbox.checked) {
                passportCheckbox.disabled = false;
            } else {
                passportCheckbox.disabled = true;
                passportCheckbox.checked = false;
                showHideScreenByName();
            }
        }
    </script>
@stop
