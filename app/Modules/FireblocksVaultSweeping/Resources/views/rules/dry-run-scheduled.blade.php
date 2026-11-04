@extends('layouts.cp')
@section('title', 'Scheduled Dry Run Results')

@section('content')
<div class="subheader">
    <h1 class="subheader-title">
        <span class="fw-300">Vault Sweeping</span> <i class="fal fa-angle-right"></i>
        Scheduled Dry Run Results
    </h1>
</div>

<div class="alert alert-warning">
    Global dry run is enabled. Below are before/after projected balances for each scheduled rule.
</div>

@forelse ($reports as $entry)
    @php
        $rule = $entry['rule'];
        $before = $entry['before'];
        $after = $entry['after'];
    @endphp
    <div class="panel mb-3">
        <div class="panel-hdr">
            <h2>{{ $rule->name }} <small class="text-muted">(Rule #{{ $rule->id }})</small></h2>
            <div class="panel-toolbar">
                <a href="{{ route('fireblocks.vault-sweeping.rules.dry-run', $rule) }}" class="btn btn-xs btn-outline-info">Full detail</a>
            </div>
        </div>
        <div class="panel-container show">
            <div class="panel-content">
                <p class="mb-2">
                    Destination:
                    <strong>{{ $after['destination_vault_name'] ?? $after['destination_vault_title'] ?? '—' }}</strong>
                    <span class="text-muted">({{ $after['destination_vault_id'] ?? '—' }})</span> ·
                    Would sweep: <strong>{{ $after['summary']['would_sweep'] ?? 0 }}</strong>
                </p>
                <div class="row">
                    <div class="col-md-6">
                        <h6>Destination — before → after</h6>
                        <table class="table table-sm table-bordered">
                            <thead><tr><th>Asset</th><th>Before</th><th>After</th><th>Inflow</th></tr></thead>
                            <tbody>
                                @foreach ($after['destination_after'] ?? [] as $i => $row)
                                    @php $b = $after['destination_before'][$i] ?? ['available' => '0']; @endphp
                                    <tr>
                                        <td>{{ $row['asset_id'] }}</td>
                                        <td>{{ $b['available'] }}</td>
                                        <td><strong>{{ $row['available'] }}</strong></td>
                                        <td>+{{ $row['inflow'] ?? '0' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6>Sources — sample (first 5)</h6>
                        <table class="table table-sm table-bordered">
                            <thead><tr><th>Vault name</th><th>Vault ID</th><th>Asset</th><th>Before</th><th>After</th></tr></thead>
                            <tbody>
                                @foreach (array_slice($after['source_rows'] ?? [], 0, 5) as $row)
                                    <tr>
                                        <td>{{ $row['source_vault_name'] ?? $row['source_vault_title'] ?? '—' }}</td>
                                        <td>{{ $row['source_vault_id'] }}</td>
                                        <td>{{ $row['asset_id'] }}</td>
                                        <td>{{ $row['available_before'] }}</td>
                                        <td>{{ $row['available_after'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@empty
    <div class="alert alert-info">No scheduled rules to evaluate.</div>
@endforelse

<a href="{{ route('fireblocks.vault-sweeping.rules.index') }}" class="btn btn-outline-secondary">Back to Rules</a>
@endsection
