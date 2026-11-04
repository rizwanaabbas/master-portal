@extends('layouts.cp')
@section('title', 'Unassigned Cards - Card Management')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Client Card Management</span>
        </h1>

        <a href="{{ url('/card-management/cards/sync-unassigned-cards') }}" class="btn btn-primary mr-3">
            <i class="fal fa-sync"></i> Sync </a>
    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false"
        data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                Unassigned Cards
            </h2>

        </div>

        <div class="panel-container show mb-3">
            <div class="panel-content" style="overflow: scroll;">
                @include('common.error-message')
                <div class="kyc-error-messages"></div>

                <table class="table table-bordered table-hover m-0 shadow" id="data-grid">
                    <thead class="thead-themed">
                        <tr>
                            <th width="30"></th>
                            <th>Card Code</th>
                            <th>Masked Card Number</th>
                            <th>Card Type</th>
                            <th>Provider</th>
                            <th>Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if ($unassignedCards)
                            @foreach ($unassignedCards as $unassignedCard)
                                <tr>
                                    <td align="center" scope="row">
                                        <div class="d-flex gap-1">
                                            <span title="Assign this Card to a Client"
                                                onclick="assignCard('{{ $unassignedCard->card_code }}')"
                                                class="cursor-pointer">
                                                <i class="fal fa-2x fa-address-card font-weight-200"></i>
                                            </span>
                                        </div>
                                    </td>
                                    <td>{{ $unassignedCard->card_code }}</td>
                                    <td>{{ $unassignedCard->masked_card_number }}</td>
                                    <td>{{ $unassignedCard->card_type }}</td>
                                    <td>{{ ucwords(str_replace('_', ' ', $unassignedCard->provider)) }}</td>
                                    <td>{{ \Carbon\Carbon::parse($unassignedCard->created_at)->format('d M, Y') }}</td>
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

    <div class="modal fade" id="assign_card" role="dialog" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title ">Assign Card to a Client</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span><i class="fal fa-times"></i></span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="assign_card_form" method="post"
                        action="{{ url('/card-management/link-users/assign-and-link') }}" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="card_code" id="card_code" value="">
                        <div class="form-group">
                            <label class="form-label" for="client_id">Clients</label>
                            <select name="client_id" autofocus id="client_id" value="" data-select2-id="client_id"
                                class="form-control select2" required>
                                <option value="" selected>Choose Client</option>
                                @foreach ($clients as $client)
                                    <option value={{ $client->id }}>
                                        {{ $client->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('client_id')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                        <div class="form-group">
                            <button type="button" onclick="handleAssignCard()" id="assign_button"
                                class="btn btn-secondary">Assign</button>
                            <span id="assigning_card_loader">
                                <?= '<?xml version="1.0" encoding="utf-8"?>' ?>
                                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
                                    style="background: none; shape-rendering: auto;" width="28px" height="28px"
                                    viewBox="0 0 100 100" preserveAspectRatio="xMidYMid">
                                    <circle cx="50" cy="50" fill="none" stroke="#ffc800" stroke-width="13"
                                        r="38" stroke-dasharray="179.0707812546182 61.690260418206066">
                                        <animateTransform attributeName="transform" type="rotate" repeatCount="indefinite"
                                            dur="0.625s" values="0 50 50;360 50 50" keyTimes="0;1"></animateTransform>
                                    </circle>
                                </svg>
                            </span>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@stop
@section('scripts')
    <script>
        let sessionClientId = "{{ session('client_id') ?? '' }}";

        $(document).ready(function() {
            $('#data-grid').dataTable({
                responsive: true
            });
            $('#assigning_card_loader').hide();
        });

        function assignCard(card_code) {
            $("#card_code").val(card_code);
            $("#assign_card").modal('show');

            if (sessionClientId) {
                $('#client_id').val(sessionClientId).trigger('change');
                $('#client_id').prop('disabled', true);
            } else {
                $('#client_id').prop('disabled', false);
            }
        }

        function handleAssignCard() {
            $("#assign_button").prop('disabled', true);
            $("#assigning_card_loader").show();
            $("#assign_card_form").submit();
        }
        $(document).ready(function() {
            $('#assign_card').on('shown.bs.modal', function() {
                $('#client_id').select2({
                    dropdownParent: $('#assign_card') // Ensure dropdown is inside modal
                });
            });
        });
    </script>
@stop
