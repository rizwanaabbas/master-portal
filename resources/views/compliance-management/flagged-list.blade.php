@extends('layouts.cp')
@section('title', 'Flagged Transactions - Compliance Management')

@section('content')
    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false">
        <div class="panel-hdr">
            <h2>Flagged Transactions</h2>
        </div>
        <div class="panel-container show mb-3">
            <div class="panel-content" style="overflow: scroll;">
                @include('common.error-message')

                <form name="search" id="search" method="GET" action="{{ route('flagged.transactions') }}">
                    <div class="row mb-3">
                        <!-- Left: Search input -->
                        <div class="col-sm-12 col-md-6 d-flex align-items-center justify-content-start">
                            <div id="data-grid_filter" class="dataTables_filter w-100">
                                <label class="w-100 d-flex align-items-center mb-0">
                                    <div class="input-group-text d-inline-flex width-3 align-items-center justify-content-center border-bottom-right-radius-0 border-top-right-radius-0 border-rightt-0"
                                        style="height:36px">
                                        <i class="fal fa-search"></i>
                                    </div>
                                    <input type="text" name="search_string" id="search_string"
                                        value="{{ request('search_string') ?? '' }}"
                                        class="form-control border-top-left-radius-0 border-bottom-left-radius-0 ml-0 width-md shadow-inset-1"
                                        placeholder="Search..." aria-controls="data-grid">
                                </label>
                            </div>
                        </div>

                        <!-- Right: Per page select -->
                        <div class="col-sm-12 col-md-6 d-flex justify-content-end">
                            <div class="dataTables_length" id="data-grid_length">
                                <label>
                                    Show entries
                                    <select name="per_page" id="per_page" aria-controls="data-grid"
                                        class="form-control custom-select" onchange="this.form.submit()">
                                        <option value="10" {{ request('per_page') == 10 ? 'selected' : '' }}>10</option>
                                        <option value="15" {{ request('per_page') == 15 ? 'selected' : '' }}>15</option>
                                        <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25</option>
                                        <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                                        <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100
                                        </option>
                                        <option value="-1" {{ request('per_page') == -1 ? 'selected' : '' }}>All
                                        </option>
                                    </select>

                                </label>
                            </div>
                        </div>
                    </div>
                </form>



                <table class="table table-bordered table-hover m-0 shadow" id="data-grid">
                    <thead class="thead-themed">
                        <tr>
                            <th width="30">View</th>
                            <th>
                                <a
                                    href="?sort_by=account_title&sort_dir={{ request('sort_dir') == 'asc' ? 'desc' : 'asc' }}&{{ http_build_query(request()->except('sort_by', 'sort_dir')) }}">Account
                                    Title</a>
                            </th>
                            <th>
                                <a
                                    href="?sort_by=transaction_type&sort_dir={{ request('sort_dir') == 'asc' ? 'desc' : 'asc' }}&{{ http_build_query(request()->except('sort_by', 'sort_dir')) }}">Type</a>
                            </th>
                            <th>
                                <a
                                    href="?sort_by=transaction_nature&sort_dir={{ request('sort_dir') == 'asc' ? 'desc' : 'asc' }}&{{ http_build_query(request()->except('sort_by', 'sort_dir')) }}">Nature</a>
                            </th>
                            <th>Remarks</th>
                            <th>
                                <a
                                    href="?sort_by=amount&sort_dir={{ request('sort_dir') == 'asc' ? 'desc' : 'asc' }}&{{ http_build_query(request()->except('sort_by', 'sort_dir')) }}">Amount</a>
                            </th>
                            <th>
                                <a
                                    href="?sort_by=updated_at&sort_dir={{ request('sort_dir') == 'asc' ? 'desc' : 'asc' }}&{{ http_build_query(request()->except('sort_by', 'sort_dir')) }}">Request
                                    Date</a>
                            </th>
                            <th>Compliance Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($transactions as $trn)
                            <tr>
                                <td align="center">
                                    <a
                                        href="{{ url('/finance-management/pending-transactions/' . ($trn->status == 'Rejected' ? 'edit' : 'view') . '/' . strtolower($trn->transaction_nature) . '/' . $trn->id) }}">
                                        <i class="fal fa-{{ $trn->status == 'Rejected' ? 'edit' : 'tv' }}"></i>
                                    </a>
                                </td>
                                <td>{{ $trn->account->title }}</td>
                                <td>{{ $trn->transaction_type }}</td>
                                <td>{{ $trn->transaction_nature }}</td>
                                <td width="40%">{{ $trn->remarks }}</td>
                                <td>{{ number_format($trn->amount, 2) }}</td>
                                <td>{{ \Carbon\Carbon::parse($trn->updated_at)->format('d M, Y') }}</td>
                                <td><span class="badge badge-primary">{{ $trn->compliance_status }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8">No records found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                <div style="margin-top:20px;">
                    {{ $transactions->links('global-partials.pagination') }}
                </div>
            </div>
        </div>
    </div>

    <form name="ajax_search" id="ajax_search" method="GET" action="{{ route('flagged.transactions') }}">
        <input type="hidden" name="per_page" id="hidden_per_page" value="{{ $perPage }}">
        <input type="hidden" name="search_string" id="hidden_search_string" value="{{ $search_string }}">
    </form>
@stop

@section('scripts')
    <script>
        $(document).ready(function() {
            // Handle search input debounce
            let debounceTimeout;
            $('#search_string').on('input', function() {
                clearTimeout(debounceTimeout);
                let val = $(this).val();
                debounceTimeout = setTimeout(function() {
                    $('#hidden_search_string').val(val);
                    $('#ajax_search').submit();
                }, 500);
            });

            // Handle per_page change
            $('#per_page').on('change', function() {
                $('#hidden_per_page').val($(this).val());
                $('#ajax_search').submit();
            });
        });
    </script>
@stop
