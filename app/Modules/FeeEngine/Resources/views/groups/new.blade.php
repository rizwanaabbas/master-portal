@extends('layouts.cp')
@section('title', 'New Fee Tier Group - Fee Engine')

@section('content')
    <div class="subheader">
        <h1 class="subheader-title"><span class='fw-300'>Fee Engine</span> &mdash; New Fee Tier Group</h1>
        <a href="{{ url('/settings/fee-engine/groups') }}" class="btn btn-primary ml-auto"><i class="fal fa-arrow-alt-left"></i> Go Back</a>
    </div>

    <div class="panel">
        <div class="panel-hdr"><h2>New Fee Tier Group</h2></div>
        <div class="panel-container show">
            <div class="panel-content">
                @include('feeengine::partials.flash')

                <form method="post" action="{{ url('/settings/fee-engine/groups') }}">
                    @csrf
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" value="{{ old('name') }}" class="form-control" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="form-label">Code (slug)</label>
                            <input type="text" name="code" value="{{ old('code') }}" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="2">{{ old('description') }}</textarea>
                    </div>
                    <div class="custom-control custom-switch mb-2">
                        <input type="hidden" name="is_default" value="0">
                        <input type="checkbox" class="custom-control-input" id="is_default" name="is_default" value="1">
                        <label class="custom-control-label" for="is_default">Default group (fallback for unassigned clients)</label>
                    </div>
                    <div class="custom-control custom-switch mb-3">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" checked>
                        <label class="custom-control-label" for="is_active">Active</label>
                    </div>
                    <hr>
                    <button type="submit" class="btn btn-primary">Create Group</button>
                </form>
            </div>
        </div>
    </div>
@stop
