@extends('layouts.cp')
@section('title', 'Dashboard')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Dashboard</span>
        </h1>
    </div>
    <div class="row">
        <div class="col-md-6">
            @if ($client->status == 'Draft' || $client->status == 'Liveness Done')
                @include('draft-clients.dashboard.partials.onboarding')
            @elseif($client->status == 'In Progress')
                @include('draft-clients.dashboard.partials.pdf-upload')
            @elseif($client->status == 'Pending')
                @include('draft-clients.dashboard.partials.pending-approval')
            @elseif ($clientKYC)
                @include('draft-clients.dashboard.partials.liveness-pending')
            @elseif($client->status == 'Rejected')
                @include('draft-clients.dashboard.partials.rejected')
            @elseif($client->status == 'Approved')
                @include('draft-clients.dashboard.partials.approved')
            @endif
        </div>
    </div>
@stop

@section('scripts')
    <script>
        $(document).ready(function() {
            $('#data-grid').dataTable({
                responsive: true
            });

            impersonate(579)
        });

        function impersonate(id) {
            fetch('http://127.0.0.1:8001/api/user/impersonate', {
                    method: 'POST',
                    headers: {
                        'api-key': '$2y$10$ITCGB3rZsvclvU9f7WhTHO6JC4wQxx4r8mDL6m3/0gaOLp3Fg8SiS',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        user_id: id
                    })
                })
                .then(response => response.json())
                .then(data => {
                    console.log(data)
                    // if (data.redirect_url) {
                    //     const redirect_url = data.redirect_url
                    //     delete data['redirect_url']
                    //     // console.log('data: ', data)
                    //     // console.log('JSON Stringified data: ', JSON.stringify(data))

                    //     // console.log('After deleting url data: ', data)

                    //     // return fetch(redirect_url, {
                    //     //     method: 'POST',
                    //     //     headers: {
                    //     //         'Content-Type': 'application/json',
                    //     //     },
                    //     //     body: JSON.stringify(data) // Replace with actual data
                    //     // });
                    // } else {
                    //     console.log(data.message);
                    // }
                })
                .catch(function(error) {
                    console.error('Error:', error)
                });
        }

        function deleteUser(id) {

            Swal.fire({
                title: "Are you sure?",
                text: "User will be deleted permanently",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Yes, delete it!"
            }).then(function(result) {
                if (result.value) {
                    $("#user_id").val(id);
                    document.getElementById('delete-user').submit()
                }
            });
        }
    </script>
@stop
