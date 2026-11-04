<?php

namespace App\Mail;

use App\Helpers\Common;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NotifyClientforCardUnblockRejection extends Mailable implements ShouldQueue
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

        $baseUrl = env('REACT_APP_BASE_URL');

        $data = [
            'row_id' => $this->data->id,
            'message' => "Your card unblock request is incomplete.",
            'url' => url($baseUrl . '/card')
        ];

        Common::createNotification($this->data->created_by, $data);

        return $this->view('emails.system.notify-client-for-card-unblock-rejection')
            ->from($address, $name)
            ->replyTo($address, $name)
            ->subject($subject)
            ->with(['trn' => $this->data, 'url' => $data['url']]);
    }
}
