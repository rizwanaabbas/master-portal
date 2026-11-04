<?php

namespace App\Helpers;

use App\Models\UserJourneyHeatPoint;
use App\Models\UserJourneyLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class UserJourneyAnalytics
{
    public static function rangeDays(int $days): array
    {
        $days = in_array($days, [7, 28, 90], true) ? $days : 28;
        $from = Carbon::now()->subDays($days)->startOfDay();

        return [$days, $from];
    }

    public static function rangeFromRequest($request): array
    {
        $dateFrom = trim((string) $request->get('date_from', ''));
        $dateTo = trim((string) $request->get('date_to', ''));
        $days = (int) $request->get('days', 28);
        if ((string) $request->get('days') === 'all' && $dateFrom === '' && $dateTo === '') {
            $from = Carbon::create(2020, 1, 1)->startOfDay();
            $to = Carbon::now()->endOfDay();
            $days = 0;
            $dateFrom = '';
            $dateTo = '';

            return [$days, $from, $to, $dateFrom, $dateTo];
        }
        if ($dateFrom !== '' || $dateTo !== '') {
            $from = $dateFrom !== '' ? Carbon::parse($dateFrom)->startOfDay() : Carbon::now()->subDays(90)->startOfDay();
            $to = $dateTo !== '' ? Carbon::parse($dateTo)->endOfDay() : Carbon::now()->endOfDay();
            if ($from->gt($to)) {
                [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
            }
            $days = max(1, $from->diffInDays($to));
        } else {
            [$days, $from] = self::rangeDays($days);
            $to = Carbon::now()->endOfDay();
            $dateFrom = $from->toDateString();
            $dateTo = $to->toDateString();
        }

        return [$days, $from, $to, $dateFrom, $dateTo];
    }

    public static function requestFilters($request): array
    {
        return [
            'channel' => trim((string) $request->get('channel', '')),
            'event_name' => trim((string) $request->get('event_name', '')),
            'os' => trim((string) $request->get('os', '')),
            'device_name' => trim((string) $request->get('device_name', '')),
            'user' => trim((string) $request->get('user', '')),
            'email' => trim((string) $request->get('email', '')),
            'country' => strtoupper(trim((string) $request->get('country', ''))),
            'ip' => trim((string) $request->get('ip', '')),
            'path' => trim((string) $request->get('path', '')),
            'date_from' => trim((string) $request->get('date_from', '')),
            'date_to' => trim((string) $request->get('date_to', '')),
        ];
    }

    public static function applyFilters($query, array $filters, string $table = 'user_journey_logs')
    {
        if (!empty($filters['channel'])) {
            $query->where($table . '.channel', $filters['channel']);
        }
        if (!empty($filters['event_name']) && $table === 'user_journey_logs') {
            $query->where($table . '.event_name', $filters['event_name']);
        }
        if (!empty($filters['os']) && $table === 'user_journey_logs') {
            $query->where($table . '.os', $filters['os']);
        }
        if (!empty($filters['device_name']) && $table === 'user_journey_logs' && \Schema::hasColumn('user_journey_logs', 'device_name')) {
            $query->where($table . '.device_name', 'LIKE', '%' . $filters['device_name'] . '%');
        }
        if (!empty($filters['country']) && $table === 'user_journey_logs') {
            $query->where($table . '.country', $filters['country']);
        }
        if (!empty($filters['ip']) && $table === 'user_journey_logs') {
            $ip = $filters['ip'];
            $query->where(function ($inner) use ($table, $ip) {
                $inner->where($table . '.ip_address', 'LIKE', '%' . $ip . '%');
                foreach (['ip_v4', 'ip_v6', 'ip_vpn', 'ip_forwarded'] as $column) {
                    if (\Schema::hasColumn('user_journey_logs', $column)) {
                        $inner->orWhere($table . '.' . $column, 'LIKE', '%' . $ip . '%');
                    }
                }
            });
        }
        if (!empty($filters['path'])) {
            $query->where($table . '.route_path', 'LIKE', '%' . $filters['path'] . '%');
        }
        if (!empty($filters['user'])) {
            $name = $filters['user'];
            $query->whereExists(function ($sub) use ($table, $name) {
                $sub->selectRaw('1')
                    ->from('users')
                    ->whereColumn('users.id', $table . '.user_id')
                    ->where('users.name', 'LIKE', '%' . $name . '%');
            });
        }
        if (!empty($filters['email'])) {
            $email = $filters['email'];
            $query->whereExists(function ($sub) use ($table, $email) {
                $sub->selectRaw('1')
                    ->from('users')
                    ->whereColumn('users.id', $table . '.user_id')
                    ->where('users.email', 'LIKE', '%' . $email . '%');
            });
        }

        return $query;
    }

    public static function logs(Carbon $from, ?Carbon $to = null, array $filters = [])
    {
        $q = UserJourneyLog::query()
            ->where('created_at', '>=', $from)
            ->when($to, function ($query) use ($to) {
                $query->where('created_at', '<=', $to);
            });

        return self::applyFilters($q, $filters);
    }

    public static function overview(Carbon $from, ?Carbon $to = null, array $filters = []): array
    {
        $base = self::logs($from, $to, $filters);

        return [
            'views' => (clone $base)->count(),
            'users' => (clone $base)->whereNotNull('user_id')->distinct('user_id')->count('user_id'),
            'sessions' => (clone $base)->distinct('session_id')->count('session_id'),
            'countries' => (clone $base)->whereNotNull('country')->distinct('country')->count('country'),
            'admin_views' => (clone $base)->where('channel', 'portal_admin')->count(),
            'client_views' => (clone $base)->whereIn('channel', ['portal_client', 'mobile'])->count(),
        ];
    }

    public static function trend(Carbon $from, ?Carbon $to = null, array $filters = []): array
    {
        return self::logs($from, $to, $filters)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as views, COUNT(DISTINCT session_id) as sessions, COUNT(DISTINCT user_id) as users')
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->toArray();
    }

    public static function countries(Carbon $from, ?Carbon $to = null, array $filters = [])
    {
        return self::logs($from, $to, $filters)
            ->selectRaw('country, COUNT(*) as views, COUNT(DISTINCT session_id) as sessions, COUNT(DISTINCT user_id) as users')
            ->whereNotNull('country')
            ->where('country', '<>', '')
            ->groupBy('country')
            ->orderByDesc('views')
            ->limit(40)
            ->get();
    }

    public static function cities(Carbon $from, ?Carbon $to = null, array $filters = [])
    {
        return self::logs($from, $to, $filters)
            ->selectRaw('country, city, COUNT(*) as views, COUNT(DISTINCT session_id) as sessions')
            ->whereNotNull('city')
            ->where('city', '<>', '')
            ->groupBy('country', 'city')
            ->orderByDesc('views')
            ->limit(25)
            ->get();
    }

    public static function pages(Carbon $from, ?Carbon $to = null, array $filters = [])
    {
        return self::logs($from, $to, $filters)
            ->selectRaw('route_path, COUNT(*) as views, COUNT(DISTINCT session_id) as sessions, COUNT(DISTINCT user_id) as users')
            ->where(function ($q) {
                $q->whereNull('event_name')
                    ->orWhereIn('event_name', ['page_view', 'api_view']);
            })
            ->groupBy('route_path')
            ->orderByDesc('views')
            ->limit(20)
            ->get();
    }

    public static function devices(Carbon $from, ?Carbon $to = null, array $filters = []): array
    {
        $rows = self::logs($from, $to, $filters)
            ->selectRaw('device_type, browser, os, COUNT(*) as views')
            ->groupBy('device_type', 'browser', 'os')
            ->orderByDesc('views')
            ->get();

        return [
            'rows' => $rows,
            'devices' => $rows->groupBy('device_type')->map->sum('views'),
            'browsers' => $rows->groupBy('browser')->map->sum('views'),
        ];
    }

    public static function flows(Carbon $from, ?Carbon $to = null, array $filters = [])
    {
        $extra = '';
        $bindings = [$from->toDateTimeString()];
        if ($to) {
            $extra .= ' AND a.created_at <= ?';
            $bindings[] = $to->toDateTimeString();
        }
        if (!empty($filters['channel'])) {
            $extra .= ' AND a.channel = ?';
            $bindings[] = $filters['channel'];
        }
        $sql = "SELECT a.route_path AS from_path, b.route_path AS to_path, COUNT(*) AS hits
            FROM user_journey_logs a
            INNER JOIN user_journey_logs b
                ON a.session_id = b.session_id
                AND b.id = (
                    SELECT MIN(c.id) FROM user_journey_logs c
                    WHERE c.session_id = a.session_id AND c.id > a.id
                )
            WHERE a.created_at >= ?
              AND a.event_name = 'page_view'
              AND b.event_name = 'page_view'
              {$extra}
            GROUP BY a.route_path, b.route_path
            ORDER BY hits DESC
            LIMIT 15";

        return collect(DB::select($sql, $bindings))->map(function ($row) {
            $row->from_name = self::pageName($row->from_path ?? '');
            $row->to_name = self::pageName($row->to_path ?? '');
            $row->hits = (int) $row->hits;

            return $row;
        })->all();
    }

    public static function funnel(Carbon $from, ?Carbon $to = null, array $filters = []): array
    {
        $steps = [
            ['key' => 'home', 'label' => 'Home / dashboard', 'like' => ['/home', '/dashboard%', '/client/home%']],
            ['key' => 'clients', 'label' => 'Clients', 'like' => ['/client-management/%', '/api/client/%']],
            ['key' => 'cards', 'label' => 'Cards', 'like' => ['/card-management/%', '/client/card-management/%', '%/cards%', '%/fingate%']],
            ['key' => 'crypto', 'label' => 'Crypto', 'like' => ['/crypto-management/%', '%/crypto%', '%/fireblocks%']],
        ];

        $out = [];
        foreach ($steps as $step) {
            $q = self::logs($from, $to, $filters)
                ->where(function ($inner) use ($step) {
                    foreach ($step['like'] as $i => $pattern) {
                        $method = $i === 0 ? 'where' : 'orWhere';
                        $inner->{$method}('route_path', 'like', $pattern);
                    }
                });
            $out[] = [
                'key' => $step['key'],
                'label' => $step['label'],
                'sessions' => (clone $q)->distinct('session_id')->count('session_id'),
                'views' => $q->count(),
            ];
        }

        $first = (int) ($out[0]['sessions'] ?? 0);
        $prev = 0;
        foreach ($out as $i => &$step) {
            $sessions = (int) $step['sessions'];
            $step['conv_start'] = $first > 0 ? (int) round(($sessions / $first) * 100) : 0;
            $step['conv_prev'] = ($i === 0 || $prev <= 0) ? 100 : (int) round(($sessions / $prev) * 100);
            $step['drop_prev'] = ($i === 0 || $prev <= 0) ? 0 : max(0, $prev - $sessions);
            $step['drop_pct'] = ($i === 0 || $prev <= 0) ? 0 : (int) round(($step['drop_prev'] / $prev) * 100);
            $prev = $sessions;
        }
        unset($step);

        return $out;
    }

    public static function heat(Carbon $from, ?string $path = null, ?Carbon $to = null, array $filters = [])
    {
        $q = UserJourneyHeatPoint::query()->where('created_at', '>=', $from);
        if ($to) {
            $q->where('created_at', '<=', $to);
        }
        if ($path) {
            $q->where('route_path', $path);
        }
        self::applyFilters($q, $filters, 'user_journey_heat_points');

        return $q->selectRaw('route_path, x, y, COUNT(*) as hits')
            ->groupBy('route_path', 'x', 'y')
            ->orderByDesc('hits')
            ->limit(400)
            ->get();
    }

    public static function heatPaths(Carbon $from, ?Carbon $to = null, array $filters = [])
    {
        $q = UserJourneyHeatPoint::query()
            ->selectRaw('route_path, COUNT(*) as hits')
            ->where('created_at', '>=', $from)
            ->when($to, function ($query) use ($to) {
                $query->where('created_at', '<=', $to);
            });
        self::applyFilters($q, $filters, 'user_journey_heat_points');

        return $q
            ->groupBy('route_path')
            ->orderByDesc('hits')
            ->limit(20)
            ->get()
            ->map(function ($row) {
                $row->page_name = self::pageName($row->route_path);
                return $row;
            });
    }

    public static function zoneFromPoint($x, $y): array
    {
        $x = (int) $x;
        $y = (int) $y;
        if ($y < 12) {
            return ['key' => 'header', 'label' => 'Header / top bar', 'hint' => 'Top of the screen: logo, search, user menu.'];
        }
        if ($y >= 88) {
            return ['key' => 'footer', 'label' => 'Footer / bottom', 'hint' => 'Bottom of the page: pagination or footer actions.'];
        }
        if ($x < 18) {
            return ['key' => 'menu', 'label' => 'Left menu', 'hint' => 'Navigation on the left side of the portal.'];
        }
        if ($x >= 82) {
            return ['key' => 'right', 'label' => 'Right side', 'hint' => 'Right panel, filters, or action buttons.'];
        }

        return ['key' => 'content', 'label' => 'Main content', 'hint' => 'Centre of the page: tables, forms, and main work.'];
    }

    public static function decorateHeat($points)
    {
        return collect($points)->map(function ($point) {
            $zone = self::zoneFromPoint($point->x ?? 0, $point->y ?? 0);
            $point->page_name = self::pageName($point->route_path ?? '');
            $point->zone = $zone['label'];
            $point->zone_hint = $zone['hint'];
            $point->hits = (int) $point->hits;

            return $point;
        });
    }

    public static function heatInsight($points): array
    {
        $zones = [
            'header' => ['label' => 'Top bar / header', 'hits' => 0],
            'menu' => ['label' => 'Left menu', 'hits' => 0],
            'content' => ['label' => 'Main content', 'hits' => 0],
            'right' => ['label' => 'Right side', 'hits' => 0],
            'footer' => ['label' => 'Footer / bottom', 'hits' => 0],
        ];
        $total = 0;
        foreach ($points as $point) {
            $hits = (int) $point->hits;
            $total += $hits;
            $zone = self::zoneFromPoint($point->x ?? 0, $point->y ?? 0);
            if (isset($zones[$zone['key']])) {
                $zones[$zone['key']]['hits'] += $hits;
            }
        }

        $list = [];
        foreach ($zones as $key => $zone) {
            $list[] = [
                'key' => $key,
                'label' => $zone['label'],
                'hits' => $zone['hits'],
                'pct' => $total ? round(($zone['hits'] / $total) * 100) : 0,
            ];
        }
        usort($list, function ($a, $b) {
            return $b['hits'] <=> $a['hits'];
        });
        $top = $list[0] ?? null;
        $summary = $total
            ? ('Staff clicked ' . number_format($total) . ' times. Most activity is in the '
                . strtolower($top['label']) . ' (' . $top['pct'] . '%). Hover or click a red dot for details. Darker red means more clicks.')
            : 'No click heat yet. Browse the portal as staff with the analytics module on to collect clicks.';

        return [
            'total' => $total,
            'zones' => $list,
            'summary' => $summary,
        ];
    }

    public static function channels(Carbon $from, ?Carbon $to = null, array $filters = [])
    {
        return self::logs($from, $to, $filters)
            ->selectRaw('channel, COUNT(*) as views, COUNT(DISTINCT session_id) as sessions')
            ->groupBy('channel')
            ->orderByDesc('views')
            ->get();
    }

    public static function sessionFlows(string $sessionId, $userId = null): array
    {
        $events = UserJourneyLog::query()
            ->where('session_id', $sessionId)
            ->when($userId, function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->where(function ($q) {
                $q->whereNull('event_name')
                    ->orWhereIn('event_name', ['page_view', 'api_view']);
            })
            ->orderBy('id')
            ->get(['id', 'route_path']);

        $pairs = [];
        $prev = null;
        foreach ($events as $event) {
            if ($prev && $prev->route_path !== $event->route_path) {
                $key = $prev->route_path . '=>' . $event->route_path;
                if (!isset($pairs[$key])) {
                    $pairs[$key] = (object) [
                        'from_path' => $prev->route_path,
                        'to_path' => $event->route_path,
                        'hits' => 0,
                    ];
                }
                $pairs[$key]->hits++;
            }
            $prev = $event;
        }

        return array_values($pairs);
    }

    public static function visit(int $id): ?array
    {
        $visit = DB::table('user_journey_logs')
            ->select('user_journey_logs.*', 'users.name as user_name', 'users.email as user_email')
            ->leftJoin('users', 'users.id', '=', 'user_journey_logs.user_id')
            ->where('user_journey_logs.id', $id)
            ->first();
        if (!$visit) {
            return null;
        }

        UserJourneyRecorder::hydrateLogLocation($visit);

        $chain = collect();
        $timeline = [];
        $flows = [];
        $sessionSeconds = 0;

        if (!empty($visit->session_id)) {
            $chain = DB::table('user_journey_logs')
                ->select(
                    'user_journey_logs.id',
                    'user_journey_logs.user_id',
                    'user_journey_logs.session_id',
                    'user_journey_logs.route_path',
                    'user_journey_logs.ip_address',
                    'user_journey_logs.country',
                    'user_journey_logs.region',
                    'user_journey_logs.city',
                    'user_journey_logs.latitude',
                    'user_journey_logs.longitude',
                    'user_journey_logs.device_type',
                    'user_journey_logs.browser',
                    'user_journey_logs.os',
                    'user_journey_logs.channel',
                    'user_journey_logs.event_name',
                    'user_journey_logs.created_at'
                )
                ->where('user_journey_logs.session_id', $visit->session_id)
                ->when(!empty($visit->user_id), function ($q) use ($visit) {
                    $q->where('user_journey_logs.user_id', $visit->user_id);
                }, function ($q) use ($visit) {
                    $q->whereNull('user_journey_logs.user_id')
                        ->where('user_journey_logs.ip_address', $visit->ip_address);
                })
                ->orderBy('user_journey_logs.created_at')
                ->orderBy('user_journey_logs.id')
                ->limit(80)
                ->get();

            $flows = self::sessionFlows($visit->session_id, $visit->user_id ?? null);
            $timeline = self::sessionTimeline($chain);
            $sessionSeconds = $timeline['seconds'] ?? 0;
        }

        return compact('visit', 'chain', 'flows', 'timeline', 'sessionSeconds');
    }

    public static function sessionTimeline($chain): array
    {
        $rows = collect($chain)->filter(function ($row) {
            $event = $row->event_name ?: 'page_view';

            return in_array($event, ['page_view', 'api_view'], true);
        })->values();

        $merged = [];
        foreach ($rows as $row) {
            $path = self::cleanPath($row->route_path ?? '');
            $last = $merged ? $merged[count($merged) - 1] : null;
            if ($last && $last->path === $path) {
                continue;
            }
            $merged[] = (object) [
                'id' => $row->id,
                'path' => $path,
                'name' => self::pageName($path),
                'event' => $row->event_name ?: 'page_view',
                'at' => Carbon::parse($row->created_at),
            ];
        }

        $count = count($merged);
        if ($count === 0) {
            return ['steps' => [], 'seconds' => 0];
        }

        $start = $merged[0]->at;
        $end = $merged[$count - 1]->at;
        $seconds = max(0, $end->diffInSeconds($start));
        $steps = [];
        foreach ($merged as $i => $row) {
            $dwell = 0;
            if ($i + 1 < $count) {
                $dwell = max(0, $merged[$i + 1]->at->diffInSeconds($row->at));
            }
            $row->dwell = $dwell;
            $row->offset = max(0, $row->at->diffInSeconds($start));
            $row->last = $i + 1 === $count;
            $steps[] = $row;
        }

        return compact('steps', 'seconds');
    }

    public static function cleanPath(?string $path): string
    {
        $path = trim((string) $path);
        if ($path === '') {
            return '/';
        }
        if (strpos($path, '?') !== false) {
            $path = substr($path, 0, strpos($path, '?'));
        }

        return '/' . trim($path, '/');
    }

    public static function pageName(?string $path): string
    {
        $path = strtolower(trim((string) $path));
        $path = $path === '' ? '/' : $path;
        if (strpos($path, '?') !== false) {
            $path = substr($path, 0, strpos($path, '?'));
        }
        $path = '/' . trim($path, '/');

        $map = [
            '/home' => 'Home',
            '/dashboard' => 'Dashboard',
            '/dashboard/admin' => 'Admin dashboard',
            '/client-management/clients' => 'Clients',
            '/card-management/cards/list' => 'Cards list',
            '/crypto-management/crypto-transactions' => 'Crypto transactions',
            '/user-trace/user-journey-logs' => 'Journey analytics',
            '/settings/modules' => 'Modules',
            '/api/client/accounts' => 'Mobile accounts',
            '/api/client/cards' => 'Mobile cards',
            '/api/fingate/card-details' => 'Fingate card details',
        ];
        if (isset($map[$path])) {
            return $map[$path];
        }

        foreach ($map as $prefix => $name) {
            if ($prefix !== '/' && strpos($path, $prefix) === 0) {
                return $name;
            }
        }
        if (strpos($path, '/client-management/') === 0) {
            return 'Client management';
        }
        if (strpos($path, '/card-management/') === 0 || strpos($path, '/cards') !== false) {
            return 'Cards';
        }
        if (strpos($path, '/crypto') !== false || strpos($path, '/fireblocks') !== false) {
            return 'Crypto';
        }
        if (strpos($path, '/api/') === 0) {
            $bits = array_values(array_filter(explode('/', $path)));
            $last = str_replace('-', ' ', end($bits) ?: 'api');
            return 'Mobile ' . ucwords($last);
        }

        $bits = array_values(array_filter(explode('/', $path)));
        $last = str_replace(['-', '_'], ' ', end($bits) ?: 'Page');

        return ucwords($last);
    }

    public static function formatDuration(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds . 's';
        }
        if ($seconds < 3600) {
            return floor($seconds / 60) . 'm ' . ($seconds % 60) . 's';
        }

        return floor($seconds / 3600) . 'h ' . floor(($seconds % 3600) / 60) . 'm';
    }

    public static function filterOptions(Carbon $from): array
    {
        $base = UserJourneyLog::query()->where('created_at', '>=', $from);

        return [
            'channels' => (clone $base)->whereNotNull('channel')->distinct()->orderBy('channel')->pluck('channel'),
            'events' => (clone $base)->whereNotNull('event_name')->distinct()->orderBy('event_name')->pluck('event_name'),
            'oses' => (clone $base)->whereNotNull('os')->distinct()->orderBy('os')->pluck('os'),
            'devices' => (clone $base)->whereNotNull('device_name')->distinct()->orderBy('device_name')->limit(40)->pluck('device_name'),
            'countries' => (clone $base)->whereNotNull('country')->where('country', '<>', '')->distinct()->orderBy('country')->pluck('country'),
        ];
    }
}
