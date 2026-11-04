<?php

namespace App\Mail;

use App\Helpers\Common;
use Illuminate\Support\Facades\Auth;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NotifyRequestRejection extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;
    public $data;
    public $sender;
    public $requestType;
    public $userName;
    public $ownCompany;


    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data, $sender, $requestType, $userName, $ownCompany)
    {
        $this->data = $data;
        $this->sender = $sender;
        $this->requestType = $requestType;
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
            'row_id' => $this->data->id,
            'message' => "The (" . $this->requestType . ") Request has been declined by " . $this->userName,
            'url' => url($baseUrl . '/services-requests') //. strtolower($this->data->transaction_nature) . "/" . $this->data->id)
        ];

        Common::createNotification($this->data->user_id, $data);

        return $this->view('emails.system.notify-mobile-request-rejection')
            ->from($address, $name)
            ->replyTo($address, $name)
            ->subject($subject)
            ->with(['request' => $this->data]);
    }
}
