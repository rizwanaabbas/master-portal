<?php

use App\Models\Module;
use App\Models\ModuleConfiguration;
use App\Modules\LiveTransactions\Support\LiveTransactionsModuleConfigDefinitions;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('live_transaction_feed', function (Blueprint $table) {
            if (!Schema::hasColumn('live_transaction_feed', 'is_crypto')) {
                $table->boolean('is_crypto')->default(false)->after('transaction_type')->index();
            }
            if (!Schema::hasColumn('live_transaction_feed', 'direction')) {
                $table->string('direction', 20)->default('internal')->after('is_crypto')->index();
            }
        });

        $module = Module::where('name', 'LiveTransactions')->first();
        if (!$module) {
            $module = new Module();
            $module->name = 'LiveTransactions';
            $module->is_enabled = 0;
            $module->save();
        }

        $configurations = LiveTransactionsModuleConfigDefinitions::all();
        $existingConfig = ModuleConfiguration::where('module_id', $module->id)->first();

        if ($existingConfig) {
            $existingConfigurations = unserialize($existingConfig->configurations);
            $existingConfig->configurations = serialize(
                LiveTransactionsModuleConfigDefinitions::mergeInto(is_array($existingConfigurations) ? $existingConfigurations : [])
            );
            $existingConfig->save();
        } else {
            $config = new ModuleConfiguration();
            $config->module_id = $module->id;
            $config->configurations = serialize($configurations);
            $config->save();
        }

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
        Schema::table('live_transaction_feed', function (Blueprint $table) {
            if (Schema::hasColumn('live_transaction_feed', 'direction')) {
                $table->dropColumn('direction');
            }
            if (Schema::hasColumn('live_transaction_feed', 'is_crypto')) {
                $table->dropColumn('is_crypto');
            }
        });

        $module = Module::where('name', 'LiveTransactions')->first();
        if ($module) {
            ModuleConfiguration::where('module_id', $module->id)->delete();
            $module->delete();
        }
    }
};
