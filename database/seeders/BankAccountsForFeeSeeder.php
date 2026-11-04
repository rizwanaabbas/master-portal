<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Helpers\Common;
use App\Models\Account;
use App\Models\Currency;

class BankAccountsForFeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $ownCompany = Common::getOwnCompanyData();

        // Seed Bank Accounts for Bank-Service Fee.
        self::seedData(
            ($ownCompany ? $ownCompany->short_name : 'Service') . ' Fee',
            $ownCompany ? $ownCompany->address : 'Kuala Lumpur, Malaysia',
            1,
            0,
            ($ownCompany ? $ownCompany->short_name : 'Service') . ' Fee Bank Account - ',
            10000000000000
        );

        // Seed Bank Accounts for Transaction Fee.
        self::seedData(
            'Correspondent Charges',
            $ownCompany ? $ownCompany->address : 'Kuala Lumpur, Malaysia',
            0,
            1,
            'TT Outward Charges Bank Account - ',
            30000000000000
        );
    }

    private function seedData($bank_name, $bank_address, $is_service_fee_bank, $is_trn_fee_bank, $account_title_prefix, $account_number_prefix)
    {
        $bank_id = DB::table('banks')->insertGetId([
            'name' => $bank_name,
            'address' => $bank_address,
            'status' => 'Approved',
            'is_service_fee_bank' => $is_service_fee_bank,
            'is_trn_fee_bank' => $is_trn_fee_bank
        ]);

        $currencies = Currency::all();
        $iteration = 1;

        foreach ($currencies as $currency) {
            $account_number = $account_number_prefix;

            if ($currency->is_crypto) {
                $account_number = $account_number_prefix + 10000000000000;
            }

            DB::table('accounts')->insert([
                'bank_id' => $bank_id,
                'type' => 'bank',
                'title' => $account_title_prefix . $currency->short,
                'account_number' => $account_number + $iteration,
                'account_currency' => $currency->id,
                'is_active' => 1,
                'is_crypto' => $currency->is_crypto ? $currency->is_crypto : 0,
            ]);

            $iteration++;
        }
    }
}
