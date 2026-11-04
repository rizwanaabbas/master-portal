@extends('layouts.cp')
@section('title', 'Fee Charge Log - Fee Engine')

@section('content')
    <div class="subheader">
        <h1 class="subheader-title"><span class='fw-300'>Fee Engine</span> &mdash; Charge Log</h1>
        <a href="{{ url('/settings/fee-engine/groups') }}" class="btn btn-primary ml-auto"><i class="fal fa-arrow-alt-left"></i> Groups</a>
    </div>

    <div class="panel">
        <div class="panel-hdr"><h2>Fee Transaction Charges</h2></div>
        <div class="panel-container show">
            <div class="panel-content">
                <form method="get" class="form-inline mb-3">
                    <input type="text" name="reference" value="{{ $reference }}" class="form-control mr-2" placeholder="Transaction reference">
                    <button class="btn btn-primary">Search</button>
                </form>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover table-striped m-0">
                        <thead class="thead-themed">
                            <tr>
                                <th>Ref</th><th>Type</th><th>Client</th><th>Currency</th><th>Dir</th>
                                <th>Fee Label</th><th>Rule</th><th class="text-right">Txn Amount</th>
                                <th class="text-right">Fee</th><th>Display</th><th>Status</th><th>When</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($charges as $charge)
                                <tr>
                                    <td><small>{{ $charge->transaction_reference }}</small></td>
                                    <td>{{ $charge->transaction_type }}</td>
                                    <td>{{ optional($charge->client)->name ?? $charge->client_id }}</td>
                                    <td>{{ optional($charge->currency)->short ?? $charge->currency_id }}</td>
                                    <td>{{ ucfirst($charge->direction) }}</td>
                                    <td>{{ $charge->fee_label }}</td>
                                    <td>{{ ucfirst($charge->fee_type) }} @ {{ $charge->fee_rate }}{{ $charge->fee_type === 'percentage' ? '%' : '' }}</td>
                                    <td class="text-right">{{ $charge->transaction_amount }}</td>
                                    <td class="text-right">{{ $charge->fee_amount }}</td>
                                    <td>{{ $charge->crypto_fee_display ?? '—' }}</td>
                                    <td>
                                        <span class="badge {{ $charge->status === 'charged' ? 'badge-success' : ($charge->status === 'reversed' ? 'badge-danger' : 'badge-secondary') }}">
                                            {{ ucfirst($charge->status) }}
                                        </span>
                                    </td>
                                    <td><small>{{ optional($charge->created_at)->format('Y-m-d H:i') }}</small></td>
                                </tr>
                            @empty
                                <tr><td colspan="12" class="text-center text-muted">No fee charges recorded.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">{{ $charges->links() }}</div>
            </div>
        </div>
    </div>
@stop
