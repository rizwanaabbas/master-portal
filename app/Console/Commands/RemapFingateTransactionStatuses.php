<?php

namespace App\Console\Commands;

use App\Helpers\FingateHelper;
use App\Models\CardDetail;
use App\Models\CardStatement;
use App\Models\CardStatementsDetail;
use Illuminate\Console\Command;
use Illuminate\Http\JsonResponse;

class RemapFingateTransactionStatuses extends Command
{
    protected $signature = 'transactions:remap-fingate-statuses
        {--dry-run : Show the status changes without saving}';

    protected $description = 'Fetch Fingate transactions per active card and rewrite stored statuses with the current provider mapping';

    /** @var array<string, FingateHelper> */
    private $helpers = [];

    private $cancelled = false;

    public function handle()
    {
        $dryRun = (bool) $this->option('dry-run');
        $cards = $this->cards();
        if ($this->cancelled) {
            return 0;
        }
        if ($cards === null) {
            return 1;
        }
        if ($cards->isEmpty()) {
            $this->warn('No matching cards found.');

            return 0;
        }

        $updated = 0;
        $unchanged = 0;
        $missing = 0;

        foreach ($cards as $card) {
            $this->line('');
            $this->info(sprintf(
                'Card %s (%s, %s)',
                $card->cardNumber ?: 'unknown number',
                $card->card_id,
                $card->cardStatus ?: 'no status'
            ));

            $transactions = $this->transactionsFor($card);
            if ($transactions === null) {
                continue;
            }

            $forCard = 0;
            foreach ($transactions as $txn) {
                if ((string) ($txn->CardId ?? '') !== (string) $card->card_id) {
                    continue;
                }
                $forCard++;
                $txnId = $this->statementTransactionId($txn);
                $statement = CardStatement::where('txn_id', $txnId)
                    ->where(function ($query) use ($card) {
                        $query->where('card_id', $card->card_id)
                            ->orWhere('local_card_id', $card->id);
                    })
                    ->first();

                if (!$statement) {
                    $missing++;
                    $this->line('  skip ' . $txnId . ' (not in card_statements)');
                    continue;
                }

                $result = FingateHelper::mapTransactionStatus($txn->TransactionStatus ?? null);
                $state = FingateHelper::mapSettlementStatus($txn->SettlementStatus ?? null);
                if ($statement->result === $result && $statement->transaction_state === $state) {
                    $unchanged++;
                    continue;
                }

                $this->line(sprintf(
                    '  %s result %s -> %s, settlement %s -> %s',
                    $txnId,
                    $statement->result ?: 'empty',
                    $result,
                    $statement->transaction_state ?: 'empty',
                    $state
                ));

                if (!$dryRun) {
                    $statement->result = $result;
                    $statement->transaction_state = $state;
                    $statement->save();

                    CardStatementsDetail::where('transaction_id', $txnId)->update([
                        'json_data' => json_encode($txn),
                    ]);
                }
                $updated++;
            }

            $this->line('  ' . $forCard . ' transaction(s) returned for this card.');
        }

        $this->line('');
        $this->info(sprintf(
            '%s %d statement(s). %d already matched. %d API transaction(s) had no local statement.',
            $dryRun ? 'Would update' : 'Updated',
            $updated,
            $unchanged,
            $missing
        ));

        return 0;
    }

    private function cards()
    {
        $choice = $this->choice(
            'How should existing transaction statuses be updated?',
            ['Backfill all active cards', 'Enter a specific card_id'],
            1
        );

        $query = CardDetail::query()
            ->whereNotNull('card_id')
            ->where('card_id', '!=', '');

        if ($choice === 'Backfill all active cards') {
            $count = (clone $query)->where('cardStatus', 'Active')->count();
            if (!$this->confirm('Backfill statuses for ' . $count . ' active card(s)?', false)) {
                $this->warn('Cancelled.');
                $this->cancelled = true;

                return collect();
            }

            return $query->where('cardStatus', 'Active')->orderBy('id')->get();
        }

        $cardId = trim((string) $this->ask('Enter the Fingate card_id'));
        if ($cardId === '') {
            $this->error('A card_id is required.');
            $this->cancelled = true;

            return collect();
        }

        $card = (clone $query)->where('card_id', $cardId)->first();
        if (!$card) {
            $this->error('No card found for card_id ' . $cardId . '.');

            return null;
        }

        $this->info(sprintf(
            'Selected card_id %s, number %s, status %s.',
            $card->card_id,
            $card->cardNumber ?: 'unknown',
            $card->cardStatus ?: 'unknown'
        ));

        return collect([$card]);
    }

    private function transactionsFor(CardDetail $card): ?array
    {
        try {
            $response = $this->helper($card)->getAllTransactions([
                'SearchCardId' => (string) $card->card_id,
            ]);
        } catch (\Throwable $e) {
            $this->error('  API error: ' . $e->getMessage());

            return null;
        }

        if ($response instanceof JsonResponse) {
            $body = $response->getData();
            $this->error('  API error: ' . ($body->error ?? $body->ErrorDescription ?? 'request failed'));

            return null;
        }
        if (is_string($response)) {
            $this->error('  API error: ' . $response);

            return null;
        }
        if (empty($response->TransactionList)) {
            $this->warn('  No transactions returned.');

            return [];
        }

        return is_array($response->TransactionList)
            ? $response->TransactionList
            : iterator_to_array($response->TransactionList);
    }

    private function helper(CardDetail $card): FingateHelper
    {
        $provider = ($card->provider_type ?? '') === 'e_money' ? 'e_money' : 'match_move';
        if (!isset($this->helpers[$provider])) {
            $this->helpers[$provider] = new FingateHelper($provider);
        }

        return $this->helpers[$provider];
    }

    private function statementTransactionId($txn): string
    {
        $txnId = (string) ($txn->TransactionId ?? '');
        $description = strtolower((string) ($txn->Description ?? ''));
        if (
            $txnId !== ''
            && ($txn->FeeAmount ?? null) == ($txn->BillingAmount ?? null)
            && strpos($description, 'transaction fee') !== false
            && substr($txnId, -4) !== '-fee'
        ) {
            $txn->TransactionId = $txnId . '-fee';

            return $txn->TransactionId;
        }

        return $txnId;
    }
}
