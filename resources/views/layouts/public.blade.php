<!DOCTYPE html>
<html lang="en">

<head>
    @include('common.analytics')
    <meta charset="utf-8">
    <title>
        @yield('title') - {{ $ownCompany ? $ownCompany->short_name : '' }}
    </title>
    <meta name="description" content="Login">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no, user-scalable=no, minimal-ui">
    <!-- Call App Mode on ios devices -->
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <!-- Remove Tap Highlight on Windows Phone IE -->
    <meta name="msapplication-tap-highlight" content="no">
    <!-- base css -->
    <link id="vendorsbundle" rel="stylesheet" media="screen, print"
        href="{{ asset('/assets/css/vendors.bundle.css') }}">
    <link id="appbundle" rel="stylesheet" media="screen, print" href="{{ asset('/assets/css/app.bundle.css') }}">
    <link id="mytheme" rel="stylesheet" media="screen, print" href="#">
    <link id="myskin" rel="stylesheet" media="screen, print" href="{{ asset('/assets/css/skins/skin-master.css') }}">

    <!-- Place favicon.ico in the root directory -->
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('/assets/img/favicon/apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32"
        href="{{ $ownCompany ? $ownCompany->favicon : asset('assets/img/favicon/favicon-32x32.png') }}">
    <link rel="mask-icon" href="{{ asset('/assets/img/favicon/safari-pinned-tab.svg') }}" color="#5bbad5">
    <link rel="stylesheet" media="screen, print" href="{{ asset('/assets/css/fa-brands.css') }}">
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
    <link rel="stylesheet" media="screen, print"
        href="{{ asset('assets/css/formplugins/select2/select2.bundle.css') }}">
    <link rel="stylesheet" media="screen, print"
        href="{{ asset('assets/css/formplugins/bootstrap-datepicker/bootstrap-datepicker.css') }}">
    {{-- Own Company theme LAST so badge overrides beat vendors.bundle (_badge.scss). --}}
    @php
        $themeFile = $ownCompany->theme ?? 'cust-theme-14.css';
        $themePath = public_path('assets/css/themes/' . $themeFile);
        $themeVer = is_file($themePath) ? filemtime($themePath) : time();
    @endphp
    <link id="own-company-theme" rel="stylesheet" media="screen, print"
        href="{{ asset('assets/css/themes/' . $themeFile) }}?v={{ $themeVer }}">
    @if (request()->routeIs('register'))
        {{ ReCaptcha::htmlScriptTagJsApi() }}
    @endif
</head>

<body class="mod-skin-light">


    <div class="page-wrapper auth">
        <div class="page-inner bg-brand-gradient">
            <div class="page-content-wrapper bg-transparent m-0">
                <div class="height-10 w-100 shadow-lg px-4 bg-brand-gradient">
                    <div class="d-flex align-items-center container p-0">
                        <div class="d-flex align-items-center justify-content-center m-0 p-0 width-mobile-auto bg-transparent bg-img-none shadow-0 border-0">
                            <a href="javascript:void(0)" class="page-logo-link press-scale-down d-flex align-items-center">
                                <img 
                                    class="mt-2"
                                    src="{{ $ownCompany ? $ownCompany->logo : '' }}"
                                    alt="{{ $ownCompany ? $ownCompany->name : '' }}" 
                                    aria-roledescription="logo"
                                    style="height: 48px; width: auto; max-height: 55px; max-width: 220px; object-fit: contain;"    
                                >
                            </a>
                        </div>

                    </div>
                </div>
                @php
                    // Prefer Own Company login image when set; otherwise shared default art.
                    $loginBg = ($ownCompany && !empty($ownCompany->login_page_image))
                        ? $ownCompany->login_page_image
                        : asset('assets/img/svg/blue_dots.png');
                    $loginBgSize = ($ownCompany && !empty($ownCompany->login_page_image)) ? 'cover' : 'auto';
                    $loginBgPos = ($ownCompany && !empty($ownCompany->login_page_image)) ? 'center center' : 'left bottom';
                @endphp
                <div class="flex-1"
                    style="background: url({{ $loginBg }}) no-repeat {{ $loginBgPos }} fixed; background-size: {{ $loginBgSize }};">
                    <div class="container py-4 py-lg-5 my-lg-5 px-4 px-sm-0">
                        <div class="row">

                            @yield('content')

                        </div>
                        <div class="position-absolute pos-bottom pos-left pos-right p-4 text-center text-white">
                            {{ date('Y') }} © {{ $ownCompany ? $ownCompany->short_name : '' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <script src="{{ asset('/assets/js/vendors.bundle.js') }}"></script>
    <script src="{{ asset('/assets/js/app.bundle.js') }}"></script>
    <script src="{{ asset('assets/js/formplugins/select2/select2.bundle.js') }}"></script>
    <script src="{{ asset('assets/js/dependency/moment/moment.js') }}"></script>
    <script src="{{ asset('assets/js/miscellaneous/fullcalendar/fullcalendar.bundle.js') }}"></script>
    <script src="{{ asset('assets/js/formplugins/bootstrap-datepicker/bootstrap-datepicker.js') }}"></script>
    <script>
        $(document).ready(function() {
            $('#passport-expiry').datepicker({
                format: 'dd/mm/yyyy',
                todayHighlight: true,
                autoclose: true,
                orientation: "bottom left",
                startDate: "{{ \Carbon\Carbon::today()->format('d/m/Y') }}"
            });
            $('#dob').datepicker({
                format: 'dd/mm/yyyy',
                todayHighlight: true,
                autoclose: true,
                orientation: "bottom left",
                endDate: '+0d'

            });

        });
        $("#js-login-btn").click(function(event) {

            // Fetch form to apply custom Bootstrap validation
            var form = $("#js-login")

            if (form[0].checkValidity() === false) {
                event.preventDefault()
                event.stopPropagation()
            }

            form.addClass('was-validated');
            // Perform ajax submit here...
        });

        $(document).ready(function() {
            $('.select2').select2();
        })
    </script>
    @yield('scripts')


</body>
<!-- END Body -->

</html>