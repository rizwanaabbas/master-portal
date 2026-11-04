<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DeleteExpiredIdempotencyKeys extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'delete:idempotency-keys';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete the 24hrs old Idempotency Keys.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            DB::beginTransaction();

            DB::table('idempotency_keys')
                ->where('created_at', '<', now()->subDay())
                ->delete();

            $tables = [
                'account_transaction_requests',
                'clients',
                'client_beneficiaries',
                'client_company_files',
                'client_individual_files',
                'client_kyc_records',
                'mobile_requests'
            ];

            // Nullify idempotency keys in each table
            foreach ($tables as $tableName) {
                DB::table($tableName)
                    ->where('created_at', '<', now()->subDay())
                    ->update(['idempotency_key' => null]);
            }

            DB::commit();

            return Command::SUCCESS;
        } catch (\Exception $e) {
            DB::rollBack();

            return Command::FAILURE;
        }
    }
}
