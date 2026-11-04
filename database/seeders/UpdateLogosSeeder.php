<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class UpdateLogosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Path to your JSON file
        $filePath = storage_path('fireblock_assets.json');

        if (!File::exists($filePath)) {
            $this->command->error("File not found at: {$filePath}");
            return;
        }

        $jsonPayload = File::get($filePath);
        $data = json_decode($jsonPayload, true);

        if (!isset($data['data']) || !is_array($data['data'])) {
            $this->command->error("Invalid or empty JSON structure in: {$filePath}");
            return;
        }

        $assetsUpdated = 0;
        $networksUpdated = 0;

        foreach ($data['data'] as $item) {
            $fireblocksId = $item['id'] ?? null;
            $legacyId     = $item['legacyId'] ?? null;
            $assetClass   = $item['assetClass'] ?? null;
            $symbol       = $item['displaySymbol'] ?? null;
            $contractAddr = $item['onchain']['address'] ?? null;
            $mediaList    = $item['metadata']['media'] ?? [];

            // Extract logo URL (Prefers PNG, falls back to SVG/first available)
            $logoUrl = $this->extractLogoUrl($mediaList);

            if (!$logoUrl) {
                continue;
            }

            // 1. UPDATE ASSETS TABLE
            $assetQuery = DB::table('assets')
                ->where('asset_id', $legacyId);

            // if ($contractAddr) {
            //     $assetQuery->orWhere(function ($q) use ($contractAddr) {
            //         $q->whereNotNull('contract_address')
            //           ->where('contract_address', $contractAddr);
            //     });
            // }

            $assetsUpdated += $assetQuery->update([
                'logo_url'   => $logoUrl,
                'updated_at' => now(),
            ]);

            // 2. UPDATE NETWORKS TABLE (for NATIVE tokens)
            if ($assetClass === 'NATIVE') {
                $networkQuery = DB::table('networks')
                    ->where('code', $symbol);

                if ($legacyId) {
                    $networkQuery->orWhere('code', $legacyId);
                }

                $networksUpdated += $networkQuery->update([
                    'logo_url'   => $logoUrl,
                    'updated_at' => now(),
                ]);
            }
        }

        $this->command->info("Seeding Completed Successfully:");
        $this->command->info("- Assets logos updated: {$assetsUpdated}");
        $this->command->info("- Network logos updated: {$networksUpdated}");
    }

    /**
     * Extract PNG image URL if available, otherwise return first image URL.
     */
    private function extractLogoUrl(array $media): ?string
    {
        if (empty($media)) {
            return null;
        }

        foreach ($media as $item) {
            if (isset($item['type']) && $item['type'] === 'image/png') {
                return $item['url'];
            }
        }

        return $media[0]['url'] ?? null;

        // TODO: Upload S3
    }
}