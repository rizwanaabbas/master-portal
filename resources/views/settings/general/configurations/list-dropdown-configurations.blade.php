@extends('layouts.cp')
@section('title', 'General Configurations - System Settings')

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
                Configurations List
            </h2>

        </div>

        <div class="panel-container show mb-3">
            <div class="panel-content" style="overflow: scroll;">
                @include('common.error-message')
                <form method="post" action="{{ url('/settings/general-configurations/dropdown-configurations/store') }}">
                    @csrf
                    <div class="row p-2">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label" for="type">Dropdown Type</label>
                                <input type="text" id="type" name="type" value="" class="form-control"
                                    required>
                                <small>Example: CONTACT_US_REASON or CARD_BLOCKING_REASON</small>
                                @error('type')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="form-label" for="value">Name</label>
                                <input type="text" id="value" name="value" value="" class="form-control"
                                    required>
                                @error('value')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="form-label" for="text">Text (Description)</label>
                                <input type="text" id="text" name="text" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-2 mt-4">
                            <button type="submit" class="btn btn-primary">Save</button>
                        </div>
                    </div>
                </form>
                <hr>
                <table class="table table-bordered table-hover m-0 shadow" id="data-grid">
                    <thead class="thead-themed">
                        <tr>
                            <th width="30">S.NO</th>
                            <th>Type</th>
                            <th>Value</th>
                            <th>Text</th>
                            <th>Created At</th>
                            @can('delete records')
                                <th width="30" align="center">Action</th>
                            @endcan
                        </tr>
                    </thead>
                    <tbody>
                        @if ($dropdownConfigurations)
                            @foreach ($dropdownConfigurations as $config)
                                <tr>
                                    <td align="center" scope="row">{{ $loop->iteration }}</td>
                                    <td>{{ $config->type }}</td>
                                    <td>{{ $config->value }}</td>
                                    <td>{{ $config->text }}</td>
                                    <td>{{ \Carbon\Carbon::parse($config->created_at)->format('d M, Y') }}</td>
                                    @can('delete records')
                                        <td><a href="javascript:handleDelete({{ $config->id }})"><i
                                                    class="alert-danger fal fa-times-circle rounded-circle"></i></a></td>
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

    <form method="POST" name="delete-configuration" id="delete_configuration"
        action="{{ url('/settings/general-configurations/dropdown-configurations/delete') }}">
        @csrf
        <input type="hidden" name="configuration_id" id="configuration_id" value="">
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

        function handleDelete(id) {

            Swal.fire({
                title: "Are you sure?",
                text: "This Configuration will be deleted permanently",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Yes, delete it!"
            }).then(function(result) {
                if (result.value) {
                    $("#configuration_id").val(id);
                    document.getElementById('delete_configuration').submit()
                }
            });
        }
    </script>
@stop
