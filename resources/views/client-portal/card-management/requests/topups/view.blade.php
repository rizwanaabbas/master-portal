@extends('layouts.cp')
@section('title', 'View Top-up Card Request - Client Card Management')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Client Card Management</span>
        </h1>
        <a href="{{ url('/client/card-management/card/topups/list') }}" class="btn btn-primary mr-3"><i
                class="fal fa-arrow-alt-left"></i> Go Back </a>

    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false"
        data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                View Top-up Card Request
            </h2>
        </div>

        <div class="panel-container show mb-3">
            <div class="panel-content" style="overflow: scroll;">
                @include('common.error-message')
                <table class="table table-bordered table-hover m-0 shadow" id="data-grid">
                    <thead class="thead-dark">
                        <tr>
                            <th width="200">From Account</th>
                            <td>{{ $transaction->account->title . ' - ' . $transaction->account->account_number }}</td>
                        </tr>
                        <tr>
                            <th>Amount</th>
                            <td>{{ number_format($transaction->amount, 2) }}</td>
                        </tr>
                        @if ($transaction->status == 'Approved' && $transaction->ex_rate)
                            <tr>
                                <th>Amount in USD</th>
                                <td>{{ number_format($transaction->amount / $transaction->ex_rate, 0) }}</td>
                            </tr>
                        @endif
                        <tr>
                            <th>Request Date</th>
                            <td>{{ \Carbon\Carbon::parse($transaction->transaction_date)->format('d M, Y') }}</td>
                        </tr>
                        <tr>
                            <th>Type</th>
                            <td>{{ $transaction->transaction_type }}</td>
                        </tr>
                        <tr>
                            <th>Mode</th>
                            <td>{{ $transaction->transaction_nature }}</td>
                        </tr>

                        <tr>
                            <th>Remarks</th>
                            <td>{{ $transaction->remarks }}</td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td>
                                <span
                                    class="badge {{ $statusBadge[$transaction->status] }}">{{ $transaction->status }}</span>
                            </td>

                        </tr>
                        @if ($transaction->status == 'Rejected')
                            <tr>
                                <th>Reason</th>
                                <td>{{ $transaction->reject_reason }}</td>
                            </tr>
                        @endif
                    </thead>
                </table>
            </div>
        </div>
    </div>

@stop
