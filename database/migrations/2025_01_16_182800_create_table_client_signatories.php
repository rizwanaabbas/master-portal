<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableClientSignatories extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('client_signatories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('passport_number')->nullable();
            $table->date('passport_expiry')->nullable();
            $table->date('dob')->nullable();
            $table->string('gender')->nullable();
            $table->bigInteger('client_id')->nullable();
            $table->bigInteger('user_id')->nullable();
            $table->string('sanctions_reports')->nullable();
            $table->bigInteger('created_by')->nullable();
            $table->boolean('kyc_status')->default(0)->nullable();
            $table->bigInteger('nationality_id')->nullable();
            $table->integer('residence_country_id')->nullable();
            $table->bigInteger('updated_by')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->boolean('is_deleted')->default(0)->nullable();
            // $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('client_signatories');
    }
}
