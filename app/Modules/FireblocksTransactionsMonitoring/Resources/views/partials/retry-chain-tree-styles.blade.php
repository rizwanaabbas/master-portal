{{-- Resubmit / retry chain tree (detail modal). --}}
<style>
.fb-retry-chain-panel {
    border: 1px solid #e3e6f0;
    border-radius: .35rem;
    background: #fafbfc;
    padding: 1rem 1rem .5rem;
}
.fb-retry-chain-legend {
    font-size: .75rem;
    color: #6c757d;
    margin-bottom: .75rem;
}
.fb-retry-chain-legend span {
    margin-right: 1rem;
}
.fb-retry-chain-legend .dot {
    display: inline-block;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    margin-right: .25rem;
    vertical-align: middle;
}
.fb-retry-chain-tree {
    overflow-x: auto;
    padding-bottom: .5rem;
}
.fb-retry-node {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
}
.fb-retry-node-card {
    position: relative;
    z-index: 2;
    min-width: 220px;
    max-width: 280px;
    border: 1px solid #d1d3e2;
    border-radius: .35rem;
    background: #fff;
    padding: .65rem .75rem;
    box-shadow: 0 1px 2px rgba(0, 0, 0, .04);
    text-align: left;
}
.fb-retry-node-card.is-current {
    border-color: #36b9cc;
    box-shadow: 0 0 0 2px rgba(54, 185, 204, .25);
}
.fb-retry-node-card.is-failed {
    border-left: 3px solid #e74a3b;
}
.fb-retry-node-card.is-root .fb-retry-node-role {
    color: #4e73df;
}
.fb-retry-node-role {
    font-size: .65rem;
    font-weight: 700;
    letter-spacing: .04em;
    text-transform: uppercase;
    color: #858796;
    margin-bottom: .25rem;
}
.fb-retry-node-id {
    font-size: .72rem;
    word-break: break-all;
    margin-bottom: .35rem;
}
.fb-retry-node-meta {
    font-size: .75rem;
    color: #5a5c69;
    margin-bottom: .15rem;
}
.fb-retry-node-meta .badge {
    font-size: .68rem;
}
.fb-retry-node-actions {
    margin-top: .45rem;
}
.fb-retry-children-wrap {
    position: relative;
    display: flex;
    justify-content: center;
    padding-top: 1.5rem;
    width: 100%;
}
.fb-retry-children-wrap::before {
    content: '';
    position: absolute;
    top: 0;
    left: 50%;
    width: 2px;
    height: 1.5rem;
    background: #cbd3e1;
    transform: translateX(-50%);
}
.fb-retry-children {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 1.25rem 2rem;
    position: relative;
    padding-top: .5rem;
}
.fb-retry-children-parallel::before {
    content: '';
    position: absolute;
    top: 0;
    left: 10%;
    right: 10%;
    height: 2px;
    background: #cbd3e1;
}
.fb-retry-branch {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    min-width: 220px;
}
.fb-retry-branch::before {
    content: '';
    position: absolute;
    top: -0.5rem;
    left: 50%;
    width: 2px;
    height: .5rem;
    background: #cbd3e1;
    transform: translateX(-50%);
}
.fb-retry-children-parallel .fb-retry-branch::after {
    content: '';
    position: absolute;
    top: -0.5rem;
    left: 50%;
    width: 2px;
    height: .5rem;
    background: #cbd3e1;
}
.fb-retry-parallel-label {
    font-size: .65rem;
    color: #858796;
    font-style: italic;
    margin: -.25rem 0 .5rem;
    text-align: center;
}
</style>
