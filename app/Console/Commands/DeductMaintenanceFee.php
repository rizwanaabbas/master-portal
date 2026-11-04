<?php

namespace App\Console\Commands;

use App\Helpers\EmailNotification;
use App\Helpers\FeeHelper;
use App\Models\Client;
use App\Models\MaintenanceFeeReport;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DeductMaintenanceFee extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deduct:maintenance-fee';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deduct maintenance fee, been defined against each currency, from all clients.';

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
        $date = Carbon::now()->format('Y-m');

        $clients = MaintenanceFeeReport::whereRaw("DATE_FORMAT(maintenance_fee_reports.created_at, '%Y-%m') = ?", [$date])->get();

        if ($clients->count() > 0) {
            dd('The cron job has been run for this month once.');
        }

        $records = MaintenanceFeeReport::whereRaw("DATE_FORMAT(maintenance_fee_reports.created_at, '%Y-%m') = ?", [Carbon::parse($date)->subMonth()->format('Y-m')])->get();
        $previous_fee_report = [];

        foreach ($records as $record) {
            $previous_fee_report[$record->client_id] = [
                'account_id' => $record->account_id,
                'amount' => $record->amount,
                'have_paid' => $record->have_paid,
                'previous_pending_months' => $record->previous_pending_months
            ];
        }

        // Get approved clients
        $clients = Client::select(
            "clients.id as client_id",
            "clients.type",
        )
            ->where("clients.status", "Approved")
            ->where("clients.charge_maintenance_fee", true)
            ->where("clients.is_bank", false)
            ->where("clients.parent_id", 0)
            ->get();

        $data = FeeHelper::prepareMaintenanceFeeDeduction($clients, $previous_fee_report);

        FeeHelper::storeMaintenanceFeeReport($data['clients'], $data['date_charged']);

        $paidCount = array_filter($data['clients'], function ($client, $key) {
            if ($key != 'pending_for_3_months') {
                return $client['have_paid'] == true;
            }
        }, ARRAY_FILTER_USE_BOTH);

        $unpaidCount = array_filter($data['clients'], function ($client, $key) {
            if ($key != 'pending_for_3_months') {
                return $client['have_paid'] == false;
            }
        }, ARRAY_FILTER_USE_BOTH);

        echo "Fee deduction completed!";

        // if (count($data) > 0) {
        $clientsPendingFor3Months = [];

        if (isset($data['clients']['pending_for_3_months'])) {
            $clientIds = [];

            foreach ($data['clients'] as $key => $client) {
                if (isset($client['pending_for_3_months'])) {
                    $clientIds[] = $key;
                }
            }

            $clientsPendingFor3Months = Client::select('id', 'name', 'status')->whereIn('id', $clientIds)->get();

            // EmailNotification::maintenanceFee3MonthsPendingAlertForFinanceManager($clients);
        }

        EmailNotification::maintenanceFeeAlertForFinanceManager(['paid_count' => count($paidCount), 'unpaid_count' => count($unpaidCount)], $clientsPendingFor3Months);
        // }


        return 0;
    }
}
