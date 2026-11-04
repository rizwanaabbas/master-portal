<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Helpers\Common;
use App\Helpers\OpenpaydApis;
use App\Models\BeneficiaryRequiredFields;
use App\Models\Country;
use App\Models\Currency;
use Illuminate\Support\Facades\Log;

class SyncBeneficiaryDetailsWithOpenpayd extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:beneficiary-details-with-openpayd {currency=EUR} {country?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This is a scheduled command for syncing beneficiary details with Openpayd.';

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
        Log::info('SyncBeneficiaryDetailsWithOpenpayd command started.');

        // Check if OpenPayd module is enabled
        if (!Common::isModuleEnabled('OpenPayd')) {
            echo 'OpenPayd module is disabled. Exiting sync process.';
            return Command::SUCCESS;
        }

        $currencyCode = strtoupper($this->argument('currency'));

        Log::info("Sync started for currency: {$currencyCode}");

        if (!Common::isModuleEnabled('OpenPayd')) {
            echo 'OpenPayd module is disabled.';
            return Command::SUCCESS;
        }

        $currency = Currency::where('short', $currencyCode)
            ->where('virtual_iban_provider', 'openpayd')
            ->select('id', 'short')
            ->first();
       
        if (!$currency) {
            echo "Currency [{$currencyCode}] not found.";
            return Command::FAILURE;
        }

        // Fetch countries supporting SEPA
        if ($this->argument('country')) {
            $countries = Country::where('sepa_supported', true)
                ->where('iso', strtoupper($this->argument('country')))
                ->pluck('iso', 'id');
        } else {
            $countries = Country::where('sepa_supported', true)->pluck('iso', 'id');
        }
        
        if ($countries->isEmpty()) {
            Log::warning('No SEPA-supported countries found. Nothing to sync.');
            echo "\n No SEPA-supported countries found. Nothing to sync.";
            return Command::SUCCESS;
        }

        $beneficiaryTypes = ['RETAIL', 'CORPORATE'];
        $openpaydApi = new OpenpaydApis();

        try {
            foreach ($countries as $countryId => $countryCode) {
                foreach ($beneficiaryTypes as $type) {

                    $existing = BeneficiaryRequiredFields::where([
                        'country_id' => $countryId,
                        'currency_id' => $currency->id,
                        'beneficiary_type' => $type,
                    ])->first();

                    // Skip if already exists with required fields
                    if ($existing && !empty($existing->required_fields)) {
                        continue;
                    }

                    // Fetch required details from OpenPayd API
                    $requiredDetails = $openpaydApi->getBeneficiaryRequiredDetails(
                        $countryCode,
                        $currency->short,
                        $type
                    );

                    // Save or update entry
                    if ($requiredDetails) {
                        $requiredFields = new BeneficiaryRequiredFields();
                        $requiredFields::updateOrCreate(
                            [
                                'country_id' => $countryId,
                                'currency_id' => $currency->id,
                                'beneficiary_type' => $type,
                            ],
                            [
                                'required_fields' => json_encode($requiredDetails),
                            ]
                        );
                        
                        Log::info("Synced required details for {$type} - {$countryCode}");
                        echo "\n {$countryCode} => Synced required details for {$type} - {$countryCode} - {$currency->short}";

                    } else {
                        echo "\n ******* {$countryCode} => Details not found from API {$type} - {$countryCode} - {$currency->short} *********";
                        Log::warning("No required details returned for {$type} - {$countryCode}");
                    }

                   
                }
            }

            Log::info('Beneficiary required details synced successfully for all eligible countries.');
            echo 'Beneficiary required details synced successfully for all eligible countries.';
        } catch (\Throwable $e) {
            Log::error('Error during synchronization: ' . $e->getMessage(), [
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'trace' => $e->getTraceAsString(),
            ]);
            return Command::FAILURE;
        }

        Log::info('SyncBeneficiaryDetailsWithOpenpayd command completed successfully.');
        echo 'SyncBeneficiaryDetailsWithOpenpayd command completed successfully.';
        return Command::SUCCESS;
    }
}
