@extends('layouts.cp')
@section('title', 'User Clients Report - Reports Management')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Reports Management</span>
        </h1>
    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false"
        data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                User Clients Report
            </h2>

        </div>

        <div class="panel-container show mb-3">
            <div class="panel-content" style="overflow: scroll;">
                @include('common.error-message')
                <form method="post" action="{{ url('/reports-management/client/user-client-wise-search') }}">
                    @csrf
                    <div class="row">
                        <div class="col-md-5">
                            <div class="form-group">
                                <label class="form-label" for="user_id">User</label>
                                <select name="user_id" id="user_id" data-select2-id="user_id"
                                    class="form-control select2 w-100" required>
                                    <option value="">choose user</option>
                                    @foreach ($users as $user)
                                        @if ($user->id == old('user_id'))
                                            <option value="{{ $user->id }}" selected>{{ $user->email }}</option>
                                        @else
                                            <option value="{{ $user->id }}">{{ $user->email }}</option>
                                        @endif
                                    @endforeach
                                </select>
                                @error('user_id')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-2 mt-4">
                            <button typ="submit" class="btn btn-primary">Search</button>
                        </div>
                    </div>
                </form>
                <br>
                <table class="table table-bordered table-hover m-0 shadow"
                    @if ($users->count() > 0) id="data-grid" @endif>
                    <thead class="thead-themed">
                        <tr>
                            <th width="30">S.NO</th>
                            <th>Client</th>
                            @if ($currencies->count() > 0)
                                @foreach ($currencies as $currency)
                                    <th>{{ $currency->short }}</th>
                                @endforeach
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="{{ $currencies->count() + 2 }}" align="center">Please choose a user to get related
                                client report.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@stop
@section('scripts')
    <script>
        $(document).ready(function() {
            $('.select2').select2();

            $('#data-grid').dataTable({
                responsive: true,
                lengthChange: false,
                dom: "<'row mb-3'<'col-sm-12 col-md-6 d-flex align-items-center justify-content-start'f><'col-sm-12 col-md-6 d-flex align-items-center justify-content-end'lB>>" +
                    "<'row'<'col-sm-12'tr>>" +
                    "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
                buttons: [
                    /*{
                        extend:    'colvis',
                        text:      'Column Visibility',
                        titleAttr: 'Col visibility',
                        className: 'mr-sm-3'
                    },*/
                    {
                        extend: 'pdfHtml5',
                        text: 'PDF',
                        titleAttr: 'Generate PDF',
                        className: 'btn-outline-danger btn-sm mr-1'
                    },
                    {
                        extend: 'excelHtml5',
                        text: 'Excel',
                        titleAttr: 'Generate Excel',
                        className: 'btn-outline-success btn-sm mr-1'
                    }
                ]
            });

            $('.js-thead-colors a').on('click', function() {
                var theadColor = $(this).attr("data-bg");
                $('#dt-basic-example thead').removeClassPrefix('bg-').addClass(theadColor);
            });

            $('.js-tbody-colors a').on('click', function() {
                var theadColor = $(this).attr("data-bg");
                $('#dt-basic-example').removeClassPrefix('bg-').addClass(theadColor);
            });

        });
    </script>
@stop
