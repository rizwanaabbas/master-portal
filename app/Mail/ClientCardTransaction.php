<?php

namespace App\Mail;

use Illuminate\Support\Facades\Mail;
use App\Helpers\ClientHelper;
use App\Helpers\Common;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;


class ClientCardTransaction extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;
    public $data;
    public $sender;
    public $txn_id;
    public $ownCompany;
    public $text;
    public $send_notification;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data, $sender, $txn_id, $text, $ownCompany, $send_notification = true)
    {
        $this->data = $data;
        $this->sender = $sender;
        $this->txn_id = $txn_id;
        $this->ownCompany = $ownCompany;
        $this->text = $text;
        $this->send_notification = $send_notification;
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
            'row_id' => $this->txn_id,
            'subject' => $subject,
            'message' => $this->text,
            'url' => "/"
        ];

        // get client user
        //$user = ClientHelper::getUserByClientId($this->data->id);
        //Common::createNotification($user->id, $data);

        if ($this->send_notification) {
            foreach ($this->data->users as $user) {
                Common::createNotification($user->user_id, $data);
            }
        }

        $template = 'emails.client.card-transaction-alert';

        //dd($this->data);
        return $this->view($template)
            ->from($address, $name)
            ->replyTo($address, $name)
            ->subject($subject)
            ->with(['data' => $this->data, 'info' => $data]);
    }
}
