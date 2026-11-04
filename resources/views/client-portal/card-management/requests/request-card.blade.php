@extends('layouts.cp')
@section('title', 'Instant Card Request - Client Card Management')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Client Card Management</span>
        </h1>
    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false"
        data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                Instant Card Request
            </h2>
        </div>

        <form method="post" action="{{ url('/client/card-management/request-card') }}" enctype="multipart/form-data">
            @csrf
            <div class="panel-container show">
                <div class="panel-content">
                    @include('common.error-message')
                    <div class="row">
                        <div class="col-md-6">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label" for="firstName">First Name</label>
                                        <input type="text" id="firstName" name="firstName" value="{{ old('firstName') }}"
                                            class="form-control" required>
                                        @error('firstName')
                                            <span class="invalid-feedback"
                                                role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label" for="lastName">Last Name</label>
                                        <input type="text" id="lastName" name="lastName" value="{{ old('lastName') }}"
                                            class="form-control" required>
                                        @error('lastName')
                                            <span class="invalid-feedback"
                                                role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row mt-4">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label" for="email">Email</label>
                                        <input type="email" id="email" name="email" value="{{ old('email') }}"
                                            class="form-control" required>
                                        @error('email')
                                            <span class="invalid-feedback"
                                                role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label" for="mobileNo">Mobile Number</label>
                                        <input type="number" id="mobileNo" name="mobileNo" value="{{ old('mobileNo') }}"
                                            class="form-control" required>
                                        @error('mobileNo')
                                            <span class="invalid-feedback"
                                                role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row mt-4">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label" for="dateOfBirth">Date of Birth</label>
                                        <input type="text" autocomplete="off" class="form-control" name="dateOfBirth"
                                            id="dateOfBirth" placeholder="Select date" required>
                                        @error('dateOfBirth')
                                            <span class="invalid-feedback"
                                                role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label" for="gender">Gender</label><br>
                                        <div class="custom-control custom-radio custom-control-inline mt-2">
                                            <input type="radio" required class="custom-control-input" id="male"
                                                value="male" name="gender">
                                            <label class="custom-control-label" for="male">Male</label>
                                        </div>
                                        <div class="custom-control custom-radio custom-control-inline mb-3">
                                            <input type="radio" required class="custom-control-input" id="female"
                                                value="female" name="gender">
                                            <label class="custom-control-label" for="female">Female</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6" style="border-left: 1px solid lightgray;">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label" for="country">Country</label>
                                        <select name="country" autofocus id="country" value=""
                                            data-select2-id="country" class="form-control select2 w-100" required>
                                            <option value="">Choose Country</option>
                                            @foreach ($countries as $country)
                                                <option value="{{ $country->id }}">{{ $country->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('country')
                                            <span class="invalid-feedback"
                                                role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label" for="state">State</label>
                                        <input type="text" id="state" name="state" value="{{ old('state') }}"
                                            class="form-control" required>
                                        @error('state')
                                            <span class="invalid-feedback"
                                                role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row mt-4">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label" for="city">City</label>
                                        <input type="text" id="city" name="city" value="{{ old('city') }}"
                                            class="form-control" required>
                                        @error('city')
                                            <span class="invalid-feedback"
                                                role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label" for="postalCode">Postal Code</label>
                                        <input type="text" id="postalCode" name="postalCode"
                                            value="{{ old('postalCode') }}" class="form-control" required>
                                        @error('postalCode')
                                            <span class="invalid-feedback"
                                                role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row mt-4">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="form-label" for="address">Address</label>
                                        <textarea maxlength="92" class="form-control" required name="address" id="address" cols="30"
                                            rows="2">{{ old('address') }}</textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <hr>
                                <button id="submit" type="submit" class="btn btn-primary">Submit</button>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </form>
    </div>
@stop
@section('scripts')
    <script>
        var controls = {
            leftArrow: '<i class="fal fa-angle-left" style="font-size: 1.25rem"></i>',
            rightArrow: '<i class="fal fa-angle-right" style="font-size: 1.25rem"></i>'
        }

        $(document).ready(function() {

            // not working
            $('.select2').select2();

            // minimum setup
            $('#dateOfBirth').datepicker({
                format: 'dd/mm/yyyy',
                todayHighlight: true,
                orientation: "bottom left",
                templates: controls,
                endDate: "{{ \Carbon\Carbon::today()->format('d/m/Y') }}"
            });
        })
    </script>
@stop
