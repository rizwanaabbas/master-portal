@extends('layouts.cp')
@section('title', 'New Client - Company - Client Management')

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
                New Client - Company
            </h2>
        </div>

        <form method="post" action="{{ url('/client-management/clients/store/company') }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="is_bank" value="{{ $is_bank }}">
            @if ($parent_client_id)
                <input type="hidden" name="parent_client_id" value="{{ $parent_client_id }}">
            @endif
            <div class="panel-container show">
                <div class="panel-content">
                    @include('common.error-message')

                    <div id="tabs">
                        <ul class="nav nav-tabs nav-tabs-clean" role="tablist">
                            <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#tab-general"
                                    role="tab" aria-selected="false">Company</a></li>
                            <li class="nav-item"><a class="nav-link disabled" data-toggle="tab" href="#contact-person"
                                    role="tab" aria-selected="false">Contact Person</a></li>
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
                                            <label class="form-label" for="name">Company Name</label>
                                            <input type="text" autofocus id="name" name="name"
                                                value="{{ old('name') }}" class="form-control" required>
                                            @error('name')
                                                <span class="invalid-feedback"
                                                    role="alert"><strong>{{ $message }}</strong></span>
                                            @enderror
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label" for="registration_date">Date of Incorporation</label>
                                            <input type="text" autocomplete="off" class="form-control"
                                                name="registration_date" id="registration_date"
                                                value="{{ old('registration_date') }}" placeholder="Select date">
                                            @error('registration_date')
                                                <span class="invalid-feedback"
                                                    role="alert"><strong>{{ $message }}</strong></span>
                                            @enderror
                                        </div>

                                        <div class="form-group">
                                            <label class="form-label" for="expiry_date">License Expiry Date</label>
                                            <input type="text" autocomplete="off" class="form-control" name="expiry_date"
                                                id="expiry_date" value="{{ old('expiry_date') }}" placeholder="Select date">
                                            @error('expiry_date')
                                                <span class="invalid-feedback"
                                                    role="alert"><strong>{{ $message }}</strong></span>
                                            @enderror
                                        </div>

                                        <div class="form-group">
                                            <label class="form-label" for="company_place">Place of Registration of
                                                Company</label>
                                            <input type="text" id="company_place" name="company_place"
                                                value="{{ old('company_place') }}" class="form-control" required>
                                            @error('company_place')
                                                <span class="invalid-feedback"
                                                    role="alert"><strong>{{ $message }}</strong></span>
                                            @enderror
                                        </div>

                                        <div class="form-group">
                                            <label class="form-label" for="company_city">City of Company</label>
                                            <input type="text" id="company_city" name="company_city"
                                                value="{{ old('company_place') }}" class="form-control" required>
                                            @error('company_city')
                                                <span class="invalid-feedback"
                                                    role="alert"><strong>{{ $message }}</strong></span>
                                            @enderror
                                        </div>

                                        <label class="form-label">Type of Company</label>
                                        <hr>
                                        @foreach ($companyTypes as $type)
                                            <div class="custom-control custom-radio mb-3">
                                                <input type="radio" class="custom-control-input"
                                                    id="ctype-{{ $type->id }}" value="{{ $type->id }}"
                                                    name="company_type">
                                                <label class="custom-control-label"
                                                    for="ctype-{{ $type->id }}">{{ $type->name }}</label>
                                            </div>
                                        @endforeach
                                    </div>

                                    <div class="col-md-6">


                                        <div class="form-group">
                                            <label class="form-label" for="business_reg_number">Business Registration
                                                Number</label>
                                            <input type="text" id="business_reg_number" name="business_reg_number"
                                                value="{{ old('business_reg_number') }}" class="form-control" required>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label" for="business_reg_address">Business Registration
                                                Address</label>
                                            <input type="text" id="business_reg_address" name="business_reg_address"
                                                value="{{ old('business_reg_address') }}" class="form-control" required>
                                            @error('business_reg_address')
                                                <span class="invalid-feedback"
                                                    role="alert"><strong>{{ $message }}</strong></span>
                                            @enderror
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label" for="nature_of_business">Nature of Business</label>

                                            <select name="nature_of_business" id="nature_of_business"
                                                data-select2-id="nature_of_business" class="form-control select2 w-100"
                                                required>
                                                <option value="">Select Nature of Business</option>
                                                @foreach ($industries as $industry)
                                                    @if ($industry->title == old('nature_of_business'))
                                                        <option value="{{ $industry->title }}" selected>
                                                            {{ $industry->title }}</option>
                                                    @else
                                                        <option value="{{ $industry->title }}">{{ $industry->title }}
                                                        </option>
                                                    @endif
                                                @endforeach
                                                <option value="other">Other</option>
                                            </select>


                                            @error('nature_of_business')
                                                <span class="invalid-feedback"
                                                    role="alert"><strong>{{ $message }}</strong></span>
                                            @enderror
                                        </div>

                                        <div class="form-group">
                                            <label class="form-label" for="company_country">Choose country of
                                                company</label>
                                            <select name="company_country" id="company_country"
                                                data-select2-id="company_country" class="form-control select2 w-100"
                                                required>
                                                <option value="">Choose country</option>
                                                @foreach ($nationalities as $nationality)
                                                    @if ($nationality->id == old('company_country'))
                                                        <option value="{{ $nationality->id }}" selected>
                                                            {{ $nationality->name }}</option>
                                                    @else
                                                        <option value="{{ $nationality->id }}">{{ $nationality->name }}
                                                        </option>
                                                    @endif
                                                @endforeach
                                            </select>
                                            @error('company_country')
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
                                                <label for="" class="form-label">Send Emails (below actions will
                                                    be
                                                    done
                                                    on account approval)</label>
                                                <hr>
                                                <div class="custom-control custom-checkbox">
                                                    <input type="checkbox" class="custom-control-input"
                                                        id="send_welcome_email" name="send_welcome_email">
                                                    <label class="custom-control-label" for="send_welcome_email">Welcome
                                                        Email</label>
                                                </div>
                                                <div class="custom-control custom-checkbox mt-3">
                                                    <input type="checkbox" class="custom-control-input" id="send_ssi"
                                                        name="send_ssi">
                                                    <label class="custom-control-label" for="send_ssi">SSI Email</label>
                                                </div>
                                                <div class="custom-control custom-checkbox mt-3">
                                                    <input type="checkbox" class="custom-control-input" id="create_user"
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
@stop
@section('scripts')

    <script>
        var controls = {
            leftArrow: '<i class="fal fa-angle-left" style="font-size: 1.25rem"></i>',
            rightArrow: '<i class="fal fa-angle-right" style="font-size: 1.25rem"></i>'
        }

        $(document).ready(function() {
            // minimum setup
            $('#registration_date').datepicker({
                format: 'dd/mm/yyyy',
                todayHighlight: true,
                orientation: "bottom left",
                templates: controls,
                endDate: "{{ \Carbon\Carbon::today()->format('d/m/Y') }}"
            });

            $('#expiry_date').datepicker({
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
