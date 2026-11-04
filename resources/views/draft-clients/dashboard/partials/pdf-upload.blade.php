<form action="{{ url("/client-profile/upload-pdf") }}" method="post" enctype="multipart/form-data">
    @csrf
    <div class="card p-5">

        @include("common.error-message")
        <h4>Upload PDF</h4>
        <p>We have sent you the PDF on your registered email, once you have filled and signed the PDF, please upload it
            to complete your registration.</p>
        <p>If you didn't receive the PDF, please <strong><a href="{{ url('/client-profile/download-pdf') }}">download it
                    here</a></strong>
        </p>
        @foreach ($configFiles as $file)
        <div class="row">
            <div class="col-md-12">
                <label class="form-label" for="file-{{ $file->id }}">{{ $file->title }}</label>
                <input class="form-control" type="file" required name="file-{{ $file->id }}" >
            </div>
        </div><br>
        @endforeach
        <div class="row mt-3">
            <div class="col-md-12">
                <div class="custom-control custom-checkbox custom-control-inline">
                    <input required type="checkbox" class="custom-control-input" value="1" id="verified_details"
                           name="verified_details">
                    <label class="custom-control-label" for="verified_details">I have
                        signed @if ($client->type == "company") and stamped @endif the PDF</label>
                </div>
            </div>
        </div>
        <hr>
        <div class="row">
            <div class="col-md-12">
                <button type="submit" class="btn btn-primary">Submit</button>
            </div>
        </div>
    </div>
</form>
