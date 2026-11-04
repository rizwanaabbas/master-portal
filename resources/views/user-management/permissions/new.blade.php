@extends("layouts.cp")
@section('title', 'New Permission - User Management')

@section("content")

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>User Management</span>
        </h1>
        <a href="{{ url("/user-management/permissions") }}" class="btn btn-primary mr-3"><i class="fal fa-arrow-alt-left"></i> Go Back </a>
    </div>

    <div  class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false" data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                New Permission
            </h2>
        </div>

        <form method="post" action="{{ url("/user-management/permissions/store") }}" enctype="multipart/form-data">
            @csrf
            <div class="panel-container show">
                <div class="panel-content">
                    @include("common.error-message")

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label" for="name">Permission Name</label>
                                <input type="text" autofocus id="name" name="name" value="{{ old('name') }}" class="form-control" required>
                                <small>You can also specify multiple with comma separation (add user, delete user etc)</small>
                                @error('name')<span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>@enderror
                            </div>

                            <div class="custom-control custom-switch">
                                <input type="checkbox" name="status" class="custom-control-input" id="status">
                                <label class="custom-control-label" for="status">Status</label>
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