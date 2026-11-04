<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        //company
        DB::table('client_files_config')
            ->where('title', 'Certificate of Incorporation')->where('type','company')
            ->update(['identifier' => 'certificate_of_incorporation']);

        DB::table('client_files_config')
            ->where('title', "Memorandum and Articles of Association (Certify by company's secretary)")->where('type','company')
            ->update(['identifier' => 'memorandum_of_association']);
        
        DB::table('client_files_config')
            ->where('title', "3 months Company Bank Statements up to current month")->where('type','company')
            ->update(['identifier' => 'bank_statement']);
        
        //individual
        DB::table('client_files_config')
            ->where('title', "6 months Bank Statement (up to current month)")->where('type','individual')
            ->update(['identifier' => 'bank_statement']);
        
        DB::table('client_files_config')
            ->where('title', "Latest Utility Bill showing current residential address")->where('type','individual')
            ->update(['identifier' => 'utility_bill']);
        
        
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // If you want to restore previous values, you can set them back here.
        // For now, just leave blank unless you know the old values.

       
    }
};
