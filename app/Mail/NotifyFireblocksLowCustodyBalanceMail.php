<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NotifyFireblocksLowCustodyBalanceMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $data;
    public $sender;
    public $ownCompany;

    public function __construct($data, $sender, $ownCompany)
    {
        $this->data = $data;
        $this->sender = $sender;
        $this->ownCompany = $ownCompany;
        $this->onQueue(env('QUEUE_PREFIX'));
    }

    public function build()
    {
        $address = $this->sender['email'];
        $subject = $this->sender['subject'];
        $name = $this->sender['name'];

        return $this->view('emails.system.notify-fireblocks-low-custody-balance')
            ->from($address, $name)
            ->replyTo($address, $name)
            ->subject($subject)
            ->with([
                'transaction' => $this->data,
            ]);
    }
}
