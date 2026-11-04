<?php

namespace App\Modules\Fireblocks\Helpers;

use App\Modules\Fireblocks\Support\FireblocksStatusCatalog;
use Fireblocks\Sdk\Exceptions\FireblocksException;
use Illuminate\Database\Eloquent\Builder;

/**
 * Maps Fireblocks primary transaction statuses and substatuses
 * to internal application statuses and human-readable messages.
 *
 * @see https://developers.fireblocks.com/reference/statuses
 * @see https://developers.fireblocks.com/reference/sub-statuses
 */
class FireblocksStatusHelper
{
    /**
     * Fireblocks statuses that map to our internal "Pending" state.
     */
    const PENDING_STATUSES = [
        'SUBMITTED',
        'PENDING_ENRICHMENT',
        'PENDING_AML_SCREENING',
        'PENDING_AUTHORIZATION',
        'QUEUED',
        'PENDING_SIGNATURE',
        'SIGNED',
        'PENDING_3RD_PARTY_MANUAL_APPROVAL',
        'PENDING_3RD_PARTY',
        'PENDING_EMAIL_APPROVAL',
        'BROADCASTING',
        'CONFIRMING',
        'PARTIALLY_COMPLETED',
    ];

    /** Complete list of known Fireblocks primary statuses (Fireblocks docs order). */
    public static function allPrimaryStatuses(): array
    {
        return FireblocksStatusCatalog::PRIMARY_ORDER;
    }

    /** @deprecated Use allPrimaryStatuses() */
    const ALL_PRIMARY_STATUSES = [
        'SUBMITTED',
        'PENDING_AML_SCREENING',
        'PENDING_ENRICHMENT',
        'PENDING_AUTHORIZATION',
        'QUEUED',
        'PENDING_SIGNATURE',
        'SIGNED',
        'PENDING_3RD_PARTY_MANUAL_APPROVAL',
        'PENDING_EMAIL_APPROVAL',
        'PENDING_3RD_PARTY',
        'BROADCASTING',
        'CONFIRMING',
        'PARTIALLY_COMPLETED',
        'COMPLETED',
        'CANCELLING',
        'CANCELLED',
        'BLOCKED',
        'REJECTED',
        'FAILED',
        'TIMEOUT',
    ];

    /** Primary statuses evaluated with the CONFIRMING/BROADCASTING stuck threshold. */
    const STUCK_CONFIRMING_PRIMARY = ['CONFIRMING', 'BROADCASTING'];

    /** Primary statuses evaluated with the PENDING_SIGNATURE/QUEUED stuck threshold. */
    const STUCK_SIGNATURE_PRIMARY = [
        'PENDING_SIGNATURE',
        'QUEUED',
        'PENDING_AUTHORIZATION',
        'PENDING_3RD_PARTY',
        'PENDING_3RD_PARTY_MANUAL_APPROVAL',
        'PENDING_EMAIL_APPROVAL',
    ];

    /** Other in-flight primaries that use the CONFIRMING threshold. */
    const STUCK_OTHER_PENDING_PRIMARY = [
        'SUBMITTED',
        'PENDING_AML_SCREENING',
        'PENDING_ENRICHMENT',
        'SIGNED',
        'PARTIALLY_COMPLETED',
    ];

    const APPROVED_STATUSES = [
        'COMPLETED',
    ];

    const NEEDS_REVIEW_SUBSTATUSES = [
        'COMPLETED_BUT_3RD_PARTY_FAILED',
        'COMPLETED_BUT_3RD_PARTY_REJECTED',
    ];

    const CANCELLED_STATUSES = [
        'CANCELLING',
        'CANCELLED',
    ];

    const REJECTED_STATUSES = [
        'REJECTED',
        'FAILED',
        'BLOCKED',
        'TIMEOUT',
    ];

    const BOOST_DROP_PROCESSING_PRIMARY = [
        'CONFIRMING',
        'BROADCASTING',
        'SUBMITTED',
        'PARTIALLY_COMPLETED',
    ];

    const BOOST_DROP_REJECTED_PRIMARY = [
        'FAILED',
        'REJECTED',
        'TIMEOUT',
        'BLOCKED',
    ];

    const BOOST_DROP_EXCLUDED_PRIMARY = [
        'COMPLETED',
        'CANCELLED',
        'CANCELLING',
    ];

    const EVM_ASSET_PREFIXES = ['ETH', 'MATIC', 'BNB', 'AVAX', 'FTM', 'ARB', 'OP', 'BASE', 'LINEA', 'SCROLL'];

    /** Sub-statuses indicating the vault lacked funds (usually no on-chain tx hash). */
    const VAULT_FUNDING_FAILURE_SUBSTATUSES = [
        'INSUFFICIENT_FUNDS',
        'INSUFFICIENT_FUNDS_FOR_FEE',
    ];

    const BENEFICIARY_APPROVED_STATUSES = ['APPROVED'];
    const BENEFICIARY_PENDING_STATUSES  = ['WAITING_FOR_APPROVAL', 'PENDING', 'PENDING_APPROVAL'];
    const BENEFICIARY_REJECTED_STATUSES = ['REJECTED', 'CANCELLED', 'FAILED'];

    const COMPLETED_CONFIRMATION_SUBSTATUSES = ['CONFIRMED', '3RD_PARTY_COMPLETED'];

