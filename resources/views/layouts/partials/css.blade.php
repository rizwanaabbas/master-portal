<link id="vendorsbundle" rel="stylesheet" media="screen, print" href="{{ asset('assets/css/vendors.bundle.css') }}">
<link id="appbundle" rel="stylesheet" media="screen, print" href="{{ asset('assets/css/app.bundle.css') }}">
{{-- <link id="mytheme" rel="stylesheet" media="screen, print" href="#"> --}}
<link id="myskin" rel="stylesheet" media="screen, print" href="{{ asset('assets/css/skins/skin-master.css') }}">
<link id="myskin" rel="stylesheet" media="screen, print"
    href="{{ asset('assets/css/themes/' . ($ownCompany->theme ?? 'cust-theme-14.css')) }}">
<!-- Place favicon.ico in the root directory -->
<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('assets/img/favicon/apple-touch-icon.png') }}">
<link rel="icon" type="image/png" sizes="32x32"
    href="{{ $ownCompany ? $ownCompany->favicon : asset('assets/img/favicon/favicon-32x32.png') }}">
<link rel="mask-icon" href="{{ asset('assets/img/favicon/safari-pinned-tab.svg') }}" color="#5bbad5">
<link rel="stylesheet" media="screen, print" href="{{ asset('assets/css/miscellaneous/reactions/reactions.css') }}">
<link rel="stylesheet" media="screen, print" href="{{ asset('assets/css/miscellaneous/jqvmap/jqvmap.bundle.css') }}">
<link rel="stylesheet" media="screen, print"
    href="{{ asset('assets/css/notifications/sweetalert2/sweetalert2.bundle.css') }}">

<link rel="stylesheet" media="screen, print" href="{{ asset('assets/css/formplugins/select2/select2.bundle.css') }}">
{{-- <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.6.0/main.min.css"> --}}
<link rel="stylesheet" media="screen, print"
    href="{{ asset('assets/css/miscellaneous/fullcalendar/fullcalendar.bundle.css') }}">
<link rel="stylesheet" media="screen, print" href="{{ asset('assets/css/statistics/chartjs/chartjs.css') }}">
<link rel="stylesheet" media="screen, print"
    href="{{ asset('assets/css/formplugins/bootstrap-datepicker/bootstrap-datepicker.css') }}">
<link rel="stylesheet" media="screen, print"
    href="{{ asset('assets/css/datagrid/datatables/datatables.bundle.css') }}">
<link rel="stylesheet" href="{{ asset('css/custom.css') }}">
<link rel="stylesheet" media="screen, print" href="{{ asset('/assets/css/fa-brands.css') }}">
<link rel="stylesheet" href="{{ asset('assets/css/jquery.simplegauge.css') }}">
