@extends('layouts.cp')
@section('title', 'Beneficiary List - Transactions Requests')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Transactions Requests</span>
        </h1>
        <a href="{{ url('/client/transaction-requests/beneficiaries/new') }}" class="btn btn-primary mr-3"><i
                class="fal fa-plus-circle"></i> Add Beneficiary </a>

    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false"
        data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                Beneficiary List
            </h2>

        </div>

        <div class="panel-container show mb-3">
            <div class="panel-content" style="overflow: scroll;">
                @include('common.error-message')
                <table class="table table-bordered table-hover m-0 shadow" id="data-grid">
                    <thead class="thead-themed">
                        <tr>
                            <th width="30">S.NO</th>
                            <th>Name</th>
                            <th>Account Number</th>
                            <th>IBAN</th>
                            <th>Bank Name</th>
                            <th>Created Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if ($beneficiaries)
                            @foreach ($beneficiaries as $ben)
                                <tr>
                                    <td align="center"><a
                                            href="{{ url('/client/transaction-requests/beneficiaries/view/' . $ben->id) }}"><i
                                                class="fal fa-tv"></i></a></td>
                                    <td>{{ $ben->name }}</td>
                                    <td>{{ $ben->account_number }}</td>
                                    <td>{{ $ben->iban }}</td>
                                    <td>{{ $ben->bank_name }}</td>
                                    <td>{{ \Carbon\Carbon::parse($ben->created_at)->format('d M, Y') }}</td>
                                    <td><span class="badge {{ $statusBadge[$ben->status] }}">{{ $ben->status }}</span></td>
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
                type: "danger",
                icon: "danger",
                dangerMode: true,
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
