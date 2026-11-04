<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            'fireblocks custody',
            'view fireblocks vault sweeping',
            'manage fireblocks vault sweeping',
            'view fireblocks transaction monitoring',
            'manage fireblocks transaction monitoring',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        foreach (['super admin', 'Super Admin', 'super-admin'] as $roleName) {
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
    }

    public function down(): void
    {
        Permission::where('guard_name', 'web')
            ->whereIn('name', [
                'fireblocks custody',
                'view fireblocks vault sweeping',
                'manage fireblocks vault sweeping',
                'view fireblocks transaction monitoring',
                'manage fireblocks transaction monitoring',
            ])
            ->delete();
    }
};
