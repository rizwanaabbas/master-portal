@extends("layouts.cp")
@section('title', 'Update Permission - User Management')

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
                Update Permission
            </h2>
        </div>

        <form method="post" action="{{ url("/user-management/permissions/update") }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="id" value="{{ $permission->id }}">
            <div class="panel-container show">
                <div class="panel-content">
                    @include("common.error-message")

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label" for="name">Permission Name</label>
                                <input type="text" autofocus id="name" name="name" value="{{ $permission->name }}" class="form-control" required>
                                @error('name')<span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>@enderror
                            </div>

                            <div class="custom-control custom-switch">
                                <input type="checkbox" name="status" @if($permission->status) checked @endif class="custom-control-input" id="status">
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