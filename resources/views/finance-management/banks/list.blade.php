@extends('layouts.cp')
@section('title', 'Bank List - Finance Management')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Finance Management</span>
        </h1>
        <a href="{{ url('/finance-management/banks/new') }}" class="btn btn-primary mr-3"><i class="fal fa-plus-circle"></i>
            Add New </a>
    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false"
        data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                Banks List
            </h2>

        </div>

        <div class="panel-container show mb-3">
            <div class="panel-content" style="overflow: scroll;">
                @include('common.error-message')
                <table class="table table-bordered table-hover m-0 shadow">
                    <thead class="thead-themed">
                        <tr>
                            <th width="30"></th>
                            <th>Name</th>
                            <th>Accounts</th>
                            <th>Address</th>
                            <th>Status</th>
                            <th>Created at</th>
                            @can('delete records')
                                <th width="30" align="center"></th>
                            @endcan
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($banks as $bank)
                            <tr>
                                <td align="center" scope="row">
                                    @can('edit bank')
                                        <a href="{{ url('/finance-management/banks/edit/' . $bank->id) }}"><i
                                                class="fal fa-edit"></i></a>
                                    @endcan
                                </td>
                                <td>{{ $bank->name }}</td>
                                <td>{{ $bank->accounts ? $bank->accounts->count() : '--' }}</td>
                                <td>{{ $bank->address }}</td>
                                <td><span class="badge {{ $statusBadge[$bank->status] }}">{{ $bank->status }}</span></td>
                                <td>{{ \Carbon\Carbon::parse($bank->created_at)->format('d M, Y') }}</td>
                                @can('delete records')
                                    <td>
                                        <a href="javascript:deleteBank({{ $bank->id }})"><i
                                                class="alert-danger fal fa-times-circle rounded-circle"></i></a>
                                    </td>
                                @endcan
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <form method="POST" name="delete-bank" id="delete-bank" action="{{ url('/finance-management/banks/delete') }}">
        @csrf
        <input type="hidden" name="bank_id" id="bank_id" value="">
    </form>

    <script>
        function deleteBank(id) {

            Swal.fire({
                title: "Are you sure?",
                text: "Bank will be deleted permanently",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Yes, delete it!"
            }).then(function(result) {
                if (result.value) {
                    $("#bank_id").val(id);
                    document.getElementById('delete-bank').submit()
                }
            });
        }
    </script>

@stop
