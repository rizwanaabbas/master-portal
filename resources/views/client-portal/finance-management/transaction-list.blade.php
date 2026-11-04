@extends('layouts.cp')
@section('title', 'Client Transaction List - Finance Management')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Finance Management</span>
        </h1>
    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false"
        data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                Client Transaction List
            </h2>

        </div>

        <div class="panel-container show mb-3">
            <div class="panel-content" style="overflow: scroll;">
                @include('common.error-message')

                <table class="table table-bordered table-hover m-0 shadow"
                    @if ($transactions->count() > 0) id="data-grid" @endif>
                    <thead class="thead-themed">
                        <tr>
                            <th width="30"></th>
                            <th>Account Title</th>
                            <th>Mode</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Date</th>
                            <th>Created at</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if ($transactions->count() > 0)
                            @foreach ($transactions as $transaction)
                                @if (!$transaction->is_hidden_for_clients)
                                    <tr>
                                        <td align="center" scope="row"><a
                                                href="{{ url('/client/dashboard/transactions/view/' . $transaction->id) }}"><i
                                                    class="fal fa-eye"></i></a></td>
                                        <td width="30%">
                                            {{ $transaction->account->title }}<br><small><i>{{ $transaction->account->account_number }}</i></small>
                                        </td>
                                        <td>{{ $transaction->transaction_nature }}</td>
                                        <td>{{ $transaction->transaction_type }}</td>
                                        <td>{{ number_format($transaction->amount) }}</td>
                                        <td>{{ \Carbon\Carbon::parse($transaction->transaction_date)->format('d M, Y') }}
                                        </td>
                                        <td>{{ \Carbon\Carbon::parse($transaction->created_at)->format('d M, Y') }}</td>
                                    </tr>
                                @endif
                            @endforeach
                        @else
                            <tr>
                                <td colspan="8" align="center">No records found.</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <form method="POST" name="delete-transaction" id="delete-transaction"
        action="{{ url('/finance-management/transactions/delete') }}">
        @csrf
        <input type="hidden" name="trn_id" id="trn_id" value="">
    </form>

@stop
@section('scripts')
    <script>
        $(document).ready(function() {
            $('#data-grid').dataTable({
                responsive: true
            });

            $('.js-thead-colors a').on('click', function() {
                var theadColor = $(this).attr("data-bg");
                console.log(theadColor);
                $('#dt-basic-example thead').removeClassPrefix('bg-').addClass(theadColor);
            });

            $('.js-tbody-colors a').on('click', function() {
                var theadColor = $(this).attr("data-bg");
                console.log(theadColor);
                $('#dt-basic-example').removeClassPrefix('bg-').addClass(theadColor);
            });

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
