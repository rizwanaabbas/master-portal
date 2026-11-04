@extends('layouts.cp')
@section('title', 'Client List - Client Management')

@section('content')

    @php $hideSubAccountColumn = true @endphp

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Client Management</span>
        </h1>
        <div class="d-flex">
            <a href="{{ url('/client-management/clients/new/company/0') }}" class="btn btn-primary btn-shape ml-2"><i
                    class="fal fa-plus-circle"></i> Add Company </a>
            <a href="{{ url('/client-management/clients/new/individual') }}" class="btn btn-primary btn-shape ml-2"><i
                    class="fal fa-plus-circle"></i> Add Individual </a>
            <a href="{{ url('/client-management/clients/new/company/1') }}" class="btn btn-primary btn-shape ml-2"><i
                    class="fal fa-plus-circle"></i> Add Bank </a>
        </div>
    </div>
    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false"
        data-panel-color="false" data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                Client List
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
                            <th>Type</th>
                            <th>Member Since</th>
                            <th>Creator</th>
                            <th>Introducer</th>
                            @if (in_array('SubAccounts', $enabledModules))
                                @php $hideSubAccountColumn = false @endphp
                                <th>Is Sub Account</th>
                            @endif
                            <th>Approver</th>
                            <th>Approved At</th>
                            <th>Status</th>
                            {{-- <th width="30" align="center"></th> --}}
                        </tr>
                    </thead>
                    <tbody>
                        @if ($clients)
                            @foreach ($clients as $client)
                                <tr>
                                    <td align="center" scope="row">
                                        <a
                                            href="{{ url('client-management/clients/' . strtolower($client->type) . '/edit/' . $client->id) }}">
                                            <i class="fal fa-edit"></i>
                                        </a>
                                    </td>
                                    <td>{{ $client->name }}</td>
                                    <td>
                                        {{ $client->is_bank ? 'bank' : $client->type }}
                                    </td>
                                    <td>
                                        {{ \Carbon\Carbon::parse($client->created_at)->format('d M, Y') }}
                                    </td>
                                    <td>
                                        {{ $client->creator ? $client->creator : '--' }}
                                    </td>
                                    <td>
                                        {{ $client->introducer ? $client->introducer : '--' }}
                                    </td>
                                    @if (!$hideSubAccountColumn)
                                        <td>
                                            <span
                                                class="badge {{ $client->parent ? 'badge-success' : 'badge-danger' }}">{{ $client->parent ? 'Yes' : 'No' }}</span>
                                            {{ $client->parent ? ' - ' . $client->parent['name'] : '' }}
                                        </td>
                                    @endif
                                    <td>
                                        {{ $client->approver ? $client->approver : '' }}
                                    </td>
                                    <td>
                                        {{ $client->approved_at ? \Carbon\Carbon::parse($client->approved_at)->format('d M, Y') : '' }}
                                    </td>
                                    <td>
                                        <span
                                            class="badge {{ $client->status == 'Pending' ? 'badge-primary' : ($client->status == 'Approved' ? 'badge-success' : 'badge-danger') }}">
                                            {{ $client->status }}
                                        </span>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="5">No records found</td>
                            </tr>
                        @endif
                    </tbody>
                </table>

                @if (strpos(Request::route()->uri, 'client-management/clients/search') !== false)
                    {{ $clients->links('global-partials.pagination-post-req', [
                        'perPage' => $clients->perPage(),
                        'currentPage' => $clients->currentPage(),
                        'total' => $clients->total(),
                        'hiddenInputs' => [
                            'per_page' => $request['per_page'],
                            'search_string' => $request['search_string'],
                        ],
                    ]) }}
                @else
                    {{ $clients->links('global-partials.pagination', [
                        'perPage' => $clients->perPage(),
                        'currentPage' => $clients->currentPage(),
                        'total' => $clients->total(),
                    ]) }}
                @endif
                <div class="row d-flex justify-content-end">
                    <div class="col-md-2 d-flex justify-content-end">
                        <div class="form-group">
                            <hr>
                            <a class="btn btn-success text-white"
                                href="{{ url('/client-management/client-profile-wise-generate-excel/') }}">
                                Export to Excel
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @php
        $search_string = isset($request) && isset($request['search_string']) ? $request['search_string'] : null;
    @endphp

    <form name="search" id="search" method="post" action="{{ url('/client-management/clients/search') }}"
        enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="per_page" id="per_page"
            value="{{ isset($request->per_page) ? $request->per_page : '' }}">
        <input type="hidden" name="search_string" id="search_string"
            value="{{ isset($request->search_string) ? $request->search_string : '' }}" />
    </form>

    {{-- Disabling Delete-Client option on CIB's request --}}
    {{-- <form method="POST" name="delete-client" id="delete-client" action="{{ url('/client-management/clients/delete') }}">
        @csrf
        <input type="hidden" name="client_id" id="client_id" value="">
    </form> --}}
@stop
@section('scripts')
    <script>
        var hiddenInput = document.getElementById('search_string')
        var search_string = "<?php echo $search_string; ?>"
        var perPage = "<?php echo $request['per_page']; ?>"
        var recordsCount = "<?php echo count($clients->items()); ?>"

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

        $('.js-thead-colors a').on('click', function() {
            var theadColor = $(this).attr("data-bg");
            console.log(theadColor);
            $('#dt-basic-example thead').removeClassPrefix('bg-').addClass(theadColor);
        });

        $('.js-tbody-colors a').on('click', function() {
            var theadColor = $(this).attr("data-bg");
            console.log(theadColor);
            $('#dt-basic-example').removeClassPrefix('bg-').addClass(theadColor);
        });

        const updateDebounceText = debounce(searchString => {
            hiddenInput.value = searchString
            document.getElementById('search').submit();
        });

        function debounce(callback, delay = 1000) {
            let timeout

            return (...args) => {
                clearTimeout(timeout)
                timeout = setTimeout(() => {
                    callback(...args)
                }, delay)
            }
        }

        // Disabling Delete-Client option on CIB's request
        // function deleteClient(id){

        //     Swal.fire(
        //         {
        //             title: "Are you sure?",
        //             text: "Client will be deleted permanently",
        //             icon: "warning",
        //             showCancelButton: true,
        //             confirmButtonText: "Yes, delete it!"
        //         }).then(function(result)
        //     {
        //         if (result.value)
        //         {
        //             $("#client_id").val(id);
        //             document.getElementById('delete-client').submit()
        //         }
        //     });
        // }
    </script>
@stop
