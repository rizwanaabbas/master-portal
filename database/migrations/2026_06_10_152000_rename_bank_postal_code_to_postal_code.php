<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameBankPostalCodeToPostalCode extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('client_beneficiaries', function (Blueprint $table) {
            $table->renameColumn('bank_postal_code', 'postal_code');
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
            $table->renameColumn('postal_code', 'bank_postal_code');
        });
    }
}
