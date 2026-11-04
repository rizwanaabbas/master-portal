<?php

namespace App\Http\Controllers;

use App\Models\WorldCheckApiLog;
use App\Services\WorldCheckConfigService;
use Illuminate\Http\Request;

class WorldCheckApiLogController extends Controller
{
    public function index(Request $request)
    {
        $query = WorldCheckApiLog::query()
            ->with(['user:id,name,email', 'client:id,name,type'])
            ->orderByDesc('id');

        if ($search = trim((string) $request->get('search_string'))) {
            $query->where(function ($q) use ($search) {
                $q->where('endpoint', 'LIKE', '%' . $search . '%')
                    ->orWhere('url', 'LIKE', '%' . $search . '%')
                    ->orWhere('http_status', 'LIKE', '%' . $search . '%')
                    ->orWhere('method', 'LIKE', '%' . $search . '%');
            });
        }

        if ($endpoint = trim((string) $request->get('endpoint'))) {
            $query->where('endpoint', 'LIKE', $endpoint . '%');
        }

        if ($status = $request->get('http_status')) {
            $query->where('http_status', (int) $status);
        }

        $perPage = (int) ($request->get('per_page') ?: 25);
        if ($perPage < 1) {
            $perPage = 25;
        }
        if ($perPage > 200) {
            $perPage = 200;
        }

        $logs = $query->paginate($perPage)->appends($request->query());
        $worldCheckEnabled = WorldCheckConfigService::isEnabled();

        return view('compliance-management.worldcheck-api-logs.index', compact('logs', 'request', 'worldCheckEnabled'));
    }

    public function show($id)
    {
        $log = WorldCheckApiLog::with(['user:id,name,email', 'client:id,name,type'])->findOrFail($id);
        $worldCheckEnabled = WorldCheckConfigService::isEnabled();

        return view('compliance-management.worldcheck-api-logs.show', compact('log', 'worldCheckEnabled'));
    }
}
