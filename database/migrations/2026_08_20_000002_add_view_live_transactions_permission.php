<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $permission = Permission::firstOrCreate(
            ['name' => 'view live transactions', 'guard_name' => 'web']
        );

        foreach (['super admin', 'Super Admin', 'super-admin'] as $roleName) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
            if ($role && !$role->hasPermissionTo($permission)) {
                $role->givePermissionTo($permission);
            }
        }
    }

    public function down(): void
    {
        Permission::where('name', 'view live transactions')->where('guard_name', 'web')->delete();
    }
};
