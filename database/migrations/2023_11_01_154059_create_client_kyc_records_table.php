<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClientKycRecordsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('client_kyc_records', function (Blueprint $table) {
            $table->id();
            $table->integer("client_id");
            $table->text("passport_copy_url");
            $table->text("selfie_photo_url");
            $table->text("signature_photo_url")->nullable();
            $table->string("kyc_status")->nullable();
            $table->string("liveness_status")->nullable();
            $table->string("liveness_score")->nullable();
            $table->string("compliance_status")->nullable();
            $table->integer("reviewer_id")->nullable();
            $table->dateTime("reviewed_at")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('client_kyc_records');
    }
}
