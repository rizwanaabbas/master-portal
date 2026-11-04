<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDeviceNameToUserJourneyLogs extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user_journey_logs') && !Schema::hasColumn('user_journey_logs', 'device_name')) {
            Schema::table('user_journey_logs', function (Blueprint $table) {
                $table->string('device_name', 80)->nullable()->after('device_type');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('user_journey_logs', 'device_name')) {
            Schema::table('user_journey_logs', function (Blueprint $table) {
                $table->dropColumn('device_name');
            });
        }
    }
}
