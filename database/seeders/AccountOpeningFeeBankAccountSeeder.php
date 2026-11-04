<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;
use App\Helpers\Common;
use App\Models\Currency;
use Illuminate\Database\Seeder;

class AccountOpeningFeeBankAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $ownCompany = Common::getOwnCompanyData();

        // Seed Bank Accounts for Bank-Maintenance Fee.
        self::seedData(
            'Account Opening Fee (Correspondent Bank)',
            $ownCompany ? $ownCompany->address : 'Kuala Lumpur, Malaysia',
            1,
            'Account Opening Fee (Correspondent Bank) Account - ',
            70000000000000
        );
    }

    private function seedData($bank_name, $bank_address, $is_account_opening_fee_bank, $account_title_prefix, $account_number_prefix)
    {
        $bank_id = DB::table('banks')->insertGetId([
            'name' => $bank_name,
            'address' => $bank_address,
            'status' => 'Approved',
            'is_account_opening_fee_bank' => $is_account_opening_fee_bank,

        ]);

        $currencies = Currency::whereIn('short', ['AED', 'USD', 'EUR', 'RUB', 'USDT'])->get();
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
                'is_crypto' => $currency->is_crypto,
            ]);

            $iteration++;
        }
    }
}
