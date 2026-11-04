<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SeedNaicsCodesToDropdowns extends Migration
{
    public function up()
    {
        $naicsCodes = [
            '425120' => 'Wholesale Trade Agents and Brokers',
            '441110' => 'New Car Dealers',
            '441120' => 'Used Car Dealers',
            '522190' => 'Other Depository Credit Intermediation',
            '522220' => 'Sales Financing',
            '522291' => 'Consumer Lending',
            '522292' => 'Real Estate Credit',
            '522293' => 'International Trade Financing',
            '522320' => 'Financial Transactions Processing, Reserve, and Clearinghouse Activities',
            '522390' => 'Other Credit Intermediation',
            '523120' => 'Securities Brokerage',
            '523130' => 'Commodity Contracts Dealing',
            '523210' => 'Securities and Commodity Exchanges',
            '523920' => 'Portfolio Management',
            '523930' => 'Investment Advice',
            '523999' => 'Miscellaneous Financial Investment Activities',
            '524113' => 'Direct Life Insurance Carriers',
            '524210' => 'Insurance Agencies and Brokerages',
            '525120' => 'Other Insurance Funds',
            '525910' => 'Open-End Investment Funds',
            '525990' => 'Other Financial Vehicles',
            '531210' => 'Offices of Real Estate Agents and Brokers',
            '533110' => 'Lessors of Nonfinancial Intangible Assets (except Copyrighted Works)',
            '541214' => 'Payroll Services',
            '551111' => 'Offices of Bank Holding Companies',
            '561450' => 'Credit Bureaus',
        ];

        $data = [];
        $now = Carbon::now();

        $adminUser = DB::table('users')->orderBy('id')->first();
        $adminId = $adminUser ? $adminUser->id : null;
        
        foreach ($naicsCodes as $code => $description) {
            $data[] = [
                'type'       => 'NAICS_CODE',
                'value'      => $code,
                'text'       => $description,
                'created_by' => $adminId,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('dropdown_configurations')->insert($data);
    }

    public function down()
    {
        DB::table('dropdown_configurations')
            ->where('type', 'NAICS_CODE')
            ->delete();
    }
}
