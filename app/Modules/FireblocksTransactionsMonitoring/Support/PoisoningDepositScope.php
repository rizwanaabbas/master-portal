<?php

namespace App\Modules\FireblocksTransactionsMonitoring\Support;

use App\Models\FireblocksTransaction;
use Illuminate\Database\Eloquent\Builder;

/**
 * Heuristic filter for address-poisoning style deposits: small incoming transfers
 * to registered client deposit addresses (ledger-linked), not custody-only orphans.
 */
class PoisoningDepositScope
{
    public const DEFAULT_MAX_AMOUNT = '1';

    /**
     * @param  array<string, mixed>|null  $config  Resolved FireblocksTransactionsMonitoring config
     * @return array{default: string, by_asset: array<string, string>}
     */
    public static function resolveAmountLimits(?array $config): array
    {
        $default = trim((string) ($config['poisoning_deposit_max_amount'] ?? self::DEFAULT_MAX_AMOUNT));
        if ($default === '' || !is_numeric($default) || (float) $default <= 0) {
            $default = self::DEFAULT_MAX_AMOUNT;
        }

        $byAsset = [];
        $raw = trim((string) ($config['poisoning_deposit_asset_max_amounts'] ?? ''));
        if ($raw !== '') {
            foreach (preg_split('/\s*,\s*/', $raw) as $pair) {
                if ($pair === '' || !str_contains($pair, ':')) {
                    continue;
                }
                [$assetId, $max] = array_map('trim', explode(':', $pair, 2));
                if ($assetId === '' || $max === '' || !is_numeric($max) || (float) $max <= 0) {
                    continue;
                }
                $byAsset[strtoupper($assetId)] = $max;
            }
        }

        return ['default' => $default, 'by_asset' => $byAsset];
    }

    /**
     * @param  array{default: string, by_asset: array<string, string>}  $limits
     */
    public static function apply(Builder $query, array $limits, string $table = 'fireblocks_transactions'): void
    {
        MonitoringTransactionScope::applyExcludeOrphans($query, $table);

        $query->where(function (Builder $q) use ($table) {
            $q->where($table . '.is_sweep', false)
                ->orWhereNull($table . '.is_sweep');
        });

        self::applyIncomingDepositShape($query, $table);
        self::applyAmountWithinLimits($query, $limits, $table);
    }

    protected static function applyIncomingDepositShape(Builder $query, string $table): void
    {
        $directionCol = $table . '.direction';
        $noteCol = $table . '.note';
        $sourceCol = $table . '.source_vault_account_id';

        $query->where(function (Builder $q) use ($directionCol, $noteCol, $sourceCol) {
            $q->where($directionCol, 'incoming')
                ->orWhere(function (Builder $inner) use ($directionCol, $noteCol, $sourceCol) {
                    $inner->where(function (Builder $dir) use ($directionCol) {
                        $dir->whereNull($directionCol)
                            ->orWhere($directionCol, '')
                            ->orWhereNotIn($directionCol, ['outgoing', 'internal']);
                    })->where(function (Builder $shape) use ($noteCol, $sourceCol) {
                        $shape->where($noteCol, 'Incoming client deposit')
                            ->orWhere($sourceCol, 'external')
                            ->orWhere($sourceCol, '')
                            ->orWhereNull($sourceCol);
                    });
                });
        });

        $query->where(function (Builder $q) use ($noteCol) {
            $q->whereNull($noteCol)
                ->orWhere($noteCol, '')
                ->orWhere($noteCol, 'Incoming client deposit')
                ->orWhere(function (Builder $inner) use ($noteCol) {
                    $inner->where($noteCol, '!=', FireblocksTransaction::CUSTODY_ONLY_NO_LEDGER_NOTE)
                        ->where($noteCol, '!=', FireblocksTransaction::CUSTODY_ONLY_SWEEP_GAS_FEED_NOTE)
                        ->where($noteCol, 'not like', 'Custody only —%');
                });
        });
    }

    /**
     * @param  array{default: string, by_asset: array<string, string>}  $limits
     */
    protected static function applyAmountWithinLimits(Builder $query, array $limits, string $table): void
    {
        $amountCol = $table . '.amount';
        $assetCol = $table . '.asset_id';

        $query->whereNotNull($amountCol)
            ->where($amountCol, '>', 0);

        $default = $limits['default'];
        $byAsset = $limits['by_asset'];

        if ($byAsset === []) {
            $query->whereRaw('CAST(' . $amountCol . ' AS DECIMAL(36,18)) <= ?', [$default]);

            return;
        }

        $assetIds = array_keys($byAsset);
        $query->where(function (Builder $q) use ($amountCol, $assetCol, $default, $byAsset, $assetIds) {
            foreach ($byAsset as $assetId => $max) {
                $q->orWhere(function (Builder $inner) use ($amountCol, $assetCol, $assetId, $max) {
                    $inner->where($assetCol, $assetId)
                        ->whereRaw('CAST(' . $amountCol . ' AS DECIMAL(36,18)) <= ?', [$max]);
                });
            }

            $q->orWhere(function (Builder $inner) use ($amountCol, $assetCol, $default, $assetIds) {
                $inner->where(function (Builder $notListed) use ($assetCol, $assetIds) {
                    $notListed->whereNull($assetCol)
                        ->orWhere($assetCol, '')
                        ->orWhereNotIn($assetCol, $assetIds);
                })->whereRaw('CAST(' . $amountCol . ' AS DECIMAL(36,18)) <= ?', [$default]);
            });
        });
    }
}
