@extends('layouts.cp')
@section('title', 'Top-ups List - Card Management')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Card Management</span>
        </h1>
        @if (auth()->user()->hasAnyRole(['operations manager', 'finance manager', 'super admin']))
            @can('add transaction')
                <a href="{{ url('/card-management/card/topup-requests/topup') }}" class="btn btn-primary mr-3"><i
                        class="fal fa-plus-circle"></i> TopUp </a>
            @endcan
        @endif
    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false"
        data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                Top-ups List
            </h2>

        </div>

        <div class="panel-container show mb-3">
            <div class="panel-content" style="overflow: scroll;">
                @include('common.error-message')
                <table class="table table-bordered table-hover m-0 shadow" id="data-grid">
                    <thead class="thead-themed">
                        <tr>
                            <th width="30"></th>
                            <th>Account Title</th>
                            <th>Mode</th>
                            <th>Type</th>
                            <th>Card Number</th>
                            <th>Amount</th>
                            <th>Date</th>
                            <th>Creator</th>
                            <th>Approver</th>
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

@stop
@section('scripts')
    <script>
        $(document).ready(function() {
            $('#data-grid').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ url('/card-management/card/topup-requests/list') }}",
                columns: [{
                        'data': '',
                        render: function(data, type, row) {
                            var id = row['id'];
                            var url = "{{ url('card-management/card/topup-requests/view') }}";

                            return '<a href="' + url + '/' + id +
                                '"><i class="fal fa-tv"></i></a>'
                        },
                        className: "text-center"
                    },
                    {
                        'data': 'title',
                        render: function(data, type, row) {
                            var title = row['title'].replace("&amp;amp;", "&")
                            var title = title.replace("&amp;", "&");
                            return title + '<br><small><i>' + row['account_number'] +
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
                        'data': 'cardNumber'
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
                        'data': 'name'
                    },
                    {
                        'data': 'approver'
                    },
                    {
                        'data': 'provider',
                        render: function(data, type, row) {
                            if (row.provider) {
                                return row.provider
                                    .replace(/_/g, ' ') // replace underscores with spaces
                                    .toLowerCase() // make all lowercase
                                    .replace(/\b\w/g, c => c.toUpperCase()); // capitalize each word
                            }
                            return '';
                        }
                    },
                    {
                        'data': 'status',
                        render: function(data, type, row) {
                            var colors = {
                                'Pending': 'badge-primary',
                                'Approved': 'badge-success',
                                'Rejected': 'badge-danger'
                            }

                            return '<span class="badge ' + colors[data] + '">' +
                                data + '</span>'
                        }
                    },
                ],

            });
        });
    </script>
@stop
