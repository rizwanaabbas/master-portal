<?php

namespace App\Modules\FireblocksVaultSweeping\Support;

use App\Modules\FireblocksVaultSweeping\Models\FireblocksSweepRule;
use Illuminate\Validation\ValidationException;

class SweepRuleExport
{
    public const EXPORT_VERSION = 1;

    /** @var list<string> */
    private const EXPORT_KEYS = [
        'name',
        'priority',
        'enabled',
        'source_type',
        'source_vault_ids',
        'destination_vault_id',
        'asset_ids',
        'target_asset_id',
        'gas_funding_vault_id',
        'feed_trx_amount',
        'use_gasless',
        'treat_as_gross_amount',
        'min_balance',
        'scheduled_min_balance',
        'schedule_interval_minutes',
        'schedule_run_at',
        'min_balance_usd',
        'reserve_amount',
        'sweep_amount_source',
        'trigger_modes',
        'deposit_delay_minutes',
        'sweep_orphan_deposits',
        'fee_level',
        'max_fee_usd',
        'skip_if_fee_high',
        'sweep_tokens_before_base',
        'dry_run',
    ];

    public static function document(FireblocksSweepRule $rule): array
    {
        $attributes = [];
        foreach (self::EXPORT_KEYS as $key) {
            $attributes[$key] = self::normalizeExportValue($key, $rule->getAttribute($key));
        }

        return [
            'export_version' => self::EXPORT_VERSION,
            'exported_at' => now()->toIso8601String(),
            'source_rule_id' => $rule->id,
            'rule' => $attributes,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function attributesForCreate(array $document): array
    {
        $version = (int) ($document['export_version'] ?? 0);
        if ($version !== self::EXPORT_VERSION) {
            throw ValidationException::withMessages([
                'import' => 'Unsupported export version. Re-export from a current portal.',
            ]);
        }

        $rule = $document['rule'] ?? null;
        if (!is_array($rule)) {
            throw ValidationException::withMessages([
                'import' => 'Invalid file: missing "rule" object.',
            ]);
        }

        $data = [];
        foreach (self::EXPORT_KEYS as $key) {
            if (array_key_exists($key, $rule)) {
                $data[$key] = $rule[$key];
            }
        }

        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            throw ValidationException::withMessages(['import' => 'Rule name is required in the import file.']);
        }

        $assetId = trim((string) ($data['asset_ids'] ?? ''));
        if ($assetId === '' || str_contains($assetId, ',')) {
            throw ValidationException::withMessages([
                'import' => 'Import supports one source asset per rule (asset_ids must be a single id).',
            ]);
        }

        $triggerModes = array_filter(array_map('trim', explode(',', strtolower((string) ($data['trigger_modes'] ?? '')))));
        if ($triggerModes === []) {
            throw ValidationException::withMessages([
                'import' => 'At least one trigger (deposit and/or scheduled) is required.',
            ]);
        }

        $useGasless = filter_var($data['use_gasless'] ?? false, FILTER_VALIDATE_BOOLEAN);
        if ($useGasless && !SweepAssetMetadata::usesEvmGasRelay($assetId)) {
            throw ValidationException::withMessages([
                'import' => 'Gas relay applies to Ethereum ERC-20 USDC/USDT only.',
            ]);
        }

        $treatAsGross = filter_var($data['treat_as_gross_amount'] ?? false, FILTER_VALIDATE_BOOLEAN);
        if ($treatAsGross && !SweepAssetMetadata::isNativeAccountAsset($assetId)) {
            throw ValidationException::withMessages([
                'import' => 'Treat as gross amount applies to native/base assets only.',
            ]);
        }

        if ($treatAsGross && $useGasless) {
            throw ValidationException::withMessages([
                'import' => 'Cannot combine treat as gross amount with gas relay.',
            ]);
        }

        $scheduleRunAt = trim((string) ($data['schedule_run_at'] ?? ''));
        if ($scheduleRunAt === '') {
            $data['schedule_run_at'] = null;
        } elseif (!preg_match('/^(\d{2}):(\d{2})$/', $scheduleRunAt, $timeParts)
            || (int) $timeParts[1] > 23
            || (int) $timeParts[2] > 59) {
            throw ValidationException::withMessages([
                'import' => 'schedule_run_at must be a valid 24-hour time (HH:MM).',
            ]);
        }

        $data['name'] = self::uniqueImportName($name);
        $data['enabled'] = filter_var($data['enabled'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $data['priority'] = max(1, (int) ($data['priority'] ?? 100));
        $data['source_type'] = in_array($data['source_type'] ?? '', ['client_deposit_vaults', 'vault_id_list', 'vault_name_prefix'], true)
            ? $data['source_type']
            : 'client_deposit_vaults';
        $data['target_asset_id'] = trim((string) ($data['target_asset_id'] ?? '')) ?: $assetId;
        $data['fee_level'] = strtoupper((string) ($data['fee_level'] ?? 'LOW'));
        if (!in_array($data['fee_level'], FireblocksSweepRule::FEE_LEVELS, true)) {
            $data['fee_level'] = 'LOW';
        }

        $sweepSource = (string) ($data['sweep_amount_source'] ?? FireblocksSweepRule::SWEEP_AMOUNT_SOURCE_AVAILABLE);
        $data['sweep_amount_source'] = $sweepSource === FireblocksSweepRule::SWEEP_AMOUNT_SOURCE_MAX_SPENDABLE
            ? FireblocksSweepRule::SWEEP_AMOUNT_SOURCE_MAX_SPENDABLE
            : FireblocksSweepRule::SWEEP_AMOUNT_SOURCE_AVAILABLE;

        $data['use_gasless'] = $useGasless;
        $data['treat_as_gross_amount'] = $treatAsGross;
        $data['sweep_orphan_deposits'] = filter_var($data['sweep_orphan_deposits'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $data['dry_run'] = filter_var($data['dry_run'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $data['skip_if_fee_high'] = filter_var($data['skip_if_fee_high'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $data['sweep_tokens_before_base'] = filter_var($data['sweep_tokens_before_base'] ?? false, FILTER_VALIDATE_BOOLEAN);

        unset($data['schedule_last_run_at']);

        return $data;
    }

    /**
     * @param  mixed  $value
     * @return mixed
     */
    private static function normalizeExportValue(string $key, $value)
    {
        if ($value === null) {
            return null;
        }

        if ($key === 'schedule_run_at') {
            if ($value instanceof \DateTimeInterface) {
                return $value->format('H:i');
            }

            return (string) $value;
        }

        if (is_bool($value) || is_int($value)) {
            return $value;
        }

        if (is_float($value)) {
            return (string) $value;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->toIso8601String();
        }

        if (is_object($value) && !is_array($value)) {
            return (string) $value;
        }

        return $value;
    }

    private static function uniqueImportName(string $name): string
    {
        if (!FireblocksSweepRule::query()->where('name', $name)->exists()) {
            return $name;
        }

        $suffix = ' (imported)';
        $candidate = $name . $suffix;
        if (!FireblocksSweepRule::query()->where('name', $candidate)->exists()) {
            return $candidate;
        }

        return $name . $suffix . ' ' . now()->format('Y-m-d H:i');
    }
}
