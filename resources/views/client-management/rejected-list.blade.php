@extends('layouts.cp')
@section('title', 'Rejected Client List - Client Management')

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
                Rejected Client List
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
                            <th>Member Since</th>
                            <th>Status</th>
                            @if (in_array('SubAccounts', $enabledModules))
                                <th>Is Sub Account</th>
                            @endif
                            <th>Creator</th>
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
                                    @if (in_array('SubAccounts', $enabledModules))
                                        <td>
                                            <span class="badge {{ $client->parent_id ? 'badge-success' : 'badge-danger' }}">
                                                {{ $client->parent_id ? 'Yes' : 'No' }}
                                            </span>{{ $client->parent_id ? ' - ' . $client->parent->name : '' }}
                                        </td>
                                    @endif
                                    <td>{{ $client->creator ? $client->creator->name : '--' }}</td>
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
                text: "Client will be deleted permanently",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Yes, delete it!"
            }).then(function(result) {
                if (result.value) {
                    $("#client_id").val(id);
                    document.getElementById('delete-client').submit()
                }
            });
        }
    </script>
@stop
