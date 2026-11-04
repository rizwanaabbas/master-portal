@extends('layouts.cp')
@section('title', 'Permission List - User Management')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>User Management</span>
        </h1>
        @can('add permission')
            <button type="button" class="btn btn-primary mr-3" id="btn-add-permission"><i class="fal fa-plus-circle"></i>
                Add Permission </button>
        @endcan
    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false"
        data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>Permissions</h2>
        </div>

        <div class="panel-container show mb-3">
            <div class="panel-content">
                @include('common.error-message')

                <ul class="nav nav-tabs nav-tabs-clean" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" data-toggle="tab" href="#tab-permissions" role="tab">Permissions</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="tab" href="#tab-module-groups" role="tab">Module Groups</a>
                    </li>
                </ul>

                <div class="tab-content pt-3">
                    <div class="tab-pane fade show active" id="tab-permissions" role="tabpanel">
                        <div class="row mb-3">
                            <div class="col-md-3 mb-2">
                                <label class="form-label">Module Group</label>
                                <select id="filter-group" class="form-control">
                                    <option value="all">All</option>
                                    @foreach ($moduleGroups as $group)
                                        <option value="{{ $group->id }}">{{ $group->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3 mb-2">
                                <label class="form-label">Guard</label>
                                <select id="filter-guard" class="form-control">
                                    <option value="all">All</option>
                                    @foreach ($guards as $guard)
                                        <option value="{{ $guard }}">{{ $guard }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2 mb-2 d-flex align-items-end">
                                <button type="button" id="btn-reset-permission-filters" class="btn btn-sm btn-outline-secondary">Reset</button>
                            </div>
                        </div>

                        <table class="table table-bordered table-hover m-0 shadow" id="permissions-grid" style="width:100%">
                            <thead class="thead-themed">
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Module Group</th>
                                    <th>Guard</th>
                                    <th>Created</th>
                                    @if (auth()->user()->can('edit permission') || auth()->user()->can('update permission'))
                                        <th width="50"></th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>

                    <div class="tab-pane fade" id="tab-module-groups" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <p class="text-muted mb-0">Organize permissions by product module. This does not change access.</p>
                            @can('add permission')
                                <button type="button" class="btn btn-sm btn-primary" id="btn-add-group"><i class="fal fa-plus"></i> Add Group</button>
                            @endcan
                        </div>
                        <table class="table table-bordered table-hover" id="module-groups-table">
                            <thead class="thead-themed">
                                <tr>
                                    <th>Name</th>
                                    <th>Slug</th>
                                    <th>Permissions</th>
                                    @if (auth()->user()->can('edit permission') || auth()->user()->can('update permission'))
                                        <th width="50"></th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($moduleGroups as $group)
                                    <tr>
                                        <td>{{ $group->name }}</td>
                                        <td><code>{{ $group->slug }}</code></td>
                                        <td>{{ $group->permissions()->count() }}</td>
                                        @if (auth()->user()->can('edit permission') || auth()->user()->can('update permission'))
                                            <td class="text-center">
                                                <button type="button" class="btn btn-link p-0 btn-edit-group"
                                                    data-id="{{ $group->id }}" data-name="{{ $group->name }}"
                                                    data-locked="{{ $group->slug === 'general' ? 1 : 0 }}">
                                                    <i class="fal fa-edit"></i>
                                                </button>
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="permission-form-modal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form id="permission-form">
                    @csrf
                    <input type="hidden" name="id" id="permission-id" value="">
                    <div class="modal-header">
                        <h5 class="modal-title" id="permission-form-title">Add Permission</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div id="permission-form-error" class="alert alert-danger d-none"></div>
                        <div class="form-group">
                            <label class="form-label" for="permission-name">Permission Name</label>
                            <input type="text" id="permission-name" name="name" class="form-control" required autocomplete="off">
                            <small class="text-muted" id="permission-name-hint">Use lowercase words, e.g. view users. Comma-separated names are allowed when adding.</small>
                        </div>
                        <div class="form-group mb-0">
                            <label class="form-label" for="permission-group">Module Group</label>
                            <select id="permission-group" name="module_group_id" class="form-control" required>
                                @foreach ($moduleGroups as $group)
                                    <option value="{{ $group->id }}" @if ($group->slug === 'general') selected @endif>{{ $group->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="permission-form-submit">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="group-form-modal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form id="group-form">
                    <input type="hidden" id="group-id" value="">
                    <div class="modal-header">
                        <h5 class="modal-title" id="group-form-title">Add Module Group</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div id="group-form-error" class="alert alert-danger d-none"></div>
                        <div class="form-group mb-0">
                            <label class="form-label" for="group-name">Group Name</label>
                            <input type="text" id="group-name" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="group-form-submit">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@stop
@section('scripts')
    <script>
        const canEditPermission = @json(auth()->user()->can('edit permission') || auth()->user()->can('update permission'));
        const csrfToken = @json(csrf_token());

        $(document).ready(function() {
            function filterParams() {
                return {
                    filter_group: $('#filter-group').val(),
                    filter_guard: $('#filter-guard').val(),
                };
            }

            let columns = [];
            columns.push({ data: 'id', name: 'id' });
            columns.push({ data: 'name', name: 'name' });
            columns.push({ data: 'module_group_label', name: 'module_group_name' });
            columns.push({ data: 'guard_name', name: 'guard_name' });
            columns.push({ data: 'created_fmt', name: 'created_at' });
            if (canEditPermission) {
                columns.push({
                    data: 'edit_action',
                    orderable: false,
                    searchable: false,
                    className: 'text-center'
                });
            }

            $('#filter-group, #filter-guard').select2({ width: '100%' });
            $('#permission-group').select2({
                width: '100%',
                dropdownParent: $('#permission-form-modal')
            });

            const table = $('#permissions-grid').DataTable({
                processing: true,
                serverSide: true,
                pageLength: 25,
                order: [[1, 'asc']],
                ajax: {
                    url: "{{ url('/user-management/permissions') }}",
                    data: function(d) {
                        Object.assign(d, filterParams());
                    }
                },
                columns: columns
            });

            $('#filter-group, #filter-guard').on('change', function() {
                table.ajax.reload();
            });

            $('#btn-reset-permission-filters').on('click', function() {
                $('#filter-group').val('all').trigger('change.select2');
                $('#filter-guard').val('all').trigger('change.select2');
                table.ajax.reload();
            });

            function openPermissionModal(mode, id, name, groupId) {
                $('#permission-form-error').addClass('d-none').text('');
                $('#permission-form')[0].reset();
                $('#permission-id').val(id || '');
                $('#permission-name').val(name || '');
                const selectedGroup = groupId || $('#permission-group option[selected]').val() || $('#permission-group option:first').val();
                $('#permission-group').val(String(selectedGroup)).trigger('change.select2');
                if (mode === 'edit') {
                    $('#permission-form-title').text('Edit Permission');
                    $('#permission-name-hint').text('Changing a permission name updates it everywhere it is assigned.');
                    $('#permission-form-submit').text('Update');
                } else {
                    $('#permission-form-title').text('Add Permission');
                    $('#permission-name-hint').text('Use lowercase words, e.g. view users. Comma-separated names are allowed when adding.');
                    $('#permission-form-submit').text('Save');
                }
                $('#permission-form-modal').modal('show');
                setTimeout(function() { $('#permission-name').trigger('focus'); }, 200);
            }

            $('#btn-add-permission').on('click', function() {
                openPermissionModal('add');
            });

            $('#permissions-grid').on('click', '.btn-edit-permission', function() {
                openPermissionModal('edit', $(this).data('id'), $(this).data('name'), $(this).data('group-id'));
            });

            $('#permission-form').on('submit', function(e) {
                e.preventDefault();
                const id = $('#permission-id').val();
                const url = id
                    ? "{{ url('/user-management/permissions/update') }}"
                    : "{{ url('/user-management/permissions/store') }}";
                $('#permission-form-error').addClass('d-none').text('');
                $('#permission-form-submit').prop('disabled', true);
                $.ajax({
                    url: url,
                    method: 'POST',
                    data: {
                        _token: csrfToken,
                        id: id,
                        name: $('#permission-name').val(),
                        module_group_id: $('#permission-group').val()
                    }
                }).done(function(res) {
                    $('#permission-form-modal').modal('hide');
                    table.ajax.reload(null, false);
                    if (window.toastr && res.message) {
                        toastr.success(res.message);
                    }
                }).fail(function(xhr) {
                    let msg = 'Unable to save permission.';
                    try {
                        const body = xhr.responseJSON || {};
                        if (body.errors && body.errors.name) {
                            msg = body.errors.name[0];
                        } else if (body.message) {
                            msg = body.message;
                        }
                    } catch (err) {}
                    $('#permission-form-error').removeClass('d-none').text(msg);
                }).always(function() {
                    $('#permission-form-submit').prop('disabled', false);
                });
            });

            function openGroupModal(id, name, locked) {
                $('#group-form-error').addClass('d-none').text('');
                $('#group-id').val(id || '');
                $('#group-name').val(name || '').prop('disabled', !!locked);
                $('#group-form-title').text(id ? 'Edit Module Group' : 'Add Module Group');
                $('#group-form-modal').modal('show');
            }

            $('#module-groups-table').DataTable({
                pageLength: 25,
                order: [[0, 'asc']],
                columnDefs: canEditPermission ? [{ targets: -1, orderable: false, searchable: false }] : []
            });

            $('#btn-add-group').on('click', function() {
                openGroupModal();
            });
            $('#module-groups-table').on('click', '.btn-edit-group', function() {
                openGroupModal($(this).data('id'), $(this).data('name'), $(this).data('locked') == 1);
            });

            $('#group-form').on('submit', function(e) {
                e.preventDefault();
                const id = $('#group-id').val();
                const url = id
                    ? "{{ url('/user-management/permissions/groups/update') }}"
                    : "{{ url('/user-management/permissions/groups/store') }}";
                $('#group-form-submit').prop('disabled', true);
                $.ajax({
                    url: url,
                    method: 'POST',
                    data: { _token: csrfToken, id: id, name: $('#group-name').val() }
                }).done(function() {
                    window.location.reload();
                }).fail(function(xhr) {
                    let msg = 'Unable to save group.';
                    try {
                        const body = xhr.responseJSON || {};
                        if (body.message) msg = body.message;
                    } catch (err) {}
                    $('#group-form-error').removeClass('d-none').text(msg);
                    $('#group-form-submit').prop('disabled', false);
                });
            });
        });
    </script>
@stop
