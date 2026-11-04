<div class="card p-5">

    @include("common.error-message")
    <h4>Congratulations!</h4>
    <p>Your account has been approved, welcome to the {{ $ownCompany ? $ownCompany->short_name : '' }} family.</p>
    <p>Now you can visit your new Dashboard on below link</p>
    <div class="row">
        <div class="col-md-12">
            <a href="{{ url("/") }}" class="btn btn-primary">Visit Dashboard</a>
        </div>
    </div>
</div>
