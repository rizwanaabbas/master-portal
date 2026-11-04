<?php

namespace App\Modules\FireblocksVaultSweeping\Services;

use App\Modules\Fireblocks\Models\Asset;
use App\Modules\Fireblocks\Models\FireblocksVaultAccount;
use App\Modules\FireblocksVaultSweeping\Models\FireblocksSweepRule;
use App\Modules\FireblocksVaultSweeping\Support\SweepDestination;
use Illuminate\Support\Collection;

class SweepRuleMatcher
{
    public function __construct(
        protected ?TaggedVaultScanService $taggedVaultScan = null,
    ) {
        $this->taggedVaultScan = $taggedVaultScan ?? app(TaggedVaultScanService::class);
    }
    /**
     * @param  array<int, array<string, mixed>>|null  $webhookDestinationTags
     */
    public function matchingRules(
        string $sourceVaultId,
        string $assetId,
        string $triggerMode,
        ?array $config = null,
        ?array $webhookDestinationTags = null
    ): Collection {
        $config = $config ?? SweepConfigService::tryGet() ?? [];

        return FireblocksSweepRule::query()
            ->active()
            ->orderBy('priority')
            ->orderBy('id')
            ->get()
            ->filter(fn (FireblocksSweepRule $rule) => $this->ruleApplies(
                $rule,
                $sourceVaultId,
                $assetId,
                $config,
                $triggerMode,
                $webhookDestinationTags
            ));
    }

    /**
     * @param  array<int, array<string, mixed>>|null  $webhookDestinationTags
     */
    public function ruleApplies(
        FireblocksSweepRule $rule,
        string $sourceVaultId,
        string $assetId,
        ?array $config = null,
        ?string $triggerMode = null,
        ?array $webhookDestinationTags = null
    ): bool {
        if (!$rule->isRunnable()) {
            return false;
        }

        $config = $config ?? SweepConfigService::tryGet() ?? [];
        $omnibusVaultId = trim((string) ($config['default_omnibus_vault_id'] ?? ''));

        if (!SweepDestination::isPlaceholderVaultId($omnibusVaultId) && $sourceVaultId === $omnibusVaultId) {
            return false;
        }

        if (!$this->isAccountBasedAsset($assetId)) {
            return false;
        }

        if ($triggerMode !== null && !$rule->supportsTrigger($triggerMode)) {
            return false;
        }

        if (!$this->ruleMatchesSource($rule, $sourceVaultId, $webhookDestinationTags, $config)) {
            return false;
        }

        if (!$this->ruleMatchesAsset($rule, $assetId)) {
            return false;
        }

        $destination = SweepDestination::resolveVaultId($rule, $config);

        if (!SweepDestination::isValidForSource($destination, $sourceVaultId)) {
            return false;
        }

        return true;
    }

    public function isAccountBasedAsset(string $assetId): bool
    {
        $asset = Asset::query()->where('asset_id', $assetId)->first();

        if (!$asset) {
            return true;
        }

        return $asset->type !== 'utxo';
    }

    /**
     * @param  array<int, array<string, mixed>>|null  $webhookDestinationTags
     */
    protected function ruleMatchesSource(
        FireblocksSweepRule $rule,
        string $sourceVaultId,
        ?array $webhookDestinationTags = null,
        ?array $config = null
    ): bool {
        return match ($rule->source_type) {
            'vault_id_list' => in_array($sourceVaultId, $rule->sourceVaultIdList(), true),
            'vault_name_prefix' => $this->vaultTitleMatchesPrefix($sourceVaultId, $rule->source_vault_ids),
            default => $this->taggedVaultScan->vaultHasClientIntermediaryTag(
                $sourceVaultId,
                $config,
                $webhookDestinationTags
            ),
        };
    }

    /**
     * Explain why deposit webhook rules did or did not match (for logging).
     *
     * @param  array<int, array<string, mixed>>|null  $webhookDestinationTags
     * @return list<array<string, mixed>>
     */
    public function diagnoseDepositRules(
        string $sourceVaultId,
        string $assetId,
        ?array $webhookDestinationTags = null,
        ?array $config = null,
        bool $orphanDeposit = false
    ): array {
        $config = $config ?? SweepConfigService::tryGet() ?? [];
        $diagnostics = [];

        foreach (FireblocksSweepRule::query()->active()->orderBy('priority')->orderBy('id')->get() as $rule) {
            $reasons = [];
            $matched = $this->ruleApplies($rule, $sourceVaultId, $assetId, $config, 'deposit', $webhookDestinationTags);

            if ($orphanDeposit && !$rule->sweepsOrphanDeposits()) {
                $reasons[] = 'orphan sweep disabled';
                $matched = false;
            }

            if (!$rule->supportsTrigger('deposit')) {
                $reasons[] = 'deposit trigger disabled';
                $matched = false;
            }
            if (!$this->ruleMatchesAsset($rule, $assetId)) {
                $reasons[] = 'asset mismatch (rule: ' . ($rule->asset_ids ?: 'any') . ')';
                $matched = false;
            }
            if (!$this->ruleMatchesSource($rule, $sourceVaultId, $webhookDestinationTags, $config)) {
                $reasons[] = 'source vault scope mismatch';
                $matched = false;
            }
            if ($matched && !$this->ruleApplies($rule, $sourceVaultId, $assetId, $config, 'deposit', $webhookDestinationTags)) {
                $reasons[] = 'destination/omnibus/asset type blocked match';
                $matched = false;
            }

            $diagnostics[] = [
                'rule_id' => $rule->id,
                'rule_name' => $rule->name,
                'matched' => $matched && ($orphanDeposit ? $rule->sweepsOrphanDeposits() : true),
                'reasons' => $reasons,
                'orphan_sweep' => $rule->sweepsOrphanDeposits(),
            ];
        }

        return $diagnostics;
    }

    protected function ruleMatchesAsset(FireblocksSweepRule $rule, string $assetId): bool
    {
        $allowed = $rule->assetIdList();

        return $allowed === [] || in_array($assetId, $allowed, true);
    }

    protected function vaultTitleMatchesPrefix(string $vaultId, ?string $prefix): bool
    {
        $prefix = trim((string) $prefix);
        if ($prefix === '') {
            return false;
        }

        $vault = FireblocksVaultAccount::query()->where('vault_id', $vaultId)->first();
        if (!$vault) {
            return false;
        }

        return str_starts_with(strtolower((string) $vault->vault_title), strtolower($prefix));
    }
}
