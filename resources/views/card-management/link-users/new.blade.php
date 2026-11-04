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

        <form method="post" action="{{ url('/card-management/link-users/store') }}" enctype="multipart/form-data">
            @csrf
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
                                                <option value="{{ $client->id }}">
                                                    {{ $client->name }}
                                                </option>
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
                                                <th>Status</th>
                                                <th width="30">Hide Balance</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @if ($unlinkedCards)
                                                @foreach ($unlinkedCards as $unlinkedCard)
                                                    <tr>
                                                        <td align="center" scope="row">
                                                            <input type="checkbox"
                                                                onclick="enableHideBalanceInput(this.checked, {{ $unlinkedCard->id }})"
                                                                @if ($unlinkedCard->id == $assigned_card_id) checked @endif
                                                                name="linked_card_ids[]" value="{{ $unlinkedCard->id }}" />
                                                        </td>
                                                        <td>{{ $unlinkedCard->cardHolderName }}</td>
                                                        <td>{{ $unlinkedCard->cardNumber }}</td>
                                                        <td>{{ $unlinkedCard->cType }}</td>
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
        $(document).ready(function() {


            $('#data-grid').dataTable({
                responsive: true,
                lengthChange: false,
                searching: false,
                paging: false,
                stateSave: true
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
                document.getElementById(`hide_balance_${targetId}`).disabled = false
            } else {
                document.getElementById(`hide_balance_${targetId}`).disabled = true
            }
        }
    </script>
@stop
