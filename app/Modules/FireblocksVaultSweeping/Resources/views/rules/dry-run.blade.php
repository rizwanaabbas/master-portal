@extends('layouts.cp')
@section('title', 'Dry Run — ' . $rule->name)

@section('styles')
@include('fireblocks-sweeping::partials.stylesheets', ['sheets' => ['sweep-datagrid.css']])
@endsection

@section('content')
@php
    $showAfter = !empty($executed);
    $before = $showAfter ? ($beforeReport ?? []) : ($report ?? []);
    $after = $showAfter ? ($afterReport ?? []) : null;
    $destBefore = $before['destination_before'] ?? [];
    $destAfter = $after['destination_after'] ?? [];
@endphp

<div class="subheader">
    <h1 class="subheader-title">
        <span class="fw-300">Vault Sweeping</span> <i class="fal fa-angle-right"></i>
        Dry Run — {{ $rule->name }}
    </h1>
</div>

@include('fireblocks-sweeping::partials.module-nav', ['sweepNavActive' => 'rules'])

<div class="alert alert-warning">
    <strong>Dry run mode</strong> — balances shown are live from Fireblocks.
    @if ($showAfter)
        Projected <em>after</em> balances assume sweeps complete successfully with no network fees deducted from source (fee paid separately on chain).
    @else
        Review current balances below, then run the simulation to see projected balances after sweep.
    @endif
</div>

<div class="panel mb-3">
    <div class="panel-hdr">
        <h2>Target account (destination / omnibus) — before</h2>
    </div>
    <div class="panel-container show">
        <div class="panel-content">
            <p class="mb-2">
                <strong>{{ $before['destination_vault_name'] ?? $before['destination_vault_title'] ?? '—' }}</strong>
                @if (!empty($before['destination_vault_id']))
                    <span class="text-muted">(Vault ID: {{ $before['destination_vault_id'] }})</span>
                @endif
            </p>
            @if (empty($destBefore))
                <p class="text-muted mb-0">No matching source/asset pairs to evaluate destination balances.</p>
            @else
                <div class="sweep-datagrid-wrap">
                <table class="table table-sm table-bordered sweep-datagrid">
                    <thead>
                        <tr>
                            <th>Asset</th>
                            <th>Available (before)</th>
                            <th>Total (before)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($destBefore as $row)
                            <tr>
                                <td>{{ $row['asset_id'] }}</td>
                                <td>{{ $row['available'] }}</td>
                                <td>{{ $row['total'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                </div>
            @endif
        </div>
    </div>
</div>

<div class="panel mb-3">
    <div class="panel-hdr">
        <h2>Source accounts — before</h2>
    </div>
    <div class="panel-container show">
        <div class="panel-content">
            @if (empty($before['source_rows']))
                <p class="text-muted mb-0">No source vault/asset pairs with a non-zero balance match this rule.</p>
            @else
                <div class="sweep-datagrid-wrap">
                <table class="table table-sm table-bordered sweep-datagrid">
                    <thead>
                        <tr>
                            <th>Vault name</th>
                            <th>Vault ID</th>
                            <th>Asset</th>
                            <th>Available (before)</th>
                            <th>Total (before)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($before['source_rows'] as $row)
                            <tr>
                                <td>{{ $row['source_vault_name'] ?? $row['source_vault_title'] ?? '—' }}</td>
                                <td>{{ $row['source_vault_id'] }}</td>
                                <td>{{ $row['asset_id'] }}</td>
                                <td>{{ $row['available_before'] }}</td>
                                <td>{{ $row['total_before'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                </div>
            @endif
        </div>
    </div>
</div>

@if (!$showAfter)
<div class="panel mb-3">
    <div class="panel-container show">
        <div class="panel-content">
            <form method="POST" action="{{ route('fireblocks.vault-sweeping.rules.dry-run.execute', $rule) }}">
                @csrf
                <input type="hidden" name="persist_jobs" value="1">
                <button type="submit" class="btn btn-primary">
                    <i class="fal fa-flask"></i> Run Dry Run Simulation
                </button>
            </form>
            <small class="form-text text-muted mt-2">Creates dry-run sweep job records and shows projected after balances.</small>
        </div>
    </div>
</div>
@endif

@if ($showAfter && $after)
<div class="panel mb-3 border-success">
    <div class="panel-hdr bg-success-50">
        <h2 class="text-success">Simulation complete — projected after balances</h2>
    </div>
    <div class="panel-container show">
        <div class="panel-content">
            <p class="mb-3">
                Would sweep: <strong>{{ $after['summary']['would_sweep'] ?? 0 }}</strong> ·
                Skipped: <strong>{{ $after['summary']['skipped'] ?? 0 }}</strong> ·
                Pairs evaluated: <strong>{{ $after['summary']['pairs_evaluated'] ?? 0 }}</strong>
            </p>

            <h5 class="mb-2">Target account (destination / omnibus) — after</h5>
            <p class="mb-2">
                <strong>{{ $after['destination_vault_name'] ?? $after['destination_vault_title'] ?? '—' }}</strong>
                <span class="text-muted">(Vault ID: {{ $after['destination_vault_id'] ?? '—' }})</span>
            </p>
            <div class="sweep-datagrid-wrap">
            <table class="table table-sm table-bordered mb-4 sweep-datagrid">
                <thead>
                    <tr>
                        <th>Asset</th>
                        <th>Available (before)</th>
                        <th>Inflow from sweeps</th>
                        <th>Available (after)</th>
                        <th>Total (after)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($after['destination_after'] as $i => $row)
                        @php $b = $after['destination_before'][$i] ?? ['available' => '0', 'total' => '0']; @endphp
                        <tr>
                            <td>{{ $row['asset_id'] }}</td>
                            <td>{{ $b['available'] ?? '0' }}</td>
                            <td class="text-success">+{{ $row['inflow'] ?? '0' }}</td>
                            <td><strong>{{ $row['available'] }}</strong></td>
                            <td><strong>{{ $row['total'] }}</strong></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>

            <h5 class="mb-2">Source accounts — after</h5>
            <div class="sweep-datagrid-wrap">
            <table class="table table-sm table-bordered sweep-datagrid">
                    <thead>
                        <tr>
                            <th>Vault name</th>
                            <th>Vault ID</th>
                            <th>Asset</th>
                            <th>Available (before)</th>
                            <th>Sweep amount</th>
                            <th>Available (after)</th>
                            <th>Result</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($after['source_rows'] as $row)
                            <tr>
                                <td>{{ $row['source_vault_name'] ?? $row['source_vault_title'] ?? '—' }}</td>
                                <td class="text-muted">{{ $row['source_vault_id'] }}</td>
                            <td>{{ $row['asset_id'] }}</td>
                            <td>{{ $row['available_before'] }}</td>
                            <td>
                                @if (!empty($row['would_sweep']))
                                    <span class="text-danger">−{{ $row['sweep_amount'] }}</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td><strong>{{ $row['available_after'] }}</strong></td>
                            <td>
                                @if (!empty($row['would_sweep']))
                                    <span class="badge badge-success">Would sweep</span>
                                @else
                                    <span class="badge badge-secondary">{{ $row['skip_reason'] ?? 'Skipped' }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>

        </div>
    </div>
</div>
@endif
@endsection

@section('scripts')
@include('fireblocks-sweeping::partials.datagrid-init')
<script>
$(function () {
    $('.sweep-datagrid').each(function () {
        SweepDatagrid.initClientGrid(this, { order: [] });
    });
});
</script>
@endsection
