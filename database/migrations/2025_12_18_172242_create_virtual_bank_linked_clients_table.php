<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('virtual_bank_linked_clients', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('legacy_id')->nullable();
            $table->string('provider_name', 50)->index();

            $table->string('linked_client_id')->nullable()->index();
            $table->string('client_id')->nullable();
            $table->string('account_holder_id')->nullable();

            $table->string('status', 50)->default('INACTIVE');
            $table->string('country')->nullable();
            $table->string('kyc_status')->nullable();

            $table->json('response_data')->nullable();

            $table->timestamps();

            $table->unique(
                ['provider_name', 'linked_client_id'],
                'vb_provider_client_unique'
            );
        });

        DB::table('openpayd_linked_clients')->orderBy('id')->chunk(200, function ($rows) {
            foreach ($rows as $row) {
                DB::table('virtual_bank_linked_clients')->insert([
                    'legacy_id' => $row->id,
                    'provider_name' => 'openpayd',
                    'linked_client_id' => $row->linked_client_id,
                    'client_id' => $row->client_id,
                    'account_holder_id' => $row->account_holder_id,
                    'status' => $row->status,
                    'country' => $row->country,
                    'kyc_status' => $row->kyc_status,
                    'response_data' => $row->response_data,
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('virtual_bank_linked_clients');
    }
};

