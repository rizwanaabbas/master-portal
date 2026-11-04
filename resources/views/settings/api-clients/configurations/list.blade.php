@extends('layouts.cp')
@section('title', 'API Clients - System Settings')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>System Settings</span>
        </h1>
    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false"
        data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                API Clients List
            </h2>

        </div>

        <div class="panel-container show mb-3">
            <div class="panel-content">
                @include('common.error-message')
                <form method="post" action="{{ url('/settings/api-clients/store') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label" for="user_id">Client Name</label>
                                <select name="user_id" autofocus id="user_id" class="form-control select2 w-100" required>
                                    <option value="">Select Client</option>
                                    @foreach ($users as $user)
                                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6 mt-4 d-flex justify-content-end">
                            <button type="submit"class="text-white btn btn-success" id="generate_secret">Generate API
                                Key</button>
                        </div>
                    </div>
                </form>
                <hr>
                <table class="table table-bordered table-hover m-0 shadow" id="data-grid">
                    <thead class="thead-themed">
                        <tr>
                            {{-- <th width="20">View</th> --}}
                            <th>Client Name</th>
                            <th>Client Email</th>
                            <th>Client ID</th>
                            <th>Client Secret</th>
                            <th>Created At</th>
                            <th>Updated At</th>
                            <th>Access Status</th>
                            {{-- <th width="30" align="center">Delete</th> --}}
                        </tr>
                    </thead>
                    <tbody>
                        @if ($apiClients)
                            @foreach ($apiClients as $apiClient)
                                <tr>
                                    {{-- <td align="center">
                                        <a href="{{ url('/settings/api-client/view/' . $apiClient->id) }}"><i
                                                class="fal fa-tv"></i></a>
                                    </td> --}}
                                    <td width="20%">{{ $apiClient->name }}</td>
                                    <td>{{ $apiClient->email }}</td>
                                    <td>{{ $apiClient->id }}</td>
                                    <td>{{ $apiClient->secret ? (substr($apiClient->secret, 0, 4) . str_repeat('*', max(strlen($apiClient->secret) - 8, 0)) . substr($apiClient->secret, -4)) : '' }}</td>
                                    <td>{{ \Carbon\Carbon::parse($apiClient->created_at)->format('d M, Y') }}</td>
                                    <td>{{ \Carbon\Carbon::parse($apiClient->updated_at)->format('d M, Y') }}</td>
                                    <td>
                                        @if ($apiClient->revoked == 0)
                                            <button type="button" class="btn btn-danger btn-sm"
                                                onclick="toggleAccess({{ $apiClient->id }}, 'revoke')">
                                                Revoke Access
                                            </button>
                                        @else
                                            <button type="button" class="btn btn-success btn-sm"
                                                onclick="toggleAccess({{ $apiClient->id }}, 'reinstate')">
                                                Reinstate Access
                                            </button>
                                        @endif
                                    </td>
                                    {{-- <td><a href="javascript:deleteApiClient({{ $apiClient->id }})"><i
                                                class="alert-danger fal fa-times-circle rounded-circle"></i></a></td> --}}
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="6" align="center">No API Clients Found</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <form method="POST" name="delete-api-client" id="delete-api-client"
        action="{{ url('/settings/api-clients/delete') }}">
        @csrf
        <input type="hidden" name="api_client_id" id="api_client_id" value="">
    </form>

    <form method="POST" name="toggle-access-form" id="toggle-access-form"
        action="{{ url('/settings/api-clients/toggle-access') }}">
        @csrf
        <input type="hidden" name="client_id" id="toggle_client_id" value="">
    </form>

@stop
@section('scripts')
    <script>
        $(document).ready(function() {
            $("#generate_secret").prop("disabled", true);

            $("#user_id").on("input", function() {
                var userName = $(this).val();
                $("#generate_secret").prop("disabled", userName === "");
            });
        })

        function deleteApiClient(id) {
            Swal.fire({
                title: "Are you sure?",
                text: "Client API will be deleted permanently.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Yes, delete it!",
            }).then(function(result) {
                if (result.value) {
                    $("#api_client_id").val(id);
                    document.getElementById("delete-api-client").submit();
                }
            })
        }

        function toggleAccess(id, action) {
            const actionMapping = {
                'revoke': {
                    title: "Revoke Access?",
                    text: "The client will no longer be able to use the API.",
                    confirmBtnText: "Yes, revoke it!"
                },
                'reinstate': {
                    title: "Reinstate Access?",
                    text: "The client's access to the API will be restored.",
                    confirmBtnText: "Yes, reinstate it!"
                }
            };

            const {
                title,
                text,
                confirmBtnText
            } = actionMapping[action];


            Swal.fire({
                title: title,
                text: text,
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: confirmBtnText,
            }).then(function(result) {
                if (result.value) {
                    $("#toggle_client_id").val(id);
                    document.getElementById("toggle-access-form").submit();
                }
            })
        }
    </script>
@stop
