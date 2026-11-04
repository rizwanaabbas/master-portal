<?php

namespace App\Modules\Fireblocks\Services;

use App\Modules\Fireblocks\Models\Asset;
use App\Modules\Fireblocks\Models\FireblocksWithdrawalPoolBalance;
use App\Modules\FireblocksVaultSweeping\Services\SweepBalanceService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class WithdrawalPoolBalanceGridService
{
    public function __construct(protected SweepBalanceService $vaultNames)
    {
    }

    public function baseQuery(): Builder
    {
        return FireblocksWithdrawalPoolBalance::query()
            ->with(['portalAsset.network', 'catalogAsset.network']);
    }

    public function applyFilters(Builder $query, Request $request): void
    {
        if ($request->filled('balance_source')) {
            $source = (string) $request->input('balance_source');
            if ($source === 'omnibus') {
                $query->whereIn('balance_source', ['source_vault', 'utxo_vault']);
            } elseif ($source === 'wallet_pool') {
                $query->where('balance_source', 'wallet_pool_member');
            }
        }

        if ($request->filled('vault_account_id')) {
            $query->where('vault_account_id', $request->input('vault_account_id'));
        }

        if ($request->filled('asset_id')) {
            $query->where('asset_id', 'like', '%' . $request->input('asset_id') . '%');
        }

        if ($request->boolean('hide_zero')) {
            $query->where(function (Builder $q) {
                $q->where('total', '>', 0)
                    ->orWhere('available', '>', 0);
            });
        }
    }

    public function applySearch(Builder $query, ?string $search): void
    {
        $search = trim((string) $search);
        if ($search === '') {
            return;
        }

        $query->where(function (Builder $q) use ($search) {
            $q->where('asset_id', 'like', "%{$search}%")
                ->orWhere('vault_account_id', 'like', "%{$search}%")
                ->orWhere(fn (Builder $catalogQ) => $this->applyCatalogAssetSearch($catalogQ, $search));
        });
    }

    public function networkAssetLabel($row): string
    {
        $asset = self::resolveCatalogAsset($row);
        if ($asset === null) {
            return '—';
        }

        $network = $asset->network;
        $networkLabel = trim((string) ($network->name ?? ''));
        if ($networkLabel === '') {
            $networkLabel = trim((string) ($network->code ?? ''));
        }

        $assetLabel = trim((string) ($asset->name ?? ''));
        if ($assetLabel === '') {
            $assetLabel = trim((string) ($asset->asset_id ?? ''));
        }

        if ($networkLabel === '' && $assetLabel === '') {
            return '—';
        }
        if ($networkLabel === '') {
            return $assetLabel;
        }
        if ($assetLabel === '') {
            return $networkLabel;
        }

        return $networkLabel . ' ' . $assetLabel;
    }

    public static function resolveCatalogAsset(FireblocksWithdrawalPoolBalance $row): ?Asset
    {
        if ($row->relationLoaded('portalAsset') && $row->portalAsset) {
            if (!$row->portalAsset->relationLoaded('network')) {
                $row->portalAsset->load('network');
            }

            return $row->portalAsset;
        }

        if ($row->portal_asset_id) {
            return Asset::query()->with('network')->find($row->portal_asset_id);
        }

        if ($row->relationLoaded('catalogAsset') && $row->catalogAsset) {
            if (!$row->catalogAsset->relationLoaded('network')) {
                $row->catalogAsset->load('network');
            }

            return $row->catalogAsset;
        }

        $fireblocksAssetId = trim((string) ($row->asset_id ?? ''));
        if ($fireblocksAssetId === '') {
            return null;
        }

        return Asset::query()->with('network')->where('asset_id', $fireblocksAssetId)->first();
    }

    protected function applyCatalogAssetSearch(Builder $query, string $search): void
    {
        $query->whereHas('catalogAsset', function (Builder $assetQ) use ($search) {
            $assetQ->where('assets.name', 'like', "%{$search}%")
                ->orWhere('assets.asset_id', 'like', "%{$search}%")
                ->orWhereHas('network', function (Builder $networkQ) use ($search) {
                    $networkQ->where('networks.name', 'like', "%{$search}%")
                        ->orWhere('networks.code', 'like', "%{$search}%");
                });
        })->orWhereHas('portalAsset', function (Builder $assetQ) use ($search) {
            $assetQ->where('assets.name', 'like', "%{$search}%")
                ->orWhere('assets.asset_id', 'like', "%{$search}%")
                ->orWhereHas('network', function (Builder $networkQ) use ($search) {
                    $networkQ->where('networks.name', 'like', "%{$search}%")
                        ->orWhere('networks.code', 'like', "%{$search}%");
                });
        });
    }

    public static function formatDecimal($value): string
    {
        $formatted = rtrim(rtrim(number_format((float) $value, 8, '.', ''), '0'), '.');

        return $formatted === '' ? '0' : $formatted;
    }

    public function vaultLabel(string $vaultId): string
    {
        return $this->vaultNames->vaultDisplayLabel($vaultId);
    }
}
