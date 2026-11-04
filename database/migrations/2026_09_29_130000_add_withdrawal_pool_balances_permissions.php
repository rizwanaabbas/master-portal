<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            'view fireblocks withdrawal pool balances',
            'manage fireblocks withdrawal pool balances',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $superAdminRoleNames = ['super admin', 'Super Admin', 'super-admin'];

        foreach ($superAdminRoleNames as $roleName) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
            if (!$role) {
                continue;
            }

            foreach ($permissions as $name) {
                if (!$role->hasPermissionTo($name)) {
                    $role->givePermissionTo($name);
                }
            }
        }

        $custodyRoles = Role::where('guard_name', 'web')
            ->whereHas('permissions', fn ($q) => $q->where('name', 'fireblocks custody'))
            ->get();

        foreach ($custodyRoles as $role) {
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
                'view fireblocks withdrawal pool balances',
                'manage fireblocks withdrawal pool balances',
            ])
            ->delete();
    }
};
