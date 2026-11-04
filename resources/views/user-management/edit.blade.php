@extends('layouts.cp')
@section('title', 'Update User - User Management')

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
                Update User
            </h2>
        </div>

        <div class="panel-container show">
            <div class="panel-content">
                @include('common.error-message')

                <form method="post" action="{{ url('/user-management/users/update') }}" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="id" id="id" value="{{ $user->id }}">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label" for="code">Code</label>
                                <input type="text" autofocus id="code" name="code" value="{{ $user->code }}"
                                    class="form-control" required>
                                @error('code')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="name">Full Name</label>
                                <input type="text" id="name" name="name" value="{{ $user->name }}"
                                    class="form-control" required>
                                @error('name')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="name">Email</label>
                                <input type="email" id="email" name="email" value="{{ $user->email }}"
                                    class="form-control" required>
                                @error('email')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="manager_id">Line Manager</label>
                                <select name="manager_id" id="manager_id" class="form-control">
                                    <option value="">choose manager</option>
                                    @foreach ($managers as $manager)
                                        @if ($manager->id == $user->manager_id)
                                            <option selected value="{{ $manager->id }}">{{ $manager->name }}</option>
                                        @else
                                            <option value="{{ $manager->id }}">{{ $manager->name }}</option>
                                        @endif
                                    @endforeach

                                </select>
                                @error('manager_id')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="allowed_currencies">Allowed Currencies</label>
                                <select id="allowed_currencies" name="allowed_currencies[]" multiple=""
                                    class="form-control">
                                    @foreach ($currencies as $currency)
                                        <option @if (in_array($currency->id, $user->allowed_currencies)) selected @endif
                                            value="{{ $currency->id }}">{{ $currency->short }}</option>
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

                            <div class="form-group">
                                @if ($user->profile_photo_url != '')
                                    <img src="{{ $user->profile_photo_url }}" style="width: 35px; height:35px"
                                        class="mr-2 border border-primary profile-image rounded-circle" alt="Profile Photo">
                                @endif
                                <label for="profile_photo" class="btn btn-light">Upload Photo</label>
                                <span id="file-name-display"> choose jpg/png, recommended size 50x50</span>
                                <input type="file" name="profile_photo" accept="image/*" onchange="setFile(this)"
                                    id="profile_photo" class="invisible item-file" />
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <div class="custom-control custom-switch mt-3">
                                            <input type="checkbox" class="custom-control-input"
                                                @if ($user->is_selfie_required) checked @endif id="is_selfie_required"
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
                                                @if ($user->is_initial_review_required) checked @endif
                                                id="is_initial_review_required" value="1"
                                                name="is_initial_review_required">
                                            <label class="custom-control-label" for="is_initial_review_required">Is
                                                Initial Review Required</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <div class="custom-control custom-switch mt-3">
                                            <input type="checkbox" class="custom-control-input"
                                                @if ($user->is_internal_transfer_approval_required) checked @endif
                                                id="is_internal_transfer_approval_required" value="1"
                                                name="is_internal_transfer_approval_required">
                                            <label class="custom-control-label"
                                                for="is_internal_transfer_approval_required">Is
                                                Internal Transfer Approval Required</label>
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
                                                @if ($user->is_mobile_otp_required) checked @endif
                                                id="is_mobile_otp_required" value="1" name="is_mobile_otp_required">
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
                                                    @if ($unit->id == $user->unit_id)
                                                        <option selected value="{{ $unit->id }}">{{ $unit->name }}
                                                        </option>
                                                    @else
                                                        <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                                                    @endif
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
                            <table class="table table-bordered mt-4">
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
                                                        @if ($user->hasRole($role->name)) checked @endif
                                                        class="custom-control-input" id="{{ $role->id }}">
                                                    <label class="custom-control-label"
                                                        for="{{ $role->id }}">{{ $role->name }}</label>
                                                </div>
                                            @endforeach

                                        </td>
                                    </tr>
                                </tbody>
                            </table>

                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <hr>
                                <button type="submit" class="btn btn-primary">Submit</button>
                                @if (\Illuminate\Support\Facades\Auth::user()->hasRole('super admin'))
                                    @if (!$user->locked)
                                        <button onclick="lockAccount(); return false;"
                                            class="btn btn-danger">Lock</button>
                                    @endif
                                    @if ($user->locked)
                                        <button onclick="unLockAccount(); return false;"
                                            class="btn btn-success">Unlock</button>
                                    @endif
                                    <button onclick="generatePassword(); return false;" class="btn btn-default">Generate
                                        Password</button>
                                    @if ($user->email_verified_at == null)
                                        <button onclick="verifyUser(); return false;"
                                            class="btn btn-primary">Verify</button>
                                    @endif
                                @endif

                            </div>
                        </div>
                    </div>
                </form>

            </div>

        </div>

    </div>
    <form method="POST" name="lock-user" id="lock-user" action="{{ url('/user-management/users/lock-account') }}">
        @csrf
        <input type="hidden" name="id" id="id" value="{{ $user->id }}">
    </form>
    <form method="POST" name="unlock-user" id="unlock-user"
        action="{{ url('/user-management/users/unlock-account') }}">
        @csrf
        <input type="hidden" name="id" id="id" value="{{ $user->id }}">
    </form>
    <form method="POST" name="generate-password" id="generate-password"
        action="{{ url('/user-management/users/generate-password') }}">
        @csrf
        <input type="hidden" name="id" id="id" value="{{ $user->id }}">
    </form>
    <form method="POST" name="verify-user" id="verify-user" action="{{ url('/user-management/users/verify-user') }}">
        @csrf
        <input type="hidden" name="id" id="id" value="{{ $user->id }}">
    </form>
@stop
@section('scripts')
    <script>
        function generatePassword() {
            Swal.fire({
                title: "Are you sure?",
                text: "New Password will be generated and sent to the user on the registered email address.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Yes, Generate it!"
            }).then(function(result) {
                if (result.value) {
                    document.getElementById('generate-password').submit()
                }
            });
        }

        function verifyUser() {
            Swal.fire({
                title: "Are you sure?",
                text: "This user will be verified.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Yes, Verify!"
            }).then(function(result) {
                if (result.value) {
                    document.getElementById('verify-user').submit()
                }
            });
        }

        function changeCredentaisl() {
            //document.changeCredentials.submit();
            document.getElementById("modifyPassword").submit();
        }

        function setFile(obj) {

            var file = obj.files[0];
            var filename = file.name;
            document.getElementById("file-name-display").innerHTML = filename;
        }

        function lockAccount() {

            Swal.fire({
                title: "Are you sure?",
                text: "User account will be locked, user will not be able to login.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Yes, Lock it!"
            }).then(function(result) {
                if (result.value) {
                    document.getElementById('lock-user').submit()
                }
            });
        }

        function unLockAccount() {

            Swal.fire({
                title: "Are you sure?",
                text: "User account will be unlocked, user will be able to login.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Yes, Unlock it!"
            }).then(function(result) {
                if (result.value) {
                    document.getElementById('unlock-user').submit()
                }
            });
        }
    </script>
@stop
