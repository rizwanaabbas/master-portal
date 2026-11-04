<?php

namespace App\Modules\Fireblocks\Support;

/**
 * Canonical Fireblocks primary + sub-status catalog.
 *
 * @see https://developers.fireblocks.com/reference/statuses
 * @see https://developers.fireblocks.com/reference/sub-statuses
 */
class FireblocksStatusCatalog
{
    /** @var array<string, string> Fireblocks Console label by API code. */
    public const PRIMARY_LABELS = [
        'SUBMITTED'                       => 'Submitted',
        'PENDING_AML_SCREENING'           => 'Pending Screening',
        'PENDING_ENRICHMENT'              => 'Pending Security Screening',
        'PENDING_AUTHORIZATION'           => 'Pending Authorization',
        'QUEUED'                          => 'Queued',
        'PENDING_SIGNATURE'               => 'Pending Signature',
        'SIGNED'                          => 'Signed',
        'PENDING_3RD_PARTY_MANUAL_APPROVAL' => 'Pending email approval',
        'PENDING_EMAIL_APPROVAL'          => 'Pending email approval',
        'PENDING_3RD_PARTY'               => 'Processing at the exchange',
        'BROADCASTING'                    => 'Broadcasting',
        'CONFIRMING'                      => 'Confirming',
        'PARTIALLY_COMPLETED'             => 'Partially Completed',
        'COMPLETED'                       => 'Completed',
        'CANCELLING'                      => 'Cancelling',
        'CANCELLED'                       => 'Cancelled',
        'BLOCKED'                         => 'Blocked by policy',
        'REJECTED'                        => 'Rejected',
        'FAILED'                          => 'Failed',
        'TIMEOUT'                         => 'Timeout',
    ];

    /** Primary statuses in Fireblocks documentation order (+ legacy API codes). */
    public const PRIMARY_ORDER = [
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

    /**
     * Sub-status catalog: message, UI category, optional alias codes.
     *
     * @return array<string, array{message: string, category: string, aliases?: array<int, string>, retriable?: bool, non_retriable?: bool}>
     */
    public static function substatuses(): array
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }

