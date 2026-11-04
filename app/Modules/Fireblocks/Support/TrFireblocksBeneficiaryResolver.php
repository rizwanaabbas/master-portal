<?php

namespace App\Modules\Fireblocks\Support;

use App\Models\FireblocksTransaction;
use App\Models\TRRequest;
use App\Modules\Fireblocks\Models\CryptoBeneficiary;

class TrFireblocksBeneficiaryResolver
{
    public static function resolveForFireblocksTransaction(FireblocksTransaction $record): ?CryptoBeneficiary
    {
        if (!self::isOutgoingFireblocks($record)) {
            return null;
        }

        $tr = self::findLinkedTransactionRequest($record);

        return $tr ? self::resolve($tr) : self::resolveByDestinationAddress($record);
    }

    public static function resolve(TRRequest $tr): ?CryptoBeneficiary
    {
        if (strcasecmp((string) $tr->transaction_nature, 'Withdraw') !== 0) {
            return null;
        }

        $detailsId = trim((string) ($tr->crypto_details_id ?? ''));
        if ($detailsId !== '' && ctype_digit($detailsId)) {
            $byId = CryptoBeneficiary::query()->find((int) $detailsId);
            if ($byId && (int) $byId->account_id === (int) $tr->account_id) {
                return $byId;
            }
        }

        return self::findByAccountAndWallet(
            (int) $tr->account_id,
            (string) ($tr->to_wallet_address ?? ''),
            (string) ($tr->destination_tag ?? '')
        );
    }

    protected static function findLinkedTransactionRequest(FireblocksTransaction $record): ?TRRequest
    {
        if (self::isNumericId($record->external_tx_id)) {
            $tr = TRRequest::query()->find((int) $record->external_tx_id);
            if ($tr) {
                return $tr;
            }
        }

        $trId = TRRequest::query()
            ->where('crypto_transaction_id', $record->fireblocks_tx_id)
            ->orderByDesc('id')
            ->value('id');

        if ($trId) {
            return TRRequest::query()->find($trId);
        }

        return null;
    }

    protected static function resolveByDestinationAddress(FireblocksTransaction $record): ?CryptoBeneficiary
    {
        $address = trim((string) ($record->destination_address ?? ''));
        if ($address === '') {
            return null;
        }

        $candidates = CryptoBeneficiary::query()
            ->whereRaw('LOWER(wallet_address) = ?', [strtolower($address)])
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        if ($candidates->isEmpty()) {
            return null;
        }

        $tag = trim((string) ($record->destination_tag ?? ''));
        if ($tag !== '') {
            $tagged = $candidates->first(fn (CryptoBeneficiary $b) => (string) ($b->destination_tag ?? '') === $tag);
            if ($tagged) {
                return $tagged;
            }
        }

        return $candidates->count() === 1 ? $candidates->first() : null;
    }

    protected static function findByAccountAndWallet(int $accountId, string $address, string $tag): ?CryptoBeneficiary
    {
        $address = trim($address);
        if ($address === '' || $accountId <= 0) {
            return null;
        }

        $normalized = strtolower($address);

        $query = CryptoBeneficiary::query()
            ->where('account_id', $accountId)
            ->whereRaw('LOWER(wallet_address) = ?', [$normalized]);

        $tag = trim($tag);
        if ($tag !== '') {
            $match = (clone $query)->where('destination_tag', $tag)->orderByDesc('id')->first();
            if ($match) {
                return $match;
            }
        }

        $matches = $query->orderByDesc('id')->limit(2)->get();
        if ($matches->count() === 1) {
            return $matches->first();
        }

        if ($matches->count() > 1 && $tag === '') {
            return $matches->first();
        }

        return null;
    }

    protected static function isOutgoingFireblocks(FireblocksTransaction $record): bool
    {
        return strtolower((string) $record->direction) === 'outgoing';
    }

    protected static function isNumericId(mixed $value): bool
    {
        $value = trim((string) $value);

        return $value !== '' && ctype_digit($value);
    }

    public static function displayName(CryptoBeneficiary $beneficiary): string
    {
        $label = trim((string) ($beneficiary->label ?? ''));
        if ($label !== '') {
            return $label;
        }

        if (strcasecmp((string) $beneficiary->beneficiary_type, 'BUSINESS') === 0) {
            return trim((string) ($beneficiary->beneficiary_legal_name ?? '')) ?: 'Business beneficiary';
        }

        $name = trim(trim((string) ($beneficiary->beneficiary_first_name ?? '')) . ' '
            . trim((string) ($beneficiary->beneficiary_last_name ?? '')));

        return $name !== '' ? $name : 'Individual beneficiary';
    }
}
