<?php

namespace App\Modules\FireblocksTransactionsMonitoring\Support;

use App\Models\AccountTransaction;
use App\Models\FireblocksTransaction;
use App\Models\TRRequest;

/**
 * Finds a Fireblocks transaction (and link metadata) from ledger / TR rows.
 */
final class LedgerFireblocksTransactionResolver
{
    /**
     * @return array{
     *     should_show: bool,
     *     fireblocks_tx_id: ?string,
     *     linked_reference: ?string,
     *     fireblocks_record: ?FireblocksTransaction
     * }|null null when this flow is not Fireblocks-related at all
     */
    public function resolve(?AccountTransaction $accountTransaction, ?TRRequest $trRequest = null): ?array
    {
        if (!$accountTransaction && !$trRequest) {
            return null;
        }

        if (!$trRequest && $accountTransaction?->tr_request_id) {
            $trRequest = TRRequest::query()->find($accountTransaction->tr_request_id);
        }

        $referenceIds = $this->collectReferenceIds($accountTransaction, $trRequest);
        $fireblocksFlow = $this->isFireblocksFlow($accountTransaction, $trRequest, $referenceIds);

        if (!$fireblocksFlow) {
            return null;
        }

        foreach ($referenceIds as $referenceId) {
            $record = FireblocksTransaction::query()
                ->where('fireblocks_tx_id', $referenceId)
                ->first();
            if ($record) {
                return [
                    'should_show' => true,
                    'fireblocks_tx_id' => $record->fireblocks_tx_id,
                    'linked_reference' => $referenceId,
                    'fireblocks_record' => $record,
                ];
            }
        }

        if ($trRequest) {
            $trId = (string) (int) $trRequest->id;
            $byExternal = FireblocksTransaction::query()
                ->where(function ($q) use ($trRequest, $trId) {
                    $q->where('external_tx_id', $trId)
                        ->orWhere('external_tx_id', (string) $trRequest->id);
                })
                ->orderByDesc('id')
                ->first();

            if ($byExternal) {
                return [
                    'should_show' => true,
                    'fireblocks_tx_id' => $byExternal->fireblocks_tx_id,
                    'linked_reference' => $byExternal->fireblocks_tx_id,
                    'fireblocks_record' => $byExternal,
                ];
            }
        }

        $linkedReference = $referenceIds[0] ?? null;

        return [
            'should_show' => true,
            'fireblocks_tx_id' => null,
            'linked_reference' => $linkedReference,
            'fireblocks_record' => null,
        ];
    }

    /**
     * @return list<string>
     */
    protected function collectReferenceIds(?AccountTransaction $accountTransaction, ?TRRequest $trRequest): array
    {
        $ids = [];

        foreach ([
            $accountTransaction?->crypto_transaction_id,
            $trRequest?->crypto_transaction_id,
        ] as $candidate) {
            $candidate = trim((string) $candidate);
            if ($candidate !== '') {
                $ids[] = $candidate;
            }
        }

        if ($trRequest) {
            $siblingIds = AccountTransaction::query()
                ->where('tr_request_id', $trRequest->id)
                ->whereNotNull('crypto_transaction_id')
                ->where('crypto_transaction_id', '!=', '')
                ->orderByDesc('id')
                ->pluck('crypto_transaction_id');

            foreach ($siblingIds as $siblingId) {
                $siblingId = trim((string) $siblingId);
                if ($siblingId !== '') {
                    $ids[] = $siblingId;
                }
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * @param  list<string>  $referenceIds
     */
    protected function isFireblocksFlow(
        ?AccountTransaction $accountTransaction,
        ?TRRequest $trRequest,
        array $referenceIds
    ): bool {
        foreach ([
            $accountTransaction?->crypto_system,
            $trRequest?->crypto_system,
        ] as $system) {
            if ($this->isNonFireblocksVenue($system)) {
                return false;
            }
            if ($this->isFireblocksSystem($system)) {
                return true;
            }
        }

        foreach ($referenceIds as $referenceId) {
            if ($this->looksLikeFireblocksTxId($referenceId)) {
                return true;
            }
        }

        if ($trRequest && FireblocksTransaction::query()
            ->where(function ($q) use ($trRequest) {
                $trId = (string) (int) $trRequest->id;
                $q->where('external_tx_id', $trId)
                    ->orWhere('external_tx_id', (string) $trRequest->id);
            })
            ->exists()) {
            return true;
        }

        return false;
    }

    protected function isFireblocksSystem(mixed $system): bool
    {
        return strcasecmp(trim((string) $system), 'fireblocks') === 0;
    }

    protected function isNonFireblocksVenue(mixed $system): bool
    {
        return in_array(strtolower(trim((string) $system)), ['aquanow', 'b2c2', 'bitpace'], true);
    }

    protected function looksLikeFireblocksTxId(string $id): bool
    {
        return (bool) preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $id
        );
    }
}
