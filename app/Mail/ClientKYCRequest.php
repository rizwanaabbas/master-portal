<?php

namespace App\Mail;

use Illuminate\Support\Facades\Mail;
use App\Helpers\ClientHelper;
use App\Helpers\Common;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;


class ClientKYCRequest extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;
    public $data;
    public $sender;
    public $redirectUrl;
    public $ownCompany;
    public $is_emoney;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data, $sender, $redirectUrl, $ownCompany,$is_emoney)
    {
        $this->data = $data;
        $this->sender = $sender;
        $this->redirectUrl = $redirectUrl;
        $this->ownCompany = $ownCompany;
        $this->is_emoney = $is_emoney;
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
            'message' => "Account KYC required",
            'url' => "/"
        ];

        // get client user
        //$user = ClientHelper::getUserByClientId($this->data->id);
        //Common::createNotification($user->id, $data);
        if($this->is_emoney) {
            $template = 'emails.client.e-money-kyc-request';
        } else{
            $template = 'emails.client.kyc-request';
        }

        return $this->view($template)
            ->from($address, $name)
            ->replyTo($address, $name)
            ->subject($subject)
            ->with(['client' => $this->data]);
    }
}
