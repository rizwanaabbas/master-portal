<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterDbAndClientTablesCharsetAndCollation extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Change character set and collation of the entire database
        DB::statement('ALTER DATABASE ' . env('DB_DATABASE') . ' CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci');

        // Change character set and collation of specific tables
        DB::statement('ALTER TABLE account_transaction_requests CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci');
        DB::statement('ALTER TABLE account_transaction_cheques CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci');
        DB::statement('ALTER TABLE account_transactions CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci');
        DB::statement('ALTER TABLE accounts CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci');
        DB::statement('ALTER TABLE assigned_cards CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci');
        DB::statement('ALTER TABLE card_details CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci');
        DB::statement('ALTER TABLE client_beneficiaries CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci');
        DB::statement('ALTER TABLE client_companies CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci');
        DB::statement('ALTER TABLE client_company_contact_person CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci');
        DB::statement('ALTER TABLE client_draft_currencies CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci');
        DB::statement('ALTER TABLE client_files_config CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci');
        DB::statement('ALTER TABLE client_help_requests CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci');
        DB::statement('ALTER TABLE client_individual_files CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci');
        DB::statement('ALTER TABLE client_individuals CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci');
        DB::statement('ALTER TABLE client_individuals_employer_details CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci');
        DB::statement('ALTER TABLE client_kyc_records CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci');
        DB::statement('ALTER TABLE clients CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci');
        DB::statement('ALTER TABLE user_card_links CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci');
        DB::statement('ALTER TABLE users CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
