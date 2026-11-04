@extends("layouts.cp")
@section('title', 'Client Notifications & Actions - Client Management')

@section("content")

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Client Management</span>
        </h1>
    </div>

    <div  class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false" data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                Client Notifications & Actions
            </h2>

        </div>

        <div class="panel-container show mb-3">
            <div class="panel-content" style="overflow: scroll;">
                @include("common.error-message")
                <form method="post" action="{{ url("/client-management/send-client-notifications") }}">
                    @csrf
                    <div class="row">
                        <div class="col-md-5">
                            <div class="form-group">
                                <label class="form-label" for="client_id">Clients (<small>Approved only</small>)</label>
                                <select name="client_id" id="client_id" data-select2-id="client_id" class="form-control select2 w-100" required>
                                    <option value="">choose client</option>
                                    @foreach ($clients as $client)
                                        @if ($client->id == old ("client_id"))
                                            <option value="{{ $client->id }}" selected>{{ $client->name }}</option>
                                        @else
                                            <option value="{{ $client->id }}">{{ $client->name }}</option>
                                        @endif
                                    @endforeach
                                </select>
                                @error('client_id')<span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>@enderror
                            </div>
                        </div>
                    </div>
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <label for="" class="form-label mb-3">Send Emails</label><br>
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="send_welcome_email" name="send_welcome_email">
                                <label class="custom-control-label" for="send_welcome_email">Welcome Email (this contains the welcome information for the client)</label>
                            </div>
                            <div class="custom-control custom-checkbox mt-3">
                                <input type="checkbox" class="custom-control-input" id="send_ssi" name="send_ssi">
                                <label class="custom-control-label" for="send_ssi">SSI Email (select this option with welcome email, as we are sending SSI as attachment)</label>
                            </div>
                            <div class="custom-control custom-checkbox mt-3">
                                <input type="checkbox" class="custom-control-input" id="create_user" name="create_user">
                                <label class="custom-control-label" for="create_user">Online Log-in Details Email (this will create a user and password and will send it to the client, if no user was created before)</label>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-2">
                            <button typ="submit" class="btn btn-primary">Send</button>
                        </div>
                    </div>
                </form>
                <br>
            </div>
        </div>
    </div>

@stop
@section("scripts")
    <script>

        $(document).ready(function() {
            $('.select2').select2();
        });
    </script>
@stop
