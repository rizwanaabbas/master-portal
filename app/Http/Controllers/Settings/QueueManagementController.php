<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class QueueManagementController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', '2fa', 'can:settings']);
        $this->middleware('role:super admin');
    }

    public function index()
    {
        $queueDriver = config('queue.default');
        $hasJobsTable = Schema::hasTable('jobs');
        $hasFailedJobsTable = Schema::hasTable('failed_jobs');

        return view('settings.queue-management.index', compact('queueDriver', 'hasJobsTable', 'hasFailedJobsTable'));
    }

    public function pendingData(Request $request)
    {
        if (!Schema::hasTable('jobs')) {
            return DataTables::of(collect())->make(true);
        }

        $query = DB::table('jobs')->orderByDesc('id');

        return DataTables::of($query)
            ->addColumn('job_name', fn ($row) => $this->extractJobName($row->payload))
            ->addColumn('status', function ($row) {
                if (!empty($row->reserved_at)) {
                    return '<span class="badge badge-primary">Processing</span>';
                }

                return '<span class="badge badge-info">Queued</span>';
            })
            ->addColumn('available_at_fmt', fn ($row) => $this->formatTimestamp($row->available_at))
            ->addColumn('created_at_fmt', fn ($row) => $this->formatTimestamp($row->created_at))
            ->addColumn('actions', function ($row) {
                return '<button type="button" class="btn btn-sm btn-outline-danger btn-delete-job" data-id="' . (int) $row->id . '" title="Remove from queue"><i class="fal fa-trash"></i></button>';
            })
            ->rawColumns(['status', 'actions'])
            ->make(true);
    }

    public function failedData(Request $request)
    {
        if (!Schema::hasTable('failed_jobs')) {
            return DataTables::of(collect())->make(true);
        }

        $query = DB::table('failed_jobs')->orderByDesc('id');

        return DataTables::of($query)
            ->addColumn('job_name', fn ($row) => $this->extractJobName($row->payload))
            ->addColumn('exception_short', fn ($row) => e(Str::limit($row->exception, 120)))
            ->addColumn('failed_at_fmt', fn ($row) => e($row->failed_at))
            ->addColumn('actions', function ($row) {
                $uuid = e($row->uuid);
                $html = '<div class="btn-group btn-group-sm">';
                $html .= '<button type="button" class="btn btn-outline-success btn-retry-failed" data-uuid="' . $uuid . '" title="Retry"><i class="fal fa-redo"></i></button>';
                $html .= '<button type="button" class="btn btn-outline-danger btn-forget-failed" data-uuid="' . $uuid . '" title="Forget"><i class="fal fa-trash"></i></button>';
                $html .= '<button type="button" class="btn btn-outline-secondary btn-view-failed" data-id="' . (int) $row->id . '" title="View error"><i class="fal fa-eye"></i></button>';
                $html .= '</div>';

                return $html;
            })
            ->rawColumns(['actions', 'exception_short'])
            ->make(true);
    }

    public function showFailed(int $id)
    {
        if (!Schema::hasTable('failed_jobs')) {
            return response()->json(['success' => false, 'message' => 'Failed jobs table not available'], 404);
        }

        $job = DB::table('failed_jobs')->where('id', $id)->first();
        if (!$job) {
            return response()->json(['success' => false, 'message' => 'Job not found'], 404);
        }

        return response()->json([
            'success' => true,
            'job' => [
                'id' => $job->id,
                'uuid' => $job->uuid,
                'queue' => $job->queue,
                'job_name' => $this->extractJobName($job->payload),
                'payload' => json_decode($job->payload, true),
                'exception' => $job->exception,
                'failed_at' => $job->failed_at,
            ],
        ]);
    }

    public function retryFailed(Request $request)
    {
        $uuid = $request->input('uuid');
        if (!$uuid) {
            return response()->json(['success' => false, 'message' => 'Missing job UUID'], 422);
        }

        try {
            Artisan::call('queue:retry', ['id' => $uuid]);

            return response()->json([
                'success' => true,
                'message' => 'Failed job queued for retry',
                'output' => trim(Artisan::output()),
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function forgetFailed(Request $request)
    {
        $uuid = $request->input('uuid');
        if (!$uuid) {
            return response()->json(['success' => false, 'message' => 'Missing job UUID'], 422);
        }

        try {
            Artisan::call('queue:forget', ['id' => $uuid]);

            return response()->json([
                'success' => true,
                'message' => 'Failed job removed',
                'output' => trim(Artisan::output()),
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function deletePending(Request $request)
    {
        if (!Schema::hasTable('jobs')) {
            return response()->json(['success' => false, 'message' => 'Jobs table not available'], 404);
        }

        $id = (int) $request->input('id');
        $deleted = DB::table('jobs')->where('id', $id)->delete();

        return response()->json([
            'success' => (bool) $deleted,
            'message' => $deleted ? 'Pending job removed from queue' : 'Job not found',
        ]);
    }

    public function retryAllFailed()
    {
        try {
            Artisan::call('queue:retry', ['id' => 'all']);

            return response()->json([
                'success' => true,
                'message' => 'All failed jobs queued for retry',
                'output' => trim(Artisan::output()),
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    protected function extractJobName(string $payload): string
    {
        $decoded = json_decode($payload, true);
        if (!is_array($decoded)) {
            return 'Unknown job';
        }

        $command = $decoded['data']['command'] ?? null;
        if (!$command) {
            return e($decoded['displayName'] ?? 'Unknown job');
        }

        if (preg_match('/O:\d+:"([^"]+)"/', $command, $matches)) {
            $class = $matches[1];
            $parts = explode('\\', $class);

            return e(end($parts));
        }

        return e($decoded['displayName'] ?? 'Unknown job');
    }

    protected function formatTimestamp($timestamp): string
    {
        if (empty($timestamp)) {
            return '—';
        }

        return date('Y-m-d H:i:s', (int) $timestamp);
    }
}
