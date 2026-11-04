<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableUserJourneyLogs extends Migration
{
    public function up(): void
    {
        Schema::create('user_journey_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('session_id')->index();
            $table->string('route_path');
            $table->string('method', 10);
            $table->string('user_agent')->nullable();
            $table->ipAddress('ip_address');
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_journey_logs');
    }
};
