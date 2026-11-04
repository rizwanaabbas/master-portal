<style>
    #zenus-account-wizard-modal .modal-header .close { color: #fff; text-shadow: none; opacity: .85; }
    #zenus-account-wizard-modal .zw-kicker { font-size: .7rem; letter-spacing: .08em; text-transform: uppercase; opacity: .85; margin-bottom: .15rem; }
    #zenus-account-wizard-modal .zw-meta { margin-top: .45rem; display: flex; flex-wrap: wrap; gap: .4rem; }
    #zenus-account-wizard-modal .zw-chip {
        background: rgba(255,255,255,.2); border-radius: 999px; padding: .15rem .6rem;
        font-size: .75rem; font-weight: 500;
    }
    #zenus-account-wizard-modal .zw-stepper { list-style: none; margin: 0; padding: 0; max-height: 70vh; overflow-y: auto; }
    #zenus-account-wizard-modal .zw-step {
        display: flex; align-items: flex-start; gap: .75rem; padding: .45rem 0; position: relative;
    }
    #zenus-account-wizard-modal .zw-step:not(:last-child)::before {
        content: ''; position: absolute; left: 13px; top: 32px; bottom: -6px; width: 2px; background: #e6eaf0;
    }
    #zenus-account-wizard-modal .zw-step.is-done:not(:last-child)::before { background: #28a745; }
    #zenus-account-wizard-modal .zw-dot {
        width: 28px; height: 28px; border-radius: 50%; flex-shrink: 0; display: flex; align-items: center; justify-content: center;
        font-size: .72rem; font-weight: 700; background: #eef2f6; color: #6c757d; z-index: 1;
    }
    #zenus-account-wizard-modal .zw-step.is-done .zw-dot { background: #28a745; color: #fff; }
    #zenus-account-wizard-modal .zw-step.is-current .zw-dot { background: var(--primary, #886ab5); color: #fff; }
    #zenus-account-wizard-modal .zw-step.is-error .zw-dot { background: #dc3545; color: #fff; }
    #zenus-account-wizard-modal .zw-step-label { font-weight: 600; font-size: .88rem; margin: 0; }
    #zenus-account-wizard-modal .zw-step-info { font-size: .78rem; color: #6c757d; margin: .1rem 0 0; line-height: 1.4; }
    #zenus-account-wizard-modal .zw-panel {
        background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 4px; padding: 1rem;
        min-height: 100%;
    }
    #zenus-account-wizard-modal .zw-panel h6 { font-size: .72rem; text-transform: uppercase; letter-spacing: .06em; color: #6c757d; margin-bottom: .35rem; }
    #zenus-account-wizard-modal .zw-panel .zw-current-title { font-size: 1.05rem; font-weight: 600; margin-bottom: .4rem; }
    #zenus-account-wizard-modal .zw-alert { margin-top: .75rem; margin-bottom: 0; }
    #zenus-account-wizard-modal .zw-fix-body { max-height: 360px; overflow-y: auto; padding-right: .25rem; }
