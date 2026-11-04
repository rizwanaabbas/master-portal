<?php

namespace Tests\Unit;

use App\Modules\FireblocksVaultSweeping\Support\SweepModuleConfigDefinitions;
use PHPUnit\Framework\TestCase;

class SweepModuleConfigDefinitionsTest extends TestCase
{
    public function test_merge_into_preserves_existing_values(): void
    {
        $merged = SweepModuleConfigDefinitions::mergeInto([
            'sweeping_enabled' => ['value' => '1', 'type' => 'checkbox', 'label' => 'Old'],
        ]);

        $this->assertSame('1', $merged['sweeping_enabled']['value']);
        $this->assertArrayHasKey('default_scheduled_threshold', $merged);
        $this->assertArrayNotHasKey('notification_emails', $merged);
    }
}
