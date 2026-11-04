@extends('layouts.cp')
@section('title', 'New Crypto Currency - Crypto Management')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Crypto Management</span>
        </h1>
        <a href="{{ url('/crypto-management/crypto-currencies') }}" class="btn btn-primary mr-3"><i
                class="fal fa-arrow-alt-left"></i> Go Back </a>
    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false"
        data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                New Crypto Currency
            </h2>
        </div>

        <form method="post" action="{{ url('/crypto-management/crypto-currencies/store') }}" enctype="multipart/form-data">
            @csrf
            <div class="panel-container show">
                <div class="panel-content">
                    @include('common.error-message')

                    <div class="row">
                        <div class="col-md-12">
                            <div class="row mt-4">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="form-label" for="short">Short</label>
                                        <input type="text" autofocus id="short" maxlength="5" name="short"
                                            value="{{ old('short') }}" class="form-control" required>
                                        @error('short')
                                            <span class="invalid-feedback"
                                                role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="form-label" for="name">Name</label>
                                        <input type="text" autofocus id="name" name="name"
                                            value="{{ old('name') }}" class="form-control" required>
                                        @error('name')
                                            <span class="invalid-feedback"
                                                role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-4">
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label class="form-label" for="min-amount">Min</label>
                                        <input type="text" autofocus id="min-amount" name="min-amount" value=""
                                            class="form-control" required>
                                        @error('min-amount')
                                            <span class="invalid-feedback"
                                                role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label class="form-label" for="max-amount">Max</label>
                                        <input type="text" autofocus id="max-amount" name="max-amount" value=""
                                            class="form-control" required>
                                        @error('max-amount')
                                            <span class="invalid-feedback"
                                                role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="form-group">
                                    <div class="custom-control custom-switch mt-3">
                                        <input type="checkbox" class="custom-control-input" id="is_active"
                                            value="1" name="is_active">
                                        <label class="custom-control-label" for="is_active">Active</label>
                                    </div>
                                </div>
                            
                            
                                <div class="form-group">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="request_auto_approval"
                                            value="1" name="request_auto_approval">
                                        <label class="custom-control-label" for="request_auto_approval">Auto approve service request</label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mt-4">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="form-label" for="logo">Logo</label><br />
                                        <label for="logo" class="btn btn-light">Upload</label>
                                        <span id="file-name-display"> Choose jpg/png, recommended size 18x18</span>
                                        <input type="file" name="logo" accept="image/*" onchange="setFile(this)"
                                            id="logo" class="invisible item-file" />
                                    </div>
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

    <script>
        function setFile(obj) {

            var file = obj.files[0];
            var filename = file.name;
            document.getElementById("file-name-display").innerHTML = filename;
        }
    </script>
@stop
