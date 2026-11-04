@extends('layouts.cp')
@section('title', 'Unassigned Cards - Card Management')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Client Card Management</span>
        </h1>
        {{-- <a href="{{ url('/card-management/client-members/new') }}" class="btn btn-primary mr-3"><i
                class="fal fa-plus-circle"></i> Add Member </a> --}}
    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false"
        data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                Unassigned Cards
            </h2>

        </div>

        <div class="panel-container show mb-3">
            <div class="panel-content" style="overflow: scroll;">
                @include('common.error-message')

                <table class="table table-bordered table-hover m-0 shadow" id="data-grid">
                    <thead class="thead-themed">
                        <tr>
                            <th>S.No.</th>
                            <th>Kit Number VistaMoney ID</th>
                            <th>Kit Number</th>
                            <th>Created-At {{ $ownCompany ? $ownCompany->short_name : '' }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if ($kitNumbers)
                            @foreach ($kitNumbers as $kitNumber)
                                <tr>
                                    <td align="center" scope="row">{{ $loop->iteration }}</td>
                                    <td>{{ $kitNumber->kit_number_id }}</td>
                                    <td>{{ $kitNumber->kit_number }}</td>
                                    <td>{{ \Carbon\Carbon::parse($kitNumber->created_at)->format('d M, Y') }}</td>
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

@stop
@section('scripts')
    <script>
        $(document).ready(function() {
            $('#data-grid').dataTable({
                responsive: true
            });
        });
    </script>
@stop
