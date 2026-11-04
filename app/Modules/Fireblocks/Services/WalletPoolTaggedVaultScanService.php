<?php

namespace App\Modules\Fireblocks\Services;

use Fireblocks\Sdk\Facades\Fireblocks;
use Illuminate\Support\Facades\Log;

/**
 * Pages vault accounts attached to a Fireblocks Wallet Pool tag (module wallet_pool_id).
 */
class WalletPoolTaggedVaultScanService
{
    public const PAGE_LIMIT = 500;

    /**
     * @return \Generator<int, array{accounts: array<int, array<string, mixed>>, paging: array<string, mixed>}>
     */
    public function eachPoolMemberPage(string $walletPoolTagId, ?string $assetId = null): \Generator
    {
        $walletPoolTagId = trim($walletPoolTagId);
        if ($walletPoolTagId === '') {
            return;
        }

        $after = null;

        do {
            $params = [
                'includeTagIds' => $walletPoolTagId,
                'tagIds' => $walletPoolTagId,
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
                Log::error('Wallet pool vault scan failed', [
                    'wallet_pool_id' => $walletPoolTagId,
                    'asset_id' => $assetId,
                    'after' => $after,
                    'error' => $e->getMessage(),
                ]);

                break;
            }

            $page = is_array($page) ? $page : (array) $page;

            yield $page;

            $after = (string) (($page['paging']['after'] ?? null) ?? '');
        } while ($after !== '');
    }
}
