@extends('layouts.cp')
@section('title', 'Internal Transfer Transaction - Transaction Requests')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Transaction Requests</span>
        </h1>
        <a href="{{ url('/client/transaction-requests/list') }}" class="btn btn-primary mr-3"><i
                class="fal fa-arrow-alt-left"></i> Go Back </a>

    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false"
        data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                Internal Transfer Transaction
            </h2>
        </div>

        <div class="panel-container show mb-3">
            <div class="panel-content" style="overflow: scroll;">
                @include('common.error-message')
                @php
                    $sender = $transaction->account;
                    $receiver = $transaction->receiver ?? false;

                @endphp
                {{-- <form id="approval-form" method="post"
                    action="{{ url('/finance-management/pending-transaction/approve/internal') }}">
                    @csrf
                    <input type="hidden" id="id" name="id" value="{{ $transaction->id }}">
                    <input type="hidden" name="credit_currency_id" id="credit_currency_id"
                        value="{{ $sender->account_currency }}">
                    <input type="hidden" name="debit_currency_id" id="debit_currency_id"
                        value="{{ $receiver->account_currency }}"> --}}

                <table class="table table-bordered table-hover m-0 shadow" id="data-grid">
                    <thead class="thead-dark">
                        <tr>
                            <th width="200">From Account</th>
                            <td>
                                {{ $sender->title . ' - ' . $sender->account_number }}
                            </td>
                        </tr>
                        @php $balance = \App\Helpers\Common::getAccountBalanceById($sender->id); @endphp
                        <tr>
                            <th>From Account Balance</th>
                            <td>{{ number_format($balance, 2) }}</td>
                        </tr>

                        @if ($receiver)
                            <tr>
                                <th>To Account</th>
                                <td>{{ $receiver->title . ' - ' . $receiver->account_number }}</td>
                            </tr>
                        @endif

                        <tr>
                            <th>Amount</th>
                            <td>{{ number_format($transaction->amount, 2) }}</td>
                        </tr>

                        <tr>
                            <th>Request Date</th>
                            <td>{{ \Carbon\Carbon::parse($transaction->updated_at)->format('d/m/Y') }}</td>
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
                        @if ($transaction->docs->count() > 0)
                            <tr>
                                <th>Supporting Documents</th>
                                <td>
                                    @foreach ($transaction->docs as $doc)
                                        <a href="{{ $doc->url }}" class="btn btn-sm btn-outline-secondary"
                                            target="_blank"><i class="fal fa-file-alt"></i> View</a>
                                    @endforeach

                                </td>
                            </tr>
                        @endif
                        <tr>
                            <th>Status</th>
                            <td><span
                                    class="badge {{ $statusBadge[$transaction->status] }}">{{ $transaction->status }}</span>
                            </td>
                         </tr>
                            @if (in_array('NeroData', $enabledModules))
                            <tr>
                                <th>Transaction Monitoring Status</th>

                                <td><span> <span class="badge badge-warning">{{ $transaction->compliance_status }} </span>
                                    @if (!empty($rulesText))
                                        {!! $rulesText !!}
                                    @endif
                                    @php $notes = collect($amlNoteTexts ?? []); @endphp
                                    @if ($notes->isNotEmpty())
                                        <div class="mt-2">
                                            <strong>AML Notes</strong>
                                            <ul class="mb-0">
                                                @foreach ($notes as $note)
                                                    <li>{{ $note }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif
                                        <a class="btn btn-primary" target="_blank" href="{{$portalTransactionsUrl}}">VIEW / MODERATE</a>
                                </td>
                            </tr>
                        @endif
                       
                        @if ($transaction->status == 'Rejected')
                            <tr>
                                <th>Reject Reason</th>
                                <td>{{ $transaction->reject_reason }}</td>
                            </tr>
                        @endif
                    </thead>
                </table>
                <hr>
                {{-- </form> --}}

            </div>
        </div>
    </div>

@stop
