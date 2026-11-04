@extends('layouts.cp')
@section('title', 'Currency List - Finance Management')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Finance Management</span>
        </h1>
        <a href="{{ url('/finance-management/currencies/new') }}" class="btn btn-primary mr-3"><i
                class="fal fa-plus-circle"></i> Add New </a>
    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false"
        data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                Currency List
            </h2>

        </div>

        <div class="panel-container show mb-3">
            <div class="panel-content" style="overflow: scroll;">
                @include('common.error-message')
                <table class="table table-bordered table-hover m-0 shadow" id="data-grid">
                    <thead class="thead-themed">
                        <tr>
                            <th width="30"></th>
                            <th>Short</th>
                            <th>Name</th>
                            <th>Logo</th>
                            <th>IBAN Support</th>
                            <th>SR Auto Approve</th>
                            <th>Individual Fee</th>
                            <th>Company Fee</th>
                            <th>Active</th>
                            @can('delete records')
                                <th width="30" align="center"></th>
                            @endcan
                        </tr>
                    </thead>
                    <tbody>
                        @if ($currencies)
                            @foreach ($currencies as $currency)
                                <tr>
                                    <td align="center" scope="row">
                                        @can('edit bank')
                                            <a href="{{ url('/finance-management/currencies/edit/' . $currency->id) }}"><i
                                                    class="fal fa-edit"></i></a>
                                        @endcan
                                    </td>
                                    <td>{{ $currency->short }}</td>
                                    <td>{{ $currency->name }}</td>

                                    <td>
                                        @if ($currency->logo)
                                            <img src="{{ $currency->logo }}" alt="{{ $currency->name }}" height="18px"
                                                style="vertical-align: middle;" />
                                        @endif
                                    </td>
                                    <td>
                                        @if ($currency->virtual_iban_provider)
                                            {{ $currency->virtual_iban_provider }}
                                        @else
                                            ----
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge {{ $statusBadge[$currency->request_auto_approval] }}">
                                            {{ $currency->request_auto_approval ? 'Yes' : 'No' }}
                                        </span>
                                    </td>
                                    <td>
                                        {{ $currency->maintenance ? $currency->maintenance->individual_fee : '--' }}
                                    </td>
                                    <td>
                                        {{ $currency->maintenance ? $currency->maintenance->company_fee : '--' }}
                                    </td>
                                    <td>
                                        <span class="badge {{ $statusBadge[$currency->is_active] }}">
                                            {{ $currency->is_active ? 'Yes' : 'No' }}
                                        </span>
                                    </td>
                                    @can('delete records')
                                        <td>
                                            <a href="javascript:deleteCurrency({{ $currency->id }})"><i
                                                    class="alert-danger fal fa-times-circle rounded-circle"></i></a>
                                        </td>
                                    @endcan
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="5">No records found</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <form method="POST" name="delete-currency" id="delete-currency"
        action="{{ url('/finance-management/currencies/delete') }}">
        @csrf
        <input type="hidden" name="row_id" id="row_id" value="">
    </form>

@stop
@section('scripts')
    <script>
        $(document).ready(function() {
            $('#data-grid').dataTable({
                responsive: true
            });
        });

        function deleteCurrency(id) {

            Swal.fire({
                title: "Are you sure?",
                text: "Currency will be deleted permanently",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Yes, delete it!"
            }).then(function(result) {
                if (result.value) {
                    $("#row_id").val(id);
                    document.getElementById('delete-currency').submit()
                }
            });
        }
    </script>
@stop
