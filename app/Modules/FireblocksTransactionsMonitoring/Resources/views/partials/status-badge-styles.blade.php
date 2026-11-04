<style>
    /* Fireblocks Transaction Monitoring — distinct badge colors per status */
    .badge.fb-int-pending { background-color: #fd7e14; color: #fff; }
    .badge.fb-int-approved { background-color: #28a745; color: #fff; }
    .badge.fb-int-cancelled { background-color: #6c757d; color: #fff; }
    .badge.fb-int-rejected { background-color: #dc3545; color: #fff; }
    .badge.fb-int-needs-review { background-color: #e83e8c; color: #fff; }

    .badge.fb-pri-submitted { background-color: #17a2b8; color: #fff; }
    .badge.fb-pri-pending-aml { background-color: #fd7e14; color: #fff; }
    .badge.fb-pri-pending-enrichment { background-color: #f0ad4e; color: #212529; }
    .badge.fb-pri-pending-auth { background-color: #ffc107; color: #212529; }
    .badge.fb-pri-queued { background-color: #6f42c1; color: #fff; }
    .badge.fb-pri-pending-signature { background-color: #ff851b; color: #fff; }
    .badge.fb-pri-signed { background-color: #007bff; color: #fff; }
    .badge.fb-pri-pending-3p-manual { background-color: #ffb347; color: #212529; }
    .badge.fb-pri-pending-email { background-color: #ffcc80; color: #212529; }
    .badge.fb-pri-pending-3p { background-color: #20c997; color: #fff; }
    .badge.fb-pri-broadcasting { background-color: #0056b3; color: #fff; }
    .badge.fb-pri-confirming { background-color: #004085; color: #fff; }
    .badge.fb-pri-partial { background-color: #218838; color: #fff; }
    .badge.fb-pri-completed { background-color: #28a745; color: #fff; }
    .badge.fb-pri-cancelling { background-color: #adb5bd; color: #212529; }
    .badge.fb-pri-cancelled { background-color: #868e96; color: #fff; }
    .badge.fb-pri-blocked { background-color: #fd7e14; color: #fff; }
    .badge.fb-pri-rejected { background-color: #dc3545; color: #fff; }
    .badge.fb-pri-failed { background-color: #c82333; color: #fff; }
    .badge.fb-pri-timeout { background-color: #721c24; color: #fff; }

    .badge.fb-sub-pending { background-color: #fd7e14; color: #fff; }
    .badge.fb-sub-confirming { background-color: #007bff; color: #fff; }
    .badge.fb-sub-completed { background-color: #28a745; color: #fff; }
    .badge.fb-sub-blocked { background-color: #fd7e14; color: #fff; }
    .badge.fb-sub-blocked-aml { background-color: #ff851b; color: #fff; }
    .badge.fb-sub-cancelled { background-color: #6c757d; color: #fff; }
    .badge.fb-sub-rejected { background-color: #dc3545; color: #fff; }
    .badge.fb-sub-failed-user { background-color: #e83e8c; color: #fff; }
    .badge.fb-sub-failed-mpc { background-color: #6610f2; color: #fff; }
    .badge.fb-sub-failed-fb { background-color: #bd2130; color: #fff; }
    .badge.fb-sub-failed-3p-acct { background-color: #d63384; color: #fff; }
    .badge.fb-sub-failed-3p-sys { background-color: #c2255c; color: #fff; }
    .badge.fb-sub-failed-chain { background-color: #842029; color: #fff; }
    .badge.fb-sub-other { background-color: #17a2b8; color: #fff; }

    .badge.fb-mon-stuck { background-color: #dc3545; color: #fff; }
    .badge.fb-mon-action-pending { background-color: #ffc107; color: #212529; }
    .badge.fb-mon-boosted { background-color: #007bff; color: #fff; }
    .badge.fb-mon-dropped { background-color: #343a40; color: #fff; }
    .badge.fb-mon-resubmitted { background-color: #17a2b8; color: #fff; }
    .badge.fb-mon-needs-review { background-color: #e83e8c; color: #fff; }
    .badge.fb-mon-normal { background-color: #28a745; color: #fff; }

    .badge.fb-ca-passed { background-color: #28a745; color: #fff; }
    .badge.fb-ca-rejected { background-color: #dc3545; color: #fff; }
    .badge.fb-ca-pending { background-color: #fd7e14; color: #fff; }
    .badge.fb-ca-failed { background-color: #6c757d; color: #fff; }
    .badge.fb-ca-unknown { background-color: #adb5bd; color: #212529; }
</style>
