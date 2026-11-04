<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Client;
use App\Models\ClientCompany;
use App\Models\ClientIndividual;
use App\Helpers\EmailNotification;
use Carbon\Carbon;

class DocsExpiryNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notify:docs-expiry';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notify the Operations-Manager, 30 days before, about the Passport expiry for individual client and License expiry for company client.';

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
        $expiringLicenses = ClientCompany::where(function ($q) {
            $q->whereBetween('expiry_date', [
                Carbon::today(),
                Carbon::now()->addDays(30)->toDateString()
            ])
                ->orWhereDate('expiry_date', '<', Carbon::today());
        }) // where('expiry_date', '=', Carbon::now()->addDays(30)->toDateString())
            ->whereHas('client', function ($query) {
                $query->where('status', 'Approved');
            })
            ->get();

        $expiringPassports = ClientIndividual::where(function ($q) {
            $q->whereBetween('passport_expiry_date', [
                Carbon::today(),
                Carbon::now()->addDays(30)->toDateString()
            ])
                ->orWhereDate('passport_expiry_date', '<', Carbon::today());
        }) // where('passport_expiry_date', '=', Carbon::now()->addDays(30)->toDateString())
            ->whereHas('client', function ($query) {
                $query->where('status', 'Approved');
            })
            ->get();

        // Fire notifications for Company clients
        $data = self::prepareEmailData($expiringLicenses, 'License', 'company', 'expiry_date');


        // Fire notifications for individual clients
        $data = array_merge($data, self::prepareEmailData($expiringPassports, 'Passport', 'individual', 'passport_expiry_date'));
        // dd($data);
        EmailNotification::notifyOperationManagersForDocsExpiry($data);
    }

    protected function prepareEmailData($expiringDocs, $documentType, $clientType, $expiryField)
    {
        $clientsData = [];
        $counter = 0;

        foreach ($expiringDocs as $item) {
            $counter++;
            if ($counter > 1) {
                break;
            }

            $clientsData[$item->client_id]['id'] = $item->client_id;
            $clientsData[$item->client_id]['clientname'] = Client::find($item->client_id)['name'];
            $clientsData[$item->client_id]['document'] = $documentType;
            $clientsData[$item->client_id]['url'] = url('/client-management/clients/' . $clientType . '/edit/' . $item->client_id);
            $clientsData[$item->client_id]['expiryDate'] = $item[$expiryField];
            // dd($clientsData);
        }

        // dd(count($clientsData));

        return $clientsData;
    }
}
