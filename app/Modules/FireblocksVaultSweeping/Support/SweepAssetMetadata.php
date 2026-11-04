<?php

namespace App\Modules\FireblocksVaultSweeping\Support;

use App\Modules\Fireblocks\Models\Asset;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SweepAssetMetadata
{
    /**
     * Build asset catalogue keyed by Fireblocks asset ID for sweep rule UI.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function catalogue(): array
    {
        $assets = Asset::query()
            ->with('network')
            ->where(function ($query) {
                $query->whereIn('type', ['account', 'token', 'utxo'])
                    ->orWhereNull('type');
            })
            ->orderBy('asset_id')
            ->get();

        $catalogue = [];
        foreach ($assets as $asset) {
            $meta = self::describe($asset);
            $catalogue[(string) $asset->asset_id] = $meta;
        }

        if ($catalogue === []) {
            return self::catalogueFromFallbackSources();
        }

        return $catalogue;
    }

    /**
     * Build a minimal catalogue when the assets table is empty or not yet synced.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function catalogueFromFallbackSources(): array
    {
        $ids = collect();

        if (Schema::hasTable('crypto_addresses')) {
            $ids = $ids->merge(
                DB::table('crypto_addresses')
                    ->where('provider', 'fireblocks')
                    ->whereNotNull('asset_id')
                    ->where('asset_id', '!=', '')
                    ->distinct()
                    ->pluck('asset_id')
            );
        }

        if (Schema::hasTable('fireblocks_transactions')) {
            $ids = $ids->merge(
                DB::table('fireblocks_transactions')
                    ->whereNotNull('asset_id')
                    ->where('asset_id', '!=', '')
                    ->distinct()
                    ->pluck('asset_id')
            );
        }

        $catalogue = [];
        foreach ($ids->unique()->sort()->values() as $assetId) {
            $assetId = trim((string) $assetId);
            if ($assetId === '') {
                continue;
            }

            $catalogue[$assetId] = self::describe(null, $assetId);
        }

        return $catalogue;
    }

    /**
     * @return array<string, mixed>
     */
    public static function describe(?Asset $asset, ?string $assetId = null): array
    {
        $assetId = $asset !== null ? (string) $asset->asset_id : trim((string) $assetId);
        $networkCode = strtoupper((string) ($asset?->network?->code ?? ''));
        $networkName = (string) ($asset?->network?->name ?? self::guessNetworkName($assetId));
        $type = (string) ($asset?->type ?? self::guessType($assetId));
        $isTestnet = (bool) ($asset?->is_testnet ?? self::looksTestnet($assetId));
        $isTron = self::isTronNetwork($networkCode, $networkName, $assetId);
        $isEvmToken = $type === 'token' && self::isEvmNetwork($networkCode, $networkName, $assetId);
        $usesGasRelay = self::usesEvmGasRelay($assetId, $networkCode, $networkName, $isTron);
        $gas = self::resolveGasAsset($type, $isTron, $isEvmToken, $isTestnet, $assetId, $networkCode, $networkName, $usesGasRelay);

        return [
            'asset_id' => $assetId,
            'label' => $assetId,
            'network_code' => $networkCode,
            'network_name' => $networkName,
            'type' => $type,
            'is_testnet' => $isTestnet,
            'default_target_asset_id' => $assetId,
            'gas_asset_id' => $gas['asset_id'],
            'gas_required' => $gas['required'],
            'gas_funding_mode' => $gas['funding_mode'],
            'uses_gas_relay' => $usesGasRelay,
            'supports_gas_relay' => $usesGasRelay,
            'gas_note' => $gas['note'],
            'sweep_flow' => self::buildSweepFlow($assetId, $type, $isTron, $gas, $usesGasRelay),
        ];
    }

    public static function describeById(string $assetId): array
    {
        $asset = Asset::query()->with('network')->where('asset_id', $assetId)->first();

        return self::describe($asset, $assetId);
    }

    public static function isNativeAccountAsset(string $assetId): bool
    {
        $assetId = trim($assetId);
        if ($assetId === '') {
            return false;
        }

        return (self::describeById($assetId)['type'] ?? '') === 'account';
    }

    /**
     * Ethereum ERC-20 stablecoins (USDC/USDT) use Fireblocks Universal Gasless / gas relay —
     * fees are sponsored; source vault does not need native ETH for the sweep.
     */
    public static function usesEvmGasRelay(
        string $assetId,
        string $networkCode = '',
        string $networkName = '',
        ?bool $isTron = null
    ): bool {
        $upper = strtoupper(trim($assetId));
        if ($upper === '') {
            return false;
        }

        if (!str_contains($upper, 'USDC') && !str_contains($upper, 'USDT')) {
            return false;
        }

        $isTron ??= self::isTronNetwork($networkCode, $networkName, $assetId);
        if ($isTron) {
            return false;
        }

        if (str_contains($upper, 'BASECHAIN')) {
            return false;
        }

        if (self::isEthereumErc20Network($networkCode, $networkName, $assetId)) {
            return true;
        }

        $haystack = strtoupper($networkCode . ' ' . $networkName . ' ' . $assetId);

        return str_contains($haystack, 'ETH') || str_contains($haystack, 'ERC');
    }

    protected static function isEthereumErc20Network(string $networkCode, string $networkName, string $assetId): bool
    {
        $haystack = strtoupper(trim($networkCode . ' ' . $networkName));
        if ($haystack === '') {
            return false;
        }

        if (str_contains($haystack, 'BASECHAIN') || str_contains($haystack, 'TRON') || str_contains($haystack, 'TRC')) {
            return false;
        }

        return str_contains($haystack, 'ETH')
            || str_contains($haystack, 'ERC20')
            || str_contains($haystack, 'ERC-20')
            || str_contains($haystack, 'ETHEREUM');
    }

    /**
     * @return array{asset_id: ?string, required: bool, funding_mode: string, note: ?string}
     */
    protected static function resolveGasAsset(
        string $type,
        bool $isTron,
        bool $isEvmToken,
        bool $isTestnet,
        string $assetId,
        string $networkCode = '',
        string $networkName = '',
        bool $usesGasRelay = false
    ): array {
        if ($type === 'token' && $usesGasRelay) {
            $gasAssetId = self::inferNativeGasAssetId($networkCode, $networkName, $assetId, $isTestnet);

            return [
                'asset_id' => $gasAssetId,
                'required' => true,
                'funding_mode' => 'gas_relay',
                'note' => sprintf(
                    'ERC-20 %s sweep uses Fireblocks gas relay (useGasless). Source vault does not need %s — relay sponsors network fees.',
                    str_contains(strtoupper($assetId), 'USDC') ? 'USDC' : 'USDC/USDT',
                    $gasAssetId ?? 'native gas'
                ),
            ];
        }

        if ($type === 'token' && $isTron) {
            $trxAsset = $isTestnet ? 'TRX_TEST' : 'TRX';

            return [
                'asset_id' => $trxAsset,
                'required' => true,
                'funding_mode' => 'tron_station',
                'note' => 'TRC-20 sweeps need TRX in the source vault for bandwidth/energy. Ensure the client deposit vault holds enough TRX before sweeping tokens.',
            ];
        }

        if ($type === 'token') {
            $gasAssetId = self::inferNativeGasAssetId($networkCode, $networkName, $assetId, $isTestnet);
            if ($gasAssetId !== null) {
                return [
                    'asset_id' => $gasAssetId,
                    'required' => true,
                    'funding_mode' => 'native',
                    'note' => sprintf(
                        'Token sweeps require native gas asset %s in the source vault (fee currency for the transfer).',
                        $gasAssetId
                    ),
                ];
            }
        }

        if ($type === 'account' && $isTron) {
            return [
                'asset_id' => $assetId,
                'required' => false,
                'funding_mode' => 'none',
                'note' => 'Native TRX transfers pay gas from the TRX balance being swept.',
            ];
        }

        if ($type === 'account') {
            return [
                'asset_id' => $assetId,
                'required' => false,
                'funding_mode' => 'none',
                'note' => 'Native asset sweeps use the same asset for network fees (e.g. ETH pays ETH gas).',
            ];
        }

        return [
            'asset_id' => null,
            'required' => false,
            'funding_mode' => 'none',
            'note' => null,
        ];
    }

    protected static function inferNativeGasAssetId(
        string $networkCode,
        string $networkName,
        string $assetId,
        bool $isTestnet
    ): ?string {
        $networkCode = strtoupper(trim($networkCode));
        if ($networkCode !== '' && self::looksLikeNativeAssetId($networkCode)) {
            return $networkCode;
        }

        $haystack = strtoupper($networkCode . ' ' . $networkName . ' ' . $assetId);

        if (str_contains($haystack, 'BASECHAIN')) {
            return $isTestnet ? 'BASECHAIN_ETH_TEST5' : 'BASECHAIN_ETH';
        }

        if (str_contains($haystack, 'TRON') || str_contains($haystack, 'TRC') || str_starts_with(strtoupper($assetId), 'TRX_')) {
            return $isTestnet ? 'TRX_TEST' : 'TRX';
        }

        if (str_contains($haystack, 'BSC') || str_contains($haystack, 'BNB')) {
            return 'BNB_BSC';
        }

        if (str_contains($haystack, 'ETH') || str_contains($haystack, 'ERC')) {
            return $isTestnet ? 'ETH_TEST5' : 'ETH';
        }

        if (str_contains($haystack, 'AVAX')) {
            return $isTestnet ? 'AVAXTEST' : 'AVAX';
        }

        return null;
    }

    protected static function looksLikeNativeAssetId(string $code): bool
    {
        $upper = strtoupper(trim($code));
        if ($upper === '') {
            return false;
        }

        if (str_contains($upper, 'USDC') || str_contains($upper, 'USDT')) {
            return false;
        }

        return (bool) preg_match(
            '/^(ETH|TRX|BNB|AVAX|BASECHAIN|MATIC|POL|SOL|BTC)/',
            $upper
        );
    }

    /**
     * @param  array{asset_id: ?string, required: bool, funding_mode: string, note: ?string}  $gas
     * @return list<string>
     */
    protected static function buildSweepFlow(string $assetId, string $type, bool $isTron, array $gas, bool $usesGasRelay = false): array
    {
        $steps = [
            '1. Client deposit arrives in a Fireblocks client deposit vault (external → vault).',
            '2. Fireblocks webhook reaches COMPLETED with internal status Approved — client ledger is credited.',
            '3. Matching deposit rule is evaluated for this vault + source asset.',
            '4. Vault balance must meet the deposit threshold (after optional delay).',
            '5. Sweep transfers source asset from client deposit vault → Omnibus (target asset).',
        ];

        if ($type === 'token' && $usesGasRelay) {
            $steps[] = '6. Estimate sweep fee via Fireblocks gas relay (useGasless) — source vault does not need native ETH.';
            $steps[] = '7. Submit ERC-20 sweep with useGasless; relay sponsors network fees.';
        } elseif ($type === 'token' && $isTron && $gas['required']) {
            $steps[] = '6. Estimate TRX energy/bandwidth required for the token transfer.';
            $steps[] = '7. If source vault TRX balance is insufficient, fund TRX from TRON Station (configured on rule or module).';
            $steps[] = '8. Wait for gas feed webhook COMPLETED — job status: waiting_for_gas.';
            $steps[] = '9. Submit token sweep CI → Omnibus after TRX is confirmed in source vault.';
        } elseif ($type === 'token' && !$isTron && $gas['required']) {
            $steps[] = '6. Token note: native gas asset (' . ($gas['asset_id'] ?? 'native') . ') must be present in the source vault.';
        } elseif ($type === 'account') {
            $steps[] = '6. Native asset: enable “Treat as gross amount” on the rule so Fireblocks deducts network fee from the sweep amount (treatAsGrossAmount — e.g. ETH_TEST5 Sepolia).';
        }

        $steps[] = 'Ledger balances are not changed by sweeps — only custody vault balances move.';

        return $steps;
    }

    protected static function isTronNetwork(string $networkCode, string $networkName, string $assetId): bool
    {
        $haystack = strtoupper($networkCode . ' ' . $networkName . ' ' . $assetId);

        return str_contains($haystack, 'TRX')
            || str_contains($haystack, 'TRON')
            || str_contains($haystack, 'TRC');
    }

    protected static function isEvmNetwork(string $networkCode, string $networkName, string $assetId): bool
    {
        $haystack = strtoupper($networkCode . ' ' . $networkName . ' ' . $assetId);

        return str_contains($haystack, 'ETH')
            || str_contains($haystack, 'ERC20')
            || str_contains($haystack, 'EVM')
            || str_contains($haystack, 'BSC')
            || str_contains($haystack, 'BNB')
            || str_contains($haystack, 'POLYGON')
            || str_contains($haystack, 'MATIC')
            || str_contains($haystack, 'AVAX');
    }

    protected static function guessNetworkName(string $assetId): string
    {
        $upper = strtoupper($assetId);
        if (self::isTronNetwork('', '', $assetId)) {
            return 'Tron';
        }
        if (str_contains($upper, 'ETH') || str_contains($upper, 'ERC')) {
            return 'Ethereum';
        }
        if (str_contains($upper, 'BTC')) {
            return 'Bitcoin';
        }

        return 'Unknown';
    }

    protected static function guessType(string $assetId): string
    {
        $upper = strtoupper($assetId);
        if (str_contains($upper, 'USDT') || str_contains($upper, 'USDC') || str_contains($upper, 'ERC20')) {
            return 'token';
        }

        return 'account';
    }

    protected static function looksTestnet(string $assetId): bool
    {
        $upper = strtoupper($assetId);

        return str_contains($upper, 'TEST') || str_contains($upper, '_SEPOLIA') || str_contains($upper, '_GOERLI');
    }

    /**
     * @return Collection<int, string>
     */
    public static function assetIdChoices(): Collection
    {
        return Asset::query()
            ->where(function ($query) {
                $query->whereIn('type', ['account', 'token', 'utxo'])
                    ->orWhereNull('type');
            })
            ->orderBy('asset_id')
            ->pluck('asset_id')
            ->map(fn ($id) => (string) $id);
    }
}
