@php
    $hasHint = !empty($field['hint']) || !empty($field['usage']) || !empty($field['sample']) || !empty($field['doc_url']);
    $mode = $mode ?? 'content';
    $docLabel = $field['doc_label'] ?? 'Documentation';
@endphp

@if ($hasHint && $mode === 'trigger')
    <button type="button"
        class="btn btn-sm btn-icon config-field-help-btn"
        title="Field help"
        data-hint-id="config-hint-{{ $fieldKey }}"
        data-field-label="{{ $field['label'] ?? $fieldKey }}"
        aria-label="Help: {{ $field['label'] ?? $fieldKey }}">
        <i class="fal fa-question-circle"></i>
    </button>
@endif

@if ($hasHint && $mode === 'content')
    <template id="config-hint-{{ $fieldKey }}">
        @if (!empty($field['hint']))
            <p class="mb-3 text-muted">{{ $field['hint'] }}</p>
        @endif
        @if (!empty($field['usage']))
            <div class="mb-3">
                <strong class="d-block mb-1">Where this is used</strong>
                <p class="mb-0 text-muted">{{ $field['usage'] }}</p>
            </div>
        @endif
        @if (!empty($field['sample']))
            <div class="mb-3">
                <strong class="d-block mb-1">Sample value</strong>
                <code class="d-inline-block px-2 py-1 bg-light rounded">{{ $field['sample'] }}</code>
            </div>
        @endif
        @if (!empty($field['doc_url']))
            <div>
                <a href="{{ $field['doc_url'] }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-primary">
                    {{ $docLabel }} <i class="fal fa-external-link ml-1"></i>
                </a>
            </div>
        @endif
    </template>
@endif
