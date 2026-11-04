<?php

namespace App\Modules\FireblocksVaultSweeping\Support;

use App\Models\FireblocksTransaction;
use App\Modules\FireblocksVaultSweeping\Models\FireblocksSweepJob;
use App\Modules\FireblocksVaultSweeping\Services\TronGasFundingService;

class SweepLocalTransactionRecorder
{
    /**
     * Persist a vault-to-vault sweep leg in fireblocks_transactions for monitoring/live feed.
     *
     * @param  object  $transaction  Fireblocks SDK transaction response
     * @param  array<string, mixed>  $responseMeta
     */
    public static function recordVaultTransfer(
        object $transaction,
        string $transferAssetId,
        string $sourceVaultId,
        string $destinationVaultId,
        string $amount,
        FireblocksSweepJob $job,
        string $externalTxId,
        string $note,
        array $responseMeta = []
    ): FireblocksTransaction {
        $isGasFeed = ($responseMeta['leg'] ?? null) === 'gas_feed'
            || TronGasFundingService::isGasFeedTransaction([
                'id' => $transaction->id ?? null,
                'externalTxId' => $externalTxId,
                'note' => $note,
            ]);

        return FireblocksTransaction::create([
            'fireblocks_tx_id' => $transaction->id ?? null,
            'asset_id' => $transferAssetId,
            'source_vault_account_id' => $sourceVaultId,
            'destination_address' => self::resolveDestinationAddress($transaction, $destinationVaultId),
            'amount' => $amount,
            'status' => $transaction->status ?? 'SUBMITTED',
            'sub_status' => $transaction->subStatus ?? null,
            'internal_status' => 'Pending',
            'monitoring_state' => 'normal',
            'direction' => 'outgoing',
            'operation' => 'TRANSFER',
            'is_sweep' => true,
            'sweep_job_id' => $job->id,
            'root_fireblocks_tx_id' => $transaction->id ?? null,
            'tx_hash' => $transaction->txHash ?? null,
            'fee' => $transaction->fee ?? null,
            'note' => $isGasFeed ? FireblocksTransaction::CUSTODY_ONLY_SWEEP_GAS_FEED_NOTE : $note,
            'external_tx_id' => $externalTxId,
            'response_data' => array_merge([
                'source' => $transaction->source ?? null,
                'destination' => $transaction->destination ?? null,
            ], $responseMeta),
        ]);
    }

    public static function resolveDestinationAddress(object $transaction, string $destinationVaultId): string
    {
        $destination = $transaction->destination ?? null;

        $candidates = [
            $transaction->destinationAddress ?? null,
            is_object($destination) ? ($destination->id ?? null) : null,
            is_array($destination) ? ($destination['id'] ?? null) : null,
            $destinationVaultId !== '' ? $destinationVaultId : null,
        ];

        foreach ($candidates as $candidate) {
            $value = trim((string) $candidate);
            if ($value !== '') {
                return $value;
            }
        }

        return 'vault:unknown';
    }
}