    /** Category display order for filter dropdown optgroups. */
    private const SUBSTATUS_FILTER_CATEGORY_ORDER = [
        'Pending 3rd party',
        'Confirming',
        'Completed',
        'Blocked',
        'Blocked / AML',
        'Cancelled',
        'Rejected',
        'Failed — User input',
        'Failed — MPC',
        'Failed — Fireblocks system',
        'Failed — Third-party account',
        'Failed — Third-party system',
        'Failed — Blockchain',
    ];

    /**
     * All canonical sub-status codes => human-readable message.
     *
     * @return array<string, string>
     */
    public static function substatusMessages(): array
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }

        $cache = [];
        foreach (FireblocksStatusCatalog::substatuses() as $code => $meta) {
            if (isset($meta['canonical'])) {
                continue;
            }
            $cache[$code] = $meta['message'];
        }

        return $cache;
    }

    public static function beneficiaryStatusBadge(?string $status, ?bool $isActive = null): array
    {
        if (empty($status)) {
            if ($isActive === true) {
                return ['label' => 'Approved', 'class' => 'badge-success'];
            }

            return ['label' => 'Pending', 'class' => 'badge-warning'];
        }

        $normalized = strtoupper($status);

        if (in_array($normalized, self::BENEFICIARY_APPROVED_STATUSES, true)) {
            return ['label' => 'Approved', 'class' => 'badge-success'];
        }

        if (in_array($normalized, self::BENEFICIARY_REJECTED_STATUSES, true)) {
            return ['label' => ucfirst(strtolower($normalized)), 'class' => 'badge-danger'];
        }

        if (in_array($normalized, self::BENEFICIARY_PENDING_STATUSES, true)) {
            return ['label' => 'Pending', 'class' => 'badge-warning'];
        }

        return ['label' => ucwords(strtolower(str_replace('_', ' ', $normalized))), 'class' => 'badge-secondary'];
    }

    public static function mapToInternalStatus(string $fbStatus): string
    {
        $status = strtoupper($fbStatus);

        if (in_array($status, self::APPROVED_STATUSES, true)) {
            return 'Approved';
        }

        if (in_array($status, self::CANCELLED_STATUSES, true)) {
            return 'Cancelled';
        }

        if (in_array($status, self::REJECTED_STATUSES, true)) {
            return 'Rejected';
        }

        return 'Pending';
    }

    public static function getSubstatusMessage(?string $subStatus): string
    {
        if (empty($subStatus)) {
            return '';
        }

        return FireblocksStatusCatalog::substatusMessage($subStatus);
    }

    public static function substatusCategory(?string $subStatus): string
    {
        if (empty($subStatus)) {
            return '';
        }

        return FireblocksStatusCatalog::substatusCategory($subStatus);
    }

    public static function formatSubstatusDisplay(?string $subStatus, bool $includeCode = true): string
    {
        if ($subStatus === null || $subStatus === '') {
            return '—';
        }

        $message = self::getSubstatusMessage($subStatus);
        $category = self::substatusCategory($subStatus);
        $text = $message;
        if ($category !== '' && $category !== 'Other') {
            $text = '[' . $category . '] ' . $text;
        }
        if ($includeCode) {
            $text .= ' (' . strtoupper($subStatus) . ')';
        }

        return $text;
    }

    /**
     * Grouped sub-status options for module-config multiselect fields.
     *
     * @return array<string, array<string, string>>
     */
    public static function substatusMultiselectGroups(): array
    {
        return FireblocksStatusCatalog::substatusMultiselectGroups();
    }

    public static function primaryStatusLabel(string $fbStatus): string
    {
        return FireblocksStatusCatalog::primaryLabel($fbStatus);
    }

    /**
     * Bootstrap badge class for Fireblocks primary (API) status in monitoring UI.
     *
     * @return array{label: string, class: string}
     */
    /**
     * @return array<string, string> API code => badge CSS classes (includes "badge")
     */
    public static function primaryStatusBadgeClassMap(): array
    {
        return [
            'SUBMITTED' => 'badge fb-pri-submitted',
            'PENDING_AML_SCREENING' => 'badge fb-pri-pending-aml',
            'PENDING_ENRICHMENT' => 'badge fb-pri-pending-enrichment',
            'PENDING_AUTHORIZATION' => 'badge fb-pri-pending-auth',
            'QUEUED' => 'badge fb-pri-queued',
            'PENDING_SIGNATURE' => 'badge fb-pri-pending-signature',
            'SIGNED' => 'badge fb-pri-signed',
            'PENDING_3RD_PARTY_MANUAL_APPROVAL' => 'badge fb-pri-pending-3p-manual',
            'PENDING_EMAIL_APPROVAL' => 'badge fb-pri-pending-email',
            'PENDING_3RD_PARTY' => 'badge fb-pri-pending-3p',
            'BROADCASTING' => 'badge fb-pri-broadcasting',
            'CONFIRMING' => 'badge fb-pri-confirming',
            'PARTIALLY_COMPLETED' => 'badge fb-pri-partial',
            'COMPLETED' => 'badge fb-pri-completed',
            'CANCELLING' => 'badge fb-pri-cancelling',
            'CANCELLED' => 'badge fb-pri-cancelled',
            'BLOCKED' => 'badge fb-pri-blocked',
            'REJECTED' => 'badge fb-pri-rejected',
            'FAILED' => 'badge fb-pri-failed',
            'TIMEOUT' => 'badge fb-pri-timeout',
        ];
    }

    public static function primaryStatusBadge(string $fbStatus): array
    {
        $code = strtoupper(trim($fbStatus));
        $label = $code !== '' ? self::primaryStatusLabel($code) : 'Unknown';
        $class = self::primaryStatusBadgeClassMap()[$code] ?? 'badge badge-light';

        return ['label' => $label, 'class' => $class];
    }

    /**
     * @return array{label: string, class: string}
     */
    public static function substatusBadge(?string $subStatus): array
    {
        if ($subStatus === null || trim($subStatus) === '') {
            return ['label' => '—', 'class' => 'badge badge-light'];
        }

        $code = strtoupper(trim(str_replace(' ', '_', $subStatus)));
        $normalized = FireblocksStatusCatalog::normalizeSubstatusCode($code) ?? $code;
        $label = $code;
        $category = self::substatusCategory($normalized);

        $class = match ($category) {
            'Pending 3rd party' => 'badge fb-sub-pending',
            'Confirming' => 'badge fb-sub-confirming',
            'Completed' => 'badge fb-sub-completed',
            'Blocked' => 'badge fb-sub-blocked',
            'Blocked / AML' => 'badge fb-sub-blocked-aml',
            'Cancelled' => 'badge fb-sub-cancelled',
            'Rejected' => 'badge fb-sub-rejected',
            'Failed — User input' => 'badge fb-sub-failed-user',
            'Failed — MPC' => 'badge fb-sub-failed-mpc',
            'Failed — Fireblocks system' => 'badge fb-sub-failed-fb',
            'Failed — Third-party account' => 'badge fb-sub-failed-3p-acct',
            'Failed — Third-party system' => 'badge fb-sub-failed-3p-sys',
            'Failed — Blockchain' => 'badge fb-sub-failed-chain',
            default => 'badge fb-sub-other',
        };

        if ($normalized === 'BLOCKED_BY_POLICY') {
            $class = 'badge fb-sub-blocked';
        }

        return ['label' => $label, 'class' => $class];
    }

    /**
     * Primary status options for dashboard / module-config filter dropdowns.
     *
     * @return array<string, string> code => Fireblocks Console label
     */
    public static function primaryStatusFilterOptions(): array
    {
        $options = [];
        foreach (self::allPrimaryStatuses() as $code) {
            $options[$code] = self::primaryStatusLabel($code);
        }

        return $options;
    }

    /**
     * Internal (portal rollup) status options for monitoring dashboard filters.
     *
     * @return array<string, string> stored internal_status value => label
     */
    public static function internalStatusFilterOptions(): array
    {
        return [
            'Pending' => 'Pending',
            'Approved' => 'Approved',
            'Cancelled' => 'Cancelled',
            'Rejected' => 'Rejected',
            'Needs Review' => 'Needs Review',
        ];
    }

    /**
     * Dashboard filter: match effective internal status (same rules as resolveForMonitoring / grid badge).
     */
    public static function applyInternalStatusFilter(Builder $query, string $internalStatus, string $table = 'fireblocks_transactions'): void
    {
        if ($internalStatus === '' || $internalStatus === 'all' || !array_key_exists($internalStatus, self::internalStatusFilterOptions())) {
            return;
        }

        $statusCol = $table . '.status';
        $subCol = $table . '.sub_status';
        $internalCol = $table . '.internal_status';
        $responseCol = $table . '.response_data';

        $completedTerminalSubs = array_values(array_unique(array_merge(
            self::COMPLETED_CONFIRMATION_SUBSTATUSES,
            self::NEEDS_REVIEW_SUBSTATUSES
        )));

        $query->where(function (Builder $q) use (
            $internalStatus,
            $statusCol,
            $subCol,
            $internalCol,
            $responseCol,
            $completedTerminalSubs,
        ) {
            switch ($internalStatus) {
                case 'Approved':
                    $q->where($internalCol, 'Approved')
                        ->orWhere(function (Builder $c) use ($statusCol, $subCol) {
                            $c->whereRaw('UPPER(' . $statusCol . ') = ?', ['COMPLETED'])
                                ->whereIn($subCol, self::COMPLETED_CONFIRMATION_SUBSTATUSES);
                        });
                    break;
                case 'Needs Review':
                    $q->where($internalCol, 'Needs Review')
                        ->orWhere(function (Builder $c) use ($statusCol, $subCol) {
                            $c->whereRaw('UPPER(' . $statusCol . ') = ?', ['COMPLETED'])
                                ->whereIn($subCol, self::NEEDS_REVIEW_SUBSTATUSES);
                        });
                    break;
                case 'Cancelled':
                    $q->where($internalCol, 'Cancelled')
                        ->orWhere(function (Builder $c) use ($statusCol) {
                            self::whereUpperStatusIn($c, $statusCol, self::CANCELLED_STATUSES);
                        });
                    break;
                case 'Rejected':
                    $q->where($internalCol, 'Rejected')
                        ->orWhere(function (Builder $c) use ($statusCol, $internalCol, $responseCol) {
                            self::whereUpperStatusIn($c, $statusCol, self::REJECTED_STATUSES);
                            $c->where(function (Builder $exclude) use ($internalCol, $responseCol) {
                                $exclude->where($internalCol, '!=', 'Pending')
                                    ->orWhereNull($internalCol)
                                    ->orWhere(function (Builder $noFailedSubmit) use ($responseCol) {
                                        $noFailedSubmit->whereNull($responseCol)
                                            ->orWhere($responseCol, 'NOT LIKE', '%"submission_failed":true%');
                                    });
                            });
                        });
                    break;
                case 'Pending':
                default:
                    $q->where($internalCol, 'Pending')
                        ->orWhere(function (Builder $c) use ($statusCol) {
                            self::whereUpperStatusIn($c, $statusCol, self::PENDING_STATUSES);
                        })
                        ->orWhere(function (Builder $c) use ($statusCol, $subCol, $completedTerminalSubs) {
                            $c->whereRaw('UPPER(' . $statusCol . ') = ?', ['COMPLETED'])
                                ->where(function (Builder $sub) use ($subCol, $completedTerminalSubs) {
                                    $sub->whereNull($subCol)
                                        ->orWhereNotIn($subCol, $completedTerminalSubs);
                                });
                        })
                        ->orWhere(function (Builder $c) use ($internalCol, $responseCol) {
                            $c->where($responseCol, 'LIKE', '%"submission_failed":true%')
                                ->where($internalCol, 'Pending');
                        });
                    break;
            }
        });
    }

    /**
     * @param  array<int, string>  $statuses
     */
    protected static function whereUpperStatusIn(Builder $query, string $statusColumn, array $statuses): void
    {
        $normalized = array_values(array_unique(array_map('strtoupper', $statuses)));
        if ($normalized === []) {
            return;
        }

        $placeholders = implode(', ', array_fill(0, count($normalized), '?'));
        $query->whereRaw('UPPER(' . $statusColumn . ') IN (' . $placeholders . ')', $normalized);
    }

    /**
     * All sub-status codes that should match a filter value (canonical + aliases).
     *
     * @return array<int, string>
     */
    public static function substatusFilterMatchCodes(string $code): array
    {
        $upper = strtoupper(trim($code));
        $normalized = FireblocksStatusCatalog::normalizeSubstatusCode($upper) ?? $upper;
        $codes = [$upper, $normalized];

        foreach (FireblocksStatusCatalog::substatuses() as $alias => $meta) {
            if (($meta['canonical'] ?? null) === $normalized) {
                $codes[] = $alias;
            }
        }

        return array_values(array_unique($codes));
    }

    /**
     * Whether a transaction's primary and/or sub-status matches an eligibility list.
     * List entries may be primary statuses (e.g. CONFIRMING) or sub-statuses (e.g. FAIL_ON_LOW_FEE).
     *
     * @param  array<int, string>  $list
     */
    public static function matchesActionEligibleList(?string $primaryStatus, ?string $subStatus, array $list): bool
    {
        if (empty($list)) {
            return false;
        }

        $primary = strtoupper(trim((string) $primaryStatus));
        $rawSub = strtoupper(trim(str_replace(' ', '_', (string) $subStatus)));
        $normalizedSub = $rawSub !== ''
            ? (FireblocksStatusCatalog::normalizeSubstatusCode($rawSub) ?? $rawSub)
            : '';

        $normalizedList = [];
        foreach ($list as $item) {
            $upper = strtoupper(trim(str_replace(' ', '_', (string) $item)));
            if ($upper === '') {
                continue;
            }
            $normalizedList[] = FireblocksStatusCatalog::normalizeSubstatusCode($upper) ?? $upper;
        }
        $normalizedList = array_values(array_unique($normalizedList));

        if ($primary !== '' && in_array($primary, $normalizedList, true)) {
            return true;
        }

        if ($rawSub === '' && $normalizedSub === '') {
            return false;
        }

        $txSubCodes = FireblocksStatusCatalog::substatusMatchCodes($rawSub !== '' ? $rawSub : $normalizedSub);
        $listSubCodes = [];
        foreach ($normalizedList as $item) {
            $listSubCodes[] = $item;
            $listSubCodes = array_merge($listSubCodes, FireblocksStatusCatalog::substatusMatchCodes($item));
        }

        return !empty(array_intersect($txSubCodes, array_unique($listSubCodes)));
    }

    public static function isVaultFundingFailureSubstatus(?string $subStatus): bool
    {
        if ($subStatus === null || $subStatus === '') {
            return false;
        }

        $normalized = FireblocksStatusCatalog::normalizeSubstatusCode($subStatus)
            ?? strtoupper(trim(str_replace(' ', '_', $subStatus)));

        return in_array($normalized, self::VAULT_FUNDING_FAILURE_SUBSTATUSES, true);
    }

    public static function boostDisabledReason(?string $subStatus, bool $hasTxHash): ?string
    {
        if ($hasTxHash) {
            return null;
        }

        if (self::isVaultFundingFailureSubstatus($subStatus)) {
            return 'Insufficient funds failures are usually not on-chain. Fund the vault and use Resubmit. Boost requires a tx hash (RBF).';
        }

        return 'Boost requires an on-chain transaction hash (RBF).';
    }

    public static function dropDisabledReason(?string $subStatus, bool $hasTxHash, bool $isEvmAsset): ?string
    {
        if (!$isEvmAsset) {
            return 'Drop is only supported on EVM chains.';
        }

        if ($hasTxHash) {
            return null;
        }

        if (self::isVaultFundingFailureSubstatus($subStatus)) {
            return 'Insufficient funds failures are usually not on-chain. Fund the vault and use Resubmit. Drop requires a tx hash.';
        }

        return 'Drop requires an on-chain transaction hash.';
    }

    public static function internalStatusBadge(string $internalStatus): array
    {
        return match ($internalStatus) {
            'Approved' => ['label' => 'Approved', 'class' => 'badge fb-int-approved'],
            'Cancelled' => ['label' => 'Cancelled', 'class' => 'badge fb-int-cancelled'],
            'Rejected' => ['label' => 'Rejected', 'class' => 'badge fb-int-rejected'],
            'Needs Review' => ['label' => 'Needs Review', 'class' => 'badge fb-int-needs-review'],
            default => ['label' => $internalStatus ?: 'Pending', 'class' => 'badge fb-int-pending'],
        };
    }

    public static function monitoringStateBadge(string $state): array
    {
        return match ($state) {
            'stuck' => ['label' => 'Stuck', 'class' => 'badge fb-mon-stuck'],
            'action_pending' => ['label' => 'Action Pending', 'class' => 'badge fb-mon-action-pending'],
            'boosted' => ['label' => 'Boosted', 'class' => 'badge fb-mon-boosted'],
            'dropped' => ['label' => 'Dropped', 'class' => 'badge fb-mon-dropped'],
            'resubmitted' => ['label' => 'Resubmitted', 'class' => 'badge fb-mon-resubmitted'],
            'needs_review' => ['label' => 'Needs Review', 'class' => 'badge fb-mon-needs-review'],
            default => ['label' => 'Normal', 'class' => 'badge fb-mon-normal'],
        };
    }

    /**
     * @return array{status: string, sub_status: ?string, error_code: ?string}
     */
    public static function resolveStoredPrimaryStatus(object $record): string
    {
        $status = strtoupper(trim((string) ($record->status ?? '')));
        if ($status !== '') {
            return $status;
        }

        $data = self::decodeRecordResponseData($record);
        if (is_array($data)) {
            $fromData = strtoupper(trim((string) ($data['fireblocks_status'] ?? '')));
            if ($fromData !== '') {
                return $fromData;
            }
        }

        return self::isFailedSubmissionPending($record) ? 'FAILED' : '';
    }

    public static function resolveStoredSubStatus(object $record): ?string
    {
        $sub = trim((string) ($record->sub_status ?? ''));
        if ($sub !== '') {
            return FireblocksStatusCatalog::normalizeSubstatusCode($sub) ?? strtoupper($sub);
        }

        $data = self::decodeRecordResponseData($record);
        if (is_array($data)) {
            $fromData = trim((string) ($data['fireblocks_sub_status'] ?? ''));
            if ($fromData !== '') {
                return FireblocksStatusCatalog::normalizeSubstatusCode($fromData) ?? strtoupper($fromData);
            }
        }

        if (self::isFailedSubmissionPending($record)) {
            $errorCode = is_array($data) ? ($data['fireblocks_code'] ?? null) : null;

            return self::inferSubStatusFromApiErrorMessage((string) ($record->error_message ?? ''), $errorCode);
        }

        return null;
    }

    public static function resolveFailedSubmissionFromThrowable(\Throwable $e): array
    {
        $message = trim($e->getMessage());
        $errorData = $e instanceof FireblocksException ? ($e->getErrorData() ?? []) : [];
        $errorCode = $e instanceof FireblocksException ? $e->getErrorCode() : null;

        $status = self::pickErrorField($errorData, ['status', 'transactionStatus', 'primaryStatus']) ?? 'FAILED';
        $subStatus = self::pickErrorField($errorData, ['subStatus', 'sub_status', 'substatus']);

        if ($subStatus === null || $subStatus === '') {
            $subStatus = self::inferSubStatusFromApiErrorMessage($message, $errorCode);
        } elseif ($subStatus !== null) {
            $subStatus = FireblocksStatusCatalog::normalizeSubstatusCode($subStatus) ?? strtoupper(trim($subStatus));
        }

        return [
            'status' => strtoupper(trim($status)),
            'sub_status' => ($subStatus !== null && $subStatus !== '') ? $subStatus : null,
            'error_code' => $errorCode,
        ];
    }

    public static function inferSubStatusFromApiErrorMessage(string $message, ?string $errorCode = null): ?string
    {
        $upper = strtoupper(trim($message));

        if (str_contains($upper, 'INSUFFICIENT') && str_contains($upper, 'FEE')) {
            return 'INSUFFICIENT_FUNDS_FOR_FEE';
        }

        if (
            str_contains($upper, 'INSUFFICIENT')
            || str_contains($upper, 'NOT ENOUGH')
            || str_contains($upper, 'NO VAULT ACCOUNT WITH SUFFICIENT')
        ) {
            return 'INSUFFICIENT_FUNDS';
        }

        if (str_contains($upper, 'INVALID') && str_contains($upper, 'ADDRESS')) {
            return 'INVALID_ADDRESS';
        }

        if (str_contains($upper, 'UNSUPPORTED ASSET')) {
            return 'UNSUPPORTED_ASSET';
        }

        if ($errorCode !== null && $errorCode !== '') {
            $code = strtoupper(preg_replace('/[^A-Z0-9_]+/', '_', $errorCode) ?? '');

            return $code !== '' ? $code : null;
        }

        return null;
    }

    public static function isLowCustodySubmissionFailure(?string $subStatus, string $message): bool
    {
        if (in_array($subStatus, ['INSUFFICIENT_FUNDS', 'INSUFFICIENT_FUNDS_FOR_FEE'], true)) {
            return true;
        }

        $upper = strtoupper($message);

        return str_contains($upper, 'INSUFFICIENT')
            || str_contains($upper, 'NOT ENOUGH')
            || str_contains($upper, 'NO VAULT ACCOUNT WITH SUFFICIENT');
    }

    public static function buildFailedSubmissionPendingReason(string $status, ?string $subStatus, string $apiMessage): string
    {
        $resolved = self::resolve($status, $subStatus);
        $base = trim($resolved['message']);
        $apiMessage = trim($apiMessage);

        if ($apiMessage !== '' && !str_contains($base, $apiMessage)) {
            $base .= ' — ' . $apiMessage;
        }

        return $base . ' Resubmit from Transaction Monitoring when resolved.';
    }

    /**
     * @param  array<string, mixed>  $errorData
     */
    protected static function pickErrorField(array $errorData, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (isset($errorData[$key]) && is_scalar($errorData[$key])) {
                $value = trim((string) $errorData[$key]);

                return $value !== '' ? $value : null;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function decodeRecordResponseData(object $record): ?array
    {
        $data = $record->response_data ?? null;
        if (is_string($data)) {
            $data = json_decode($data, true);
        }

        return is_array($data) ? $data : null;
    }

    /** Fireblocks create failed locally (local-* id) — show as Pending, not Rejected. */
    public static function isFailedSubmissionPending(object $record): bool
    {
        $data = self::decodeRecordResponseData($record);
        if ($data === null || empty($data['submission_failed'])) {
            return false;
        }

        return strcasecmp(trim((string) ($record->internal_status ?? '')), 'Pending') === 0;
    }

    /**
     * @deprecated Use isFailedSubmissionPending()
     */
    public static function isCustodyBalancePending(object $record): bool
    {
        $data = self::decodeRecordResponseData($record);

        return is_array($data) && !empty($data['custody_balance_pending']);
    }

    /**
     * Monitoring grid/detail: failed create → Pending with Fireblocks primary + sub-status on the row.
     */
    public static function resolveForMonitoring(object $record): array
    {
        if (self::isFailedSubmissionPending($record)) {
            $fbStatus = self::resolveStoredPrimaryStatus($record) ?: 'FAILED';
            $fbSub = self::resolveStoredSubStatus($record);

            $reason = trim((string) ($record->pending_reason ?? ''));
            if ($reason === '') {
                $reason = self::buildFailedSubmissionPendingReason(
                    $fbStatus,
                    is_string($fbSub) ? $fbSub : null,
                    (string) ($record->error_message ?? '')
                );
            }

            return [
                'internal_status' => 'Pending',
                'primary_status'  => $fbStatus,
                'sub_status'      => $fbSub,
                'message'         => $reason,
            ];
        }

        return self::resolve((string) ($record->status ?? ''), $record->sub_status ?? null);
    }

    public static function resolve(string $fbStatus, ?string $fbSubStatus = null): array
    {
        $internalStatus = self::mapToInternalStatus($fbStatus);
        $subMessage = self::getSubstatusMessage($fbSubStatus);

        if ($internalStatus === 'Approved') {
            if (in_array($fbSubStatus, self::NEEDS_REVIEW_SUBSTATUSES, true)) {
                $internalStatus = 'Needs Review';
            } elseif (!in_array($fbSubStatus, self::COMPLETED_CONFIRMATION_SUBSTATUSES, true)) {
                $internalStatus = 'Pending';
            }
        }

        $message = 'Fireblocks status: ' . $fbStatus;
        if ($fbSubStatus) {
            $message .= ' (' . $fbSubStatus . ')';
        }
        if ($subMessage) {
            $message .= ' — ' . $subMessage;
        }

        return [
            'internal_status' => $internalStatus,
            'primary_status'  => $fbStatus,
            'sub_status'      => $fbSubStatus,
            'message'         => $message,
        ];
    }

    public static function boostDropBestPracticePrimaryStatuses(): array
    {
        return array_values(array_unique(array_merge(
            self::BOOST_DROP_PROCESSING_PRIMARY,
            self::PENDING_STATUSES,
            self::BOOST_DROP_REJECTED_PRIMARY
        )));
    }

    /** Default Resubmit-eligible codes when the textarea is empty. */
    public static function defaultResubmitEligibleStatuses(): array
    {
        return array_values(array_unique(array_merge(
            self::REJECTED_STATUSES,
            ['FAIL_ON_LOW_FEE', 'REJECTED_BY_BLOCKCHAIN', 'DROPPED_BY_BLOCKCHAIN']
        )));
    }

    /**
     * Resubmit allowed when resubmit-eligible list matches, or retriable sub-status on failed primary.
     *
     * @param  array<int, string>  $resubmitEligible
     * @param  array<int, string>  $retriableSubstatuses
     */
    public static function isResubmitAllowedByConfig(
        ?string $primaryStatus,
        ?string $subStatus,
        array $resubmitEligible,
        array $retriableSubstatuses,
        bool $useDefaultEligibleWhenEmpty = true
    ): bool {
        $eligible = !empty($resubmitEligible)
            ? $resubmitEligible
            : ($useDefaultEligibleWhenEmpty ? self::defaultResubmitEligibleStatuses() : []);

        if (self::matchesActionEligibleList($primaryStatus, $subStatus, $eligible)) {
            return true;
        }

        $primary = strtoupper(trim((string) $primaryStatus));
        if ($primary !== ''
            && in_array($primary, self::BOOST_DROP_REJECTED_PRIMARY, true)
            && self::isRetriableSubstatus($subStatus, $retriableSubstatuses)) {
            return true;
        }

        return false;
    }

    public static function isNonRetriableSubstatus(?string $subStatus, array $nonRetriableList): bool
    {
        if ($subStatus === null || $subStatus === '') {
            return false;
        }

        $normalized = FireblocksStatusCatalog::normalizeSubstatusCode($subStatus) ?? strtoupper($subStatus);
        $normalizedList = array_map(
            fn ($code) => FireblocksStatusCatalog::normalizeSubstatusCode($code) ?? strtoupper($code),
            $nonRetriableList
        );

        return in_array($normalized, $normalizedList, true);
    }

    public static function isRetriableSubstatus(?string $subStatus, array $retriableList): bool
    {
        if ($subStatus === null || $subStatus === '') {
            return false;
        }

        $normalized = FireblocksStatusCatalog::normalizeSubstatusCode($subStatus) ?? strtoupper($subStatus);
        $normalizedList = array_map(
            fn ($code) => FireblocksStatusCatalog::normalizeSubstatusCode($code) ?? strtoupper($code),
            $retriableList
        );

        return in_array($normalized, $normalizedList, true);
    }

    /**
     * Non-retriable blocks an action unless the user explicitly listed this tx in that action's config textarea.
     *
     * @param  array<int, string>  $actionEligibleList
     * @param  array<int, string>  $nonRetriableList
     */
    public static function isBlockedByNonRetriableForAction(
        ?string $primaryStatus,
        ?string $subStatus,
        array $nonRetriableList,
        array $actionEligibleList,
        bool $userConfiguredActionList
    ): bool {
        if ($userConfiguredActionList
            && self::matchesActionEligibleList($primaryStatus, $subStatus, $actionEligibleList)) {
            return false;
        }

        return self::isNonRetriableSubstatus($subStatus, $nonRetriableList);
    }

    public static function isBoostDropPrimaryEligible(string $fbStatus, ?array $overrideList = null): bool
    {
        $status = strtoupper(trim($fbStatus));
        if ($status === '' || in_array($status, self::BOOST_DROP_EXCLUDED_PRIMARY, true)) {
            return false;
        }

        $allowed = $overrideList ?: self::boostDropBestPracticePrimaryStatuses();

        return in_array($status, $allowed, true);
    }

    public static function isBoostDropRejectedBucketEligible(string $fbStatus, ?string $subStatus, array $retriableList): bool
    {
        $status = strtoupper(trim($fbStatus));

        return in_array($status, self::BOOST_DROP_REJECTED_PRIMARY, true)
            && self::isRetriableSubstatus($subStatus, $retriableList);
    }

    public static function isEvmAsset(?string $assetId): bool
    {
        if ($assetId === null || $assetId === '') {
            return false;
        }

        $upper = strtoupper($assetId);
        foreach (self::EVM_ASSET_PREFIXES as $prefix) {
            if ($upper === $prefix || strpos($upper, $prefix . '_') === 0) {
                return true;
            }
        }

        return str_contains($upper, '_ETH') || str_contains($upper, '_ERC20');
    }

    /**
     * Grouped sub-status options for dashboard filter dropdowns.
     *
     * @return array<string, array<string, string>>
     */
    public static function substatusFilterOptions(): array
    {
        $groups = [];
        foreach (FireblocksStatusCatalog::substatuses() as $code => $meta) {
            if (isset($meta['canonical'])) {
                continue;
            }
            $groups[$meta['category']][$code] = $meta['message'];
        }

        $ordered = [];
        foreach (self::SUBSTATUS_FILTER_CATEGORY_ORDER as $category) {
            if (!empty($groups[$category])) {
                ksort($groups[$category]);
                $ordered[$category] = $groups[$category];
                unset($groups[$category]);
            }
        }

        ksort($groups);
        foreach ($groups as $category => $items) {
            ksort($items);
            $ordered[$category] = $items;
        }

        return $ordered;
    }

    public static function primaryStatusActionHint(string $fbStatus): string
    {
        $status = strtoupper($fbStatus);
        if (in_array($status, self::BOOST_DROP_EXCLUDED_PRIMARY, true)) {
            return 'No manual Boost/Drop';
        }
        if (in_array($status, self::BOOST_DROP_PROCESSING_PRIMARY, true)) {
            return 'Boost/Drop when stuck on-chain (requires tx hash for Boost)';
        }
        if ($status === 'SIGNED') {
            return 'Sign-only — caller broadcasts; no Boost/Drop until on-chain';
        }
        if (in_array($status, self::PENDING_STATUSES, true)) {
            return 'Boost/Drop shown; may require tx hash once broadcast';
        }
        if (in_array($status, self::BOOST_DROP_REJECTED_PRIMARY, true)) {
            return 'Boost/Drop if retriable sub-status; Resubmit if withdrawal failed';
        }

        return 'Sync only';
    }

    public static function statusReferenceCatalog(?array $config = null): array
    {
        $config = $config ?? [];
        $retriable = $config['retriable_substatuses'] ?? [];
        $nonRetriable = $config['non_retriable_substatuses'] ?? [];
        $boostAllowed = !empty($config['boost_eligible_primary_statuses'])
            ? $config['boost_eligible_primary_statuses']
            : self::boostDropBestPracticePrimaryStatuses();
        $dropAllowed = !empty($config['drop_eligible_primary_statuses'])
            ? $config['drop_eligible_primary_statuses']
            : self::boostDropBestPracticePrimaryStatuses();
        $resubmitAllowed = !empty($config['resubmit_eligible_primary_statuses'])
            ? $config['resubmit_eligible_primary_statuses']
            : array_merge(
                self::REJECTED_STATUSES,
                ['FAIL_ON_LOW_FEE', 'REJECTED_BY_BLOCKCHAIN', 'DROPPED_BY_BLOCKCHAIN']
            );

        $primaryStatuses = [];
        foreach (self::allPrimaryStatuses() as $code) {
            $resolved = self::resolve($code, null);
            $primaryBadge = self::primaryStatusBadge($code);
            $primaryStatuses[] = [
                'code' => $code,
                'label' => self::primaryStatusLabel($code),
                'console_label' => self::primaryStatusLabel($code),
                'default_internal' => $resolved['internal_status'],
                'badge_class' => $primaryBadge['class'],
                'badge_label' => $primaryBadge['label'],
                'ops_action_hint' => self::primaryStatusActionHint($code),
                'boost' => self::formatActionCatalogCell($code, $boostAllowed, 'boost'),
                'drop' => self::formatActionCatalogCell($code, $dropAllowed, 'drop'),
                'resubmit' => self::formatResubmitCatalogCell($code, $resubmitAllowed),
            ];
        }

        $substatuses = [];
        foreach (self::substatusMessages() as $code => $message) {
            $retriableFlag = self::isRetriableSubstatus($code, $retriable) ? 'Yes'
                : (self::isNonRetriableSubstatus($code, $nonRetriable) ? 'No' : '—');
            $subBadge = self::substatusBadge($code);
            $substatuses[] = [
                'code' => $code,
                'category' => self::substatusCategory($code),
                'message' => $message,
                'badge_class' => $subBadge['class'],
                'badge_label' => $subBadge['label'],
                'retriable' => $retriableFlag,
                'boost' => in_array($code, $boostAllowed, true) ? 'Yes' : '—',
                'drop' => in_array($code, $dropAllowed, true) ? 'Yes (EVM only)' : '—',
                'resubmit' => in_array($code, $resubmitAllowed, true) ? 'Yes' : '—',
                'ops_action_hint' => $retriableFlag === 'Yes'
                    ? 'May qualify for Boost/Drop/Resubmit when listed in module config'
                    : ($retriableFlag === 'No' ? 'Resolve root cause manually' : 'Add code to action-eligibility textarea in module config'),
            ];
        }

        $internalStatuses = [
            ['label' => 'Pending', 'description' => 'Transaction still processing or awaiting confirmations.', 'sources' => 'Most PENDING_* primaries; COMPLETED without CONFIRMED sub-status; SIGNED (sign-only)', 'badge_class' => 'badge fb-int-pending'],
            ['label' => 'Approved', 'description' => 'Successfully completed.', 'sources' => 'COMPLETED + CONFIRMED or 3RD_PARTY_COMPLETED', 'badge_class' => 'badge fb-int-approved'],
            ['label' => 'Cancelled', 'description' => 'Cancelled before completion.', 'sources' => 'CANCELLED, CANCELLING', 'badge_class' => 'badge fb-int-cancelled'],
            ['label' => 'Rejected', 'description' => 'Failed, blocked, or timed out.', 'sources' => 'FAILED, REJECTED, BLOCKED, TIMEOUT', 'badge_class' => 'badge fb-int-rejected'],
            ['label' => 'Needs Review', 'description' => 'On-chain success but downstream issue.', 'sources' => 'COMPLETED + COMPLETED_BUT_3RD_PARTY_* sub-statuses', 'badge_class' => 'badge fb-int-needs-review'],
        ];

        return [
            'primary_statuses' => $primaryStatuses,
            'substatuses' => $substatuses,
            'internal_statuses' => $internalStatuses,
        ];
    }

    protected static function formatActionCatalogCell(string $code, array $allowed, string $action): string
    {
        if (!in_array($code, $allowed, true)) {
            return 'No';
        }

        return $action === 'drop' ? 'Yes (EVM only)' : 'Yes';
    }

    protected static function formatResubmitCatalogCell(string $code, array $allowed): string
    {
        return in_array($code, $allowed, true) ? 'Yes' : 'No';
    }
}
