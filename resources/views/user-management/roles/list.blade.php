@extends('layouts.cp')
@section('title', 'Roles List - User Management')

@section('content')

    <style>
        .role-list-search {
            max-width: 360px;
            border-radius: 0;
            border-color: #cbd5e1;
        }
        .role-perm-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: .35rem;
            margin-bottom: .65rem;
            padding: 0;
        }
        .role-perm-tab {
            border: 1px solid #d5dbe3;
            border-radius: 0;
            background: #f8fafc;
            color: #334155;
            font-size: .75rem;
            font-weight: 650;
            padding: .32rem .6rem;
            line-height: 1.3;
            cursor: pointer;
        }
        .role-perm-tab.is-active {
            background: #e8eef4;
            border-color: #64748b;
            color: #0f172a;
        }
        .role-perm-count {
            display: inline-block;
            margin-left: .35rem;
            padding: 0 .3rem;
            background: #e2e8f0;
            color: #334155;
            font-size: .68rem;
            font-weight: 700;
        }
        .role-perm-tab.is-active .role-perm-count {
            background: #cbd5e1;
        }
        .role-perm-section {
            border: 1px solid #e2e8f0;
            margin-bottom: .55rem;
            border-radius: 0;
            background: #fff;
        }
        .role-perm-section-title {
            background: #eef2f6;
            color: #334155;
            border-bottom: 1px solid #e2e8f0;
            font-size: .75rem;
            font-weight: 700;
            letter-spacing: .02em;
            padding: .4rem .65rem;
        }
        .role-perm-section-body {
            background: #fff;
            padding: .55rem .65rem .35rem;
        }
        .role-perm-section .role-perm-badge {
            background: #f8fafc;
            color: #1e293b;
            border: 1px solid #e2e8f0;
            border-radius: 0;
            font-weight: 600;
            font-size: .75rem;
            padding: .28rem .45rem;
        }
        .role-list-table td { vertical-align: top; }
    </style>

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>User Management</span>
        </h1>
        @role('super admin')
            <a href="{{ url('/user-management/roles/new') }}" class="btn btn-primary mr-3"><i class="fal fa-plus-circle"></i> Add
                New </a>
        @endrole
    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false"
        data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                Roles List
            </h2>
        </div>

        <div class="panel-container show mb-3">
            <div class="panel-content" style="overflow: scroll;">
                @include('common.error-message')

                <div class="form-group mb-3">
                    <label class="form-label" for="role-list-search">Search</label>
                    <input type="search" id="role-list-search" class="form-control role-list-search" placeholder="Search role or permission">
                </div>

                <table class="table table-bordered table-hover m-0 shadow role-list-table">
                    <thead class="thead-themed">
                        <tr>
                            <th>Name</th>
                            <th>Permissions</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($roles as $role)
                            @php $sections = $roleSections[$role->id] ?? []; @endphp
                            <tr>
                                <td nowrap="nowrap" class="align-top pt-3 role-name">{{ $role->name }}</td>
                                <td class="align-top">
                                    @if (empty($sections))
                                        <span class="text-muted">None</span>
                                    @else
                                        <div class="role-perm-tabs">
                                            <button type="button" class="role-perm-tab is-active" data-role="{{ $role->id }}" data-section="all">All</button>
                                            @foreach ($sections as $section)
                                                <button type="button" class="role-perm-tab" data-role="{{ $role->id }}" data-section="{{ $section['key'] }}">
                                                    {{ $section['name'] }}
                                                    <span class="role-perm-count">{{ $section['permissions']->count() }}</span>
                                                </button>
                                            @endforeach
                                        </div>
                                        <div class="role-perm-sections" data-role="{{ $role->id }}">
                                            @foreach ($sections as $section)
                                                <div class="role-perm-section" data-section="{{ $section['key'] }}">
                                                    <div class="role-perm-section-title">{{ $section['name'] }}</div>
                                                    <div class="role-perm-section-body">
                                                        @foreach ($section['permissions'] as $permission)
                                                            <span class="badge badge-warning mb-1 role-perm-badge">{{ $permission->name }}</span>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </td>
                                <td style="width: 130px;" class="align-top pt-3"><a
                                        href="{{ url('/user-management/roles/permissions/' . $role->id) }}"
                                        class="text-primary"><i class="fal fa-lock-alt"></i> Permissions</a></td>
                            </tr>
                        @endforeach

                    </tbody>
                </table>

            </div>

        </div>
    </div>

    <form method="POST" name="delete-role" id="delete-role" action="{{ url('/user-management/roles/delete') }}">
        @csrf
        <input type="hidden" name="id" id="id" value="">
    </form>

    <script>
        function deleteRole(id) {

            function deleteRole(id) {

                Swal.fire({
                    title: "Are you sure?",
                    text: "User will be deleted permanently",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonText: "Yes, delete it!"
                }).then(function(result) {
                    if (result.value) {
                        $("#id").val(id);
                        document.getElementById('delete-role').submit()
                    }
                });
            }
    </script>

@stop
@section('scripts')
    <script>
        function applyRoleListFilters() {
            const query = ($('#role-list-search').val() || '').toLowerCase().trim();
            $('.role-list-table tbody tr').each(function () {
                const $row = $(this);
                const roleName = $row.find('.role-name').text().toLowerCase();
                const roleMatches = query === '' || roleName.indexOf(query) !== -1;
                const activeSection = $row.find('.role-perm-tab.is-active').data('section') || 'all';
                let anyVisible = roleMatches && query !== '';

                $row.find('.role-perm-section').each(function () {
                    const $section = $(this);
                    const sectionKey = $section.data('section');
                    const tabAllows = activeSection === 'all' || String(activeSection) === String(sectionKey);
                    let sectionHasMatch = false;
                    $section.find('.role-perm-badge').each(function () {
                        const badgeMatches = query === '' || roleMatches || $(this).text().toLowerCase().indexOf(query) !== -1;
                        $(this).toggle(badgeMatches);
                        if (badgeMatches) {
                            sectionHasMatch = true;
                        }
                    });
                    const showSection = tabAllows && sectionHasMatch;
                    $section.toggle(showSection);
                    if (showSection) {
                        anyVisible = true;
                    }
                });

                if (query === '' && $row.find('.role-perm-section').length === 0) {
                    anyVisible = true;
                }
                if (query !== '' && $row.find('.role-perm-badge').length === 0) {
                    anyVisible = roleMatches;
                }
                $row.toggle(anyVisible);
            });
        }

        $(document).on('click', '.role-perm-tab', function () {
            const $button = $(this);
            const $row = $button.closest('tr');
            $row.find('.role-perm-tab').removeClass('is-active');
            $button.addClass('is-active');
            applyRoleListFilters();
        });

        $('#role-list-search').on('input', applyRoleListFilters);
    </script>
@stop
