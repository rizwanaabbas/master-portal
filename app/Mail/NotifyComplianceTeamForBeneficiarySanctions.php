<?php

namespace App\Mail;

use App\Helpers\Common;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyComplianceTeamForBeneficiarySanctions extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;
    public $data;
    public $sender;
    public $ownCompany;


    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data, $sender, $ownCompany)
    {
        $this->data = $data;
        $this->sender = $sender;
        $this->ownCompany = $ownCompany;
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

        // create bell notification
        $data = [
            'row_id' => $this->data->id,
            'message' => "Suspicious transaction",
            'url' => url("dashboards/compliance-management/cases")
        ];

        Common::createNotification($this->data->creator->id, $data);

        return $this->view('emails.system.notify-compliance-for-suspicious-transaction')
            ->from($address, $name)
            ->replyTo($address, $name)
            ->subject($subject)
            ->with(['trn' => $this->data, 'url' => $data['url'], 'title_needle' => "Transaction Requests"]);
    }
}
