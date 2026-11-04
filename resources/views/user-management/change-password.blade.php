@extends("layouts.cp")
@section('title', 'Change Password - User Management')

@section("content")

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>User Management</span>
        </h1>
        <a href="{{ url("/home") }}" class="btn btn-primary mr-3"><i class="fal fa-arrow-alt-left"></i> Go Back </a>
    </div>

    <div  class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false" data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                Change Password
            </h2>
        </div>

        <form method="post" action="{{ url("/update-password") }}" enctype="multipart/form-data">
            @csrf
            <div class="panel-container show">
                <div class="panel-content">
                    @include("common.error-message")

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="email">Current Password:</label>
                                <input name="current_password" type="password" class="form-control">
                                @error('current_password')<small class="text-danger">{{ $message }}</small>@enderror

                            </div>

                            <div class="form-group">
                                <label for="email">New Password:</label>
                                <input name="new_password" type="password" class="form-control">
                                @error('new_password')<small class="text-danger">{{ $message }}</small>@enderror

                            </div>

                            <div class="form-group">
                                <label for="email">Confirm Password:</label>
                                <input name="new_password_confirmation" type="password" class="form-control">
                                @error('new_password_confirmation')<small class="text-danger">{{ $message }}</small>@enderror

                            </div>
                        </div>

                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <hr>
                                <button type="submit" class="btn btn-primary">Submit</button>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </form>
    </div>

@stop