(function ($) {
    'use strict';

    const STORAGE_KEY = 'liveTransactionsTrayOpen';
    const cfg = window.__liveTransactionsConfig || {};

    const state = {
        open: false,
        page: 1,
        pagination: { page: 1, last_page: 1, total: 0 },
        items: new Map(),
        searchFilter: '',
        statusFilter: 'all',
        pollTimer: null,
        lastSince: null,
        pendingCount: 0,
        pollOpenMs: (cfg.poll_interval_open_seconds || 3) * 1000,
        pollClosedMs: (cfg.poll_interval_closed_seconds || 8) * 1000,
        loadingPage: false,
    };

    function init() {
        const $root = $('#live-transactions-tray-root');
        if (!$root.length) {
            return;
        }

        state.open = localStorage.getItem(STORAGE_KEY) === '1';
        $root.toggleClass('is-open', state.open);

        bindEvents($root);
        loadPage(1, true);
        schedulePoll();
    }

    function bindEvents($root) {
        $root.on('click', '.ltt-tab', toggleTray);
        $root.on('click', '[data-ltt-close]', function () { setOpen(false); });

        $root.on('input', '#ltt-search', function () {
            state.searchFilter = $(this).val().trim().toLowerCase();
            applyClientFilters();
        });

        $root.on('click', '.ltt-filter-pill', function () {
            $root.find('.ltt-filter-pill').removeClass('active');
            $(this).addClass('active');
            state.statusFilter = $(this).data('filter') || 'all';
            applyClientFilters();
        });

        $root.on('click', '#ltt-prev-page', function () {
            if (state.page > 1) {
                loadPage(state.page - 1, true);
            }
        });

        $root.on('click', '#ltt-next-page', function () {
            if (state.page < state.pagination.last_page) {
                loadPage(state.page + 1, true);
            }
        });
    }

    function toggleTray() {
        setOpen(!state.open);
    }

    function setOpen(open) {
        state.open = open;
        $('#live-transactions-tray-root').toggleClass('is-open', open);
        localStorage.setItem(STORAGE_KEY, open ? '1' : '0');
        schedulePoll();
        if (open) {
            pollDelta();
        }
    }

    function schedulePoll() {
        if (state.pollTimer) {
            clearInterval(state.pollTimer);
        }
        const interval = state.open ? state.pollOpenMs : state.pollClosedMs;
        state.pollTimer = setInterval(pollDelta, interval);
    }

    /** Background tray requests must not trigger global ajaxStart / page loaders. */
    function trayRequest(params) {
        return $.ajax({
            url: '/live-transactions/feed',
            method: 'GET',
            data: params,
            global: false,
        });
    }

    function loadPage(page, fullReplace) {
        if (state.loadingPage) {
            return;
        }
        state.loadingPage = true;

        trayRequest({ page: page })
            .done(function (response) {
                if (!response || !response.success) {
                    return;
                }

                applyConfig(response.config);
                state.page = page;
                state.pagination = response.pagination || state.pagination;
                state.pendingCount = response.pending_count || 0;
                state.lastSince = response.cursor || response.server_time;

                if (fullReplace) {
                    state.items.clear();
                }

                (response.items || []).forEach(function (item) {
                    state.items.set(item.stream_key, item);
                });

                if (response.mode === 'page') {
                    syncPageDom(response.items || []);
                    enforcePageCardLimit();
                }

                updatePaginationUi();
                updateBadges();
                applyClientFilters();
            })
            .always(function () {
                state.loadingPage = false;
            });
    }

    function pollDelta() {
        if (!state.lastSince) {
            return;
        }

        trayRequest({
            since: state.lastSince,
            page: state.page,
        }).done(function (response) {
            if (!response || !response.success) {
                return;
            }

            applyConfig(response.config);
            state.lastSince = response.cursor || response.server_time;
            state.pendingCount = response.pending_count ?? state.pendingCount;
            state.pagination = response.pagination || state.pagination;

            if (response.mode === 'delta' && response.items && response.items.length) {
                applyDelta(response.items);
            } else if (response.mode === 'page' && state.page === 1 && Array.isArray(response.items)) {
                (response.items || []).forEach(function (item) {
                    state.items.set(item.stream_key, item);
                });
                syncPageDom(response.items);
                applyClientFilters();
            }

            updateBadges();
            if (response.mode !== 'delta' || (response.pagination && response.pagination.page !== state.pagination.page)) {
                updatePaginationUi();
            } else if (response.pagination) {
                const p = response.pagination;
                const prev = state.pagination;
                if (p.total !== prev.total || p.last_page !== prev.last_page) {
                    updatePaginationUi();
                }
            }
        });
    }

    function applyConfig(config) {
        if (!config) {
            return;
        }

        let pollChanged = false;

        if (config.poll_interval_open_ms && config.poll_interval_open_ms !== state.pollOpenMs) {
            state.pollOpenMs = config.poll_interval_open_ms;
            pollChanged = true;
        }
        if (config.poll_interval_closed_ms && config.poll_interval_closed_ms !== state.pollClosedMs) {
            state.pollClosedMs = config.poll_interval_closed_ms;
            pollChanged = true;
        }

        if (pollChanged) {
            schedulePoll();
        }
    }

    function applyDelta(items) {
        const $list = $('#ltt-list');

        items.forEach(function (item) {
            const prev = state.items.get(item.stream_key);
            const isNew = !prev;
            const isUpdated = prev && prev.updated_at !== item.updated_at;

            removeSupersededCards(item);
            state.items.set(item.stream_key, item);

            let $card = $list.find('.ltt-card[data-stream-key="' + cssEscape(item.stream_key) + '"]');

            if ($card.length) {
                updateCardElement($card, item);
                if (isUpdated) {
                    $card.addClass('ltt-updated');
                    setTimeout(function () { $card.removeClass('ltt-updated'); }, 1200);
                }
                return;
            }

            if (state.page === 1 && isNew) {
                $list.find('.ltt-empty').remove();
                $card = $(buildCardHtml(item)).addClass('ltt-new');
                $list.prepend($card);
                setTimeout(function () { $card.removeClass('ltt-new'); }, 400);
            }
        });

        enforcePageCardLimit();

        if (state.statusFilter !== 'all' || state.searchFilter) {
            applyClientFilters();
        }
    }

    function syncPageDom(items) {
        const $list = $('#ltt-list');
        const desiredKeys = items.map(function (i) { return i.stream_key; });

        $list.find('.ltt-card').each(function () {
            const key = cardStreamKey($(this));
            if (desiredKeys.indexOf(key) === -1) {
                $(this).remove();
            }
        });

        if (!items.length) {
            if (!$list.find('.ltt-empty').length) {
                $list.empty().append(
                    '<div class="ltt-empty"><i class="fal fa-stream fa-2x mb-2 d-block"></i>No live transactions match the current filters.</div>'
                );
            }
            return;
        }

        $list.find('.ltt-empty').remove();

        items.forEach(function (item, index) {
            let $card = $list.find('.ltt-card[data-stream-key="' + cssEscape(item.stream_key) + '"]');
            if ($card.length) {
                updateCardElement($card, item);
            } else {
                $card = $(buildCardHtml(item));
                const $ref = $list.find('.ltt-card').eq(index);
                if ($ref.length) {
                    $card.insertBefore($ref);
                } else {
                    $list.append($card);
                }
            }
        });

        while ($list.find('.ltt-card').length > items.length) {
            $list.find('.ltt-card').last().remove();
        }
    }

    function updateCardElement($card, item) {
        const tone = item.status_tone || 'pending';
        const directionMeta = resolveDirectionMeta(item);

        $card.attr('data-status-tone', tone);
        $card.attr('data-search', buildSearchBlob(item));
        $card.attr('data-tr-request-id', item.tr_request_id || '');
        $card.attr('data-fireblocks-tx-id', item.fireblocks_tx_id || '');
        $card.attr('data-account-transaction-id', item.account_transaction_id || '');
        $card.attr('data-liquidity-order-id', item.liquidity_order_id || '');

        setTextIfChanged($card.find('[data-field="portal"]'), item.portal_source || 'Portal');
        updateDirectionBadge($card, item, directionMeta);
        updateTypeBadge($card, item);
        setTextIfChanged($card.find('[data-field="flow-from"]'), item.flow_from || '—');
        $card.find('[data-field="flow-from"]').attr('title', item.flow_from || '');
        setTextIfChanged($card.find('[data-field="flow-to"]'), item.flow_to || '—');
        $card.find('[data-field="flow-to"]').attr('title', item.flow_to || '');
        setTextIfChanged($card.find('[data-field="asset-icon"]'), (item.asset_code || '—').substring(0, 4));
        setTextIfChanged($card.find('[data-field="asset-title"]'), formatAssetTitle(item));
        setTextIfChanged($card.find('[data-field="asset-sub"]'), item.network_name || item.transaction_nature || '');
        setTextIfChanged($card.find('[data-field="amount"]'), item.amount_display || '—');

        const $fiat = $card.find('[data-field="fiat"]');
        if (item.fiat_display) {
            setTextIfChanged($fiat, item.fiat_display);
            if (!$fiat.is(':visible')) {
                $fiat.show();
            }
        } else if ($fiat.is(':visible')) {
            $fiat.hide();
        }

        setTextIfChanged($card.find('[data-field="status"]'), item.status_label || 'Pending');
        setTextIfChanged($card.find('[data-field="meta"]'), formatMeta(item));

        const statusClass = 'ltt-status tone-' + tone;
        const $status = $card.find('.ltt-status');
        if ($status.attr('class') !== statusClass) {
            $status.attr('class', statusClass);
        }

        const progressClass = 'ltt-progress-bar tone-' + tone;
        const progressWidth = (item.progress_percent || 0) + '%';
        const $progress = $card.find('.ltt-progress-bar');
        if ($progress.attr('class') !== progressClass) {
            $progress.attr('class', progressClass);
        }
        if ($progress.css('width') !== progressWidth) {
            $progress.css('width', progressWidth);
        }

        const $ledger = $card.find('[data-field="ledger-link"]');
        if (item.ledger_url) {
            if (!$ledger.is('a')) {
                $ledger.replaceWith(
                    '<a href="' + escapeAttr(item.ledger_url) + '" class="ltt-ledger-btn" data-field="ledger-link" title="Open transaction"><i class="fal fa-external-link"></i></a>'
                );
            } else {
                if ($ledger.attr('href') !== item.ledger_url) {
                    $ledger.attr('href', item.ledger_url);
                }
                if (!$ledger.is(':visible')) {
                    $ledger.show();
                }
            }
        } else if ($ledger.length && $ledger.is(':visible')) {
            $ledger.hide();
        }
    }

    function setTextIfChanged($el, value) {
        const next = value == null ? '' : String(value);
        if ($el.text() !== next) {
            $el.text(next);
        }
    }

    function buildCardHtml(item) {
        const tone = item.status_tone || 'pending';
        const directionMeta = resolveDirectionMeta(item);
        const ledger = item.ledger_url
            ? '<a href="' + escapeAttr(item.ledger_url) + '" class="ltt-ledger-btn" data-field="ledger-link" title="Open transaction"><i class="fal fa-external-link"></i></a>'
            : '<span class="ltt-ledger-btn is-disabled d-none" data-field="ledger-link" title="No link available"><i class="fal fa-link-slash"></i></span>';

        return (
            '<article class="ltt-card" data-stream-key="' + escapeAttr(item.stream_key) + '" data-status-tone="' + escapeAttr(tone) + '" data-search="' + escapeAttr(buildSearchBlob(item)) + '" data-tr-request-id="' + escapeAttr(item.tr_request_id || '') + '" data-fireblocks-tx-id="' + escapeAttr(item.fireblocks_tx_id || '') + '" data-account-transaction-id="' + escapeAttr(item.account_transaction_id || '') + '" data-liquidity-order-id="' + escapeAttr(item.liquidity_order_id || '') + '">' +
                '<div class="ltt-card-top">' +
                    '<div class="ltt-portal-tag" data-field="portal">' + escapeHtml(item.portal_source || 'Portal') + '</div>' +
                    '<div class="ltt-card-badges">' +
                        directionBadgeHtml(item, directionMeta) +
                        '<span class="ltt-type-badge" data-field="tx-type">' + escapeHtml(item.transaction_type_label || formatTransactionType(item)) + '</span>' +
                    '</div>' +
                '</div>' +
                '<div class="ltt-flow">' +
                    '<span class="ltt-flow-box" data-field="flow-from" title="' + escapeAttr(item.flow_from || '') + '">' + escapeHtml(item.flow_from || '—') + '</span>' +
                    '<span class="ltt-flow-arrow"><i class="fal fa-chevron-right"></i></span>' +
                    '<span class="ltt-flow-box" data-field="flow-to" title="' + escapeAttr(item.flow_to || '') + '">' + escapeHtml(item.flow_to || '—') + '</span>' +
                '</div>' +
                '<div class="ltt-card-main">' +
                    '<div class="ltt-asset-icon" data-field="asset-icon">' + escapeHtml((item.asset_code || '—').substring(0, 4)) + '</div>' +
                    '<div class="ltt-asset-info">' +
                        '<p class="ltt-asset-title" data-field="asset-title">' + escapeHtml(formatAssetTitle(item)) + '</p>' +
                        '<p class="ltt-asset-sub" data-field="asset-sub">' + escapeHtml(item.network_name || item.transaction_nature || '') + '</p>' +
                    '</div>' +
                    '<div class="ltt-amounts">' +
                        '<p class="ltt-amount-primary" data-field="amount">' + escapeHtml(item.amount_display || '—') + '</p>' +
                        '<p class="ltt-amount-fiat" data-field="fiat"' + (item.fiat_display ? '' : ' style="display:none"') + '>' + escapeHtml(item.fiat_display || '') + '</p>' +
                        ledger +
                    '</div>' +
                '</div>' +
                '<div class="ltt-card-footer">' +
                    '<span class="ltt-status tone-' + tone + '"><i class="fal fa-info-circle"></i> <span data-field="status">' + escapeHtml(item.status_label || 'Pending') + '</span></span>' +
                    '<span class="ltt-meta-line" data-field="meta">' + escapeHtml(formatMeta(item)) + '</span>' +
                '</div>' +
                '<div class="ltt-progress"><div class="ltt-progress-bar tone-' + tone + '" style="width:' + (item.progress_percent || 0) + '%"></div></div>' +
            '</article>'
        );
    }

    function applyClientFilters() {
        const $cards = $('#ltt-list .ltt-card');
        if (!$cards.length) {
            return;
        }

        $cards.each(function () {
            const $card = $(this);
            const tone = ($card.attr('data-status-tone') || '').toString();
            const search = ($card.attr('data-search') || '').toString();

            let visible = true;
            if (state.statusFilter !== 'all' && tone !== state.statusFilter) {
                visible = false;
            }
            if (state.searchFilter && search.indexOf(state.searchFilter) === -1) {
                visible = false;
            }

            $card.toggleClass('ltt-hidden', !visible);
        });
    }

    function updateBadges() {
        $('#ltt-tab-badge').text(state.pendingCount || '');
        $('#ltt-count-badge').text(state.pagination.total || 0);
    }

    function updatePaginationUi() {
        const p = state.pagination;
        $('#ltt-page-info').text('Page ' + (p.page || 1) + ' of ' + (p.last_page || 1));
        $('#ltt-prev-page').prop('disabled', (p.page || 1) <= 1);
        $('#ltt-next-page').prop('disabled', (p.page || 1) >= (p.last_page || 1));
    }

    function resolveDirectionMeta(item) {
        const direction = (item.direction || '').toLowerCase();

        if (direction === 'incoming') {
            return { className: 'dir-incoming', icon: 'fa-arrow-down', label: item.direction_label || 'Incoming' };
        }

        if (direction === 'outgoing') {
            return { className: 'dir-outgoing', icon: 'fa-arrow-up', label: item.direction_label || 'Outgoing' };
        }

        return { className: 'dir-internal', icon: 'fa-arrows-h', label: item.direction_label || 'Internal' };
    }

    function directionBadgeHtml(item, directionMeta) {
        return (
            '<span class="ltt-direction ' + directionMeta.className + '" data-field="direction">' +
                '<i class="fal ' + directionMeta.icon + '"></i> ' +
                '<span data-field="direction-label">' + escapeHtml(directionMeta.label) + '</span>' +
            '</span>'
        );
    }

    function updateDirectionBadge($card, item, directionMeta) {
        const $badge = $card.find('[data-field="direction"]');
        const nextClass = 'ltt-direction ' + directionMeta.className;

        if ($badge.attr('class') !== nextClass) {
            $badge.attr('class', nextClass);
        }

        const $icon = $badge.find('i').first();
        const iconClass = 'fal ' + directionMeta.icon;
        if ($icon.attr('class') !== iconClass) {
            $icon.attr('class', iconClass);
        }

        setTextIfChanged($badge.find('[data-field="direction-label"]'), directionMeta.label);
    }

    function updateTypeBadge($card, item) {
        setTextIfChanged($card.find('[data-field="tx-type"]'), item.transaction_type_label || formatTransactionType(item));
    }

    function formatTransactionType(item) {
        const nature = (item.transaction_nature || '').toLowerCase();
        const labels = {
            deposit: 'Deposit',
            withdraw: 'Withdraw',
            buy: 'Buy',
            sell: 'Sell',
            swap: 'Swap',
            internal: 'Internal',
            tt: 'TT',
            cheque: 'Cheque',
            'maintenance fee': 'Maintenance Fee',
        };

        return labels[nature] || item.transaction_nature || '—';
    }

    function formatAssetTitle(item) {
        return (item.asset_code || '') + (item.asset_name ? ' · ' + item.asset_name : '');
    }

    function formatMeta(item) {
        const parts = [];
        if (item.initiator_name) parts.push(item.initiator_name);
        else if (item.client_name) parts.push(item.client_name);
        if (item.time_label) parts.push(item.time_label);
        return parts.join(' · ');
    }

    function buildSearchBlob(item) {
        return [
            item.flow_from, item.flow_to, item.asset_code, item.asset_name,
            item.status_label, item.client_name, item.portal_source,
            item.transaction_nature, item.transaction_type_label, item.direction_label,
            item.initiator_name,
        ].join(' ').toLowerCase();
    }

    function cardStreamKey($card) {
        return ($card.attr('data-stream-key') || '').toString();
    }

    function itemsRepresentSameTransaction($card, item) {
        const trRequestId = ($card.attr('data-tr-request-id') || '').toString();
        if (trRequestId && item.tr_request_id && trRequestId === String(item.tr_request_id)) {
            return true;
        }

        const fireblocksTxId = ($card.attr('data-fireblocks-tx-id') || '').toString();
        if (fireblocksTxId && item.fireblocks_tx_id && fireblocksTxId === String(item.fireblocks_tx_id)) {
            return true;
        }

        const accountTransactionId = ($card.attr('data-account-transaction-id') || '').toString();
        if (accountTransactionId && item.account_transaction_id && accountTransactionId === String(item.account_transaction_id)) {
            return true;
        }

        const liquidityOrderId = ($card.attr('data-liquidity-order-id') || '').toString();
        if (liquidityOrderId && item.liquidity_order_id && liquidityOrderId === String(item.liquidity_order_id)) {
            return true;
        }

        return false;
    }

    function removeSupersededCards(item) {
        $('#ltt-list .ltt-card').each(function () {
            const $card = $(this);
            if (cardStreamKey($card) === item.stream_key) {
                return;
            }

            if (itemsRepresentSameTransaction($card, item)) {
                state.items.delete(cardStreamKey($card));
                $card.remove();
            }
        });
    }

    function enforcePageCardLimit() {
        if (state.page !== 1) {
            return;
        }

        const maxCards = state.pagination.per_page || 20;
        const keepKeys = new Set(
            Array.from(state.items.values())
                .sort(function (a, b) {
                    return Date.parse(b.updated_at || 0) - Date.parse(a.updated_at || 0);
                })
                .slice(0, maxCards)
                .map(function (entry) {
                    return entry.stream_key;
                })
        );

        $('#ltt-list .ltt-card').each(function () {
            const $card = $(this);
            if (!keepKeys.has(cardStreamKey($card))) {
                $card.remove();
            }
        });
    }

    function cssEscape(value) {
        return String(value).replace(/\\/g, '\\\\').replace(/"/g, '\\"');
    }

    function escapeHtml(value) {
        return $('<div>').text(value == null ? '' : String(value)).html();
    }

    function escapeAttr(value) {
        return escapeHtml(value).replace(/"/g, '&quot;');
    }

    $(document).ready(init);
})(jQuery);
