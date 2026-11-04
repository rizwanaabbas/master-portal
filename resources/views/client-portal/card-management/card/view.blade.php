@extends('layouts.cp')
@section('title', 'Client Card Details - Client Card Management')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Client Card Management</span>
        </h1>
        {{-- <a href="{{ url('/card-management/client-members/new') }}" class="btn btn-primary mr-3"><i
                class="fal fa-plus-circle"></i> Add Member </a> --}}
    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false"
        data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                Client Card Details
            </h2>

        </div>

        <div class="panel-container show mb-3">
            <div class="panel-content" style="overflow: scroll;">
                @include('common.error-message')

                <input type="hidden" id="current_card_id" value="{{ $id }}" />
                <table class="table m-0 shadow" id="data-grid">
                    <tbody>
                        <tr>
                            <td>
                                <div class="d-flex justify-content-center">
                                    <h4 style="margin-top: 10px;">Current Card..</h4>
                                    <div class="col-sm-12 col-md-4 col-lg-2 col-xl-2 col-xxl-2">
                                        <div class="form-group">
                                            <select onchange="getCardDetails(this.value);" name="card_id" autofocus
                                                id="card_id" value="" data-select2-id="card_id"
                                                class="form-control select2 w-100" required>
                                                <option value="">Choose Card</option>
                                                @foreach ($linkedCards as $card)
                                                    @if ($id !== null && $card->id == $id)
                                                        <option value="{{ $card->id }}" selected>
                                                            {{ $card->cardDetails->cardNumber }}
                                                        </option>
                                                    @else
                                                        <option value="{{ $card->id }}">{{ $card->cardDetails->cardNumber }}
                                                        </option>
                                                    @endif
                                                @endforeach
                                            </select>
                                            @error('card_id')
                                                <span class="invalid-feedback"
                                                    role="alert"><strong>{{ $message }}</strong></span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="row">
                                    <div class="col-sm-12 col-md-12 col-lg-12 col-xl-6 mx-auto">
                                        <div class="row">
                                            <div class="col-sm-9 col-md-7 col-lg-6 col-xl-7 col-xxl-6 mx-auto">
                                                <div class="p-3 rounded vista-card detailed-member-card overflow-hidden position-relative text-white mb-g"
                                                    style="width: 350px;height:200px;">
                                                    <div class="">
                                                        @if ($isBalanceHidden)
                                                            <span class="display-4 d-block l-h-n m-0 fw-500">&nbsp;</span>
                                                        @else
                                                            <h3 class="display-4 d-block l-h-n m-0 fw-500">
                                                                &nbsp;<span name="balance_element">
                                                                    <?= '<?xml version="1.0" encoding="utf-8"?>' ?>
                                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                                        xmlns:xlink="http://www.w3.org/1999/xlink"
                                                                        style="background: none; display: block; float: left; margin-top: 10px; shape-rendering: auto;"
                                                                        width="28px" height="28px" viewBox="0 0 100 100"
                                                                        preserveAspectRatio="xMidYMid">
                                                                        <circle cx="50" cy="50" fill="none"
                                                                            stroke="#ffc800" stroke-width="13" r="38"
                                                                            stroke-dasharray="179.0707812546182 61.690260418206066">
                                                                            <animateTransform attributeName="transform"
                                                                                type="rotate" repeatCount="indefinite"
                                                                                dur="0.625s" values="0 50 50;360 50 50"
                                                                                keyTimes="0;1"></animateTransform>
                                                                        </circle>
                                                                    </svg>
                                                                </span>
                                                                {{ $cardDetails->currency }}&nbsp;
                                                            </h3>
                                                        @endif
                                                        <h4 class="vista-card-title">{{ $cardDetails->cardNumber }}</h4>
                                                        <p class="m-0 l-h-n vista-card-title">
                                                            @if ($cardDetails->cType != 'Instant Card')
                                                                {{ $cardDetails->cardHolderName }}&nbsp;
                                                            @endif
                                                        </p>
                                                    </div>
                                                    <i class="position-absolute pos-right pos-bottom opacity-15 mb-n1 mr-n4"
                                                        style="font-size: 6rem;"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-12 col-md-12 col-lg-12 col-xl-6">
                                        <table class="mx-auto">
                                            <tbody>
                                                <tr>
                                                    <td style="vertical-align: middle;"><strong>Card Status:</strong>
                                                        <span
                                                            class="badge {{ $statusBadge[$cardDetails->cardUserStatus] }}">{{ $cardDetails->cardUserStatus }}</span>
                                                    </td>
                                                    <td>
                                                        @php
                                                            $status_mapping = [
                                                                'Active' => 'Unblocked',
                                                                'Locked' => 'Blocked',
                                                                'Inactive' => 'Blocked',
                                                            ];
                                                            $inverse_mapping = [
                                                                'Active' => 'Block',
                                                                'Locked' => 'Unblock',
                                                                'Inactive' => 'Unblock',
                                                            ];
                                                        @endphp
                                                        Your card is <span
                                                            class="badge {{ $statusBadge[$cardDetails->cardStatus] }}">
                                                            {{ $status_mapping[$cardDetails->cardStatus] }}
                                                        </span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td style="vertical-align: middle;">
                                                        @if ($cardDetails->cType != 'Instant Card')
                                                            <strong>Card Holder Name:</strong>
                                                            {{ $cardDetails->cardHolderName }}
                                                        @endif
                                                    </td>
                                                    <td style="border-top-width: 0px;">
                                                        <button type="button"
                                                            onclick="blockUnblockCard('{{ $cardDetails->cardStatus }}')"
                                                            class="btn {{ $cardDetails->cardStatus == 'Active' ? 'btn-danger' : 'btn-success' }} btn-card-details">
                                                            {{ $inverse_mapping[$cardDetails->cardStatus] }}
                                                        </button>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td style="vertical-align: middle;"><strong>Card Number:</strong>
                                                        {{ $cardDetails->cardNumber }}</td>
                                                    <td>
                                                        <button type="button" onclick="changePin();"
                                                            class="btn btn-danger btn-card-details">
                                                            Change Pin
                                                        </button>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td style="vertical-align: middle;"><strong style="float: left;">
                                                            @if ($isBalanceHidden)
                                                                <span>&nbsp;</span>
                                                            @else
                                                                Balance:
                                                        </strong>&nbsp;
                                                        <span name="balance_element">
                                                            <?= '<?xml version="1.0" encoding="utf-8"?>' ?>
                                                            <svg xmlns="http://www.w3.org/2000/svg"
                                                                xmlns:xlink="http://www.w3.org/1999/xlink"
                                                                style="background: none; display: block; float: left; shape-rendering: auto;"
                                                                width="28px" height="20px" viewBox="0 0 100 100"
                                                                preserveAspectRatio="xMidYMid">
                                                                <circle cx="50" cy="50" fill="none"
                                                                    stroke="#ffc800" stroke-width="13" r="38"
                                                                    stroke-dasharray="179.0707812546182 61.690260418206066">
                                                                    <animateTransform attributeName="transform"
                                                                        type="rotate" repeatCount="indefinite"
                                                                        dur="0.625s" values="0 50 50;360 50 50"
                                                                        keyTimes="0;1"></animateTransform>
                                                                </circle>
                                                            </svg>
                                                        </span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <button type="button" onclick="topup('{{ $id }}');"
                                                            class="btn btn-danger btn-card-details">
                                                            Top-up
                                                        </button>
                                                    </td>
                                                </tr>

                                                {{-- <tr>
                                                    <td>
                                                        <div class="form-group">
                                                            <div class="custom-control custom-switch">
                                                                <input type="checkbox"
                                                                    onclick="blockUnblockCard(this.checked)"
                                                                    class="custom-control-input" id="status"
                                                                    {{ $cardDetails->cardStatus == 'Active' ? 'checked' : '' }}
                                                                    value="" name="status">
                                                                <label class="custom-control-label"
                                                                    for="status">{{ $cardDetails->cardStatus }}</label>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr> --}}

                                                {{-- <tr>
                                                    <td>
                                                        <button type="button" onclick="toggleBalanceVisibility();"
                                                            id="toggle-visibility-button"
                                                            class="btn btn-warning btn-card-details">
                                                            Show Balance
                                                        </button>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <button type="button" onclick="changePassword();"
                                                            class="btn btn-danger btn-card-details">
                                                            Change Password
                                                        </button>
                                                    </td>
                                                </tr> --}}
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    {{--
    <form action="{{ url('/client/card-management/card/block') }}" id="block_card_form" method="post">
        @csrf
        <input type="hidden" name="username" value="{{ $cardDetails->userName }}">
    </form> --}}

    <div class="modal fade" id="block_modal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="block_modal_title">Block Card Request</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true"><i class="fal fa-times"></i></span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="{{ url('/client/card-management/card/block') }}" method="post">
                        @csrf
                        <input type="hidden" name="updatable" value="Block">
                        <input type="hidden" name="card_id" value="{{ $id }}">
                        <div class="form-group">
                            <label class="form-label" for="request_reason">Reason</label>
                            <textarea required class="form-control" name="request_reason" id="block_request_reason" cols="30"
                                rows="2"></textarea>
                        </div>
                        {{-- <div class="form-group">
                            <label class="form-label" for="need_replacement">Need Replacement</label>
                            <input type="checkbox" class="custom-control-input" name="need_replacement">
                        </div> --}}

                        <div class="form-group">
                            <button type="submit" id="block_modal_submit" class="btn btn-danger">Block</button>
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>

    <div class="modal fade" id="unblock_modal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="unblock_modal_title">Unblock Card Request</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true"><i class="fal fa-times"></i></span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="{{ url('/client/card-management/card/unblock') }}" method="post">
                        @csrf
                        <input type="hidden" name="updatable" value="Unblock">
                        <input type="hidden" name="card_id" value="{{ $id }}">
                        <div class="form-group">
                            <label class="form-label" for="request_reason">Reason</label>
                            <textarea required class="form-control" name="request_reason" id="unblock_request_reason" cols="30"
                                rows="2"></textarea>
                        </div>

                        <div class="form-group">
                            <button type="submit" id="unblock_modal_submit" class="btn btn-danger">Unblock</button>
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>

    <div class="modal fade" id="change_pin_modal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title ">Change PIN</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true"><i class="fal fa-times"></i></span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="{{ url('/client/card-management/card/change-pin') }}" method="post">
                        @csrf
                        <input type="hidden" name="card_id" value="{{ $id }}">
                        {{-- <div class="form-group">
                            <label class="form-label" for="current_pin">Current PIN</label>
                            <input type="pin" required class="form-control" name="current_pin"
                                id="current_pin" />
                        </div> --}}
                        <div class="form-group">
                            <label class="form-label" for="new_pin">New PIN</label>
                            <input type="password" maxlength="4" pattern="\d{4}" max="4" required
                                class="form-control number-only" name="new_pin" id="new_pin" />
                            <small id="new_pin_message" class="text-danger"></small>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="retype_pin">Retype PIN</label>
                            <input type="password" maxlength="4" pattern="\d{4}" max="4" required
                                class="form-control number-only" name="retype_pin" id="retype_pin" />
                            <small id="retype_pin_message" class="text-danger"></small>
                        </div>
                        <div class="form-group">
                            <div class="custom-control custom-switch mt-5">
                                <input type="checkbox" class="custom-control-input"
                                    onclick="toggleSecretVisibility('new_pin', 'retype_pin', 'toggle-pin-visibility-label', 'PIN')"
                                    id="toggle-pin-visibility" value="1">
                                <label class="custom-control-label" id="toggle-pin-visibility-label"
                                    for="toggle-pin-visibility">Show PIN</label>
                            </div>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-danger">Change PIN</button>
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>

