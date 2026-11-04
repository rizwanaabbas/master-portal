@extends('layouts.cp')
@section('title', 'Bank SSI List - Finance Management')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Finance Management</span>
        </h1>
        <a href="{{ url('/finance-management/bank-ssi/new') }}" class="btn btn-primary mr-3"><i class="fal fa-plus-circle"></i>
            Add New </a>
    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false"
        data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                Bank SSI List
            </h2>
        </div>

        <div class="panel-container show mb-3">
            <div class="panel-content" style="overflow: scroll;">
                @include('common.error-message')

                <!-- Check if there are any banks with SSI -->
                @if ($banks->isEmpty())
                    <div class="alert alert-warning" role="alert">
                        <strong>No banks with associated SSIs found!</strong> There are no banks with SSI records available.
                    </div>
                @else
                    <table class="table table-bordered table-hover m-0 shadow" id="data-grid">
                        <thead class="thead-themed">
                            <tr>
                                <th width="30"></th>
                                <th>Name</th>
                                <th>Currency</th>
                                <th>Is Active</th>
                                <th>Created by</th>
                                <th>Created at</th>
                                <th>Updated by</th>
                                <th>Updated at</th>
                                @can('delete records')
                                    <th width="30" align="center"></th>
                                @endcan
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($banks as $bank)
                                @foreach ($bank->bankSSI as $ssi)
                                    <tr>
                                        <td align="center" scope="row">
                                            @can('edit bank')
                                                <a
                                                    href="{{ url('/finance-management/bank-ssi/edit/' . $bank->id . '/' . $ssi->id) }}"><i
                                                        class="fal fa-edit"></i></a>
                                            @endcan
                                        </td>
                                        <td>{{ $bank->name }}</td>
                                        <td>{{ $ssi->currency->name }}</td>
                                        <td>
                                            <span class="badge {{ $statusBadge[$ssi->is_active] }}">
                                                {{ $ssi->is_active ? 'Yes' : 'No' }}
                                            </span>
                                        </td>
                                        <td>{{ $ssi->createdBy->name }}</td>
                                        <td>{{ \Carbon\Carbon::parse($ssi->created_at)->format('d/m/Y') }}</td>
                                        <td>{{ $ssi->updatedBy->name ?? '' }}</td>
                                        <td>{{ \Carbon\Carbon::parse($ssi->updated_at)->format('d/m/Y') }}</td>
                                        @can('delete records')
                                            <td>
                                                <a href="javascript:deleteBankSSI({{ $bank->id }} ,{{ $ssi->id }})"><i
                                                        class="alert-danger fal fa-times-circle rounded-circle"></i></a>
                                            </td>
                                        @endcan
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>

    <form method="POST" name="delete-bank-ssi" id="delete-bank-ssi"
        action="{{ url('/finance-management/bank-ssi/delete') }}">
        @csrf
        <input type="hidden" name="bank_id" id="bank_id" value="">
        <input type="hidden" name="bankssi_id" id="bankssi_id" value="">
    </form>
@stop

@section('scripts')
    <script>
        $(document).ready(function() {
            $('#data-grid').dataTable({
                responsive: true
            });
        });

        function deleteBankSSI(id, ssi_id) {
            Swal.fire({
                title: "Are you sure?",
                text: "Bank SSI will be deleted permanently",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Yes, delete it!"
            }).then(function(result) {
                if (result.value) {
                    $("#bank_id").val(id);
                    $("#bankssi_id").val(ssi_id);
                    document.getElementById('delete-bank-ssi').submit()
                }
            });
        }
    </script>

@stop
