@if (!isset($pdfOptions['features']['show_license_in_title']) || !empty($pdfOptions['features']['show_license_in_title']))
    @if (!empty($ownCompany->license_number))
        ({{ $ownCompany->license_number }})
    @endif
@endif
