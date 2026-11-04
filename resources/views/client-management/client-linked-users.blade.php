@extends('layouts.cp')
@section('title', 'Client Users - Client Management')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Client Management</span>
        </h1>
    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false"
        data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                Linked Users
            </h2>

        </div>

        <div class="panel-container show mb-3">
            <div class="panel-content" style="overflow: scroll;">
                @include('common.error-message')


                <table class="table table-bordered table-hover m-0 shadow" id="data-grid">
                    <thead class="thead-themed">

                        <tr>
                            <th width="30">S.NO</th>
                            <th>Client Type</th>
                            <th>Client</th>

                            <th>User Name</th>

                            <th>Email</th>
                            <!-- <th>Roles</th>
                                        <th width="30" align="center"></th> -->
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($user_clients as $cu)
                            <tr>
                                <td align="center" scope="row">{{ $loop->iteration }}</td>
                                <td>{{ $cu->client->type ?? '--' }}</td>
                                <td>{{ $cu->client->name ?? '--' }}</td>

                                <td> {{ $cu->user->name ?? '--' }}</td>
                                <td>{{ $cu->user->email ?? '--' }}</td>
                                <!-- <td>
                                                @if ($cu->user)
    @foreach ($cu->user->roles->pluck('name')->toArray() as $roleName)
    <span class="badge badge-warning">{{ $roleName }}</span>
    @endforeach
@else
    --
    @endif
                                            </td> -->
                                <!-- <td></td> -->
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <form method="POST" name="delete-link" id="delete-link" action="{{ url('/user-management/link-user/delete') }}">
        @csrf
        <input type="hidden" name="cu_id" id="cu_id" value="">
    </form>

@stop

@section('scripts')
    <script>
        $(document).ready(function() {
            $('.select2').select2();

            $('#data-grid').dataTable({
                responsive: true
            });
        });
    </script>
@stop
