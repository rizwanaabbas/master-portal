<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMobileRequestIdToCardDetailsTable extends Migration
{
    public function up()
    {
        Schema::table('card_details', function (Blueprint $table) {
            $table->unsignedBigInteger('mobile_request_id')->nullable()->after('encrypted_cvv2');

            // Assuming mobile_requests.id exists
            //$table->foreign('mobile_request_id')->references('id')->on('mobile_requests')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('card_details', function (Blueprint $table) {
            //$table->dropForeign(['mobile_request_id']);
            $table->dropColumn('mobile_request_id');
        });
    }
}

