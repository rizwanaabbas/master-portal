{{-- Shared crypto transaction summary (no tables). Expects $transaction (TRRequest). --}}
@php
    $nature = $transaction->transaction_nature ?? 'Transaction';
    $isSwap = $nature === 'Swap';
    $fromAccount = $transaction->account;
    $toAccount = $transaction->receiver ?? null;
    $fromCurrency = optional($fromAccount->currency)->short ?? '';
    $toCurrency = $toAccount ? optional($toAccount->currency)->short ?? '' : '';
    $isCryptoDebit = in_array($nature, ['Sell', 'Withdraw'], true);
    $debitAmount = $isCryptoDebit
        ? ($transaction->crypto_amount ?? 0)
        : ($transaction->amount ?? 0);
    $creditAmount = $isCryptoDebit
        ? ($transaction->amount ?? 0)
        : ($transaction->crypto_amount ?? 0);
    $debitFormatted = \App\Helpers\Common::formatNumber($debitAmount, (bool) optional($fromAccount->currency)->is_crypto);
    $creditFormatted = $toAccount
        ? \App\Helpers\Common::formatNumber($creditAmount, (bool) optional($toAccount->currency)->is_crypto)
        : \App\Helpers\Common::formatNumber($creditAmount, true);
    $currencyShort = $fromCurrency;
    $isCryptoAmount = ($transaction->crypto_amount ?? 0) > 0;
    $amountLine = $isCryptoAmount
        ? \App\Helpers\Common::formatNumber($transaction->crypto_amount, true) . ($currencyShort ? ' ' . $currencyShort : '')
        : \App\Helpers\Common::formatNumber($transaction->amount ?? 0, true) . ($currencyShort ? ' ' . $currencyShort : '');
    $submittedAt = \Carbon\Carbon::parse($transaction->transaction_date ?? $transaction->created_at)->format('d M Y, H:i') . ' (UTC)';
    $accountLabel = strip_tags($fromAccount->title ?? '');
    $toAccountLabel = strip_tags($toAccount->title ?? '');
@endphp
<div class="summary-box">
    <p style="margin: 0 0 10px;"><strong>Reference:</strong> #{{ $transaction->id }}</p>
    <p style="margin: 0 0 10px;"><strong>Transaction:</strong> {{ $nature }}</p>
    @if($isSwap)
        <p style="margin: 0 0 10px;"><strong>From asset:</strong> {{ $debitFormatted }}@if($fromCurrency) {{ $fromCurrency }}@endif</p>
        <p style="margin: 0 0 10px;"><strong>From account:</strong> {{ $fromAccount->account_number ?? '—' }}@if($accountLabel !== '') — {{ $accountLabel }}@endif</p>
        <p style="margin: 0 0 10px;"><strong>To asset:</strong> {{ $creditFormatted }}@if($toCurrency) {{ $toCurrency }}@endif</p>
        @if($toAccount)
            <p style="margin: 0 0 10px;"><strong>To account:</strong> {{ $toAccount->account_number ?? '—' }}@if($toAccountLabel !== '') — {{ $toAccountLabel }}@endif</p>
        @elseif(!empty($transaction->to_wallet_address))
            <p style="margin: 0 0 10px;"><strong>To account:</strong> {{ $transaction->to_wallet_address }}</p>
        @endif
    @else
        <p style="margin: 0 0 10px;"><strong>Amount:</strong> {{ $amountLine }}</p>
        <p style="margin: 0 0 10px;"><strong>Account:</strong> {{ $fromAccount->account_number ?? '—' }}@if($accountLabel !== '') — {{ $accountLabel }}@endif</p>
        @if($nature === 'Withdraw' && !empty($transaction->to_wallet_address))
            <p style="margin: 0 0 10px;"><strong>Destination:</strong> {{ $transaction->to_wallet_address }}</p>
        @endif
    @endif
    @if(!empty($transaction->crypto_network))
        <p style="margin: 0 0 10px;"><strong>Network:</strong> {{ $transaction->crypto_network }}</p>
    @endif
    <p style="margin: 0;"><strong>Submitted:</strong> {{ $submittedAt }}</p>
</div>
