<?php

namespace App\Mail;

use App\Helpers\Common;
use Illuminate\Support\Facades\Auth;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NotifyClientForCardAssignment extends Mailable implements ShouldQueue
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

        $baseUrl = env('REACT_APP_BASE_URL');

        // create bell notification
        $data = [
            'row_id' => $this->data[0]->id,
            'message' => "You have been assigned a Card by " . $this->userName,
            'url' => url($baseUrl . '/card') //. strtolower($this->data->transaction_nature) . "/" . $this->data->id)
        ];

        Common::createNotification($this->data[0]->owner->users[0]->user->id, $data);

        return $this->view('emails.system.card.notify-client-for-card-assignment')
            ->from($address, $name)
            ->replyTo($address, $name)
            ->subject($subject)
            ->with(['links' => $this->data]);
    }
}
