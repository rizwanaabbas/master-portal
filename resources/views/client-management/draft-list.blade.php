@extends('layouts.cp')
@section('title', 'Draft Client List - Client Management')

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
                Draft Client List
            </h2>

        </div>

        <div class="panel-container show mb-3">
            <div class="panel-content" style="overflow: scroll;">
                @include('common.error-message')

                <table class="table table-bordered table-hover m-0 shadow" id="data-grid">
                    <thead class="thead-themed">
                        <tr>
                            <th width="30"></th>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Created Date</th>
                            <th>Status</th>
                            <th>Creator</th>
                            <th>Introducer</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @if ($clients)
                            @foreach ($clients as $client)
                                <tr>
                                    <td align="center" scope="row"><a
                                            href="{{ url('/client-management/clients/' . $client->type . '/edit/' . $client->id) }}"><i
                                                class="fal fa-edit"></i></a></td>
                                    <td width="30%">{{ $client->name }}</td>
                                    <td>{{ $client->is_bank ? 'bank' : $client->type }}</td>
                                    <td>{{ \Carbon\Carbon::parse($client->created_at)->format('d M, Y') }}</td>
                                    <td><span class="badge {{ $statusBadge[$client->status] }}">{{ $client->status }}</span>
                                    </td>
                                    <td>
                                        @if ($client->creator !== null)
                                            {{ $client->creator->name }}
                                        @endif
                                    </td>
                                    <td>
                                        @if ($client->introducer !== null)
                                            {{ $client->introducer }}
                                        @endif
                                    </td>
                                    @can('delete records')
                                        <td>
                                            <a href="javascript:deleteClient({{ $client->id }})">
                                                <i class="alert-danger fal fa-times-circle rounded-circle"></i>
                                            </a>
                                        </td>
                                    @endcan

                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="6" align="center">No records found.</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <form method="POST" name="delete-client" id="delete-client" action="{{ url('/client-management/clients/delete') }}">
        @csrf
        <input type="hidden" name="client_id" id="client_id" value="">
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

        function deleteClient(id) {

            Swal.fire({
                title: "Are you sure?",
                text: "Client will be rejected and user deleted!",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Yes, do it!"
            }).then(function(result) {
                if (result.value) {
                    $("#client_id").val(id);
                    document.getElementById('delete-client').submit()
                }
            });
        }
    </script>
@stop
