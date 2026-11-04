<?php

namespace Tests\Unit;

use App\Models\FireblocksTransaction;
use App\Modules\Fireblocks\Helpers\FireblocksStatusHelper;
use App\Modules\FireblocksTransactionsMonitoring\Services\TransactionActionEligibilityService;
use Tests\TestCase;

class TransactionActionEligibilityServiceTest extends TestCase
{
    private function service(): TransactionActionEligibilityService
    {
        return new TransactionActionEligibilityService();
    }

    private function config(): array
    {
        return [
            'retriable_substatuses' => ['FAIL_ON_LOW_FEE', 'REJECTED_BY_BLOCKCHAIN'],
            'non_retriable_substatuses' => ['INSUFFICIENT_FUNDS', 'BLOCKED_BY_POLICY'],
            'boost_eligible_primary_statuses' => [],
            'drop_eligible_primary_statuses' => [],
            'resubmit_eligible_primary_statuses' => [],
        ];
    }

    private function tx(array $attrs): FireblocksTransaction
    {
        return new FireblocksTransaction(array_merge([
            'fireblocks_tx_id' => 'tx-1',
            'asset_id' => 'ETH',
            'status' => 'CONFIRMING',
            'sub_status' => 'PENDING_BLOCKCHAIN_CONFIRMATIONS',
            'tx_hash' => '0xabc',
            'direction' => 'outgoing',
        ], $attrs));
    }

    public function test_boost_visible_for_confirming_with_hash(): void
    {
        $actions = $this->service()->availableActions($this->tx([]), $this->config());
        $this->assertTrue($actions['boost']['visible']);
        $this->assertTrue($actions['boost']['enabled']);
    }

    public function test_boost_visible_when_substatus_listed(): void
    {
        $config = $this->config();
        $config['boost_eligible_primary_statuses'] = ['FAIL_ON_LOW_FEE'];

        $actions = $this->service()->availableActions($this->tx([
            'status' => 'FAILED',
            'sub_status' => 'FAIL_ON_LOW_FEE',
        ]), $config);

        $this->assertTrue($actions['boost']['visible']);
    }

    public function test_boost_disabled_without_hash(): void
    {
        $actions = $this->service()->availableActions($this->tx(['tx_hash' => null]), $this->config());
        $this->assertTrue($actions['boost']['visible']);
        $this->assertFalse($actions['boost']['enabled']);
        $this->assertStringContainsString('hash', strtolower($actions['boost']['disabled_reason']));
    }

    public function test_boost_hidden_for_completed(): void
    {
        $actions = $this->service()->availableActions($this->tx(['status' => 'COMPLETED']), $this->config());
        $this->assertFalse($actions['boost']['visible']);
    }

    public function test_resubmit_visible_for_retriable_failed(): void
    {
        $actions = $this->service()->availableActions($this->tx([
            'status' => 'FAILED',
            'sub_status' => 'FAIL_ON_LOW_FEE',
        ]), $this->config());
        $this->assertTrue($actions['resubmit']['visible']);
    }

    public function test_resubmit_visible_when_only_substatus_configured(): void
    {
        $config = $this->config();
        $config['resubmit_eligible_primary_statuses'] = ['FAIL_ON_LOW_FEE'];

        $actions = $this->service()->availableActions($this->tx([
            'status' => 'FAILED',
            'sub_status' => 'FAIL_ON_LOW_FEE',
        ]), $config);

        $this->assertTrue($actions['resubmit']['visible']);
    }

    public function test_resubmit_visible_when_explicitly_listed_despite_non_retriable_default(): void
    {
        $config = $this->config();
        $config['resubmit_eligible_primary_statuses'] = ['FAILED', 'INSUFFICIENT_FUNDS'];

        $actions = $this->service()->availableActions($this->tx([
            'status' => 'FAILED',
            'sub_status' => 'INSUFFICIENT_FUNDS',
            'direction' => 'outgoing',
        ]), $config);

        $this->assertTrue($actions['resubmit']['visible'], $actions['resubmit']['hidden_reason'] ?? '');
        $this->assertTrue($actions['resubmit']['enabled']);
    }

    public function test_resubmit_hidden_for_non_retriable_substatus(): void
    {
        $actions = $this->service()->availableActions($this->tx([
            'status' => 'FAILED',
            'sub_status' => 'INSUFFICIENT_FUNDS',
        ]), $this->config());
        $this->assertFalse($actions['resubmit']['visible']);
    }

