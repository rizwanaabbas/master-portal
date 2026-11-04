@php
$logCount = count($transaction_log);
$trayId = 'transaction-log-tray-' . uniqid();

function isTimestampKey($key)
{
    $timestampKeys = [
        'created_at', 'updated_at', 'deleted_at',
        'date_created', 'date_updated', 'date_modified',
        'createdat', 'lastupdated', 'lastupdatedat', 'createdtime', 'updatedtime',
        'completed_at', 'approved_at', 'rejected_at', 'processed_at',
        'confirmed_at', 'executed_at', 'verified_at', 'submitted_at',
        'transaction_date', 'date', 'timestamp', 'datetime', 'time',
        'expiry_date', 'expiration_date', 'valid_until',
    ];
    return in_array(strtolower(str_replace([' ', '-'], '_', $key)), $timestampKeys);
}

function formatTimestamp($value)
{
    if (empty($value) || $value === 'null' || $value === '0000-00-00 00:00:00') {
        return '<span style="color:#6c757d;">-</span>';
    }

    $str = (string) $value;

    // Purely numeric values – could be Unix timestamps (seconds or milliseconds)
    if (ctype_digit($str)) {
        $len = strlen($str);
        try {
            if ($len === 10) {
                $dt = \Carbon\Carbon::createFromTimestamp((int) $str);
            } elseif ($len === 13) {
                $dt = \Carbon\Carbon::createFromTimestampMs((int) $str);
            } else {
                // Unrecognizable numeric length – show raw
                return '<span style="color:#212529; word-break:break-word;">' . htmlspecialchars($str) . '</span>';
            }

            // Sanity check: must be between 2010 and 2035
            if ($dt->year < 2010 || $dt->year > 2035) {
                return '<span style="color:#212529; word-break:break-word;">' . htmlspecialchars($str) . '</span>';
            }

            return '<span class="tl-date-badge"><i class="fal fa-calendar mr-1"></i>' . $dt->format('d M Y, h:i A') . '</span>';
        } catch (\Exception $e) {
            return '<span style="color:#212529; word-break:break-word;">' . htmlspecialchars($str) . '</span>';
        }
    }

    // String values – try Carbon parse with sanity check
    try {
        $dt = \Carbon\Carbon::parse($str);

        if ($dt->year < 2010 || $dt->year > 2035) {
            return '<span style="color:#212529; word-break:break-word;">' . htmlspecialchars($str) . '</span>';
        }

        return '<span class="tl-date-badge"><i class="fal fa-calendar mr-1"></i>' . $dt->format('d M Y, h:i A') . '</span>';
    } catch (\Exception $e) {
        return '<span style="color:#212529; word-break:break-word;">' . htmlspecialchars($str) . '</span>';
    }
}

function isStatusKey($key)
{
    $statusKeys = [
        'status', 'sub_status', 'substatus',
        'internal_status', 'internalstatus',
        'payment_status', 'paymentstatus',
        'transaction_status', 'transactionstatus',
        'order_status', 'orderstatus',
        'approval_status', 'approvalstatus',
        'verification_status', 'verificationstatus',
        'processing_status', 'processingstatus',
        'deposit_status', 'depositstatus',
        'withdrawal_status', 'withdrawalstatus',
        'kyc_status', 'kycstatus',
        'compliance_status', 'compliancestatus',
        'statuscode'
    ];
    return in_array(strtolower(str_replace([' ', '-'], '_', $key)), $statusKeys);
}

function statusBadgeClass($value)
{
    $lower = strtolower(trim((string) $value));

    $success = ['approved', 'success', 'completed', 'confirmed', 'verified', 'active', 'done', 'accepted', 'settled', 'credited', 'paid', 'resolved', 'valid', 'enabled', 'successful', 'processed'];
    $danger  = ['rejected', 'failed', 'declined', 'error', 'cancelled', 'canceled', 'expired', 'inactive', 'disabled', 'refused', 'denied', 'fraud', 'suspended', 'blocked', 'unverified', 'returned', 'bounced'];
    $warning = ['pending', 'in progress', 'processing', 'initiated', 'waiting', 'queued', 'partial', 'hold', 'on hold', 'review', 'under review', 'manual review', 'flagged', 'warned', 'waiting_for_external_provider', 'waiting_for_aml'];
    $info    = ['submitted', 'sent', 'requested', 'created', 'started', 'new', 'open', 'draft', 'generated', 'discovered'];

    if (in_array($lower, $success)) return 'tl-badge-success';
    if (in_array($lower, $danger))  return 'tl-badge-danger';
    if (in_array($lower, $warning)) return 'tl-badge-warning';
    if (in_array($lower, $info))    return 'tl-badge-info';

    return 'tl-badge-secondary';
}

