@extends('layouts.cp')
@section('title', 'Pending Transaction Request List - Finance Management')

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
                Pending Transaction Request List
            </h2>

        </div>


        <div id="tabs">
            <ul class="nav nav-tabs nav-tabs-clean" role="tablist">
                <li class="nav-item"><a class="nav-link active" data-toggle="tab" id="back_office_tab"
                        href="#back-office-requests" role="tab" aria-selected="false">Back-Offic Requests</a></li>
                @can('finance client requests tab')
                    <li class="nav-item"><a class="nav-link" data-toggle="tab" id="client_tab" href="#client-requests"
                            role="tab" aria-selected="false">Client Requests</a>
                    </li>
                @endcan
            </ul>

            <div class="tab-content p-3">
                <div class="tab-pane fade active show" id="back-office-requests" role="tabpanel"
                    aria-labelledby="back-office-requests">
                    <div class="panel-container show mb-3">
                        <div class="panel-content" style="overflow: scroll;">
                            @include('common.error-message')
                            <table class="table table-bordered table-hover m-0 shadow" id="data-grid">
                                <thead class="thead-themed">
                                    <tr>
                                        <th width="30"></th>
                                        <th width="150">Account Title</th>
                                        <th>Mode</th>
                                        <th>Type</th>
                                        <th>Amount</th>
                                        <th>Method</th>
                                        <th>Date</th>
                                        <th>Creator</th>
                                        <th>Status</th>
                                        @can('delete records')
                                            <th width="30" align="center"></th>
                                        @endcan
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                @can('finance client requests tab')
                    <div class="tab-pane fade" id="client-requests" role="tabpanel" aria-labelledby="client-requests">
                        <div class="panel-container show mb-3">
                            <div class="panel-content" style="overflow: scroll;">
                                <table class="table table-bordered table-hover m-0 shadow w-100" id="data-grid-client">
                                    <thead class="thead-themed">
                                        <tr>
                                            <th width="30">S.NO</th>
                                            <th width="150">Account Title</th>
                                            <th>Mode</th>
                                            <th>Type</th>
                                            <th>Amount</th>
                                            {{--   @if (in_array('Compliance', $enabledModules))
                                            <th>Is Suspicious</th>
                                        @endif --}}
                                            <th>Method</th>
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
                                                            href="{{ url('/finance-management/pending-transactions/view/' . strtolower($trn->transaction_nature) . '/' . $trn->id) }}"><i
                                                                class="fal fa-tv"></i></a></td>
                                                    <td width="150">
                                                        @if ($trn->account)
                                                            {{ $trn->account->title }}
                                                            <br /><small>{{ $trn->account->account_number . ' - (Request ID: ' . $trn->id . ')' }}</small>
                                                        @else
                                                            --
                                                        @endif
                                                    </td>
                                                    <td>
                                                        {{ $trn->transaction_nature . ($trn->is_exchange ? ' - Exchange' : '') }}
                                                    </td>
                                                    <td>{{ $trn->transaction_type }}</td>
                                                    <td>{{ number_format($trn->amount, 2) }}</td>
                                                    <td>{{ $trn->method->name ?? '' }} @if (!empty($trn->method->module->name))
                                                            - {{ $trn->method->module->name }}
                                                        @endif
                                                    </td>
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
                                                        <span
                                                            class="badge {{ $statusBadge[$trn->status] }}">{{ $trn->status }}</span>
                                                        @if ($trn->status == 'Rejected')
                                                            <i class="fal fa-question-circle rejected_tooltip"
                                                                data-toggle="tooltip" data-placement="auto" title=""
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
                    </div>
                @endcan
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
            window.canDeleteRecords = @json(auth()->user()->can('delete records'));

            $(document).ready(function() {
                $('#back_office_tab').click()
                $('#data-grid-client').dataTable({
                    responsive: true
                });

                let columns = [{
                        'data': null,
                        render: function(data, type, row) {
                            var nature = row['transaction_nature'].toLowerCase();
                            var id = row['id'];
                            var url = "{{ url('finance-management/transactions/edit') }}";

                            return '<a href="' + url + '/' + nature + '/' + id +
                                '"><i class="fal fa-edit"></i></a>'
                        },
                        className: "text-center"
                    },
                    {
                        'data': 'title',
                        render: function(data, type, row) {
                            var title = row['title'] ? row['title'].replace("&amp;amp;", "&") : '';
                            title = title.replace("&amp;", "&");
                            return title + '<br><small><i>' + row['account_number'] +
                                ' - (Request ID: ' + row['tr_request_id'] + ')'
                            '</i></small>';
                        }
                    },
                    {
                        'data': 'transaction_nature',
                        render: function(data, type, row) {
                            return row['transaction_nature'] + (row['is_exchange'] ? ' - Exchange' : '');
                        }
                    },
                    {
                        'data': 'transaction_type'
                    },
                    {
                        'data': 'amount',
                        render: function(data, type, row) {
                            return parseFloat(data).toLocaleString(undefined, {
                                minimumFractionDigits: 2
                            })
                        }
                    },
                    {
                        'data': 'method',
                        render: function(data, type, row) {
                            return row.method;
                        }
                    },
                    {
                        'data': 'transaction_date',
                        render: function(data, type, row) {
                            var date = new Date(Date.parse(data.replace(/-/g, '/')));
                            var dd = date.toLocaleDateString('en-US', {
                                day: "numeric"
                            });
                            var mm = date.toLocaleDateString('en-US', {
                                month: "short"
                            });
                            var yy = date.toLocaleDateString('en-US', {
                                year: "numeric"
                            });

                            return dd + ' ' + mm + ', ' + yy;
                        }
                    },
                    {
                        'data': 'creator'
                    },
                    {
                        'data': 'status',
                        render: function(data, type, row) {
                            var colors = {
                                'Pending': 'badge-primary',
                                'Approved': 'badge-success',
                                'Rejected': 'badge-danger'
                            }

                            return '<span class="badge ' + colors[data] + '">' + data + '</span>'
                        }
                    }
                ];
                if (window.canDeleteRecords) {
                    columns.push({
                        'data': null,
                        render: function(data, type, row) {
                            var id = row['id'];
                            return '<a href="javascript:deleteTransaction(' + id +
                                ')"><i class="alert-danger fal fa-times-circle rounded-circle"></i></a>'
                        },
                        className: "text-center"
                    });
                }

                $('#data-grid').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: "{{ url('/finance-management/back-office-pending-transactions') }}",
                    columns: columns,
                    responsive: true
                });

                setTimeout(() => {
                    let clientsTabClassList = document.getElementById('client_tab').classList

                    if (clientsTabClassList.contains('active')) {
                        clientsTabClassList.remove('active')
                    }

                    let clientsRequestsClassList = document.getElementById('client-requests').classList

                    if (clientsRequestsClassList.contains('active')) {
                        clientsRequestsClassList.remove('active')
                    }

                    if (clientsRequestsClassList.contains('show')) {
                        clientsRequestsClassList.remove('show')
                    }
                }, 300);
            });

            function deleteTransaction(id) {

                Swal.fire({
                    title: "Are you sure?",
                    text: "Transaction, along with linked transactions, will be deleted permanently.",
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
                    text: "Transaction Request, along with linked transactions, will be deleted permanently.",
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
