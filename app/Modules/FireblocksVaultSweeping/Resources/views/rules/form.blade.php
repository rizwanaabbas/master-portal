@extends('layouts.cp')
@section('title', ($rule->exists ? 'Edit' : 'New') . ' Sweep Rule')

@section('styles')
@include('fireblocks-sweeping::partials.stylesheets', ['sheets' => [
    'sweep-common.css',
    'sweep-datagrid.css',
    'sweep-rule-form.css',
]])
@endsection

@section('content')
@php
    $moduleConfig = $moduleConfig ?? [];
    $defaultOmnibus = $moduleConfig['default_omnibus_vault_id'] ?? '';
    $defaultTronStation = $defaultTronStation ?? trim((string) ($moduleConfig['default_tron_station_vault_id'] ?? ''));
    $clientIntermediaryTagId = trim((string) ($moduleConfig['client_intermediary_tag_id'] ?? ''));
    $defaultDepositThreshold = $moduleConfig['default_deposit_threshold'] ?? '0';
    $defaultScheduledThreshold = $moduleConfig['default_scheduled_threshold'] ?? '0';
    $defaultScheduleInterval = max(1, (int) ($moduleConfig['scheduled_interval_minutes'] ?? 5));
    $globalDryRun = !empty($moduleConfig['dry_run_mode']);
    $destinationValue = old('destination_vault_id', $rule->destination_vault_id ?: $defaultOmnibus);
    $selectedSourceType = old('source_type', $rule->source_type);
    $triggerList = array_values(array_filter(array_map('trim', explode(',', strtolower(old('trigger_modes', $rule->trigger_modes ?? 'deposit,scheduled'))))));
    $triggerDeposit = in_array('deposit', $triggerList, true);
    $triggerScheduled = in_array('scheduled', $triggerList, true);
    $assetCatalogue = $assetCatalogue ?? [];
    $selectedSourceAsset = old('asset_id', $rule->sourceAssetId() ?? '');
    $selectedTargetAsset = old('target_asset_id', $rule->target_asset_id ?: $selectedSourceAsset);
    $gasFundingVault = old('gas_funding_vault_id', $rule->gas_funding_vault_id ?: $defaultTronStation);
    $feedTrxAmount = old('feed_trx_amount', $rule->feed_trx_amount ?? '');
    $useGasless = old('use_gasless', $rule->use_gasless ?? false);
    $treatAsGross = filter_var(old('treat_as_gross_amount', $rule->treat_as_gross_amount ?? false), FILTER_VALIDATE_BOOLEAN);
    $selectedFeeLevel = strtoupper(old('fee_level', $rule->fee_level ?? 'LOW'));
    if (!in_array($selectedFeeLevel, \App\Modules\FireblocksVaultSweeping\Models\FireblocksSweepRule::FEE_LEVELS, true)) {
        $selectedFeeLevel = 'LOW';
    }
    $selectedSweepAmountSource = old(
        'sweep_amount_source',
        $rule->sweep_amount_source ?? \App\Modules\FireblocksVaultSweeping\Models\FireblocksSweepRule::SWEEP_AMOUNT_SOURCE_AVAILABLE
    );
    $showAdvancedSource = in_array($selectedSourceType, ['vault_id_list', 'vault_name_prefix'], true)
        || old('show_advanced_source') === '1';
@endphp

<div class="subheader">
    <h1 class="subheader-title">
        <span class="fw-300">Vault Sweeping</span> <i class="fal fa-angle-right"></i>
        {{ $rule->exists ? 'Edit Rule' : 'New Rule' }}
    </h1>
</div>

@include('fireblocks-sweeping::partials.module-nav', ['sweepNavActive' => 'rules'])

<div class="sweep-page-intro mb-4">
    <div class="d-flex align-items-start gap-3">
        <div class="sweep-section-icon blue"><i class="fal fa-route"></i></div>
        <div class="flex-grow-1">
            <h5 class="mb-1 font-weight-bold">What this rule does</h5>
            <p class="text-muted mb-2">
                Moves crypto from <strong>tagged client deposit vaults</strong> into your <strong>Omnibus vault</strong> after deposits are approved.
                Client ledger balances are <em>not</em> changed — only Fireblocks custody balances move.
            </p>
            <div class="alert alert-light border mb-0 py-2 px-3">
                <small><i class="fal fa-info-circle text-primary mr-1"></i>
                    <strong>Thresholds use <code>available</code> balance</strong> (spendable now), not total/pending/locked.
                    Example: total 100 USDT but available 70 → a 50 threshold passes; available 30 → skipped.
                    We scan tagged vaults via Fireblocks API and compare <code>available</code> in our engine — we do <em>not</em> use Fireblocks <code>minAmountThreshold</code> (that filters on total balance).
                </small>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-3 col-sm-6 mb-3 mb-md-0">
        <div class="sweep-stat-card">
            <div class="label">Omnibus vault</div>
            <div class="value">{{ $defaultOmnibus !== '' ? $defaultOmnibus : 'Not configured' }}</div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 mb-3 mb-md-0">
        <div class="sweep-stat-card">
            <div class="label">Deposit threshold default</div>
            <div class="value">{{ $defaultDepositThreshold }}</div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 mb-3 mb-md-0">
        <div class="sweep-stat-card">
            <div class="label">Dust threshold default</div>
            <div class="value">{{ $defaultScheduledThreshold }}</div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="sweep-stat-card">
            <div class="label">CI vault tag</div>
            <div class="value"><small>{{ $clientIntermediaryTagId !== '' ? $clientIntermediaryTagId : 'Not configured' }}</small></div>
        </div>
    </div>
</div>

@include('common.error-message')

