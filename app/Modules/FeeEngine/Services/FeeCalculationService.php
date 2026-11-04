<?php

namespace App\Modules\FeeEngine\Services;

use App\Models\Account;
use App\Modules\FeeEngine\Models\CurrencyFeeTier;
use App\Modules\FeeEngine\Models\FeeTierGroup;
use App\Modules\FeeEngine\Models\FeeTransactionCharge;
use RuntimeException;
use Illuminate\Support\Facades\Log;

/**
 * Generic fee engine.
 *
 * A fee rule applies at the account/currency level and fires the same way
 * regardless of transaction direction. Fees can stack (multiple rules per
 * currency) and are each calculated independently against the original amount.
 */
class FeeCalculationService
{
    /**
     * Compute the fee breakdown WITHOUT persisting anything (used for quotes).
     *
     * $account       — the account the fee is charged in (its currency = fee currency,
     *                  and drives rounding precision).
     * $tierAccount   — optional: the account used to resolve the tier group / rules /
     *                  applicability. Defaults to $account. B2c2 passes the CRYPTO
     *                  account here so a crypto-configured rule can be charged in FIAT.
     */
    public function preview(Account $account, float $amount, string $direction, ?Account $tierAccount = null): array
    {
        $tierAccount = $tierAccount ?: $account;

        $notApplied = [
            'applied'            => false,
            'charges'            => [],
            'total_fee'          => 0.0,
            'net_amount'         => $amount,
            'crypto_fee_display' => ConfigService::cryptoFeeDisplay(),
            'group_id'           => null,
            'currency_id'        => $account->account_currency,
        ];

        if (!ConfigService::isEnabled()) {
            return $notApplied;
        }

        // Charge currency (fee denomination + precision) vs. tier currency
        // (which rules/applicability to evaluate).
        $chargeCurrency = $account->currency;
        $chargeType = ($chargeCurrency && $chargeCurrency->is_crypto) ? 'crypto' : 'fiat';

        $tierCurrency = $tierAccount->currency;
        $tierType = ($tierCurrency && $tierCurrency->is_crypto) ? 'crypto' : 'fiat';

        // Applicability is judged on the tier currency type (what's being traded).
        if (!ConfigService::appliesTo($tierType)) {
            return $notApplied;
        }

        $precision = $chargeType === 'crypto'
            ? ConfigService::get('rounding_precision_crypto', 8)
            : ConfigService::get('rounding_precision_fiat', 2);

        // Tier group is optional when FeeHelper integration is on (FeeHelper can
        // supply a fee even without a configured tier group).
        $group = $this->resolveGroup($tierAccount);
        $rules = $group ? $this->matchRules($group, $tierAccount->account_currency, $tierType) : collect();

        $charges = [];
        $totalFee = 0.0;

        foreach ($rules as $rule) {
            $feeAmount = $this->computeRuleFee($rule, $amount, $precision);
            $totalFee += $feeAmount;

            $charges[] = [
                'currency_fee_tier_id' => $rule->id,
                'label'                => $rule->label,
                'fee_type'             => $rule->fee_type,
                'fee_rate'             => (float) $rule->fee_value,
                'fee_amount'           => $feeAmount,
            ];
        }

        // Additive: pull the existing FeeHelper fee when integration is enabled
        // for the traded currency type. Never modifies FeeHelper's own logic.
        if (ConfigService::feeHelperEnabledFor($tierType)) {
            $helperFee = $this->round(\App\Helpers\FeeHelper::resolveAccountFeeAmount($account), $precision);
            if ($helperFee > 0) {
                $totalFee += $helperFee;
                $charges[] = [
                    'currency_fee_tier_id' => null,
                    'label'                => 'Account Fee (FeeHelper)',
                    'fee_type'             => 'flat',
                    'fee_rate'             => $helperFee,
                    'fee_amount'           => $helperFee,
                ];
            }
        }

        if (empty($charges)) {
            $this->warn('No matching fee rule (and no FeeHelper fee) for account', $account);
            return $this->handleMissing($notApplied);
        }

        $totalFee = $this->round($totalFee, $precision);

        return [
            'applied'            => true,
            'charges'            => $charges,
            'total_fee'          => $totalFee,
            'net_amount'         => $this->applyDirection($amount, $totalFee, $direction),
            'crypto_fee_display' => ConfigService::cryptoFeeDisplay(),
            'group_id'           => $group->id ?? null,
            'currency_id'        => $account->account_currency,
            'currency_type'      => $chargeType,   // fee denomination
            'tier_currency_type' => $tierType,     // what was traded (crypto for B2c2)
        ];
    }

