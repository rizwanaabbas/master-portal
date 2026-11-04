<?php

namespace App\Mail;

use App\Helpers\Common;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NotifyDraftClientCreationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;
    public $data;
    public $sender;
    public $manager;
    public $ownCompany;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data, $sender, $manager, $ownCompany)
    {
        $this->data = $data;
        $this->sender = $sender;
        $this->manager = $manager;
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
            'message' => "The client profile (" . $this->data->name . ") has been submitted for approval",
            'url' => url("/client-management/clients/" . $this->data->type . "/edit/" . $this->data->id)
        ];

        Common::createNotification($this->manager->id, $data);

        return $this->view('emails.system.notify-draft-client-creation')
            ->from($address, $name)
            ->replyTo($address, $name)
            ->subject($subject)
            ->with([
                'client' => $this->data,
                'manager' => $this->manager,
            ]);
    }
}
