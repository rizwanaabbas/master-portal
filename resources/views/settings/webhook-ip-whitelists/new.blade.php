@extends('layouts.cp')
@section('title', 'New Webhook IP Whitelist - System Settings')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>System Settings</span>
        </h1>
        <a href="{{ url('/settings/webhook-ip-whitelists') }}" class="btn btn-primary mr-3"><i class="fal fa-arrow-alt-left"></i>
            Go Back </a>
    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false"
        data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                New Webhook IP Whitelist
            </h2>
        </div>

        <form method="post" action="{{ url('/settings/webhook-ip-whitelists/store') }}" enctype="multipart/form-data">
            @csrf
            <div class="panel-container show">
                <div class="panel-content">
                    @include('common.error-message')

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label" for="name">Service</label>
                                <input type="text" autofocus id="service" name="service" value="{{ old('service') }}"
                                    class="form-control" required>
                                @error('service')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="identifier">IP Address</label>
                                <input type="text" autofocus id="ip_address" name="ip_address"
                                    value="{{ old('ip_address') }}" class="form-control">
                                @error('ip_address')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="identifier">Whitelisting For</label>
                                <input type="text" autofocus id="whitelisted_for" name="whitelisted_for"
                                    value="{{ old('whitelisted_for') }}" class="form-control">
                                @error('whitelisted_for')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input"
                                        id="is_active" value="1" name="is_active" @if (old('is_active')) checked @endif>
                                    <label class="custom-control-label"
                                        for="is_active">Is Active</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label" for="name">Description</label>
                                <textarea maxlength="100" required class="form-control" name="description" id="description" cols="30" rows="5"
                                    placeholder="Please provide description for the Webhook IP whitelist entry."></textarea>
                                <small>maximum 100 characters allowed.</small>
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
