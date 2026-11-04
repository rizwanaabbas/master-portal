@php
    $selectedValues = array_filter(array_map(
        'trim',
        explode(',', (string) ($value['value'] ?? ''))
    ));
    $optionGroups = $value['option_groups'] ?? null;
@endphp
<div class="form-group mb-0">
    <label class="form-label" for="{{ $key }}">{{ $value['label'] }}</label>
    <select name="configurations[{{ $key }}][]" id="{{ $key }}"
        class="form-control config-multiselect" multiple size="12">
        @if (!empty($optionGroups) && is_array($optionGroups))
            @foreach ($optionGroups as $groupLabel => $groupOptions)
                <optgroup label="{{ $groupLabel }}">
                    @foreach ($groupOptions as $optVal => $optLabel)
                        <option value="{{ $optVal }}"
                            {{ in_array((string) $optVal, $selectedValues, true) ? 'selected' : '' }}>
                            {{ $optLabel }}
                        </option>
                    @endforeach
                </optgroup>
            @endforeach
        @elseif (isset($value['options']))
            @foreach ($value['options'] as $optVal => $optLabel)
                <option value="{{ $optVal }}"
                    {{ in_array((string) $optVal, $selectedValues, true) ? 'selected' : '' }}>
                    {{ $optLabel }}
                </option>
            @endforeach
        @endif
    </select>
    <small class="form-text text-muted">Hold Ctrl/Cmd to select multiple values. Options follow Fireblocks documentation categories.</small>
</div>
