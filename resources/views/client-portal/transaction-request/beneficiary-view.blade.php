@extends("layouts.cp")
@section('title', 'Beneficiary Details - Finance Management')

@section("content")

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Finance Management</span>
        </h1>
        <a href="{{ url("/client/transaction-requests/beneficiaries") }}" class="btn btn-primary mr-3"><i
                class="fal fa-arrow-alt-left"></i> Go Back </a>

    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false"
         data-panel-color="false" data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                Beneficiary Details
            </h2>

        </div>

        <div class="panel-container show mb-3">
            <div class="panel-content" style="overflow: scroll;">
                @include("common.error-message")
              
                <table class="table table-bordered table-hover m-0 shadow" id="data-grid">
                    <tr>
                        <th width="200">Name</th>
                        <td>{{ $ben->name }}</td>
                    </tr>
                    <tr>
                        <th>Account Number</th>
                        <td>{{ $ben->account_number }}</td>
                    </tr>
                    <tr>
                        <th>IBAN</th>
                        <td>{{ $ben->iban }}</td>
                    </tr>

                    <tr>
                        <th>Address</th>
                        <td>{{ $ben->address }}</td>
                    </tr>
                    <tr>
                        <th>Bank Name</th>
                        <td>{{ $ben->bank_name }}</td>
                    </tr>
                    <tr>
                        <th>Swift Code</th>
                        <td>{{ $ben->swift_code }}</td>
                    </tr>
                    <tr>
                        <th>Bank Address</th>
                        <td>{{ $ben->bank_address }}</td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td><span class="badge {{ $statusBadge[$ben->status] }}">{{ $ben->status }}</span></td>
                    </tr>
                    @if ($ben->status == "Rejected")
                        <tr>
                            <th>Reason</th>
                            <td>{{ $ben->reject_reason }}</td>
                        </tr>
                    @endif
                   
                </table>
                
            </div>
        </div>
    </div>
@stop
