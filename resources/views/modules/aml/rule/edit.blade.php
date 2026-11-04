@extends('layouts.cp')
@section('title', 'AML Management - Edit Rule')

@section('content')

<div class="subheader">
    <h1 class="subheader-title">
        <span class='fw-300'>Edit AML Rule</span>
    </h1>
    <div class="d-flex">
        <a href="{{ url('/settings/aml') }}" class="btn btn-primary ml-2 mx-md-2">
            <i class="fal fa-arrow-alt-left"></i> Go Back
        </a>
    </div>
</div>

<div class="">
    <h1>Edit AML Rule</h1>
    <form id="aml-form" action="{{ url('/settings/aml/update', $rule->id) }}" method="POST">
        @csrf
     
        <div class="form-group">
            <label for="data_id">Data Set</label>
            <select name="data_id" id="data_id" class="form-control select2" required>
                <option value="">Select Data Set</option>
                @foreach ($amlData as $data_set)
                    <option value="{{ $data_set->id }}" {{ $data_set->id == $rule->data_id ? 'selected' : '' }}>
                        {{ $data_set->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="rule_name">Rule Name</label>
            <input type="text" name="rule_name" id="rule_name" class="form-control" value="{{ $rule->rule_name }}" required>
        </div>
        <div class="form-group">
            <label for="rule_description">Rule Description</label>
            <input type="text" name="rule_description" id="rule_description" class="form-control" value="{{ $rule->rule_description }}" required>
        </div>
        <div class="form-group">
            <label for="field">Field</label>
            <select name="field" id="field" class="form-control select2" required>
                <option value="">Choose Field</option>
            </select>
        </div>
        <div class="form-group">
            <label for="criteria">Criteria</label>
            <select name="rule_criteria" id="criteria" class="form-control select2" required>
                <option value="">Choose Criteria</option>
                <option value="=" {{ $rule->rule_criteria == '=' ? 'selected' : '' }} > = </option>
                <option value="<>" {{ $rule->rule_criteria == '<>' ? 'selected' : '' }} > &lt;&gt; </option>
                <option value=">" {{ $rule->rule_criteria == '>' ? 'selected' : '' }}> &gt; </option>
                <option value=">=" {{ $rule->rule_criteria == '>=' ? 'selected' : '' }}> &gt;= </option>
                <option value="<" {{ $rule->rule_criteria == '<' ? 'selected' : '' }}> &lt; </option>
                <option value="<=" {{ $rule->rule_criteria == '<=' ? 'selected' : '' }}> &lt;= </option>
                <option value="in" {{ $rule->rule_criteria == 'in' ? 'selected' : '' }}> in </option>
                <option value="between" {{ $rule->rule_criteria == 'between' ? 'selected' : '' }}> Between </option>
                <option value="like" {{ $rule->rule_criteria == 'like' ? 'selected' : '' }}> like </option>
            </select>
        </div>
        <div class="form-group" id="country-text">
            <label for="value">Value</label>
            <input type="text" name="value" id="value" class="form-control" value="{{ $rule->value }}">
        </div>
       
        <div class="form-group" id="country-dropdown" style="display: none;">
            <label for="country_id">Country</label>
            <select name="country_id[]" id="country_id" class="form-control select2" multiple>
                @foreach ($nationalities as $nationality)

                    <option value="{{ $nationality->id }}" @if(is_array(json_decode($rule->value,true))) {{ in_array($nationality->id, json_decode($rule->value,true) ?? []) ? 'selected' : '' }} @endif>
                        {{ $nationality->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="status">Result</label>
            <select name="status" id="status" class="form-control select2" required>
                <option value="">Choose Status</option>
                <option value="rejected" {{ $rule->status == 'rejected' ? 'selected' : '' }}>Rejected</option>
                <option value="blacklisted" {{ $rule->status == 'blacklisted' ? 'selected' : '' }}>Blacklisted</option>
                <option value="pass" {{ $rule->status == 'pass' ? 'selected' : '' }}>Pass</option>
                <option value="normal_risk" {{ $rule->status == 'normal_risk' ? 'selected' : '' }}>Normal Risk</option>
                <option value="low_risk" {{ $rule->status == 'low_risk' ? 'selected' : '' }}>Low Risk</option>
                <option value="medium_risk" {{ $rule->status == 'medium_risk' ? 'selected' : '' }}>Medium Risk</option>
                <option value="high_risk" {{ $rule->status == 'high_risk' ? 'selected' : '' }}>High Risk</option>
                <option value="prohibited" {{ $rule->status == 'prohibited' ? 'selected' : '' }}>Prohibited</option>
            </select>
        </div>
        <div class="form-group">
            <label for="badge">Badge</label>
            <select name="badge" id="badge" class="form-control select2" required>
                <option value="">Choose Badge</option>
                <option value="primary" {{ $rule->badge == 'primary' ? 'selected' : '' }} data-content="<span class='badge badge-primary'>Primary</span>">Primary</option>
                <option value="secondary" {{ $rule->badge == 'secondary' ? 'selected' : '' }} data-content="<span class='badge badge-secondary'>Secondary</span>">Secondary</option>
                <option value="success" {{ $rule->badge == 'success' ? 'selected' : '' }}  data-content="<span class='badge badge-success'>Success</span>">Success</option>
                <option value="danger" {{ $rule->badge == 'danger' ? 'selected' : '' }} data-content="<span class='badge badge-danger'>Danger</span>">Danger</option>
                <option value="warning" {{ $rule->badge == 'warning' ? 'selected' : '' }} data-content="<span class='badge badge-warning'>Warning</span>">Warning</option>
                <option value="info" {{ $rule->badge == 'info' ? 'selected' : '' }} data-content="<span class='badge badge-info'>Info</span>">Info</option>
                <option value="light" {{ $rule->badge == 'light' ? 'selected' : '' }} data-content="<span class='badge badge-light'>Light</span>">Light</option>
                <option value="dark" {{ $rule->badge == 'dark' ? 'selected' : '' }} data-content="<span class='badge badge-dark'>Dark</span>">Dark</option>
                <option value="prohibited" {{ $rule->badge == 'prohibited' ? 'selected' : '' }} data-content="<span class='badge badge-prohibited'>Dark Brown</span>">Dark Brown</option>

                
            </select>
        </div>
        <div class="form-group">
            <button type="submit" class="btn btn-primary">Update Rule</button>
        </div>
    </form>
</div>

@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        $('.select2').select2({
            templateResult: function(option) {
                if (!option.id) {
                    return option.text;
                }
                var $option = $(option.element);
                var content = $option.data('content');
                return content ? $(content) : option.text;
            },
            escapeMarkup: function(markup) {
                return markup;
            }
        });

        $('#data_id').on('change', function() {
            var dataSetId = $(this).val();
            if (dataSetId) {
                $.ajax({
                    url: '/settings/aml/data/' + dataSetId + '/fields',
                    type: 'GET',
                    success: function(response) {
                        var fieldsDropdown = $('#field');
                        fieldsDropdown.empty();
                        fieldsDropdown.append('<option value="">Choose Field</option>');
                        $.each(response.fields, function(key, value) {
                            fieldsDropdown.append('<option value="'+ key +'">'+ value +'</option>');
                        });
                        fieldsDropdown.val('{{ $rule->field }}');
                        fieldsDropdown.trigger('change');
                    }
                });
            }
        });

        function updateDropdownVisibility() {
            const field = $('#field').val();
            if (field === 'nationality_id' || field === 'residence_country_id') {
                $('#country-dropdown').show();
                $('#country-text').hide();
            } else {
                $('#country-dropdown').hide();
                $('#country-text').show();
            }
            validateConditionalFields();
        }

        function validateConditionalFields() {
            const isCountryVisible = $('#country-dropdown').is(':visible');
            if (isCountryVisible) {
                $('#country_id').prop('required', true);
                $('#value').prop('required', false);
            } else {
                $('#country_id').prop('required', false);
                $('#value').prop('required', true);
            }
        }

        $('#field').on('change', function() {
            updateDropdownVisibility();
        });

        $('#data_id').trigger('change');
        updateDropdownVisibility();

        $('#aml-form').on('submit', function(e) {
            const isCountryVisible = $('#country-dropdown').is(':visible');
            if (isCountryVisible) {
                if ($('#country_id').val().length === 0) {
                    e.preventDefault();
                    alert('Please select at least one country.');
                }
            } else {
                if ($('#value').val().trim() === '') {
                    e.preventDefault();
                    alert('Please enter a value.');
                }
            }
        });
    });
</script>
@endsection
