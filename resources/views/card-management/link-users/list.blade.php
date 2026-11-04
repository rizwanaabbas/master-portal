@extends('layouts.cp')
@section('title', 'User-Card Links List - Card Management')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Card Management</span>
        </h1>
        <a href="{{ url('/card-management/link-users/new') }}" class="btn btn-primary mr-3"><i class="fal fa-plus-circle"></i>
            Add Link </a>
    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false"
        data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                User-Card Links List
            </h2>

        </div>

        <div class="panel-container show mb-3">
            <div class="panel-content" style="overflow: scroll;">
                @include('common.error-message')

                <table class="table table-bordered table-hover m-0 shadow" id="data-grid">
                    <thead class="thead-themed">
                        <tr>
                            <th width="30"></th>
                            <th>Card Number</th>
                            <th>Owner</th>
                            <th>Linked Since</th>
                            <th>Balance Hidden</th>
                            <th>Provider</th>
                            {{-- <th>Status</th> --}}
                            <th>Creator</th>
                            {{-- <th width="30" align="center"></th> --}}
                        </tr>
                    </thead>
                    <tbody>
                        @if ($links)
                            @foreach ($links as $link)
                                @php
                                    $provider_type = '';
                                    if (!empty($link->cardDetails)) {
                                        $provider_type = $link->cardDetails->provider_type;
                                    }
                                @endphp

                                <tr>
                                    <td align="center" scope="row">

                                        @if ($provider_type != 'match_move')
                                            <a href="{{ url('/card-management/link-users/edit/' . $link->owner->id) }}"><i
                                                    class="fal fa-edit"></i></a>
                                        @endif
                                    </td>
                                    <td width="30%">{{ $link->linkedCard->cardNumber }}</td>
                                    <td width="30%">{{ $link->owner ? $link->owner->name : '--' }}</td>
                                    <td>{{ \Carbon\Carbon::parse($link->created_at)->format('d M, Y') }}</td>

                                    <td>
                                        <span
                                            class="badge {{ $statusBadge[$link->hide_balance] }}">{{ $link->hide_balance ? 'Yes' : 'No' }}</span>
                                    </td>
                                    <td>{{ ucwords(str_replace('_', ' ', $provider_type)) }}</td>
                                    {{-- <td><span
                                            class="badge {{ $statusBadge[$link->status] }}">{{ $link->status ? 'Active' : 'Inactive' }}</span>
                                    </td> --}}
                                    <td>{{ $link->creator ? $link->creator->name : '--' }}</td>

                                    {{-- Disabling Delete-Client option on FAIB's request --}}
                                    {{-- @if (\Illuminate\Support\Facades\Auth::user()->hasRole(['super admin', 'operations manager']))
                                        <td><a href="javascript:deleteClient({{ $link->id }})"><i
                                                    class="alert-danger fal fa-times-circle rounded-circle"></i></a></td>
                                    @elseif ($link->status != 'Approved')
                                        <td><a href="javascript:deleteClient({{ $link->id }})"><i
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
