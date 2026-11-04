<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('worldcheck_api_logs')) {
            Schema::create('worldcheck_api_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('method', 16)->default('POST')->index();
                $table->string('endpoint', 255)->index();
                $table->string('url', 500)->nullable();
                $table->unsignedSmallInteger('http_status')->nullable()->index();
                $table->unsignedInteger('duration_ms')->nullable();
                $table->unsignedInteger('attempt')->default(0);
                $table->longText('request_payload')->nullable();
                $table->longText('request_headers')->nullable();
                $table->longText('response_body')->nullable();
                $table->longText('response_headers')->nullable();
                $table->string('error_message', 500)->nullable();
                $table->timestamps();

                $table->index('created_at');
            });
        }

        $permissions = [
            'view worldcheck api logs',
            'view worldcheck api log detail',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $roles = Role::where('guard_name', 'web')
            ->where(function ($query) {
                $query->whereIn('name', ['super admin', 'Super Admin', 'super-admin']);
            })
            ->get();

        foreach ($roles as $role) {
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
                'view worldcheck api logs',
                'view worldcheck api log detail',
            ])
            ->delete();

        Schema::dropIfExists('worldcheck_api_logs');
    }
};
