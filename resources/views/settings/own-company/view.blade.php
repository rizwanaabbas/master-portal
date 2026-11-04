@extends('layouts.cp')

@section('title', 'Company Details - Own Company')
@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Own Company</span>
        </h1>
        <a href="{{ url('/settings/own-company/edit/' . $company->id) }}" class="btn btn-primary mr-2">
            <i class="fal fa-edit"></i> Edit
        </a>
        <a href="{{ url('/settings/own-company/theme') }}" class="btn btn-outline-primary mr-2">
            <i class="fal fa-palette"></i> Edit CSS
        </a>
        <a href="{{ url('/settings/own-company/onboarding-pdf') }}" class="btn btn-outline-primary mr-2">
            <i class="fal fa-file-pdf"></i> Onboarding PDF
        </a>
        <form method="post" action="{{ url('/settings/own-company/theme/reset') }}" class="d-inline mr-3"
            onsubmit="return confirm('Reset theme to stock CSS cust-theme-14.css? You can reconfigure colors afterward.');">
            @csrf
            <input type="hidden" name="stock_theme" value="cust-theme-14.css">
            <button type="submit" class="btn btn-outline-danger">
                <i class="fal fa-undo"></i> Reset CSS
            </button>
        </form>
    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false"
        data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                Company Details
            </h2>
        </div>

        <div class="panel-container show mb-3">
            <div class="panel-content" style="overflow: scroll;">
                @include('common.error-message')
                <table class="table table-bordered table-hover m-0 shadow" id="data-grid">
                    <thead class="thead-dark">
                        <tr>
                            <th width="200">Company Name</th>
                            <td>{{ $company->name }}</td>
                        </tr>
                        <tr>
                            <th>Short Name</th>
                            <td>{{ $company->short_name }}</td>
                        </tr>
                        <tr>
                            <th>Chair-Person Email</th>
                            <td>{{ $company->chairperson_email }}</td>
                        </tr>
                        <tr>
                            <th>PoC Name</th>
                            <td>{{ $company->poc_name }}</td>
                        </tr>
                        <tr>
                            <th>PoC Email</th>
                            <td>{{ $company->poc_email }}</td>
                        </tr>
                        <tr>
                            <th>Support Team Emails</th>
                            <td>{{ $company->support_team_emails }}</td>
                        </tr>
                        <tr>
                            <th>Country</th>
                            <td>{{ $company->country }}</td>
                        </tr>
                        <tr>
                            <th>Address</th>
                            <td>{{ $company->address }}</td>
                        </tr>
                        <tr>
                            <th>License Number</th>
                            <td>{{ $company->license_number }}</td>
                        </tr>
                        <tr>
                            <th>Contact Number</th>
                            <td>{{ $company->contact_number }}</td>
                        </tr>
                        <tr>
                            <th>Account Number Prefix</th>
                            <td>{{ $company->account_number_prefix }}</td>
                        </tr>
                        <tr>
                            <th>Tech Email</th>
                            <td>{{ $company->tech_email }}</td>
                        </tr>
                        <tr>
                            <th>Max File Size</th>
                            <td>{{ $company->max_file_size }}</td>
                        </tr>
                        <tr>
                            <th>App Store URl</th>
                            <td>{{ $company->app_store_url }}</td>
                        </tr>
                        <tr>
                            <th>Play Store URl</th>
                            <td>{{ $company->play_store_url }}</td>
                        </tr>
                        <tr>
                            <th>Terms And Conditions URL</th>
                            <td>{{ $company->terms_and_conditions_url }}</td>
                        </tr>
                        <tr>
                            <th>Find Us Links</th>
                            <td>

                                @php $urls = explode(',', $company->find_us); @endphp
                                @if (count($urls) > 0 && $urls[0] !== '')
                                    <div class="d-sm-flex flex-column align-items-center justify-content-center d-md-block">
                                        <div class="d-flex flex-row opacity-70">
                                            @foreach ($urls as $url)
                                                @php
                                                    preg_match('/www\.([a-z]+)\./i', $url, $matches);
                                                    $icon = $matches[1];
                                                @endphp

                                                <a target="_blank" href="{{ $url }}"
                                                    class="mr-2 fs-xxl text-white">
                                                    <i class="fab fa-{{ $icon }} text-dark"></i>
                                                </a>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th valign="middle">Logo</th>
                            <td><img src="{{ $ownCompany ? $ownCompany->logo : '' }}" alt="{{ $company->name }}"
                                    height="100px" /></td>
                        </tr>
                        <tr>
                            <th valign="middle">Client Portal Logo (Light)</th>
                            <td><img src="{{ $ownCompany ? $ownCompany->client_portal_light_logo : '' }}"
                                    alt="{{ $company->name }}" height="100px" /></td>
                        </tr>
                        <tr>
                            <th valign="middle">Client Portal Logo (Dark)</th>
                            <td><img src="{{ $ownCompany ? $ownCompany->client_portal_dark_logo : '' }}"
                                    alt="{{ $company->name }}" height="100px" /></td>
                        </tr>
                        <tr>
                            <th valing="middle">Client Manual</th>
                            <td><a href="{{ $ownCompany ? $ownCompany->client_manual : '' }}" target="_blank">
                                    View Client Manual
                                </a></td>
                        </tr>
                        <tr>
                            <th valign="middle">Favicon</th>
                            <td><img src="{{ $ownCompany ? $ownCompany->favicon : '' }}" alt="{{ $company->name }}"
                                    height="32px" /></td>
                        </tr>
                        <tr>
                            <th valign="middle">Stamp</th>
                            <td><img src="{{ $ownCompany ? $ownCompany->stamp : '' }}" alt="{{ $company->name }}"
                                    height="50px"></td>
                        </tr>
                        <tr>
                            <th valign="middle">Watermark</th>
                            <td><img src="{{ $ownCompany ? $ownCompany->watermark : '' }}" alt="{{ $company->name }}"
                                    height="100px"></td>
                        </tr>
                        <tr>
                            <th valign="middle">Login Page Image</th>
                            <td><img src="{{ $ownCompany ? $ownCompany->login_page_image : '' }}"
                                    alt="{{ $company->name }}" height="100px"></td>
                        </tr>
                        <tr>
                            <th>Theme</th>
                            <td>
                                {{ $company->theme ?: 'Default' }}
                                <a href="{{ url('/settings/own-company/theme') }}" class="btn btn-sm btn-outline-primary ml-2">
                                    <i class="fal fa-palette"></i> Create / Edit theme
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <th>Onboarding PDF</th>
                            <td>
                                @php
                                    $pdfPreset = $company->onboarding_pdf_options['preset'] ?? 'cib';
                                    $pdfPresetLabel = \App\Services\OnboardingPdfOptionsService::PRESET_LABELS[$pdfPreset]
                                        ?? \App\Services\OnboardingPdfOptionsService::PRESET_LABELS['cib']
                                        ?? ucfirst($pdfPreset);
                                @endphp
                                Bank preset: <strong>{{ $pdfPresetLabel }}</strong>
                                <a href="{{ url('/settings/own-company/onboarding-pdf') }}"
                                    class="btn btn-sm btn-outline-primary ml-2">
                                    <i class="fal fa-file-pdf"></i> Configure layout
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <th>Card Colors</th>
                            <td>
                                @if ($ownCompany && $ownCompany->card_colors)
                                    @php
                                        $colors = json_decode($ownCompany->card_colors, true);
                                    @endphp
                                    <div class="d-flex flex-wrap">
                                        @foreach ($colors as $color)
                                            <span class="badge badge-primary mr-2 mb-2">{{ $color }}</span>
                                        @endforeach
                                    </div>
                                @else
                                    No card colors set.
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Chart Color</th>
                            <td>
                                <span class="badge badge-primary">{{ $ownCompany ? $ownCompany->chart_color : 'Default' }}</span>
                            </td>
                        </tr>

                        <tr>
                            <th>Show Introducer Field</th>
                            <td>
                                <span class="badge badge-primary">{{ $ownCompany ? ($ownCompany->show_introducer_field ? 'Yes' : 'No') : 'No' }}</span>
                            </td>
                        </tr>

                        <tr>
                            <th>Maintenance Fee Job</th>
                            <td>
                                <span class="badge badge-primary">{{ $ownCompany ? ($ownCompany->maintenance_fee_job_enabled ? 'Enabled' : 'Disabled') : 'Disabled' }}</span>
                            </td>
                        </tr>
                        
                    </thead>
                </table>
                <hr>
            </div>
        </div>
    </div>

@stop
