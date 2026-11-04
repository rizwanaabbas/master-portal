@extends('layouts.cp')
@section('title', 'Add User Card Link - Card Management')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Card Management</span>
        </h1>
        <a href="{{ url('/card-management/link-users/list') }}" class="btn btn-primary mr-3"><i
                class="fal fa-arrow-alt-left"></i> Go Back </a>
    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false"
        data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                Add User Card Link
            </h2>
        </div>

        <form method="post" action="{{ url('/card-management/link-users/update') }}" enctype="multipart/form-data">
            @csrf
            {{-- <input type="hidden" id="assigned_card_ids" name="assigned_card_ids" value=""> --}}
            <div class="panel-container show">
                <div class="panel-content">
                    @include('common.error-message')

                    <div class="row">
                        <div class="col-md-12">
                            <div class="row mt-4">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label" for="client_id">Clients</label>
                                        <select name="client_id" onchange="getLinkedCards(this.value)" autofocus
                                            id="client_id" value="" data-select2-id="client_id"
                                            class="form-control select2 w-100" required>
                                            <option value="" selected>Choose Client</option>
                                            @foreach ($clients as $client)
                                                @if ($client->id == $client_id)
                                                    <option value="{{ $client->id }}" selected>
                                                        {{ $client->name }}
                                                    </option>
                                                @else
                                                    <option value="{{ $client->id }}">{{ $client->name }}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                        @error('client_id')
                                            <span class="invalid-feedback"
                                                role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6 mt-4">
                                    <div class="form-group">
                                        <button type="submit" class="btn btn-primary">Submit</button>
                                    </div>
                                </div>
                                {{-- <div class="col-md-6">
                                    <div class="form-group">
                                        <div class="custom-control custom-switch mt-5">
                                            <input type="checkbox" class="custom-control-input" id="status"
                                                @if ($link->status) checked @endif
                                                id="status"value="1" name="status">
                                            <label class="custom-control-label" for="status">Active</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-4"> --}}
                                {{-- <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label" for="assigned_card_id">Card Holder</label>
                                        <select name="assigned_card_id" autofocus id="assigned_card_id" value=""
                                            data-select2-id="assigned_card_id" class="form-control select2 w-100" required>
                                            <option value="" selected>Choose Card Holder</option>
                                            @foreach ($cardUsers as $cardUser)
                                                @if ($cardUser->id == $link->assigned_card_id)
                                                    <option value="{{ $cardUser->id }}" selected>
                                                        {{ $cardUser->cardHolderName . ' (' . $cardUser->cardNumber . ')' }}
                                                    </option>
                                                @else
                                                    <option value="{{ $cardUser->id }}">
                                                        {{ $cardUser->cardHolderName . ' (' . $cardUser->cardNumber . ')' }}
                                                    </option>
                                                @endif
                                            @endforeach
                                        </select>
                                        @error('assigned_card_id')
                                            <span class="invalid-feedback"
                                                role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div> --}}
                                {{-- <div class="col-md-5">
                                    <div class="form-group">
                                        <label class="form-label" for="password">Password</label>
                                        <input type="password" autofocus id="password" name="password"
                                            value="{{ $link->password }}" class="form-control" required>
                                        @error('password')
                                            <span class="invalid-feedback"
                                                role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-1">
                                    <div class="form-group">
                                        <div class="form-group">
                                            <div class="custom-control custom-switch mt-5">

                                            <input type="checkbox" class="custom-control-input"
                                                    onclick="togglePasswordVisibility()" id="toggle-visibility"
                                                    value="1">
                                                <label class="custom-control-label" id="toggle-visibility-label"
                                                    for="toggle-visibility">Show</label>
                                            </div>
                                        </div>
                                    </div>
                                </div> --}}
                            </div>
                            <div class="row mt-4">
                                <div class="col-md-12">
                                    <table class="table table-bordered table-hover m-0 shadow" id="data-grid">
                                        <thead class="thead-themed">
                                            <tr>
                                                <th width="30">Select</th>
                                                <th>Card Holder Name</th>
                                                <th>Card Number</th>
                                                <th>Card Type</th>
                                                <th>Provider</th>
                                                <th>Status</th>
                                                <th width="30">Hide Balance</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @if ($clientCards)
                                                @foreach ($clientCards as $clientCard)
                                                    {{-- @if (in_array($clientCard->id, $linkedIds)) --}}
                                                    {{-- @php //dd($clientCard);@endphp --}}
                                                    <tr>
                                                        <td align="center" scope="row">



                                                            <input type="checkbox"
                                                                onclick="enableHideBalanceInput(this.checked, {{ $clientCard->id }})"
                                                                name="linked_card_ids[]" checked
                                                                value="{{ $clientCard->id }}"
                                                                @if ($clientCard->provider === 'match_move') style="display:none;" @endif />

                                                        </td>
                                                        <td>{{ $clientCard->cardHolderName }}</td>
                                                        <td>{{ $clientCard->cardNumber }}</td>
                                                        <td>{{ $clientCard->cType }}</td>
                                                        <td>{{ ucwords(str_replace('_', ' ', $clientCard->provider)) }}
                                                        </td>
                                                        <td align="center">
                                                            <span
                                                                class="badge {{ $statusBadge[$clientCard->status] }}">{{ $clientCard->status }}</span>
                                                        </td>
                                                        <td align="center">
                                                            <input type="checkbox"
                                                                @if ($clientCard->link->hide_balance) checked @endif
                                                                id="hide_balance_{{ $clientCard->id }}"
                                                                name="hide_balance_ids[]" value="{{ $clientCard->id }}" />
                                                        </td>
                                                    </tr>
                                                    {{-- @php
                                                        unset($assignedCards[$loop->iteration - 1]);
                                                        @endphp --}}
                                                    {{-- @endif --}}
                                                @endforeach
                                            @endif
                                            @if ($unlinkedCards)
                                                @foreach ($unlinkedCards as $unlinkedCard)
                                                    <tr>
                                                        <td align="center" scope="row">
                                                            @if ($unlinkedCard->provider !== 'match_move')
                                                                <input type="checkbox"
                                                                    onclick="enableHideBalanceInput(this.checked, {{ $unlinkedCard->id }})"
                                                                    name="linked_card_ids[]"
                                                                    value="{{ $unlinkedCard->id }}" />
                                                            @endif
                                                        </td>
                                                        <td>{{ $unlinkedCard->cardHolderName }}</td>
                                                        <td>{{ $unlinkedCard->cardNumber }}</td>
                                                        <td>{{ $unlinkedCard->cType }}</td>
                                                        <td>{{ ucwords(str_replace('_', ' ', $unlinkedCard->provider)) }}
                                                        </td>
                                                        <td align="center">
                                                            <span
                                                                class="badge {{ $statusBadge[$unlinkedCard->status] }}">{{ $unlinkedCard->status }}</span>
                                                        </td>
                                                        <td align="center">
                                                            <input type="checkbox"id="hide_balance_{{ $unlinkedCard->id }}"
                                                                disabled name="hide_balance_ids[]"
                                                                value="{{ $unlinkedCard->id }}" />
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            @else
                                                <tr>
                                                    <td colspan="6" align="center">No records found.</td>
                                                </tr>
                                            @endif
                                        </tbody>
                                    </table>
                                </div>
                                {{-- </div>
                                <div class="row mt-2">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <div class="custom-control custom-switch mt-5">
                                                <input type="checkbox" class="custom-control-input" id="hide_balance"
                                                    @if ($link->hide_balance) checked @endif
                                                    id="hide_balance"value="1" name="hide_balance">
                                                <label class="custom-control-label" for="hide_balance">Hide Balance</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div> --}}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

@stop
@section('scripts')
    <script>
        // var idsObject = '@php echo json_encode($linkedIds) @endphp'

        // var var_assigned_card_ids = []
        // idsObject.map((x) => {
        //     var_assigned_card_ids.push(x)
        // })
        // console.log(var_assigned_card_ids)

        $(document).ready(function() {


            $('#data-grid').dataTable({
                responsive: true,
                lengthChange: false,
                searching: false,
                paging: false
            });

            $('.js-thead-colors a').on('click', function() {
                var theadColor = $(this).attr("data-bg");
                console.log(theadColor);
                $('#dt-basic-example thead').removeClassPrefix('bg-').addClass(theadColor);
            });

            $('.js-tbody-colors a').on('click', function() {
                var theadColor = $(this).attr("data-bg");
                console.log(theadColor);
                $('#dt-basic-example').removeClassPrefix('bg-').addClass(theadColor);
            });
        });

        function getLinkedCards(client_id) {
            window.location.href = "{{ url('/card-management/link-users/edit') }}" + "/" + client_id
        }

        function enableHideBalanceInput(isChecked, targetId) {
            if (isChecked) {
                // var_assigned_card_ids.push(targetId)
                // // console.log(typeof assigned_card_ids)
                // console.log(var_assigned_card_ids)
                document.getElementById(`hide_balance_${targetId}`).disabled = false
            } else {
                document.getElementById(`hide_balance_${targetId}`).disabled = true
            }
        }

        // function togglePasswordVisibility() {
        //     let element = document.getElementById('password')
        //     let label = document.getElementById('toggle-visibility-label')

        //     if (element.type === 'password') {
        //         element.type = 'text'
        //         label.innerHTML = 'Hide'
        //     } else {
        //         element.type = 'password'
        //         label.innerHTML = 'Show'
        //     }
        // }
    </script>
@stop