function renderJsonValue($value, $key = null, $level = 0)
{
    if ($key === 'blockchain_tx_id' && (is_string($value) || is_numeric($value))) {
        $url = 'https://www.blockchain.com/explorer/search?search=' . urlencode($value);
        return '<a href="' . $url . '" target="_blank" style="word-break:break-all;">' . htmlspecialchars((string)$value) . '</a>';
    }

    if (is_array($value) || is_object($value)) {
        $value = (array) $value;
        $html = '<div class="json-nested" style="margin-left:' . ($level * 12) . 'px; border-left:1px dashed #ced4da; padding-left:8px; margin-top:2px;">';
        foreach ($value as $k => $v) {
            $label = ucfirst(str_replace('_', ' ', $k));
            $html .= '<div style="margin:2px 0;">';
            $html .= '<span style="font-weight:600; color:#495057; font-size:0.85rem;">' . htmlspecialchars($label) . ':</span> ';
            $html .= renderJsonValue($v, $k, $level + 1);
            $html .= '</div>';
        }
        $html .= '</div>';
        return $html;
    }

    if (isTimestampKey($key)) {
        return formatTimestamp($value);
    }

    if (isStatusKey($key)) {
        $badgeClass = statusBadgeClass($value);
        return '<span class="' . $badgeClass . '">' . htmlspecialchars((string)$value) . '</span>';
    }

    return '<span style="color:#212529; word-break:break-word;">' . htmlspecialchars((string)$value) . '</span>';
}
@endphp

@if ($logCount > 0)

    <div class="text-right">
       <a href="javascript:void(0)" onclick="openTransactionLogTray('{{ $trayId }}')" class="btn btn-info ml-2 mx-md-2 waves-effect waves-themed"> <i class="fal fa-history"></i> Transaction log History ({{ $logCount }})</a>
    </div>
@endif

{{-- Slide-out Tray --}}
<div id="{{ $trayId }}" class="transaction-log-tray">
    <div class="transaction-log-tray-header">
        <h5 class="m-0 font-weight-bold text-primary">
            <i class="fal fa-history mr-2"></i>Transaction History
        </h5>
        <button type="button" class="transaction-log-tray-close" onclick="closeTransactionLogTray('{{ $trayId }}')">&times;</button>
    </div>
    <div class="transaction-log-tray-body">
        @if ($logCount > 0)
            <div class="tl-timeline">
                @foreach ($transaction_log as $log)
                    <div class="tl-item">
                        <div class="tl-marker"></div>
                        <div class="tl-content">
                            <div class="tl-meta">
                                <span class="tl-source">{{ $log->source }}</span>
                            </div>
                            <div class="tl-dates">
                                @if(!empty($log->date_created))
                                    <span class="tl-date"><i class="fal fa-calendar-alt mr-1"></i>{{ $log->date_created }}</span>
                                @endif
                                @if(!empty($log->created_at))
                                    <span class="tl-date"><i class="fal fa-calendar mr-1"></i>{{ \Carbon\Carbon::parse($log->created_at)->format('d M Y, h:i A') }}</span>
                                @endif
                                @if(!empty($log->updated_at) && $log->updated_at != $log->created_at)
                                    <span class="tl-date"><i class="fal fa-edit mr-1"></i>{{ \Carbon\Carbon::parse($log->updated_at)->format('d M Y, h:i A') }}</span>
                                @endif
                            </div>
                            <div class="tl-details">
                                @php
                                    $details = json_decode($log->details, true);
                                @endphp
                                @if(is_array($details))
                                    {{-- {{ dd($details) }} --}}
                                    @foreach($details as $key => $val)
                                        <div class="tl-detail-row">
                                            <span class="tl-key">{{ ucfirst(str_replace('_', ' ', $key)) }}:</span>
                                            {!! renderJsonValue($val, $key) !!}
                                        </div>
                                    @endforeach
                                @else
                                    <div class="tl-detail-row">{{ $log->details }}</div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center text-muted py-5">
                <i class="fal fa-history fa-3x mb-3" style="opacity:0.3;"></i>
                <p>No transaction history available.</p>
            </div>
        @endif
    </div>
</div>
<div id="{{ $trayId }}-overlay" class="transaction-log-overlay" onclick="closeTransactionLogTray('{{ $trayId }}')"></div>

<style>
/* Tray */
.transaction-log-tray {
    position: fixed;
    top: 0;
    right: -480px;
    width: 480px;
    max-width: 92vw;
    height: 100vh;
    background: #fff;
    box-shadow: -4px 0 24px rgba(0, 0, 0, 0.18);
    z-index: 9999;
    transition: right 0.35s cubic-bezier(0.4, 0, 0.2, 1);
    display: flex;
    flex-direction: column;
}
.transaction-log-tray.open {
    right: 0;
}
.transaction-log-tray-header {
    padding: 16px 20px;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-bottom: 1px solid #dee2e6;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-shrink: 0;
}
.transaction-log-tray-close {
    background: rgba(0,0,0,0.06);
    border: none;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    font-size: 22px;
    line-height: 1;
    cursor: pointer;
    color: #495057;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.2s, color 0.2s;
}
.transaction-log-tray-close:hover {
    background: rgba(0,0,0,0.12);
    color: #212529;
}
.transaction-log-tray-body {
    flex: 1;
    overflow-y: auto;
    padding: 20px;
    background: #f4f6f8;
}
.transaction-log-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(0, 0, 0, 0.45);
    z-index: 9998;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.3s ease, visibility 0.3s ease;
}
.transaction-log-overlay.open {
    opacity: 1;
    visibility: visible;
}

