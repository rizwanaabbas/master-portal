<?php

namespace App\Mail;

use App\Helpers\Common;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class NotifyTransactionApproval extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;
    public $data;
    public $sender;
    public $userName;
    public $ownCompany;


    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data, $sender, $userName, $ownCompany)
    {
        $this->data = $data;
        $this->sender = $sender;
        $this->userName = $userName;
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
            'message' => "The (" . $this->data->transaction_nature . ") transaction has been approved by " .
                $this->userName .
                ".",
            'url' => url("finance-management/transactions/edit/" . strtolower($this->data->transaction_nature) . "/" . $this->data->id)
        ];

        Common::createNotification($this->data->created_by, $data);

        return $this->view('emails.system.notify-transaction-approval')
            ->from($address, $name)
            ->replyTo($address, $name)
            ->subject($subject)
            ->with(['transaction' => $this->data]);
    }
}
