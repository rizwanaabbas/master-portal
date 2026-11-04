@extends('layouts.cp')
@section('title', 'Rule #' . $rule->id . ' — ' . $rule->name)

@section('styles')
@include('fireblocks-sweeping::partials.stylesheets', ['sheets' => [
    'sweep-common.css',
    'sweep-datagrid.css',
    'sweep-rule-show.css',
]])
@endsection

@section('content')
<div class="subheader">
    <h1 class="subheader-title">
        <span class="fw-300">Vault Sweeping</span> <i class="fal fa-angle-right"></i>
        <a href="{{ route('fireblocks.vault-sweeping.rules.index') }}" class="text-muted">Rules</a>
        <i class="fal fa-angle-right"></i>
        Rule #{{ $rule->id }}
    </h1>
</div>

@include('fireblocks-sweeping::partials.module-nav', ['sweepNavActive' => 'rules'])

@include('common.error-message')
@if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif
@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if (session('info'))
    <div class="alert alert-info">{{ session('info') }}</div>
@endif

<div class="panel mb-3">
    <div class="panel-hdr">
        <h2>{{ $rule->name }}</h2>
    </div>
    <div class="panel-container show">
        <div class="panel-content">
            @include('fireblocks-sweeping::rules.partials.rule-flow-diagram', [
                'rule' => $rule,
                'flowDiagram' => $flowDiagram,
                'lastScheduledJob' => $lastScheduledJob ?? null,
            ])

            <div class="border-top pt-4 mt-2 sweep-rule-detail-actions">
                <h6 class="font-weight-bold mb-3">Actions</h6>
                @include('fireblocks-sweeping::rules.partials.rule-actions', [
                    'rule' => $rule,
                    'moduleConfig' => $moduleConfig,
                    'manualRunPreflight' => $manualRunPreflight,
                    'sweepingActive' => $sweepingActive,
                    'runRedirect' => 'show',
                    'btnSize' => 'lg',
                ])
            </div>
        </div>
    </div>
</div>

@include('fireblocks-sweeping::rules.partials.run-history', [
    'rule' => $rule,
    'runHistorySummary' => $runHistorySummary,
    'runHistoryService' => $runHistoryService,
    'moduleConfig' => $moduleConfig,
    'manualRunPreflight' => $manualRunPreflight,
    'sweepingActive' => $sweepingActive,
    'runRedirect' => 'show',
    'hideActionToolbar' => true,
])
@endsection

@section('scripts')
@include('fireblocks-sweeping::partials.confirm-modal')
@include('fireblocks-sweeping::partials.datagrid-init')
@include('fireblocks-sweeping::rules.partials.run-history-grid-script', [
    'rule' => $rule,
    'runHistorySummary' => $runHistorySummary,
    'runHistoryService' => $runHistoryService,
])
@endsection
