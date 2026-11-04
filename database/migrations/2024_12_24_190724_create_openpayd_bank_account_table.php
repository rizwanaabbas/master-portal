<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOpenpaydBankAccountTable extends Migration
{
    public function up()
    {
        Schema::create('openpayd_bank_account', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('openpayd_linked_client_id')->nullable();
            $table->string('currency');
            $table->string('status');
            $table->string('internal_account_id');
            $table->string('bank_country');
            $table->text('bank_address');
            $table->string('iban');
            $table->string('bic');
            $table->string('account_number');
            $table->string('bank_name');
            $table->string('bank_account_holder_name');
            $table->string('provider');
            $table->string('payment_type')->nullable();
            $table->boolean('domestic');
            $table->json('routing_code_entries')->nullable();
            $table->string('pay_in_reference')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('openpayd_bank_account');
    }
}