</style>
<div class="modal fade" id="zenus-account-wizard-modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <div>
                    <div class="zw-kicker">Zenus Bank</div>
                    <h5 class="modal-title">Create virtual account</h5>
                    <div class="zw-meta" id="zenus-wizard-meta"></div>
                </div>
                <button type="button" class="close" id="zenus-wizard-close" aria-label="Close">
                    <span aria-hidden="true"><i class="fal fa-times"></i></span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6 pr-md-3 mb-3 mb-md-0">
                        <ol class="zw-stepper" id="zenus-wizard-steps"></ol>
                    </div>
                    <div class="col-md-6">
                        <div class="zw-panel" id="zenus-wizard-step-panel">
                            <h6>Current step</h6>
                            <div class="zw-current-title" id="zenus-wizard-current-title">—</div>
                            <p class="zw-step-info mb-0" id="zenus-wizard-current-info">Each step calls Zenus separately. If a step fails, fix the issue and retry without restarting the whole flow.</p>
                            <div id="zenus-wizard-alert" class="alert zw-alert d-none" role="alert"></div>
                            <div id="zenus-wizard-docs" class="d-none mt-3">
                                <p id="zenus-wizard-docs-message" class="mb-2"></p>
                                <ul id="zenus-wizard-docs-list" class="list-unstyled mb-2"></ul>
                                <div class="d-flex flex-wrap" style="gap:.4rem;">
                                    <button type="button" class="btn btn-primary btn-sm" id="zenus-wizard-docs-upload">Upload pending</button>
                                    <button type="button" class="btn btn-outline-primary btn-sm" id="zenus-wizard-docs-reupload">Reupload</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm d-none" id="zenus-wizard-docs-skip-file">Skip this file</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" id="zenus-wizard-docs-skip">Skip rest</button>
                                </div>
                            </div>
                            <div id="zenus-wizard-exists" class="d-none mt-3">
                                <p class="mb-1 font-weight-bold">Current value at Zenus</p>
                                <p id="zenus-wizard-current-value" class="mb-2"></p>
                                <div id="zenus-wizard-soc-wrap">
                                    <label class="form-label mb-1" for="zenus-wizard-soc">Occupation (SOC)</label>
                                    <select id="zenus-wizard-soc" class="form-control mb-2"></select>
                                </div>
                                <div class="d-flex flex-wrap" style="gap:.4rem;">
                                    <button type="button" class="btn btn-primary btn-sm" id="zenus-wizard-update">Update</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" id="zenus-wizard-skip">Skip</button>
                                </div>
                            </div>
                        </div>
                        <div class="zw-panel d-none" id="zenus-wizard-fix-panel">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <h6 class="mb-0" id="zenus-wizard-fix-title">Update details</h6>
                                <button type="button" class="btn btn-sm btn-secondary" id="zenus-wizard-fix-cancel">Back</button>
                            </div>
                            <div id="zenus-wizard-fix-error" class="alert alert-danger d-none" role="alert"></div>
                            <div id="zenus-wizard-fix-body" class="zw-fix-body">Loading…</div>
                            <button type="button" class="btn btn-primary btn-sm mt-2" id="zenus-wizard-fix-save">Save</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary mr-auto" id="zenus-wizard-clear">Clear state</button>
                <button type="button" class="btn btn-secondary d-none" id="zenus-wizard-back">Back</button>
                <button type="button" class="btn btn-info d-none" id="zenus-wizard-fix">Fix details</button>
                <button type="button" class="btn btn-warning d-none" id="zenus-wizard-retry"><i class="fal fa-redo mr-1"></i> Retry</button>
                <button type="button" class="btn btn-primary" id="zenus-wizard-next">Next <i class="fal fa-arrow-right ml-1"></i></button>
            </div>
        </div>
    </div>
