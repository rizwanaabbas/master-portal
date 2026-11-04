@php
    $lanes = $flowDiagram['lanes'] ?? [];
    $stats = $flowDiagram['stats'] ?? [];
    $toneClasses = [
        'blue' => 'tone-blue',
        'green' => 'tone-green',
        'amber' => 'tone-amber',
        'purple' => 'tone-purple',
        'slate' => 'tone-slate',
    ];
@endphp

<div class="sweep-rule-flow-wrap mb-4">
    <div class="sweep-rule-flow-header">
        <div>
            <h5 class="mb-1 font-weight-bold">Rule flow</h5>
            <p class="text-muted mb-0 small">Generated from this rule’s triggers, thresholds, gas settings, and destination.</p>
        </div>
        <div class="sweep-rule-flow-badges">
            @if ($rule->enabled)
                <span class="badge badge-success">Active</span>
            @else
                <span class="badge badge-secondary">Disabled</span>
            @endif
            @if ($rule->dry_run)
                <span class="badge badge-warning">Dry run</span>
            @endif
            @if ($rule->sweepsOrphanDeposits() && $rule->supportsTrigger('deposit'))
                <span class="badge badge-info">Orphan deposits</span>
            @endif
        </div>
    </div>

    <div class="sweep-rule-stat-grid mb-3">
        @foreach ($stats as $stat)
            @php $tone = $toneClasses[$stat['tone'] ?? 'slate'] ?? 'tone-slate'; @endphp
            <div class="sweep-rule-stat {{ $tone }}">
                <div class="sweep-rule-stat-label">{{ $stat['label'] }}</div>
                <div class="sweep-rule-stat-value">{{ $stat['value'] }}</div>
            </div>
        @endforeach
    </div>

    <div class="sweep-rule-lanes">
        @foreach ($lanes as $lane)
            @php $tone = $toneClasses[$lane['tone'] ?? 'slate'] ?? 'tone-slate'; @endphp
            <div class="sweep-rule-lane {{ $tone }}">
                <div class="sweep-rule-lane-head">
                    <span class="sweep-rule-lane-icon"><i class="fal {{ $lane['icon'] ?? 'fa-circle' }}"></i></span>
                    <span class="sweep-rule-lane-title">{{ $lane['title'] }}</span>
                </div>
                <div class="sweep-rule-lane-body">
                    @foreach ($lane['nodes'] as $node)
                        <div class="sweep-rule-lane-node">
                            <div class="sweep-rule-lane-node-label">{{ $node['label'] }}</div>
                            <div class="sweep-rule-lane-node-detail">{{ $node['detail'] }}</div>
                            @if (!empty($node['badge']))
                                <span class="sweep-rule-lane-badge">{{ $node['badge'] }}</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
            @if (!$loop->last)
                <div class="sweep-rule-lane-arrow" aria-hidden="true"><i class="fal fa-long-arrow-right"></i></div>
            @endif
        @endforeach
    </div>

    @if ($rule->supportsTrigger('scheduled') && (!empty($lastScheduledJob) || !empty($rule->schedule_last_run_at)))
        <div class="sweep-rule-schedule-footnote mt-3">
            <i class="fal fa-clock mr-1"></i>
            <strong>Last scheduled activity</strong>
            @if ($rule->schedule_last_run_at)
                · Run at {{ $rule->schedule_last_run_at->format('Y-m-d H:i:s') }}
            @endif
            @if (!empty($lastScheduledJob))
                · Job <code>#{{ $lastScheduledJob->id }}</code> status
                <span class="badge badge-{{ $lastScheduledJob->status === 'submitted' || $lastScheduledJob->status === 'completed' ? 'success' : ($lastScheduledJob->status === 'failed' ? 'danger' : 'secondary') }}">
                    {{ $lastScheduledJob->status }}
                </span>
                @if ($lastScheduledJob->fireblocks_status)
                    · Fireblocks {{ $lastScheduledJob->fireblocks_status }}
                    @if ($lastScheduledJob->fireblocks_sub_status)
                        / {{ $lastScheduledJob->fireblocks_sub_status }}
                    @endif
                @endif
            @endif
        </div>
    @endif
</div>
