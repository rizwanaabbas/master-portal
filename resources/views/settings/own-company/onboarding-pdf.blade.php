@extends('layouts.cp')
@section('title', 'Onboarding PDF Options - Own Company')

@section('content')
    <div class="subheader">
        <h1 class="subheader-title">
            <span class="fw-300">Onboarding PDF Options</span>
        </h1>
        <a href="{{ url('/settings/own-company-details') }}" class="btn btn-primary mr-3">
            <i class="fal fa-arrow-alt-left"></i> Go Back
        </a>
    </div>

    <div class="panel">
        <div class="panel-hdr">
            <h2>Configure all onboarding PDFs</h2>
        </div>
        <div class="panel-container show">
            <div class="panel-content">
                @include('common.error-message')

                <p class="text-muted mb-3">
                    Controls <code>individual</code>, <code>company</code>, <code>letter-of-indemnity</code>, and
                    <code>board-resolution</code>. Branding still comes from Own Company fields
                    (name, address, license, logo). Live preview updates as you change options.
                </p>

                <div class="row mb-3">
                    <div class="col-lg-7">
                        <div class="form-inline mb-3">
                            <label class="mr-2 font-weight-bold" for="quick-preset">Bank preset</label>
                            <select id="quick-preset" class="form-control mr-2">
                                @foreach ($presets as $p)
                                    <option value="{{ $p }}" @if (($pdfOptions['preset'] ?? '') === $p) selected @endif>
                                        {{ $presetLabels[$p] ?? ucfirst($p) }}
                                    </option>
                                @endforeach
                            </select>
                            <button type="button" id="load-preset-btn" class="btn btn-outline-secondary">
                                Load preset
                            </button>
                            <button type="button" id="save-preset-btn" class="btn btn-outline-primary ml-2"
                                title="Save this bank preset to Own Company now">
                                Apply &amp; save
                            </button>
                        </div>
                        <p class="small text-muted mb-3">
                            Load fills every option (documents, features, labels, colors) from the system-core bank preset.
                            Apply &amp; save writes it immediately; or edit first then Save PDF options.
                        </p>

                        <form method="post" action="{{ url('/settings/own-company/onboarding-pdf') }}" id="pdf-options-form">
                            @csrf
                            <input type="hidden" name="preset" id="form-preset" value="{{ $pdfOptions['preset'] ?? 'custom' }}">

                            <h5>Documents to generate</h5>
                            <div class="row mb-3">
                                @foreach ($documents as $doc)
                                    <div class="col-md-6 mb-2">
                                        <div class="custom-control custom-switch">
                                            <input type="checkbox" class="custom-control-input live-input"
                                                id="doc_{{ $doc }}" name="doc_{{ $doc }}" value="1"
                                                @if (!empty($pdfOptions['documents'][$doc])) checked @endif>
                                            <label class="custom-control-label" for="doc_{{ $doc }}">
                                                {{ str_replace('_', ' ', $doc) }}
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <h5>Shared</h5>
                            <div class="form-group" style="max-width: 220px;">
                                <label for="legal_entity_suffix">Legal suffix (e.g. Ltd.)</label>
                                <input type="text" class="form-control live-input" id="legal_entity_suffix"
                                    name="legal_entity_suffix"
                                    value="{{ old('legal_entity_suffix', $pdfOptions['labels']['legal_entity_suffix'] ?? 'Ltd.') }}">
                            </div>
                            <div class="custom-control custom-switch mb-3">
                                <input type="checkbox" class="custom-control-input live-input" id="show_license_in_title"
                                    name="show_license_in_title" value="1"
                                    @if (!isset($pdfOptions['features']['show_license_in_title']) || $pdfOptions['features']['show_license_in_title']) checked @endif>
                                <label class="custom-control-label" for="show_license_in_title">Show license in application titles</label>
                            </div>

                            <hr>
                            <h5>Individual / Company application</h5>
                            <div class="row">
                                <div class="col-md-6 mb-2">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input live-input" id="show_race"
                                            name="show_race" value="1"
                                            @if (!empty($pdfOptions['features']['show_race'])) checked @endif>
                                        <label class="custom-control-label" for="show_race">Show Race (individual)</label>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input live-input"
                                            id="show_maker_checker_labels" name="show_maker_checker_labels" value="1"
                                            @if (!empty($pdfOptions['features']['show_maker_checker_labels'])) checked @endif>
                                        <label class="custom-control-label" for="show_maker_checker_labels">Maker / Checker captions</label>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-2">
                                    <label>Maker caption</label>
                                    <input type="text" class="form-control live-input" name="maker_caption"
                                        value="{{ old('maker_caption', $pdfOptions['labels']['maker_caption'] ?? '') }}">
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label>Checker caption</label>
                                    <input type="text" class="form-control live-input" name="checker_caption"
                                        value="{{ old('checker_caption', $pdfOptions['labels']['checker_caption'] ?? '') }}">
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label>Individual form title</label>
                                    <input type="text" class="form-control live-input" name="individual_form_title"
                                        value="{{ old('individual_form_title', $pdfOptions['labels']['individual_form_title'] ?? '') }}">
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label>Company form title</label>
                                    <input type="text" class="form-control live-input" name="company_form_title"
                                        value="{{ old('company_form_title', $pdfOptions['labels']['company_form_title'] ?? '') }}">
                                </div>
                            </div>
                            <div class="form-group mt-2">
                                <label for="product_checklist">Account type checklist</label>
                                <select name="product_checklist" id="product_checklist" class="form-control live-input" style="max-width:280px;">
                                    @php $pc = $pdfOptions['features']['product_checklist'] ?? 'wealth'; @endphp
                                    <option value="wealth" @if ($pc === 'wealth') selected @endif>Wealth</option>
                                    <option value="banking" @if ($pc === 'banking') selected @endif>Banking</option>
                                    <option value="custom" @if ($pc === 'custom') selected @endif>Custom lists</option>
                                </select>
                            </div>
                            <div class="row" id="custom-lists">
                                <div class="col-md-6 mb-2">
                                    <label>Left list (one per line)</label>
                                    <textarea class="form-control live-input" name="account_types_left" rows="4">{{ old('account_types_left', implode("\n", $pdfOptions['account_types_left'] ?? [])) }}</textarea>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label>Right list (one per line)</label>
                                    <textarea class="form-control live-input" name="account_types_right" rows="4">{{ old('account_types_right', implode("\n", $pdfOptions['account_types_right'] ?? [])) }}</textarea>
                                </div>
                            </div>
                            <div class="form-group" style="max-width:420px;">
                                <label>Race options (one per line)</label>
                                <textarea class="form-control live-input" name="race_options" rows="3">{{ old('race_options', implode("\n", $pdfOptions['race_options'] ?? [])) }}</textarea>
                            </div>

                            <hr>
                            <h5>Letter of Indemnity</h5>
                            <div class="row">
                                <div class="col-md-6 mb-2">
                                    <label>Title</label>
                                    <input type="text" class="form-control live-input" name="loi_title"
                                        value="{{ old('loi_title', $pdfOptions['labels']['loi_title'] ?? '') }}">
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label>Salutation</label>
                                    <input type="text" class="form-control live-input" name="loi_salutation"
                                        value="{{ old('loi_salutation', $pdfOptions['labels']['loi_salutation'] ?? '') }}">
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label>Individual account label</label>
                                    <input type="text" class="form-control live-input" name="individual_account_label"
                                        value="{{ old('individual_account_label', $pdfOptions['labels']['individual_account_label'] ?? '') }}">
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label>Company account label</label>
                                    <input type="text" class="form-control live-input" name="company_account_label"
                                        value="{{ old('company_account_label', $pdfOptions['labels']['company_account_label'] ?? '') }}">
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-md-4">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input live-input" id="show_loi_witness"
                                            name="show_loi_witness" value="1"
                                            @if (!isset($pdfOptions['features']['show_loi_witness']) || $pdfOptions['features']['show_loi_witness']) checked @endif>
                                        <label class="custom-control-label" for="show_loi_witness">Witness block</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input live-input" id="show_loi_company_position"
                                            name="show_loi_company_position" value="1"
                                            @if (!isset($pdfOptions['features']['show_loi_company_position']) || $pdfOptions['features']['show_loi_company_position']) checked @endif>
                                        <label class="custom-control-label" for="show_loi_company_position">Position in company</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input live-input" id="show_loi_letterhead_note"
                                            name="show_loi_letterhead_note" value="1"
                                            @if (!isset($pdfOptions['features']['show_loi_letterhead_note']) || $pdfOptions['features']['show_loi_letterhead_note']) checked @endif>
                                        <label class="custom-control-label" for="show_loi_letterhead_note">Letterhead note</label>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Letterhead note text</label>
                                <input type="text" class="form-control live-input" name="loi_letterhead_note"
                                    value="{{ old('loi_letterhead_note', $pdfOptions['labels']['loi_letterhead_note'] ?? '') }}">
                            </div>

                            <hr>
                            <h5>Board Resolution</h5>
                            <div class="row">
                                <div class="col-md-6 mb-2">
                                    <label>Resolved heading</label>
                                    <input type="text" class="form-control live-input" name="board_resolved_heading"
                                        value="{{ old('board_resolved_heading', $pdfOptions['labels']['board_resolved_heading'] ?? '') }}">
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label>Directors heading</label>
                                    <input type="text" class="form-control live-input" name="board_directors_heading"
                                        value="{{ old('board_directors_heading', $pdfOptions['labels']['board_directors_heading'] ?? '') }}">
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label>MIA clause text</label>
                                    <input type="text" class="form-control live-input" name="board_mia_text"
                                        value="{{ old('board_mia_text', $pdfOptions['labels']['board_mia_text'] ?? '') }}">
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label>Letterhead note</label>
                                    <input type="text" class="form-control live-input" name="board_letterhead_note"
                                        value="{{ old('board_letterhead_note', $pdfOptions['labels']['board_letterhead_note'] ?? '') }}">
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input live-input" id="show_board_signatory_clause"
                                            name="show_board_signatory_clause" value="1"
                                            @if (!isset($pdfOptions['features']['show_board_signatory_clause']) || $pdfOptions['features']['show_board_signatory_clause']) checked @endif>
                                        <label class="custom-control-label" for="show_board_signatory_clause">Authorised signatory clause</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input live-input" id="show_board_mia_clause"
                                            name="show_board_mia_clause" value="1"
                                            @if (!isset($pdfOptions['features']['show_board_mia_clause']) || $pdfOptions['features']['show_board_mia_clause']) checked @endif>
                                        <label class="custom-control-label" for="show_board_mia_clause">MIA clause</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input live-input" id="show_board_letterhead_note"
                                            name="show_board_letterhead_note" value="1"
                                            @if (!isset($pdfOptions['features']['show_board_letterhead_note']) || $pdfOptions['features']['show_board_letterhead_note']) checked @endif>
                                        <label class="custom-control-label" for="show_board_letterhead_note">Letterhead note</label>
                                    </div>
                                </div>
                            </div>

                            <hr>
                            <h5>Colors <small class="text-muted">(system-core defaults; configurable)</small></h5>
                            <p class="small text-muted">Section green, accent blue, and title navy match system-core. Change any value for a custom bank look.</p>
                            <div class="row mb-3">
                                @php
                                    $pdfColors = $pdfOptions['colors'] ?? [];
                                @endphp
                                @foreach ($colorKeys as $ck)
                                    <div class="col-md-4 col-lg-3 mb-2">
                                        <label class="d-block small mb-1" for="color_{{ $ck }}">{{ $colorLabels[$ck] ?? $ck }}</label>
                                        <div class="d-flex align-items-center">
                                            <input type="color" class="form-control live-input p-1"
                                                style="width: 52px; height: 38px;"
                                                id="color_{{ $ck }}" name="color_{{ $ck }}"
                                                value="{{ old('color_' . $ck, $pdfColors[$ck] ?? '#FFFFFF') }}">
                                            <input type="text" class="form-control form-control-sm ml-2 live-input color-hex"
                                                data-color-key="{{ $ck }}"
                                                value="{{ old('color_' . $ck, $pdfColors[$ck] ?? '#FFFFFF') }}"
                                                maxlength="7" style="max-width: 96px;">
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <button type="submit" class="btn btn-primary" id="save-pdf-options">
                                <i class="fal fa-save"></i> Save PDF options
                            </button>
                            <a href="{{ url('/settings/own-company-details') }}" class="btn btn-secondary">Cancel</a>
                        </form>

                        <form method="post" action="{{ url('/settings/own-company/onboarding-pdf/preset') }}" id="preset-save-form" class="d-none">
                            @csrf
                            <input type="hidden" name="preset" id="preset-save-value" value="">
                        </form>
                    </div>

                    <div class="col-lg-5">
                        <div class="border rounded p-3 bg-white shadow-sm sticky-top" style="top: 12px;">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <h5 class="mb-0">Live preview</h5>
                                <small class="text-muted" id="preview-status">Updating…</small>
                            </div>
                            <div class="btn-group btn-group-sm mb-2 flex-wrap" role="group" id="preview-doc-tabs">
                                <button type="button" class="btn btn-outline-primary active preview-doc-btn" data-doc="individual">Individual</button>
                                <button type="button" class="btn btn-outline-primary preview-doc-btn" data-doc="company">Company</button>
                                <button type="button" class="btn btn-outline-primary preview-doc-btn" data-doc="letter_of_indemnity">LOI</button>
                                <button type="button" class="btn btn-outline-primary preview-doc-btn" data-doc="board_resolution">Board</button>
                            </div>
                            <div class="border rounded mb-2" style="height: 520px; overflow: hidden; background: #f7f7f7;">
                                <iframe id="live-preview-frame" title="Onboarding PDF live preview"
                                    style="width:100%; height:100%; border:0; background:#fff;"></iframe>
                            </div>
                            <a href="#" id="open-pdf-preview" class="btn btn-sm btn-outline-secondary" target="_blank">
                                <i class="fal fa-file-pdf"></i> Open as PDF
                            </a>
                            <p class="small text-muted mt-2 mb-0">
                                Preview uses sample applicant data and your current (unsaved) form values.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop

