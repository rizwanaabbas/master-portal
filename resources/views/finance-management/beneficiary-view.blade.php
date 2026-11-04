@extends('layouts.cp')
@section('title', 'Beneficiary Details - Finance Management')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Finance Management</span>
        </h1>
        <a href="{{ url('finance-management/beneficiaries/edit/' . $ben->id) }}" class="btn btn-primary mr-3">
            <i class="fal fa-edit"></i> Edit
        </a>

        <a href="{{ url('finance-management/beneficiaries/') }}" class="btn btn-primary mr-3">
            <i class="fal fa-arrow-alt-left"></i> Go Back
        </a>

    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false"
        data-panel-color="false" data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                Beneficiary Details
            </h2>

        </div>

        @php
            $beneficiary_type_map = [
                'RETAIL' => 'Individual',
                'CORPORATE' => 'Company',
                'individual' => 'Individual',
                'company' => 'Company',
            ];
            $virtual_bank_beneficiary = $ben->virtualBankBeneficiary;
            $sort_code = null;
            $routing_number = null;
            $payment_types = null;

            if (!empty($virtual_bank_beneficiary)) {
                $provider = strtolower($virtual_bank_beneficiary->provider_name);

                if ($provider === 'openpayd') {
                    $sort_code = $virtual_bank_beneficiary->bank_routing_codes[0]['routingCodeValue'] ?? null;
                } elseif (in_array($provider, ['lorum', 'zenus'])) {
                    $codes = json_decode($virtual_bank_beneficiary->bank_routing_codes);
                    $firstCode = is_array($codes) ? $codes[0] ?? null : $codes;
                    $routing_number = $firstCode->routingCodeValue ?? null;
                }

                $payment_types = json_encode($virtual_bank_beneficiary->payment_types);
            }
        @endphp

        <div class="panel-container show mb-3">
            <div class="panel-content" style="overflow: scroll;">
                @include('common.error-message')
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-bordered table-hover m-0 shadow" id="data-grid">
                            <tr>
                                <th width="200">Name</th>
                                <td>{{ $ben->name ?? '--' }}</td>
                            </tr>
                            <tr>
                                <th>Beneficiary Type</th>
                                <td>{{ isset($ben->beneficiary_type) ? $beneficiary_type_map[$ben->beneficiary_type] ?? $ben->beneficiary_type : '--' }}
                                </td>
                            </tr>
                            <tr>
                                <th>Beneficiary Address</th>
                                <td>{{ $ben->address ?? '--' }}</td>
                            </tr>
                            <tr>
                                <th>Beneficiary City</th>
                                <td>{{ $ben->beneficiary_city ?? '--' }}</td>
                            </tr>
                            <tr>
                                <th>Beneficiary State/Region</th>
                                <td>{{ $ben->beneficiary_state ?? '--' }}</td>
                            </tr>
                            <tr>
                                <th>Beneficiary Country</th>
                                <td>{{ $ben->country->name ?? '--' }}</td>
                            </tr>
                            <tr>
                                <th>Account Currency</th>
                                <td>{{ $ben->currency->short ?? '--' }}</td>
                            </tr>
                            <tr>
                                <th>Account Number</th>
                                <td>{{ $ben->account_number ?? '--' }}</td>
                            </tr>
                            <tr>
                                <th>IBAN</th>
                                <td>{{ $ben->iban ?? '--' }}</td>
                            </tr>

                            <tr>
                                <th>Bank Name</th>
                                <td>{{ $ben->bank_name ?? '--' }}</td>
                            </tr>
                            <tr>
                                <th>Bank Account Country</th>
                                <td>{{ $ben->bankAccountCountry->name ?? ($ben->bank_account_country ?? '--') }}</td>
                            </tr>
                            <tr>
                                <th>Bank City</th>
                                <td>{{ $ben->bank_city ?? '--' }}</td>
                            </tr>
                            <tr>
                                <th>Bank Address</th>
                                <td>{{ $ben->bank_address ?? '--' }}</td>
                            </tr>
                            <tr>
                                <th>Postal/Zip Code</th>
                                <td>{{ $ben->postal_code ?? '--' }}</td>
                            </tr>
                            <tr>
                                <th>Swift Code/ BIC Code</th>
                                <td>{{ $ben->swift_code ?? '--' }}</td>
                            </tr>
                            <tr>
                                <th>Sort Code</th>
                                <td>{{ $sort_code ?? ($ben->sort_code ?? '--') }}</td>
                            </tr>
                            <tr>
                                <th>Routing Number</th>
                                <td>{{ $routing_number ?? '--' }}</td>
                            </tr>
                            <tr>
                                <th>Payment Types</th>
                                <td>{{ $payment_types ?? '--' }}</td>
                            </tr>
                            <tr>
                                <th>Status</th>
                                <td><span
                                        class="badge {{ $statusBadge[$ben->status] ?? '' }}">{{ $ben->status ?? '--' }}</span>
                                </td>
                            </tr>
                            <tr>
                                <th>Is Deleted</th>
                                <td><span class="badge {{ $statusBadge[$ben->is_deleted] ?? '' }}">
                                        {{ $ben->is_deleted ? 'Yes' : 'No' }}
                                    </span></td>
                            </tr>
                            @if ($ben->status == 'Rejected')
                                <tr>
                                    <th>Reason</th>
                                    <td>{{ $ben->reject_reason ?? '--' }}</td>
                                </tr>
                            @endif
                        </table>
                    </div>
                    <div class="col-md-6">
                        @if (!empty($ben->virtualBankBeneficiary))

                            <div class="alert alert-secondary" role="alert"
                                style="overflow: auto; white-space: pre-wrap;color:red">
                                <pre style="color:red;">{{ json_encode($ben->virtualBankBeneficiary, JSON_PRETTY_PRINT) }}</pre>
                            </div>
                        @else
                            @php
                                $isOpenPaydEnabled = in_array('OpenPayd', $enabledModules);
                            @endphp

                            @if ($isOpenPaydEnabled)
                                <button type="button" class="btn btn-success"
                                    onclick="linkToVirtualBank({{ $ben->id }})">Link to Virtual Bank</button>
                            @endif
                        @endif
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-md-12">
                        <form method="post" action="{{ url('/finance-management/beneficiaries/approve/') }}">
                            @csrf
                            <input type="hidden" id="id" name="id" value="{{ $ben->id }}">
                            @if ($ben->status != 'Approved')
                                <button type="submit" class="btn btn-success">Approve</button>
                            @endif
                            @if ($ben->status != 'Rejected' && !$ben->is_deleted)
                                <button onclick="rejectBeneficiary(); return false;" class="btn btn-danger">Reject</button>
                            @endif
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="reject-modal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title ">Reject Client</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true"><i class="fal fa-times"></i></span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="{{ url('/finance-management/beneficiaries/reject/') }}" method="post">
                        @csrf
                        <input type="hidden" name="id" id="id" value="{{ $ben->id }}">
                        <div class="form-group">
                            <label class="form-label" for="reject_reason">Reject Reason</label>
                            <textarea required class="form-control" name="reject_reason" id="reject_reason" cols="30" rows="2"></textarea>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-danger">Reject</button>
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
            $('#data-grid').dataTable({
                responsive: true
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

        function linkToVirtualBank(ben_id) {

            Swal.fire({
                title: "Are you sure?",
                text: "You want to link this beneficiary to Virtual Bank?",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Yes, link it!"
            }).then(function(result) {
                if (result.value) {
                    window.location = "{{ url('/finance-management/beneficiaries/link-to-virtual-bank') }}/" +
                        ben_id;
                }
            });
        }

        function rejectBeneficiary() {
            $("#reject-modal").modal('show');
        }
    </script>

@stop
