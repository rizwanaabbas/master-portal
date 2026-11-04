<form id="zenus-wizard-fix-form" enctype="multipart/form-data">
    @csrf
    @forelse ($missing as $field)
        <div class="form-group {{ $loop->last ? 'mb-0' : '' }}">
            <label class="form-label text-danger" for="zw-{{ $field['key'] }}">
                {{ $field['label'] }}
                <span class="font-weight-bold">— This is required</span>
            </label>
            @if (($field['type'] ?? '') === 'select')
                <select name="{{ $field['key'] }}" id="zw-{{ $field['key'] }}" class="form-control is-invalid" required>
                    <option value="">Select {{ strtolower($field['label']) }}</option>
                    @foreach ($socCodes as $code)
                        <option value="{{ $code->soc_code }}">{{ $code->job_title }} ({{ $code->soc_code }})</option>
                    @endforeach
                </select>
            @elseif (($field['type'] ?? '') === 'file')
                <input type="file" name="{{ $field['key'] }}{{ !empty($field['multiple']) ? '[]' : '' }}"
                    id="zw-{{ $field['key'] }}" class="form-control is-invalid" required
                    @if (!empty($field['multiple'])) multiple @endif>
            @elseif (($field['type'] ?? '') === 'date')
                <input type="date" name="{{ $field['key'] }}" id="zw-{{ $field['key'] }}"
                    class="form-control is-invalid" required value="{{ $field['value'] ?? '' }}">
            @else
                <input type="text" name="{{ $field['key'] }}" id="zw-{{ $field['key'] }}"
                    class="form-control is-invalid" required value="{{ $field['value'] ?? '' }}">
            @endif
            <div class="invalid-feedback d-block">{{ $field['reason'] }}</div>
        </div>
    @empty
        <p class="mb-0 text-muted">No missing required fields.</p>
    @endforelse
</form>
