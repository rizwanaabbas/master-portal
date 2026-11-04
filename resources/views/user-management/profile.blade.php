@extends("layouts.cp")
@section('title', 'User Profile - User Management')

@section("content")

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>User Management</span>
        </h1>
        <a href="{{ url("/home") }}" class="btn btn-primary mr-3"><i class="fal fa-arrow-alt-left"></i> Go Back </a>
    </div>

    <div  class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false" data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                User Profile
            </h2>
        </div>


            <div class="panel-container show">
                <div class="panel-content">
                    @include("common.error-message")

                    <div class="tab-content p-3">
                        <div class="tab-pane fade show active" id="tab-general" role="tabpanel" aria-labelledby="tab-general">
                            <form method="post" action="{{ url("/profile-update") }}" enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="id" id="id" value="{{ $user->id }}">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label" for="name">Full Name</label>
                                            <input type="text" id="name" name="name" value="{{ $user->name }}" class="form-control" required>
                                            @error('name')<span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>@enderror
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label" for="name">Email</label>
                                            <input type="email" id="email" disabled="" name="email" value="{{ $user->email }}" class="form-control" required>
                                            @error('email')<span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>@enderror
                                        </div>
                                        <div class="form-group">
                                            @if ($user->profile_photo_url != "")
                                                <img src="{{ $user->profile_photo_url }}" style="width: 35px; height:35px" class="mr-2 border border-primary profile-image rounded-circle" alt="Profile Photo">
                                            @endif
                                            <label for="profile_photo" class="btn btn-light">Upload Photo</label>
                                            <span id="file-name-display"> choose jpg/png, recommended size 50x50</span>
                                            <input type="file" name="profile_photo" accept="image/*" onchange="setFile(this)" id="profile_photo" class="invisible item-file"/>
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
                            </form>
                    </div>
                </div>

            </div>

    </div>

    <script>

        function changeCredentaisl(){
            //document.changeCredentials.submit();
            document.getElementById("modifyPassword").submit();
        }

        function setFile(obj){

            var file = obj.files[0];
            var filename = file.name;
            document.getElementById("file-name-display").innerHTML = filename;
        }

    </script>

@stop