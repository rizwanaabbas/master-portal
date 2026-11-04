@extends('layouts.cp')
@section('title', 'Initial Review Transaction Requests List - Finance Management')

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
                Initial Review Transaction Requests List
            </h2>

        </div>

        <div class="panel-container show mb-3">
            <div class="panel-content" style="overflow: scroll;">
                <table class="table table-bordered table-hover m-0 shadow w-100" id="data-grid-client">
                    <thead class="thead-themed">
                        <tr>
                            <th width="30">S.NO</th>
                            <th>Account Title</th>
                            <th>Mode</th>
                            <th>Type</th>
                            <th>Amount</th>
                            {{--   @if (in_array('Compliance', $enabledModules))
                                            <th>Is Suspicious</th>
                                        @endif --}}
                            <th>Creator</th>
                            <th>Request Date</th>
                            <th>Status</th>
                            @can('delete records')
                                <th width="30" align="center"></th>
                            @endcan
                        </tr>
                    </thead>
                    <tbody>
                        @if ($transactions)
                            @foreach ($transactions as $trn)
                                <tr>
                                    <td align="center"><a
                                            href="{{ url('/finance-management/initial-review-transactions/view/' . $trn->id) }}"><i
                                                class="fal fa-tv"></i></a></td>
                                    <td>
                                        @if ($trn->account)
                                            {{ $trn->account->title }}
                                            <br /><small>{{ $trn->account->account_number . ' - (Request ID: ' . $trn->id . ')' }}</small>
                                        @else
                                            --
                                        @endif
                                    </td>
                                    <td>{{ $trn->transaction_nature }}</td>
                                    <td>{{ $trn->transaction_type }}</td>
                                    <td>{{ number_format($trn->amount, 2) }}</td>
                                    {{--   @if (in_array('Compliance', $enabledModules))
                                                    @if (isset($trn->is_suspicious))
                                                        @php  $isSuspicious = [0 => 'No', 1 => 'Yes']; @endphp
                                                        <td> <span
                                                                class="badge {{ $statusBadge[$trn->is_suspicious . '_suspicious'] }}">{{ $isSuspicious[$trn->is_suspicious] }}</span>
                                                        </td>
                                                    @else
                                                        <td>
                                                            N/A
                                                        </td>
                                                    @endif
                                                @endif --}}
                                    </td>
                                    <td>{{ $trn->creator ? $trn->creator->name : '' }}
                                    </td>
                                    <td>{{ \Carbon\Carbon::parse($trn->updated_at)->format('d M, Y') }}
                                    </td>
                                    <td>
                                        <span class="badge {{ $statusBadge[$trn->status] }}">{{ $trn->status }}</span>
                                        @if ($trn->status == 'Rejected')
                                            <i class="fal fa-question-circle rejected_tooltip" data-toggle="tooltip"
                                                data-placement="auto" title=""
                                                data-original-title="{{ $trn->reject_reason }}"></i>
                                        @endif
                                    </td>
                                    @can('delete records')
                                        <td>
                                            @if ($trn->status != 'Approved')
                                                <a href="javascript:deleteClientTransaction({{ $trn->id }})"><i
                                                        class="alert-danger fal fa-times-circle rounded-circle"></i></a>
                                            @endif
                                        </td>
                                    @endcan

                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

        <form method="POST" name="delete-transaction" id="delete-client-transaction"
            action="{{ url('/finance-management/pending-transaction/delete') }}">
            @csrf
            <input type="hidden" name="id" id="id" value="">
        </form>

        <form method="POST" name="delete-transaction" id="delete-back-office-transaction"
            action="{{ url('/finance-management/transactions/delete') }}">
            @csrf
            <input type="hidden" name="trn_id" id="trn_id" value="">
        </form>

    @stop
    @section('scripts')

        <script>
            $(document).ready(function() {
                $('#back_office_tab').click()

                $('#data-grid-client').dataTable({
                    responsive: true
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
                        document.getElementById('delete-back-office-transaction').submit()
                    }
                });
            }

            function deleteClientTransaction(id) {

                Swal.fire({
                    title: "Are you sure?",
                    text: "Transaction Request will be deleted permanently",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonText: "Yes, delete it!"
                }).then(function(result) {
                    if (result.value) {
                        $("#id").val(id);
                        document.getElementById('delete-client-transaction').submit()
                    }
                });
            }
        </script>
    @stop
