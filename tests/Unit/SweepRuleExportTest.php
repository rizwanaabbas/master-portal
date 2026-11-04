<?php

namespace Tests\Unit;

use App\Modules\FireblocksVaultSweeping\Models\FireblocksSweepRule;
use App\Modules\FireblocksVaultSweeping\Support\SweepRuleExport;
use PHPUnit\Framework\TestCase;

class SweepRuleExportTest extends TestCase
{
    public function test_document_encodes_to_json_without_error(): void
    {
        $rule = new FireblocksSweepRule([
            'id' => 42,
            'name' => 'Test rule',
            'priority' => 10,
            'enabled' => true,
            'source_type' => 'client_deposit_vaults',
            'asset_ids' => 'ETH',
            'trigger_modes' => 'deposit,scheduled',
            'min_balance' => '0.01',
            'schedule_run_at' => '14:30',
            'treat_as_gross_amount' => false,
        ]);

        $document = SweepRuleExport::document($rule);

        $json = json_encode($document, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        $this->assertIsString($json);
        $this->assertSame(1, $document['export_version']);
        $this->assertSame('14:30', $document['rule']['schedule_run_at']);
    }
}
