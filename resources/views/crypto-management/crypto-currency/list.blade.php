@extends('layouts.cp')
@section('title', 'Crypto Currency List - Crypto Management')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Crypto Management</span>
        </h1>
        <a href="{{ url('/crypto-management/crypto-currencies/new') }}" class="btn btn-primary mr-3"><i
                class="fal fa-plus-circle"></i> Add New </a>
    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false"
        data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                Crypto Currency List
            </h2>
        </div>

        <div class="panel-container show mb-3">
            <div class="panel-content" style="overflow: scroll;">
                @include('common.error-message')

                <div class="row mb-3">
                    <div class="col-md-2 mb-2">
                        <label class="form-label">Active</label>
                        <select id="filter-active" class="form-control form-control-sm">
                            <option value="all">All</option>
                            <option value="1" selected>Yes</option>
                            <option value="0">No</option>
                        </select>
                    </div>
                    <div class="col-md-2 mb-2 d-flex align-items-end">
                        <button type="button" id="btn-reset-currency-filters" class="btn btn-sm btn-outline-secondary">Reset</button>
                    </div>
                </div>

                <table class="table table-bordered table-hover m-0 shadow" id="data-grid" style="width:100%">
                    <thead class="thead-themed">
                        <tr>
                            <th width="30"></th>
                            <th>ID</th>
                            <th>Short</th>
                            <th>Name</th>
                            <th>USD Rate</th>
                            <th>Logo</th>
                            <th>Active</th>
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

    <form method="POST" name="delete-crypto-currency" id="delete-crypto-currency"
        action="{{ url('/crypto-management/crypto-currencies/delete') }}">
        @csrf
        <input type="hidden" name="row_id" id="row_id" value="">
    </form>

@stop
@section('scripts')
    <script>
        const canDeleteDocuments = @json(auth()->user()->can('delete records'));

        $(document).ready(function() {
            function filterParams() {
                return {
                    filter_active: $('#filter-active').val(),
                };
            }

            let columns = [{
                    data: 'edit_action',
                    orderable: false,
                    searchable: false,
                    className: 'text-center'
                },
                {
                    data: 'id',
                    name: 'id'
                },
                {
                    data: 'short',
                    name: 'short'
                },
                {
                    data: 'name',
                    name: 'name'
                },
                {
                    data: 'usd_rate_label',
                    name: 'usd_rate',
                    className: 'text-right'
                },
                {
                    data: 'logo_html',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'active_badge',
                    name: 'is_active'
                }
            ];

            if (canDeleteDocuments) {
                columns.push({
                    data: 'delete_action',
                    orderable: false,
                    searchable: false,
                    className: 'text-center'
                });
            }

            const table = $('#data-grid').DataTable({
                processing: true,
                serverSide: true,
                pageLength: 25,
                order: [[1, 'asc']],
                ajax: {
                    url: "{{ url('/crypto-management/crypto-currencies') }}",
                    data: function(d) {
                        Object.assign(d, filterParams());
                    }
                },
                columns: columns
            });

            $('#filter-active').on('change', function() {
                table.ajax.reload();
            });

            $('#btn-reset-currency-filters').on('click', function() {
                $('#filter-active').val('1');
                table.ajax.reload();
            });
        });

        function deleteCurrency(id) {

            Swal.fire({
                title: "Are you sure?",
                text: "Crypto Currency will be deleted permanently",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Yes, delete it!"
            }).then(function(result) {
                if (result.value) {
                    $("#row_id").val(id);
                    document.getElementById('delete-crypto-currency').submit()
                }
            });
        }
    </script>
@stop
