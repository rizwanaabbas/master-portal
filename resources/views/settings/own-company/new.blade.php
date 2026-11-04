@extends('layouts.cp')
@section('title', 'Add Company Details - Own Company')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Own Company</span>
        </h1>
        <a href="{{ url('/settings/own-company-details') }}" class="btn btn-primary mr-3"><i class="fal fa-arrow-alt-left"></i>
            Go Back </a>
    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false"
        data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                Add Company Details
            </h2>
        </div>

        <div class="panel-container show">
            <div class="panel-content">
                @include('common.error-message')

                <form method="post" action="{{ url('/settings/own-company/store') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label" for="name">Name</label>
                                <input type="text" autofocus id="name" name="name" value=""
                                    class="form-control" required>
                                @error('name')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="short_name">Short Name</label>
                                <input type="text" id="short_name" name="short_name" value="" class="form-control"
                                    required>
                                @error('short_name')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="chairperson_email">Chair-Person Email</label>
                                <input type="email" id="chairperson_email" name="chairperson_email" value=""
                                    class="form-control" required>
                                @error('chairperson_email')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="poc_name">PoC Name</label>
                                <input type="text" id="poc_name" name="poc_name" value="" class="form-control"
                                    required>
                                @error('poc_name')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="poc_email">PoC Email</label>
                                <input type="email" id="poc_email" name="poc_email" value="" class="form-control"
                                    required>
                                @error('poc_email')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="support_team_emails">Support Team Emails</label>
                                <input type="text" id="support_team_emails" name="support_team_emails" value=""
                                    class="form-control" required>
                                @error('support_team_emails')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="country">Country</label>
                                <input type="text" id="country" name="country" value="" class="form-control"
                                    required>
                                @error('country')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="account_number_prefix">Account Number Prefix</label>
                                <input type="text" id="account_number_prefix" name="account_number_prefix" value=""
                                    class="form-control">
                                @error('account_number_prefix')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="stamp">Stamp</label><br />
                                <label for="stamp" class="btn btn-light">Upload Stamp</label>
                                <span id="file-name-display-stamp"> choose jpg/png, recommended size 100x100</span>
                                <input type="file" name="stamp" accept="image/*" onchange="setFile(this)"
                                    id="stamp" class="invisible item-file" />
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="watermark">Watermark</label><br />
                                <label for="watermark" class="btn btn-light">Upload Watermark</label>
                                <span id="file-name-display-watermark"> choose jpg/png, recommended size 100x100</span>
                                <input type="file" name="watermark" accept="image/*" onchange="setFile(this)"
                                    id="watermark" class="invisible item-file" />
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="login_page_image">Login Page Image</label><br />
                                <label for="login_page_image" class="btn btn-light">Upload Login Page Image</label>
                                <span id="file-name-display-login_page_image"> choose gif, recommended size
                                    527x684</span>
                                <input type="file" name="login_page_image" accept="image/*" onchange="setFile(this)"
                                    id="login_page_image" class="invisible item-file" />
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="theme">Theme</label>
                                <input type="text" id="theme" name="theme" value=""
                                    class="form-control">
                                <small>Enter the theme Name e.g. cust-theme-14.css</small>
                                @error('theme')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="card_colors">Card Colors</label>
                                <input type="text" id="card_colors" name="card_colors" value=""
                                    class="form-control">
                                <small>Enter the card colors e.g. ["bg-danger-200", "bg-info-200", "bg-warning-200",
                                    "bg-primary-200"]</small>
                                @error('card_colors')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="chart_color">Chart Color</label>
                                <input type="text" id="chart_color" name="chart_color" value=""
                                    class="form-control">
                                <small>Enter the chart color e.g. #ffcc00</small>
                                @error('chart_color')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="maintenance_fee_job_enabled">Maintenance Fee Job</label>
                                <div class="custom-control custom-switch">
                                    <input class="custom-control-input" type="checkbox" id="maintenance_fee_job_enabled"
                                        name="maintenance_fee_job_enabled" checked>
                                    <label class="custom-control-label" for="maintenance_fee_job_enabled">
                                        Check to run the monthly maintenance fee deduction job (1st of each month, 23:30).
                                    </label>
                                </div>
                                @error('maintenance_fee_job_enabled')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">

                            <div class="form-group">
                                <label class="form-label" for="max_file_size">Max File Size (for File Upload)</label>
                                <input type="number" id="max_file_size" name="max_file_size" value=""
                                    class="form-control" required>
                                <small>>Enter in bytes</small>
                                @error('max_file_size')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="tech_email">Tech Email</label>
                                <input type="email" id="tech_email" name="tech_email" value=""
                                    class="form-control" required>
                                @error('tech_email')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="find_us">Find Us Links</label>
                                <input type="text" id="find_us" name="find_us" value=""
                                    class="form-control">
                                <small>Comma separated URL's</small>
                                @error('find_us')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="contact_number">Contact Number</label>
                                <input type="text" id="contact_number" name="contact_number" value=""
                                    class="form-control" required>
                                @error('contact_number')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="address">Address</label>
                                <textarea class="form-control" required name="address" id="address" cols="30" rows="2"
                                    placeholder="Complete address of the company."></textarea>
                                @error('address')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="license_number">License Number</label>
                                <input type="text" id="license_number" name="license_number" value=""
                                    class="form-control" required>
                                @error('license_number')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="app_store_url">App Store URL</label>
                                <input type="text" id="app_store_url" name="app_store_url" value=""
                                    class="form-control" required>
                                @error('app_store_url')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="play_store_url">Play Store URL</label>
                                <input type="text" id="play_store_url" name="play_store_url" value=""
                                    class="form-control" required>
                                @error('play_store_url')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="terms_and_conditions_url">Terms And Conditions URL</label>
                                <input type="text" id="terms_and_conditions_url" name="terms_and_conditions_url"
                                    value="" class="form-control" required>
                                @error('terms_and_conditions_url')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="logo">Logo</label><br />
                                <label for="logo" class="btn btn-light">Upload Logo</label>
                                <span id="file-name-display-logo"> choose jpg/png, recommended size 32x32</span>
                                <input type="file" name="logo" accept="image/*" onchange="setFile(this)"
                                    id="logo" class="invisible item-file" />
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="client_portal_light_logo">Client Portal Logo
                                    (Light)</label><br />
                                <label for="client_portal_light_logo" class="btn btn-light">Upload Client Portal Logo
                                    (Light)</label>
                                <span id="file-name-display-client_portal_light_logo"> choose jpg/png, recommended size
                                    134x42</span>
                                <input type="file" name="client_portal_light_logo" accept="image/*"
                                    onchange="setFile(this)" id="client_portal_light_logo" class="invisible item-file" />
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="client_portal_dark_logo">Client Portal Logo
                                    (Dark)</label><br />
                                <label for="client_portal_dark_logo" class="btn btn-light">Upload Client Portal Logo
                                    (Dark)</label>
                                <span id="file-name-display-client_portal_dark_logo"> choose jpg/png, recommended size
                                    134x42</span>
                                <input type="file" name="client_portal_dark_logo" accept="image/*"
                                    onchange="setFile(this)" id="client_portal_dark_logo" class="invisible item-file" />
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="client_manual">Client Manual</label><br />
                                <label for="client_manual" class="btn btn-light">Upload Client Manual</label>
                                <span id="file-name-display-client_manual"> choose pdf</span>
                                <input type="file" name="client_manual" accept="application/pdf"
                                    onchange="setFile(this)" id="client_manual" class="invisible item-file" />
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="favicon">Favicon</label><br />
                                <label for="favicon" class="btn btn-light">Upload Favicon</label>
                                <span id="file-name-display-favicon"> choose png/svg, recommended size 32x32</span>
                                <input type="file" name="favicon" accept="image/*" onchange="setFile(this)"
                                    id="favicon" class="invisible item-file" />
                            </div>

                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <hr>
                                <button type="submit" class="btn btn-primary">Submit</button>
                            </div>
                        </div>
                    </div>
                </form>

            </div>
        </div>
    </div>
@stop
@section('scripts')
    <script>
        function setFile(obj) {

            var file = obj.files[0];
            var filename = file.name;
            document.getElementById("file-name-display-" + obj.name).innerHTML = filename;
        }
    </script>
@stop
