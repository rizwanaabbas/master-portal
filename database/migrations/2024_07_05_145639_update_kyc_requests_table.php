<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateKycRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('kyc_requests', function (Blueprint $table) {
            if (Schema::hasColumn('kyc_requests', 'person_name')) {
                $table->dropColumn('person_name');
            }
            if (Schema::hasColumn('kyc_requests', 'email')) {
                $table->dropColumn('email');
            }
            $table->unsignedBigInteger('director_id')->nullable()->after('client_id');
            //$table->string('status')->default('pending')->nullable()->after('director_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('kyc_requests', function (Blueprint $table) {
            $table->string('person_name')->nullable();
            $table->string('email')->nullable();

            $table->dropColumn('director_id');
            //$table->dropColumn('status');
        });
    }
}
