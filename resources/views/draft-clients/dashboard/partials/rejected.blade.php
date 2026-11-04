<div class="card p-5">

    @include('common.error-message')
    <h4>Account Incomplete</h4>
    <p>Your account is incomplete due to below reason.</p>
    <p class="alert alert-warning">{{ $client->reject_reason }}</p>
    <div class="row">
        <div class="col-md-12">
            <a href="{{ url('/client-profile') }}" class="btn btn-primary">Update Profile</a>
        </div>
    </div>
</div>
