@extends('layouts.cp')
@section('title', 'Fireblocks Transaction Monitoring — Ops Manual')

@section('content')
@include('fireblocks-monitoring::partials.status-badge-styles')
<style>
    .fb-ops-manual { max-width: 960px; }
    .fb-ops-manual h2 { margin-top: 2rem; padding-top: 1rem; border-top: 1px solid #eee; }
    .fb-ops-manual h3 { margin-top: 1.25rem; }
    .fb-ops-manual table { font-size: 0.9rem; }
    .fb-ops-manual .mermaid { margin: 1rem 0 1.5rem; overflow-x: auto; }
    .fb-ops-manual pre { background: #f8f9fa; padding: 1rem; border-radius: 4px; }
</style>

<div class="subheader">
    <h1 class="subheader-title">
        <span class="fw-300">Fireblocks</span> <i class="fal fa-angle-right"></i>
        Transaction Monitoring <i class="fal fa-angle-right"></i>
        <span class="fw-300">Ops Manual</span>
    </h1>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="panel">
            <div class="panel-hdr">
                <h2>Operations Manual</h2>
                <div class="panel-toolbar">
                    <a href="{{ route('fireblocks.transaction-monitoring.index') }}" class="btn btn-sm btn-outline-primary">
                        <i class="fal fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </div>
            <div class="panel-container show">
                <div class="panel-content fb-ops-manual">

                    <p class="text-muted mb-0">Last updated: 2026-09-29</p>

                    <h2 class="mt-3">Quick links</h2>
                    <table class="table table-sm table-bordered">
                        <tr><th style="width:30%">Dashboard</th><td>Crypto Management → Fireblocks → Transaction Monitoring</td></tr>
                        <tr><th>Module config</th><td>Settings → Module Configurations → <code>FireblocksTransactionsMonitoring</code></td></tr>
                        <tr><th>Ledger detail</th><td>Crypto transaction edit → <strong>Fireblocks</strong> panel (same layout as dashboard detail)</td></tr>
                        <tr><th>Scheduler</th><td><code>fireblocks:monitor-transactions</code> (every 5 min when enabled)</td></tr>
                        <tr><th>Markdown source</th><td><code>docs/Fireblocks/FireblocksTransactionsMonitoring/TRANSACTION_MONITORING_OPS.md</code></td></tr>
                    </table>

                    <h2>Summary cards</h2>
                    <p>Top-of-page KPIs refresh when filters change (<code>GET …/summary</code>). Click a card to apply a matching filter.</p>
                    <table class="table table-sm table-bordered">
                        <thead><tr><th>Card</th><th>Meaning</th></tr></thead>
                        <tbody>
                            <tr><td><strong>Stuck</strong></td><td><code>monitoring_state = stuck</code> (within current transaction scope)</td></tr>
                            <tr><td><strong>Action Pending</strong></td><td><code>monitoring_state = action_pending</code></td></tr>
                            <tr><td><strong>Open ATC Alerts</strong></td><td>Open rows in monitoring alerts</td></tr>
                            <tr><td><strong>Pending (FB)</strong></td><td>Fireblocks primary still in-flight (e.g. CONFIRMING)</td></tr>
                            <tr><td><strong>Completed</strong></td><td><code>COMPLETED</code> / internal Approved</td></tr>
                            <tr><td><strong>Orphan Transactions</strong></td><td>Custody-only txs not on client ledger (global count; click → Orphan scope)</td></tr>
                        </tbody>
                    </table>

                    <h2>Transaction scope</h2>
                    <p>Default: <strong>Ledger-linked (has account)</strong> — hides orphan custody notes and rows without a resolved client account (same as the <strong>Account</strong> column).</p>
                    <table class="table table-sm table-bordered">
                        <thead><tr><th>Scope</th><th>Grid shows</th></tr></thead>
                        <tbody>
                            <tr><td><strong>Ledger-linked (has account)</strong></td><td>Client-linked custody activity only</td></tr>
                            <tr><td><strong>All transactions</strong></td><td>Every row in <code>fireblocks_transactions</code> (other filters still apply)</td></tr>
                            <tr><td><strong>Orphan custody only</strong></td><td>Deposits/notes not registered to ledger (e.g. unknown deposit address)</td></tr>
                            <tr><td><strong>Small incomings</strong></td><td>Incoming credits to <strong>registered</strong> client deposit addresses at or below configured max amount (bot dust / address poisoning). Excludes custody-only orphans and sweeps. Tune <code>poisoning_deposit_max_amount</code> in module config.</td></tr>
                        </tbody>
                    </table>
                    <p class="text-muted">Parameter: <code>ledger_scope</code> = <code>ledger</code> | <code>all</code> | <code>orphan</code> | <code>poisoning</code>. Legacy <code>show_orphans=1</code> maps to orphan.</p>

                    <h2>Analytics</h2>
                    <p>Four Chart.js charts in one row (respect filters including transaction scope):</p>
                    <ul>
                        <li><strong>Flow &amp; custody</strong> — Incoming / Outgoing / Internal / Orphan (donut)</li>
                        <li><strong>Ops pipeline</strong> — Stuck / Action pending / In progress (FB) / Completed</li>
                        <li><strong>Stuck queue age</strong> — 0–15m, 15–60m, 1–4h, 4h+</li>
                        <li><strong>Actions (7d)</strong> — Boost, Drop, Resubmit, Sync from audit logs</li>
                    </ul>
                    <p class="text-muted">Removed: “Monitoring State Mix” and “Fireblocks Status Mix” (replaced by summary cards + pipeline chart).</p>

                    <h2>Webhooks &amp; Sync</h2>
                    <ul>
                        <li><strong>Webhooks</strong> update status, sub-status, internal status, direction, compliance, <code>last_webhook_at</code>; monitoring sync runs when the module is enabled (stuck detection still requires <code>monitoring_enabled</code>).</li>
                        <li><strong>Sync</strong> (detail): polls Fireblocks, applies compliance from payload, then refreshes Chainalysis if still empty (beneficiary screening cache → Chainalysis Entities API when Chainalysis module is on).</li>
                    </ul>

                    <h2>Architecture overview</h2>
                    <div class="mermaid">
flowchart TB
    subgraph inputs [Inputs]
        WH[Fireblocks webhooks]
        POLL[Scheduled polling]
        MAN[Manual Sync / actions]
    end
    subgraph portal [CIB Portal]
        SYNC[TransactionMonitoringSync]
        CHAIN[ChainalysisComplianceSyncService]
        DET[StuckTransactionDetector]
        ACT[TransactionActionService]
        DASH[Monitoring Dashboard]
        LED[Ledger Fireblocks panel]
    end
    subgraph storage [Data]
        FBTX[(fireblocks_transactions)]
        LOGS[(monitoring_logs)]
    end
    subgraph fb [Fireblocks API]
        API[Transactions API]
    end
    WH --> SYNC
    POLL --> SYNC
    SYNC --> CHAIN
    SYNC --> FBTX
    DET --> FBTX
    DET --> ACT
    MAN --> ACT
    ACT --> API
    ACT --> LOGS
    DASH --> FBTX
    LED --> FBTX
                    </div>

                    <h2>Status mapping</h2>
                    <div class="mermaid">
flowchart LR
    FB[Fireblocks primary + sub-status]
    RES[resolve]
    INT[Internal status]
    FB --> RES --> INT
                    </div>
                    <table class="table table-sm table-striped table-bordered">
                        <thead><tr><th>Internal</th><th>Fireblocks sources</th></tr></thead>
                        <tbody>
                            <tr><td><strong>Pending</strong></td><td>Most in-flight; <code>COMPLETED</code> without confirming sub-status</td></tr>
                            <tr><td><strong>Approved</strong></td><td><code>COMPLETED</code> + <code>CONFIRMED</code> or <code>3RD_PARTY_COMPLETED</code></td></tr>
                            <tr><td><strong>Cancelled</strong></td><td><code>CANCELLED</code>, <code>CANCELLING</code></td></tr>
                            <tr><td><strong>Rejected</strong></td><td><code>FAILED</code>, <code>REJECTED</code>, <code>BLOCKED</code>, <code>TIMEOUT</code></td></tr>
                            <tr><td><strong>Needs Review</strong></td><td><code>COMPLETED</code> + 3rd-party failure sub-statuses</td></tr>
                        </tbody>
                    </table>
                    <div class="alert alert-info"><i class="fal fa-info-circle"></i> Use <strong>Understand statuses</strong> on the dashboard for the full catalog (including badge colors per code).</div>

                    <h2>Status badge colors</h2>
                    <p>Grid, detail modal, and <strong>Understand statuses</strong> use the same CSS classes so each status type is visually distinct.</p>
                    <table class="table table-sm table-bordered">
                        <thead><tr><th>Family</th><th>Examples</th><th>Color rule</th></tr></thead>
                        <tbody>
                            <tr>
                                <td><strong>Internal</strong></td>
                                <td>
                                    <span class="badge fb-int-pending">Pending</span>
                                    <span class="badge fb-int-approved">Approved</span>
                                    <span class="badge fb-int-rejected">Rejected</span>
                                    <span class="badge fb-int-needs-review">Needs Review</span>
                                </td>
                                <td>Pending orange; Rejected red; Approved green; each internal label has its own class.</td>
                            </tr>
                            <tr>
                                <td><strong>Fireblocks primary</strong></td>
                                <td><span class="badge fb-pri-confirming">CONFIRMING</span> <span class="badge fb-pri-completed">COMPLETED</span> <span class="badge fb-pri-failed">FAILED</span></td>
                                <td>One shade per primary API code (see primary tab in Understand statuses).</td>
                            </tr>
                            <tr>
                                <td><strong>Sub-status</strong></td>
                                <td>
                                    <span class="badge fb-sub-blocked">BLOCKED_BY_POLICY</span>
                                    <span class="badge fb-sub-rejected">REJECTED_BY_BLOCKCHAIN</span>
                                </td>
                                <td>Policy / blocked sub-statuses use the same orange as Pending; rejections red; other categories have dedicated classes.</td>
                            </tr>
                            <tr>
                                <td><strong>Monitoring state</strong></td>
                                <td><span class="badge fb-mon-stuck">stuck</span> <span class="badge fb-mon-action-pending">action_pending</span> <span class="badge fb-mon-normal">normal</span></td>
                                <td>Ops pipeline states (stuck, boosted, etc.) each have a unique badge.</td>
                            </tr>
                            <tr>
                                <td><strong>Chainalysis</strong></td>
                                <td><span class="badge fb-ca-pending">PENDING</span> <span class="badge fb-ca-passed">COMPLETED</span> <span class="badge fb-ca-rejected">REJECTED</span></td>
                                <td>AML/KYT screening outcome on the Chainalysis column.</td>
                            </tr>
                        </tbody>
                    </table>

                    <h2>Manual actions</h2>
                    <div class="mermaid">
flowchart TD
    START[Open transaction detail]
    NR{Non-retriable and not in action list?}
    ELIG{In Boost/Drop/Resubmit textarea?}
    HASH{Has tx_hash?}
    HIDE[Hidden]
    DIS[Visible disabled]
    OK[Enabled]

    START --> NR
    NR -->|Yes| HIDE
    NR -->|No| ELIG
    ELIG -->|No| HIDE
    ELIG -->|Boost/Drop| HASH
    HASH -->|No| DIS
    HASH -->|Yes| OK
                    </div>

                    <h3>Boost (RBF)</h3>
                    <ul>
                        <li>Requires on-chain <code>tx_hash</code></li>
                        <li>Configured via <code>boost_eligible_primary_statuses</code> (comma-separated primary or sub-status)</li>
                        <li><code>INSUFFICIENT_FUNDS</code>: usually disabled — fund vault and use <strong>Resubmit</strong></li>
                    </ul>

                    <h3>Drop (EVM)</h3>
                    <ul>
                        <li>Same textarea pattern as Boost; EVM assets only; requires <code>tx_hash</code></li>
                    </ul>

                    <h3>Resubmit</h3>
                    <ul>
                        <li>New Fireblocks withdrawal; links via <code>parent_fireblocks_tx_id</code> / <code>root_fireblocks_tx_id</code></li>
                        <li>Explicit action textarea overrides non-retriable for that action</li>
                    </ul>

                    <h3>Sync</h3>
                    <p>Always available — see <strong>Webhooks &amp; Sync</strong> above.</p>

                    <h2>Resubmit chain</h2>
                    <div class="mermaid">
flowchart TB
    ROOT[Root TX]
    P1[Parent]
    CUR[Current]
    ROOT --> P1 --> CUR
    P1 --> R1[Parallel retry A]
    P1 --> R2[Parallel retry B]
                    </div>
                    <p>Shown in dashboard detail modal and ledger Fireblocks panel (shared template).</p>

                    <h2>Dashboard grid columns</h2>
                    <p class="text-muted">Queue, Reattempts, Last Reattempt, Stuck For, and Ledger are <strong>hidden by default</strong>. Enable each under Settings → Module Configurations → Fireblocks Transaction Monitoring.</p>
                    <table class="table table-sm table-bordered">
                        <thead><tr><th>Column</th><th>Meaning</th></tr></thead>
                        <tbody>
                            <tr>
                                <td><strong>Account</strong></td>
                                <td>Client ledger title + number from <code>account_transaction_requests.account_id</code> (via <code>external_tx_id</code> or <code>crypto_transaction_id</code>).</td>
                            </tr>
                            <tr>
                                <td><strong>FB Status / Sub-status / Internal</strong></td>
                                <td>Raw Fireblocks primary and sub-status codes, plus <strong>Internal</strong> — the portal’s rolled-up custody label (Pending, Approved, Rejected, etc.) from those two fields. Not the same as optional <strong>Ledger</strong> (TR posting).</td>
                            </tr>
                            <tr>
                                <td><strong>Direction</strong></td>
                                <td>incoming / outgoing / internal (from webhook/poll source &amp; destination types).</td>
                            </tr>
                            <tr>
                                <td><strong>Chainalysis</strong></td>
                                <td>
                                    AML/KYT from Fireblocks <code>complianceResults</code>, beneficiary screening, or manual Sync refresh.
                                    Grid and detail link open <strong>Chainalysis KYT</strong> entity pages:
                                    <code>https://kyt.chainalysis.com/entities/{wallet_address}</code>
                                    (e.g. screened destination on withdrawals). The public Entities API URL is kept for reference only; ops should use KYT.
                                    Optional <strong>KYT Transaction</strong> link when Fireblocks compliance includes an external screening id.
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Ledger</strong> (optional)</td>
                                <td>TR / <code>account_transactions</code> posting status only — enable via module config if needed.</td>
                            </tr>
                        </tbody>
                    </table>

                    <h2>Dashboard filters</h2>
                    <div class="mermaid">
flowchart LR
    CFG[Module config]
    SESS[sessionStorage]
    GRID[Grid + charts]
    CFG --> INIT[Initial filters]
    INIT --> GRID
    SESS --> GRID
    GRID --> SESS
                    </div>
                    <table class="table table-sm table-bordered">
                        <thead><tr><th>Filter</th><th>Default</th></tr></thead>
                        <tbody>
                            <tr><td>Monitoring State</td><td><code>default_filter_monitoring_state</code> (default: <strong>stuck</strong>)</td></tr>
                            <tr><td>Fireblocks Status / Sub-status / Internal</td><td><strong>All</strong> (not duplicated in module config)</td></tr>
                            <tr><td>Direction</td><td>Follows <code>monitored_directions</code></td></tr>
                            <tr><td><strong>Transaction scope</strong></td><td><strong>Ledger-linked (has account)</strong></td></tr>
                            <tr><td>Stuck only</td><td>Off</td></tr>
                        </tbody>
                    </table>
                    <p><strong>Reset filters</strong> restores module defaults and clears saved session filter state. Other choices persist in <code>sessionStorage</code> until reset or session end.</p>

                    <h2>Troubleshooting</h2>
                    <table class="table table-sm table-bordered">
                        <thead><tr><th>Scenario</th><th>Action</th></tr></thead>
                        <tbody>
                            <tr><td>Stuck <code>CONFIRMING</code></td><td>Sync → Boost if hash present</td></tr>
                            <tr><td><code>FAIL_ON_LOW_FEE</code></td><td>Boost or Resubmit</td></tr>
                            <tr><td><code>INSUFFICIENT_FUNDS</code></td><td>Fund vault → Resubmit</td></tr>
                            <tr><td>Dry run on</td><td>Actions logged only</td></tr>
                            <tr><td>Boost greyed out</td><td>Usually missing <code>tx_hash</code></td></tr>
                            <tr><td>Grid empty but txs in Fireblocks</td><td>Check <strong>Transaction scope</strong> (default hides orphans / no account)</td></tr>
                            <tr><td>Chainalysis empty</td><td>Sync; enable Chainalysis module; confirm destination/source wallet on tx</td></tr>
                            <tr><td>Chainalysis link opens wrong site</td><td>Re-sync; links should be KYT <code>/entities/…</code>, not <code>public.chainalysis.com</code> API URLs</td></tr>
                            <tr><td>Status not updating on webhook</td><td>Confirm module enabled; use Sync; check logs</td></tr>
                        </tbody>
                    </table>

                    <h2>Changelog</h2>
                    <ul class="text-muted">
                        <li><strong>2026-09-29</strong> — Status badge color legend; Chainalysis KYT entity links; Understand statuses badge column</li>
                        <li><strong>2026-09-24</strong> — Transaction scope, summary cards, Chart.js analytics, Sync+Chainalysis, reset filters</li>
                        <li><strong>2026-08-27</strong> — Unified detail template, filter defaults, ops manual</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/mermaid@10/dist/mermaid.min.js"></script>
<script>
    mermaid.initialize({ startOnLoad: true, theme: 'neutral', securityLevel: 'loose' });
</script>
@endsection
