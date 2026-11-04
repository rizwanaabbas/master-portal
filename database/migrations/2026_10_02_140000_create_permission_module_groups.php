<?php

use App\Services\PermissionModuleGroupOrganizer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('permission_module_groups')) {
            Schema::create('permission_module_groups', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (Schema::hasTable('permissions') && !Schema::hasColumn('permissions', 'module_group_id')) {
            Schema::table('permissions', function (Blueprint $table) {
                $table->unsignedBigInteger('module_group_id')->nullable()->after('guard_name');
                $table->index('module_group_id');
            });
        }

        PermissionModuleGroupOrganizer::syncGroupsFromModules();
        PermissionModuleGroupOrganizer::assignUngroupedPermissions();
    }

    public function down(): void
    {
        if (Schema::hasTable('permissions') && Schema::hasColumn('permissions', 'module_group_id')) {
            Schema::table('permissions', function (Blueprint $table) {
                $table->dropIndex(['module_group_id']);
                $table->dropColumn('module_group_id');
            });
        }
        Schema::dropIfExists('permission_module_groups');
    }
};
