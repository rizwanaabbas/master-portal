@extends('layouts.cp')
@section('title', 'Card Unblock Requests List - Card Management')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Card Management</span>
        </h1>
        {{-- <a href="{{ url('/card-management/client-members/new') }}" class="btn btn-primary mr-3"><i
                class="fal fa-plus-circle"></i> Add Member </a> --}}
    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false"
        data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                Card Unblock Requests List
            </h2>

        </div>

        <div class="panel-container show mb-3">
            <div class="panel-content" style="overflow: scroll;">
                @include('common.error-message')

                <table class="table table-bordered table-hover m-0 shadow" id="data-grid">
                    <thead class="thead-themed">
                        <tr>
                            <th width="30"></th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Creator</th>
                            <th>Created At</th>
                            {{-- <th width="30" align="center"></th> --}}
                        </tr>
                    </thead>
                    <tbody>
                        @if ($requests)
                            @foreach ($requests as $request)
                                <tr>
                                    <td align="center" scope="row"><a
                                            href="{{ url('/card-management/card/unblock-requests/view/' . $request->id) }}"><i
                                                class="fal fa-tv"></i></a></td>
                                    <td>{{ $request->type }}</td>
                                    <td>
                                        <span
                                            class="badge {{ $statusBadge[$request->status] }}">{{ $request->status }}</span>
                                        <i class="fal fa-question-circle info_tooltip" data-toggle="tooltip"
                                            data-placement="auto" title=""
                                            data-original-title="{{ $request->request_reason }}"></i>
                                    </td>
                                    <td>{{ $request->creator->name }}</td>
                                    <td>{{ $request->created_at }}</td>

                                    {{-- Disabling Delete-Client option on FAIB's request --}}
                                    {{-- @if (\Illuminate\Support\Facades\Auth::user()->hasRole(['super admin', 'operations manager']))
                                        <td><a href="javascript:deleteClient({{ $card->id }})"><i
                                                    class="alert-danger fal fa-times-circle rounded-circle"></i></a></td>
                                    @elseif ($card->status != 'Approved')
                                        <td><a href="javascript:deleteClient({{ $card->id }})"><i
                                                    class="alert-danger fal fa-times-circle rounded-circle"></i></a></td>
                                    @else
                                        <td></td>
                                    @endif --}}
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="6" align="center">No records found.</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Disabling Delete-Client option on FAIB's request --}}
    {{-- <form method="POST" name="delete-client" id="delete-client" action="{{ url('/client-management/clients/delete') }}">
        @csrf
        <input type="hidden" name="client_id" id="client_id" value="">
    </form> --}}
@stop
@section('scripts')
    <script>
        $(document).ready(function() {
            $('#data-grid').dataTable({
                responsive: true
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

        });

        // Disabling Delete-Client option on FAIB's request
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
