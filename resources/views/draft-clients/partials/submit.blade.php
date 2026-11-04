<form method="post" action="{{ url('/client-profile/submit') }}">
    @csrf
    <input type="hidden" name="client_id" value="{{ $client->id }}">

    @if ($client->type == 'individual')
        <input type="hidden" name="individual_id" value="{{ $client->individual->id }}">
    @else
        <input type="hidden" name="company_id" value="{{ $client->company->id }}">
    @endif
    <div class="main">
        <div class="text">
            <h2>Submit</h2>
            <p>Review and confirm your details before you submit.</p>
        </div>

        <div class="row">
            <div class="col-md-12">
                <ul class="list-group">
                    <li class="list-group-item">Please review your information on all the steps, make sure you have
                        provided the correct data.</li>
                    <li class="list-group-item">The filled information will be sent to you as a PDF for signature.</li>
                </ul>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-md-12 alert alert-warning">
                <div class="custom-control custom-checkbox custom-control-inline">
                    <input required type="checkbox" class="custom-control-input" value="1" id="verified_details"
                        name="verified_details">
                    <label class="custom-control-label" for="verified_details">I have reviewed all the details.</label>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <hr>
                    <button type="button" class="btn btn-outline-secondary back_button"><i
                            class="fal fa-arrow-left"></i> Back</button>
                    <button type="submit" class=" btn btn-primary">Submit</button>
                </div>
            </div>
        </div>
    </div>
</form>
