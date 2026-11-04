@extends('layouts.cp')
@section('title', 'New Permission - User Management')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Account Number Correction</span>
        </h1>
    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false"
        data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                Change Account Number
            </h2>
        </div>

        <form method="post" action="{{ url('/accounts-correction/store') }}" enctype="multipart/form-data">
            @csrf
            <div class="panel-container show">
                <div class="panel-content">
                    @include('common.error-message')

                    <div class="row">
                        <table class="table table-bordered table-hover m-0 shadow">
                            <thead class="thead-themed">
                                <tr>
                                    @can('update permission')
                                        <th width="30"></th>
                                    @endcan
                                    <th>S.NO</th>
                                    <th>ID</th>
                                    <th>Title</th>
                                    <th>Account Number</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($duplicates as $dup)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td><input type="checkbox" name="ids[]" value="{{ $dup->id }}">
                                            {{ $dup->id }}</td>
                                        <td>{{ $dup->title }}</td>
                                        <td><input type="text" name="number_{{ $dup->id }}"
                                                value="{{ $dup->account_number }}"></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <br>
                    <h3>Changes Made</h3>
                    <div class="row">
                        <table class="table table-bordered table-hover m-0 shadow">
                            <thead class="thead-themed">
                                <tr>
                                    @can('update permission')
                                        <th width="30"></th>
                                    @endcan
                                    <th>S.NO</th>
                                    <th>Title</th>
                                    <th>Old Account Number</th>
                                    <th>New Account Number</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($changes as $change)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $change->account->title }}</td>
                                        <td>{{ $change->old_number }}</td>
                                        <td>{{ $change->new_number }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    {{--                    <div class="row"> --}}
                    {{--                        <div class="col-md-12"> --}}
                    {{--                            <div class="form-group"> --}}
                    {{--                                <hr> --}}
                    {{--                                <button type="submit" class="btn btn-primary">Submit</button> --}}
                    {{--                            </div> --}}
                    {{--                        </div> --}}
                    {{--                    </div> --}}
                </div>

            </div>
        </form>
    </div>

@stop
