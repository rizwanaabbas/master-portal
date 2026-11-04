@extends('layouts.cp')
@section('title', 'New Client File Config - System Settings')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>System Settings</span>
        </h1>
        <a href="{{ url('/settings/client-file-config') }}" class="btn btn-primary mr-3"><i class="fal fa-arrow-alt-left"></i>
            Go Back </a>
    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false"
        data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                New Client File Config
            </h2>
        </div>

        <form method="post" action="{{ url('/settings/client-file-config/store') }}" enctype="multipart/form-data">
            @csrf
            <div class="panel-container show">
                <div class="panel-content">
                    @include('common.error-message')

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label" for="name">Title</label>
                                <input type="text" autofocus id="title" name="title" value="{{ old('title') }}"
                                    class="form-control" required>
                                @error('title')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="identifier">Identifier</label>
                                <input type="text" autofocus id="identifier" name="identifier"
                                    value="{{ old('identifier') }}" class="form-control">
                                @error('identifier')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                            <div class="form-group">
                                <div class="input-group mt-3">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="isMultiple"
                                                    value="1" name="isMultiple">
                                                <label class="custom-control-label" for="isMultiple">Multiple (multiple
                                                    files are required)</label>
                                            </div>
                                        </span>
                                    </div>
                                    <input type="text" id="maxCount" name="maxCount" class="form-control"
                                        placeholder="number of files" aria-label="maxCount" aria-describedby="maxCount">
                                </div>
                                <div class="custom-control custom-switch mt-3">
                                    <input type="checkbox" class="custom-control-input" id="isMandatory" value="1"
                                        name="isMandatory">
                                    <label class="custom-control-label" for="isMandatory">Mandatory (the user must upload
                                        this file)</label>
                                </div>

                                <div class="custom-control custom-switch mt-3">
                                    <input type="checkbox" class="custom-control-input" id="is_active" value="1"
                                        name="is_active">
                                    <label class="custom-control-label" for="is_active">Active</label>
                                </div>

                                <div class="custom-control custom-switch mt-3">
                                    <input type="checkbox" class="custom-control-input" id="signed_pdf" value="1"
                                        name="signed_pdf">
                                    <label class="custom-control-label" for="signed_pdf">Signed</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Client Type</label>
                            <hr>
                            @foreach ($types as $type)
                                <div class="custom-control custom-radio mb-3">
                                    <input type="radio" class="custom-control-input" id="{{ $type }}"
                                        value="{{ $type }}" name="type" required>
                                    <label class="custom-control-label"
                                        for="{{ $type }}">{{ ucwords($type) }}</label>
                                </div>
                            @endforeach
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
