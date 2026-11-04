<script>
(function ($) {
    if (window.FireblocksMonitoringActions) {
        return;
    }

    const settings = {
        csrf: '{{ csrf_token() }}',
        baseUrl: '{{ url('/fireblocks/transaction-monitoring') }}',
        defaultFeeLevel: '{{ strtoupper($defaultFeeLevel ?? 'HIGH') }}',
    };

    let pendingConfirmCallback = null;
    let activeBoostTxId = null;
    let activeResponseTarget$ = null;

    function resolveResponseTarget(txId, $fallback) {
        if (activeResponseTarget$ && activeResponseTarget$.length) {
            return activeResponseTarget$;
        }
        if ($fallback && $fallback.length) {
            return $fallback;
        }
        const $scoped = $('.fb-monitoring-actions[data-tx-id="' + txId + '"]').find('.fb-action-response').first();
        if ($scoped.length) {
            return $scoped;
        }
        return $('#tx-action-response');
    }

    function showActionResponse($target, res, isError) {
        const cls = isError ? 'alert-danger' : (res.dry_run ? 'alert-warning' : (res.queued ? 'alert-info' : 'alert-success'));
        let html = '<div class="alert ' + cls + ' mb-0">';
        html += '<strong>' + (isError ? 'Action failed' : 'Action result') + ':</strong> ' + (res.message || 'Done');
        if (res.replacement_tx_id) html += '<br><small>Replacement TX: ' + res.replacement_tx_id + '</small>';
        if (res.new_tx_id) html += '<br><small>New TX: ' + res.new_tx_id + '</small>';
        html += '</div>';
        $target.removeClass('d-none').html(html);
    }

    function postAction(txId, path, payload, $responseTarget, onSuccess) {
        if (!txId) return;

        $.post(settings.baseUrl + '/' + encodeURIComponent(txId) + path, $.extend({ _token: settings.csrf }, payload || {}))
            .done(function (res) {
                showActionResponse($responseTarget, res, !res.success);
                if (res.success && typeof onSuccess === 'function') {
                    onSuccess(res);
                }
            })
            .fail(function (xhr) {
                showActionResponse($responseTarget, { message: xhr.responseJSON?.message || 'Request failed' }, true);
            });
    }

    function openConfirm(title, message, onConfirm) {
        pendingConfirmCallback = onConfirm;
        $('#fb-confirm-modal-title').text(title);
        $('#fb-confirm-modal-body').html('<p class="mb-0">' + message + '</p>');
        $('#fb-confirm-modal').modal('show');
    }

    function selectedFeeLevel() {
        return $('input.fb-boost-fee-level:checked').val() || settings.defaultFeeLevel;
    }

    window.FireblocksMonitoringActions = {
        setResponseTarget: function ($el) {
            activeResponseTarget$ = $el && $el.length ? $el : null;
        },

        clearResponseTarget: function () {
            activeResponseTarget$ = null;
        },

        runSync: function (txId, $responseTarget, onSuccess) {
            postAction(txId, '/sync', {}, resolveResponseTarget(txId, $responseTarget), onSuccess);
        },

        openBoostModal: function (txId, $responseTarget) {
            activeBoostTxId = txId;
            activeResponseTarget$ = $responseTarget && $responseTarget.length ? $responseTarget : null;
            if (!$('input.fb-boost-fee-level:checked').length) {
                $('input.fb-boost-fee-level[value="' + settings.defaultFeeLevel + '"]').prop('checked', true);
            }
            $('#fb-boost-modal').modal('show');
        },

        runDrop: function (txId, $responseTarget, onSuccess) {
            const $target = resolveResponseTarget(txId, $responseTarget);
            openConfirm(
                'Drop EVM Transaction',
                'Drop this stuck EVM transaction? This replaces it with a zero-value self-transfer at higher gas to free the nonce.',
                function () {
                    postAction(txId, '/drop', {}, $target, onSuccess);
                }
            );
        },

        runResubmit: function (txId, $responseTarget, onSuccess) {
            const $target = resolveResponseTarget(txId, $responseTarget);
            openConfirm(
                'Resubmit Withdrawal',
                'Resubmit this failed withdrawal as a new Fireblocks transaction with the same destination and amount?',
                function () {
                    postAction(txId, '/resubmit', {}, $target, onSuccess);
                }
            );
        },

        applyButtonState: function ($container, actions, txId) {
            if (!$container || !$container.length) {
                return;
            }

            if (txId) {
                $container.attr('data-tx-id', txId);
                $container.find('[data-tx-id]').attr('data-tx-id', txId);
            }

            const map = {
                sync: '.fb-btn-sync',
                boost: '.fb-btn-boost',
                drop: '.fb-btn-drop',
                resubmit: '.fb-btn-resubmit',
            };

            $.each(map, function (key, selector) {
                const $btn = $container.find(selector);
                if (!$btn.length) {
                    return;
                }

                const state = (actions || {})[key] || {};
                if (!state.visible) {
                    $btn.addClass('d-none').prop('disabled', true).removeAttr('title');
                    return;
                }

                $btn.removeClass('d-none');
                $btn.prop('disabled', !state.enabled);
                if (state.enabled) {
                    $btn.removeAttr('title');
                } else {
                    $btn.attr('title', state.disabled_reason || '');
                }
            });
        },
    };

    $('#fb-confirm-modal-proceed').on('click', function () {
        $('#fb-confirm-modal').modal('hide');
        if (typeof pendingConfirmCallback === 'function') {
            pendingConfirmCallback();
        }
        pendingConfirmCallback = null;
    });

    $('#fb-boost-modal-confirm').on('click', function () {
        const txId = activeBoostTxId;
        const feeLevel = selectedFeeLevel();
        const gasPrice = $('#fb_boost_gas_price').val().trim();
        const $responseTarget = resolveResponseTarget(txId, activeResponseTarget$);

        $('#fb-boost-modal').modal('hide');

        openConfirm(
            'Confirm Boost',
            'Boost (RBF) this transaction using fee level <strong>' + feeLevel + '</strong>' +
                (gasPrice ? ' and gas price <strong>' + gasPrice + ' gwei</strong>' : '') + '?',
            function () {
                postAction(txId, '/boost', { fee_level: feeLevel, gas_price: gasPrice }, $responseTarget, function () {
                    if (typeof window.fbMonitoringReloadDetail === 'function') {
                        window.fbMonitoringReloadDetail(txId);
                    }
                    if (typeof window.fbMonitoringReloadGrid === 'function') {
                        window.fbMonitoringReloadGrid();
                    }
                    if (typeof window.fbMonitoringReloadLedger === 'function') {
                        window.fbMonitoringReloadLedger();
                    }
                });
            }
        );
    });

    $(document).on('click', '.fb-btn-sync', function () {
        const txId = $(this).data('tx-id');
        const $response = resolveResponseTarget(txId, $(this).closest('.fb-monitoring-actions').find('.fb-action-response'));
        postAction(txId, '/sync', {}, $response, function () {
            if (typeof window.fbMonitoringReloadDetail === 'function') {
                window.fbMonitoringReloadDetail(txId);
            }
            if (typeof window.fbMonitoringReloadGrid === 'function') {
                window.fbMonitoringReloadGrid();
            }
            if (typeof window.fbMonitoringReloadLedger === 'function') {
                window.fbMonitoringReloadLedger();
            }
        });
    });

    $(document).on('click', '.fb-btn-boost', function () {
        if ($(this).prop('disabled')) {
            return;
        }
        const txId = $(this).data('tx-id');
        const $response = $(this).closest('.fb-monitoring-actions').find('.fb-action-response');
        FireblocksMonitoringActions.openBoostModal(txId, $response.length ? $response : null);
    });

    $(document).on('click', '.fb-btn-drop', function () {
        if ($(this).prop('disabled')) {
            return;
        }
        const txId = $(this).data('tx-id');
        const $response = $(this).closest('.fb-monitoring-actions').find('.fb-action-response');
        FireblocksMonitoringActions.runDrop(txId, $response.length ? $response : null, function () {
            if (typeof window.fbMonitoringReloadDetail === 'function') {
                window.fbMonitoringReloadDetail(txId);
            }
            if (typeof window.fbMonitoringReloadGrid === 'function') {
                window.fbMonitoringReloadGrid();
            }
            if (typeof window.fbMonitoringReloadLedger === 'function') {
                window.fbMonitoringReloadLedger();
            }
        });
    });

    $(document).on('click', '.fb-btn-resubmit', function () {
        if ($(this).prop('disabled')) {
            return;
        }
        const txId = $(this).data('tx-id');
        const $response = $(this).closest('.fb-monitoring-actions').find('.fb-action-response');
        FireblocksMonitoringActions.runResubmit(txId, $response.length ? $response : null, function () {
            if (typeof window.fbMonitoringReloadDetail === 'function') {
                window.fbMonitoringReloadDetail(txId);
            }
            if (typeof window.fbMonitoringReloadGrid === 'function') {
                window.fbMonitoringReloadGrid();
            }
            if (typeof window.fbMonitoringReloadLedger === 'function') {
                window.fbMonitoringReloadLedger();
            }
        });
    });
})(jQuery);
</script>
