{{-- Inline ? tooltip. Expects $hint; optional $light for dark/summary backgrounds. --}}
@if (!empty($hint))
    <a href="#"
        class="fb-detail-field-hint ml-1 {{ !empty($light) ? 'text-white-50' : 'text-muted' }}"
        tabindex="0"
        data-toggle="tooltip"
        data-placement="auto"
        title="{{ $hint }}"
        onclick="return false;"
        aria-label="Help">
        <i class="fal fa-question-circle"></i>
    </a>
@endif
