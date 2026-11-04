<?php

namespace App\Modules\Fireblocks\Console\Commands;

use App\Modules\Fireblocks\Support\CoinGeckoUsdPriceService;
use Illuminate\Console\Command;

class RefreshCoinGeckoUsdPrices extends Command
{
    protected $signature = 'fireblocks:refresh-coingecko-prices';

    protected $description = 'Fetch CoinGecko USD spots and cache per coin for Transaction Monitoring Now USD';

    public function handle(): int
    {
        $result = CoinGeckoUsdPriceService::refreshCachedPrices();
        $this->info('Cached ' . $result['count'] . ' CoinGecko USD rate(s) for enabled crypto currencies: ' . implode(', ', $result['ids']));

        if ($result['count'] === 0) {
            $this->warn('No rates stored. Enable crypto currencies (is_active) that map to CoinGecko, then check HTTP/API key. USDT/USDC Now still falls back to $1.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
