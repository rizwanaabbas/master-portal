<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ChangeDomainNameInDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'change:domain-name {from_domain_name} {to_domain_name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command is used to change the domain name in the database. Example: php artisan change:domain-name https://prod-static.centralasiabank.com https://prod-static.firstasiainvestmentbank.com';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if (env('CHANGE_DOMAIN_NAME') !== true) {
            $this->error('This command is disabled. Please set CHANGE_DOMAIN_NAME=true in your .env file.');
            return 1;
        }

        if (!$this->argument('from_domain_name') || !$this->argument('to_domain_name')) {
            $this->error('Please provide both from_domain_name and to_domain_name arguments.');
            return 1;
        }

        if ($this->argument('from_domain_name') === $this->argument('to_domain_name')) {
            $this->error('From domain name and to domain name cannot be the same.');
            return 1;
        }

        $this->info('Changing domain name in database...');
        $this->info('From domain name: ' . $this->argument('from_domain_name'));
        $this->info('To domain name: ' . $this->argument('to_domain_name'));
        $this->info('Please wait...');

        // Define the tables and columns to update
        $tables = [
            'accoun_transaction_request_docs',
            'client_company_files',
            'client_individual_files',
            'client_kyc_records',
            'currencies',
            'model_change_details',
            'own_companies',
            'sanctions_reports',
            'users',
            'vostro_banks_reports'
        ];
        $columns = [
            'accoun_transaction_request_docs' => ['url'],
            'client_company_files' => ['url'],
            'client_individual_files' => ['url'],
            'client_kyc_records' => ['passport_copy_url', 'selfie_photo_url', 'signature_photo_url'],
            'currencies' => ['logo'],
            'model_change_details' => ['old_value', 'new_value'],
            'own_companies' => ['logo', 'client_portal_light_logo', 'client_portal_dark_logo', 'client_manual', 'favicon', 'stamp', 'watermark', 'login_page_image'],
            'sanctions_reports' => ['url'],
            'users' => ['profile_photo_url'],
            'vostro_banks_reports' => ['file_url']
        ];
        $fromDomainName = $this->argument('from_domain_name');
        $toDomainName = $this->argument('to_domain_name');

        foreach ($tables as $table) {

            try {
                $tableColumns = $columns[$table] ?? null;

                if ($tableColumns) {
                    foreach ($tableColumns as $column) {
                        DB::table($table)
                            ->where($column, 'like', '%' . $fromDomainName . '%')
                            ->update([$column => DB::raw("REPLACE($column, '$fromDomainName', '$toDomainName')")]);
                    }
                }
                $this->info("Updated table: $table");
            } catch (\Exception $ex) {
                dd($ex->getMessage());
            }
        }

        return 0;
    }
}
