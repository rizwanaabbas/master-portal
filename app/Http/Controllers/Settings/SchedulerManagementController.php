<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\SchedulerExecutionLog;
use App\Services\ScheduledTaskCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class SchedulerManagementController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', '2fa', 'can:settings']);
        $this->middleware('role:super admin');
    }

    public function index()
    {
        $tasks = ScheduledTaskCatalog::tasks();
        $environment = env('APP_ENV');

        return view('settings.scheduler-management.index', compact('tasks', 'environment'));
    }

    public function logsData(Request $request)
    {
        $query = SchedulerExecutionLog::query()->orderByDesc('started_at');

        if ($request->filled('command') && $request->command !== 'all') {
            $query->where('command', 'like', '%' . $request->command . '%');
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        return DataTables::of($query)
            ->addColumn('status_badge', function ($row) {
                $map = [
                    'running' => 'primary',
                    'success' => 'success',
                    'failed' => 'danger',
                ];
                $class = $map[$row->status] ?? 'secondary';

                return '<span class="badge badge-' . $class . '">' . e(ucfirst($row->status)) . '</span>';
            })
            ->addColumn('started_at_fmt', fn ($row) => $row->started_at?->format('Y-m-d H:i:s') ?? '—')
            ->addColumn('finished_at_fmt', fn ($row) => $row->finished_at?->format('Y-m-d H:i:s') ?? '—')
            ->addColumn('duration_fmt', fn ($row) => $row->duration_ms !== null ? $row->duration_ms . ' ms' : '—')
            ->addColumn('error_short', fn ($row) => $row->error_message ? e(Str::limit($row->error_message, 100)) : '—')
            ->addColumn('actions', function ($row) {
                if (empty($row->error_message) && empty($row->output)) {
                    return '<span class="text-muted">—</span>';
                }

                return '<button type="button" class="btn btn-sm btn-outline-secondary btn-view-log" data-id="' . (int) $row->id . '"><i class="fal fa-eye"></i></button>';
            })
            ->rawColumns(['status_badge', 'actions', 'error_short'])
            ->make(true);
    }

    public function showLog(int $id)
    {
        $log = SchedulerExecutionLog::findOrFail($id);

        return response()->json([
            'success' => true,
            'log' => $log,
        ]);
    }

    public function runNow(Request $request)
    {
        $command = $request->input('command');
        $allowed = collect(ScheduledTaskCatalog::tasks())->pluck('command')->all();

        if (!$command || !in_array($command, $allowed, true)) {
            return response()->json(['success' => false, 'message' => 'Command not allowed'], 422);
        }

        $log = SchedulerExecutionLog::create([
            'command' => $command,
            'description' => 'Manual run from scheduler management',
            'status' => 'running',
            'started_at' => now(),
        ]);

        try {
            Artisan::call($command);
            $output = trim(Artisan::output());

            $log->update([
                'status' => 'success',
                'output' => $output,
                'finished_at' => now(),
                'duration_ms' => $log->started_at ? (int) $log->started_at->diffInMilliseconds(now()) : null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Command executed successfully',
                'output' => $output,
            ]);
        } catch (\Throwable $e) {
            $log->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'finished_at' => now(),
                'duration_ms' => $log->started_at ? (int) $log->started_at->diffInMilliseconds(now()) : null,
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
