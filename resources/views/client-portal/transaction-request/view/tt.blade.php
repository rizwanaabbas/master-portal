@extends('layouts.cp')
@section('title', 'TT Transaction - Transaction Requests')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Transaction Requests</span>
        </h1>
        <a href="{{ url('/client/transaction-requests/list') }}" class="btn btn-primary mr-3"><i
                class="fal fa-arrow-alt-left"></i> Go Back </a>

    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false"
        data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                TT Transaction
            </h2>
        </div>

        <div class="panel-container show mb-3">
            <div class="panel-content" style="overflow: scroll;">
                @include('common.error-message')
                <table class="table table-bordered table-hover m-0 shadow" id="data-grid">
                    <thead class="thead-dark">
                        <tr>
                            <th width="200">From Account</th>
                            <td>{{ $transaction->account->title . ' - ' . $transaction->account->account_number }}</td>
                        </tr>
                        <tr>
                            <th>Amount</th>
                            <td>{{ number_format($transaction->amount, 2) }}</td>
                        </tr>
                        <tr>
                            <th>Request Date</th>
                            <td>{{ \Carbon\Carbon::parse($transaction->updated_at)->format('d M, Y') }}</td>
                        </tr>
                        <tr>
                            <th>Type</th>
                            <td>{{ $transaction->transaction_type }}</td>
                        </tr>
                        <tr>
                            <th>Mode</th>
                            <td>{{ $transaction->transaction_nature }}</td>
                        </tr>

                        <!-- check if the TT is for external transfer -->
                        @if ($transaction->beneficiary_id > 0)
                            <tr>
                                <th>Beneficiary</th>
                                <td><button onclick="viewBeneficiary();" type="button"
                                        class="btn btn-outline-secondary btn-sm">{{ $transaction->beneficiary->name }}</button>
                                </td>
                            </tr>
                        @endif
                        <tr>
                            <th>Remarks</th>
                            <td>{{ $transaction->remarks }}</td>
                        </tr>
                        <tr>
                            <th>Supporting Documents</th>
                            <td>
                                @if ($transaction->docs)
                                    @foreach ($transaction->docs as $doc)
                                        @if ($doc->is_payment == 0)
                                            <a href="{{ $doc->url }}" class="btn btn-sm btn-outline-secondary"
                                                target="_blank"><i class="fal fa-file-alt"></i> View</a>
                                        @endif
                                    @endforeach
                                @else
                                    --
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Payment Advice Documents</th>
                            <td>
                                @if ($transaction->docs)
                                    @foreach ($transaction->docs as $doc)
                                        @if ($doc->is_payment == 1)
                                            <a href="{{ $doc->url }}" class="btn btn-sm btn-outline-secondary"
                                                target="_blank"><i class="fal fa-file-alt"></i> View</a>
                                        @endif
                                    @endforeach
                                @else
                                    --
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td><span
                                    class="badge {{ $statusBadge[$transaction->status] }}">{{ $transaction->status }}</span>
                            </td>

                        </tr>
                        @if ($transaction->status == 'Rejected')
                            <tr>
                                <th>Reason</th>
                                <td>{{ $transaction->reject_reason }}</td>
                            </tr>
                        @endif
                        @if ($transaction->corresponding_bank_name)
                            <tr>
                                <th colspan="2">Corresponding Bank Details</th>
                            </tr>
                            <tr>
                                <th>Bank Name</th>
                                <td>{{ $transaction->corresponding_bank_name }}</td>
                            </tr>
                            <tr>
                                <th>Swift Code</th>
                                <td>{{ $transaction->corresponding_bank_swift_code }}</td>
                            </tr>
                            <tr>
                                <th>Bank Address</th>
                                <td>{{ $transaction->corresponding_bank_address }}</td>
                            </tr>
                        @endif
                        @if (in_array('NeroData', $enabledModules))
                            <tr>
                                <th>Transaction Monitoring Status</th>

                                <td><span> <span class="badge badge-warning">{{ $transaction->compliance_status }} </span>
                                    @if (!empty($rulesText))
                                        {!! $rulesText !!}
                                    @endif
                                    @php $notes = collect($amlNoteTexts ?? []); @endphp
                                    @if ($notes->isNotEmpty())
                                        <div class="mt-2">
                                            <strong>AML Notes</strong>
                                            <ul class="mb-0">
                                                @foreach ($notes as $note)
                                                    <li>{{ $note }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif
                                        <a class="btn btn-primary" target="_blank" href="{{$portalTransactionsUrl}}">VIEW / MODERATE</a>
                                </td>
                            </tr>
                        @endif
                    </thead>
                </table>
            </div>
        </div>
    </div>

    @if ($transaction->beneficiary_id > 0)
        <div class="modal fade" id="beneficiary-modal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title ">Beneficiary Details</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true"><i class="fal fa-times"></i></span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <table class="table table-bordered table-hover m-0 shadow">
                            <thead class="thead-dark">
                                <tr>
                                    <th width="150">Name</th>
                                    <td>{{ $transaction->beneficiary->name }}</td>
                                </tr>
                                <tr>
                                    <th>Account Number</th>
                                    <td>{{ $transaction->beneficiary->account_number }}</td>
                                </tr>
                                <tr>
                                    <th>IBAN Number</th>
                                    <td>{{ $transaction->beneficiary->iban }}</td>
                                </tr>
                                <tr>
                                    <th>Beneficiary Address</th>
                                    <td>{{ $transaction->beneficiary->address }}</td>
                                </tr>
                                <tr>
                                    <th>Bank Name</th>
                                    <td>{{ $transaction->beneficiary->bank_name }}</td>
                                </tr>
                                <tr>
                                    <th>Swift Code</th>
                                    <td>{{ $transaction->beneficiary->swift_code }}</td>
                                </tr>
                                <tr>
                                    <th>Bank Address</th>
                                    <td>{{ $transaction->beneficiary->bank_address }}</td>
                                </tr>
                                <tr>
                                    <th>Status</th>
                                    <td><span>{{ $transaction->beneficiary->status }}</span></td>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endif

@stop
@section('scripts')
    <script>
        function viewBeneficiary() {
            $("#beneficiary-modal").modal('show');
        }
    </script>
@stop
