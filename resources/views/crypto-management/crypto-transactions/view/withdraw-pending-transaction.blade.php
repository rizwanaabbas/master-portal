@extends('layouts.cp')
@section('title', 'Withdraw-Crypto Transaction - Crypto Management')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Crypto Management</span>
        </h1>
        <div class="d-flex">
            <a href="{{ url('crypto-management/crypto-pending-transactions') }}" class="btn btn-primary ml-2 mx-md-2"><i
                    class="fal fa-arrow-alt-left"></i> Go Back </a>
            <a href="{{ url('crypto-management/crypto-pending-transactions/print/' . $transaction->id) }}"
                class="btn btn-primary ml-2 mx-md-2" target="_blank"><i class="fal fa-print"></i> Print </a>
        <div class="row">
            <div class="col-md-12">
                @include('bitpace.transaction-log')
            </div>
        </div>
        </div>
    </div>
    @if (!empty($sanctions->identifications))
        <div class="alert alert-danger">Withdraw approval blocked as this a sanctioned wallet <button onclick="Sanctioned()"
                class="btn btn-primary pull-right" style="margin-left:20px;">View Details</button></div>
    @endif
    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false"
        data-panel-color="false" data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            @php
                $disabled = '';

                if (
                    in_array($transaction->compliance_status, $complianceStatus) &&
                    $transaction->status == 'Pending' &&
                    in_array('NeroData', $enabledModules)
                ) {
                    $disabled = 'disabled';
                }

            @endphp
            <h2>
                Crypto Withdraw Transaction
            </h2>

        </div>

        <div class="panel-container show mb-3">
            <div class="panel-content" style="overflow: scroll;">
                @if (!empty($disabled) && $transaction->status == 'Pending' && in_array('NeroData', $enabledModules))
                    <div class="alert alert-warning">Please contact compliance team to clear this transaction. Current
                        status from transaction monitoring is <strong>{{ $transaction->compliance_status }}</strong>
                    </div>
                @endif
                @include('common.error-message')
                <form id="approval-form" method="post"
                    action="{{ url('/crypto-management/crypto-pending-transaction/approve/withdraw') }}"
                    enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" id="id" name="id" value="{{ $transaction->id }}">
                    <table class="table table-bordered table-hover m-0 shadow" id="data-grid">
                        <thead class="thead-dark">
                            <tr>
                                <th width="200">Crypto Account</th>
                                <td>{{ $transaction->account->title . ' - ' . $transaction->account->account_number }}
                                </td>
                            </tr>

                            @include('finance-management.transactions.partials.tr-approval-balance-rows', [
                                'trRequest' => $transaction,
                            ])


                            <tr>
                                <th>Amount to Withdraw</th>
                                <td>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <input type="number" step="any" id="crypto_amount" name="crypto_amount"
                                                    value="{{ rtrim(rtrim(json_encode($transaction->crypto_amount, JSON_UNESCAPED_SLASHES), ''), '.') }}"
                                                    class="form-control" required />
                                                <span id="crypto_amount-message" style="color: red;"></span>
                                                @error('crypto_amount')
                                                    <span class="invalid-feedback"
                                                        role="alert"><strong>{{ $message }}</strong></span>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                    {{-- <img src="{{ $transaction->receiver->currency->logo }}"
                                        alt="{{ $transaction->receiver->currency->name }}" height="17px"
                                        style="vertical-align: middle;" />&nbsp;{{ \App\Helpers\Common::formatNumber($transaction->crypto_amount, true) }} --}}
                                </td>
                                {{-- <td><img src="{{ $transaction->account->currency->logo }}"
                                        alt="{{ $transaction->account->currency->name }}" height="17px"
                                        style="vertical-align: middle;" />&nbsp;{{ \App\Helpers\Common::formatNumber($transaction->crypto_amount, true) }}
                                </td> --}}
                            </tr>
                            <tr>
                                <th>To Wallet Address</th>
                                <td>{{ $transaction->to_wallet_address }}</td>
                            </tr>
                            <tr>
                                <th>Beneficiary</th>
                                <td>
                                    @include('crypto-management.crypto-transactions.partials.fireblocks-beneficiary-cell', [
                                        'fireblocksCryptoBeneficiary' => $fireblocksCryptoBeneficiary ?? null,
                                    ])
                                </td>
                            </tr>
                            <tr>
                                <th>Crypto Network</th>
                                <td>
                                    {{ $transaction->crypto_network ? $transaction->crypto_network : 'N/A' }}
                                </td>
                            </tr>
                            @if ($transaction->destination_tag != '')
                                <tr>
                                    <th>Destination Tag</th>
                                    <td>{{ $transaction->destination_tag }}</td>
                                </tr>
                            @endif
                            <tr>
                                <th>Request Date</th>
                                <td>{{ \Carbon\Carbon::parse($transaction->created_at)->format('d M, Y') }}</td>
                            </tr>

                            <tr>
                                <th>Withdrawal Date</th>
                                <td>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <input type="text" autocomplete="off" class="form-control"
                                                value="{{ \Carbon\Carbon::parse($transaction->transaction_date)->format('d/m/Y') }}"
                                                name="transaction_date" id="transaction_date" placeholder="Select date"
                                                required>
                                            @error('transaction_date')
                                                <span class="invalid-feedback"
                                                    role="alert"><strong>{{ $message }}</strong></span>
                                            @enderror
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <th>Mode</th>
                                <td>{{ $transaction->transaction_nature }}</td>
                            </tr>
                            <tr>
                                <th>Remarks</th>
                                <td>
                                    @if ($transaction->status == 'Pending')
                                        <div class="row">
                                            <div class="col-md-12">
                                                <textarea name="remarks" id="remarks" class="form-control" cols="30" rows="2">{{ $transaction->remarks }}</textarea>
                                            </div>
                                        </div>
                                    @else
                                        {{ $transaction->remarks }}
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Transaction Notes</th>
                                <td>
                                    @include('finance-management.transactions.partials.transaction-notes', [
                                        'transaction_request_id' => $transaction->id,
                                    ])
                                </td>
                            </tr>
                            
                            <tr>
                                <th>Supporting Documents</th>
                                <td>
                                    @if ($transaction->docs)
                                        @foreach ($transaction->docs as $doc)
                                            <a href="{{ $doc->url }}" class="btn btn-sm btn-outline-secondary"
                                                target="_blank"><i class="fal fa-file-alt"></i> View</a>
                                        @endforeach
                                    @else
                                        --
                                    @endif

                                    <div class="row">
                                        <div class="col-md-6">
                                            @include('finance-management.transactions.partials.new-supporting-documents')
                                        </div>
                                    </div>

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
                            @if ($transaction->crypto_system != '')
                                <tr>
                                    <th>Crypto Portal</th>
                                    <td>{{ ucwords(str_replace('_', ' ', $transaction->crypto_system)) }}
                                        @if ($transaction->crypto_system == 'Bitpace')
                                            => <small> Once clicked on Approve button this transaction will be posted on
                                                Live system automatically</small>
                                    </td>
                            @endif
                            </tr>
                            @endif
                            @if (count($aml_results) > 0)
                                <tr>
                                    <th>AML Results</th>
                                    <td>
                                        <div class="mt-2">
                                            @foreach ($aml_results as $aml)
                                                <span class="badge badge-{{ $aml['badge'] }}">
                                                    {{ $aml['status'] }}</span>
                                                <i class="fal fa-question-circle {{ $aml['badge'] }}" data-toggle="tooltip"
                                                    data-placement="auto" title=""
                                                    data-original-title="{{ $aml['rule_description'] }}"></i>
                                            @endforeach
                                        </div>
                                    </td>
                                </tr>
                            @endif
                            @if (in_array('NeroData', $enabledModules))
                                <tr>
                                    <th>Transaction Monitoring Status</th>

                                    <td><span>{{ $transaction->compliance_status }}</span>
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
                                    </td>
                                </tr>
                            @endif

                            @if ($transaction->bitpaceBeneficiary)
                                <tr>
                                    <th colspan="2">BitPace Travel Rules Data</th>

                                </tr>
                                @php $beneficiary = $transaction->bitpaceBeneficiary;  @endphp
                                <tr>
                                    <th>Sending to Myself </th>
                                    <td>{{ $beneficiary->is_sending_to_myself ? 'Yes' : 'No' }}</td>
                                </tr>
                                <tr>
                                    <th>Beneficiary Type</th>
                                    <td>{{ $beneficiary->beneficiary_type }}</td>
                                </tr>

                                @if ($beneficiary->beneficiary_type === 'INDIVIDUAL')
                                    <tr>
                                        <th>First Name</th>
                                        <td>{{ $beneficiary->beneficiary_name }}</td>
                                    </tr>
                                    <tr>
                                        <th>Last Name</th>
                                        <td>{{ $beneficiary->beneficiary_last_name }}</td>
                                    </tr>
                                @elseif ($beneficiary->beneficiary_type === 'BUSINESS' && !$beneficiary->is_sending_to_myself)
                                    <tr>
                                        <th>Legal Name</th>
                                        <td>{{ $beneficiary->beneficiary_legal_name }}</td>
                                    </tr>
                                @endif
                                <tr>
                                    <th>Wallet Type</th>
                                    <td>{{ $beneficiary->wallet_type }}</td>
                                </tr>

                                @if ($beneficiary->wallet_type === 'CUSTODIAL')

                                    @if ($beneficiary->is_not_on_vasp_list)
                                        <tr>
                                            <th>VASP Not Listed</th>
                                            <td>YES</td>
                                        </tr>
                                        <tr>
                                            <th>VASP Name</th>
                                            <td>{{ $beneficiary->beneficiary_vasp_name }}</td>
                                        </tr>
                                    @else
                                        <tr>
                                            <th>VASP DID</th>
                                            <td>{{ $beneficiary->beneficiary_vasp_did }}</td>
                                        </tr>
                                    @endif
                                @elseif ($beneficiary->wallet_type === 'SELF_HOSTED')
                                    <tr>
                                        <th>Ownership Declaration</th>
                                        <td>{{ $beneficiary->is_ownership_declaration ? 'Yes' : 'No' }}</td>
                                    </tr>
                                @endif
                            @endif


                        </thead>
                    </table>
                    <hr>
                    @if ($transaction->status == 'Pending')
                        <div class="row">
                            <div class="col-md-12">
                                @if (!empty($disabled) && $transaction->status == 'Pending' && in_array('NeroData', $enabledModules))
                                    <div class="alert alert-warning">Please contact compliance team to clear this
                                        transaction. Current status from transaction monitoring is
                                        <strong>{{ $transaction->compliance_status }} <a
                                                href="{{ $moderate_url ?? $portalTransactionsUrl . '/' . $neroDataTransactionId }}"
                                                target="_blank" rel="noopener" class="btn btn-warning ml-2 mr-2">
                                                Moderate
                                            </a></strong>
                                    </div>
                                @endif
                                @if (empty($sanctions->identifications))
                                    @if ($transaction->status != 'Approved')
                                        <button type="button" onclick="approve();" class="btn btn-success"
                                            {{-- {{ $disabled }}
                                            @if ($transaction->is_suspicious && !$transaction->is_resolved) disabled @endif --}}>Approve</button>
                                    @endif
                                @endif

                                @if ($transaction->status != 'Rejected')
                                    <button onclick="rejectTransaction(); return false;"
                                        class="btn btn-danger">Reject</button>
                                @endif
                            </div>
                        </div>
                    @endif
                </form>
                <form id="save-note" method="POST" action="{{ url('/finance-management/transactions/save-note') }}">
                    @csrf
                </form>
            </div>
        </div>
    </div>

    @include('crypto-management.crypto-transactions.partials.fireblocks-monitoring-panel', [
        'transactionRequest' => $transaction,
        'fireblocksMonitoring' => $fireblocksMonitoring ?? null,
    ])

    <div class="modal fade" id="reject-modal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title ">Reject Transaction</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true"><i class="fal fa-times"></i></span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="{{ url('/crypto-management/crypto-pending-transaction/reject/crypto-transaction') }}"
                        method="post">
                        @csrf
                        <input type="hidden" name="id" id="id" value="{{ $transaction->id }}">
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
        var controls = {
            leftArrow: '<i class="fal fa-angle-left" style="font-size: 1.25rem"></i>',
            rightArrow: '<i class="fal fa-angle-right" style="font-size: 1.25rem"></i>'
        }

        function approve() {
            if (document.getElementById('crypto_amount').value == 0) {
                document.getElementById('crypto_amount-message').innerHTML = 'Amount is required!'
                setTimeout(() => {
                    document.getElementById('amount-message').innerHTML = ''
                }, 3000);
            } else {
                Swal.fire({
                    title: "Are you sure?",
                    text: "Transaction will be approved",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonText: "Yes, Approve it!"
                }).then(function(result) {
                    if (result.value) {
                        document.getElementById('approval-form').submit()
                    }
                });
            }
        }

        function Sanctioned() {
            //var sanctions = "{{ json_encode($sanctions) }}";
            var sanctions = @json($sanctions);
            //sanctions = jQuery.parseJSON(sanctions);
            var sanctions_text = '';
            $.each(sanctions.identifications, function(index, item) {
                var chatBubble = '<div class="sanction-chat-bubble">';
                chatBubble += '<div><strong>Category:</strong> ' + item.category + '</div>';
                chatBubble += '<div><strong>Name:</strong> ' + item.name + '</div>';
                chatBubble += '<div><strong>Description:</strong> ' + item.description.replace(/\n/g, '<br>') +
                    '</div>';
                chatBubble += '<div><strong>URL:</strong> <a href="' + item.url + '" target="_blank">' + item.url +
                    '</a></div>';
                chatBubble += '</div>';

                sanctions_text += chatBubble;
            });
            //$('#chatContainer').append(chatBubble);
            Swal.fire({
                title: "Sanctioned Wallet info",
                html: sanctions_text,
                //icon: "warning",
            });


        }

        function rejectTransaction() {
            $("#reject-modal").modal('show');
        }

        @include('crypto-management.crypto-transactions.partials.fireblocks-beneficiary-modal-script')

        $(document).ready(function () {

            // minimum setup
            $('#transaction_date').datepicker({
                format: 'dd/mm/yyyy',
                todayHighlight: true,
                orientation: "bottom left",
                templates: controls,
                //startDate: "{{ \Carbon\Carbon::today()->format('d/m/Y') }}"
            });
        })
    </script>
    @include('crypto-management.crypto-transactions.partials.fireblocks-monitoring-scripts')
@stop