        $entries = [
            // Pending 3rd party
            '3RD_PARTY_PROCESSING' => ['category' => 'Pending 3rd party', 'message' => 'Awaiting approval by a third-party service (e.g., exchange).'],
            '3RD_PARTY_PENDING_SERVICE_MANUAL_APPROVAL' => ['category' => 'Pending 3rd party', 'message' => 'Fireblocks is waiting for the third-party service to manually approve this transaction.'],
            'PENDING_3RD_PARTY_MANUAL_APPROVAL' => ['category' => 'Pending 3rd party', 'message' => 'The third-party service is waiting for customer approval (email or app).'],

            // Confirming
            '3RD_PARTY_CONFIRMING' => ['category' => 'Confirming', 'message' => 'Awaiting confirmation by a third-party service (e.g., exchange).'],
            'PENDING_BLOCKCHAIN_CONFIRMATIONS' => ['category' => 'Confirming', 'message' => 'Awaiting confirmation on the blockchain.'],

            // Completed
            '3RD_PARTY_COMPLETED' => ['category' => 'Completed', 'message' => 'Completed on a third-party service (e.g., exchange).'],
            'COMPLETED_BUT_3RD_PARTY_FAILED' => ['category' => 'Completed', 'message' => 'Completed on the blockchain but failed on the third-party platform.'],
            'COMPLETED_BUT_3RD_PARTY_REJECTED' => ['category' => 'Completed', 'message' => 'Completed on the blockchain but rejected by the third-party platform.'],
            'CONFIRMED' => ['category' => 'Completed', 'message' => 'Reached the required number of confirmations on the blockchain.'],

            // Blocked
            'BLOCKED_BY_POLICY' => ['category' => 'Blocked', 'message' => 'Blocked due to a Transaction Authorization Policy (TAP) rule.', 'non_retriable' => true],
            'FAILED_AML_SCREENING' => ['category' => 'Blocked / AML', 'message' => 'AML screening failed due to provider communication issue.'],
            'MISSING_TAG_OR_MEMO' => ['category' => 'Blocked / AML', 'message' => 'A tag or memo is required to send funds to a third-party address.'],

            // Cancelled
            '3RD_PARTY_CANCELLED' => ['category' => 'Cancelled', 'message' => 'Canceled by a third-party service (e.g., exchange).'],
            '3RD_PARTY_REJECTED' => ['category' => 'Cancelled', 'message' => 'Rejected by a third party or not approved in time.'],
            'CANCELLED_BY_USER' => ['category' => 'Cancelled', 'message' => 'Canceled via the Fireblocks Console or API.'],
            'CANCELLED_BY_USER_REQUEST' => ['category' => 'Cancelled', 'message' => 'Canceled by a user contacting Fireblocks Support.'],
            'REJECTED_BY_USER' => ['category' => 'Cancelled', 'message' => 'Rejected by one of its signers.'],
            'CANCELLED_EXTERNALLY' => ['category' => 'Cancelled', 'message' => 'Canceled by the third-party service.'],

            // Rejected
            'AUTO_FREEZE' => ['category' => 'Rejected', 'message' => 'Frozen automatically by the Transaction Screening Policy.', 'non_retriable' => true],
            'FROZEN_MANUALLY' => ['category' => 'Rejected', 'message' => 'Frozen manually by a Fireblocks user.', 'non_retriable' => true],
            'REJECTED_AML_SCREENING' => ['category' => 'Rejected', 'message' => 'Rejected as high-risk by the AML service provider.', 'non_retriable' => true],

            // Failed — user input
            'ACTUAL_FEE_TOO_HIGH' => ['category' => 'Failed — User input', 'message' => 'Specified fee was lower than the current gas price.'],
            'ADDRESS_WHITELISTING_SUSPENDED' => ['category' => 'Failed — User input', 'message' => 'Destination address was whitelisted too recently.'],
            'AMOUNT_TOO_SMALL' => ['category' => 'Failed — User input', 'message' => 'Withdrawal request is below the minimum allowed amount.'],
            'AUTHORIZATION_FAILED' => ['category' => 'Failed — User input', 'message' => 'API Co-Signer Callback Handler did not correctly approve or sign.'],
            'AUTHORIZER_NOT_FOUND' => ['category' => 'Failed — User input', 'message' => 'No authorizer found or the only authorizer is the initiator.'],
            'ENV_UNSUPPORTED_ASSET' => ['category' => 'Failed — User input', 'message' => 'Asset is not supported in this workspace (testnet vs mainnet).'],
            'ERROR_UNSUPPORTED_TRANSACTION_TYPE' => ['category' => 'Failed — User input', 'message' => 'Fireblocks does not support this transaction type for the blockchain.'],
            'FAIL_ON_LOW_FEE' => ['category' => 'Failed — User input', 'message' => 'Current blockchain fee is higher than selected.', 'retriable' => true],
            'GAS_LIMIT_TOO_LOW' => ['category' => 'Failed — User input', 'message' => 'Gas limit was set below the minimum.'],
            'GAS_PRICE_TOO_LOW_FOR_RBF' => ['category' => 'Failed — User input', 'message' => 'Gas price too low for Replace-by-Fee (RBF).', 'retriable' => true],
            'INCOMPLETE_USER_SETUP' => ['category' => 'Failed — User input', 'message' => 'Transaction initiator did not complete MPC key setup.'],
            'INSUFFICIENT_FUNDS' => ['category' => 'Failed — User input', 'message' => 'Vault account does not have enough funds.', 'non_retriable' => true],
            'INSUFFICIENT_FUNDS_FOR_FEE' => ['category' => 'Failed — User input', 'message' => 'Vault account does not have enough funds for the requested fee.', 'non_retriable' => true],
            'INTEGRATION_SUSPENDED' => ['category' => 'Failed — User input', 'message' => 'Exchange integration was deactivated by Fireblocks Support.'],
            'INVALID_ADDRESS' => ['category' => 'Failed — User input', 'message' => 'Destination address format is unsupported.'],
            'INVALID_CONTRACT_CALL_DATA' => ['category' => 'Failed — User input', 'message' => 'Transaction data was not encoded properly.'],
            'INVALID_FEE_PARAMS' => ['category' => 'Failed — User input', 'message' => 'Fee parameters are inconsistent or unknown.'],
            'INVALID_NONCE_FOR_RBF' => ['category' => 'Failed — User input', 'message' => 'No matching pending transaction found for RBF.', 'retriable' => true],
            'INVALID_NONCE_TOO_HIGH' => ['category' => 'Failed — User input', 'message' => 'Nonce too high, creating a gap.'],
            'INVALID_NONCE_TOO_LOW' => ['category' => 'Failed — User input', 'message' => 'Illegal nonce (already used by a confirmed transaction).'],
            'INVALID_TAG_OR_MEMO' => ['category' => 'Failed — User input', 'message' => 'Invalid destination tag or memo.'],
            'INVALID_UNMANAGED_WALLET' => ['category' => 'Failed — User input', 'message' => 'Associated whitelisted wallet was removed or nonexistent.'],
            'MAX_FEE_EXCEEDED' => ['category' => 'Failed — User input', 'message' => 'Network gas prices are above the specified maximum fee.'],
            'MISSING_DEPOSIT_CONFIRMATIONS_FOR_WITHDRAWAL' => ['category' => 'Failed — User input', 'message' => 'Deposit has not reached required confirmations for withdrawal.'],
            'NEED_MORE_TO_CREATE_DESTINATION' => ['category' => 'Failed — User input', 'message' => 'Insufficient funds for creating the destination account.'],
            'NO_MORE_PREPROCESSED_INDEXES' => ['category' => 'Failed — User input', 'message' => 'Cold Wallet device has used all available signatures.'],
            'NON_EXISTING_ACCOUNT_NAME' => ['category' => 'Failed — User input', 'message' => 'Destination address cannot be validated.'],
            'RAW_MSG_EMPTY_OR_INVALID' => ['category' => 'Failed — User input', 'message' => 'Raw signing transaction failed due to incorrect raw data.'],
            'RAW_MSG_LEN_INVALID' => ['category' => 'Failed — User input', 'message' => 'Raw signing data or message length parameter incorrect.'],
            'SIGNER_NOT_FOUND' => ['category' => 'Failed — User input', 'message' => 'No signer found or signer missing MPC setup.'],
            'TIMEOUT' => ['category' => 'Failed — User input', 'message' => 'Transaction request timed out or expired.', 'retriable' => true],
            'TOO_MANY_INPUTS' => ['category' => 'Failed — User input', 'message' => 'Maximum number of UTXO inputs exceeded.'],
            'TX_SIZE_EXCEEDED_MAX' => ['category' => 'Failed — User input', 'message' => 'Transaction exceeded the allowed amount (Cardano).'],
            'UNAUTHORISED_DEVICE' => ['category' => 'Failed — User input', 'message' => 'Unauthorized device was used to initiate, approve, or sign.', 'aliases' => ['UNAUTHORISED__DEVICE']],
            'UNAUTHORISED_USER' => ['category' => 'Failed — User input', 'message' => 'Unauthorized user attempted to initiate or approve.', 'aliases' => ['UNAUTHORISED__USER']],
            'UNALLOWED_RAW_PARAM_COMBINATION' => ['category' => 'Failed — User input', 'message' => 'Incorrect raw data parameters.'],
            'UNSUPPORTED_OPERATION' => ['category' => 'Failed — User input', 'message' => 'Operation not supported by Fireblocks or third-party.'],
            'UNSUPPORTED_TRANSACTION_TYPE' => ['category' => 'Failed — User input', 'message' => 'Unsupported transaction type or internal error.'],
            'ZERO_BALANCE_IN_PERMANENT_ADDRESS' => ['category' => 'Failed — User input', 'message' => 'Permanent address does not hold enough Omni.'],

            // Failed — MPC
            'OUT_OF_DATE_SIGNING_KEYS' => ['category' => 'Failed — MPC', 'message' => "Signer's key share must be updated to the MPC-CMP protocol."],

            // Failed — Fireblocks system
            'CONNECTIVITY_ERROR' => ['category' => 'Failed — Fireblocks system', 'message' => 'Internal network error.', 'retriable' => true],
            'ERROR_ASYNC_TX_IN_FLIGHT' => ['category' => 'Failed — Fireblocks system', 'message' => 'Fireblocks internal error during processing.'],
            'INTERNAL_ERROR' => ['category' => 'Failed — Fireblocks system', 'message' => 'Fireblocks internal error during processing.', 'retriable' => true],
            'INVALID_ROUTING_DESTINATION' => ['category' => 'Failed — Fireblocks system', 'message' => 'Could not identify a valid destination (Fireblocks Network).'],
            'LOCKING_NONCE_ACCOUNT_TIMEOUT' => ['category' => 'Failed — Fireblocks system', 'message' => 'Transaction request timed out due to internal error.'],
            'NETWORK_ROUTING_MISMATCH' => ['category' => 'Failed — Fireblocks system', 'message' => 'Valid routing destination could not be identified.'],
            'NONCE_ALLOCATION_FAILED' => ['category' => 'Failed — Fireblocks system', 'message' => 'Transaction could not be created due to internal error.'],
            'RESOURCE_ALREADY_EXISTS' => ['category' => 'Failed — Fireblocks system', 'message' => 'Fireblocks internal error during processing.'],
            'SIGNING_ERROR' => ['category' => 'Failed — Fireblocks system', 'message' => 'Internal error during MPC signing process.', 'retriable' => true],
            'TX_OUTDATED' => ['category' => 'Failed — Fireblocks system', 'message' => 'Nonce was used by another transaction.'],
            'UNKNOWN_ERROR' => ['category' => 'Failed — Fireblocks system', 'message' => 'Unexpected error in the Fireblocks system.', 'retriable' => true],
            'UNSUPPORTED_MEDIA_TYPE' => ['category' => 'Failed — Fireblocks system', 'message' => 'Fireblocks internal error during processing.'],
            'VAULT_WALLET_NOT_READY' => ['category' => 'Failed — Fireblocks system', 'message' => 'Asset wallet was not available for the transaction.'],

            // Failed — Third-party account
            'ADDRESS_NOT_WHITELISTED' => ['category' => 'Failed — Third-party account', 'message' => 'Withdrawal to a non-whitelisted destination address.', 'non_retriable' => true],
            'API_KEY_MISMATCH' => ['category' => 'Failed — Third-party account', 'message' => 'Third-party API connectivity information is no longer accurate.'],
            'ASSET_NOT_ENABLED_ON_DESTINATION' => ['category' => 'Failed — Third-party account', 'message' => 'Asset requires manual enablement at the destination.'],
            'DEST_TYPE_NOT_SUPPORTED' => ['category' => 'Failed — Third-party account', 'message' => 'Transaction to selected destination is not supported.'],
            'EXCEEDED_DECIMAL_PRECISION' => ['category' => 'Failed — Third-party account', 'message' => 'Third-party service does not support the amount of decimal digits.'],
            'EXCHANGE_CONFIGURATION_MISMATCH' => ['category' => 'Failed — Third-party account', 'message' => 'Exchange integration temporarily unavailable.'],
            'EXCHANGE_VERSION_INCOMPATIBLE' => ['category' => 'Failed — Third-party account', 'message' => 'Exchange integration temporarily unavailable.'],
            'INVALID_EXCHANGE_ACCOUNT' => ['category' => 'Failed — Third-party account', 'message' => 'Exchange account is disabled or nonexistent.'],
            'METHOD_NOT_ALLOWED' => ['category' => 'Failed — Third-party account', 'message' => 'Exchange integration temporarily unavailable.'],
            'NON_EXISTENT_AUTO_ACCOUNT' => ['category' => 'Failed — Third-party account', 'message' => 'Third-party account configuration is incorrect.'],
            'ON_PREMISE_CONNECTIVITY_ERROR' => ['category' => 'Failed — Third-party account', 'message' => 'Connectivity error with Fireblocks On-Prem Exchange Service.'],
            'PEER_ACCOUNT_DOES_NOT_EXIST' => ['category' => 'Failed — Third-party account', 'message' => 'Third-party account details are incorrect.'],
            'THIRD_PARTY_MISSING_ACCOUNT' => ['category' => 'Failed — Third-party account', 'message' => 'Incorrect third-party account configuration.'],
            'UNAUTHORISED_IP_WHITELISTING' => ['category' => 'Failed — Third-party account', 'message' => 'Fireblocks IP address is incorrect or missing.', 'aliases' => ['UNAUTHORISED__IP_WHITELISTING']],
            'UNAUTHORISED_MISSING_CREDENTIALS' => ['category' => 'Failed — Third-party account', 'message' => 'Missing credentials from a third-party service.', 'aliases' => ['UNAUTHORISED__MISSING_CREDENTIALS']],
            'UNAUTHORISED_MISSING_PERMISSION' => ['category' => 'Failed — Third-party account', 'message' => 'Missing third-party service API permission.', 'aliases' => ['UNAUTHORISED__MISSING_PERMISSION']],
            'UNAUTHORISED_OTP_FAILED' => ['category' => 'Failed — Third-party account', 'message' => 'Wrong 2FA code entered during third-party configuration.', 'aliases' => ['UNAUTHORISED__OTP_FAILED']],
            'WITHDRAW_LIMIT' => ['category' => 'Failed — Third-party account', 'message' => 'Exceeded the exchange withdrawal limit.'],
            'PROVIDER_INSUFFICIENT_BALANCE' => ['category' => 'Failed — Third-party account', 'message' => 'Provider account does not have sufficient balance.', 'aliases' => ['PROVIDER_INSUFFICIENT_BALANC']],
            'PROVIDER_ADDRESS_NOT_VERIFIED' => ['category' => 'Failed — Third-party account', 'message' => 'Destination address is not verified with the provider.'],
            'PROVIDER_COMPLIANCE_INFO_REQUIRED' => ['category' => 'Failed — Third-party account', 'message' => 'Provider requires additional compliance information.'],
            'PROVIDER_ACCOUNT_ERROR' => ['category' => 'Failed — Third-party account', 'message' => 'An error occurred with the provider account.'],
            'PROVIDER_WITHDRAWAL_LIMIT_EXCEEDED' => ['category' => 'Failed — Third-party account', 'message' => 'Withdrawal exceeds the provider\'s allowed limit.'],

            // Failed — Third-party system
            '3RD_PARTY_FAILED' => ['category' => 'Failed — Third-party system', 'message' => 'Transaction failed with a third-party service.'],
            'API_CALL_LIMIT' => ['category' => 'Failed — Third-party system', 'message' => 'Exceeded the API call limit of a third-party service.'],
            'API_INVALID_SIGNATURE' => ['category' => 'Failed — Third-party system', 'message' => 'Third-party API provided an invalid signature.'],
            'INVALID_FEE' => ['category' => 'Failed — Third-party system', 'message' => 'Insufficient balance for withdrawal and transaction fees.'],
            'INVALID_THIRD_PARTY_RESPONSE' => ['category' => 'Failed — Third-party system', 'message' => 'Unrecognized or invalid response from a third-party service.'],
            'MANUAL_DEPOSIT_ADDRESS_REQUIRED' => ['category' => 'Failed — Third-party system', 'message' => 'Error retrieving deposit address from exchange.'],
            'MISSING_DEPOSIT_ADDRESS' => ['category' => 'Failed — Third-party system', 'message' => 'Unable to retrieve deposit address from exchange.', 'aliases' => ['MISSING_DEPOSIT ADDRESS']],
            'NO_DEPOSIT_ADDRESS' => ['category' => 'Failed — Third-party system', 'message' => 'Deposit address unavailable or not created.'],
            'SUB_ACCOUNTS_NOT_SUPPORTED' => ['category' => 'Failed — Third-party system', 'message' => 'Exchange does not support transactions between main and sub-accounts.'],
            'SPEND_COINBASE_TOO_EARLY' => ['category' => 'Failed — Third-party system', 'message' => 'Assets have not reached the minimum blockchain confirmations.'],
            'THIRD_PARTY_INTERNAL_ERROR' => ['category' => 'Failed — Third-party system', 'message' => 'Internal error response from a third-party service.'],
            'TX_ID_NOT_ACCEPTED_BY_THIRD_PARTY' => ['category' => 'Failed — Third-party system', 'message' => 'Unexpected error resolving destination address through Zodia registry.'],
            'UNSUPPORTED_ASSET' => ['category' => 'Failed — Third-party system', 'message' => 'Asset is not supported by a third-party service.'],
            'PROVIDER_SYSTEM_ERROR' => ['category' => 'Failed — Third-party system', 'message' => 'Provider experienced an internal system error.', 'retriable' => true],
            'PROVIDER_WITHDRAWAL_SUSPENDED' => ['category' => 'Failed — Third-party system', 'message' => 'Withdrawals are currently suspended by the provider.'],
            'PROVIDER_ASSET_NOT_AVAILABLE' => ['category' => 'Failed — Third-party system', 'message' => 'Asset or blockchain not available for withdrawal on the provider.'],
            'PROVIDER_WITHDRAWAL_MAINTENANCE' => ['category' => 'Failed — Third-party system', 'message' => 'Provider withdrawals temporarily unavailable for maintenance.'],
            'PROVIDER_RATE_LIMITED' => ['category' => 'Failed — Third-party system', 'message' => 'Provider rejected the request due to rate limiting.', 'retriable' => true],

            // Failed — Blockchain
            'DOUBLE_SPENDING' => ['category' => 'Failed — Blockchain', 'message' => 'Blockchain determined a transaction with a duplicate hash exists.', 'retriable' => true],
            'DROPPED_BY_BLOCKCHAIN' => ['category' => 'Failed — Blockchain', 'message' => 'Replaced on the blockchain by a higher-fee transaction or dropped from mempool.', 'retriable' => true],
            'INSUFFICIENT_RESERVED_FUNDING' => ['category' => 'Failed — Blockchain', 'message' => 'Minimum amount must remain in the asset wallet.'],
            'INVALID_SIGNATURE' => ['category' => 'Failed — Blockchain', 'message' => 'Blockchain rejected the transaction due to an invalid signature.', 'retriable' => true],
            'PARTIALLY_FAILED' => ['category' => 'Failed — Blockchain', 'message' => 'One or more aggregated transactions have failed.'],
            'POWERUP_SUGGESTION_FAILURE' => ['category' => 'Failed — Blockchain', 'message' => 'Internal error processing transaction fees on the EOS blockchain.'],
            'REACHED_MEMPOOL_LIMIT_FOR_ACCOUNT' => ['category' => 'Failed — Blockchain', 'message' => "Blockchain mempool does not allow more than one active transaction from this account."],
            'REJECTED_BY_BLOCKCHAIN' => ['category' => 'Failed — Blockchain', 'message' => 'Rejected by the blockchain due to low fees, bad inputs, or bad nonce.', 'retriable' => true],
            // Internal — Fireblocks create failed before a custody tx id was assigned
            'REJECTED_BY_API' => ['category' => 'Failed — Third-party system', 'message' => 'Fireblocks rejected the withdrawal request before creating a transaction.', 'retriable' => true],
            'SMART_CONTRACT_EXECUTION_FAILED' => ['category' => 'Failed — Blockchain', 'message' => 'Transaction reverted due to a smart contract error.'],
            'TOO_LONG_MEMPOOL_CHAIN' => ['category' => 'Failed — Blockchain', 'message' => 'Too many unconfirmed transactions pending from this address.', 'retriable' => true],
        ];

