@extends('layouts.cp')
@section('title', 'Beneficiary List - Finance Management')

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
                            <th>Client</th>
                            <th>Beneficiary Name</th>
                            <th>Bank Name</th>
                            <th>Swift Code</th>
                            <th>Created Date</th>
                            <th>Status</th>
                            <th>Is Deleted</th>
                            @can('delete records')
                                <th width="30" align="center"></th>
                            @endcan
                        </tr>
                    </thead>
                    <tbody>
                        @if ($beneficiaries)
                            @foreach ($beneficiaries as $ben)
                                <tr>
                                    <td align="center"><a
                                            href="{{ url('/finance-management/beneficiaries/view/' . $ben->id) }}"><i
                                                class="fal fa-tv"></i></a></td>
                                    <td>{{ $ben->user?->name }}</td>
                                    <td>{{ $ben->name }}</td>
                                    <td>{{ $ben->bank_name }}</td>
                                    <td>{{ $ben->swift_code }}</td>
                                    <td>{{ \Carbon\Carbon::parse($ben->created_at)->format('d M, Y') }}</td>
                                    <td><span class="badge {{ $statusBadge[$ben->status] }}">{{ $ben->status }}</span></td>
                                    <td>
                                        <span class="badge {{ $statusBadge[$ben->is_deleted] }}">
                                            {{ $ben->is_deleted ? 'Yes' : 'No' }}
                                        </span>
                                    </td>
                                    @can('delete records')
                                        <td>
                                            @if (!$ben->is_deleted)
                                                <a href="javascript:deleteBeneficiary({{ $ben->id }})"><i
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

    <form method="POST" name="delete-beneficiary" id="delete-beneficiary"
        action="{{ url('/finance-management/beneficiaries/delete') }}">
        @csrf
        <input type="hidden" name="id" id="id" value="">
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

        function deleteBeneficiary(id) {

            Swal.fire({
                title: "Are you sure?",
                text: "Beneficiary will be deleted permanently",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Yes, delete it!"
            }).then(function(result) {
                if (result.value) {
                    $("#id").val(id);
                    document.getElementById('delete-beneficiary').submit()
                }
            });
        }
    </script>
@stop
