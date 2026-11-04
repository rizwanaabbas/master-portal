@extends('layouts.cp')
@section('title', 'Transactions Cases List - Compliance Management')

@section('content')
    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Compliance Management</span>
        </h1>
    </div>
    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false"
        data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                Transactions Cases List
            </h2>
        </div>
        <div class="panel-container show mb-3">
            <div class="panel-content" style="overflow: scroll;">
                @include('common.error-message')

                <table class="table table-bordered table-hover m-0 shadow" id="data-grid">
                    <thead class="thead-themed">
                        <tr>
                            <th width="30"></th>
                            <th>Name</th>
                            <th>Entity Type</th>
                            <th>Created At</th>
                            <th>Suspicious</th>
                            <th>Status</th>
                            <th>Resolved By / Blocked By</th>
                            <th>Resolved At</th>
                            <th>Creator</th>
                            <th>Case ID</th>
                        </tr>
                    </thead>
                    <tbody>

                        @if ($cases)
                            @foreach ($cases as $case)
                                <tr>
                                    <td align="center" scope="row">
                                        <a
                                            href="{{ url('/dashboards/compliance-management/transactions-detailed-case/' . $case->id) }}"><i
                                                class="fal fa-tv"></i></a>
                                    </td>
                                    <td>
                                        {{ $case->name }}
                                    </td>
                                    <td>
                                        @if ($case->entity_type == 'individual')
                                            individual
                                        @else
                                            company
                                        @endif
                                    </td>
                                    <td>{{ \Carbon\Carbon::parse($case->created_at)->format('d/m/Y') }}</td>
                                    <td>
                                        @php  $isSuspicious = [0 => 'No', 1 => 'Yes']; @endphp
                                        <span
                                            class="badge {{ $statusBadge[$case->is_suspicious . '_suspicious'] }}">{{ $isSuspicious[$case->is_suspicious] }}</span>
                                    </td>
                                    <td>
                                        @if ($case->is_suspicious && $case->resolved)
                                            <span class="badge {{ $statusBadge['Approved'] }}">Resolved</span>
                                        @elseif($case->is_suspicious && $case->blocked)
                                            <span class="badge {{ $statusBadge['Rejected'] }}">Blocked</span>
                                        @elseif ($case->is_suspicious)
                                            <span class="badge {{ $statusBadge['Pending'] }}">Pending</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if (isset($case->resolved_by))
                                            {{ $case->resolved_by }}
                                        @endif
                                        @if (isset($case->blocked_by))
                                            {{ $case->blocked_by }}
                                        @endif
                                    </td>
                                    <td>
                                        @if (isset($case->resolved_at))
                                            {{ \Carbon\Carbon::parse($case->resolved_at)->format('d/m/Y') }}
                                        @endif
                                    </td>
                                    <td>{{ $case->creator }}</td>
                                    <td>{{ $case->case_id }}</td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="5">No records found</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
                @if (strpos(Request::route()->uri, 'dashboards/compliance-management/transactions-cases/search') !== false)
                    {{ $cases->links('global-partials.pagination-post-req', [
                        'perPage' => $cases->perPage(),
                        'currentPage' => $cases->currentPage(),
                        'total' => $cases->total(),
                        'hiddenInputs' => [
                            'per_page' => $request['per_page'],
                            'search_string' => $request['search_string'],
                        ],
                    ]) }}
                @else
                    {{ $cases->links('global-partials.pagination', [
                        'perPage' => $cases->perPage(),
                        'currentPage' => $cases->currentPage(),
                        'total' => $cases->total(),
                    ]) }}
                @endif
            </div>
        </div>
    </div>

    @php
        $search_string = isset($request) && isset($request['search_string']) ? $request['search_string'] : null;
    @endphp

    <form name="search" id="search" method="post"
        action="{{ url('dashboards/compliance-management/transactions-cases/search') }}" enctype="multipart/form-data">
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
        var recordsCount = "<?php echo count($cases->items()); ?>"

        perPage = perPage ? perPage : 10
        document.getElementById('per_page').value = perPage

        $(document).ready(function() {
            $('#data-grid').DataTable({
                responsive: true,
                // serverSide: true,
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
