<?php

namespace Tests\Unit;

use App\Services\CryptoConfigService;
use Tests\TestCase;

class CryptoConfigServiceTest extends TestCase
{
    public function test_manual_and_advanced_enabled_when_bitpace_module_enabled(): void
    {
        if (!\App\Helpers\Common::isModuleEnabled('Bitpace')) {
            $this->markTestSkipped('Bitpace module is not enabled in this environment.');
        }

        CryptoConfigService::flush();

        $this->assertTrue(CryptoConfigService::isManualCryptoEnabled());
        $this->assertTrue(CryptoConfigService::isAdvancedCryptoEnabled());
    }
}
