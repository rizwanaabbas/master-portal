<?php

namespace App\Modules\Fireblocks\Services;

use App\Modules\Fireblocks\Helpers\FireblocksStatusHelper;
use App\Modules\Fireblocks\Models\Asset;
use App\Modules\Fireblocks\Models\CryptoBeneficiary;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class BeneficiaryGridService
{
    public function baseQuery(): Builder
    {
        return CryptoBeneficiary::query()
            ->select('crypto_beneficiaries.*')
            ->leftJoin('accounts', 'accounts.id', '=', 'crypto_beneficiaries.account_id')
            ->leftJoin('currencies', 'currencies.id', '=', 'crypto_beneficiaries.currency_id')
            ->leftJoin('networks', 'networks.id', '=', 'crypto_beneficiaries.network_id')
            ->leftJoin('crypto_address_compliance', 'crypto_address_compliance.crypto_beneficiary_id', '=', 'crypto_beneficiaries.id')
            ->with(['account:id,title,account_number', 'currency:id,short,name', 'network:id,code,name', 'compliance']);
    }

    public function applyEnabledCurrencyScope(Builder $query): void
    {
        $query->where(function (Builder $q) {
            $q->whereNull('crypto_beneficiaries.currency_id')
                ->orWhere('currencies.is_active', true);
        });
    }

    public function applyFilters(Builder $query, Request $request): void
    {
        if ($currencyId = (int) $request->input('currency_id')) {
            $query->where('crypto_beneficiaries.currency_id', $currencyId);
        }

        if ($assetId = (int) $request->input('asset_id')) {
            $asset = Asset::query()->find($assetId);
            if ($asset) {
                $query->where(function (Builder $q) use ($asset) {
                    $q->where('crypto_beneficiaries.fireblocks_asset_id', $asset->asset_id);
                    if ($asset->network_id) {
                        $q->orWhere('crypto_beneficiaries.network_id', $asset->network_id);
                    }
                });
            }
        }

        if ($fbStatus = trim((string) $request->input('fb_asset_status'))) {
            $query->where('crypto_beneficiaries.fireblocks_asset_status', $fbStatus);
        }

        $internalStatus = trim((string) $request->input('internal_status'));
        if ($internalStatus !== '') {
            $this->applyInternalStatusFilter($query, $internalStatus);
        }

        if ($risk = trim((string) $request->input('compliance_risk'))) {
            $query->where('crypto_address_compliance.risk', $risk);
        }

        if ($type = trim((string) $request->input('beneficiary_type'))) {
            $query->where('crypto_beneficiaries.beneficiary_type', $type);
        }
    }

    public function applySearch(Builder $query, ?string $search): void
    {
        if ($search === null || $search === '') {
            return;
        }

        $like = '%' . $search . '%';
        $query->where(function (Builder $q) use ($like) {
            $q->where('crypto_beneficiaries.fireblocks_wallet_id', 'like', $like)
                ->orWhere('crypto_beneficiaries.label', 'like', $like)
                ->orWhere('crypto_beneficiaries.wallet_address', 'like', $like)
                ->orWhere('crypto_beneficiaries.beneficiary_first_name', 'like', $like)
                ->orWhere('crypto_beneficiaries.beneficiary_last_name', 'like', $like)
                ->orWhere('crypto_beneficiaries.beneficiary_legal_name', 'like', $like)
                ->orWhere('crypto_beneficiaries.fireblocks_asset_status', 'like', $like)
                ->orWhere('accounts.title', 'like', $like)
                ->orWhere('accounts.account_number', 'like', $like)
                ->orWhere('currencies.short', 'like', $like)
                ->orWhere('networks.code', 'like', $like)
                ->orWhere('networks.name', 'like', $like)
                ->orWhere('crypto_address_compliance.status', 'like', $like);
        });
    }

    protected function applyInternalStatusFilter(Builder $query, string $internalStatus): void
    {
        $table = 'crypto_beneficiaries';

        if ($internalStatus === 'Approved') {
            $query->where(function (Builder $q) use ($table) {
                $q->whereIn($table . '.fireblocks_asset_status', FireblocksStatusHelper::BENEFICIARY_APPROVED_STATUSES)
                    ->orWhere(function (Builder $inner) use ($table) {
                        $inner->where(function (Builder $empty) use ($table) {
                            $empty->whereNull($table . '.fireblocks_asset_status')
                                ->orWhere($table . '.fireblocks_asset_status', '');
                        })->where($table . '.is_active', true);
                    });
            });

            return;
        }

        if ($internalStatus === 'Pending') {
            $query->where(function (Builder $q) use ($table) {
                $q->whereIn($table . '.fireblocks_asset_status', FireblocksStatusHelper::BENEFICIARY_PENDING_STATUSES)
                    ->orWhere(function (Builder $inner) use ($table) {
                        $inner->where(function (Builder $empty) use ($table) {
                            $empty->whereNull($table . '.fireblocks_asset_status')
                                ->orWhere($table . '.fireblocks_asset_status', '');
                        })->where($table . '.is_active', false);
                    });
            });

            return;
        }

        if ($internalStatus === 'Rejected') {
            $query->whereIn($table . '.fireblocks_asset_status', FireblocksStatusHelper::BENEFICIARY_REJECTED_STATUSES);

            return;
        }

        if ($internalStatus === 'Other') {
            $query->where(function (Builder $q) use ($table) {
                $q->whereNotNull($table . '.fireblocks_asset_status')
                    ->where($table . '.fireblocks_asset_status', '!=', '')
                    ->whereNotIn($table . '.fireblocks_asset_status', array_merge(
                        FireblocksStatusHelper::BENEFICIARY_APPROVED_STATUSES,
                        FireblocksStatusHelper::BENEFICIARY_PENDING_STATUSES,
                        FireblocksStatusHelper::BENEFICIARY_REJECTED_STATUSES
                    ));
            });
        }
    }
}
