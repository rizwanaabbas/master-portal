@extends('layouts.email')
@section('title', 'Insufficient Fireblocks Custody Balance')

@section('content')
    @php
        $account = $transaction->account ?? null;
        $currencyShort = $account?->currency?->short ?? '';
        $amountFormatted = \App\Helpers\Common::formatNumber($transaction->amount ?? 0, false);
        $alertAt = \Carbon\Carbon::parse($transaction->created_at ?? now())->format('d M Y, H:i') . ' (UTC)';
        $txRef = $transaction->fireblocks_tx_id ?? '—';
        $assetId = $transaction->asset_id ?? '—';
        $channel = $transaction->channel ?? 'Fireblocks withdraw';
        $monitoringUrl = $transaction->monitoring_url ?? null;
    @endphp
    <table cellpadding="0" cellspacing="0">
        <tr>
            <td class="spacer">&nbsp;</td>
        </tr>
        <tr>
            <td class="container-td">
                <div align="center" class="container-div">
                    <table cellpadding="0" cellspacing="0">
                        <tr>
                            <td>&nbsp;</td>
                        </tr>
                        <tr>
                            <td align="center" class="logo-td"><img class="logo"
                                    src="{{ $ownCompany ? $ownCompany->logo : '' }}"
                                    alt="{{ $ownCompany ? $ownCompany->name : '' }}" aria-roledescription="logo">
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div align="center" class="inner-div">
                                    <table cellspacing="0" cellpadding="10">
                                        <tr>
                                            <td align="center">
                                                <h1>Custody alert — Fireblocks</h1>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="left" style="text-align: left; line-height: 1.6;">
                                                <p>Fireblocks vault custody for <strong>{{ $assetId }}</strong> is insufficient to submit a client withdrawal while the client account had adequate available balance. Please replenish the custody vault and resubmit from Transaction Monitoring.</p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="left" style="text-align: left;">
                                                <div class="gray-bg" style="padding: 16px 20px; text-align: left; line-height: 1.65;">
                                                    <p style="margin: 0 0 10px;"><strong>Monitoring reference:</strong> {{ $txRef }}</p>
                                                    <p style="margin: 0 0 10px;"><strong>Asset:</strong> {{ $assetId }}</p>
                                                    <p style="margin: 0 0 10px;"><strong>Withdrawal amount:</strong> {{ $amountFormatted }}{{ $currencyShort ? ' ' . $currencyShort : '' }}</p>
                                                    <p style="margin: 0 0 10px;"><strong>Source vault:</strong> {{ $transaction->vault_account_id ?? 'N/A' }}</p>
                                                    <p style="margin: 0 0 10px;"><strong>Destination:</strong> {{ $transaction->destination_address ?? 'N/A' }}</p>
                                                    @if($account)
                                                        <p style="margin: 0 0 10px;"><strong>Client account:</strong> {{ $account->account_number ?? 'N/A' }} ({{ $account->title ?? '' }})</p>
                                                    @endif
                                                    <p style="margin: 0 0 10px;"><strong>Channel:</strong> {{ $channel }}</p>
                                                    <p style="margin: 0 0 10px;"><strong>Fireblocks sub-status:</strong> {{ $transaction->sub_status ?? 'INSUFFICIENT_FUNDS' }}</p>
                                                    @if(!empty($transaction->error_message))
                                                        <p style="margin: 0 0 10px;"><strong>API message:</strong> {{ $transaction->error_message }}</p>
                                                    @endif
                                                    <p style="margin: 0;"><strong>Detected:</strong> {{ $alertAt }}</p>
                                                </div>
                                            </td>
                                        </tr>
                                        @if($monitoringUrl)
                                            <tr>
                                                <td align="center">
                                                    <p><a class="action-button" href="{{ $monitoringUrl }}">Open in Transaction Monitoring</a></p>
                                                </td>
                                            </tr>
                                        @endif
                                        <tr>
                                            <td align="center">
                                                <h2>The {{ $ownCompany ? $ownCompany->name : '' }} Team</h2>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td align="center">@include('emails.system.partial.email-footer')</td>
                                        </tr>
                                    </table>
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
        <tr>
            <td class="spacer">&nbsp;</td>
        </tr>
    </table>
@stop
