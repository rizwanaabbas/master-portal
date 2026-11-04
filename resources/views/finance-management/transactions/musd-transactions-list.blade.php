@extends('layouts.cp')
@section('title', 'MUSD Transactions List - Finance Management')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Finance Management</span>
        </h1>
    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false"
        data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                MUSD Transactions List
            </h2>
            <h2>
                Reserves Bank Balance: {{ number_format($reservesBalance, 2) }}
            </h2>
        </div>

        <div class="panel-container show mb-3">
            <div class="panel-content" style="overflow: scroll;">
                @include('common.error-message')
                <table class="table table-bordered table-hover m-0 shadow" id="data-grid">
                    <thead class="thead-themed ">
                        <tr>
                            <th width="30">S.No.</th>
                            <th>Account Title</th>
                            <th>Mode</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Date</th>
                            <th>Creator</th>
                            <th>Approver</th>
                            <th>Status</th>
                            @can('delete records')
                                <th width="30" align="center"></th>
                            @endcan
                        </tr>
                    </thead>
                    <tbody>
                        @if ($transactions)
                            @foreach ($transactions as $transaction)
                                <tr>
                                    {{-- <td align="center"><a
                                        href="{{ url('/finance-management/musd-transactions/view/' . strtolower($transaction->transaction_nature) . '/' . $trn->id) }}"><i
                                            class="fal fa-tv"></i></a></td> --}}
                                    <td align="center">{{ $loop->iteration }}</td>
                                    <td>
                                        {{ $transaction->account_title }} -
                                        <br /><small>{{ $transaction->account_number . ' - (Request ID: ' . $transaction->tr_request_id . ')' }}</small>
                                    </td>
                                    <td>{{ $transaction->transaction_nature }}</td>
                                    <td>{{ $transaction->transaction_type }}</td>
                                    <td>{{ number_format($transaction->amount, 2) }}</td>
                                    <td>{{ \Carbon\Carbon::parse($transaction->transaction_date)->format('d/m/Y') }}
                                    </td>
                                    <td>{{ $transaction->creator }}</td>
                                    <td>{{ $transaction->approver }}</td>
                                    <td>
                                        <span class="badge {{ $statusBadge[$transaction->status] }}">
                                            {{ $transaction->status }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="5">No records found</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
                @if (strpos(Request::route()->uri, 'finance-management/transaction/musd-transactions/search') !== false)
                    {{ $transactions->links('global-partials.pagination-post-req', [
                        'perPage' => $transactions->perPage(),
                        'currentPage' => $transactions->currentPage(),
                        'total' => $transactions->total(),
                        'hiddenInputs' => [
                            'per_page' => $request['per_page'],
                            'search_string' => $request['search_string'],
                        ],
                    ]) }}
                @else
                    {{ $transactions->links('global-partials.pagination', [
                        'perPage' => $transactions->perPage(),
                        'currentPage' => $transactions->currentPage(),
                        'total' => $transactions->total(),
                    ]) }}
                @endif
            </div>
        </div>
    </div>

    @php
        $search_string = isset($request) && isset($request['search_string']) ? $request['search_string'] : null;
    @endphp

    <form name="search" id="search" method="post"
        action="{{ url('/finance-management/transaction/musd-transactions/search') }}" enctype="multipart/form-data">
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
        var recordsCount = "<?php echo count($transactions->items()); ?>"

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
