@extends("layouts.cp")
@section('title', 'Manage Permissions - User Management')

@section("content")

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>User Management</span>
        </h1>
        <a href="{{ url("/user-management/roles") }}" class="btn btn-primary mr-3"><i class="fal fa-arrow-alt-left"></i> Go Back </a>
    </div>

    <div  class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false" data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                Manage Permissions — {{ $role->name }}
            </h2>
        </div>

        <form method="post" action="{{ url("/user-management/roles/permissions/storePermission") }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="role_id" value="{{ $role->id }}">
            <div class="panel-container show">
                <div class="panel-content">
                    @include("common.error-message")
                    @php
                        $assigned = $role->getAllPermissions()->pluck('name')->toArray();
                    @endphp

                    @foreach($grouped as $groupKey => $bundle)
                        @php $groupPermissions = $bundle['permissions']; @endphp
                        @if ($groupPermissions->isEmpty())
                            @continue
                        @endif
                        <div class="card mb-3">
                            <div class="card-header d-flex justify-content-between align-items-center py-2">
                                <strong>{{ $bundle['name'] }}</strong>
                                <button type="button" class="btn btn-xs btn-outline-secondary btn-toggle-group" data-group="{{ $groupKey }}">Toggle all</button>
                            </div>
                            <div class="card-body py-3">
                                <div class="row">
                                    @foreach($groupPermissions as $permission)
                                        <div class="col-md-3 mb-2">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" name="permissions[]"
                                                    class="custom-control-input group-{{ $groupKey }}"
                                                    value="{{ $permission->name }}"
                                                    id="permission-{{ $permission->id }}"
                                                    @if (in_array($permission->name, $assigned, true)) checked @endif>
                                                <label class="custom-control-label" for="permission-{{ $permission->id }}">{{ $permission->name }}</label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach

                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <hr>
                                <button type="submit" class="btn btn-primary">Submit</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

@stop
@section('scripts')
    <script>
        $(document).on('click', '.btn-toggle-group', function () {
            const cls = '.group-' + $(this).data('group');
            const boxes = $(cls);
            const allChecked = boxes.length && boxes.filter(':checked').length === boxes.length;
            boxes.prop('checked', !allChecked);
        });
    </script>
@stop
