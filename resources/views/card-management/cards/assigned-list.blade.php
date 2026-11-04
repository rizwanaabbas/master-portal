@extends('layouts.cp')
@section('title', 'Assigned Cards - Card Management')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Client Card Management</span>
        </h1>
        {{-- <a href="{{ url('/card-management/cards/sync-unassigned-cards') }}" class="btn btn-primary mr-3">
            <i class="fal fa-sync"></i> Sync </a> --}}
        @if ($match_move_enabled == 1)
            <a href="javascript:void(0)" onclick="showMainWalletBalance('match_move')" class="btn btn-primary mr-3">
                <i class="fal fa-money-bill-alt"></i> Show MatchMove Wallet Balance </a>
        @endif
        @if ($emoney_enabled == 1)
            <a href="javascript:void(0)" onclick="showMainWalletBalance('e_money')" class="btn btn-primary mr-3">
                <i class="fal fa-money-bill-alt"></i> Show E-Money Wallet Balance </a>
        @endif

    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false"
        data-panel-color="false" data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                Assigned Cards
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
                            <th>ID</th>
                            <th>Card Holder Name</th>
                            <th>Username</th>
                            <th>Card Number</th>
                            <th>Card Type</th>
                            <th>Program Type</th>
                            <th>Provider</th>
                            <th>Status</th>
                            <th>Statement</th>
                            <th>Balance</th>
                            <th>Created At</th>
                            <th width="30">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if ($assignedCards)
                            @php $inverse_mapping = ['Active' => 'Block', 'Locked' => 'Unblock', 'Inactive' => 'Unblock', 'Cancelled' => 'Cancelled', 'Waiting For Activation' => 'Waiting For Activation']; @endphp


                            @foreach ($assignedCards as $assignedCard)
                                <tr>
                                    <td align="center" scope="row"><a
                                            href="{{ url('/card-management/cards/view/' . $assignedCard->id) }}"><i
                                                class="fal fa-tv"></i></a></td>
                                    <td>
                                        @if (!empty($assignedCard->link))
                                            {{ $assignedCard->link->detail_id }}
                                        @endif
                                    </td>
                                    <td>{{ $assignedCard->cardHolderName }}</td>
                                    <td>{{ $assignedCard->userName }}</td>
                                    <td>{{ $assignedCard->cardNumber }}</td>
                                    <td>{{ $assignedCard->cType }}
                                        ({{ $assignedCard->physical == 1 ? 'Physical' : 'Virtual' }})
                                    </td>
                                    <td>{{ $assignedCard->programType }}</td>
                                    <td>{{ ucwords(str_replace('_', ' ', $assignedCard->provider)) }}</td>
                                    <td>
                                        <span
                                            class="badge {{ $statusBadge[$assignedCard->status] }}">{{ $assignedCard->status }}</span>
                                    </td>

                                    <td>
                                        <div class="d-flex justify-content-center w-100">
                                            @if ($assignedCard->status != 'Pending' && $assignedCard->status != 'Inactive')
                                                {{-- <span class="btn cursor-pointer"
                                                    onclick="handleDownloadStatement({{ $assignedCard->id }})"> --}}
                                                <a href="{{ url('/card-management/cards/download-statement/' . $assignedCard->id) }}"
                                                    class="btn cursor-pointer" target="_blank">
                                                    <i class="fal fa-download font-weight-900"></i>
                                                </a>
                                                {{-- </span> --}}
                                            @endif
                                        </div>
                                    </td>

                                    <td class="d-flex justify-content-between">
                                        <span id="{{ $assignedCard->id }}_balance_element" class="mt-2">***</span>
                                        <span id="{{ $assignedCard->id }}_balance_toggle" title="Show card balance."
                                            onclick="getCardBalance('{{ $assignedCard->id }}')" class="btn cursor-pointer">
                                            <i class="font-weight-900 fal fa-eye"
                                                id="{{ $assignedCard->id }}_balance_toggle_icon"></i>
                                        </span>
                                        <input type="hidden" id="{{ $assignedCard->id }}_show_balance" value="Show" />
                                    </td>
                                    <td>{{ \Carbon\Carbon::parse($assignedCard->card_created_at)->format('d M, Y') }}</td>

                                    <td>
                                        <div class="d-flex gap-1">
                                            @if (!empty($assignedCard->status) && $assignedCard->status != 'Pending' && $assignedCard->status != 'Inactive')
                                                <span
                                                    @if (isset($inverse_mapping[$assignedCard->status])) title=" {{ $inverse_mapping[$assignedCard->status] }}  this Card!" @endif
                                                    onclick="blockUnblockCard('{{ $assignedCard->status }}', '{{ $assignedCard->id }}')"
                                                    class="btn btn-sm btn-outline-primary cursor-pointer  mr-2">
                                                    <i
                                                        class="fal fa-{{ $assignedCard->status == 'Active' ? 'lock' : 'unlock' }} font-weight-900"></i>
                                                </span>
                                            @endif


                                        </div>
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
                    <form action="{{ url('/card-management/cards/block') }}" method="post">
                        @csrf
                        <input type="hidden" name="updatable" value="Block">
                        <input type="hidden" name="id" id="block_id" value="Unblock">
                        <div class="form-group">
                            <label class="form-label" for="request_reason">Reason</label>
                            <textarea required class="form-control" name="request_reason" id="request_reason" cols="30" rows="2"></textarea>
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
                    <form action="{{ url('/card-management/cards/unblock') }}" method="post">
                        @csrf
                        <input type="hidden" name="updatable" value="Unblock">
                        <input type="hidden" name="id" id="unblock_id" value="Unblock">
                        <div class="form-group">
                            <label class="form-label" for="request_reason">Reason</label>
                            <textarea required class="form-control" name="request_reason" id="request_reason" cols="30" rows="2"></textarea>
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

    <form id="download_statement_form" method="post" action="{{ url('/card-management/cards/download-statement') }}">
        @csrf
        <input type="hidden" id="card_id" name="card_id" value="">
    </form>
    @if ($match_move_enabled == 1)
        <div class="modal fade" id="matchmove-account-balance-modal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title " id="balanace-model-title">Match Move Wallet Balance</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true"><i class="fal fa-times"></i></span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group" id="matchmove-account-balances">

                        </div>

                        <div class="form-group">

                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        </div>

                    </div>

                </div>
            </div>
        </div>
    @endif
