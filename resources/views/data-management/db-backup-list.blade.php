@extends('layouts.cp')
@section('title', 'Database Backups - Settings')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Settings</span>
        </h1>
    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false"
        data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                Database Backups
            </h2>

        </div>

        <div class="panel-container show mb-3">
            <div class="panel-content" style="overflow: scroll;">
                @include('common.error-message')

                <table class="table table-bordered table-hover m-0 shadow">
                    <thead class="thead-themed">
                        <tr>
                            <th width="30">S.NO</th>
                            <th>Name</th>
                            @switch(env("BUCKET_PROVIDER"))
                                @case('S3')
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($files as $file)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $file }}</td>
                                    </tr>
                                @endforeach
                            @break

                            @case('OSS')
                                <th>Size</th>
                                </tr>
                                </thead>
                            <tbody>
                                @foreach ($files as $file)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $file->getKey() }}</td>
                                        <td>{{ round($file->getSize() / 1024 / 1024, 3) . 'MB' }} </td>
                                    </tr>
                                @endforeach
                            @break

                            @default
                                </tr>
                                </thead>
                            <tbody>
                                <tr>
                                    <td></td>
                                    <td>No Records found!</td>
                                </tr>
                        @endswitch

                    </tbody>
                </table>

                {{--                <div class="row"> --}}
                {{--                    <div class="col-md-12"> --}}
                {{--                        <div class="form-group"> --}}
                {{--                            <hr> --}}
                {{--                            <button type="button" onclick="restoreBackup()" class="btn btn-primary">Restore</button> --}}
                {{--                        </div> --}}
                {{--                    </div> --}}
                {{--                </div> --}}
            </div>

        </div>
    </div>

    <form method="POST" name="delete-backup" id="delete-backup" action="{{ url('/settings/backups/delete') }}">
        @csrf
        <input type="hidden" name="id" id="id" value="">
    </form>

    <form method="POST" name="restore-backup" id="restore-backup" action="{{ url('/settings/backups/restore') }}">
        @csrf
        <input type="hidden" name="file_name" id="file_name" value="">
    </form>

    <script>
        function deleteBackup(id) {

            Swal.fire({
                title: "Are you sure?",
                text: "User will be deleted permanently",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Yes, delete it!"
            }).then(function(result) {
                if (result.value) {
                    $("#id").val(id);
                    document.getElementById('delete-backup').submit()
                }
            });
        }

        // function restoreBackup(id){
        //
        //     Swal.fire(
        //         {
        //             title: "Are you sure?",
        //             text: "Database will be restored",
        //             icon: "warning",
        //             showCancelButton: true,
        //             confirmButtonText: "Yes, restore it!"
        //         }).then(function(result)
        //     {
        //         if (result.value)
        //         {
        //             $("#file_name").val($("input[name='bk']:checked").val());
        //             document.getElementById('restore-backup').submit()
        //         }
        //     });
        // }
    </script>

@stop
