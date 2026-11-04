<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableWebhookIpWhitelists extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('webhook_ip_whitelists', function (Blueprint $table) {
        $table->id();

        $table->string('service', 50);
        $table->string('ip_address', 45);
        $table->string('whitelisted_for', 100);
        $table->string('description', 100)->nullable();
        $table->boolean('is_active')->default(true);

        $table->foreignId('created_by')->nullable()
            ->constrained('users')
            ->nullOnDelete();

        $table->foreignId('updated_by')->nullable()
            ->constrained('users')
            ->nullOnDelete();

        $table->timestamps();

        $table->unique(['service', 'ip_address']);
    });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('webhook_ip_whitelists');
    }
}
