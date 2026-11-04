<?php

namespace App\Modules\Liquidity\Services;

use App\Modules\Liquidity\Models\Balance;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class BalanceGridService
{
    public function baseQuery(string $provider, bool $showAllAvailable): Builder
    {
        $query = Balance::query()->provider($provider);

        if ($showAllAvailable) {
            return $query
                ->leftJoin('currencies', 'currencies.short', '=', 'liquidity_balances.currency')
                ->select('liquidity_balances.*');
        }

        return $query->joinActiveCurrency();
    }

    public function applyFilters(Builder $query, Request $request): void
    {
        if ($code = trim((string) $request->input('currency_code'))) {
            $query->where('liquidity_balances.currency', $code);
        }

        $assetType = $request->input('asset_type');
        if ($assetType === 'crypto') {
            $query->where(function (Builder $q) {
                $q->where('currencies.is_crypto', true)
                    ->orWhereNull('currencies.id');
            });
        } elseif ($assetType === 'fiat') {
            $query->where('currencies.is_crypto', false);
        }

        if ($request->boolean('hide_zero')) {
            $query->where(function (Builder $q) {
                $q->where('liquidity_balances.amount', '>', 0)
                    ->orWhere('liquidity_balances.available', '>', 0)
                    ->orWhere('liquidity_balances.hold', '>', 0);
            });
        }
    }

    public function applySearch(Builder $query, ?string $search): void
    {
        if ($search === null || $search === '') {
            return;
        }

        $like = '%' . $search . '%';
        $query->where(function (Builder $q) use ($like) {
            $q->where('liquidity_balances.currency', 'like', $like)
                ->orWhere('currencies.name', 'like', $like)
                ->orWhere('currencies.short', 'like', $like);
        });
    }

    public static function formatDecimal($value): string
    {
        return rtrim(rtrim(number_format((float) $value, 8, '.', ''), '0'), '.') ?: '0';
    }

    public static function currencyStatusBadge(?bool $isActive): string
    {
        if ($isActive === null) {
            return '<span class="badge badge-secondary">Unknown</span>';
        }

        return $isActive
            ? '<span class="badge badge-success">Enabled</span>'
            : '<span class="badge badge-warning">Disabled</span>';
    }
}
