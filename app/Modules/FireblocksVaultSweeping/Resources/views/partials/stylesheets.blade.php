@php
    $sheets = $sheets ?? [];
    $basePath = 'assets/css/fireblocks-vault-sweeping';
@endphp
@foreach ($sheets as $sheet)
<link rel="stylesheet" media="screen, print" href="{{ asset($basePath . '/' . ltrim($sheet, '/')) }}">
@endforeach
