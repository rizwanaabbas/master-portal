@once('fireblocks-sweep-confirm-modal')
<div class="modal fade" id="sweepConfirmModal" tabindex="-1" role="dialog" aria-labelledby="sweepConfirmTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-fusion-50 border-bottom-0 pb-0">
                <div>
                    <h5 class="modal-title font-weight-bold" id="sweepConfirmTitle">Confirm action</h5>
                    <small class="text-muted" id="sweepConfirmSubtitle"></small>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body pt-3">
                <div id="sweepConfirmBody" class="mb-0"></div>

                <div id="sweepConfirmPriorRun" class="d-none mt-3">
                    <div class="alert alert-warning mb-2 py-2">
                        <div class="d-flex align-items-start">
                            <i class="fal fa-history fa-lg mr-2 mt-1"></i>
                            <div>
                                <strong>This rule has already been run</strong>
                                <div id="sweepConfirmPriorRunText" class="small mt-1 mb-0"></div>
                            </div>
                        </div>
                    </div>
                    <div id="sweepConfirmInFlightWarn" class="alert alert-danger py-2 d-none">
                        <small><i class="fal fa-exclamation-triangle mr-1"></i>
                            A sweep is currently in flight for this rule. Resubmitting may create duplicate transactions.
                        </small>
                    </div>
                    <div id="sweepConfirmForceWrap" class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="sweepForceResubmit">
                        <label class="custom-control-label" for="sweepForceResubmit">
                            Skip idempotency checks and resubmit
                        </label>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="sweepConfirmBtn" disabled>
                    Confirm
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(function ($) {
    'use strict';

    var $modal = $('#sweepConfirmModal');
    var $pendingForm = null;

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function truthy(value) {
        return value === 1 || value === '1' || value === true;
    }

    function resetModalState() {
        $('#sweepConfirmPriorRun').addClass('d-none');
        $('#sweepConfirmForceWrap').addClass('d-none');
        $('#sweepConfirmInFlightWarn').addClass('d-none');
        $('#sweepForceResubmit').prop('checked', false);
        $('#sweepConfirmSubtitle').text('');
        $('#sweepConfirmBtn')
            .removeClass('btn-danger btn-success btn-primary')
            .addClass('btn-primary')
            .prop('disabled', false)
            .text('Confirm');
    }

    function showConfirmModal($form) {
        resetModalState();

        var type = $form.data('sweep-confirm-type') || 'generic';
        var title = $form.data('sweep-confirm-title') || 'Confirm action';
        var body = $form.data('sweep-confirm-body') || 'Are you sure you want to continue?';
        var variant = $form.data('sweep-confirm-variant') || 'primary';
        var confirmLabel = $form.data('sweep-confirm-button') || 'Confirm';

        if (type === 'manual-run') {
            var ruleName = $form.data('sweep-rule-name') || 'this rule';
            var hasPrior = truthy($form.data('sweep-has-prior-run'));
            var priorMsg = $form.data('sweep-prior-message') || '';
            var inFlight = truthy($form.data('sweep-in-flight'));
            var dryRun = truthy($form.data('sweep-dry-run'));

            title = 'Manual sweep';
            body = 'Run manual sweep for <strong>' + escapeHtml(ruleName) + '</strong>?';

            if (dryRun) {
                body += ' <span class="text-warning d-block mt-2"><i class="fal fa-flask mr-1"></i>Rule dry run is ON — jobs will be logged only, no live Fireblocks transactions.</span>';
            } else {
                body += ' Qualifying vaults will submit a <strong>new</strong> Fireblocks transaction.';
            }

            if (hasPrior) {
                $('#sweepConfirmPriorRun').removeClass('d-none');
                $('#sweepConfirmForceWrap').removeClass('d-none');
                $('#sweepConfirmPriorRunText').html(escapeHtml(priorMsg).replace(/\n/g, '<br>'));

                if (inFlight) {
                    $('#sweepConfirmInFlightWarn').removeClass('d-none');
                }

                confirmLabel = 'Skip idempotency & resubmit';
                $('#sweepConfirmBtn').prop('disabled', true);
            } else {
                confirmLabel = 'Run manual sweep';
            }
        }

        if (type === 'delete') {
            title = title || 'Delete sweep rule';
            confirmLabel = $form.data('sweep-confirm-button') || 'Delete rule';
            variant = 'danger';
        }

        $('#sweepConfirmTitle').text(title);
        $('#sweepConfirmBody').html(body);

        var $btn = $('#sweepConfirmBtn');
        $btn.removeClass('btn-primary btn-success btn-danger');

        if (variant === 'danger') {
            $btn.addClass('btn-danger');
        } else if (variant === 'success') {
            $btn.addClass('btn-success');
        } else {
            $btn.addClass('btn-primary');
        }

        $btn.text(confirmLabel);

        if (!truthy($form.data('sweep-has-prior-run')) || type !== 'manual-run') {
            $btn.prop('disabled', false);
        }

        $modal.modal('show');
    }

    $(document).on('submit', '.js-sweep-confirm-form', function (e) {
        e.preventDefault();
        $pendingForm = $(this);
        showConfirmModal($pendingForm);
    });

    $('#sweepForceResubmit').on('change', function () {
        $('#sweepConfirmBtn').prop('disabled', !this.checked);
    });

    $('#sweepConfirmBtn').on('click', function () {
        if (!$pendingForm || !$pendingForm.length) {
            return;
        }

        var type = $pendingForm.data('sweep-confirm-type') || 'generic';
        var hasPrior = truthy($pendingForm.data('sweep-has-prior-run'));

        if (type === 'manual-run' && hasPrior) {
            if (!$('#sweepForceResubmit').is(':checked')) {
                return;
            }
            $pendingForm.find('input[name="force_resubmit"]').remove();
            $('<input>', { type: 'hidden', name: 'force_resubmit', value: '1' }).appendTo($pendingForm);
        }

        var formEl = $pendingForm[0];
        $pendingForm.off('submit');
        $modal.modal('hide');
        formEl.submit();
        $pendingForm = null;
    });

    $modal.on('hidden.bs.modal', function () {
        $pendingForm = null;
        resetModalState();
    });
})(jQuery);
</script>
@endonce
