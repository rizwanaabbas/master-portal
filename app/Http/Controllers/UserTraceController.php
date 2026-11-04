<?php

namespace App\Http\Controllers;

use App\Helpers\UserJourneyAnalytics;
use App\Helpers\UserJourneyRecorder;
use App\Models\AccessLogs;
use Illuminate\Http\Request;
use OwenIt\Auditing\Models\Audit;
use App\Models\Sessions;
use Yajra\DataTables\DataTables;

class UserTraceController extends Controller
{
    public function getActiveUsers()
    {
        $activeUsers = Sessions::where('user_id', '<>', null)->get();
        return view("user-trace.active-users", compact('activeUsers'));
    }

    public function getUserAccessLogs(Request $request)
    {
        $accessLogs = AccessLogs::select(
            'access_logs.id',
            'access_logs.request_url',
            'access_logs.request_datetime',
            'creator.name as creator'
        )
            ->leftJoin("users as creator", "access_logs.user_id", "=", "creator.id")
            ->orderBy('access_logs.request_datetime', 'desc');
        $perPage = $request->get("per_page");
        // dd($perPage);
        $searchString = $request->get('search_string');

        if ($searchString) {
            $accessLogs->where('request_url', 'LIKE', '%' . $searchString . '%')
                ->orwhere('creator.name', 'LIKE', '%' . $searchString . '%')
                ->orwhere('request_datetime', 'LIKE', '%' . $searchString . '%');
        }

        if ($perPage == -1 || ($searchString && $accessLogs->count() < 10)) {
            $perPage = $accessLogs->count();
        } elseif (!$request->get("per_page")) {
            $perPage = 10;
        }

        $accessLogs = $accessLogs->paginate($perPage);

        return view("user-trace.user-access-logs", compact('accessLogs', 'request'));
    }

    public function getUserJourneyLogs(Request $request)
    {
        $analyticsOn = UserJourneyRecorder::isEnabled();
        [$days, $from, $to, $dateFrom, $dateTo] = UserJourneyAnalytics::rangeFromRequest($request);
        $tab = $request->get('tab', 'events');
        $filters = UserJourneyAnalytics::requestFilters($request);
        $filters['date_from'] = $dateFrom;
        $filters['date_to'] = $dateTo;

        $hasDeviceName = \Schema::hasColumn('user_journey_logs', 'device_name');
        $hasIpV4 = \Schema::hasColumn('user_journey_logs', 'ip_v4');
        $select = [
            'user_journey_logs.id',
            'user_journey_logs.route_path',
            'user_journey_logs.ip_address',
            'user_journey_logs.country as country',
            'user_journey_logs.region as region',
            'user_journey_logs.city as city',
            'user_journey_logs.device_type',
            'user_journey_logs.browser',
            'user_journey_logs.os',
            'user_journey_logs.channel',
            'user_journey_logs.event_name',
            'user_journey_logs.created_at',
            'users.name as user_name',
            'users.email as user_email',
        ];
        if ($hasDeviceName) {
            $select[] = 'user_journey_logs.device_name';
        }
        if ($hasIpV4) {
            $select[] = 'user_journey_logs.ip_v4';
            $select[] = 'user_journey_logs.ip_v6';
            $select[] = 'user_journey_logs.ip_vpn';
            $select[] = 'user_journey_logs.ip_forwarded';
        }

        $userJourneyLogs = \DB::table('user_journey_logs')
            ->select($select)
            ->leftJoin('users', 'users.id', '=', 'user_journey_logs.user_id')
            ->where('user_journey_logs.created_at', '>=', $from)
            ->where('user_journey_logs.created_at', '<=', $to)
            ->orderBy('user_journey_logs.id', 'desc');

        if ($filters['channel'] !== '') {
            $userJourneyLogs->where('user_journey_logs.channel', $filters['channel']);
        }
        if ($filters['event_name'] !== '') {
            $userJourneyLogs->where('user_journey_logs.event_name', $filters['event_name']);
        }
        if ($filters['os'] !== '') {
            $userJourneyLogs->where('user_journey_logs.os', $filters['os']);
        }
        if ($hasDeviceName && $filters['device_name'] !== '') {
            $userJourneyLogs->where('user_journey_logs.device_name', 'LIKE', '%' . $filters['device_name'] . '%');
        }
        if ($filters['country'] !== '') {
            $userJourneyLogs->where('user_journey_logs.country', $filters['country']);
        }
        if ($filters['ip'] !== '') {
            $ip = $filters['ip'];
            $userJourneyLogs->where(function ($query) use ($ip, $hasIpV4) {
                $query->where('user_journey_logs.ip_address', 'LIKE', '%' . $ip . '%');
                if ($hasIpV4) {
                    $query->orWhere('user_journey_logs.ip_v4', 'LIKE', '%' . $ip . '%')
                        ->orWhere('user_journey_logs.ip_v6', 'LIKE', '%' . $ip . '%')
                        ->orWhere('user_journey_logs.ip_vpn', 'LIKE', '%' . $ip . '%')
                        ->orWhere('user_journey_logs.ip_forwarded', 'LIKE', '%' . $ip . '%');
                }
            });
        }
        if ($filters['path'] !== '') {
            $userJourneyLogs->where('user_journey_logs.route_path', 'LIKE', '%' . $filters['path'] . '%');
        }
        if (($filters['user'] ?? '') !== '') {
            $userJourneyLogs->where('users.name', 'LIKE', '%' . $filters['user'] . '%');
        }
        if (($filters['email'] ?? '') !== '') {
            $userJourneyLogs->where('users.email', 'LIKE', '%' . $filters['email'] . '%');
        }

        $perPage = $request->get("per_page");
        $searchString = $request->get('search_string');

        if ($searchString) {
            $userJourneyLogs->where(function ($query) use ($searchString) {
                $query->where('route_path', 'LIKE', '%' . $searchString . '%')
                    ->orWhere('user_journey_logs.method', 'LIKE', '%' . $searchString . '%')
                    ->orWhere('user_journey_logs.ip_address', 'LIKE', '%' . $searchString . '%')
                    ->orWhere('user_journey_logs.user_agent', 'LIKE', '%' . $searchString . '%')
                    ->orWhere('user_journey_logs.country', 'LIKE', '%' . $searchString . '%')
                    ->orWhere('user_journey_logs.city', 'LIKE', '%' . $searchString . '%')
                    ->orWhere('user_journey_logs.channel', 'LIKE', '%' . $searchString . '%')
                    ->orWhere('user_journey_logs.os', 'LIKE', '%' . $searchString . '%')
                    ->orWhere('users.name', 'LIKE', '%' . $searchString . '%')
                    ->orWhere('users.email', 'LIKE', '%' . $searchString . '%')
                    ->orWhere('user_journey_logs.created_at', 'LIKE', '%' . $searchString . '%');
            });
        }

        if ($perPage == -1 || ($searchString && $userJourneyLogs->count() < 10)) {
            $perPage = $userJourneyLogs->count();
        } elseif (!$request->get("per_page")) {
            $perPage = 10;
        }
        $perPage = (int) $perPage;
        if ($perPage < 1) {
            $perPage = 10;
        }

        $userJourneyLogs = $userJourneyLogs->paginate($perPage);

        return view("user-trace.user-journey-logs", compact(
            'userJourneyLogs',
            'request',
            'analyticsOn',
            'days',
            'tab',
            'filters',
            'from',
            'to'
        ));
    }

