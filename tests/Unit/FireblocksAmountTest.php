<?php

namespace Tests\Unit;

use App\Models\FireblocksTransaction;
use App\Modules\Fireblocks\Support\FireblocksAmount;
use PHPUnit\Framework\TestCase;

class FireblocksAmountTest extends TestCase
{
    public function test_outgoing_uses_requested_amount_when_top_level_amount_is_zero(): void
    {
        $tx = new FireblocksTransaction([
            'amount' => 0,
            'asset_id' => 'ETH',
            'response_data' => [
                'amount' => 0,
                'amountInfo' => [
                    'amount' => '0',
                    'requestedAmount' => '1.25',
                    'netAmount' => '1.249',
                ],
            ],
        ]);

        $this->assertSame('1.25', FireblocksAmount::fromRecord($tx));
        $this->assertStringContainsString('1.25', FireblocksAmount::formatPlain($tx));
        $this->assertStringContainsString('ETH', FireblocksAmount::formatPlain($tx));
        $this->assertStringContainsString('ETH', FireblocksAmount::formatHtml($tx));
    }

    public function test_incoming_prefers_stored_positive_amount(): void
    {
        $tx = new FireblocksTransaction([
            'amount' => '0.5',
            'asset_id' => 'BTC',
            'response_data' => [
                'amountInfo' => ['requestedAmount' => '9'],
            ],
        ]);

        $this->assertSame('0.5', FireblocksAmount::fromRecord($tx));
    }

    public function test_webhook_bootstrap_amount_info(): void
    {
        $tx = new FireblocksTransaction([
            'amount' => 0,
            'asset_id' => 'USDT_BSC',
            'response_data' => [
                'webhook_bootstrap' => [
                    'amountInfo' => ['requestedAmount' => '100'],
                ],
            ],
        ]);

        $this->assertSame('100', FireblocksAmount::fromRecord($tx));
    }

    public function test_does_not_overwrite_positive_amount_with_zero_payload(): void
    {
        $tx = new FireblocksTransaction([
            'amount' => '2',
            'asset_id' => 'ETH',
        ]);

        FireblocksAmount::applyToRecord($tx, ['amount' => 0, 'amountInfo' => ['amount' => '0']]);

        $this->assertEqualsWithDelta(2.0, (float) $tx->amount, 0.00000001);
    }

    public function test_fills_zero_row_from_payload(): void
    {
        $tx = new FireblocksTransaction([
            'amount' => 0,
            'asset_id' => '',
        ]);

        FireblocksAmount::applyToRecord($tx, [
            'assetId' => 'SOL',
            'amountInfo' => ['requestedAmount' => '3.5'],
        ]);

        $this->assertEqualsWithDelta(3.5, (float) $tx->amount, 0.00000001);
        $this->assertSame('SOL', $tx->asset_id);
    }

    public function test_outgoing_html_includes_asset_when_only_payload_has_it(): void
    {
        $tx = new FireblocksTransaction([
            'amount' => '19',
            'asset_id' => '',
            'response_data' => [
                'assetId' => 'USDT_ERC20',
            ],
        ]);

        $html = FireblocksAmount::formatHtml($tx);
        $this->assertStringContainsString('19', $html);
        $this->assertStringContainsString('USDT_ERC20', $html);
        $this->assertStringContainsString('Tx:', $html);
        $this->assertStringContainsString('Now:', $html);
    }

    public function test_tx_usd_from_amount_info(): void
    {
        $tx = new FireblocksTransaction([
            'amount' => '19',
            'asset_id' => 'USDT_ERC20',
            'response_data' => [
                'amountInfo' => ['amountUSD' => '19.02'],
            ],
        ]);

        $this->assertEqualsWithDelta(19.02, FireblocksAmount::usdAtTxFromRecord($tx), 0.001);
    }

    public function test_small_usd_values_keep_full_decimals_in_html(): void
    {
        $tx = new FireblocksTransaction([
            'amount' => '0.000121',
            'asset_id' => 'USDT_ERC20',
            'amount_usd' => 0.000121,
            'response_data' => [
                'amountInfo' => ['amountUSD' => '0.000121'],
            ],
        ]);

        $html = FireblocksAmount::formatHtml($tx);
        $this->assertStringContainsString('0.000121', $html);
        $this->assertStringContainsString('$0.000121 USD', $html);
        $this->assertStringNotContainsString('$0.00 USD', $html);
    }

    public function test_tron_usdt_asset_uses_stable_now_usd(): void
    {
        $tx = new FireblocksTransaction([
            'amount' => '19',
            'asset_id' => 'TRX_USDT_S2UZ',
        ]);

        $this->assertTrue(\App\Modules\Fireblocks\Support\CoinGeckoUsdPriceService::isUsdStableAsset('TRX_USDT_S2UZ'));
        $this->assertSame('tether', \App\Modules\Fireblocks\Support\CoinGeckoUsdPriceService::geckoIdForAsset('TRX_USDT_S2UZ'));
        $this->assertEqualsWithDelta(19.0, FireblocksAmount::usdNowFromRecord($tx), 0.01);
    }
}
