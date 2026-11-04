<?php

namespace App\Modules\FireblocksVaultSweeping\Support;

use Fireblocks\Sdk\Facades\Fireblocks;
use Fireblocks\Sdk\Models\Transaction;
use Fireblocks\Sdk\Models\TransactionRequest;

/**
 * Fireblocks transaction API helpers for vault sweeping.
 *
 * The vendor PHP SDK omits {@see useGasless}; this wrapper sends the full payload
 * (including gas relay) for estimate_fee and create transaction calls.
 */
class SweepFireblocksTransactions
{
    public function estimateFee(TransactionRequest $request, bool $useGasless = false): array
    {
        return Fireblocks::post(
            '/v1/transactions/estimate_fee',
            SweepFireblocksDiagnostics::buildTransactionPayload($request, $useGasless)
        );
    }

    public function create(TransactionRequest $request, bool $useGasless = false): Transaction
    {
        $response = Fireblocks::post(
            '/v1/transactions',
            SweepFireblocksDiagnostics::buildTransactionPayload($request, $useGasless)
        );

        return new Transaction($response);
    }
}
