<?php

namespace App\Http\Controllers;

use App\Models\PermissionModuleGroup;
use App\Services\PermissionModuleGroupOrganizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Yajra\DataTables\DataTables;

class PermissionController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:add permission', ['only' => ['add', 'store', 'storeGroup']]);
        $this->middleware('permission:edit permission|update permission', ['only' => ['edit', 'update', 'updateGroup']]);
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Permission::query();
            if (Schema::hasColumn('permissions', 'module_group_id')) {
                $query->leftJoin('permission_module_groups', 'permission_module_groups.id', '=', 'permissions.module_group_id')
                    ->select('permissions.*', 'permission_module_groups.name as module_group_name');
            }

            $guard = trim((string) $request->input('filter_guard', ''));
            if ($guard !== '' && $guard !== 'all') {
                $query->where('permissions.guard_name', $guard);
            }

            $groupId = trim((string) $request->input('filter_group', ''));
            if ($groupId !== '' && $groupId !== 'all' && Schema::hasColumn('permissions', 'module_group_id')) {
                $query->where('permissions.module_group_id', (int) $groupId);
            }

            $canEdit = auth()->user()->can('edit permission') || auth()->user()->can('update permission');

            $dataTable = DataTables::of($query)
                ->filter(function ($q) use ($request) {
                    $search = trim((string) $request->input('search.value'));
                    if ($search === '') {
                        return;
                    }
                    $q->where(function ($inner) use ($search) {
                        $inner->where('permissions.id', 'like', "%{$search}%")
                            ->orWhere('permissions.name', 'like', "%{$search}%")
                            ->orWhere('permissions.guard_name', 'like', "%{$search}%");
                        if (Schema::hasColumn('permissions', 'module_group_id')) {
                            $inner->orWhere('permission_module_groups.name', 'like', "%{$search}%");
                        }
                    });
                }, false)
                ->addColumn('edit_action', function ($permission) use ($canEdit) {
                    if (!$canEdit) {
                        return '';
                    }

                    return '<button type="button" class="btn btn-link p-0 btn-edit-permission" title="Edit"'
                        . ' data-id="' . (int) $permission->id . '"'
                        . ' data-name="' . e($permission->name) . '"'
                        . ' data-group-id="' . e($permission->module_group_id ?? '') . '">'
                        . '<i class="fal fa-edit"></i></button>';
                })
                ->addColumn('module_group_label', function ($permission) {
                    return e($permission->module_group_name ?? 'General');
                })
                ->addColumn('created_fmt', function ($permission) {
                    return $permission->created_at ? $permission->created_at->format('Y-m-d H:i') : '—';
                })
                ->rawColumns(['edit_action', 'module_group_label']);

            $dataTable->orderColumn('id', function ($query, $order) {
                $query->reorder()->orderBy('permissions.id', $order);
            });
            $dataTable->orderColumn('name', function ($query, $order) {
                $query->reorder()->orderBy('permissions.name', $order);
            });
            $dataTable->orderColumn('module_group_name', function ($query, $order) {
                $query->reorder()->orderBy('permission_module_groups.name', $order);
            });
            $dataTable->orderColumn('guard_name', function ($query, $order) {
                $query->reorder()->orderBy('permissions.guard_name', $order);
            });
            $dataTable->orderColumn('created_at', function ($query, $order) {
                $query->reorder()->orderBy('permissions.created_at', $order);
            });

            return $dataTable->make(true);
        }

        PermissionModuleGroupOrganizer::syncGroupsFromModules();
        PermissionModuleGroupOrganizer::assignUngroupedPermissions();

        $guards = Permission::query()->select('guard_name')->distinct()->orderBy('guard_name')->pluck('guard_name');
        $moduleGroups = PermissionModuleGroup::query()->orderBy('sort_order')->orderBy('name')->get();

        return view('user-management.permissions.list', compact('guards', 'moduleGroups'));
    }

    public function add()
    {
        return redirect('/user-management/permissions');
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
            'module_group_id' => 'nullable|integer',
        ]);

        $groupId = $this->resolveGroupId($request->input('module_group_id'));
        $permissions = preg_split('/\s*,\s*/', (string) $request->get('name'), -1, PREG_SPLIT_NO_EMPTY);
        $created = 0;
        foreach ($permissions as $permissionName) {
            $row = Permission::firstOrCreate([
                'name' => strtolower(trim($permissionName)),
                'guard_name' => 'web',
            ]);
            if ($row->wasRecentlyCreated) {
                $created++;
            }
            if (Schema::hasColumn('permissions', 'module_group_id') && empty($row->module_group_id)) {
                $row->module_group_id = $groupId;
                $row->save();
            } elseif (Schema::hasColumn('permissions', 'module_group_id') && $groupId) {
                $row->module_group_id = $groupId;
                $row->save();
            }
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $created > 0 ? 'Permission added successfully' : 'Permission saved',
            ]);
        }

        return redirect('/user-management/permissions')->with('success', 'Permission added successfully');
    }

    public function edit($id)
    {
        return redirect('/user-management/permissions');
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'id' => 'required',
            'name' => 'required|unique:permissions,name,' . $request->get('id'),
            'module_group_id' => 'nullable|integer',
        ]);

        $permission = Permission::findOrFail($request->get('id'));
        $permission->name = strtolower(trim((string) $request->get('name')));
        $permission->guard_name = $permission->guard_name ?: 'web';
        if (Schema::hasColumn('permissions', 'module_group_id')) {
            $permission->module_group_id = $this->resolveGroupId($request->input('module_group_id'));
        }
        $permission->save();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Permission updated successfully',
            ]);
        }

        return redirect('/user-management/permissions')->with('success', 'Permission updated successfully');
    }

    public function storeGroup(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|string|max:100',
        ]);

        $name = trim((string) $request->get('name'));
        $group = PermissionModuleGroup::firstOrCreate(
            ['slug' => Str::slug($name)],
            ['name' => $name, 'sort_order' => ((int) PermissionModuleGroup::max('sort_order')) + 10]
        );
        if (!$group->wasRecentlyCreated) {
            $group->name = $name;
            $group->save();
        }

        return response()->json(['success' => true, 'message' => 'Module group saved', 'group' => $group]);
    }

    public function updateGroup(Request $request)
    {
        $this->validate($request, [
            'id' => 'required|integer',
            'name' => 'required|string|max:100',
        ]);

        $group = PermissionModuleGroup::findOrFail($request->get('id'));
        if ($group->slug === 'general') {
            $group->name = 'General';
        } else {
            $group->name = trim((string) $request->get('name'));
            $group->slug = Str::slug($group->name);
        }
        $group->save();

        return response()->json(['success' => true, 'message' => 'Module group updated', 'group' => $group]);
    }

    protected function resolveGroupId($value): ?int
    {
        $id = (int) $value;
        if ($id > 0 && PermissionModuleGroup::query()->where('id', $id)->exists()) {
            return $id;
        }
        $general = PermissionModuleGroup::general();

        return $general ? (int) $general->id : null;
    }
}
