@extends('layouts.cp')
@section('title', 'Assigned Card Details - Card Management')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Card Management</span>
        </h1>
        <div class="d-flex">
        <a href="{{ url('/card-management/cards/list') }}" class="btn btn-primary ml-2 mx-md-2">
            <i class="fal fa-arrow-alt-left"></i> Go Back </a>
        @if (!$assignedTo)
            <a href="{{ url('/card-management/link-users/new/' . $assigned_card_id) }}" class="btn btn-primary ml-2 mx-md-2">
                <i class="fal fa-plus-circle"></i>
                Link to {{ $ownCompany ? $ownCompany->short_name : '' }} User
            </a>
        @endif
        </div>
    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false"
        data-panel-color="false" data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                Assigned Card Details
            </h2>
        </div>

        <div class="panel-container show mb-3">
            <div class="panel-content" style="overflow: scroll;">
                @include('common.error-message')
                <table class="table table-bordered table-hover m-0 shadow" id="data-grid">
                    <thead class="thead-dark">
                        {{-- Card Holder Details --}}
                        <tr>
                            <th>
                                <h3>Card Holder Details</h3>
                            </th>
                            <th>
                                @if ($assignedTo)
                                    <h3>Assigned to <span class="text-primary">{{ $assignedTo->owner->name }}</span></h3>
                                @endif
                            </th>
                        </tr>
                    </thead>
                        {{-- Card Details --}}
                            <tr>
                                <td colspan="2">&nbsp;</td>
                            </tr>
                            <tr>
                                <th colspan="2">
                                    <h3>Card Details</h3>
                                </th>
                            </tr>
                             <tr>
                                <th width="200">Provider</th>
                                <td>@if(!empty($card->provider_type)) {{ ucfirst(str_replace('_',' ',$card->provider_type)) }} @endif</td>
                            </tr>
                            <tr>
                                <th width="200">Card ID</th>
                                <td>{{ $details->CardId }}</td>
                            </tr>
                            <tr>
                                <th>Emboss Name</th>
                                <td>{{ $details->EmbossName }}</td>
                            </tr>
                            <tr>
                                <th>Card  Number</th>
                                <td>{{ $card->cardNumber }}</td>
                            </tr>
                            <tr>
                                <th width="200">Card Reference Number</th>
                                <td>{{ $details->CardReferenceNumber }}</td>
                            </tr>
                            <tr>
                                <th>Card Type</th>
                                <td>{{ $details->CardType }}</td>
                            </tr>
                            <tr>
                                <th>Card Brand</th>
                                <td>{{ $details->CardBrand }}</td>
                            </tr>
                            <tr>
                                <th>Card Status</th>
                                <td>{{ $details->CardStatus }}</td>
                            </tr>
                            
                            <tr>
                                <th>Wallet ID</th>
                                <td>{{ $details->WalletId }}</td>
                            </tr>
                            <tr>
                                <th>Wallet Currency Code</th>
                                <td>{{ $details->WalletCurrencyCode }}</td>
                            </tr>
                            <tr>
                                <th>Card Balance</th>
                                <td>{{ $details->CardBalance }}</td>
                            </tr>
                            <tr>
                                <th>Card Physical Type</th>
                                <td>{{ $details->CardPhysicalType }}</td>
                            </tr>
                           
                            <tr>
                                <th>Emboss Status</th>
                                <td>{{ $details->EmbossStatus }}</td>
                            </tr>
                            <tr>
                                <th>Is Activated</th>
                                <td>{{ $details->IsActivated ? 'Yes' : 'No' }}</td>
                            </tr>
                            <tr>
                                <th>Activation Date</th>
                                <td>{{ \Carbon\Carbon::parse($details->ActivationDateTime)->format('d M Y H:i') }}</td>
                            </tr>
                            <tr>
                                <th>Process Date</th>
                                <td>{{ \Carbon\Carbon::parse($details->ProcessDateTime)->format('d M Y H:i') }}</td>
                            </tr>
                            <tr>
                                <th>Approval Transaction ID</th>
                                <td>{{ $details->ApprovalTransactionId }}</td>
                            </tr>
                            <tr>
                                <th>Is In Approval</th>
                                <td>{{ $details->IsInApproval ? 'Yes' : 'No' }}</td>
                            </tr>
                             <tr>
                                <th>KYC/KYB Remarks</th>
                                <td><pre>
                                {{ json_encode(json_decode($assignedTo->cardDetails->kyc_remark), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}
                                </pre>
                                <div class="kyc-error-messages"></div>
                                <button class="btn btn-primary" onclick="verifyKYCStatus('{{$assignedTo->cardDetails->id}}', '{{$assignedTo->client_id}}')">Verify Documents Status</button>
                                  @if($assignedCard->provider == 'match_move' && !empty($assignedCard->cardDetails) && !empty($assignedCard->link)) 
                                        <button title="Re-Submit Documents" class="btn btn-secondary"
                                            onclick="verifyKYC('{{ $assignedCard->cardDetails->id }}','{{ $assignedCard->cardDetails->customer_id }}','{{$assignedCard->link->client_id}}')"
                                            class="btn btn-sm btn-outline-success cursor-pointer">
                                            <i class="fal fa-check font-weight-900"></i> Re-Submit Documents
                                        </button>
                                    @endif
                                </td>
                            </tr>

                   
                </table>

            </div>
        </div>
    </div>

<script>
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
                    $.get(`/card-management/cards/verify/documents/${detail_id}/${customer_id}/${client_id}`, function(data, status) {
                        console.log("documents:", data);
                       if(!data.IsInApproval) {
                         $('.kyc-error-messages').html('<div class="alert alert-warning">'+data.ErrorCode+': '+ data.ErrorDescription + '</div>');
                       } else{
                             $('.kyc-error-messages').html('<div class="alert alert-success">Documents verified successfully.</div>');
                       }
                    }).fail(function(xhr) {
                        console.error("Error:", xhr.responseText);
                    });
                }
            });
        }
        
    function verifyKYCStatus(detail_id, client_id) {
            Swal.fire({
                title: "Are you sure?",
                text: `Do you want to proceed?`,
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: `Yes, verify!`
            }).then(function(result) {
                console.log(result);
                if (result.value) {
                    $.get(`/card-management/cards/documents/status/${detail_id}/${client_id}`, function(data, status) {
                        console.log("documents:", data);
                       if(!data.IsInApproval) {
                         $('.kyc-error-messages').html('<div class="alert alert-warning">'+data.ErrorCode+': '+ data.ErrorDescription + '</div>');
                       } else{
                             $('.kyc-error-messages').html('<div class="alert alert-success">Documents verified successfully.</div>');
                       }
                    }).fail(function(xhr) {
                        console.error("Error:", xhr.responseText);
                    });
                }
            });
        }
</script>
@stop
