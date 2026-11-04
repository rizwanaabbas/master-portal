@extends('layouts.cp')
@section('title', 'Company Client')
@section('styles')
    <link rel="stylesheet" media="screen, print"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="{{ asset('css/client-on-boarding.css') }}">
@stop
@section('content')
    @php
        $onboardingEnabled = in_array('Onboarding', $enabledModules);
    @endphp
    @include('common.error-message')
    @if ($onboardingEnabled)
        <div class="row" style="display: flex; justify-content: center; align-items: center; max-height: 25px; ">
            <div class="text" style="text-align: center;">
                <div class="top-side">
                    <span id="step-name" style="padding-right: 5px; font-weight:500; font-size:18px;">Personal
                    </span>
                    <span style="
            font-size:13px;
            font-weight:400;
            color:#cbced4;">
                        Step <span class="step-number">1</span> <span>/6</span>
                    </span>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="container onboarding-container" style="padding-bottom: 50vh;">
                    <div class="card" style="width: 100%;">
                        <div class="form">
                            <div class="left-side">
                                <div class="left-heading">
                                    <h3>Complete Profile</h3>
                                </div>
                                <div class="steps-content">
                                    <h3>Step <span class="step-number">1</span></h3>
                                </div>
                                <ul class="wz-progress-bar">
                                    <li class="active">Personal</li>
                                    <li>Company</li>
                                    <li>Facial Recognition</li>
                                    <li>Currencies</li>
                                    <li>Signature</li>
                                    <li>Related Files</li>
                                    {{-- <li>Submit</li> --}}
                                </ul>
                            </div>
                            <div class="right-side">
                                <form id="form1" method="POST" action="{{ url('/client-profile/company/store') }}"
                                    enctype="multipart/form-data">
                                    @csrf
                                    <!-- employer form -->
                                    @include('draft-clients.partials.company-contact')

                                    <!-- Personal information -->
                                    @include('draft-clients.partials.company')

                                    <!-- client liveness -->
                                    @include('draft-clients.partials.facial-recognition')

                                    <!-- account currencies -->
                                    @include('draft-clients.partials.currency')

                                    <!-- client signature -->
                                    @include('draft-clients.partials.signature')

                                    <!-- files -->
                                </form>

                                @include('draft-clients.partials.files')
                                {{--                             <!-- Verify Details and Submit -->
                            @include('draft-clients.partials.submit')
 --}}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <form method="POST" name="delete-file" id="delete-file" action="{{ url('/clients/company/files/delete') }}">
            @csrf
            <input type="hidden" name="client_id" id="client_id" value="{{ $client->id }}">
            <input type="hidden" name="file_id" id="file_id" value="">
        </form>
    @else
        <div class="row">
            <div class="col-md-12">
                <div class="card p-6 mt-5 rounded-plus">
                    <div class="row">
                        <div class="col-md-12">
                            <h1 class="text-center">Client Registeration</h1>
                            <p class="text-center">If you want to complete the registration, please download the
                                {{ $ownCompany ? $ownCompany->short_name : '' }} Mobile
                                application and signup there.
                            </p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 text-center">
                            <div class="d-flex flex-column flex-md-row justify-content-center align-items-center">
                                <a href="{{ $ownCompany ? $ownCompany->app_store_url : '' }}"
                                    class="btn btn-primary mb-3 mb-md-0">
                                    <img src="{{ asset('assets/img/apple.png') }}" alt="google play" width="40">
                                    <span class="d-inline-block text-left align-middle">
                                        <small class="font-weight-normal d-block">Download on the</small>
                                        <b class="text-uppercase">App Store</b>
                                    </span>
                                </a>
                                <a href="{{ $ownCompany ? $ownCompany->play_store_url : '' }}"
                                    class="btn btn-primary ml-0 ml-md-2 mt-3 mt-md-0">
                                    <img src="{{ asset('assets/img/google-play.png') }}" alt="google play" width="40">
                                    <span class="d-inline-block text-left text-uppercase align-middle">
                                        <small class="font-weight-normal d-block">Get it on the</small>
                                        <b class="text-uppercase">Google Play</b>
                                    </span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
@stop
@section('scripts')
    <script>
        var liveness_base_url = "{{ env('AWS_AMPLIFY_LIVENESS_URL') }}"

        $(document).ready(function() {
            let step = {{ session('formnumber') ?? 0 }};
            formnumber = 0;
            for (let i = 0; i < step; i++) {
                formnumber++;
                updateform();
                progress_forward();
            }
        })

        var controls = {
            leftArrow: '<i class="fal fa-angle-left" style="font-size: 1.25rem"></i>',
            rightArrow: '<i class="fal fa-angle-right" style="font-size: 1.25rem"></i>'
        }

        $(document).ready(function() {
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

        function submitForApproval() {

            Swal.fire({
                title: "Are you sure?",
                text: "You manager will be notified for client approval.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Yes, Submit it!"
            }).then(function(result) {
                if (result.value) {
                    document.getElementById('submit-for-approval').submit()
                }
            });
        }

        function deleteFile(id) {

            Swal.fire({
                title: "Are you sure?",
                text: "File will be deleted permanently",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Yes, delete it!"
            }).then(function(result) {
                if (result.value) {
                    $("#file_id").val(id);
                    document.getElementById('delete-file').submit()
                }
            });
        }

        function setFileCount(obj, maxcount) {

            var file_count = $(obj)[0].files.length;

            console.log($(obj)[0]);

            if (file_count > maxcount) {

                Swal.fire({
                    title: "Too much files!",
                    text: "You can upload up to " + maxcount + " files maximum",
                    icon: "error",
                    showCancelButton: false,
                    confirmButtonText: "ok"
                }).then(function(result) {
                    $(obj).val('');
                    return;
                });
            }
        }

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
    <script src="{{ asset('js/client-on-boarding.js') }}"></script>
@stop
