<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fireblocks_users', function (Blueprint $table) {
            $table->id();
            $table->string('fireblocks_user_id', 64)->unique();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->nullable()->index();
            $table->string('role', 64)->nullable()->index();
            $table->boolean('enabled')->nullable();
            $table->json('raw_response')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });

        Schema::table('fireblocks_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('fireblocks_transactions', 'initiator_user_id')) {
                $table->string('initiator_user_id', 64)->nullable()->index();
            }
            if (!Schema::hasColumn('fireblocks_transactions', 'signed_by_user_ids')) {
                $table->json('signed_by_user_ids')->nullable();
            }
            if (!Schema::hasColumn('fireblocks_transactions', 'tap_approver_user_ids')) {
                $table->json('tap_approver_user_ids')->nullable();
            }
        });

        $permissions = [
            'view fireblocks users',
            'manage fireblocks users',
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
        Schema::table('fireblocks_transactions', function (Blueprint $table) {
            foreach (['initiator_user_id', 'signed_by_user_ids', 'tap_approver_user_ids'] as $column) {
                if (Schema::hasColumn('fireblocks_transactions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::dropIfExists('fireblocks_users');

        Permission::where('guard_name', 'web')
            ->whereIn('name', [
                'view fireblocks users',
                'manage fireblocks users',
            ])
            ->delete();
    }
};
