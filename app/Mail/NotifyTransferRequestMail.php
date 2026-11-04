<?php

namespace App\Mail;

use App\Helpers\Common;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;

class NotifyTransferRequestMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;
    public $data;
    public $sender;
    public $manager;
    public $userName;
    public $ownCompany;


    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data, $sender, $manager, $userName, $ownCompany)
    {
        $this->data = $data;
        $this->sender = $sender;
        $this->manager = $manager;
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
            'message' => "New (" . $this->data->transaction_nature . ") transaction has been created by " . $this->userName . ". Please review and approve",
            'url' => url("finance-management/transaction-requests/edit/" . strtolower($this->data->transaction_nature) . "/" . $this->data->id)
        ];

        Common::createNotification($this->manager->id, $data);

        return $this->view('emails.system.notify-transaction-request-transfer')
            ->from($address, $name)
            ->replyTo($address, $name)
            ->subject($subject)
            ->with([
                'transaction' => $this->data,
                'manager' => $this->manager,
            ]);
    }
}