@stop
@section('scripts')
    <script>
        $(document).ready(function() {
            const card_id = document.getElementById('current_card_id').value
            const isBalanceHidden = Number('@php echo $isBalanceHidden; @endphp')

            if (!isBalanceHidden) {
                $.getJSON("{{ url('/client/card-management/card/get-balance') }}" + "/" + card_id, function(
                    jsonData) {
                    $("[name='balance_element']").html(jsonData);
                })
            }
        });

        function getCardDetails(id) {
            window.location.href = "{{ url('/client/card-management/card/view') }}/" + id
        }

        // function toggleBalanceVisibility() {
        //     let element = document.getElementById('balance_element')
        //     let button = document.getElementById('toggle-visibility-button')

        //     if (element.innerHTML === '***') {
        //         element.innerHTML = '@php echo $balance; @endphp'
        //         button.innerHTML = 'Hide Balance'
        //     } else {
        //         element.innerHTML = '***'
        //         button.innerHTML = 'Show Balance'
        //     }
        // }

        $("input.number-only").bind({
            keydown: function(e) {
                if (e.shiftKey === true) {
                    if (e.which == 9) {
                        return true;
                    }
                    return false;
                }
                if (e.which > 57) {
                    return false;
                }
                if (e.which == 32) {
                    return false;
                }
                return true;
            }
        });

        function blockModal() {
            $("#block_modal").modal('show');
        }

        function unblockModal() {
            $("#unblock_modal").modal('show');
        }

        function changePin() {
            $("#change_pin_modal").modal('show');
        }

        function topup(id) {
            return window.location.replace("{{ url('/client/card-management/card/topups/new') }}/" + id);
        }

        function toggleSecretVisibility(field1Id, field2Id, labelId, labelText) {
            const elements = [
                document.getElementById(field1Id),
                document.getElementById(field2Id)
            ]
            const label = document.getElementById(labelId)
            const toggles = {
                'password': {
                    'type': 'text',
                    'needle': 'Show'
                },
                'text': {
                    'type': 'password',
                    'needle': 'Hide'
                }
            }

            elements.map(element => {
                element.type = toggles[element.type]['type']
            })
            label.innerHTML = `${toggles[elements[0].type]['needle']} ${labelText}`
        }

        function blockUnblockCard(isChecked) {
            let statusArray = {
                'Active': 'block',
                'Locked': 'unblock',
                'Inactive': 'unblock'
            }
            const status = statusArray['@php echo $cardDetails->cardStatus @endphp']

            Swal.fire({
                title: "Are you sure?",
                text: `You are about to ${status} your Card. Do you want to proceed?`,
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: `Yes, ${status}!`
            }).then(function(result) {
                if (result.value) {
                    if (status == 'unblock') {
                        unblockModal()
                    } else {
                        blockModal()
                    }
                } else {
                    document.getElementById('status').checked = !isChecked
                }
            });
        }
    </script>
@stop
