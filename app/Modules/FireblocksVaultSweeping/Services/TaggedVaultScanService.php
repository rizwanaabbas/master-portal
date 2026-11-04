<?php

namespace App\Modules\FireblocksVaultSweeping\Services;

use App\Modules\FireblocksVaultSweeping\Models\FireblocksSweepRule;
use App\Modules\FireblocksVaultSweeping\Support\SweepMath;
use Fireblocks\Sdk\Api\Vaults;
use Fireblocks\Sdk\Facades\Fireblocks;
use Illuminate\Support\Facades\Log;

class TaggedVaultScanService
{
    public const PAGE_LIMIT = 500;

    /**
     * Cursor-paged scan of Client Intermediary vaults by Fireblocks tag ID.
     *
     * Passes assetId to Fireblocks so the API pre-filters vaults that hold the asset
     * (less paging/iteration). Threshold checks still use available balance in our engine.
     *
     * @return \Generator<int, array{accounts: array<int, array<string, mixed>>, paging: array<string, mixed>}>
     */
    public function eachTaggedVaultPage(?array $config = null, ?string $assetId = null): \Generator
    {
        $tagId = $this->resolveTagId($config);
        if ($tagId === '') {
            return;
        }

        $after = null;
        $firstPage = true;

        do {
            $params = [
                // includeTagIds = current API; tagIds = older/sandbox fallback (same UUID)
                'includeTagIds' => $tagId,
                'tagIds' => $tagId,
                'limit' => self::PAGE_LIMIT,
            ];

            if ($assetId !== null && $assetId !== '') {
                $params['assetId'] = $assetId;
            }

            if ($after !== null && $after !== '') {
                $params['after'] = $after;
            }

            try {
                $page = Fireblocks::vaults()->listAccountsPaged($params);
            } catch (\Throwable $e) {
                Log::error('Tagged vault scan page failed', [
                    'tag_id' => $tagId,
                    'asset_id' => $assetId,
                    'after' => $after,
                    'query' => Vaults::buildPagedQueryString($params),
                    'error' => $e->getMessage(),
                ]);

                break;
            }

            if ($firstPage && ($page['accounts'] ?? []) === []) {
                Log::warning('Tagged vault scan returned no accounts on first page', [
                    'tag_id' => $tagId,
                    'asset_id' => $assetId,
                    'query' => Vaults::buildPagedQueryString($params),
                    'paging' => $page['paging'] ?? [],
                ]);
            }

            $firstPage = false;

            yield $page;

            $after = (string) ($page['paging']['after'] ?? '');
        } while ($after !== '');
    }

    /**
     * Sweep candidates for a rule using tagged vault scan + available-balance threshold (engine-side).
     *
     * @return array<int, array{vault_id: string, asset_id: string, available: string}>
     */
    public function findCandidatesForRule(
        FireblocksSweepRule $rule,
        string $triggerSource,
        string $threshold,
        ?array $config = null,
        bool $nonZeroOnly = true
    ): array {
        if ($rule->source_type !== 'client_deposit_vaults') {
            return [];
        }

        $tagId = $this->resolveTagId($config);
        if ($tagId === '') {
            Log::warning('Tagged vault scan skipped — Client Intermediary tag ID not configured');

            return [];
        }

        $assetIds = $rule->assetIdList();
        if ($assetIds === []) {
            return [];
        }

        $pairs = [];

        foreach ($assetIds as $assetId) {
            foreach ($this->eachTaggedVaultPage($config, $assetId) as $page) {
                foreach ($page['accounts'] as $account) {
                    $vaultId = (string) ($account['id'] ?? '');
                    if ($vaultId === '') {
                        continue;
                    }

                    $available = $this->extractAvailableBalance($account, $assetId);

                    if ($nonZeroOnly && !SweepMath::isPositive($available)) {
                        continue;
                    }

                    if (SweepMath::comp($available, $threshold, 18) < 0) {
                        continue;
                    }

                    $pairs[] = [
                        'vault_id' => $vaultId,
                        'asset_id' => $assetId,
                        'available' => $available,
                    ];
                }
            }
        }

        return $pairs;
    }

    public function vaultHasClientIntermediaryTag(
        string $vaultId,
        ?array $config = null,
        ?array $webhookDestinationTags = null
    ): bool {
        $tagId = $this->resolveTagId($config);
        if ($tagId === '') {
            return false;
        }

        if ($webhookDestinationTags !== null && $webhookDestinationTags !== []
            && $this->accountHasTag($webhookDestinationTags, $tagId)
        ) {
            return true;
        }

        // Webhook payloads often send tag names such as "client-intermediary".
        // Sweep matching uses only Client Intermediary Vault Tag ID from module config.
        try {
            $account = Fireblocks::vaults()->getAccount($vaultId);
            $tags = is_object($account)
                ? ($account->tags ?? [])
                : (is_array($account) ? ($account['tags'] ?? []) : []);

            return $this->accountHasTag(is_array($tags) ? $tags : [], $tagId);
        } catch (\Throwable $e) {
            Log::warning('Unable to verify Client Intermediary vault tag', [
                'vault_id' => $vaultId,
                'tag_id' => $tagId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function resolveTagId(?array $config = null): string
    {
        $config = $config ?? SweepConfigService::tryGet() ?? [];
        $raw = $config['client_intermediary_tag_id'] ?? '';

        if (is_array($raw)) {
            return trim((string) ($raw['value'] ?? ''));
        }

        return trim((string) $raw);
    }

    /**
     * @param  array<string, mixed>  $vaultAccount
     */
    public function extractAvailableBalance(array $vaultAccount, string $assetId): string
    {
        foreach ($vaultAccount['assets'] ?? [] as $asset) {
            $row = is_array($asset) ? $asset : (array) $asset;
            if ((string) ($row['id'] ?? '') !== $assetId) {
                continue;
            }

            return (string) ($row['available'] ?? '0');
        }

        return '0';
    }

    /**
     * @param  array<int, mixed>  $tags
     */
    public function accountHasTag(array $tags, string $tagId): bool
    {
        foreach ($tags as $tag) {
            if (in_array($tagId, $this->extractTagIdentifiers($tag), true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Collect Fireblocks tag UUIDs only — ignore display names such as client-intermediary.
     *
     * @param  mixed  $tag
     * @return list<string>
     */
    public function extractTagIdentifiers($tag): array
    {
        if (is_string($tag) || is_numeric($tag)) {
            $value = trim((string) $tag);

            return $this->looksLikeTagId($value) ? [$value] : [];
        }

        if (!is_array($tag) && !is_object($tag)) {
            return [];
        }

        $row = is_array($tag) ? $tag : (array) $tag;
        $ids = [];

        foreach (['id', 'tagId', 'tag_id'] as $key) {
            $value = trim((string) ($row[$key] ?? ''));
            if ($value !== '' && $this->looksLikeTagId($value)) {
                $ids[] = $value;
            }
        }

        return array_values(array_unique($ids));
    }

    protected function looksLikeTagId(string $value): bool
    {
        if ($value === '' || strcasecmp($value, 'client-intermediary') === 0) {
            return false;
        }

        return (bool) preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $value
        );
    }
}