@section('scripts')
    <script>
        (function() {
            var form = document.getElementById('pdf-options-form');
            var frame = document.getElementById('live-preview-frame');
            var statusEl = document.getElementById('preview-status');
            var pdfLink = document.getElementById('open-pdf-preview');
            var currentDoc = 'individual';
            var timer = null;
            var liveUrl = @json(url('/settings/own-company/onboarding-pdf/live-preview'));
            var pdfUrl = @json(url('/settings/own-company/onboarding-pdf/preview'));
            var csrf = form ? form.querySelector('input[name="_token"]').value : '';
            var presetConfigs = @json($presetConfigs);
            var presetLabels = @json($presetLabels);

            function setStatus(text) {
                if (statusEl) statusEl.textContent = text;
            }

            function setChecked(name, on) {
                var el = form.querySelector('[name="' + name + '"]');
                if (el && el.type === 'checkbox') el.checked = !!on;
            }

            function setValue(name, value) {
                var el = form.querySelector('[name="' + name + '"]');
                if (!el) return;
                if (el.type === 'checkbox') {
                    el.checked = !!value;
                } else {
                    el.value = value == null ? '' : value;
                }
            }

            function setColor(key, hex) {
                if (!hex) return;
                hex = String(hex).toUpperCase();
                if (hex.charAt(0) !== '#') hex = '#' + hex;
                var picker = form.querySelector('#color_' + key);
                var text = form.querySelector('.color-hex[data-color-key="' + key + '"]');
                if (picker) picker.value = hex;
                if (text) text.value = hex;
            }

            function loadPresetIntoForm(key) {
                var cfg = presetConfigs[key];
                if (!cfg) return false;
                var features = cfg.features || {};
                var labels = cfg.labels || {};
                var docs = cfg.documents || {};
                var colors = cfg.colors || {};

                document.getElementById('form-preset').value = key;

                Object.keys(docs).forEach(function(doc) {
                    setChecked('doc_' + doc, docs[doc]);
                });

                setChecked('show_race', features.show_race);
                setChecked('show_maker_checker_labels', features.show_maker_checker_labels);
                setChecked('show_license_in_title', features.show_license_in_title !== false);
                setChecked('show_loi_witness', features.show_loi_witness !== false);
                setChecked('show_loi_company_position', features.show_loi_company_position !== false);
                setChecked('show_loi_letterhead_note', features.show_loi_letterhead_note !== false);
                setChecked('show_board_signatory_clause', features.show_board_signatory_clause !== false);
                setChecked('show_board_mia_clause', features.show_board_mia_clause !== false);
                setChecked('show_board_letterhead_note', features.show_board_letterhead_note !== false);

                setValue('product_checklist', features.product_checklist || 'wealth');
                setValue('legal_entity_suffix', labels.legal_entity_suffix);
                setValue('maker_caption', labels.maker_caption);
                setValue('checker_caption', labels.checker_caption);
                setValue('individual_form_title', labels.individual_form_title);
                setValue('company_form_title', labels.company_form_title);
                setValue('individual_account_label', labels.individual_account_label);
                setValue('company_account_label', labels.company_account_label);
                setValue('loi_title', labels.loi_title);
                setValue('loi_salutation', labels.loi_salutation);
                setValue('loi_letterhead_note', labels.loi_letterhead_note);
                setValue('board_resolved_heading', labels.board_resolved_heading);
                setValue('board_directors_heading', labels.board_directors_heading);
                setValue('board_mia_text', labels.board_mia_text);
                setValue('board_letterhead_note', labels.board_letterhead_note);

                setValue('account_types_left', (cfg.account_types_left || []).join('\n'));
                setValue('account_types_right', (cfg.account_types_right || []).join('\n'));
                setValue('race_options', (cfg.race_options || []).join('\n'));

                Object.keys(colors).forEach(function(ck) {
                    setColor(ck, colors[ck]);
                });

                return true;
            }

            function formData() {
                var fd = new FormData(form);
                fd.set('doc', currentDoc);
                form.querySelectorAll('input[type="checkbox"]').forEach(function(cb) {
                    if (!cb.checked) fd.set(cb.name, '0');
                });
                // Prefer color picker values (hex text mirrors them)
                form.querySelectorAll('input[type="color"][name^="color_"]').forEach(function(picker) {
                    fd.set(picker.name, picker.value);
                });
                return fd;
            }

            function refreshPreview() {
                if (!form || !frame) return;
                setStatus('Updating…');
                var fd = formData();
                fetch(liveUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'text/html'
                    },
                    body: fd,
                    credentials: 'same-origin'
                }).then(function(r) { return r.text(); }).then(function(html) {
                    frame.srcdoc = html;
                    setStatus('Live · ' + currentDoc.replace(/_/g, ' '));
                }).catch(function() {
                    setStatus('Preview failed');
                });

                if (pdfLink) {
                    pdfLink.href = pdfUrl + '?doc=' + encodeURIComponent(currentDoc);
                    pdfLink.onclick = function(e) {
                        e.preventDefault();
                        var f = document.createElement('form');
                        f.method = 'POST';
                        f.action = pdfUrl;
                        f.target = '_blank';
                        var token = document.createElement('input');
                        token.type = 'hidden';
                        token.name = '_token';
                        token.value = csrf;
                        f.appendChild(token);
                        formData().forEach(function(value, key) {
                            var input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = key;
                            input.value = value;
                            f.appendChild(input);
                        });
                        document.body.appendChild(f);
                        f.submit();
                        document.body.removeChild(f);
                    };
                }
            }

            function schedule() {
                clearTimeout(timer);
                timer = setTimeout(refreshPreview, 350);
            }

            document.querySelectorAll('.preview-doc-btn').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    document.querySelectorAll('.preview-doc-btn').forEach(function(b) {
                        b.classList.remove('active');
                    });
                    btn.classList.add('active');
                    currentDoc = btn.getAttribute('data-doc');
                    refreshPreview();
                });
            });

            if (form) {
                form.querySelectorAll('.live-input').forEach(function(el) {
                    el.addEventListener('input', schedule);
                    el.addEventListener('change', schedule);
                });

                // Keep color text + picker in sync
                form.querySelectorAll('input[type="color"][name^="color_"]').forEach(function(picker) {
                    picker.addEventListener('input', function() {
                        var key = picker.name.replace(/^color_/, '');
                        var text = form.querySelector('.color-hex[data-color-key="' + key + '"]');
                        if (text) text.value = picker.value.toUpperCase();
                        // Editing a color marks config as custom unless still on a bank preset intentionally
                        schedule();
                    });
                });
                form.querySelectorAll('.color-hex').forEach(function(text) {
                    text.addEventListener('change', function() {
                        var hex = text.value.trim();
                        if (hex.charAt(0) !== '#') hex = '#' + hex;
                        if (/^#[0-9A-Fa-f]{6}$/.test(hex)) {
                            text.value = hex.toUpperCase();
                            var picker = form.querySelector('#color_' + text.getAttribute('data-color-key'));
                            if (picker) picker.value = hex;
                            schedule();
                        }
                    });
                });
            }

            var loadBtn = document.getElementById('load-preset-btn');
            var savePresetBtn = document.getElementById('save-preset-btn');
            var quickPreset = document.getElementById('quick-preset');

            function confirmModal(opts) {
                return Swal.fire({
                    title: opts.title || 'Are you sure?',
                    text: opts.text || '',
                    icon: opts.icon || 'warning',
                    showCancelButton: true,
                    confirmButtonText: opts.confirmText || 'Yes',
                    cancelButtonText: opts.cancelText || 'Cancel'
                }).then(function(result) {
                    return !!(result.value || result.isConfirmed);
                });
            }

            function notifyModal(opts) {
                return Swal.fire({
                    title: opts.title || 'Done',
                    text: opts.text || '',
                    icon: opts.icon || 'success',
                    confirmButtonText: opts.confirmText || 'OK'
                });
            }

            if (loadBtn && quickPreset) {
                loadBtn.addEventListener('click', function() {
                    var key = quickPreset.value;
                    var label = presetLabels[key] || key;
                    confirmModal({
                        title: 'Load preset?',
                        text: 'Load full "' + label + '" preset (layout, labels, colors)? Unsaved edits will be replaced in the form.',
                        confirmText: 'Yes, load'
                    }).then(function(ok) {
                        if (!ok) return;
                        if (loadPresetIntoForm(key)) {
                            setStatus('Loaded ' + label);
                            refreshPreview();
                            notifyModal({
                                title: 'Preset loaded',
                                text: '"' + label + '" options are in the form. Save PDF options when ready.',
                                icon: 'success'
                            });
                        }
                    });
                });
            }

            if (savePresetBtn && quickPreset) {
                savePresetBtn.addEventListener('click', function() {
                    var key = quickPreset.value;
                    var label = presetLabels[key] || key;
                    confirmModal({
                        title: 'Apply & save preset?',
                        text: 'Apply and save "' + label + '" preset to Own Company now?',
                        confirmText: 'Yes, apply & save'
                    }).then(function(ok) {
                        if (!ok) return;
                        document.getElementById('preset-save-value').value = key;
                        document.getElementById('preset-save-form').submit();
                    });
                });
            }

            // Save keeps the currently loaded preset key (bank or custom)
            var saveBtn = document.getElementById('save-pdf-options');
            if (saveBtn) {
                saveBtn.addEventListener('click', function() {
                    // If user changed fields after a bank load, keep that preset name unless they picked Custom
                    var key = quickPreset ? quickPreset.value : 'custom';
                    document.getElementById('form-preset').value = key;
                });
            }

            refreshPreview();
        })();
    </script>
@stop
