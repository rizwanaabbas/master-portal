@extends('layouts.cp')
@section('title', 'Withdraw Crypto Transaction - Crypto Transaction Requests')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Crypto Transaction Requests</span>
        </h1>
        <a href="{{ url('/client/crypto-transaction-requests/list') }}" class="btn btn-primary mr-3"><i
                class="fal fa-arrow-alt-left"></i> Go Back </a>

    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false"
        data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                Withdraw Crypto Transaction
            </h2>
        </div>

        <div class="panel-container show mb-3">
            <div class="panel-content" style="overflow: scroll;">
                @include('common.error-message')
                @php
                    $sender = $transaction->account;
                    $receiver = $transaction->receiver;
                    
                @endphp
                <table class="table table-bordered table-hover m-0 shadow" id="data-grid">
                    <thead class="thead-dark">
                        <tr>
                            <th width="200">Crypto Account</th>
                            <td>
                                {{ $sender->title . ' - ' . $sender->account_number }}
                            </td>
                        </tr>
                        @php $balance = \App\Helpers\Common::getAccountBalanceById($sender->id); @endphp
                        <tr>
                            <th>Crypto Account Balance</th>
                            <td><img src="{{ $sender->currency->logo }}" alt="{{ $sender->currency->name }}" height="17px"
                                    style="vertical-align: middle;" />&nbsp;{{ \App\Helpers\Common::formatNumber($balance, true) }}
                            </td>
                        </tr>
                        <tr>
                            <th>To Wallet Address</th>
                            <td>{{ $transaction->to_wallet_address }}</td>
                        </tr>
                        @if ($transaction->destination_tag != '')
                        <tr>
                            <th>Destination Tag</th>
                            <td>{{ $transaction->destination_tag }}</td>
                        </tr>
                        @endif
                        <tr>
                            <th>Network</th>
                            <td>{{ $transaction->crypto_network }}</td>
                        </tr>
                        <tr>
                            <th>Amount</th>
                            <td><img src="{{ $sender->currency->logo }}" alt="{{ $sender->currency->name }}" height="17px"
                                    style="vertical-align: middle;" />&nbsp;
                                {{ \App\Helpers\Common::formatNumber($transaction->crypto_amount, true) }}
                            </td>
                        </tr>

                        <tr>
                            <th>Request Date</th>
                            <td>{{ \Carbon\Carbon::parse($transaction->transaction_date)->format('d/m/Y') }}</td>
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
                            <th>Supporting Documents</th>
                            <td>
                                @if ($transaction->docs)
                                    @foreach ($transaction->docs as $doc)
                                        @if ($doc->is_payment == 0)
                                            <a href="{{ $doc->url }}" class="btn btn-sm btn-outline-secondary"
                                                target="_blank"><i class="fal fa-file-alt"></i> View</a>
                                        @endif
                                    @endforeach
                                @else
                                    --
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td><span
                                    class="badge {{ $statusBadge[$transaction->status] }}">{{ $transaction->status }}</span>
                            </td>
                        </tr>
                       
                        @if (!empty($blockchain_tx_id))
                        <tr>
                            <th>Explore Transaction Details</th>
                            <td>
                            <a href="https://www.blockchain.com/explorer/search?search={{$blockchain_tx_id}}" target="_blank">{{$blockchain_tx_id}}</a>
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
