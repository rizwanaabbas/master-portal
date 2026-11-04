<?php

namespace App\Http\Controllers;

use App\Models\PermissionModuleGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::with('permissions')->orderBy('name')->get();
        $groups = collect();
        if (Schema::hasTable('permission_module_groups')) {
            $groups = PermissionModuleGroup::query()->orderBy('sort_order')->orderBy('name')->get();
        }

        $roleSections = [];
        foreach ($roles as $role) {
            $roleSections[$role->id] = $this->permissionSections($role, $groups);
        }

        return view('user-management.roles.list', compact('roles', 'roleSections'));
    }

    /**
     * @return array<int, array{key: string, name: string, permissions: \Illuminate\Support\Collection}>
     */
    protected function permissionSections(Role $role, $groups): array
    {
        $rolePermissions = $role->permissions;
        if ($rolePermissions->isEmpty()) {
            return [];
        }

        if ($groups->isEmpty()) {
            return [[
                'key' => 'all',
                'name' => 'Permissions',
                'permissions' => $rolePermissions->sortBy('name')->values(),
            ]];
        }

        $sections = [];
        $renderedIds = [];
        foreach ($groups as $group) {
            $items = $rolePermissions->filter(function ($permission) use ($group) {
                return (int) $permission->module_group_id === (int) $group->id;
            });
            if (strtolower((string) $group->slug) === 'general') {
                $items = $items->merge($rolePermissions->filter(function ($permission) {
                    return empty($permission->module_group_id);
                }));
            }
            $items = $items->unique('id')->sortBy('name')->values();
            if ($items->isEmpty()) {
                continue;
            }
            foreach ($items as $permission) {
                $renderedIds[] = (int) $permission->id;
            }
            $sections[] = [
                'key' => 'g' . $group->id,
                'name' => $group->name,
                'permissions' => $items,
            ];
        }

        $leftover = $rolePermissions->filter(function ($permission) use ($renderedIds) {
            return !in_array((int) $permission->id, $renderedIds, true);
        })->sortBy('name')->values();
        if ($leftover->isNotEmpty()) {
            $sections[] = [
                'key' => 'general',
                'name' => 'General',
                'permissions' => $leftover,
            ];
        }

        return $sections;
    }

    public function add()
    {
        return view('user-management.roles.new');
    }

    public function store(Request $request)
    {
        $role = new Role();

        $role->name = $request->get('name');
       // $role->status = true;

        $role->save();

        return redirect('/user-management/roles');
    }

    public function permissions($id)
    {
        $role = Role::findOrFail($id);
        $permissions = Permission::query()->orderBy('name')->get();
        $groups = collect();
        if (Schema::hasTable('permission_module_groups')) {
            $groups = PermissionModuleGroup::query()->orderBy('sort_order')->orderBy('name')->get();
        }

        $grouped = [];
        foreach ($groups as $group) {
            $grouped[$group->id] = [
                'name' => $group->name,
                'permissions' => $permissions->where('module_group_id', $group->id)->values(),
            ];
        }
        $ungrouped = $permissions->filter(function ($permission) {
            return empty($permission->module_group_id);
        })->values();
        if ($ungrouped->isNotEmpty()) {
            $grouped['general-fallback'] = [
                'name' => 'General',
                'permissions' => $ungrouped,
            ];
        }

        return view('user-management.roles.permissions', compact('permissions', 'role', 'grouped'));
    }

    public function storePermission(Request $request)
    {
        $role = Role::find ($request->get('role_id'));

        $role->syncPermissions($request->get('permissions'));

        return redirect('/user-management/roles')->with('success', 'Permissions modified successfully');

    }
}
