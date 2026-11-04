<style>
    #fb-snapshot-modal .modal-content {
        border: 0;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 18px 50px rgba(15, 23, 42, .18);
    }
    #fb-snapshot-modal .fb-snap-header {
        background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 55%, #0ea5a4 140%);
        color: #fff;
        border-bottom: 0;
        padding: 1.1rem 1.35rem;
    }
    #fb-snapshot-modal .fb-snap-header .close { color: #fff; opacity: .75; text-shadow: none; }
    #fb-snapshot-modal .fb-snap-kicker {
        font-size: .68rem; letter-spacing: .08em; text-transform: uppercase; opacity: .72; margin-bottom: .15rem;
    }
    #fb-snapshot-modal .modal-title { font-weight: 650; }
    #fb-snapshot-modal .fb-snap-hint { color: rgba(255,255,255,.78); font-size: .8rem; margin: .25rem 0 0; }
    #fb-snapshot-modal .modal-body { background: #f5f7fb; padding: 1rem 1.15rem 1.15rem; }
    #fb-snapshot-modal .fb-snap-table {
        background: #fff; border-radius: 12px; overflow: hidden; margin: 0;
        box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
    }
    #fb-snapshot-modal .fb-snap-table thead th {
        border-top: 0; background: #eef2f7; color: #475569; font-size: .72rem;
        text-transform: uppercase; letter-spacing: .04em; font-weight: 700; white-space: nowrap;
    }
    #fb-snapshot-modal .fb-snap-table tbody td { vertical-align: middle; border-color: #eef2f7; }
    #fb-snapshot-modal .fb-snap-asset {
        display: flex; align-items: center; gap: .7rem;
    }
    #fb-snapshot-modal .fb-snap-logo,
    #fb-snapshot-modal .fb-snap-logo-fallback {
        width: 34px; height: 34px; border-radius: 50%; flex: 0 0 34px;
        background: #e2e8f0; object-fit: contain; padding: 3px;
    }
    #fb-snapshot-modal .fb-snap-logo-fallback {
        display: inline-flex; align-items: center; justify-content: center;
        font-size: .7rem; font-weight: 700; color: #334155;
    }
    #fb-snapshot-modal .fb-snap-name { font-weight: 650; color: #0f172a; line-height: 1.15; }
    #fb-snapshot-modal .fb-snap-symbol { font-size: .75rem; color: #64748b; }
    #fb-snapshot-modal .fb-snap-num { font-variant-numeric: tabular-nums; font-weight: 600; color: #1e293b; }
    #fb-snapshot-modal .fb-snap-usd {
        display: inline-block; padding: .2rem .5rem; border-radius: 999px;
        background: #ecfdf5; color: #047857; font-weight: 700; font-size: .8rem;
        font-variant-numeric: tabular-nums; white-space: nowrap;
    }
    #fb-snapshot-modal .fb-snap-pill {
        display: inline-block; padding: .15rem .5rem; border-radius: 999px;
        background: #e0f2fe; color: #0369a1; font-size: .72rem; font-weight: 650;
    }
    #fb-snapshot-modal .fb-snap-asset-id {
        font-size: .72rem; background: #f1f5f9; color: #475569; padding: .15rem .4rem; border-radius: 6px;
    }
    #fb-snapshot-modal .modal-footer { background: #fff; border-top: 1px solid #eef2f7; }
</style>
<div class="modal fade" id="fb-snapshot-modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header fb-snap-header">
                <div>
                    <div class="fb-snap-kicker" id="fb-snapshot-kicker">Enabled crypto</div>
                    <h5 class="modal-title mb-0" id="fb-snapshot-title">Snapshot</h5>
                    <p class="fb-snap-hint mb-0" id="fb-snapshot-hint">Enabled crypto currencies only.</p>
                </div>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div id="fb-snapshot-error" class="alert alert-danger d-none"></div>
                <div id="fb-snapshot-source-tabs" class="d-none mb-2">
                    <button type="button" class="btn btn-sm btn-primary fb-balance-source" data-source="wallet_pool">Withdrawal Pool</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary fb-balance-source" data-source="omnibus">Omnibus</button>
                </div>
                <div id="fb-snapshot-zero-filter" class="d-none mb-2">
                    <label class="mb-0 small text-muted" for="fb-snapshot-show-zero">
                        <input type="checkbox" id="fb-snapshot-show-zero" class="mr-1">
                        Show zero and negative balances
                    </label>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover fb-snap-table mb-0">
                        <thead id="fb-snapshot-head"></thead>
                        <tbody id="fb-snapshot-body">
                            <tr><td class="text-muted">Loading…</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer justify-content-between align-items-center">
                <div class="text-muted small text-left">
                    <div id="fb-snapshot-last-refresh">Last refresh: —</div>
                    <div id="fb-snapshot-interval"></div>
                </div>
                <div>
                    <button type="button" class="btn btn-outline-primary mr-2" id="fb-snapshot-sync">
                        <i class="fal fa-sync"></i> Sync
                    </button>
                    <button type="button" class="btn btn-light" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>
