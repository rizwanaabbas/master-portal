@extends('layouts.cp')
@section('title', 'Update Client File Config - System Settings')

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
                Update Client File Config
            </h2>
        </div>

        <form method="post" action="{{ url('/settings/client-file-config/update') }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="id" id="id" value="{{ $file->id }}">
            <div class="panel-container show">
                <div class="panel-content">
                    @include('common.error-message')

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label" for="name">Title</label>
                                <input type="text" autofocus id="title" name="title" value="{{ $file->title }}"
                                    class="form-control" required>
                                @error('title')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="identifier">Identifier</label>
                                <input type="text" autofocus id="identifier" name="identifier"
                                    value="{{ $file->identifier }}" class="form-control">
                                @error('identifier')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                            <div class="form-group">
                                <div class="input-group mt-3">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input"
                                                    @if ($file->isMultiple) checked @endif id="isMultiple"
                                                    value="1" name="isMultiple">
                                                <label class="custom-control-label" for="isMultiple">Multiple (multiple
                                                    files are required)</label>
                                            </div>
                                        </span>
                                    </div>
                                    <input type="text" id="maxCount" name="maxCount" value="{{ $file->maxCount }}"
                                        class="form-control" placeholder="number of files" aria-label="maxCount"
                                        aria-describedby="maxCount">
                                </div>
                                <div class="custom-control custom-switch mt-3">
                                    <input type="checkbox" class="custom-control-input"
                                        @if ($file->isMandatory) checked @endif id="isMandatory" value="1"
                                        name="isMandatory">
                                    <label class="custom-control-label" for="isMandatory">Mandatory (the user must upload
                                        this file)</label>
                                </div>

                                <div class="custom-control custom-switch mt-3">
                                    <input type="checkbox" class="custom-control-input"
                                        @if ($file->is_active) checked @endif id="is_active" value="1"
                                        name="is_active">
                                    <label class="custom-control-label" for="is_active">Active</label>
                                </div>

                                <div class="custom-control custom-switch mt-3">
                                    <input type="checkbox" class="custom-control-input"
                                        @if ($file->signed_pdf) checked @endif id="signed_pdf" value="1"
                                        name="signed_pdf">
                                    <label class="custom-control-label" for="signed_pdf">Signed</label>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            
                            <div class="row">
                                {{-- Client Type --}}
                                <div class="col-md-3 form-group">
                                    <label class="form-label">Client Type</label>
                                    <hr>

                                    @foreach ($types as $type)
                                        <div class="custom-control custom-radio mb-2">
                                            <input type="radio" class="custom-control-input" id="type_{{ $type }}"
                                                name="type" value="{{ $type }}"
                                                @if ($type == $file->type) checked @endif required>
                                            <label class="custom-control-label" for="type_{{ $type }}">
                                                {{ ucwords($type) }}
                                            </label>
                                        </div>
                                    @endforeach
                                </div>

                                {{-- Providers --}}
                                <div class="col-md-3 form-group">
                                    <label class="form-label">Required for virtual bank</label>
                                    <hr>
                                    
                                    @php
                                        $required_on_providers = json_decode($file->required_for_providers) ?? [];
                                    @endphp

                                    @foreach ($virtual_providers_map as $provider => $label)
                                        <div class="custom-control custom-switch mb-2">

                                            <input 
                                                type="checkbox"
                                                class="custom-control-input"
                                                id="provider_{{ $provider }}"
                                                name="required_for_providers[]"
                                                value="{{ $provider }}"
                                                @if(in_array($provider, $required_on_providers )) checked @endif
                                            >

                                            <label 
                                                class="custom-control-label"
                                                for="provider_{{ $provider }}">
                                                {{ $label }}
                                            </label>

                                        </div>
                                    @endforeach

                                </div>
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