    public function getUserJourneyTab(Request $request, string $tab)
    {
        if (!UserJourneyRecorder::isEnabled()) {
            return response()->json(['ok' => false, 'error' => 'Module off'], 404);
        }

        try {
            [$days, $from, $to] = UserJourneyAnalytics::rangeFromRequest($request);
            $filters = UserJourneyAnalytics::requestFilters($request);

            if ($tab === 'overview') {
                return response()->json([
                    'overview' => UserJourneyAnalytics::overview($from, $to, $filters),
                    'trend' => UserJourneyAnalytics::trend($from, $to, $filters),
                    'channels' => UserJourneyAnalytics::channels($from, $to, $filters),
                    'devices' => UserJourneyAnalytics::devices($from, $to, $filters),
                ]);
            }
            if ($tab === 'locations') {
                return response()->json([
                    'countries' => UserJourneyAnalytics::countries($from, $to, $filters),
                    'cities' => UserJourneyAnalytics::cities($from, $to, $filters),
                ]);
            }
            if ($tab === 'pages') {
                return response()->json(['pages' => UserJourneyAnalytics::pages($from, $to, $filters)]);
            }
            if ($tab === 'funnels') {
                return response()->json([
                    'funnel' => UserJourneyAnalytics::funnel($from, $to, $filters),
                    'flows' => UserJourneyAnalytics::flows($from, $to, $filters),
                ]);
            }
            if ($tab === 'heat') {
                $heatPaths = UserJourneyAnalytics::heatPaths($from, $to, $filters);
                $heatPath = $request->get('heat_path', '');
                $heat = UserJourneyAnalytics::decorateHeat(
                    UserJourneyAnalytics::heat($from, $heatPath !== '' ? $heatPath : null, $to, $filters)
                );
                return response()->json([
                    'heatPaths' => $heatPaths,
                    'heatPath' => $heatPath,
                    'pageName' => $heatPath !== '' ? UserJourneyAnalytics::pageName($heatPath) : 'All pages',
                    'heat' => $heat,
                    'insight' => UserJourneyAnalytics::heatInsight($heat),
                ]);
            }
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 500);
        }

