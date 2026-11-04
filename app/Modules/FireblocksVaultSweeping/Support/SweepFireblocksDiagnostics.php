<?php

namespace App\Modules\FireblocksVaultSweeping\Support;

use Fireblocks\Sdk\Exceptions\FireblocksException;
use Fireblocks\Sdk\Models\TransactionRequest;
use Throwable;

class SweepFireblocksDiagnostics
{
    /**
     * Build a structured log context for Fireblocks API failures during sweep execution.
     *
     * @param  array<string, mixed>  $meta
     * @return array<string, mixed>
     */
    public static function executionFailureContext(Throwable $e, array $meta = []): array
    {
        $context = array_merge($meta, [
            'error' => $e->getMessage(),
            'exception_class' => $e::class,
        ]);

        if ($e instanceof FireblocksException) {
            $context['http_status'] = $e->getCode();
            $context['fireblocks_code'] = $e->getErrorCode();
            $context['fireblocks_response'] = $e->getErrorData();
        }

        $context['diagnosis'] = self::diagnose($e, $meta);

        return $context;
    }

    /**
     * @param  array<string, mixed>  $meta
     * @return array<string, mixed>
     */
    protected static function diagnose(Throwable $e, array $meta): array
    {
        $message = strtolower($e->getMessage());
        $step = (string) ($meta['fireblocks_step'] ?? '');
        $assetId = (string) ($meta['asset_id'] ?? '');

        $diagnosis = [
            'summary' => null,
            'likely_causes' => [],
            'suggested_actions' => [],
        ];

        if (str_contains($message, 'unsupported asset')) {
            $diagnosis['summary'] = 'Fireblocks max_spendable_amount API rejected this asset before a sweep transaction was created.';
            $diagnosis['likely_causes'] = array_values(array_filter([
                $step === 'get_max_spendable_amount'
                    ? 'GET max_spendable_amount is not supported for this asset (common on testnet ERC-20). No POST /v1/transactions was sent — nothing appears in Fireblocks console.'
                    : null,
                $step === 'create_transaction'
                    ? 'Vault-to-vault TRANSFER may not be enabled for this asset in the workspace.'
                    : null,
                $assetId !== ''
                    ? "Asset \"{$assetId}\" may be deposit-only in sandbox or missing transfer/sweep capability."
                    : null,
                'Sweep stops at pre-flight unless available-balance fallback applies (Unsupported Asset + positive available).',
            ]));
            $diagnosis['suggested_actions'] = [
                'Ensure global and rule dry run are OFF if you expect live Fireblocks transactions.',
                'In Fireblocks console, confirm the asset is enabled for vault-to-vault TRANSFER (not just deposits).',
                'Verify the asset ID matches the workspace network (testnet vs production).',
                'Retry after deploy: unsupported max_spendable_amount now falls back to available and submits POST /v1/transactions.',
                'Check portal sweep job status — failed locally without FB tx means failure happened before create transaction.',
            ];
        }

        if (str_contains($message, 'could not find vault wallet for asset')) {
            $diagnosis['summary'] = 'A Fireblocks vault account does not have the required asset wallet enabled.';
            $diagnosis['likely_causes'] = array_values(array_filter([
                'For ERC-20 / Base token sweeps, the source vault needs native gas (e.g. ETH_TEST5, BASECHAIN_ETH_TEST5) — this is separate from the USDC token.',
                'Automatic gas feed from TRON Station only works for TRX/TRC-20 — not for Ethereum/Base native gas.',
                'Destination omnibus vault may be unset — configure Omnibus Vault ID in module settings if sweeps fail at fee estimate.',
                preg_match('/on accounts:\s*\[(\d+)\]/', $e->getMessage(), $m)
                    ? "Vault {$m[1]} is missing the asset wallet mentioned in the error."
                    : null,
            ]));
            $diagnosis['suggested_actions'] = [
                'In Fireblocks console, enable the native gas asset on the source client deposit vault.',
                'Set Omnibus Vault ID in Settings → Fireblocks Vault Sweeping if not already configured.',
                'For USDC_ETH_TEST5 sweeps, ensure source vault holds ETH_TEST5 for transaction fees.',
                'TRON Station vault is only for TRX gas — do not rely on it for Ethereum/Base tokens.',
            ];
        }

        if ($diagnosis['summary'] === null) {
            $diagnosis['summary'] = 'Fireblocks API call failed during sweep execution.';
            if ($step !== '') {
                $diagnosis['likely_causes'][] = "Failed at step: {$step}.";
            }
        }

        return $diagnosis;
    }

