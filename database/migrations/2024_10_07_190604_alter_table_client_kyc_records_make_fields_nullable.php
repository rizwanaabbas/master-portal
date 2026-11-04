<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableClientKycRecordsMakeFieldsNullable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('client_kyc_records', function (Blueprint $table) {
            $table->text('passport_copy_url')->nullable()->change();
            $table->text('selfie_photo_url')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('client_kyc_records', function (Blueprint $table) {
            $table->text('passport_copy_url')->change();
            $table->text('selfie_photo_url')->change();
        });
    }
}
