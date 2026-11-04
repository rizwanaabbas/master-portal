@extends('layouts.cp')
@section('title', 'Pending Top-ups List - Card Management')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Card Management</span>
        </h1>
    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false"
        data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                Pending Top-ups List
            </h2>

        </div>


        <div id="tabs">
            <ul class="nav nav-tabs nav-tabs-clean" role="tablist">
                <li class="nav-item"><a class="nav-link" data-toggle="tab" id="client_tab" href="#client-requests"
                        role="tab" aria-selected="true">Client Requests</a></li>
                <li class="nav-item"><a class="nav-link" data-toggle="tab" id="back_office_tab" href="#back-office-requests"
                        role="tab" aria-selected="false">Back-Office Requests</a></li>
            </ul>

            <div class="tab-content p-3">
                <div class="tab-pane fade" id="client-requests" role="tabpanel" aria-labelledby="client-requests">
                    <div class="panel-container show mb-3">
                        <div class="panel-content" style="overflow: scroll;">
                            @include('common.error-message')
                            <table class="table table-bordered table-hover m-0 shadow w-100" id="data-grid-client">
                                <thead class="thead-themed ">
                                    <tr>
                                        <th width="30">S.NO</th>
                                        <th>Account Title</th>
                                        <th>Mode</th>
                                        <th>Type</th>
                                        <th>Card Number</th>
                                        <th>Amount</th>
                                        <th>Creator</th>
                                        <th>Request Date</th>
                                        <th>Provider</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if ($transactions)
                                        @foreach ($transactions as $trn)
                                            <tr>

                                                <td align="center"><a
                                                        href="{{ url('/card-management/card/topup-pending-requests/view/' . $trn->id) }}"><i
                                                            class="fal fa-tv"></i></a></td>
                                                <td>{{ $trn->account ? $trn->account->title : '--' }}</td>
                                                <td>{{ $trn->transaction_nature }}</td>
                                                <td>{{ $trn->transaction_type }}</td>
                                                <td>{{ $trn->card ? $trn->card->cardNumber : '--' }}</td>
                                                <td>{{ number_format($trn->amount, 2) }}</td>
                                                <td>{{ $trn->creator ? $trn->creator->name : '' }}
                                                </td>
                                                <td>{{ \Carbon\Carbon::parse($trn->transaction_date)->format('d M, Y') }}
                                                </td>
                                                <td>
                                                    @if (!empty($trn->card))
                                                        {{ ucwords(str_replace('_', ' ', $trn->card->provider)) }}
                                                    @endif
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
                                            </tr>
                                        @endforeach
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="back-office-requests" role="tabpanel" aria-labelledby="back-office-requests">
                    <div class="panel-container mb-3">
                        <div class="panel-content" style="overflow: scroll;">
                            @include('common.error-message')
                            <table class="table table-bordered table-hover m-0 shadow w-100" id="data-grid">
                                <thead class="thead-themed ">
                                    <tr>
                                        <th width="30"></th>
                                        <th>Account Title</th>
                                        <th>Mode</th>
                                        <th>Type</th>
                                        <th>Card Number</th>
                                        <th>Amount</th>
                                        <th>Creator</th>
                                        <th>Request Date</th>
                                        <th>Provider</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@stop

@section('scripts')

    <script>
        $(document).ready(function() {
            window.__statusBadgeMap = {{ json_encode(
                $statusBadge ?? [
                    'Pending' => 'badge-warning',
                    'Approved' => 'badge-success',
                    'Rejected' => 'badge-danger',
                ],
            ) }};

            $('#tabs .nav-link').removeClass('active');
            $('#tabs .tab-pane').removeClass('active show');
            $('#client_tab').tab('show');

            $('#data-grid-client').dataTable({
                responsive: true
            });

            $('#data-grid').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ url('/card-management/card/back-office-pending-topup-requests') }}",
                columns: [{
                        'data': '',
                        render: function(data, type, row) {
                            var trId = row['id'] ?? null;
                            var trUrl =
                                "{{ url('/card-management/card/topup-pending-requests/edit') }}";
                            return '<a href="' + trUrl + '/' + trId +
                                '"><i class="fal fa-edit"></i></a>';

                        },
                        className: "text-center"
                    },
                    {
                        'data': 'account_title',
                        render: function(data, type, row) {
                            var rawTitle = (row['account_title'] ?? '--').toString();
                            var title = rawTitle.replace("&amp;amp;", "&");
                            title = title.replace("&amp;", "&");
                            var accountNumber = (row['account_number'] ?? '--').toString();
                            return title + '<br><small><i>' + accountNumber +
                                '</i></small>';
                        }
                    },
                    {
                        'data': 'transaction_nature'
                    },
                    {
                        'data': 'transaction_type'
                    },
                    {
                        'data': 'cardNumber',
                        render: function(data, type, row) {
                            return (row['cardNumber'] ?? '--');
                        }
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
                        'data': 'creator',
                        render: function(data, type, row) {
                            return (row['creator'] ?? '');
                        }
                    },
                    {
                        'data': 'request_date',
                        render: function(data, type, row) {
                            if (!data) {
                                data = row['transaction_date'];
                            }
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
                        'data': 'provider',
                        render: function(data, type, row) {
                            if (!data) return '';
                            return data.toString().replace(/_/g, ' ').replace(/\b\w/g, function(c) {
                                return c.toUpperCase();
                            });
                        }
                    },
                    {
                        'data': 'status',
                        render: function(data, type, row) {
                            var status = (data ?? '').toString();
                            var map = window.__statusBadgeMap || {};
                            var badgeClass = map[status] || 'badge-secondary';
                            return '<span class="badge ' + badgeClass + '">' + status + '</span>';
                        }
                    },
                ],

            });

            $('a[data-toggle="tab"]').on('shown.bs.tab', function() {
                $.fn.dataTable.tables({
                    visible: true,
                    api: true
                }).columns.adjust();
            });
        });
    </script>

@stop
