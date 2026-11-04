@php
    $hint = $hint ?? '';
    $sample = $sample ?? '';
    $fieldKey = $fieldKey ?? uniqid('sweep-hint-');
@endphp

@if ($hint !== '' || $sample !== '')
    <button type="button"
        class="btn btn-sm btn-icon sweep-field-help-btn ml-1 align-middle"
        title="Field help"
        data-hint-id="sweep-hint-{{ $fieldKey }}"
        data-field-label="{{ $label ?? 'Field' }}"
        aria-label="Help: {{ $label ?? 'Field' }}">
        <i class="fal fa-question-circle"></i>
    </button>
    <template id="sweep-hint-{{ $fieldKey }}">
        @if ($hint !== '')
            <p class="mb-3 text-muted">{{ $hint }}</p>
        @endif
        @if ($sample !== '')
            <div>
                <strong class="d-block mb-1">Sample value</strong>
                <code class="d-inline-block px-2 py-1 bg-light rounded">{{ $sample }}</code>
            </div>
        @endif
    </template>
@endif
