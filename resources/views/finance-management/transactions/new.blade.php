@extends("layouts.cp")
@section('title', 'New Transaction - Finance Management')

@section("content")

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Finance Management</span>
        </h1>
        <a href="{{ url("/finance-management/transactions") }}" class="btn btn-primary mr-3"><i class="fal fa-arrow-alt-left"></i> Go Back </a>
    </div>

    <div  class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false" data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                New Transaction
            </h2>
        </div>

        <form method="post" action="{{ url("/finance-management/transactions/store") }}" enctype="multipart/form-data">
            @csrf
            <div class="panel-container show">
                <div class="panel-content">
                    @include("common.error-message")
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label" for="name">Account Title</label>
                                <input type="text" autofocus id="name" name="name" value="{{ old('name') }}" class="form-control" required>
                                @error('name')<span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>@enderror
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="transaction_date">Transaction Date</label>
                                {{--<input type="text" class="form-control" name="registration_date" id="datepicker-1" readonly placeholder="Select date">--}}
                                <input type="date" id="transaction_date" placeholder="dd/mm/yyyy" max="{{ date("Y-m-d") }}" name="transaction_date" value="{{ old('transaction_date') }}" class="form-control" required>
                                @error('transaction_date')<span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>@enderror
                            </div>

                            <label class="form-label">Transaction Type: </label>
                            <div class="custom-control custom-radio custom-control-inline mb-3">
                                <input type="radio" class="custom-control-input" id="credit" value="credit" name="type">
                                <label class="custom-control-label" for="credit">Credit</label>
                            </div>
                            <div class="custom-control custom-radio custom-control-inline mb-3">
                                <input type="radio" class="custom-control-input" id="debit" value="debit" name="type">
                                <label class="custom-control-label" for="debit">Debit</label>
                            </div>
                            <hr>
                            <label class="form-label">Payment Mode: </label>
                            <div class="custom-control custom-radio custom-control-inline mb-3">
                                <input type="radio" class="custom-control-input" id="cheque " value="cheque " name="mode">
                                <label class="custom-control-label" for="cheque ">Cheque</label>
                            </div>
                            <div class="custom-control custom-radio custom-control-inline mb-3">
                                <input type="radio" class="custom-control-input" id="TT" value="TT" name="mode">
                                <label class="custom-control-label" for="TT">TT</label>
                            </div>
                            <div class="custom-control custom-radio custom-control-inline mb-3">
                                <input type="radio" class="custom-control-input" id="internal" value="Internal Transfer" name="mode">
                                <label class="custom-control-label" for="internal">Internal Transfer</label>
                            </div>
                        </div>
                        <div class="col-md-6"></div>
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

        var controls = {
            leftArrow: '<i class="fal fa-angle-left" style="font-size: 1.25rem"></i>',
            rightArrow: '<i class="fal fa-angle-right" style="font-size: 1.25rem"></i>'
        }

        $(document).ready(function(){
            // minimum setup
            $('#datepicker-1').datepicker(
                {
                    todayHighlight: true,
                    orientation: "bottom left",
                    templates: controls
                });
        })

        function setCompanyType(type){

            if (type == "Other"){
                $("#other-company-type").removeClass("disabled")
            }else{
                $("#other-company-type:not([class*='disabled'])").addClass("disabled");
            }
        }

        function setFile(obj){

            var file = obj.files[0];
            var filename = file.name;
            document.getElementById("file-name-display").innerHTML = filename;
        }


    </script>
@stop
