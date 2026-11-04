@extends('layouts.cp')
@section('title', 'User Journey Logs - User Trace')

@section('styles')
    <style>
        .uj-card { border: 1px solid #e6eaf0; border-radius: 8px; background: #fff; padding: 1rem 1.1rem; height: 100%; }
        .uj-kpi { font-size: 1.6rem; font-weight: 700; line-height: 1.1; }
        .uj-kicker { font-size: .72rem; text-transform: uppercase; letter-spacing: .06em; color: #6c757d; }
        .uj-heat { position: relative; height: 460px; background: #e9ecef; border-radius: 8px; overflow: hidden; border: 1px solid #ced4da; cursor: help; }
        .uj-dot { position: absolute; width: 18px; height: 18px; margin: -9px 0 0 -9px; border-radius: 50%; background: #dc3545; cursor: pointer; z-index: 2; }
        .uj-dot:hover { transform: scale(1.25); box-shadow: 0 0 0 4px rgba(220,53,69,.2); }
        .uj-tip { display: none; position: absolute; z-index: 5; min-width: 220px; max-width: 280px; background: #212529; color: #fff; border-radius: 8px; padding: .65rem .8rem; font-size: .8rem; box-shadow: 0 8px 20px rgba(0,0,0,.2); pointer-events: none; }
        .uj-tip strong { display: block; margin-bottom: .2rem; }
        .uj-flabel { display: block; font-size: .68rem; font-weight: 600; color: #6c757d; margin-bottom: 2px; }
        .uj-legend span { display: inline-block; width: 12px; height: 12px; border-radius: 50%; margin-right: 4px; vertical-align: middle; }
        .uj-hint { color: #6c757d; cursor: help; margin-left: 4px; }
        .uj-help { font-size: .8rem; color: #6c757d; margin: 0 0 .75rem; }
        .uj-tabs .nav-link { font-weight: 600; cursor: pointer; }
        .uj-path { font-family: SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: .8rem; }
        .uj-mc { max-width: 560px; margin: 0 auto; padding: .25rem 0 1rem; }
        .uj-mc-head { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 1rem; }
        .uj-mc-rate { font-size: 1.7rem; font-weight: 700; color: #1a73e8; line-height: 1; }
        .uj-mc-rate span { display: block; font-size: .72rem; font-weight: 600; color: #5f6368; text-transform: uppercase; letter-spacing: .04em; margin-top: 4px; }
        .uj-mc-stage { margin: 0 auto; color: #fff; text-align: center; padding: 16px 20px 20px; clip-path: polygon(3% 0, 97% 0, 93% 100%, 7% 100%); box-shadow: 0 8px 18px rgba(26,115,232,.12); }
        .uj-mc-stage .n { font-size: 1.55rem; font-weight: 700; line-height: 1.1; }
        .uj-mc-stage .l { font-size: .92rem; font-weight: 600; margin-top: 2px; }
        .uj-mc-stage .s { font-size: .72rem; opacity: .88; margin-top: 3px; }
        .uj-mc-drop { display: flex; justify-content: center; align-items: center; gap: 14px; padding: 8px 0 7px; font-size: .78rem; font-weight: 600; }
        .uj-mc-drop .ok { color: #137333; }
        .uj-mc-drop .lost { color: #c5221f; }
        .uj-mc-drop .line { width: 1px; height: 18px; background: #dadce0; }
        .uj-next { display: flex; align-items: center; gap: .75rem; padding: .65rem 0; border-bottom: 1px solid #f0f2f6; }
        .uj-next:last-child { border-bottom: 0; }
        .uj-next-bar { flex: 1; height: 8px; background: #e8eaed; border-radius: 99px; overflow: hidden; }
        .uj-next-bar i { display: block; height: 100%; background: #1a73e8; border-radius: 99px; }
        .uj-next-meta { min-width: 92px; text-align: right; font-size: .8rem; color: #3c4043; }
        .uj-next-name { width: 42%; font-weight: 600; color: #202124; font-size: .86rem; }
        .uj-channel { display: flex; align-items: flex-start; gap: .55rem; }
        .uj-channel i { width: 18px; text-align: center; margin-top: 2px; }
        .uj-channel .uj-sub { font-size: .75rem; color: #6c757d; }
        .uj-ip { color: #5b3d8c; font-weight: 600; cursor: pointer; }
        .uj-filters .form-control { min-width: 120px; }
        .uj-pane { min-height: 0; }
        .uj-pane.uj-chart { min-height: 120px; }
        .uj-filters { margin-bottom: .75rem; }
        a.uj-loc { color: #007bff; font-weight: 600; }
        .uj-ip-plain { user-select: all; }
        .uj-loading { color: #6c757d; padding: 2rem 0; text-align: center; }
        .uj-visit-meta { display: grid; grid-template-columns: 140px 1fr; gap: .35rem .75rem; font-size: .9rem; }
        .uj-visit-meta dt { color: #6c757d; }
        .uj-chain { border-left: 2px solid #d6def0; margin-left: 8px; padding-left: 14px; }
        .uj-chain-item { margin-bottom: .85rem; }
        .uj-chain-item.active { background: #f6f1ff; border-radius: 6px; padding: .4rem .55rem; margin-left: -8px; padding-left: 8px; }
    </style>
@endsection

@section('content')
    @php
        $baseUrl = url('/user-trace/user-journey-logs');
        $tabUrl = url('/user-trace/journey-tab');
    @endphp

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>User Trace</span>
        </h1>
    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false"
        data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>User Journey @if ($analyticsOn) Analytics @else Logs @endif</h2>
        </div>
        <div class="panel-container show mb-3">
            <div class="panel-content">
                @include('common.error-message')

                @if (!$analyticsOn)
                    <div class="alert alert-info">Enable the <strong>UserJourneyAnalytics</strong> module in Settings → Modules for maps, funnels, flows, and client-side admin logging.</div>
                @endif

                <form method="get" action="{{ $baseUrl }}" class="uj-filters" id="uj-global-filters">
                    <input type="hidden" name="tab" id="uj-filter-tab" value="{{ $tab }}">
                    <p class="uj-help mb-2">These filters apply to every tab: Event grid, Overview, Locations, Pages, Funnels, and Heat map.
                        <i class="fal fa-question-circle uj-hint" title="Set a date range or pick Last 7 / 28 / 90 days, then Apply. Switching tabs keeps the same filters."></i></p>
                    <div class="form-inline align-items-end">
                        <div class="mr-2 mb-2">
                            <label class="uj-flabel" for="uj-days">Period <i class="fal fa-question-circle uj-hint" title="7 = last week. 28 = last 4 weeks. 90 = last 3 months. All time = no date limit. Custom = use From/To."></i></label>
                            <select name="days" id="uj-days" class="form-control form-control-sm">
                                <option value="all" @if ((string) $request->get('days') === 'all') selected @endif>All time</option>
                                <option value="" @if ($request->get('days') === '' && $request->get('date_from')) selected @endif>Custom dates</option>
                                <option value="7" @if ((int) $days === 7 && $request->get('days') !== 'all') selected @endif>Last 7 days (1 week)</option>
                                <option value="28" @if ((int) $days === 28 && $request->get('days') !== 'all' && !$request->filled('date_from')) selected @endif>Last 28 days (4 weeks)</option>
                                <option value="90" @if ((int) $days === 90 && $request->get('days') !== 'all') selected @endif>Last 90 days (3 months)</option>
                            </select>
                        </div>
                        <div class="mr-2 mb-2">
                            <label class="uj-flabel" for="uj-date-from">From</label>
                            <input type="date" name="date_from" id="uj-date-from" value="{{ $filters['date_from'] ?? '' }}" class="form-control form-control-sm">
                        </div>
                        <div class="mr-2 mb-2">
                            <label class="uj-flabel" for="uj-date-to">To</label>
                            <input type="date" name="date_to" id="uj-date-to" value="{{ $filters['date_to'] ?? '' }}" class="form-control form-control-sm">
                        </div>
                        <div class="mr-2 mb-2">
                            <label class="uj-flabel">Channel</label>
                            <select name="channel" class="form-control form-control-sm">
                                <option value="">All channels</option>
                                @foreach (['portal_admin' => 'Admin portal', 'portal_client' => 'Client portal', 'mobile' => 'Mobile app'] as $value => $label)
                                    <option value="{{ $value }}" @if ($filters['channel'] === $value) selected @endif>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mr-2 mb-2">
                            <label class="uj-flabel">Event</label>
                            <select name="event_name" class="form-control form-control-sm">
                                <option value="">All events</option>
                                @foreach (['page_view' => 'Page view', 'click' => 'Click', 'api_view' => 'API / app screen'] as $event => $label)
                                    <option value="{{ $event }}" @if ($filters['event_name'] === $event) selected @endif>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mr-2 mb-2">
                            <label class="uj-flabel">OS</label>
                            <select name="os" class="form-control form-control-sm">
                                <option value="">All OS</option>
                                @foreach (['Windows', 'macOS', 'Linux', 'Android', 'iOS'] as $os)
                                    <option value="{{ $os }}" @if ($filters['os'] === $os) selected @endif>{{ $os }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mr-2 mb-2">
                            <label class="uj-flabel">User</label>
                            <input type="text" name="user" value="{{ $filters['user'] ?? '' }}" class="form-control form-control-sm" placeholder="All users">
                        </div>
                        <div class="mr-2 mb-2">
                            <label class="uj-flabel">Email</label>
                            <input type="text" name="email" value="{{ $filters['email'] ?? '' }}" class="form-control form-control-sm" placeholder="All emails">
                        </div>
                        <div class="mr-2 mb-2">
                            <label class="uj-flabel">Device</label>
                            <input type="text" name="device_name" value="{{ $filters['device_name'] }}" class="form-control form-control-sm" placeholder="All devices">
                        </div>
                        <div class="mr-2 mb-2">
                            <label class="uj-flabel">Country</label>
                            <input type="text" name="country" value="{{ $filters['country'] }}" class="form-control form-control-sm" placeholder="All countries" maxlength="2">
                        </div>
                        <div class="mr-2 mb-2">
                            <label class="uj-flabel">IP</label>
                            <input type="text" name="ip" value="{{ $filters['ip'] }}" class="form-control form-control-sm" placeholder="All IPs">
                        </div>
                        <div class="mr-2 mb-2">
                            <label class="uj-flabel">Path</label>
                            <input type="text" name="path" value="{{ $filters['path'] }}" class="form-control form-control-sm" placeholder="All paths">
                        </div>
                        <div class="mr-2 mb-2">
                            <button class="btn btn-sm btn-primary" type="submit">Apply</button>
                            <a class="btn btn-sm btn-outline-secondary" href="{{ $baseUrl }}?tab={{ $tab }}">Reset</a>
                        </div>
                    </div>
                </form>

                @if ($analyticsOn)
                    <ul class="nav nav-tabs uj-tabs mb-3" id="uj-tabs" role="tablist">
                        @foreach (['events' => 'Event grid', 'overview' => 'Overview', 'locations' => 'Locations', 'pages' => 'Pages', 'funnels' => 'Funnels & flows', 'heat' => 'Heat map'] as $key => $label)
                            <li class="nav-item">
                                <a class="nav-link @if ($tab === $key) active @endif" id="uj-tab-{{ $key }}" data-toggle="tab" href="#uj-pane-{{ $key }}" role="tab" data-tab="{{ $key }}"
                                    title="@switch($key)
                                        @case('events') Every logged page view, click, and API hit. @break
                                        @case('overview') Totals, trend, channels, and devices for the selected days. @break
                                        @case('locations') Where users were when events were recorded. @break
                                        @case('pages') Which screens were opened most. @break
                                        @case('funnels') How people move from Home to Clients, Cards, and Crypto. @break
                                        @case('heat') Where staff clicked on a page. Grey screen, red = more clicks. @break
                                    @endswitch">{{ $label }}</a>
                            </li>
                        @endforeach
                    </ul>
                @endif

                <div class="tab-content" id="uj-tab-content">
                    <div class="tab-pane fade uj-pane uj-chart @if ($tab === 'overview') show active @endif" id="uj-pane-overview" role="tabpanel">
                        <div class="uj-loading">Loading overview...</div>
                    </div>
                    <div class="tab-pane fade uj-pane uj-chart @if ($tab === 'locations') show active @endif" id="uj-pane-locations" role="tabpanel">
                        <div class="uj-loading">Loading locations...</div>
                    </div>
                    <div class="tab-pane fade uj-pane uj-chart @if ($tab === 'pages') show active @endif" id="uj-pane-pages" role="tabpanel">
                        <div class="uj-loading">Loading pages...</div>
                    </div>
                    <div class="tab-pane fade uj-pane uj-chart @if ($tab === 'funnels') show active @endif" id="uj-pane-funnels" role="tabpanel">
                        <div class="uj-loading">Loading funnels...</div>
                    </div>
                    <div class="tab-pane fade uj-pane uj-chart @if ($tab === 'heat') show active @endif" id="uj-pane-heat" role="tabpanel">
                        <div class="uj-loading">Loading heat map...</div>
                    </div>
                    <div class="tab-pane fade @if (!$analyticsOn || $tab === 'events') show active @endif" id="uj-pane-events" role="tabpanel">
            <div style="overflow: scroll;" id="uj-grid-wrap">
                    <p class="uj-help">Event grid lists each visit in the filters above. Click the <strong>IP address</strong> to open that visit. Location is resolved on the visit page (or on every event if enabled in module config).
                        <i class="fal fa-question-circle uj-hint" title="A session is one browser or app visit. Channel is Admin portal, Client portal, or Mobile app."></i></p>
                        <div class="d-flex align-items-center mb-3" id="uj-grid-toolbar">
                            <input type="search" id="uj-grid-search" class="form-control form-control-sm" style="max-width:280px"
                                placeholder="Search users, email, path, IP, location…"
                                value="{{ $request->get('search_string') }}">
                        </div>
                        <table class="table table-bordered table-hover m-0 shadow" id="data-grid" style="width:100%">
                            <thead class="thead-themed">
                                <tr>
                                    <th>User Name</th>
                                    <th>User Email</th>
                                    <th>Path</th>
                                    <th>Event</th>
                                    <th>Channel / device</th>
                                    <th>IP Address</th>
                                    <th>Timestamp</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if (count($userJourneyLogs->items()) > 0)
                                    @foreach ($userJourneyLogs as $log)
                                        @php
                                            $channel = \App\Helpers\UserJourneyRecorder::channelMeta($log->channel ?? null);
                                            $osIcon = \App\Helpers\UserJourneyRecorder::osIcon($log->os ?? null, $log->device_name ?? null);
                                            $deviceLabel = trim(($log->os ?: '') . ($log->device_name ? ' · ' . $log->device_name : ($log->device_type ? ' · ' . $log->device_type : '')));
                                            $path = $log->route_path;
                                            if (strpos($path, '?') !== false) {
                                                $path = substr($path, 0, strpos($path, '?'));
                                            }
                                        @endphp
                                        <tr>
                                            <td>{{ $log->user_name ? $log->user_name : 'Guest User' }}</td>
                                            <td>{{ $log->user_email ? $log->user_email : 'N/A' }}</td>
                                            <td class="uj-path">{{ $path }}</td>
                                            <td>{{ $log->event_name ?? 'page_view' }}</td>
                                            <td>
                                                <div class="uj-channel">
                                                    <i class="{{ $channel['icon'] }}" title="{{ $channel['label'] }}"></i>
                                                    <div>
                                                        <div>{{ $channel['label'] }}</div>
                                                        <div class="uj-sub"><i class="{{ $osIcon }}"></i> {{ $deviceLabel ?: 'Unknown device' }}@if ($log->browser) · {{ $log->browser }}@endif</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="uj-ip-plain" style="white-space:pre-line">
                                                <a class="uj-loc" href="{{ url('/user-trace/user-journey-visit/' . $log->id) }}">{{ \App\Helpers\UserJourneyRecorder::formatIps($log) }}</a>
                                            </td>
                                            <td>{{ \Carbon\Carbon::parse($log->created_at)->format('d M, Y H:i:s') }}</td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td></td><td></td><td></td><td></td>
                                        <td>No logs found.</td>
                                        <td></td><td></td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                        @if (strpos(Request::route()->uri, 'user-trace/user-journey-logs/search') !== false)
                            {{ $userJourneyLogs->links('global-partials.pagination-post-req', [
                                'perPage' => $userJourneyLogs->perPage(),
                                'currentPage' => $userJourneyLogs->currentPage(),
                                'total' => $userJourneyLogs->total(),
                                'hiddenInputs' => array_filter([
                                    'per_page' => $request['per_page'],
                                    'search_string' => $request['search_string'],
                                    'tab' => 'events',
                                    'days' => $days ?? 28,
                                    'channel' => $filters['channel'],
                                    'event_name' => $filters['event_name'],
                                    'os' => $filters['os'],
                                    'device_name' => $filters['device_name'],
                                    'country' => $filters['country'],
                                    'ip' => $filters['ip'],
                                    'path' => $filters['path'],
                                    'date_from' => $filters['date_from'] ?? '',
                                    'date_to' => $filters['date_to'] ?? '',
                                ]),
                            ]) }}
                        @else
                            {{ $userJourneyLogs->links('global-partials.pagination', [
                                'perPage' => $userJourneyLogs->perPage(),
                                'currentPage' => $userJourneyLogs->currentPage(),
                                'total' => $userJourneyLogs->total(),
                            ]) }}
                        @endif
            </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @php
        $search_string = isset($request) && isset($request['search_string']) ? $request['search_string'] : null;
    @endphp

    <form name="search" id="search" method="post" action="{{ url('/user-trace/user-journey-logs/search') }}"
        enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="per_page" id="per_page"
            value="{{ isset($request->per_page) ? $request->per_page : '' }}">
        <input type="hidden" name="search_string" id="search_string"
            value="{{ isset($request->search_string) ? $request->search_string : '' }}" />
        <input type="hidden" name="tab" value="events">
        <input type="hidden" name="days" value="{{ $days ?? 28 }}">
        <input type="hidden" name="channel" value="{{ $filters['channel'] }}">
        <input type="hidden" name="event_name" value="{{ $filters['event_name'] }}">
        <input type="hidden" name="os" value="{{ $filters['os'] }}">
        <input type="hidden" name="user" value="{{ $filters['user'] ?? '' }}">
        <input type="hidden" name="email" value="{{ $filters['email'] ?? '' }}">
        <input type="hidden" name="device_name" value="{{ $filters['device_name'] }}">
        <input type="hidden" name="country" value="{{ $filters['country'] }}">
        <input type="hidden" name="ip" value="{{ $filters['ip'] }}">
        <input type="hidden" name="path" value="{{ $filters['path'] }}">
        <input type="hidden" name="date_from" value="{{ $filters['date_from'] ?? '' }}">
        <input type="hidden" name="date_to" value="{{ $filters['date_to'] ?? '' }}">
    </form>
@stop

@section('scripts')
    <script>
        var hiddenInput = document.getElementById('search_string');
        var search_string = {!! json_encode(isset($search_string) ? (string) $search_string : '') !!};
        var perPage = {{ (int) ($request->get('per_page') ?: 10) }};
        var recordsCount = {{ (int) count($userJourneyLogs->items()) }};
        var ujDays = {{ (int) $days }};
        var ujTab = {!! json_encode((string) $tab) !!};
        var ujTabUrl = {!! json_encode(url('/user-trace/journey-tab')) !!};
        var ujAnalytics = {{ !empty($analyticsOn) ? 'true' : 'false' }};
        var ujLoaded = {};
        var ujChartsReady = false;

        if (document.getElementById('per_page')) {
            document.getElementById('per_page').value = perPage;
        }

        function hint(text) {
            return ' <i class="fal fa-question-circle uj-hint" title="' + escapeHtml(text) + '"></i>';
        }

        function escapeHtml(value) {
            return String(value == null ? '' : value)
                .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        function bindHeatTips(points, pageName) {
            var box = document.getElementById('uj-heat-box');
            var tip = document.getElementById('uj-heat-tip');
            if (!box || !tip) return;
            function zoneOf(x, y) {
                if (y < 12) return 'Header / top bar: logo, search, profile.';
                if (y >= 88) return 'Footer / bottom: pager or footer actions.';
                if (x < 18) return 'Left menu: portal navigation.';
                if (x >= 82) return 'Right side: extra panels or actions.';
                return 'Main content: tables, forms, and the work area.';
            }
            function showTip(html, ev) {
                var rect = box.getBoundingClientRect();
                tip.innerHTML = html;
                tip.style.display = 'block';
                var left = ev.clientX - rect.left + 12;
                var top = ev.clientY - rect.top + 12;
                if (left > rect.width - 240) left = rect.width - 240;
                if (top > rect.height - 120) top = rect.height - 120;
                tip.style.left = Math.max(8, left) + 'px';
                tip.style.top = Math.max(8, top) + 'px';
            }
            box.addEventListener('mousemove', function (ev) {
                var dot = ev.target.classList && ev.target.classList.contains('uj-dot') ? ev.target : null;
                if (!dot) return;
                var p = points[parseInt(dot.getAttribute('data-i'), 10)] || {};
                var x = Number(p.x || 0);
                var y = Number(p.y || 0);
                showTip(
                    '<strong>' + escapeHtml(p.page_name || pageName) + '</strong>' +
                    Number(p.hits || 0) + ' click' + (Number(p.hits) === 1 ? '' : 's') + ' here<br>' +
                    escapeHtml(p.zone || '') + '<br>' +
                    '<span style="opacity:.8">' + escapeHtml(p.zone_hint || zoneOf(x, y)) + '</span>',
                    ev
                );
            });
            box.addEventListener('click', function (ev) {
                var dot = ev.target.classList && ev.target.classList.contains('uj-dot') ? ev.target : null;
                if (dot) {
                    var p = points[parseInt(dot.getAttribute('data-i'), 10)] || {};
                    showTip(
                        '<strong>' + escapeHtml(p.page_name || pageName) + '</strong>' +
                        Number(p.hits || 0) + ' click' + (Number(p.hits) === 1 ? '' : 's') + ' at this spot<br>' +
                        escapeHtml(p.zone || 'Screen area') + ' &middot; ' + Number(p.x || 0) + '% from left, ' + Number(p.y || 0) + '% from top<br>' +
                        '<span style="opacity:.8">' + escapeHtml(p.zone_hint || '') + '</span>',
                        ev
                    );
                    return;
                }
                showTip('Grey area is the page. Red dots are clicks. Darker red means more people clicked that spot. Top is header, left is menu, middle is content, bottom is footer.', ev);
            });
            box.addEventListener('mouseleave', function () {
                tip.style.display = 'none';
            });
        }

        function rememberTab(tab) {
            var tabInput = document.getElementById('uj-filter-tab');
            if (tabInput) tabInput.value = tab;
            if (history.replaceState) {
                var url = new URL(window.location.href);
                url.searchParams.set('tab', tab);
                history.replaceState({}, '', url.toString());
            }
        }

        function ujFilterQuery() {
            var form = document.getElementById('uj-global-filters');
            if (!form) return '';
            var data = new FormData(form);
            var parts = [];
            data.forEach(function (value, key) {
                if (key !== 'tab' && value) {
                    parts.push(encodeURIComponent(key) + '=' + encodeURIComponent(value));
                }
            });
            return parts.length ? '&' + parts.join('&') : '';
        }

        function loadTab(tab, heatPath) {
            if (tab === 'events' || !ujAnalytics) return;
            var pane = document.getElementById('uj-pane-' + tab);
            if (!pane) return;
            var cacheKey = tab + ':' + (heatPath || '');
            if (ujLoaded[cacheKey] && tab !== 'heat') return;
            pane.innerHTML = '<div class="uj-loading">Loading ' + escapeHtml(tab) + '...</div>';
            var url = ujTabUrl + '/' + encodeURIComponent(tab) + '?days=' + ujDays + ujFilterQuery();
            if (heatPath) url += '&heat_path=' + encodeURIComponent(heatPath);
            fetch(url, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin'
            }).then(function (r) {
                return r.json().then(function (data) {
                    if (!r.ok) throw new Error((data && data.error) || ('HTTP ' + r.status));
                    return data;
                });
            }).then(function (data) {
                ujLoaded[cacheKey] = data;
                renderTab(tab, data);
            }).catch(function (err) {
                pane.innerHTML = '<div class="alert alert-danger">Could not load this view. ' + escapeHtml(err.message || err) + '</div>';
            });
        }

        function withCharts(cb) {
            if (ujChartsReady) return cb()
            function start() {
                google.charts.load('current', { packages: ['corechart', 'geochart', 'line', 'sankey'] })
                google.charts.setOnLoadCallback(function () {
                    ujChartsReady = true
                    cb()
                })
            }
            if (window.google && google.charts) return start()
            var s = document.createElement('script')
            s.src = 'https://www.gstatic.com/charts/loader.js'
            s.onload = start
            document.head.appendChild(s)
        }

        function renderMerchantFunnel(steps, valueKey, subtitle) {
            valueKey = valueKey || 'sessions';
            var list = (steps || []).map(function (s, i) {
                var value = Number(s[valueKey] != null ? s[valueKey] : (s.hits || s.sessions || 0));
                return {
                    label: s.label || s.page_name || ('Step ' + (i + 1)),
                    sub: s.sub || subtitle || '',
                    value: value,
                    conv_prev: s.conv_prev,
                    drop_pct: s.drop_pct,
                    drop_prev: s.drop_prev
                };
            });
            if (!list.length) {
                return '<p class="text-muted mb-0">No funnel data in this range.</p>';
            }
            var first = list[0].value || 0;
            var last = list[list.length - 1].value || 0;
            var overall = first > 0 ? Math.round((last / first) * 100) : 0;
            var colors = ['#174ea6', '#1a73e8', '#4285f4', '#8ab4f8', '#aecbfa'];
            var html = '<div class="uj-mc-head"><div><div class="uj-mc-rate">' + overall + '%<span>completed last step</span></div></div>' +
                '<div class="small text-muted">' + Number(first).toLocaleString() + ' started · ' + Number(last).toLocaleString() + ' finished</div></div><div class="uj-mc">';
            list.forEach(function (step, i) {
                var width = Math.max(42, 100 - i * 12);
                var conv = step.conv_prev != null ? step.conv_prev : (i === 0 || list[i - 1].value <= 0 ? 100 : Math.round((step.value / list[i - 1].value) * 100));
                var dropPct = step.drop_pct != null ? step.drop_pct : Math.max(0, 100 - conv);
                var dropped = step.drop_prev != null ? step.drop_prev : (i === 0 ? 0 : Math.max(0, list[i - 1].value - step.value));
                if (i > 0) {
                    html += '<div class="uj-mc-drop"><span class="ok">' + conv + '% continued</span><span class="line"></span><span class="lost">' + dropPct + '% left · ' + Number(dropped).toLocaleString() + '</span></div>';
                }
                html += '<div class="uj-mc-stage" style="width:' + width + '%;background:' + colors[i % colors.length] + '">' +
                    '<div class="n">' + Number(step.value).toLocaleString() + '</div>' +
                    '<div class="l">' + escapeHtml(step.label) + '</div>' +
                    (step.sub ? '<div class="s">' + escapeHtml(step.sub) + '</div>' : '') +
                    '</div>';
            });
            return html + '</div>';
        }

        function renderPathFunnel(flows) {
            var edges = (flows || []).filter(function (f) { return f.from_path && f.to_path && f.from_path !== f.to_path; });
            if (!edges.length) {
                return '<p class="text-muted mb-0">Need more sequential page views to build a path funnel.</p>';
            }
            var bestFrom = {};
            edges.forEach(function (f) {
                if (!bestFrom[f.from_path] || Number(bestFrom[f.from_path].hits) < Number(f.hits)) {
                    bestFrom[f.from_path] = f;
                }
            });
            var startPath = edges[0].from_path;
            var outgoing = edges.filter(function (f) { return f.from_path === startPath; }).slice(0, 6);
            var startTotal = outgoing.reduce(function (sum, f) { return sum + Number(f.hits); }, 0);
            var cursor = startPath;
            var seen = {};
            var steps = [{
                label: edges[0].from_name || startPath,
                sub: startPath,
                sessions: startTotal || Number(edges[0].hits)
            }];
            seen[cursor] = true;
            var guard = 0;
            while (guard++ < 5 && bestFrom[cursor] && !seen[bestFrom[cursor].to_path]) {
                var edge = bestFrom[cursor];
                steps.push({
                    label: edge.to_name || edge.to_path,
                    sub: edge.to_path,
                    sessions: Number(edge.hits)
                });
                seen[edge.to_path] = true;
                cursor = edge.to_path;
            }
            var outMax = Math.max.apply(null, [1].concat(outgoing.map(function (f) { return Number(f.hits); })));
            var next = outgoing.map(function (f) {
                var w = Math.max(6, Math.round((Number(f.hits) / outMax) * 100));
                return '<div class="uj-next"><div class="uj-next-name">' + escapeHtml(f.to_name || f.to_path) +
                    '<div class="small text-muted text-truncate">' + escapeHtml(f.to_path) + '</div></div>' +
                    '<div class="uj-next-bar"><i style="width:' + w + '%"></i></div>' +
                    '<div class="uj-next-meta"><strong>' + Number(f.hits).toLocaleString() + '</strong><div class="text-muted">moves</div></div></div>';
            }).join('');
            return renderMerchantFunnel(steps, 'sessions', 'people who took this next page') +
                '<div class="uj-kicker mt-3 mb-2">Where they went next from ' + escapeHtml(edges[0].from_name || startPath) + '</div>' +
                (next || '<p class="text-muted mb-0">No outgoing paths.</p>');
        }

        function renderTab(tab, data) {
            var pane = document.getElementById('uj-pane-' + tab)
            if (tab === 'overview') {
                var o = data.overview || {}
                var kpiHelp = {
                    Users: 'Distinct signed-in people in this date range.',
                    Sessions: 'Separate visits (browser or app session).',
                    Views: 'Total page and API events recorded.',
                    Countries: 'How many countries events came from.',
                    'Admin views': 'Events from staff on the admin portal.',
                    'Client / app': 'Events from the client portal or mobile app.'
                }
                pane.innerHTML =
                    '<p class="uj-help">Overview is a snapshot of traffic for the days selected at the top.' + hint('Change Last 7 / 28 / 90 days in the header to refresh these numbers.') + '</p>' +
                    '<div class="row mb-3">' +
                    [['Users', o.users], ['Sessions', o.sessions], ['Views', o.views], ['Countries', o.countries], ['Admin views', o.admin_views], ['Client / app', o.client_views]].map(function (kpi) {
                        return '<div class="col-md-2 col-sm-4 mb-3"><div class="uj-card"><div class="uj-kicker">' + kpi[0] + hint(kpiHelp[kpi[0]] || '') + '</div><div class="uj-kpi">' + Number(kpi[1] || 0).toLocaleString() + '</div></div></div>'
                    }).join('') +
                    '</div><div class="uj-card mb-3"><div class="uj-kicker mb-2">Traffic' + hint('Line chart of views and sessions per day.') + '</div><div id="uj-trend" style="height:280px"></div></div>' +
                    '<div class="row"><div class="col-md-6 mb-3"><div class="uj-card"><div class="uj-kicker mb-2">Channel' + hint('Admin portal vs client portal vs mobile app.') + '</div><div id="uj-channel" style="height:240px"></div></div></div>' +
                    '<div class="col-md-6 mb-3"><div class="uj-card"><div class="uj-kicker mb-2">Devices' + hint('Desktop, mobile, or tablet used to open the product.') + '</div><div id="uj-device" style="height:240px"></div></div></div></div>'
                withCharts(function () {
                    var trend = new google.visualization.DataTable()
                    trend.addColumn('string', 'Day'); trend.addColumn('number', 'Views'); trend.addColumn('number', 'Sessions')
                    trend.addRows((data.trend || []).map(function (r) { return [String(r.day), Number(r.views), Number(r.sessions)] }))
                    new google.visualization.LineChart(document.getElementById('uj-trend')).draw(trend, { legend: { position: 'bottom' }, chartArea: { width: '85%', height: '70%' } })
                    var ch = new google.visualization.DataTable(); ch.addColumn('string', 'Channel'); ch.addColumn('number', 'Views')
                    ch.addRows((data.channels || []).map(function (r) { return [r.channel || 'unknown', Number(r.views)] }))
                    new google.visualization.PieChart(document.getElementById('uj-channel')).draw(ch, { pieHole: 0.45 })
                    var devices = (data.devices && data.devices.devices) || {}
                    var dv = new google.visualization.DataTable(); dv.addColumn('string', 'Device'); dv.addColumn('number', 'Views')
                    Object.keys(devices).forEach(function (k) { dv.addRow([k || 'Unknown', Number(devices[k])]) })
                    new google.visualization.PieChart(document.getElementById('uj-device')).draw(dv, { pieHole: 0.45 })
                })
            }
            if (tab === 'locations') {
                var cities = (data.cities || []).map(function (c) {
                    return '<tr><td>' + escapeHtml(c.city) + ' <span class="text-muted">' + escapeHtml(c.country || '') + '</span></td><td>' + c.views + '</td></tr>'
                }).join('') || '<tr><td colspan="2">No city data yet.</td></tr>'
                pane.innerHTML = '<p class="uj-help">Darker countries had more recorded views. City list is the same data, ranked.' + hint('Location comes from CDN headers or the device pin when the app sends it.') + '</p>' +
                    '<div class="row"><div class="col-lg-8 mb-3"><div class="uj-card"><div class="uj-kicker mb-2">Users by country' + hint('Hover a country on the map to see its view count.') + '</div><div id="uj-map" style="height:420px"></div></div></div>' +
                    '<div class="col-lg-4 mb-3"><div class="uj-card"><div class="uj-kicker mb-2">Top cities' + hint('Cities with the most events in this range.') + '</div><table class="table table-sm mb-0"><thead><tr><th>City</th><th>Views</th></tr></thead><tbody>' + cities + '</tbody></table></div></div></div>'
                withCharts(function () {
                    var rows = [['Country', 'Views']].concat((data.countries || []).map(function (r) { return [r.country, Number(r.views)] }))
                    if (rows.length < 2) rows.push(['United States', 0])
                    new google.visualization.GeoChart(document.getElementById('uj-map')).draw(google.visualization.arrayToDataTable(rows), {
                        colorAxis: { colors: ['#d6e4f5', '#5b8def', '#5b3d8c'] }, datalessRegionColor: '#f4f6f9'
                    })
                })
            }
            if (tab === 'pages') {
                var rows = (data.pages || []).map(function (p) {
                    return '<tr><td class="uj-path">' + escapeHtml(p.route_path) + '</td><td>' + p.views + '</td><td>' + p.sessions + '</td><td>' + p.users + '</td></tr>'
                }).join('') || '<tr><td colspan="4">No page views in this range.</td></tr>'
                pane.innerHTML = '<p class="uj-help">Most opened screens. Views count every hit; sessions and users are unique.' + hint('Use this to see which modules people actually open.') + '</p>' +
                    '<div class="uj-card"><div class="uj-kicker mb-2">Top pages' + hint('Path is the URL or API route that was opened.') + '</div><table class="table table-sm mb-0"><thead><tr><th>Path</th><th>Views</th><th>Sessions</th><th>Users</th></tr></thead><tbody>' + rows + '</tbody></table></div>'
            }
            if (tab === 'funnels') {
                pane.innerHTML =
                    '<p class="uj-help">Each stage is sessions that reached that step. Green is the share that continued; red is the drop-off, as in Merchant Center.' + hint('The left funnel is product modules. The right funnel is the most common page-to-page path.') + '</p>' +
                    '<div class="row">' +
                    '<div class="col-lg-6 mb-3"><div class="uj-card">' +
                    '<div class="uj-kicker mb-2">Conversion funnel</div>' +
                    renderMerchantFunnel(data.funnel || [], 'sessions', 'sessions reached this module') +
                    '</div></div>' +
                    '<div class="col-lg-6 mb-3"><div class="uj-card">' +
                    '<div class="uj-kicker mb-2">Path flow</div>' +
                    renderPathFunnel(data.flows || []) +
                    '</div></div></div>'
            }
            if (tab === 'heat') {
                var insight = data.insight || { total: 0, zones: [], summary: 'No click heat yet.' }
                var opts = '<option value="">All pages</option>' + (data.heatPaths || []).map(function (p) {
                    return '<option value="' + escapeHtml(p.route_path) + '"' + (p.route_path === data.heatPath ? ' selected' : '') + '>' + escapeHtml(p.page_name || p.route_path) + ' (' + p.hits + ' clicks)</option>'
                }).join('')
                var maxHits = Math.max.apply(null, [1].concat((data.heat || []).map(function (p) { return Number(p.hits) })))
                var dots = (data.heat || []).map(function (p, i) {
                    var a = Math.min(0.9, 0.25 + (Number(p.hits) / maxHits) * 0.65)
                    return '<span class="uj-dot" data-i="' + i + '" style="left:' + p.x + '%;top:' + p.y + '%;background:rgba(220,53,69,' + a + ')"></span>'
                }).join('')
                var zoneRows = (insight.zones || []).map(function (z) {
                    return '<tr><td>' + escapeHtml(z.label) + '</td><td>' + z.hits + '</td><td>' + z.pct + '%</td></tr>'
                }).join('') || '<tr><td colspan="3">No clicks recorded.</td></tr>'
                pane.innerHTML =
                    '<p class="uj-help">Grey box is the page. Hover or click a red dot for page name, click count, and which part of the screen it is. Click empty grey for a reminder.' + hint('Top = header, left = menu, middle = content, bottom = footer.') + '</p>' +
                    '<div class="alert alert-light border mb-3">' + escapeHtml(insight.summary) + '</div>' +
                    '<div class="d-flex align-items-center mb-3">' +
                    '<select id="uj-heat-path" class="form-control mr-3" style="max-width:420px">' + opts + '</select>' +
                    '<div class="uj-legend small text-muted"><span style="background:#f5c2c7"></span> Few clicks &nbsp; <span style="background:#dc3545"></span> Many clicks</div>' +
                    '</div>' +
                    '<div class="row"><div class="col-lg-8 mb-3"><div class="uj-card p-2"><div class="uj-kicker mb-2 px-2 pt-1">Click map: ' + escapeHtml(data.pageName || 'All pages') + '</div>' +
                    '<div class="uj-heat" id="uj-heat-box">' + dots + '<div class="uj-tip" id="uj-heat-tip"></div></div></div></div>' +
                    '<div class="col-lg-4 mb-3"><div class="uj-card"><div class="uj-kicker mb-2">Clicks by screen area</div>' +
                    '<div class="uj-kpi mb-2">' + Number(insight.total || 0).toLocaleString() + '</div><div class="small text-muted mb-2">Total clicks in this view</div>' +
                    '<table class="table table-sm mb-0"><thead><tr><th>Area</th><th>Clicks</th><th>%</th></tr></thead><tbody>' + zoneRows + '</tbody></table></div></div></div>'
                var select = document.getElementById('uj-heat-path')
                if (select) select.addEventListener('change', function () { ujLoaded = {}; loadTab('heat', select.value) })
                bindHeatTips(data.heat || [], data.pageName || 'All pages')
            }
        }

        $(document).ready(function() {
            $('#uj-tabs a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
                var tab = e.target.getAttribute('data-tab')
                rememberTab(tab)
                loadTab(tab)
            })
            if (window.jQuery && $.fn.tooltip) {
                $('body').tooltip({ selector: '.uj-hint', container: 'body' });
            }
            var daysSelect = document.getElementById('uj-days');
            if (daysSelect) {
                daysSelect.addEventListener('change', function () {
                    if (daysSelect.value === 'all') {
                        document.getElementById('uj-date-from').value = '';
                        document.getElementById('uj-date-to').value = '';
                        return;
                    }
                    var days = parseInt(daysSelect.value, 10);
                    if (!days) return;
                    var to = new Date();
                    var from = new Date();
                    from.setDate(to.getDate() - days);
                    function iso(d) {
                        var m = (d.getMonth() + 1);
                        var day = d.getDate();
                        return d.getFullYear() + '-' + (m < 10 ? '0' : '') + m + '-' + (day < 10 ? '0' : '') + day;
                    }
                    document.getElementById('uj-date-from').value = iso(from);
                    document.getElementById('uj-date-to').value = iso(to);
                });
            }
            if (ujAnalytics && ujTab && ujTab !== 'events') {
                loadTab(ujTab)
            }

            var searchInput = document.getElementById('uj-grid-search');
            if (searchInput) {
                if (search_string) searchInput.value = search_string;
                searchInput.addEventListener('input', function (e) {
                    if (hiddenInput) hiddenInput.value = e.target.value;
                    updateDebounceText(e.target.value);
                });
            }

            if (!document.getElementById('data-grid')) return;
            $('#data-grid').dataTable({
                responsive: false,
                autoWidth: false,
                scrollX: false,
                searching: false,
                dom: "<'row mb-3'<'col-sm-12 col-md-6'><'col-sm-12 col-md-6 d-flex justify-content-end'l>>" +
                    "<'row'<'col-sm-12'tr>>",
                'order': []
            });

            const pageLength = document.getElementsByName('data-grid_length')[0]

            if (pageLength) {
                const options = Array.from(pageLength.options)
                let optionToSelect = options.find(item => item.value == perPage)
                if (optionToSelect) optionToSelect.setAttribute('selected', true)

                if (perPage == -1) {
                    perPage = recordsCount
                }

                $('#data-grid').DataTable().page.len(perPage).draw();

                pageLength.addEventListener('change', function() {
                    document.getElementById('per_page').value = pageLength.value
                    document.getElementById('search').submit()
                })
            }

        });

        var updateDebounceText = debounce(function (searchString) {
            hiddenInput.value = searchString;
            document.getElementById('search').submit();
        });

        function debounce(callback, delay) {
            var timeout;
            delay = delay || 1000;
            return function () {
                var args = arguments;
                clearTimeout(timeout);
                timeout = setTimeout(function () {
                    callback.apply(null, args);
                }, delay);
            };
        }
    </script>
@stop
