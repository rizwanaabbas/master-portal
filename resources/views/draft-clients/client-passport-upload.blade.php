@extends('layouts.public')
@section('title', 'Upload passport')
@section('styles')
    <link rel="stylesheet" media="screen, print"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="{{ asset('css/client-on-boarding.css') }}">
@stop

@section('content')

    <div id="error-case" class="col-md-12" style="display: block">
        @if(session()->has('error'))
            <div class="alert alert-danger">
                {{ session()->get('error') }}
            </div>
        @endif
 
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
        <div class="text">
            <h2>Upload Passport</h2>
            <h4>Please upload your latest passport.</h4>
        </div>
        <hr>
        <form method="POST" action="{{ url('/client-kyc/upload-passport') }}" enctype="multipart/form-data" id="passport-form">
            @csrf
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="passport-upload" class="form-label">Upload Passport</label>
                        <input type="file" class="form-control" id="passport-upload" name="passport-upload" accept="image/jpeg,image/gif,image/png,application/pdf,image/x-eps" onchange="previewPassport()">
                        <div class="invalid-feedback ">Please upload valid passport.</div>
                        <div id="passport-preview" class="mt-3">
                            <img id="passport-preview-img" src="{{ asset('assets/img/passport-mock-placeholder.png') }}" alt="Passport Picture" class="img-thumbnail" style="max-width: 300px; display: block;">
                            <iframe id="passport-preview-pdf" style="width: 100%; height: 500px; display: none;"></iframe>
                        </div>
                    </div>
                </div>


                <div class="col-md-6">
                    <div class="form-group row">
                        <div class="col-md-12">
                            <label for="passport-number" class="form-label">Passport Number</label>
                            <input type="text" class="form-control" id="passport-number" name="passport_number" placeholder="Passport Number" required>
                            <div class="invalid-feedback">Please enter a valid Passport Number.</div>
                        </div>
                        <div class="col-md-12 mt-4">
                            <label for="passport-expiry" class="form-label">Passport Expiry</label>
                            <input type="text" class="form-control passport-expiry" id="passport-expiry" name="passport_expiry" placeholder="Passport Expiry" autocomplete="off" required>
                            <div class="invalid-feedback">Please enter a valid Passport Expiry.</div>
                        </div>
                        <div class="col-md-12 mt-4">
                            <label for="dob" class="form-label">Date of Birth</label>
                            <input type="text" class="form-control dob" id="dob" data-date-end-date="0d" name="dob" placeholder="Date of Birth"  autocomplete="off" required>
                            <div class="invalid-feedback">Please enter a valid Date of Birth.</div>
                        </div>
                        <div class="col-md-12 mt-4">
                            <label class="form-label">Gender</label>
                            <div class="custom-control custom-radio custom-control-inline">
                                <input type="radio" class="custom-control-input" id="male" name="gender" value="MALE" required>
                                <label class="custom-control-label" for="male">Male</label>
                            </div>
                            <div class="custom-control custom-radio custom-control-inline">
                                <input type="radio" class="custom-control-input" id="female" name="gender" value="FEMALE" required>
                                <label class="custom-control-label" for="female">Female</label>
                            </div>
                            <div class="invalid-feedback gender-feedback">Please select gender.</div>
                        </div>
                        <div class="col-md-12 mt-4">
                            <label for="nationality_id" class="form-label">Nationality</label>
                            <select name="nationality_id" id="nationality_id" class="form-control select2 w-100 select2" required>
                                <option value="">Choose nationality</option>
                                @foreach ($nationalities as $nationality)
                                    <option value="{{ $nationality->id }}">{{ $nationality->name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback nationality-select-feedback">Please select Nationality.</div>
                        </div>
                        <div class="col-md-12 mt-4">
                            <label for="nationality_id" class="form-label">Country of Residence</label>
                            <select name="residence_country_id" id="residence_country_id" class="form-control select2 w-100 select2" required>
                                <option value="">Choose Residence</option>
                                @foreach ($nationalities as $nationality)
                                    <option value="{{ $nationality->id }}">{{ $nationality->name }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback nationality-select-feedback">Please select Residence.</div>
                        </div>
                    </div>
                    </div>
                </div>
                <div id="erros" class="col-md-12" style="display: none"></div>
                <input type="hidden" name="hash" id="hash" value="{{$hash}}">
                <input type="hidden" name="document-type" id="hash" value="file-8">
                <div class="row"><div class="col-md-12"><button type="button" onclick="submitImage()" class="btn btn-primary mt-3 float-right">Submit</button></div></div>
            </div>
        </form>
    

    <script>
    function validate(id) {
       // value = $('#'+id);
        var value = $('#'+id).val();
        if( value == '' ) {
                error = 1;
                console.log( $('#'+id).attr('id'));
                $('#'+id).next().show();
                if($('#'+id).attr('id') == 'nationality_id') {
                    $('.nationality-select-feedback').show();
                }
                if(!$('#male, #female').is(':checked')) { 
                    $('.gender-feedback').show();
                }
                return false;
            } else {
                if($('#'+id).next().hasClass('invalid-feedback')) {
                    $('#'+id).next().hide(); 
                }
                if($('#'+id).attr('id') == 'nationality_id') {
                    $('.nationality-select-feedback').hide();
                }
                if($('#male, #female').is(':checked')) { 
                    $('.gender-feedback').hide();
                }
                return true;
            }
    }

    function submitImage() {
        var error = 0;
       
        if(!validate('passport-upload')){
            error = 1;
        }
        if(!validate('passport-expiry')){
            error = 1;
        }
        if(!validate('passport-number')){
            error = 1;
        }
        if(!validate('dob')){
            error = 1;
        }
        if(!validate('male')){
            error = 1;
        }
        if(!validate('female')){
            error = 1;
        }
        if(!validate('nationality_id')){
            error = 1;
        }
          
       
        console.log(error);
        if( error == 1)
            return false;

        console.log(error);
        if( $('#passport-upload').val() == '') {
            $('#erros').html('<div class="alert alert-danger">Please select passport file to upload.</div>').show();
            return false;
       }

       $('#passport-form').submit();
    }
    
    function previewPassport() {
        $('#erros').html('').hide();
        const fileInput = document.getElementById('passport-upload');
        const previewImg = document.getElementById('passport-preview-img');
        const previewPdf = document.getElementById('passport-preview-pdf');
        const file = fileInput.files[0];

        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const fileType = file.type;
                console.log(fileType);
                if (fileType.startsWith('image/')) {
                    // Preview image
                    previewImg.src = e.target.result;
                    previewImg.style.display = 'block';
                    previewPdf.style.display = 'none';
                } else if (fileType === 'application/pdf') {
                    // Preview PDF
                    previewPdf.src = e.target.result;
                    previewPdf.style.display = 'block';
                    previewImg.style.display = 'none';
                }
            }
            reader.readAsDataURL(file);
        }
    }

   
    </script>
    
@stop