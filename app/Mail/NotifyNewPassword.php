<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NotifyNewPassword extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;
    public $data;
    public $sender;
    public $password;
    public $ownCompany;


    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data, $sender, $password, $ownCompany)
    {
        $this->data = $data;
        $this->sender = $sender;
        $this->password = $password;
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

        return $this->view('emails.system.notify-new-password')
            ->from($address, $name)
            ->replyTo($address, $name)
            ->subject($subject)
            ->with(['user' => $this->data, "password" => $this->password]);
    }
}
