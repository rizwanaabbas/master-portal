<?php

use App\Services\PermissionModuleGroupOrganizer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            'view fireblocks crypto networks',
            'view fireblocks beneficiaries',
            'view aquanow instruments',
            'view aquanow balances',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        if (Schema::hasColumn('permissions', 'module_group_id') && Schema::hasTable('permission_module_groups')) {
            PermissionModuleGroupOrganizer::syncGroupsFromModules();
            PermissionModuleGroupOrganizer::assignUngroupedPermissions();
        }

        $grantTo = Role::where('guard_name', 'web')
            ->where(function ($query) {
                $query->whereIn('name', ['super admin', 'Super Admin', 'super-admin'])
                    ->orWhereHas('permissions', function ($permissions) {
                        $permissions->where('name', 'crypto management');
                    });
            })
            ->get();

        foreach ($grantTo as $role) {
            foreach ($permissions as $name) {
                if (!$role->hasPermissionTo($name)) {
                    $role->givePermissionTo($name);
                }
            }
        }
    }

    public function down(): void
    {
        Permission::where('guard_name', 'web')
            ->whereIn('name', [
                'view fireblocks crypto networks',
                'view fireblocks beneficiaries',
                'view aquanow instruments',
                'view aquanow balances',
            ])
            ->delete();
    }
};