    public function test_resubmit_hidden_when_not_in_configured_list(): void
    {
        $config = $this->config();
        $config['resubmit_eligible_primary_statuses'] = ['REJECTED'];
        $config['retriable_substatuses'] = [];

        $actions = $this->service()->availableActions($this->tx([
            'status' => 'FAILED',
            'sub_status' => 'FAIL_ON_LOW_FEE',
        ]), $config);

        $this->assertFalse($actions['resubmit']['visible']);
    }

    public function test_boost_hidden_when_not_in_configured_list(): void
    {
        $config = $this->config();
        $config['boost_eligible_primary_statuses'] = ['BROADCASTING'];

        $actions = $this->service()->availableActions($this->tx([
            'status' => 'CONFIRMING',
            'tx_hash' => '0xabc',
        ]), $config);

        $this->assertFalse($actions['boost']['visible']);
    }

    public function test_resubmit_visible_via_retriable_fallback(): void
    {
        $config = $this->config();
        $config['resubmit_eligible_primary_statuses'] = ['REJECTED'];
        $config['retriable_substatuses'] = ['FAIL_ON_LOW_FEE'];

        $actions = $this->service()->availableActions($this->tx([
            'status' => 'FAILED',
            'sub_status' => 'FAIL_ON_LOW_FEE',
        ]), $config);

        $this->assertTrue($actions['resubmit']['visible']);
    }

    public function test_boost_and_drop_visible_but_disabled_for_insufficient_funds_without_hash(): void
    {
        $config = $this->config();
        $config['boost_eligible_primary_statuses'] = ['FAILED', 'INSUFFICIENT_FUNDS'];
        $config['drop_eligible_primary_statuses'] = ['FAILED', 'INSUFFICIENT_FUNDS'];

        $actions = $this->service()->availableActions($this->tx([
            'status' => 'FAILED',
            'sub_status' => 'INSUFFICIENT_FUNDS',
            'tx_hash' => null,
        ]), $config);

        $this->assertTrue($actions['boost']['visible'], $actions['boost']['hidden_reason'] ?? '');
        $this->assertFalse($actions['boost']['enabled']);
        $this->assertStringContainsString('Resubmit', $actions['boost']['disabled_reason']);

        $this->assertTrue($actions['drop']['visible'], $actions['drop']['hidden_reason'] ?? '');
        $this->assertFalse($actions['drop']['enabled']);
        $this->assertStringContainsString('Resubmit', $actions['drop']['disabled_reason']);
    }

    public function test_boost_matches_insufficient_funds_for_fee_when_config_lists_insufficient_funds(): void
    {
        $config = $this->config();
        $config['boost_eligible_primary_statuses'] = ['INSUFFICIENT_FUNDS'];

        $actions = $this->service()->availableActions($this->tx([
            'status' => 'FAILED',
            'sub_status' => 'INSUFFICIENT_FUNDS_FOR_FEE',
            'tx_hash' => '0xabc',
        ]), $config);

        $this->assertTrue($actions['boost']['visible'], $actions['boost']['hidden_reason'] ?? '');
        $this->assertTrue($actions['boost']['enabled']);
    }

    public function test_boost_hidden_when_only_substatus_configured_and_substatus_missing_on_tx(): void
    {
        $config = $this->config();
        $config['boost_eligible_primary_statuses'] = ['INSUFFICIENT_FUNDS'];

        $actions = $this->service()->availableActions($this->tx([
            'status' => 'FAILED',
            'sub_status' => null,
            'tx_hash' => '0xabc',
        ]), $config);

        $this->assertFalse($actions['boost']['visible']);
    }

    public function test_matches_action_eligible_list_accepts_primary_or_substatus(): void
    {
        $this->assertTrue(FireblocksStatusHelper::matchesActionEligibleList(
            'CONFIRMING',
            'PENDING_BLOCKCHAIN_CONFIRMATIONS',
            ['CONFIRMING']
        ));

        $this->assertTrue(FireblocksStatusHelper::matchesActionEligibleList(
            'FAILED',
            'FAIL_ON_LOW_FEE',
            ['FAIL_ON_LOW_FEE']
        ));

        $this->assertTrue(FireblocksStatusHelper::matchesActionEligibleList(
            'FAILED',
            'INSUFFICIENT_FUNDS_FOR_FEE',
            ['INSUFFICIENT_FUNDS']
        ));

        $this->assertFalse(FireblocksStatusHelper::matchesActionEligibleList(
            'FAILED',
            'INSUFFICIENT_FUNDS',
            ['FAIL_ON_LOW_FEE']
        ));
    }
}
