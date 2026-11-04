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
                        <tr>
                            <th width="200">Name</th>
                            <td>{{ $details->cardHolderName }}</td>
                        </tr>
                        <tr>
                            <th>Gender</th>
                            <td>{{ $details->get_member->gender }}</td>
                        </tr>
                        <tr>
                            <th>Email</th>
                            <td>{{ $details->get_member->email }}</td>
                        </tr>
                        <tr>
                            <th>Mobile Number</th>
                            <td>{{ $details->get_member->mobileNo }}</td>
                        </tr>
                        <tr>
                            <th>Date Of Birth</th>
                            <td>{{ $details->get_member->dateOfBirth }}</td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td><span
                                    class="badge {{ $statusBadge[$details->get_member->status] }}">{{ $details->get_member->status }}</span>
                            </td>
                        </tr>

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
                            <th width="200">Number</th>
                            <td>
                                {{ $details->cardNumber }}
                            </td>
                        </tr>
                        <tr>
                            <th>Type</th>
                            <td>{{ $details->cType }}</td>
                        </tr>
                        <tr>
                            <th>Expiry Date</th>
                            <td>{{ $details->expiryDate }}
                            </td>
                        </tr>
                        <tr>
                            <th>Username</th>
                            <td>{{ $details->get_member->userName }}</td>
                        </tr>
                        <tr>
                            <th>Card Image</th>
                            <td>
                                @if ($details->cardImage)
                                    <a href="{{ $details->cardImage }}" class="btn btn-sm btn-outline-secondary"
                                        target="_blank"><i class="fal fa-file-alt"></i>
                                        View</a>
                                @else
                                    --
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>MPIN</th>
                            <td>{{ $details->mpin }}</td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td><span class="badge {{ $statusBadge[$details->status] }}">{{ $details->status }}</span>
                            </td>
                        </tr>
                        <tr>
                            <th>Program Type</th>
                            <td>{{ $details->programType }}</td>
                        </tr>
                        <tr>
                            <th>Remarks</th>
                            <td>{{ $details->remarks }}</td>
                        </tr>
                        <tr>
                            <th>Max Balance Limit</th>
                            <td>{{ $details->max_balance_limit }}</td>
                        </tr>
                        <tr>
                            <th>Currency</th>
                            <td>{{ $details->currency }}</td>
                        </tr>
                        <tr>
                            <th>Processor Message</th>
                            <td>{{ $details->get_member->processor_message }}</td>
                        </tr>
                        <tr>
                            <th>Wallet ID</th>
                            <td>{{ $details->get_member->wallet_id }}</td>
                        </tr>
                        <tr>
                            <th>Unique ID</th>
                            <td>{{ $details->get_member->unique_id }}</td>
                        </tr>

                        {{-- Card Holder Address Details --}}
                        <tr>
                            <td colspan="2">&nbsp;</td>
                        </tr>
                        <tr>
                            <th colspan="2">
                                <h3>Card Holder Address Details</h3>
                            </th>
                        </tr>
                        <tr>
                            <th>Address</th>
                            <td>{{ $details->get_member->address }}</td>
                        </tr>
                        <tr>
                            <th>City</th>
                            <td>{{ $details->get_member->city }}</td>
                        </tr>
                        <tr>
                            <th>State</th>
                            <td>{{ $details->get_member->state }}</td>
                        </tr>
                        <tr>
                            <th>Postal Code</th>
                            <td>{{ $details->get_member->postalCode }}</td>
                        </tr>
                        <tr>
                            <th>Country Code</th>
                            <td>{{ $details->get_member->countryCode }}</td>
                        </tr>
                        <tr>
                            <th>Calling Code</th>
                            <td>{{ $details->get_member->callingCode }}</td>
                        </tr>

                        {{-- KYC Details --}}
                        <tr>
                            <td colspan="2">&nbsp;</td>
                        </tr>
                        <tr>
                            <th colspan="2">
                                <h3>KYC Details</h3>
                            </th>
                        </tr>
                        <tr>
                            <th>ID Proof Submission Date</th>
                            <td>{{ $details->get_member->id_proof_submission_date }}
                            </td>
                        </tr>
                        <tr>
                            <th>Address Proof Submission Date</th>
                            <td>{{ $details->get_member->address_proof_submission_date }}
                            </td>
                        </tr>
                        <tr>
                            <th>KYC Remarks</th>
                            <td>{{ $details->get_member->kyc_remark }}</td>
                        </tr>
                        <tr>
                            <th>KYC Remarks Date</th>
                            <td>{{ $details->get_member->kyc_remark_date }}</td>
                        </tr>
                        <tr>
                            <th>ID Proof Front</th>
                            <td>
                                @if ($details->get_member->id_proof_front)
                                    <a href="{{ $details->get_member->id_proof_front }}"
                                        class="btn btn-sm btn-outline-secondary" target="_blank"><i
                                            class="fal fa-file-alt"></i>
                                        View</a>
                                @else
                                    --
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>ID Proof Back</th>
                            <td>
                                @if ($details->get_member->id_proof_back)
                                    <a href="{{ $details->get_member->id_proof_back }}"
                                        class="btn btn-sm btn-outline-secondary" target="_blank"><i
                                            class="fal fa-file-alt"></i>
                                        View</a>
                                @else
                                    --
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Address Proof Front</th>
                            <td>
                                @if ($details->get_member->address_proof_front)
                                    <a href="{{ $details->get_member->address_proof_front }}"
                                        class="btn btn-sm btn-outline-secondary" target="_blank"><i
                                            class="fal fa-file-alt"></i>
                                        View</a>
                                @else
                                    --
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Address Proof Back</th>
                            <td>
                                @if ($details->get_member->address_proof_back)
                                    <a href="{{ $details->get_member->address_proof_back }}"
                                        class="btn btn-sm btn-outline-secondary" target="_blank"><i
                                            class="fal fa-file-alt"></i>
                                        View</a>
                                @else
                                    --
                                @endif
                            </td>
                        </tr>
                    </thead>
                </table>

            </div>
        </div>
    </div>


@stop
