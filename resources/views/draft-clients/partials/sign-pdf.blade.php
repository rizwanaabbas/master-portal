<form action="{{ url("/client-profile/sign-pdf/update") }}" method="post">
    @csrf
    <input type="hidden" name="client_id" value="{{ $client->id }}">
    <input type="hidden" name="individual_id" value="{{ $client->individual->id }}">
    <div class="main">
        <div class="text">
            <h2>Upload Signed PDF</h2>
            <p>Upload the signed PDF file.</p>
        </div>
        <div class="row">
            <div class="col-md-12">
                <p>Thank you for taking this long, one final step to go.</p>
                <p>We have sent you a PDF file on your register email address, please follow the instruction on the email. Once you are done, please come back here and upload your file.</p>

                <div class="form-group">
                    <div class="col-md-12">
                        <input type="file" class="form-control">
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <hr>
                    <div class="d-flex justify-content-between">
                        <button type="submit" class=" btn btn-primary">Submit</button>
                        <div>
                            <button type="button" class="btn btn-outline-secondary back_button"><i class="fal fa-arrow-left"></i> Back</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
