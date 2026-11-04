@extends('layouts.cp')
@section('title', 'Add Module Configurations - System Settings')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>System Settings</span>
        </h1>
        <div class="d-flex">
            <a href="{{ url('/settings/module-configurations') }}" class="btn btn-primary btn-shape ml-2">
                <i class="fal fa-arrow-alt-left"></i> Go Back
            </a>
            <button class="btn btn-primary btn-shape ml-2" type="button" id="field-button"><i class="fal fa-plus-circle"></i>
                Add new field</button>
        </div>

    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false"
        data-panel-color="false" data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                Add Module Configurations
            </h2>
        </div>

        <div class="panel-container show">
            <div class="panel-content">
                <div class="container">
                    @include('common.error-message')

                    <form method="post" action="{{ url('/settings/module-configurations/store') }}"
                        enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label" for="module_id">Module</label>
                                    <select name="module_id" autofocus id="module_id" value=""
                                        data-select2-id="module_id" class="form-control select2 w-100" required>
                                        <option value="">Choose Module</option>
                                        @foreach ($modules as $module)
                                            @if ($module->id == old('module_id'))
                                                <option value="{{ $module->id }}" selected>
                                                    {{ $module->name }}
                                                </option>
                                            @else
                                                <option value="{{ $module->id }}">{{ $module->name }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                    @error('module_id')
                                        <span class="invalid-feedback"
                                            role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div id="dynamic-fields">
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <hr>
                                    <button type="submit" class="btn btn-primary" id="module-config-submit">Submit</button>
                                    <a href="{{ url('/settings/module-configurations') }}" class="btn btn-danger"
                                        id="module-config-cancle">Cancel</a>
                                </div>
                            </div>
                        </div>
                        <div class="modal fade" id="addFieldModal" tabindex="-1" role="dialog">
                            <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Add Field</h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">
                                        @include('settings.modules.configurations.partials.field-form-fields', [
                                            'prefix' => 'add-',
                                            'showAttributeName' => true,
                                        ])
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-primary" id="modal-submit">Add Field</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@stop
@section('scripts')
    <script>
        $(document).ready(function() {
            let newRow = null;
            $('#module_id').change(function() {
                var selectedModuleId = $(this).val();
                if (selectedModuleId != '') {
                    $('#field-button').show();
                } else {
                    $('#field-button').hide();
                }
            })

            $('.select2').select2();             
            $('#addFieldModal').on('shown.bs.modal', function() {
                $('#add-field-type-dropdown').select2({
                    dropdownParent: $('#addFieldModal')
                });
            });

            function toggleOptionsRow() {
                const type = $('#add-field-type-dropdown').val();
                const showOptions = type === 'select' || type === 'multiselect';
                $('#addFieldModal').find('.config-field-options-row').toggleClass('d-none', !showOptions);
            }

            $(document).on('change', '#add-field-type-dropdown', toggleOptionsRow);

            function handleDeleteField() {
                const removeButton = $("<button>", {
                    type: "button",
                    class: "btn btn-danger mt-2 ml-2 btn-sm",
                    html: '<i class="fal fa-times"></i>',
                    click: function() {
                        $(this).closest(".col-md-6").remove();
                        if ($("#dynamic-fields").children().length === 0) {
                            $("#module-config-submit").hide();
                            $("#module-config-cancle").hide();
                        }
                    }
                });
                return removeButton;
            }

            $("#field-button").click(function() {
                $("#add-field-type-dropdown").val("").trigger('change');
                $("#add-field-attribute-input").val("");
                $("#add-field-attribute-label").val("");
                $("#add-field-hint").val("");
                $("#add-field-sample").val("");
                $("#add-field-doc-url").val("");
                $("#add-field-placeholder").val("");
                $("#add-field-options").val("");
                toggleOptionsRow();
                $("#addFieldModal").modal("show");
            });

            $("#modal-submit").click(function() {
                const selectedType = $("#add-field-type-dropdown").val();
                const attributeName = $("#add-field-attribute-input").val().trim();
                const attributeLabel = $("#add-field-attribute-label").val().trim();
                const fieldHint = $("#add-field-hint").val().trim();
                const fieldSample = $("#add-field-sample").val().trim();
                const fieldDocUrl = $("#add-field-doc-url").val().trim();
                const fieldPlaceholder = $("#add-field-placeholder").val().trim();
                const fieldOptions = $("#add-field-options").val().trim();

                if (!selectedType || !attributeName) {
                    alert("Please fill in both the field type and attribute name.");
                    return;
                }

                const dynamicFieldsDiv = $("#dynamic-fields");
                const fieldCount = dynamicFieldsDiv.children().length;
                if (fieldCount % 2 === 0) {
                    newRow = $("<div>", {
                        class: "row mt-3"
                    });
                    dynamicFieldsDiv.append(newRow);
                }

                const newFieldContainer = $("<div>", {
                    class: "col-md-6"
                });

                const newFieldId = attributeName;

                const newDiv = $("<div>", {
                    class: selectedType == "checkbox" ?
                        "custom-control custom-switch mt-3" : "form-group mt-3"
                });

                const newField = $("<input>", {
                    type: selectedType === 'textarea' ? 'text' : selectedType,
                    id: newFieldId,
                    name: attributeName,
                    class: selectedType == "checkbox" ? "form-group custom-control-input" :
                        "form-control",
                    value: selectedType == "checkbox" ? 1 : "",
                    required: selectedType == "checkbox" ? false : true,
                });

                if (fieldPlaceholder && selectedType !== 'checkbox') {
                    newField.attr('placeholder', fieldPlaceholder);
                }

                const newLabel = $("<label>", {
                    class: selectedType == "checkbox" ? "custom-control-label" : "form-label",
                    for: newFieldId,
                    text: attributeLabel
                });

                if (selectedType == "checkbox") {
                    newDiv.append(newField);
                    newDiv.append(newLabel);
                } else {
                    newDiv.append(newLabel);
                    newDiv.append(newField);
                }

                if (selectedType == "password") {
                    const eyeIcon = $("<i>", {
                        class: "fal fa-eye-slash",
                        id: `${newFieldId}-eye-icon`,
                        click: function() {
                            const inputField = $(`#${newFieldId}`);
                            const eyeIcon = $(`#${newFieldId}-eye-icon`);
                            if (inputField.attr("type") === "password") {
                                inputField.attr("type", "text");
                                eyeIcon.removeClass("fa-eye-slash");
                                eyeIcon.addClass("fa-eye");
                            } else {
                                inputField.attr("type", "password");
                                eyeIcon.removeClass("fa-eye");
                                eyeIcon.addClass("fa-eye-slash");
                            }
                        }
                    });
                    newDiv.append(eyeIcon);
                }

                dynamicFieldsDiv.append(newDiv);

                if (dynamicFieldsDiv.children().length > 0) {
                    $("#module-config-submit").show();
                    $("#module-config-cancle").show();
                } else {
                    $("#module-config-submit").hide();
                    $("#module-config-cancle").hide();
                }

                const hiddenInput = $("<input>", {
                    type: "hidden",
                    name: `${newFieldId}_type`,
                    value: selectedType
                });

                const hiddenLabel = $("<input>", {
                    type: "hidden",
                    name: `${newFieldId}_label`,
                    value: attributeLabel
                });

                const hiddenHint = $("<input>", {
                    type: "hidden",
                    name: `${newFieldId}_hint`,
                    value: fieldHint
                });

                const hiddenSample = $("<input>", {
                    type: "hidden",
                    name: `${newFieldId}_sample`,
                    value: fieldSample
                });

                const hiddenDocUrl = $("<input>", {
                    type: "hidden",
                    name: `${newFieldId}_doc_url`,
                    value: fieldDocUrl
                });

                const hiddenPlaceholder = $("<input>", {
                    type: "hidden",
                    name: `${newFieldId}_placeholder`,
                    value: fieldPlaceholder
                });

                const hiddenOptions = $("<input>", {
                    type: "hidden",
                    name: `${newFieldId}_options`,
                    value: fieldOptions
                });

                if (selectedType === "checkbox") {
                    const newHiddenField = $("<input>", {
                        type: "hidden",
                        name: `${newFieldId}`,
                        value: "0"
                    });
                    newDiv.append(newHiddenField);

                    newField.change(function() {
                        newHiddenField.val(this.checked ? "1" : "0");
                    });
                }

                newDiv.append(hiddenInput);
                newDiv.append(hiddenLabel);
                newDiv.append(hiddenHint);
                newDiv.append(hiddenSample);
                newDiv.append(hiddenDocUrl);
                newDiv.append(hiddenPlaceholder);
                newDiv.append(hiddenOptions);

                newFieldContainer.append(newDiv);
                if (newRow !== null) {
                    newRow.append(newFieldContainer);
                }

                const deleteButton = handleDeleteField();
                newDiv.append(deleteButton);

                $("#addFieldModal").modal("hide");
            });
        });
    </script>
@stop
