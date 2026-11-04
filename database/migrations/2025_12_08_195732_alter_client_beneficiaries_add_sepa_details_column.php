<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterClientBeneficiariesAddSepaDetailsColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('client_beneficiaries', function (Blueprint $table) {
            $table->string('sort_code')->nullable()->default(null)->after('payment_type_id');
            $table->string('bank_postal_code')->nullable()->default(null)->after('payment_type_id');
            $table->string('bank_city')->nullable()->default(null)->after('payment_type_id');
            $table->string('beneficiary_type')->nullable()->default(null)->after('payment_type_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('client_beneficiaries', function (Blueprint $table) {
            $table->dropColumn('sort_code');
            $table->dropColumn('bank_postal_code');
            $table->dropColumn('bank_city');
            $table->dropColumn('beneficiary_type');
        });
    }
}
