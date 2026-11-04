@extends('layouts.cp')
@section('title', 'User Audit - User Trace')

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
                User Audit
            </h2>

        </div>

        <div class="panel-container show mb-3">
            <div class="panel-content" style="overflow: scroll;">
                @include('common.error-message')

                <table class="table table-bordered table-hover m-0 shadow" id="data-grid">
                    <thead class="thead-themed">
                        <tr>
                            <th>
                                View
                            </th>
                            <th>
                                User Name
                            </th>
                            <th>
                                User Role(s)
                            </th>
                            <th>
                                Action
                            </th>
                            <th>
                                Auditable ID
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
                        @if ($audits)
                            @foreach ($audits as $audit)
                                <tr>
                                    <td align="center" scope="row">
                                        <a href="{{ url('user-trace/user-audits/view/' . $audit->id) }}">
                                            <i class="fal fa-tv"></i>
                                    </td>
                                    <td>{{ $audit->name ? $audit->name : 'Guest' }}</td>
                                    <td>
                                        @if ($audit->role_name)
                                            <span class="badge badge-primary">{{ $audit->role_name }}</span>
                                        @else
                                            --
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $colors = [
                                                'created' => 'badge-primary',
                                                'updated' => 'badge-secondary',
                                                'deleted' => 'badge-danger',
                                            ];
                                            $modelMapping = [
                                                'App\\Models\\Account' => 'Account',
                                                'App\\Models\\AccountTransaction' => 'Transaction',
                                                'App\\Models\\AccountTransactionCheque' => 'Cheque Transaction',
                                                'App\\Models\\Bank' => 'Bank',
                                                'App\\Models\\Client' => 'Client',
                                                'App\\Models\\ClientCompany' => 'Company Client',
                                                'App\\Models\\ClientIndividual' => 'Individual Client',
                                            ];
                                            $modelName = $modelMapping[$audit->auditable_type] ?? null;
                                        @endphp
                                        <span class="badge {{ $colors[$audit->event] ?? 'badge-secondary' }}">
                                            {{ $modelName ? $modelName . ' ' : '' }}{{ $audit->event }}
                                        </span>
                                    </td>
                                    <td>{{ $audit->auditable_id }}</td>
                                    <td>{{ parse_url($audit->url, PHP_URL_PATH) }}</td>
                                    <td>{{ \Carbon\Carbon::parse($audit->created_at)->format('d M, Y H:i:s') }}</td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="5">No records found</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
                @if (strpos(Request::route()->uri, 'user-trace/user-audits/search') !== false)
                    {{ $audits->links('global-partials.pagination-post-req', [
                        'perPage' => $audits->perPage(),
                        'currentPage' => $audits->currentPage(),
                        'total' => $audits->total(),
                        'hiddenInputs' => [
                            'per_page' => $request['per_page'],
                            'search_string' => $request['search_string'],
                        ],
                    ]) }}
                @else
                    {{ $audits->links('global-partials.pagination', [
                        'perPage' => $audits->perPage(),
                        'currentPage' => $audits->currentPage(),
                        'total' => $audits->total(),
                    ]) }}
                @endif
            </div>
        </div>
    </div>

    @php
        $search_string = isset($request) && isset($request['search_string']) ? $request['search_string'] : null;
    @endphp

    <form name="search" id="search" method="post" action="{{ url('/user-trace/user-audits/search') }}"
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
        var recordsCount = "<?php echo count($audits->items()); ?>"

        perPage = perPage ? perPage : 10
        document.getElementById('per_page').value = perPage

        $(document).ready(function() {
            $('#data-grid').DataTable({
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
    </script>
@stop
