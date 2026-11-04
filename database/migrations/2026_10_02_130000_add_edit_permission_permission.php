<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        Permission::firstOrCreate(['name' => 'edit permission', 'guard_name' => 'web']);

        $roles = Role::where('guard_name', 'web')
            ->where(function ($q) {
                $q->whereIn('name', ['super admin', 'Super Admin', 'super-admin'])
                    ->orWhereHas('permissions', fn ($p) => $p->where('name', 'update permission'));
            })
            ->get();

        foreach ($roles as $role) {
            if (!$role->hasPermissionTo('edit permission')) {
                $role->givePermissionTo('edit permission');
            }
        }
    }

    public function down(): void
    {
        Permission::where('name', 'edit permission')->where('guard_name', 'web')->delete();
    }
};
