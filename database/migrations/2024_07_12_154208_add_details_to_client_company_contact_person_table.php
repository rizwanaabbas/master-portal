<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDetailsToClientCompanyContactPersonTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('client_company_contact_person', function (Blueprint $table) {
            $table->string('passport_number')->nullable()->after('designation');
            $table->date('passport_expiry')->nullable()->after('passport_number');
            $table->date('dob')->nullable()->after('passport_expiry');
            $table->string('gender',6)->nullable()->after('dob');
            $table->unsignedBigInteger('nationality_id')->nullable()->after('gender');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('client_company_contact_person', function (Blueprint $table) {
            $table->dropColumn(['passport_number', 'passport_expiry', 'dob', 'gender', 'nationality_id']);
        });
    }
}

