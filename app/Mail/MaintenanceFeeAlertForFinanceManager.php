<?php

namespace App\Mail;

use App\Helpers\Common;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MaintenanceFeeAlertForFinanceManager extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;
    public $data;
    public $sender;
    public $manager;
    public $ownCompany;
    public $clientsPendingFor3Months;


    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data, $sender, $manager, $ownCompany, $clientsPendingFor3Months)
    {
        $this->data = $data;
        $this->sender = $sender;
        $this->manager = $manager;
        $this->ownCompany = $ownCompany;
        $this->clientsPendingFor3Months = $clientsPendingFor3Months;
        $queuePrefix = env('QUEUE_PREFIX');

        $this->onQueue($queuePrefix);
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $address = $this->sender['email'];
        $subject = $this->sender['subject'];
        $name = $this->sender['name'];
        $url = url('/reports-management/maintenance-fee-report');

        // create bell notification
        $data = [
            'row_id' => $this->manager->id,
            'message' => 'Monthly Maintenance fee deduction report.',
            'url' => $url
        ];

        Common::createNotification($this->manager->id, $data);

        return $this->view('emails.system.maintenance-fee-alert')
            ->from($address, $name)
            ->replyTo($address, $name)
            ->subject($subject)
            ->with(['data' => $this->data, 'clientsPendingFor3Months' => $this->clientsPendingFor3Months, 'url' => $url]);
    }
}
