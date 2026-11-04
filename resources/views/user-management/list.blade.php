@extends('layouts.cp')
@section('title', 'User List - User Management')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>User Management</span>
        </h1>
        <a href="{{ url('/user-management/users/new') }}" class="btn btn-primary mr-3"><i class="fal fa-plus-circle"></i> Add
            New </a>
    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false"
        data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                User List
            </h2>

        </div>

        <div class="panel-container show mb-3">
            <div class="panel-content" style="overflow: scroll;">
                @include('common.error-message')

                <table class="table table-bordered table-hover m-0 shadow" id="data-grid">
                    <thead class="thead-themed">
                        <tr>
                            <th width="30"></th>
                            <th width="30">Login</th>
                            <th>Name</th>
                            <th>Manager</th>
                            <th>Email</th>
                            <th>Is Locked?</th>
                            <th>Roles</th>
                            <th>Unit</th>
                            @can('delete records')
                                <th width="30" align="center"></th>
                            @endcan
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $user)
                            @if (\Illuminate\Support\Facades\Auth::user()->hasRole('super admin'))
                                <tr>
                                    <td align="center" scope="row"><a
                                            href="{{ url('/user-management/users/edit/' . $user->id) }}"><i
                                                class="fal fa-edit"></i></a></td>
                                    <td align="center"><a
                                            href="{{ url('/user-management/users/impersonate/' . $user->id) }}"><i
                                                class="fal fa-unlock"></i></a></td>
                                    <td>{{ $user->name }}</td>
                                    <td>{{ \App\Helpers\Common::getLineManagerName($user) }}</td>
                                    <td>{{ $user->email }}</td>
                                    <td>{{ $user->locked ? 'Yes' : 'No' }}</td>
                                    <td>
                                        @foreach ($user->roles->pluck('name')->toArray() as $roleName)
                                            <span class="badge badge-warning">{{ $roleName }}</span>
                                        @endforeach
                                    </td>
                                    <td>{{ $user->unit ? $user->unit->name : '---' }}</td>
                                    @can('delete records')
                                        <td><a href="javascript:deleteUser({{ $user->id }})"><i
                                                    class="alert-danger fal fa-times-circle rounded-circle"></i></a></td>
                                    @endcan
                                </tr>
                            @elseif(!$user->hasRole('super admin'))
                                <tr>
                                    <td align="center" scope="row"><a
                                            href="{{ url('/user-management/users/edit/' . $user->id) }}"><i
                                                class="fal fa-edit"></i></a></td>
                                    <td>{{ $user->name }}</td>
                                    <td>{{ \App\Helpers\Common::getLineManagerName($user) }}</td>
                                    <td>{{ $user->email }}</td>
                                    <td>{{ $user->locked ? 'Yes' : 'No' }}</td>
                                    <td>
                                        @foreach ($user->roles->pluck('name')->toArray() as $roleName)
                                            <span class="badge badge-warning">{{ $roleName }}</span>
                                        @endforeach
                                    </td>
                                    @can('delete records')
                                        <td><a href="javascript:deleteUser({{ $user->id }})"><i
                                                    class="alert-danger fal fa-times-circle rounded-circle"></i></a></td>
                                    @endcan
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
                @if (strpos(Request::route()->uri, 'user-management/users/search') !== false)
                    {{ $users->links('global-partials.pagination-post-req', [
                        'perPage' => $users->perPage(),
                        'currentPage' => $users->currentPage(),
                        'total' => $users->total(),
                        'hiddenInputs' => [
                            'per_page' => $request['per_page'],
                            'search_string' => $request['search_string'],
                        ],
                    ]) }}
                @else
                    {{ $users->links('global-partials.pagination', [
                        'perPage' => $users->perPage(),
                        'currentPage' => $users->currentPage(),
                        'total' => $users->total(),
                    ]) }}
                @endif
            </div>
        </div>
    </div>

    <form method="POST" name="delete-user" id="delete-user" action="{{ url('/user-management/users/delete') }}">
        @csrf
        <input type="hidden" name="user_id" id="user_id" value="">
    </form>

    @php
        $search_string = isset($request) && isset($request['search_string']) ? $request['search_string'] : null;
    @endphp

    <form name="search" id="search" method="post" action="{{ url('/user-management/users/search') }}"
        enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="per_page" id="per_page"
            value="{{ isset($request->per_page) ? $request->per_page : '' }}">
        <input type="hidden" name="search_string" id="search_string"
            value="{{ isset($request->search_string) ? $request->search_string : '' }}" />
    </form>

@stop

@section('scripts')
    <script>
        var hiddenInput = document.getElementById('search_string')
        var search_string = "<?php echo $search_string; ?>"
        var perPage = "<?php echo $request['per_page']; ?>"
        var recordsCount = "<?php echo count($users->items()); ?>"

        perPage = perPage ? perPage : 10
        document.getElementById('per_page').value = perPage

        $(document).ready(function() {
            $('#data-grid').dataTable({
                responsive: true,
                dom: "<'row mb-3'<'col-sm-12 col-md-6 d-flex align-items-center justify-content-start'f><'col-sm-12 col-md-6 d-flex justify-content-end'l>>" +
                    "<'row'<'col-sm-12'tr>>"
            });


            const pageLength = document.getElementsByName('data-grid_length')[0]
            const searchInput = document.querySelectorAll('input[type="search"]')[0]

            if (pageLength) {
                const options = Array.from(pageLength.options)
                let optionToSelect = options.find(item => item.value == perPage)
                optionToSelect.setAttribute('selected', true)

                if (perPage == -1) {
                    perPage = recordsCount
                }

                $('#data-grid').DataTable().page.len(perPage).draw();

                pageLength.addEventListener('change', function() {
                    document.getElementById('per_page').value = pageLength.value
                    document.getElementById('search').submit()
                })
            }

            // Search using debounce mechanism starts
            if (search_string && searchInput) {
                searchInput.value = search_string

            }

            if (searchInput) {
                searchInput.addEventListener('input', e => {
                    hiddenInput.value = e.target.value
                    updateDebounceText(e.target.value)
                })
            }
        });

        const updateDebounceText = debounce(searchString => {
            hiddenInput.value = searchString
            document.getElementById('search').submit();
        })

        function debounce(callback, delay = 1000) {
            let timeout

            return (...args) => {
                clearTimeout(timeout)
                timeout = setTimeout(() => {
                    callback(...args)
                }, delay)
            }
        }

        function deleteUser(id) {

            Swal.fire({
                title: "Are you sure?",
                text: "User will be deleted permanently",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Yes, delete it!"
            }).then(function(result) {
                if (result.value) {
                    $("#user_id").val(id);
                    document.getElementById('delete-user').submit()
                }
            });
        }
    </script>
@stop
