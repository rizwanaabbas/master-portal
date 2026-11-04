<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('worldcheck_api_logs')) {
            return;
        }

        Schema::table('worldcheck_api_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('worldcheck_api_logs', 'client_id')) {
                $table->unsignedBigInteger('client_id')->nullable()->after('user_id')->index();
            }
            if (!Schema::hasColumn('worldcheck_api_logs', 'client_type')) {
                $table->string('client_type', 32)->nullable()->after('client_id');
            }
            if (!Schema::hasColumn('worldcheck_api_logs', 'client_name')) {
                $table->string('client_name', 255)->nullable()->after('client_type');
            }
            if (!Schema::hasColumn('worldcheck_api_logs', 'entity_label')) {
                $table->string('entity_label', 255)->nullable()->after('client_name');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('worldcheck_api_logs')) {
            return;
        }

        Schema::table('worldcheck_api_logs', function (Blueprint $table) {
            foreach (['client_id', 'client_type', 'client_name', 'entity_label'] as $col) {
                if (Schema::hasColumn('worldcheck_api_logs', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
