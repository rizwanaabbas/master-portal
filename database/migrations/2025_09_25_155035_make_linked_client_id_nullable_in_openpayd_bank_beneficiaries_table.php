<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeLinkedClientIdNullableInOpenpaydBankBeneficiariesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('openpayd_bank_beneficiaries', function (Blueprint $table) {
            //
            $table->uuid('linked_client_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('openpayd_bank_beneficiaries', function (Blueprint $table) {
            //
            $table->uuid('linked_client_id')->nullable(false)->change();

        });
    }
}