        foreach ($entries as $code => $meta) {
            foreach ($meta['aliases'] ?? [] as $alias) {
                $entries[$alias] = array_merge($meta, ['canonical' => $code]);
            }
        }

        return $cache = $entries;
    }

    /** Sub-status codes that should match interchangeably in action-eligibility lists. */
    private const SUBSTATUS_EQUIVALENCE_GROUPS = [
        ['INSUFFICIENT_FUNDS', 'INSUFFICIENT_FUNDS_FOR_FEE'],
    ];

    public static function normalizeSubstatusCode(?string $code): ?string
    {
        if ($code === null || $code === '') {
            return null;
        }

        $upper = strtoupper(trim(str_replace(' ', '_', $code)));
        $catalog = self::substatuses();

        if (isset($catalog[$upper]['canonical'])) {
            return $catalog[$upper]['canonical'];
        }

        return isset($catalog[$upper]) ? $upper : $upper;
    }

    /**
     * Expand a sub-status code to equivalent aliases for eligibility matching.
     *
     * @return array<int, string>
     */
    public static function substatusMatchCodes(?string $code): array
    {
        if ($code === null || $code === '') {
            return [];
        }

        $normalized = self::normalizeSubstatusCode($code) ?? strtoupper(trim(str_replace(' ', '_', $code)));
        $codes = [$normalized];

        foreach (self::SUBSTATUS_EQUIVALENCE_GROUPS as $group) {
            if (in_array($normalized, $group, true)) {
                $codes = array_merge($codes, $group);
            }
        }

        foreach (self::substatuses() as $alias => $meta) {
            if (($meta['canonical'] ?? null) === $normalized) {
                $codes[] = $alias;
            }
        }

        return array_values(array_unique($codes));
    }

    public static function substatusMessage(string $code): string
    {
        $normalized = self::normalizeSubstatusCode($code) ?? $code;
        $catalog = self::substatuses();

        if (isset($catalog[$normalized]['message'])) {
            return $catalog[$normalized]['message'];
        }

        return 'Unknown sub-status: ' . $code;
    }

    public static function substatusCategory(string $code): string
    {
        $normalized = self::normalizeSubstatusCode($code) ?? $code;
        $catalog = self::substatuses();

        return $catalog[$normalized]['category'] ?? 'Other';
    }

    /** @return array<int, string> */
    public static function defaultRetriableSubstatuses(): array
    {
        $codes = [];
        foreach (self::substatuses() as $code => $meta) {
            if (!empty($meta['retriable']) && !isset($meta['canonical'])) {
                $codes[] = $code;
            }
        }

        return $codes;
    }

    /** @return array<int, string> */
    public static function defaultNonRetriableSubstatuses(): array
    {
        $codes = [];
        foreach (self::substatuses() as $code => $meta) {
            if (!empty($meta['non_retriable']) && !isset($meta['canonical'])) {
                $codes[] = $code;
            }
        }

        return $codes;
    }

    /** @return array<string, array<string, string>> */
    public static function substatusMultiselectGroups(): array
    {
        $groups = [];
        foreach (self::substatuses() as $code => $meta) {
            if (isset($meta['canonical'])) {
                continue;
            }
            $category = $meta['category'];
            $label = $code;
            if (!empty($meta['retriable'])) {
                $label .= ' (recommended retriable)';
            } elseif (!empty($meta['non_retriable'])) {
                $label .= ' (recommended non-retriable)';
            }
            $groups[$category][$code] = $label;
        }

        ksort($groups);

        return $groups;
    }

    /** Flat multiselect options keyed by code. */
    public static function substatusMultiselectOptions(): array
    {
        $options = [];
        foreach (self::substatusMultiselectGroups() as $items) {
            foreach ($items as $code => $label) {
                $options[$code] = $label;
            }
        }

        return $options;
    }

    public static function primaryLabel(string $code): string
    {
        $upper = strtoupper(trim($code));

        return self::PRIMARY_LABELS[$upper]
            ?? ucwords(strtolower(str_replace('_', ' ', $upper)));
    }
}
