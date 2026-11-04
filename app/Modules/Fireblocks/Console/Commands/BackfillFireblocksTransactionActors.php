<?php

namespace App\Modules\Fireblocks\Console\Commands;

use App\Models\FireblocksTransaction;
use App\Modules\Fireblocks\Services\FireblocksTransactionActorService;
use Illuminate\Console\Command;

class BackfillFireblocksTransactionActors extends Command
{
    protected $signature = 'fireblocks:backfill-tx-actors {--chunk=50} {--from-api} {--limit=0}';

    protected $description = 'Parse initiator, TAP approvers, and signers from stored payloads (optional Fireblocks GET)';

    public function handle(FireblocksTransactionActorService $actors): int
    {
        $chunk = max(10, (int) $this->option('chunk'));
        $fromApi = (bool) $this->option('from-api');
        $limit = max(0, (int) $this->option('limit'));
        $updated = 0;
        $seen = 0;

        $query = FireblocksTransaction::query()
            ->whereNotNull('fireblocks_tx_id')
            ->orderBy('id');

        $query->chunkById($chunk, function ($rows) use ($actors, $fromApi, $limit, &$updated, &$seen) {
            foreach ($rows as $record) {
                if ($limit > 0 && $seen >= $limit) {
                    return false;
                }
                $seen++;
                $before = [
                    $record->initiator_user_id,
                    json_encode($record->signed_by_user_ids),
                    json_encode($record->tap_approver_user_ids),
                ];
                if ($fromApi) {
                    $actors->enrichFromApi($record);
                } else {
                    $actors->apply($record);
                }
                $after = [
                    $record->initiator_user_id,
                    json_encode($record->signed_by_user_ids),
                    json_encode($record->tap_approver_user_ids),
                ];
                if ($before !== $after) {
                    $record->save();
                    $updated++;
                } elseif ($fromApi) {
                    $record->save();
                }
            }

            return true;
        });

        $this->info("Updated {$updated} Fireblocks transaction(s).");

        return self::SUCCESS;
    }
}
