@php
    $prefix = $prefix ?? '';
    $showAttributeName = $showAttributeName ?? true;
    $attributeReadonly = $attributeReadonly ?? false;
@endphp

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label for="{{ $prefix }}field-type-dropdown" class="form-label">Field Type</label>
            <select id="{{ $prefix }}field-type-dropdown" class="form-control config-field-type-select">
                <option value="" selected>Choose Type</option>
                <option value="text">Text</option>
                <option value="number">Number</option>
                <option value="checkbox">Checkbox</option>
                <option value="password">Password</option>
                <option value="textarea">Textarea</option>
                <option value="select">Select</option>
                <option value="multiselect">Multiselect</option>
            </select>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="{{ $prefix }}field-attribute-label" class="form-label">Label</label>
            <input class="form-control" type="text" id="{{ $prefix }}field-attribute-label">
        </div>
    </div>
</div>

@if ($showAttributeName)
    <div class="row mt-3">
        <div class="col-md-6">
            <div class="form-group">
                <label for="{{ $prefix }}field-attribute-input" class="form-label">Attribute Name (key)</label>
                <input class="form-control" type="text" id="{{ $prefix }}field-attribute-input"
                    @if ($attributeReadonly) readonly @endif>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="{{ $prefix }}field-placeholder" class="form-label">Placeholder</label>
                <input class="form-control" type="text" id="{{ $prefix }}field-placeholder">
            </div>
        </div>
    </div>
@else
    <div class="row mt-3">
        <div class="col-md-6">
            <div class="form-group mb-0">
                <label class="form-label">Attribute Name (key)</label>
                <input class="form-control" type="text" id="{{ $prefix }}field-attribute-input" readonly>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group mb-0">
                <label for="{{ $prefix }}field-placeholder" class="form-label">Placeholder</label>
                <input class="form-control" type="text" id="{{ $prefix }}field-placeholder">
            </div>
        </div>
    </div>
@endif

<div class="row mt-3">
    <div class="col-md-12">
        <div class="form-group">
            <label for="{{ $prefix }}field-hint" class="form-label">Help Hint Text</label>
            <textarea id="{{ $prefix }}field-hint" class="form-control" rows="3"
                placeholder="Help text shown when the user clicks the ? icon on this field"></textarea>
            <small class="form-text text-muted">Displayed in the help modal when users click the question-mark icon.</small>
        </div>
    </div>
</div>

<div class="row mt-3">
    <div class="col-md-6">
        <div class="form-group">
            <label for="{{ $prefix }}field-sample" class="form-label">Sample Value</label>
            <input class="form-control" type="text" id="{{ $prefix }}field-sample"
                placeholder="Example value shown in help modal">
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="{{ $prefix }}field-doc-url" class="form-label">Documentation URL</label>
            <input class="form-control" type="url" id="{{ $prefix }}field-doc-url"
                placeholder="https://docs.example.com/...">
        </div>
    </div>
</div>

<div class="row mt-3 config-field-options-row d-none">
    <div class="col-md-12">
        <div class="form-group mb-0">
            <label for="{{ $prefix }}field-options" class="form-label">Select Options</label>
            <textarea id="{{ $prefix }}field-options" class="form-control" rows="4"
                placeholder="value=Label&#10;another_value=Another Label"></textarea>
            <small class="form-text text-muted">One option per line. Format: <code>value=Label</code></small>
        </div>
    </div>
</div>