</div>
<script>
    (function () {
        var accountId = null;
        var currentStep = null;
        var running = false;
        var autoContinue = false;
        var stopAuto = false;
        var currentFix = null;
        var csrf = '{{ csrf_token() }}';
        var wizardUrl = '{{ url('/client-management/clients/zenus-account-wizard') }}';
        var clearUrl = '{{ url('/client-management/clients/zenus-account-wizard/clear') }}';
        var fixUrl = '{{ url('/client-management/clients/zenus-account-wizard/fix') }}';

        function jq() {
            return window.jQuery || window.$;
        }

        function setAlert(type, message) {
            var $ = jq();
            if (!$) return;
            var $el = $('#zenus-wizard-alert');
            if (!message) {
                $el.addClass('d-none').text('');
                return;
            }
            $el.removeClass('d-none alert-danger alert-success alert-warning alert-info')
                .addClass('alert-' + type)
                .text(message);
        }

        function uniqueBits(parts) {
            var seen = {};
            return parts.filter(function (part) {
                part = (part || '').toString().trim();
                if (!part) return false;
                var key = part.toLowerCase();
                if (seen[key]) return false;
                seen[key] = true;
                return true;
            });
        }

        function escapeHtml(value) {
            return String(value || '').replace(/[&<>"']/g, function (ch) {
                return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[ch];
            });
        }

        function renderMeta(res) {
            var $ = jq();
            if (!$) return;
            var chips = uniqueBits([
                res.client_name,
                res.account_number ? 'Acc ' + res.account_number : '',
                res.currency
            ]);
            var html = chips.map(function (c) {
                return '<span class="zw-chip">' + escapeHtml(c) + '</span>';
            }).join('');
            $('#zenus-wizard-meta').html(html || '');
        }

        function renderProgress(progress, errored) {
            var $ = jq();
            var html = '';
            var current = null;
            (progress.steps || []).forEach(function (step, idx) {
                var state = 'upcoming';
                if (step.done) state = 'done';
                else if (progress.next === step.key) state = errored ? 'error' : 'current';
                if (state === 'current' || state === 'error') current = step;
                var mark = step.done ? '<i class="fal fa-check"></i>' : (idx + 1);
                html += '<li class="zw-step is-' + state + '">' +
                    '<div class="zw-dot">' + mark + '</div>' +
                    '<div><p class="zw-step-label">' + escapeHtml(step.label) + '</p>' +
                    '<p class="zw-step-info">' + escapeHtml(step.info || '') + '</p></div></li>';
            });
            currentStep = progress.next || null;
            if ($) {
                $('#zenus-wizard-steps').html(html);
                if (current) {
                    $('#zenus-wizard-current-title').text(current.label);
                    $('#zenus-wizard-current-info').text(current.info || '');
                } else if (!currentStep) {
                    $('#zenus-wizard-current-title').text('All steps complete');
                    $('#zenus-wizard-current-info').text('The Zenus virtual account is linked. You can close this window and refresh the client accounts list.');
                }
                if (!currentStep) {
                    // $('#zenus-wizard-next').prop('disabled', false).html('Done');
                    $('#zenus-wizard-next').prop('disabled', false).html('Done').always(function () {
                        running = false;
                        // $('#zenus-wizard-next, #zenus-wizard-update, #zenus-wizard-skip, #zenus-wizard-back, #zenus-wizard-docs-upload, #zenus-wizard-docs-reupload, #zenus-wizard-docs-skip, #zenus-wizard-docs-skip-file').prop('disabled', false);
                        $('#zenus-wizard-next').prop('disabled', !currentStep);
                    });
                    $('#zenus-wizard-retry').addClass('d-none');
                    $('#zenus-wizard-fix').addClass('d-none');
                    $('#zenus-wizard-back').addClass('d-none');
                } else {
                    $('#zenus-wizard-next').prop('disabled', false).html('Next <i class="fal fa-arrow-right ml-1"></i>');
                    if (progress.previous) {
                        $('#zenus-wizard-back').removeClass('d-none');
                    } else {
                        $('#zenus-wizard-back').addClass('d-none');
                    }
                }
                showExists(progress.exists || null);
                showDocuments(progress.documents || null);
            }
        }

        var docFocus = null;

        function showDocuments(panel) {
            var $ = jq();
            if (!$) return;
            var box = $('#zenus-wizard-docs');
            if (!panel || !panel.rows) {
                box.addClass('d-none');
                docFocus = null;
                return;
            }
            stopAuto = true;
            box.removeClass('d-none');
            $('#zenus-wizard-docs-message').text(panel.message || 'Review the files already on this client.');
            docFocus = panel.failed_type || null;
            var html = '';
            panel.rows.forEach(function (row) {
                var mark = row.status === 'done' ? '✓' : (row.status === 'skipped' ? '–' : (row.status === 'failed' || row.status === 'missing' ? '!' : '•'));
                var detail = row.name || (row.present ? 'File on client' : 'Not uploaded yet');
                if (row.error) detail += ' — ' + row.error;
                if (!docFocus && row.focus && row.status !== 'done' && row.status !== 'skipped') {
                    docFocus = row.type;
                }
                html += '<li class="mb-1' + (row.focus ? ' font-weight-bold' : '') + '">' +
                    escapeHtml(mark + ' ' + (row.label || row.type) + ' — ' + detail) + '</li>';
            });
            $('#zenus-wizard-docs-list').html(html);
            if (panel.failed_type) {
                $('#zenus-wizard-docs-skip-file').removeClass('d-none');
            } else {
                $('#zenus-wizard-docs-skip-file').addClass('d-none');
            }
            $('#zenus-wizard-next').addClass('d-none');
            setAlert(panel.failed_type ? 'warning' : 'info', panel.message || '');
        }

        function showExists(choice) {
            var $ = jq();
            if (!$) return;
            var box = $('#zenus-wizard-exists');
            if (!choice) {
                box.addClass('d-none');
                $('#zenus-wizard-next').removeClass('d-none');
                return;
            }
            stopAuto = true;
            box.removeClass('d-none');
            $('#zenus-wizard-current-value').text(choice.current_value || 'Already saved at Zenus');
            var select = document.getElementById('zenus-wizard-soc');
            var options = choice.options || [];
            var selected = choice.new_value || choice.current_value || '';
            if (choice.soc && options.length && select) {
                var html = '<option value="">Choose occupation code</option>';
                options.forEach(function (row) {
                    var code = row.code || '';
                    html += '<option value="' + escapeHtml(code) + '"' + (code === selected ? ' selected' : '') + '>' +
                        escapeHtml((row.title || code) + ' (' + code + ')') + '</option>';
                });
                select.innerHTML = html;
                $('#zenus-wizard-soc-wrap').removeClass('d-none');
            } else {
                $('#zenus-wizard-soc-wrap').addClass('d-none');
            }
            $('#zenus-wizard-next').addClass('d-none');
            $('#zenus-wizard-retry').addClass('d-none');
            setAlert('warning', choice.message || 'This value already exists at Zenus. Update it or skip to the next step.');
        }

        function applyFixButton(fix) {
            var $ = jq();
            currentFix = fix || null;
            if (!$) return;
            if (currentFix && currentFix.kind) {
                $('#zenus-wizard-fix').removeClass('d-none').text(currentFix.label || 'Fix details');
            } else {
                $('#zenus-wizard-fix').addClass('d-none');
                showFixPanel(false);
            }
        }

        function closeWizard() {
            var $ = jq();
            if (!$) return;
            $('#zenus-account-wizard-modal').modal('hide');
        }

        function clearWizardState() {
            var $ = jq();
            if (!$ || !accountId || running) return;
            if (!window.confirm('Clear saved wizard progress for this account and start from the first step?')) {
                return;
            }
            running = true;
            stopAuto = true;
            $.ajax({
                url: clearUrl,
                method: 'POST',
                data: { account_id: accountId, _token: csrf },
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
            }).done(function (res) {
                var progress = (res && res.progress) || {};
                autoContinue = !!progress.auto_continue;
                renderProgress(progress, false);
                applyFixButton(null);
                showFixPanel(false);
                setAlert('success', (res && res.message) || 'Wizard progress cleared.');
                $('#zenus-wizard-retry').addClass('d-none');
            }).fail(function (xhr) {
                setAlert('danger', (xhr.responseJSON && xhr.responseJSON.message) || 'Could not clear wizard progress.');
            }).always(function () {
                running = false;
            });
        }

        function showFixPanel(show) {
            var $ = jq();
            if (!$) return;
            if (show) {
                $('#zenus-wizard-step-panel').addClass('d-none');
                $('#zenus-wizard-fix-panel').removeClass('d-none');
            } else {
                $('#zenus-wizard-fix-panel').addClass('d-none');
                $('#zenus-wizard-step-panel').removeClass('d-none');
            }
        }

        function maybeAutoContinue() {
            if (!autoContinue || stopAuto || !currentStep || running) {
                return;
            }
            setTimeout(function () {
                if (!autoContinue || stopAuto || running || !currentStep) {
                    return;
                }
                window.runZenusWizardStep();
            }, 450);
        }

        window.openZenusAccountWizard = function (id) {
            var $ = jq();
            accountId = id;
            currentStep = null;
            running = false;
            stopAuto = false;
            currentFix = null;
            if (!$) {
                return;
            }
            setAlert('info', 'Loading wizard…');
            $('#zenus-wizard-next').prop('disabled', true);
            $('#zenus-wizard-fix, #zenus-wizard-retry').addClass('d-none');
            showFixPanel(false);
            $('#zenus-account-wizard-modal').modal('show');
            $.get(wizardUrl, { account_id: id })
                .done(function (res) {
                    var progress = res.progress || {};
                    autoContinue = !!progress.auto_continue;
                    renderMeta(res);
                    var hasError = !!progress.last_error && !progress.exists;
                    renderProgress(progress, hasError);
                    applyFixButton(progress.fix);
                    if (progress.exists || progress.documents) {
                        stopAuto = true;
                    } else {
                    setAlert('', '');
                    }
                    if (progress.documents) {
                        showDocuments(progress.documents);
                    } else if (progress.exists) {
                        showExists(progress.exists);
                    } else if (hasError) {
                        stopAuto = true;
                        setAlert('danger', progress.last_error);
                        $('#zenus-wizard-retry').removeClass('d-none').prop('disabled', false);
                        if (progress.fix && progress.fix.kind) {
                            openFixModal();
                        }
                    } else if (progress.message) {
                        setAlert('info', progress.message);
                    }
                    if (autoContinue && !hasError) {
                        setAlert('info', 'Auto continue is on. Running the next step…');
                        maybeAutoContinue();
                    }
                })
                .fail(function (xhr) {
                    setAlert('danger', (xhr.responseJSON && xhr.responseJSON.message) || 'Failed to load wizard.');
                    $('#zenus-wizard-next').prop('disabled', true);
                });
        };

        window.runZenusWizardStep = function (action) {
            var $ = jq();
            if (!$ || !accountId || !currentStep || running) {
                return;
            }
            running = true;
            $('#zenus-wizard-next, #zenus-wizard-retry, #zenus-wizard-update, #zenus-wizard-skip, #zenus-wizard-back, #zenus-wizard-docs-upload, #zenus-wizard-docs-reupload, #zenus-wizard-docs-skip, #zenus-wizard-docs-skip-file').prop('disabled', true);
            setAlert('info', action === 'back' ? 'Going back…' : 'Checking Zenus status, then running this step…');
            var payload = {
                _token: csrf,
                account_id: accountId,
                step: currentStep
            };
            if (action) {
                payload.action = action;
            }
            if (action === 'update') {
                payload.soc_code = $('#zenus-wizard-soc').val() || '';
            }
            if (action === 'skip_file') {
                payload.doc_type = docFocus || '';
            }
            $.ajax({
                url: wizardUrl,
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf },
                data: payload
            }).done(function (res) {
                renderProgress(res.progress || {}, false);
                applyFixButton((res.progress && res.progress.fix) || null);
                if (res.needs_choice || (res.progress && (res.progress.exists || res.progress.documents))) {
                    stopAuto = true;
                    showExists((res.progress && res.progress.exists) || {
                        message: res.message,
                        current_value: res.current_value,
                        new_value: res.new_value,
                        soc: true
                    });
                } else {
                    stopAuto = false;
                    setAlert('success', res.message || 'Step completed.');
                    $('#zenus-wizard-retry').addClass('d-none');
                    maybeAutoContinue();
                }
            }).fail(function (xhr) {
                var body = xhr.responseJSON || {};
                stopAuto = true;
                setAlert('danger', body.message || 'Step failed. You can retry.');
                if (body.progress) {
                    renderProgress(body.progress, true);
                    applyFixButton(body.fix || body.progress.fix);
                } else {
                    applyFixButton(body.fix);
                }
                $('#zenus-wizard-retry').removeClass('d-none').prop('disabled', false);
                if (currentFix && currentFix.kind) {
                    openFixModal();
                }
            }).always(function () {
                running = false;
                $('#zenus-wizard-next, #zenus-wizard-update, #zenus-wizard-skip, #zenus-wizard-back, #zenus-wizard-docs-upload, #zenus-wizard-docs-reupload, #zenus-wizard-docs-skip, #zenus-wizard-docs-skip-file').prop('disabled', false);
                $('#zenus-wizard-next').prop('disabled', !currentStep);
            });
        };

        function openFixModal() {
            var $ = jq();
            if (!$ || !accountId || !currentFix || !currentFix.kind) {
                return;
            }
            $('#zenus-wizard-fix-title').text(currentFix.label || 'Update details');
            $('#zenus-wizard-fix-error').addClass('d-none').text('');
            $('#zenus-wizard-fix-body').text('Loading…');
            showFixPanel(true);
            $.get(fixUrl, { account_id: accountId, kind: currentFix.kind })
                .done(function (html) {
                    $('#zenus-wizard-fix-body').html(html);
                })
                .fail(function () {
                    $('#zenus-wizard-fix-body').text('Unable to load the form.');
                });
        }

        function fixErrorMessage(xhr) {
            var body = xhr.responseJSON || null;
            if (body && body.errors) {
                var parts = [];
                Object.keys(body.errors).forEach(function (key) {
                    var list = body.errors[key];
                    if (list && list.length) {
                        parts.push(list[0]);
                    }
                });
                if (parts.length) {
                    return parts.join(' ');
                }
            }
            if (body && body.message && body.message !== 'The given data was invalid.' && body.message !== 'Server Error') {
                return body.message;
            }
            if (xhr.status === 413) {
                return 'The file is larger than the server upload limit.';
            }
            return 'The file could not be uploaded. Check the file type and size, then try again.';
        }

        function saveFixForm() {
            var $ = jq();
            if (!$) return;
            var form = document.getElementById('zenus-wizard-fix-form');
            if (!form) {
                return;
            }
            var data = new FormData(form);
            data.append('account_id', accountId);
            data.append('_token', csrf);
            $('#zenus-wizard-fix-error').addClass('d-none').text('');
            $('#zenus-wizard-fix-save').prop('disabled', true);
            $.ajax({
                url: fixUrl,
                method: 'POST',
                data: data,
                processData: false,
                contentType: false,
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
            }).done(function (res) {
                showFixPanel(false);
                setAlert('success', (res && res.message) || 'Saved. Retrying this step…');
                stopAuto = false;
                window.runZenusWizardStep();
            }).fail(function (xhr) {
                var msg = fixErrorMessage(xhr);
                var $err = $('#zenus-wizard-fix-error');
                $err.removeClass('d-none').text(msg);
                showFixPanel(true);
            }).always(function () {
                $('#zenus-wizard-fix-save').prop('disabled', false);
            });
        }

        document.addEventListener('click', function (event) {
            var target = event.target && event.target.closest
                ? event.target.closest('#zenus-wizard-next, #zenus-wizard-retry, #zenus-wizard-fix, #zenus-wizard-fix-save, #zenus-wizard-fix-cancel, #zenus-wizard-close, #zenus-wizard-clear, #zenus-wizard-back, #zenus-wizard-update, #zenus-wizard-skip, #zenus-wizard-docs-upload, #zenus-wizard-docs-reupload, #zenus-wizard-docs-skip, #zenus-wizard-docs-skip-file')
                : null;
            if (!target) {
                return;
            }
            event.preventDefault();
            if (target.id === 'zenus-wizard-close') {
                closeWizard();
                return;
            }
            if (target.id === 'zenus-wizard-clear') {
                clearWizardState();
                return;
            }
            if (target.id === 'zenus-wizard-back') {
                window.runZenusWizardStep('back');
                return;
            }
            if (target.id === 'zenus-wizard-update') {
                window.runZenusWizardStep('update');
                return;
            }
            if (target.id === 'zenus-wizard-skip' || target.id === 'zenus-wizard-docs-skip') {
                window.runZenusWizardStep('skip');
                return;
            }
            if (target.id === 'zenus-wizard-docs-upload') {
                window.runZenusWizardStep('upload_missing');
                return;
            }
            if (target.id === 'zenus-wizard-docs-reupload') {
                window.runZenusWizardStep('reupload');
                return;
            }
            if (target.id === 'zenus-wizard-docs-skip-file') {
                window.runZenusWizardStep('skip_file');
                return;
            }
            if (target.id === 'zenus-wizard-fix-cancel') {
                showFixPanel(false);
                return;
            }
            if (target.id === 'zenus-wizard-fix') {
                openFixModal();
                return;
            }
            if (target.id === 'zenus-wizard-fix-save') {
                saveFixForm();
                return;
            }
            if (target.id === 'zenus-wizard-next' && !currentStep) {
                closeWizard();
                window.location.reload();
                return;
            }
            window.runZenusWizardStep();
        });
    })();
</script>