@stop
@section('scripts')
    <script>
        $(document).ready(function() {
            $('#data-grid').dataTable({
                responsive: true
            });
        });

        function showMainWalletBalance(provider) {
            $('#loading-spinner').show();
            $.get("{{ url('/card-management/cards/wallet/balance') }}/" + provider,
                function(data, status) {

                    // data = JSON.parse(data);
                    console.log(data.balance);
                    let formatted_provider = provider
                        .replace(/_/g, " ") // replace underscores with spaces
                        .replace(/\b\w/g, c => c.toUpperCase());
                    $('#matchmove-account-balances').html('');
                    $('#balanace-model-title').html(formatted_provider + ' Wallet Balance');
                    var cardHtml = `
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <h5>Balance: </h5>
                        </div>
                        <div class="col-md-4">
                            <p><strong>${data.balance} ${data.currency}</strong></p>
                        </div>

                    </div>
                `;
                    $('#matchmove-account-balances').append(cardHtml);


                    $("#matchmove-account-balance-modal").modal('show');
                    $('#loading-spinner').hide();
                });
        }

        function getCardBalance(id) {
            const showBalance = document.getElementById(id + '_show_balance').value

            if (showBalance === 'Show') {

                document.getElementById(id + '_balance_element').innerHTML =
                    '<?= '\<\?xml version="1.0" encoding="utf-8" ?\>' ?>' +
                    '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"' +
                    'style="background: none; display: block; float: left; margin-bottom: -2px; shape-rendering: auto;"' +
                    'width="22px" height="22px" viewBox="0 0 100 100"' +
                    'preserveAspectRatio="xMidYMid">' +
                    '<circle cx="50" cy="50" fill="none" stroke="#ffc800"' +
                    'stroke-width="13" r="38"' +
                    'stroke-dasharray="179.0707812546182 61.690260418206066">' +
                    '<animateTransform attributeName="transform" type="rotate"' +
                    'repeatCount="indefinite" dur="0.625s" values="0 50 50;360 50 50"' +
                    'keyTimes="0;1"></animateTransform>' +
                    '</circle>' +
                    '</svg>'

                $.getJSON("{{ url('/card-management/card/get-balance') }}" + "/" + id,
                    function(jsonData) {
                        document.getElementById(id + '_balance_element').innerHTML = jsonData + ' USD'
                        document.getElementById(id + '_show_balance').value = 'Hide'
                        document.getElementById(id + '_balance_toggle_icon').classList.remove("fa-eye")
                        document.getElementById(id + '_balance_toggle_icon').classList.add("fa-eye-slash")
                    }
                )
            } else {
                document.getElementById(id + '_balance_element').innerHTML = '***'
                document.getElementById(id + '_show_balance').value = 'Show'
                document.getElementById(id + '_balance_toggle_icon').classList.remove("fa-eye-slash")
                document.getElementById(id + '_balance_toggle_icon').classList.add("fa-eye")
            }
            document.getElementById(id + '_balance_toggle').title = `${showBalance} card balance.`
        }

        function verifyKYC(detail_id, customer_id, client_id) {
            Swal.fire({
                title: "Are you sure?",
                text: `You are about to resubmit documents. Do you want to proceed?`,
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: `Yes, resubmit!`
            }).then(function(result) {
                console.log(result);
                if (result.value) {
                    $.get(`/card-management/cards/verify/documents/${detail_id}/${customer_id}/${client_id}`,
                        function(data, status) {
                            console.log("documents:", data);
                            if (!data.IsInApproval) {
                                $('.kyc-error-messages').html('<div class="alert alert-warning">' + data
                                    .ErrorCode + ': ' + data.ErrorDescription + '</div>');
                            } else {
                                $('.kyc-error-messages').html(
                                    '<div class="alert alert-success">Documents verified successfully.</div>'
                                );
                            }
                        }).fail(function(xhr) {
                        console.error("Error:", xhr.responseText);
                    });
                }
            });
        }

        function blockModal() {
            $("#block_modal").modal('show');
        }

        function unblockModal() {
            $("#unblock_modal").modal('show');
        }

        function handleDownloadStatement(id) {
            document.getElementById('card_id').value = id
            document.getElementById('download_statement_form').submit()
        }

        function blockUnblockCard(cardStatus, id) {
            let statusArray = {
                'Active': 'block',
                'Locked': 'unblock',
                'Inactive': 'unblock'
            }
            const status = statusArray[cardStatus]

            Swal.fire({
                title: "Are you sure?",
                text: `You are about to ${status} your Card. Do you want to proceed?`,
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: `Yes, ${status}!`
            }).then(function(result) {
                if (result.value) {
                    document.getElementById(`${status}_id`).value = id
                    if (status == 'unblock') {
                        unblockModal()
                    } else {
                        blockModal()
                    }
                }
            });
        }
    </script>
@stop
