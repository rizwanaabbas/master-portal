@extends('layouts.cp')
@section('title', 'Visit detail - User Trace')

@section('styles')
    <style>
        .uj-visit-meta { display: grid; grid-template-columns: 140px 1fr; gap: .4rem .75rem; }
        .uj-visit-meta dt { color: #6c757d; }
        .uj-path { font-family: SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: .8rem; }
        .uj-chain { border-left: 2px solid #d6def0; margin-left: 8px; padding-left: 14px; }
        .uj-chain-item { margin-bottom: .85rem; }
        .uj-chain-item.active { background: #f6f1ff; border-radius: 6px; padding: .4rem .55rem; margin-left: -8px; padding-left: 8px; }
        .uj-flow { display: flex; align-items: center; gap: .5rem; padding: .45rem 0; border-bottom: 1px solid #f0f2f6; }
        .uj-chip { background: #f3f0fa; color: #5b3d8c; border-radius: 999px; padding: .15rem .65rem; }
        .uj-map { width: 100%; height: 280px; border: 0; border-radius: 8px; }
        .uj-dwell { width: 100%; font-size: .86rem; }
        .uj-dwell th { font-size: .72rem; text-transform: uppercase; letter-spacing: .04em; color: #6c757d; border-top: 0; }
        .uj-dwell td { vertical-align: top; }
        .uj-bar { height: 8px; background: #e8eaed; border-radius: 99px; overflow: hidden; margin-top: 6px; }
        .uj-bar i { display: block; height: 100%; background: #1a73e8; }
        .uj-diagram { display: flex; flex-wrap: wrap; align-items: stretch; gap: 0; margin: .5rem 0 1rem; }
        .uj-node { background: #fff; border: 1px solid #d6def0; border-radius: 10px; min-width: 140px; max-width: 200px; padding: .65rem .8rem; box-shadow: 0 1px 2px rgba(16,24,40,.06); }
        .uj-node.active { border-color: #886ab5; background: #f6f1ff; }
        .uj-node .uj-step { font-size: .68rem; text-transform: uppercase; letter-spacing: .04em; color: #6c757d; }
        .uj-node .uj-title { font-weight: 700; color: #2d3748; }
        .uj-node .uj-sub { font-size: .72rem; color: #718096; word-break: break-all; }
        .uj-connector { display: flex; align-items: center; padding: 0 .35rem; color: #886ab5; font-weight: 700; }
        .uj-hint { color: #6c757d; cursor: help; margin-left: 4px; }
        .uj-help { font-size: .8rem; color: #6c757d; margin: 0 0 .75rem; }
    </style>
@endsection

@section('content')
    @php
        $geoRow = $visit;
        if (empty($visit->city) && empty($visit->country) && !empty($chain)) {
            $geoRow = collect($chain)->first(function ($row) {
                return !empty($row->city) || !empty($row->country);
            }) ?: $visit;
        }
        $location = \App\Helpers\UserJourneyRecorder::displayLocation($geoRow);
        $lat = is_numeric(($visit->latitude ?? null) ?: ($geoRow->latitude ?? null)) ? (float) (($visit->latitude ?? null) ?: $geoRow->latitude) : null;
        $lng = is_numeric(($visit->longitude ?? null) ?: ($geoRow->longitude ?? null)) ? (float) (($visit->longitude ?? null) ?: $geoRow->longitude) : null;
        if ($lat && $lng) {
            $mapSrc = 'https://www.openstreetmap.org/export/embed.html?bbox='
                . ($lng - 0.08) . ',' . ($lat - 0.05) . ',' . ($lng + 0.08) . ',' . ($lat + 0.05)
                . '&layer=mapnik&marker=' . $lat . ',' . $lng;
        } elseif ($location !== '') {
            $mapSrc = 'https://maps.google.com/maps?q=' . urlencode($location) . '&z=6&output=embed';
        } else {
            $mapSrc = null;
        }
        $steps = $timeline['steps'] ?? [];
        $maxDwell = max(1, collect($steps)->max('dwell') ?: 1);
        $diagram = [];
        foreach ($steps as $step) {
            $path = \App\Helpers\UserJourneyAnalytics::cleanPath($step->path ?? '');
            $name = $step->name ?? \App\Helpers\UserJourneyAnalytics::pageName($path);
            $last = end($diagram);
            if ($last && $last['path'] === $path) {
                continue;
            }
            $diagram[] = ['name' => $name, 'path' => $path, 'id' => $step->id];
        }
    @endphp

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>User Trace</span>
        </h1>
        <a href="{{ url('/user-trace/user-journey-logs') }}" class="btn btn-sm btn-outline-secondary">Back to journey logs</a>
    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false"
        data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>{{ $visit->user_name ?: 'Guest' }} · {{ $location }} · {{ \App\Helpers\UserJourneyAnalytics::formatDuration((int) $sessionSeconds) }}</h2>
        </div>
        <div class="panel-container show mb-3">
            <div class="panel-content">
                <div class="row">
                    <div class="col-lg-5 mb-3">
                        <p class="uj-help">This page is only this person’s session — not every user. Location is city and country. Path is the screen they opened.
                            <i class="fal fa-question-circle uj-hint" title="Opening a visit from the All users grid still keeps this user and session only."></i></p>
                        <dl class="uj-visit-meta mb-3">
                            <dt>User</dt><dd>{{ $visit->user_name ?: 'Guest' }}</dd>
                            <dt>Email</dt><dd>{{ $visit->user_email ?: 'N/A' }}</dd>
                            <dt>IP</dt>
                            <dd class="uj-ip-plain" style="white-space:pre-line">{{ \App\Helpers\UserJourneyRecorder::formatIps($visit) }}
                                @if (!empty($visit->ip_forwarded))
                                    <div class="small text-muted">Seen on request: {{ $visit->ip_forwarded }}</div>
                                @endif
                            </dd>
                            <dt>Session</dt><dd class="uj-path">{{ $visit->session_id }}</dd>
                            <dt>Duration <i class="fal fa-question-circle uj-hint" title="Time from first to last event in this session."></i></dt><dd>{{ \App\Helpers\UserJourneyAnalytics::formatDuration((int) $sessionSeconds) }} ({{ $chain->count() }} events)</dd>
                            <dt>Path</dt><dd class="uj-path">{{ $visit->route_path }}</dd>
                            <dt>Event</dt><dd>{{ $visit->event_name ?: 'page_view' }}</dd>
                            <dt>Channel</dt>
                            <dd><i class="{{ $visit->channel_icon }}"></i> {{ $visit->channel_label }}</dd>
                            <dt>Device</dt>
                            <dd><i class="{{ $visit->os_icon }}"></i> {{ trim(($visit->os ?: '') . ' · ' . ($visit->device_name ?: $visit->device_type ?: '')) }} {{ $visit->browser ? '· '.$visit->browser : '' }}</dd>
                            <dt>Location</dt><dd>{{ $location }}</dd>
                            <dt>When</dt><dd>{{ \Carbon\Carbon::parse($visit->created_at)->format('d M, Y H:i:s') }}</dd>
                            <dt>User agent</dt><dd class="small">{{ $visit->user_agent }}</dd>
                        </dl>
                        @if ($mapSrc)
                            <iframe class="uj-map" src="{{ $mapSrc }}" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                        @else
                            <p class="text-muted mb-0">No location coordinates for this visit.</p>
                        @endif
                    </div>
                    <div class="col-lg-7 mb-3">
                        <h6>Time on each page <i class="fal fa-question-circle uj-hint" title="Clock time they opened the page, then how long until the next different page. Refreshes are ignored."></i></h6>
                        <p class="uj-help">Each row is a screen in this visit. The bar is time until they moved on. The last row has no duration because the session ended there.</p>
                        @if (count($steps) > 0)
                            <table class="table table-sm uj-dwell mb-4">
                                <thead>
                                    <tr>
                                        <th>Opened at</th>
                                        <th>Page</th>
                                        <th>Time on page</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($steps as $step)
                                        <tr class="@if ((int) $step->id === (int) $visit->id) table-active @endif">
                                            <td class="text-nowrap">{{ $step->at->format('H:i:s') }}</td>
                                            <td>
                                                <strong>{{ $step->name ?? \App\Helpers\UserJourneyAnalytics::pageName($step->path) }}</strong>
                                                <div class="uj-path text-muted">{{ $step->path }}</div>
                                                <div class="uj-bar"><i style="width: {{ !empty($step->last) ? 4 : max(8, round(($step->dwell / $maxDwell) * 100)) }}%"></i></div>
                                            </td>
                                            <td class="text-nowrap">
                                                @if (!empty($step->last))
                                                    Last page
                                                @else
                                                    {{ \App\Helpers\UserJourneyAnalytics::formatDuration((int) $step->dwell) }}
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <p class="text-muted">No page views in this session yet.</p>
                        @endif

                        <h6 class="mt-4">Session path flow <i class="fal fa-question-circle uj-hint" title="Pages this user opened, in order. Highlighted card is the visit you clicked."></i></h6>
                        @if (count($diagram) > 0)
                            <div class="uj-diagram">
                                @foreach ($diagram as $i => $node)
                                    <div class="uj-node @if ((int) $node['id'] === (int) $visit->id) active @endif">
                                        <div class="uj-step">Step {{ $i + 1 }}</div>
                                        <div class="uj-title">{{ $node['name'] }}</div>
                                        <div class="uj-sub">{{ $node['path'] }}</div>
                                    </div>
                                    @if (!$loop->last)
                                        <div class="uj-connector">→</div>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                        @forelse ($flows as $flow)
                            <div class="uj-flow">
                                <span class="uj-chip">{{ \App\Helpers\UserJourneyAnalytics::pageName($flow->from_path) }}
                                    <span class="uj-path d-block">{{ $flow->from_path }}</span></span>
                                <span>→</span>
                                <span class="uj-chip">{{ \App\Helpers\UserJourneyAnalytics::pageName($flow->to_path) }}
                                    <span class="uj-path d-block">{{ $flow->to_path }}</span></span>
                                <strong class="ml-auto">{{ $flow->hits }}</strong>
                            </div>
                        @empty
                            @if (count($steps) < 2)
                                <p class="text-muted">Not enough sequential views in this session to draw a flow.</p>
                            @endif
                        @endforelse

                        <h6 class="mt-4">Chained events (this session only) <i class="fal fa-question-circle uj-hint" title="Every event in the same session, oldest first. Other IPs or sessions are excluded."></i></h6>
                        <div class="uj-chain">
                            @foreach ($chain as $row)
                                <div class="uj-chain-item @if ((int) $row->id === (int) $visit->id) active @endif">
                                    <div class="small text-muted">{{ \Carbon\Carbon::parse($row->created_at)->format('d M, Y H:i:s') }} · {{ $row->event_name ?: 'page_view' }}</div>
                                    <div>
                                        <a href="{{ url('/user-trace/user-journey-visit/' . $row->id) }}">{{ \App\Helpers\UserJourneyAnalytics::pageName($row->route_path) }}</a>
                                        <div class="uj-path text-muted">{{ $row->route_path }}</div>
                                    </div>
                                    <div class="small text-muted">
                                        <i class="{{ $row->channel_icon }}"></i> {{ $row->channel_label }}
                                        · <i class="{{ $row->os_icon }}"></i> {{ $row->os }}
                                        · {{ $row->ip_address }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop
