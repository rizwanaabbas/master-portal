{{-- Label + optional ? tooltip. Expects $label; optional $hint, $width (e.g. 40%). --}}
<th @if (!empty($width)) style="width:{{ $width }}" @endif>
    {{ $label }}
    @if (!empty($hint))
        <a href="#"
            class="fb-detail-field-hint text-muted ml-1"
            tabindex="0"
            data-toggle="tooltip"
            data-placement="auto"
            title="{{ $hint }}"
            onclick="return false;"
            aria-label="Help: {{ $label }}">
            <i class="fal fa-question-circle"></i>
        </a>
    @endif
</th>
