@extends('layouts.cp')
@section('title', 'Edit Module Configurations - System Settings')

@section('content')

    <style>
        .config-search-group {
            max-width: 340px;
            flex: 1 1 260px;
        }

        .config-field-card {
            border: 1px solid #e2e6ea;
            border-radius: 6px;
            padding: 12px 14px;
            height: 100%;
            background: #fff;
            transition: box-shadow .15s ease, border-color .15s ease;
        }

        .config-field-card:hover {
            border-color: #c7cdd3;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .06);
        }

        .config-field-card-hdr {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
        }

        .config-drag-handle {
            cursor: grab;
            color: #adb5bd;
            font-size: 13px;
        }

        .config-drag-handle:active {
            cursor: grabbing;
        }

        .config-field-key {
            font-size: 11px;
            color: #8a94a6;
            background: transparent;
            padding: 0;
        }

        .config-field-card .btn-icon {
            padding: 2px 6px;
            line-height: 1;
            color: #c0392b;
        }

        .config-field-card .config-field-help-btn {
            color: #17a2b8;
        }

        .config-field-card .config-field-help-btn:hover {
            color: #117a8b;
        }

        .config-field-card .edit-config-field {
            color: #6c757d;
        }

        .config-field-card .edit-config-field:hover {
            color: #495057;
        }

        .config-field-card-hdr-actions {
            display: flex;
            align-items: center;
            gap: 4px;
            margin-left: auto;
        }

        .config-field-col.d-none,
        .config-field-col.config-show-when-hidden {
            display: none !important;
        }

        .config-field-col.config-sort-placeholder {
            visibility: visible !important;
            border: 2px dashed #adb5bd;
            border-radius: 6px;
            background: #f4f6f8;
            min-height: 70px;
            margin-top: 1rem;
        }

        .config-field-col.ui-sortable-helper .config-field-card {
            box-shadow: 0 6px 16px rgba(0, 0, 0, .15);
        }
    </style>

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>System Settings</span>
        </h1>
        <div class="d-flex">
            <a href="{{ url('/settings/module-configurations/export/' . $module_id) }}" class="btn btn-outline-primary mr-2">
                <i class="fal fa-file-export"></i> Export
            </a>
            <button type="button" class="btn btn-outline-secondary mr-2" data-toggle="modal"
                data-target="#import-module-config-modal" data-import-module="{{ $module_id }}"
                data-import-name="{{ $selectedModule->name ?? '' }}">
                <i class="fal fa-file-import"></i> Import
            </button>
            <a href="{{ url('/settings/module-configurations') }}" class="btn btn-primary mr-3">
                <i class="fal fa-arrow-alt-left"></i> Go Back
            </a>
            <button type="button" class="btn btn-primary" id="toggle-config-btn">+ Show Config</button>
            <button type="button" class="btn btn-primary btn-shape ml-2" id="new-field-button"><i
                    class="fal fa-plus-circle"></i>
                Add new field
            </button>
        </div>
    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false"
        data-panel-color="false" data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                Edit Module Configurations
            </h2>
        </div>

        <div class="panel-container ">
            <div class="panel-content">
                <div class="container edit-config-wrap" style="display:none">
                    @include('common.error-message')

                    @php
                        $hasConfidentialFields = false;
                        if (!empty($configurations)) {
                            foreach ($configurations as $key => $value) {
                                if (
                                    is_array($value) &&
                                    \App\Helpers\ModuleConfigurationSecretField::isSensitive($key, $value)
                                ) {
                                    $hasConfidentialFields = true;
                                    break;
                                }
                            }
                        }
                    @endphp
                    <form method="post" action="{{ url('/settings/module-configurations/update') }}"
                        enctype="multipart/form-data">
                        @csrf

                        <div class="form-wrap ">

                            <input type="hidden" name="id" id="id" value="{{ $id }}">
                            <div class="row align-items-start">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="form-label" for="module_id">Module</label>
                                        <select name="module_id" autofocus id="module_id" value=""
                                            data-select2-id="module_id" class="form-control select2 w-100" required>
                                            <option value="">Choose Module</option>
                                            @foreach ($modules as $module)
                                                @if ($module->id == $module_id)
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

                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="form-label d-block">Module Status</label>
                                        <div class="custom-control custom-switch ">
                                            <input type="hidden" name="module_is_enabled" value="0">
                                            <input type="checkbox" class="custom-control-input" id="module_is_enabled"
                                                name="module_is_enabled" value="1"
                                                {{ $selectedModule->is_enabled ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="module_is_enabled">
                                                {{ $selectedModule->is_enabled ? 'Enabled' : 'Disabled' }}
                                            </label>
                                        </div>
                                        <small class="form-text text-muted">Enable or disable this module directly from
                                            configuration.</small>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    @if ($hasConfidentialFields && env('GOOGLE_AUTHENTICATOR'))
                                        <div class="form-group">
                                            <label class="form-label d-block">Security</label>
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input"
                                                    id="show_credentials_toggle">
                                                <label class="custom-control-label text-primary font-weight-bold"
                                                    for="show_credentials_toggle" style="cursor: pointer;">
                                                    <i class="fal fa-eye"></i> Show Credentials
                                                </label>
                                            </div>
                                            <small class="form-text text-muted">Authenticate to reveal masked keys.
                                            </small>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @php
                            $bitpaceModuleEnabled = $bitpaceModuleEnabled ?? false;
                            $isCryptoModuleConfig = ($selectedModule->name ?? '') === 'Crypto';
                        @endphp
                        @if ($isCryptoModuleConfig && $bitpaceModuleEnabled)
                            <div class="alert alert-info mt-3 mb-0" role="status">
                                <strong>Bitpace module is enabled.</strong>
                                Enable Manual Crypto and Enable Advanced Crypto are locked on and overridden by Bitpace.
                                Disable the Bitpace module under Settings → Modules to control these options
                                independently.
                            </div>
                        @endif
                        @if ($configurations)
                            <div class="config-fields-toolbar d-flex align-items-center flex-wrap mt-3 mb-2">
                                <div class="input-group config-search-group mr-3 mb-2">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text bg-white"><i class="fal fa-search"></i></span>
                                    </div>
                                    <input type="text" id="config-field-search" class="form-control"
                                        placeholder="Search fields by name or key…">
                                </div>
                                <span class="badge badge-light mb-2" id="config-field-count">
                                    {{ count($configurations) }} field{{ count($configurations) == 1 ? '' : 's' }}
                                </span>
                                <span class="text-muted small mb-2 ml-3">
                                    <i class="fal fa-arrows-alt"></i> Drag the handle to reorder fields
                                </span>
                                <span class="text-muted small mb-2 ml-3">
                                    <i class="fal fa-question-circle"></i> Use <strong>?</strong> on a field for what it
                                    does and where it is used in the app
                                </span>
                            </div>
                            <div class="row" id="config-fields-grid">
                                @foreach ($configurations as $key => $value)
                                    <div class="col-md-6 mt-3 config-field-col" data-key="{{ $key }}"
                                        data-search="{{ strtolower(($value['label'] ?? '') . ' ' . $key) }}"
                                        @if (!empty($value['show_when']['field'])) data-show-when-field="{{ $value['show_when']['field'] }}"
                                                data-show-when-value="{{ $value['show_when']['value'] ?? '' }}" @endif>
                                        <input type="hidden" class="field-order-input" name="field_order[]"
                                            value="{{ $key }}">
                                        @include(
                                            'settings.modules.configurations.partials.field-meta-inputs',
                                            [
                                                'key' => $key,
                                                'value' => $value,
                                            ]
                                        )
                                        <div class="config-field-card">
                                            <div class="config-field-card-hdr">
                                                <span class="config-drag-handle" title="Drag to reorder">
                                                    <i class="fal fa-grip-vertical"></i>
                                                </span>
                                                <code class="config-field-key"
                                                    title="Configuration key">{{ $key }}</code>

                                                <div class="config-field-card-hdr-actions">
                                                    @include(
                                                        'settings.modules.configurations.partials.field-hint',
                                                        [
                                                            'field' => $value,
                                                            'fieldKey' => $key,
                                                            'mode' => 'trigger',
                                                        ]
                                                    )
                                                    <button type="button" class="btn btn-sm btn-icon edit-config-field"
                                                        title="Edit field settings" data-key="{{ $key }}">
                                                        <i class="fal fa-pencil"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-icon delete-config-field"
                                                        data-label="{{ $value['label'] }}" title="Delete field">
                                                        <i class="fal fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>

                                            @php
                                                $isSecretField = \App\Helpers\ModuleConfigurationSecretField::isSensitive(
                                                    $key,
                                                    $value,
                                                );
                                            @endphp

                                            @if ($isSecretField)
                                                @include(
                                                    'settings.modules.configurations.partials.secret-field',
                                                    [
                                                        'key' => $key,
                                                        'value' => $value,
                                                    ]
                                                )
                                            @elseif ($value['type'] == 'checkbox')
                                                @php
                                                    $cryptoBitpaceGated =
                                                        $isCryptoModuleConfig &&
                                                        in_array(
                                                            $key,
                                                            ['enable_manual_crypto', 'enable_advanced_crypto'],
                                                            true,
                                                        );
                                                    $bitpaceLocksCheckbox =
                                                        $cryptoBitpaceGated && $bitpaceModuleEnabled;
                                                    $checkboxChecked = $bitpaceLocksCheckbox || $value['value'] == 1;
                                                @endphp
                                                <div class="custom-control custom-switch form-group mb-0">
                                                    @if ($bitpaceLocksCheckbox)
                                                        <input type="hidden" name="configurations[{{ $key }}]"
                                                            value="1">
                                                    @else
                                                        <input type="hidden" name="configurations[{{ $key }}]"
                                                            value="0">
                                                    @endif
                                                    <input type="checkbox" class="custom-control-input"
                                                        id="{{ $key }}"
                                                        name="configurations[{{ $key }}]" value="1"
                                                        {{ $checkboxChecked ? 'checked' : '' }}
                                                        @if ($bitpaceLocksCheckbox) disabled @endif>
                                                    <label class="custom-control-label"
                                                        for="{{ $key }}">{{ $value['label'] }}</label>
                                                    @if ($bitpaceLocksCheckbox)
                                                        <small class="form-text text-muted d-block mt-1">
                                                            Locked while Bitpace is enabled (both manual and advanced
                                                            are on).
                                                        </small>
                                                    @elseif ($cryptoBitpaceGated)
                                                        <small class="form-text text-muted d-block mt-1">
                                                            Editable while Bitpace is disabled — use the help icon for
                                                            details.
                                                        </small>
                                                    @endif
                                                </div>
                                            @elseif ($value['type'] == 'multiselect')
                                                @include(
                                                    'settings.modules.configurations.partials.multiselect-field',
                                                    [
                                                        'key' => $key,
                                                        'value' => $value,
                                                    ]
                                                )
                                            @elseif ($value['type'] == 'select')
                                                <div class="form-group mb-0">
                                                    <label class="form-label"
                                                        for="{{ $key }}">{{ $value['label'] }}</label>
                                                    <select name="configurations[{{ $key }}]"
                                                        id="{{ $key }}" class="form-control">
                                                        @if (isset($value['options_source']) && $value['options_source'] === 'fee_tier_groups')
                                                            <option value="">— Use is_default group —</option>
                                                            @foreach (\App\Modules\FeeEngine\Models\FeeTierGroup::where('is_active', true)->orderBy('name')->get() as $g)
                                                                <option value="{{ $g->id }}"
                                                                    {{ (string) $value['value'] === (string) $g->id ? 'selected' : '' }}>
                                                                    {{ $g->name }} ({{ $g->code }})
                                                                </option>
                                                            @endforeach
                                                        @elseif (!empty($value['option_groups']) && is_array($value['option_groups']))
                                                            @foreach ($value['option_groups'] as $groupLabel => $groupOptions)
                                                                <optgroup label="{{ $groupLabel }}">
                                                                    @foreach ($groupOptions as $optVal => $optLabel)
                                                                        <option value="{{ $optVal }}"
                                                                            {{ (string) $value['value'] === (string) $optVal ? 'selected' : '' }}>
                                                                            {{ $optLabel }}
                                                                        </option>
                                                                    @endforeach
                                                                </optgroup>
                                                            @endforeach
                                                        @elseif (isset($value['options']))
                                                            @foreach ($value['options'] as $optVal => $optLabel)
                                                                <option value="{{ $optVal }}"
                                                                    {{ (string) $value['value'] === (string) $optVal ? 'selected' : '' }}>
                                                                    {{ $optLabel }}
                                                                </option>
                                                            @endforeach
                                                        @endif
                                                    </select>
                                                </div>
                                            @elseif ($value['type'] == 'textarea')
                                                <div class="form-group mb-0">
                                                    <label class="form-label"
                                                        for="{{ $key }}">{{ $value['label'] }}</label>
                                                    <textarea name="configurations[{{ $key }}]" id="{{ $key }}" rows="6" class="form-control"
                                                        placeholder="{{ $value['placeholder'] ?? ($value['sample'] ?? '') }}">{{ $value['value'] ?? '' }}</textarea>
                                                </div>
                                            @else
                                                <div class="form-group mb-0">
                                                    <label class="form-label"
                                                        for="{{ $key }}">{{ $value['label'] }}</label>
                                                    <input type="{{ $value['type'] }}"
                                                        name="configurations[{{ $key }}]"
                                                        id="{{ $key }}" value="{{ $value['value'] ?? '' }}"
                                                        class="form-control"
                                                        placeholder="{{ $value['placeholder'] ?? ($value['sample'] ?? '') }}"
                                                        @if (!isset($value['required']) || $value['required'] !== false) required @endif>
                                                </div>
                                            @endif
                                            @include(
                                                'settings.modules.configurations.partials.field-hint',
                                                [
                                                    'field' => $value,
                                                    'fieldKey' => $key,
                                                    'mode' => 'content',
                                                ]
                                            )
                                        </div>
                                    </div>
                                @endforeach
                                <div class="col-12 mt-2 d-none" id="config-no-results">
                                    <div class="text-center text-muted py-4">
                                        <i class="fal fa-search fa-2x mb-2 d-block"></i>
                                        No fields match your search.
                                    </div>
                                </div>
                            </div>

                        @endif
                        <div id="fingate-card-providers">

                        </div>
                        <div id="dynamic-fields">
                        </div>
                </div><!-- form wrap div -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <hr>
                            <button type="submit" class="btn btn-primary">Submit</button>
                            @if (isset($configurations['partner_name']) && $configurations['partner_name']['value'] == 'FinGate')
                                @if (!app()->environment('production'))
                                    <button type="button" class="btn btn-success ml-3" data-toggle="modal"
                                        id="simulateTransactionButton">
                                        Simulate Transaction
                                    </button>
                                @endif
                                <button type="button" class="btn btn-info ml-3" data-toggle="modal"
                                    id="syncStatementButton">
                                    Sync Statement
                                </button>
                                <button type="button" class="btn btn-success ml-3" data-toggle="modal"
                                    id="showCardProviders">
                                    Show Card Providers
                                </button>
                            @endif

                            {{-- Openpayd Sync Beneficiary Fields --}}
                            @if ($selectedModule->name == 'OpenPayd')
                                <button type="button" class="btn btn-info ml-3" data-toggle="modal"
                                    id="syncBeneficiaryFieldsButton">
                                    Sync Single Type Beneficiary Fields
                                </button>

                                <button type="button" class="btn btn-success ml-3" data-toggle="modal"
                                    id="syncAllBeneficiaryFieldsButton">
                                    Sync All Types Beneficiary Fields
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
                </form>
                <div class="modal fade" id="simulateTransactionModal" tabindex="-1" role="dialog"
                    aria-labelledby="simulateTransactionLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="simulateTransactionLabel">Simulate Transaction</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <form method="post"
                                    action="{{ url('/settings/module-configurations/simulate-card-transaction') }}"
                                    enctype="multipart/form-data">
                                    @csrf
                                    <div class="form-group">
                                        <label class="form-label" for="card_id">Select Card</label>
                                        <select name="card_id" id="card_id" value="" data-select2-id="card_id"
                                            class="form-control select2 w-100" required>
                                            <option value="">Choose Card</option>
                                            @foreach ($cards as $card)
                                                <option value="{{ $card->card_id }}">
                                                    **** **** **** {{ substr($card->cardNumber, -4) }} -
                                                    {{ $card->cardHolderName }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('card_id')
                                            <span class="invalid-feedback"
                                                role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label" for="amount">Billing Transaction Amount</label>
                                        <input type="text" name="amount" id="amount" value=""
                                            class="form-control" required>
                                        @error('amount')
                                            <span class="invalid-feedback"
                                                role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label" for="description">Description</label>
                                        <input type="text" name="description" id="description" value=""
                                            class="form-control" required>
                                        @error('description')
                                            <span class="invalid-feedback"
                                                role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary"
                                            data-dismiss="modal">Close</button>
                                        <button type="submit" class="btn btn-primary">Submit</button>
                                    </div>

                                </form>
                            </div>
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

                <div class="modal fade" id="editFieldModal" tabindex="-1" role="dialog">
                    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Edit Field Settings</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                @include('settings.modules.configurations.partials.field-form-fields', [
                                    'prefix' => 'edit-',
                                    'showAttributeName' => false,
                                    'attributeReadonly' => true,
                                ])
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-primary" id="edit-field-modal-submit">Save
                                    Field Settings</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="configFieldHelpModal" tabindex="-1" role="dialog"
                    aria-labelledby="configFieldHelpModalTitle" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="configFieldHelpModalTitle">Field Help</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body" id="configFieldHelpModalBody"></div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="syncBeneficiaryFieldsModal" tabindex="-1" role="dialog"
                    aria-labelledby="syncBeneficiaryFieldsLable" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="syncBeneficiaryFieldsLable">Sync Single Types Beneficiary
                                    Required
                                    Fields</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>

                            <div class="modal-body">
                                <form method="post"
                                    action="{{ url('/settings/sync-beneficiary-fields-with-openpayd') }}"
                                    enctype="multipart/form-data">
                                    @csrf
                                    <div class="form-group">
                                        <label class="form-label" for="currency_id">Select Currency</label>
                                        <select name="currency_id" id="currency_id" value=""
                                            data-select2-id="currency_id" class="form-control select2 w-100" required>
                                            <option value="">Choose Currency</option>
                                            @foreach ($currencies as $currency)
                                                <option value="{{ $currency->id }}">
                                                    {{ $currency->name }} ({{ $currency->short }})
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('currency_id')
                                            <span class="invalid-feedback"
                                                role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label" for="country_id">Select Country</label>
                                        <select name="country_id" id="country_id" value=""
                                            data-select2-id="country_id" class="form-control select2 w-100" required>
                                            <option value="">Choose Country</option>
                                            @foreach ($countries as $country)
                                                <option value="{{ $country->id }}">
                                                    {{ $country->name }} ({{ $country->iso3 }})
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('country_id')
                                            <span class="invalid-feedback"
                                                role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label" for="beneficiary_type">Select Beneficiary
                                            Type</label>
                                        <select name="beneficiary_type" id="beneficiary_type" value=""
                                            data-select2-id="beneficiary_type" class="form-control select2 w-100"
                                            required>
                                            <option value="">Choose Beneficiary Type</option>
                                            @foreach ($beneficiaryTypes as $type)
                                                <option value="{{ $type }}">
                                                    {{ $type }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('description')
                                            <span class="invalid-feedback"
                                                role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary"
                                            data-dismiss="modal">Close</button>
                                        <button type="submit" class="btn btn-primary">Sync Beneficiary
                                            Fields</button>
                                    </div>

                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="syncAllBeneficiaryFieldsModal" tabindex="-1" role="dialog"
                    aria-labelledby="syncAllBeneficiaryFieldsLable" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="syncAllBeneficiaryFieldsLable">Sync All Types Beneficiary
                                    Required
                                    Fields</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>

                            <div class="modal-body">
                                <form method="post"
                                    action="{{ url('/settings/sync-all-beneficiary-fields-with-openpayd') }}"
                                    enctype="multipart/form-data">
                                    @csrf
                                    <div class="form-group">
                                        <label class="form-label" for="sync_currency_id">Select Currency</label>
                                        <select name="sync_currency_id" id="sync_currency_id" value=""
                                            data-select2-id="sync_currency_id" class="form-control select2 w-100"
                                            required>
                                            <option value="">Choose Currency</option>
                                            @foreach ($currencies as $currency)
                                                <option value="{{ $currency->id }}">
                                                    {{ $currency->name }} ({{ $currency->short }})
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('sync_currency_id')
                                            <span class="invalid-feedback"
                                                role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>

                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary"
                                            data-dismiss="modal">Close</button>
                                        <button type="submit" class="btn btn-primary">Sync All Beneficiary
                                            Fields</button>
                                    </div>

                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="revealCredentialsModal" tabindex="-1" role="dialog" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-sm" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Authenticator Required</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <p class="text-muted mb-3">Please enter your Google Authenticator code to reveal
                                    confidential data.</p>
                                <div class="form-group">
                                    <label class="form-label" for="reveal_otp">6-digit Code</label>
                                    <input type="text" inputmode="numeric" id="reveal_otp" class="form-control"
                                        autocomplete="off" maxlength="6" pattern="\d{6}" autofocus>
                                    <div class="invalid-feedback" id="reveal_otp_error">Invalid OTP. Please try again.
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-primary" id="verifyRevealOtpBtn">Verify</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="loading"
                    style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); height: 100px; display:none; z-index: 9999;">
                    <?= '<?xml version="1.0" encoding="utf-8"?>' ?>
                    <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
                        style="background: none; shape-rendering: auto;" width="50px" height="50px"
                        viewBox="0 0 100 100" preserveAspectRatio="xMidYMid">
                        <circle cx="50" cy="50" fill="none" stroke="#ffc800" stroke-width="13" r="38"
                            stroke-dasharray="179.0707812546182 61.690260418206066">
                            <animateTransform attributeName="transform" type="rotate" repeatCount="indefinite"
                                dur="0.625s" values="0 50 50;360 50 50" keyTimes="0;1"></animateTransform>
                        </circle>
                    </svg>
                </div>
                @include('settings.modules.partials.import-config-modal')
            @stop
            @section('scripts')
                <script>
                    let editingFieldCol = null;
                    let newRow = null;

                    function showAlert(message, type = 'success') {
                        let bgClass = type === 'success' ? 'bg-success' : 'bg-danger';
                        const alertHtml = `
                               <div class="alert ${bgClass} alert-dismissible fade show mt-3 " role="alert">
                                <strong class="text-white">${message}</strong>
                                  <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                  </button>
                                </div>`;
                        $('.panel-content .container').prepend(alertHtml);
                        setTimeout(function() {
                            $('.alert').alert('close');
                        }, 5000);
                    }

                    function toggleSpinner(show) {
                        const spinner = document.getElementById('loading');
                        spinner.style.display = show ? 'block' : 'none';
                    }

                    function toggleOptionsRow(prefix) {
                        const type = $('#' + prefix + 'field-type-dropdown').val();
                        const showOptions = type === 'select' || type === 'multiselect';
                        const modalId = prefix === 'add-' ? '#addFieldModal' : '#editFieldModal';
                        $(modalId).find('.config-field-options-row').toggleClass('d-none', !showOptions);
                    }

                    function applyShowWhenFields() {
                        $('.config-field-col[data-show-when-field]').each(function() {
                            const $col = $(this);
                            const field = String($col.data('show-when-field') || '');
                            const expected = String($col.data('show-when-value') ?? '');
                            const current = String($('#' + field).val() ?? '');
                            $col.toggleClass('config-show-when-hidden', current !== expected);
                        });
                    }

                    function buildHintTemplateHtml(hint, sample, docUrl) {
                        let html = '';
                        if (hint) {
                            html += '<p class="mb-3 text-muted">' + $('<div>').text(hint).html() + '</p>';
                        }
                        if (sample) {
                            html += '<div class="mb-3"><strong class="d-block mb-1">Sample value</strong>' +
                                '<code class="d-inline-block px-2 py-1 bg-light rounded">' +
                                $('<div>').text(sample).html() + '</code></div>';
                        }
                        if (docUrl) {
                            html += '<div><a href="' + $('<div>').text(docUrl).html() +
                                '" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-primary">' +
                                'Documentation <i class="fal fa-external-link ml-1"></i></a></div>';
                        }
                        return html;
                    }

                    function updateFieldHelpTrigger($col, label, hint, sample, docUrl, fieldKey) {
                        const hasHint = hint || sample || docUrl;
                        const $actions = $col.find('.config-field-card-hdr-actions');
                        $actions.find('.config-field-help-btn').remove();

                        const $template = $('#config-hint-' + fieldKey);
                        if ($template.length) {
                            $template.remove();
                        }

                        if (hasHint) {
                            const templateHtml = buildHintTemplateHtml(hint, sample, docUrl);
                            $col.find('.config-field-card').append(
                                '<template id="config-hint-' + fieldKey + '">' + templateHtml + '</template>'
                            );

                            const $helpBtn = $('<button>', {
                                type: 'button',
                                class: 'btn btn-sm btn-icon config-field-help-btn',
                                title: 'Field help',
                                'data-hint-id': 'config-hint-' + fieldKey,
                                'data-field-label': label,
                                'aria-label': 'Help: ' + label,
                                html: '<i class="fal fa-question-circle"></i>'
                            });
                            $actions.find('.edit-config-field').before($helpBtn);
                        }
                    }

                    function fieldVal(selector) {
                        const value = $(selector).val();
                        return value == null ? '' : String(value).trim();
                    }

                    function initFieldTypeSelect(prefix) {
                        const $select = $('#' + prefix + 'field-type-dropdown');
                        if (!$select.length) {
                            return;
                        }

                        if ($select.hasClass('select2-hidden-accessible')) {
                            $select.select2('destroy');
                        }

                        const modalId = prefix === 'add-' ? '#addFieldModal' : '#editFieldModal';
                        $select.select2({
                            dropdownParent: $(modalId),
                            width: '100%',
                        });
                    }

                    $(function() {
                        $(document).on('click', '#new-field-button', function(e) {
                            e.preventDefault();
                            $('.edit-config-wrap').show();
                            $('#add-field-type-dropdown').val('').trigger('change');
                            $('#add-field-attribute-input').val('');
                            $('#add-field-attribute-label').val('');
                            $('#add-field-hint').val('');
                            $('#add-field-sample').val('');
                            $('#add-field-doc-url').val('');
                            $('#add-field-placeholder').val('');
                            $('#add-field-options').val('');
                            toggleOptionsRow('add-');
                            $('#addFieldModal').modal('show');
                        });

                        $(document).on('click', '.edit-config-field', function(e) {
                            e.preventDefault();
                            editingFieldCol = $(this).closest('.config-field-col');
                            const $meta = editingFieldCol.find('.field-meta-inputs');

                            $('#edit-field-attribute-input').val(editingFieldCol.data('key'));
                            $('#edit-field-attribute-label').val($meta.find('.field-meta-label').val());
                            $('#edit-field-hint').val($meta.find('.field-meta-hint').val());
                            $('#edit-field-sample').val($meta.find('.field-meta-sample').val());
                            $('#edit-field-doc-url').val($meta.find('.field-meta-doc-url').val());
                            $('#edit-field-placeholder').val($meta.find('.field-meta-placeholder').val());
                            $('#edit-field-options').val($meta.find('.field-meta-options').val());

                            $('#editFieldModal').modal('show');

                            $('#editFieldModal').one('shown.bs.modal', function() {
                                initFieldTypeSelect('edit-');
                                $('#edit-field-type-dropdown')
                                    .val($meta.find('.field-meta-type').val())
                                    .trigger('change');
                                toggleOptionsRow('edit-');
                            });
                        });

                        $(document).on('click', '#edit-field-modal-submit', function(e) {
                            e.preventDefault();

                            if (!editingFieldCol) {
                                alert('No field selected to update.');
                                return;
                            }

                            const label = fieldVal('#edit-field-attribute-label');
                            const type = fieldVal('#edit-field-type-dropdown') || editingFieldCol.find(
                                '.field-meta-type').val();
                            const hint = fieldVal('#edit-field-hint');
                            const sample = fieldVal('#edit-field-sample');
                            const docUrl = fieldVal('#edit-field-doc-url');
                            const placeholder = fieldVal('#edit-field-placeholder');
                            const optionsText = fieldVal('#edit-field-options');
                            const fieldKey = editingFieldCol.data('key');

                            if (!label || !type) {
                                alert('Label and field type are required.');
                                return;
                            }

                            const $meta = editingFieldCol.find('.field-meta-inputs');
                            $meta.find('.field-meta-label').val(label);
                            $meta.find('.field-meta-type').val(type);
                            $meta.find('.field-meta-hint').val(hint);
                            $meta.find('.field-meta-sample').val(sample);
                            $meta.find('.field-meta-doc-url').val(docUrl);
                            $meta.find('.field-meta-placeholder').val(placeholder);
                            $meta.find('.field-meta-options').val(optionsText);

                            editingFieldCol.find('.config-field-label-text').text(label);
                            editingFieldCol.attr('data-search', (label + ' ' + fieldKey).toLowerCase());
                            editingFieldCol.find('label.form-label, label.custom-control-label').text(label);
                            editingFieldCol.find('.delete-config-field').data('label', label);

                            const $input = editingFieldCol.find('[name="configurations[' + fieldKey + ']"]').not(
                                '[type="hidden"]');
                            if ($input.length && placeholder && !$input.is(':checkbox') && !$input.is('select')) {
                                $input.attr('placeholder', placeholder);
                            }

                            updateFieldHelpTrigger(editingFieldCol, label, hint, sample, docUrl, fieldKey);

                            $('#editFieldModal').modal('hide');
                            editingFieldCol = null;

                            if (typeof Swal !== 'undefined') {
                                Swal.fire({
                                    toast: true,
                                    position: 'top-end',
                                    icon: 'success',
                                    title: 'Field settings updated. Click Submit to save.',
                                    showConfirmButton: false,
                                    timer: 3500,
                                });
                            }
                        });
                    });

                    $(document).ready(function() {
                        // Drag-and-drop field reordering — scoped to this page only.
                        // Reordering the .config-field-col cards also physically moves
                        // their hidden "field_order[]" input, so the submitted order
                        // reflects the new arrangement without any extra bookkeeping.
                        if ($.fn.sortable) {
                            $('#config-fields-grid').sortable({
                                items: '> .config-field-col',
                                handle: '.config-drag-handle',
                                placeholder: 'col-md-6 mt-3 config-field-col config-sort-placeholder',
                                tolerance: 'pointer',
                                forcePlaceholderSize: true,
                                cursor: 'grabbing'
                            });
                        }

                        $('#config-field-search').on('input', function() {
                            const term = $(this).val().trim().toLowerCase();
                            let visible = 0;

                            $('.config-field-col').each(function() {
                                const matches = !term || $(this).data('search').toString().includes(term);
                                $(this).toggleClass('d-none', !matches);
                                if (matches) visible++;
                            });

                            $('#config-no-results').toggleClass('d-none', visible > 0);
                            $('#config-field-count').text(visible + ' field' + (visible === 1 ? '' : 's') +
                                (term ? ' matching "' + $(this).val().trim() + '"' : ''));
                        });

                        $(document).on('click', '.config-field-help-btn', function() {
                            const hintId = $(this).data('hint-id');
                            const template = document.getElementById(hintId);

                            if (!template) {
                                return;
                            }

                            $('#configFieldHelpModalTitle').text($(this).data('field-label') || 'Field Help');
                            $('#configFieldHelpModalBody').html(template.innerHTML);
                            $('#configFieldHelpModal').modal('show');
                        });
                    });

                    $(document).on('click', '.toggle-password', function() {
                        var input = $(this).closest('.input-group').find('input');
                        if (input.attr('type') === 'password') {
                            input.attr('type', 'text');
                            $(this).removeClass('fa-eye-slash').addClass('fa-eye');
                        } else {
                            input.attr('type', 'password');
                            $(this).removeClass('fa-eye').addClass('fa-eye-slash');
                        }
                    });

                    $(document).on('focus', '.config-secret-field', function() {
                        const $field = $(this);
                        if ($field.data('has-stored-value') == 1 && !$field.data('edited')) {
                            $field.val('').attr('placeholder', 'Enter new value to replace saved secret');
                            $field.data('edited', 1);
                        }
                    });

                    $(document).ready(function() {
                        $('#syncBeneficiaryFieldsModal').on('shown.bs.modal', function() {
                            $('#currency_id, #beneficiary_type, #country_id').select2({
                                dropdownParent: $(this) // Ensure dropdown is inside modal
                            });
                        });

                        $('#syncAllBeneficiaryFieldsModal').on('shown.bs.modal', function() {
                            $('#sync_currency_id').select2({
                                dropdownParent: $(this) // Ensure dropdown is inside modal
                            });
                        });

                        $('.select2').select2();
                        applyShowWhenFields();
                        $(document).on('change', '.config-field-col select', applyShowWhenFields);
                        $('#addFieldModal').on('shown.bs.modal', function() {
                            initFieldTypeSelect('add-');
                        });

                        $(document).on('change', '#add-field-type-dropdown, #edit-field-type-dropdown', function() {
                            const prefix = this.id.startsWith('add-') ? 'add-' : 'edit-';
                            toggleOptionsRow(prefix);
                        });

                        $('#module_is_enabled').on('change', function() {
                            $('label[for="module_is_enabled"]').text(this.checked ? 'Enabled' : 'Disabled');
                        });
                    });

                    $(document).ready(function() {
                        $('#toggle-config-btn').on('click', function() {
                            $('.edit-config-wrap').toggle();
                            $(this).text(function(_, text) {
                                return text === '+ Show Config' ? '- Hide Config' : '+ Show Config';
                            });
                        });

                        function checkModule() {
                            const selectedModuleText = $('#partner_name').val();
                            if (selectedModuleText == 'FinGate') {
                                $('#simulateTransactionButton').show();
                                $('#syncStatementButton').show();
                                $('#showCardProviders').show();
                            } else {
                                $('.edit-config-wrap').show();

                                $('#simulateTransactionButton').hide();
                                $('#syncStatementButton').hide();
                                $('#showCardProviders').hide();

                            }
                        }

                        checkModule();

                        $('#module_id').on('change', function() {
                            checkModule();
                        });


                        $('#card_id').select2({
                            dropdownParent: $('#simulateTransactionModal'),
                            width: '100%'
                        });

                        $('#simulateTransactionButton').on('click', function() {
                            $('#simulateTransactionModal').modal('show');
                        });

                        $('#syncBeneficiaryFieldsButton').on('click', function() {
                            $('#syncBeneficiaryFieldsModal').modal('show');
                        });

                        $('#syncAllBeneficiaryFieldsButton').on('click', function() {
                            $('#syncAllBeneficiaryFieldsModal').modal('show');
                        });

                        $('#syncStatementButton').on('click', function(e) {
                            toggleSpinner(true);
                            fetch('{{ url('/fingate-run-transaction') }}', {
                                    method: 'POST',
                                    headers: {
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                        'Content-Type': 'application/json',
                                    },
                                })
                                .then(response => response.json())
                                .then(data => {
                                    toggleSpinner(false);
                                    if (data.success) {
                                        showAlert(data.message, 'success');
                                    } else {
                                        showAlert(data.message, 'error');
                                    }
                                })
                                .catch(error => {
                                    toggleSpinner(false);
                                    showAlert("Something went wrong. Please try again.", 'error');
                                });
                        });

                        $('#simulateTransactionModal form').on('submit', function() {
                            toggleSpinner(true);
                        });

                        $('#showCardProviders').on('click', function() {
                            toggleSpinner(true);
                            fetch('{{ url('/fingate-card-providers') }}', {
                                    method: 'GET',
                                    headers: {
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                        'Content-Type': 'application/json',
                                    },
                                })
                                .then(response => response.json())
                                .then(data => {
                                    toggleSpinner(false);
                                    if (data.success) {
                                        showAlert(data.message, 'success');
                                        $("#fingate-card-providers").empty().html(
                                            '<div class="row"><div class="col-md-12 mt-4"><h4>Fingate Card Providers</h4></div></div>'
                                        ); // clear old rows if any
                                        data.data.forEach(item => {
                                            const row = `
                                                        <div class="row align-items-center mb-2 provider-row p-2 border rounded shadow-sm" data-id="${item.Id}">
                                                            <div class="col col-md-3">
                                                                <strong>ID:  ${item.Id} </strong>
                                                            </div>
                                                            <div class="col col-md-3">
                                                                <strong> ${item.Description}</strong>
                                                            </div>
                                                             <div class="col col-md-3">
                                                                <strong> ${item.ParameterKey}</strong>
                                                            </div>
                                                            <div class="col-auto col-md-3">
                                                                <button class="btn btn-primary btn-sm select-provider-btn" data-key="${item.Id}">
                                                                    <i class="fal fa-check-circle me-1"></i> Select
                                                                </button>
                                                            </div>
                                                        </div>
                                                    `;
                                            $("#fingate-card-providers").append(row);
                                        });


                                        console.log(data);
                                    } else {
                                        showAlert(data.message, 'error');
                                    }
                                })
                                .catch(error => {
                                    toggleSpinner(false);
                                    showAlert("Something went wrong. Please try again.", 'error');
                                });
                        });
                        // Button click handler
                        $(document).on("click", ".select-provider-btn", function() {
                            const id = $(this).closest(".provider-row").data("id");
                            const key = $(this).data("key");
                            $('#provider_id').val(id);
                            return false;
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

                            if ($('[data-key="' + attributeName + '"]').length) {
                                alert('A field with this attribute name already exists.');
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
                                type: selectedType,
                                id: newFieldId,
                                name: 'configurations[' + attributeName + ']',
                                class: selectedType == "checkbox" ? "form-group custom-control-input" :
                                    "form-control",
                                value: selectedType == "checkbox" ? 1 : "",
                                required: selectedType == "checkbox" ? false : true,
                            });

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

                        $(document).on('click', '.delete-config-field', function() {
                            const $col = $(this).closest('.config-field-col');
                            const label = $(this).data('label') || 'this field';
                            if (confirm('Are you sure you want to delete "' + label + '"?')) {
                                $col.remove();
                            }
                        });

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
                    });

                    $(document).ready(function() {
                        $('#show_credentials_toggle').on('change', function(e) {
                            if ($(this).is(':checked')) {
                                $(this).prop('checked', false);
                                $('#reveal_otp').val('').removeClass('is-invalid');
                                $('#revealCredentialsModal').modal('show');
                            } else {
                                // If turning OFF, re-mask the credentials locally
                                $('.config-secret-field').each(function() {
                                    if ($(this).data('has-stored-value') == '1') {
                                        $(this).val('').attr('placeholder', $(this).data('mask'));
                                        $(this).data('edited', 0);
                                    }
                                });
                                showAlert('Credentials securely hidden.', 'success');
                            }
                        });

                        // 2. Clear invalid states when typing
                        $('#reveal_otp').on('input', function() {
                            this.value = this.value.replace(/[^0-9]/g, '');
                            $(this).removeClass('is-invalid');
                        });

                        // 3. Process the OTP via AJAX
                        $('#verifyRevealOtpBtn').on('click', function() {
                            const otp = $('#reveal_otp').val().trim();
                            const configId = $('#id').val();

                            if (!otp || otp.length !== 6) {
                                $('#reveal_otp_error').text('Please enter a valid 6-digit code.');
                                $('#reveal_otp').addClass('is-invalid');
                                return;
                            }

                            toggleSpinner(true);
                            $('#verifyRevealOtpBtn').prop('disabled', true);

                            $.ajax({
                                url: '{{ route('module-configurations.reveal-secrets') }}',
                                type: 'POST',
                                data: {
                                    _token: '{{ csrf_token() }}',
                                    id: configId,
                                    otp: otp
                                },
                                success: function(response) {
                                    toggleSpinner(false);
                                    $('#verifyRevealOtpBtn').prop('disabled', false);

                                    if (response.success) {
                                        $('#revealCredentialsModal').modal('hide');

                                        $('#show_credentials_toggle').prop('checked', true);

                                        // Loop through the received secrets and map them to their HTML IDs
                                        const secrets = response.secrets;
                                        $('.config-secret-field').each(function() {
                                            const fieldKey = $(this).attr('id');

                                            if (secrets && secrets[fieldKey] !== undefined) {
                                                $(this).val(secrets[fieldKey]);

                                                // If it's a password field it is shown as dots. 
                                                $(this).data('edited', 1);
                                            }
                                        });

                                        showAlert('Credentials successfully revealed.', 'success');
                                    } else {
                                        $('#reveal_otp_error').text(response.message);
                                        $('#reveal_otp').addClass('is-invalid');
                                    }
                                },
                                error: function(xhr) {
                                    toggleSpinner(false);
                                    $('#verifyRevealOtpBtn').prop('disabled', false);
                                    $('#reveal_otp_error').text('Server error occurred. Please try again.');
                                    $('#reveal_otp').addClass('is-invalid');
                                }
                            });
                        });

                        $('#revealCredentialsModal').on('shown.bs.modal', function() {
                            $('#reveal_otp').focus();
                        });
                    });
                </script>
            @stop
