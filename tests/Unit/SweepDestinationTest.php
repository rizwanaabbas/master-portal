<?php

namespace Tests\Unit;

use App\Modules\FireblocksVaultSweeping\Models\FireblocksSweepRule;
use App\Modules\FireblocksVaultSweeping\Support\SweepDestination;
use PHPUnit\Framework\TestCase;

class SweepDestinationTest extends TestCase
{
    public function test_rejects_empty_and_junk_vault_ids(): void
    {
        $this->assertTrue(SweepDestination::isPlaceholderVaultId(''));
        $this->assertTrue(SweepDestination::isPlaceholderVaultId('null'));
        $this->assertFalse(SweepDestination::isPlaceholderVaultId('0'));
        $this->assertFalse(SweepDestination::isPlaceholderVaultId('95'));
    }

    public function test_zero_is_valid_omnibus_vault_id(): void
    {
        $rule = new FireblocksSweepRule(['destination_vault_id' => '']);

        $this->assertSame('0', SweepDestination::resolveVaultId($rule, [
            'default_omnibus_vault_id' => '0',
        ]));
        $this->assertTrue(SweepDestination::isValidForSource('0', '137'));
    }

    public function test_resolve_uses_rule_destination_first(): void
    {
        $rule = new FireblocksSweepRule(['destination_vault_id' => '42']);

        $this->assertSame('42', SweepDestination::resolveVaultId($rule, [
            'default_omnibus_vault_id' => '99',
        ]));
    }
}
