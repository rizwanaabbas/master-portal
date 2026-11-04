@extends('layouts.cp')
@section('title', 'New User - User Management')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>User Management</span>
        </h1>
        <a href="{{ url('/user-management/users') }}" class="btn btn-primary mr-3"><i class="fal fa-arrow-alt-left"></i> Go
            Back </a>
    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false"
        data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                New User
            </h2>
        </div>

        <form method="post" action="{{ url('/user-management/users/store') }}" enctype="multipart/form-data">
            @csrf
            <div class="panel-container show">
                <div class="panel-content">
                    @include('common.error-message')

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label" for="code">Code</label>
                                <input type="text" autofocus id="code" name="code" value="{{ old('code') }}"
                                    class="form-control" required>
                                @error('code')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="name">Full Name</label>
                                <input type="text" autofocus id="name" name="name" value="{{ old('name') }}"
                                    class="form-control" required>
                                @error('name')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="name">Email</label>
                                <input type="email" id="email" name="email" autocomplete="off"
                                    value="{{ old('email') }}" class="form-control" required>
                                @error('email')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="name">Password</label>
                                <input type="password" autocomplete="off" id="password" name="password"
                                    class="form-control" required>
                                @error('password')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="name">Password Confirmation</label>
                                <input type="password" autocomplete="off" id="password" name="password_confirmation"
                                    class="form-control" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="allowed_currencies">Allowed Currencies</label>
                                <select id="allowed_currencies" name="allowed_currencies[]" multiple=""
                                    class="form-control">
                                    @foreach ($currencies as $currency)
                                        <option value="{{ $currency->id }}">{{ $currency->short }}</option>
                                    @endforeach
                                </select>
                                <small>Select Multiple Currencies by holding <b>Ctrl key</b> on Windows and <b>Command
                                        key</b> on Mac and click one-by-one. This
                                    field is mainly intended for B2B/Merchant API
                                    Users.</small>
                                @error('allowed_currencies')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <div class="custom-control custom-switch mt-3">
                                            <input type="checkbox" class="custom-control-input" id="is_selfie_required"
                                                value="1" name="is_selfie_required">
                                            <label class="custom-control-label" for="is_selfie_required">Is Selfie
                                                Required</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <div class="custom-control custom-switch mt-3">
                                            <input type="checkbox" class="custom-control-input"
                                                id="is_initial_review_required" value="1"
                                                name="is_initial_review_required">
                                            <label class="custom-control-label" for="is_initial_review_required">Is
                                                Initial
                                                Review Required</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <div class="custom-control custom-switch mt-3">
                                            <input type="checkbox" class="custom-control-input"
                                                id="is_internal_transfer_approval_required" value="1"
                                                name="is_internal_transfer_approval_required" checked>
                                            <label class="custom-control-label"
                                                for="is_internal_transfer_approval_required">Is Internal Transfer Approval
                                                Required</label>
                                            <br />
                                            <small>Internal Transfer Request for this User (API Role Only) will be
                                                automatically approved,
                                                if this flag is turned-off.</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <div class="custom-control custom-switch mt-3">
                                            <input type="checkbox" class="custom-control-input"
                                                id="is_mobile_otp_required" value="1" name="is_mobile_otp_required"
                                                checked>
                                            <label class="custom-control-label" for="is_mobile_otp_required">Is
                                                Mobile OTP Required</label>
                                            <br />
                                            <small>Mobile app login for this User asks for an email OTP when this flag
                                                is turned-on.</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            @can('update user unit')
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group mt-4">
                                            <label class="form-label" for="manager_id">User Unit</label>
                                            <select name="unit_id" id="unit_id" class="form-control">
                                                <option value="">Choose Unit</option>
                                                @foreach ($units as $unit)
                                                    <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                                                @endforeach

                                            </select>
                                            @error('unit_id')
                                                <span class="invalid-feedback"
                                                    role="alert"><strong>{{ $message }}</strong></span>
                                            @enderror
                                            <small>Only Select With Role Basic Admin </small>

                                        </div>
                                    </div>
                                </div>
                            @endcan
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label" for="name">Line Manager</label>
                                <select name="manager_id" id="manager_id" class="form-control">
                                    <option value="">choose manager</option>
                                    @foreach ($managers as $manager)
                                        <option value="{{ $manager->id }}">{{ $manager->name }}</option>
                                    @endforeach

                                </select>
                                @error('manager')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                            <table class="table table-bordered m-0">
                                <thead class="thead-themed">
                                    <tr>
                                        <th>Assign Roles</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>
                                            @foreach ($roles as $role)
                                                <div class="custom-control custom-checkbox mb-3">
                                                    <input type="checkbox" name="roles[]" value="{{ $role->name }}"
                                                        class="custom-control-input" id="{{ $role->id }}">
                                                    <label class="custom-control-label"
                                                        for="{{ $role->id }}">{{ $role->name }}</label>
                                                </div>
                                            @endforeach

                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <br>

                            <div class="form-group">
                                <label for="profile_photo" class="btn btn-light">Upload Photo</label>
                                <span id="file-name-display"> choose jpg/png, recommended size 50x50</span>
                                <input type="file" name="profile_photo" accept="image/*" onchange="setFile(this)"
                                    id="profile_photo" class="invisible item-file" />
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
