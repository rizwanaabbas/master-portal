@php
    use App\Helpers\ModuleConfigurationSecretField;

    $hasStoredSecret = !empty($value['_has_stored_secret']);
    $secretMask = ModuleConfigurationSecretField::MASK;
    $useTextarea = ($value['type'] ?? '') === 'textarea';
    $isRequired = !$hasStoredSecret && (!isset($value['required']) || $value['required'] !== false);
@endphp

<div class="form-group mb-0">
    <label class="form-label" for="{{ $key }}">{{ $value['label'] }}</label>

    @if ($useTextarea)
        <textarea name="configurations[{{ $key }}]"
            id="{{ $key }}"
            rows="6"
            class="form-control config-secret-field"
            data-has-stored-value="{{ $hasStoredSecret ? '1' : '0' }}"
            data-mask="{{ $secretMask }}"
            placeholder="{{ $hasStoredSecret ? $secretMask : ($value['placeholder'] ?? ($value['sample'] ?? 'Enter value')) }}"
            autocomplete="off"
            @if ($isRequired) required @endif></textarea>
    @else
        <div class="input-group">
            <input type="password"
                name="configurations[{{ $key }}]"
                id="{{ $key }}"
                value=""
                class="form-control config-secret-field"
                data-has-stored-value="{{ $hasStoredSecret ? '1' : '0' }}"
                data-mask="{{ $secretMask }}"
                placeholder="{{ $hasStoredSecret ? $secretMask : ($value['placeholder'] ?? ($value['sample'] ?? 'Enter value')) }}"
                autocomplete="new-password"
                @if ($isRequired) required @endif>
            <div class="input-group-append">
                <span class="input-group-text">
                    <i class="fal fa-eye-slash toggle-password" id="toggle-{{ $key }}"></i>
                </span>
            </div>
        </div>
    @endif

    @if ($hasStoredSecret)
        <small class="form-text text-muted">A value is already saved and hidden for security. Enter a new value only if you want to replace it.</small>
    @endif
</div>
