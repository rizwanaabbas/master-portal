@extends('layouts.cp')
@section('title', 'Transactions Requests List - Transactions Requests')

@section('content')

    <div class="subheader">
        {{--        <h1 class="subheader-title"> --}}
        {{--            <span class='fw-300'>Transactions Requests</span> --}}
        {{--        </h1> --}}
        {{--        <a href="{{ url("/client/transaction-requests/deposit/new") }}" class="btn btn-primary mr-3"><i class="fal fa-plus-circle"></i> Deposit </a> --}}
        {{--        <a href="{{ url("/client/transaction-requests/transfer/new") }}" class="btn btn-primary mr-3"><i class="fal fa-plus-circle"></i> Transfer </a> --}}
        {{--        <a href="{{ url("/client/transaction-requests/internal/new") }}" class="btn btn-primary mr-3"><i class="fal fa-plus-circle"></i> Internal Transfer </a> --}}
    </div>

    <div class="row mb-4">
        <div class="col-md-3 mt-sm-0 mt-4">
            <div class="card h-100">
                <div class="card-header bg-primary">
                    <h3 class="card-title text-white">Deposit Request</h3>
                </div>
                <div class="card-body">
                    <p>Don't forget to request a transaction whenever you make a deposit to your
                        {{ $ownCompany ? $ownCompany->short_name : '' }} account. Your transaction request will be reviewed
                        by finance and will approve it once they validate the funds in your account.</p>
                </div>
                <div class="card-footer">
                    <a href="{{ url('/client/transaction-requests/deposit/new') }}" class="btn btn-primary mr-3"><i
                            class="fal fa-plus-circle"></i> Deposit </a>
                </div>

            </div>
        </div>
        <div class="col-md-3 mt-sm-0 mt-4">
            <div class="card h-100">
                <div class="card-header bg-primary">
                    <h3 class="card-title text-white">Transfer Request</h3>
                </div>
                <div class="card-body">
                    <p>You can make a transfer anywhere anytime, just add your beneficiary under the beneficiary list, once
                        approved you will be able to transfer money.</p>
                </div>
                <div class="card-footer">
                    <a href="{{ url('/client/transaction-requests/transfer/new') }}" class="btn btn-primary mr-3"><i
                            class="fal fa-plus-circle"></i> Transfer </a>
                </div>
            </div>
        </div>
        <div class="col-md-3 mt-sm-0 mt-4">
            <div class="card h-100">
                <div class="card-header bg-primary">
                    <h3 class="card-title text-white">Transfer to another {{ $ownCompany ? $ownCompany->short_name : '' }}
                        Account</h3>
                </div>
                <div class="card-body">
                    <p>You can transfer funds to any registered accounts in
                        {{ $ownCompany ? $ownCompany->short_name : '' }}.
                        Just provider the account number of a {{ $ownCompany ? $ownCompany->short_name : '' }} account
                        holder and you will be able to transfer funds. </p>
                </div>
                <div class="card-footer">
                    <a href="{{ url('/client/transaction-requests/internal/new') }}" class="btn btn-primary mr-3"><i
                            class="fal fa-plus-circle"></i> Internal Transfer </a>
                    <!-- <a href="{{ url('/client/transaction-requests/internal/transfer-type') }}" class="btn btn-primary mr-3"><i
                                            class="fal fa-plus-circle"></i> Internal Transfer </a> -->
                </div>
            </div>
        </div>
        <div class="col-md-3 mt-sm-0 mt-4">
            <div class="card h-100">
                <div class="card-header bg-primary">
                    <h3 class="card-title text-white">Currency Exchange</h3>
                </div>
                <div class="card-body">
                    <p>You can perform currency exchange between any of your registered accounts in
                        {{ $ownCompany ? $ownCompany->short_name : '' }}.
                    </p>
                </div>
                <div class="card-footer">
                    <a href="{{ url('/client/transaction-requests/exchange/new') }}" class="btn btn-primary mr-3"><i
                            class="fal fa-plus-circle"></i> Exchange </a>
                </div>
            </div>
        </div>
    </div>


    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false"
        data-panel-color="false" data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                Transaction Requests
            </h2>

        </div>

        <div class="panel-container show mb-3">
            <div class="panel-content" style="overflow: scroll;">
                @include('common.error-message')
                <table class="table table-bordered table-hover m-0 shadow" id="data-grid">
                    <thead class="thead-themed">
                        <tr>
                            <th width="30">View/Edit</th>
                            <th>Account Title</th>
                            <th>Remarks</th>
                            <th>Payment Advice MT 103 (SWIFT Copy)</th>
                            <th>Amount</th>
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
                                            href="{{ url('/client/transaction-requests/' . ($trn->status == 'Rejected' ? 'edit' : 'view') . '/' . $trn->id) }}"><i
                                                class="fal fa-{{ $trn->status == 'Rejected' ? 'edit' : 'tv' }}"></i></a>
                                    </td>
                                    <td>{{ $trn->account->title }}</td>
                                    <td width="40%">{{ $trn->remarks }}</td>
                                    <td>
                                        @if ($trn->docs)
                                            @foreach ($trn->docs as $doc)
                                                @if ($doc->is_payment == 1)
                                                    <a href="{{ $doc->url }}" class="mb-2" target="_blank">View</a>
                                                @endif
                                            @endforeach
                                        @endif
                                    </td>
                                    <td>{{ number_format($trn->amount, 2) }}</td>
                                    <td>{{ \Carbon\Carbon::parse($trn->updated_at)->format('d M, Y') }}</td>
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
        action="{{ url('/finance-management/transaction-request/delete') }}">
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
    </script>
@stop
