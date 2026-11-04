<div class="card p-5">

    @include('common.error-message')
    <h4>KYC Required</h4>
    <p>Your account is incomplete please perform liveness check.</p>
    <div class="row">
        <div class="col-md-12">
            We've sent you an email with <b>Perform Liveness</b> button. Please check your inbox and click on that
            button to complete your profile.
            {{-- <a href="{{ url('/client-profile') }}" class="btn btn-primary">Update Profile</a> --}}
        </div>
    </div>
</div>
