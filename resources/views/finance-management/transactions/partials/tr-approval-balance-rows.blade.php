{{-- Expects $trRequest (TRRequest). Shows ledger, pending, available, and max for this approval. --}}
@php
    $balanceBreakdown = \App\Helpers\Common::getAccountBalanceBreakdownForTrApproval($trRequest);
    $cryptoHoldActive = !empty($balanceBreakdown['crypto_hold_active']);
@endphp
<tr>
    <th>Total balance (ledger)</th>
    <td>
        {{ number_format((float) $balanceBreakdown['ledger'], 2) }}
        <small class="text-muted d-block">Approved credits minus approved debits.</small>
    </td>
</tr>
<tr>
    <th>Pending holds</th>
    <td>
        {{ number_format((float) $balanceBreakdown['pending_hold'], 2) }}
        @if ($cryptoHoldActive)
            <small class="text-warning d-block">Pending crypto is reserving this account. Holds are OTP-verified Pending debits only (not Initial Review, not unverified OTP).</small>
        @else
            <small class="text-muted d-block">No pending crypto debit — other pending TTs are not held.</small>
        @endif
    </td>
</tr>
<tr>
    <th>Available (other requests)</th>
    <td>
        {{ number_format((float) $balanceBreakdown['available'], 2) }}
        <small class="text-muted d-block">Ledger minus pending holds. What is left if this request is ignored.</small>
    </td>
</tr>
<tr>
    <th>Max for this approval</th>
    <td>
        <strong>{{ number_format((float) $balanceBreakdown['approval_available'], 2) }}</strong>
        <small class="text-muted d-block">Amount + fees charged on this from-account cannot exceed this figure. This request’s own pending hold is included in the maximum when crypto is holding the account.</small>
    </td>
</tr>