    public static function fireblocksResponseMessage(Throwable $e): ?string
    {
        if (!$e instanceof FireblocksException) {
            return null;
        }

        $data = $e->getErrorData();
        if (!is_array($data)) {
            return null;
        }

        $message = trim((string) ($data['message'] ?? ''));

        return $message !== '' ? $message : null;
    }

    /**
     * Human-readable log line including Fireblocks API message when present.
     */
    public static function formatExceptionMessage(Throwable $e, ?string $prefix = null): string
    {
        $parts = array_filter([
            $prefix !== null && $prefix !== '' ? rtrim($prefix, '.') : null,
            trim($e->getMessage()),
        ]);

        $fbMessage = self::fireblocksResponseMessage($e);
        if ($fbMessage !== null && !str_contains(strtolower($parts[1] ?? ''), strtolower($fbMessage))) {
            $parts[] = 'Fireblocks: ' . $fbMessage;
        }

        if ($e instanceof FireblocksException && $e->getErrorCode() !== null && $e->getErrorCode() !== '') {
            $parts[] = 'FB code ' . $e->getErrorCode();
        }

        return implode(' — ', $parts);
    }

    /**
     * @return array{message: string, failure_reason: string, why: string, diagnosis: array<string, mixed>}
     */
    public static function insufficientNativeGasDiagnostics(
        string $sourceVaultId,
        string $gasAssetId,
        string $sourceAssetId,
        string $availableGas,
        string $requiredGas,
        bool $gasWalletFound,
        ?string $feeEstimateSource = null
    ): array {
        $shortfall = SweepMath::comp($availableGas, $requiredGas, 18) >= 0
            ? '0'
            : SweepMath::sub($requiredGas, $availableGas, 18);

        if (!$gasWalletFound) {
            $why = sprintf(
                '%s is not enabled on source vault %s in Fireblocks.',
                $gasAssetId,
                $sourceVaultId
            );
            $failureReason = 'native_gas_wallet_missing';
        } elseif (SweepMath::comp($availableGas, '0', 18) <= 0) {
            $why = sprintf(
                '%s wallet exists on vault %s but available balance is 0 (need at least %s for fees).',
                $gasAssetId,
                $sourceVaultId,
                $requiredGas
            );
            $failureReason = 'zero_native_gas_balance';
        } else {
            $why = sprintf(
                'Vault %s has %s %s available but ~%s required (shortfall %s).',
                $sourceVaultId,
                $availableGas,
                $gasAssetId,
                $requiredGas,
                $shortfall
            );
            $failureReason = 'insufficient_native_gas';
        }

        $message = sprintf(
            'Sweep blocked before submit: %s Token sweep asset %s requires native gas %s on the source vault. %s Automatic gas feed only supports TRON (TRX).',
            $why,
            $sourceAssetId,
            $gasAssetId,
            $feeEstimateSource !== null ? "Fee estimate source: {$feeEstimateSource}." : ''
        );

        return [
            'message' => preg_replace('/\s+/', ' ', trim($message)),
            'failure_reason' => $failureReason,
            'why' => $why,
            'diagnosis' => [
                'summary' => $why,
                'likely_causes' => [
                    'ERC-20 / EVM token sweeps pay network fees from a separate native asset (not from the USDC balance).',
                    'TRON Station cannot fund ETH_TEST5 or BASECHAIN_ETH — only TRX.',
                ],
                'suggested_actions' => [
                    "In Fireblocks, deposit {$gasAssetId} to source vault {$sourceVaultId} (at least {$requiredGas}).",
                    'Re-run the sweep after the native gas balance is visible in Fireblocks.',
                ],
            ],
        ];
    }

