@extends('layouts.cp')
@section('title', 'New Bank - Finance Management')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Finance Management</span>
        </h1>
        <a href="{{ url('/finance-management/banks') }}" class="btn btn-primary mr-3"><i class="fal fa-arrow-alt-left"></i> Go
            Back </a>
    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false"
        data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                New Bank
            </h2>
        </div>

        <form method="post" action="{{ url('/finance-management/banks/store') }}" enctype="multipart/form-data">
            @csrf
            <div class="panel-container show">
                <div class="panel-content">
                    @include('common.error-message')

                    <ul class="nav nav-tabs nav-tabs-clean" role="tablist">
                        <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#tab-general"
                                role="tab">General</a></li>
                        <li class="nav-item"><a class="nav-link disabled" data-toggle="tab" href="#tab-account"
                                role="tab">Accounts</a></li>
                    </ul>

                    <div class="tab-content p-3">
                        <div class="tab-pane fade show active" id="tab-general" role="tabpanel"
                            aria-labelledby="tab-general">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label" for="name">Name</label>
                                        <input type="text" autofocus id="name" name="name"
                                            value="{{ old('name') }}" class="form-control" required>
                                        @error('name')
                                            <span class="invalid-feedback"
                                                role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label" for="address">Address</label>
                                        <input type="text" id="address" name="address" value="{{ old('address') }}"
                                            class="form-control" required>
                                        @error('address')
                                            <span class="invalid-feedback"
                                                role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>

                                    <div class="row">
                                        <div class="col-md-4">

                                            <div class="custom-control custom-radio mb-3">
                                                <input type="radio" class="custom-control-input" id="service_fee_bank"
                                                    value="service_fee_bank" name="fee_type">
                                                <label class="custom-control-label" for="service_fee_bank">Is
                                                    {{ $ownCompany ? $ownCompany->short_name : 'Service' }} Fee
                                                    Bank</label>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="custom-control custom-radio mb-3">
                                                <input type="radio" class="custom-control-input" id="trn_fee_bank"
                                                    value="trn_fee_bank" name="fee_type">
                                                <label class="custom-control-label" for="trn_fee_bank">Is Transaction
                                                    Fee
                                                    Bank</label>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <div class="custom-control custom-switch">
                                                    <input type="checkbox" class="custom-control-input" id="is_reserve_bank"
                                                        value="1" name="is_reserve_bank">
                                                    <label class="custom-control-label" for="is_reserve_bank">Is Reserve
                                                        Bank</label>
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
