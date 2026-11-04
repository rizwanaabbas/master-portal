<?php

namespace Tests\Unit;

use App\Models\FireblocksTransaction;
use App\Modules\FireblocksTransactionsMonitoring\Services\TransactionRetryChainService;
use ReflectionClass;
use Tests\TestCase;

class TransactionRetryChainServiceTest extends TestCase
{
    private function buildTree(array $entries, string $rootId): ?array
    {
        $service = new TransactionRetryChainService();
        $method = (new ReflectionClass($service))->getMethod('buildTree');
        $method->setAccessible(true);

        return $method->invoke($service, $entries, $rootId);
    }

    private function entry(string $id, array $overrides = []): array
    {
        return array_merge([
            'fireblocks_tx_id' => $id,
            'status' => 'FAILED',
            'status_label' => 'Failed',
            'sub_status' => null,
            'substatus_message' => '',
            'substatus_category' => '',
            'internal_status' => 'Rejected',
            'internal_status_badge' => ['label' => 'Rejected', 'class' => 'badge-danger'],
            'monitoring_state' => 'normal',
            'monitoring_badge' => ['label' => 'Normal', 'class' => 'badge-success'],
            'parent_fireblocks_tx_id' => null,
            'replaced_by_fireblocks_tx_id' => null,
            'created_at' => '2026-08-26 10:00:00',
            'is_current' => false,
            'is_failed' => true,
        ], $overrides);
    }

    public function test_single_transaction_tree_is_leaf(): void
    {
        $tree = $this->buildTree([
            $this->entry('root-1', ['is_current' => true]),
        ], 'root-1');

        $this->assertSame('root-1', $tree['entry']['fireblocks_tx_id']);
        $this->assertTrue($tree['is_root']);
        $this->assertSame('leaf', $tree['branch_type']);
        $this->assertEmpty($tree['children']);
    }

    public function test_builds_linear_parent_child_tree(): void
    {
        $tree = $this->buildTree([
            $this->entry('root-1', ['created_at' => '2026-08-26 10:00:00', 'replaced_by_fireblocks_tx_id' => 'retry-1']),
            $this->entry('retry-1', ['parent_fireblocks_tx_id' => 'root-1', 'created_at' => '2026-08-26 11:00:00', 'status' => 'COMPLETED', 'is_failed' => false]),
        ], 'root-1');

        $this->assertSame('root-1', $tree['entry']['fireblocks_tx_id']);
        $this->assertTrue($tree['is_root']);
        $this->assertCount(1, $tree['children']);
        $this->assertSame('linear', $tree['branch_type']);
        $this->assertSame('retry-1', $tree['children'][0]['entry']['fireblocks_tx_id']);
        $this->assertSame('leaf', $tree['children'][0]['branch_type']);
    }

    public function test_builds_parallel_siblings_from_same_parent(): void
    {
        $tree = $this->buildTree([
            $this->entry('root-1', ['created_at' => '2026-08-26 10:00:00']),
            $this->entry('retry-a', ['parent_fireblocks_tx_id' => 'root-1', 'created_at' => '2026-08-26 11:00:00']),
            $this->entry('retry-b', ['parent_fireblocks_tx_id' => 'root-1', 'created_at' => '2026-08-26 11:05:00']),
        ], 'root-1');

        $this->assertSame('parallel', $tree['branch_type']);
        $this->assertCount(2, $tree['children']);
        $this->assertSame('retry-a', $tree['children'][0]['entry']['fireblocks_tx_id']);
        $this->assertSame('retry-b', $tree['children'][1]['entry']['fireblocks_tx_id']);
    }

    public function test_grid_badge_shows_chain_control_when_parent_linked(): void
    {
        $tx = new FireblocksTransaction([
            'fireblocks_tx_id' => 'child-tx-1',
            'parent_fireblocks_tx_id' => 'parent-tx-1',
            'root_fireblocks_tx_id' => 'root-tx-1',
        ]);

        $html = (new TransactionRetryChainService())->gridBadgeHtml($tx);

        $this->assertStringContainsString('btn-chain-view', $html);
        $this->assertStringContainsString('child-tx-1', $html);
    }

    public function test_nested_tree_with_parallel_and_linear_branches(): void
    {
        $tree = $this->buildTree([
            $this->entry('root-1', ['created_at' => '2026-08-26 10:00:00']),
            $this->entry('retry-1', ['parent_fireblocks_tx_id' => 'root-1', 'created_at' => '2026-08-26 11:00:00']),
            $this->entry('retry-2a', ['parent_fireblocks_tx_id' => 'retry-1', 'created_at' => '2026-08-26 12:00:00']),
            $this->entry('retry-2b', ['parent_fireblocks_tx_id' => 'retry-1', 'created_at' => '2026-08-26 12:05:00']),
        ], 'root-1');

        $this->assertCount(1, $tree['children']);
        $this->assertSame('retry-1', $tree['children'][0]['entry']['fireblocks_tx_id']);
        $this->assertSame('parallel', $tree['children'][0]['branch_type']);
        $this->assertCount(2, $tree['children'][0]['children']);
    }
}
