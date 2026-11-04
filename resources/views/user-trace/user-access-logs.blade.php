@extends('layouts.cp')
@section('title', 'User Access Logs - User Trace')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>User Trace</span>
        </h1>
    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false"
        data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                User Access Logs
            </h2>

        </div>

        <div class="panel-container show mb-3">
            <div class="panel-content" style="overflow: scroll;">
                @include('common.error-message')

                <table class="table table-bordered table-hover m-0 shadow" id="data-grid">
                    <thead class="thead-themed">
                        <tr>
                            <th>
                                Name
                            </th>
                            <th>
                                Requested URL
                            </th>
                            <th>
                                Timestamp
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @if (count($accessLogs->items()) > 0)
                            @foreach ($accessLogs as $log)
                                <tr>
                                    <td>
                                        {{ $log->creator ? $log->creator : '' }}
                                    </td>
                                    <td>
                                        @if (strpos($log->request_url, '?') !== false)
                                            {{ substr($log->request_url, 0, strpos($log->request_url, '?')) }}
                                        @else
                                            {{ $log->request_url }}
                                        @endif
                                    </td>
                                    <td>
                                        {{ \Carbon\Carbon::parse($log->request_datetime)->format('d M, Y H:i:s') }}
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td>No logs found.</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
                @if (strpos(Request::route()->uri, 'user-trace/user-access-logs/search') !== false)
                    {{ $accessLogs->links('global-partials.pagination-post-req', [
                        'perPage' => $accessLogs->perPage(),
                        'currentPage' => $accessLogs->currentPage(),
                        'total' => $accessLogs->total(),
                        'hiddenInputs' => [
                            'per_page' => $request['per_page'],
                            'search_string' => $request['search_string'],
                        ],
                    ]) }}
                @else
                    {{ $accessLogs->links('global-partials.pagination', [
                        'perPage' => $accessLogs->perPage(),
                        'currentPage' => $accessLogs->currentPage(),
                        'total' => $accessLogs->total(),
                    ]) }}
                @endif
            </div>
        </div>
    </div>

    @php
        $search_string = isset($request) && isset($request['search_string']) ? $request['search_string'] : null;
    @endphp

    <form name="search" id="search" method="post" action="{{ url('/user-trace/user-access-logs/search') }}"
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
        var recordsCount = "<?php echo count($accessLogs->items()); ?>"

        perPage = perPage ? perPage : 10
        document.getElementById('per_page').value = perPage

        $(document).ready(function() {
            $('#data-grid').dataTable({
                responsive: true,
                dom: "<'row mb-3'<'col-sm-12 col-md-6 d-flex align-items-center justify-content-start'f><'col-sm-12 col-md-6 d-flex justify-content-end'l>>" +
                    "<'row'<'col-sm-12'tr>>",
                'order': []
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
            // Search using debounce mechanism ends
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
    </script>
@stop
