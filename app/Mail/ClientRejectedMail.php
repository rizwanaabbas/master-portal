<?php

namespace App\Mail;

use App\Helpers\ClientHelper;
use App\Helpers\Common;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ClientRejectedMail extends Mailable implements ShouldQueue
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

        // create bell notification
        $data = [
            'row_id' => $this->data->id,
            'message' => "Your account is incomplete, please update your profile accordingly",
            'url' => url($baseUrl)
        ];

        // get client user
        $user = ClientHelper::getUserByClientId($this->data->id);
        Common::createNotification($user->id, $data);

        return $this->view('emails.client.rejected')
            ->from($address, $name)
            ->replyTo($address, $name)
            ->subject($subject)
            ->with(['client' => $this->data]);
    }
}
