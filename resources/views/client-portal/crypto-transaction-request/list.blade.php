@extends('layouts.cp')
@section('title', 'Crypto Transactions Requests List - Crypto Transactions Requests')

@section('content')

    <div class="row mb-4">
        <div class="col-md-3 mt-sm-0 mt-4">
            <div class="card">
                <div class="card-header bg-primary">
                    <h3 class="card-title text-white">Buy Crypto</h3>
                </div>
                <div class="card-body">


                    <a href="javascript:void(0)" onclick="openActionPage('Buy')" class="btn btn-primary mr-3"><i
                            class="fal fa-plus-circle"></i> Buy </a>

                </div>
            </div>
        </div>
        <div class="col-md-3 mt-sm-0 mt-4">
            <div class="card">
                <div class="card-header bg-primary">
                    <h3 class="card-title text-white">Sell Crypto</h3>
                </div>
                <div class="card-body">
                    <a href="javascript:void(0)" onclick="openActionPage('Sell')" class="btn btn-primary mr-3"><i
                            class="fal fa-plus-circle"></i> Sell </a>
                </div>
            </div>
        </div>
        <div class="col-md-3 mt-sm-0 mt-4">
            <div class="card">
                <div class="card-header bg-primary">
                    <h3 class="card-title text-white">Withdraw Crypto</h3>
                </div>
                <div class="card-body">
                    <a href="{{ url('/client/crypto-transaction-requests/withdraw/new') }}" class="btn btn-primary mr-3"><i
                            class="fal fa-plus-circle"></i> Withdraw </a>
                </div>
            </div>
        </div>
        @if (in_array('Bitpace', $enabledModules))
            <div class="col-md-3">
                <div class="card">
                    <div class="card-header bg-primary">
                        <h3 class="card-title text-white">Deposit Crypto</h3>
                    </div>
                    <div class="card-body">
                        <a href="{{ url('/client/crypto-transaction-requests/deposit/new') }}"
                            class="btn btn-primary mr-3"><i class="fal fa-plus-circle"></i> Deposit </a>
                    </div>
                </div>
            </div>
        @endif
    </div>


    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false"
        data-panel-color="false" data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                Crypto Transaction Requests
            </h2>

        </div>

        <div class="panel-container show mb-3">
            <div class="panel-content" style="overflow: scroll;">
                @include('common.error-message')
                <table class="table table-bordered table-hover m-0 shadow" id="data-grid">
                    <thead class="thead-themed">
                        <tr>
                            <th width="30">View/Edit</th>
                            <th>From Account</th>
                            <th>To Account / Wallet Address</th>
                            <th>Type</th>
                            <th>Fiat Amount</th>
                            <th>Crypto Amount</th>
                            <th>Request Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if ($transactions)
                            @foreach ($transactions as $trn)
                                <tr>
                                    {{-- <td>{{ $loop->iteration }}</td> --}}
                                    <td align="center" scope="row"><a
                                            href="{{ url('/client/crypto-transaction-requests/' . ($trn->status == 'Rejected' ? 'edit' : 'view') . '/' . $trn->id) }}"><i
                                                class="fal fa-{{ $trn->status == 'Rejected' ? 'edit' : 'tv' }}"></i></a>
                                    </td>
                                    <td>
                                        @if ($trn->transaction_nature != 'Deposit')
                                            {{ $trn->account->title }}
                                        @else
                                            --
                                        @endif
                                    </td>
                                    <td>
                                        @if ($trn->receiver)
                                            {{ $trn->receiver->title }}
                                        @elseif ($trn->transaction_nature == 'Deposit')
                                            {{ $trn->account->title }}
                                        @else
                                            {{ $trn->to_wallet_address }}
                                        @endif
                                    </td>
                                    <td>{{ $trn->transaction_nature }}</td>
                                    <td>
                                        {{ $trn->amount ? \App\Helpers\Common::formatNumber($trn->amount, false) : '--' }}
                                    </td>
                                    <td>{{ \App\Helpers\Common::formatNumber($trn->crypto_amount, true) }}
                                    </td>
                                    <td>{{ \Carbon\Carbon::parse($trn->transaction_date)->format('d M, Y') }}</td>
                                    <td><span class="badge {{ $statusBadge[$trn->status] }}">
                                            @if ($trn->status != 'Rejected')
                                                {{ $trn->status }}
                                        </span>
                                    @else
                                        {{ $trn->status }}
                                        </span>
                                        <i class="fal fa-question-circle rejected_tooltip" data-toggle="tooltip"
                                            data-placement="auto" title=""
                                            data-original-title="{{ $trn->reject_reason }}"></i>
                            @endif
                            </td>

                            </tr>
                        @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <form method="POST" name="delete-transaction" id="delete-transaction"
        action="{{ url('/client/crypto-transaction-requests/delete') }}">
        @csrf
        <input type="hidden" name="trn_id" id="trn_id" value="">
    </form>

@stop
@section('scripts')
    <script>
        $(document).ready(function() {

        });

        function deleteTransaction(id) {

            Swal.fire({
                title: "Are you sure?",
                text: "Transaction will be deleted permanently",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Yes, delete it!"
            }).then(function(result) {
                if (result.value) {
                    $("#trn_id").val(id);
                    document.getElementById('delete-transaction').submit()
                }
            });
        }
        var have_ged_account = "{{ $have_ged_account }}";

        function openActionPage(page) {

            console.log(have_ged_account);
            if (have_ged_account == 'yes') {
                Swal.fire({
                    title: "Please select method to " + page,
                    // text: "You won't be able to revert this!",
                    icon: "info",
                    showCancelButton: true,
                    showCloseButton: true,
                    confirmButtonColor: "#3085d6",
                    cancelButtonColor: "#ffc800",
                    confirmButtonText: "GED",
                    cancelButtonText: "Crypto"

                }).then((result) => {
                    //console.log(result.dsmiss);
                    /* Read more about isConfirmed, isDenied below */
                    if (result.value) {
                        redirect(page, 'GED');
                        return false;
                    } else if (result.dismiss == 'cancel') {
                        redirect(page, 'crypto');
                        return false;
                    }
                });
            } else {
                redirect(page, 'crypto');
            }
        }

        function redirect(page, method) {
            console.log(page);
            if (page == 'Buy' && method == 'crypto')
                var url = "{{ url('/client/crypto-transaction-requests/buy/new') }}";
            else
                var url = "{{ url('/client/crypto-transaction-requests/sell/new') }}";

            if (have_ged_account == 'yes' && method == 'GED') {
                if (page == 'Buy')
                    var url = "{{ url('/client/crypto-transaction-requests/ged/buy/new') }}";
                else
                    var url = "{{ url('/client/crypto-transaction-requests/ged/sell/new') }}";
            }
            window.location = url;
        }
    </script>
@stop
