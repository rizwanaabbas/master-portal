<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateKycRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('kyc_requests', function (Blueprint $table) {
            $table->id();
            $table->string('person_name')->nullable();
            $table->string('email')->nullable();
            $table->string('hash')->nullable();
            $table->integer('client_id')->nullable();
            $table->integer('created_by');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->timestamp('expiry')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('kyc_requests');
    }
}
