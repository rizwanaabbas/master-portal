    @php
        $canApprove = false;
        $isSumsubEnabled = isset($enabledModules) && in_array('Sumsub', $enabledModules);

        if ($isSumsubEnabled) {
            if (isset($client) && $client->type === 'company') {
                if (isset($company_transaction_log) && count($company_transaction_log) > 0) {
                    $logData = json_decode($company_transaction_log[0]->details, true);

                    $rStatus = strtolower($logData['reviewStatus'] ?? '');
                    $rAnswer = ($logData['reviewResult']['reviewAnswer'] ?? '');

                    if ($rStatus === 'completed' && $rAnswer === 'GREEN') {
                        $canApprove = true;
                    }
                }
            } else {
                // Individual Client Logic
                if (isset($clientKYC)) {
                    $canApprove =
                        strtolower($clientKYC->sumsub_kyc_status) === 'completed' &&
                        $clientKYC->sumsub_kyc_answer === 'GREEN';
                }
            }
        } else {
            // Legacy Fallback
            if (isset($clientKYC)) {
                $canApprove = $clientKYC->compliance_status === 'Verified';
            }
        }
    @endphp
    @can('approve client')
        @if ($user->id != $client->created_by || !$user->manager_id > 0)
            @if ($client->status != 'Approved')
                <button onclick="approveClient(); return false;" {{ !$canApprove ? 'disabled' : '' }}
                    class="btn btn-success">Approve</button>
            @endif
            @if ($client->status != 'Rejected')
                <button onclick="rejectClient(); return false;" class="btn btn-danger">Reject</button>
            @endif
        @endif
    @endcan