    public static function maxSpendableEndpoint(string $vaultId, string $assetId): string
    {
        return "/v1/vault/accounts/{$vaultId}/{$assetId}/max_spendable_amount";
    }

    /**
     * Build the exact JSON body Fireblocks SDK sends for create / estimate_fee.
     *
     * @return array<string, mixed>
     */
    public static function buildTransactionPayload(TransactionRequest $request, bool $useGasless = false): array
    {
        $payload = [
            'assetId' => $request->assetId,
            'source' => [
                'type' => $request->sourceType,
                'id' => $request->sourceId,
            ],
            'destination' => [
                'type' => $request->destinationType,
            ],
            'amount' => SweepMath::sanitizeTransactionAmount((string) ($request->amount ?? '0')),
        ];

        if ($request->sourceWalletId !== null) {
            $payload['source']['walletId'] = $request->sourceWalletId;
        }

        if ($request->destinationType === 'ONE_TIME_ADDRESS') {
            $payload['destination']['oneTimeAddress'] = [
                'address' => $request->destinationAddress,
            ];
            if ($request->destinationTag !== null) {
                $payload['destination']['oneTimeAddress']['tag'] = $request->destinationTag;
            }
        } else {
            $payload['destination']['id'] = $request->destinationId;
            if ($request->destinationWalletId !== null) {
                $payload['destination']['walletId'] = $request->destinationWalletId;
            }
        }

        if ($request->feeLevel !== null) {
            $payload['feeLevel'] = $request->feeLevel;
        }
        if ($request->note !== null) {
            $payload['note'] = $request->note;
        }
        if ($request->externalTxId !== null) {
            $payload['externalTxId'] = $request->externalTxId;
        }
        if ($request->customerRefId !== null) {
            $payload['customerRefId'] = $request->customerRefId;
        }
        if ($request->treatAsGrossAmount !== null) {
            $payload['treatAsGrossAmount'] = $request->treatAsGrossAmount;
        }
        if ($request->failOnLowFee !== null) {
            $payload['failOnLowFee'] = $request->failOnLowFee;
        }
        if ($request->forceSweep !== null) {
            $payload['forceSweep'] = $request->forceSweep;
        }
        if ($request->replaceTxByHash !== null) {
            $payload['replaceTxByHash'] = $request->replaceTxByHash;
        }
        if ($request->extraParameters !== null) {
            $payload['extraParameters'] = $request->extraParameters;
        }
        if ($useGasless) {
            $payload['useGasless'] = true;
        }

        return $payload;
    }

    /**
     * @return array{endpoint: string, method: string, payload: array<string, mixed>|null, payload_json: string|null, use_gasless?: bool}
     */
    public static function apiRequestContext(string $method, string $endpoint, ?TransactionRequest $request = null, bool $useGasless = false): array
    {
        $payload = $request !== null ? self::buildTransactionPayload($request, $useGasless) : null;

        $context = [
            'endpoint' => $endpoint,
            'method' => strtoupper($method),
            'payload' => $payload,
            'payload_json' => $payload !== null
                ? json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)
                : null,
        ];

        if ($useGasless) {
            $context['use_gasless'] = true;
            $context['gas_funding_mode'] = 'gas_relay';
        }

        return $context;
    }

    public static function formatApiRequestMessage(string $method, string $endpoint, ?TransactionRequest $request = null, bool $useGasless = false): string
    {
        $context = self::apiRequestContext($method, $endpoint, $request, $useGasless);

        $message = sprintf('Fireblocks %s %s', $context['method'], $context['endpoint']);

        if ($context['payload_json'] !== null) {
            $message .= ' — ' . $context['payload_json'];
        }

        return $message;
    }

    public static function isUnsupportedAssetError(Throwable $e): bool
    {
        return str_contains(strtolower($e->getMessage()), 'unsupported asset');
    }
}
