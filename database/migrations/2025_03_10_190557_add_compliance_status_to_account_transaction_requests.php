<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('account_transaction_requests', function (Blueprint $table) {
            $table->string('compliance_status')->nullable()->after('is_exchange');
        });
    }

    public function down()
    {
        Schema::table('account_transaction_requests', function (Blueprint $table) {
            $table->dropColumn('compliance_status');
        });
    }
};