/* Timeline */
.tl-timeline {
    position: relative;
    padding-left: 8px;
}
.tl-timeline::before {
    content: '';
    position: absolute;
    left: 14px;
    top: 8px;
    bottom: 8px;
    width: 2px;
    background: linear-gradient(to bottom, #886ab5, #2196f3);
    border-radius: 2px;
}
.tl-item {
    position: relative;
    margin-bottom: 20px;
    padding-left: 28px;
}
.tl-marker {
    position: absolute;
    left: 0px;
    top: 6px;
    width: 14px;
    height: 14px;
    border-radius: 50%;
    background: #fff;
    border: 3px solid #886ab5;
    box-shadow: 0 0 0 2px rgba(136, 106, 181, 0.2);
    z-index: 1;
}
.tl-content {
    background: #fff;
    border-radius: 10px;
    padding: 14px 16px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}
.tl-meta {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
    margin-bottom: 6px;
    padding-bottom: 6px;
    border-bottom: 1px dashed #e2e8f0;
}
.tl-dates {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 6px;
    margin-bottom: 10px;
    padding-bottom: 10px;
    border-bottom: 1px dashed #e2e8f0;
}
.tl-date {
    font-size: 0.75rem;
    color: #6c757d;
    font-weight: 500;
    background: #f1f3f5;
    padding: 2px 8px;
    border-radius: 12px;
}
.tl-date-badge {
    font-size: 0.8rem;
    color: #495057;
    font-weight: 500;
    background: #e7f3ff;
    padding: 2px 10px;
    border-radius: 12px;
    border: 1px solid #b8daff;
}
.tl-badge-success {
    font-size: 0.75rem;
    font-weight: 600;
    color: #155724;
    background: #d4edda;
    border: 1px solid #c3e6cb;
    padding: 2px 10px;
    border-radius: 12px;
    text-transform: capitalize;
}
.tl-badge-danger {
    font-size: 0.75rem;
    font-weight: 600;
    color: #721c24;
    background: #f8d7da;
    border: 1px solid #f5c6cb;
    padding: 2px 10px;
    border-radius: 12px;
    text-transform: capitalize;
}
.tl-badge-warning {
    font-size: 0.75rem;
    font-weight: 600;
    color: #856404;
    background: #fff3cd;
    border: 1px solid #ffeeba;
    padding: 2px 10px;
    border-radius: 12px;
    text-transform: capitalize;
}
.tl-badge-info {
    font-size: 0.75rem;
    font-weight: 600;
    color: #0c5460;
    background: #d1ecf1;
    border: 1px solid #bee5eb;
    padding: 2px 10px;
    border-radius: 12px;
    text-transform: capitalize;
}
.tl-badge-secondary {
    font-size: 0.75rem;
    font-weight: 600;
    color: #383d41;
    background: #e2e3e5;
    border: 1px solid #d6d8db;
    padding: 2px 10px;
    border-radius: 12px;
    text-transform: capitalize;
}
.tl-source {
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    font-weight: 700;
    color: #fff;
    background: linear-gradient(135deg, #886ab5, #2196f3);
    padding: 3px 10px;
    border-radius: 20px;
}
.tl-details {
    font-size: 0.88rem;
}
.tl-detail-row {
    margin: 4px 0;
    line-height: 1.5;
}
.tl-key {
    font-weight: 600;
    color: #495057;
    font-size: 0.82rem;
}
.json-nested {
    background: #f8f9fa;
    border-radius: 6px;
    padding: 6px 8px !important;
    margin-top: 4px;
}
</style>

<script>
    function openTransactionLogTray(trayId) {
        var tray = document.getElementById(trayId);
        var overlay = document.getElementById(trayId + '-overlay');
        if (tray) tray.classList.add('open');
        if (overlay) overlay.classList.add('open');
        document.body.style.overflow = 'hidden';
    }

    function closeTransactionLogTray(trayId) {
        var tray = document.getElementById(trayId);
        var overlay = document.getElementById(trayId + '-overlay');
        if (tray) tray.classList.remove('open');
        if (overlay) overlay.classList.remove('open');
        document.body.style.overflow = '';
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.transaction-log-tray.open').forEach(function(tray) {
                closeTransactionLogTray(tray.id);
            });
        }
    });
</script>
