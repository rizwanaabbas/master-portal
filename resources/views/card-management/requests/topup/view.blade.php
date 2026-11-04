@extends('layouts.cp')
@section('title', 'Approved Top-up - Card Management')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Card Management</span>
        </h1>
        <div class="d-flex">
            <a href="{{ url('card-management/card/topup-requests/list') }}" class="btn btn-primary ml-2 mx-md-2"><i
                    class="fal fa-arrow-alt-left"></i> Go Back </a>
            <a href="{{ url('finance-management/transactions/print/' . $transaction->id) }}"
                class="btn btn-primary ml-2 mx-md-2" target="_blank"><i class="fal fa-print"></i> Print </a>
        </div>
    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false"
        data-panel-color="false" data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                Approved Top-up
            </h2>
        </div>

        <div class="panel-container show mb-3">
            <div class="panel-content" style="overflow: scroll;">
                @include('common.error-message')
                <form id="approval-form" method="post" action="{{ url('/card-management/card/topup-update') }}">
                    @csrf
                    <input type="hidden" id="id" name="id" value="{{ $transaction->id }}">
                    <table class="table table-bordered table-hover m-0 shadow" id="data-grid">
                        <thead class="thead-dark">
                            <tr>
                                <th width="200">From Account</th>
                                <td>{{ $transaction->account->title . ' - ' . $transaction->account->account_number }}</td>
                            </tr>
                            @include('finance-management.transactions.partials.tr-approval-balance-rows', [
                                'trRequest' => $transaction,
                            ])
                            <tr>
                                <th>Amount</th>
                                <td>{{ number_format($transaction->amount, 2) }}</td>
                            </tr>
                            @if ($transaction->status == 'Approved' && $transaction->ex_rate && $transaction->currency->short !== 'USD')
                                <tr>
                                    <th>Amount in USD</th>
                                    <td>{{ number_format($transaction->amount / $transaction->ex_rate, 2) }}</td>
                                </tr>
                            @endif
                            <tr>
                                <th>Request Date</th>
                                <td>{{ \Carbon\Carbon::parse($transaction->created_at)->format('d M, Y') }}</td>
                            </tr>
                            <tr>
                                <th>Top-up Date</th>
                                <td>
                                    <input type="text" required
                                        value="{{ \Carbon\Carbon::parse($transaction->transaction_date)->format('d/m/Y') }}"
                                        autocomplete="off" class="form-control w-25" name="transaction_date"
                                        id="transaction_date" placeholder="Select date">
                                </td>
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
                                <td>
                                    {{ $transaction->remarks }}
                                </td>
                            </tr>
                            <tr>
                                <th>Status</th>
                                <td>
                                    <span
                                        class="badge {{ $statusBadge[$transaction->status] }}">{{ $transaction->status }}</span>
                                </td>
                    
                            </tr>
                            @if(!empty($transaction->requestedTrn))
                            <tr>
                                <th>Provider</th>
                                <td>{{ ucwords(str_replace('_', ' ', $transaction->requestedTrn->card->provider)) }}</td>
                            </tr>
                           @endif
                        </thead>
                    </table>
                    <hr>
                    <div class="row">
                        <div class="col-md-12">
                            <button type="Submit" class="btn btn-success">Save</button>
                        </div>
                    </div>
                </form>
                <hr>
            </div>
        </div>
    </div>

@stop
@section('scripts')
    <script>
        var controls = {
            leftArrow: '<i class="fal fa-angle-left" style="font-size: 1.25rem"></i>',
            rightArrow: '<i class="fal fa-angle-right" style="font-size: 1.25rem"></i>'
        }

        $(document).ready(function() {

            // minimum setup
            $('#transaction_date').datepicker({
                format: 'dd/mm/yyyy',
                todayHighlight: true,
                orientation: "bottom left",
                templates: controls
            });
        })
    </script>
@stop
