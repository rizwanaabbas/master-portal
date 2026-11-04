@extends('layouts.cp')
@section('title', 'Fireblocks Users')

@section('content')
    <div class="subheader">
        <h1 class="subheader-title">
            <span class="fw-300">Fireblocks Users</span>
        </h1>
    </div>

    <div class="panel">
        <div class="panel-hdr">
            <h2>Workspace users</h2>
            <div class="panel-toolbar">
                <button type="button" class="btn btn-success btn-sm mr-2" id="add-user-button">
                    <i class="fal fa-plus"></i> Add user
                </button>
                <button type="button" class="btn btn-primary btn-sm" id="sync-button" onclick="syncFireblocksUsers()">
                    <i class="fal fa-sync"></i> Sync from Fireblocks
                </button>
            </div>
        </div>
        <div class="panel-container show">
            <div class="panel-content">
                <p class="text-muted mb-3">
                    Map Fireblocks user IDs to names and roles. Add manually when the API key cannot list users.
                    Transaction Monitoring shows the name when mapped, otherwise the raw Fireblocks ID.
                    @if (!empty($lastSynced))
                        Last API sync: {{ \Illuminate\Support\Carbon::parse($lastSynced)->format('Y-m-d H:i') }}
                    @endif
                </p>
                <div id="sync-message"></div>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label" for="filter-role">Role</label>
                        <select id="filter-role" class="form-control w-100">
                            <option value="">All roles</option>
                            @foreach ($roles as $role)
                                <option value="{{ $role }}">{{ $role }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <table id="fireblocks-users-table" class="table table-bordered table-hover table-striped w-100">
                    <thead class="thead-themed">
                        <tr>
                            <th>Name</th>
                            <th>Role</th>
                            <th>Color</th>
                            <th>Fireblocks ID</th>
                            <th width="80">Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="user-modal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form id="user-form">
                    <div class="modal-header">
                        <h5 class="modal-title" id="user-modal-title">Add Fireblocks user</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="user-row-id" value="">
                        <div class="form-group">
                            <label for="user-fb-id">Fireblocks ID <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="user-fb-id" required maxlength="64" autocomplete="off">
                        </div>
                        <div class="form-group">
                            <label for="user-name">Name</label>
                            <input type="text" class="form-control" id="user-name" maxlength="255" placeholder="Optional — leave blank to show ID only">
                        </div>
                        <div class="form-group">
                            <label for="user-role">Role</label>
                            <input type="text" class="form-control" id="user-role" maxlength="64" placeholder="e.g. ADMIN, SIGNER, APPROVER" list="user-role-options">
                            <datalist id="user-role-options">
                                <option value="OWNER">
                                <option value="ADMIN">
                                <option value="NON_SIGNING_ADMIN">
                                <option value="SIGNER">
                                <option value="APPROVER">
                                <option value="EDITOR">
                                <option value="VIEWER">
                                <option value="AUDITOR">
                            </datalist>
                        </div>
                        <div class="form-group mb-0">
                            <label for="user-badge-color">Badge color</label>
                            <div class="d-flex align-items-center">
                                <input type="color" class="form-control" id="user-badge-color" value="#6C757D" style="max-width:70px;padding:2px;height:38px;">
                                <span class="badge ml-2" id="user-badge-preview">Preview</span>
                            </div>
                        </div>
                        <div id="user-form-error" class="alert alert-danger mt-3 d-none"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="user-save-button">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@stop

@section('scripts')
    <script>
        $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } });

        let usersTable;

        function reloadUsersTable() {
            if (usersTable) {
                usersTable.ajax.reload(null, false);
            }
        }

        function syncFireblocksUsers() {
            $('#sync-button').prop('disabled', true);
            $('#sync-message').html('').removeClass('alert alert-danger alert-success');

            $.ajax({
                url: "{{ url('/fireblocks/users/sync') }}",
                method: 'POST',
                success: function (res) {
                    if (!res.success) {
                        $('#sync-message').html(res.message || 'Sync failed. Add users manually below.').addClass('alert alert-danger');
                        $('#sync-button').prop('disabled', false);
                        return;
                    }
                    $('#sync-message').html(res.message || 'Users synced.').addClass('alert alert-success');
                    reloadUsersTable();
                    $('#sync-button').prop('disabled', false);
                },
                error: function (xhr) {
                    const res = xhr.responseJSON || {};
                    $('#sync-message').html(res.message || res.error || 'Sync failed. Add users manually.').addClass('alert alert-danger');
                    $('#sync-button').prop('disabled', false);
                }
            });
        }

        function resetUserForm() {
            $('#user-row-id').val('');
            $('#user-fb-id').val('').prop('readonly', false);
            $('#user-name').val('');
            $('#user-role').val('');
            $('#user-badge-color').val('#6c757d');
            updateBadgePreview();
            $('#user-form-error').addClass('d-none').text('');
            $('#user-modal-title').text('Add Fireblocks user');
        }

        function openAddUser() {
            resetUserForm();
            $('#user-modal').modal('show');
        }

        function openEditUser(id) {
            resetUserForm();
            $.get("{{ url('/fireblocks/users') }}/" + id).done(function (res) {
                const row = res.data || {};
                $('#user-row-id').val(row.id);
                $('#user-fb-id').val(row.fireblocks_user_id || '');
                $('#user-name').val((row.name || '').trim());
                $('#user-role').val(row.role || '');
                $('#user-badge-color').val((row.badge_color || '#6C757D').toLowerCase());
                updateBadgePreview();
                $('#user-modal-title').text('Edit Fireblocks user');
                $('#user-modal').modal('show');
            }).fail(function () {
                $('#sync-message').html('Could not load user.').addClass('alert alert-danger');
            });
        }

        function updateBadgePreview() {
            const color = $('#user-badge-color').val() || '#6c757d';
            const name = $('#user-name').val().trim() || 'Preview';
            $('#user-badge-preview').text(name).css({
                'background-color': color,
                'color': '#fff',
            });
        }

        $(function () {
            $('#user-badge-color, #user-name').on('input change', updateBadgePreview);
            usersTable = $('#fireblocks-users-table').DataTable({
                processing: true,
                serverSide: true,
                pageLength: 25,
                order: [[0, 'asc']],
                ajax: {
                    url: "{{ url('/fireblocks/users/data') }}",
                    data: function (d) {
                        d.role = $('#filter-role').val();
                    },
                },
                columns: [
                    { data: 'name', name: 'name' },
                    { data: 'role', name: 'role' },
                    { data: 'color_swatch', orderable: false, searchable: false },
                    { data: 'fireblocks_user_id', name: 'fireblocks_user_id' },
                    { data: 'actions', orderable: false, searchable: false },
                ],
            });

            $('#filter-role').on('change', reloadUsersTable);
            $('#add-user-button').on('click', openAddUser);
            $('#fireblocks-users-table').on('click', '.btn-edit-user', function () {
                openEditUser($(this).data('id'));
            });

            $('#user-form').on('submit', function (e) {
                e.preventDefault();
                const id = $('#user-row-id').val();
                const payload = {
                    fireblocks_user_id: $('#user-fb-id').val().trim(),
                    name: $('#user-name').val().trim(),
                    role: $('#user-role').val().trim(),
                    badge_color: $('#user-badge-color').val(),
                };
                $('#user-save-button').prop('disabled', true);
                $('#user-form-error').addClass('d-none').text('');

                $.ajax({
                    url: id ? ("{{ url('/fireblocks/users') }}/" + id) : "{{ url('/fireblocks/users') }}",
                    method: id ? 'PUT' : 'POST',
                    data: payload,
                    success: function (res) {
                        $('#user-save-button').prop('disabled', false);
                        if (!res.success) {
                            $('#user-form-error').removeClass('d-none').text(res.message || 'Save failed.');
                            return;
                        }
                        $('#user-modal').modal('hide');
                        $('#sync-message').html(res.message || 'Saved.').removeClass('alert-danger').addClass('alert alert-success');
                        reloadUsersTable();
                    },
                    error: function (xhr) {
                        $('#user-save-button').prop('disabled', false);
                        const res = xhr.responseJSON || {};
                        let msg = res.message || 'Save failed.';
                        if (res.errors) {
                            msg = Object.values(res.errors).flat().join(' ');
                        }
                        $('#user-form-error').removeClass('d-none').text(msg);
                    }
                });
            });
        });
    </script>
@stop
