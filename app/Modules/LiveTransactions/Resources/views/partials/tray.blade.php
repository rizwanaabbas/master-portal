<div id="live-transactions-tray-root" aria-live="polite">
    <button type="button" class="ltt-tab" title="Live transactions">
        <i class="fal fa-bolt"></i>
        LIVE
        <span class="ltt-tab-badge" id="ltt-tab-badge">0</span>
    </button>

    <aside class="ltt-panel" id="live-transactions-tray">
        <div class="ltt-header">
            <div class="ltt-header-top">
                <h2 class="ltt-title">
                    Recent activity
                    <span class="ltt-count-badge" id="ltt-count-badge">0</span>
                </h2>
                <div class="ltt-header-actions">
                    <button type="button" class="ltt-icon-btn" data-ltt-close title="Close tray">
                        <i class="fal fa-times"></i>
                    </button>
                </div>
            </div>
            <div class="ltt-search-wrap">
                <i class="fal fa-search"></i>
                <input type="text" id="ltt-search" placeholder="Search transactions…" autocomplete="off">
            </div>
            <div class="ltt-filters">
                <button type="button" class="ltt-filter-pill active" data-filter="all">All</button>
                <button type="button" class="ltt-filter-pill" data-filter="pending">In progress</button>
                <button type="button" class="ltt-filter-pill" data-filter="success">Completed</button>
                <button type="button" class="ltt-filter-pill" data-filter="warning">Review</button>
                <button type="button" class="ltt-filter-pill" data-filter="danger">Failed</button>
            </div>
        </div>
        <div class="ltt-body" id="ltt-list"></div>
        <div class="ltt-footer">
            <button type="button" class="ltt-page-btn" id="ltt-prev-page" disabled>
                <i class="fal fa-chevron-left"></i>
            </button>
            <span class="ltt-page-info" id="ltt-page-info">Page 1 of 1</span>
            <button type="button" class="ltt-page-btn" id="ltt-next-page" disabled>
                <i class="fal fa-chevron-right"></i>
            </button>
        </div>
    </aside>
</div>

<script>
    window.__liveTransactionsConfig = @json($liveTransactionsConfig ?? []);
</script>
