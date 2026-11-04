<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('scheduler_execution_logs')) {
            return;
        }

        Schema::create('scheduler_execution_logs', function (Blueprint $table) {
            $table->id();
            $table->string('command')->index();
            $table->string('description')->nullable();
            $table->string('schedule_expression')->nullable();
            $table->string('status', 32)->default('running')->index();
            $table->text('output')->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->timestamp('started_at')->nullable()->index();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduler_execution_logs');
    }
};
