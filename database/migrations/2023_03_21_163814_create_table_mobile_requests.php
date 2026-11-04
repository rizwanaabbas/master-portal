<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableMobileRequests extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mobile_requests', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id'); // id of the user who is making the request
            $table->string('type'); // APPLY_CARD, APPLY_ACCOUNT, APPLY_CRYPTO, CLOSE_ACCOUNT
            $table->string('status'); //Pending, Approved, Rejected
            $table->integer('approved_by'); // user id who is approving the request
            $table->dateTime('approved_at'); // approval date
            $table->integer('rejected_by'); // user id who is rejecting the request
            $table->dateTime('rejected_at'); // rejection date
            $table->text('reject_reason'); // reject reason
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
        Schema::dropIfExists('mobile_requests');
    }
}
