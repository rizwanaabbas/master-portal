@php
    $optionsText = '';
    if (!empty($value['options']) && is_array($value['options'])) {
        $lines = [];
        foreach ($value['options'] as $optVal => $optLabel) {
            $lines[] = $optVal . '=' . $optLabel;
        }
        $optionsText = implode("\n", $lines);
    }
@endphp
<div class="field-meta-inputs d-none">
    <input type="hidden" name="field_meta[{{ $key }}][label]" value="{{ $value['label'] ?? '' }}" class="field-meta-label">
    <input type="hidden" name="field_meta[{{ $key }}][type]" value="{{ $value['type'] ?? 'text' }}" class="field-meta-type">
    <textarea name="field_meta[{{ $key }}][hint]" class="field-meta-hint d-none" rows="1">{{ $value['hint'] ?? '' }}</textarea>
    <input type="hidden" name="field_meta[{{ $key }}][doc_url]" value="{{ $value['doc_url'] ?? '' }}" class="field-meta-doc-url">
    <input type="hidden" name="field_meta[{{ $key }}][sample]" value="{{ $value['sample'] ?? '' }}" class="field-meta-sample">
    <input type="hidden" name="field_meta[{{ $key }}][placeholder]" value="{{ $value['placeholder'] ?? '' }}" class="field-meta-placeholder">
    <textarea name="field_meta[{{ $key }}][options_text]" class="field-meta-options d-none" rows="1">{{ $optionsText }}</textarea>
</div>
