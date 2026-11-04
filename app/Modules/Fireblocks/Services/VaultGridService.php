<?php

namespace App\Modules\Fireblocks\Services;

use App\Modules\Fireblocks\Models\FireblocksVaultAccount;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class VaultGridService
{
    public function baseQuery(): Builder
    {
        return FireblocksVaultAccount::query()
            ->leftJoin('clients', 'clients.id', '=', 'fireblocks_vault_accounts.client_id')
            ->select('fireblocks_vault_accounts.*', 'clients.name as client_name')
            ->selectRaw(
                '(SELECT COUNT(*) FROM crypto_addresses WHERE crypto_addresses.vault_account_id = fireblocks_vault_accounts.vault_id) as address_count'
            );
    }

    public function applyFilters(Builder $query, Request $request): void
    {
        if ($clientId = (int) $request->input('client_id')) {
            $query->where('fireblocks_vault_accounts.client_id', $clientId);
        }

        if ($request->boolean('has_addresses')) {
            $query->whereRaw(
                '(SELECT COUNT(*) FROM crypto_addresses WHERE crypto_addresses.vault_account_id = fireblocks_vault_accounts.vault_id) > 0'
            );
        }
    }

    public function applySearch(Builder $query, ?string $search): void
    {
        if ($search === null || $search === '') {
            return;
        }

        $like = '%' . $search . '%';
        $query->where(function (Builder $q) use ($like) {
            $q->where('fireblocks_vault_accounts.vault_id', 'like', $like)
                ->orWhere('fireblocks_vault_accounts.vault_title', 'like', $like)
                ->orWhere('clients.name', 'like', $like);
        });
    }
}
