<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EnhanceUserJourneyLogsForAnalytics extends Migration
{
    public function up(): void
    {
        Schema::table('user_journey_logs', function (Blueprint $table) {
            $table->string('country', 2)->nullable()->after('ip_address');
            $table->string('region', 80)->nullable()->after('country');
            $table->string('city', 80)->nullable()->after('region');
            $table->decimal('latitude', 10, 7)->nullable()->after('city');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->string('device_type', 20)->nullable()->after('longitude');
            $table->string('device_name', 80)->nullable()->after('device_type');
            $table->string('browser', 40)->nullable()->after('device_name');
            $table->string('os', 40)->nullable()->after('browser');
            $table->string('referrer', 255)->nullable()->after('os');
            $table->string('page_title', 160)->nullable()->after('referrer');
            $table->string('channel', 20)->nullable()->after('page_title');
            $table->string('event_name', 40)->nullable()->after('channel');
            $table->string('screen_name', 80)->nullable()->after('event_name');

            $table->index(['created_at', 'country'], 'ujl_created_country_idx');
            $table->index(['user_id', 'created_at'], 'ujl_user_created_idx');
            $table->index(['route_path', 'created_at'], 'ujl_path_created_idx');
            $table->index(['device_type', 'created_at'], 'ujl_device_created_idx');
            $table->index(['channel', 'created_at'], 'ujl_channel_created_idx');
            $table->index(['event_name', 'created_at'], 'ujl_event_created_idx');
        });

        Schema::create('user_journey_heat_points', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('session_id')->index();
            $table->string('route_path', 255);
            $table->unsignedTinyInteger('x');
            $table->unsignedTinyInteger('y');
            $table->string('channel', 20)->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
            $table->index(['route_path', 'created_at'], 'ujh_path_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_journey_heat_points');

        Schema::table('user_journey_logs', function (Blueprint $table) {
            $table->dropIndex('ujl_created_country_idx');
            $table->dropIndex('ujl_user_created_idx');
            $table->dropIndex('ujl_path_created_idx');
            $table->dropIndex('ujl_device_created_idx');
            $table->dropIndex('ujl_channel_created_idx');
            $table->dropIndex('ujl_event_created_idx');
            $table->dropColumn([
                'country', 'region', 'city', 'latitude', 'longitude',
                'device_type', 'device_name', 'browser', 'os', 'referrer', 'page_title',
                'channel', 'event_name', 'screen_name',
            ]);
        });
    }
}