<form method="POST" action="{{ $rule->exists ? route('fireblocks.vault-sweeping.rules.update', $rule) : route('fireblocks.vault-sweeping.rules.store') }}" id="sweepRuleForm">
    @csrf
    @if ($rule->exists)
        @method('PUT')
    @endif

    {{-- Section 1: Basics --}}
    <div class="sweep-section">
        <div class="sweep-section-hdr">
            <span class="sweep-section-icon blue"><i class="fal fa-tag"></i></span>
            <div>
                <h5 class="mb-0 font-weight-bold">Rule identity</h5>
                <small class="text-muted">Name and activation status</small>
            </div>
        </div>
        <div class="sweep-section-body">
            <div class="row align-items-end">
                <div class="col-lg-8 mb-3 mb-lg-0">
                    <label class="sweep-field-label" for="name">
                        Rule name
                        @include('fireblocks-sweeping::rules.partials.field-hint', [
                            'fieldKey' => 'name',
                            'label' => 'Rule name',
                            'hint' => 'Ops-friendly label shown in the rules list. Include source asset and network for quick identification.',
                            'sample' => 'USDT TRC-20 → Omnibus',
                        ])
                    </label>
                    <input type="text" name="name" id="name" class="form-control form-control-lg" value="{{ old('name', $rule->name) }}" required
                        placeholder="e.g. USDT TRC-20 → Omnibus">
                </div>
                <div class="col-lg-4">
                    <div class="custom-control custom-switch custom-switch-lg">
                        <input type="checkbox" name="enabled" value="1" class="custom-control-input" id="enabled"
                            @if (old('enabled', $rule->enabled)) checked @endif>
                        <label class="custom-control-label font-weight-bold" for="enabled">
                            Rule active
                            @include('fireblocks-sweeping::rules.partials.field-hint', [
                                'fieldKey' => 'enabled',
                                'label' => 'Enabled',
                                'hint' => 'When off, the rule is ignored by deposit webhooks, cron, and manual run.',
                                'sample' => 'ON = active',
                            ])
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Section 2: Asset route --}}
    <div class="sweep-section">
        <div class="sweep-section-hdr">
            <span class="sweep-section-icon green"><i class="fal fa-exchange-alt"></i></span>
            <div>
                <h5 class="mb-0 font-weight-bold">Asset route</h5>
                <small class="text-muted">What moves from client vaults into Omnibus</small>
            </div>
        </div>
        <div class="sweep-section-body">
            @if ($assetCatalogue === [])
                <div class="alert alert-warning">
                    <strong>No Fireblocks assets found.</strong>
                    Sync assets first: <code>php artisan sync:fireblocks-assets</code>
                    (or enter asset IDs manually below).
                </div>
            @endif

            <div class="row mb-3">
                <div class="col-md-5 mb-3 mb-md-0">
                    <label class="sweep-field-label" for="asset_id">
                        Source asset
                        @include('fireblocks-sweeping::rules.partials.field-hint', [
                            'fieldKey' => 'source_asset',
                            'label' => 'Source asset',
                            'hint' => 'Fireblocks asset ID in client deposit vaults. Deposit webhooks match on this asset. One source asset per rule.',
                            'sample' => 'TRX_USDT_S2UZ or ETH',
                        ])
                    </label>
                    <select name="asset_id" id="asset_id" class="form-control sweep-asset-select w-100" required
                        data-placeholder="Search source asset…">
                        <option value=""></option>
                        @foreach ($assetCatalogue as $assetId => $meta)
                            <option value="{{ $assetId }}" @if ($selectedSourceAsset === $assetId) selected @endif>
                                {{ $assetId }} — {{ $meta['network_name'] ?? 'Unknown network' }}
                            </option>
                        @endforeach
                    </select>
                    <small class="form-text text-muted">Type to search by asset ID or network.</small>
                </div>

                <div class="col-md-2 d-none d-md-flex align-items-center justify-content-center pt-4">
                    <i class="fal fa-long-arrow-right text-muted fa-2x"></i>
                </div>

                <div class="col-md-5">
                    <label class="sweep-field-label" for="target_asset_id">
                        Target asset
                        @include('fireblocks-sweeping::rules.partials.field-hint', [
                            'fieldKey' => 'target_asset',
                            'label' => 'Target asset',
                            'hint' => 'Fireblocks asset ID received in the Omnibus vault. Auto-filled to match the source asset — change only if your Omnibus uses a different asset ID.',
                            'sample' => 'TRX_USDT_S2UZ (same as source)',
                        ])
                    </label>
                    <select name="target_asset_id" id="target_asset_id" class="form-control sweep-asset-select w-100"
                        data-placeholder="Auto-matched from source…">
                        <option value=""></option>
                        @foreach ($assetCatalogue as $assetId => $meta)
                            <option value="{{ $assetId }}" @if ($selectedTargetAsset === $assetId) selected @endif>
                                {{ $assetId }} — {{ $meta['network_name'] ?? 'Unknown network' }}
                            </option>
                        @endforeach
                    </select>
                    <small class="form-text text-muted"><i class="fal fa-magic mr-1"></i> Auto-selected when you pick a source asset.</small>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6 mb-3 mb-md-0">
                    <label class="sweep-field-label" for="sweep_amount_source">
                        Sweep amount basis
                        @include('fireblocks-sweeping::rules.partials.field-hint', [
                            'fieldKey' => 'sweep_amount_source',
                            'label' => 'Sweep amount basis',
                            'hint' => 'Controls how much can be swept from the source vault. Max spendable calls Fireblocks GET max_spendable_amount (fee-aware cap). Available balance uses vault available only — recommended for testnet ERC-20 where max spendable is unsupported.',
                            'sample' => 'Max spendable (Fireblocks API)',
                        ])
                    </label>
                    <select name="sweep_amount_source" id="sweep_amount_source" class="form-control">
                        <option value="available" @if ($selectedSweepAmountSource === 'available') selected @endif>
                            Available balance
                        </option>
                        <option value="max_spendable" @if ($selectedSweepAmountSource === 'max_spendable') selected @endif>
                            Max spendable (Fireblocks API)
                        </option>
                    </select>
                    <small class="form-text text-muted">
                        Threshold checks always use <code>available</code> balance. This setting only caps the transfer amount.
                    </small>
                </div>
                <div class="col-md-6">
                    <label class="sweep-field-label" for="destination_vault_id">
                        Destination Omnibus vault
                        @include('fireblocks-sweeping::rules.partials.field-hint', [
                            'fieldKey' => 'destination',
                            'label' => 'Destination vault',
                            'hint' => 'Fireblocks vault account ID where swept funds are consolidated. Leave blank to use the module default Omnibus vault.',
                            'sample' => $defaultOmnibus !== '' ? $defaultOmnibus : '42',
                        ])
                    </label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fal fa-vault"></i></span>
                        </div>
                        <input type="text" name="destination_vault_id" id="destination_vault_id" class="form-control"
                            value="{{ $destinationValue }}"
                            placeholder="{{ $defaultOmnibus !== '' ? 'Default: ' . $defaultOmnibus : 'Omnibus vault ID' }}">
                    </div>
                </div>
            </div>

            <div class="row mb-3 d-none" id="sweep-amount-source-hint">
                <div class="col-md-12">
                    <div class="alert alert-info py-2 px-3 mb-0">
                        <small><i class="fal fa-info-circle mr-1"></i>
                            <strong>Available balance</strong> skips the max spendable API and sweeps up to the vault's available balance (after reserve).
                            Use this when Fireblocks returns unsupported asset errors on testnet tokens.
                        </small>
                    </div>
                </div>
            </div>

            <div class="sweep-gas-fees-stack mb-3">
            <div id="tron-gas-panel" class="border rounded p-3 d-none" style="background: #fffbeb; border-color: #fcd34d !important;">
                <h6 class="font-weight-bold mb-2"><i class="fal fa-gas-pump text-warning mr-1"></i> TRON gas funding (TRC-20)</h6>
                <p class="text-muted small mb-3">
                    Before sweeping this token, the engine estimates TRX required, checks the client deposit vault balance,
                    and if needed transfers TRX from your <strong>TRON Station</strong>. The sweep waits for the gas feed webhook, then submits the token transfer.
                </p>
                <div class="row">
                    <div class="col-md-6 mb-3 mb-md-0">
                        <label class="sweep-field-label" for="gas_funding_vault_id">
                            TRON Station vault
                            @include('fireblocks-sweeping::rules.partials.field-hint', [
                                'fieldKey' => 'gas_funding_vault',
                                'label' => 'TRON Station vault',
                                'hint' => 'Fireblocks vault that holds TRX and funds client deposit vaults before TRC-20 sweeps. Leave blank to use module default TRON Station.',
                                'sample' => $defaultTronStation !== '' ? $defaultTronStation : '99',
                            ])
                        </label>
                        <input type="text" name="gas_funding_vault_id" id="gas_funding_vault_id" class="form-control"
                            value="{{ $gasFundingVault }}"
                            placeholder="{{ $defaultTronStation !== '' ? 'Default: ' . $defaultTronStation : 'TRON Station vault ID' }}">
                    </div>
                    <div class="col-md-6">
                        <label class="sweep-field-label" for="feed_trx_amount">
                            Target TRX in source vault (optional)
                            @include('fireblocks-sweeping::rules.partials.field-hint', [
                                'fieldKey' => 'feed_trx_amount',
                                'label' => 'Target TRX balance',
                                'hint' => 'Minimum TRX to maintain in the client deposit vault before sweeping. If blank, uses Fireblocks fee estimate + 15% buffer. Feed amount = target minus existing TRX.',
                                'sample' => '15',
                            ])
                        </label>
                        <input type="text" name="feed_trx_amount" id="feed_trx_amount" class="form-control"
                            value="{{ $feedTrxAmount }}"
                            placeholder="Auto from fee estimate">
                    </div>
                </div>
            </div>

            <div id="evm-gas-relay-panel" class="border rounded p-3 d-none" style="background: #eff6ff; border-color: #93c5fd !important;">
                <h6 class="font-weight-bold mb-2"><i class="fal fa-bolt text-primary mr-1"></i> Ethereum gas relay (Universal Gasless)</h6>
                <p class="text-muted small mb-3">
                    For ERC-20 USDC/USDT on Ethereum, Fireblocks can sponsor network fees via a gas relay
                    (<code>useGasless: true</code> on fee estimate and sweep). Source vaults do not need native
                    <code>ETH_TEST5</code> / <code>ETH</code> when relay is enabled.
                </p>
                <div class="custom-control custom-switch custom-switch-lg mb-3">
                    <input type="checkbox" name="use_gasless" value="1" class="custom-control-input" id="use_gasless"
                        @if ($useGasless) checked @endif>
                    <label class="custom-control-label font-weight-bold" for="use_gasless">
                        Use Fireblocks gas relay (useGasless)
                        @include('fireblocks-sweeping::rules.partials.field-hint', [
                            'fieldKey' => 'use_gasless',
                            'label' => 'Use gas relay',
                            'hint' => 'When enabled, estimate_fee and create transaction include useGasless:true. Fees are sponsored by your workspace gas relay — not deducted from native ETH on the client deposit vault.',
                            'sample' => 'ON for USDC_ETH_TEST5_0GER sweeps',
                        ])
                    </label>
                </div>
                <div id="gas-relay-status" class="sweep-gas-banner info d-none">
                    <span class="banner-icon"><i class="fal fa-spinner fa-spin"></i></span>
                    <div class="flex-grow-1">
                        <strong id="gas-relay-status-title">Checking Fireblocks gas relay…</strong>
                        <div id="gas-relay-status-message" class="small mt-1"></div>
                        <pre id="gas-relay-status-probe" class="small bg-white border rounded p-2 mt-2 mb-0 d-none" style="max-height: 160px; overflow: auto;"></pre>
                    </div>
                </div>
            </div>

            <div id="native-gross-panel" class="border rounded p-3 d-none mb-3" style="background: #f0fdf4; border-color: #86efac !important;">
                <h6 class="font-weight-bold mb-2"><i class="fal fa-coins text-success mr-1"></i> Native asset sweep (gross amount)</h6>
                <p class="text-muted small mb-3">
                    For native/base assets (e.g. <code>ETH_TEST5</code> on Sepolia, <code>TRX</code>), network fees are paid from the same asset.
                    Fireblocks <a href="https://developers.fireblocks.com/reference/create-transactions#treatasgrossamount" target="_blank" rel="noopener">treatAsGrossAmount</a>
                    deducts the fee from the requested sweep amount so you can sweep available balance without a separate gas reserve.
                </p>
                <div class="custom-control custom-switch custom-switch-lg mb-0">
                    <input type="checkbox" name="treat_as_gross_amount" value="1" class="custom-control-input" id="treat_as_gross_amount"
                        @if ($treatAsGross) checked @endif>
                    <label class="custom-control-label font-weight-bold" for="treat_as_gross_amount">
                        Treat sweep amount as gross (deduct network fee from amount)
                        @include('fireblocks-sweeping::rules.partials.field-hint', [
                            'fieldKey' => 'treat_as_gross_amount',
                            'label' => 'Treat as gross amount',
                            'hint' => 'Sets treatAsGrossAmount:true on POST /v1/transactions. Recommended for ETH Sepolia (ETH_TEST5) and other native account assets when sweeping full available balance.',
                            'sample' => 'ON for ETH_TEST5 deposit sweeps',
                        ])
                    </label>
                </div>
            </div>

            <div id="fee-level-panel" class="border rounded p-3" style="background: #f8fafc; border-color: #e2e8f0 !important;">
                <div class="row align-items-end">
                    <div class="col-md-6">
                        <label class="sweep-field-label" for="fee_level">
                            Fireblocks fee level
                            @include('fireblocks-sweeping::rules.partials.field-hint', [
                                'fieldKey' => 'fee_level',
                                'label' => 'Fee level',
                                'hint' => 'Sent as feeLevel on POST /v1/transactions and estimate_fee. LOW is recommended for sweeps (not time-sensitive). MEDIUM and HIGH pay higher network fees for faster confirmation.',
                                'sample' => 'LOW',
                            ])
                        </label>
                        <select name="fee_level" id="fee_level" class="form-control" required>
                            @foreach (\App\Modules\FireblocksVaultSweeping\Models\FireblocksSweepRule::FEE_LEVELS as $level)
                                <option value="{{ $level }}" @if ($selectedFeeLevel === $level) selected @endif>{{ $level }}</option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted mb-0">Maps to <code>feeLevel</code> in the Fireblocks transaction JSON (with gas relay when enabled).</small>
                    </div>
                </div>
            </div>
            </div>

            {{-- Live route preview --}}
            <div class="sweep-flow-track mb-3">
                <div class="sweep-flow-node">
                    <div class="node-title"><i class="fal fa-wallet mr-1"></i> Client deposit vault</div>
                    <div class="node-value" id="preview-source-asset">{{ $selectedSourceAsset ?: '—' }}</div>
                    <span class="sweep-asset-badge network d-none" id="preview-network-badge"></span>
                </div>
                <div class="sweep-flow-arrow"><i class="fal fa-arrow-right"></i></div>
                <div class="sweep-flow-node highlight">
                    <div class="node-title"><i class="fal fa-landmark mr-1"></i> Omnibus vault</div>
                    <div class="node-value" id="preview-dest-vault">{{ $destinationValue ?: ($defaultOmnibus ?: 'Module default') }}</div>
                    <div class="node-value text-primary mt-1" id="preview-target-asset">{{ $selectedTargetAsset ?: '—' }}</div>
                </div>
            </div>

            <div id="asset-gas-alert" class="sweep-gas-banner warning d-none mb-3"></div>

            <div class="border rounded p-3 bg-light">
                <h6 class="font-weight-bold mb-2"><i class="fal fa-list-ol mr-1"></i> How this asset sweeps</h6>
                <p id="asset-flow-intro" class="text-muted mb-2">Select a source asset above to see gas requirements and the step-by-step logic.</p>
                <ol class="sweep-steps" id="asset-flow-steps"></ol>
            </div>
        </div>
    </div>

    {{-- Section 3: Triggers --}}
    <div class="sweep-section">
        <div class="sweep-section-hdr">
            <span class="sweep-section-icon amber"><i class="fal fa-bolt"></i></span>
            <div>
                <h5 class="mb-0 font-weight-bold">When to sweep</h5>
                <small class="text-muted">Choose deposit webhook and/or scheduled dust cleanup</small>
            </div>
        </div>
        <div class="sweep-section-body">
            <div class="row">
                <div class="col-lg-6 mb-3 mb-lg-0">
                    <div class="sweep-trigger-card @if ($triggerDeposit) active @endif" id="triggerDepositCard">
                        <div class="trigger-head">
                            <span class="trigger-icon"><i class="fal fa-inbox-in"></i></span>
                            <div class="flex-grow-1">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" name="trigger_deposit" value="1" class="custom-control-input" id="trigger_deposit"
                                        @if ($triggerDeposit) checked @endif>
                                    <label class="custom-control-label font-weight-bold" for="trigger_deposit">
                                        After deposit (webhook)
                                        @include('fireblocks-sweeping::rules.partials.field-hint', [
                                            'fieldKey' => 'trigger_deposit',
                                            'label' => 'Deposit webhook trigger',
                                            'hint' => 'Runs after a deposit is Approved and the client ledger is credited — not on intermediate Fireblocks statuses.',
                                            'sample' => 'Checked',
                                        ])
                                    </label>
                                </div>
                                <small class="text-muted d-block mt-1">Fires when ledger credit completes (Approved), or for orphan deposits when enabled below.</small>
                            </div>
                        </div>
                        <label class="sweep-field-label" for="min_balance">
                            Deposit threshold
                            @include('fireblocks-sweeping::rules.partials.field-hint', [
                                'fieldKey' => 'min_balance',
                                'label' => 'Deposit threshold',
                                'hint' => 'Minimum available balance (spendable now) before a deposit-triggered sweep runs. 0 uses module default. Not compared to total/pending/locked.',
                                'sample' => '500 USDT',
                            ])
                        </label>
                        <input type="text" name="min_balance" id="min_balance" class="form-control"
                            value="{{ old('min_balance', $rule->min_balance) }}"
                            placeholder="Default: {{ $defaultDepositThreshold }}">
                        <label class="sweep-field-label mt-3" for="deposit_delay_minutes">Deposit delay (minutes)</label>
                        <input type="number" name="deposit_delay_minutes" id="deposit_delay_minutes" class="form-control"
                            value="{{ old('deposit_delay_minutes', $rule->deposit_delay_minutes) }}" min="0" max="1440"
                            placeholder="0 = immediate">
                        <div class="custom-control custom-checkbox mt-3">
                            <input type="checkbox" name="sweep_orphan_deposits" value="1" class="custom-control-input" id="sweep_orphan_deposits"
                                @if (filter_var(old('sweep_orphan_deposits', $rule->sweep_orphan_deposits), FILTER_VALIDATE_BOOLEAN)) checked @endif>
                            <label class="custom-control-label" for="sweep_orphan_deposits">
                                Sweep orphan deposits
                                @include('fireblocks-sweeping::rules.partials.field-hint', [
                                    'fieldKey' => 'sweep_orphan_deposits',
                                    'label' => 'Sweep orphan deposits',
                                    'hint' => 'When enabled, deposit-triggered sweeps run if the deposit address is not linked to any client ledger account. No AccountTransaction or client balance credit is created — only custody logging and vault sweep.',
                                    'sample' => 'Checked for unregistered deposit addresses',
                                ])
                            </label>
                        </div>
                        <small class="text-muted d-block mt-1">Use when funds arrive at a vault address that was never registered in the ledger.</small>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="sweep-trigger-card @if ($triggerScheduled) active @endif" id="triggerScheduledCard">
                        <div class="trigger-head">
                            <span class="trigger-icon"><i class="fal fa-clock"></i></span>
                            <div class="flex-grow-1">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" name="trigger_scheduled" value="1" class="custom-control-input" id="trigger_scheduled"
                                        @if ($triggerScheduled) checked @endif>
                                    <label class="custom-control-label font-weight-bold" for="trigger_scheduled">
                                        Scheduled dust cleanup
                                        @include('fireblocks-sweeping::rules.partials.field-hint', [
                                            'fieldKey' => 'trigger_scheduled',
                                            'label' => 'Scheduled trigger',
                                            'hint' => 'Periodic cron job that clears leftover balances using the lower dust threshold.',
                                            'sample' => 'Checked',
                                        ])
                                    </label>
                                </div>
                                <small class="text-muted d-block mt-1">Cron + manual Run — clears vault leftovers.</small>
                            </div>
                        </div>
                        <label class="sweep-field-label" for="scheduled_min_balance">
                            Dust threshold
                            @include('fireblocks-sweeping::rules.partials.field-hint', [
                                'fieldKey' => 'scheduled_min_balance',
                                'label' => 'Dust threshold',
                                'hint' => 'Minimum available balance for scheduled/manual dust cleanup. Usually 0 to sweep all residue. Uses available only — not Fireblocks minAmountThreshold.',
                                'sample' => '0',
                            ])
                        </label>
                        <input type="text" name="scheduled_min_balance" id="scheduled_min_balance" class="form-control"
                            value="{{ old('scheduled_min_balance', $rule->scheduled_min_balance ?? 0) }}"
                            placeholder="Default: {{ $defaultScheduledThreshold }} (usually 0)">

                        <label class="sweep-field-label mt-3" for="priority">
                            Run priority
                            @include('fireblocks-sweeping::rules.partials.field-hint', [
                                'fieldKey' => 'priority',
                                'label' => 'Run priority',
                                'hint' => 'Sequence when multiple rules run in the same scheduled pass. Lower number runs first (1 before 100). Drag-and-drop on the rules list also updates this value.',
                                'sample' => '10',
                            ])
                        </label>
                        <input type="number" name="priority" id="priority" class="form-control"
                            value="{{ old('priority', $rule->priority ?? 100) }}" min="1" max="99999" required>

                        <div class="border-top mt-3 pt-3" id="scheduleTimingFields" @if (!$triggerScheduled) style="display:none" @endif>
                            <h6 class="font-weight-bold mb-2"><i class="fal fa-sort-numeric-down mr-1"></i> Schedule order & timing</h6>
                            <p class="text-muted small mb-3">
                                Cron scans every {{ $defaultScheduleInterval ?? 5 }} min (module setting). This rule runs on its own interval or daily time when due.
                            </p>
                            <div class="row">
                                <div class="col-md-6 mb-3 mb-md-0">
                                    <label class="sweep-field-label" for="schedule_interval_minutes">
                                        Run every (minutes)
                                        @include('fireblocks-sweeping::rules.partials.field-hint', [
                                            'fieldKey' => 'schedule_interval_minutes',
                                            'label' => 'Schedule interval',
                                            'hint' => 'How often this rule runs on cron. Leave empty to use the module default scan interval. Ignored when a daily run time is set.',
                                            'sample' => '60',
                                        ])
                                    </label>
                                    <input type="number" name="schedule_interval_minutes" id="schedule_interval_minutes" class="form-control"
                                        value="{{ old('schedule_interval_minutes', $rule->schedule_interval_minutes) }}"
                                        min="1" max="525600"
                                        placeholder="Module default: {{ $defaultScheduleInterval ?? 5 }}"
                                        @if (old('schedule_run_at', $rule->schedule_run_at)) disabled @endif>
                                </div>
                                <div class="col-md-6">
                                    <label class="sweep-field-label" for="schedule_run_at">
                                        Daily run at (optional)
                                        @include('fireblocks-sweeping::rules.partials.field-hint', [
                                            'fieldKey' => 'schedule_run_at',
                                            'label' => 'Daily run time',
                                            'hint' => 'When set, this rule runs once per day at this time (server timezone) instead of the interval above.',
                                            'sample' => '03:00',
                                        ])
                                    </label>
                                    <input type="time" name="schedule_run_at" id="schedule_run_at" class="form-control"
                                        value="{{ old('schedule_run_at', $rule->schedule_run_at) }}">
                                </div>
                            </div>
                            @if ($rule->schedule_last_run_at)
                                <small class="text-muted d-block mt-1">
                                    Last scheduled run: {{ $rule->schedule_last_run_at->format('Y-m-d H:i:s') }}
                                </small>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Section 4: Safety --}}
    <div class="sweep-section">
        <div class="sweep-section-hdr">
            <span class="sweep-section-icon purple"><i class="fal fa-shield-check"></i></span>
            <div>
                <h5 class="mb-0 font-weight-bold">Safety</h5>
                <small class="text-muted">Test without moving funds on-chain</small>
            </div>
        </div>
        <div class="sweep-section-body">
            <div class="custom-control custom-checkbox">
                <input type="checkbox" name="dry_run" value="1" class="custom-control-input" id="dry_run"
                    @if (old('dry_run', $rule->dry_run)) checked @endif>
                <label class="custom-control-label" for="dry_run">
                    <strong>Rule dry run</strong> — evaluate and log only, no Fireblocks transaction
                    @include('fireblocks-sweeping::rules.partials.field-hint', [
                        'fieldKey' => 'dry_run',
                        'label' => 'Rule dry run',
                        'hint' => 'Evaluates thresholds and logs sweep jobs without creating a Fireblocks transaction.',
                        'sample' => 'Unchecked for live sweeps',
                    ])
                </label>
            </div>
        </div>
        <div class="sweep-form-footer">
            <a href="{{ route('fireblocks.vault-sweeping.rules.index') }}" class="btn btn-outline-secondary">Cancel</a>
            <div class="sweep-form-footer-actions">
                <button type="submit" class="btn btn-primary btn-lg px-4">
                    <i class="fal fa-save mr-1"></i> Save Rule
                </button>
            </div>
        </div>
    </div>

    {{-- Advanced --}}
    <div class="text-center mb-4">
        <a class="btn btn-sm btn-link text-muted" data-toggle="collapse" href="#advancedSource" role="button"
            aria-expanded="{{ $showAdvancedSource ? 'true' : 'false' }}">
            <i class="fal fa-sliders-h mr-1"></i> Advanced source scope
        </a>
    </div>

    <div class="collapse @if ($showAdvancedSource) show @endif mb-4" id="advancedSource">
        <div class="sweep-section">
            <div class="sweep-section-hdr">
                <span class="sweep-section-icon slate"><i class="fal fa-filter"></i></span>
                <div>
                    <h5 class="mb-0 font-weight-bold">Advanced source scope</h5>
                    <small class="text-muted">Limit which client deposit vaults this rule applies to</small>
                </div>
            </div>
            <div class="sweep-section-body">
                <input type="hidden" name="show_advanced_source" value="{{ $showAdvancedSource ? '1' : '0' }}" id="show_advanced_source">
                <div class="row">
                    <div class="col-md-4 mb-3 mb-md-0">
                        <label class="sweep-field-label" for="source_type">Source scope</label>
                        <select name="source_type" id="source_type" class="form-control">
                            <option value="client_deposit_vaults" @if ($selectedSourceType === 'client_deposit_vaults') selected @endif>All client deposit vaults</option>
                            <option value="vault_id_list" @if ($selectedSourceType === 'vault_id_list') selected @endif>Specific vault IDs</option>
                            <option value="vault_name_prefix" @if ($selectedSourceType === 'vault_name_prefix') selected @endif>Vault name prefix</option>
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label class="sweep-field-label" for="source_vault_ids">Vault IDs or prefix</label>
                        <input type="text" name="source_vault_ids" id="source_vault_ids" class="form-control"
                            value="{{ old('source_vault_ids', $rule->source_vault_ids) }}"
                            placeholder="12,34,56 or Client-">
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

