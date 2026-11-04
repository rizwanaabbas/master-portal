<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClientBeneficiaries extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('client_beneficiaries', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->string('name');
            $table->string('account_number');
            $table->string('iban');
            $table->text('address')->nullable();
            $table->string('bank_name');
            $table->text('bank_address')->nullable();
            $table->string('swift_code')->nullable();
            $table->string('status');
            $table->datetime("created_at")->nullable();
            $table->datetime("updated_at")->nullable();
            $table->datetime("approved_at")->nullable();
            $table->datetime("rejected_at")->nullable();

            $table->integer("created_by")->nullable();
            $table->integer("updated_by")->nullable();
            $table->integer("approved_by")->nullable();
            $table->integer("rejected_by")->nullable();

            $table->text("reject_reason")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('client_beneficiaries');
    }
}
