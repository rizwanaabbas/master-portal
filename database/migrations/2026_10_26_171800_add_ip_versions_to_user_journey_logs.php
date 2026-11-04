<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIpVersionsToUserJourneyLogs extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('user_journey_logs')) {
            return;
        }

        Schema::table('user_journey_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('user_journey_logs', 'ip_v4')) {
                $table->string('ip_v4', 45)->nullable()->after('ip_address');
            }
            if (!Schema::hasColumn('user_journey_logs', 'ip_v6')) {
                $table->string('ip_v6', 45)->nullable()->after('ip_v4');
            }
            if (!Schema::hasColumn('user_journey_logs', 'ip_vpn')) {
                $table->string('ip_vpn', 191)->nullable()->after('ip_v6');
            }
            if (!Schema::hasColumn('user_journey_logs', 'ip_forwarded')) {
                $table->string('ip_forwarded', 255)->nullable()->after('ip_vpn');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('user_journey_logs')) {
            return;
        }

        Schema::table('user_journey_logs', function (Blueprint $table) {
            foreach (['ip_v4', 'ip_v6', 'ip_vpn', 'ip_forwarded'] as $column) {
                if (Schema::hasColumn('user_journey_logs', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
}
