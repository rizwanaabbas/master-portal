@extends('layouts.cp')
@section('title', 'New Service Request - Service Request')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class="fw-300">Services Request</span>
        </h1>
        <a href="{{ url('/client/services/requests/list') }}" class="btn btn-primary mr-3"><i
                class="fal fa-arrow-alt-left"></i> Go Back </a>
    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false"
        data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                New Service Request
            </h2>
        </div>

        <form method="post" action="{{ url('/client/services/requests/store') }}" enctype="multipart/form-data">
            @csrf
            <div class="panel-container show">
                <div class="panel-content">
                    @include('common.error-message')

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label" for="client_id">Client</label>
                                <select name="client_id" onchange="handleClientChange(this.value)" autofocus id="client_id"
                                    value="" data-select2-id="client_id" class="form-control select2 w-100" required>
                                    <option value="">Choose Client</option>
                                    <option value="{{ $client->id }}" @if ($client_id == $client->id) selected @endif>
                                        {{ $client->name }}</option>
                                    @if ($subAccounts)
                                        @foreach ($subAccounts as $subAccount)
                                            <option value="{{ $subAccount->id }}"
                                                @if ($client_id == $subAccount->id) selected @endif>
                                                {{ $subAccount->name }}
                                            </option>
                                        @endforeach
                                    @endif

                                    @if ($linkedClients->count())
                                        @foreach ($linkedClients as $linkedClient)
                                            @if ($linkedClient->id != $client->id)
                                                <option value="{{ $linkedClient->id }}"
                                                    @if ($client_id == $linkedClient->id) selected @endif>
                                                    {{ $linkedClient->name }}
                                                </option>
                                            @endif
                                        @endforeach
                                    @endif
                                </select>
                                @error('client_id')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror

                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label" for="service_type">Type of Service</label>
                                <select name="service_type" id="service_type" class="form-control select2 w-100" autofocus
                                    required>
                                    <option selected value="">Choose Service Type</option>
                                    @if($client->status != 'Rejected')
                                    <option value="APPLY_ACCOUNT">New Fiat Account</option>
                                    <option value="APPLY_CRYPTO">New Crypto Account</option>
                                    <option value="APPLY_CARD">New Card</option>
                                    <option value="CLOSE_ACCOUNT">Close Account</option>
                                    <option value="BALANCE_CERTIFICATE">Balance Certificate</option>
                                    @else
                                    <option value="CLOSE_ACCOUNT">Close Account</option>
                                    @endif
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4" id="service_currency_list">
                            <div class="form-group">
                                <label class="form-label" for="currency_id">Currency List</label>
                                <select name="currency_id" id="currency_id" class="form-control select2 w-100">
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4" id="service_account_list">
                            <div class="form-group">
                                <label class="form-label" for="account_id">Accounts</label>
                                <select name="account_id" id="account_id" class="form-control select2 w-100">
                                </select>
                            </div>
                        </div>

                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <hr>
                                <button id="submit" type="submit" disabled class="btn btn-primary">Submit</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </form>

    </div>
@section('scripts')
    <script>
        $(document).ready(function() {

            $('#service_type').change(function() {
                $('#submit').prop('disabled', true)
                var selectedServiceType = $(this).val();

                if (selectedServiceType === 'APPLY_ACCOUNT') {
                    $('#service_account_list').hide();
                    $('#account_id').val(null);
                    $('#service_currency_list').show();
                    $('#currency_id').html('<option selected value="">Choose Currency</option>');

                    @if ($account_currency->count() > 0)
                        $('#submit').prop('disabled', false)
                        @foreach ($account_currency as $currency)
                            $('#currency_id').append(
                                '<option value="{{ $currency->id }}">{{ $currency->name }}</option>');
                        @endforeach
                    @else
                        $('#currency_id').html(
                            '<option selected value="">No Currencies Remaining to Request for.</option>'
                        );
                    @endif
                } else if (selectedServiceType === 'APPLY_CRYPTO') {
                    $('#service_account_list').hide();
                    $('#account_id').val(null);
                    $('#service_currency_list').show();
                    $('#currency_id').html('<option selected value="">Choose Currency</option>');

                    @if ($crypto_currency->count() > 0)
                        $('#submit').prop('disabled', false)
                        @foreach ($crypto_currency as $currency)
                            $('#currency_id').append(
                                '<option value="{{ $currency->id }}">{{ $currency->name }}</option>');
                        @endforeach
                    @else
                        $('#currency_id').html(
                            '<option selected value="">No Currencies Remaining to Request for.</option>'
                        );
                    @endif
                } else if (selectedServiceType === 'BALANCE_CERTIFICATE') {
                    $('#service_currency_list').hide();
                    $('#currency_id').val(null);
                    $('#service_account_list').show();
                    $('#account_id').html('<option selected value="">Choose Account</option>');

                    @if ($client->clientAccounts->count() > 0)
                        $('#submit').prop('disabled', false)
                        @foreach ($client->clientAccounts as $account)
                            @if (!in_array($account->id, $accountExists))
                                $('#account_id').append(
                                    '<option value="{{ $account->id }}">{{ $account->title }} - {{$account->account_number}}</option>'
                                );
                            @endif
                        @endforeach
                    @else
                        $('#account_id').html(
                            '<option selected value="">There is no account for the current cliet.</option>'
                        );
                    @endif

                } else {
                    $('#submit').prop('disabled', false)
                    $('#service_currency_list').hide();
                    $('#currency_id').val(null);
                    $('#service_account_list').hide();
                    $('#account_id').val(null);
                }
            });
        });

        function handleClientChange(client_id) {
            window.location.href = "{{ url('/client/services/requests/new/') }}/" + client_id;
        }
    </script>
@stop

@stop
