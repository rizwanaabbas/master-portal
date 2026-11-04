<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            'view transaction monitoring currency rates',
            'view transaction monitoring fireblocks balances',
            'view transaction monitoring aquanow balances',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $grantTo = Role::where('guard_name', 'web')
            ->where(function ($q) {
                $q->whereIn('name', ['super admin', 'Super Admin', 'super-admin'])
                    ->orWhereHas('permissions', function ($p) {
                        $p->whereIn('name', [
                            'view fireblocks transaction monitoring',
                            'fireblocks custody',
                        ]);
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
                'view transaction monitoring currency rates',
                'view transaction monitoring fireblocks balances',
                'view transaction monitoring aquanow balances',
            ])
            ->delete();
    }
};
