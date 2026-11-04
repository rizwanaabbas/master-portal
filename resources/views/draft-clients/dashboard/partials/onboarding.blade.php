<div class="card p-5">
    <h4>Welcome {{ \Illuminate\Support\Facades\Auth::user()->name }},</h4>
    <p>Thank you for registering your account with us, please provide further detials for your account.</p>
    <p>You can go to the left menu "Client Profile" or click on below link to complete your profile.</p>
    <div class="row">
        <div class="col-md-4">
            <a href="{{ url("/client-profile") }}" class="btn btn-primary">Client Profile</a>
        </div>
    </div>
</div>
