@extends('layouts.cp')
@section('title', 'Link User to Client - User Management')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>User Management</span>
        </h1>
    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false"
        data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                Link User to Client
            </h2>

        </div>

        <div class="panel-container show mb-3">
            <div class="panel-content" style="overflow: scroll;">
                @include('common.error-message')
                <form method="post" action="{{ url('/user-management/link-user/store') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="row p-2">
                        <div class="col-md-5">
                            <div class="form-group">
                                <label class="form-label" for="client_id">Client</label>
                                <select name="client_id" id="client_id" data-select2-id="client_id"
                                    class="form-control select2 w-100" required>
                                    <option value="">choose client</option>
                                    @foreach ($clients as $client)
                                        @if ($client->id == old('client_id'))
                                            <option value="{{ $client->id }}" selected>{{ $client->name }}</option>
                                        @else
                                            <option value="{{ $client->id }}">{{ $client->name }}</option>
                                        @endif
                                    @endforeach
                                </select>
                                @error('client_id')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="form-group">
                                <label class="form-label" for="user_id">User</label>
                                <select name="user_id" id="user_id" data-select2-id="user_id"
                                    class="form-control select2 w-100" required>
                                    <option value="">choose user</option>
                                    @foreach ($users as $user)
                                        @if ($user->id == old('user_id'))
                                            <option value="{{ $user->id }}" selected>{{ $user->email }}</option>
                                        @else
                                            <option value="{{ $user->id }}">{{ $user->email }}</option>
                                        @endif
                                    @endforeach
                                </select>
                                @error('client_id')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-2 mt-4">
                            <button type="submit" class="btn btn-primary">Link</button>
                        </div>
                    </div>
                </form>
                <hr>
                <table class="table table-bordered table-hover m-0 shadow" id="data-grid">
                    <thead class="thead-themed">
                        <tr>
                            <th width="30">S.NO</th>
                            <th>Client</th>
                            <th>Email</th>
                            <th>Roles</th>
                            @can('delete records')
                                <th width="30" align="center"></th>
                            @endcan
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($user_clients as $cu)
                            <tr>
                                <td align="center" scope="row">{{ $loop->iteration }}</td>
                                <td>{{ $cu->client->name ?? '--' }}</td>
                                <td>{{ $cu->user->email ?? '--' }}</td>
                                <td>
                                    @if ($cu->user)
                                        @foreach ($cu->user->roles->pluck('name')->toArray() as $roleName)
                                            <span class="badge badge-warning">{{ $roleName }}</span>
                                        @endforeach
                                    @else
                                        --
                                    @endif
                                </td>
                                @can('delete records')
                                    <td><a href="javascript:deleteLink({{ $cu->id }})"><i
                                                class="alert-danger fal fa-times-circle rounded-circle"></i></a></td>
                                @endcan
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                @if (strpos(Request::route()->uri, 'user-management/link-user/search') !== false)
                    {{ $user_clients->links('global-partials.pagination-post-req', [
                        'perPage' => $user_clients->perPage(),
                        'currentPage' => $user_clients->currentPage(),
                        'total' => $user_clients->total(),
                        'hiddenInputs' => [
                            'per_page' => $request['per_page'],
                            'search_string' => $request['search_string'],
                        ],
                    ]) }}
                @else
                    {{ $user_clients->links('global-partials.pagination', [
                        'perPage' => $user_clients->perPage(),
                        'currentPage' => $user_clients->currentPage(),
                        'total' => $user_clients->total(),
                    ]) }}
                @endif
            </div>
        </div>
    </div>

    <form method="POST" name="delete-link" id="delete-link" action="{{ url('/user-management/link-user/delete') }}">
        @csrf
        <input type="hidden" name="cu_id" id="cu_id" value="">
    </form>

    @php
        $search_string = isset($request) && isset($request['search_string']) ? $request['search_string'] : null;
    @endphp

    <form name="search" id="search" method="post" action="{{ url('/user-management/link-user/search') }}"
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
        var recordsCount = "<?php echo count($user_clients->items()); ?>"

        perPage = perPage ? perPage : 10
        document.getElementById('per_page').value = perPage

        $(document).ready(function() {
            $('.select2').select2();

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

        function deleteLink(id) {

            Swal.fire({
                title: "Are you sure?",
                text: "User link will be deleted permanently",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Yes, delete it!"
            }).then(function(result) {
                if (result.value) {
                    $("#cu_id").val(id);
                    document.getElementById('delete-link').submit()
                }
            });
        }
    </script>
@stop