    /**
     * Compute AND persist a charge row per matched rule. Returns the same
     * breakdown as preview(), with an added 'charge_ids' list.
     */
    public function charge(
        Account $account,
        float $amount,
        string $direction,
        string $transactionReference,
        string $transactionType,
        array $meta = [],
        ?Account $tierAccount = null
    ): array {
        $result = $this->preview($account, $amount, $direction, $tierAccount);

        if (!$result['applied'] || empty($result['charges'])) {
            $result['charge_ids'] = [];
            return $result;
        }

        $chargeIds = [];

        foreach ($result['charges'] as $charge) {
            $row = FeeTransactionCharge::create([
                'transaction_reference' => $transactionReference,
                'transaction_type'      => $transactionType,
                'b2c2_order_id'         => $meta['b2c2_local_order'] ?? null,
                'tr_request_id'         => $meta['tr_request_id'] ?? null,
                'client_id'             => $account->client_id,
                'account_id'            => $account->id,
                'currency_id'           => $account->account_currency,
                'direction'             => $direction,
                'fee_tier_group_id'     => $result['group_id'],
                'currency_fee_tier_id'  => $charge['currency_fee_tier_id'],
                'fee_label'             => $charge['label'],
                'fee_type'              => $charge['fee_type'],
                'fee_rate'              => $charge['fee_rate'],
                'transaction_amount'    => $amount,
                'fee_amount'            => $charge['fee_amount'],
                // Snapshot the display mode for crypto trades (even when the fee
                // is denominated in fiat).
                'crypto_fee_display'    => ($result['tier_currency_type'] ?? $result['currency_type'] ?? null) === 'crypto'
                    ? $result['crypto_fee_display']
                    : null,
                'status'                => 'charged',
                'meta'                  => $meta ?: null,
            ]);
            $chargeIds[] = $row->id;
        }

        $result['charge_ids'] = $chargeIds;
        return $result;
    }

    /**
     * Resolve the client's assigned group, falling back to the default group.
     */
    protected function resolveGroup(Account $account): ?FeeTierGroup
    {
        // Resolve the client WITHOUT global scopes — a client's fee tier must
        // apply regardless of the viewer's client-visibility restrictions (e.g.
        // the "basic admin" unit scope on the Client model would otherwise hide
        // the client and silently fall the fee back to the default tier).
        $client = $account->client_id
            ? \App\Models\Client::withoutGlobalScopes()->find($account->client_id)
            : null;

        // Fall back to the parent client's assignment if this one has none.
        $guard = 0;
        while ($client && empty($client->fee_tier_group_id) && !empty($client->parent_id) && $guard++ < 5) {
            $client = \App\Models\Client::withoutGlobalScopes()->find($client->parent_id);
        }

        if ($client && $client->fee_tier_group_id) {
            $group = FeeTierGroup::where('id', $client->fee_tier_group_id)
                ->where('is_active', true)
                ->first();
            if ($group) {
                return $group;
            }
        }

        $configuredDefault = ConfigService::get('default_fee_tier_group_id');
        if ($configuredDefault) {
            $group = FeeTierGroup::where('id', $configuredDefault)->where('is_active', true)->first();
            if ($group) {
                return $group;
            }
        }

        return FeeTierGroup::default();
    }

    /**
     * A specific-currency rule always overrides the matching type-wildcard
     * (all_crypto / all_fiat). Only if there is no exact-currency rule do the
     * type-wildcard rules apply.
     */
    protected function matchRules(FeeTierGroup $group, $currencyId, string $currencyType)
    {
        $wildcardScope = $currencyType === 'fiat' ? 'all_fiat' : 'all_crypto';

        $exact = CurrencyFeeTier::where('fee_tier_group_id', $group->id)
            ->where('is_active', true)
            ->where('currency_scope', 'currency')
            ->where('currency_id', $currencyId)
            ->orderBy('priority')
            ->get();

        if ($exact->isNotEmpty()) {
            return $exact;
        }

        return CurrencyFeeTier::where('fee_tier_group_id', $group->id)
            ->where('is_active', true)
            ->where('currency_scope', $wildcardScope)
            ->orderBy('priority')
            ->get();
    }

    protected function computeRuleFee(CurrencyFeeTier $rule, float $amount, int $precision): float
    {
        if ($rule->fee_type === 'flat') {
            return $this->round((float) $rule->fee_value, $precision);
        }

        // percentage
        $fee = $amount * ((float) $rule->fee_value / 100);

        if ($rule->min_fee !== null && $fee < (float) $rule->min_fee) {
            $fee = (float) $rule->min_fee;
        }
        if ($rule->max_fee !== null && $fee > (float) $rule->max_fee) {
            $fee = (float) $rule->max_fee;
        }

        return $this->round($fee, $precision);
    }

    /**
     * Debit: client pays amount + fee. Credit: client receives amount - fee.
     */
    protected function applyDirection(float $amount, float $totalFee, string $direction): float
    {
        return $direction === 'debit' ? $amount + $totalFee : $amount - $totalFee;
    }

    protected function round(float $value, int $precision): float
    {
        $mode = ConfigService::get('rounding_mode', 'half_up');
        $phpMode = match ($mode) {
            'half_down' => PHP_ROUND_HALF_DOWN,
            'up', 'down' => PHP_ROUND_HALF_UP, // simple approximation; see note
            default => PHP_ROUND_HALF_UP,
        };

        if ($mode === 'up') {
            $factor = 10 ** $precision;
            return ceil($value * $factor) / $factor;
        }
        if ($mode === 'down') {
            $factor = 10 ** $precision;
            return floor($value * $factor) / $factor;
        }

        return round($value, $precision, $phpMode);
    }

    /**
     * Fail-open vs fail-closed on missing configuration.
     */
    protected function handleMissing(array $notApplied): array
    {
        if (ConfigService::get('block_on_missing_tier', false)) {
            throw new RuntimeException('No fee tier configured for this account currency.');
        }
        return $notApplied; // fail open: zero fee
    }

    protected function warn(string $message, Account $account): void
    {
        Log::warning('[FeeEngine] ' . $message, [
            'account_id'  => $account->id,
            'client_id'   => $account->client_id,
            'currency_id' => $account->account_currency,
        ]);
    }
}
