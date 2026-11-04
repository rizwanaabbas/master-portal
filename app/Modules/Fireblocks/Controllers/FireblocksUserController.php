<?php

namespace App\Modules\Fireblocks\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Fireblocks\Models\FireblocksUser;
use App\Modules\Fireblocks\Services\FireblocksUserSyncService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class FireblocksUserController extends Controller
{
    public function __construct(protected FireblocksUserSyncService $syncService)
    {
        $this->middleware(['auth', '2fa']);
        $this->middleware('permission:view fireblocks users');
    }

    public function page()
    {
        $roles = FireblocksUser::query()
            ->whereNotNull('role')
            ->where('role', '!=', '')
            ->distinct()
            ->orderBy('role')
            ->pluck('role');

        $lastSynced = FireblocksUser::query()->max('last_synced_at');

        return view('fireblocks::users', compact('roles', 'lastSynced'));
    }

    public function data(Request $request)
    {
        $query = FireblocksUser::query();
        $role = trim((string) $request->input('role', ''));
        if ($role !== '') {
            $query->where('role', $role);
        }

        return DataTables::of($query)
            ->filter(function (Builder $q) use ($request) {
                $search = trim((string) $request->input('search.value', ''));
                if ($search === '') {
                    return;
                }
                $q->where(function (Builder $inner) use ($search) {
                    $inner->where('fireblocks_user_id', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('role', 'like', "%{$search}%");
                });
            }, true)
            ->addColumn('name', function (FireblocksUser $row) {
                return $row->badgeHtml();
            })
            ->addColumn('color_swatch', function (FireblocksUser $row) {
                $bg = $row->resolvedBadgeColor();

                return '<span class="d-inline-block rounded" style="width:18px;height:18px;background:' . e($bg) . ';border:1px solid #ccc;vertical-align:middle;"></span> <small>' . e($bg) . '</small>';
            })
            ->editColumn('role', fn (FireblocksUser $row) => e($row->role ?: '—'))
            ->editColumn('fireblocks_user_id', fn (FireblocksUser $row) => '<code>' . e($row->fireblocks_user_id) . '</code>')
            ->addColumn('actions', function (FireblocksUser $row) {
                return '<button type="button" class="btn btn-sm btn-outline-primary btn-edit-user" data-id="'
                    . (int) $row->id . '" title="Edit"><i class="fal fa-edit"></i></button>';
            })
            ->orderColumn('name', 'first_name $1')
            ->rawColumns(['name', 'color_swatch', 'fireblocks_user_id', 'actions'])
            ->make(true);
    }

    public function show($id)
    {
        $user = FireblocksUser::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'fireblocks_user_id' => $user->fireblocks_user_id,
                'name' => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')),
                'role' => $user->role,
                'badge_color' => $user->resolvedBadgeColor(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatedUser($request);

        $user = FireblocksUser::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Fireblocks user saved.',
            'id' => $user->id,
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = FireblocksUser::findOrFail($id);
        $data = $this->validatedUser($request, $user->id);
        $user->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Fireblocks user updated.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function validatedUser(Request $request, ?int $ignoreId = null): array
    {
        $unique = 'unique:fireblocks_users,fireblocks_user_id';
        if ($ignoreId) {
            $unique .= ',' . $ignoreId;
        }

        $validated = $request->validate([
            'fireblocks_user_id' => ['required', 'string', 'max:64', $unique],
            'name' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', 'string', 'max:64'],
            'badge_color' => ['nullable', 'string', 'max:7'],
        ]);

        $name = trim((string) ($validated['name'] ?? ''));
        $first = $name !== '' ? $name : null;
        $last = null;
        if ($name !== '' && strpos($name, ' ') !== false) {
            $parts = preg_split('/\s+/', $name, 2);
            $first = $parts[0] ?? $name;
            $last = $parts[1] ?? null;
        }

        $color = strtoupper(trim((string) ($validated['badge_color'] ?? '')));
        if (!preg_match('/^#[0-9A-F]{6}$/', $color)) {
            $color = '#6C757D';
        }

        return [
            'fireblocks_user_id' => trim($validated['fireblocks_user_id']),
            'first_name' => $first !== '' ? $first : null,
            'last_name' => $last !== '' ? $last : null,
            'role' => trim((string) ($validated['role'] ?? '')) ?: null,
            'badge_color' => $color,
        ];
    }

    public function sync()
    {
        set_time_limit(120);

        try {
            $result = $this->syncService->sync();
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'synced' => 0,
                'message' => 'Fireblocks user sync failed. Check logs. Admin API permission is required for GET /v1/users.',
            ], 500);
        }

        return response()->json($result, !empty($result['success']) ? 200 : 422);
    }
}
