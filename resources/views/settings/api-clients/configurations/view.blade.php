@extends('layouts.cp')
@section('title', 'View API Client - System Settings')

@section('content')
    <div class="subheader">
        <h1 class="subheader-title">
            <span class="fw-300">System Settings</span>
        </h1>
        <div class="d-flex">
            <a href="{{ url('/settings/api-clients') }}" class="btn btn-primary mr-3">
                <i class="fal fa-arrow-left"></i>Go Back
            </a>
        </div>
    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false"
        data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                API Client Details
            </h2>
        </div>

        <div class="panel-container show mb-3">
            <div class="panel-content">
                @include('common.error-message')
                <table class="table table-bordered table-hover m-0 shadow" id="data-grid">
                    <thead class="thead-dark">
                        <tr>
                            <th width="200">Client Name</th>
                            <td>{{ $apiClient->name }}</td>
                        </tr>
                        <tr>
                            <th>Client Secret</th>
                            <td>{{ $apiClient->api_key }}</td>
                        </tr>
                        <tr>
                            <th>Creator</th>
                            <td>{{ $apiClient->creator ? $apiClient->creator->name : '--' }}</td>
                        </tr>
                        <tr>
                            <th>Created At</th>
                            <td>{{ \Carbon\Carbon::parse($apiClient->created_at)->format('d M, Y') }}</td>
                        </tr>
                    </thead>
                </table>
                <hr>
                <form id="generate-key" method="post" action="{{ url('/settings/api-clients/regenerate-api-key') }}">
                    @csrf
                    <input type="hidden" name="api_client_id" value="{{ $apiClient->id }}">
                    <div class="row">
                        <div class="col-md-6">
                            <button type="button" onclick="generate()" class="btn btn-success">Generate New Key</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@stop
@section('scripts')
    <script>
        function generate() {
            Swal.fire({
                title: 'It will delete existing api key!',
                text: "Are you sure You want to proceed with generating new api key?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: "Yes, Generate it!",
            }).then(function(result) {
                if (result.value) {
                    document.getElementById('generate-key').submit()
                }
            });
        }
    </script>
@stop
