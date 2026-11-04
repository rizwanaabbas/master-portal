<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
class CreateCryptoCurrencyNetworkAddressTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Schema::create('crypto_currency_network_address', function (Blueprint $table) {
        //     $table->id();
        //     $table->foreignId('currency_id')->constrained('currencies');
        //     $table->string('network', 10);
        //     $table->string('crypto_address', 255)->nullable();
        //     $table->timestamps();
        // });
         // Alter the existing table
         Schema::table('currencies', function (Blueprint $table) {
            //$table->dropColumn('bitpace_address');
        });
        // Modify the column 'destination_tag' to change its type
         // Add a new column 'crypto_address'
        Schema::table('crypto_addresses', function (Blueprint $table) {
            if (!Schema::hasColumn('crypto_addresses', 'destination_tag')) {
                $table->string('destination_tag', 100)->nullable()->charset('utf8mb4')->collation('utf8mb4_0900_ai_ci');
             }
        });
        Schema::table('crypto_addresses', function (Blueprint $table) {
            if (!Schema::hasColumn('crypto_addresses', 'crypto_address')) {
                $table->string('crypto_address', 100)->nullable()->after('destination_tag');
            }
        });
        // Schema::table('crypto_addresses', function (Blueprint $table) {
        //     $table->string('destination_tag', 100)->nullable()->charset('utf8mb4')->collation('utf8mb4_unicode_ci')->change();
        // });

       

        Schema::dropIfExists('crypto_currency_network_address');
        // Revert the column change for 'crypto_transaction_id'
        if (Schema::hasColumn('account_transactions', 'osl_transaction_id')) {
            DB::statement("ALTER TABLE account_transactions CHANGE  osl_transaction_id crypto_transaction_id varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL;");
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('crypto_currency_network_address');
         // Drop the 'crypto_address' column
         Schema::table('crypto_addresses', function (Blueprint $table) {
            $table->dropColumn('crypto_address');
        });

        // Modify the 'destination_tag' column back to its original type
        Schema::table('crypto_addresses', function (Blueprint $table) {
            $table->string('destination_tag')->nullable()->change();
        });
         // Add back the dropped column if needed in the down method
        //  Schema::table('currencies', function (Blueprint $table) {
        //     $table->string('bitpace_address')->nullable()->after('column_name');
        // });
    }
}
