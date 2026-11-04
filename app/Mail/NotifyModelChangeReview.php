<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NotifyModelChangeReview extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;
    public $user;
    public $data;
    public $sender;
    public $status;
    public $approver;
    public $ownCompany;


    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($user, $data, $sender, $status, $approver, $ownCompany)
    {
        $this->user = $user;
        $this->data = $data;
        $this->sender = $sender;
        $this->status = $status;
        $this->approver = $approver;
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

        return $this->view('emails.system.notify-model-change-review')
            ->from($address, $name)
            ->replyTo($address, $name)
            ->subject($subject)
            ->with([
                'user' => $this->user,
                'updates' => $this->data,
                "status" => $this->status,
                "approver" => $this->approver,
            ]);
    }
}
