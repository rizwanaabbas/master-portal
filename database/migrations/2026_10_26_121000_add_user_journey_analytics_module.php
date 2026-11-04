<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddUserJourneyAnalyticsModule extends Migration
{
    public function up(): void
    {
        DB::table('modules')->insertOrIgnore([
            'name' => 'UserJourneyAnalytics',
            'is_enabled' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('modules')->where('name', 'UserJourneyAnalytics')->delete();
    }
}
