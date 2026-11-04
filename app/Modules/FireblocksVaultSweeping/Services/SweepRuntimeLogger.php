<?php

namespace App\Modules\FireblocksVaultSweeping\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Console\OutputStyle;
use Symfony\Component\Console\Output\OutputInterface;

class SweepRuntimeLogger
{
    public const CHANNEL_SCHEDULED = 'scheduled cron';

    public const CHANNEL_RECONCILE = 'reconcile';

    protected ?OutputInterface $output = null;

    public function bindOutput(OutputInterface $output): self
    {
        $this->output = $output;

        return $this;
    }

    public function clearOutput(): void
    {
        $this->output = null;
    }

    public function hasConsoleOutput(): bool
    {
        return $this->output !== null;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function info(string $channel, string $message, array $context = []): void
    {
        $this->emit('info', $channel, $message, $context);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function warning(string $channel, string $message, array $context = []): void
    {
        $this->emit('warning', $channel, $message, $context);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function error(string $channel, string $message, array $context = []): void
    {
        $this->emit('error', $channel, $message, $context);
    }

    /**
     * @param  array<int, array<string, mixed>>  $results
     */
    public function printResultsTable(array $results): void
    {
        if ($this->output === null || $results === []) {
            return;
        }

        $headers = ['Rule', 'Vault', 'Asset', 'Status', 'Job', 'Reason / FB Tx'];
        $rows = [];

        foreach ($results as $row) {
            $reason = trim((string) ($row['reason'] ?? $row['skip_reason'] ?? $row['message'] ?? ''));
            $fbTx = trim((string) ($row['fireblocks_tx_id'] ?? ''));
            $detail = $reason !== '' ? $reason : ($fbTx !== '' ? $fbTx : '—');

            $rows[] = [
                $this->ruleLabel($row),
                (string) ($row['vault_id'] ?? $row['source_vault_id'] ?? '—'),
                (string) ($row['asset_id'] ?? '—'),
                (string) ($row['status'] ?? '—'),
                isset($row['job_id']) ? (string) $row['job_id'] : '—',
                $detail,
            ];
        }

        $this->output->writeln('');
        $this->output->writeln('<info>Sweep evaluation results</info>');
        $this->printTable($headers, $rows);
    }

    /**
     * @param  list<string>  $headers
     * @param  list<list<string>>  $rows
     */
    protected function printTable(array $headers, array $rows): void
    {
        if ($this->output instanceof OutputStyle) {
            $this->output->table($headers, $rows);

            return;
        }

        $this->output->writeln(implode(' | ', $headers));
        foreach ($rows as $row) {
            $this->output->writeln(implode(' | ', $row));
        }
    }

    /**
     * @param  array<string, mixed>  $summary
     */
    public function printRunSummary(array $summary): void
    {
        if ($this->output === null) {
            return;
        }

        $this->output->writeln(sprintf(
            '<info>Summary:</info> evaluated=%d submitted=%d skipped_not_due=%d',
            (int) ($summary['evaluated'] ?? 0),
            (int) ($summary['submitted'] ?? 0),
            (int) ($summary['skipped_not_due'] ?? 0)
        ));

        if (!empty($summary['message'])) {
            $this->output->writeln('<comment>' . (string) $summary['message'] . '</comment>');
        }
    }

    /**
     * @param  array<string, mixed>  $context
     */
    protected function emit(string $level, string $channel, string $message, array $context): void
    {
        $logMessage = '[Sweep ' . $channel . '] ' . $message;

        match ($level) {
            'warning' => Log::warning($logMessage, $context),
            'error' => Log::error($logMessage, $context),
            default => Log::info($logMessage, $context),
        };

        if ($this->output === null) {
            return;
        }

        $line = $logMessage;
        $contextSuffix = $this->formatContextForConsole($context);

        if ($contextSuffix !== '') {
            $line .= ' ' . $contextSuffix;
        }

        $styled = match ($level) {
            'warning' => '<comment>' . $line . '</comment>',
            'error' => '<error>' . $line . '</error>',
            default => $line,
        };

        $this->output->writeln($styled);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    protected function formatContextForConsole(array $context): string
    {
        if ($context === []) {
            return '';
        }

        $parts = [];
        foreach ($context as $key => $value) {
            if (is_array($value)) {
                $value = json_encode($value, JSON_UNESCAPED_SLASHES);
            }

            $parts[] = $key . '=' . (string) $value;
        }

        return '(' . implode(', ', $parts) . ')';
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function ruleLabel(array $row): string
    {
        $id = $row['rule_id'] ?? null;
        $name = trim((string) ($row['rule_name'] ?? ''));

        if ($id === null) {
            return $name !== '' ? $name : '—';
        }

        return $name !== '' ? sprintf('#%s %s', $id, $name) : '#' . $id;
    }
}