@if ($rule->exists)
    @include('fireblocks-sweeping::rules.partials.run-history', [
        'runHistorySummary' => $runHistorySummary ?? null,
        'runHistoryService' => $runHistoryService ?? null,
        'sweepingActive' => $sweepingActive ?? \App\Modules\FireblocksVaultSweeping\Services\SweepConfigService::isActive(),
        'manualRunPreflight' => $manualRunPreflight ?? null,
    ])
@endif

<div class="modal fade" id="sweepFieldHelpModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title font-weight-bold" id="sweepFieldHelpModalLabel">Field help</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body pt-2" id="sweepFieldHelpModalBody"></div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
@include('fireblocks-sweeping::partials.confirm-modal')
@include('fireblocks-sweeping::partials.datagrid-init')
@if ($rule->exists)
@include('fireblocks-sweeping::rules.partials.run-history-grid-script', [
    'rule' => $rule,
    'runHistorySummary' => $runHistorySummary ?? null,
    'runHistoryService' => $runHistoryService ?? null,
])
@endif
<script>
    window.sweepAssetCatalogue = @json($assetCatalogue);
    window.sweepDefaultOmnibus = @json($defaultOmnibus ?: 'Module default');
    window.sweepGasRelayStatusUrl = @json(route('fireblocks.vault-sweeping.rules.gas-relay-status'));

    $(document).ready(function () {
        var $sourceSelect = $('#asset_id');
        var $targetSelect = $('#target_asset_id');
        var $gasAlert = $('#asset-gas-alert');
        var $flowIntro = $('#asset-flow-intro');
        var $flowSteps = $('#asset-flow-steps');
        var $gasRelayPanel = $('#evm-gas-relay-panel');
        var $nativeGrossPanel = $('#native-gross-panel');
        var $useGasless = $('#use_gasless');
        var $treatAsGross = $('#treat_as_gross_amount');
        var gasRelayProbeRequest = null;
        var userToggledGasless = {{ $rule->exists && $rule->use_gasless ? 'true' : 'false' }};
        var userToggledTreatGross = {{ $rule->exists && $rule->treat_as_gross_amount ? 'true' : 'false' }};
        var catalogue = window.sweepAssetCatalogue || {};
        var targetLockedToSource = true;

        function initSelect2() {
            $('.sweep-asset-select').each(function () {
                var $el = $(this);
                if ($el.hasClass('select2-hidden-accessible')) {
                    $el.select2('destroy');
                }
                $el.select2({
                    width: '100%',
                    allowClear: true,
                    placeholder: $el.data('placeholder') || 'Select asset…',
                });
            });
        }

        function getSelectedMeta() {
            var assetId = $sourceSelect.val();
            if (!assetId || !catalogue[assetId]) {
                return null;
            }
            return catalogue[assetId];
        }

        function syncTargetToSource(meta, force) {
            if (!meta) {
                return;
            }
            var targetId = meta.default_target_asset_id || meta.asset_id || '';
            if (!targetId) {
                return;
            }
            if (force || targetLockedToSource) {
                $targetSelect.val(targetId).trigger('change');
            }
        }

        function updateRoutePreview(meta) {
            var sourceId = $sourceSelect.val() || '—';
            var targetId = $targetSelect.val() || sourceId || '—';
            var destVault = $('#destination_vault_id').val().trim() || window.sweepDefaultOmnibus;

            $('#preview-source-asset').text(sourceId);
            $('#preview-target-asset').text(targetId);
            $('#preview-dest-vault').text(destVault);

            var $networkBadge = $('#preview-network-badge');
            if (meta && meta.network_name) {
                $networkBadge.removeClass('d-none').html('<i class="fal fa-globe"></i> ' + meta.network_name);
            } else {
                $networkBadge.addClass('d-none').text('');
            }
        }

        function renderAssetPanel(meta) {
            $gasAlert.addClass('d-none').removeClass('warning info').empty();
            var isTronGas = meta && meta.gas_required && !meta.supports_gas_relay;
            $('#tron-gas-panel').toggleClass('d-none', !isTronGas);

            var supportsRelay = !!(meta && meta.supports_gas_relay);
            $gasRelayPanel.toggleClass('d-none', !supportsRelay);

            var isNativeAccount = !!(meta && meta.type === 'account');
            $nativeGrossPanel.toggleClass('d-none', !isNativeAccount);
            if (isNativeAccount && !userToggledTreatGross && !$treatAsGross.prop('checked')) {
                $treatAsGross.prop('checked', true);
            }

            if (supportsRelay && !userToggledGasless) {
                $useGasless.prop('checked', true);
            }

            if (supportsRelay) {
                fetchGasRelayStatus(meta.asset_id);
            } else {
                $('#gas-relay-status').addClass('d-none');
            }

            if (!meta) {
                $flowIntro.removeClass('d-none').text('Select a source asset above to see gas requirements and the step-by-step logic.');
                $flowSteps.empty();
                updateRoutePreview(null);
                return;
            }

            if (meta.gas_required && meta.gas_asset_id && !meta.supports_gas_relay) {
                $gasAlert.removeClass('d-none').addClass('warning')
                    .html('<span class="banner-icon"><i class="fal fa-gas-pump"></i></span><div><strong>Gas required: ' + meta.gas_asset_id + '</strong><br>' + (meta.gas_note || '') + '</div>');
            } else if (meta.supports_gas_relay) {
                $gasAlert.removeClass('d-none').addClass('info')
                    .html('<span class="banner-icon"><i class="fal fa-bolt"></i></span><div><strong>Gas relay eligible</strong><br>' + (meta.gas_note || '') + '</div>');
            } else if (meta.gas_note) {
                $gasAlert.removeClass('d-none').addClass('info')
                    .html('<span class="banner-icon"><i class="fal fa-info-circle"></i></span><div>' + meta.gas_note + '</div>');
            }

            $flowIntro.addClass('d-none');
            $flowSteps.empty();
            (meta.sweep_flow || []).forEach(function (step) {
                $flowSteps.append($('<li></li>').text(step));
            });

            updateRoutePreview(meta);
        }

        function fetchGasRelayStatus(assetId) {
            if (!window.sweepGasRelayStatusUrl || !assetId) {
                return;
            }

            if (gasRelayProbeRequest) {
                gasRelayProbeRequest.abort();
            }

            var $status = $('#gas-relay-status');
            var $title = $('#gas-relay-status-title');
            var $message = $('#gas-relay-status-message');
            var $probe = $('#gas-relay-status-probe');

            $status.removeClass('d-none warning info').addClass('info');
            $title.text('Checking Fireblocks gas relay…');
            $message.text('Calling POST /v1/transactions/estimate_fee with useGasless:true');
            $probe.addClass('d-none').text('');

            gasRelayProbeRequest = $.getJSON(window.sweepGasRelayStatusUrl, {
                asset_id: assetId,
                destination_vault_id: $('#destination_vault_id').val() || ''
            }).done(function (data) {
                var status = data.status || 'unknown';
                $status.removeClass('warning info');

                if (status === 'enabled') {
                    $status.addClass('info');
                    $title.html('<i class="fal fa-check-circle text-success mr-1"></i> Gas relay: <strong>enabled</strong>');
                } else if (status === 'disabled') {
                    $status.addClass('warning');
                    $title.html('<i class="fal fa-times-circle text-danger mr-1"></i> Gas relay: <strong>disabled or rejected</strong>');
                } else if (status === 'not_applicable') {
                    $status.addClass('info');
                    $title.text('Gas relay not applicable for this asset');
                } else {
                    $status.addClass('warning');
                    $title.html('<i class="fal fa-question-circle mr-1"></i> Gas relay status: <strong>' + status + '</strong>');
                }

                $message.text(data.message || '');

                if (data.probe && data.probe.payload) {
                    $probe.removeClass('d-none').text(JSON.stringify(data.probe.payload, null, 2));
                }

                if (status === 'enabled' && !userToggledGasless) {
                    $useGasless.prop('checked', true);
                }
            }).fail(function (xhr) {
                $status.removeClass('info').addClass('warning');
                $title.text('Could not check gas relay status');
                $message.text((xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Fireblocks API request failed');
            });
        }

        function onSourceAssetChange(forceSync) {
            var meta = getSelectedMeta();
            syncTargetToSource(meta, forceSync === true);
            renderAssetPanel(meta);
        }

        function toggleTriggerCard(checkboxId, cardId) {
            $(cardId).toggleClass('active', $(checkboxId).is(':checked'));
        }

        initSelect2();

        $sourceSelect.on('change', function () {
            onSourceAssetChange(true);
        });

        $targetSelect.on('change', function () {
            var sourceId = $sourceSelect.val();
            var targetId = $(this).val();
            targetLockedToSource = !targetId || targetId === sourceId;
            updateRoutePreview(getSelectedMeta());
        });

        $('#destination_vault_id').on('input', function () {
            updateRoutePreview(getSelectedMeta());
            var meta = getSelectedMeta();
            if (meta && meta.supports_gas_relay) {
                fetchGasRelayStatus(meta.asset_id);
            }
        });

        $useGasless.on('change', function () {
            userToggledGasless = true;
        });

        $treatAsGross.on('change', function () {
            userToggledTreatGross = true;
        });

        $('#trigger_deposit').on('change', function () {
            toggleTriggerCard('#trigger_deposit', '#triggerDepositCard');
        });
        $('#trigger_scheduled').on('change', function () {
            toggleTriggerCard('#trigger_scheduled', '#triggerScheduledCard');
            toggleScheduleFields();
        });

        function toggleScheduleFields() {
            var scheduledOn = $('#trigger_scheduled').is(':checked');
            $('#scheduleTimingFields').toggle(scheduledOn);
            toggleDailyScheduleMode();
        }

        function toggleDailyScheduleMode() {
            var hasDailyTime = ($('#schedule_run_at').val() || '').trim() !== '';
            $('#schedule_interval_minutes').prop('disabled', hasDailyTime);
        }

        $('#schedule_run_at').on('change input', toggleDailyScheduleMode);
        toggleScheduleFields();

        function toggleSweepAmountSourceHint() {
            $('#sweep-amount-source-hint').toggleClass('d-none', $('#sweep_amount_source').val() !== 'available');
        }

        $('#sweep_amount_source').on('change', toggleSweepAmountSourceHint);
        toggleSweepAmountSourceHint();

        $('#advancedSource').on('show.bs.collapse', function () {
            $('#show_advanced_source').val('1');
        });

        $(document).on('click', '.sweep-field-help-btn', function (e) {
            e.preventDefault();
            var hintId = $(this).data('hint-id');
            var label = $(this).data('field-label') || 'Field help';
            var html = $('#' + hintId).html() || '<p class="text-muted mb-0">No help available.</p>';
            $('#sweepFieldHelpModalLabel').text(label);
            $('#sweepFieldHelpModalBody').html(html);
            $('#sweepFieldHelpModal').modal('show');
        });

        if ($sourceSelect.val()) {
            var initialSource = $sourceSelect.val();
            var initialTarget = $targetSelect.val();
            targetLockedToSource = !initialTarget || initialTarget === initialSource;
            onSourceAssetChange(false);
        }
    });
</script>
@endsection
