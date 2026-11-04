<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterUsersAddIsInternalTransferApprovalRequiredColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_internal_transfer_approval_required')->after('is_initial_review_required')->default(1);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop columns if they exist
            if (Schema::hasColumn('users', 'is_internal_transfer_approval_required')) {
                $table->dropColumn('is_internal_transfer_approval_required');
            }
        });
    }
}
