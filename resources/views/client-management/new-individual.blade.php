@extends('layouts.cp')
@section('title', 'New Client - Individual - Client Management')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Client Management</span>
        </h1>
        <a href="{{ url('/client-management/clients') }}" class="btn btn-primary mr-3"><i class="fal fa-arrow-alt-left"></i> Go
            Back </a>
    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false"
        data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                New Client - Individual
            </h2>
        </div>

        <form method="post" action="{{ url('/client-management/clients/store/individual') }}"
            enctype="multipart/form-data">
            @csrf
            @if ($parent_client_id)
                <input type="hidden" name="parent_client_id" value="{{ $parent_client_id }}">
            @endif
            <div class="panel-container show">
                <div class="panel-content">
                    @include('common.error-message')

                    <div id="tabs">
                        <ul class="nav nav-tabs nav-tabs-clean" role="tablist">
                            <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#tab-general"
                                    role="tab" aria-selected="false">Individual</a></li>
                            <li class="nav-item"><a class="nav-link disabled" data-toggle="tab" href="#contact-person"
                                    role="tab" aria-selected="false">Employer Details</a></li>
                            <li class="nav-item"><a class="nav-link disabled"data-toggle="tab" href="#account-currency"
                                    role="tab" aria-selected="true">Accounts</a></li>
                            <li class="nav-item"><a class="nav-link disabled" data-toggle="tab" href="#related-files"
                                    role="tab" aria-selected="true">Related Files</a></li>
                        </ul>

                        <div class="tab-content tab-content-client p-3">
                            <div class="tab-pane fade active show" id="tab-general" role="tabpanel"
                                aria-labelledby="tab-general">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label" for="name">Name</label>
                                            <input type="text" autofocus id="name" name="name"
                                                value="{{ old('name') }}" class="form-control" required>
                                            @error('name')
                                                <span class="invalid-feedback"
                                                    role="alert"><strong>{{ $message }}</strong></span>
                                            @enderror
                                        </div>
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
                                        <div class="form-group">
                                            <label class="form-label" for="nationality_id">Nationality</label>
                                            <select name="nationality_id" id="nationality_id"
                                                data-select2-id="nationality_id" class="form-control select2 w-100"
                                                required>
                                                <option value="">Choose nationality</option>
                                                @foreach ($nationalities as $nationality)
                                                    @if ($nationality->id == old('nationality_id'))
                                                        <option value="{{ $nationality->id }}" selected>
                                                            {{ $nationality->name }}</option>
                                                    @else
                                                        <option value="{{ $nationality->id }}">{{ $nationality->name }}
                                                        </option>
                                                    @endif
                                                @endforeach
                                            </select>
                                            @error('nationality_id')
                                                <span class="invalid-feedback"
                                                    role="alert"><strong>{{ $message }}</strong></span>
                                            @enderror
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label" for="residence_country_id">Country of
                                                Residence</label>
                                            <select name="residence_country_id" id="residence_country_id"
                                                data-select2-id="residence_country_id" class="form-control select2 w-100"
                                                required>
                                                <option value="">Choose country of residence</option>
                                                @foreach ($nationalities as $nationality)
                                                    @if ($nationality->id == old('residence_country_id'))
                                                        <option value="{{ $nationality->id }}" selected>
                                                            {{ $nationality->name }}</option>
                                                    @else
                                                        <option value="{{ $nationality->id }}">{{ $nationality->name }}
                                                        </option>
                                                    @endif
                                                @endforeach
                                            </select>

                                            <span class="error-input" role="alert"><strong></strong></span>

                                        </div>

                                        <div class="form-group">
                                            <label class="form-label" for="city">City of Individual</label>
                                            <input type="text" autofocus id="city" name="city"
                                                value="{{ old('city') }}" class="form-control" required>
                                            @error('city')
                                                <span class="invalid-feedback"
                                                    role="alert"><strong>{{ $message }}</strong></span>
                                            @enderror
                                        </div>

                                        <div class="form-group">
                                            <label class="form-label" for="mailing_address">Mailing Address</label>
                                            <input type="text" autofocus id="mailing_address" name="mailing_address"
                                                value="{{ old('mailing_address') }}" class="form-control" required>
                                            @error('mailing_address')
                                                <span class="invalid-feedback"
                                                    role="alert"><strong>{{ $message }}</strong></span>
                                            @enderror
                                        </div>

                                        <label class="form-label" for="gender">Gender</label><br>
                                        <div class="custom-control custom-radio custom-control-inline mb-3">
                                            <input type="radio" class="custom-control-input" id="male"
                                                value="male" @if (old('gender') == 'male') checked @endif
                                                name="gender">
                                            <label class="custom-control-label" for="male">Male</label>
                                        </div>
                                        <div class="custom-control custom-radio custom-control-inline mb-3">
                                            <input type="radio" class="custom-control-input" id="female"
                                                @if (old('gender') == 'female') checked @endif value="female"
                                                name="gender">
                                            <label class="custom-control-label" for="female">Female</label>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        @include('client-management.partials.contact-number.add')
                                        <div class="form-group">
                                            <label class="form-label" for="email_address">Email Address</label>
                                            <input type="email" id="email_address" name="email_address"
                                                value="{{ old('email_address') }}" class="form-control" required>
                                            @error('email_address')
                                                <span class="invalid-feedback"
                                                    role="alert"><strong>{{ $message }}</strong></span>
                                            @enderror
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label" for="passport_number">Passport Number</label>
                                            <input type="text" id="passport_number" name="passport_number"
                                                value="{{ old('passport_number') }}" class="form-control" required>
                                            @error('passport_number')
                                                <span class="invalid-feedback"
                                                    role="alert"><strong>{{ $message }}</strong></span>
                                            @enderror
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label" for="passport_expiry_date">Passport Expiry
                                                Date</label>
                                            <input type="text" autocomplete="off" class="form-control"
                                                value="{{ old('passport_expiry_date') }}"name="passport_expiry_date"
                                                id="passport_expiry_date" placeholder="Select date" required>
                                            @error('passport_expiry_date')
                                                <span class="invalid-feedback"
                                                    role="alert"><strong>{{ $message }}</strong></span>
                                            @enderror
                                        </div>

                                        @if (!$parent_client_id)
                                            @role('super admin')
                                                <div class="form-group">
                                                    <label class="form-label" for="sub_accounts_prefix">Sub-Account
                                                        Prefix</label>
                                                    <input type="text" id="sub_accounts_prefix" name="sub_accounts_prefix"
                                                        value="{{ old('sub_accounts_prefix') }}" min="3"
                                                        max="5" class="form-control">
                                                    @error('sub_accounts_prefix')
                                                        <span class="invalid-feedback"
                                                            role="alert"><strong>{{ $message }}</strong></span>
                                                    @enderror
                                                </div>
                                            @endrole

                                            <div class="form-group">
                                                <label class="form-label" for="introducer">Introducer</label>
                                                <input type="text" id="introducer" name="introducer"
                                                    value="{{ old('introducer') }}" class="form-control">
                                                @error('introducer')
                                                    <span class="invalid-feedback"
                                                        role="alert"><strong>{{ $message }}</strong></span>
                                                @enderror
                                            </div>

                                            <div class="form-group">
                                                <label for="" class="form-label mb-2">Send Emails</label><br>
                                                <div class="custom-control custom-checkbox custom-control-inline">
                                                    <input type="checkbox" class="custom-control-input"
                                                        id="send_welcome_email"
                                                        @if (old('send_welcome_email')) checked @endif
                                                        name="send_welcome_email">
                                                    <label class="custom-control-label" for="send_welcome_email">Welcome
                                                        Email</label>
                                                </div>
                                                <div class="custom-control custom-checkbox custom-control-inline">
                                                    <input type="checkbox" class="custom-control-input" id="send_ssi"
                                                        @if (old('send_ssi')) checked @endif name="send_ssi">
                                                    <label class="custom-control-label" for="send_ssi">SSI Email</label>
                                                </div>
                                                <div class="custom-control custom-checkbox custom-control-inline">
                                                    <input type="checkbox" class="custom-control-input" id="create_user"
                                                        @if (old('create_user')) checked @endif
                                                        name="create_user">
                                                    <label class="custom-control-label" for="create_user">Online Log-in
                                                        Details
                                                        Email</label>
                                                </div>
                                            </div>
                                        @endif

                                        <div class="form-group">
                                            <label for="" class="form-label">Maintenance Fee</label>
                                            <hr>
                                            <div class="custom-control custom-checkbox mt-3">
                                                <input type="checkbox" class="custom-control-input"
                                                    id="charge_maintenance_fee" name="charge_maintenance_fee">
                                                <label class="custom-control-label" for="charge_maintenance_fee">Charge
                                                    Maintenance Fee</label>
                                            </div>
                                        </div>
                                    </div>

                                    @role('super admin')
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="" class="form-label">Internal Transfers</label>
                                                <hr>
                                                <div class="custom-control custom-checkbox mt-3">
                                                    <input type="checkbox" class="custom-control-input"
                                                        id="is_b2b_internal_transfer_allowed"
                                                        name="is_b2b_internal_transfer_allowed">
                                                    <label class="custom-control-label"
                                                        for="is_b2b_internal_transfer_allowed">Allow Internal
                                                        Transfers</label><br>
                                                    <small>Allow internal transfers from clients been onboarded via
                                                        B2B-API/Merchant-API to this client.</small>
                                                </div>
                                            </div>
                                        </div>
                                    @endrole
                                </div>

                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <hr>
                                <button type="submit" class="btn btn-primary">Save</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
    {{--    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script> --}}
@stop
@section('scripts')

    <script>
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
    </script>
@stop