        return response()->json(['ok' => false], 404);
    }

    public function viewUserJourneyVisit($id)
    {
        $data = UserJourneyAnalytics::visit((int) $id);
        if (!$data) {
            abort(404);
        }

        $visit = $data['visit'];
        $channel = UserJourneyRecorder::channelMeta($visit->channel ?? null);
        $visit->channel_label = $channel['label'];
        $visit->channel_icon = $channel['icon'];
        $visit->os_icon = UserJourneyRecorder::osIcon($visit->os ?? null, $visit->device_name ?? null);
        $chain = collect($data['chain'])->map(function ($row) {
            $meta = UserJourneyRecorder::channelMeta($row->channel ?? null);
            $row->channel_label = $meta['label'];
            $row->channel_icon = $meta['icon'];
            $row->os_icon = UserJourneyRecorder::osIcon($row->os ?? null, $row->device_name ?? null);
            return $row;
        });
        $flows = $data['flows'] ?? [];
        $timeline = $data['timeline'] ?? ['steps' => [], 'seconds' => 0];
        $sessionSeconds = (int) ($data['sessionSeconds'] ?? 0);

        return view('user-trace.user-journey-visit', compact('visit', 'chain', 'flows', 'timeline', 'sessionSeconds'));
    }

    public function storeJourneyBeacon(Request $request)
    {
        if (!UserJourneyRecorder::isEnabled()) {
            return response()->json(['ok' => true, 'skipped' => true]);
        }

        $data = $request->validate([
            'event' => 'required|string|max:40',
            'path' => 'nullable|string|max:255',
            'title' => 'nullable|string|max:160',
            'x' => 'nullable|integer|min:0|max:100',
            'y' => 'nullable|integer|min:0|max:100',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'city' => 'nullable|string|max:80',
            'country' => 'nullable|string|max:2',
        ]);

        $user = $request->user();
        $channel = 'portal_admin';
        if ($user && method_exists($user, 'hasRole') && $user->hasRole(['client', 'draft client', 'basic client'])) {
            $channel = 'portal_client';
        }

        if (($data['event'] ?? '') === 'page_view') {
            return response()->json(['ok' => true, 'skipped' => 'refresh']);
        }

        if (($data['event'] ?? '') === 'geo') {
            if ($request->hasSession()) {
                if (isset($data['latitude'], $data['longitude'])) {
                    $request->session()->put('uj_latitude', (float) $data['latitude']);
                    $request->session()->put('uj_longitude', (float) $data['longitude']);
                }
                if (!empty($data['city'])) {
                    $request->session()->put('uj_city', $data['city']);
                }
                if (!empty($data['country'])) {
                    $request->session()->put('uj_country', strtoupper($data['country']));
                }
            }

            return response()->json(['ok' => true, 'geo' => true]);
        }

        if (($data['event'] ?? '') === 'click' && isset($data['x'], $data['y'])) {
            UserJourneyRecorder::recordHeat($request, $data['path'] ?? $request->path(), (int) $data['x'], (int) $data['y'], $channel);
        }

        UserJourneyRecorder::record($request, [
            'route_path' => $data['path'] ?? ('/' . ltrim($request->path(), '/')),
            'method' => 'CLIENT',
            'page_title' => $data['title'] ?? null,
            'channel' => $channel,
            'event_name' => $data['event'],
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
        ]);

        return response()->json(['ok' => true]);
    }

    public function getUserAudits(Request $request)
    {
        // if ($request->ajax()) {
        $audits = Audit::select(
            "audits.id",
            "audits.user_id",
            "user.name",
            "role.name as role_name",
            "audits.event",
            "audits.auditable_type",
            "audits.auditable_id",
            "audits.url",
            "audits.created_at"
        )
            ->leftJoin("users as user", "audits.user_id", "=", "user.id")
            ->leftJoin("model_has_roles as user_roles", "user_roles.model_id", "=", "user.id")
            ->leftJoin("roles as role", "role.id", "=", "user_roles.role_id")
            ->orderBy('audits.created_at', 'desc');

        $perPage = $request->get("per_page");

        if ($request->get('search_string')) {
            $audits->where(function ($query) use ($request) {
                $query->where('audits.event', 'LIKE', '%' . $request->get('search_string') . '%')
                    ->orwhere('audits.auditable_type', 'LIKE', '%' . $request->get('search_string') . '%')
                    ->orwhere('audits.auditable_id', 'LIKE', '%' . $request->get('search_string') . '%')
                    ->orwhere('audits.url', 'LIKE', '%' . $request->get('search_string') . '%')
                    ->orwhere('role.name', 'LIKE', '%' . $request->get('search_string') . '%')
                    ->orWhere('user.name', 'LIKE', '%' . $request->get('search_string') . '%')
                    ->orwhere('audits.created_at', 'LIKE', '%' . $request->get('search_string') . '%');
            });
        }

        if (
            $perPage == -1 || ($request->get('search_string') && $audits->count() < 10)
        ) {
            $perPage = $audits->count();
        } elseif (!$request->get("per_page")) {
            $perPage = 10;
        }
        // return Datatables::of($audits)->make(true);
        // }

        $audits = $audits->paginate($perPage);

        // $audits = [];
        return view("user-trace.user-audits", compact('audits', 'request'));
    }

    public function viewAudit($id)
    {
        $audit = Audit::find($id);
        // dd($audit);
        return view("user-trace.view-user-audit", compact('audit'));
    }
}
