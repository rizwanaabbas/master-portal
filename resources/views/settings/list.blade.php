@extends('layouts.cp')
@section('title', 'Client File Config List - System Settings')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>System Settings</span>
        </h1>
        <a href="{{ url('/settings/client-file-config/new') }}" class="btn btn-primary mr-3"><i class="fal fa-plus-circle"></i>
            Add New </a>

    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false"
        data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                Client File Config List
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
                            <th>Identifier</th>
                            <th>Type</th>
                            <th>Mandatory</th>
                            <th>Multiple</th>
                            <th>Signed PDF</th>
                            <th>Active</th>
                            @if (\Illuminate\Support\Facades\Auth::user()->hasRole(['super admin']))
                                <th width="30" align="center"></th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @if ($files)
                            @foreach ($files as $file)
                                @php
                                    switch ($file->is_active) {
                                        case '1':
                                            $class = 'badge-success';
                                            break;
                                        case '0':
                                            $class = 'badge-danger';
                                            break;
                                        default:
                                            $class = 'badge-secondary';
                                    }
                                @endphp
                                <tr>
                                    <td align="center" scope="row"><a
                                            href="{{ url('/settings/client-file-config/edit/' . $file->id) }}"><i
                                                class="fal fa-edit"></i></a></td>
                                    <td width="30%">{{ $file->title }}</td>
                                    <td>{{ $file->identifier }}</td>
                                    <td>{{ $file->type }}</td>
                                    <td>{{ $file->isMandatory ? 'Yes' : 'No' }}</td>
                                    <td>{{ $file->isMultiple ? 'Yes' : 'No' }} @if ($file->maxCount > 0)
                                            {{ ' (' . $file->maxCount . ' files)' }}
                                        @endif
                                    </td>
                                    <td>{{ $file->signed_pdf ? 'Yes' : 'No' }}</td>
                                    <td>{{ $file->is_active ? 'Yes' : 'No' }}</td>
                                    @if (\Illuminate\Support\Facades\Auth::user()->hasRole(['super admin']))
                                        <td><a href="javascript:deleteFileConfig({{ $file->id }})"><i
                                                    class="alert-danger fal fa-times-circle rounded-circle"></i></a></td>
                                    @endif
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

    <form method="POST" name="delete-file-config" id="delete-file-config"
        action="{{ url('/settings/client-file-config/delete') }}">
        @csrf
        <input type="hidden" name="file_id" id="file_id" value="">
    </form>
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

        function deleteFileConfig(id) {

            Swal.fire({
                title: "Are you sure?",
                text: "File Config will be deleted permanently",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Yes, delete it!"
            }).then(function(result) {
                if (result.value) {
                    $("#file_id").val(id);
                    document.getElementById('delete-file-config').submit()
                }
            });
        }
    </script>
@stop
