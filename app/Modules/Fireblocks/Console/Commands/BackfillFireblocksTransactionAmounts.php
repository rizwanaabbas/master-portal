<?php

namespace App\Modules\Fireblocks\Console\Commands;

use App\Models\FireblocksTransaction;
use App\Modules\Fireblocks\Support\CoinGeckoUsdPriceService;
use App\Modules\Fireblocks\Support\FireblocksAmount;
use Fireblocks\Sdk\Facades\Fireblocks;
use Illuminate\Console\Command;

class BackfillFireblocksTransactionAmounts extends Command
{
    protected $signature = 'fireblocks:backfill-tx-amounts
        {--chunk=25}
        {--limit=0}
        {--from-api : GET Fireblocks /v1/transactions/{id} and store amountInfo}
        {--only-missing : Only rows with empty amount_usd}
        {--outgoing : Only outgoing}
        {--sleep=150 : Milliseconds between Fireblocks GETs}';

    protected $description = 'Backfill Fireblocks amount / amount_usd (amountInfo.amountUSD) for monitoring Tx USD';

    public function handle(): int
    {
        $chunk = max(5, (int) $this->option('chunk'));
        $limit = max(0, (int) $this->option('limit'));
        $fromApi = (bool) $this->option('from-api');
        $onlyMissing = (bool) $this->option('only-missing');
        $outgoing = (bool) $this->option('outgoing');
        $sleepMs = max(0, (int) $this->option('sleep'));
        $updated = 0;
        $skipped = 0;
        $seen = 0;
        $errors = 0;

        $query = FireblocksTransaction::query()
            ->whereNotNull('fireblocks_tx_id')
            ->where('fireblocks_tx_id', '!=', '')
            ->orderBy('id');

        if ($outgoing) {
            $query->where('direction', 'outgoing');
        }
        if ($onlyMissing) {
            $query->where(function ($q) {
                $q->whereNull('amount_usd')->orWhere('amount_usd', 0);
            });
        }

        $query->chunkById($chunk, function ($rows) use (
            $fromApi,
            $limit,
            $sleepMs,
            &$updated,
            &$skipped,
            &$seen,
            &$errors
        ) {
            foreach ($rows as $record) {
                if ($limit > 0 && $seen >= $limit) {
                    return false;
                }
                $seen++;

                $apiData = [];
                if ($fromApi) {
                    $txId = (string) $record->fireblocks_tx_id;
                    if (!$this->looksLikeFireblocksId($txId)) {
                        $skipped++;
                        continue;
                    }
                    try {
                        $apiData = Fireblocks::get('/v1/transactions/' . $txId);
                        if (!is_array($apiData)) {
                            $apiData = [];
                        }
                    } catch (\Throwable $e) {
                        $this->warn("GET {$txId}: " . $e->getMessage());
                        $errors++;
                        continue;
                    }
                    if ($sleepMs > 0) {
                        usleep($sleepMs * 1000);
                    }
                    $existing = is_array($record->response_data) ? $record->response_data : [];
                    foreach (['amountInfo', 'requestedAmount', 'netAmount', 'assetId', 'amount'] as $key) {
                        if (array_key_exists($key, $apiData)) {
                            $existing[$key] = $apiData[$key];
                        }
                    }
                    $record->response_data = $existing;
                }

                $beforeUsd = $record->amount_usd ?? null;
                $beforeAmt = $record->amount ?? null;
                FireblocksAmount::applyToRecord($record, $apiData);

                $usdEmpty = $record->amount_usd === null || $record->amount_usd === '' || (float) $record->amount_usd <= 0;
                if ($usdEmpty && CoinGeckoUsdPriceService::isUsdStableAsset((string) ($record->asset_id ?? ''))) {
                    $qty = FireblocksAmount::fromRecord($record, $apiData);
                    if ($qty !== null && is_numeric($qty) && (float) $qty > 0) {
                        $record->amount_usd = (float) $qty;
                    }
                }

                $changed = (string) ($record->amount_usd ?? '') !== (string) ($beforeUsd ?? '')
                    || (string) ($record->amount ?? '') !== (string) ($beforeAmt ?? '')
                    || $fromApi;

                if ($changed) {
                    $record->save();
                    if ((string) ($record->amount_usd ?? '') !== (string) ($beforeUsd ?? '')) {
                        $updated++;
                    }
                }
            }

            return true;
        });

        $this->info("Scanned {$seen}. Updated amount_usd on {$updated}. Skipped {$skipped} local ids. API errors {$errors}.");
        $this->comment('Tx USD uses stored amount_usd / Fireblocks amountInfo.amountUSD. Now USD uses CoinGecko (USDT/USDC fallback $1).');

        return self::SUCCESS;
    }

    protected function looksLikeFireblocksId(string $txId): bool
    {
        return (bool) preg_match('/^[a-f0-9-]{20,}$/i', $txId);
    }
}
