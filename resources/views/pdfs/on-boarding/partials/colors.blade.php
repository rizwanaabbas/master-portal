{{--
  Resolve PDF palette (system-core defaults, Own Company overrides).
  Always emit hex — browsers ignore bgcolor="rgb(...)" in live HTML preview.
--}}
@php
    if (!isset($pdfColors) || !is_array($pdfColors)) {
        $pdfColors = app(\App\Services\OnboardingPdfOptionsService::class)
            ->colors(isset($pdfOptions) && is_array($pdfOptions) ? $pdfOptions : []);
    }
@endphp
<style type="text/css">
    /* Force light scheme so OS dark mode does not invert live preview */
    :root { color-scheme: light; }
    html, body {
        background-color: {{ $pdfColors['page_bg'] }} !important;
        color: {{ $pdfColors['text'] }};
    }
    body {
        font-family: Arial, Helvetica, sans-serif;
        color: {{ $pdfColors['text'] }};
    }
    table.border {
        border-top: 1px solid {{ $pdfColors['muted'] }};
        border-left: 1px solid {{ $pdfColors['muted'] }};
    }
    table.border td {
        border-right: 1px solid {{ $pdfColors['muted'] }};
        border-bottom: 1px solid {{ $pdfColors['muted'] }};
    }
    td.blueTitle,
    h3.blueTitle {
        background: {{ $pdfColors['title_bar'] }} !important;
        color: {{ $pdfColors['title_bar_text'] }} !important;
    }
    .blue {
        color: {{ $pdfColors['title'] }} !important;
    }
    ul.square li::before {
        border: 1px solid {{ $pdfColors['muted'] }};
    }
    .pdf-section-bg {
        background-color: {{ $pdfColors['section'] }} !important;
    }
    .pdf-accent-bg {
        background-color: {{ $pdfColors['accent'] }} !important;
        color: {{ $pdfColors['title_bar_text'] }} !important;
    }
    .pdf-border-bg {
        background-color: {{ $pdfColors['border'] }} !important;
    }
    .pdf-page-bg {
        background-color: {{ $pdfColors['page_bg'] }} !important;
    }
    .pdf-alert {
        color: {{ $pdfColors['alert'] }} !important;
    }
    .pdf-highlight {
        color: {{ $pdfColors['highlight'] }} !important;
    }
</style>
